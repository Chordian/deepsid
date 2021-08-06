<?php
/**
 * DeepSID / Account
 *
 * Attempt login or register.
 *
 * @uses		$_POST['submitted']
 * @uses		$_GET['register']
 *
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (isset($_POST['submitted'])) {
	// Do we need to register a user first?
	if (isset($_GET['register']) && $_GET['register'] == 'true' && !$account->RegisterUser())
		die(json_encode(array('result' => false, 'error' => $account->GetErrorMessage())));

	// Always login regardless of having registered first or not
	if ($account->Login()) {
		echo json_encode(array('result' => true, 'error' => ''));
	} else {
		echo json_encode(array('result' => false, 'error' => $account->GetErrorMessage()));
	}
}
?>