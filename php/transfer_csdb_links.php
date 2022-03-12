<?php
/**
 * DeepSID
 *
 * One-shot script.
 * 
 * @used-by		N/A
 */

require_once("setup.php");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$select = $db->query('SELECT id, name, csdbid FROM composers WHERE csdbid != 0 AND csdbtype = "scener"');
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach ($select as $row) {

		$select_link = $db->query('SELECT 1 FROM composers_links WHERE composers_id = '.$row->id.' AND name = "CSDb"');
		if ($select_link->rowCount()) {
			echo 'Already had the CSDb link: '.$row->name.'<br />';
		} else {
			// Create the external 'CSDb' link now 
			$db->query('INSERT INTO composers_links (composers_id, name, url)
				VALUES('.$row->id.', "CSDb", "https://csdb.dk/scener/?id='.$row->csdbid.'")');
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>