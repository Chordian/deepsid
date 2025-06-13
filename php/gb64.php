<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to GameBase64 entries (the links are all
 * contained in the database) or a specific entry sub page.
 * 
 * @uses		$_GET['fullname']			for a page with links to sub pages
 * 
 * 	- OR -
 * 
 * @uses		$_GET['id']					for a sub page with a specific entry
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

/**
 * Return an array with information from the imported GameBase64 database.
 * 
 * REMOVE THIS LINE!
 * 
 * @global		object		$gb					database connection
 *
 * @param		int			$id					id of page entry
 *
 * @return		array							array with information
 */
function ReadGB64DB($id) {

	global $gb;

	// Get the general info for the game
	$select_games = $gb->prepare('SELECT * FROM Games WHERE GA_Id = :id LIMIT 1');
	$select_games->execute(array(':id'=>$id));
	$select_games->setFetchMode(PDO::FETCH_OBJ);
	$games = $select_games->fetch();

	// Get the title
	$title = $games->Name;

	// Get the year
	$select_years = $gb->query('SELECT Year FROM Years WHERE YE_Id = '.$games->YE_Id.' LIMIT 1');
	$select_years->setFetchMode(PDO::FETCH_OBJ);
	$year = $select_years->fetch()->Year;
	switch($year) {
		case 9991:
			$year = '????';
			break;
		case 9992:
			$year = '19??';
			break;
		case 9994:
			$year = '198?';
			break;
		case 9995:
			$year = '199?';
			break;
	}

	// Get the publisher
	$select_publishers = $gb->query('SELECT Publisher FROM Publishers WHERE PU_Id = '.$games->PU_Id.' LIMIT 1');
	$select_publishers->setFetchMode(PDO::FETCH_OBJ);
	$company = $select_publishers->fetch()->Publisher;

	// Get the musician
	$select_musicians = $gb->query('SELECT Musician FROM Musicians WHERE MU_Id = '.$games->MU_Id.' LIMIT 1');
	$select_musicians->setFetchMode(PDO::FETCH_OBJ);
	$musician = $select_musicians->fetch()->Musician;

	// Get the artist
	$select_artists = $gb->query('SELECT Artist FROM Artists WHERE AR_Id = '.$games->AR_Id.' LIMIT 1');
	$select_artists->setFetchMode(PDO::FETCH_OBJ);
	$graphics = $select_artists->fetch()->Artist;

	// Get the programmer
	$select_programmers = $gb->query('SELECT Programmer FROM Programmers WHERE PR_Id = '.$games->PR_Id.' LIMIT 1');
	$select_programmers->setFetchMode(PDO::FETCH_OBJ);
	$programmer = $select_programmers->fetch()->Programmer;

	// Get the language
	$select_languages = $gb->query('SELECT Language FROM Languages WHERE LA_Id = '.$games->LA_Id.' LIMIT 1');
	$select_languages->setFetchMode(PDO::FETCH_OBJ);
	$language = $select_languages->fetch()->Language;

	// Get the genre
	$select_genres = $gb->query('SELECT * FROM Genres WHERE GE_Id = '.$games->GE_Id.' LIMIT 1');
	$select_genres->setFetchMode(PDO::FETCH_OBJ);
	$genres = $select_genres->fetch();
	$genre = $genres->Genre;

		// Get the parent genre (prefix the genre)
		$select_pgenres = $gb->query('SELECT ParentGenre FROM PGenres WHERE PG_Id = '.$genres->PG_Id.' LIMIT 1');
		$select_pgenres->setFetchMode(PDO::FETCH_OBJ);
		$genre = $select_pgenres->fetch()->ParentGenre.' - '.$genre;

	$clone = ''; // Usually always empty - let's just forget about it for now

	// Get control method
	// @todo Where can I find information about the 'Control' values?
	$pcontrol = $games->Control == 0 ? 'Joystick Port 2' : '';

	// Get number of players
	// @todo Might be more values to find later?
	$pl_fr = $games->PlayersFrom;
	$pl_to = $games->PlayersTo;
	$players = '';
	if ($pl_fr == 1 && $pl_to == 1)
		$players = '1P Only';
	else if ($pl_fr == 1 && $pl_to == 2)
		$players = '1 - 2';

	// Get comment
	$comments = $games->Comment;

	// Get screenshot paths
	$webPathBase = '/images/gb64'; // Folder with GB64 screenshots
	$diskPathBase = $_SERVER['HTTP_HOST'] == LOCALHOST
		? '..'.$webPathBase
		: $_SERVER['DOCUMENT_ROOT'].'/deepsid'.$webPathBase;
	
	if (empty($games->ScrnshotFilename)) {
		// There are no screenshots at all
		$thumbnails = array('/noscreenshot.gif');
	} else {
		// Extract the directory and filename (without extension)
		$relativePath = str_replace('\\', '/', $games->ScrnshotFilename);
		
		$dirname = pathinfo($relativePath, PATHINFO_DIRNAME); // "S"
		$filename = pathinfo($relativePath, PATHINFO_FILENAME); // "Sanxion"
		$extension = pathinfo($relativePath, PATHINFO_EXTENSION); // "png"

		// Build the glob pattern for e.g. "S/Sanxion.png" and its variants with "_1", "_2", etc.
		$pattern = $diskPathBase . '/' . $dirname . '/' . $filename . '{,_?}.' . $extension;

		// Some filenames include e.g. "[Preview]" - those brackets need to be escaped
		$pattern = str_replace('[', '\[', $pattern);
		$pattern = str_replace(']', '\]', $pattern);

		// Find all matching files
		$matches = glob($pattern, GLOB_BRACE);

		if (!empty($matches)) {
			// Convert full paths to web paths (strip 'images/gb64/')
			if ($_SERVER['HTTP_HOST'] == LOCALHOST) {
				$thumbnails = array_map(function($path) use ($diskPathBase) {
					return '/' . substr($path, strlen($diskPathBase));
				}, $matches);
			} else {
		        $thumbnails = array_map(function($path) use ($diskPathBase) {
		            // Convert absolute path to web path
        		    return str_replace($diskPathBase, '', $path);
        		}, $matches);
			}
		} else {
			$thumbnails = array('/noscreenshot.gif');
		}
	}
	$thumbnails = array_reverse($thumbnails);		// Want the title screen to be first in line

	return array(
		'title'			=> $title,
		'year' 			=> $year,
		'company'		=> $company,
		'musician'		=> $musician,
		'graphics'		=> $graphics,
		'programmer'	=> $programmer,
		'language'		=> $language,
		'genre'			=> $genre,
		'clone'			=> $clone,
		'pcontrol'		=> $pcontrol,
		'players'		=> $players,
		'comments'		=> $comments,
		'thumbnails'	=> $thumbnails,
	);
}

/***** START *****/

try {

	// Connect to DeepSID database
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Connect to imported GameBase64 database
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$gb = new PDO(PDO_GB_LOCAL, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$gb = new PDO(PDO_GB_ONLINE, USER_GB_ONLINE, PWD_GB_ONLINE);
	$gb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$gb->exec("SET NAMES UTF8");

	if (isset($_GET['fullname'])) {

		// Get rid of the HVSC folder in the beginning
		$sidFilename = substr($_GET['fullname'], strpos($_GET['fullname'], '/') + 1);
		$sidFilename = str_replace('/', '\\', $sidFilename);

		// What games are using this SID file?
		$select = $gb->prepare('SELECT GA_Id FROM Games WHERE SidFilename = :fullname');
		$select->execute(array(':fullname'=>$sidFilename));
		$select->setFetchMode(PDO::FETCH_OBJ);

		// Collect the GB64 ID numbers (if any)
		$gbIds = array();
		if ($select->rowCount()) {
			foreach ($select as $row) {
				$gbIds[] = $GbRows = $row->GA_Id;
			}
		} else {
			die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>No GameBase64 entries available.</i></p>')));
		}

		// If only one result then just show that as a sub page
		$page_id = count($gbIds) == 1 ? $gbIds[0] : 0;

	} else if (isset($_GET['id'])) {

		// A specific sub page ID was specified
		$page_id = $_GET['id'];
		$gbIds = array(1);

	} else
		die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

} catch(PDOException $e) {
	$account->LogActivityError('gb64.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

if ($page_id) {

	/***** SUB PAGE *****/
	$data = ReadGB64DB($page_id);

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
		$col_of_thumbnails .= '<img class="thumbnail-gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /> ';

	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-bottom:20px;">'.$data['title'].'</h2>'.
	(isset($_GET['id']) ? '<button id="go-back-gb64">Back</button>' : '').
	'<div class="corner-icons">'.
		'<a href="https://gb64.com/game.php?id='.$page_id.'" title="See this at GameBase64" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>'.
	'<table style="border:none;">
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
				$comments.'
			</td>
			<td style="width:340px;padding:0;border:none;vertical-align:top;text-align:right;">'.
				$col_of_thumbnails.
			'</td>
		</tr>
	</table>';

} else {

	/***** LIST *****/

	$rows = '';

	foreach($gbIds as $id) {

		$data = ReadGB64DB($id);

		$thumbnails = array_slice($data['thumbnails'], 0, 4);	// Maximum 4 thumbnails

		$line_of_thumbnails = '';
		foreach($thumbnails as $thumbnail)
			$line_of_thumbnails .= '<a class="gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" target="_blank" data-id="'.$id.'"><img class="gb64" src="images/gb64'.$thumbnail.'" alt="'.$thumbnail.'" /></a>';

		$rows .=
			'<tr>'.
				'<td class="info">'.
					'<a class="name gb64-list-entry" href="https://gb64.com/game.php?id='.$id.'" target="_blank" data-id="'.$id.'">'.$data['title'].'</a><br />'.
					$data['year'].' '.$data['company'].'<br />'.
					'<span class="language">'.$data['language'].'</span>'.
				'</td>'.
				'<td class="thumbnail">'.
					$line_of_thumbnails.
				'</td>'.
			'</tr>';
	}

	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-top:0;">GameBase64</h2>'.
		'<h3>'.count($gbIds).' entr'.(count($gbIds) > 1 ? 'ies' : 'y').' found</h3>'.
		'<table class="releases">'.
			$rows.
		'</table>';
}
echo json_encode(array('status' => 'ok', 'html' => $html, 'count' => count($gbIds)));
?>