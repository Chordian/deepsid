<?php
/**
 * DeepSID
 *
 * Either add a SID file to an existing symlist folder, or create a new symlist
 * folder bearing a unique version of the specified SID file name.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['symlist']			if not set then create a new symlist
 * @uses		$_POST['subtune']
 * 
 * @used-by		browser.js
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

	if (empty($_POST['symlist'])) {

		// CREATE NEW SYMLIST FOLDER

		// Isolate raw SID filename without folders and extension, and use spaces instead of "_" between words
		$array = explode('/', $_POST['fullname']);
		$isolated_sid_name = substr(end($array), 0, -4);
		$isolated_sid_name = str_replace('_', ' ', $isolated_sid_name);

		// Keep polling the database until we're sure we have a unique symlist folder name
		$amend = 1;
		$suggested_symlist_name = '!'.$isolated_sid_name;
		do {
			$select = $db->prepare('SELECT 1 FROM hvsc_folders WHERE fullname = :fullname AND user_id = '.$user_id);
			$select->execute(array(':fullname'=>$suggested_symlist_name));

			if ($select->rowCount()) {
				$suggested_symlist_name = '!'.$isolated_sid_name.' '.$amend; // Try adding a number then
				$amend++;
			}
		} while ($select->rowCount());

		// Create the new symlist entry and get its ID
		$insert = $db->query('INSERT INTO hvsc_folders (fullname, user_id)'.
			' VALUES("'.$suggested_symlist_name.'", '.$user_id.')');
		if ($insert->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => "Could not create ".$suggested_symlist_name)));
		$account->LogActivity('User "'.$_SESSION['user_name'].'" created the "'.$suggested_symlist_name.'" playlist');

		$folder_id = $db->lastInsertId();
		$file_count = 0;

		$symlist_folder = $suggested_symlist_name;

	} else {

		// ADD TO EXISTING SYMLIST FOLDER

		$symlist_folder = str_replace(' [PUBLIC]', '', $_POST['symlist']);

		$select = $db->prepare('SELECT id, files FROM hvsc_folders WHERE fullname = :fullname AND user_id = '.$user_id.' LIMIT 1');
		$select->execute(array(':fullname'=>$symlist_folder));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if (!$select->rowCount())
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$symlist_folder)));

		$row = $select->fetch();
		$folder_id = $row->id;
		$file_count = $row->files;
	}

	// Get the ID of the fullname SID file the user wanted to add as an entry
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find ".$_POST['fullname'])));

	$file_id = $select->fetch()->id;

	// Now create the symlist entry (different SID name via renaming is done in a different PHP file)
	$insert = $db->prepare('INSERT INTO symlists (folder_id, file_id, subtune) VALUES('.$folder_id.', '.$file_id.', :subtune)');
	$insert->execute(array(':subtune'=>$_POST['subtune']));
	if ($insert->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not create the entry in '.$symlist_folder)));

	// Increase the count of files
	$update = $db->query('UPDATE hvsc_folders SET files = '.++$file_count.' WHERE id = '.$folder_id);
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not update the count of files in '.$symlist_folder)));

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'name' => $symlist_folder));
?>