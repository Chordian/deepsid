<?php
/**
 * DeepSID
 *
 * Build an HTML welcome page for the root.
 * 
 *  - Three recommendation boxes
 *  - Random "descent" box
 *  - Important message (good or bad)
 *  - Left and right boxes for top lists
 */

require_once("class.account.php"); // Includes setup
require_once("root_generate.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$decent_box_shown = false;

// $important = 'The database connections sometimes act up at the moment. If it persists I will consult the web hotel provider.';
$important = 'The audio bug seems to be fixed in version 91 of Chrome and Edge. Make sure you have updated to this version.';

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

	function CreateRecBox($random_id) {

		global $db, $decent_box_shown;

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
			if (!$decent_box_shown) {

				// Show a "decent" randomizer box ("CLICK HERE")
				$decent_box_shown = true;
				$decent_composers = [];

				// Get an array of all the folder ID belonging to composers JCH have given 2 stars or more
				$select_decent = $db->query('SELECT table_id FROM ratings WHERE user_id = '.JCH.' AND rating >= 2 AND type = "FOLDER"');
				$select_decent->setFetchMode(PDO::FETCH_OBJ);
				foreach($select_decent as $row_decent)
					array_push($decent_composers, $row_decent->table_id);

				// Pick a random "decent" folder
				$random_decent = $decent_composers[array_rand($decent_composers)];

				// Get the fullname of it
				$select_decent = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$random_decent);
				$select_decent->setFetchMode(PDO::FETCH_OBJ);

				return '<table class="tight compo recommended decent" data-folder="'.$select_decent->fetch()->fullname.'" style="padding-bottom:0;"><tr><td style="height:123px;">'.
					'<div class="random-container">'.
						'<span>Click here</span><br />'.
						'to visit a random<br />'.
						'composer folder of a<br />'.
						'decent quality or better<br />'.
					'</div>'.
				'</td></tr></table>';
			} else
				// Just shown empty space there
				return '<table class="tight compo recommended" style="border:none;"></table>';
		}

		$name = empty($row->shortname) ? $row->name : $row->shortname;
		$parts = explode(',', $row->handles);
		$handle = empty($row->shorthandle) ? end($parts) : $row->shorthandle;

		// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
		$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
		$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
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
						'<div style="position:absolute;bottom:8px;"><img class="icon doublenote" src="images/composer_doublenote.svg" title="Country" alt="" />'.$songs.' songs</div>'.
					'</td>'.
				'</tr>'.
			'</table>';
	}

	$good_composers = [];

	// Get an array of all the folder ID belonging to composers JCH have given 3 stars or more
	$select = $db->query('SELECT table_id FROM ratings WHERE user_id = '.JCH.' AND rating >= 3 AND type = "FOLDER"');
	$select->setFetchMode(PDO::FETCH_OBJ);
	foreach($select as $row)
		array_push($good_composers, $row->table_id);

	// Randomly choose three ID's while also making sure they're not the same ones
	$choices = array_rand($good_composers, 3);
	$random_id_1 = $good_composers[$choices[0]];
	$random_id_2 = $good_composers[$choices[1]];
	$random_id_3 = $good_composers[$choices[2]];

} catch(PDOException $e) {
	$account->LogActivityError('root.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$html =
	'<div style="height:149px;"></div>'.
	'<div class="root-wide good-news">'.$important.'</div>'.
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
	'</table>'.
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
	// Banner exchange
	'<div style="text-align:center;">'.
		'<iframe src="https://cbm8bit.com/banner-exchange/show-random-banner/any?width=468" title="Commodore Banner Exchange" frameborder="0" style="width: 468px; height: 60px; border: 0; margin: 5px;"></iframe><br />'.
		'<small style="position:relative;top:-13px;"><a target="_blank" href="https://cbm8bit.com/banner-exchange/" title="Commodore Banner Exchange">Commodore Banner Exchange</a></small>'.
	'</div>';

echo json_encode(array('status' => 'ok', 'html' => $html, 'left' => $choice_left, 'right' => $choice_right));
?>