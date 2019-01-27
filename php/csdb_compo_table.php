<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and build an HTML page with the results of the
 * competition that the SID tune somehow participated in.
 * 
 * @uses		$_GET['compo']
 * @uses		$_GET['id']
 * @uses		$_GET['mark'] - the ID of the release page to mark on the list
 */

require_once("setup.php");
require_once("csdb_comments.php");
require_once("countries.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['compo']) || !isset($_GET['id']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

$scener_handle = array();
$scener_id = array();

// Get the XML from the CSDb web service
$xml = file_get_contents('https://csdb.dk/webservice/?type=event&id='.$_GET['id']);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>'.
		'<b>ID:</b> <a href="https://csdb.dk/event/?id='.$_GET['id'].'" target="_blank">'.$_GET['id'].'</a>')));
$csdb = simplexml_load_string(utf8_decode($xml));

$compos = $csdb->Event->Compo;
if (!isset($compos))
	die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;">The XML data from CSDb page had no competition entries.</p>')));

// Event type
$date_and_country = '';
if (isset($csdb->Event->EventType))
	$date_and_country = $csdb->Event->EventType.' &#9642 ';

// The dates this event took place
$months = array(
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July ',
	'August',
	'September',
	'October',
	'November',
	'December',
);
$startYear	= isset($csdb->Event->StartYear) ? (int)$csdb->Event->StartYear : '?';
$startMonth	= isset($csdb->Event->StartMonth) ? $months[(int)$csdb->Event->StartMonth - 1] : '?';
$startDay	= isset($csdb->Event->StartDay) ? (int)$csdb->Event->StartDay : '?';
$endYear	= isset($csdb->Event->EndYear) ? (int)$csdb->Event->EndYear : '?';
$endMonth	= isset($csdb->Event->EndMonth) ? $months[(int)$csdb->Event->EndMonth - 1] : '?';
$endDay		= isset($csdb->Event->EndDay) ? (int)$csdb->Event->EndDay : '?';

$year		= $startYear == $endYear ? $startYear : '';
$month		= $startMonth == $endMonth ? $startMonth : '';
$day		= $startDay == $endDay ? $startDay : '';

if (!empty($year) && !empty($month) && !empty($day))
	$date_and_country .= $day.' '.$month.' '.$year;
else if (!empty($year) && !empty($month))
	$date_and_country .= $startDay.' &ndash; '.$endDay.' '.$month.' '.$year;
else if (!empty($year))
	$date_and_country .= $startDay.' '.$startMonth.' &ndash; '.$endDay.' '.$endMonth.' '.$year;
else
	$date_and_country .= $startDay.' '.$startMonth.' '.$startYear.' &ndash; '.$endDay.' '.$endMonth.' '.$endYear;

// Country
if (isset($csdb->Event->Country)) {
	$country = $csdb->Event->Country;
	if (array_key_exists(strtolower($country), $countryCodes)) {
		// Append a flag image to country
		$code = $countryCodes[strtolower($csdb->Event->Country)];
		$country .= ' <img class="flag" src="images/countries/'.$code.'.png" alt="'.$code.'" />';
	}
	$date_and_country .= ' &#9642; '.$country;
}

// NOTE: CSDb follows this standard for event images:
// https://csdb.dk/gfx/events/(x)000/(id).jpg
// (x) is the first digit of the ID and (id) is the event ID itself.
// Example: https://csdb.dk/gfx/events/2000/2043.jpg

$image = 'https://csdb.dk/gfx/events/'.substr($_GET['id'], 0, 1).'000/'.$_GET['id'].'.jpg';
$event_image = @getimagesize($image) ? '<img src="'.$image.'" style="max-width:50%;max-height:50%;" />' : '';

$participants = '';
$unknown = 0;
foreach($compos as $compo) {
	if (strtolower($compo->Type) == strtolower($_GET['compo'])) {
		$releases = $compo->Releases->Release;
		if (!isset($releases))
			die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;">No results found for this competition.</p>')));

		$participants_array = array();
		foreach($releases as $release) {
			$id			= isset($release->ID) ? $release->ID : 0;
			$place 		= isset($release->Achievement->Place) ? $release->Achievement->Place : '?';
			$name 		= isset($release->Name) ? $release->Name : '?';
			$mark		= $id && $id == $_GET['mark']; // Boolean

			// Test cases for mix of numeric places and question marks:
			// http://chordian/deepsid/?file=/MUSICIANS/Z/Zardax/Proven_Futile.sid&tab=csdb
			// http://chordian/deepsid/?file=/MUSICIANS/N/Nygaard_Richard/Thats_the_Wave_It_Is.sid&tab=csdb
			
			// Test cases for entries that share the exact same place:
			// http://chordian/deepsid/?file=/MUSICIANS/L/Luca/Boy_Band.sid&tab=csdb
			// http://chordian/deepsid/?file=/MUSICIANS/A/Agemixer/N_Trans.sid&tab=csdb

			$bold = $mark ? ' class="compo-bold"' : '';
			$participants_array[substr($place, 0, 1) == '?' ? 'z'.++$unknown : (string)str_pad($place, 3, '0', STR_PAD_LEFT).$name] =
				'<tr'.$bold.'>'.
					'<td class="compo-arrow">'.
						($mark ? '<span class="compo-pos"></span>' : '').
					'</td>'.
					'<td class="compo-place">'.
						$place.
					'</td>'.
					'<td class="compo-name">'.
						($id
							? '<a class="participant ellipsis" href="https://csdb.dk/release/?id='.$id.'" target="_blank">'.$name.'</a>'
							: '<span class="participant ellipsis">'.$name.'</span>'
						).
					'</td>'.
					'<td class="compo-path" data-id="'.$id.'">'.
						// These cells will be filled by 'csdb_compo_path.php' (from 'browser.js')
					'</td>'.
				'</tr>';
		}

		ksort($participants_array);
		foreach($participants_array as $key => $entry) {
			$participants .= $entry;
		}

		if (!empty($participants)) {
			$participants =
				'<table class="tight compo">'.
					'<tr>'.
						'<th style="width:20px;"></th>'.
						'<th style="width:1px;text-align:right;padding-right:12px;"><u>#</u></th>'.
						'<th style="width:1px;"><u>Release</u></th>'.
						'<th><u>Click to play</u></th>'.
					'</tr>'.
					$participants.
				'</table>';
		}
		break;
	}
}

$user_comments = isset($csdb->Event->UserComment)
	? CommentsTable('User comments', $csdb->Event->UserComment, $scener_handle, $scener_id)
	: '';

$comment_button = '<button id="csdb-comment" data-type="event" data-id="'.$_GET['id'].'">Comment</button><br />';

// Build the sticky header HTML for the '#sticky' DIV
$sticky = '<h2 style="display:inline-block;margin-top:0;">'.$csdb->Event->Name.'</h2>'.
	'<button id="go-back" class="compo">Back</button>'.
	'<div id="corner-icons">'.
		'<a href="https://csdb.dk/event/?id='.$csdb->Event->ID.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
	'</div>';

// And now the body HTML for the '#page' DIV
$html = '<p style="position:relative;top:-20px;margin-top:16px;">'.$date_and_country.'</p>'.
	'<p style="position:relative;top:-12px;">'.$event_image.'</p>'.
	'<h3 style="margin-top:-8px;">'.$_GET['compo'].' Competition results:</h3>'.
	$participants.
	$user_comments.
	$comment_button;

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i>'));
?>