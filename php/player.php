<?php
/**
 * DeepSID
 *
 * Build the HTML page for the 'Players' tab.
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
		$title = $row->title == $_GET['player'] ? $row->title : '<span style="color:#a1a294;">'.$_GET['player'].'</span><img class="arrow" src="images/composer_arrowright.svg" alt="" style="position:relative;top:0;margin:0 12px;" />'.$row->title ;

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

		// Use 'id' to figure out the name of the thumbnails (if they exist)
		$thumbnails = '';
		foreach (glob('../images/players/'.$id.'_*.png') as $filename) {
			$thumbnails .= '<img class="thumbnail-player" src="'.substr($filename, 3).'" alt="" />';
		}

		$cputime = str_replace('[SD]', '<sup><a href="http://csdb.dk/release/?id=152422" title="Measured with SIDDump">SD</a></sup>', $row->cputime);

		$download = '';
		$label = '<span style="float:right;margin-right:2px;"><b style="margin-right:7px;">Download:</b>';
		if (!empty($row->site))
			$download .= $label.'<a href="'.$row->site.'">Site</a>';
		if ($row->csdbid)
			$download .= (!empty($download) ? '<span style="margin:0 6px;color:#8a8c7a;font-size:10px;">&#9642;</span>' : $label).'<a href="https://csdb.dk/release/?id='.$row->csdbid.'">CSDb</a>';
		if (!empty($download))
			$download .= '</span>';

		// Now build the HTML
		$html = '<h2 style="display:inline-block;margin:0;">'.$title.'</h2><br />'.
		'<p style="position:relative;top:-9px;left:1px;font-size:13px;margin-bottom:-5px;">'.trim($years.$developer).$download.'</p>'.
		'<p style="margin-bottom:12px;">'.$row->description.'</p>'.

		'<table style="border:none;">
			<tr>
				<td style="width:384px;padding:0;border-right:none;vertical-align:top;">'.
					$thumbnails.
				'</td>
				<td style="padding:0 0 0 9px;vertical-align:top;border:none;">
					<table class="playerinfo">'.

						(!empty($row->platform) ||
						 !empty($row->distribution) ||
						 !empty($row->encoding) ||
						 !empty($row->sourcecode) ||
						 !empty($row->docs) ||
						 !empty($row->exampletunes) ||
						 !empty($row->fileformat)
						? '<tr><td class="corner" colspan="2" style="background:#f8f1f1;"><img class="svg" src="images/players_package.svg" style="position:relative;top:1px;" alt="" /><span>Package</span></tr>' : '').

						(!empty($row->platform) ? '<tr><td>Platform</td><td>'.$row->platform.'</td></tr>' : '').
						(!empty($row->distribution) ? '<tr><td>Distribution</td><td>'.$row->distribution.'</td></tr>' : '').
						(!empty($row->encoding) ? '<tr><td>PAL / NTSC</td><td>'.$row->encoding.'</td></tr>' : '').
						(!empty($row->sourcecode) ? '<tr><td>Source code</td><td>'.$row->sourcecode.'</td></tr>' : '').
						(!empty($row->docs) ? '<tr><td>Documentation</td><td>'.$row->docs.'</td></tr>' : '').
						(!empty($row->exampletunes) ? '<tr><td>Example tunes</td><td>'.$row->exampletunes.'</td></tr>' : '').
						(!empty($row->fileformat) ? '<tr><td>Proprietary file format</td><td>'.$row->fileformat.'</td></tr>' : '').

						(!empty($row->sidchipcount) ||
						 !empty($row->channelsvisible) ||
						 !empty($row->speeds) ||
						 !empty($row->digi) ||
						 !empty($row->auxsupport) ||
						 !empty($row->importfrom) ||
						 !empty($row->saveto) ||
						 !empty($row->packer) ||
						 !empty($row->relocator) ||
						 !empty($row->loadsavesnd) ||
						 !empty($row->instruments) ||
						 !empty($row->subtunes)
						? '<tr><td class="corner" colspan="2" style="background:#fafaee;"><img class="svg" src="images/players_features.svg" style="position:relative;top:1px;" alt="" /><span>Features</span></td></tr>' : '').

						(!empty($row->sidchipcount) ? '<tr><td>Number of SID chips</td><td>'.$row->sidchipcount.'</td></tr>' : '').
						(!empty($row->channelsvisible) ? '<tr><td>Channels visible</td><td>'.$row->channelsvisible.'</td></tr>' : '').
						(!empty($row->speeds) ? '<tr><td>Speeds</td><td>'.$row->speeds.'</td></tr>' : '').
						(!empty($row->digi) ? '<tr><td>Digi / Samples</td><td>'.$row->digi.'</td></tr>' : '').
						(!empty($row->auxsupport) ? '<tr><td>Auxiliary support</td><td>'.$row->auxsupport.'</td></tr>' : '').
						(!empty($row->importfrom) ? '<tr><td>Import from</td><td>'.$row->importfrom.'</td></tr>' : '').
						(!empty($row->saveto) ? '<tr><td>Save/Export to</td><td>'.$row->saveto.'</td></tr>' : '').
						(!empty($row->packer) ? '<tr><td>Packer</td><td>'.$row->packer.'</td></tr>' : '').
						(!empty($row->relocator) ? '<tr><td>Relocator</td><td>'.$row->relocator.'</td></tr>' : '').
						(!empty($row->loadsavesnd) ? '<tr><td>Load/Save sounds</td><td>'.$row->loadsavesnd.'</td></tr>' : '').
						(!empty($row->instruments) ? '<tr><td>Instruments / Sounds</td><td>'.$row->instruments.'</td></tr>' : '').
						(!empty($row->subtunes) ? '<tr><td>Sub tunes</td><td>'.$row->subtunes.'</td></tr>' : '').

						(!empty($row->noteworthy) ||
						 !empty($row->playersize) ||
						 !empty($row->zeropages) ||
						 !empty($row->cputime) ||
						 !empty($row->arpeggio) ||
						 !empty($row->pulsating) ||
						 !empty($row->filtering) ||
						 !empty($row->vibrato) ||
						 !empty($row->hardrestart)
						? '<tr><td class="corner" colspan="2" style="background:#f1f1f8;"><img class="svg" src="images/players_player.svg" style="position:relative;top:1px;" alt="" /><span>Player</span></td></tr>' : '').

						(!empty($row->noteworthy) ? '<tr><td>Noteworthy</td><td>'.$row->noteworthy.'</td></tr>' : '').
						(!empty($row->playersize) ? '<tr><td>Size of player</td><td>'.$row->playersize.'</td></tr>' : '').
						(!empty($row->zeropages) ? '<tr><td>Zero page usage</td><td>'.$row->zeropages.'</td></tr>' : '').
						(!empty($row->cputime) ? '<tr><td>CPU time (1x)</td><td>'.$cputime.'</td></tr>' : '').
						(!empty($row->arpeggio) ? '<tr><td>Arpeggio</td><td>'.$row->arpeggio.'</td></tr>' : '').
						(!empty($row->pulsating) ? '<tr><td>Pulsating</td><td>'.$row->pulsating.'</td></tr>' : '').
						(!empty($row->filtering) ? '<tr><td>Filtering</td><td>'.$row->filtering.'</td></tr>' : '').
						(!empty($row->vibrato) ? '<tr><td>Vibrato</td><td>'.$row->vibrato.'</td></tr>' : '').
						(!empty($row->hardrestart) ? '<tr><td>Hard restart</td><td>'.$row->hardrestart.'</td></tr>' : '').

						(!empty($row->tracksystem) ||
						 !empty($row->patterns) ||
						 !empty($row->followplay) ||
						 !empty($row->copypaste) ||
						 !empty($row->undoing) ||
						 !empty($row->trackcmds) ||
						 !empty($row->noteinput)
						? '<tr><td class="corner" colspan="2" style="background:#f2f8f2;"><img class="svg" src="images/players_editor.svg" style="position:relative;top:1px;" alt="" /><span>Editor</span></td></tr>' : '').

						(!empty($row->tracksystem) ? '<tr><td>Track system</td><td>'.$row->tracksystem.'</td></tr>' : '').
						(!empty($row->patterns) ? '<tr><td>Patterns / Sequences</td><td>'.$row->patterns.'</td></tr>' : '').
						(!empty($row->followplay) ? '<tr><td>Follow-play</td><td>'.$row->followplay.'</td></tr>' : '').
						(!empty($row->copypaste) ? '<tr><td>Copy and Paste</td><td>'.$row->copypaste.'</td></tr>' : '').
						(!empty($row->undoing) ? '<tr><td>Undo</td><td>'.$row->undoing.'</td></tr>' : '').
						(!empty($row->trackcmds) ? '<tr><td>Track commands</td><td>'.$row->trackcmds.'</td></tr>' : '').
						(!empty($row->noteinput) ? '<tr><td>Note input layout</td><td>'.$row->noteinput.'</td></tr>' : '').
					'</table>
				</td>
			</tr>
		</table>';
	}

} catch(PDOException $e) {
	$account->LogActivityError('player.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'html' => $html));
?>