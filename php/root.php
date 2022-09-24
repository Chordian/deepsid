<?php
/**
 * DeepSID
 *
 * Build an HTML welcome page for the root.
 * 
 *  - Three recommendation boxes
 *  - Random "decent" or CShellDB box
 *  - Left and right boxes for top lists
 *  - Active, procrastinating and game composers
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("root_generate.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

/**
 * Return the HTML for a recommendation box.
 *
 * @global		object		$db					database connection
 * @global		bool		$alt_box			array of alternative boxes
 * 
 * @param		int			$random_id			random ID for a HVSC composer
 *
 * @return		string							HTML
 */
function CreateRecBox($random_id) {

	global $db, $alt_box;

	// Get the fullname
	$select = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$random_id);
	$select->setFetchMode(PDO::FETCH_OBJ);
	$fullname = $select->rowCount() ? $select->fetch()->fullname : '';

	// Get composer data via the fullname
	$select = $db->query('SELECT name, shortname, handles, shorthandle FROM composers WHERE fullname = "'.$fullname.'"');
	$select->setFetchMode(PDO::FETCH_OBJ);
	$row = $select->fetch();

	// Error or irrelevant (such as big parent folders in HVSC)
	if ($select->rowCount() == 0) {

		// Choose random box then remove it as a choice next time
		shuffle($alt_box);
		switch (array_pop($alt_box)) {

		/*$sovs = BOX_PLAYLIST;
		switch ($sovs) { */

			case BOX_DECENT:

				// Show a "decent" randomizer box ("CLICK HERE")
				$decent_composers = [];

				// Get an array of all the folder ID belonging to composers the 'Ratings' user have given 2 stars or more
				$select_decent = $db->query('SELECT table_id FROM ratings WHERE user_id = '.USER_RATINGS.' AND rating >= 2 AND type = "FOLDER"');
				$select_decent->setFetchMode(PDO::FETCH_OBJ);
				foreach($select_decent as $row_decent)
					array_push($decent_composers, $row_decent->table_id);

				// Pick a random "decent" folder
				$random_decent = $decent_composers[array_rand($decent_composers)];

				// Get the fullname of it
				$select_decent = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$random_decent);
				$select_decent->setFetchMode(PDO::FETCH_OBJ);

				$return_html = $select_decent->rowCount()
					? '
						<table class="tight compo recommended pseudo decent" data-folder="'.$select_decent->fetch()->fullname.'" style="padding-bottom:0;">
							<tr>
								<td style="height:123px;">
									<div class="random-container">
										<span>Click here</span><br />
										to visit a random<br />
										composer folder of a<br />
										decent quality or better
									</div>
								</td>
							</tr>
						</table>'
					: '<table class="tight compo recommended" style="border:none;"></table>';
				return $return_html;

			case BOX_PLAYLIST:

				$playlist_folders = [];

				// Get an array of all public playlists
				$select_playlist = $db->query('SELECT id FROM hvsc_folders WHERE fullname LIKE "$%"');
				$select_playlist->setFetchMode(PDO::FETCH_OBJ);
				foreach($select_playlist as $row_playlist)
					array_push($playlist_folders, $row_playlist->id);

				// Pick a random public playlist folder
				$random_playlist = $playlist_folders[array_rand($playlist_folders)];

				// Get the needed information from this playlist entry
				$select_playlist = $db->query('SELECT fullname, files, user_id FROM hvsc_folders WHERE id = '.$random_playlist.' LIMIT 1');
				$select_playlist->setFetchMode(PDO::FETCH_OBJ);
				$playlist = $select_playlist->fetch();

				$plural = $playlist->files > 1 ? 's' : '';

				// Get the handle of the playlist creator
				$select_user = $db->query('SELECT username FROM users WHERE id = '.$playlist->user_id.' LIMIT 1');
				$select_user->setFetchMode(PDO::FETCH_OBJ);
				$handle = $select_user->fetch()->username;

				return
					'<table class="tight compo recommended" data-folder="'.$playlist->fullname.'">'.
						'<tr>'.
							'<td colspan="2" style="padding-right:10px;"><img class="folder" src="images/if_folder_star.svg" alt="" /><h3>Playlist spotlight</h3></td>'.
						'</tr>'.
						'<tr>'.
							'<td style="height:85px;padding-top:1px;padding-right:10px;">'.
								'<h5>'.substr($playlist->fullname, 1).'</h5>'.
								'<div style="position:absolute;bottom:8px;"><img class="icon doublenote" src="images/composer_doublenote.svg" title="Songs" alt="" />'.$playlist->files.' song'.$plural.'<span style="font-family:Asap Condensed,sans-serif;margin-left:20px;"><img class="icon doublenote" src="images/select.svg" style="position:relative;top:4.5px;height:17.5px;" title="Songs" alt="" />'.$handle.'</span></div>'.
							'</td>'.
						'</tr>'.
					'</table>';
		
			case BOX_CSHELLDB:

				// Show an "ad" for my other site CShellDB
				return '
					<table class="tight compo recommended pseudo cshelldb" data-folder="cshelldb" style="padding-bottom:0;">
						<tr>
							<td style="height:123px;">
								<div class="random-container">
									<span>Visit</span><br />
									a modern<br />
									interface<br />
									for CSDb
								</div>
							</td>
						</tr>
					</table>';

			case BOX_PLAYMOD:

				// Show an "ad" for Jürgen Wothke's site PlayMOD
				return '
					<table class="tight compo recommended pseudo playmod" data-folder="playmod" style="padding-bottom:0;">
						<tr>
							<td style="height:123px;">
								<div class="random-container">
									<span>PlayMOD</span><br />
									just like DeepSID<br />
									but for modules<br />
									and chiptunes
								</div>
							</td>
						</tr>
					</table>';
		}
	}

	$name = empty($row->shortname) ? $row->name : $row->shortname;
	$parts = explode(',', $row->handles);
	$handle = empty($row->shorthandle) ? end($parts) : $row->shorthandle;

	if ($name == '?')
		$name = '<small class="u1">?</small>?<small class="u2">?</small>';

	// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
	$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
	$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
	$fn = str_replace(' ', '_', $fn);
	$fn = strtolower(str_replace('/', '_', $fn));
	$thumbnail = 'images/composers/'.$fn.'.jpg';
	if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
	
	// Get type and file count
	$select = $db->query('SELECT type, files FROM hvsc_folders WHERE fullname = "'.$fullname.'"');
	$select->setFetchMode(PDO::FETCH_OBJ);
	$row = $select->fetch();
	$type = $row->type == 'GROUP' ? 'group' : 'single';
	$songs = $row->files;

	// Create the HTML table for the box

	return
		'<table class="tight compo recommended" data-folder="'.$fullname.'">'.
			'<tr>'.
				'<td colspan="2"><img class="folder" src="images/if_folder_'.$type.'.svg" alt="" /><h3>Recommended Folder</h3></td>'.
			'</tr>'.
			'<tr>'.
				'<td style="width:88px;padding-right:8px;">'.
					'<img class="composer root-thumbnail" src="'.$thumbnail.'" alt="" />'.
				'</td>'.
				'<td style="padding-top:1px;">'.
					'<h4>'.$name.'</h4>'.
					'<h5>'.$handle.'</h5>'.
					'<div style="position:absolute;bottom:8px;"><img class="icon doublenote" src="images/composer_doublenote.svg" title="Songs" alt="" />'.$songs.' songs</div>'.
				'</td>'.
			'</tr>'.
		'</table>';
}

/**
 * Create an associative array from the latest database query.
 * 
 * @global		object		$db					database connection
 * 
 * @param		object		&$select			reference to rows from database
 * @param		array		&$composers			reference to array of composers
 * @param		boolean		$pro				false (default) if not a game composer
 */
function CreateComposersArray(&$select, &$composers, $pro = false) {

	global $db;

	foreach($select as $row) {

		$name = $raw_name = empty($row->shortname) ? $row->name : $row->shortname;
		if ($name == '?') $name = '<small class="u1">?</small>?<small class="u2">?</small>';
		$parts = explode(',', $row->handles);
		$handle = trim(empty($row->shorthandle) ? end($parts) : $row->shorthandle);

		// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
		$hvsc_path = str_replace('_High Voltage SID Collection/', '', $row->fullname);
		$fn = str_replace(' ', '_', $hvsc_path);
		$fn = strtolower(str_replace('/', '_', $fn));
		$thumbnail = 'images/composers/'.$fn.'.jpg';
		if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';

		$sort_name = $pro
		 	// Professional game composers don't use handles
			? strtolower($raw_name)
			// Sort by handle (unless it's missing or abandoned then sort by real name instead)
			: strtolower(empty($handle) || stripos($handle, '<del>') !== false ? $raw_name : $handle);

		$composers[] = array(
			'sort'		=> $sort_name,
			'avatar'	=> $thumbnail,
			'file'		=> $hvsc_path,
			'name'		=> $name,
			'handle'	=> ($pro ? $row->affiliation : $handle),
		);
	}

	$sort_by = array_column($composers, 'sort');
	array_multisort($sort_by, SORT_ASC, $composers);	
}

/**
 * Return the HTML for one 'quick box' table cell.
 *
 * @param		array		&$author			reference to author array
 *
 * @return		string							HTML for one TD cell
 */
function QuickShortcutRow(&$author) {

	if (isset($author['file']))
		$return_row =
			'<td>'.
				'<table class="tight recommended quickbox" data-folder="'.$author['file'].'">'.
					'<tr>'.
						'<td class="quickline" colspan="2"></td>'.
					'</tr>'.
					'<tr>'.
						// src="'.$author['avatar'].'"
						'<td style="width:42px;padding:0 !important;">'.
							'<img class="composer quick-thumbnail" src="images/composer.png" alt="" data-src="'.$author['avatar'].'" />'.
						'</td>'.
						'<td style="padding-top:2px;">'.
							'<h4>'.$author['name'].'</h4>'.
							'<h5>'.$author['handle'].'</h5>'.
						'</td>'.
					'</tr>'.
				'</table>'.
			'</td>';
	else
		$return_row = '<td></td>';
	return $return_row;
}

/***** START *****/

const BOX_DECENT		= 0;
const BOX_PLAYLIST		= 1;
const BOX_CSHELLDB		= 2;
const BOX_PLAYMOD		= 3;

$alt_box = [BOX_DECENT, BOX_PLAYLIST, BOX_CSHELLDB, BOX_PLAYMOD];

$available_lists = ['maxfiles', 'longest', 'mostgames', 'countries', 'startaddr', 'maxtime'];
$dropdown_options =
	'<option value="'.$available_lists[0].'">Most SID tunes produced</option>'.
	'<option value="'.$available_lists[1].'">The longest SID tunes</option>'.
	'<option value="'.$available_lists[2].'">Most games covered</option>'.
	'<option value="'.$available_lists[3].'">Composers in countries</option>'.
	'<option value="'.$available_lists[4].'">Most popular start address</option>'.
	'<option value="'.$available_lists[5].'">Total playing time produced</option>'.
	'';

$row_options =
	'<option value="10">10</option>'.
	'<option value="25">25</option>'.
	'<option value="50">50</option>'.
	'<option value="100">100</option>'.
	'<option value="250">250</option>';

// Randomly choose two lists while also making sure they're not the same one
$choices = array_rand($available_lists, 2);
$choice_left = $available_lists[$choices[0]];
$choice_right = $available_lists[$choices[1]];

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$good_composers = [];

	// Get an array of all the folder ID belonging to composers the 'Ratings' user have given 3 stars or more
	$select = $db->query('SELECT table_id FROM ratings WHERE user_id = '.USER_RATINGS.' AND rating >= 3 AND type = "FOLDER"');
	$select->setFetchMode(PDO::FETCH_OBJ);
	foreach($select as $row)
		array_push($good_composers, $row->table_id);

	// Randomly choose three ID's while also making sure they're not the same ones
	$choices = array_rand($good_composers, 3);
	$random_id_1 = $good_composers[$choices[0]];
	$random_id_2 = $good_composers[$choices[1]];
	$random_id_3 = $good_composers[$choices[2]];

	// QUICK SHORTCUTS

	$composers_active = array();
	$composers_snoozing = array();
	$composers_game = array();

	// Get composers that were active this year (and are still alive)
	$select = $db->query('
		SELECT fullname, name, shortname, handles, shorthandle FROM composers
		WHERE active = "'.date("Y").'"
		AND died = "0000-00-00"
	');
	$select->setFetchMode(PDO::FETCH_OBJ);
	CreateComposersArray($select, $composers_active);

	// Get composers that were active last year (and are still alive)
	$select = $db->query('
		SELECT fullname, name, shortname, handles, shorthandle FROM composers
		WHERE active = "'.(date("Y") - 1).'"
		AND died = "0000-00-00"
	');
	$select->setFetchMode(PDO::FETCH_OBJ);
	CreateComposersArray($select, $composers_snoozing);

	// Get composers that made for games professionally (magazines don't count)
	$select = $db->query('
		SELECT fullname, name, shortname, handles, shorthandle, affiliation FROM composers
		WHERE (focus = "PRO" OR focus = "BOTH") AND fullname NOT LIKE "%/GROUPS/%"
	');
	$select->setFetchMode(PDO::FETCH_OBJ);
	CreateComposersArray($select, $composers_game, true);

	$i = 0;
	$quick_shortcuts = '';
	while (true) {
		$author_active = count($composers_active) > $i ? $composers_active[$i] : '';
		$author_snoozing = count($composers_snoozing) > $i ? $composers_snoozing[$i] : '';
		$author_game = count($composers_game) > $i ? $composers_game[$i] : '';

		if (empty($author_active) && empty($author_snoozing) &&  empty($author_game)) break;

		$quick_shortcuts .=
			'<tr>'.
				QuickShortcutRow($author_active).
				QuickShortcutRow($author_snoozing).
				QuickShortcutRow($author_game).
			'</tr>';
		$i++;
	}

} catch(PDOException $e) {
	$account->LogActivityError('root.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$html =
	'<div style="height:149px;"></div>'.
	// Recommendations
	'<table class="root rec"><tr>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_1).
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_2).
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_3).
		'</td>'.
	'</tr></table>'.
	// Top lists
	'<table class="root"><tr>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-left" name="select-top-list-left">'.
				$dropdown_options.
			'</select>'.
			'<label>Rows</label>'.
			'<select class="dropdown-top-rows dropdown-top-rows-left" name="select-top-rows-left">'.
				$row_options.
			'</select>'.
			'<table class="top-list-left tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList(10, $choice_left).
			'</table>'.
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-right" name="select-top-list-right">'.
				$dropdown_options.
			'</select>'.
			'<label>Rows</label>'.
			'<select class="dropdown-top-rows dropdown-top-rows-right" name="select-top-rows-right">'.
				$row_options.
			'</select>'.
			'<table class="top-list-right tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList(10, $choice_right).
			'</table>'.
		'</td>'.
	'</tr></table>'.
	// Quick shortcuts
	'<table class="root compo rec quicklinks">'.
		'<tr>'.
			'<th>Active Composers <span class="quickyear">'.date("Y").'</span></th>'.
			'<th>Procrastinators <span class="quickyear">'.(date("Y") - 1).'</span></th>'.
			'<th>Game Composers <span class="quickyear" style="margin:0;">1982&ndash;</span></th>'.
		'</tr>'.
		$quick_shortcuts.
	'</table>'.
	// Banner exchange
	'<div style="text-align:center;">'.
		'<iframe src="https://cbm8bit.com/banner-exchange/show-random-banner/any?width=468" title="Commodore Banner Exchange" frameborder="0" style="width: 468px; height: 60px; border: 0; margin: 5px;"></iframe><br />'.
		'<small style="position:relative;top:-13px;"><a target="_blank" href="https://cbm8bit.com/banner-exchange/" title="Commodore Banner Exchange">Commodore Banner Exchange</a></small>'.
	'</div>';

echo json_encode(array('status' => 'ok', 'html' => $html, 'left' => $choice_left, 'right' => $choice_right));
?>