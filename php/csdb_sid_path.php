<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and get the page for the specified ID, find one
 * SID HVSC path on it, and return it if found.
 * 
 * This is called in a JavaScript loop to populate the forum thread links.
 * 
 * If the path returned is empty, either a SID path did not exist or there were
 * more than one SID file. The jQuery code can then use the name returned to
 * instead make the link look a little prettier (still an improvement).
 * 
 * @uses		$_GET['type'] - can be 'release' or 'sid'
 * @uses		$_GET['id']
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['type']) || !isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variable.')));

// Get the XML from the CSDb web service
$xml = file_get_contents('https://csdb.dk/webservice/?type='.$_GET['type'].'&id='.$_GET['id']);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'path' => 'N/A')));
$csdb = simplexml_load_string(utf8_decode($xml));

switch ($_GET['type']) {
	case 'sid':
		die(json_encode(array('status' => 'ok','path' => (isset($csdb->SID->HVSCPath) ? $csdb->SID->HVSCPath : ''), 'name' => $csdb->SID->Name)));
	case 'release':
		if (isset($csdb->Release->UsedSIDs) && count($csdb->Release->UsedSIDs->SID) == 1)
			die(json_encode(array('status' => 'ok', 'path' => (isset($csdb->Release->UsedSIDs->SID->HVSCPath) ? $csdb->Release->UsedSIDs->SID->HVSCPath : ''), 'name' => $csdb->Release->Name)));
		else
			die(json_encode(array('status' => 'ok', 'path' => '', 'name' => $csdb->Release->Name)));
	default:
		die(json_encode(array('status' => 'error', 'message' => 'Unknown type "'.$_GET['type'].'" specified.')));
}
?>