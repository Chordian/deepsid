<?php
/**
 * DeepSID
 *
 * Accept uploading one or more SID files for testing in the JS emulators.
 * 
 * @uses		$_FILES[count]
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$files_ext = array();

foreach($_FILES as $sid) {

	if (!isset($sid['error']) || is_array($sid['error']))
		die(json_encode(array('status' => 'error', 'message' => 'Invalid parameters.')));
	else if ($sid['size'] > 1000000)
		die(json_encode(array('status' => 'error', 'message' => 'File size limit exceeded.')));

	$file = @file_get_contents($sid['tmp_name']);
// @todo also need to check for MUS file...
	if ($file[0x1] !== 'S' || $file[0x2] !== 'I' || $file[0x3] !== 'D')
		die(json_encode(array('status' => 'error', 'message' => 'Invalid file format.')));
	file_put_contents('../temp/test/'.$sid['name'], $file);

	$byte = array_values(unpack('C*', $file));

	$name = $author = $copyright = '';
	for($pos = 0x16; $pos <= 0x35; $pos++) {
		$name .= $file[$pos] != "\u{0000}" ? utf8_decode($file[$pos]) : '';
		$author .= $file[$pos + 0x20] != "\u{0000}" ? utf8_decode($file[$pos + 0x20]) : '';
		$copyright .= $file[$pos + 0x40] != "\u{0000}" ? utf8_decode($file[$pos + 0x40]) : '';
	}

	$subtunes = $byte[0xE] * 256 + $byte[0xF];
	$load_addr = $byte[0x8] * 256 + $byte[0x9];
	$data_offset = $byte[0x6] * 256 + $byte[0x7];
	$version = $byte[0x5] * 1 == 0x4E ? '4E (WebSid only)' : $byte[0x5].'.'.$byte[0x4];

	array_push($files_ext, array(
		'filename' =>		$sid['name'],
		'player' =>			'an undetermined player',
		'lengths' => 		rtrim(str_repeat('20:00 ', $subtunes)),
		'type' => 			$file[0].'SID',
		'version' => 		$version,
		'datasize' => 		strlen($file) - $data_offset,
		'loadaddr' => 		$load_addr ? $load_addr : $byte[$data_offset + 1] * 256 + $byte[$data_offset],
		'initaddr' => 		$byte[0xA] * 256 + $byte[0xB],
		'playaddr' => 		$byte[0xC] * 256 + $byte[0xD],
		'subtunes' => 		$subtunes,
		'startsubtune' => 	$byte[0x10] * 256 + $byte[0x11],
		'name' => 			$name,
		'author' => 		$author,
		'copyright' => 		$copyright,
		'stil' => 			'<i>This is a temporary SID file for emulator testing.</i>',
	));
}

echo json_encode(array('status' => 'ok', 'files' => $files_ext));
?>