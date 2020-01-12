<?php
/**
 * DeepSID
 *
 * This script us used both for finalizing editing or uploading.
 * 
 * EDIT:
 * 
 * The existing two rows in the database are updated with the new information
 * about the profile, CSDb ID, song lengths and custom STIL text.
 * 
 * UPLOAD:
 * 
 * Finally move the new SID file in the public upload folder and also create
 * the two new rows in the database.
 * 
 * This is called when the upload wizard is finished. The 'upload_new.php'
 * script should have been called earlier by the upload wizard to upload the
 * new file to a temporary location and gather information about the profile,
 * CSDb ID, song lengths and custom STIL text.
 * 
 * @uses		$_POST['info']		the updated info array
 */

require_once("class.account.php"); // Includes setup

define('PATH_UPLOADS', '_SID Happens/');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to edit/upload SID files.')));

$info = $_POST['info'];

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Is this editing or uploading?
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname' => $info['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount()) {
		
		// EDIT

		$files_id = $select->fetch()->id;

		$array = explode('/', $info['fullname']);
		$filename = end($array);

		// Update the general database row
		$update = $db->prepare('UPDATE hvsc_files SET
				lengths 		= :lengths,
				stil 			= :stil,
				csdbtype 		= :csdbtype,
				csdbid 			= :csdbid
			WHERE fullname = :fullname LIMIT 1');
		$update->execute(array(
				':lengths'		=> $info['lengths'],
				':stil'			=> $info['stil'],
				':csdbtype'		=> $info['csdbid'] ? 'release' : '',
				':csdbid'		=> $info['csdbid'],
				':fullname'		=> $info['fullname']
			));

		// Get the ID of the specified composer profile
		$select = $db->prepare('SELECT id FROM composers WHERE fullname = :profile LIMIT 1');
		$select->execute(array(':profile' => str_replace('HVSC/', '_High Voltage SID Collection/', $info['profile'])));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$composers_id = $select->rowCount() ? $select->fetch()->id : 0;

		// Update the composer profile in the special database row
		$update = $db->query('UPDATE uploads SET composers_id = '.$composers_id.' WHERE files_id = '.$files_id.' LIMIT 1');

		// Finally log it
		$account->LogActivity('User "'.$account->UserName().'" edited the "'.$filename.'" file');

	} else {

		// UPLOAD

		// Move the new SID file to the proper location
		rename('../temp/upload/'.$info['filename'], ROOT_HVSC.'/'.PATH_UPLOADS.$info['filename']);

		// Add a new general database row for the new SID file
		$insert = $db->prepare('INSERT INTO hvsc_files(
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
				stil,
				csdbtype,
				csdbid
			) VALUES (
				:fullname,
				:player,
				:lengths,
				:type,
				:version,
				"Normal built-in",
				:playercompat,
				:clockspeed,
				:sidmodel,
				:dataoffset,
				:datasize,
				:loadaddr,
				:initaddr,
				:playaddr,
				:subtunes,
				:startsubtune,
				:name,
				:author,
				:copyright,
				:stil,
				'.($info['csdbid'] ? '"release"' : '""').',
				:csdbid
			)');

		$insert->execute(array(
				':fullname'		=> PATH_UPLOADS.$info['filename'],
				':player'		=> $info['player'],
				':lengths'		=> $info['lengths'],					// Modified by upload wizard
				':type'			=> $info['type'],
				':version'		=> $info['version'],
				':playercompat'	=> $info['playercompat'],
				':clockspeed'	=> $info['clockspeed'],
				':sidmodel'		=> $info['sidmodel'],
				':dataoffset'	=> $info['dataoffset'],
				':datasize'		=> $info['datasize'],
				':loadaddr'		=> $info['loadaddr'],
				':initaddr'		=> $info['initaddr'],
				':playaddr'		=> $info['playaddr'],
				':subtunes'		=> $info['subtunes'],
				':startsubtune'	=> $info['startsubtune'],
				':name'			=> $info['name'],
				':author'		=> $info['author'],
				':copyright'	=> $info['copyright'],
				':stil'			=> $info['stil'],						// Created by upload wizard
				':csdbid'		=> $info['csdbid'],						// Created by upload wizard
			));

		$files_id = $db->lastInsertId();

		if ($insert->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not create the general database entry for the "'.$info['filename'].'" file.')));

		// Get the ID of the specified composer profile
		$select = $db->prepare('SELECT id FROM composers WHERE fullname = :profile LIMIT 1');
		$select->execute(array(':profile' => str_replace('HVSC/', '_High Voltage SID Collection/', $info['profile'])));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$composers_id = $select->rowCount() ? $select->fetch()->id : 0;

		// Add the special database row that only the upload folder uses
		$insert = $db->query('INSERT INTO uploads(
				files_id,
				composers_id,
				uploaded
			) VALUES (
				'.$files_id.',
				'.$composers_id.',
				"'.date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).'"
			)');

		if ($insert->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not create the special database entry for the "'.$info['filename'].'" file.')));

		// Finally log it
		$account->LogActivity('User "'.$account->UserName().'" uploaded the "'.$info['filename'].'" file');
	}

} catch(PDOException $e) {
	$account->LogActivityError('upload_final.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok'));
?>