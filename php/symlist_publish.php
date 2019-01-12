<?php
/**
 * DeepSID
 *
 * Publish a symlist folder so that everyone can see it (and edit it if logged
 * in). Technically it just swaps the prepended '!' with a '$' character
 * instead, thereby denoting a public symlist.
 * 
 * @uses		$_POST['symlist']		existing symlist folder
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

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
	$select->execute(array(':folder'=>'$'.substr($_POST['symlist'], 1)));
	if ($select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => 'There is already a public playlist with that name. Please rename your playlist and try again.')));

	// Make the transition
	$update = $db->prepare('UPDATE hvsc_folders SET fullname = :public WHERE fullname = :fullname AND user_id = '.$user_id.' LIMIT 1');
	$update->execute(array(':public'=>'$'.substr($_POST['symlist'], 1), ':fullname'=>$_POST['symlist']));
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not publish "'.$_POST['symlist'])));
	$account->LogActivity('User "'.$_SESSION['user_name'].'" published the "'.$_POST['symlist'].'" playlist');

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}

echo json_encode(array('status' => 'ok'));
?>