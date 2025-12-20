<?php
/**
 * DeepSID
 *
 * Add one single tag to a specific file. The action is ignored if it already
 * exists for the file. This is used for automatic updating and doesn't require
 * that a user has logged in first.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['tag']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("tags_read.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['fullname']) || !isset($_POST['tag']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get the ID of this file
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not find "'.$_POST['fullname'].'" in the database')));
	$file_id = $select->fetch()->id;

	// Get the ID of the tag
	$select = $db->prepare('SELECT id FROM tags_info WHERE name = :name LIMIT 1');
	$select->execute(array(':name'=>$_POST['tag']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not find the "'.$_POST['tag'].'" tag in the database')));
	$tag_id = $select->fetch()->id;

	// Only add the database entry if it's not already applied to that file
	$select = $db->query('SELECT 1 FROM tags_lookup WHERE files_id = '.$file_id.' AND tags_id = '.$tag_id.' LIMIT 1');
	if ($select->rowCount() == 0) {
		$insert = $db->query('INSERT INTO tags_lookup (files_id, tags_id) VALUES('.$file_id.', '.$tag_id.')');
		// Report this to the CSV log file describing an activity only regarding tags
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/tags.txt',
			date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
			$_SERVER['REMOTE_ADDR'].','.
			'0,'.
			'[AUTO],'.
			$file_id.','.
			$_POST['fullname'].','.
			'ADD,'.
			$tag_id.','.
			$_POST['tag'].
		PHP_EOL, FILE_APPEND);
	}

	// Now get sorted arrays of the tag names and types used by this file right now
	$list_of_tags = array();
	$type_of_tags = array();
	$id_of_tags = array();
	$id_tag_start = $id_tag_end = 0;
	GetTagsAndTypes($file_id, $list_of_tags, $type_of_tags, $id_of_tags, $id_tag_start, $id_tag_end);

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'tags' => $list_of_tags, 'tagtypes' => $type_of_tags, 'tagids' => $id_of_tags, 'tagidstart' => $id_tag_start, 'tagidend' => $id_tag_end));
?>