<?php
/**
 * DeepSID
 *
 * Transfer all ratings from one user ID to another.
 * 
 * @used-by		N/A
 */

require_once("setup.php");

define('USER_SOURCE', 		2);		// JCH
define('USER_DESTINATION',	3);		// Dummy

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First, purge all previous ratings by the destination user
	$db->query('DELETE FROM ratings WHERE user_id = '.USER_DESTINATION);

	// Get all ratings by the source user
	$select = $db->query('SELECT * FROM ratings WHERE user_id = '.USER_SOURCE);
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach ($select as $row) {
		// Create the destination row
		$db->query('INSERT INTO ratings (user_id, table_id, type, hash, rating)
			VALUES('.USER_DESTINATION.', '.$row->table_id.', "'.$row->type.'", "'.$row->hash.'", '.$row->rating.')');
	}
	echo 'Transferred '.$select->rowCount().' rows.';

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>