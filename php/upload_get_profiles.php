<?php
/**
 * DeepSID
 *
 * Get a list of profiles for a drop-down box in the upload wizard.
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

	$select = $db->query('SELECT fullname FROM hvsc_folders ORDER BY fullname');
	$select->setFetchMode(PDO::FETCH_OBJ);
	foreach($select as $row) {
		$folder = $row->fullname;
		if (strpos($folder, '/') && in_array(substr($folder, 0, 5), array('_High', '_Comp', '_Exot'))) {
			$folder = str_replace('_High Voltage SID Collection/', 'HVSC/', $folder);
			$folder = str_replace('_Compute\'s Gazette SID Collection/', 'CGSC/', $folder);
			$folder = str_replace('_Exotic SID Tunes Collection/', 'ESTC/', $folder);
			$all_profiles[] = $folder;
		}
	}

} catch(PDOException $e) {
	$account->LogActivityError('upload_get_profiles.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok', 'profiles' => $all_profiles));
?>