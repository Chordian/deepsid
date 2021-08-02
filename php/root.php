<?php
/**
 * DeepSID
 *
 * Build an HTML welcome page for the root.
 * 
 *  - Three recommendation boxes
 *  - Random "descent" box
 *  - Important message (good or bad)
 *  - Left and right boxes for top lists
 */

require_once("class.account.php"); // Includes setup
require_once("root_generate.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$decent_box_shown = false;

// $important = 'The database connections sometimes act up at the moment. If it persists I will consult the web hotel provider.';
$important = 'The audio bug seems to be fixed in version 91 of Chrome and Edge. Make sure you have updated to this version.';

$available_lists = ['maxfiles', 'longest', 'mostgames', 'countries', 'startaddr', 'maxtime'];
$dropdown_options =
	'<option value="'.$available_lists[0].'">Most SID tunes produced</option>'.
	'<option value="'.$available_lists[1].'">The longest SID tunes</option>'.
	'<option value="'.$available_lists[2].'">Most games covered</option>'.
	'<option value="'.$available_lists[3].'">Composers in countries</option>'.
	'<option value="'.$available_lists[4].'">Most popular start address</option>'.
	'<option value="'.$available_lists[5].'">Total playing time produced</option>'.
	'';

$row_options =
	'<option value="10">10</option>'.
	'<option value="25">25</option>'.
	'<option value="50">50</option>'.
	'<option value="100">100</option>'.
	'<option value="250">250</option>';

// Randomly choose two lists while also making sure they're not the same one
$choices = array_rand($available_lists, 2);
$choice_left = $available_lists[$choices[0]];
$choice_right = $available_lists[$choices[1]];

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	function CreateRecBox($random_id) {

		global $db, $decent_box_shown;

		// Get the fullname
		$select = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$random_id);
		$select->setFetchMode(PDO::FETCH_OBJ);
		$fullname = $select->rowCount() ? $select->fetch()->fullname : '';

		// Get composer data via the fullname
		$select = $db->query('SELECT name, shortname, handles, shorthandle FROM composers WHERE fullname = "'.$fullname.'"');
		$select->setFetchMode(PDO::FETCH_OBJ);
		$row = $select->fetch();

		// Error or irrelevant (such as big parent folders in HVSC)
		if ($select->rowCount() == 0) {
			if (!$decent_box_shown) {

				// Show a "decent" randomizer box ("CLICK HERE")
				$decent_box_shown = true;
				$decent_composers = [];

				// Get an array of all the folder ID belonging to composers JCH have given 2 stars or more
				$select_decent = $db->query('SELECT table_id FROM ratings WHERE user_id = '.JCH.' AND rating >= 2 AND type = "FOLDER"');
				$select_decent->setFetchMode(PDO::FETCH_OBJ);
				foreach($select_decent as $row_decent)
					array_push($decent_composers, $row_decent->table_id);

				// Pick a random "decent" folder
				$random_decent = $decent_composers[array_rand($decent_composers)];

				// Get the fullname of it
				$select_decent = $db->query('SELECT fullname FROM hvsc_folders WHERE id = '.$random_decent);
				$select_decent->setFetchMode(PDO::FETCH_OBJ);

				return '<table class="tight compo recommended decent" data-folder="'.$select_decent->fetch()->fullname.'" style="padding-bottom:0;"><tr><td style="height:123px;">'.
					'<div class="random-container">'.
						'<span>Click here</span><br />'.
						'to visit a random<br />'.
						'composer folder of a<br />'.
						'decent quality or better<br />'.
					'</div>'.
				'</td></tr></table>';
			} else
				// Just shown empty space there
				return '<table class="tight compo recommended" style="border:none;"></table>';
		}

		$name = empty($row->shortname) ? $row->name : $row->shortname;
		$parts = explode(',', $row->handles);
		$handle = empty($row->shorthandle) ? end($parts) : $row->shorthandle;

		// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
		$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
		$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
		$fn = strtolower(str_replace('/', '_', $fn));
		$thumbnail = 'images/composers/'.$fn.'.jpg';
		if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
		
		// Get type and file count
		$select = $db->query('SELECT type, files FROM hvsc_folders WHERE fullname = "'.$fullname.'"');
		$select->setFetchMode(PDO::FETCH_OBJ);
		$row = $select->fetch();
		$type = $row->type == 'GROUP' ? 'group' : 'single';
		$songs = $row->files;

		// Create the HTML table for the box

		return
			'<table class="tight compo recommended" data-folder="'.$fullname.'">'.
				'<tr>'.
					'<td colspan="2"><img class="folder" src="images/if_folder_'.$type.'.svg" alt="" /><h3>Recommended Folder</h3></td>'.
				'</tr>'.
				'<tr>'.
					'<td style="width:88px;padding-right:8px;">'.
						'<img class="composer root-thumbnail" src="'.$thumbnail.'" alt="" />'.
					'</td>'.
					'<td style="padding-top:1px;">'.
						'<h4>'.$name.'</h4>'.
						'<h5>'.$handle.'</h5>'.
						'<div style="position:absolute;bottom:8px;"><img class="icon doublenote" src="images/composer_doublenote.svg" title="Country" alt="" />'.$songs.' songs</div>'.
					'</td>'.
				'</tr>'.
			'</table>';
	}

	$good_composers = [];

	// Get an array of all the folder ID belonging to composers JCH have given 3 stars or more
	$select = $db->query('SELECT table_id FROM ratings WHERE user_id = '.JCH.' AND rating >= 3 AND type = "FOLDER"');
	$select->setFetchMode(PDO::FETCH_OBJ);
	foreach($select as $row)
		array_push($good_composers, $row->table_id);

	// Randomly choose three ID's while also making sure they're not the same ones
	$choices = array_rand($good_composers, 3);
	$random_id_1 = $good_composers[$choices[0]];
	$random_id_2 = $good_composers[$choices[1]];
	$random_id_3 = $good_composers[$choices[2]];

} catch(PDOException $e) {
	$account->LogActivityError('root.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$composers_game = array(
	['name' => 'Adam Gilmore',			'file' => '?file=/MUSICIANS/G/Gilmore_Adam'],
	['name' => 'Ben Daglish',			'file' => '?file=/MUSICIANS/D/Daglish_Ben/'],
	['name' => 'Charles Deenen',		'file' => '?file=/MUSICIANS/D/Deenen_Charles/'],
	['name' => 'Chris Cox',				'file' => '?file=/MUSICIANS/C/Cox_Chris/'],
	['name' => 'Chris Grigg',			'file' => '?file=/MUSICIANS/G/Grigg_Chris/'],
	['name' => 'Chris Hülsbeck',		'file' => '?file=/MUSICIANS/H/Huelsbeck_Chris/'],
	['name' => 'Clever Music',			'file' => '?file=/MUSICIANS/C/Clever_Music/'],
	['name' => 'Dave Lowe',				'file' => '?file=/MUSICIANS/L/Lowe_Dave/'],
	['name' => 'David Dunn',			'file' => '?file=/MUSICIANS/D/Dunn_David/'],
	['name' => 'David Hanlon',			'file' => '?file=/MUSICIANS/H/Hanlon_David/'],
	['name' => 'David Whittaker',		'file' => '?file=/MUSICIANS/D/Whittaker_David/'],
	['name' => 'Ed Bogas',				'file' => '?file=/MUSICIANS/B/Bogas_Ed/'],
	['name' => 'Fred Gray',				'file' => '?file=/MUSICIANS/G/Gray_Fred/'],
	['name' => 'Geoff Follin',			'file' => '?file=/MUSICIANS/F/Follin_Geoff/'],
	['name' => 'Graham Hansford',		'file' => '?file=/MUSICIANS/H/Hansford_Graham/'],
	['name' => 'Jason Brooke',			'file' => '?file=/MUSICIANS/B/Brooke_Jason/'],
	['name' => 'Jay Derrett',			'file' => '?file=/MUSICIANS/D/Derrett_Jay/'],
	['name' => 'Jeroen Tel',			'file' => '?file=/MUSICIANS/T/Tel_Jeroen/'],
	['name' => 'John Fitzpatrick',		'file' => '?file=/MUSICIANS/F/Fitzpatrick_John'],
	['name' => 'Jonathan Dunn',			'file' => '?file=/MUSICIANS/D/Dunn_Jonathan/'],
	['name' => 'Kris Hatlelid',			'file' => '?file=/MUSICIANS/H/Hatlelid_Kris'],
	['name' => 'Mark Cooksey',			'file' => '?file=/MUSICIANS/C/Cooksey_Mark/'],
	['name' => 'Martin Galway',			'file' => '?file=/MUSICIANS/G/Galway_Martin/'],
	['name' => 'Martin Walker',			'file' => '?file=/MUSICIANS/W/Walker_Martin/'],
	['name' => 'Matt Furniss',			'file' => '?file=/MUSICIANS/F/Furniss_Matt'],
	['name' => 'Matt Gray',				'file' => '?file=/MUSICIANS/G/Gray_Matt/'],
	['name' => 'Matthew Cannon',		'file' => '?file=/MUSICIANS/C/Cannon_Matthew/'],
	['name' => 'Neil Brennan',			'file' => '?file=/MUSICIANS/B/Brennan_Neil/'],
	['name' => 'Paul Hodgson',			'file' => '?file=/MUSICIANS/H/Hodgson_Paul/'],
	['name' => 'Peter Clarke',			'file' => '?file=/MUSICIANS/C/Clarke_Peter/'],
	['name' => 'Reyn Ouwehand',			'file' => '?file=/MUSICIANS/O/Ouwehand_Reyn/'],
	['name' => 'Richard Joseph',		'file' => '?file=/MUSICIANS/J/Joseph_Richard/'],
	['name' => 'Rob Hubbard',			'file' => '?file=/MUSICIANS/H/Hubbard_Rob/'],
	['name' => 'Russell Lieblich',		'file' => '?file=/MUSICIANS/L/Lieblich_Russell/'],
	['name' => 'Steve Barrett',			'file' => '?file=/MUSICIANS/B/Barrett_Steve/'],
	['name' => 'Steve Turner',			'file' => '?file=/MUSICIANS/T/Turner_Steve/'],
	['name' => 'Tim Follin',			'file' => '?file=/MUSICIANS/F/Follin_Tim/'],
	['name' => 'Wally Beben',			'file' => '?file=/MUSICIANS/B/Beben_Wally/'],
);

$composer_active = array(
	['name' => '4-Mat',					'file' => '?file=/MUSICIANS/0-9/4-Mat/'],
	['name' => 'Abaddon',				'file' => '?file=/MUSICIANS/A/Abaddon/'],
	['name' => 'Acrouzet',				'file' => '?file=/MUSICIANS/A/Acrouzet/'],
	['name' => 'Adam Morton',			'file' => '?file=/MUSICIANS/M/Morton_Adam'],
	['name' => 'Agemixer',				'file' => '?file=/MUSICIANS/A/Agemixer'],
	['name' => 'Ajitek',				'file' => '?file=/MUSICIANS/A/Ajitek/'],
	['name' => 'Aldo Chiummo',			'file' => '?file=/MUSICIANS/C/Chiummo_Aldo/'],
	['name' => 'BOGG',					'file' => '?file=/MUSICIANS/B/BOGG/'],
	['name' => 'Booker',				'file' => '?file=/MUSICIANS/B/Booker'],
	['name' => 'Britelite',				'file' => '?file=/MUSICIANS/B/Britelite/'],
	['name' => 'Buddha',				'file' => '?file=/MUSICIANS/B/Buddha/'],
	['name' => 'Båtsman',				'file' => '?file=/MUSICIANS/B/Batsman/'],
	['name' => 'c0zmo',					'file' => '?file=/MUSICIANS/C/C0zmo/'],
	['name' => 'Cadaver',				'file' => '?file=/MUSICIANS/C/Cadaver/'],
	['name' => 'Chabee',				'file' => '?file=/MUSICIANS/C/Chabee/'],
	['name' => 'CreaMD',				'file' => '?file=/MUSICIANS/C/CreaMD/'],
	['name' => 'DaFunk',				'file' => '?file=/MUSICIANS/D/DaFunk/'],
	['name' => 'dalezy',				'file' => '?file=/MUSICIANS/D/Dalezy/'],
	['name' => 'Dane',					'file' => '?file=/MUSICIANS/M/Mitch_and_Dane/Dane/'],
	['name' => 'Danko',					'file' => '?file=/MUSICIANS/D/Danko_Tomas/'],
	['name' => 'Deetsay',				'file' => '?file=/MUSICIANS/D/Deetsay/'],
	['name' => 'Devilock',				'file' => '?file=/MUSICIANS/D/Devilock/'],
	['name' => 'Divertigo',				'file' => '?file=/MUSICIANS/D/Divertigo/'],
	['name' => 'dLx',					'file' => '?file=/MUSICIANS/D/Dlx'],
	['name' => 'DRAX',					'file' => '?file=/MUSICIANS/D/DRAX/'],
	['name' => 'Dya',					'file' => '?file=/MUSICIANS/D/Dya'],
	['name' => 'Encore',				'file' => '?file=/MUSICIANS/E/Encore/'],
	['name' => 'Eric Dobek',			'file' => '?file=/MUSICIANS/D/Dobek_Eric/'],
	['name' => 'Factor6',				'file' => '?file=/MUSICIANS/F/Factor6'],
	['name' => 'Fegolhuzz',				'file' => '?file=/MUSICIANS/F/Fegolhuzz/'],
	['name' => 'Flex',					'file' => '?file=/MUSICIANS/H/Hannula_Antti/'],
	['name' => 'Flotsam',				'file' => '?file=/MUSICIANS/F/Flotsam/'],
	['name' => 'Fredrik',				'file' => '?file=/MUSICIANS/F/Fredrik'],
	['name' => 'Gaetano Chiummo',		'file' => '?file=/MUSICIANS/C/Chiummo_Gaetano/'],
	['name' => 'Gerard Hultink',		'file' => '?file=/MUSICIANS/H/Hultink_Gerard/'],
	['name' => 'Glen Rune Gallefoss',	'file' => '?file=/MUSICIANS/B/Blues_Muz/Gallefoss_Glenn/'],
	['name' => 'Goto80',				'file' => '?file=/MUSICIANS/G/Goto80'],
	['name' => 'Haschpipan',			'file' => '?file=/MUSICIANS/H/Haschpipan/'],
	['name' => 'HeatWave',				'file' => '?file=/MUSICIANS/H/HeatWave/'],
	['name' => 'Hermit',				'file' => '?file=/MUSICIANS/H/Hermit'],
	['name' => 'Highway Guy',			'file' => '?file=/MUSICIANS/H/Highway_Guy/'],
	['name' => 'Hydrogen',				'file' => '?file=/MUSICIANS/H/Hydrogen'],
	['name' => 'Isildur',				'file' => '?file=/MUSICIANS/I/Isildur/'],
	['name' => 'Jab',					'file' => '?file=/MUSICIANS/J/Jab/'],
	['name' => 'Jammer',				'file' => '?file=/MUSICIANS/J/Jammer/'],
	['name' => 'Jangler',				'file' => '?file=/MUSICIANS/H/Hannula_Janne/'],
	['name' => 'JCH',					'file' => '?file=/MUSICIANS/J/JCH/'],
	['name' => 'Jellica',				'file' => '?file=/MUSICIANS/J/Jellica/'],
	['name' => 'Jani Joeli',			'file' => '?file=/MUSICIANS/J/Joeli_Jani/'],
	['name' => 'Jason Page',			'file' => '?file=/MUSICIANS/P/Page_Jason/'],
	['name' => 'Juzdie',				'file' => '?file=/MUSICIANS/J/Juzdie/'],
	['name' => 'Konrád Kiss',			'file' => '?file=/MUSICIANS/K/Kiss_Konrad/'],
	['name' => 'Klegg',					'file' => '?file=/MUSICIANS/K/Klegg/'],
	['name' => 'Kompositkrut',			'file' => '?file=/MUSICIANS/K/Kompositkrut/'],
	['name' => 'Kribust',				'file' => '?file=/MUSICIANS/K/Kribust'],
	['name' => 'Laurikka',				'file' => '?file=/MUSICIANS/L/Laurikka/'],
	['name' => 'Laxity',				'file' => '?file=/MUSICIANS/L/Laxity/'],
	['name' => 'Lft',					'file' => '?file=/MUSICIANS/L/Lft/'],
	['name' => 'Linus',					'file' => '?file=/MUSICIANS/L/Linus/'],
	['name' => 'LMan',					'file' => '?file=/MUSICIANS/L/LMan/'],
	['name' => 'Luca',					'file' => '?file=/MUSICIANS/L/Luca/'],
	['name' => 'Maak',					'file' => '?file/MUSICIANS/M/Maak/'],
	['name' => 'Magnar',				'file' => '?file=/MUSICIANS/M/Magnar/'],
	['name' => 'Mahoney',				'file' => '?file=/MUSICIANS/M/Mahoney/'],
	['name' => 'Max Hall',				'file' => '?file=/MUSICIANS/M/Max_F3H/'],
	['name' => 'mAZE',					'file' => '?file=/MUSICIANS/M/Maze/'],
	['name' => 'MCH',					'file' => '?file=/MUSICIANS/M/MCH/'],
	['name' => 'Mermaid',				'file' => '?file=/MUSICIANS/M/Mermaid/'],
	['name' => 'Metal',					'file' => '?file=/MUSICIANS/M/Metal/'],
	['name' => 'Mibri',					'file' => '?file=/MUSICIANS/M/Mibri/'],
	['name' => 'Mixer',					'file' => '?file=/MUSICIANS/M/Mixer/'],
	['name' => 'Møllpauk',				'file' => '?file=/MUSICIANS/M/Moellpauk/'],
	['name' => 'Mr. Death',				'file' => '?file=/MUSICIANS/M/Mr_Death/'],
	['name' => 'Mr. Mouse',				'file' => '?file=/MUSICIANS/M/Mr_Mouse/'],
	['name' => 'Mutetus',				'file' => '?file=/MUSICIANS/M/Mutetus/'],
	['name' => 'Mythus',				'file' => '?file=/MUSICIANS/M/Mythus/'],
	['name' => 'PCH',					'file' => '?file=/MUSICIANS/P/PCH/'],
	['name' => 'Prosonix',				'file' => '?file=/MUSICIANS/P/Prosonix/'],
	['name' => 'Proton',				'file' => '?file=/MUSICIANS/P/Proton/'],
	['name' => 'psych858o',				'file' => '?file=/MUSICIANS/P/Psych858o/'],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => 'Sean Connolly',			'file' => '?file=/MUSICIANS/C/Connolly_Sean/'],
	['name' => 'Saul Cross',			'file' => '?file=/MUSICIANS/C/Cross_Saul/'],
	['name' => 'Owen Crowley',			'file' => '?file=/MUSICIANS/C/Crowley_Owen'],
	['name' => '_V_',					'file' => '?file=/MUSICIANS/M/Merken_Vincent'],
	['name' => 'Richard Bayliss',		'file' => '?file=/MUSICIANS/B/Bayliss_Richard/'],
);

$composer_retired = array(
	['name' => '20CC',					'file' => '?file=/MUSICIANS/0-9/20CC/'],
	['name' => 'A-Man',					'file' => '?file=/MUSICIANS/A/A-Man/'],
	['name' => 'Alex Mauer',			'file' => '?file=/MUSICIANS/M/Mauer_Alex/'],
	['name' => 'AMJ',					'file' => '?file=/MUSICIANS/A/AMJ/'],
	['name' => 'Arman Behdad',			'file' => '?file=/MUSICIANS/B/Behdad_Arman/'],
	['name' => 'Ashley Hogg',			'file' => '?file=/MUSICIANS/H/Hogg_Ashley/'],
	['name' => 'ATOO',					'file' => '?file=/MUSICIANS/A/ATOO/'],
	['name' => 'Audial Arts',			'file' => '?file=/MUSICIANS/A/Audial_Arts/'],
	['name' => 'Avalon',				'file' => '?file=/MUSICIANS/A/Avalon/'],
	['name' => 'Dwayne Bakewell',		'file' => '?file=/MUSICIANS/B/Bakewell_Dwayne/'],
	['name' => 'Barry Leitch',			'file' => '?file=/MUSICIANS/L/Leitch_Barry/'],
	['name' => 'Boogaloo',				'file' => '?file=/MUSICIANS/B/Boogaloo/'],
	['name' => 'Brandon Walsh',			'file' => '?file=/MUSICIANS/B/Brandon_Walsh/'],
	['name' => 'Brian of Graffity',		'file' => '?file=/MUSICIANS/B/Brian/'],
	['name' => 'Bzyk',					'file' => '?file=/MUSICIANS/B/Bzyk/'],
	['name' => 'Carlos of Breeze',		'file' => '?file=/MUSICIANS/C/Carlos_Breeze/'],
	['name' => 'Cerror',				'file' => '?file=/MUSICIANS/C/Cerror/'],
	['name' => 'Chotaire',				'file' => '?file=/MUSICIANS/C/Chotaire/'],
	['name' => 'Compod',				'file' => '?file=/MUSICIANS/C/Compod/'],
	['name' => 'Cycleburner',			'file' => '?file=/MUSICIANS/C/Cycleburner/'],
	['name' => 'Daf',					'file' => '?file=/MUSICIANS/D/Daf/'],
	['name' => 'Dalton',				'file' => '?file=/MUSICIANS/D/Dalton/'],
	['name' => 'Deek',					'file' => '?file=/MUSICIANS/D/Deek'],
	['name' => 'Dr. Voice',				'file' => '?file=/MUSICIANS/D/Dr_Voice'],
	['name' => 'Duck LaRock',			'file' => '?file=/MUSICIANS/D/Duck_LaRock/'],
	['name' => 'F.A.M.E.',				'file' => '?file=/MUSICIANS/F/FAME/'],
	['name' => 'Fanta',					'file' => '?file=/MUSICIANS/F/Fanta/'],
	['name' => 'Freedom',				'file' => '?file=/MUSICIANS/F/Freedom/'],
	['name' => 'Future Freak',			'file' => '?file=/MUSICIANS/F/Future_Freak'],
	['name' => 'Glover',				'file' => '?file=/MUSICIANS/G/Glover'],
	['name' => 'Griff',					'file' => '?file=/MUSICIANS/G/Griff/'],
	['name' => 'Harti',					'file' => '?file=/MUSICIANS/H/Harti/'],
	['name' => 'Hayes',					'file' => '?file=/MUSICIANS/M/Mueller_Markus/'],
	['name' => 'Hein Holt',				'file' => '?file=/MUSICIANS/H/Holt_Hein/'],
	['name' => 'iLKke',					'file' => '?file=/MUSICIANS/I/Ilkke/'],
	['name' => 'Jammic',				'file' => '?file=/MUSICIANS/J/Jammic/'],
	['name' => 'Jeff',					'file' => '?file=/MUSICIANS/J/Jeff/'],
	['name' => 'Jens Blidon',			'file' => '?file=/MUSICIANS/B/Blidon_Jens/'],
	['name' => 'Jeroen Koops',			'file' => '?file=/MUSICIANS/K/Koops_Jeroen/'],
	['name' => 'JO',					'file' => '?file=/MUSICIANS/J/JO/'],
	['name' => 'Johannes Bjerregaard',	'file' => '?file=/MUSICIANS/B/Bjerregaard_Johannes/'],
	['name' => 'Holger Gehrmann',		'file' => '?file=/MUSICIANS/G/Gehrmann_Holger'],
	['name' => 'kb',					'file' => '?file=/MUSICIANS/K/KB/'],
	['name' => 'Kjell Nordbø',			'file' => '?file=/MUSICIANS/B/Blues_Muz/Nordboe_Kjell/'],
	['name' => 'Klax',					'file' => '?file=/MUSICIANS/K/Klax/'],
	['name' => 'Kordiaukis',			'file' => '?file=/MUSICIANS/K/Kordiaukis/'],
	['name' => 'Link',					'file' => '?file=/MUSICIANS/L/Link/'],
	['name' => 'Marcel Donné',			'file' => '?file=/MUSICIANS/M/Mad_Donne_Marcel/'],
	['name' => 'Marcy',					'file' => '?file=/MUSICIANS/M/Marcy/'],
	['name' => 'Megmyx',				'file' => '?file=/MUSICIANS/M/Megmyx/'],
	['name' => 'Monk',					'file' => '?file=/MUSICIANS/M/Monk/'],
	['name' => 'Moog',					'file' => '?file=/MUSICIANS/M/Moog/'],
	['name' => 'Moppe',					'file' => '?file=/MUSICIANS/M/Moppe/'],
	['name' => 'Mortimer Twang',		'file' => '?file=/MUSICIANS/M/Mortimer_Twang/'],
	['name' => 'MotionRide',			'file' => '?file=/MUSICIANS/M/MotionRide/'],
	['name' => 'MSK',					'file' => '?file=/MUSICIANS/M/MSK/'],
	['name' => 'Odi',					'file' => '?file=/MUSICIANS/O/Odi/'],
	['name' => 'Oedipus',				'file' => '?file=/MUSICIANS/O/Oedipus/'],
	['name' => 'Orcan',					'file' => '?file=/MUSICIANS/O/Orcan/'],
	['name' => 'Pater Pi',				'file' => '?file=/MUSICIANS/P/Pater_Pi/'],
	['name' => 'Peacemaker',			'file' => '?file=/MUSICIANS/P/Peacemaker/'],
	['name' => 'Pece',					'file' => '?file=/MUSICIANS/P/Pece/'],
	['name' => 'Phobos',				'file' => '?file=/MUSICIANS/P/Phobos/'],
	['name' => 'Praiser',				'file' => '?file=/MUSICIANS/P/Praiser/'],
	['name' => 'PRI',					'file' => '?file=/MUSICIANS/P/PRI/'],
	['name' => 'Professor Chaos',		'file' => '?file=/MUSICIANS/P/Professor_Chaos'],
	['name' => 'Psylicium',				'file' => '?file=/MUSICIANS/P/Psylicium/'],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => '',				'file' => '?file='],
	['name' => 'Tim Kleinert',			'file' => '?file=/MUSICIANS/K/Kleinert_Tim/'],
	['name' => 'Thomas Detert',			'file' => '?file=/MUSICIANS/D/Detert_Thomas/'],
	['name' => 'Stello Doussis',		'file' => '?file=/MUSICIANS/D/Doussis_Stello/'],
	['name' => 'Rayne',					'file' => '?file=/MUSICIANS/M/Mueller_Manuel/'],
	['name' => 'Sidman',				'file' => '?file=/MUSICIANS/H/Hesford_Paul'],
	['name' => 'Oliver Kläwer',			'file' => '?file=/MUSICIANS/K/Klaewer_Oliver/'],
	['name' => 'Xayne',					'file' => '?file=/MUSICIANS/B/Beat_Machine/Xayne/'],
);


/*
	Next up above is 'Q' - but I think I'm going to have to go database instead for flexibility.

	Idea:

	Don't add thumbnails (too small) - but country flags might work!

*/


$composer_cgsc = array(
	// ['name' => '',			'file' => '?file=/MUSICIANS///'],
	['name' => 'Brian Copeland',		'file' => '?file=/Compute\'s%20Gazette%20SID%20Collection/Brian_L_Copeland/'],
);

$i = 0;
$quick_shortcuts = '';
while (true) {
	$author_game = count($composers_game) > $i ? $composers_game[$i] : '';
	$author_active = count($composer_active) > $i ? $composer_active[$i] : '';
	$author_retired = count($composer_retired) > $i ? $composer_retired[$i] : '';
	$author_cgsc = count($composer_cgsc) > $i ? $composer_cgsc[$i] : '';

	if (empty($author_game) && empty($author_active)) break;

	$quick_shortcuts .=	
		'<tr>'.
			'<td>'.(!empty($author_game) ? '<a href="'.$author_game['file'].'">'.$author_game['name'].'</a>' : '').'</td>'.
			'<td>'.(!empty($author_active) ? '<a href="'.$author_active['file'].'">'.$author_active['name'].'</a>' : '').'</td>'.
			'<td>'.(!empty($author_retired) ? '<a href="'.$author_retired['file'].'">'.$author_retired['name'].'</a>' : '').'</td>'.
			'<td>'.(!empty($author_cgsc) ? '<a href="'.$author_cgsc['file'].'">'.$author_cgsc['name'].'</a>' : '').'</td>'.
		'</tr>';
	$i++;
};

$html =
	'<div style="height:149px;"></div>'.
	// '<div class="root-wide good-news">'.$important.'</div>'.
	// Recommendations
	'<table class="root rec"><tr>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_1).
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_2).
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:10px;">'.
			CreateRecBox($random_id_3).
		'</td>'.
	'</tr></table>'.
	// Top lists
	'<table class="root"><tr>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-left" name="select-top-list-left">'.
				$dropdown_options.
			'</select>'.
			'<label>Rows</label>'.
			'<select class="dropdown-top-rows dropdown-top-rows-left" name="select-top-rows-left">'.
				$row_options.
			'</select>'.
			'<table class="top-list-left tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList(10, $choice_left).
			'</table>'.
		'</td>'.
		'<td style="width:10px;"></td>'.
		'<td style="max-width:300px;">'.
			'<select class="dropdown-top-list dropdown-top-list-right" name="select-top-list-right">'.
				$dropdown_options.
			'</select>'.
			'<label>Rows</label>'.
			'<select class="dropdown-top-rows dropdown-top-rows-right" name="select-top-rows-right">'.
				$row_options.
			'</select>'.
			'<table class="top-list-right tight compo" style="max-width:100%;font-size:14px;padding:8px 12px;">'.
				GenerateList(10, $choice_right).
			'</table>'.
		'</td>'.
	'</tr></table>'.
	// Quick shortcuts
	'<table class="root compo rec quicklinks">'.
		'<tr>'.
			'<th>Game Legends</th>'.
			'<th>Active Sceners</th>'.
			'<th>Retired Sceners</th>'.
			'<th>Compute\'s Gazette</th>'.
		'</tr>'.
		$quick_shortcuts.
	'</table>'.
	// Banner exchange
	'<div style="text-align:center;">'.
		'<iframe src="https://cbm8bit.com/banner-exchange/show-random-banner/any?width=468" title="Commodore Banner Exchange" frameborder="0" style="width: 468px; height: 60px; border: 0; margin: 5px;"></iframe><br />'.
		'<small style="position:relative;top:-13px;"><a target="_blank" href="https://cbm8bit.com/banner-exchange/" title="Commodore Banner Exchange">Commodore Banner Exchange</a></small>'.
	'</div>';

echo json_encode(array('status' => 'ok', 'html' => $html, 'left' => $choice_left, 'right' => $choice_right));
?>