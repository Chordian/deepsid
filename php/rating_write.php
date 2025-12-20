<?php
/**
 * DeepSID
 *
 * Set or clear a rating for a folder or a SID file. Files or folders that
 * share the same hash (MD5) will also share the same rating.
 * 
 * If the rating is the same as is already stored, it will be cleared instead.
 * 
 * @uses		$_POST['fullname']
 * @uses		$_POST['rating']
 * 
 * @used-by		main.js
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!$account->CheckLogin())
	die(json_encode(['status'=>'error', 'message'=>'User not logged in']));

$user_id = $account->UserID();

try {
	$db = $account->GetDB();

	// ---------------------------------------------------------------------
	// PREPARE INPUT
	// ---------------------------------------------------------------------
	$fullname = $_POST['fullname'] ?? '';
	$fullname = str_replace('CSDb Music Competitions/', '', $fullname);

	$table = (str_ends_with($fullname, '.sid') || str_ends_with($fullname, '.mus'))
			? 'hvsc_files'
			: 'hvsc_folders';

	$type  = ($table == 'hvsc_files') ? 'FILE' : 'FOLDER';

	// ---------------------------------------------------------------------
	// 1. FETCH TARGET ROW (id + hash)
	// ---------------------------------------------------------------------
	$select = $db->prepare("SELECT id, hash FROM $table WHERE fullname = :f LIMIT 1");
	$select->execute([':f' => $fullname]);
	$row = $select->fetch(PDO::FETCH_OBJ);

	if (!$row) {
		$account->LogActivityError(basename(__FILE__),
			"Name error; fullname '$fullname' not found in '$table'");
		die(json_encode(['status'=>'error','message'=>DB_ERROR]));
	}

	// ---------------------------------------------------------------------
	// 2. FETCH ALL IDS WITH SAME HASH (duplicate files)
	// ---------------------------------------------------------------------
	$hash = $row->hash;
	$id_list = [];

	if ($hash) {
		// The file or folder has a genuine hash so get all ID's that match it
		$q = $db->prepare("SELECT id FROM $table WHERE hash = :h");
		$q->execute([':h' => $hash]);
		$id_list = $q->fetchAll(PDO::FETCH_COLUMN, 0);
	} else {
		// Undefined hash so there's just the one
		$id_list = [$row->id];
	}

	// ---------------------------------------------------------------------
	// 3. APPLY RATING TO ALL MATCHING IDS (original code)
	// ---------------------------------------------------------------------
	foreach ($id_list as $id) {
		// Did the user rate this folder or SID file before?
		/*$select = $db->query('SELECT id, rating FROM ratings
							  WHERE user_id = '.$user_id.' AND table_id = '.$id.' AND type = "'.$type.'"LIMIT 1');*/
		$select = $db->prepare(
			'SELECT id, rating FROM ratings
			WHERE user_id = :uid AND table_id = :tid AND type = :type
			LIMIT 1'
		);
		$select->execute([
			':uid'  => $user_id,
			':tid'  => $id,
			':type' => $type
		]);

		$select->setFetchMode(PDO::FETCH_OBJ);

		$rating = $_POST['rating'];

		if ($select->rowCount()) {
			// Yes, get ready to update the row
			$row = $select->fetch();
			if ($row->rating == $rating) $rating = 0; // Same rating so clear it instead
			$update = $db->prepare('UPDATE ratings SET rating = :rating, hash = "'.$hash.'" WHERE id = '.$row->id);
			$update->execute(array(':rating' => $rating));
		} else {
			// No, we must create the row afresh
			$insert = $db->prepare('INSERT INTO ratings (user_id, table_id, type, hash, rating)'.
				' VALUES('.$user_id.', '.$id.', "'.$type.'", "'.$hash.'", :rating)');
			$insert->execute(array('rating' => $rating));
		}
	}

	// NOTE: Updating the ratings cache was moved to the 'rating_cache.php' script (called by browser.js).

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'rating' => $rating));
?>