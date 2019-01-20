<?php
/**
 * DeepSID
 *
 * Publish or unpublish a symlist folder. Publishing causes it to be seen by
 * everyone while unpublishing takes it back to only being visible to the user
 * again. Technically it just swaps the prepended '!' with a '$' characters.
 * 
 * @uses		$_POST['publish']		1 to publish, 0 to unpublish
 * @uses		$_POST['symlist']		existing symlist folder
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;
$symlist_char = $_POST['publish'] ? '$' : '!';

if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to publish playlists.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First let's make sure there is no public playlist with the same name
	$select = $db->prepare('SELECT 1 FROM hvsc_folders WHERE fullname = :folder');
	$select->execute(array(':folder'=>$symlist_char.substr($_POST['symlist'], 1)));
	if ($select->rowCount() ) {
		if ($symlist_char == '$')
			die(json_encode(array('status' => 'error', 'message' => 'There is already a public playlist with that name. Please rename your playlist and try again.')));
		else
			die(json_encode(array('status' => 'error', 'message' => 'You already have a personal playlist with that name. Please rename the public playlist and try again.')));
	}

	// Make the transition
	$update = $db->prepare('UPDATE hvsc_folders SET fullname = :public WHERE fullname = :fullname AND user_id = '.$user_id.' LIMIT 1');
	$update->execute(array(':public'=>$symlist_char.substr($_POST['symlist'], 1), ':fullname'=>$_POST['symlist']));
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not '.($symlist_char == '!' ? 'un' : '').'publish "'.$_POST['symlist'])));
	$account->LogActivity('User "'.$_SESSION['user_name'].'" '.($symlist_char == '!' ? 'un' : '').'published the "'.$_POST['symlist'].'" playlist');

} catch(PDOException $e) {
	$account->LogActivityError('symlist_publish.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>