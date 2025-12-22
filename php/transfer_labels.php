<?php
/**
 * DeepSID
 * 
 * ONE-SHOT SCRIPT
 *
 * This script transfer tag labels to the new label database tables, and also
 * cleans up in the tags database tables.
 */

require_once("class.account.php"); // Includes setup

die("This script has served its purpose. It doesn't need to be run again.");

try {
	$db = $account->GetDB();

	// 1. Walk all tags with 'LABEL' type in 'tags_info' table
	$select_labels = $db->query('SELECT id, name FROM tags_info WHERE type = "LABEL"');
	$select_labels->setFetchMode(PDO::FETCH_OBJ);

	foreach($select_labels as $lrow) {

		// 2. Create label counterpart in 'labels_info' table
		$insert = $db->query('INSERT INTO labels_info (name, type, csdbid)
			VALUES("'.$lrow->name.'", "Demo", 0)');
		$labels_id = $db->lastInsertId();
		echo 'Created label "'.$lrow->name.'" (id = '.$labels_id.')<br />';

		// 3. Walk all 'tags_id' for this tag label in 'tags_lookup' table
		$select_tags = $db->query('SELECT id, files_id FROM tags_lookup WHERE tags_id = '.$lrow->id);
		$select_tags->setFetchMode(PDO::FETCH_OBJ);
		echo 'Looping through all tags for this label...<br />';

		foreach($select_tags as $trow) {

			// 4. Create counterpart for same 'files_id' in 'labels_lookup' table
			$insert = $db->query('INSERT INTO labels_lookup (files_id, labels_id)
				VALUES('.$trow->files_id.', '.$labels_id.')');
			echo '&nbsp;&nbsp;&nbsp;Inserted lookup entry in labels lookup table (files_id = '.$trow->files_id.')<br />';

			// 5. Delete the 'tags_id' row in the 'tags_lookup' table
			$db->query('DELETE FROM tags_lookup WHERE id = "'.$trow->id.'" LIMIT 1');
			echo '&nbsp;&nbsp;&nbsp;Deleted tag lookup ('.$trow->id.')<br />';
		}

		// 6. Delete the tag label row in the 'tags_info' table
		$db->query('DELETE FROM tags_info WHERE id = "'.$lrow->id.'" LIMIT 1');
		echo 'Deleted tag label (id = '.$lrow->id.')<br /><br />';
	}

	echo 'Script completed.';

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
}
?>