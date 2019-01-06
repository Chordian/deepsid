<?php
/**
 * DeepSID
 *
 * Specify a default sub tune for a SID file in an existing symlist folder.
 * 
 * @uses		$_POST['symlist']
 * @uses		$_POST['fullname']
 * @uses		$_POST['symid']			if > 0 then use this as reference instead
 * 
 * @uses		$_POST['subtune']		the new default
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

	if (isset($_POST['symid']) && $_POST['symid']) {

		// We must reference the symlist ID directly because of multiple ocurrences of the same SID file
		$update = $db->prepare('UPDATE symlists SET subtune = :subtune WHERE id = :symid LIMIT 1');
		$update->execute(array(':subtune'=>$_POST['subtune'], ':symid'=>$_POST['symid']));
		if ($update->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'It was already set to that sub tune value.')));
	
	} else {

		// Get ID of symlist folder
		$select = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :folder AND user_id = '.$user_id.' LIMIT 1');
		$select->execute(array(':folder'=>$_POST['symlist']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if (!$select->rowCount())
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['symlist'])));

		$folder_id = $select->fetch()->id;

		// Get ID of actual SID file
		$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$_POST['fullname']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if (!$select->rowCount())
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['fullname'])));

		$file_id = $select->fetch()->id;

		// Set the new sub tunes value
		$update = $db->prepare('UPDATE symlists SET subtune = :subtune WHERE folder_id = '.$folder_id.' AND file_id = '.$file_id.' LIMIT 1');
		$update->execute(array(':subtune'=>$_POST['subtune']));
		if ($update->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'It was already set to that sub tune value.')));
	}

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}

echo json_encode(array('status' => 'ok'));
?>