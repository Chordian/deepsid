<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and build an HTML page with the results of the
 * competition that the SID tune somehow participated in.
 * 
 * @uses		$_GET['compo']
 * @uses		$_GET['id']
 * @uses		$_GET['mark'] - the ID of the release page to mark on the list
 */

require_once("setup.php");
require_once("csdb_compo.php");
require_once("csdb_comments.php");
require_once("countries.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['compo']) || !isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

$scener_handle = array();
$scener_id = array();

$csdb =					CompoGetXML($_GET['id']);
$compos =				CompoGetEntries($csdb);
$type_date_country =	CompoGetTypeDateCountry($csdb);
$event_image =			CompoGetImage($_GET['id']);
//$participants =			CompoGetTable($compos, $_GET['compo'], $_GET['mark']);
$participants =			CompoGetTable($compos, 'C64 Demo', -1, true);

$user_comments = isset($csdb->Event->UserComment)
	? CommentsTable('User comments', $csdb->Event->UserComment, $scener_handle, $scener_id)
	: '';

$comment_button = '<button id="csdb-comment" data-type="event" data-id="'.$_GET['id'].'">Comment</button><br />';

// Build the sticky header HTML for the '#sticky' DIV
$sticky = '<h2 style="display:inline-block;margin-top:0;">'.$csdb->Event->Name.'</h2>'.
	'<button id="go-back" class="compo">Back</button>'.
	'<div id="corner-icons">'.
		'<a href="https://csdb.dk/event/?id='.$csdb->Event->ID.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>';

// And now the body HTML for the '#page' DIV
$html = '<p style="position:relative;top:-20px;margin-top:16px;">'.$type_date_country.'</p>'.
	'<p style="position:relative;top:-12px;">'.$event_image.'</p>'.
	'<h3 style="margin-top:-8px;">'.$_GET['compo'].' Competition results:</h3>'.
	$participants.
	$user_comments.
	$comment_button;

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i><button id="to-top" title="Scroll back to the top" style="display:none;"><img src="images/to_top.svg" alt="" /></button>'));
?>