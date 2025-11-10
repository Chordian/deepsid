<?php
/**
 * DeepSID
 *
 * Track a behavior on the web site.
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// @todo Check again if this works:
if ($account->CheckLogin() && ($account->UserName() == 'JCH' || $account->UserName() == 'Ratings'))
	exit();

exit(); //////////////// TURNED OFF FOR NOW

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

    $insert = $db->prepare('
        INSERT INTO tracking (event_type, target, ip, created_at)
        VALUES (:type, :target, :ip, NOW())
    ');
    $insert->execute([
        ':type'   => $_POST['type'] ?? 'unknown',
        ':target' => $_POST['target'] ?? null,
        ':ip'     => $_SERVER['REMOTE_ADDR'],
    ]);
} catch (Exception $e) {
    // Silently fail, don't echo to user
}
?>