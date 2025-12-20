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

	// Get the ID for this SID tune
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$fullname));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$file_id = $select->fetch()->id;

	// First delete the file database entry
	$delete = $db->query('DELETE FROM hvsc_files WHERE id = '.$file_id.' LIMIT 1');

	// Then delete the special database row with the date
	$delete = $db->query('DELETE FROM uploads WHERE files_id = '.$file_id.' LIMIT 1');

	// Now delete the actual file too
	unlink(ROOT_HVSC.'/'.$fullname);

	$account->LogActivity('An administrator deleted the "'.$fullname.'" file');

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>