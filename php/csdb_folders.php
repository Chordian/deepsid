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
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "_High Voltage SID Collection/%"');
		$rows = $select->fetchAll(PDO::FETCH_COLUMN);

		// Always emit UTF-8 JSON
		echo json_encode($rows, JSON_UNESCAPED_UNICODE);

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
?>