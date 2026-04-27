<?php
/**
 * DeepSID
 *
 * Build the HTML page for the 'Players' tab.
 * 
 * @uses		$_GET['player']				e.g. "GoatTracker v2.x"
 * 
 * 	- OR -
 * 
 * @uses		$_GET['id']
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['player']) && !isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'player\' or \'id\' as a GET variable.')));

try {
	$db = $account->getDB();

	// If a player string was specified then first look it up in the many-to-one table
	if (isset($_GET['player'])) {
		$select = $db->prepare('SELECT player_id FROM players_lookup WHERE player = :player LIMIT 1');
		$select->execute(array(':player'=>$_GET['player']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount()) {
			$id = $select->fetch()->player_id;
		} else {
			// Not defined (yet)
			$sticky = '<h2 style="display:inline-block;margin-top:0;">Players / Editors</h2>';
			die(json_encode(array('status' => 'warning', 'info' => false, 'sticky' => $sticky, 'html' => '<p style="margin-top:0;"><i>No information available.</i></p>')));
		}
	} else
		$id = $_GET['id'];

	// Get all information available
	$select = $db->prepare('SELECT * FROM players_info WHERE id = :playerid LIMIT 1');
	$select->execute(array(':playerid'=>$id));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>The information for this player was conspicuously missing.</i></p>')));
	else {
		$row = $select->fetch();

		$title = $row->title;

		$devs = explode('|', str_replace('++', '', $row->developer));
		$developer = ' by ';
		$comma = '';
		foreach ($devs as $dev) {
			$developer .= $comma.$dev;
			$comma = ', ';
		}
		if (strpos($row->developer, '++')) $developer .= ' et al.';
		
		$years = '';
		if ($row->start_year != '0000') $years .= $row->start_year;
		if ($row->end_year != '0000') $years .= '-'.$row->end_year;

		// Use 'id' to figure out the name of the thumbnails (if they exist)
		$thumbnails = '';
		foreach (glob('../images/players/'.$id.'_*.png') as $filename) {
			$thumbnails .= '<img class="thumbnail-player zoom-up" src="'.substr($filename, 3).'" data-src="'.substr($filename, 3).'" alt="" />';
		}

		$cpu_time = str_replace('[SD]', '<sup><a href="http://csdb.chordian.net/?type=release&id=152422" title="Measured with SIDDump">SD</a></sup>', $row->cpu_time);

		$download = '';
		$label = '<span style="float:right;margin-right:2px;"><b style="margin-right:7px;">Download:</b>';
		if (!empty($row->site))
			$download .= $label.'<a href="'.$row->site.'">Site</a>';
		if ($row->csdb_id)
			$download .= (!empty($download) ? '<span class="download-dot">&#9642;</span>' : $label).'<a href="http://csdb.chordian.net/?type=release&id='.$row->csdb_id.'">CSDb</a>';
		if (!empty($download))
			$download .= '</span>';

		$search = empty($row->search) ? strtolower($row->title) : $row->search;
		$svg_permalink = '<svg class="permalink" style="enable-background:new 0 0 80 80;" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg>';

		// Build the sticky header HTML for the '#sticky' DIV
		// NOTE: No ellipsis handling here because I am in control of all the titles.
		$sticky = '<h2 style="display:inline-block;margin:0;" title="'.$title.'">'.$title.'</h2>'.
			'<button id="go-back-player">Back</button>'.
			'<a class="clipboard" href="//deepsid.chordian.net?player='.$id.'&type=player&search='.str_replace(' ', '_', $search).'" title="Permalink">'.$svg_permalink.'</a>'.
			'<p style="position:relative;top:-9px;left:1px;font-size:13px;margin-bottom:-5px;">'.trim($years.$developer).$download.'</p>';

		// Now build the HTML
		$html = '<p style="margin-top:0;margin-bottom:12px;">'.$row->description.'</p>'.

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
						 !empty($row->source_code) ||
						 !empty($row->docs) ||
						 !empty($row->example_tunes) ||
						 !empty($row->file_format)
						? '<tr><td class="corner package" colspan="2"><img class="svg" src="images/players_package.svg" style="position:relative;top:1px;" alt="" /><span>Package</span></tr>' : '').

						(!empty($row->platform) ? '<tr><td>Platform</td><td>'.$row->platform.'</td></tr>' : '').
						(!empty($row->distribution) ? '<tr><td>Distribution</td><td>'.$row->distribution.'</td></tr>' : '').
						(!empty($row->encoding) ? '<tr><td>PAL / NTSC</td><td>'.$row->encoding.'</td></tr>' : '').
						(!empty($row->source_code) ? '<tr><td>Source code</td><td>'.$row->source_code.'</td></tr>' : '').
						(!empty($row->docs) ? '<tr><td>Documentation</td><td>'.$row->docs.'</td></tr>' : '').
						(!empty($row->example_tunes) ? '<tr><td>Example tunes</td><td>'.$row->example_tunes.'</td></tr>' : '').
						(!empty($row->file_format) ? '<tr><td>Proprietary file format</td><td>'.$row->file_format.'</td></tr>' : '').

						(!empty($row->sid_chip_count) ||
						 !empty($row->channels_visible) ||
						 !empty($row->speeds) ||
						 !empty($row->digi) ||
						 !empty($row->aux_support) ||
						 !empty($row->import_from) ||
						 !empty($row->save_to) ||
						 !empty($row->packer) ||
						 !empty($row->relocator) ||
						 !empty($row->load_save_snd) ||
						 !empty($row->instruments) ||
						 !empty($row->subtunes)
						? '<tr><td class="corner features" colspan="2"><img class="svg" src="images/players_features.svg" style="position:relative;top:1px;" alt="" /><span>Features</span></td></tr>' : '').

						(!empty($row->sid_chip_count) ? '<tr><td>Number of SID chips</td><td>'.$row->sid_chip_count.'</td></tr>' : '').
						(!empty($row->channels_visible) ? '<tr><td>Channels visible</td><td>'.$row->channels_visible.'</td></tr>' : '').
						(!empty($row->speeds) ? '<tr><td>Speeds</td><td>'.$row->speeds.'</td></tr>' : '').
						(!empty($row->digi) ? '<tr><td>Digi / Samples</td><td>'.$row->digi.'</td></tr>' : '').
						(!empty($row->aux_support) ? '<tr><td>Auxiliary support</td><td>'.$row->aux_support.'</td></tr>' : '').
						(!empty($row->import_from) ? '<tr><td>Import from</td><td>'.$row->import_from.'</td></tr>' : '').
						(!empty($row->save_to) ? '<tr><td>Save/Export to</td><td>'.$row->save_to.'</td></tr>' : '').
						(!empty($row->packer) ? '<tr><td>Packer</td><td>'.$row->packer.'</td></tr>' : '').
						(!empty($row->relocator) ? '<tr><td>Relocator</td><td>'.$row->relocator.'</td></tr>' : '').
						(!empty($row->load_save_snd) ? '<tr><td>Load/Save sounds</td><td>'.$row->load_save_snd.'</td></tr>' : '').
						(!empty($row->instruments) ? '<tr><td>Instruments / Sounds</td><td>'.$row->instruments.'</td></tr>' : '').
						(!empty($row->subtunes) ? '<tr><td>Sub tunes</td><td>'.$row->subtunes.'</td></tr>' : '').

						(!empty($row->noteworthy) ||
						 !empty($row->player_size) ||
						 !empty($row->zero_pages) ||
						 !empty($row->cpu_time) ||
						 !empty($row->arpeggio) ||
						 !empty($row->pulsating) ||
						 !empty($row->filtering) ||
						 !empty($row->vibrato) ||
						 !empty($row->hard_restart)
						? '<tr><td class="corner player" colspan="2"><img class="svg" src="images/players_player.svg" style="position:relative;top:1px;" alt="" /><span>Player</span></td></tr>' : '').

						(!empty($row->noteworthy) ? '<tr><td>Noteworthy</td><td>'.$row->noteworthy.'</td></tr>' : '').
						(!empty($row->player_size) ? '<tr><td>Size of player</td><td>'.$row->player_size.'</td></tr>' : '').
						(!empty($row->zero_pages) ? '<tr><td>Zero page usage</td><td>'.$row->zero_pages.'</td></tr>' : '').
						(!empty($row->cpu_time) ? '<tr><td>CPU time (1x)</td><td>'.$cpu_time.'</td></tr>' : '').
						(!empty($row->arpeggio) ? '<tr><td>Arpeggio</td><td>'.$row->arpeggio.'</td></tr>' : '').
						(!empty($row->pulsating) ? '<tr><td>Pulsating</td><td>'.$row->pulsating.'</td></tr>' : '').
						(!empty($row->filtering) ? '<tr><td>Filtering</td><td>'.$row->filtering.'</td></tr>' : '').
						(!empty($row->vibrato) ? '<tr><td>Vibrato</td><td>'.$row->vibrato.'</td></tr>' : '').
						(!empty($row->hard_restart) ? '<tr><td>Hard restart</td><td>'.$row->hard_restart.'</td></tr>' : '').

						(!empty($row->track_system) ||
						 !empty($row->patterns) ||
						 !empty($row->follow_play) ||
						 !empty($row->copy_paste) ||
						 !empty($row->undoing) ||
						 !empty($row->track_cmds) ||
						 !empty($row->note_input)
						? '<tr><td class="corner editor" colspan="2"><img class="svg icon-editor" src="images/players_editor.svg" style="position:relative;top:1px;" alt="" /><span>Editor</span></td></tr>' : '').

						(!empty($row->track_system) ? '<tr><td>Track system</td><td>'.$row->track_system.'</td></tr>' : '').
						(!empty($row->patterns) ? '<tr><td>Patterns / Sequences</td><td>'.$row->patterns.'</td></tr>' : '').
						(!empty($row->follow_play) ? '<tr><td>Follow-play</td><td>'.$row->follow_play.'</td></tr>' : '').
						(!empty($row->copy_paste) ? '<tr><td>Copy and Paste</td><td>'.$row->copy_paste.'</td></tr>' : '').
						(!empty($row->undoing) ? '<tr><td>Undo</td><td>'.$row->undoing.'</td></tr>' : '').
						(!empty($row->track_cmds) ? '<tr><td>Track commands</td><td>'.$row->track_cmds.'</td></tr>' : '').
						(!empty($row->note_input) ? '<tr><td>Note input layout</td><td>'.$row->note_input.'</td></tr>' : '').
					'</table>
				</td>
			</tr>
		</table>';
	}

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'info' => true, 'sticky' => $sticky, 'html' => $html));
?>