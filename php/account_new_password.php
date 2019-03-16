<?php
/**
 * DeepSID / Account
 *
 * Set a new password.
 *
 * @uses		$_POST['newpwd']
 *
 * @output		json
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if ($account->ChangePassword())
	echo json_encode(array('result' => true, 'error' => ''));
else
	echo json_encode(array('result' => false, 'error' => $account->GetErrorMessage()));
?>