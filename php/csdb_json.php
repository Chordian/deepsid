<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and get info for any kind of type and ID.
 * 
 * @uses		$_GET['depth']				set to 1 if not specified (minimal information)
 * @uses		$_GET['type']				can be "release", "sid", "group", etc.
 * @uses		$_GET['id']
 * 
 * @used-by		browser.js
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['type']) || !isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

$depth = isset($_GET['depth']) ? $_GET['depth'] : '1';

// Get the XML from the CSDb web service
$xml = curl('https://csdb.dk/webservice/?type='.$_GET['type'].'&id='.$_GET['id'].'&depth='.$depth);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'info' => 'N/A')));

$csdb = simplexml_load_string($xml);
$csdb = json_decode(json_encode($csdb), true);

echo json_encode(array(
		'status'	=> 'ok',
		'csdb'		=> $csdb,
));
?>