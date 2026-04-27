<?php
/**
 * DeepSID
 *
 * Get an array with the personal and public symlist folders the user currently have.
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->checkLogin() ? $account->userID() : 0;

try {
	$db = $account->getDB();

	$symlists = array();

	$select = $db->query('SELECT id, collection_path, files, user_id FROM hvsc_folders WHERE (collection_path LIKE "!%" OR collection_path LIKE "$%") AND user_id = '.$user_id);
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount()) {
		foreach ($select as $row) {
			array_push($symlists, array(
				'id' =>				$row->id,
				'fullname' =>		$row->collection_path,
				'files' =>			$row->files,
				'public' =>			substr($row->collection_path, 0, 1) == '$',
			));
		}
	}

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'symlists' => $symlists));
?>