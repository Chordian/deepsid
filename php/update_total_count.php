<?php
/**
 * DeepSID
 *
 * Find all composer folders in letter folders of MUSICIANS that have one or
 * more sub folders (e.g. work tunes), add file counts up, then update the
 * count of the composer folder itself with this total.
 * 
 * This must be done after having imported the count of files for a new HVSC
 * version, created by a simple python script. (So, why not just let this
 * python script also do that? You know, that's a very good question.)
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

define('MUSICIANS', '_High Voltage SID Collection/MUSICIANS/');

try {
	$db = $account->getDB();

	$letters = ['0-9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

	foreach($letters as $letter) {
		// Get a list of all composer folders in this letter folder
		$select = $db->query('SELECT collection_path FROM hvsc_folders WHERE collection_path LIKE "'.MUSICIANS.$letter.'/%"');
		$select->setFetchMode(PDO::FETCH_OBJ);

		$collection_paths = [];
		foreach($select as $row)
			array_push($collection_paths, $row->collection_path);
		// To make sure sub folders with sub folders are handled first (affects absolute total)
		$collection_paths = array_reverse($collection_paths);

		foreach($collection_paths as $collection_path) {
			// Any sub folder(s)?
			if (substr_count($collection_path, '/') > 3) {
				// Get count of this sub folder (it could have been updated in an earlier iteration)
				$select_this = $db->query('SELECT files FROM hvsc_folders WHERE collection_path = "'.$collection_path.'"');
				$select_this->setFetchMode(PDO::FETCH_OBJ);
				$this_count = $select_this->fetch()->files;

				// Get parent and its children
				$parent = substr($collection_path, 0, strrpos($collection_path, '/'));

				// Get current count of files in parent
				$select_parent = $db->query('SELECT files FROM hvsc_folders WHERE collection_path = "'.$parent.'"');
				$select_parent->setFetchMode(PDO::FETCH_OBJ);
				$parent_count = $select_parent->fetch()->files;

				$new_parent_count = $parent_count + $this_count;

				echo $parent.' ('.$parent_count.') <br />';
				echo $collection_path.' ('.$this_count.') <br />';
				echo 'New parent count: '.$parent_count.' + '.$this_count.' = '.$new_parent_count.'<br /><br />';

				// Now store the new parent count
				// $db->query('UPDATE hvsc_folders SET files = '.$new_parent_count.' WHERE collection_path = "'.$parent.'" LIMIT 1');
			}
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>