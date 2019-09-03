<?php
/**
 * DeepSID
 *
 * Build an HTML page with details about the folder/composer. Includes charts
 * for players (pie chart) and active years (graph).
 * 
 * The group/work tables have been moved to the 'groups.php' file instead.
 * 
 * @uses		$_GET['fullname'] (to folder)
 */

require_once("class.account.php"); // Includes setup
require_once("pretty_player_names.php");
require_once("csdb_compo.php");
require_once("csdb_comments.php");
require_once("countries.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$html = '';
$fullname = $_GET['fullname'];
if (isset($fullname)) {

	if (empty($fullname))
		die(json_encode(array('status' => 'ok', 'html' => ''))); // Don't do root

	if (substr($fullname, 0, 23) == 'CSDb Music Competitions' && strlen($fullname) > 24) {

		// INSIDE ONE COMPETITION FOLDER

		try {
			if ($_SERVER['HTTP_HOST'] == LOCALHOST)
				$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
			else
				$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("SET NAMES UTF8");

			// Get the event ID of this compo folder
			$select = $db->prepare('SELECT event_id FROM competitions WHERE competition = :compo LIMIT 1');
			$select->execute(array(':compo'=>str_replace('CSDb Music Competitions/', '', $fullname)));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$event_id = $select->rowCount() ? $select->fetch()->event_id : 0;

			if ($event_id) {

				$sceners = array();

				$csdb =					CompoGetXML($event_id);
				$compos =				CompoGetEntries($csdb);
				$type_date_country =	CompoGetTypeDateCountry($csdb);
				$event_image =			CompoGetImage($event_id);
				$user_comments = 		CompoGetComments($csdb, $event_id);

				$aka = '<p style="position:relative;top:-6px;left:1px;margin:0 0 4px;"><small>'.(isset($csdb->Event->AKA) ? '('.$csdb->Event->AKA.')' : '&nbsp;').'</small></p>';

				$tagline = isset($csdb->Event->Tagline)
				? '<p style="position:relative;top:-'.(!empty($aka) ? '10' : '4').'px;margin:0;"><i>- '.$csdb->Event->Tagline.'</i></p>'
				: '';

				$address = isset($csdb->Event->Address) ? $csdb->Event->Address : '';
				$city = isset($csdb->Event->City) ? (!empty($address) ? ', ' : '').$csdb->Event->City : '';
				$state = isset($csdb->Event->State) ? (!empty($address) || !empty($city) ? ', ' : '').$csdb->Event->State : '';
				//$country = isset($csdb->Event->Country) ? $csdb->Event->Country : '';

				$place = !empty($address.$city.$state/*.$country*/)
					? '<p><b>Place:</b><br />'.$address.$city.$state./*$country.*/'</p>'
					: '';

				$website = isset($csdb->Event->Website)
					? '<p><b>Web Site:</b><br /><a href="'.$csdb->Event->Website.'" target="_blank">'.$csdb->Event->Website.'</a></p>'
					: '';

				$organizers = $comma = '';
				$amount = 8;
				if (isset($csdb->Event->Organizer)) {
					$orgs = $csdb->Event->Organizer;
					foreach($orgs as $org) {
						if (!isset($org->Handle)) break;
						$id = $org->Handle->ID;
						if (isset($org->Handle->Handle)) {
							// There's a handle, get it and store the ID for it for later reference
							$handle = $org->Handle->Handle.','.$id;
							$sceners[(string)$id] = $org->Handle->Handle;
						} else if (array_key_exists((string)$id, $sceners)) {
							// We've had this scener before so we know the name
							$handle = $sceners[(string)$id].','.$id;
						} else {
							// Can't figure this scener out so just use the ID
							$handle = $id;
						}
						if (strpos($handle, ',')) {
							$parts = explode(',', $handle);
							// ID and handle
							$m = '<a href="https://csdb.dk/scener/?id='.$parts[1].'" target="_blank">'.$parts[0].'</a>';
						} else {
							// [Scener:1234]
							$m = '[<a href="https://csdb.dk/scener/?id='.$handle.'" target="_blank">Scener:'.$handle.'</a>]';
						}
						$organizers .= $comma.$m;
						if (!$amount) {
							$organizers .= ' [...]';
							break;
						}
						$amount--;
						$comma = ', ';
					}
					$organizers = '<p><b>Organizers:</b><br />'.$organizers.'</p>';
				}

				$org_groups = $comma = '';
				$amount = 8;
				if (isset($csdb->Event->OrganizerGroup)) {
					$orgs = $csdb->Event->OrganizerGroup;
					foreach($orgs as $org) {
						if (!isset($org->Group)) break;
						$id = $org->Group->ID;
						if (isset($org->Group->Name)) {
							// There's a group name
							$group = $org->Group->Name.','.$id;
						} else {
							// Can't figure this group out so just use the ID
							$group = $id;
						}
						if (strpos($group, ',')) {
							$parts = explode(',', $group);
							// ID and group
							$m = '<a href="https://csdb.dk/group/?id='.$parts[1].'" target="_blank">'.$parts[0].'</a>';
						} else {
							// [Group:1234]
							$m = '[<a href="https://csdb.dk/group/?id='.$group.'" target="_blank">Group:'.$group.'</a>]';
						}
						$org_groups .= $comma.$m;
						if (!$amount) {
							$org_groups .= ' [...]';
							break;
						}
						$amount--;
						$comma = ', ';
					}
					$org_groups = '<p><b>Organizer Groups:</b><br />'.$org_groups.'</p>';
				}

				// Build the page HTML
				$html = '<div id="compo-profile"><h2 style="display:inline-block;margin:0;">'.$csdb->Event->Name.'</h2>'.
					'<div class="corner-icons">'.
						'<a href="https://csdb.dk/event/?id='.$event_id.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
					'</div>'.
					$aka.
					'<p'.(!empty($aka) ? ' style="position:relative;top:-8px;margin:0 0 4px;"' : '').'>'.$type_date_country.'</p>'.
					'<p'.(!empty($aka) ? ' style="position:relative;top:-6px;margin:0 0 4px;"' : '').'>'.$event_image.'</p>'.
					$tagline.
					'<div style="height:4px;"></div>'.
					$place.
					$website.
					$organizers.
					$org_groups.
					$user_comments.'</div>';

				die(json_encode(array('status' => 'ok', 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i><button class="to-top" title="Scroll back to the top" style="display:none;"><img src="images/to_top.svg" alt="" /></button>')));
			}

		} catch(PDOException $e) {
			$account->LogActivityError('composer.php (compo)', $e->getMessage());
			die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
		}
		die(json_encode(array('status' => 'ok', 'html' => $html)));

	} else {

		// OTHER FOLDERS

		try {
			if ($_SERVER['HTTP_HOST'] == LOCALHOST)
				$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
			else
				$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("SET NAMES UTF8");

			// If we are in a sub folder of a composer (e.g. work tunes or a previous handle) with no profile then re-use
			// NOTE: This block is also used in the 'groups.php' file.
			$folders = explode('/', $fullname);
			if (count($folders) > 3 && $folders[1] == 'MUSICIANS' && !empty($folders[4])) {
				// Do we have a profile for the unique sub folder of this composer?
				$select = $db->prepare('SELECT 1 FROM composers WHERE fullname = :fullname LIMIT 1');
				$select->execute(array(':fullname'=>$fullname));
				if ($select->rowCount() == 0)
					// No, re-use the profile of the parent composer folder then
					$fullname = str_replace('/'.$folders[count($folders) - 1], '', $fullname);
			}

			// Get data for top part like birthday, country, etc.
			$select = $db->prepare('SELECT * FROM composers WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			$select->setFetchMode(PDO::FETCH_OBJ);

			if ($select->rowCount())
				$row = $select->fetch();

			// Get data about players for the charts
			$select = $db->prepare('SELECT player, count(player) AS count FROM hvsc_files WHERE fullname LIKE :fullname GROUP BY player');
			$select->execute(array(':fullname'=>$fullname.'/%'));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$player_labels = Array();
			$player_counts = Array();
			if ($select->rowCount()) {
				foreach($select as $player_row) {
					$player_labels[] = empty($player_row->player) ? 'Unidentified player' : $player_row->player;
					$player_counts[] = $player_row->count;
				}
				foreach($player_labels as $key => $label) {
					if (isset($prettyPlayerNames[$label]))
						$player_labels[$key] = str_replace('a Basic Program', 'Basic Program', $prettyPlayerNames[$label]);
					else
						$player_labels[$key] = str_replace('_', ' ', preg_replace('/(V)(\d)/', 'v$2', $player_labels[$key]));
					$player_labels[$key] = str_replace('/', ' / ', $player_labels[$key]);
				}

				$max_allowed = 14; // 9
				array_multisort($player_counts, $player_labels);
				if (count($player_counts) > $max_allowed) {
					$less_counts = array_slice($player_counts, 0, count($player_counts) - $max_allowed);
					$player_labels = array_slice($player_labels, -$max_allowed);
					$player_counts = array_slice($player_counts, -$max_allowed);
					array_unshift($player_labels, 'Other');
					array_unshift($player_counts, (string)array_sum($less_counts));
				}
			}

			// Get data about active years
			$select = $db->prepare('SELECT copyright FROM hvsc_files WHERE fullname LIKE :fullname');
			$select->execute(array(':fullname'=>$fullname.'/%'));
			$select->setFetchMode(PDO::FETCH_OBJ);

			$years = Array();
			if ($select->rowCount()) {
				foreach($select as $player_row) {
					$year = substr($player_row->copyright, 0, 4);
					if (is_numeric($year)) $years[] = $year;
				}
			}
			sort($years);

			$ycounts = array_count_values($years);
			/*$years_labels = array_keys($ycounts);
			$years_counts = Array(array_values($ycounts));*/
			$years_labels = Array();
			$years_counts = Array();
			if (!empty($years)) {
				for($year = 1982; $year <= date("Y") ; $year++) {
					$years_labels[] = substr($year, -2);
					$years_counts[] = array_key_exists($year, $ycounts) ? $ycounts[$year] : null;
				}
				$years_counts = Array($years_counts);
			}

		} catch(PDOException $e) {
			$account->LogActivityError('composer.php', $e->getMessage());
			die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
		}
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

// Figure out if the fullname is a folder with folders or a folder belonging to a composer (or group)
$files = glob(ROOT_HVSC.'/'.$fullname.'/*.{sid,mus}', GLOB_BRACE);
if (!empty($files) && !in_array($fullname, Array(
	'DEMOS/0-9',
	'DEMOS/A-F',
	'DEMOS/G-L',
	'DEMOS/M-R',
	'DEMOS/S-Z',
	'DEMOS/UNKNOWN',
	'GAMES/0-9',
	'GAMES/A-F',
	'GAMES/G-L',
	'GAMES/M-R',
	'GAMES/S-Z',
	'_Datastorm 2018',
	'_From JCH\'s Special Collection',
))) {
	// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
	$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
	$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
	$fn = str_replace('_Exotic SID Tunes Collection', 'estc', $fn);
	$fn = strtolower(str_replace('/', '_', $fn));
	$thumbnail = 'images/composers/'.$fn.'.jpg';
	if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
} else {
	// Folder with folders
	$thumbnail = 'images/folder.png';
	$csdbid = 0;
}

$active_years = !empty($years) ? ($years[0] == end($years) ? $years[0] : $years[0].'-'.end($years)) : '';

if (isset($row)) {
	// We have extended info from the 'composers' database table
	$name		= $row->name;
	$handles	= str_replace(', ', ', <img class="arrow" src="images/composer_arrowright.svg" alt="" style="position:relative;top:1px;" />', $row->handles);
	$born		= $row->born; 
	$died		= substr($row->died, 0, 4);
	$cause		= (!empty($row->cause) ? '('.$row->cause.')' : '');
	$onsid		= (!empty($row->onsid) ? $row->onsid : $active_years); // Often the generated one will suffice
	$notable	= str_replace('[#]', '<img class="inline-icon icon-editor" src="images/composer_editor.svg" title="Music editor" alt="">', $row->notable);
	$country	= $row->country;
	$csdbtype	= $row->csdbtype;
	$csdbid		= $row->csdbid;
	$brand		= $row->brand;
	$spinner	= true;

	$died = $died == '1970' ? '<i>Unknown date</i>' : $died;

	// Append flag images to the potentially comma-separated list of multiple countries
	foreach($countryCodes as $key => $code) {
		$countryFound = strpos(strtolower($country), $key);
		if ($countryFound > -1)
			$country = str_ireplace($key, substr($country, $countryFound, strlen($key)).' <img class="flag" src="images/countries/'.$code.'.png" alt="'.$code.'" />', $country);
	}

} else {
	// No database help; we have to figure things out for ourselves
	$name		= substr('/'.$fullname, strrpos('/'.$fullname, '/') + 1);
	$handles	= '';
	$born		= '0000-00-00';
	$died		= '0000';
	$cause		= '';
	$onsid		= $active_years;
	$notable	= '';
	$country	= '';
	$csdbid		= 0;
	$brand		= '';
	$spinner	= false;

	// Ditch the prepended custom "_" or symlist "!" character
	// @todo Uh, why is '!' here? Does that ever appear in a composer name!?
	$name = substr($name, 0, 1) == '_' || substr($name, 0, 1) == '!' ? substr($name, 1) : $name;
}

$csdbCompoFolder = 'CSDb Music Competitions';
$exoticFolder = '_Exotic SID Tunes Collection';

// Top part with thumbnail, birthday, country, etc.
$html = '<table style="border:none;margin-bottom:0;"><tr>'.
			'<td style="padding:0;border:none;width:184px;">'.
				'<img class="composer" src="'.$thumbnail.'" alt="" />'.
			'</td>'.
			'<td style="position:relative;vertical-align:top;">'.
				'<h2 style="margin-top:0;'.(!empty($handles) ? 'margin-bottom:-1px;' : 'margin-bottom:6px;').'">'.$name.'</h2>'.
				(!empty($handles) ? '<h3 style="margin-top:0;margin-bottom:7px;">'.$handles.'</h3>' : '').
				($born != '0000-00-00' ? '<span class="line"><img class="icon cake" src="images/composer_cake.svg" title="Born" alt="" />'.
					substr($born, 0, 4).'</span>' : '').
				/*(!empty($onsid) ? '<span class="line onsid"><img class="icon" src="images/composer_chip.svg" title="OnSID" alt="" style="height:19px;" />'.
					str_replace(', ', ', <img class="arrow" src="images/composer_arrowright.svg" title="...then later..." alt="" />', $onsid) : '').'</span>'.*/
				($died != '0000' ? '<span class="line"><img class="icon stone" src="images/composer_stone.svg" title="Died" alt="" style="position:relative;top:3px;height:18px;margin-right:5px;" />'.
					$died.' '.$cause.'</span>' : '').
				(!empty($notable) ? '<span class="notable">'.
					'<img class="icon cstar" src="images/composer_star.svg" title="Notable" alt="" style="top:-1px;" /><b style="position:relative;top:-5px;">'.$notable.'&nbsp;</b></span>' : '').
				(!empty($country) ? '<span style="position:absolute;left:10px;bottom:10px;">'.
					'<img class="icon earth" src="images/composer_earth.svg" title="Country" alt="" />'.
					str_replace(', ', ', <img class="arrow" src="images/composer_arrowright.svg" title="Moved" alt="" />', $country).
				'</span>' : '').
				(!empty($brand)
				? '<img id="brand" src="images/brands/'.$brand.'" alt="'.$brand.'" />'
				: '').
			'</td>'.
		'</tr></table>'.
		// Below is empty groups/work table placeholder
		($fullname != $csdbCompoFolder && $fullname != $exoticFolder ?
			'<table id="table-groups" class="tight top" style="min-width:100%;font-size:14px;margin-top:5px;">'.
				'<tr>'.
					'<td class="topline bottomline leftline rightline" style="height:30px;padding:0 !important;text-align:center;">'.($spinner ? '<img class="loading-dots" src="images/loading_threedots.svg" alt="" style="margin-top:10px;" />' : '<div class="no-profile">No profile data</div>').'</td>'.
				'</tr>'.
			'</table>' : '').
		'<div class="corner-icons">'.
			'<div id="profilechange" style="'.($csdbid ? 'left:-153' : 'right:-3').'px;"></div>'.
			($csdbid ? '<a href="https://csdb.dk/'.$csdbtype.'/?id='.$csdbid.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>' : '').
		'</div>';

// Chartist - @link https://gionkunz.github.io/chartist-js/index.html
$cgsc = "_Compute's Gazette SID Collection";

if ($fullname == $cgsc) {
	// Show an IFRAME with the CGSC web site
	$html = '<iframe class="deepsid-iframe" src="//www.c64music.co.uk/" onload="ResizeIframe();"></iframe>';

} else if ($fullname == $exoticFolder) {
	// Show a box with technical information about the custom SID format
	$info = file_get_contents('../sidv4e.txt');
	$html .= '<pre class="fixed-font-info">'.$info.'</pre>';
	
} else if (substr($fullname, 0, strlen($cgsc)) != $cgsc && $fullname != $csdbCompoFolder) {
	// Charts for HVSC sub folders as well as custom "_" folders
	$html .= '<h3 style="margin-top:21px;">Active years<div class="legend">X = year (1982-)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Y = number of SID files</div></h3>
		<div id="ct-years"></div>
		<h3 style="margin-top:0;">Players used</h3><div id="ct-players"></div>
		<script type="text/javascript">'.
			/*'console.log("Labels: ", '.json_encode($player_labels).');
			console.log("Series: ", '.json_encode($player_counts).');'.*/
			'ctYears = new Chartist.Line("#ct-years",
			{
				labels: '.json_encode($years_labels).',
				series: '.json_encode($years_counts).',
			},
			{
				height: 400,
				fullWidth: true,
				chartPadding: {
					top: 16,
					right: 30,
				},
				axisX: {
					labelOffset: {
						x: -7,
						y: 2
					}
				},
				axisY: {
					offset: 30,
					onlyInteger: true,
				},
			});
			ctPlayers = new Chartist.Bar("#ct-players",
			{
				labels: '.json_encode($player_labels).',
				series: '.json_encode($player_counts).',
			},
			{
				height: '.((32 * count($player_labels)) + 42).',
				/*width: "90%",*/
				horizontalBars: true,
				distributeSeries: true,
				chartPadding: {
					top: 0,
					right: 90,
				},
				axisX: {
					onlyInteger: true,
				},
				axisY: {
					offset: 140,
					showGrid: false,
				},
			}).on("draw", function(data) {
				if(data.type === "bar") {
					data.element.attr({
						style: "stroke-width: 20px"
					});
				}
			});
		</script>';
}

echo json_encode(array('status' => 'ok', 'html' => $html));
?>