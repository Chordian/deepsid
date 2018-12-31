<?php
/**
 * DeepSID
 *
 * A procedure for returning the inside contents of a top 20 list.
 */

require_once("setup.php");

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
?>