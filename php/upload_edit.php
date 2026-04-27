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

if (!$account->checkLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to edit SID files.')));

try {
	$db = $account->getDB();
	
	// Get all general file info
	$select = $db->prepare('SELECT * FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
	$select->execute(array(':collection_path' => $_GET['fullname']));
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
		$select = $db->query('SELECT collection_path FROM composers WHERE id = '.$special->composers_id.' LIMIT 1');
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not read the profile path for the "'.$_GET['fullname'].'" file.')));

		$profile = $select->fetch()->collection_path;
	}

	$info = array(
		// No "_" in keys
		'fullname' =>		$general->collection_path,
		'player' =>			$general->player,
		'lengths' => 		$general->lengths,
		'type' => 			$general->type,
		'version' => 		$general->version,
		'playertype' =>		$general->player_type,
		'playercompat' =>	$general->player_compat,
		'clockspeed' =>		$general->clock_speed,
		'sidmodel' =>		$general->sid_model,
		'dataoffset' =>		$general->data_offset,
		'datasize' => 		$general->data_size,
		'loadaddr' => 		$general->load_addr,
		'initaddr' => 		$general->init_addr,
		'playaddr' => 		$general->play_addr,
		'subtunes' => 		$general->subtunes,
		'startsubtune' => 	$general->start_subtune,
		'name' => 			$general->name,
		'author' => 		$general->author,
		'copyright' => 		$general->copyright,
		'stil' =>			$general->stil,
		'csdbtype' =>		$general->csdb_type,
		'csdbid' =>			$general->csdb_id,
		'profile' =>		$profile,
		'uploaded' =>		$special->uploaded,
	);

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'info' => $info));
?>