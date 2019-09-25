<?php
/**
 * DeepSID
 *
 * Add a specific tag to all files in HVSC according to evidence that reveals
 * when it should have it. A file is ignored if the tag is already present.
 * 
 * Note that CGSC is not involved, only HVSC.
 */

require_once("class.account.php"); // Includes setup

// SET THE TAG PARSING MODE HERE
define('MODE', 'Game');

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get a list of all file rows in HVSC only
	$select = $db->query('SELECT * FROM hvsc_files WHERE fullname LIKE "_High Voltage SID Collection/%" ORDER BY id');
	$select->setFetchMode(PDO::FETCH_OBJ);

	// Get the ID of the relevant tag
	switch (MODE) {
		case 'Game':
			$tag = $db->query('SELECT id FROM tags_info WHERE name LIKE "Game" LIMIT 1');
			$tag ->setFetchMode(PDO::FETCH_OBJ);
			$tagid_game = $tag->fetch()->id;
			break;
	}

	echo 'Tag: "'.MODE.'" (ID: '.$tagid_game.')<br /><br />
		<style>body, table { font: normal 15px arial, sans-serif; } td { padding-right: 20px; }</style>
		<table style="text-align:left;"><tr><th>Action</th><th>ID</th><th>Fullname</th></tr>';

	//$test_max = 1000;

	// NOTE: Temporarily increase 'max_execution_time' to 800 in PHP.INI when done in LOCALHOST.
	// Don't worry about doing it online; it's crazy fast there (less than half a minute).
	foreach($select as $row) {
		switch (MODE) {
			case 'Game':
				// Does the file need to have this tag?
				if (!empty($row->application)) {
					// But wait, does it already have this tag?
					$tag = $db->query('SELECT 1 FROM tags_lookup WHERE files_id = '.$row->id.' AND tags_id = '.$tagid_game.' LIMIT 1');
					if ($tag->rowCount()) {
						echo '<tr><td>Tag already exists for</td><td>'.$row->id.'</td><td>'.$row->fullname.'</td></tr>';
					} else {
						$db->query('INSERT INTO tags_lookup (files_id, tags_id) VALUES('.$row->id.', '.$tagid_game.')');
						echo '<tr><td>Tag added to</td><td>'.$row->id.'</td><td>'.$row->fullname.'</td></tr>';
					}
				}
				break;
		}
		//$test_max--; if (!$test_max) die("</table><br />Test stop.");
	}

	echo "</table><br />Script 'update_tags.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>