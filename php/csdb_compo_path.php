<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and get the release page for the specified ID,
 * find a SID HVSC path on it, and return it if found.
 * 
 * This is called in a JavaScript loop to populate the 'Click to play' column
 * in the table with competition results from CSDb.
 * 
 * @uses		$_GET['id']
 * 
 * @used-by		browser.js
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variable.')));

// Get the XML from the CSDb web service
$xml = curl('https://csdb.dk/webservice/?type=release&id='.$_GET['id']);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'path' => 'N/A')));
$csdb = simplexml_load_string($xml);

$path = '<i>No SID file found</i>';
if (isset($csdb->Release->UsedSIDs)) {
	if (count($csdb->Release->UsedSIDs->SID) > 1)
		$path = '<i>Multiple SID files found</i>';
	else
		$path = isset($csdb->Release->UsedSIDs->SID->HVSCPath)
			? '<a href="#" class="compo-go redirect">'.$csdb->Release->UsedSIDs->SID->HVSCPath.'</a>' : $path;
}

echo json_encode(array('status' => 'ok', 'path' => $path));
?>