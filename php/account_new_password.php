<?php
/**
 * DeepSID / Account
 *
 * Set a new password.
 *
 * @uses		$_POST['oldpwd']
 * @uses		$_POST['newpwd']
 *
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if ($account->ChangePassword())
	echo json_encode(array('status' => 'ok', 'message' => 'Saved'));
else
	echo json_encode(array('status' => 'mismatch', 'message' => $account->GetErrorMessage()));
?>