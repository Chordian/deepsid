
<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to GameBase64 entries (the links are all
 * contained in the database) or a specific entry sub page.
 * 
 * @uses		$_GET['fullname']			for a page with links to sub pages
 * @uses		$_GET['fileid']				file id of the song
 * @uses		$_GET['noprimary']			Set to 1 to override primary release
 * 
 * 	- OR -
 * 
 * @uses		$_GET['id']					for a sub page with a specific entry
 * @uses		$_GET['fileid']				file id of the song
 * @uses		$_GET['noprimary']			Set to 1 to override primary release
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup
require_once("gb64_functions.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$primary_corner = '';
$primary_back_button = false;
$primary_csdb_needed = false;
$user_id = $account->checkLogin() ? $account->userID() : 0;

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Get the site type and site ID from the labels, if this exists for the
 * specified collection file ID.
 * 
 * @param		int			$id					the file ID of the SID file
 * 
 * @return		array							type and ID, or NULL if not found
 */
function getLabelTypeId($id) { // @todo Same function as in 'csdb.php'
	global $db;

	$labels = $db->query(
		'SELECT li.site, li.site_id
		 FROM labels_lookup ll
		 INNER JOIN labels_info li ON li.id = ll.labels_id
		 WHERE ll.files_id = '.$id.' LIMIT 1'
	);

	$row = $labels->fetch(PDO::FETCH_ASSOC);

	if (!$row)
		return null;

	return [
		'type' => strtolower($row['site']),
		'id'   => $row['site_id']
	];
}

// --------------------------------------------------------------------------
// START
// --------------------------------------------------------------------------

try {

	// Connect to DeepSID database
	$db = $account->getDB();

	// Connect to imported GameBase64 database
	$gb = new PDO(
		'mysql:host='.$config['db_gb64_host'].';dbname='.$config['db_gb64_name'],
		$config['db_gb64_user'],
		$config['db_gb64_pwd']);
	$gb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$gb->exec("SET NAMES UTF8");

	$footnote = '<i><small>Generated using <a href="https://gb64.com/downloads.php" target="_blank">GameBase64</a> database v19</small></i>';

	if (isset($_GET['fullname'])) {

		// Get rid of the HVSC folder in the beginning
		$sid_filename = substr($_GET['fullname'], strpos($_GET['fullname'], '/') + 1);
		$sid_filename = str_replace('/', '\\', $sid_filename);

		// What games are using this SID file?
		$select = $gb->prepare('SELECT GA_Id FROM Games WHERE SidFilename = :collection_path');
		$select->execute(array(':collection_path' => $sid_filename));
		$select->setFetchMode(PDO::FETCH_OBJ);

		// Collect the GB64 ID numbers (if any)
		$gb_ids = array();
		if ($select->rowCount()) {
			foreach ($select as $row) {
				$gb_ids[] = $row->GA_Id;
			}
		} else {
			$sticky = '<h2 style="display:inline-block;margin-top:0;">GameBase64</h2>';
			$label = getLabelTypeId($_GET['fileid']);
			if ($label && $label['type'] == 'csdb') {
				// Placeholder for CSDb primary preview
				$primary_corner = '<div id="primary-corner-gb64"><img class="loading-pp" src="images/loading_threedots.svg" alt="" /></div>';
				$primary_csdb_needed = true;
			}
			die(json_encode(array(
				'status'	=> 'warning',
				'sticky'	=> $sticky,
				'html'		=> $primary_corner.'<h3>0 entries found</h3><div style="border-top:1px solid var(--color-border-csdb);">'.$footnote.'</div>',
				'needcsdb'	=> $primary_csdb_needed,
				'primary'	=> $primary_back_button
			)));
		}

		// Is a CSDb entry the primary release for this SID file?
		$label = getLabelTypeId($_GET['fileid']);
		$is_csdb_primary = $label && $label['type'] == 'csdb';

		// If only one result then just show that as a sub page. An exception is if a CSDb entry is the primary
		// release; then we know the one GB64 game is not and we no longer care enough about it to show its page.
		// Instead, this opens up for showing a CSDb primary preview.
		$page_id = count($gb_ids) == 1 && !$is_csdb_primary ? $gb_ids[0] : 0;

	} else if (isset($_GET['id'])) {

		// A specific sub page ID was specified
		$page_id = $_GET['id'];
		$gb_ids = array(1);

	} else
		die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

// --------------------------------------------------------------------------
// PRIMARY RELEASE
// --------------------------------------------------------------------------

// Any SID can have a label (which shows a primary release). If this is present (and the user has activated the
// corresponding feature in the settings) a specific sub page for that label should be shown instead. If the user
// did not activate the feature, at least show which release in a game list is the primary one.

$primary_id = 0;
if ($page_id == 0 && !$_GET['noprimary']) {

	$label = getLabelTypeId($_GET['fileid']);
	if ($label && $label['type'] == 'gb64')
		$primary_id = $label['id'];

	// Get the user's settings
	$users = $db->query('SELECT flags FROM users WHERE id = '.$user_id)->fetch(PDO::FETCH_OBJ);
	$settings = unserialize($users->flags);

	// Does the user want to see the primary release?
	if ($primary_id && $settings['primaryrelease']) {
		$primary_back_button = true;
		$page_id = $primary_id;
	}
}

// --------------------------------------------------------------------------
// BUILD HTML
// --------------------------------------------------------------------------

if ($page_id) {

	// --------------------------------------------------------------------------
	// SUB PAGE ONLY
	// --------------------------------------------------------------------------
	
	$data = readGB64DB($page_id);

	$published		= '<p style="margin-top:-2px;"><b>Published:</b><br />'.$data['year'].', '.$data['company'].'</p>';
	$musician		= (!empty($data['musician']) ? '<p><b>Music:</b><br />'.$data['musician'].'</p>' : '');
	$graphics		= (!empty($data['graphics']) ? '<p><b>Graphics:</b><br />'.$data['graphics'].'</p>' : '');
	$programmer		= (!empty($data['programmer']) ? '<p><b>Programming:</b><br />'.$data['programmer'].'</p>' : '');
	$language		= (!empty($data['language']) ? '<p><b>Language:</b><br />'.$data['language'].'</p>' : '');
	$genre			= (!empty($data['genre']) ? '<p><b>Genre:</b><br />'.$data['genre'].'</p>' : '');
	$clone			= (!empty($data['clone']) ? '<p><b>Clone of:</b><br />'.$data['clone'].'</p>' : '');
	$pcontrol		= (!empty($data['pcontrol']) ? '<p><b>Primary control:</b><br />'.$data['pcontrol'].'</p>' : '');
	$players		= (!empty($data['players']) ? '<p><b>Players:</b><br />'.$data['players'].'</p>' : '');
	$comments		= (!empty($data['comments']) ? '<p><b>Comments:</b><br />'.$data['comments'].'</p>' : '');

	$col_of_thumbnails = '';
	foreach($data['thumbnails'] as $thumbnail)
		$col_of_thumbnails .= '<a href="#" class="zoom-up" data-src="images/gb64'.$thumbnail.'"><img class="thumbnail-gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /></a> ';

	// If this is a primary release then prepare the arrow-and-bow icon
	$primary_bow_icon = '';
	if (isset($_GET['fileid'])) {
		$label = getLabelTypeId($_GET['fileid']);
		if ($label && $label['type'] == 'gb64' && $label['id'] == $page_id)
			$primary_bow_icon = '<div class="primary-bow-tail"></div>';
	}

	// Build the sticky header HTML for the '#sticky' DIV
	$sticky = '<h2 class="ellipsis" style="display:inline-block;margin:0 0 -8px 0;max-width:720px;" title="'.$data['title'].'">'.$data['title'].'</h2>'.
		(isset($_GET['id']) || $primary_back_button ? '<button id="go-back-gb64">Back</button>' : '').
		$primary_bow_icon.
		'<div class="corner-icons">'.
			'<a href="https://gb64.com/game.php?id='.$page_id.'" title="See this at GameBase64" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
		'</div>';

	$data_type = explode(' - ', htmlspecialchars($data['genre'], ENT_QUOTES, 'UTF-8'))[0];

	// Now build the HTML
	$html = '<table style="border:none;">
		<tr>
			<td style="padding-left:0;vertical-align:top;border:none;">
				<h3 style="margin-top:6px;">Game info</h3>
			</td>
			<td style="padding-left:19px;vertical-align:top;border:none;">
				<h3 style="margin-top:6px;">Screenshots</h3>
			</td>
		</tr>
		<tr>
			<td style="padding:0;vertical-align:top;border:none;">'.
				$published.
				$musician.
				$graphics.
				$programmer.
				$language.
				$genre.
				$clone.
				$pcontrol.
				$players.
				$comments.
				// Info for controls outside of the page (e.g. on the context menu for a SID file)
				'<div id="gb64-info" style="display:none;"
					data-name="'.htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8').'"
					data-type="'.$data_type.'"
					data-siteid="'.$page_id.'"></div>
			</td>
			<td style="width:340px;padding:0;border:none;vertical-align:top;text-align:right;">'.
				$col_of_thumbnails.
			'</td>
		</tr>
	</table><div style="border-top:1px solid var(--color-border-csdb);">'.$footnote.'</div>';

} else {

	// --------------------------------------------------------------------------
	// LIST
	// --------------------------------------------------------------------------

	$rows = '';

	foreach($gb_ids as $id) {

		$data = readGB64DB($id);

		$thumbnails = array_slice($data['thumbnails'], 0, 3);	// Maximum 3 thumbnails

		$line_of_thumbnails = '';
		foreach($thumbnails as $thumbnail)
			$line_of_thumbnails .= '<a class="gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" target="_blank" data-id="'.$id.'"><img class="gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /></a>';

		$primary_title_icon = '';
		if (isset($_GET['fileid'])) {
			$label = getLabelTypeId($_GET['fileid']);
			if ($label && $label['type'] == 'gb64' && $label['id'] == $id) {

				// Icon for one entry in the GB64 list
				$primary_title_icon = '<div class="primary-title-icon"></div>'; 

				// Prepare primary preview in top right corner
				$data = readGB64DB($id);

				$pp_thumbnails = array_slice($data['thumbnails'], 0, 4);	// Maximum 4 thumbnails

				$pp_line_of_thumbnails = '';
				foreach($pp_thumbnails as $pp_thumbnail)
					$pp_line_of_thumbnails .= '<span style="padding:2.4px;"></span><a class="gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" target="_blank" data-id="'.$id.'"><img class="gb64" src="images/gb64'.$pp_thumbnail.'" alt="'.$pp_thumbnail.'" /></a>';

				// Primary preview for the GB64 release
				$primary_corner = '<table class="primary">'.
				'<tr>'.
					'<td class="thumbnail">'.
						$pp_line_of_thumbnails.
					'</td>'.
					'<td class="info">'.
						'<a class="name gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" data-id="'.$id.'" target="_blank">'.$data['title'].'</a><span class="primary-entry primary-gb64"></span><br />'.
						$data['year'].' '.$data['company'].'<br />'.
					'</td>'.
				'</tr></table>';

			} else if ($label && $label['type'] == 'csdb') {
				// Placeholder for CSDb primary preview
				$primary_corner = '<div id="primary-corner-gb64"><img class="loading-pp" src="images/loading_threedots.svg" alt="" /></div>';
				$primary_csdb_needed = true;
			}
		}

		$rows .=
			'<tr>'.
				'<td class="info">'.
					'<a class="name gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" target="_blank" data-id="'.$id.'">'.$data['title'].'</a>'.$primary_title_icon.'<br />'.
					$data['year'].' '.$data['company'].'<br />'.
					'<span class="language">'.$data['language'].'</span>'.
				'</td>'.
				'<td class="thumbnail thumbnail-list">'.
					$line_of_thumbnails.
				'</td>'.
			'</tr>';
	}

	// Build the sticky header HTML for the '#sticky' DIV
	$sticky = '<h2 style="display:inline-block;margin-top:0;">GameBase64</h2>';

	// And now build the HTML
	$html = $primary_corner.'<h3>'.count($gb_ids).' entr'.(count($gb_ids) > 1 ? 'ies' : 'y').' found</h3>'.
		'<table class="releases">'.
			$rows.
		'</table>'.$footnote;
}

// --------------------------------------------------------------------------
// FINAL OUTPUT
// --------------------------------------------------------------------------

echo json_encode(array(
	'status'	=> 'ok',
	'sticky'	=> $sticky,
	'html'		=> $html,
	'count' 	=> count($gb_ids),
	'needcsdb'	=> $primary_csdb_needed,
	'primary'	=> $primary_back_button
));
?>