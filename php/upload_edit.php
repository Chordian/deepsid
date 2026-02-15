<?php
/**
 * DeepSID
 *
 * This script initiates the editing of an existing 'SID Happens' file that has
 * been uploaded earlier. The existing information for the array is obtained
 * from the two rows in the database and returned. The upload wizard then
 * displays the information in a few steps.
 * 
 * @uses		$_GET['fullname']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to edit SID files.')));

try {
	$db = $account->GetDB();
	
	// Get all general file info
	$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_GET['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not read the general database entry for the "'.$_GET['fullname'].'" file.')));

	$general = $select->fetch();

	// Get special 'SID Happens' info
	$select = $db->query('SELECT composers_id, uploaded FROM uploads WHERE files_id = '.$general->id.' LIMIT 1');
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not read the special database entry for the "'.$_GET['fullname'].'" file.')));

	$special = $select->fetch();

	// And get the full path to the composer profile (if set)
	$profile = 'unset';
	if ($special->composers_id) {
		$select = $db->query('SELECT fullname FROM composers WHERE id = '.$special->composers_id.' LIMIT 1');
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not read the profile path for the "'.$_GET['fullname'].'" file.')));

		$profile = $select->fetch()->fullname;
	}

	$info = array(
		'fullname' =>		$general->fullname,
		'player' =>			$general->player,
		'lengths' => 		$general->lengths,
		'type' => 			$general->type,
		'version' => 		$general->version,
		'playertype' =>		$general->playertype,
		'playercompat' =>	$general->playercompat,
		'clockspeed' =>		$general->clockspeed,
		'sidmodel' =>		$general->sidmodel,
		'dataoffset' =>		$general->dataoffset,
		'datasize' => 		$general->datasize,
		'loadaddr' => 		$general->loadaddr,
		'initaddr' => 		$general->initaddr,
		'playaddr' => 		$general->playaddr,
		'subtunes' => 		$general->subtunes,
		'startsubtune' => 	$general->startsubtune,
		'name' => 			$general->name,
		'author' => 		$general->author,
		'copyright' => 		$general->copyright,
		'stil' =>			$general->stil,
		'csdbtype' =>		$general->csdbtype,
		'csdbid' =>			$general->csdbid,
		'profile' =>		$profile,
		'uploaded' =>		$special->uploaded,
	);

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'info' => $info));
?>