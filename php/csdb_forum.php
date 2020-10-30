<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and build a forum thread.
 * 
 * @uses		$_GET['room']	- ID; e.g. 14 for "C64 Composing"
 * @uses		$_GET['topic']	- also an ID number
 */

require_once("setup.php");
require_once("jbbcode/Parser.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['room']) || !isset($_GET['topic']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

// Get the XML from the CSDb web service
// NOTE: The "&id=1" must be there or it doesn't work. The actual number can be anything.
$xml = curl('https://csdb.dk/webservice/?type=forum&id=1&roomid='.$_GET['room'].'&topicid='.$_GET['topic']);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>')));
$csdb = simplexml_load_string(utf8_decode($xml));

$parser = new JBBCode\Parser();
$parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());

$rows = '';
$comments_array = array();
foreach($csdb->Forum->Room->Topic->Post as $post) {

	$time = substr($post->Time, 0, 10).' <span class="time">'.substr($post->Time, 11, 5).'</span>'; // YYYY-MM-DD HH:MM

	$comment = htmlspecialchars_decode($post->Text);

	// Shorten ------------------- lines typically used for competition results
	//$comment = str_replace(str_repeat('-', 50), str_repeat('-', 10), $comment);

	// Figure out handle, and if we get it, store ID too as repeated use doesn't have handle along with it
	$handle = '';
	if (isset($post->Author->CSDbUser->Handle))
		$handle = $post->Author->CSDbUser->Handle;
	else if (isset($post->Author->CSDbUser->CSDbEntry->Handle->Handle))
		$handle = $post->Author->CSDbUser->CSDbEntry->Handle->Handle;
	else if (isset($post->Author->CSDbUser->Login))
		$handle = $post->Author->CSDbUser->Login;

	$user_id = $post->Author->CSDbUser->ID; // This ID can't be used to find scener ID but it's always available

	$scid = 0;
	if (isset($post->Author->CSDbUser->CSDbEntry)) {
		$scid = $post->Author->CSDbUser->CSDbEntry->Handle->ID;
		// There's a scener ID, store it for later reference
		$scener_id[(string)$user_id] = $scid;
	} else if(array_key_exists((string)$user_id, $scener_id))
		// We've obtained the scener ID for this scener before so get it now
		$scid = $scener_id[(string)$user_id];

	if (!empty($handle))
		// There's a handle for this scener; store it for later reference
		$scener_handle[(string)$user_id] = $handle;
	else if (array_key_exists((string)$user_id, $scener_handle))
		// We've had this scener before so we know the name
		$handle = $scener_handle[(string)$user_id];

	// If the scener ID is in the 'composers' database table then get his/her HVSC home folder
	$hvsc_folder = '';
	if ($scid) {
		try {
			if ($_SERVER['HTTP_HOST'] == LOCALHOST)
				$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
			else
				$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("SET NAMES UTF8");
	
			$select = $db->prepare('SELECT fullname FROM composers WHERE csdbid = :csdbid LIMIT 1');
			$select->execute(array(':csdbid'=>$scid));
			$select->setFetchMode(PDO::FETCH_OBJ);
	
			if ($select->rowCount())
				$hvsc_folder = $select->fetch()->fullname;

		} catch(PDOException $e) {
			// Just forget it then...
		}
	}

	// Figure out the name of the thumbnail (if it exists) for the composer
	if (!empty($hvsc_folder)) {
		$fn = str_replace('_High Voltage SID Collection/', '', $hvsc_folder);
		$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
		$fn = strtolower(str_replace('/', '_', $fn));
		$thumbnail = 'images/composers/'.$fn.'.jpg';
		if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
	} else {
		// Not a composer but there might be a thumbnail in a different folder
		$fn = preg_replace('/[^a-z0-9]+/i', ' ', $handle);
		$fn = trim($fn);
		$fn = str_replace(" ", "_", $fn);
		$fn = strtolower($fn);
		$thumbnail = 'images/csdb/'.$fn.'.jpg';
		if (!file_exists('../'.$thumbnail)) $thumbnail = '';
	}

	/***** REDIRECT (PLINKS) ADAPTATIONS - BEGIN *****/

	$dom = new DOMDocument();
	libxml_use_internal_errors(true);				// Ignores those pesky PHP warnings
	$dom->loadHTML('<span>'.$comment.'</span>');	// The <SPAN> hack avoids it being wrapped in <P> tags

	$xpath = new DOMXPath($dom);

	$anchor_paths = array(); // Used to ensure that a specific HVSC path is only adapted once

	$anchor_list = $xpath->query('//a');
	foreach($anchor_list as $a) {
		$url = $a->getAttribute('href');
		$hvsc_path = '';
		// Get HVSC path from all <a href> (if available) and use it as content + add "redirect" class
		if (preg_match('/(DEMO[^\s].+\.sid|GAMES[^\s]+\.sid|MUSICIANS[^\s]+\.sid)/', $url, $matches))
			// HVSC path with .sid extension and all
			$hvsc_path = '/'.$matches[0];
		else if (strpos(strtolower($url), 'c64.org/hvsc/') && substr(strtolower($url), -4) != '.sid')
			// Some posters use 'C64.org' HVSC links without a .sid extension in the end
			$hvsc_path = substr($url, strpos(strtolower($url), '/hvsc/') + 5).'.sid';
		if (!empty($hvsc_path)) {
			// We have a genuine HVSC path that DeepSID can play
			array_push($anchor_paths, strtolower($hvsc_path));
			$a->textContent = $hvsc_path;
			$a->setAttribute('class', 'redirect'); // It is now a "plink"
		}
	}

	$comment = $dom->saveHTML();

	// Find all HVSC paths (with or without leading slash) regardless of how they're wrapped
	// NOTE: For now, DEMO/GAMES/MUSICIANS must stay upper case as the regex may be a little too friendly.
	preg_match_all('/([\/|\\\]?DEMO[^\s].+\.sid|[\/|\\\]?GAMES[^\s]+\.sid|[\/|\\\]?MUSICIANS[^\s]+\.sid)/', $comment, $matches);
	for ($i = 0; $i < count($matches[0]); $i++) {
		// Some posters erroneously use backslashes or don't start with a slash
		$hvsc_path = str_replace('\\', '/', $matches[0][$i]);
		if (substr($hvsc_path, 0, 1) != '/') $hvsc_path = '/'.$hvsc_path;
		// Is this HVSC path one we've handled earlier?
		if (!in_array(strtolower($hvsc_path), $anchor_paths)) {
			// No, it must be a new straggler, so handle it now
			$comment = str_replace($matches[0][$i], '<a href="#" class="redirect">'.$hvsc_path.'</a>', $comment);
			array_push($anchor_paths, strtolower($hvsc_path));
		}
	}

	/***** REDIRECT (PLINKS) ADAPTATIONS - END *****/

	// Store the comment in an array in case someone quotes it later
	$comments_array[(string)$post->ID]['handle'] = $handle;
	$comments_array[(string)$post->ID]['comment'] = $comment;
	
	// Does THIS post quote something?
	if (isset($post->Quoting)) {
		// Prepend it now then
		$quoted = $comments_array[(string)$post->Quoting->Post->ID];
		$comment = '<span class="quote">Quote by '.$quoted['handle'].':</span><div class="quote">'.$quoted['comment'].'</div>'.$comment;
	}
	
	// Build the HTML row
	$rows .= '<tr>'.
		'<td class="user">'.
			($scid
				? '<a href="https://csdb.dk/scener/?id='.$scid.'" target="_blank"><b>'.$handle.'</b></a>'
				: '<b>'.(!empty($handle) ? $handle : '[?]').'</b>'
			).
			'<br /><span class="date">'.$time.'</span><br />'.
			(!empty($hvsc_folder)
				? '<a href="'.HOST.'?file=/'.$hvsc_folder.'"><img class="avatar" src="'.$thumbnail.'" alt="" /></a>'
				: (!empty($thumbnail) ? '<img class="avatar" src="'.$thumbnail.'" title="Not a composer" alt="" style="cursor:not-allowed;" />' : '')
			).
			'<span class="count pm"><a href="https://csdb.dk/privatemessages/sendmessage.php?userid='.$user_id.'&selectdone.x=1" target="_blank">PM</a></span>'.
			// (!empty($hvsc_folder) ? '<img class="home-folder" src="images/if_folder.svg" alt="" />' : '').
			(!empty($hvsc_folder) ? '<span class="count home-folder" title="Show DeepSID folder" data-home="'.$hvsc_folder.'"><img style="width:14px;" src="images/if_folder.svg" alt="" /></span>' : '').
		'</td>'.
		'<td class="comment">'.
			$comment.
		'</td>'.
	'</tr>';
}

// Wrap all the rows in a table
$html = '<b style="display:inline-block;margin-top:20px;">'.$csdb->Forum->Room->Topic->TopicName.'</b>'.
	'<span class="post-count">'.$csdb->Forum->Room->Topic->Replies.' replies</span>'.
	'<table class="thread comments">'.$rows.'</table>'.
	'<button id="csdb-post-reply" data-roomid="'.$_GET['room'].'" data-topicid="'.$_GET['topic'].'">Post Reply</button><br />';

// Build the sticky header HTML for the '#sticky' DIV
$arrow = '<img class="arrow" src="images/composer_arrowright.svg" alt="" />';
$sticky = '<h2 style="display:inline-block;margin-top:0;">Forums '.$arrow.' '.$csdb->Forum->Room->RoomName.' '.$arrow.' <div class="topic-wrap"><div class="topic" title="'.$csdb->Forum->Room->Topic->TopicName.'"><div class="topic-inner"><button id="topics" style="position:relative;float:right;margin:2px 0 0 8px;z-index:2;">Back</button>'.$csdb->Forum->Room->Topic->TopicName.'</div></div></div></h2>'.
	'<div class="corner-icons">'.
		'<a href="https://csdb.dk/forums/?roomid='.$_GET['room'].'&topicid='.$_GET['topic'].'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>';

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i><button class="to-top" title="Scroll back to the top" style="display:none;"><img src="images/to_top.svg" alt="" /></button>'));
?>