<?php
/**
 * DeepSID
 *
 * Get an array of SID files from the specified folder, or perform a search
 * query if specified.
 * 
 * @uses		$_GET['folder']
 * @uses		$_GET['searchType']
 * @uses		$_GET['searchQuery']		overrides 'folder' if used
 * @uses		$_GET['searchHere']			1 = in current folder, 0 = in everything
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");
require_once("tags_read.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

/**
 * Determine who owns a public symlist specified in the GET variable.
 * 
 * @global		object		$db					database connection
 *
 * @return		string		$owner 				user name
 */
function PublicSymlistOwner() {

	global $db;

	// First get its user ID
	$select = $db->prepare('SELECT user_id FROM hvsc_folders WHERE fullname = :folder LIMIT 1');
	$select->execute(array(':folder'=>substr($_GET['folder'], 1)));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$owner = 'an unknown user';
	if ($select->rowCount()) {
		// Now get the name of the user ID
		$select_user = $db->query('SELECT username FROM users WHERE id = '.$select->fetch()->user_id.' LIMIT 1');
		$select_user->setFetchMode(PDO::FETCH_OBJ);
		if ($select_user->rowCount())
			$owner = $select_user->fetch()->username;
	}
	return $owner;
}

/**
 * Adapt the search query and create an array of words.
 *
 * @param		string		$query				search query from a GET variable
 *
 * @return		array							array with individual search words
 */
function ParseQuery($query) {

	// Replace spaces ('_') inside quoted queries with '%' and remove the quotes themselves
	// NOTE: This is actually a weird shortcut and sometimes produce unexpected results.
	preg_match_all('/"[^"]+"/', $query, $quoted);
	foreach($quoted[0] as $q) {
		$adapted = trim(str_replace('_', '%', $q), '"');
		$query = str_replace($q, $adapted, $query);
	}
	// Get rid of any lonely quote stragglers and return an array
	return explode('_', str_replace('"', '', $query));
}

/***** START *****/

$found = $symlist_folder_id = 0;
$debug = $incompatible = $owner = $new_uploads = '';
$user_id = $account->CheckLogin() ? $account->UserID() : 0;
$isSearching = isset($_GET['searchQuery']) && !empty($_GET['searchQuery']);
$isPersonalSymlist = substr($_GET['folder'], 0, 2) == '/!';
$isPublicSymlist = substr($_GET['folder'], 0, 2) == '/$';
// NOTE: A comparison of the 'COMPO' type is performed below that may also set these variables.
$isCSDbFolder = substr($_GET['folder'], 0, 24) == '/CSDb Music Competitions';
$isCSDbCompo = $isCSDbFolder && !$isSearching;
$compoName = $isCSDbCompo && strlen($_GET['folder']) > 25 ? explode('/', $_GET['folder'])[2] : '';

$folders_version = HVSC_VERSION;
$search_shortcut_type = array();
$search_shortcut_query = array();
$redirect_folder = array();

// In current folder or everything?
$searchContext = '1';
if ($_GET['searchHere'])
	$searchContext = $isCSDbFolder
		? 'type = "COMPO"' // This will also be compared in hvsc_files but it exists and is never "COMPO" anyway
		: 'fullname LIKE "'.substr($_GET['folder'], 1).'%"';

try {

	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if ($isSearching) {

		// This tricky logic disallows symlists unless searching for everything
		if ((!$isPublicSymlist && !$isPersonalSymlist) ||
			(!$_GET['searchHere'] && ($isPublicSymlist || $isPersonalSymlist))) {

			// SEARCH PHYSICAL FILES

			// Perform a search query and fill the array with the results of fullnames
			$select = null;
			if ($_GET['searchType'] == 'rating') {

				// Search for a specific user rating (1-5) or a range (e.g. -3 or 3-)
				$operators = substr($_GET['searchQuery'], 0, 1) == '-' ? '<='
					: (substr($_GET['searchQuery'], -1) == '-' ? '>=' : '=');
				$select = $db->prepare('SELECT fullname FROM hvsc_files'.
					' INNER JOIN ratings ON hvsc_files.id = ratings.table_id'.
					' WHERE '.$searchContext.' AND ratings.user_id = '.$user_id.' AND ratings.rating '.$operators.' :rating AND ratings.type = "FILE" LIMIT 1000');
				$select->execute(array(':rating'=>str_replace('-', '', $_GET['searchQuery'])));

			} else if ($_GET['searchType'] == 'tag') {

				// Search for one or more tags
				$tag_list = '';
				$search_tags = ParseQuery($_GET['searchQuery']);
				foreach($search_tags as $tag)
					$tag_list .= ' OR tags_info.name LIKE "%'.$tag.'%"';

				$select = $db->query('SELECT fullname FROM hvsc_files'.
					' LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id'.
					' LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id'.
					' WHERE '.str_replace('fullname', 'hvsc_files.fullname', $searchContext).
					' AND ('.substr($tag_list, 4).')'.
					' GROUP BY tags_lookup.files_id'.
					' HAVING COUNT(*) = '.count($search_tags).' LIMIT 1000');

			} else if ($_GET['searchType'] == 'location') {

				$select = $db->prepare('SELECT fullname FROM hvsc_files'.
					' WHERE '.$searchContext.' AND loadaddr = :loadaddr LIMIT 1000');
				$location = $_GET['searchQuery'];
				if (substr($location, 0, 1) == '$')
					$location = hexdec(substr($location, 1));
				else if (substr($location, 0, 2) == '0x')
					$location = hexdec(substr($location, 2));
				$select->execute(array(':loadaddr'=>$location));

			} else if ($_GET['searchType'] == 'maximum') {

				$select = $db->prepare('SELECT fullname FROM hvsc_files'.
					' WHERE '.$searchContext.' AND datasize <= :datasize AND fullname LIKE "_High Voltage SID Collection%" LIMIT 1000');
				$datasize = $_GET['searchQuery'];
				if (substr($datasize, 0, 1) == '$')
					$datasize = hexdec(substr($datasize, 1));
				else if (substr($datasize, 0, 2) == '0x')
					$datasize = hexdec(substr($datasize, 2));
				$select->execute(array(':datasize'=>$datasize));

			} else if ($_GET['searchType'] == 'type') {

				$select = $db->prepare('SELECT fullname FROM hvsc_files'.
					' WHERE '.$searchContext.' AND type = :type LIMIT 1000');
				$select->execute(array(':type'=>$_GET['searchQuery']));

			} else if ($_GET['searchType'] == 'latest') {

				$words = explode(',', $_GET['searchQuery']);
				if (count($words) == 1) {
					$query = $words[0];
					$version = HVSC_VERSION;	// For queries like "laxity/"
				} else {
					$query = $words[0];
					$version = $words[1];		// For queries like "laxity/,74"
				}
				$select = $db->query('SELECT fullname from hvsc_files'.
					' WHERE new = "'.$version.'" AND (fullname LIKE "%'.$query.'%" OR author LIKE "%'.$query.'%") LIMIT 1000');

			} else if ($_GET['searchType'] == 'folders') {

				// Don't find any files for this one

			} else if ($_GET['searchType'] == 'special') {

				switch(strtolower($_GET['searchQuery'])) {

					case 'multispeed':

						// Search for all multispeed types (2x, 3x, 4x, etc.)
						$select = $db->query('SELECT fullname FROM hvsc_files'.
							' LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id'.
							' LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id'.
							' WHERE tags_info.name IN ("multispeed", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x", "11x", "12x", "13x", "14x", "15x", "16x")'.
							' GROUP BY tags_lookup.files_id LIMIT 1000');
						break;

					case 'multisid':

						// Search for all multisid types (2SID, 3SID, etc.)
						$select = $db->query('SELECT fullname FROM hvsc_files'.
							' WHERE fullname REGEXP "_2SID|_3SID|_4SID|_8SID|_10SID" LIMIT 1000');
						break;

					case 'gamecomposers':

						// Search for most popular game composers in one big list
						$select = $db->query('SELECT fullname FROM hvsc_files'.
							' LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id'.
							' LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id'.
							' WHERE author REGEXP "Rob Hubbard|Martin Galway|Fred Gray|Wally Beben|Neil Brennan|Ben Daglish|Charles Deenen|Tim Follin|Geoff Follin|Matt Gray|Chris Hülsbeck|Richard Joseph|Russell Lieblich|Reyn Ouwehand|Jeroen Tel|Steve Turner|Martin Walker|Johannes Bjerregaard|David Dunn|Laxity|Yip"'.
							' AND tags_info.name LIKE "%Game" LIMIT 1000');
						break;

					default:

						// Don't find anything if not a recognized special search
				}

			} else if ($_GET['searchType'] != 'country') { // ALL OTHER SEARCH TYPES

				// Normal type search (handles any position of words and excluding with "-" prepended)
				// NOTE: This would have been easier with 'Full-Text' search but I'm not using the MyISAM engine.
				$exclude = '';
				if ($_GET['searchType'] == 'new') {
					$include = $_GET['searchType'].' LIKE "%'.str_replace('.', '', $_GET['searchQuery']).'%"';
				} else {
					$words = ParseQuery($_GET['searchQuery']);
					$include = '(';
					$i_and = $e_and = '';
					foreach($words as $word) {
						if (substr($word, 0, 1) == '-') {
							$exclude .= $e_and.$_GET['searchType'].' NOT LIKE "%'.substr($word, 1).'%"';
							$e_and = ' AND ';
						} else {
							$include .= $i_and.$_GET['searchType'].' LIKE "%'.$word.'%"';
							$i_and = ' AND ';
						}
					}
					$include .= ')';
					if (!empty($exclude)) $exclude = ' AND ('.$exclude.')';

					if ($_GET['searchType'] == '#all#') {
						// Searching ALL should of course include a range of columns
						$columns = $comma = '';
						foreach(array('fullname', 'author', 'copyright', 'player', 'stil', 'gb64') as $column) {
							$columns .= $comma.$column.', " "';
							$comma = ', ';
						}
						// Treating all columns as one long search entity is MUCH easier
						$include_folders = $include;
						$exclude_folders = $exclude;
						$include = str_replace('#all#', 'CONCAT('.$columns.')', $include);
						$exclude = str_replace('#all#', 'CONCAT('.$columns.')', $exclude);
					}
				}
				$select = $db->query('SELECT fullname FROM hvsc_files WHERE '.$searchContext.' AND '.$include.$exclude.' LIMIT 1000');
			}

			$files = array();
			if ($select) {
				$select->setFetchMode(PDO::FETCH_OBJ);

				$found = $select->rowCount();

				foreach ($select as $row)
					$files[] = $row->fullname;
			}

			// SEARCH PHYSICAL FOLDERS

			// Repeat search query again but this time for folders
			// NOTE: Notice the extra "NOT LIKE" to avoid finding personal playlists by other users.
			$select = null;
			if ($_GET['searchType'] == 'rating') {

				$select = $db->prepare('SELECT fullname FROM hvsc_folders'.
					' INNER JOIN ratings ON hvsc_folders.id = ratings.table_id'.
					' WHERE '.$searchContext.' AND ratings.user_id = '.$user_id.' AND ratings.rating '.$operators.' :rating AND ratings.type = "FOLDER" AND (fullname NOT LIKE "!%") LIMIT 1000');
				$select->execute(array(':rating'=>str_replace('-', '', $_GET['searchQuery'])));

			} else if ($_GET['searchType'] == 'country') {

				// Search for country in composer profiles
				$select = $db->prepare('SELECT fullname FROM composers WHERE '.$searchContext.' AND country LIKE :query LIMIT 1000');
				$query = strtolower($_GET['searchQuery']) == 'holland' ? 'netherlands' : $_GET['searchQuery'];
				$select->execute(array(':query'=>'%'.$query.'%'));

			} else if ($_GET['searchType'] == 'folders') {

				// Search for folders affected by the specified 'new' version
				$select = $db->prepare('SELECT DISTINCT hvsc_folders.fullname FROM hvsc_folders'.
					' INNER JOIN hvsc_files ON hvsc_files.fullname LIKE CONCAT("%", hvsc_folders.fullname ,"/%")'.
					' WHERE hvsc_files.new = :version AND LENGTH(hvsc_folders.fullname) - LENGTH(REPLACE(hvsc_folders.fullname, "/", "")) > 2');
				$folders_version = $_GET['searchQuery'];
				$select->execute(array(':version'=>$folders_version));

			/*} else if ($_GET['searchType'] == 'special') { // NOT USED

				switch(strtolower($_GET['searchQuery'])) {

					case 'mon':

						$select = $db->query('SELECT fullname FROM hvsc_folders WHERE id = 437 OR id = 841 OR id = 1458 OR id = 390 OR id = 903 OR id = 192 OR id = 1511 OR id = 865 OR id = 657 OR id = 733 OR id = 1078');
						break;

					case 'vibrants':

						$select = $db->query('SELECT fullname FROM hvsc_folders WHERE id = 389 OR id = 437 OR id = 731 OR id = 746 OR id = 841 OR id = 857 OR id = 954');
						break;

					default:

						// Don't find anything if not a recognized special search
				}*/

			} else if ($_GET['searchType'] == '#all#' || $_GET['searchType'] == 'fullname' || $_GET['searchType'] == 'author' || $_GET['searchType'] == 'new') {

				$fullnames = '';

				// Normal type search
				if ($_GET['searchType'] == 'author') {

					// Let 'author' also find folders using 'fullname' as replacement type
					$exclude = str_replace('author NOT LIKE "%', 'fullname NOT LIKE "%', $exclude);
					$include = str_replace('author LIKE "%', 'fullname LIKE "%', $include);

				} else if ($_GET['searchType'] == '#all#') {

					// Search the 'composers' table to see if the query matches the real name
					// This makes it possible to search for e.g. "Max Hall" and see his "../Max_F3H" folder.
					$composers = $db->query('SELECT fullname FROM composers WHERE '.str_replace('#all#', 'name', $include_folders).' LIMIT 1000');
					$composers->setFetchMode(PDO::FETCH_OBJ);

					foreach($composers as $composer_row)
						$fullnames .= 'OR fullname = "'.$composer_row->fullname.'" ';

					// Just search 'fullname' - none of the other columns exist in this table
					$include = str_replace('#all#', 'fullname', $include_folders);
					$exclude = str_replace('#all#', 'fullname', $exclude_folders);
				}
				$query = 'SELECT fullname FROM hvsc_folders WHERE '.$searchContext.' AND '.$include.$exclude.' AND (fullname NOT LIKE "!%") AND (fullname NOT LIKE "_High Voltage SID Collection/^%") '.$fullnames.' LIMIT 1000';
				$select = $db->query($query);
				//die($query);
			}

			if ($select) {
				$select->setFetchMode(PDO::FETCH_OBJ);

				$found += $select->rowCount();

				foreach ($select as $row) {
					$files[] = $row->fullname;

					if ($_GET['searchType'] == 'folders') {

						// This will turn the folders in the result list into search shortcuts
						$search_shortcut_type[$row->fullname] = 'latest';
						$parts = explode("/", $row->fullname);
						$search_shortcut_query[$row->fullname] = '/'.end($parts).'/,'.$folders_version; // "/Foo/,72"

					} else if (strpos($row->fullname, '/GROUPS/') !== false) {

						// Include where the group member folder will redirect to
						$group = explode('/', $row->fullname)[2];

						$select_groups = $db->query('SELECT folder, redirect FROM groups WHERE name = "'.$group.'"');
						$select_groups->setFetchMode(PDO::FETCH_OBJ);

						foreach($select_groups as $member) {
							if (strtolower(substr($row->fullname, 36)) == strtolower($group.'/'.$member->folder))
								$redirect_folder[$row->fullname] = $member->redirect;
						}
					}
				}
			}

		} else {

			// SEARCH IN SYMLIST

			$files = array();

			// First get the ID of the symlist
			$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :fullname'.($isPersonalSymlist ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select_folder->execute(array(':fullname'=>substr($_GET['folder'], 1)));
			$select_folder->setFetchMode(PDO::FETCH_OBJ);

			if ($select_folder->rowCount()) {

				$symlist_folder_id = $select_folder->fetch()->id;
				if ($_GET['searchType'] == 'rating') {

					// Search for a specific user rating (1-5) or a range (e.g. -3 or 3-)
					$operators = substr($_GET['searchQuery'], 0, 1) == '-' ? '<='
						: (substr($_GET['searchQuery'], -1) == '-' ? '>=' : '=');
					$select_files = $db->prepare('SELECT fullname FROM hvsc_files'.
						' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
						' INNER JOIN ratings ON symlists.file_id = ratings.table_id'.
						' WHERE ratings.user_id = '.$user_id.' AND ratings.rating '.$operators.' :rating AND ratings.type = "FILE" AND symlists.folder_id = '.$symlist_folder_id);
					$select_files->execute(array(':rating'=>str_replace('-', '', $_GET['searchQuery'])));

				} else if ($_GET['searchType'] == 'tag') {

					// Search for one or more tags
					$tag_list = '';
					$search_tags = ParseQuery($_GET['searchQuery']);
					foreach($search_tags as $tag)
						$tag_list .= ' OR tags_info.name LIKE "%'.$tag.'%"';

					$select_files = $db->query('SELECT h.fullname FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' LEFT JOIN tags_lookup ON h.id = tags_lookup.files_id'.
						' LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.
						' AND ('.substr($tag_list, 4).')'.
						' GROUP BY tags_lookup.files_id'.
						' HAVING COUNT(*) = '.count($search_tags).' LIMIT 1000');

				} else if ($_GET['searchType'] == 'location') {

					$select_files = $db->prepare('SELECT h.fullname FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.' AND loadaddr = :loadaddr LIMIT 1000');
					$location = $_GET['searchQuery'];
					if (substr($location, 0, 1) == '$')
						$location = hexdec(substr($location, 1));
					else if (substr($location, 0, 2) == '0x')
						$location = hexdec(substr($location, 2));
					$select_files->execute(array(':loadaddr'=>$location));

				} else if ($_GET['searchType'] == 'maximum') {

					$select_files = $db->prepare('SELECT h.fullname FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.' AND datasize <= :datasize AND fullname LIKE "_High Voltage SID Collection%" LIMIT 1000');
					$datasize = $_GET['searchQuery'];
					if (substr($datasize, 0, 1) == '$')
						$datasize = hexdec(substr($datasize, 1));
					else if (substr($datasize, 0, 2) == '0x')
						$datasize = hexdec(substr($datasize, 2));
					$select_files->execute(array(':datasize'=>$datasize));

				} else if ($_GET['searchType'] == 'country') {

					// Search for country in composer profiles
					$select_files = $db->prepare('SELECT h.fullname FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' INNER JOIN composers c ON h.fullname LIKE CONCAT(c.fullname, "%")'.
						' WHERE c.country LIKE :query AND symlists.folder_id = '.$symlist_folder_id);
					$query = strtolower($_GET['searchQuery']) == 'holland' ? 'netherlands' : $_GET['searchQuery'];
					$select_files->execute(array(':query'=>'%'.$query.'%'));

				} else {

					// Normal type search (handles any position of words and excluding with "-" prepended)
					// NOTE: This would have been easier with 'Full-Text' search but I'm not using the MyISAM engine.
					$exclude = '';
					if ($_GET['searchType'] == 'new') {
						$include = $_GET['searchType'].' LIKE "%'.str_replace('.', '', $_GET['searchQuery']).'%"';
					} else {
						$query = $_GET['searchQuery'];

						// Replace spaces ('_') inside quoted queries with '%' and remove the quotes themselves
						// NOTE: This is actually a weird shortcut and sometimes produce unexpected results.
						preg_match_all('/"[^"]+"/', $query, $quoted);
						foreach($quoted[0] as $q) {
							$adapted = trim(str_replace('_', '%', $q), '"');
							$query = str_replace($q, $adapted, $query);
						}
						// Get rid of any lonely quote stragglers
						$query = str_replace('"', '', $query);

						$words = explode('_', $query);
						$include = '(';
						$i_and = $e_and = '';
						foreach($words as $word) {
							if (substr($word, 0, 1) == '-') {
								$exclude .= $e_and.$_GET['searchType'].' NOT LIKE "%'.substr($word, 1).'%"';
								$e_and = ' AND ';
							} else {
								$include .= $i_and.$_GET['searchType'].' LIKE "%'.$word.'%"';
								$i_and = ' AND ';
							}
						}
						$include .= ')';
						if (!empty($exclude)) $exclude = ' AND ('.$exclude.')';

						if ($_GET['searchType'] == '#all#') {
							// Searching ALL should of course include a range of columns
							$columns = $comma = '';
							foreach(array('fullname', 'author', 'copyright', 'player', 'stil', 'gb64') as $column) {
								$columns .= $comma.$column.', " "';
								$comma = ', ';
							}
							// Treating all columns as one long search entity is MUCH easier
							$include = str_replace('#all#', 'CONCAT('.$columns.')', $include);
							$exclude = str_replace('#all#', 'CONCAT('.$columns.')', $exclude);
						}
					}	

					$select_files = $db->query('SELECT fullname FROM hvsc_files'.
						' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
						' WHERE '.$include.$exclude.' AND symlists.folder_id = '.$symlist_folder_id);
				}

				$select_files->setFetchMode(PDO::FETCH_OBJ);

				$found = $select_files->rowCount();

				foreach ($select_files as $row)
					$files[] = $row->fullname;
			}

			// If this is a public symlist we need to know who made it
			if ($isPublicSymlist) $owner = PublicSymlistOwner();
		}

	} else if ($isPublicSymlist || $isPersonalSymlist) {

		// CONTENTS OF SYMLIST FOLDER

		$files = array();

		// First get the ID of the symlist
		$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :fullname'.($isPersonalSymlist ? ' AND user_id = '.$user_id : '').' LIMIT 1');
		$select_folder->execute(array(':fullname'=>substr($_GET['folder'], 1)));
		$select_folder->setFetchMode(PDO::FETCH_OBJ);

		if ($select_folder->rowCount()) {
			$symlist_folder_id = $select_folder->fetch()->id;
			$select_files = $db->query('SELECT fullname FROM hvsc_files'.
				' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
				' WHERE symlists.folder_id = '.$symlist_folder_id);
			$select_files->setFetchMode(PDO::FETCH_OBJ);

			foreach ($select_files as $row)
				$files[] = $row->fullname;
		}

		// If this is a public symlist we need to know who made it
		if ($isPublicSymlist) $owner = PublicSymlistOwner();

	} else if ($isCSDbCompo) {

		// CONTENTS OF 'CSDb Music Competitions' FOLDER

		$files = array();

		if (empty($compoName)) {

			// PARENT COMPETITION FOLDERS

			$compo = array();

			// Get the full list of competitions
			$select_compo = $db->query('SELECT prefix, competition, year, country, type, event_id FROM competitions');
			$select_compo->setFetchMode(PDO::FETCH_OBJ);

			foreach ($select_compo as $row) {
				$files[] = $row->competition;
				$compo += [strtolower($row->competition) => array(
					'prefix' =>		$row->prefix,
					'year' =>		$row->year,
					'country' =>	$row->country,
					'type' =>		$row->type,
					'event_id' =>	$row->event_id,
				)];
			}

		} else {

			// INSIDE ONE COMPETITION FOLDER

			// Get CSDb event ID
			$select_compo = $db->prepare('SELECT event_id, name FROM competitions WHERE competition = :componame LIMIT 1');
			$select_compo->execute(array(':componame'=>$compoName));
			$select_compo->setFetchMode(PDO::FETCH_OBJ);
			$row = $select_compo->fetch();

			$event_id		= $row->event_id;
			$name =	$owner	= $row->name;			// Owner is reused for competition type here

			$place = array();

			// Has this folder been cached?

			$select = $db->query('SELECT file_id, place FROM competitions_cache WHERE event_id = '.$event_id.' AND name = "'.$name.'"');
			$select->setFetchMode(PDO::FETCH_OBJ);
			$entries = $select->rowCount();

			if ($entries) {

				// We have a cache so use that now (this is extremely fast)

				foreach($select as $row) {
					// Get fullname
					$select_fullname = $db->query('SELECT fullname FROM hvsc_files WHERE id = '.$row->file_id);
					$select_fullname->setFetchMode(PDO::FETCH_OBJ);

					if ($select_fullname->rowCount()) {
						$fullname = $select_fullname->fetch()->fullname;
						$files[] = $fullname;
						// Value -1 equals place "??" in CSDb jargon
						$place[$fullname] = $row->place;
					}
				}

			} else {

				// Get the paths from the CSDb web service while caching it (much slower first time)

				// Get the event XML from the CSDb web service
				$xml = curl('https://csdb.dk/webservice/?type=event&id='.$event_id);
				if (!strpos($xml, '<CSDbData>'))
					die(json_encode(array('status' => 'error', 'message' => 'Could not get CSDb data for event ID: '.$event_id)));
				$csdb_event = simplexml_load_string(utf8_decode($xml));

				$compos = $csdb_event->Event->Compo;
				if (!isset($compos))
					die(json_encode(array('status' => 'error', 'message' => 'The XML data from CSDb page had no competition entries.')));

				foreach($compos as $compo) {
					if (strtolower($compo->Type) == strtolower($name)) {
						$releases = $compo->Releases->Release;
						break;
					}
				}
				if (!isset($releases))
					die(json_encode(array('status' => 'error', 'message' => 'No results found for the "'.$name.'" competition.')));

				$real_count = 0;
				foreach($releases as $release) {
					// If there are errors the file is skipped completely (i.e. SID file will be ABSENT from the list)
					if (isset($release->ID)) {
						// Get the release XML from the CSDb web service
						$xml = curl('https://csdb.dk/webservice/?type=release&id='.$release->ID);
						if (strpos($xml, '<CSDbData>')) {
							$csdb_release = simplexml_load_string(utf8_decode($xml));
							if (isset($csdb_release->Release->UsedSIDs->SID->HVSCPath) && count($csdb_release->Release->UsedSIDs->SID) == 1) {
								$fullname = '_High Voltage SID Collection'.$csdb_release->Release->UsedSIDs->SID->HVSCPath;
								$files[] = $fullname;
								// Value -1 equals place "??" in CSDb jargon
								$place[$fullname] = isset($release->Achievement->Place) ? $release->Achievement->Place : -1;

								// Find file ID of this HVSC path
								$select = $db->query('SELECT id FROM hvsc_files WHERE fullname = "'.$fullname.'"');
								$select->setFetchMode(PDO::FETCH_OBJ);
								$file_id = $select->rowCount() ? $select->fetch()->id : 0;

								if ($file_id) {
									// Cache this competition SID entry
									// NOTE: The release ID is actually not used but saved anyway as debug info.
									$db->query('INSERT INTO competitions_cache (event_id, name, release_id, file_id, place)'.
										' VALUES('.$event_id.', "'.$name.'", '.$release->ID.', '.$file_id.', '.$place[$fullname].')');
									$real_count++;
								}
							}
						}
					}
				}

				// Does the corresponding folder already exist?
				$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :componame LIMIT 1');
				$select_folder->execute(array(':componame'=>$compoName));
				$select_folder->setFetchMode(PDO::FETCH_OBJ);
				if ($select_folder->rowCount()) {
					// Yes; just update its files count then
					// NOTE: The check and update is necessary if regenerating the cache.
					$db->query('UPDATE hvsc_folders SET files = '.$real_count.' WHERE id = '.$select_folder->fetch()->id);
				} else {
					// No; create the folder entry with the amount of viable files found
					$insert_folder = $db->prepare('INSERT INTO hvsc_folders (fullname, type, files, user_id)'.
						' VALUES(:componame, "COMPO", '.$real_count.', 0)');
					$insert_folder->execute(array(':componame'=>$compoName));						
				}
			}
		}

	} else {

		// CONTENTS OF PHYSICAL FOLDER
		
		// Get array of files in folder, remove unwanted entries, then re-index with 0 as start
		$files = array_values(array_diff(scandir(ROOT_HVSC.$_GET['folder']), [
			'.',
			'..',
			'DOCUMENTS',			// HVSC
			'UPDATE',
			'update',
			'10_Years_HVSC.d71',
			'10_Years_HVSC.d81',
			'10_Years_HVSC.dfi',
			'10_Years_HVSC_1.d64',
			'10_Years_HVSC_2.d64',
			'20_Years_HVSC.d64',
			'HVSC_Intro_41.d64',
			'HVSC_Intro_42.d64',
			'HVSC_Intro_43.d64',
			'HVSC_Intro_44.d64',
			'readme.1st',
			'sid.bat',
			'sidinfo.csv',
			'sidinfo.exe',
			'00_Commodore64',		// CGSC
			'00_Documents',
			'00_SIDfests',
			'00_SidNews',
			'00_Utils',
		]));

		// The root is the only place that may list the symlist folders as well as the CSDb compo folder
		if (empty($_GET['folder'])) {
			// Append the special folder for CSDb music competitions
			$files[] = 'CSDb Music Competitions';

			// Append public symlist folders (starts with a "$" character)
			$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "$%"');
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount()) {
				foreach($select as $row)
					$files[] = $row->fullname;
			}
			// Append personal symlist folders (starts with a "!" character and needs the user_id)
			$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "!%" AND user_id = '.$user_id);
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount()) {
				foreach($select as $row)
					$files[] = $row->fullname;
			}

		// The first HVSC fork; append "shortcut" folders for checking out stuff in a new HVSC update
		} else if ($_GET['folder'] == '/_High Voltage SID Collection') {

			// Add search shortcuts for the latest 5 versions of HVSC updates
			// NOTE: The three digits between ^ and name is used for sorting differently than the name implies.
			for ($i = 0; $i < 5; $i++) {
				$ss_name = '^00'.$i.'New in HVSC update #'.(HVSC_VERSION - $i);
				$files[] = $ss_name;
				$search_shortcut_type[$ss_name] = 'new';
				$search_shortcut_query[$ss_name] = HVSC_VERSION - $i;
			}

			// Search shortcuts for showing MUSICIANS folders with new songs (according to latest HVSC update)
			// DISABLED SINCE PHP STARTED REPORTING A 'MAX_JOIN_SIZE' ERROR ONLINE
			/*for ($i = 0; $i < 5; $i++) {
				$ss_name = '^01'.$i.'Folders in HVSC update #'.(HVSC_VERSION - $i);
				$files[] = $ss_name;
				$search_shortcut_type[$ss_name] = 'folders';
				$search_shortcut_query[$ss_name] = HVSC_VERSION - $i;
			}*/

			// Search shortcuts for "all" of e.g. specific types of songs
			$ss_name = '^030All multispeed tunes';
			$files[] = $ss_name;
			$search_shortcut_type[$ss_name] = 'special';
			$search_shortcut_query[$ss_name] = 'multispeed';

			$ss_name = '^035All multisid tunes';
			$files[] = $ss_name;
			$search_shortcut_type[$ss_name] = 'special';
			$search_shortcut_query[$ss_name] = 'multisid';

		// Redirect folders for members of a specific group (e.g. "Maniacs of Noise")
		} else if (substr($_GET['folder'], 0, 37) == '/_High Voltage SID Collection/GROUPS/') {

			$group = explode('/', $_GET['folder'])[3];

			$select_groups = $db->query('SELECT folder, redirect FROM groups WHERE name = "'.$group.'"');
			$select_groups->setFetchMode(PDO::FETCH_OBJ);

			foreach($select_groups as $member) {
				$files[] = $member->folder;									// Name of the group member
				$redirect_folder[$member->folder] = $member->redirect;		// Which folder to redirect to
			}
		}

		// The root is also home to 'SID Happens' which needs a count of files uploaded today
		$select = $db->query('SELECT 1 FROM uploads WHERE DATE(NOW()) = DATE(uploaded)');
		if ($select->rowCount() == 0)
			$new_uploads = 'NO NEW FILES TODAY';
		else if ($select->rowCount() == 1)
			$new_uploads = 'ONE NEW FILE TODAY';
		else
			$new_uploads = $select->rowCount().' NEW FILES TODAY';

		// Get the incompatibility emulators/handlers for the parent folder
		$select = $db->prepare('SELECT incompatible FROM hvsc_folders WHERE fullname = :folder LIMIT 1');
		$select->execute(array(':folder'=>ltrim($_GET['folder'], '/')));
		$select->setFetchMode(PDO::FETCH_OBJ);
		if ($select->rowCount()) $incompatible = $select->fetch()->incompatible;
	}

	// FOLDERS AND FILES

	$folder = ltrim($_GET['folder'].'/', '/');
	$files_ext = $folders_ext = array();
	$multiple = array();

	// Extra data for CSDb compo parent folders
	$isCompoRoot = $isCSDbCompo && empty($compoName);

	foreach($files as $file) {

		$extension = substr($file, -4);
		if ($extension != '.sid' && $extension != '.mus') {

			if ($extension == '.str' || $extension == '.wds')
				continue; // Don't show those at all

			// FOLDER

			$fullname = ($isSearching || $isCSDbCompo ? '' : $folder).$file;

			$select = $db->prepare('SELECT * FROM hvsc_folders WHERE fullname = :fullname'.
				(substr($file, 0, 1) == '!' ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$incompat_row = '';
			$folder_type = $isCSDbCompo ? 'COMPO' : 'FOLDERS';
			$rating = $filescount = 0;

			// Figure out the name of the thumbnail (if it exists)
			$fullname = str_replace('_High Voltage SID Collection/', '', $fullname);
			$fullname = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fullname);
			$fullname = strtolower(str_replace('/', '_', $fullname));
			$thumbnail = str_replace(' ', '_', $fullname);
			$thumbnail = 'images/composers/'.$thumbnail.'.jpg';

			if ($select->rowCount()) {
				$row = $select->fetch();								// Example

				$folder_type =		$row->type;							// SINGLE
				$filescount =		$row->files;						// 42
				$incompat_row =		$row->incompatible;					// jssid
				$has_photo =		file_exists('../'.$thumbnail);		// TRUE
				$flags =			$row->flags;						// 1
				$hvsc = 			$row->new;							// 70

				if ($user_id) {
					// Does the user have any rating for this folder?
					if (!empty($row->hash)) {
						// Search hash first (best; will catch it if set for a clone)
						$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND hash = "'.$row->hash.'" AND type = "FOLDER"');
						$select_rating->setFetchMode(PDO::FETCH_OBJ);
						$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
					}
					if (!$rating) {
						// Try again with direct table ID (some folders doesn't have a hash value)
						$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND table_id = '.$row->id.' AND type = "FOLDER"');
						$select_rating->setFetchMode(PDO::FETCH_OBJ);
						$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
					}
				}
			}

			if ($_GET['searchType'] == 'folders') {
				// Replace total file count with number of new files in the specified HVSC update instead
				$select_latest = $db->query('SELECT count(1) as cnt from hvsc_files'.
					' WHERE new = "'.$folders_version.'" AND fullname LIKE "%/'.$fullname.'/%"');
				$select_latest->setFetchMode(PDO::FETCH_OBJ);
				$filescount = $select_latest->rowCount() ? $select_latest->fetch()->cnt : 0;
			}

			// If a competition folder pops up in search results
			if ($folder_type == 'COMPO' && !$isCSDbCompo) {
				$compo = array();

				// Get the competition stuff now then
				$select_compo = $db->query('SELECT prefix, year, country, type, event_id FROM competitions WHERE competition = "'.$fullname.'" LIMIT 1');
				$select_compo->setFetchMode(PDO::FETCH_OBJ);

				if ($select_compo->rowCount()) {
					$isCompoRoot = true;
					$row_compo = $select_compo->fetch();

					$compo[$fullname]['prefix']		= $row_compo->prefix;
					$compo[$fullname]['year']		= $row_compo->year;
					$compo[$fullname]['country']	= $row_compo->country;
					$compo[$fullname]['type']		= $row_compo->type;
					$compo[$fullname]['event_id']	= $row_compo->event_id;
				}
			}

			array_push($folders_ext, array(
				'foldername'	=> $file,
				'foldertype'	=> $folder_type,
				'filescount'	=> $filescount,
				'incompatible'	=> $incompat_row,
				'hasphoto'		=> (isset($has_photo) ? $has_photo : false),
				'rating'		=> $rating,
				'flags'			=> (isset($flags) ? $flags : 0),
				'hvsc'			=> (isset($hvsc) ? $hvsc : 0),							// Example
				
				'prefix'		=> $isCompoRoot ? $compo[$fullname]['prefix']	: '',	// Sort_Me_Differently

				'compo_year'	=> $isCompoRoot ? $compo[$fullname]['year']		: 0,	// 1992
				'compo_country'	=> $isCompoRoot && !empty($compo[$fullname]['country'])
												? $compo[$fullname]['country']	: '',	// Finland
				'compo_type'	=> $isCompoRoot && !empty($compo[$fullname]['type'])
												? $compo[$fullname]['type']		: '',	// DEMO
				'compo_id'		=> $isCompoRoot ? $compo[$fullname]['event_id']	: 0,	// 117

				'ss_type'		=> (isset($search_shortcut_type[$file]) ? $search_shortcut_type[$file] : ''),		// new
				'ss_query'		=> (isset($search_shortcut_query[$file]) ? $search_shortcut_query[$file] : ''),		// 75

				'rf_path'		=> (isset($redirect_folder[$file]) ? $redirect_folder[$file] : ''),
			));

		} else {

			// FILE

			$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>($isSearching || $isPublicSymlist || $isPersonalSymlist || $isCSDbCompo ? '' : $folder).$file));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$player = $lengths = $type = $version = $playertype = $playercompat = $clockspeed = $sidmodel = $name = $author = $copyright = $hash = $stil = '';
			$rating = $dataoffset = $datasize = $loadaddr = $initaddr = $playaddr = $subtunes = $startsubtune = $hvsc = $videos = 0;

			if ($select->rowCount()) {
				$row = $select->fetch();

				$player = 			$row->player;		// MoN/FutureComposer
				$lengths = 			$row->lengths;		// 6:47 0:46 0:04
				$type = 			$row->type;			// PSID										RSID
				$version = 			$row->version;		// 2.0										3.0
				$playertype =		$row->playertype;	// Normal built-in																	(only value seen)
				$playercompat =		$row->playercompat;	// C64 compatible							PlaySID									(typically for BASIC tunes)
				$clockspeed =		$row->clockspeed;	// PAL 50Hz									NTSC 60Hz, PAL / NTSC, Unknown
				$sidmodel =			$row->sidmodel;		// MOS6581									MOS8580, MOS6581 / MOS858, Unknown
				$dataoffset =		$row->dataoffset;	// 124										0
				$datasize =			$row->datasize;		// 4557
				$loadaddr =			$row->loadaddr;		// 57344
				$initaddr =			$row->initaddr;		// 57344
				$playaddr =			$row->playaddr;		// 57350
				$subtunes =			$row->subtunes;		// 3
				$startsubtune =		$row->startsubtune;	// 1
				$name =				$row->name;			// Alloyrun
				$author =			$row->author;		// Jeroen Tel
				$copyright =		$row->copyright;	// 1988 Starlight
				$hash =				$row->hash;			// 02df65150cbc4fa8fabf563b26c8cac4
				$stil =				$row->stil;			// (#1)<br />NAME: Title tune<br />(#2)<br />NAME: High-score<br />(#3)<br />NAME: Get-ready
				$hvsc =				$row->new;			// 0 (= 49)									50 and up
				
				if ($user_id) {
					// Does the user have any rating for this SID file?
					if (!empty($row->hash)) {
						// Search hash first (best; will catch it if set for a clone)
						$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND hash = "'.$row->hash.'" AND type = "FILE"');
						$select_rating->setFetchMode(PDO::FETCH_OBJ);
						$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
					}
					if (!$rating) {
						// Try again with direct table ID (some SID files doesn't have a hash value)
						$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND table_id = '.$row->id.' AND type = "FILE"');
						$select_rating->setFetchMode(PDO::FETCH_OBJ);
						$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
					}
				}

				// Are there any YouTube video(s) associated with this file?
				$select_youtube = $db->query('SELECT COUNT(1) as c FROM youtube WHERE file_id = '.$row->id);
				$select_youtube->setFetchMode(PDO::FETCH_OBJ);
				$videos = $select_youtube->fetch()->c;
			}

			if ($sidmodel != 'MOS8580') $sidmodel = 'MOS6581'; // Always default to 6581 if not specifically 8580

			if (empty($player) && $extension == '.mus') {
				// CGSC
				$player = in_array(str_replace('.mus', '.str', $file), $files)
					? "Compute's Stereo SidPlayer"	// Uses .mus and .str for 6 voices SID (stereo)
					: "Compute's SidPlayer";		// Normal 3 voices SID
				$lengths = '5:00';
				$subtunes = $startsubtune = 1;
			} else if (empty($player))
				$player = 'an unidentified player';
			else if ($player == 'MoN/Bjerregaard')
				$player = 'Bjerregaard';

			$stil = str_replace('<br />',	' ',						$stil);

			$stil = str_replace('<?>', '<small class="u1">?</small>?<small class="u2">?</small>', $stil);

			$stil = str_replace('ARTIST:',	'<br /><b>ARTIST:</b>',		$stil);
			$stil = str_replace('AUTHOR:',	'<br /><b>AUTHOR:</b>',		$stil);
			$stil = str_replace('COMMENT:',	'<br /><b>COMMENT:</b>',	$stil);
			$stil = str_replace('NAME:',	'<br /><b>NAME:</b>', 		$stil);
			$stil = str_replace('TITLE:',	'<br /><b>TITLE:</b>', 		$stil);

			$stil = preg_replace(['/\(#(\d+)\)/'], ['<hr /><div class="subtune">$1</div>'], $stil);

			// Make references to other HVSC tunes into redirect links (i.e. won't refresh the web page)
			$stil = preg_replace('/(\/DEMO[^\s].+\.sid|\/GAMES[^\s]+\.sid|\/MUSICIANS[^\s]+\.sid)/', '<a class="redirect" href="#">$1</a>', $stil);

			$symid = $symid_pos = 0;
			$substname = '';
			if ($isPublicSymlist || $isPersonalSymlist) {
				// We're inside a symlist so check now if the file has a different name and sub tune here
				$symlist = $db->query('SELECT id, sidname, subtune FROM symlists WHERE folder_id = '.$symlist_folder_id.' AND file_id = '.$row->id.' ORDER BY id');
				$symlist->setFetchMode(PDO::FETCH_OBJ);
				$row_sym = $symlist->fetchAll();

				$row_count = $symlist->rowCount();
				if ($row_count) {
					if ($row_count > 1) {
						// There are multiple entries of the same SID tune
						$symid_pos = array_key_exists($row->id, $multiple) ? $multiple[$row->id] : 0;
						$symid = $row_sym[$symid_pos]->id;
						$multiple[$row->id] = $symid_pos + 1;
					}
					// Did the user rename it?
					$substname = $row_sym[$symid_pos]->sidname;
					if (!empty($substname)) $substname .= substr($file, -4);
					// Also check if a different sub tune than the default one should play instead
					if ($row_sym[$symid_pos]->subtune) $startsubtune = $row_sym[$symid_pos]->subtune;
				}
			}

			if (!empty($compoName)) {
				// Prepend a place number in front of CSDb competition SID files
				$number = $place[$file] == -1 ? '<span class="q">?</span><span class="q">?</span><span class="dot">.</span> ' : $place[$file].'. ';
				$substname = str_pad($number, 4, '0', STR_PAD_LEFT).substr($file, 1);
			}

			// Get an array of tags for this file ("Jazz", "Rock", etc.)
			$list_of_tags = array();
			$type_of_tags = array();
			GetTagsAndTypes($row->id, $list_of_tags, $type_of_tags);

			// Some player names have to be fetched specifically or there may be undesired changes elsewhere
			if ($player == 'Jeff') $player = 'Jeff\'s player';
			if ($player == 'Mixer') $player = 'Mixer\'s player';
			if ($player == 'SoedeSoft') $player = 'SoedeSoft\'s player';
			if ($player == 'Zardax') $player = 'Zardax\'s player';
			if ($player == 'Daryll_Reynolds') $player = 'Daryll Reynolds\' player';
			if ($player == 'Daryll_Reynolds_Digi') $player = 'Daryll Reynolds\' digi player';
			if ($player == 'Glover') $player = 'Glover\'s player';

			// If it's an *unpacked* JCH NewPlayer tune, add that info about it
			// NOTE: Just checking the specific load address is not entirely watertight, but to be 100%
			// sure I need to get the file contents to test for bytes, and I fear that's too expensive to
			// do at this point. Instead, I'll hack my way around with exceptions.
			if (stripos(strtolower($player), 'jch_newplayer') !== false && $loadaddr == '3840' &&
				stripos($file, 'Altitude.sid') === false &&		// Altitude.sid by Dane
				stripos($file, 'Quadtron.sid') === false)		// Quadtron.sid by Cosowi
				$player .= ' (unpacked)';

			array_push($files_ext, array(
				'filename' =>		$file,
				'substname' =>		$substname,
				'playerraw' =>		$player,
				'player' =>			str_replace(array_keys($prettyPlayerNames), $prettyPlayerNames, $player), // Remember it reads the array multiple times!
				'tags' =>			$list_of_tags,
				'tagtypes' =>		$type_of_tags,
				'lengths' => 		$lengths,
				'type' => 			$type,
				'version' => 		$version,
				//'playertype' => 	$playertype,
				//'playercompat' =>	$playercompat,
				'clockspeed' => 	$clockspeed,
				'sidmodel' => 		$sidmodel,
				//'dataoffset' => 	$dataoffset,
				'datasize' => 		$datasize,
				'loadaddr' => 		$loadaddr,
				'initaddr' => 		$initaddr,
				'playaddr' => 		$playaddr,
				'subtunes' => 		$subtunes,
				'startsubtune' => 	$startsubtune,
				'name' => 			$name,
				'author' => 		$author,
				'copyright' => 		$copyright,
				//'hash' => 		$hash,
				'stil' => 			$stil,
				'rating' =>			$rating,
				'hvsc' =>			$hvsc,
				'symid' =>			$symid,
				'videos' =>			$videos,
			));

			// Add extra values for uploaded SID files too if available
			if (isset($row->id)) {
				$select_upload = $db->query('SELECT composers_id, uploaded FROM uploads WHERE files_id = '.$row->id.' LIMIT 1');
				$select_upload->setFetchMode(PDO::FETCH_OBJ);
				if ($select_upload->rowCount()) {
					$row_upload = $select_upload->fetch();

					// Get the full path to the composer profile
					$select_comp = $db->query('SELECT fullname FROM composers WHERE id = '.$row_upload->composers_id.' LIMIT 1');
					$select_comp->setFetchMode(PDO::FETCH_OBJ);

					// Append to what was just pushed above
					$files_ext[count($files_ext) - 1] += array(
						'profile' =>		$select_comp->rowCount() ? $select_comp->fetch()->fullname : '',
						'uploaded' =>		$row_upload->uploaded,
					);
				}
			}
		}
	}

} catch(PDOException $e) {
	$account->LogActivityError('hvsc.php', $e->getMessage());
	$account->LogActivityError('hvsc.php', '$_GET[\'folder\'] = '.(empty($_GET['folder']) ? '(root)' : $_GET['folder']).
		($isSearching ? ', $_GET[\'searchType\'] = '.$_GET['searchType'].', $_GET[\'searchQuery\'] = '.$_GET['searchQuery'] : ' (user was not searching)'));
	// if (isset($files_ext)) $account->LogActivityError('hvsc.php', 'Files: '.print_r($files_ext, true));
	// if (isset($folders_ext)) $account->LogActivityError('hvsc.php', 'Folders: '.print_r($folders_ext, true));
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'files' => $files_ext, 'folders' => $folders_ext, 'results' => $found, 'incompatible' => $incompatible, 'owner' => $owner, 'compo' => !empty($compoName), 'today' => date('Y-m-d H:i:s', strtotime(TIME_ADJUST)), 'uploads' => $new_uploads, 'debug' => $debug));
?>