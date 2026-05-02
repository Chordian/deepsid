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
	$db = $account->getDB();

	$all_profiles = array();

	$active_only = $_GET['active'] ? ' AND active = "'.date("Y").'" AND date_death = "0000-00-00"' : '';

	$select = $db->query('SELECT collection_path, full_name, short_name, handles, short_handle FROM composers WHERE collection_path LIKE "_High Voltage SID Collection/%" AND collection_path NOT LIKE "%/GROUPS/%"'.$active_only.' ORDER BY collection_path');
	$select->setFetchMode(PDO::FETCH_OBJ);

	foreach($select as $row) {
		$name = $row->full_name;
		$short_name = $row->short_name;
		$all_handles = explode(',', $row->handles);
		$latest_handle = trim(end($all_handles));
		$handle = strpos($latest_handle, '<del>') === false ? $latest_handle : '';
		$short_handle = $row->short_handle;
		if (!empty($short_handle)) $handle = $short_handle;

		// Name
		$author = '';
		if (!empty($short_name))
			$author = $short_name;
		else if (!empty($name) && $name != '?')
			$author = $name;

		// Handle
		if (empty($author) && !empty($handle))
			$author = $handle;
		else if (!empty($handle))
			$author .= ' ('.$handle.')';

		$all_profiles[] = array(
			'fullname'	=> str_replace('_High Voltage SID Collection', 'HVSC', $row->collection_path),
			'author'	=> $author
		);
	}

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'profiles' => $all_profiles));
?>