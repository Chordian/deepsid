<?php
/**
 * DeepSID
 *
 * Clear the cache of a competition folder. This will make the folder ready to
 * be refreshed from the original CSDb release pages.
 * 
 * @uses		$_POST['competition']
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;
if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to clear the cache of a competition folder.')));

if (!isset($_POST['competition']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variable.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");
	
	// Get event ID
	$select = $db->prepare('SELECT event_id FROM competitions WHERE competition = :compo LIMIT 1');
	$select->execute(array(':compo'=>$_POST['competition']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	$event_id = $select->rowCount() ? $select->fetch()->event_id : 0;
	if (!$event_id)
		die(json_encode(array('status' => 'error', 'message' => 'Event ID not found for the "'.$_POST['competition'].'" competition folder')));

	// Now delete all entries with this event ID in the cache table
	$delete = $db->query('DELETE FROM competitions_cache WHERE event_id = '.$event_id);
	$account->LogActivity('User "'.$_SESSION['user_name'].'" cleared the cache for the "'.$_POST['competition'].'" competition folder');

} catch(PDOException $e) {
	$account->LogActivityError('csdb_compo_refresh.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status' => 'ok'));
?>