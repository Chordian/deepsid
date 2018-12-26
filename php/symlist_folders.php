<?php
/**
 * DeepSID
 *
 * Get an array with the personal and public symlist folders the user currently have.
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$symlists = array();

	$select = $db->query('SELECT id, fullname, files, user_id FROM hvsc_folders WHERE (fullname LIKE "!%" OR fullname LIKE "$%") AND user_id = '.$user_id);
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount()) {
		foreach ($select as $row) {
			array_push($symlists, array(
				'id' =>				$row->id,
				'fullname' =>		$row->fullname,
				'files' =>			$row->files,
				'public' =>			substr($row->fullname, 0, 1) == '$',
			));
		}
	}

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}

echo json_encode(array('status' => 'ok', 'symlists' => $symlists));
?>