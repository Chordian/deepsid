<?php
/**
 * DeepSID
 *
 * Track a behavior on the web site.
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

// @todo Check again if this works
if ($account->checkLogin() && ($account->userName() == 'JCH' || $account->userName() == 'Ratings'))
	exit();

try {
    $db = $account->getDB();

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