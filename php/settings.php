<?php
/**
 * DeepSID
 *
 * Read and optionally write settings to the user's account.
 * 
 * If a setting is not specified, the script just returns the current state of
 * the settings for the logged in user.
 * 
 * Settings that can be specified for saving:
 * 
 * @uses		$_POST['skiptune']		0 or 1
 * @uses		$_POST['marktune']		0 or 1
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$user_id = $account->CheckLogin() ? $account->UserID() : 0;

if (!$user_id)
	die(json_encode(array('status' => 'error', 'message' => 'You must be logged in to adjust settings.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First get all the user's settings
	$select = $db->query('SELECT flags FROM users WHERE id = '.$user_id);
	$select->setFetchMode(PDO::FETCH_OBJ);
	$settings = unserialize($select->fetch()->flags);

	if (!$settings)
		// First time
		$settings = array(
			'skiptune' =>	0,
			'marktune' =>	0,
		);

	// Adjust settings
	if (!empty($_POST['skiptune'])) $settings['skiptune'] = (int)$_POST['skiptune'];
	if (!empty($_POST['marktune'])) $settings['marktune'] = (int)$_POST['marktune'];

	if ($_POST) {
		// Store the settings
		$update = $db->prepare('UPDATE users SET flags = :flags WHERE id = '.$user_id);
		$update->execute(array(':flags' => serialize($settings)));
		if ($update->rowCount() == 0)
			die(json_encode(array('status' => 'error', 'message' => 'Could not update yours settings')));
	}

} catch(PDOException $e) {
	$error_msg = $e->getMessage();
	$account->LogActivity('User "'.$_SESSION['user_name'].'" invoked a database error in the "settings.php" script:');
	$account->LogActivity(' '.$error_msg);
	die(json_encode(array('status' => 'error', 'message' => $error_msg)));
}

echo json_encode(array('status' => 'ok', 'settings' => $settings));
?>