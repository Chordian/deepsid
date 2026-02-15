<?php
/**
 * DeepSID
 *
 * Get a list of composer profiles for a drop-down box in the upload wizard.
 * 
 * @uses		$_GET['active']			1 = active only, 0 = everyone
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {
	$db = $account->GetDB();

	$all_profiles = array();

	$active_only = $_GET['active'] ? ' AND active = "'.date("Y").'" AND died = "0000-00-00"' : '';

	$select = $db->query('SELECT fullname, name, handles FROM composers WHERE fullname LIKE "_High Voltage SID Collection/%" AND fullname NOT LIKE "%/GROUPS/%"'.$active_only.' ORDER BY fullname');
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach($select as $row) {
		$name = $row->name;
		$all_handles = explode(',', $row->handles);
		$latest_handle = trim(end($all_handles));
		$handle = strpos($latest_handle, '<del>') === false ? $latest_handle : '';
		if ((empty($name) || $name == '?') && !empty($handle))
			$author = $handle;
		else if (!empty($name) && empty($handle))
			$author = $name;
		else if (!empty($name) && !empty($handle))
			$author = $name.' ('.$handle.')';
		else
			$author = '';
		
		// Special treatment
		$author = str_replace('Riku <del>Kangas</del> Ö', 'Riku Ö', $author);
		$author = str_replace('Wojciech Radziejewski', 'W. Radziejewski', $author);
		$author = str_replace('Psycho8580 / psych858o', 'psych858o', $author);
		$author = str_replace('Narciso Quintana Varo (Narcisound)', 'Narciso Quintana Varo', $author);
		$author = str_replace('Michael Philip Bridgewater', 'Michael P. Bridgewater', $author);
		$author = str_replace('Thomas Egeskov Petersen', 'Thomas E. Petersen', $author);
		$author = str_replace('Benjamin Dibbert', 'Ben Dibbert', $author);
		$author = str_replace('Jan Diabelez Arent Harries', 'Jan Harries', $author);
		$author = str_replace('Glenn Rune Gallefoss (6R6 / GRG)', 'Glenn Rune Gallefoss (6R6)', $author);
		$author = str_replace('Tero Mäyränen (Deetsay / Pekka Pou)', 'Tero Mäyränen (Deetsay)', $author);
		$author = str_replace('Figge Wulff Wasberger (Fegolhuzz)', 'Figge Wasberger (Fegolhuzz)', $author);
		$author = str_replace('Hein Pieter Holt (Hein Design)', 'Hein Holt', $author);
		$author = str_replace('4-Mat / 4mat', '4-Mat', $author);
		$author = str_replace('MCH / Michu', 'MCH', $author);

		$all_profiles[] = array(
			'fullname'	=> str_replace('_High Voltage SID Collection', 'HVSC', $row->fullname),
			'author'	=> $author
		);
	}

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'profiles' => $all_profiles));
?>