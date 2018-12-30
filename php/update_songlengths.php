<?php
/**
 * DeepSID
 *
 * Take all time length fields in 'hvsc_files', split them up into subtune
 * parts, then saves them as individual rows in the 'hvsc_lengths' table.
 * 
 * This table is used for displaying a top 20 list of the longest tunes. A
 * table for this is necessary as the SQL required to generate it directly
 * from the column in 'hvsc_files' is extremely complicated and may even
 * require access not granted by my web hotel.
 * 
 * The table should be emptied and this script run again after a HVSC update.
 */

require_once("class.account.php"); // Includes setup

define('HVSC_ROOT', '_High Voltage SID Collection/');

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get a list of all file rows in HVSC only
	// NOTE: Tunes in CGSC currently don't have exact lengths tracked and thus are skipped.
	$select = $db->query('SELECT id, fullname, lengths FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection/%" ORDER BY id');
	$select->setFetchMode(PDO::FETCH_OBJ);

	// NOTE: Temporarily increase 'max_execution_time' to 800 in PHP.INI when done in LOCALHOST.
	// Don't worry about doing it online; it's crazy fast there (less than half a minute).
	foreach($select as $row) {
		$lengths = explode(' ', $row->lengths);
		foreach($lengths as $key => $length)
			// $db->query('INSERT INTO hvsc_lengths (fullname, length, subtune) VALUES("'.$row->fullname.'", "'.$length.'", '.$key.')');
	}

	echo "Script 'update_songlengths.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>