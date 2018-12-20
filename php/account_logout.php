<?php
/**
 * DeepSID / Account
 *
 * Just logout (no error handling).
 */

 require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$account->LogOut();
?>