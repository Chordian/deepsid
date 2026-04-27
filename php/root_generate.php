<?php
/**
 * DeepSID
 *
 * Procedures for returning the inside contents of a top list.
 * 
 * @used-by		root.php
 * @used-by		root_get.php
 */

require_once("class.account.php"); // Includes setup
require_once("countries.php"); // Used by the 'countries' list type

/**
 * Abbreviate certain texts in the 'collection_path' value and wrap them in
 * <FONT> tags to look better.
 *
 * @param		string		$collection_path
 * @param		string		$link				link for a HREF (default empty) 
 *
 * @return		string		$adapted_collection_path
 */
function adaptBrowserName($collection_path, $link = '') {
	$adapted_collection_path = str_replace('_High Voltage SID Collection', '<font class="dim">HVSC</font>', $collection_path);
	$adapted_collection_path = str_replace('HVSC</font>/DEMOS', 'HVSC/D</font>', $adapted_collection_path);
	$adapted_collection_path = str_replace('HVSC</font>/GAMES', 'HVSC/G</font>', $adapted_collection_path);
	$adapted_collection_path = str_replace('HVSC</font>/MUSICIANS', 'HVSC/M</font>', $adapted_collection_path);
	$adapted_collection_path = str_replace("_Compute's Gazette SID Collection", '<font class="dim">CGSC</font>', $adapted_collection_path);
	if (!empty($link))
		$adapted_collection_path = str_replace('</font>', '</font><a href="'.$link.'">', $adapted_collection_path).'</a>';
	return $adapted_collection_path;
}

/**
 * Generate a top list and return its HTML.
 *
 * @global		array		$countryCodes		array with abbreviations
 * 
 * @param		int			$rows				number of rows
 * @param		string		$type				type of top list
 *
 * @return		string		$contents			HTML
 */
function generateList($rows, $type) {

	global $account, $countryCodes;

	try {
		$db = $account->getDB();

		$list = [];

		// Define and get the information
		switch($type) {
			case 'maxfiles':

				$entry = "Composer";
				$value = 'Count';

				$select = $db->query('SELECT collection_path, files FROM hvsc_folders WHERE type = "SINGLE" AND collection_path NOT LIKE "%Worktunes" AND collection_path NOT LIKE "%/GROUPS/%" ORDER BY files DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						array_push($list, array(
							'entry' =>	adaptBrowserName($row->collection_path, HOST.'?file=/'.$row->collection_path),
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
				$select = $db->query('SELECT collection_path, length, subtune FROM hvsc_lengths WHERE collection_path NOT LIKE "%psykolog_end.sid" ORDER BY TIME_TO_SEC(length) DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$length = explode(' ', $row->length)[0];
						array_push($list, array(
							'entry' =>	adaptBrowserName($row->collection_path, HOST.'?file=/'.$row->collection_path.'&subtune='.($row->subtune + 1)),
							'value' =>	explode('.', $length)[0], // No MS
							'subtune' => $row->subtune + 1,
						));
					}
				}
				break;

			case 'mostgames':

				$entry = "Composer";
				$value = 'Games';

				$select = $db->query('
					SELECT
						collection_path,
						COUNT(1) AS c
					FROM hvsc_files
					WHERE EXISTS (
						SELECT 1
						FROM tags_lookup tl
						JOIN tags_info ti ON ti.id = tl.tags_id
						WHERE tl.files_id = hvsc_files.id
							AND ti.name = "GameBase64"
					)
					GROUP BY SUBSTRING_INDEX(collection_path, "/", 4)
					HAVING c > 1
					ORDER BY c DESC
					LIMIT '.$rows
				);
				$select->setFetchMode(PDO::FETCH_OBJ);

				if ($select->rowCount()) {
					foreach ($select as $row) {
						$folder = substr($row->collection_path, 0, strrpos($row->collection_path, '/'));
						$list[] = array(
							'entry' => adaptBrowserName($folder, HOST.'?file=/'.$folder),
							'value' => (int)$row->c,
						);
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

				$select = $db->query('SELECT load_addr, count(1) AS c FROM hvsc_files WHERE load_addr != 0 '.
					'GROUP BY load_addr ORDER BY c DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$load_addr = $row->load_addr;
						switch ($load_addr) {
							case 0x0801: $append = '&nbsp;&nbsp;(BASIC program)'; break;
							case 0x1800: $append = '&nbsp;&nbsp;(Generally Future Composer)'; break;
							case 0xA000: $append = '&nbsp;&nbsp;(Start of BASIC ROM)'; break;
							case 0xC000: $append = '&nbsp;&nbsp;(Free space after BASIC ROM)'; break;
							case 0xE000: $append = '&nbsp;&nbsp;(Start of KERNAL ROM)'; break;
							default:     $append = '';
						}
						array_push($list, array(
							'entry' =>	'Memory location: <span style="font:normal 14px/0 monospace"><b>$'.str_pad(strtoupper(dechex($load_addr)), 4, '0', STR_PAD_LEFT).'</b></span>'.$append,
							'value' =>	$row->c,
						));
					}
				}
				break;

			case 'maxtime':

				$entry = 'Composer';
				$value = 'Time';

				// This query makes use of the 'hvsc_length' table
				$select = $db->query('SELECT SUBSTRING_INDEX(collection_path, "/", 4) AS f, SUM(TIME_TO_SEC(length)) AS s FROM hvsc_lengths '.
					'WHERE collection_path LIKE "%/MUSICIANS/%" '.
					'GROUP BY f '.
					'ORDER BY s DESC LIMIT '.$rows);
				$select->setFetchMode(PDO::FETCH_OBJ);
				if ($select->rowCount()) {
					foreach($select as $row) {
						$total_seconds = $row->s / 60;
						$hours = floor($total_seconds / 3600);
						$minutes = str_pad(floor(($total_seconds / 60) % 60), 2, '0', STR_PAD_LEFT);
						array_push($list, array(
							'entry' =>	adaptBrowserName($row->f, HOST.'?file=/'.$row->f),
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
					'<td class="middle"><div class="block-wrap"><div class="block">'.
					(isset($item['subtune']) && $item['subtune'] > 1 ? '<div class="subtunes specific">'.$item['subtune'].'</div>' : '').
					'<div class="top-item slimfont">'.$item['entry'].'</div></div></div></td>'.
					'<td>'.$item['value'].'</td>'.
				'</tr>';

		return $contents;

	} catch(PDOException $e) {
		$account->logActivityError(basename(__FILE__), $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}
}
?>