<?php
/**
 * DeepSID
 *
 * Delete a symlist folder and all of its contents.
 * 
 * @uses		$_POST['symlist']
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to delete playlists.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get ID of symlist folder
	$select = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :folder AND user_id = '.$user_id.' LIMIT 1');
	$select->execute(array(':folder'=>$_POST['symlist']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['symlist'])));

	$folder_id = $select->fetch()->id;

	// First delete all the symlist entries associated with that folder
	$delete = $db->query('DELETE FROM symlists WHERE folder_id = '.$folder_id);

	// Now delete the folder itself
	$delete = $db->query('DELETE FROM hvsc_folders WHERE id = '.$folder_id.' LIMIT 1');

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}

echo json_encode(array('status' => 'ok'));
?>