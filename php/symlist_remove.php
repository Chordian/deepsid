<?php
/**
 * DeepSID
 *
 * Remove a SID file from an existing symlist folder.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['symlist']
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to use playlists.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get ID of symlist folder
	$select = $db->prepare('SELECT id, files FROM hvsc_folders WHERE fullname = :folder AND user_id = '.$user_id.' LIMIT 1');
	$select->execute(array(':folder'=>$_POST['symlist']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['symlist'])));

	$row = $select->fetch();
	$folder_id = $row->id;
	$file_count = $row->files;

	// Get ID of actual SID file
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['fullname'])));

	$file_id = $select->fetch()->id;

	// Now delete the symlist entry
	$delete = $db->query('DELETE FROM symlists WHERE folder_id = '.$folder_id.' AND file_id = '.$file_id.' LIMIT 1');
	
	// Decrease the count of files
	$update = $db->query('UPDATE hvsc_folders SET files = '.--$file_count.' WHERE id = '.$folder_id);
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not update the count of files in '.$_POST['symlist'])));

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}

echo json_encode(array('status' => 'ok'));
?>