<?php
/**
 * DeepSID
 *
 * Rebuild the ratings cache for the logged on user.
 * 
 * NOTE: The cache was originally not planned to be used dynamically this way,
 * but it turned out to be fast, and it's much less error prone.
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once('build_ratings_cache_single_user.php');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(['status'=>'error', 'message'=>'User not logged in']));

$user_id = $account->UserID();

try {
	$db = $account->GetDB();

	// Delete old cache rows
	$del = $db->prepare("DELETE FROM ratings_cache WHERE user_id = ?");
	$del->execute([$user_id]);

	// Build new cache
	build_ratings_cache_for_user($db, (int)$user_id);

} catch(PDOException $e) {
	$account->LogActivityError('rating_write.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'rating' => $rating));
?>