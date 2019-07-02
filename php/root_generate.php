<?php
/**
 * DeepSID
 *
 * A procedure for returning the inside contents of a top list.
 */

require_once("class.account.php"); // Includes setup
require_once("countries.php"); // Used by the 'countries' list type

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

function GenerateList($rows, $type) {

	global $countryCodes;

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

				$select = $db->query('SELECT fullname, files FROM hvsc_folders WHERE type = "SINGLE" AND fullname NOT LIKE "%Worktunes" ORDER BY files DESC LIMIT '.$rows);
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

				// This query makes use of the 'hvsc_length' table
				// NOTE: Sebastian Bjørnerud's "Psykolog_end.sid" is exempt because it's just a series of sound effects.
				// This was actually based on a poll: https://www.facebook.com/groups/deepsid/permalink/207861710163193/
				$select = $db->query('SELECT fullname, length, subtune FROM hvsc_lengths WHERE fullname NOT LIKE "%psykolog_end.sid" ORDER BY TIME_TO_SEC(length) DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$length = explode(' ', $row->length)[0];
						array_push($list, array(
							'entry' =>	AdaptBrowserName($row->fullname, HOST.'?file=/'.$row->fullname.'&subtune='.($row->subtune + 1)),
							'value' =>	explode('.', $length)[0], // No MS
						));
					}
				}
				break;

			case 'mostgames':

				$entry = "Composer";
				$value = 'Games';

				$select = $db->query('SELECT fullname, application, count(1) AS c FROM hvsc_files WHERE application = "RELEASE" '.
					'GROUP BY SUBSTRING_INDEX(fullname, "/", 4) HAVING c > 1 ORDER BY c DESC LIMIT '.$rows);
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

			case 'countries':

				$entry = "Country";
				$value = 'Count';

				$countryCounts = [];

				foreach($countryCodes as $country => $code) {
					$select = $db->query('SELECT count(1) AS c FROM composers WHERE country LIKE "%'.$country.'%"');
					$select->setFetchMode(PDO::FETCH_OBJ);
					array_push($countryCounts, array(
						'country' =>	($country == 'usa' ? 'USA' : ucwords($country)),
						'count' =>		$select->fetch()->c,
					));
				}

				usort($countryCounts, function ($item1, $item2) {
					if ($item1['count'] == $item2['count']) return 0;
					return $item1['count'] > $item2['count'] ? -1 : 1;
				});				

				for ($i = 0; $i < $rows; $i++) {
					array_push($list, array(
						'entry' =>	'<a href="'.HOST.'?type=country&search='.$countryCounts[$i]['country'].'">'.$countryCounts[$i]['country'].'</a>',
						'value' =>	$countryCounts[$i]['count'],
					));
				}
				break;

			case 'startaddr':

				$entry = "Start address";
				$value = 'Count';

				$select = $db->query('SELECT loadaddr, count(1) AS c FROM hvsc_files WHERE loadaddr != 0 '.
					'GROUP BY loadaddr ORDER BY c DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$loadaddr = $row->loadaddr;
						switch ($loadaddr) {
							case 0x0801: $append = '&nbsp;&nbsp;(BASIC program)'; break;
							case 0x1800: $append = '&nbsp;&nbsp;(Generally Future Composer)'; break;
							case 0xA000: $append = '&nbsp;&nbsp;(Start of BASIC ROM)'; break;
							case 0xC000: $append = '&nbsp;&nbsp;(Free space after BASIC ROM)'; break;
							case 0xE000: $append = '&nbsp;&nbsp;(Start of KERNAL ROM)'; break;
							default:     $append = '';
						}
						array_push($list, array(
							'entry' =>	'Memory location: <span style="font:normal 14px/0 monospace"><b>$'.str_pad(strtoupper(dechex($loadaddr)), 4, '0', STR_PAD_LEFT).'</b></span>'.$append,
							'value' =>	$row->c,
						));
					}
				}
				break;

			case 'maxtime':

				$entry = 'Composer';
				$value = 'Time';

				// This query makes use of the 'hvsc_length' table
				$select = $db->query('SELECT SUBSTRING_INDEX(fullname, "/", 4) AS f, SUM(TIME_TO_SEC(length)) AS s FROM hvsc_lengths '.
					'WHERE fullname LIKE "%/MUSICIANS/%" '.
					'GROUP BY f '.
					'ORDER BY s DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$total_seconds = $row->s / 60;
						$hours = floor($total_seconds / 3600);
						$minutes = str_pad(floor(($total_seconds / 60) % 60), 2, '0', STR_PAD_LEFT);
						array_push($list, array(
							'entry' =>	AdaptBrowserName($row->f, HOST.'?file=/'.$row->f),
							'value' =>	'<span class="slimfont">'.$hours.'h '.$minutes.'m</span>',
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
				'<th style="width:50px;text-align:right;"><u>'.$value.'</u></th>'.
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
		$account->LogActivityError('root_generate.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
}
?>