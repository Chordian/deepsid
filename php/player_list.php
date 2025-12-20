<?php
/**
 * DeepSID
 *
 * Build the HTML page for listing all players/editors in the 'Players' tab.
 * 
 * @used-by		main.js
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

	$select = $db->query('SELECT *, case when title like "The %" then trim(substr(title from 4)) else title end as title2 FROM players_info ORDER BY title2');
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount()) {
		$account->LogActivityError(basename(__FILE__), 'No entries returned');
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find the information in the database.")));
	}

	$rows = '';

	foreach ($select as $row) {

		$search = empty($row->search) ? $row->title : $row->search;

		// Figure out the name of the first thumbnail
		$thumbnail = substr(glob('../images/players/'.$row->id.'_1_*.png')[0], 3);

		$devs = explode('|', str_replace('++', '', $row->developer));
		$developer = ' by ';
		$comma = '';
		foreach ($devs as $dev) {
			$developer .= $comma.$dev;
			$comma = ', ';
		}
		if (strpos($row->developer, '++')) $developer .= ' et al.';

		$years = '';
		if ($row->startyear != '0000') $years .= $row->startyear;
		if ($row->endyear != '0000') $years .= '-'.$row->endyear;

		$info = $row->platform;
		//$dot = '<span>&#9642;</span>';
		//if (!empty($row->cputime)) $info .= $dot;
		//$info .= $row->cputime;

		$cputime = str_replace('[SD]', '', $row->cputime);
		$cputime = str_replace('Approx ', '', $cputime);

		$rows .=
			'<tr>'.
				'<td class="thumbnail">'.
					'<a class="player-entry" href="#" data-id="'.$row->id.'" data-search="'.$search.'"><img src="'.$thumbnail.'" alt="'.$row->title.'" /></a>'.
				'</td>'.
				'<td class="info">'.
					'<a class="name player-entry" href="#" data-id="'.$row->id.'" data-search="'.$search.'">'.$row->title.'</a><br />'.
					trim($years.$developer).
					'<br /><span class="player-line" style="margin-right:0;">'.$info.'</span>'.
					(!empty($cputime) ? '<span class="player-line player-right">'.$cputime.'</span>' : '').
				'</td>'.
			'</tr>';
	}

	// Build the sticky header HTML for the '#sticky' DIV
	$sticky = '<h2 style="display:inline-block;margin-top:0;">Players / Editors</h2>';

	// Now build the HTML
	$html = '<p style="margin-top:0;">This is a list of all the players/editors in the database. If you click to see
			a page, the SID browser will automatically find all tunes related to it. Most CPU time measures you
			see here were made with <a href="http://csdb.chordian.net/?type=release&id=152422">SIDDump</a> and are approximate.</p>'.
		//'<h3>'.$select->rowCount().' entries found</h3>'.
		'<table class="releases">'.
			$rows.
		'</table>';

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html));
?>