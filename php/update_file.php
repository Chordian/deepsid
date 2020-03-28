<?php
/**
 * DeepSID
 *
 * Update the file row in the database. Only available to an administrator.
 * 
 * For now, only the following fields are updated:
 * 
 * 	player
 * 	author
 * 	copyright
 * 
 * Please be aware that an HVSC update may later overwrite fields in the file
 * row too. It makes more sense to use the script for other collections such
 * as e.g. CGSC and SID Happens.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['player']
 * @uses		$_POST['author']
 * @uses		$_POST['copyright']
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if ($user_id != JCH)
	die(json_encode(array('status' => 'error', 'message' => 'Only an administrator may edit a file row in the database.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Update the fields
	$update = $db->prepare('UPDATE hvsc_files SET player = :player, author = :author, copyright = :copyright WHERE fullname = :fullname LIMIT 1');
	$update->execute(array(
		':player'		=> $_POST['player'],
		':author'		=> $_POST['author'],
		':copyright'	=> $_POST['copyright'],
		':fullname'		=> $_POST['fullname'],
	));
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not update the file row for '.$_POST['fullname'])));

} catch(PDOException $e) {
	$account->LogActivityError('update_file.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>