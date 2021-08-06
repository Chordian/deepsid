<?php
/**
 * DeepSID / Account
 *
 * Check if a username exists.
 *
 * @uses		$_POST['username']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

echo json_encode(array('exists' => !$account->IsUserNameUnique()));
?>