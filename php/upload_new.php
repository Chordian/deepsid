<?php
/**
 * DeepSID
 *
 * Accept uploading one new SID file in the public upload folder.
 * 
 * Information is gathered from the SID file format header and returned in an
 * array. The user can then edit some more information in a few wizard steps,
 * after which the updated information is passed on to another PHP script for
 * uploading the file properly and inserting new rows in the database.
 * 
 * If the file is not a SID file or the filename exists, it is denied.
 * 
 * @uses		$_FILES						object with file information
 * @uses		$_REQUEST['path']			where to upload the file
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("sid_id.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to upload SID files.')));

// Make sure we have a clean upload folder before adding a new file to it
foreach(glob('../temp/upload/*.sid') as $filename) {
	$file_age = time() - filectime($filename);
	// Files older than 20 minutes will be deleted
    if ($file_age > (20 * 60))
		unlink($filename);
}

$sid = $_FILES[0];
$path = $_REQUEST['path'].'/';

if (!isset($sid['error']) || is_array($sid['error']))
	die(json_encode(array('status' => 'error', 'message' => 'Invalid parameters.')));
else if ($sid['size'] > 1000000)
	die(json_encode(array('status' => 'error', 'message' => 'File size limit exceeded.')));

$file = @file_get_contents($sid['tmp_name']);
if ($file[0x1] !== 'S' || $file[0x2] !== 'I' || $file[0x3] !== 'D')
	die(json_encode(array('status' => 'error', 'message' => 'Invalid file format.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Make sure the filename uses the HVSC standard (e.g. 'laurel and hardy-2.sid' = 'Laurel_and_Hardy_2.sid')
	$filename = ucwords(str_replace('_', ' ', str_replace('-', ' ', $sid['name'])));
	$excluded = [' a ', ' n ', ' an ', ' the ', ' in ', ' for ', ' and ', ' nor ', ' but ', ' or ', ' yet ', ' so ', ' such ', ' as ', ' at ', ' around ', ' by ', ' after ', ' along ', ' for ', ' from ', ' of ', ' on ', ' to ', ' with ', ' without '];
	foreach($excluded as $no_cap)
		$filename = str_replace(ucwords($no_cap), strtolower($no_cap), $filename);
	$sid['name'] = str_replace(' ', '_', $filename);

	// Make sure the extension is all lower case
	$sid['name'] = substr($sid['name'], 0, -4).'.sid';

	// Make sure a file of the same name doesn't already exist in the database
	$exists = $db->query('SELECT 1 FROM hvsc_files WHERE fullname LIKE "'.$path.$sid['name'].'" LIMIT 1');
	if ($exists->rowCount())
		die(json_encode(array('status' => 'error', 'message' => 'There is already a SID file of that name here. Duplicate names are not allowed. Try renaming it first.')));

	// Upload the file to a temp folder until it is decided to move it
	// NOTE: Can't rely on 'tmp_name' since the PHP script deletes it when completed.
	file_put_contents('../temp/upload/'.$sid['name'], $file);

	$byte = array_values(unpack('C*', $file));

	$name = $author = $copyright = '';
	for($pos = 0x16; $pos <= 0x35; $pos++) {
		$name .= $file[$pos] != "\u{0000}" ? mb_convert_encoding($file[$pos], 'UTF-8', 'ASCII') : '';
		$author .= $file[$pos + 0x20] != "\u{0000}" ? mb_convert_encoding($file[$pos + 0x20], 'UTF-8', 'ASCII') : '';
		$copyright .= $file[$pos + 0x40] != "\u{0000}" ? mb_convert_encoding($file[$pos + 0x40], 'UTF-8', 'ASCII') : '';
	}

	$subtunes = $byte[0xE] * 256 + $byte[0xF];
	$load_addr = $byte[0x8] * 256 + $byte[0x9];
	$data_offset = $byte[0x6] * 256 + $byte[0x7];
	$version = $byte[0x5] * 1 == 0x4E ? '4E (WebSid only)' : $byte[0x5].'.'.$byte[0x4];
	$compatible = $byte[0x77] & 0b00000010 ? 'C64 BASIC' : 'C64 compatible';

	switch ($byte[0x77] & 0b00001100) {
		case 0b00000100:
			$clockspeed = 'PAL 50Hz';
			break;
		case 0b00001000:
			$clockspeed = 'NTSC 60Hz';
			break;
		case 0b00001100:
			$clockspeed = 'PAL / NTSC';
			break;
		default:
			$clockspeed = 'Unknown';
	}

	switch ($byte[0x77] & 0b00110000) {
		case 0b00010000:
			$sidmodel = 'MOS6581';
			break;
		case 0b00100000:
			$sidmodel = 'MOS8580';
			break;
		case 0b00110000:
			$sidmodel = 'MOS6581 / MOS8580';
			break;
		default:
			$sidmodel = 'Unknown';
	}

	$info = array(
		'fullname' =>		$path.$sid['name'],
		'filename' =>		$sid['name'],
		'player' =>			IdentifyPlayer($sid['tmp_name']),
		'lengths' => 		rtrim(str_repeat('20:00 ', $subtunes)),
		'type' => 			$file[0].'SID',
		'version' => 		$version,
		'playertype' =>		'Normal built-in',
		'playercompat' =>	$compatible,
		'clockspeed' =>		$clockspeed,
		'sidmodel' =>		$sidmodel,
		'dataoffset' =>		$data_offset,
		'datasize' => 		strlen($file) - $data_offset,
		'loadaddr' => 		$load_addr ? $load_addr : $byte[$data_offset + 1] * 256 + $byte[$data_offset],
		'initaddr' => 		$byte[0xA] * 256 + $byte[0xB],
		'playaddr' => 		$byte[0xC] * 256 + $byte[0xD],
		'subtunes' => 		$subtunes,
		'startsubtune' => 	$byte[0x10] * 256 + $byte[0x11],
		'name' => 			$name,
		'author' => 		$author,
		'copyright' => 		$copyright,
	);

} catch(PDOException $e) {
	$account->LogActivityError('upload_new.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'info' => $info));
?>