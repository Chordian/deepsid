<?php
/**
 * DeepSID
 *
 * This script deletes an existing 'SID Happens' file that has been uploaded
 * earlier. This is only available for a DeepSID administrator.
 * 
 * @uses		$_POST['fullname']			full path of SID file
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to delete SID files.')));
else if($account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("Only a DeepSID administrator may delete files.");

$fullname = $_POST['fullname'];

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First delete the database entry
	$delete = $db->prepare('DELETE FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$delete->execute(array(':fullname'=>$fullname));

	// Now delete the actual file too
	unlink(ROOT_HVSC.'/'.$fullname);

	$account->LogActivity('An administrator deleted the "'.$fullname.'" file');

} catch(PDOException $e) {
	$account->LogActivityError('upload_delete.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>