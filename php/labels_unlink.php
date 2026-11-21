<?php
/**
 * DeepSID
 *
 * Unlink all labels from a file. (A label is a primary release factoid.)
 * 
 * @uses		$_POST['logging']		1 = log it, 0 = don't log it
 * @uses		$_POST['fullname']
 * 
 * @used-by		browser.js
 */

require_once("lib/class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
	http_response_code(403);	
	die("Direct access not permitted.");
}

if (!$account->isAdmin()) {
	http_response_code(403);
	die("This is for administrators only."); // At least for now...
}

if (!isset($_POST['logging']) || !isset($_POST['fullname']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variable.')));

try {
	$db = $account->getDB();

	// Get the ID of this file
	$select = $db->prepare('SELECT id FROM files WHERE collection_path = :collection_path LIMIT 1');
	$select->execute(array(':collection_path' => $_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not find "'.$_POST['fullname'].'" in the database')));
	$file_id = $select->fetch()->id;

	// Delete all label lookups associated with this SID file
	$db->query('DELETE FROM labels_lookup WHERE files_id = "'.$file_id.'"');

	if ($_POST['logging']) {
		// Log the action (the tags log file is used to avoid yet another log file)
		file_put_contents(__DIR__ . '/../logs/tags.txt',
			date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
			$_SERVER['REMOTE_ADDR'].','.
			$account->userID().','.
			$account->userName().','.
			$file_id.','.
			$_POST['fullname'].','.
			'LABELS:DELETE'.
		PHP_EOL, FILE_APPEND);
	}

	// Don't add 'session_write_close()' here

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok'));
?>