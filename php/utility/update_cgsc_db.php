<?php
/**
 * DeepSID
 *
 * Update the database according to the original CGSC update batch file.
 * 
 * WARNING: THIS IS A PROTOTYPE SCRIPT THAT FOR NOW ONLY PERFORMS A SUBSET. IT
 * ONLY PARSES THE MOVE LINES FOR .MUS FILES FROM AN ADAPTED TEXT FILE.
 *
 * REMEMBER TO REPLACE "\" INTO "/" INSTEAD, AND LEAVE THE "MOVE" COMMAND IN
 * PLACE IN THE BEGINNING OF EACH LINE IN THE TEXT FILE.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

const CGSC_VERSION	= '147'; // Remember to update this
const CGSC_PATH		= "_Compute's Gazette SID Collection/";

try {
	$db = $account->getDB();

	foreach (file('../_update/move.txt') as $line) {

		// Remove newlines and stuff
		$line = preg_replace('/\s+/', ' ', trim($line));

		$parts = explode(' ', $line);
		echo 'MOVE: Changing collection path from "'.$parts[1].'" to "'.$parts[2].'"';

		$db->query('UPDATE files SET collection_path = "'.CGSC_PATH.$parts[2].'", updated = '.CGSC_VERSION.
		' WHERE collection_path = "'.CGSC_PATH.$parts[1].'"');

		echo ' => DONE<br />';
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>