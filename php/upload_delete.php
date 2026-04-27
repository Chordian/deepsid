<?php
/**
 * DeepSID
 *
 * This script deletes an existing 'SID Happens' file that has been uploaded
 * earlier. This is only available for a DeepSID administrator.
 * 
 * @uses		$_POST['fullname']			full path of SID file
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->checkLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to delete SID files.')));
else if (!$account->isAdmin())
	die("Only a DeepSID administrator may delete files.");

$collection_path = $_POST['fullname'];

try {
	$db = $account->getDB();

	// Get the ID for this SID tune
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
	$select->execute(array(':collection_path' => $collection_path));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$file_id = $select->fetch()->id;

	// First delete the file database entry
	$delete = $db->query('DELETE FROM hvsc_files WHERE id = '.$file_id.' LIMIT 1');

	// Then delete the special database row with the date
	$delete = $db->query('DELETE FROM uploads WHERE files_id = '.$file_id.' LIMIT 1');

	// Now delete the actual file too
	unlink(ROOT_HVSC.'/'.$collection_path);

	$account->logActivity('An administrator deleted the "'.$collection_path.'" file');

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>