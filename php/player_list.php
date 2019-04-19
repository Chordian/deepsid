<?php
/**
 * DeepSID
 *
 * Build the HTML page for listing all players/editors in the 'Players' tab.
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

	$select = $db->query('SELECT id, title, developer, startyear, endyear FROM players_info ORDER BY title');
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount()) {
		$account->LogActivityError('player_list.php', 'No entries returned');
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find the information in the database.")));
	}

	$rows = '';

	foreach ($select as $row) {

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

		$rows .=
			'<tr>'.
				'<td class="thumbnail">'.
					'<a class="player-entry" href="#" data-id="'.$row->id.'"><img src="'.$thumbnail.'" alt="'.$row->title.'" /></a>'.
				'</td>'.
				'<td class="info">'.
					'<a class="name player-entry" href="#" data-id="'.$row->id.'">'.$row->title.'</a><br />'.
					trim($years.$developer).
				'</td>'.
			'</tr>';
	}

	$html = '<h2 style="display:inline-block;margin-top:0;">Players / Editors</h2>'.
		'<p>About...</p>'.
		//'<h3>'.$select->rowCount().' entries found</h3>'.
		'<table class="releases">'.
			$rows.
		'</table>';

} catch(PDOException $e) {
	$account->LogActivityError('player_list.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'html' => $html));
?>