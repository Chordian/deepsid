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

if ($account->CheckLogin()) {
	$user_id = $account->UserID();

	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$fullname = isset($_POST['fullname']) ? $_POST['fullname'] : '';
		$fullname = str_replace('CSDb Music Competitions/', '', $fullname);

		$table = strpos($fullname, '.sid') || strpos($fullname, '.mus') ? 'hvsc_files' : 'hvsc_folders';
		$type = $table == 'hvsc_files' ? 'FILE': 'FOLDER';

		// Get the hash (MD5) in the 'hvsc_files' or 'hvsc_folder' table
		$select = $db->prepare('SELECT id, hash FROM '.$table.' WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname' => $fullname));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if (!$select->rowCount()) {
			$account->LogActivityError('rating_write.php', 'Name error; $_POST[\'fullname\'] = '.$fullname.' not found in "'.$table.'" table');
			die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
		}

		// Now get the ID's for all rows that share that hash value
		$row = $select->fetch();
		$hash = $row->hash;
		if (!empty($hash)) {
			// The file or folder has a genuine hash so get all ID's that match it
			$select = $db->query('SELECT id FROM '.$table.' WHERE hash = "'.$hash.'"');
			$select->setFetchMode(PDO::FETCH_OBJ);

			$id_list = array();
			foreach ($select as $row) {
				array_push($id_list, $row->id);
			}
		} else
			// Undefined hash so there's just the one
			$id_list = array($row->id);

		foreach ($id_list as $id) {
			// Did the user rate this folder or SID file before?
			$select = $db->query('SELECT id, rating FROM ratings WHERE user_id = '.$user_id.' AND table_id = '.$id.' AND type = "'.$type.'" LIMIT 1');
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

	} catch(PDOException $e) {
		$account->LogActivityError('rating_write.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'User not logged in')));

echo json_encode(array('status' => 'ok', 'rating' => $rating));
?>