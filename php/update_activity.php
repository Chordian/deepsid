<?php
/**
 * DeepSID
 *
 * Finds the latest year each composer was active and stores it in the 'active'
 * column in the 'composers' table. This is only relevant for HVSC.
 * 
 * The year is determined by checking the beginning of the 'copyright' column
 * for each file belonging to each composer in the 'hvsc_files' table.
 * 
 * This table is used for displaying the lists of active and snoozing composers
 * in the root page. Direct queries were actually written and tested in the
 * script for the root page, but alas they were way too slow.
 * 
 * This script should be run again after a HVSC update.
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

	// Get a list of all file rows in HVSC only (in the MUSICIANS folder and SINGLE composers only)
	$folders = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "%/MUSICIANS/%" AND type = "SINGLE"');
	$folders->setFetchMode(PDO::FETCH_OBJ);

	echo '
		<style>
			div {border:1px solid #bbb;padding:6px;margin:2px 0;font-size:12px;}
			del {color:#999;}
		</style>';

	foreach($folders as $folder) {
		echo '<b>'.$folder->fullname.'</b><div>';

		// Get the copyright for all of the files belonging to this composer
		$files = $db->query('SELECT copyright FROM hvsc_files WHERE fullname LIKE "'.$folder->fullname.'/%"');
		$files->setFetchMode(PDO::FETCH_OBJ);

		// Add the year from each copyright to an array
		$years = array();
		foreach($files as $file) {
			$year = substr($file->copyright, 0, 4);
			if (is_numeric($year)) {
				$years[] = $year;
				echo $year.' ';
			} else
				echo '<del>'.$year.'</del> ';
		}
		if (count($years))
			sort($years); // Sort it so the end has the latest year
		else
			$years[0] = '0000';
		echo '</div>Latest activity: '.end($years).'<br /><br />';

		// Store the year in the database
		$db->query('UPDATE composers SET active = "'.end($years).'" WHERE fullname = "'.$folder->fullname.'" LIMIT 1');
	}

	echo "Script 'update_activity.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>