<?php
/**
 * DeepSID
 *
 * Build an HTML page with details about the folder/composer. Includes charts
 * for players (bar chart) and active years (graph).
 * 
 * The group/work tables have been moved to the 'groups.php' file instead.
 * 
 * As of October 2025, the file also returns HTML for the annex box.
 * 
 * @uses		$_GET['fullname']			to folder
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");
require_once("csdb_compo.php");
require_once("csdb_comments.php");
require_once("composer_exotic.php");
require_once("countries.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$html = $annex_html = $folderFocus = '';
$rating = 0;
$fullname = $_GET['fullname'];
$escaped_fullname = str_replace('_', '\_', $fullname);

if (isset($fullname)) {

	if (empty($fullname))
		 // Don't do root
		die(json_encode(array('status' => 'ok', 'html' => '', 'annex_html' => '<div class="annexMsg">No profile to show.</div>')));

	if (substr($fullname, 0, 23) == 'CSDb Music Competitions' && strlen($fullname) > 24) {

		// INSIDE ONE COMPETITION FOLDER

		try {
			if ($_SERVER['HTTP_HOST'] == LOCALHOST)
				$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
			else
				$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("SET NAMES UTF8");

			// Get the event ID of this compo folder
			$select = $db->prepare('SELECT event_id FROM competitions WHERE competition = :compo LIMIT 1');
			$select->execute(array(':compo'=>str_replace('CSDb Music Competitions/', '', $fullname)));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$event_id = $select->rowCount() ? $select->fetch()->event_id : 0;

			if ($event_id) {

				$sceners = array();

				$csdb =					CompoGetXML($event_id);
				$compos =				CompoGetEntries($csdb);
				$type_date_country =	CompoGetTypeDateCountry($csdb);
				$event_image =			CompoGetImage($event_id);
				$user_comments = 		CompoGetComments($csdb, $event_id);

				$aka = '<p style="position:relative;top:-6px;left:1px;margin:0 0 4px;"><small>'.(isset($csdb->Event->AKA) ? '('.$csdb->Event->AKA.')' : '&nbsp;').'</small></p>';

				$tagline = isset($csdb->Event->Tagline)
				? '<p style="position:relative;top:-'.(!empty($aka) ? '10' : '4').'px;margin:0;"><i>- '.$csdb->Event->Tagline.'</i></p>'
				: '';

				$address = isset($csdb->Event->Address) ? $csdb->Event->Address : '';
				$city = isset($csdb->Event->City) ? (!empty($address) ? ', ' : '').$csdb->Event->City : '';
				$state = isset($csdb->Event->State) ? (!empty($address) || !empty($city) ? ', ' : '').$csdb->Event->State : '';
				//$country = isset($csdb->Event->Country) ? $csdb->Event->Country : '';

				$place = !empty($address.$city.$state/*.$country*/)
					? '<p><b>Place:</b><br />'.$address.$city.$state./*$country.*/'</p>'
					: '';

				$website = isset($csdb->Event->Website)
					? '<p><b>Web Site:</b><br /><a href="'.$csdb->Event->Website.'" target="_blank">'.$csdb->Event->Website.'</a></p>'
					: '';

				$organizers = $comma = '';
				$amount = 8;
				if (isset($csdb->Event->Organizer)) {
					$orgs = $csdb->Event->Organizer;
					foreach($orgs as $org) {
						if (!isset($org->Handle)) break;
						$id = $org->Handle->ID;
						if (isset($org->Handle->Handle)) {
							// There's a handle, get it and store the ID for it for later reference
							$handle = $org->Handle->Handle.','.$id;
							$sceners[(string)$id] = $org->Handle->Handle;
						} else if (array_key_exists((string)$id, $sceners)) {
							// We've had this scener before so we know the name
							$handle = $sceners[(string)$id].','.$id;
						} else {
							// Can't figure this scener out so just use the ID
							$handle = $id;
						}
						if (strpos($handle, ',')) {
							$parts = explode(',', $handle);
							// ID and handle
							$m = '<a href="http://csdb.chordian.net/?type=scener&id='.$parts[1].'" target="_blank">'.$parts[0].'</a>';
						} else {
							// [Scener:1234]
							$m = '[<a href="http://csdb.chordian.net/?type=scener&id='.$handle.'" target="_blank">Scener:'.$handle.'</a>]';
						}
						$organizers .= $comma.$m;
						if (!$amount) {
							$organizers .= ' [...]';
							break;
						}
						$amount--;
						$comma = ', ';
					}
					$organizers = '<p><b>Organizers:</b><br />'.$organizers.'</p>';
				}

				$org_groups = $comma = '';
				$amount = 8;
				if (isset($csdb->Event->OrganizerGroup)) {
					$orgs = $csdb->Event->OrganizerGroup;
					foreach($orgs as $org) {
						if (!isset($org->Group)) break;
						$id = $org->Group->ID;
						if (isset($org->Group->Name)) {
							// There's a group name
							$group = $org->Group->Name.','.$id;
						} else {
							// Can't figure this group out so just use the ID
							$group = $id;
						}
						if (strpos($group, ',')) {
							$parts = explode(',', $group);
							// ID and group
							$m = '<a href="http://csdb.chordian.net/?type=group&id='.$parts[1].'" target="_blank">'.$parts[0].'</a>';
						} else {
							// [Group:1234]
							$m = '[<a href="http://csdb.chordian.net/?type=group&id='.$group.'" target="_blank">Group:'.$group.'</a>]';
						}
						$org_groups .= $comma.$m;
						if (!$amount) {
							$org_groups .= ' [...]';
							break;
						}
						$amount--;
						$comma = ', ';
					}
					$org_groups = '<p><b>Organizer Groups:</b><br />'.$org_groups.'</p>';
				}

				// Build the annex tab HTML
				$annex_html = '<div class="annexMsg">No profile to show.</div>';

				// Build the dexter page HTML
				$html = '<div id="compo-profile"><h2 style="display:inline-block;margin:0;">'.$csdb->Event->Name.'</h2>'.
					'<div class="corner-icons">'.
						'<a href="http://csdb.chordian.net/?type=event&id='.$event_id.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
					'</div>'.
					$aka.
					'<p'.(!empty($aka) ? ' style="position:relative;top:-8px;margin:0 0 4px;"' : '').'>'.$type_date_country.'</p>'.
					'<p'.(!empty($aka) ? ' style="position:relative;top:-6px;margin:0 0 4px;"' : '').'>'.$event_image.'</p>'.
					$tagline.
					'<div style="height:4px;"></div>'.
					$place.
					$website.
					$organizers.
					$org_groups.
					$user_comments.'</div>';

				die(json_encode(array('status' => 'ok', 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i>', 'annex_html' => $annex_html)));
			}

		} catch(PDOException $e) {
			$account->LogActivityError(basename(__FILE__) . ' (compo)', $e->getMessage());
			die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
		}
		die(json_encode(array('status' => 'ok', 'html' => $html, 'annex_html' => $annex_html)));

	} else {

		// OTHER FOLDERS

		$exoticFullname = ProxyExotic($fullname);
		$isExoticComposerFolder = ($fullname != $exoticFullname);
		$isGroupsFolder = stripos($fullname, '/GROUPS/') !== false;
		$fullname = $exoticFullname;

		try {
			if ($_SERVER['HTTP_HOST'] == LOCALHOST)
				$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
			else
				$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("SET NAMES UTF8");

			// If in a sub folder of a composer (e.g. work tunes or a previous handle) with no profile then re-use
			// NOTE: This block is also used in the 'groups.php' file.
			$folders = explode('/', $fullname);
			$isBorrowedProfile = false;
			if (count($folders) > 3 && $folders[1] == 'MUSICIANS' && !empty($folders[4])) {
				// Do we have a profile for the unique sub folder of this composer?
				$select = $db->prepare('SELECT 1 FROM composers WHERE fullname = :fullname LIMIT 1');
				$select->execute(array(':fullname'=>$fullname));
				if ($select->rowCount() == 0) {
					// No, re-use the profile of the parent composer folder then
					$fullname = str_replace('/'.$folders[count($folders) - 1], '', $fullname);
					$isBorrowedProfile = true;
				}
			}

			// Get data for top part like birthday, country, etc.
			$select = $db->prepare('SELECT * FROM composers WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount())
				$row = $select->fetch();

			// Get data about players for the charts
			$select = $db->prepare('SELECT player, count(player) AS count FROM hvsc_files WHERE fullname LIKE :fullname GROUP BY player');
			$select->execute(array(':fullname'=>$escaped_fullname.'/%'));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$player_labels = Array();
			$player_counts = Array();
			if ($select->rowCount()) {
				foreach($select as $player_row) {
					$player_labels[] = empty($player_row->player) ? 'Unidentified player' : $player_row->player;
					$player_counts[] = $player_row->count;
				}
				foreach($player_labels as $key => $label) {
					if (isset($prettyPlayerNames[$label]))
						$player_labels[$key] = str_replace('a Basic Program', 'Basic Program', $prettyPlayerNames[$label]);
					else
						$player_labels[$key] = str_replace('_', ' ', preg_replace('/(V)(\d)/', 'v$2', $player_labels[$key]));
					$player_labels[$key] = str_replace('/', ' / ', $player_labels[$key]);
				}

				$max_allowed = 14; // 9
				array_multisort($player_counts, $player_labels);
				if (count($player_counts) > $max_allowed) {
					$less_counts = array_slice($player_counts, 0, count($player_counts) - $max_allowed);
					$player_labels = array_slice($player_labels, -$max_allowed);
					$player_counts = array_slice($player_counts, -$max_allowed);
					array_unshift($player_labels, 'Other');
					array_unshift($player_counts, (string)array_sum($less_counts));
				}
			}

			// Get data about active years
			$select = $db->prepare('SELECT copyright FROM hvsc_files WHERE fullname LIKE :fullname');
			$select->execute(array(':fullname'=>$escaped_fullname.'/%'));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$years = Array();
			if ($select->rowCount()) {
				foreach($select as $player_row) {
					$year = substr($player_row->copyright, 0, 4);
					if (is_numeric($year)) $years[] = $year;
				}
			}
			sort($years);

			$first_year = $years[0];
			$last_year =  end($years);

			$ycounts = array_count_values($years);
			$years_labels = Array();
			$years_counts = Array();
			if (!empty($years)) {
				for($year = 1982; $year <= date("Y") ; $year++) {
					$years_labels[] = substr($year, -2);
					$years_counts[] = array_key_exists($year, $ycounts) ? $ycounts[$year] : null;
				}
				$years_counts = Array($years_counts);
			}

			// Get the user's rating for the folder
			$select = $db->prepare('SELECT id, hash FROM hvsc_folders WHERE fullname = :fullname'.
				(substr($fullname, 0, 1) == '!' ? ' AND user_id = '.$user_id : '').' LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			$select->setFetchMode(PDO::FETCH_OBJ);
			$row_folder = $select->fetch();

			$user_id = $account->CheckLogin() ? $account->UserID() : 0;

			if ($user_id) {
				// Does the user have any rating for this folder?
				if (!empty($row_folder->hash)) {
					// Search hash first (best; will catch it if set for a clone)
					$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND hash = "'.$row_folder->hash.'" AND type = "FOLDER"');
					$select_rating->setFetchMode(PDO::FETCH_OBJ);
					$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
				}
				if (!$rating) {
					// Try again with direct table ID (some folders doesn't have a hash value)
					$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.$user_id.' AND table_id = '.$row_folder->id.' AND type = "FOLDER"');
					$select_rating->setFetchMode(PDO::FETCH_OBJ);
					$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;
				}
			}

			// Get the crosslink folder if there is any (i.e. HVSC composer has a CGSC folder or vice versa)
			// NOTE: For now 'LIMIT 1' but the folder could in the future return multiple results.
			$select_alt = $db->query('SELECT type, fullname FROM folders_map WHERE folders_id = '.$row_folder->id.' LIMIT 1');
			$select_alt->setFetchMode(PDO::FETCH_OBJ);
			$row_alt = $select_alt->fetch();

			$alt_type = $alt_fullname = '';
			if ($select_alt->rowCount()) {
				$alt_type = $row_alt->type;
				$alt_fullname = $row_alt->fullname;
			}

			// Only show focus icons in MUSICIANS profile pages
			if (preg_match('~(?:^|/)MUSICIANS/[^/]+/[^/]+(?:/|$)~i', $fullname)) {
				// Fetch focus flags (defaults to 'NONE' if no row)
				$select_focus = $db->prepare('SELECT focus1, focus2 FROM composers WHERE fullname = :fullname LIMIT 1');
				$select_focus->execute([':fullname' => $fullname]);
				$row_focus = $select_focus->fetch(PDO::FETCH_ASSOC) ?: ['focus1' => 'NONE', 'focus2' => 'NONE'];

				$focus1 = $row_focus['focus1'];
				$focus2 = $row_focus['focus2'];

				// Icon snippets
				$icon = [
					'PRO'   	=> '<div class="p"  title="Professional"></div>',
					'SCENER'	=> '<div class="s"  title="Scener"></div>',
					'CNET'  	=> '<div class="c"  title="Compunet"></div>',
					'BOTB'  	=> '<div class="b"  title="Battle of the Bits"></div>',
					'MC'    	=> '<div class="m"  title="Master Composer"></div>',
					'LS'    	=> '<div class="l"  title="Loadstar Songsmith"></div>',
					'DM'    	=> '<div class="d"  title="DefleMask"></div>',
					'BASIC' 	=> '<div class="bc" title="BASIC"></div>',
					'NONE'		=> '<div class="none"></div>',
					'EXTRA'		=> '<div class="extra"></div>'
				];

				$focusExtra = $icon['EXTRA'];

				// Left icon (and special case for CNET)
				if ($focus1 === 'CNET') {
					$focusExtra = $icon['CNET'];
					$focusLeft = $icon['NONE'];
				} else	
					$focusLeft = $icon[$focus1] ?? $icon['NONE'];

				// Right icon (and special case for CNET)
				if ($focus2 === 'CNET+SCENER') {
					$focusExtra = $icon['CNET'];
					$focusRight  = $icon['SCENER'];
				} else if ($focus2 === 'CNET') {
					$focusExtra = $icon['CNET'];
					$focusRight = $icon['NONE'];
				} else {
					$focusRight  = $icon[$focus2] ?? $icon['NONE'];
				}

				$folderFocus = '<div class="folder-focus">'.$focusExtra.$focusLeft.$focusRight.'</div>';
			}

		} catch(PDOException $e) {
			$account->LogActivityError(basename(__FILE__), $e->getMessage() + ' (' + $fullname + ')');
			die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
		}
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

$uploadFolder = '_SID Happens';

// Figure out if the fullname is a folder with folders or a folder belonging to a composer (or group)
$files = glob(ROOT_HVSC.'/'.$fullname.'/*.{sid,mus}', GLOB_BRACE);
if (!empty($files) && !in_array($fullname, Array(
	'DEMOS/0-9',
	'DEMOS/A-F',
	'DEMOS/G-L',
	'DEMOS/M-R',
	'DEMOS/S-Z',
	'DEMOS/UNKNOWN',
	'GAMES/0-9',
	'GAMES/A-F',
	'GAMES/G-L',
	'GAMES/M-R',
	'GAMES/S-Z',
	'_Datastorm 2018',						// Deprecated
	'_From JCH\'s Special Collection',		// Deprecated
))) {
	// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
	if (strpos($fullname, $uploadFolder) !== false) {
		$thumbnail = 'images/composers/_sh.png';
	} else {
		$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
		$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
		$fn = str_replace('_Exotic SID Tunes Collection', 'estc', $fn);
		$fn = strtolower(str_replace('/', '_', $fn));
		$thumbnail = 'images/composers/'.$fn.'.jpg';
	}
	if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
} else if (strpos($fullname, $uploadFolder) !== false) {
	$thumbnail = 'images/composers/_sh.png';
} else if (strpos($fullname, '/GROUPS/') !== false) {
	// The unofficial folder with groups
	$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
	$fn = str_replace(' ', '_', $fn);
	$fn = strtolower(str_replace('/', '_', $fn));
	$thumbnail = 'images/composers/'.$fn.'.jpg';
	if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/folder.png';
	$csdbid = 0;
} else {
	// Folder with folders
	$thumbnail = 'images/folder.png';
	$csdbid = 0;
}

$sh_year = '';
if (strpos($fullname, $uploadFolder) !== false) {
	// Get the year if inside a year folder inside 'SID Happens'
	$parts = explode("/", $fullname);
	if (array_key_exists(1, $parts) && strlen($parts[1]) == 4 && is_numeric($parts[1]))
		$sh_year = $parts[1];
}

if (isset($row)) {
	// If there are both a birth and death year, calculate the age of death
	$year_birth = (int) substr($row->born, 0, 4);
	$year_death = (int) substr($row->died, 0, 4);
	$age_of_death = $year_birth && $year_death ? ' ('.$year_death - $year_birth.')' : '';
	$age_current = $year_birth && empty($age_of_death) ? ' ('.date("Y") - $year_birth.')' : '';

	// We have extended info from the 'composers' database table
	$name			= $row->name;
	$handles		= str_replace(', ', ', <img class="arrow" src="images/composer_arrowright.svg" alt="" style="position:relative;top:1px;" />', $row->handles);
	$born			= $row->born; 
	$died			= substr($row->died, 0, 4);
	$age_death		= $age_of_death;
	$age_now		= $age_current;
	$notable		= str_replace('[#]', '<img class="inline-icon icon-editor" src="images/composer_editor.svg" title="Music editor" alt="">', $row->notable);
	$country		= $row->country;
	$csdbtype		= $row->csdbtype;
	$csdbid			= $row->csdbid;
	$brand_light	= $row->brand;
	$brand_dark		= $row->branddark;
	$spinner		= true;

	$died = $died == '1970' ? '<i>Unknown date</i>' : $died;

	// Append flag images to the potentially comma-separated list of multiple countries
	foreach ($countryCodes as $key => $code) {

		$c_pattern = '/\b' . preg_quote($key, '/') . '\b/i';

		$country = preg_replace_callback(
			$c_pattern,
			function ($matches) use ($code) {
				return $matches[0]
					. ' <img class="flag" src="images/countries/'.$code.'.png" alt="'.$code.'" />';
			},
			$country
		);
	}

} else {
	// No database help; we have to figure things out for ourselves
	$name			= substr('/'.$fullname, strrpos('/'.$fullname, '/') + 1);
	$handles		= '';
	$born			= '0000-00-00';
	$died			= '0000';
	$age_death		= '';
	$age_now		= '';
	$notable		= '';
	$country		= '';
	$csdbid			= 0;
	$brand_light	= '';
	$brand_dark		= '';
	$spinner		= false;

	// Ditch the prepended custom "_" or symlist "!" character
	// @todo Uh, why is '!' here? Does that ever appear in a composer name!?
	$name = substr($name, 0, 1) == '_' || substr($name, 0, 1) == '!' ? substr($name, 1) : $name;
}

if ($name == '?')
	$name = '<small class="u1">?</small>?<small class="u2">?</small>';

$csdbCompoFolder = 'CSDb Music Competitions';
$exoticFolder = '_Exotic SID Tunes Collection';

$clink = '';
if (isset($row)) {
	$clink_name = empty($row->shortname) ? $row->name : $row->shortname;
	if ($clink_name == '?') {
		$clink_handle = '';
		$clink_name = $row->shorthandle;
		if (empty($clink_name)) {
			$chandles = explode(',', $row->handles);
			$clink_name = end($chandles);
		}
	} else {
		$clink_handle = $row->shorthandle;
		if (empty($clink_handle) || $clink_handle == '&nbsp;') {
			$chandles = explode(',', $row->handles);
			$clink_handle = end($chandles);
		}
	}
	$clink = '<span class="line"><img class="icon clinks" src="images/composer_link.svg" title="Links" alt="" style="position:relative;top:2.5px;height:16px;" /><a href="#" class="clinks" data-id="'.$row->id.'" data-name="'.$clink_name.'" data-handle="'.$clink_handle.'">Links</a><img class="icon clinks" src="images/composer_arrowright.svg" alt="" style="position:relative;top:3px;height:15px;margin-left:3px;" alt="" /></span>';
}

$crossfolder = '';
if (!empty($alt_type))
	$crossfolder = '<span class="line"><img class="icon xfolder" src="images/composer_folder.svg" title="See also" alt="" style="position:relative;top:3px;height:16px;margin-right:5px;" /><a href="?file=/'.$alt_fullname.'">'.$alt_type.'</a></span>';

// HTML for the slender 'Profile' tab in the annex box
if (isset($row)) {
	$annex_all_handles = $row->handles;
	$annex_full_name = $row->name;
	$annex_thumbnail = strpos($thumbnail, '.jpg')
		? '<img class="composer" src="'.$thumbnail.'" alt="" />'
		: '';
	$annex_born = $row->born != '0000-00-00' 
		? '<img class="icon cake" src="images/composer_cake.svg" title="Born" alt="" />'.substr($row->born, 0, 4)
		: '';
	$annex_died = $row->died != '0000-00-00'
		? '<img class="icon stone" style="height:16px;top:6.3px;margin-left:10px;" src="images/composer_stone.svg" title="Died" alt="" />'.substr($row->died, 0, 4)
		: '';

	// Determine plot positions in the mini activity chart below
	// @todo I'm sure this could be done a lot smarter.
	if ($first_year === date("Y"))
		$x1 = 106;
	else if ($first_year < 1987)
		$x1 = 6;
	else if ($first_year < 1997)
		$x1 = 26;
	else if ($first_year < 2007)
		$x1 = 46;
	else if ($first_year < 2017)
		$x1 = 66;
	else if ($first_year < 2027)
		$x1 = 86;

	if ($last_year === date("Y"))
		$x2 = 106;
	else if ($last_year < 1987)
		$x2 = 6;
	else if ($last_year < 1997)
		$x2 = 26;
	else if ($last_year < 2007)
		$x2 = 46;
	else if ($last_year < 2017)
		$x2 = 66;
	else if ($last_year < 2027)
		$x2 = 86;

	if (empty($first_year) && empty($last_year)) {
		$first_year = substr($player_row->copyright, 0, 4); // The last one obtained is fine
		if ($first_year === '198?') {
			$first_year = $last_year = '198?';
			$x1 = 6;
		} else if ($first_year === '199?') {
			$first_year = $last_year = '199?';
			$x1 = 26;
		} else if ($first_year === '19??') {
			$first_year = $last_year = '19??';
			$x1 = 26;
		}
	}

	// Always show just one country
	$parts = preg_split('/,\s*/', $country);
	$last_country = trim(end($parts));
	$single_country = count($parts) > 1
		? '<img class="icon arrow" style="height:16px;" src="images/composer_arrowright.svg" title="Moved" alt="" /> ' . $last_country
		: $last_country;
	$annex_country = '<br /><div class="annex-condensed" style="width:100%;margin-top:4px;">'.(!empty($country) ? '<img class="icon earth" style="top:4px;" src="images/composer_earth.svg" title="Country" alt="" /><span>'.$single_country.'</span>' : '').'<div style="float:right;margin-top:4px;">'.$folderFocus.'</div></div>';

	$annex_html = '
		<h3 class="ellipsis" style="width:229px;margin-bottom:0;" title="'.$annex_full_name.'">'.$clink_name.'</h3>
		<h4 class="ellipsis" style="width:229px;margin-top:0;" title="'.$annex_all_handles.'">'.$clink_handle.'</h4>
		' . $annex_thumbnail
		  . ($isExoticComposerFolder || $isBorrowedProfile ? '' : '<span class="folder-rating"></span>')
		  . '<div class="annex-condensed" style="position:relative;float:right;top:-4px;">' . $annex_born
		  . $annex_died . '</div>'
		  . $annex_country .
		// Below is empty groups/work table placeholder
		(!$isGroupsFolder ?
			'<div id="annex-groups-box"><table id="annex-table-groups" class="tight top" style="min-width:100%;font-size:14px;margin-top:5px;">'.
				'<tr>'.
					'<td id="annex-table-message" class="topline bottomline leftline rightline" style="height:30px;padding:0 !important;text-align:center;">'.($spinner ? '<img class="loading-dots" src="images/loading_threedots.svg" alt="" style="margin-top:10px;" />' : '<div class="no-profile">No profile data</div>').'</td>'.
				'</tr>'.
			'</table></div>' .
			// Activity (years) - sort of reverse engineered 'Chartist'
			(strpos($fullname, $uploadFolder) === false ?
				'<div style="white-space:nowrap;"><h4 style="display:inline-block;margin-top:12px;margin-right:8px;">Active</h4><span class="ct-label">'. ($last_year !== $first_year ? $first_year : ($first_year < 2007 ? '<div style="display:inline-block;width:35px;"></div>' . $first_year : '<div style="display:inline-block;width:27px;"></div>')) .'</span>
				<div style="display:inline-block;height:24px;width:114px;position:relative;top:7px;">
					<svg class="ct-chart-line" style="width:100%;height:100%;">
						<g class="ct-grids">
							<line class="ct-grid ct-horizontal" x1="6" x2="106" y1="1" y2="1"></line>
							<line class="ct-grid ct-horizontal" x1="6" x2="106" y1="23" y2="23"></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="6" x2="6" ></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="26" x2="26" ></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="46" x2="46" ></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="66" x2="66" ></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="86" x2="86" ></line>
							<line class="ct-grid ct-vertical" y1="1" y2="23" x1="106" x2="106" ></line>
						</g>
						<g>
							<g class="ct-series ct-series-a">' .
								($last_year > $first_year
									? '<line class="ct-line" x1="'.$x1.'" x2="'.$x2.'" y1="12" y2="12"></line>'
									: '') . '
								<line class="ct-point" x1="'.$x1.'" x2="'.$x1.'" y1="12" y2="12"></line>' .
								($last_year > $first_year
									? '<line class="ct-point" x1="'.$x2.'" x2="'.$x2.'" y1="12" y2="12"></line>'
									: '') . '
							</g>
						</g>
					</svg>
				</div><span class="ct-label"' . ($last_year ===  date("Y") ? ' style="font-weight:bold;color:var(--color-text-resp-good);"' : '' ). '>' . ($last_year !== $first_year ? $last_year : ($first_year > 2007 ? $last_year : '')) . '</span>
				</div>'
				: '')
			: '');

} else {
	$annex_html = '<div class="annexMsg">No profile to show.</div>';
}

// Top part with thumbnail, birthday, country, etc.
$html = '<table style="border:none;margin-bottom:0;"><tr>'.
			'<td style="position:relative;padding:0;border:none;width:184px;">'.
				(!empty($sh_year) ? '<div style="position:absolute;top:23px;left:22px;color:#33c;font:normal 15px &quot;Commodore 64&quot;, sans-serif"><b>'.$sh_year.'</b></div>' : '').
				'<img class="composer'.($fullname == $uploadFolder ? ' nobg' : '').'" src="'.$thumbnail.'" alt="" />'.
			'</td>'.
			'<td style="position:relative;vertical-align:top;">'.
				'<h2 style="margin-top:0;'.(!empty($handles) ? 'margin-bottom:-1px;' : 'margin-bottom:6px;').'">'.$name.$folderFocus.'</h2>'.
				(!empty($handles) ? '<h3 style="margin-top:0;margin-bottom:7px;">'.$handles.'</h3>' : '').
				($isExoticComposerFolder || $isBorrowedProfile ? '' : '<span class="line folder-rating"></span>'). // Placeholder for star ratings (handled by JS)
				($born != '0000-00-00' ? '<span class="line"><img class="icon cake" src="images/composer_cake.svg" title="Born" alt="" />'.
					substr($born, 0, 4).$age_now.'</span>' : '').
				($died != '0000' ? '<span class="line"><img class="icon stone" src="images/composer_stone.svg" title="Died" alt="" style="position:relative;top:3px;height:18px;margin-right:5px;" />'.
					$died.$age_death.'</span>' : '').
				$crossfolder.
				$clink.
				(!empty($notable) ? '<span class="notable">'.
					'<img class="icon cstar" src="images/composer_star.svg" title="Notable" alt="" style="top:-1px;" /><b style="position:relative;top:-5px;">'.$notable.'&nbsp;</b></span>' : '').
				(!empty($country) ? '<span style="position:absolute;left:10px;bottom:10px;">'.
					'<img class="icon earth" src="images/composer_earth.svg" title="Country" alt="" />'.
					str_replace(', ', ', <img class="arrow" src="images/composer_arrowright.svg" title="Moved" alt="" />', $country).
				'</span>' : '').
				(!empty($brand_light)
				? '<img id="brand-light" class="brand" src="images/brands/'.$brand_light.'" alt="'.$brand_light.'" style="display:none;" />'
				: '').
				(!empty($brand_dark)
				? '<img id="brand-dark" class="brand" src="images/brands/'.$brand_dark.'" alt="'.$brand_dark.'" style="display:none;" />'
				: '').
			'</td>'.
		'</tr></table>'.
		// Below is empty groups/work table placeholder
		($fullname != $csdbCompoFolder && $fullname != $exoticFolder && $fullname != $uploadFolder ?
			'<div id="groups-box"><table id="table-groups" class="tight top" style="min-width:100%;font-size:14px;margin-top:5px;">'.
				'<tr>'.
					'<td id="table-message" class="topline bottomline leftline rightline" style="height:30px;padding:0 !important;text-align:center;">'.($spinner ? '<img class="loading-dots" src="images/loading_threedots.svg" alt="" style="margin-top:10px;" />' : '<div class="no-profile">No profile data</div>').'</td>'.
				'</tr>'.
			'</table></div>' : '').
		'<div class="corner-icons">'.
			'<div id="profilechange" style="'.($csdbid ? 'left:-153' : 'right:-3').'px;"></div>'.
			($csdbid ? '<a href="http://csdb.chordian.net/?type='.$csdbtype.'&id='.$csdbid.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>' : '').
		'</div>';

// Chartist - @link https://gionkunz.github.io/chartist-js/index.html
$cgsc = "_Compute's Gazette SID Collection";
$isCGSC = substr($fullname, 0, strlen($cgsc)) === $cgsc;

if ($fullname == $exoticFolder) {
	// Show a box with technical information about the custom SID format
	$info = file_get_contents('../sidv4e.txt');
	$html .= '<pre class="fixed-font-info">'.$info.'</pre>';

} else if ($fullname == $uploadFolder) {
	// Show a box with information about uploading to the 'SID Happens' folder
	$info = file_get_contents('../upload.txt');
	$html .= '<pre class="fixed-font-info">'.$info.'</pre>';
	
} else if ($fullname != $csdbCompoFolder && strpos($fullname, '/GROUPS') === false) {
	// Charts for HVSC sub folders as well as custom "_" folders
	$html .= '<h3 style="margin-top:21px;">Active years<div class="legend">X = year (1982-)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Y = number of SID files</div></h3>
		<div id="ct-years"></div>'.
		(!$isCGSC
			? '<h3 style="margin-top:0;">Players used</h3><div id="ct-players"></div>'
			: '').
		'<script type="text/javascript">'.
			/*'console.log("Labels: ", '.json_encode($player_labels).');
			console.log("Series: ", '.json_encode($player_counts).');'.*/
			'ctYears = new Chartist.Line("#ct-years",
			{
				labels: '.json_encode($years_labels).',
				series: '.json_encode($years_counts).',
			},
			{
				height: 400,
				fullWidth: true,
				chartPadding: {
					top: 16,
					right: 30,
				},
				axisX: {
					labelOffset: {
						x: -7,
						y: 2
					}
				},
				axisY: {
					offset: 30,
					onlyInteger: true,
				},
			});'.
			(!$isCGSC
				? 'ctPlayers = new Chartist.Bar("#ct-players",
				{
					labels: '.json_encode($player_labels).',
					series: '.json_encode($player_counts).',
				},
				{
					height: '.((32 * count($player_labels)) + 42).',
					/*width: "90%",*/
					horizontalBars: true,
					distributeSeries: true,
					chartPadding: {
						top: 0,
						right: 90,
					},
					axisX: {
						onlyInteger: true,
					},
					axisY: {
						offset: 140,
						showGrid: false,
					},
				}).on("draw", function(data) {
					if(data.type === "bar") {
						data.element.attr({
							style: "stroke-width: 20px"
						});
					}
				});'
				: '').
		'</script>';
}

echo json_encode(array('status' => 'ok', 'html' => $html, 'annex_html' => $annex_html, 'rating' => $rating));
?>