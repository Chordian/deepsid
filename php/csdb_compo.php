<?php
/**
 * DeepSID
 *
 * Shared functions for generating a CSDb competition page. Included by other
 * CSDb PHP scripts.
 * 
 * @used-by		composer.php
 * @used-by		csdb_compo_table.php
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
	$xml = curl('https://csdb.dk/webservice/?type=event&id='.$event_id);
	if (!strpos($xml, '<CSDbData>'))
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>CSDb is currently unreachable.</i></p>'.
			'<b>ID:</b> <a href="https://csdb.dk/event/?id='.$event_id.'" target="_blank">'.$event_id.'</a>')));
	$csdb = simplexml_load_string($xml);
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
 * icon). Each of these are separated by a dot character. Great for ONE line.
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
	return @getimagesize($image) ? '<img class="event" src="'.$image.'" style="max-width:50%;max-height:50%;" />' : '';
}

/**
 * Get the comment table and its comment button.
 * 
 * @param	object		pointer to XML data
 * @param	int			event id
 * 
 * @return	string		HTML string with the comment table and button
 */
function CompoGetComments($csdb, $event_id) {
	$scener_handle = array();
	$scener_id = array();
	$comments = isset($csdb->Event->UserComment)
		? CommentsTable('User comments', $csdb->Event->UserComment, $scener_handle, $scener_id)
		: '';
	$comments .= '<button id="csdb-comment" data-type="event" data-id="'.$event_id.'">Comment</button><br />';
	return $comments;
}
?>