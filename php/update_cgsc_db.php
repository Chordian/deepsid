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

define('CGSC_VERSION', '147'); // Remember to update this
define('CGSC_PATH', "_Compute's Gazette SID Collection/");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	foreach (file('move.txt') as $line) {

		// Remove newlines and stuff
		$line = preg_replace('/\s+/', ' ', trim($line));

		$parts = explode(' ', $line);
		echo 'MOVE: Changing fullname from "'.$parts[1].'" to "'.$parts[2].'"';

		$db->query('UPDATE hvsc_files SET fullname = "'.CGSC_PATH.$parts[2].'", updated = '.CGSC_VERSION.
		' WHERE fullname = "'.CGSC_PATH.$parts[1].'"');

		echo ' => DONE<br />';
	}

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>