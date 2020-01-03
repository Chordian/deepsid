<?php
/**
 * DeepSID
 *
 * Accept uploading one new SID file in the public upload folder.
 * 
 * Information is gathered from the SID file format header and returned in an
 * array. The user can then edit some more information in a few wizard steps,
 * after which the final array of information is passed on to another PHP
 * script for inserting a new row in the database.
 * 
 * If the file is not a SID file or the filename exists, it is denied.
 * 
 * @uses		$_FILES
 */

 require_once("class.account.php"); // Includes setup

define('PATH_UPLOADS', '_File Uploads/');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$sid = $_FILES[0];

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

	// Make sure a file of the same name doesn't already exist in the database
	$exists = $db->query('SELECT 1 FROM hvsc_files WHERE fullname LIKE "'.PATH_UPLOADS.$sid['name'].'" LIMIT 1');
	if ($exists->rowCount())
		die(json_encode(array('status' => 'error', 'message' => 'There is already SID file of that name here. Duplicate names are not allowed. Try renaming it first.')));

	////// NO SAVE WHILE TESTING... //////file_put_contents(ROOT_HVSC.'/_File Uploads/'.$sid['name'], $file);

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

	$file = array(
		'fullname' =>		PATH_UPLOADS.$sid['name'],
		'filename' =>		$sid['name'],
		'player' =>			'an undetermined player',
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

// @todo WAIT UNTIL ASSOC + STIL EDIT STEPS ARE DONE (and probably in a different PHP file)
	// Add a new database entry for it
	// NOTE: A duplicate filename is blocked further above.
	/*$db->query('INSERT INTO hvsc_files (
			fullname,
			player,
			lengths,
			type,
			version,
			playertype,
			playercompat,
			clockspeed,
			sidmodel,
			dataoffset,
			datasize,
			loadaddr,
			initaddr,
			playaddr,
			subtunes,
			startsubtune,
			name,
			author,
			copyright,
		) VALUES('.
			'"'.PATH_UPLOADS.$file['filename'].'",'.
			'"'.$file['player'].'",'.
			'"'.$file['lengths'].'",'.
			'"'.$file['type'].'",'.
			'"'.$version.'",'.
			'"Normal built-in",'.
			'"'.$compatible.'",'.
			'"'.$clockspeed.'",'.
			'"'.$sidmodel.'",'.
			$data_offset.','.
			$file['datasize'].','.
			$file['loadaddr'].','.
			$file['initaddr'].','.
			$file['playaddr'].','.
			$subtunes.','.
			$file['startsubtune'].','.
			$name.','.
			$author.','.
			$copyright.','.
		')');*/

} catch(PDOException $e) {
	$account->LogActivityError('upload_new.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'file' => $file));
?>