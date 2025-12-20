<?php
/**
 * DeepSID
 *
 * Add or remove tags from a specific file, optionally adding new typed-in
 * tags (identified by ID's starting at 60000).
 * 
 * The script also logs to a CSV file that can be used to undo actions.
 * 
 * It is currently not possible to delete entries in the pool of all tags. If
 * you need to do this, you must do it directly in the database.
 * 
 * @uses		$_POST['fileID']			file ID of the song being affected
 * @uses		$_POST['allTags']			may contain new tags with ID 60000 and up
 * @uses		$_POST['fileTags']			list of ID's after adding and removing
 * @uses		$_POST['startTag']			start of "bracket" connecting two tags
 * @uses		$_POST['endTag']			end of "bracket" connecting two tags
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("tags_read.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['fileID']) || !isset($_POST['allTags']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

/**
 * Writes a line to a CSV log file describing an activity only regarding tags.
 * 
 * It is written in the CSV format so it can be used by another PHP script, in
 * case actions by a user with malicious intentions need to be undone.
 * 
 * Fields:	time, ip address, user id, user name, file id, fullname, action,
 * 			tag id, tag name
 * 
 * @uses		$_POST['fileID']			file ID of the song being affected
 *
 * @param		string		$action
 * @param		string		$tag_id
 * @param		string		$tag_name
 */
function LogTagActivity($action, $tag_id, $tag_name) {
	global $user_id, $user_name, $fullname;
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/tags.txt',
			date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
			$_SERVER['REMOTE_ADDR'].','.
			$user_id.','.
			$user_name.','.
			$_POST['fileID'].','.
			$fullname.','.
			$action.','.
			$tag_id.','.
			$tag_name.
		PHP_EOL, FILE_APPEND);
}

/***** START *****/

// Uncompress all tags (compressed in 'browser.js' to avoid 'max_input_vars' limit in PHP)
if (isset($_POST['allTags'])) {
	$decoded = base64_decode($_POST['allTags']);
	$decoded = mb_convert_encoding($decoded, 'UTF-8', 'ISO-8859-1');
	$_POST['allTags'] = json_decode($decoded, true);
}

$file_tags = isset($_POST['fileTags']) ? $_POST['fileTags'] : array();

if (!$account->CheckLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to edit tags for a file.')));

$user_id = $account->UserID();
$user_name = $account->UserName();

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get full name of this file ID
	$select = $db->prepare('SELECT fullname FROM hvsc_files WHERE id = :id');
	$select->execute(array(':id'=>$_POST['fileID']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	$fullname = $select->fetch()->fullname;

	// Add new typed-in tags
	// NOTE: The type is indirectly set to a default value and will be updated by an administrator later.
	foreach($_POST['allTags'] as $tag) {
		if ($tag['id'] >= 60000) {
			$insert = $db->prepare('INSERT INTO tags_info (name) VALUES(:name)');
			$insert->execute(array(':name'=>$tag['name']));
			if ($insert->rowCount() == 0)
				die(json_encode(array('status' => 'error', 'message' => 'Could not create the new tag "'.$tag['name'].'"')));
			LogTagActivity('NEW', $db->lastInsertId(), $tag['name']);
			// In the array for the file, replace the "fake" ID with the real one
			$file_tags = array_diff($file_tags, [$tag['id']]);
			$file_tags[] = $db->lastInsertId();
		}
	}

	$current_tags = array();

	// Get current list of tag ID's used by the file ID
	$select = $db->prepare('SELECT tags_id FROM tags_lookup WHERE files_id = :id');
	$select->execute(array(':id'=>$_POST['fileID']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach($select as $row) {
		$current_tags[] = (int)$row->tags_id;
		if (!in_array($row->tags_id, $file_tags)) {
			// Delete database entry if not in the revised list (i.e. tag was removed in the dialog box)
			$delete = $db->prepare('DELETE FROM tags_lookup WHERE files_id = :id AND tags_id = '.$row->tags_id.' LIMIT 1');
			$delete->execute(array(':id'=>$_POST['fileID']));
			// Get its name
			$select = $db->query('SELECT name FROM tags_info WHERE id = '.$row->tags_id);
			$select->setFetchMode(PDO::FETCH_OBJ);
			LogTagActivity('DELETE', $row->tags_id, $select->fetch()->name);
		}
	}

	// Check what tags are now associated with this file
	foreach($file_tags as $tag_id) {
		if (!in_array($tag_id, $current_tags)) {
			// Add database entry if the ID is not already there (i.e. new or existing pool tag was added)
			$insert = $db->prepare('INSERT INTO tags_lookup (files_id, tags_id) VALUES(:id, '.$tag_id.')');
			$insert->execute(array(':id'=>$_POST['fileID']));
			// Get its name
			$select = $db->query('SELECT name FROM tags_info WHERE id = '.$tag_id);
			$select->setFetchMode(PDO::FETCH_OBJ);
			LogTagActivity('ADD', $tag_id, $select->fetch()->name);
		}
	}

	// Find START tag and store counterpart END tag for "bracket" connection
	if (isset($_POST['startTag']) && isset($_POST['endTag'])) {
		// What was stored prior to this change?
		$select = $db->prepare('SELECT tags_id, end_id FROM tags_lookup WHERE files_id = :files_id');
		$select->execute(array(':files_id'=>$_POST['fileID']));
		$select->setFetchMode(PDO::FETCH_OBJ);
		
		// Assume no connection line to begin with
		$start_before = $end_before = 0;
		foreach ($select as $row) {
			if ($row->end_id) {
				// A connection line was defined
				$start_before = $row->tags_id;
				$end_before = $row->end_id;
			}			
		}

		// Update and log only if the previous connection setup is actually changed
		if ($_POST['startTag'] != $start_before || $_POST['endTag'] != $end_before) {

			// What are the names of the tags uses as start and end?
			$select = $db->prepare('SELECT name FROM tags_info WHERE id = :id');
			$select->execute(array(':id'=>$_POST['startTag']));
			$select->setFetchMode(PDO::FETCH_OBJ);
			$start_tag_name = $_POST['startTag'] ? $select->fetch()->name : '[Not selected]';

			$select = $db->prepare('SELECT name FROM tags_info WHERE id = :id');
			$select->execute(array(':id'=>$_POST['endTag']));
			$select->setFetchMode(PDO::FETCH_OBJ);
			$end_tag_name = $_POST['endTag'] ? $select->fetch()->name : '[Not selected]';

			// First reset all end tag ID just to be sure
			$update = $db->prepare('UPDATE tags_lookup SET end_id = 0 WHERE files_id = :files_id');
			$update->execute(array(':files_id'=>$_POST['fileID']));

			// The standard tags ID field also serves as the start tag ID
			$update = $db->prepare('UPDATE tags_lookup SET end_id = :end_id WHERE files_id = :files_id AND tags_id = :tags_id LIMIT 1');
			$update->execute(array(':end_id'=>$_POST['endTag'],':files_id'=>$_POST['fileID'],':tags_id'=>$_POST['startTag']));

			// More information is logged in this version
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/tags.txt',
					date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
					$_SERVER['REMOTE_ADDR'].','.
					$user_id.','.
					$user_name.','.
					$_POST['fileID'].','.
					$fullname.','.
					'BRACKET,'.
					$_POST['startTag'].','.
					$start_tag_name.','.
					$_POST['endTag'].','.
					$end_tag_name.
				PHP_EOL, FILE_APPEND);
		}
	}

	// Now get sorted arrays of the tag names and types used by this file right now
	$list_of_tags = array();
	$type_of_tags = array();
	$id_of_tags = array();
	$id_tag_start = $id_tag_end = 0;
	GetTagsAndTypes($_POST['fileID'], $list_of_tags, $type_of_tags, $id_of_tags, $id_tag_start, $id_tag_end);

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());	
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'tags' => $list_of_tags, 'tagtypes' => $type_of_tags, 'tagids' => $id_of_tags, 'tagidstart' => $id_tag_start, 'tagidend' => $id_tag_end));
?>