<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to GameBase64 entries (the links are all
 * contained in the database) or a specific entry sub page.
 * 
 * @uses		$_GET['fullname']	for a page with links to sub pages
 * 
 * 	- OR -
 * 
 * @uses		$_GET['id']			for a sub page with a specific entry
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// Have to set this up to catch an error from unserialize() as a proper exception
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler('exception_error_handler');

function ReadRawGB64($id) {

	$find_scr	= ':showscreenshot';
	$piece_scr	= 'gb64.com/Screenshots';

	try {
		// Read the raw HTML of that GameBase64 entry page
		$page = file_get_contents('http://www.gamebase64.com/game.php?id='.$id);
	} catch(ErrorException $e) {
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin:0;"><i>Uh... GameBase64? Are you there?</i></p><small>Come on, GB64, old buddy, don\'t let me down.</small>')));
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors(true); // Ignores those pesky PHP warnings
	$dom->loadHTML($page);

	$xpath = new DOMXPath($dom);

	// Find title
	$nodes = $xpath->query('//td/font[@size="4"]/..//b');
	$title = $nodes->item(0)->textContent;
	
	// Find publisher line ("1986, Mastertronic")
	$nodes = $xpath->query('//td/font[contains(text(), "Published:")]/..//b');
	$year = $nodes->item(0)->textContent;
	$company = $nodes->item(1)->textContent;

	// Find musician(s)
	$nodes = $xpath->query('//td/font[contains(text(), "Musician:")]/..//b');
	$musician = $nodes->item(0)->textContent;

	// Find graphics artist(s)
	$nodes = $xpath->query('//td/font[contains(text(), "Graphician:")]/..//b');
	$graphics = $nodes->item(0)->textContent;

	// Find programmer
	$nodes = $xpath->query('//td/font[contains(text(), "Programmer:")]/..//b');
	$programmer = $nodes->item(0)->textContent;
	
	// Find language
	$nodes = $xpath->query('//td/font[contains(text(), "Language:")]/..//b');
	$language = $nodes->item(0)->textContent;

	// Find genre (not always present)
	$nodes = $xpath->query('//td/font[contains(text(), "Genre:")]/..//b');
	$genre = isset($nodes->item(0)->textContent) ? $nodes->item(0)->textContent : '';
	
	// Find clone of (usually always empty)
	$nodes = $xpath->query('//td/font[contains(text(), "Clone Of:")]/..//b');
	$clone = $nodes->item(0)->textContent;

	// Find primary control
	$nodes = $xpath->query('//td/font[contains(text(), "Primary Control:")]/..//b');
	$pcontrol = $nodes->item(0)->textContent;

	// Find players
	$nodes = $xpath->query('//td/font[contains(text(), "Players:")]/..//b');
	$players = $nodes->item(0)->textContent;

	// Find comments (not always present)
	$nodes = $xpath->query('//td/font[contains(text(), "Comment:")]/..//b');
	$comments = isset($nodes->item(0)->textContent) ? ucfirst($nodes->item(0)->textContent) : '';
	
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
	} else if (strpos($page, $piece_scr)) {
		// There's only one screenshot in the "monitor" graphics
		$image_pos = stripos($page, $piece_scr) + strlen($piece_scr);
		$thumbnails = array(
			substr($page, $image_pos, stripos($page, '.png', $image_pos) - $image_pos + 4)
		);
	} else {
		// There are no screenshots at all
		$thumbnails = array('/noscreenshot.gif');
	}
	$thumbnails = array_reverse($thumbnails);		// Want the title screen to be first in line

	return array(
		'title'			=> $title,
		'year' 			=> $year,
		'company'		=> $company,
		'musician'		=> $musician,
		'graphics'		=> $graphics,
		'programmer'	=> $programmer,
		'language'		=> $language,
		'genre'			=> $genre,
		'clone'			=> $clone,
		'pcontrol'		=> $pcontrol,
		'players'		=> $players,
		'comments'		=> $comments,
		'thumbnails'	=> $thumbnails,
	);
}

/***** START *****/

if (isset($_GET['fullname'])) {
	// Prepare for a list of links
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		// Get the GB64 array from the database row
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
					$account->LogActivityError('gb64.php', $e);
					$account->LogActivityError('gb64.php', $array);
					die(json_encode(array('status' => 'fatal', 'html' => '<p>Something bad happened. Chordian will be able to see this in a log.</p><p>'.$e.'</p>'.$array)));
				}
				//$gb64 = array_reverse($gb64); // To get original game in top (most common order)
			}
		} else {
			$account->LogActivityError('gb64.php', 'No database info returned; $_GET[\'fullname\'] = '.$_GET['fullname']);
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find the information in the database.")));
		}
	} catch(PDOException $e) {
		$account->LogActivityError('gb64.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

	// If only one result then just show that as a sub page
	$page_id = count($gb64) == 1 ? $gb64[0]['id'] : 0;

} else if (isset($_GET['id'])) {
	// A specific sub page ID was specified
	$page_id = $_GET['id'];
	$gb64 = [1];
} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

if ($page_id) {

	/***** SUB PAGE *****/

	$data = ReadRawGB64($page_id);

	$published		= '<p><b>Published:</b><br />'.$data['year'].', '.$data['company'].'</p>';
	$musician		= (!empty($data['musician']) ? '<p><b>Music:</b><br />'.$data['musician'].'</p>' : '');
	$graphics		= (!empty($data['graphics']) ? '<p><b>Graphics:</b><br />'.$data['graphics'].'</p>' : '');
	$programmer		= (!empty($data['programmer']) ? '<p><b>Programming:</b><br />'.$data['programmer'].'</p>' : '');
	$language		= (!empty($data['language']) ? '<p><b>Language:</b><br />'.$data['language'].'</p>' : '');
	$genre			= (!empty($data['genre']) ? '<p><b>Genre:</b><br />'.$data['genre'].'</p>' : '');
	$clone			= (!empty($data['clone']) ? '<p><b>Clone of:</b><br />'.$data['clone'].'</p>' : '');
	$pcontrol		= (!empty($data['pcontrol']) ? '<p><b>Primary control:</b><br />'.$data['pcontrol'].'</p>' : '');
	$players		= (!empty($data['players']) ? '<p><b>Players:</b><br />'.$data['players'].'</p>' : '');
	$comments		= (!empty($data['comments']) ? '<p><b>Comments:</b><br />'.$data['comments'].'</p>' : '');

	$col_of_thumbnails = '';
	foreach($data['thumbnails'] as $thumbnail)
		$col_of_thumbnails .= '<img class="thumbnail-gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /> ';

	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-bottom:20px;">'.$data['title'].'</h2>'.
	(isset($_GET['id']) ? '<button id="go-back-gb64">Back</button>' : '').
	'<div class="corner-icons">'.
		'<a href="http://www.gamebase64.com/game.php?id='.$page_id.'" title="See this at GameBase64" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>'.
	'<table style="border:none;">
		<tr>
			<td style="padding:0;vertical-align:top;border:none;">
				<h3 style="margin-top:6px;">Game info</h3>'.
					$published.
					$musician.
					$graphics.
					$programmer.
					$language.
					$genre.
					$clone.
					$pcontrol.
					$players.
					$comments.'
			</td>
			<td style="width:340px;padding:0;border-right:none;vertical-align:top;text-align:right;"><p>'.
				$col_of_thumbnails.
			'</p></td>
		</tr>
	</table>';

} else {

	/***** LIST *****/

	$rows = '';

	foreach($gb64 as $entry) {

		$data = ReadRawGB64($entry['id']);

		$thumbnails = array_slice($data['thumbnails'], 0, 4);	// Maximum 4 thumbnails

		$line_of_thumbnails = '';
		foreach($thumbnails as $thumbnail)
			$line_of_thumbnails .= '<a class="gb64-list-entry" href="http://www.gamebase64.com/game.php?id='.$entry['id'].'" target="_blank" data-id="'.$entry['id'].'"><img class="gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /></a>';

		$rows .=
			'<tr>'.
				'<td class="info">'.
					'<a class="name gb64-list-entry" href="http://www.gamebase64.com/game.php?id='.$entry['id'].'" target="_blank" data-id="'.$entry['id'].'">'.$entry['name'].'</a><br />'.
					$data['year'].' '.$data['company'].'<br />'.
					'<span class="language">'.$data['language'].'</span>'.
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
}
echo json_encode(array('status' => 'ok', 'html' => $html, 'count' => count($gb64)));
?>