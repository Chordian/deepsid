<?php
/**
 * DeepSID
 *
 * Parse a CSV file to update the corresponding SID file entries in a symlist.
 * 
 * This version works on a CSV file generated from a folder with single SID
 * files that were once in HVSC. Because of this, the script have to make a lot
 * of assumptions and detective work in order to figure out how the SID files
 * connect to their HVSC counterparts. For this to work, the CSV file has to be
 * generated with SIDInfo to grab a few extra fields, to help out with this.
 * 
 * Preparing the CSV file:
 * 
 *  1. Copy the latest 'SIDInfo.exe' into the folder with SID files.
 *  2. Run this in a command box in that folder:
 *       sidinfo -l; -f filename,name,author,copyright *.sid >_list.csv
 *  3. Open the '_list.csv' it produces and search for: \;
 *     @todo If it finds any, somehow mend it and save.
 *  4. Copy the '_list.csv' file into the './php' folder.
 *  5. Run this script in the web browser:
 *       LOCALHOST: http://chordian/deepsid/php/update_symlist.php
 *       ONLINE:    http://deepsid.chordian.net/php/update_symlist.php
 *  6. Study the output. The ones skipped must be added manually, and you may
 *     also want to check up on the blue text (adapted SQL retries) to see
 *     if the tunes match the corresponding one in the original folder.
 * 
 * WARNING! For some reason the ONLINE version times out like crazy. You may
 * have to split the CSV file up into parts no larger than 20-30 KB each.
 * 
 * @todo A possible update for more stability could be to include file-specific
 * values such as size, memory location and start address. It may not be worth
 * the trouble adding this, however, since manual work will probably always be
 * needed due to how files and attributes can be overhauled by the HVSC team.
 */

require_once("class.account.php"); // Includes setup

// Folder ID for custom symlist folder
// -----------------------------------
// GRG's Ultimate SID List: LOCALHOST = 2291, ONLINE = 2306.
define('SYMFOLDER', $_SERVER['HTTP_HOST'] == LOCALHOST ? 2291 : 2306);

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// -----------------------------------------------------------------------------------------------
	// @todo FOR NEXT UPDATE, ADD CODE HERE TO DELETE/REMOVE ALL PREVIOUS SYMLIST ROWS AND START OVER!
	// -----------------------------------------------------------------------------------------------

	// Read CSV file (split with semicolons, and remember to convert it to UTF8 first!)
	$row = 0;
	echo '<table>';
	if (($handle = fopen('_list.csv', 'r')) != false) {
		while (($line = fgetcsv($handle, 0, ';')) != false) {
	
			$filename = '/'.str_replace('.\\\\', '', $line[0]);
			$title = $line[1];
			$author = $line[2];
			$copyright = $line[3];
			echo '<tr><td>'.$filename.'</td><td>'.$title.'</td><td>'.$author.'</td><td>'.$copyright.'</td>';

			// The 'filename' and 'author' fields should be enough in most cases
			$select = $db->query('SELECT id FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection%" AND fullname LIKE "%'.$filename.'" AND author = "'.$author.'"');
			$select->setFetchMode(PDO::FETCH_OBJ);
			$rows_found = $select->rowCount();

			if ($rows_found == 1)
				echo '<td style="color:#0a0;">Found one row';
			else if (!$rows_found) {
				// Filename could have changed, try 'title' and 'author' instead
				$select = $db->query('SELECT id FROM hvsc_files WHERE name = "'.$title.'" AND author = "'.$author.'"');
				$select->setFetchMode(PDO::FETCH_OBJ);
				$rows_found = $select->rowCount();
				if ($rows_found == 1)
					echo '<td style="color:#00a;">Found using name';
				else if (!$rows_found) {
					// Entire title could have changed, try 'author' and 'copyright' instead (risky)
					$select = $db->query('SELECT id FROM hvsc_files WHERE author = "'.$author.'" AND copyright = "'.$copyright.'"');
					$select->setFetchMode(PDO::FETCH_OBJ);
					$rows_found = $select->rowCount();
					if ($rows_found == 1)
						echo '<td style="color:#00a;">Found using author (DOUBLE-CHECK)';
					else if (!$rows_found) {
						// Author could have changed, try 'filename' and 'copyright' instead
						$select = $db->query('SELECT id FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection%" AND fullname LIKE "%'.$filename.'" AND copyright = "'.$copyright.'"');
						$select->setFetchMode(PDO::FETCH_OBJ);
						$rows_found = $select->rowCount();
						if ($rows_found == 1)
							echo '<td style="color:#00a;">Found using copyright';
						else if (!$rows_found)
							echo '<td style="color:#a00;"><b>Found nothing!</b>';
						else
							echo '<td style="color:#a00;"><b>Found too many!</b>';
					}
					else
						echo '<td style="color:#a00;"><b>Found too many!</b>';
				} else
					echo '<td style="color:#a00;"><b>Found too many!</b>';
			} else {
				// Too many; add 'copyright' as a third option too
				$select = $db->query('SELECT id FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection%" AND fullname LIKE "%'.$filename.'" AND author = "'.$author.'" AND copyright = "'.$copyright.'"');
				$select->setFetchMode(PDO::FETCH_OBJ);
				$rows_found = $select->rowCount();
				if ($rows_found == 1)
					echo '<td style="color:#00a;">Added copyright too';
				else if (!$rows_found)
					echo '<td style="color:#a00;"><b>Found nothing!</b>';
				else {
					// Still too many; add 'title' too then
					$select = $db->query('SELECT id FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection%" AND fullname LIKE "%'.$filename.'" AND name = "'.$title.'" AND author = "'.$author.'" AND copyright = "'.$copyright.'"');
					$select->setFetchMode(PDO::FETCH_OBJ);
					$rows_found = $select->rowCount();
					if ($rows_found == 1)
						echo '<td style="color:#00a;">Added both copyright and title';
					else if (!$rows_found)
						echo '<td style="color:#a00;"><b>Found nothing!</b>';
					else
						echo '<td style="color:#a00;"><b>Found too many!</b>';
				}
			}
			echo '</td>';

			if ($rows_found == 1) {
				// So, that single row we found - is its symlist entry there already?
				$file_id = $select->fetch()->id;
				$select = $db->query('SELECT 1 FROM symlists WHERE folder_id = '.SYMFOLDER.' AND file_id = '.$file_id);
				if ($select->rowCount())
					echo '<td style="color:#0a0;">Already in the symlist';
				else {
					echo '<td style="color:#00a;">Not in symlist; <b>adding now</b>';
					// Add the symlist entry to the database
					// $db->query('INSERT INTO symlists (folder_id, file_id) VALUES('.SYMFOLDER.', '.$file_id.')');
				}
			} else
				echo '<td style="color:#a00;"><b>Skipped (handle manually)</b>';
			echo '</td><tr>';
	
			$row++;
			// if ($row == 100) break; // For testing purposes
		}
		fclose($handle);
		echo '</table><br/><i>Parsed '.$row.' CSV rows.</i>';

		// Update the file count for the symlist folder
		$select = $db->query('SELECT COUNT(1) as c FROM symlists WHERE folder_id = '.SYMFOLDER);
		$select->setFetchMode(PDO::FETCH_OBJ);

		$update = $db->query('UPDATE hvsc_folders SET files = '.$select->fetch()->c.' WHERE id = '.SYMFOLDER);
		if ($update->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not update the count of files for symlist folder ID '.SYMFOLDER)));

	} else
		die ('A file handle error occurred.');

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>