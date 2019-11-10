<?php
/**
 * DeepSID
 *
 * Accept uploading a single SID file for testing in the JS emulators.
 * 
 * @uses		$_FILES['sid']
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_FILES['sid']['error']) || is_array($_FILES['sid']['error']))
	die(json_encode(array('status' => 'error', 'message' => 'Invalid parameters.')));
else if ($_FILES['sid']['size'] > 1000000)
	die(json_encode(array('status' => 'error', 'message' => 'File size limit exceeded.')));

$file = file_get_contents($_FILES['sid']['tmp_name']);
if ($file[1] !== 'S' || $file[2] !== 'I' || $file[3] !== 'D')
	die(json_encode(array('status' => 'error', 'message' => 'Invalid file format.')));
file_put_contents('../temp/test/'.$_FILES['sid']['name'], $file);

echo json_encode(array('status' => 'ok', 'filename' => 'temp/test/'.$_FILES['sid']['name']));
?>