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

$file = @file_get_contents($_FILES['sid']['tmp_name']);
if ($file[0x1] !== 'S' || $file[0x2] !== 'I' || $file[0x3] !== 'D')
	die(json_encode(array('status' => 'error', 'message' => 'Invalid file format.')));
file_put_contents('../temp/test/'.$_FILES['sid']['name'], $file);

$name = $author = $copyright = '';
for($pos = 0x16; $pos <= 0x35; $pos++) {
	$name .= $file[$pos] != "\u{0000}" ? $file[$pos] : '';
	$author .= $file[$pos + 0x20] != "\u{0000}" ? $file[$pos + 0x20] : '';
	$copyright .= $file[$pos + 0x40] != "\u{0000}" ? $file[$pos + 0x40] : '';
}

$byte = array_values(unpack('C*', $file));
$subtunes = $byte[0xE] * 256 + $byte[0xF];

$files_ext = array();
array_push($files_ext, array(
	'filename' =>		$_FILES['sid']['name'],
	'player' =>			'an unidentified player',
	'lengths' => 		rtrim(str_repeat('10:00 ', $subtunes)),
	'type' => 			$file[0x0].'SID',
	'version' => 		$byte[0x4] * 256 + $byte[0x5],
	'datasize' => 		strlen($file) - ($byte[0x6] * 256 + $byte[0x7] - 2),
	'loadaddr' => 		$byte[0x8] * 256 + $byte[0x9],
	'initaddr' => 		$byte[0xA] * 256 + $byte[0xB],
	'playaddr' => 		$byte[0xC] * 256 + $byte[0xD],
	'subtunes' => 		$subtunes,
	'startsubtune' => 	$byte[0x10] * 256 + $byte[0x11],
	'name' => 			$name,
	'author' => 		$author,
	'copyright' => 		$copyright,
	'stil' => 			'Temporary SID file for emulator testing.',
));

echo json_encode(array('status' => 'ok', 'files' => $files_ext));
?>