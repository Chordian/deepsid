<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to GameBase64 entries. The links are all
 * contained in the database.
 * 
 * @uses		$_GET['fullname']
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// Have to set this up to catch an error from unserialize() as a proper exception
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler('exception_error_handler');

if (isset($_GET['fullname'])) {
	// Get the GB64 array from the database row
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$select = $db->prepare('SELECT gb64 FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$_GET['fullname']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount()) {
			$array = $select->fetch()->gb64;
			if (empty($array))
				die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>No GameBase64 entries available.</i></p>')));
			else {
				// Format: [{name: "Air-Raid [Preview], id: 14006}, {...}]
				$array = preg_replace_callback( // UTF8 fix
					'!s:(\d+):"(.*?)";!s',
					function($m) {
						$len = strlen($m[2]);
						$result = "s:$len:\"{$m[2]}\";";
						return $result;
					},
					$array);
				try {
					$gb64 = unserialize($array);
				} catch(Exception $e) {
					die(json_encode(array('status' => 'fatal', 'html' => '<p>Something bad happened. Chordian might want to see this.</p><p>'.$e.'</p>'.$array)));
				}
				//$gb64 = array_reverse($gb64); // To get original game in top (most common order)
			}
		} else {
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find the information in the database.")));
		}
	} catch(PDOException $e) {
		die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

	// Collect HTML rows
$rows = '';

$find_scr	= ':showscreenshot';
$piece_scr	= 'gb64.com/Screenshots';

foreach($gb64 as $entry) {

	try {
		// Read the raw HTML of that GameBase64 entry page
		$page = file_get_contents('http://www.gamebase64.com/game.php?id='.$entry['id']);
	} catch(ErrorException $e) {
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin:0;"><i>Uh... GameBase64? Are you there?</i></p><small>Come on, GB64, old buddy, don\'t let me down.</small>')));
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors(true); // Ignores those pesky PHP warnings
	$dom->loadHTML($page);

	$xpath = new DOMXPath($dom);

	// Find publisher line ("1986, Mastertronic")
	$nodes = $xpath->query('//td/font[contains(text(), "Published:")]/..//b');
	$year = $nodes->item(0)->textContent;
	$company = $nodes->item(1)->textContent;

	// Find language
	$nodes = $xpath->query('//td/font[contains(text(), "Language:")]/..//b');
	$language = $nodes->item(0)->textContent;

	// Loop through each occurrence of the screenshot javascript call in the GB64 HTML
	// @todo This really should be converted to use the xpath stuff too.
	if (strpos($page, $find_scr)) {
		// There's a table row with several screenshots to choose among
		$last_pos = 0;
		$thumbnails = array();
		while (($last_pos = stripos($page, $find_scr, $last_pos)) !== false) {
			// Isolate the thumbnail path
			$image_pos = stripos($page, $piece_scr, $last_pos) + strlen($piece_scr);
			$thumbnails[] = substr($page, $image_pos, stripos($page, '.png', $image_pos) - $image_pos + 4);
			$last_pos = $last_pos + strlen($find_scr);
		}
	} else {
		// There's only one screenshot in the "monitor" graphics
		$image_pos = stripos($page, $piece_scr) + strlen($piece_scr);
		$thumbnails = array(
			substr($page, $image_pos, stripos($page, '.png', $image_pos) - $image_pos + 4)
		);
	}
	$thumbnails = array_reverse($thumbnails);		// Want the title screen to be first in line
	$thumbnails = array_slice($thumbnails, 0, 4);	// Maximum 4 thumbnails

	$line_of_thumbnails = '';
	foreach($thumbnails as $thumbnail) {
		$line_of_thumbnails .= '<a href="http://www.gamebase64.com/game.php?id='.$entry['id'].'" target="_blank"><img class="gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /></a>';
	}

	$rows .=
		'<tr>'.
			'<td class="info">'.
				'<a class="name" href="http://www.gamebase64.com/game.php?id='.$entry['id'].'" target="_blank">'.$entry['name'].'</a><br />'.
				$year.' '.$company.'<br />'.
				'<span class="language">'.$language.'</span>'.
			'</td>'.
			'<td class="thumbnail">'.
				$line_of_thumbnails.
			'</td>'.
		'</tr>';
}

// Now build the HTML
$html = '<h2 style="display:inline-block;margin-top:0;">GameBase64</h2>'.
	'<h3>'.count($gb64).' entr'.(count($gb64) > 1 ? 'ies' : 'y').' found</h3>'.
	'<table class="releases">'.
		$rows.
	'</table>';

echo json_encode(array('status' => 'ok', 'html' => $html, 'count' => count($gb64)));
?>