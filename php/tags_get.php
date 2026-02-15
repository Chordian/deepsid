<?php
/**
 * DeepSID
 *
 * Get two arrays; one with all tags and one with tags for a specific file.
 * 
 * @uses		$_GET['fullname']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {
	$db = $account->GetDB();

	// Get a list of all tags
	$select = $db->query('SELECT id, name, type FROM tags_info ORDER BY name');
	$select->setFetchMode(PDO::FETCH_OBJ);

	$all_tags = array();
	foreach ($select as $row) {
		array_push($all_tags, array(
			'id' =>				$row->id,
			'name' =>			$row->name,
			'type' =>			$row->type
		));
	}

	// Get the ID of this file
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_GET['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	$file_id = $select->rowCount() ? $select->fetch()->id : 0;

	// Get a list of the tags ID numbers used by this file ID
	$select = $db->query('SELECT tags_id FROM tags_lookup where files_id = '.$file_id);
	$select->setFetchMode(PDO::FETCH_OBJ);

	$sid_tags = array();
	foreach ($select as $row)
		$sid_tags[] += $row->tags_id;

	// Get the START and END tag ID numbers for "bracket" connection
	$select = $db->query('SELECT tags_id, end_id FROM tags_lookup'.
		' WHERE files_id = '.$file_id.' AND end_id != 0 LIMIT 1');
	$select->setFetchMode(PDO::FETCH_OBJ);
	$row = $select->fetch();
	$start_id = $select->rowCount() ? $row->tags_id : 0;
	$end_id = $select->rowCount() ? $row->end_id : 0;

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'all' => $all_tags, 'sid' => $sid_tags, 'id' => $file_id, 'start' => $start_id, 'end' => $end_id));
?>