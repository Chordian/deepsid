<?php
/**
 * DeepSID
 *
 * Save the rows with YouTube data from the dialog box to the database.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['subtune']
 * @uses		$_POST['videos']			array with all data or 0 for purge
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['fullname']) || !isset($_POST['subtune']) || !isset($_POST['videos']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

$user_id = $account->checkLogin() ? $account->userID() : 0;
if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to edit YouTube video links.')));

try {
	$db = $account->getDB();

	// First find the ID of the 'collection_path' text
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
	$select->execute(array(':collection_path' => $_POST['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	$file_id = $select->fetch()->id;

	// Start by deleting all data so it can be completely replaced
	$delete = $db->prepare('DELETE FROM youtube WHERE file_id = :id AND subtune = :subtune LIMIT 5');
	$delete->execute(array(':id'=>$file_id,':subtune'=>$_POST['subtune']));

	if ($_POST['videos'] == 0)
		die(json_encode(array('status' => 'purged'))); // Since the array is empty we're already done

	$order = 0;
	foreach($_POST['videos'] as $video) {
		// Add one row of a maximum of five rows of data
		$insert = $db->prepare('INSERT INTO youtube (file_id, subtune, channel, video_id, tab_order, tab_default)
			VALUES (:id, :subtune, :channel, :video_id, '.$order++.', :tab_default)');
		$insert->execute(array(
			':id'			=> $file_id,
			':subtune'		=> $_POST['subtune'],
			':channel'		=> $video['channel'],
			':video_id'		=> $video['video_id'],
			':tab_default'	=> $video['tab_default'],
		));
		if ($insert->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not save all the YouTube video links for this SID row.')));
	}

	$collection_path = str_replace('_High Voltage SID Collection', '', $_POST['fullname']);
	$account->logActivity('User "'.$_SESSION['user_name'].'" edited the video links for "'.$collection_path.'" (subtune #'.$_POST['subtune'].')');

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status'	=> 'ok'));
?>