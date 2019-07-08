<?php
/**
 * DeepSID
 *
 * Build an HTML page with all recommended boxes.
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get all the folder ID belonging to composers JCH have given 3 stars or more
	$select_rec = $db->query('SELECT table_id FROM ratings WHERE user_id = '.JCH.' AND rating >= 3 AND type = "FOLDER"');
	$select_rec->setFetchMode(PDO::FETCH_OBJ);

	$boxes = '';
	$mode = 0;

	foreach($select_rec as $row_rec) {

		// Get the fullname
		$select = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$row_rec->table_id);
		$select->setFetchMode(PDO::FETCH_OBJ);
		$fullname = $select->rowCount() ? $select->fetch()->fullname : '';

		// Get composer data via the fullname
		$select = $db->query('SELECT name, shortname, handles, shorthandle FROM composers WHERE fullname = "'.$fullname.'"');
		$select->setFetchMode(PDO::FETCH_OBJ);
		$row = $select->fetch();

		// Error or irrelevant (such as big parent folders in HVSC)
		if ($select->rowCount() == 0) continue;

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

		$prepend = '';
		switch($mode) {
			case 0;
				$prepend = '<tr>';
				// No break!
			case 1;
				$append = '<td style="width:10px;"></td>';
				break;
			case 2;
				$append = '</tr>';
		}
		$start = $prepend.'<td style="max-width:10px;">';
		$end = '</td>'.$append;

		$mode++;
		if ($mode == 3) $mode = 0;

		// Add the HTML table for the box to an array
		$boxes .=
			$prepend.'<td style="max-width:10px;padding-bottom:10px;">'.
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
				'</table>'.
			'</td>'.$append;
	}

} catch(PDOException $e) {
	$account->LogActivityError('root_recommended.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$html =
	'<div style="height:149px;"></div>'.
	'<table class="root rec rec-all">'.$boxes.'</table>';

echo json_encode(array('status' => 'ok', 'html' => $html));
?>