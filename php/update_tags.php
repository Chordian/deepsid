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

define('MODE_GAME',	'Game'); // @todo Make the code test for 'Game Prev' tag too (don't add 'Game' if exists!)
define('MODE_COOP',	'Coop');
define('MODE_UNF',	'Unfinished');
define('MODE_TINY', 'Tiny');

define('MODE', MODE_TINY); // <---- SET THE TAG PARSING MODE HERE!

function GetTagID($name) {

	global $db;

	$tag = $db->query('SELECT id FROM tags_info WHERE name LIKE "'.$name.'" LIMIT 1');
	$tag ->setFetchMode(PDO::FETCH_OBJ);
	return $tag->fetch()->id;
}

function AddTag($tagid) {

	global $db, $row;

	// Does the row already have this tag?
	$tag = $db->query('SELECT 1 FROM tags_lookup WHERE files_id = '.$row->id.' AND tags_id = '.$tagid.' LIMIT 1');
	if ($tag->rowCount()) {
		echo '<tr><td>Tag already exists for</td><td>'.$row->id.'</td><td>'.$row->fullname.'</td></tr>';
	} else {
		// No; add it now
		$db->query('INSERT INTO tags_lookup (files_id, tags_id) VALUES('.$row->id.', '.$tagid.')');
		echo '<tr><td>Tag added to</td><td>'.$row->id.'</td><td>'.$row->fullname.'</td></tr>';
	}
}

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

	// Get the ID of the relevant tag name
	$tagid = GetTagID(MODE);

	echo 'Tag: "'.MODE.'" (ID: '.$tagid.')<br /><br />
		<style>body,table { font: normal 15px arial, sans-serif; } td { padding-right: 20px; }</style>
		<table style="text-align:left;"><tr><th>Action</th><th>ID</th><th>Fullname</th></tr>';

	//$test_max = 1000;

	// NOTE: LOCALHOST can be slow - you can temporarily active the '$test_max' variable for testing.
	foreach ($select as $row) {
		switch (MODE) {
			case MODE_GAME:
				// Condition: The 'application' field is used (indicating GB64 activity)
				if (!empty($row->application))
					AddTag($tagid);
				break;
			case MODE_COOP:
				// Condition: The 'author' field must be like e.g. "Stan & Laurel"
				if (strpos($row->author, ' & '))
					AddTag($tagid);
				break;
			case MODE_UNF:
				// Condition: The 'fullname' field must have "/Worktunes" in it
				if (strpos($row->fullname, '/Worktunes'))
					AddTag($tagid);
				break;
			case MODE_TINY:
				// Condition: Number of bytes in the 'datasize' field must be less than 512
				if ($row->datasize < 512)
					AddTag($tagid);
				break;
			}
		if (isset($test_max)) {
			$test_max--;
			if (!$test_max) die("</table><br />Test stop.");
		}
	}

	echo "</table><br />Script 'update_tags.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>