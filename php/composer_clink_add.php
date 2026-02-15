<?php
/**
 * DeepSID
 *
 * Add a new "clink" (composer link) for a composer profile.
 * 
 * @uses		$_POST['cid']
 * @uses		$_POST['name']
 * @uses		$_POST['url']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['cid']) || !isset($_POST['name']) || !isset($_POST['url']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

if ($account->CheckLogin()) {
	
	try {
		$db = $account->GetDB();

		// Who exactly are we doing this for?
		$select = $db->prepare('SELECT fullname FROM composers WHERE id = :cid');
		$select->execute(array(':cid' => $_POST['cid']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$fullname = str_replace('_High Voltage SID Collection', '', $select->fetch()->fullname);

		// Add the new database entry
		$insert = $db->prepare('INSERT INTO composers_links (composers_id, name, url) VALUES(:cid, :name, :url)');
		$insert->execute(array('cid' => $_POST['cid'], 'name' => $_POST['name'], 'url' => $_POST['url']));

		if ($insert->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not create the "'.$_POST['name'].'" composer link for "'.$fullname.'"')));

		// Finally log it
		$account->LogActivity('User "'.$account->UserName().'" added the "'.$_POST['name'].'" composer link ('.$_POST['url'].') for "'.$fullname.'"');

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'User not logged in')));

echo json_encode(array('status' => 'ok'));
?>