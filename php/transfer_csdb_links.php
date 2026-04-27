<?php
/**
 * DeepSID
 *
 * Generate CSDb links for every composer with a page on CSDb.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

try {
	$db = $account->getDB();

	$select = $db->query('SELECT id, full_name, csdb_id FROM composers WHERE csdb_id != 0 AND csdb_type = "scener"');
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach ($select as $row) {

		$select_link = $db->query('SELECT 1 FROM composers_links WHERE composers_id = '.$row->id.' AND name = "CSDb"');
		if ($select_link->rowCount()) {
			echo 'Already had the CSDb link: '.$row->full_name.'<br />';
		} else {
			// Create the external 'CSDb' link now 
			$db->query('INSERT INTO composers_links (composers_id, name, url)
				VALUES('.$row->id.', "CSDb", "https://csdb.dk/scener/?id='.$row->csdb_id.'")');
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>