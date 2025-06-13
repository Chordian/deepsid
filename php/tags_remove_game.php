<?php
/**
 * DeepSID
 *
 * Remove game related tags from a specific file. Used when the 'GameBase64'
 * tag is automatically added. The list of tags removed are:
 * 
 * 		'Game'
 * 		'Game Prev'
 * 
 * The 'GTW' tag will not be removed, however.
 * 
 * @uses		$_POST['fullname']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("tags_read.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['fullname']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variable.')));

/**
 * Removes the specified tag from the file.
 *
 * @param		int			$file_id			ID of file from the big table of files
 * @param		string		$name				Name of tag to be removed
 */
function RemoveTag($file_id, $name) {

	global $db;

	// Get the ID of the specified tag name
	$select = $db->query('SELECT id FROM tags_info WHERE name = "'.$name.'" LIMIT 1');
	$select->setFetchMode(PDO::FETCH_OBJ);
	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not find the "'.$name.'" tag in the database')));
	$tag_id = $select->fetch()->id;

	// Delete database entry for that tag
	$delete = $db->query('DELETE FROM tags_lookup WHERE files_id = '.$file_id.' AND tags_id = '.$tag_id.' LIMIT 1');
	if ($delete->rowCount()) {
		// Report this to the CSV log file describing an activity only regarding tags
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/tags.txt',
			date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
			$_SERVER['REMOTE_ADDR'].','.
			'0,'.
			'[AUTO],'.
			$file_id.','.
			$_POST['fullname'].','.
			'DELETE,'.
			$tag_id.','.
			$name.
		PHP_EOL, FILE_APPEND);
	}
}

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

	RemoveTag($file_id, 'Game');
	RemoveTag($file_id, 'Game Prev');

	// Now get sorted arrays of the tag names and types used by this file right now
	$list_of_tags = array();
	$type_of_tags = array();
	GetTagsAndTypes($file_id, $list_of_tags, $type_of_tags);

} catch(PDOException $e) {
	$account->LogActivityError('tags_remove_game.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'tags' => $list_of_tags, 'tagtypes' => $type_of_tags));
?>