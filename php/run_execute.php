<?php
/**
 * DeepSID
 *
 * Run an utility PHP script.
 * 
 * For administrators only.
 * 
 * @used-by		run_shell.php
 */

require_once("class.account.php"); // Includes setup

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

// --- CONFIG ---
$allowedScripts = [
    'check_missing_info.php',
    'test_script.php',
    'update_counts_all.php',
];

$script = $_POST['script'] ?? '';

if (!in_array($script, $allowedScripts)) {
    http_response_code(403);
    echo "Error: Script not allowed.";
    exit;
}

if (!file_exists($script)) {
    echo "Error: Script not found.";
    exit;
}

// Capture output safely
ob_start();
include $script;
$output = ob_get_clean();

echo $output;
?>