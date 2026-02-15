<?php
/**
 * DeepSID
 *
 * Get information about a specific SID file.
 * 
 * @uses		$_GET['fullname']
 * 
 * @used-by		player.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['fullname']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'fullname\' as a GET variable.')));

if (substr($_GET['fullname'], -4) == '.mus') {

	// CGSC: Parse the file itself

	// If there's a .wds file we can use it for the STIL box
	$wds = @file_get_contents(str_replace('.mus', '.wds', '../hvsc/'.$_GET['fullname']));
	$stil = '';
	if ($wds) {
		$chars = unpack('C*', $wds);
		foreach(array_values($chars) as $char) {
			if ($char > 127) $char -= 128;
			$stil .= $char < 32 || $char > 126 
				? ($char == 13 ? '<br />' : ' ')
				: chr($char);
		}
	}

	$info['subtunes'] =		1;
	$info['startsubtune'] =	1;
	$info['name'] =			'N/A'; // Replaced by a JS function
	$info['author'] =		'';
	$info['copyright'] =	'';
	$info['stil'] =			$stil;

} else {

	// HVSC: Get the information from the database

	try {
		$db = $account->GetDB();

		$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$_GET['fullname']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$info = array();

		if ($select->rowCount()) {
			$row = $select->fetch();					// Example									Can also be

			$info['player'] =		$row->player;		// MoN/FutureComposer
			$info['lengths'] =		$row->lengths;		// 6:47 0:46 0:04
			$info['type'] = 		$row->type;			// PSID										RSID
			$info['version'] =		$row->version;		// 2.0										3.0
			$info['playertype'] =	$row->playertype;	// Normal built-in																	(only value seen)
			$info['playercompat'] =	$row->playercompat;	// C64 compatible							PlaySID									(typically for BASIC tunes)
			$info['clockspeed'] =	$row->clockspeed;	// PAL 50Hz									NTSC 60Hz, PAL / NTSC, Unknown
			$info['sidmodel'] =		$row->sidmodel;		// MOS6581									MOS8580, MOS6581 / MOS858, Unknown
			$info['dataoffset'] =	$row->dataoffset;	// 124										0
			$info['datasize'] =		$row->datasize;		// 4557
			$info['loadaddr'] =		$row->loadaddr;		// 57344
			$info['initaddr'] =		$row->initaddr;		// 57344
			$info['playaddr'] =		$row->playaddr;		// 57350
			$info['subtunes'] =		$row->subtunes;		// 3
			$info['startsubtune'] =	$row->startsubtune;	// 1
			$info['name'] =			$row->name;			// Alloyrun
			$info['author'] =		$row->author;		// Jeroen Tel
			$info['copyright'] =	$row->copyright;	// 1988 Starlight
			$info['hash'] =			$row->hash;			// 02df65150cbc4fa8fabf563b26c8cac4
			$info['stil'] =			$row->stil;			// (#1)<br />NAME: Title tune<br />(#2)<br />NAME: High-score<br />(#3)<br />NAME: Get-ready
		}

		// Always default to 6581 if not specifically 8580
		if ($info['sidmodel'] != 'MOS8580') $info['sidmodel'] = 'MOS6581';

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
}

echo json_encode(array('status' => 'ok', 'info' => $info));
?>