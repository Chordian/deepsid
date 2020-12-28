<?php
/**
 * DeepSID
 *
 * Update the database according to the original HVSC update script (and text
 * files) released with a new version on their web site.
 * 
 * When a new HVSC version arrives, the entire file tree is manually replaced
 * on the server. This script does NOT modify any of these files. It only
 * updates the database to match the file changes in the new tree.
 */

require_once("class.account.php"); // Includes setup

define('HVSC_VERSION', '74');
define('HVSC_PATH', '_High Voltage SID Collection/');

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	/**
	 * Read the entire file and start looping through it, line by line.
	 * 
	 * Only the commands MOVE and DELETE are handled. All the commands that
	 * update the binary data of the SID files are skipped because a number
	 * of CSV files and SQL commands will be executed instead afterwards.
	 * 
	 * -- MOVE ----------------------------------------------------------------
	 * 
	 * Careful, there are two versions. The one used in top creates new entries
	 * if the source comes from the 'update' folder and the target is a folder.
	 * 
	 * The second version used much later effectively renames existing entries
	 * as far as the database is concerned. 
	 * 
	 * Showing both versions below with details about how to handle them.
	 * 
	 * --------
	 * 
	 * #1 - Entry example for creating new entries:
	 * 
	 *   # from Wilfred: Crocketts_Theme.sid  Hiroshima_maxi.sid  Mix.sid
	 *   #               Rhythm_of_the_Night.sid  Shangai_Market.sid
	 *   #               (https://csdb.dk/release/?id=163564)
	 *   /update/new/MUSICIANS/C/Cobra/
	 *   /MUSICIANS/C/Cobra/
	 * 
	 * Creates the new entries. All SID files are extracted from the comment
	 * block, prepended the bottom folder path, then added in a loop. The table
	 * with folders is also checked to see if a new entry needs to be created.
	 * 
	 * NOTE: This depends on all of the comments to be trustworthy, which I am
	 * counting on. A safer method would have been to parse through the actual
	 * files, but using the comments is both easier and also much faster.
	 * 
	 * The HVSC version number will be added to the 'new' field.
	 * 
	 * --------
	 * 
	 * #2 - Entry example for renaming existing entries:
	 * 
	 *   /DEMOS/A-F/Compotune_Joe.sid
	 *   /DEMOS/A-F/Compotune.sid
	 *
	 *   - or -
	 * 
	 *   /DEMOS/UNKNOWN/Cover-Music.sid
	 *   /DEMOS/UNKNOWN/Master_Composer/
	 * 
	 *   - or -
	 * 
	 *   /DEMOS/UNKNOWN/Mission_Impossible_2.sid
	 *   /DEMOS/M-R/Mission_Impossible.sid
	 * 
	 * The first / line is used to find the 'fullname' which is then updated to
	 * the second line. If the second line is a target folder, the SID name is
	 * appended to it and the table with folders is checked to see if a new
	 * entry needs to be created here too.
	 * 
	 * There is also a version that only involves folders. Example:
	 * 
	 *   /MUSICIANS/W/Wizard/
	 *   /MUSICIANS/W/Wizard_Oxygen/
	 * 
	 * This means that the folder itself has been renamed. All entries in the
	 * table with files that uses the old folder have to updated, as well as
	 * the single entry in the table with folders.
	 * 
	 * NOTE: Retaining the same rows this way in the database is very important
	 * to ensure that the user rating links still work correctly.
	 * 
	 * The HVSC version number will be added to the 'updated' field.
	 * 
	 * -- DELETE --------------------------------------------------------------
	 * 
	 * Entry example:
	 * 
	 *   /MUSICIANS/0-9/4-Mat/Decline.sid
	 * 
	 * Simply deletes the entries found using the 'fullname' field.
	 * 
	 * In the clean-up section later, it may be used to delete a folder that
	 * was purged earlier. Example:
	 * 
	 *   /MUSICIANS/W/Wizard/
	 * 
	 * Here the corresponding entry in the folder table is deleted. However, be
	 * aware that the folder was probably renamed earlier (not to be deleted).
	 */

	$mode = $source = '';
	$sid_files = [];
	foreach (file('hvsc_update/Update'.HVSC_VERSION.'.hvs') as $line) {

		// Remove newlines and stuff
		$line = preg_replace('/\s+/', ' ', trim($line));

		if (in_array($line, array(
			'MOVE',
			'DELETE'))) {
				$mode = $line;
			 	echo $mode.'<br />';
		} else if (in_array($line, array(
			'REPLACE',
			'CREDITS',
			'TITLE',
			'AUTHOR',
			'RELEASED',
			'SONGS',
			'SPEED',
			'INITPLAY',
			'FLAGS',
			'CLOCK',
			'SIDMODEL',
			'FREEPAGE',
			'FIXLOAD',
			))) {
				$mode = ''; // Just ignore those commands
		}

		if ($mode == 'MOVE') {
			// Be greedy and eat all SID files you find until "/" entries are hit
			if (substr($line, 0, 1) == '#') {
				// A comment can list one or more SID files
				$parts = explode(' ', $line);
				foreach ($parts as $part) {
					if (strtolower(substr($part, -4)) == '.sid')
						array_push($sid_files, $part);
				}
			} else if(substr($line, 0, 1) == '/') {
				// Hit a HVSC path
				if (empty($source)) {
					// It's the first of two lines
					$source = substr($line, 1);
				} else {
					// Must be the second line
					$destination = substr($line, 1);
					if (strtolower(substr($source, 0, 7)) == 'update/' && substr($destination, -1) == '/') {

						// These are new SID files and have to be added as new database entries
						echo '&nbsp;&nbsp;New entries:<br />';
						foreach ($sid_files as $sid) {
							echo '&nbsp;&nbsp;&nbsp;&nbsp;- '.$destination.$sid.'<br />';

							$db->query('INSERT INTO hvsc_files (fullname, new)'.
								' VALUES("'.HVSC_PATH.$destination.$sid.'", '.HVSC_VERSION.')');

							// Better see if its folder already exists
							$folder = substr($destination, 0, -1);
							$select = $db->query('SELECT 1 FROM hvsc_folders WHERE fullname = "'.HVSC_PATH.$folder.'"');
							$select->setFetchMode(PDO::FETCH_OBJ);
							if (!$select->rowCount())
								// No, better create an entry for it then
								$db->query('INSERT INTO hvsc_folders (fullname) VALUES("'.HVSC_PATH.$folder.'")');
						}
					} else if (substr($source, -4) == '.sid' && substr($destination, -4) == '.sid') {

						// A SID file is merely renamed
						echo '&nbsp;&nbsp;Renaming one file: '.$source.'&nbsp;&nbsp;=>&nbsp;&nbsp;'.$destination.'<br />';

						$db->query('UPDATE hvsc_files SET fullname = "'.HVSC_PATH.$destination.'", updated = '.HVSC_VERSION.
							' WHERE fullname = "'.HVSC_PATH.$source.'" LIMIT 1');

					} else if (substr($source, -4) == '.sid' && substr($destination, -1) == '/') {

						// A SID file is moved to another folder (rename file entry + check folder table)
						$file = substr($source, strrpos($source, '/') + 1);
						echo '&nbsp;&nbsp;One file to folder: '.$source.'&nbsp;&nbsp;=>&nbsp;&nbsp;'.$destination.$file.'<br />';

						$db->query('UPDATE hvsc_files SET fullname = "'.HVSC_PATH.$destination.$file.'", updated = '.HVSC_VERSION.
							' WHERE fullname = "'.HVSC_PATH.$source.'" LIMIT 1');
						
						// But does that destination folder exist in the database?
						$folder = substr($destination, 0, -1);
						$select = $db->query('SELECT 1 FROM hvsc_folders WHERE fullname = "'.HVSC_PATH.$folder.'"');
						$select->setFetchMode(PDO::FETCH_OBJ);
						if (!$select->rowCount())
							// No, better create an entry for it then
							$db->query('INSERT INTO hvsc_folders (fullname) VALUES("'.HVSC_PATH.$folder.'")');

					} else if (substr($source, -1) == '/' && substr($destination, -1) == '/') {

						// Folder renaming (all file entries must be updated as well as the folder)
						echo '&nbsp;&nbsp;Renaming a folder: '.$source.'&nbsp;&nbsp;=>&nbsp;&nbsp;'.$destination.'<br />';

						// First get a list of all files that uses that folder
						$select = $db->query('SELECT fullname FROM hvsc_files WHERE fullname LIKE "'.HVSC_PATH.$source.'%"');
						$select->setFetchMode(PDO::FETCH_OBJ);
						if ($select->rowCount()) {
							foreach($select as $row) {
								// Now update every single one with the new destination folder
								$file = substr($row->fullname, strrpos($row->fullname, '/') + 1);
								$db->query('UPDATE hvsc_files SET fullname = "'.HVSC_PATH.$destination.$file.'", updated = '.HVSC_VERSION.
									' WHERE fullname = "'.$row->fullname.'"');
							}
						}
	
						// Finally rename the folder entry itself
						$db->query('UPDATE hvsc_folders SET fullname = "'.HVSC_PATH.substr($destination, 0, -1).'"'.
							' WHERE fullname = "'.HVSC_PATH.substr($source, 0, -1).'"');

					} else {
						echo '&nbsp;&nbsp;<span style="color:#f00;">ERROR: UNKNOWN MOVE COMMAND</span><br />';
					}

					$source = '';
					$sid_files = [];
				}
			}
		} else if ($mode == 'DELETE' && substr($line, 0, 1) == '/' && substr($line, 0, 8) != '/update/') {
			$target = substr($line, 1);
			if (substr($line, -4) == '.sid') {

				// One file to be deleted
				echo '&nbsp;&nbsp;Delete one file: '.$target.'<br />';

				$db->query('DELETE FROM hvsc_files WHERE fullname = "'.HVSC_PATH.$target.'" LIMIT 1');

			} else if (substr($line, -1) == '/') {

				// A folder to be deleted (usually renamed away earlier and thus not performed)
				echo '&nbsp;&nbsp;Delete one folder: '.$target.' (should have been renamed away earlier)<br />';

			} else {
				echo '&nbsp;&nbsp;<span style="color:#f00;">ERROR: UNKNOWN DELETE COMMAND</span><br />';
			}
		}
	}

	/**
	 * After running the script, the following CSV and SQL updating should be
	 * accomplished as well:
	 * 
	 * Parse songlengths
	 * Parse STIL
	 * Add players
	 * Add SIDId stuff
	 * 
	 * Refer to the importing text file for details.
	 */

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>