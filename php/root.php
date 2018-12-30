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

$lists = ['maxfiles', 'longest'];

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

				$entry = "Composer's folder";
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
							//'entry' =>	'<a href="'.HOST.'?file=/'.$row->fullname.'" class="redirect" title="'.str_replace('_High Voltage SID Collection/', '', $row->fullname).'">'.AdaptBrowserName($row->fullname).'</a>',
							'entry' =>	AdaptBrowserName($row->fullname, HOST.'?file=/'.$row->fullname.'&subtune='.($row->subtune + 1)),
							'value' =>	explode(' ', $row->length)[0],
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

$html =
	'<table class="root"><tr>'.
		'<td style="max-width:300px;">'.
			'<table class="top-list-left tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList('maxfiles').
			'</table>'.
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:300px;">'.
			'<table class="top-list-right tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList('longest').
			'</table>'.
		'</td>'.
	'</tr></table>';

echo json_encode(array('status' => 'ok', 'html' => $html));
?>