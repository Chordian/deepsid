<?php
/**
 * DeepSID
 *
 * Parse a CSV file to update the corresponding SID file entries in a symlist.
 * 
 * This version works on a CSV file with full HVSC paths to the SID files,
 * thereby eliminating the need for assumptions and detective work.
 * 
 * Preparing the CSV file:
 * 
 *  1. The CSV files must have three fields per line, separated by semi-colons;
 *     the SID file (including HVSC path), the sub tune, and a renamed entry.
 * 
 *     Example of one line:
 * 
 *       /MUSICIANS/H/Hubbard_Rob/Phantoms_of_the_Asteroid.sid;2;POTA Jingle
 * 
 *     If a renaming is not required, leave the entry empty (end with ";").
 * 
 *  2. Save it as '_list.csv' and in UTF8.
 *  3. Copy the '_list.csv' file into the './php' folder.
 *  4. Run this script in the web browser:
 *       LOCALHOST: http://chordian/deepsid/php/import_symlist_hvsc.php
 *       ONLINE:    https://deepsid.chordian.net/php/import_symlist_hvsc.php
 *  5. Study the output. There will be red errors if a HVSC path wasn't found
 *     in the database and the line was skipped because of it.
 * 
 * WARNING! If the CSV file is larger than 20-30 KB, you may have to split it
 * up to avoid the ONLINE version timing out.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

// Folder ID for custom symlist folder
// -----------------------------------
// EC64SC: LOCALHOST = 2299, ONLINE = 2309.
define('SYMFOLDER', $_SERVER['HTTP_HOST'] == LOCALHOST ? 2299 : 2309);

try {
	$db = $account->GetDB();

	$row = 0;
	echo '<table><tr><th>HVSC File</th><th>ST</th><th>Renamed To</th><th>Status</th></tr>';
	if (($handle = fopen('_list.csv', 'r')) != false) {
		while (($line = fgetcsv($handle, 0, ';')) != false) {
	
			$fullname = substr($line[0], 0, 1) == '/' ? substr($line[0], 1) : $line[0];
			$fullname = '_High Voltage SID Collection/'.$fullname;
			$subtune = $line[1];
			$substname = $line[2];
			echo '<tr><td>'.$fullname.'</td><td>'.$subtune.'</td><td>'.$substname.'</td>';

			// Find HVSC file
			$select = $db->query('SELECT id FROM hvsc_files WHERE fullname ="'.$fullname.'" LIMIT 1');
			$select->setFetchMode(PDO::FETCH_OBJ);
			if (!$select->rowCount())
				echo '<td style="color:#a00;"><b>HVSC path not found!</b>';
			else {
				// So, that single row we found - is its symlist entry there already?
				$file_id = $select->fetch()->id;
				$select = $db->query('SELECT 1 FROM symlists WHERE folder_id = '.SYMFOLDER.' AND file_id = '.$file_id.' AND subtune = '.$subtune);
				if ($select->rowCount())
					echo '<td style="color:#0a0;">Already in the symlist with that sub tune';
				else {
					echo '<td style="color:#00a;">Not in symlist; <b>adding now</b>';
					// Add the symlist entry to the database
					$db->query('INSERT INTO symlists (folder_id, file_id, sidname, subtune)'.
						' VALUES('.SYMFOLDER.','.$file_id.',"'.$substname.'",'.$subtune.')');
				}
			}
			echo '</td><tr>';
			$row++;
		}
		fclose($handle);
		echo '</table><br/><i>Parsed '.$row.' CSV rows.</i>';

		// Update the file count for the symlist folder
		$select = $db->query('SELECT COUNT(1) as c FROM symlists WHERE folder_id = '.SYMFOLDER);
		$select->setFetchMode(PDO::FETCH_OBJ);

		$update = $db->query('UPDATE hvsc_folders SET files = '.$select->fetch()->c.' WHERE id = '.SYMFOLDER);
		if ($update->rowCount() == 0)
			die('<br /><br />Could not update the count of files for symlist folder ID '.SYMFOLDER);

	} else
		die('A file handle error occurred.');

} catch(PDOException $e) {
	echo '<br />ERROR: '.$e->getMessage();
}
?>