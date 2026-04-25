<?php
/**
 * DeepSID
 *
 * Rebuild the ratings cache for the logged on user.
 * 
 * NOTE: The cache was originally not planned to be used dynamically this way,
 * but it turned out to be fast, and it's much less error prone than changing
 * counts in the 'rating_write.php' script.
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once('build_ratings_cache_single_user.php');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if ($account->checkLogin()) {
	$user_id = $account->userID();

	try {
		$db = $account->getDB();

		// Delete old cache rows
		$del = $db->prepare("DELETE FROM ratings_cache WHERE user_id = ?");
		$del->execute([$user_id]);

		// Build new cache
		buildRatingsCacheForUser($db, (int)$user_id);

	} catch(PDOException $e) {
		$account->logActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
}
echo json_encode(array('status' => 'ok'));
?>