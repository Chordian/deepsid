<?php
/**
 * DeepSID
 *
 * Remame a symlist folder or one of its entries.
 * 
 * @uses		$_POST['symlist']			existing symlist folder
 * @uses		$_POST['fullname']			if not set then a symlist is renamed
 * @uses		$_POST['symid']				if > 0 then use this as reference instead
 * 
 * @uses		$_POST['new']				the new name
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to rename playlists.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if (empty($_POST['fullname'])) {

		// RENAME SYMLIST FOLDER

		$char = substr($_POST['symlist'], 0, 1); // Can be '!' (personal) or '$' (public)
		$and_user_id = $char == '!' ? ' AND user_id = '.$user_id : '';

		// First make sure the new name doesn't already exist
		$select = $db->prepare('SELECT 1 FROM hvsc_folders WHERE fullname = :folder'.$and_user_id);
		$select->execute(array(':folder'=>$char.$_POST['new']));
		if ($select->rowCount())
			die(json_encode(array('status' => 'error', 'message' =>
				($char == '$'
					? "There's already another public playlist with that name."
					: 'You already have another playlist with that name.'))));

		// Now rename it
		$update = $db->prepare('UPDATE hvsc_folders SET fullname = :new WHERE fullname = :old AND user_id = '.$user_id);
		$update->execute(array(':old'=>$_POST['symlist'], ':new'=>$char.$_POST['new']));
		if ($update->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not rename folder "'.$_POST['symlist'].'" => "'.$char.$_POST['new'].'"')));
		$account->LogActivity('User "'.$_SESSION['user_name'].'" renamed the "'.$_POST['symlist'].'" playlist to "'.$char.$_POST['new'].'"');

	} else {

		// RENAME AN ENTRY IN A SYMLIST FOLDER

		if (isset($_POST['symid']) && $_POST['symid']) {

			// We must reference the symlist ID directly because of multiple ocurrences of the same SID file
			$update = $db->prepare('UPDATE symlists SET sidname = :new WHERE id = :symid LIMIT 1');
			$update->execute(array(':new'=>$_POST['new'], ':symid'=>$_POST['symid']));
			if ($update->rowCount() == 0)
				die(json_encode(array('status' => 'error', 'message' => 'Could not rename entry "'.$_POST['fullname'].'" => "'.$_POST['new'].'"')));

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

			$update = $db->prepare('UPDATE symlists SET sidname = :new WHERE folder_id = '.$folder_id.' AND file_id = '.$file_id.' LIMIT 1');
			$update->execute(array(':new'=>$_POST['new']));
			if ($update->rowCount() == 0)
				die(json_encode(array('status' => 'error', 'message' => 'Could not rename entry "'.$_POST['fullname'].'" => "'.$_POST['new'].'"')));
		}
	}

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>