<?php
/**
 * DeepSID
 *
 * Get a list of composer profiles for a drop-down box in the upload wizard.
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$all_profiles = array();

	$select = $db->query('SELECT fullname, name, handles FROM composers WHERE fullname LIKE "_High Voltage SID Collection/%" ORDER BY fullname');
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
		$author = str_replace('Riku Kangas', 'Riku Ö', $author);
		$author = str_replace('Wojciech Radziejewski', 'W. Radziejewski', $author);
		$author = str_replace('Psycho8580 / psych858o', 'psych858o', $author);
		$author = str_replace('Narciso Quintana Varo (Narcisound)', 'Narciso Quintana Varo', $author);
		$author = str_replace('Michael Philip Bridgewater', 'Michael P. Bridgewater', $author);
		$author = str_replace('Thomas Egeskov Petersen', 'Thomas E. Petersen', $author);
		$author = str_replace('Figge Wulff Wasberger', 'Figge Wasberger', $author);

		$all_profiles[] = array(
			'fullname'	=> str_replace('_High Voltage SID Collection', 'HVSC', $row->fullname),
			'author'	=> $author
		);
	}

} catch(PDOException $e) {
	$account->LogActivityError('upload_get_profiles.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'profiles' => $all_profiles));
?>