<?php
/**
 * DeepSID
 *
 * Get a list of HVSC profiles for a drop-down box in the upload wizard.
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

	$select = $db->query('SELECT fullname FROM hvsc_folders WHERE fullname LIKE "_High Voltage SID Collection/%" ORDER BY fullname');
	$select->setFetchMode(PDO::FETCH_OBJ);
	foreach($select as $row)
		$all_profiles[] = str_replace('_High Voltage SID Collection', 'HVSC', $row->fullname);

} catch(PDOException $e) {
	$account->LogActivityError('upload_get_profiles.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'profiles' => $all_profiles));
?>