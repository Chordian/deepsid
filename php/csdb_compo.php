<?php
/**
 * DeepSID
 *
 * Shared functions for generating a CSDb competition page. Included by other
 * CSDb PHP scripts.
 */

require_once("setup.php");
require_once("countries.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

/**
 * Get the XML from the CSDb web service.
 * 
 * @param	int			event id
 *
 * @return	object		pointer to XML data
 */
function CompoGetXML($event_id) {
	$xml = file_get_contents('https://csdb.dk/webservice/?type=event&id='.$event_id);
	if (!strpos($xml, '<CSDbData>'))
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>'.
			'<b>ID:</b> <a href="https://csdb.dk/event/?id='.$event_id.'" target="_blank">'.$event_id.'</a>')));
	$csdb = simplexml_load_string(utf8_decode($xml));
	return $csdb;		
}

/**
 * Get the compo section with entries.
 * 
 * @param	object		pointer to XML data
 * 
 * @return	object		an array of entries
 */
function CompoGetEntries($csdb) {
	$compos = $csdb->Event->Compo;
	if (!isset($compos))
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;">The XML data from the CSDb page had no competition entries.</p>')));
	return $compos;
}

/**
 * Get a string with event type, dates and country (including an appended flag
 * icon). Each of these are separated by a dot character. Good for ONE line.
 * 
 * @param	object		pointer to XML data
 * 
 * @return	string		HTML string with type, date and country
 */
function CompoGetTypeDateCountry($csdb) {

	global $countryCodes;

	// Event type
	$type_date_country = '';
	if (isset($csdb->Event->EventType))
		$type_date_country = $csdb->Event->EventType.' &#9642 ';

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
		$type_date_country .= $day.' '.$month.' '.$year;
	else if (!empty($year) && !empty($month))
		$type_date_country .= $startDay.' &ndash; '.$endDay.' '.$month.' '.$year;
	else if (!empty($year))
		$type_date_country .= $startDay.' '.$startMonth.' &ndash; '.$endDay.' '.$endMonth.' '.$year;
	else
		$type_date_country .= $startDay.' '.$startMonth.' '.$startYear.' &ndash; '.$endDay.' '.$endMonth.' '.$endYear;

	// Country
	if (isset($csdb->Event->Country)) {
		$country = $csdb->Event->Country;
		if (array_key_exists(strtolower($country), $countryCodes)) {
			// Append a flag image to country
			$code = $countryCodes[strtolower($csdb->Event->Country)];
			$country .= ' <img class="flag" src="images/countries/'.$code.'.png" alt="'.$code.'" />';
		}
		$type_date_country .= ' &#9642; '.$country;
	}
	return $type_date_country;
}

/**
 * Get the event image, if present.
 * 
 * @param	int			event id
 *
 * @return	string		HTML string with the image element
 */
function CompoGetImage($event_id) {
	// NOTE: CSDb follows this standard for event images:
	// https://csdb.dk/gfx/events/(x)000/(id).jpg
	// (x) is the first digit of the ID and (id) is the event ID itself.
	// Example: https://csdb.dk/gfx/events/2000/2043.jpg
	$image = 'https://csdb.dk/gfx/events/'.substr($event_id, 0, 1).'000/'.$event_id.'.jpg';
	return @getimagesize($image) ? '<img src="'.$image.'" style="max-width:50%;max-height:50%;" />' : '';
}

/**
 * Get a ranked list of entries in a specific type of competition.
 * 
 * @param	object		an array of entries from CompoGetEntries()
 * @param	string		type of competition, e.g. "C64 Music"
 * @param	int			optional; place in table to mark with an arrow
 * @param	boolean		optional; true to show credits in second column
 * 						or false to show clickable SID files (default)
 * 
 * @return	string		HTML string with the table and its rows
 */
function CompoGetTable($compos, $type, $position = -1, $show_credits = false) {
	global $sceners;
	$participants = '';
	$unknown = 0;
	foreach($compos as $compo) {
		if (strtolower($compo->Type) == strtolower($type)) {
			$releases = $compo->Releases->Release;
			if (!isset($releases))
				return '<p style="margin-top:0;">No competition results found for the "'.$type.'" type.</p>';

			$participants_array = array();
			foreach($releases as $release) {
				$id			= isset($release->ID) ? $release->ID : 0;
				$place 		= isset($release->Achievement->Place) ? $release->Achievement->Place : '?';
				$name 		= isset($release->Name) ? $release->Name : '?';
				$mark		= $id && $id == $position; // Boolean

				if ($show_credits) {
					$released_by = '<small class="u1">?</small>?<small class="u2">?</small>';
					if (isset($release->ReleasedBy->Handle))
						$released_by = $release->ReleasedBy->Handle;
					else if (isset($release->ReleasedBy->Group))
						$released_by = $release->ReleasedBy->Group;

					// Handles and/or groups it was released by
					$released_by = '';
					if (isset($csdb->Release->ReleasedBy)) {
						$handles = $csdb->Release->ReleasedBy->Handle; 
						if (isset($handles)) {
							foreach($handles as $handle) {
								$released_by .= ', <a href="https://csdb.dk/scener/?id='.$handle->ID.'" target="_blank">'.$handle->Handle.'</a>';
								if (!array_key_exists((string)$handle->ID, $sceners))
									// Save the handle in case the ID is repeated in 'Credits' further below
									$sceners[(string)$handle->ID] = $handle->Handle;
							}
						}
						$groups = $csdb->Release->ReleasedBy->Group;
						if (isset($groups)) {
							foreach($groups as $group) {
								$released_by .= ', <a href="https://csdb.dk/group/?id='.$group->ID.'" target="_blank">'.$group->Name.'</a>';
							}
						}
						$released_by = '<p><b>Released by:</b><br />'.substr($released_by, 2).'</p>';
					}







				}

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
						'</td>'.($show_credits
							? '<td class="compo-path">'.$credits_list
							: '<td class="compo-path" data-id="'.$id.'">' // Filled by 'csdb_compo_path.php'
						).'</td>'.
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
							'<th><u>'.($show_credits ? 'Credits' : 'Click to play').'</u></th>'.
						'</tr>'.
						$participants.
					'</table>';
			}
			break;
		}
	}
	return $participants;
}
/*
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

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i><button id="to-top" title="Scroll back to the top" style="display:none;"><img src="images/to_top.svg" alt="" /></button>'));
?>
*/