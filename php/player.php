<?php
/**
 * DeepSID
 *
 * Build the HTML page for the 'Players' tab. Use the 'CSDb' script.
 * 
 * @uses		$_GET['player'] - e.g. "GoatTracker v2.x"
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['player']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'player\' as a GET variable.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First look it up in the many-to-one table
	$select = $db->prepare('SELECT playerid FROM players_lookup WHERE player = :player LIMIT 1');
	$select->execute(array(':player'=>$_GET['player']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount())
		$id = $select->fetch()->playerid;
	else
		// Not defined (yet)
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>No editor/player information available.</i></p>')));

	// Get all information available
	$select = $db->prepare('SELECT * FROM players_info WHERE id = :playerid LIMIT 1');
	$select->execute(array(':playerid'=>$id));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>The information for this player was conspicuously missing.</i></p>')));
	else {
		$row = $select->fetch();

		// Title must "arrow" to editor name if different
		$title = $row->title == $_GET['player'] ? $row->title : $_GET['player'].'<img class="arrow" src="images/composer_arrowright.svg" alt="" style="position:relative;top:1px;margin:0 12px;" />'.$row->title ;

		$developer = '<b>'.str_replace('++', '', $row->developer).'</b>';
		if (strpos($row->developer, '++')) $developer .= ' et al.';

		$years = $row->startyear != '0000' || $row->endyear != '0000' ? ', ' : '';
		if ($row->startyear != '0000') $years .= $row->startyear;
		if ($row->endyear != '0000') $years .= '-'.$row->endyear;

		// Use 'id' to figure out the name of the thumbnails (if they exist)
		$thumbnails = '';
		foreach (glob('../images/players/'.$id.'_*.png') as $filename) {
			$thumbnails .= '<img class="thumbnail" src="'.substr($filename, 3).'" alt="" />';
		}

		$cputime = str_replace('[SD]', '<sup><a href="http://csdb.dk/release/?id=152422" title="Measured with SIDDump">SD</a></sup>', $row->cputime);

		// Now build the HTML
		$html = '<h2 style="display:inline-block;margin:0;">'.$title.'</h2><br />'.
		'<p style="position:relative;top:-9px;left:1px;font-size:13px;">Developed by '.$developer.$years.'.</p>'.
		$thumbnails.
		'<p>'.$row->description.'</p>'.
		'<table class="playerinfo">'.
			(!empty($row->platform) ? '<tr><td>Platform</td><td>'.$row->platform.'</td></tr>' : '').
			(!empty($row->encoding) ? '<tr><td>PAL / NTSC</td><td>'.$row->encoding.'</td></tr>' : '').
			(!empty($row->sourcecode) ? '<tr><td>Source code</td><td>'.$row->sourcecode.'</td></tr>' : '').
			(!empty($row->docs) ? '<tr><td>Documentation</td><td>'.$row->docs.'</td></tr>' : '').
			(!empty($row->speeds) ? '<tr><td>Speeds</td><td>'.$row->speeds.'</td></tr>' : '').
			(!empty($row->digi) ? '<tr><td>Digi / Samples</td><td>'.$row->digi.'</td></tr>' : '').
			(!empty($row->packer) ? '<tr><td>Packer</td><td>'.$row->packer.'</td></tr>' : '').
			(!empty($row->relocator) ? '<tr><td>Relocator</td><td>'.$row->relocator.'</td></tr>' : '').
			(!empty($row->instruments) ? '<tr><td>Instruments / Sounds</td><td>'.$row->instruments.'</td></tr>' : '').
			(!empty($row->subtunes) ? '<tr><td>Sub tunes</td><td>'.$row->subtunes.'</td></tr>' : '').
			(!empty($row->playersize) ? '<tr><td>Size of player</td><td>'.$row->playersize.'</td></tr>' : '').
			(!empty($row->zeropages) ? '<tr><td>Zero page usage</td><td>'.$row->zeropages.'</td></tr>' : '').
			(!empty($row->cputime) ? '<tr><td>CPU time (1x)</td><td>'.$cputime.'</td></tr>' : '').
			(!empty($row->tracksystem) ? '<tr><td>Track system</td><td>'.$row->tracksystem.'</td></tr>' : '').
			(!empty($row->patterns) ? '<tr><td>Patterns / Sequences</td><td>'.$row->patterns.'</td></tr>' : '').
		'</table>';
	}

} catch(PDOException $e) {
	$account->LogActivityError('player.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'html' => $html));
?>