<?php
/**
 * DeepSID
 *
 * Return an array of all MUSICIANS folders in HVSC.
 * 
 * @used-by		commands.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

	try {
		$db = $account->GetDB();

		$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "_High Voltage SID Collection/%"');
		$rows = $select->fetchAll(PDO::FETCH_COLUMN);

		// Always emit UTF-8 JSON
		echo json_encode($rows, JSON_UNESCAPED_UNICODE);

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
?>