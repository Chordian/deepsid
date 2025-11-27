<?php
/**
 * DeepSID
 *
 * Run an utility PHP script (streamed into iframe).
 * 
 * For administrators only.
 */

require_once("class.account.php");

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

try {
	$allowedScripts = $account->GetDB()
	    ->query('SELECT script FROM admin_scripts WHERE script <> "" AND script IS NOT NULL')
    	->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
	$account->LogActivityError('run_execute.php', $e->getMessage());
	exit;
}

$script = $_GET['script'] ?? '';

if (!in_array($script, $allowedScripts)) {
    http_response_code(403);
    echo "Error: Script not allowed.";
    exit;
}

if (!file_exists($script)) {
    echo "Error: Script not found.";
    exit;
}

// ---------------------------------------------------------------------------
// BEGIN STREAMED HTML DOCUMENT FOR IFRAMED OUTPUT
// ---------------------------------------------------------------------------

header("Content-Type: text/html; charset=utf-8");
header("X-Accel-Buffering: no"); // Disable buffering on Nginx (safe on others)

// No output buffering — let output flow directly
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        margin: 0;
        padding: 15px;
        font-family: monospace;
        white-space: pre-wrap;   /* <-- THIS is what makes \n behave like <br> */
        overflow-y: auto;
    }
</style>
</head>
<body><?php

// ---------------------------------------------------------------------------
// RUN THE UTILITY SCRIPT (collector, etc.)
// This produces plain text with \n, which renders correctly due to pre-wrap.
// ---------------------------------------------------------------------------

include $script;

// ---------------------------------------------------------------------------
// FINISH AND NOTIFY PARENT PAGE
// ---------------------------------------------------------------------------
echo '<p style="margin-bottom:0;">✓ Script finished.</p>';

?><script>
// Notify parent top-panel
if (parent && parent.document) {
    parent.document.getElementById('status').innerText = 'Completed.';
}
</script></body></html>