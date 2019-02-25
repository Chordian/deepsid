<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and get the release page for the specified ID,
 * find a SID HVSC path on it, then return an array for it.
 * 
 * This is called in a JavaScript loop to populate the placeholders inside one
 * competition folder found in the 'CSDb Music Competitions' parent.
 * 
 * @uses		$_GET['id']
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if (!isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variable.')));

// Get the XML from the CSDb web service
$xml = file_get_contents('https://csdb.dk/webservice/?type=release&id='.$_GET['id']);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'NOXML')));
$csdb = simplexml_load_string(utf8_decode($xml));

if (!isset($csdb->Release->UsedSIDs) || !isset($csdb->Release->UsedSIDs->SID->HVSCPath))
	die(json_encode(array('status' => 'NOSID')));
else if (count($csdb->Release->UsedSIDs->SID) > 1)
	die(json_encode(array('status' => 'MULTIPLE')));

// Example: MUSICIANS/A/Argon/Argon_Blues.sid
$path = '_High Voltage SID Collection'.$csdb->Release->UsedSIDs->SID->HVSCPath;

// THE FOLLOWING CODE WAS EXTRACTED AND ADAPTED FROM 'HVSC.PHP'

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$path));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$player = $lengths = $type = $version = $playertype = $playercompat = $clockspeed = $sidmodel = $name = $author = $copyright = $hash = $stil = '';
	$rating = $dataoffset = $datasize = $loadaddr = $initaddr = $playaddr = $subtunes = $startsubtune = $hvsc = 0;

	if ($select->rowCount()) {
		$row = $select->fetch();

		$player = 			$row->player;		// MoN/FutureComposer
		$lengths = 			$row->lengths;		// 6:47 0:46 0:04
		$type = 			$row->type;			// PSID										RSID
		$version = 			$row->version;		// 2.0										3.0
		$playertype =		$row->playertype;	// Normal built-in																	(only value seen)
		$playercompat =		$row->playercompat;	// C64 compatible							PlaySID									(typically for BASIC tunes)
		$clockspeed =		$row->clockspeed;	// PAL 50Hz									NTSC 60Hz, PAL / NTSC, Unknown
		$sidmodel =			$row->sidmodel;		// MOS6581									MOS8580, MOS6581 / MOS858, Unknown
		$dataoffset =		$row->dataoffset;	// 124										0
		$datasize =			$row->datasize;		// 4557
		$loadaddr =			$row->loadaddr;		// 57344
		$initaddr =			$row->initaddr;		// 57344
		$playaddr =			$row->playaddr;		// 57350
		$subtunes =			$row->subtunes;		// 3
		$startsubtune =		$row->startsubtune;	// 1
		$name =				$row->name;			// Alloyrun
		$author =			$row->author;		// Jeroen Tel
		$copyright =		$row->copyright;	// 1988 Starlight
		$hash =				$row->hash;			// 02df65150cbc4fa8fabf563b26c8cac4
		$stil =				$row->stil;			// (#1)<br />NAME: Title tune<br />(#2)<br />NAME: High-score<br />(#3)<br />NAME: Get-ready
		$hvsc =				$row->new;			// 0 (= 49)									50 and up
		
		if ($user_id) {
			// Does the user have any rating for this SID file?
			if (!empty($row->hash)) {
				// Search hash first (best; will catch it if set for a clone)
				$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND hash = "'.$row->hash.'" AND type = "FILE"');
				$select_rating->setFetchMode(PDO::FETCH_OBJ);
				$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
			}
			if (!$rating) {
				// Try again with direct table ID (some SID files doesn't have a hash value)
				$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND table_id = '.$row->id.' AND type = "FILE"');
				$select_rating->setFetchMode(PDO::FETCH_OBJ);
				$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
			}
		}
	}

	if ($sidmodel != 'MOS8580') $sidmodel = 'MOS6581'; // Always default to 6581 if not specifically 8580

	if (empty($player))
		$player = 'an unidentified player';
	else if ($player == 'MoN/Bjerregaard')
		$player = 'Bjerregaard';

	$stil = str_replace('<br />',	' ',						$stil);

	$stil = str_replace('<?>', '<small class="u1">?</small>?<small class="u2">?</small>', $stil);

	$stil = str_replace('ARTIST:',	'<br /><b>ARTIST:</b>',		$stil);
	$stil = str_replace('AUTHOR:',	'<br /><b>AUTHOR:</b>',		$stil);
	$stil = str_replace('COMMENT:',	'<br /><b>COMMENT:</b>',	$stil);
	$stil = str_replace('NAME:',	'<br /><b>NAME:</b>', 		$stil);
	$stil = str_replace('TITLE:',	'<br /><b>TITLE:</b>', 		$stil);

	$stil = preg_replace(['/\(#(\d+)\)/'], ['<hr /><div class="subtune">$1</div>'], $stil);

	// Make references to other HVSC tunes into redirect links (i.e. won't refresh the web page)
	$stil = preg_replace('/(\/DEMO[^\s].+\.sid|\/GAMES[^\s]+\.sid|\/MUSICIANS[^\s]+\.sid)/', '<a class="redirect" href="#">$1</a>', $stil);

	$file = array(
		'filename' =>		$path,
		'substname' =>		'',
		'player' =>			str_replace(array_keys($prettyPlayerNames), $prettyPlayerNames, $player), // Remember it reads the array multiple times!
		'lengths' => 		$lengths,
		'type' => 			$type,
		//'version' => 		$version,
		//'playertype' => 	$playertype,
		//'playercompat' =>	$playercompat,
		'clockspeed' => 	$clockspeed,
		'sidmodel' => 		$sidmodel,
		//'dataoffset' => 	$dataoffset,
		'datasize' => 		$datasize,
		'loadaddr' => 		$loadaddr,
		'initaddr' => 		$initaddr,
		'playaddr' => 		$playaddr,
		'subtunes' => 		$subtunes,
		'startsubtune' => 	$startsubtune,
		'name' => 			$name,
		'author' => 		$author,
		'copyright' => 		$copyright,
		//'hash' => 		$hash,
		'stil' => 			$stil,
		'rating' =>			$rating,
		'hvsc' =>			$hvsc,
		'symid' =>			0,
	);

} catch(PDOException $e) {
	$account->LogActivityError('csdb_compo_release.php', $e->getMessage());
	if (isset($file)) $account->LogActivityError('csdb_compo_release.php', 'File: '.$file);
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'file' => $file));
?>