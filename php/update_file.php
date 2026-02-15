<?php
/**
 * DeepSID
 *
 * Update the file row in the database. Only available to an administrator.
 * 
 * The purpose of this script was copied as a new step in the upload wizard for
 * the SH folder. This script may still serve as a shortcut, however.
 * 
 * For now, only the following fields are updated:
 * 
 *		name
 *		player
 *		author
 *		copyright
 * 
 * Please be aware that an HVSC update may later overwrite fields in the file
 * row too. It makes more sense to use the script for other collections such
 * as e.g. CGSC and SID Happens.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['name']
 * @uses		$_POST['player']
 * @uses		$_POST['author']
 * @uses		$_POST['copyright']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if ($user_id != JCH)
	die(json_encode(array('status' => 'error', 'message' => 'Only an administrator may edit a file row in the database.')));

try {
	$db = $account->GetDB();

	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname' => $_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	$id = $select->rowCount() ? $select->fetch()->id : 0;

	$new_name = substr($_POST['fullname'], 0, strrpos($_POST['fullname'], '/') + 1).$_POST['name'];

	// Update the fields
	$update = $db->prepare('UPDATE hvsc_files SET fullname = :newername, player = :player, author = :author, copyright = :copyright WHERE id = '.$id.' LIMIT 1');
	$update->execute(array(
		':newername'	=> $new_name,
		':player'		=> $_POST['player'],
		':author'		=> $_POST['author'],
		':copyright'	=> $_POST['copyright'],
	));
	if ($update->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not update the file row for '.$_POST['fullname'])));

	// Rename the physical file too if needed
	if ($_POST['fullname'] != $new_name)
		rename(ROOT_HVSC.'/'.$_POST['fullname'], ROOT_HVSC.'/'.$new_name);

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>