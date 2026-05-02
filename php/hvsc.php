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
 * @uses		$_GET['page']				page number for search results
 * @uses		$_GET['factoidTop']			factoid type in top line
 * @uses		$_GET['factoidBottom']		factoid type in bottom line
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");
require_once("tags_read.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Determine who owns a public symlist specified in the GET variable.
 * 
 * @global		object		$db					database connection
 *
 * @return		string		$owner 				user name
 */
function publicSymlistOwner() {

	global $db;

	// First get its user ID
	$select = $db->prepare('SELECT user_id FROM hvsc_folders WHERE collection_path = :folder LIMIT 1');
	$select->execute(array(':folder'=>substr($_GET['folder'], 1)));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$owner = 'an unknown user';
	if ($select->rowCount()) {
		// Now get the name of the user ID
		$select_user = $db->query('SELECT user_name FROM users WHERE id = '.$select->fetch()->user_id.' LIMIT 1');
		$select_user->setFetchMode(PDO::FETCH_OBJ);
		if ($select_user->rowCount())
			$owner = $select_user->fetch()->user_name;
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
function parseQuery($query) {

	// Replace spaces ('_') inside quoted queries with '¤' and remove the quotes themselves
	// NOTE: This is a weird shortcut but after exploding '_' characters below, the '¤' is
	//       replaced with '_' to ensure the phrase also work with SID filenames.
	preg_match_all('/"[^"]+"/', $query, $quoted);
	foreach($quoted[0] as $q) {
		$adapted = trim(str_replace('_', '¤', $q), '"');
		$query = str_replace($q, $adapted, $query);
	}
	// Get rid of any lonely quote stragglers and return an array
	$words = explode('_', str_replace('"', '', $query));
	return array_map(fn($item) => str_replace('¤', '_', $item), $words);
}

/**
 * Convert song length (3:33 or 3:33.333) to raw milliseconds.
 * 
 * @param		string		$length				HVSC song length
 * 
 * @return		int								Value in raw milliseconds
 */
function songLengthToMilliseconds(?string $length): ?int {
    if (!is_string($length)) return null;

    // Normalize and quick sanity checks
    $length = trim($length);
    if ($length === '') return 0;
    if (strlen($length) > 20) return 0; // Avoid accidentally passing huge strings

    // Match m:ss OR m:ss.xxx (minutes up to 4 digits, seconds 0-59)
    if (!preg_match(
        '/^(?<min>\d{1,4}):(?<sec>[0-5]?\d)(?:\.(?<frac>\d{1,9}))?$/',
        $length,
        $m
    )) {
        return 0;
    }

    $minutes = (int)$m['min'];
    $seconds = (int)$m['sec'];

    // Normalize fraction to milliseconds (pad/truncate to 3 digits)
    $frac = $m['frac'] ?? '0';
    $ms = (int) substr(str_pad($frac, 3, '0'), 0, 3);

    return ($minutes * 60 + $seconds) * 1000 + $ms;
}

// --------------------------------------------------------------------------
// START
// --------------------------------------------------------------------------

$found = $symlist_folder_id = $number_of_pages = 0;
$incompatible = $owner = $new_uploads = $message = '';
$user_id = $account->checkLogin() ? $account->userID() : 0;
$is_searching = isset($_GET['searchQuery']) && !empty($_GET['searchQuery']);
$is_personal_symlist = substr($_GET['folder'], 0, 2) == '/!';
$is_public_symlist = substr($_GET['folder'], 0, 2) == '/$';
// NOTE: A comparison of the 'COMPO' type is performed below that may also set these variables.
$is_compo_folder = substr($_GET['folder'], 0, 24) == '/CSDb Music Competitions';
$is_csdb_compo = $is_compo_folder && !$is_searching;
$compo_name = $is_csdb_compo && strlen($_GET['folder']) > 25 ? explode('/', $_GET['folder'])[2] : '';

$folders_version = HVSC_VERSION;
$search_shortcut_type = array();
$search_shortcut_query = array();
$redirect_folder = array();

// Because of snake_case overhaul in database column names
$_GET['searchType'] = str_replace('fullname', 'collection_path', $_GET['searchType']);

// In current folder or everything?
$search_context_path = $search_context_folders = '1';
if ($_GET['searchHere']) {
	$search_context_path = $search_context_folders = 'collection_path LIKE "'.substr($_GET['folder'], 1).'%"';
	if ($is_compo_folder)
		$search_context_folders = 'hvsc_folders.`type` = "COMPO"';
}

try {

	$db = $account->getDB();

	// --------------------------------------------------------------------------
	// SEARCH
	// --------------------------------------------------------------------------

	if ($is_searching) {

		// This tricky logic disallows symlists unless searching for everything
		if ((!$is_public_symlist && !$is_personal_symlist) ||
			(!$_GET['searchHere'] && ($is_public_symlist || $is_personal_symlist))) {

			// SEARCH PHYSICAL FILES

			// Perform a search query and fill the array with the results of collection paths
			$select = null;
			if ($_GET['searchType'] == 'rating') {													// Rating

				// Search for a specific user rating (1-5) or a range (e.g. -3 or 3-)
				$operators = substr($_GET['searchQuery'], 0, 1) == '-'
					? '<='
					: (substr($_GET['searchQuery'], -1) == '-' ? '>=' : '=');

				$select = $db->prepare('
					SELECT collection_path FROM hvsc_files
					INNER JOIN ratings ON hvsc_files.id = ratings.table_id
					WHERE '.$search_context_path.' AND ratings.user_id = '.$user_id.'
					AND ratings.rating '.$operators.' :rating AND ratings.type = "FILE"
				');
				$select->execute([
					':rating'  => str_replace('-', '', $_GET['searchQuery'])
				]);

			} else if ($_GET['searchType'] == 'tag') {												// Tag

				// Search for one or more tags
				$tag_list = '';
				$search_tags = parseQuery($_GET['searchQuery']);
				foreach($search_tags as $tag)
					$tag_list .= ' OR tags_info.name LIKE "%'.$tag.'%"';

				$select = $db->query('
					SELECT collection_path FROM hvsc_files
					LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id
					LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id
					WHERE '.str_replace('collection_path', 'hvsc_files.collection_path', $search_context_path).'
					AND ('.substr($tag_list, 4).')
					GROUP BY tags_lookup.files_id
					HAVING COUNT(*) = '.count($search_tags)
				);

			} else if ($_GET['searchType'] == 'label') {											// Label

				// Search for a label (primary release)
				$select = $db->prepare('
					SELECT collection_path FROM hvsc_files
					LEFT JOIN labels_lookup ON hvsc_files.id = labels_lookup.files_id
					LEFT JOIN labels_info ON labels_info.id = labels_lookup.labels_id
					WHERE '.str_replace('collection_path', 'hvsc_files.collection_path', $search_context_path).'
					AND labels_info.name LIKE :label
					GROUP BY labels_lookup.files_id
				');
				$select->execute([
					':label' => '%'.$_GET['searchQuery'].'%'
				]);

			} else if ($_GET['searchType'] == 'location') {											// Location

				$location = $_GET['searchQuery'];
				if (substr($location, 0, 1) == '$')
					$location = hexdec(substr($location, 1));
				else if (substr($location, 0, 2) == '0x')
					$location = hexdec(substr($location, 2));

				$select = $db->prepare('
					SELECT collection_path FROM hvsc_files
					WHERE '.$search_context_path.' AND load_addr = :load_addr
				');
				$select->execute([
					':load_addr' => $location
				]);

			} else if ($_GET['searchType'] == 'maximum') {											// Maximum

				$data_size = $_GET['searchQuery'];
				if (substr($data_size, 0, 1) == '$')
					$data_size = hexdec(substr($data_size, 1));
				else if (substr($data_size, 0, 2) == '0x')
					$data_size = hexdec(substr($data_size, 2));

				$select = $db->prepare('
					SELECT collection_path FROM hvsc_files
					WHERE '.$search_context_path.'
					AND data_size <= :data_size
					AND collection_path LIKE "_High Voltage SID Collection%"
				');
				$select->execute([
					':data_size' => $data_size
				]);

			} else if ($_GET['searchType'] == 'gb64') {												// GB64

				// Connect to imported GameBase64 database
				$gb = new PDO(
					'mysql:host='.$config['db_gb64_host'].';dbname='.$config['db_gb64_name'],
					$config['db_gb64_user'],
					$config['db_gb64_pwd']);
				$gb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$gb->exec("SET NAMES UTF8");

				// Parse the search query
				$word_list = '';
				$search_words = parseQuery($_GET['searchQuery']);
				foreach($search_words as $word)
					$word_list .= ' OR (Name LIKE "%'.$word.'%" AND SidFilename != "")';
				$word_list = substr($word_list, 4);

				// Get list of SID files from GameBase64 database
				$select_gb64 = $gb->query('SELECT SidFilename FROM Games WHERE '.$word_list);
				$select_gb64->setFetchMode(PDO::FETCH_OBJ);

				$chain = '1 = 2';
				foreach ($select_gb64 as $sid) {
					$sid = '_High Voltage SID Collection/'.$sid->SidFilename;
					$sid = str_replace('\\', '/', $sid);

					$chain .= ' OR collection_path = "'.$sid.'"';
				}

				$select = $db->query('
					SELECT collection_path from hvsc_files WHERE '.$chain
				);

			} else if ($_GET['searchType'] == 'type') {												// Type

				$select = $db->prepare('
					SELECT collection_path FROM hvsc_files
					WHERE '.$search_context_path.' AND type = :type
				');
				$select->execute([
					':type' => $_GET['searchQuery']
				]);

			} else if ($_GET['searchType'] == 'latest') {											// Latest

				$words = explode(',', $_GET['searchQuery']);
				if (count($words) == 1) {
					$query = $words[0];
					$version = HVSC_VERSION;	// For queries like "laxity/"
				} else {
					$query = $words[0];
					$version = $words[1];		// For queries like "laxity/,74"
				}

				$select = $db->query('
					SELECT collection_path from hvsc_files
					WHERE new = "'.$version.'"
					AND (collection_path LIKE "%'.$query.'%" OR author LIKE "%'.$query.'%")
				');

			} else if ($_GET['searchType'] == 'focus') {											// Focus

				$select = $db->prepare('
					SELECT collection_path FROM composers WHERE '.$search_context_path.'
					AND (focus1 LIKE :query OR focus2 LIKE :query)
				');
				$select->execute([
					':query' => $_GET['searchQuery'].'%'
				]);

			} else if ($_GET['searchType'] == 'folders') {											// Folders

				// Don't find any files for this one

			} else if ($_GET['searchType'] == 'special') {											// Special

				switch(strtolower($_GET['searchQuery'])) {

					case 'multispeed':

						// Search for all multispeed types (2x, 3x, 4x, etc.)
						$search_sql = '
							SELECT collection_path FROM hvsc_files
							LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id
							LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id
							WHERE tags_info.name IN ("multispeed", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x", "11x", "12x", "13x", "14x", "15x", "16x")
							GROUP BY tags_lookup.files_id
						';
						break;

					case 'multisid':

						// Search for all multisid types (2SID, 3SID, etc.)
						$search_sql = '
							SELECT collection_path FROM hvsc_files
							WHERE collection_path REGEXP "_2SID|_3SID|_4SID|_8SID|_10SID"
						';
						break;

					case 'gamecomposers':

						// Search for most popular game composers in one big list
						$search_sql = '
							SELECT collection_path FROM hvsc_files
							LEFT JOIN tags_lookup ON hvsc_files.id = tags_lookup.files_id
							LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id
							WHERE author REGEXP "Rob Hubbard|Martin Galway|Fred Gray|Wally Beben|Neil Brennan|Ben Daglish|Charles Deenen|Tim Follin|Geoff Follin|Matt Gray|Chris Hülsbeck|Richard Joseph|Russell Lieblich|Reyn Ouwehand|Jeroen Tel|Steve Turner|Martin Walker|Johannes Bjerregaard|David Dunn|Laxity|Yip"
							AND tags_info.name LIKE "%Game"
						';
						break;

					case 'died':
					case 'deceased':

						// Search for all composers that have died
						$search_sql = '
							SELECT hvsc_folders.collection_path FROM hvsc_folders
							INNER JOIN composers ON hvsc_folders.collection_path = composers.collection_path
							WHERE composers.date_death != "0000-00-00"';
						//clog('SQL', $search_sql);
						break;

					case 'nogb64yet':

						// Connect to imported GameBase64 database
						$gb = new PDO(
							'mysql:host='.$config['db_gb64_host'].';dbname='.$config['db_gb64_name'],
							$config['db_gb64_user'],
							$config['db_gb64_pwd']);
						$gb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$gb->exec("SET NAMES UTF8");

						// Get a list of all games that have a SID file indication (no limit)
						$gb64 = $gb->query('SELECT SidFilename FROM Games WHERE SidFilename != ""');
						$gb64->setFetchMode(PDO::FETCH_OBJ);

						$collection_path_list = [];
						foreach ($gb64 as $game) {
							$sidFilename = str_replace('\\', '/', $game->SidFilename);
							$collection_path = '_High Voltage SID Collection/'.$sidFilename;
							$collection_path_list[] = $collection_path;
						}

						// Escape values properly
						$escaped_list = array_map(function($val) use ($db) {
							return $db->quote($val); // Uses PDO::quote to safely escape strings
						}, $collection_path_list);

						// Join into a single comma-separated string
						$in_clause = implode(',', $escaped_list);

						// Find all SID tunes with GB64 entries that doesn't have a "GameBase64" tag yet
						$search_sql = '
							SELECT collection_path
							FROM hvsc_files
							WHERE collection_path IN ('.$in_clause.')
							AND NOT EXISTS (
								SELECT 1
								FROM tags_lookup
								JOIN tags_info ON tags_info.id = tags_lookup.tags_id
								WHERE tags_lookup.files_id = hvsc_files.id
								AND tags_info.name = "GameBase64"
							)
						';
						break;

					default:

						// Don't find anything if not a recognized special search
				}

				$select = $db->query($search_sql);

			} else if ($_GET['searchType'] != 'country') {											// All

				// Normal type search (handles any position of words and excluding with "-" prepended)
				// NOTE: This would have been easier with 'Full-Text' search but I'm not using the MyISAM engine.
				$exclude = '';
				if ($_GET['searchType'] == 'new') {
					$include = $_GET['searchType'].' LIKE "%'.str_replace('.', '', $_GET['searchQuery']).'%"';
				} else {
					$words = parseQuery($_GET['searchQuery']);
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
						foreach(array('collection_path', 'author', 'copyright', 'player', 'stil') as $column) {
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

				$select = $db->query('
					SELECT collection_path FROM hvsc_files WHERE '.$search_context_path.' AND '.$include.$exclude
				);
			}

			// FETCH: Fill a files array with the results of the search query
			$files_cgsc = array();
			$files_non_cgsc = array();
			if ($select) {
				$select->setFetchMode(PDO::FETCH_OBJ);

				$found = $select->rowCount();

				foreach ($select as $row) {
					$is_cgsc = strncmp($row->collection_path, "_Compute's Gazette SID Collection", 33) === 0;

					if ($is_cgsc) {
						$files_cgsc[] = $row->collection_path;
					} else {
						$files_non_cgsc[] = $row->collection_path;
					}
				}
				// Make sure the entirety across pages are sorted well
				sort($files_cgsc, SORT_STRING | SORT_FLAG_CASE);
				sort($files_non_cgsc, SORT_STRING | SORT_FLAG_CASE);
			}

			// SEARCH PHYSICAL FOLDERS

			// Repeat search query again but this time for folders
			// NOTE: Notice the extra "NOT LIKE" to avoid finding personal playlists by other users.
			$select = null;
			if ($_GET['searchType'] == 'rating') {													// + Rating

				$select = $db->prepare('
					SELECT collection_path FROM hvsc_folders
					INNER JOIN ratings ON hvsc_folders.id = ratings.table_id
					WHERE '.$search_context_folders.'
					AND ratings.user_id = '.$user_id.'
					AND ratings.rating '.$operators.' :rating
					AND ratings.type = "FOLDER"
					AND (collection_path NOT LIKE "!%")
				');
				$select->execute([
					':rating' => str_replace('-', '', $_GET['searchQuery'])
				]);

			} else if ($_GET['searchType'] == 'country') {											// + Country

				// Search for country in composer profiles
				$query = strtolower($_GET['searchQuery']) == 'holland' ? 'netherlands' : $_GET['searchQuery'];

				$select = $db->prepare('
					SELECT collection_path FROM composers
					WHERE '.$search_context_path.'
					AND country LIKE :query
				');
				$select->execute([
					':query' => '%'.$query.'%'
				]);

			} else if ($_GET['searchType'] == 'folders') {											// + Folders

				// Search for folders affected by the specified 'new' version
				$folders_version = $_GET['searchQuery'];

				$select = $db->prepare('
					SELECT DISTINCT hvsc_folders.collection_path FROM hvsc_folders
					INNER JOIN hvsc_files ON hvsc_files.collection_path LIKE CONCAT("%", hvsc_folders.collection_path ,"/%")
					WHERE hvsc_files.new = :version
					AND LENGTH(hvsc_folders.collection_path) - LENGTH(REPLACE(hvsc_folders.collection_path, "/", "")) > 2
				');
				$select->execute([
					':version' => $folders_version
				]);

			} else if ($_GET['searchType'] == '#all#' || $_GET['searchType'] == 'fullname' ||		// + All
				$_GET['searchType'] == 'author' || $_GET['searchType'] == 'new') {

				$collection_paths = '';

				// Normal type search
				if ($_GET['searchType'] == 'author') {

					// Let 'author' also find folders using 'collection_path' as replacement type
					$exclude = str_replace('author NOT LIKE "%', 'collection_path NOT LIKE "%', $exclude);
					$include = str_replace('author LIKE "%', 'collection_path LIKE "%', $include);

				} else if ($_GET['searchType'] == '#all#') {
					// Search the 'composers' table to see if the query matches the real name
					// This makes it possible to search for e.g. "Max Hall" and see his "../Max_F3H" folder.
					$composers = $db->query('
						SELECT collection_path FROM composers
						WHERE '.$search_context_path.'
						AND '.str_replace('#all#', 'full_name', $include_folders)
					);
					$composers->setFetchMode(PDO::FETCH_OBJ);

					foreach($composers as $composer_row)
						$collection_paths .= 'OR collection_path = "'.$composer_row->collection_path.'" ';

					// Just search 'collection_path' - none of the other columns exist in this table
					$include = str_replace('#all#', 'collection_path', $include_folders);
					$exclude = str_replace('#all#', 'collection_path', $exclude_folders);
				}
				$select = $db->query('
					SELECT collection_path FROM hvsc_folders
					WHERE '.$search_context_folders.' AND '.$include.$exclude.' AND (collection_path NOT LIKE "!%")
					AND (collection_path NOT LIKE "_High Voltage SID Collection/^%") '.$collection_paths
				);
			}

			// FETCH: Fill a folders array with the results of the search query
			$folders_cgsc = array();
			$folders_non_cgsc = array();
			if ($select) {
				$select->setFetchMode(PDO::FETCH_OBJ);

				$found += $select->rowCount();

				foreach ($select as $row) {
					$is_cgsc = strncmp($row->collection_path, "_Compute's Gazette SID Collection", 33) === 0;

					if ($is_cgsc) {
						$folders_cgsc[] = $row->collection_path;
					} else {
						$folders_non_cgsc[] = $row->collection_path;
					}

					if ($_GET['searchType'] == 'folders') {

						// This will turn the folders in the result list into search shortcuts
						$search_shortcut_type[$row->collection_path] = 'latest';
						$parts = explode("/", $row->collection_path);
						$search_shortcut_query[$row->collection_path] = '/'.end($parts).'/,'.$folders_version; // "/Foo/,72"

					} else if (strpos($row->collection_path, '/GROUPS/') !== false) {

						// Include where the group member folder will redirect to
						$group = explode('/', $row->collection_path)[2];

						$select_groups = $db->query('SELECT folder, redirect FROM `groups` WHERE name = "'.$group.'"');
						$select_groups->setFetchMode(PDO::FETCH_OBJ);

						foreach($select_groups as $member) {
							if (strtolower(substr($row->collection_path, 36)) == strtolower($group.'/'.$member->folder))
								$redirect_folder[$row->collection_path] = $member->redirect;
						}
					}
				}
				// Folders in search results are not affected by drop-down box sorting
				sort($folders_cgsc, SORT_STRING | SORT_FLAG_CASE);
				sort($folders_non_cgsc, SORT_STRING | SORT_FLAG_CASE);
			}

			// SEARCH LIMIT AND OFFSET

			// Place CGSC folders and files in the end as users are less interested in that
			$results_non_cgsc = array_merge($folders_non_cgsc, $files_non_cgsc);
			$results_cgsc     = array_merge($folders_cgsc,     $files_cgsc);

			$page_size = $account->getAdminSetting('search_page_size');
			$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

			$count_non_cgsc = count($results_non_cgsc);
			$count_cgsc = count($results_cgsc);

			// How many pages are needed for non-CGSC?
			$pages_non_cgsc = (int)ceil($count_non_cgsc / $page_size);

			// Total pages
			$number_of_pages = $pages_non_cgsc + (int)ceil($count_cgsc / $page_size);

			// Make sure CGSC is totally excluded from everything else before it
			if ($page <= $pages_non_cgsc) {

				// --- NON-CGSC PAGE ---
				$offset = ($page - 1) * $page_size;
				$files  = array_slice($results_non_cgsc, $offset, $page_size);

				if ($page < $number_of_pages && $page == $pages_non_cgsc)
					$message = '#CGSCNEXT#';

			} else {

				// --- CGSC PAGE ---
				$cgsc_page = $page - $pages_non_cgsc;
				$offset = ($cgsc_page - 1) * $page_size;
				$files = array_slice($results_cgsc, $offset, $page_size);

			}

		} else {

			// SEARCH IN SYMLIST

			$files = array();

			// First get the ID of the symlist
			$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE collection_path = :collection_path'.($is_personal_symlist ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select_folder->execute(array(':collection_path' => substr($_GET['folder'], 1)));
			$select_folder->setFetchMode(PDO::FETCH_OBJ);

			if ($select_folder->rowCount()) {

				$symlist_folder_id = $select_folder->fetch()->id;
				if ($_GET['searchType'] == 'rating') {

					// Search for a specific user rating (1-5) or a range (e.g. -3 or 3-)
					$operators = substr($_GET['searchQuery'], 0, 1) == '-' ? '<='
						: (substr($_GET['searchQuery'], -1) == '-' ? '>=' : '=');
					$select_files = $db->prepare('SELECT collection_path FROM hvsc_files'.
						' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
						' INNER JOIN ratings ON symlists.file_id = ratings.table_id'.
						' WHERE ratings.user_id = '.$user_id.' AND ratings.rating '.$operators.' :rating AND ratings.type = "FILE" AND symlists.folder_id = '.$symlist_folder_id);
					$select_files->execute(array(':rating'=>str_replace('-', '', $_GET['searchQuery'])));

				} else if ($_GET['searchType'] == 'tag') {

					// Search for one or more tags
					$tag_list = '';
					$search_tags = parseQuery($_GET['searchQuery']);
					foreach($search_tags as $tag)
						$tag_list .= ' OR tags_info.name LIKE "%'.$tag.'%"';

					$select_files = $db->query('SELECT h.collection_path FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' LEFT JOIN tags_lookup ON h.id = tags_lookup.files_id'.
						' LEFT JOIN tags_info ON tags_info.id = tags_lookup.tags_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.
						' AND ('.substr($tag_list, 4).')'.
						' GROUP BY tags_lookup.files_id'.
						' HAVING COUNT(*) = '.count($search_tags));

				} else if ($_GET['searchType'] == 'location') {

					$select_files = $db->prepare('SELECT h.collection_path FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.' AND load_addr = :load_addr');
					$location = $_GET['searchQuery'];
					if (substr($location, 0, 1) == '$')
						$location = hexdec(substr($location, 1));
					else if (substr($location, 0, 2) == '0x')
						$location = hexdec(substr($location, 2));
					$select_files->execute(array(':load_addr'=>$location));

				} else if ($_GET['searchType'] == 'maximum') {

					$select_files = $db->prepare('SELECT h.collection_path FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' WHERE symlists.folder_id = '.$symlist_folder_id.' AND data_size <= :data_size AND collection_path LIKE "_High Voltage SID Collection%"');
					$data_size = $_GET['searchQuery'];
					if (substr($data_size, 0, 1) == '$')
						$data_size = hexdec(substr($data_size, 1));
					else if (substr($data_size, 0, 2) == '0x')
						$data_size = hexdec(substr($data_size, 2));
					$select_files->execute(array(':data_size'=>$data_size));

				} else if ($_GET['searchType'] == 'country') {

					// Search for country in composer profiles
					$select_files = $db->prepare('SELECT h.collection_path FROM hvsc_files h'.
						' INNER JOIN symlists ON h.id = symlists.file_id'.
						' INNER JOIN composers c ON h.collection_path LIKE CONCAT(c.collection_path, "%")'.
						' WHERE c.country LIKE :query AND symlists.folder_id = '.$symlist_folder_id);
					$query = strtolower($_GET['searchQuery']) == 'holland' ? 'netherlands' : $_GET['searchQuery'];
					$select_files->execute(array(':query' => '%'.$query.'%'));

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
							foreach(array('collection_path', 'author', 'copyright', 'player', 'stil') as $column) {
								$columns .= $comma.$column.', " "';
								$comma = ', ';
							}
							// Treating all columns as one long search entity is MUCH easier
							$include = str_replace('#all#', 'CONCAT('.$columns.')', $include);
							$exclude = str_replace('#all#', 'CONCAT('.$columns.')', $exclude);
						}
					}	

					$select_files = $db->query('SELECT collection_path FROM hvsc_files'.
						' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
						' WHERE '.$include.$exclude.' AND symlists.folder_id = '.$symlist_folder_id);
				}

				$select_files->setFetchMode(PDO::FETCH_OBJ);

				// Searching symlists always show one big page
				$found = $select_files->rowCount();
				if ($found) $number_of_pages = 1;

				foreach ($select_files as $row)
					$files[] = $row->collection_path;
			}

			// If this is a public symlist we need to know who made it
			if ($is_public_symlist) $owner = publicSymlistOwner();
		}

	} else if ($is_public_symlist || $is_personal_symlist) {

		// --------------------------------------------------------------------------
		// CONTENTS OF SYMLIST FOLDER
		// --------------------------------------------------------------------------

		$files = array();

		// First get the ID of the symlist
		$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE collection_path = :collection_path'.($is_personal_symlist ? ' AND user_id = '.$user_id : '').' LIMIT 1');
		$select_folder->execute(array(':collection_path' => substr($_GET['folder'], 1)));
		$select_folder->setFetchMode(PDO::FETCH_OBJ);

		if ($select_folder->rowCount()) {
			$symlist_folder_id = $select_folder->fetch()->id;
			$select_files = $db->query('SELECT collection_path FROM hvsc_files'.
				' INNER JOIN symlists ON hvsc_files.id = symlists.file_id'.
				' WHERE symlists.folder_id = '.$symlist_folder_id);
			$select_files->setFetchMode(PDO::FETCH_OBJ);

			foreach ($select_files as $row)
				$files[] = $row->collection_path;
		}

		// If this is a public symlist we need to know who made it
		if ($is_public_symlist) $owner = publicSymlistOwner();

	} else if ($is_csdb_compo) {

		// --------------------------------------------------------------------------
		// CONTENTS OF 'CSDb Music Competitions' FOLDER
		// --------------------------------------------------------------------------

		$files = array();

		if (empty($compo_name)) {

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
			$select_compo = $db->prepare('SELECT event_id, name FROM competitions WHERE competition = :compo_name LIMIT 1');
			$select_compo->execute(array(':compo_name'=>$compo_name));
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
					// Get collection path
					$select_collection_path = $db->query('SELECT collection_path FROM hvsc_files WHERE id = '.$row->file_id);
					$select_collection_path->setFetchMode(PDO::FETCH_OBJ);

					if ($select_collection_path->rowCount()) {
						$collection_path = $select_collection_path->fetch()->collection_path;
						$files[] = $collection_path;
						// Value -1 equals place "??" in CSDb jargon
						$place[$collection_path] = $row->place;
					}
				}

			} else {

				// Get the paths from the CSDb web service while caching it (much slower first time)

				// Get the event XML from the CSDb web service
				$xml = curl('https://csdb.dk/webservice/?type=event&id='.$event_id);
				if (!strpos($xml, '<CSDbData>'))
					die(json_encode(array('status' => 'error', 'message' => 'Could not get CSDb data for event ID: '.$event_id)));
				$csdb_event = simplexml_load_string($xml);

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
							$csdb_release = simplexml_load_string($xml);
							if (isset($csdb_release->Release->UsedSIDs->SID->HVSCPath) && count($csdb_release->Release->UsedSIDs->SID) == 1) {
								$collection_path = '_High Voltage SID Collection'.$csdb_release->Release->UsedSIDs->SID->HVSCPath;
								$files[] = $collection_path;
								// Value -1 equals place "??" in CSDb jargon
								$place[$collection_path] = isset($release->Achievement->Place) ? $release->Achievement->Place : -1;

								// Find file ID of this HVSC path
								$select = $db->query('SELECT id FROM hvsc_files WHERE collection_path = "'.$collection_path.'"');
								$select->setFetchMode(PDO::FETCH_OBJ);
								$file_id = $select->rowCount() ? $select->fetch()->id : 0;

								if ($file_id) {
									// Cache this competition SID entry
									// NOTE: The release ID is actually not used but saved anyway as debug info.
									$db->query('INSERT INTO competitions_cache (event_id, name, release_id, file_id, place)'.
										' VALUES('.$event_id.', "'.$name.'", '.$release->ID.', '.$file_id.', '.$place[$collection_path].')');
									$real_count++;
								}
							}
						}
					}
				}

				// Does the corresponding folder already exist?
				$select_folder = $db->prepare('SELECT id FROM hvsc_folders WHERE collection_path = :compo_name LIMIT 1');
				$select_folder->execute(array(':compo_name' => $compo_name));
				$select_folder->setFetchMode(PDO::FETCH_OBJ);
				if ($select_folder->rowCount()) {
					// Yes; just update its files count then
					// NOTE: The check and update is necessary if regenerating the cache.
					$db->query('UPDATE hvsc_folders SET files = '.$real_count.' WHERE id = '.$select_folder->fetch()->id);
				} else {
					// No; create the folder entry with the amount of viable files found
					$insert_folder = $db->prepare('INSERT INTO hvsc_folders (collection_path, type, files, user_id)'.
						' VALUES(:compo_name, "COMPO", '.$real_count.', 0)');
					$insert_folder->execute(array(':compo_name' => $compo_name));						
				}
			}
		}

	} else {

		// --------------------------------------------------------------------------
		// CONTENTS OF PHYSICAL FOLDER
		// --------------------------------------------------------------------------
		
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
			$select = $db->query('SELECT collection_path FROM hvsc_folders WHERE collection_path LIKE "$%"');
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount()) {
				foreach($select as $row)
					$files[] = $row->collection_path;
			}
			// Append personal symlist folders (starts with a "!" character and needs the user_id)
			$select = $db->query('SELECT collection_path FROM hvsc_folders WHERE collection_path LIKE "!%" AND user_id = '.$user_id);
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount()) {
				foreach($select as $row)
					$files[] = $row->collection_path;
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

			$select_groups = $db->query('SELECT folder, redirect FROM `groups` WHERE name = "'.$group.'"');
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
		$select = $db->prepare('SELECT incompatible FROM hvsc_folders WHERE collection_path = :folder LIMIT 1');
		$select->execute(array(':folder'=>ltrim($_GET['folder'], '/')));
		$select->setFetchMode(PDO::FETCH_OBJ);
		if ($select->rowCount()) $incompatible = $select->fetch()->incompatible;
	}

	// --------------------------------------------------------------------------
	// FOLDERS AND FILES
	// --------------------------------------------------------------------------

	$folder = ltrim($_GET['folder'].'/', '/');
	$files_ext = $folders_ext = array();
	$multiple = array();

	// Extra data for CSDb compo parent folders
	$isCompoRoot = $is_csdb_compo && empty($compo_name);

	foreach($files as $file) {

		$extension = substr($file, -4);
		if ($extension != '.sid' && $extension != '.mus') {

			if ($extension == '.str' || $extension == '.wds')
				continue; // Don't show those at all

			// FOLDER

			$collection_path = ($is_searching || $is_csdb_compo ? '' : $folder).$file;

			$select = $db->prepare('SELECT * FROM hvsc_folders WHERE collection_path = :collection_path'.
				(substr($file, 0, 1) == '!' ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select->execute(array(':collection_path' => $collection_path));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$incompat_row = '';
			$folder_type = $is_csdb_compo ? 'COMPO' : 'FOLDERS';
			$rating = $filescount = 0;

			// Get the two focus fields (SCENER, PRO, etc.) of the composer if applicable
			$focus1 = $focus2 = 'N/A';
			if (preg_match('~(?:^|/)MUSICIANS/[^/]+/[^/]+(?:/|$)~i', $collection_path)) {
				$composer = $db->prepare('SELECT focus1, focus2 FROM composers WHERE collection_path = :collection_path LIMIT 1');
				$composer->execute([':collection_path' => $collection_path]);
				$row = $composer->fetch(PDO::FETCH_OBJ);

				$focus1 = $row ? $row->focus1 : '';	// 'PRO' or 'NONE'
				$focus2 = $row ? $row->focus2 : '';	// 'SCENER' or 'NONE'				
			}

			// Figure out the name of the thumbnail (if it exists)
			$collection_path = str_replace('_High Voltage SID Collection/', '', $collection_path);
			$collection_path = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $collection_path);
			$collection_path = strtolower(str_replace('/', '_', $collection_path));
			$thumbnail = str_replace(' ', '_', $collection_path);
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
					' WHERE new = "'.$folders_version.'" AND collection_path LIKE "%/'.$collection_path.'/%"');
				$select_latest->setFetchMode(PDO::FETCH_OBJ);
				$filescount = $select_latest->rowCount() ? $select_latest->fetch()->cnt : 0;
			}

			// If a competition folder pops up in search results
			if ($folder_type == 'COMPO' && !$is_csdb_compo) {
				$compo = array();

				// Get the competition stuff now then
				$select_compo = $db->query('SELECT prefix, year, country, type, event_id FROM competitions WHERE competition = "'.$collection_path.'" LIMIT 1');
				$select_compo->setFetchMode(PDO::FETCH_OBJ);

				if ($select_compo->rowCount()) {
					$isCompoRoot = true;
					$row_compo = $select_compo->fetch();

					$compo[$collection_path]['prefix']		= $row_compo->prefix;
					$compo[$collection_path]['year']		= $row_compo->year;
					$compo[$collection_path]['country']		= $row_compo->country;
					$compo[$collection_path]['type']		= $row_compo->type;
					$compo[$collection_path]['event_id']	= $row_compo->event_id;
				}
			}

			// Find out if the user has rated EVERYTHING inside this folder (and its sub folders)
			// NOTE: This requires that the 'ratings_cache' table is up to date with recent collections.
			// NOTE: See also 'ratings_folder.php' for duplicated code.
			$select_files = $db->prepare("
				SELECT files
				FROM hvsc_folders
				WHERE collection_path = :folder
			");
			$select_files->execute([':folder' => $folder.$file]);
			$total_files = (int)$select_files->fetchColumn();

			if ($total_files === 0) {
				$all_rated = false;
			} else {
				// Sum ratings from cache for this folder and its subfolders
				// NOTE: This requires a composite index for 'user_id' and 'folder' in the 'ratings_cache' table.
				$select_cache = $db->prepare("
					SELECT SUM(rated_files)
					FROM ratings_cache
					WHERE user_id = :uid
					AND (folder = :folder OR folder LIKE CONCAT(:folder, '/%'))
				");
				$select_cache->execute([
					':uid'    => $user_id,
					':folder' => $folder.$file
				]);

				$rated_sum = (int)$select_cache->fetchColumn();
				$all_rated = ($rated_sum === $total_files); // Boolean verdict
			}

			array_push($folders_ext, array(
				'foldername'	=> $file,
				'foldertype'	=> $folder_type,
				'filescount'	=> $filescount,
				'incompatible'	=> $incompat_row,
				'hasphoto'		=> (isset($has_photo) ? $has_photo : false),
				'focus1'		=> $focus1,
				'focus2'		=> $focus2,
				'rating'		=> $rating,
				'flags'			=> (isset($flags) ? $flags : 0),
				'hvsc'			=> (isset($hvsc) ? $hvsc : 0),									// Example
				
				'prefix'		=> $isCompoRoot ? $compo[$collection_path]['prefix']	: '',	// Sort_Me_Differently

				'compo_year'	=> $isCompoRoot ? $compo[$collection_path]['year']		: 0,	// 1992
				'compo_country'	=> $isCompoRoot && !empty($compo[$collection_path]['country'])
												? $compo[$collection_path]['country']	: '',	// Finland
				'compo_type'	=> $isCompoRoot && !empty($compo[$collection_path]['type'])
												? $compo[$collection_path]['type']		: '',	// DEMO
				'compo_id'		=> $isCompoRoot ? $compo[$collection_path]['event_id']	: 0,	// 117

				'ss_type'		=> (isset($search_shortcut_type[$file]) ? $search_shortcut_type[$file] : ''),		// new
				'ss_query'		=> (isset($search_shortcut_query[$file]) ? $search_shortcut_query[$file] : ''),		// 75

				'rf_path'		=> (isset($redirect_folder[$file]) ? $redirect_folder[$file] : ''),
				'all_rated'		=> $all_rated,
			));

		} else {

			// FILE

			$select = $db->prepare('SELECT * FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
			$select->execute(array(':collection_path'=>($is_searching || $is_public_symlist || $is_personal_symlist || $is_csdb_compo ? '' : $folder).$file));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$player = $lengths = $type = $version = $player_type = $player_compat = $clock_speed = $sid_model = $name = $author = $copyright = $hash = $stil = '';
			$id = $rating = $data_offset = $data_size = $load_addr = $init_addr = $play_addr = $subtunes = $start_subtune = $hvsc = $videos = 0;

			if ($select->rowCount()) {
				$row = $select->fetch();

				$id = 				$row->id;				// Unique database ID
				$collection_path =	$row->collection_path;	// _High Voltage SID Collection/MUSICIANS/T/Tel_Jeroen/Alloyrun.sid
				$player = 			$row->player;			// MoN/FutureComposer
				$lengths = 			$row->lengths;			// 6:47 0:46 0:04
				$type = 			$row->type;				// PSID							RSID
				$version = 			$row->version;			// 2.0							3.0
				$player_type =		$row->player_type;		// Normal built-in								(only value seen)
				$player_compat =	$row->player_compat;	// C64 compatible				PlaySID			(typically for BASIC tunes)
				$clock_speed =		$row->clock_speed;		// PAL 50Hz						NTSC 60Hz, PAL / NTSC, Unknown
				$sid_model =		$row->sid_model;		// MOS6581						MOS8580, MOS6581 / MOS858, Unknown
				$data_offset =		$row->data_offset;		// 124							0
				$data_size =		$row->data_size;		// 4557
				$load_addr =		$row->load_addr;		// 57344
				$init_addr =		$row->init_addr;		// 57344
				$play_addr =		$row->play_addr;		// 57350
				$subtunes =			$row->subtunes;			// 3
				$start_subtune =	$row->start_subtune;	// 1
				$name =				$row->name;				// Alloyrun
				$author =			$row->author;			// Jeroen Tel
				$copyright =		$row->copyright;		// 1988 Starlight
				$hash =				$row->hash;				// 02df65150cbc4fa8fabf563b26c8cac4
				$stil =				$row->stil;				// (#1)<br />NAME: Title tune<br />(#2)<br />NAME: High-score<br />(#3)<br />NAME: Get-ready
				$hvsc =				$row->new;				// 0 (= 49)						50 and up
				$csdb_type =		$row->csdb_type;		// sid							release
				$csdb_id =			$row->csdb_id;			// 58172
				
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

			if (empty($player) && $extension == '.mus') {
				// CGSC
				$player = in_array(str_replace('.mus', '.str', $file), $files)
					? "Compute's Stereo SidPlayer"	// Uses .mus and .str for 6 voices SID (stereo)
					: "Compute's SidPlayer";		// Normal 3 voices SID
				$lengths = '5:00';
				$subtunes = $start_subtune = 1;
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
			$stil = preg_replace('/(\/DEMOS[^\s]+\.sid|\/GAMES[^\s]+\.sid|\/MUSICIANS[^\s]+\.sid)/', '<a class="redirect" href="#">$1</a>', $stil);

			$symid = $symid_pos = 0;
			$substname = '';
			if ($is_public_symlist || $is_personal_symlist) {
				// We're inside a symlist so check now if the file has a different name and sub tune here
				$symlist = $db->query('SELECT id, sid_name, subtune FROM symlists WHERE folder_id = '.$symlist_folder_id.' AND file_id = '.$row->id.' ORDER BY id');
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
					$substname = $row_sym[$symid_pos]->sid_name;
					if (!empty($substname)) $substname .= substr($file, -4);
					// Also check if a different sub tune than the default one should play instead
					if ($row_sym[$symid_pos]->subtune) $start_subtune = $row_sym[$symid_pos]->subtune;
				}
			}

			if (!empty($compo_name)) {
				// Prepend a place number in front of CSDb competition SID files
				$number = $place[$file] == -1 ? '<span class="q">?</span><span class="q">?</span><span class="dot">.</span> ' : $place[$file].'. ';
				$substname = str_pad($number, 4, '0', STR_PAD_LEFT).substr($file, 1);
			}

			// Get an array of tags for this file ("Jazz", "Rock", etc.)
			$list_of_tags = array();
			$type_of_tags = array();
			$id_of_tags = array();
			$id_tag_start = $id_tag_end = 0;
			getTagsAndTypes($row->id, $list_of_tags, $type_of_tags, $id_of_tags, $id_tag_start, $id_tag_end);

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
			if (stripos(strtolower($player), 'jch_newplayer') !== false && $load_addr == '3840' &&
				stripos($file, 'Altitude.sid') === false &&		// Altitude.sid by Dane
				stripos($file, 'Quadtron.sid') === false)		// Quadtron.sid by Cosowi
				$player .= ' (unpacked)';

			// A "factoid" is an info field in two places of a SID row
			$fmode = array($_GET['factoidTop'], $_GET['factoidBottom']);
			$factoid = ["", ""];
			$fvalue = ["", ""];

			$isCGSC = stripos($collection_path, "_Compute's Gazette SID Collection/") !== false;

			foreach ([0, 1] as $f) {
				switch ($fmode[$f]) {

					case 1:		// Show tags (only bottom factoid)

						// Shown in 'browser.js'
						break;

					case 2:		// Song lengths

						if (!$isCGSC) {
							// Get the user's settings
							$frow = $db->query('SELECT flags FROM users WHERE id = '.$user_id)->fetch(PDO::FETCH_OBJ);
							$settings = unserialize($frow->flags);

							$sub_lengths = explode(' ', $lengths);

							// If multiple subtunes then get the length for the default subtune
							if ($settings['firstsubtune'])
								// User has overridden HVSC default so always the first one
								$factoid[$f] = $sub_lengths[0];
							else
								// Default HVSC subtune
								$factoid[$f] = $sub_lengths[$start_subtune - 1];

							$fvalue[$f] = songLengthToMilliseconds($factoid[$f]);

							// Get rid of the milliseconds
							$factoid[$f] = explode('.', $factoid[$f], 2)[0];
						}
						break;

					case 3:		// Type (PSID or RSID) and version (2.0, etc.)

						$factoid[$f] = $type . ' ' . $version;

						if ($factoid[$f] == 'PSID 2.0') {
							$factoid[$f] = '<div>' . $factoid[$f] . '</div>';
						}
						break;

					case 4:		// Compatibility (e.g. BASIC)

						$factoid[$f] = $player_compat == 'C64 BASIC' ? 'BASIC' : '';
						break;

					case 5:		// Clock speed

						// Mixed or unknown are not accepted
						if ($clock_speed === 'PAL 50Hz')
							$factoid[$f] = '<div>PAL</div>';
						else if ($clock_speed === 'NTSC 60Hz')
							$factoid[$f] = 'NTSC';
						break;

					case 6:		// SID model

						// Mixed or unknown are not accepted
						if ($sid_model === 'MOS6581')
							$factoid[$f] = '<div>6581</div>';
						else if ($sid_model === 'MOS8580')
							$factoid[$f] = '8580';
						break;

					case 7:		// Size in bytes (decimal)

						if (!$isCGSC) {
							$fvalue[$f] = $data_size - 3;
							//$factoid[$f] = 'Bytes: <span class="id">' . ($data_size - 3) . '</span>';
							$factoid[$f] = ($data_size - 3) . ' bytes';
						}
						break;

					case 8:		// Start and end address (hexadecimal)

						if ($data_size && $load_addr)
							$factoid[$f] = '$' . strtoupper(str_pad(dechex($load_addr), 4, '0', STR_PAD_LEFT)).'-'.
								'$' . strtoupper(str_pad(dechex($load_addr + $data_size - 3), 4, '0', STR_PAD_LEFT));
						break;

					case 9:		// HVSC or CGSC version

						if ((int)$hvsc) {
							if ($isCGSC)
								$factoid[$f] = 'CGSC v'.substr($hvsc, 0, 1).'.'.substr($hvsc, 1);
							else
								$factoid[$f] = 'HVSC #'.$hvsc;
						}
						break;

					case 10:	// [ Unused ]

						// Used to be 'Application' (RELEASE or PREVIEW) - later deprecated

						$factoid[$f] = '';
						break;

					case 11:	// Number of CSDb entries

						if ($csdb_type === 'sid') {
							$frow = $db->query('SELECT entries FROM csdb
								WHERE sid_id = '.$csdb_id.' LIMIT 1')->fetch(PDO::FETCH_OBJ);
							if ($frow) {
								$fvalue[$f] = $frow->entries;
								$factoid[$f] = 'CSDb:<span>' . $frow->entries .'</span>';
								if ($frow->entries == 0)
									$factoid[$f] = '<div>' . $factoid[$f] . '</div>'; // Fade it
							}
						}
						break;

					case 12:	// Primary release (previously tag label, now its own tables)

						$label_site = $label_name = $label_type = '';
						$label_siteid = 0;

						$lrow = $db->query('
							SELECT i.id, i.site, i.name, i.type, i.site_id, l.labels_id
							FROM labels_lookup l
							JOIN labels_info i ON l.labels_id = i.id
							WHERE l.files_id = '.$id.' LIMIT 1'
						)->fetch(PDO::FETCH_OBJ);
						if ($lrow) {
							$label_site = $lrow->site;			// CSDB
							$label_name = $lrow->name;			// Dutch Breeze
							$label_type = $lrow->type;			// Demo
							$label_siteid = $lrow->site_id;		// 11584
						}
						$factoid[$f] = $label_name;
						break;

					// ONLY ADMIN FACTOIDS BELOW

					case 1000:	// ID (hvsc_files)

						$factoid[$f] = 'DB ID: <span class="id">' . $id .'</span>';
						break;

					case 1001:	// CSDb 'sid' ID

						if ($csdb_type == 'sid' && $csdb_id)
							$factoid[$f] = 'SID ID: <span class="id">' . $csdb_id .'</span>';
						break;

					default:	// Nothing
				}
			}

			if ($sid_model != 'MOS8580') $sid_model = 'MOS6581'; // Always default to 6581 if not specifically 8580

			// Don't use underscores in key names
			array_push($files_ext, array(
				'id' =>				$id,
				'filename' =>		$file,
				'substname' =>		$substname,
				'playerraw' =>		$player,
				'player' =>			str_replace(array_keys($prettyPlayerNames), $prettyPlayerNames, $player), // Remember it reads the array multiple times!
				'tags' =>			$list_of_tags,
				'tagtypes' =>		$type_of_tags,
				'tagids' =>			$id_of_tags,
				'tagidstart' =>		$id_tag_start,
				'tagidend' =>		$id_tag_end,
				'lengths' => 		$lengths,
				'type' => 			$type,
				'version' => 		$version,
				//'playertype' => 	$player_type,
				//'playercompat' => $player_compat,
				'clockspeed' => 	$clock_speed,
				'sidmodel' => 		$sid_model,
				//'dataoffset' => 	$data_offset,
				'datasize' => 		$data_size,
				'loadaddr' => 		$load_addr,
				'initaddr' => 		$init_addr,
				'playaddr' => 		$play_addr,
				'subtunes' => 		$subtunes,
				'startsubtune' => 	$start_subtune,
				//'name' => 		$name,				// @link https://github.com/Chordian/deepsid/issues/21
				'author' => 		$author,
				'copyright' => 		$copyright,
				//'hash' => 		$hash,
				'stil' => 			$stil,
				'rating' =>			$rating,
				'hvsc' =>			$hvsc,
				'symid' =>			$symid,
				'videos' =>			$videos,
				'factoidtop' =>		$factoid[0],
				'factoidbottom' =>	$factoid[1],
				'fvaluetop' =>		$fvalue[0],
				'fvaluebottom' =>	$fvalue[1],
				'labelsite' =>		$label_site,
				'labelname' =>		$label_name,
				'labeltype' =>		$label_type,
				'labelsiteid' =>	$label_siteid,
			));

			// Add extra values for uploaded SID files too if available
			if (isset($row->id)) {
				$select_upload = $db->query('SELECT composers_id, uploaded FROM uploads WHERE files_id = '.$row->id.' LIMIT 1');
				$select_upload->setFetchMode(PDO::FETCH_OBJ);
				if ($select_upload->rowCount()) {
					$row_upload = $select_upload->fetch();

					// Get the full path to the composer profile
					$select_comp = $db->query('SELECT collection_path FROM composers WHERE id = '.$row_upload->composers_id.' LIMIT 1');
					$select_comp->setFetchMode(PDO::FETCH_OBJ);

					// Append to what was just pushed above
					$files_ext[count($files_ext) - 1] += array(
						'profile' =>		$select_comp->rowCount() ? $select_comp->fetch()->collection_path : '',
						'uploaded' =>		$row_upload->uploaded,
					);
				}
			}
		}
	}

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	$account->logActivityError(basename(__FILE__), '$_GET[\'folder\'] = '.(empty($_GET['folder']) ? '(root)' : $_GET['folder']).
		($is_searching ? ', $_GET[\'searchType\'] = '.$_GET['searchType'].', $_GET[\'searchQuery\'] = '.$_GET['searchQuery'] : ' (user was not searching)'));
	// if (isset($files_ext)) $account->logActivityError(basename(__FILE__), 'Files: '.print_r($files_ext, true));
	// if (isset($folders_ext)) $account->logActivityError(basename(__FILE__), 'Folders: '.print_r($folders_ext, true));
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

// --------------------------------------------------------------------------
// FINAL OUTPUT
// --------------------------------------------------------------------------

echo json_encode(array(
	'status' 		=> 'ok',
	'files' 		=> $files_ext,
	'folders' 		=> $folders_ext,
	'results' 		=> $found,
	'pages'			=> $number_of_pages,
	'message'		=> $message,
	'incompatible'	=> $incompatible,
	'owner' 		=> $owner,
	'compo' 		=> !empty($compo_name),
	'today' 		=> date('Y-m-d H:i:s', strtotime(TIME_ADJUST)),
	'uploads' 		=> $new_uploads));
?>