<?php
/**
 * DeepSID
 *
 * Find out if the user has rated EVERYTHING inside the specified folder (and
 * its sub folders).
 * 
 * This is code cloned from the 'hvsc.php' script.
 * 
 * @uses		$_GET['fullname']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if ($account->CheckLogin()) {
	$user_id = $account->UserID();

	try {
		$db = $account->GetDB();

		$select_files = $db->prepare("
			SELECT files
			FROM hvsc_folders
			WHERE fullname = :folder
		");
		$select_files->execute([':folder' => $_GET['fullname']]);
		$total_files = (int)$select_files->fetchColumn();

		if ($total_files === 0) {
			$all_rated = false;
		} else {
			// Sum ratings from cache for this folder and its subfolders
			$select_cache = $db->prepare("
				SELECT SUM(rated_files)
				FROM ratings_cache
				WHERE user_id = :uid
				AND (folder = :folder OR folder LIKE CONCAT(:folder, '/%'))
			");
			$select_cache->execute([
				':uid'    => $user_id,
				':folder' => $_GET['fullname']
			]);

			$rated_sum = (int)$select_cache->fetchColumn();
			$all_rated = ($rated_sum === $total_files); // Boolean verdict
		}

	} catch(PDOException $e) {
		$account->LogActivityError('rating_folder.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
} else {
	$all_rated = false;
}
echo json_encode(array('status' => 'ok', 'all_rated' => $all_rated));
?>