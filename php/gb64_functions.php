<?php
/**
 * DeepSID
 *
 * Functions for the 'GB64' tab.
 * 
 * @used-by		gb64.php
 */

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Change e.g. "Moon, The" to "The Moon" instead.
 * 
 * @param		string		$id					the original title
 *
 * @return		string							the adapted title
 */
function normalizeTitle(string $title): string
{
    $articles = [
        'The', 'A', 'An',       			// English
        'Der', 'Die', 'Das',    			// German
        'Le', 'La', 'Les',      			// French
        'El', 'Los', 'Las',     			// Spanish
        'Il', 'Lo', 'La', 'I', 'Gli', 'Le',	// Italian
        'De', 'Het'             			// Dutch
    ];

    $pattern = '/^(.*),\s*(' . implode('|', $articles) . ')$/i';

    if (preg_match($pattern, $title, $m)) {
        return $m[2] . ' ' . $m[1];
    }

    return $title;
}

/**
 * Return an array with information from the imported GameBase64 database.
 * 
 * @global		object		$gb					database connection
 *
 * @param		int			$id					id of page entry
 *
 * @return		array							array with information
 */
function readGB64DB($id) {

	global $gb;

	// Get the general info for the game
	$select_games = $gb->prepare('SELECT * FROM Games WHERE GA_Id = :id LIMIT 1');
	$select_games->execute(array(':id'=>$id));
	$select_games->setFetchMode(PDO::FETCH_OBJ);
	$games = $select_games->fetch();

	// Get the title
	$title = normalizeTitle($games->Name);

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
		$pgenre = $select_pgenres->fetch()->ParentGenre;
		$genre = empty($pgenre) ? $genre : $pgenre.' - '.$genre;
		$genre = str_replace('[uncategorized]', '<i>Uncategorized</i>', $genre);

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
?>