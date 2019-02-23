<?php
/**
 * DeepSID
 *
 * Get an array of SID files from the specified folder in the HVSC library, or
 * perform a search query if specified.
 * 
 * @uses		$_GET['folder']
 * @uses		$_GET['searchType']
 * @uses		$_GET['searchQuery']	overrides 'folder' if used
 * @uses		$_GET['searchHere']		1 = in current folder, 0 = in everything
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$found = $symlist_folder_id = 0;
$debug = $incompatible = $owner = '';
$user_id = $account->CheckLogin() ? $account->UserID() : 0;
$isSearching = isset($_GET['searchQuery']) && !empty($_GET['searchQuery']);
$isPersonalSymlist = substr($_GET['folder'], 0, 2) == '/!';
$isPublicSymlist = substr($_GET['folder'], 0, 2) == '/$';

// In current folder or everything?
$searchContext = $_GET['searchHere'] ? 'fullname LIKE "'.substr($_GET['folder'], 1).'%"' : '1';

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

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

	if ($isSearching) {

		// This tricky logic disallows symlists unless searching for everything
		if ((!$isPublicSymlist && !$isPersonalSymlist) || (!$_GET['searchHere'] && ($isPublicSymlist || $isPersonalSymlist))) {

			// SEARCH PHYSICAL FILES AND FOLDERS

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
			} else if ($_GET['searchType'] != 'country') {
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
			} else if ($_GET['searchType'] == '#all#' || $_GET['searchType'] == 'fullname' || $_GET['searchType'] == 'author' || $_GET['searchType'] == 'new') {
				// Normal type search
				if ($_GET['searchType'] == 'author') {
					// Let 'author' also find folders using 'fullname' as replacement type
					$exclude = str_replace('author NOT LIKE "%', 'fullname NOT LIKE "%', $exclude);
					$include = str_replace('author LIKE "%', 'fullname LIKE "%', $include);
				} else if ($_GET['searchType'] == '#all#') {
					// Just search 'fullname' - none of the other columns exist in this table
					$include = str_replace('#all#', 'fullname', $include_folders);
					$exclude = str_replace('#all#', 'fullname', $exclude_folders);
				}
				$select = $db->query('SELECT fullname FROM hvsc_folders WHERE '.$searchContext.' AND '.$include.$exclude.' AND (fullname NOT LIKE "!%") LIMIT 1000');
			}
			if ($select) {
				$select->setFetchMode(PDO::FETCH_OBJ);

				$found += $select->rowCount();

				foreach ($select as $row)
					$files[] = $row->fullname;
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
						' WHERE ratings.user_id = '.$user_id.' AND ratings.rating '.$operators.' :rating AND ratings.type = "FILE"  AND symlists.folder_id = '.$symlist_folder_id);
					$select_files->execute(array(':rating'=>str_replace('-', '', $_GET['searchQuery'])));
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

	} else {

		// CONTENTS OF PHYSICAL FOLDER
		
		// Get array of files in folder, remove unwanted entries, then re-index with 0 as start
		$files = array_values(array_diff(scandir(ROOT_HVSC.$_GET['folder']), [
			'.',
			'..',
			'DOCUMENTS',			// HVSC
			'UPDATE',
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

		// The root is the only place that may list symlist folders (at least for now)
		if (empty($_GET['folder'])) {
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
		}

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

	foreach($files as $file) {

		$extension = substr($file, -4);
		if ($extension != '.sid' && $extension != '.mus') {

			if ($extension == '.str' || $extension == '.wds')
				continue; // Don't show those at all

			// FOLDER

			$fullname = ($isSearching ? '' : $folder).$file;

			$select = $db->prepare('SELECT * FROM hvsc_folders WHERE fullname = :fullname'.
				(substr($file, 0, 1) == '!' ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$incompat_row = '';
			$folder_type = 'FOLDERS';
			$rating = $filescount = 0;

			// Figure out the name of the thumbnail (if it exists)
			$fullname = str_replace('_High Voltage SID Collection/', '', $fullname);
			$fullname = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fullname);
			$fullname = strtolower(str_replace('/', '_', $fullname));
			$thumbnail = 'images/composers/'.$fullname.'.jpg';

			if ($select->rowCount()) {
				$row = $select->fetch();							// Example

				$folder_type =		$row->type;						// SINGLE
				$filescount =		$row->files;					// 42
				$incompat_row =		$row->incompatible;				// jssid
				$has_photo =		file_exists('../'.$thumbnail);	// TRUE
				$flags =			$row->flags;					// 1
				$hvsc = 			$row->new;						// 70

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

			array_push($folders_ext, array(
				'foldername'	=> $file,
				'foldertype'	=> $folder_type,
				'filescount'	=> $filescount,
				'incompatible'	=> $incompat_row,
				'hasphoto'		=> $has_photo,
				'rating'		=> $rating,
				'flags'			=> $flags,
				'hvsc'			=> $hvsc,
			));

		} else {

			// FILE

			$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>($isSearching || $isPublicSymlist || $isPersonalSymlist ? '' : $folder).$file));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$player = $lengths = $type = $version = $playertype = $playercompat = $clockspeed = $sidmodel = $name = $author = $copyright = $hash = $stil = '';
			$rating = $dataoffset = $datasize = $loadaddr = $initaddr = $playaddr = $subtunes = $startsubtune = $hvsc = 0;

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

			array_push($files_ext, array(
				'filename' =>		$file,
				'substname' =>		$substname,
				'player' =>			str_replace(array_keys($prettyPlayerNames), $prettyPlayerNames, $player), // Remember it reads the array multiple times!
				'lengths' => 		$lengths,
				'type' => 			$type,
				//'version' => 		$version,
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
			));
		}
	}

} catch(PDOException $e) {
	$account->LogActivityError('hvsc.php', $e->getMessage());
	$account->LogActivityError('hvsc.php', '$_GET[\'folder\'] = '.(empty($_GET['folder']) ? '(root)' : $_GET['folder']).
		($isSearching ? ', $_GET[\'searchType\'] = '.$_GET['searchType'].', $_GET[\'searchQuery\'] = '.$_GET['searchQuery'] : ' (user was not searching)'));
	if (isset($file_ext)) $account->LogActivityError('hvsc.php', 'Files: '.$file_ext);
	if (isset($folders_ext)) $account->LogActivityError('hvsc.php', 'Folders: '.$folders_ext);
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'files' => $files_ext, 'folders' => $folders_ext, 'results' => $found, 'incompatible' => $incompatible, 'owner' => $owner, 'debug' => $debug));
?>