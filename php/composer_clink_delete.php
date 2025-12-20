<?php
/**
 * DeepSID
 *
 * Delete a "clink" (composer link) for a composer profile.
 * 
 * @uses		$_POST['cid']
 * @uses		$_POST['id']
 * @uses		$_POST['name']
 * @uses		$_POST['url']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['cid']) || !isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['url']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

if ($account->CheckLogin()) {
	
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		// Who exactly are we doing this for?
		$select = $db->prepare('SELECT fullname FROM composers WHERE id = :cid');
		$select->execute(array(':cid' => $_POST['cid']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$fullname = str_replace('_High Voltage SID Collection', '', $select->fetch()->fullname);

		// Delete new database entry
		$delete = $db->prepare('DELETE FROM composers_links WHERE id = :id LIMIT 1');
		$delete->execute(array('id' => $_POST['id']));

		// Finally log it
		$account->LogActivity('User "'.$account->UserName().'" deleted the "'.$_POST['name'].'" composer link ('.$_POST['url'].') for "'.$fullname.'"');

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'User not logged in')));

echo json_encode(array('status' => 'ok'));
?>