<?php
/**
 * DeepSID
 *
 * Get an array of JCH's ratings for all the folders in the specified letter
 * folder in the 'MUSICIANS' folder of HVSC.
 * 
 * This is used to filter folders according to a "quality" drop-down option.
 * 
 * @uses		$_GET['folder']
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

	$select = $db->prepare('SELECT id, fullname FROM hvsc_folders WHERE fullname LIKE :folder');
	$select->execute(array(':folder'=>ltrim($_GET['folder'], '/').'/%'));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$results = array();
	$ready = true;

	foreach ($select as $row) {

		// Get rating for this letter sub folder
		$select_rating = $db->query('SELECT rating FROM ratings WHERE user_id = '.JCH.' AND table_id = '.$row->id.' AND type = "FOLDER"');
		$select_rating->setFetchMode(PDO::FETCH_OBJ);
		$rating = $select_rating->rowCount() ? $select_rating->fetch()->rating : 0;

		$parts = explode('/', $row->fullname);
		if (count($parts) == 4) {
			$results[end($parts)] = $rating;
			if ($rating == 0) $ready = false;
		}
	}

} catch(PDOException $e) {
	die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
}
echo json_encode(array('status' => 'ok', 'ready' => $ready, 'results' => $results));
?>