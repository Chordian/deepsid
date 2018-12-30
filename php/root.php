<?php
/**
 * DeepSID
 *
 * Build an HTML welcome page for the root.
 * 
 * @uses		$_GET['fullname'] (to folder)
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$available_lists = ['maxfiles', 'longest', 'mostgames'];
$dropdown_options =
	'<option value="'.$available_lists[0].'">Most SID tunes produced</option>'.
	'<option value="'.$available_lists[1].'">The longest SID tunes</option>'.
	'<option value="'.$available_lists[2].'">Most games covered</option>';

function AdaptBrowserName($fullname, $link = '') {
	$adapted_fullname = str_replace('_High Voltage SID Collection', '<font class="dim">HVSC</font>', $fullname);
	$adapted_fullname = str_replace('HVSC</font>/DEMOS', 'HVSC/D</font>', $adapted_fullname);
	$adapted_fullname = str_replace('HVSC</font>/GAMES', 'HVSC/G</font>', $adapted_fullname);
	$adapted_fullname = str_replace('HVSC</font>/MUSICIANS', 'HVSC/M</font>', $adapted_fullname);
	$adapted_fullname = str_replace("_Compute's Gazette SID Collection", '<font class="dim">CGSC</font>', $adapted_fullname);
	if (!empty($link))
		$adapted_fullname = str_replace('</font>', '</font><a href="'.$link.'">', $adapted_fullname).'</a>';
	return $adapted_fullname;
}

function GenerateList($type) {
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$list = [];

		// Define and get the information
		switch($type) {
			case 'maxfiles':

				$entry = "Composer";
				$value = 'Count';

				$select = $db->query('SELECT fullname, files FROM hvsc_folders WHERE type = "SINGLE" AND fullname NOT LIKE "%Worktunes" ORDER BY files DESC LIMIT 20');
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						array_push($list, array(
							'entry' =>	AdaptBrowserName($row->fullname, HOST.'?file=/'.$row->fullname),
							'value' =>	$row->files,
						));
					}
				}
				break;

			case 'longest':

				$entry = 'SID tune';
				$value = 'Time';

				// This query makes use of the 'hvsc_length' table created especially for this purpose
				$select = $db->query('SELECT fullname, length, subtune FROM hvsc_lengths ORDER BY TIME_TO_SEC(length) DESC LIMIT 20');
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						array_push($list, array(
							'entry' =>	AdaptBrowserName($row->fullname, HOST.'?file=/'.$row->fullname.'&subtune='.($row->subtune + 1)),
							'value' =>	explode(' ', $row->length)[0],
						));
					}
				}
				break;

			case 'mostgames':

				$entry = "Composer";
				$value = 'Games';

				$select = $db->query('SELECT fullname, application, count(1) as c FROM hvsc_files WHERE application = "RELEASE" '.
					'GROUP BY SUBSTRING_INDEX(fullname, "/", 4) HAVING c > 1 ORDER by c DESC LIMIT 20');
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$folder = substr($row->fullname, 0, strrpos($row->fullname, '/'));
						array_push($list, array(
							'entry' =>	AdaptBrowserName($folder, HOST.'?file=/'.$folder),
							'value' =>	$row->c,
						));
					}
				}
				break;

			default:
				break;
		}

		// Build the rows
		$contents = 
			'<tr>'.
				'<th style="width:18px;text-align:right;"><u>#</u></th>'.
				'<th style="padding-left:14px;"><u>'.$entry.'</u></th>'.
				'<th style="width:40px;text-align:right;"><u>'.$value.'</u></th>'.
			'</tr>';
		foreach($list as $key => $item)
			$contents .=
				'<tr>'.
					'<td>'.($key + 1).'</td>'.
					'<td class="middle"><div class="block-wrap"><div class="block"><div class="top-item slimfont">'.$item['entry'].'</div></div></div></td>'.
					'<td>'.$item['value'].'</td>'.
				'</tr>';

		return $contents;

	} catch(PDOException $e) {
		die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
	}
}

// Randomly choose two lists while also making sure they're not the same one
$choices = array_rand($available_lists, 2);
$choice_left = $available_lists[$choices[0]];
$choice_right = $available_lists[$choices[1]];

$html =
	'<div style="position:relative;top:-2px;left:135px;margin-bottom:150px;font:normal 12px Montserrat,sans-serif;color:#d3d4c6;"><i>Behold!&nbsp;&nbsp;You now find yourself in &ndash;</i></div>'.
	'<table class="root"><tr>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-left">'.
				$dropdown_options.
			'</select>'.
			'<table class="top-list-left tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList($choice_left).
			'</table>'.
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-right">'.
				$dropdown_options.
			'</select>'.
			'<table class="top-list-right tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList($choice_right).
			'</table>'.
		'</td>'.
	'</tr></table>';

echo json_encode(array('status' => 'ok', 'html' => $html, 'left' => $choice_left, 'right' => $choice_right));
?>