<?php
/**
 * DeepSID
 *
 * Add a specific tag to all files in HVSC according to evidence that reveals
 * when it should have it. A file is ignored if the tag is already present.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

const MODE_COOP 	= 'Coop';
const MODE_UNF 		= 'Unfinished';
const MODE_TINY 	= 'Tiny';
const MODE_PURE 	= 'Pure';
const MODE_LONG 	= 'Long';
const MODE_SHORT 	= 'Short';
const MODE_LYRICS	= 'Lyrics'; // CGSC only

const MODE = MODE_LYRICS; // <---- SET THE TAG PARSING MODE HERE!

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Get the ID of a tag by looking up its name.
 *
 * @global		object		$db					database connection
 * 
 * @param		string		$name				tag name
 *
 * @return		int								tag id
 */
function getTagID($name) {

	global $db;

	$tag = $db->query('SELECT id FROM tags_info WHERE name LIKE "'.$name.'" LIMIT 1');
	$tag ->setFetchMode(PDO::FETCH_OBJ);
	return $tag->fetch()->id;
}

/**
 * Add the tag unless it already exists.
 *
 * @param		int			$tag_id				tag id
 */
function addTag($tag_id) {

	global $db, $row;

	// Does the row already have this tag?
	$tag = $db->query('SELECT 1 FROM tags_lookup WHERE files_id = '.$row->id.' AND tags_id = '.$tag_id.' LIMIT 1');
	if ($tag->rowCount()) {
		echo '<tr><td>Tag already exists for</td><td>'.$row->id.'</td><td>'.$row->collection_path.'</td></tr>';
	} else {
		// No; add it now
		$db->query('INSERT INTO tags_lookup (files_id, tags_id) VALUES('.$row->id.', '.$tag_id.')');
		echo '<tr><td>Tag added to</td><td>'.$row->id.'</td><td>'.$row->collection_path.'</td></tr>';
	}
}

// --------------------------------------------------------------------------
// START
// --------------------------------------------------------------------------

try {
	$db = $account->getDB();

	$collection = MODE == MODE_LYRICS ? "_Compute's Gazette SID Collection/%" : '_High Voltage SID Collection/%';
	// Get a list of all file rows in the relevant collection
	$select = $db->query('SELECT * FROM files WHERE collection_path LIKE "'.$collection.'" ORDER BY id');
	$select->setFetchMode(PDO::FETCH_OBJ);

	// Get the ID of the relevant tag name
	$tag_id = getTagID(MODE);

	echo 'Tag: "'.MODE.'" (ID: '.$tag_id.')<br /><br />
		<style>body,table { font: normal 15px arial, sans-serif; } td { padding-right: 20px; }</style>
		<table style="text-align:left;"><tr><th>Action</th><th>ID</th><th>Collection path</th></tr>';

	// $test_max = 1000;

	// NOTE: LOCALHOST can be slow - you can temporarily active the '$test_max' variable for testing.
	foreach ($select as $row) {
		switch (MODE) {
			case MODE_COOP:
				// Condition: The 'author' field must be like e.g. "Stan & Laurel"
				if (strpos($row->author, ' & '))
					addTag($tag_id);
				break;
			case MODE_UNF:
				// Condition: The 'collection_path' field must have "/Worktunes" in it
				if (strpos($row->collection_path, '/Worktunes'))
					addTag($tag_id);
				break;
			case MODE_TINY:
				// Condition: Number of bytes in the 'data_size' field must be less than 512
				if ($row->data_size < 512)
					addTag($tag_id);
				break;
			case MODE_PURE:
				// Condition: The 'player' field must have "Master_Composer" in it
				if ($row->player == 'Master_Composer')
					addTag($tag_id);
				break;
			case MODE_LONG:
				// Condition: One of the sub tunes is longer than 10 minutes
				$lengths = explode(' ', $row->lengths);
				foreach ($lengths as $length) {
					if (substr($length, 0, 2) >= 10)
						addTag($tag_id);
					break;
				}
			case MODE_SHORT:
				// Condition: None of all the sub tunes are longer than 10 seconds
				$lengths = explode(' ', $row->lengths);
				$all_short = true;
				foreach ($lengths as $length) {
					$min_sec = explode(':', $length);
					if ($min_sec[0] > 0 || $min_sec[1] > 10)
						$all_short = false; // It's too long
				}
				if ($all_short) addTag($tag_id);
				break;
			case MODE_LYRICS:
				// Condition: If there's an accompanying .WDS file thereby indicating that lyrics exists (CGSC)
				if (file_exists(ROOT_HVSC.'/'.substr($row->collection_path, 0, -4).'.wds'))
					addTag($tag_id);
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