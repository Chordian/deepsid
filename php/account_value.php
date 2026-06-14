<?php
/**
 * DeepSID / Account
 *
 * Read or write a value in the user's database row.
 *
 * @uses		$_POST['column']			database column
 * @uses		$_POST['value']				writes this to the column if specified
 *
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_POST['column']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

if (!isset($_POST['value'])) {
	// Read the current value
	echo json_encode(array('status' => 'ok', 'value' => $account->getUserSetting($_POST['column'])));
} else {
	// Write the new value
	$message = $account->setUserSetting($_POST['column'], $_POST['value']);
	echo json_encode([
		'status' => empty($message) ? 'ok' : 'error',
		'message' => $message
	]);
}
?>