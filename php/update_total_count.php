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
 */

require_once("class.account.php"); // Includes setup

define('MUSICIANS', '_High Voltage SID Collection/MUSICIANS/');

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$letters = ['0-9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

	foreach($letters as $letter) {
		// Get a list of all composer folders in this letter folder
		$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "'.MUSICIANS.$letter.'/%"');
		$select->setFetchMode(PDO::FETCH_OBJ);

		$fullnames = [];
		foreach($select as $row)
			array_push($fullnames, $row->fullname);
		// To make sure sub folders with sub folders are handled first (affects absolute total)
		$fullnames = array_reverse($fullnames);

		foreach($fullnames as $fullname) {
			// Any sub folder(s)?
			if (substr_count($fullname, '/') > 3) {
				// Get count of this sub folder (it could have been updated in an earlier iteration)
				$select_this = $db->query('SELECT files FROM hvsc_folders WHERE fullname = "'.$fullname.'"');
				$select_this->setFetchMode(PDO::FETCH_OBJ);
				$this_count = $select_this->fetch()->files;

				// Get parent and its children
				$parent = substr($fullname, 0, strrpos($fullname, '/'));

				// Get current count of files in parent
				$select_parent = $db->query('SELECT files FROM hvsc_folders WHERE fullname = "'.$parent.'"');
				$select_parent->setFetchMode(PDO::FETCH_OBJ);
				$parent_count = $select_parent->fetch()->files;

				$new_parent_count = $parent_count + $this_count;

				echo $parent.' ('.$parent_count.') <br />';
				echo $fullname.' ('.$this_count.') <br />';
				echo 'New parent count: '.$parent_count.' + '.$this_count.' = '.$new_parent_count.'<br /><br />';

				// Now store the new parent count
				// $db->query('UPDATE hvsc_folders SET files = '.$new_parent_count.' WHERE fullname = "'.$parent.'" LIMIT 1');
			}
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>