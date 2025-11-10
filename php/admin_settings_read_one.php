<?php
/**
 * DeepSID
 *
 * Read one setting in the admin table and return its value.
 * 
 * For administrators only.
 * 
 * @uses		$_GET['key']
 * 
 * @used-by		main.js (NOT USED - DEPRECATED?)
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$value = $account->GetAdminSetting($_GET['key']);
die(json_encode(array('status' => 'ok', 'value' => $value)));
?>