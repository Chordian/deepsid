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
$xml = file_get_contents('https://csdb.dk/webservice/?type=forum&id=1&roomid='.$_GET['room'].'&topicid='.$_GET['topic']);
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
	$comment = str_replace(str_repeat('-', 50), str_repeat('-', 10), $comment);

	$handle 	= $post->Author->Handle;
	$registered	= $post->Author->RegisteredDate;
	$scid		= isset($post->Author->ScenerId) ? $post->Author->ScenerId : 0;
	$uid		= 0; // @todo Perff needs to add this to the XML data.

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
	$fn = str_replace('_High Voltage SID Collection/', '', $hvsc_folder);
	$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
	$fn = strtolower(str_replace('/', '_', $fn));
	$thumbnail = 'images/composers/'.$fn.'.jpg';
	if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';

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
			(!empty($hvsc_folder) ? '<a href="'.HOST.'?file=/'.$hvsc_folder.'"><img class="avatar" src="'.$thumbnail.'" alt="" /></a>' : '').
			'<span class="count pm"><a href="https://csdb.dk/privatemessages/sendmessage.php?userid='.$uid.'&selectdone.x=1" target="_blank">PM</a></span>'.
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
	'<table class="comments">'.$rows.'</table>'.
	'<button id="csdb-post-reply" data-roomid="'.$_GET['room'].'" data-topicid="'.$_GET['topic'].'">Post Reply</button><br />';

// Build the sticky header HTML for the '#sticky' DIV
$arrow = '<img class="arrow" src="images/composer_arrowright.svg" alt="" />';
$sticky = '<h2 style="display:inline-block;margin-top:0;">Forums '.$arrow.' '.$csdb->Forum->Room->RoomName.' '.$arrow.' <div class="topic ellipsis" title="'.$csdb->Forum->Room->Topic->TopicName.'">'.$csdb->Forum->Room->Topic->TopicName.'</div></h2>'.
	'<div class="corner-icons">'.
		'<a href="https://csdb.dk/forums/?roomid='.$_GET['room'].'&topicid='.$_GET['topic'].'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>';

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i><button class="to-top" title="Scroll back to the top" style="display:none;"><img src="images/to_top.svg" alt="" /></button>'));
?>