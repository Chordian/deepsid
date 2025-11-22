<?php
/**
 * DeepSID
 *
 * Write one setting from the 'Admin' tab.
 * 
 * For administrators only.
 * 
 * @uses		$_POST['key']
 * @uses		$_POST['value']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");
if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

	try {
		$db = $account->GetDB();

		// Write or update the setting
		$insert = $db->prepare('
			INSERT INTO admin_settings (setting_key, setting_value)
			VALUES (:key, :value)
			ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
		');
		$insert->execute([':key' => $_POST['key'], ':value' => $_POST['value']]);

	} catch(PDOException $e) {
		$account->LogActivityError('admin_settings_write.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
	die(json_encode(array('status' => 'ok')));
?>