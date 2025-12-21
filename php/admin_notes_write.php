<?php
/**
 * DeepSID
 *
 * Update the notes file in the root of the DeepSID site.
 * 
 * For administrators only.
 * 
 * @uses		$_POST['text']
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

define('NOTESFILE', __DIR__ . '/../notes.txt');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
	http_response_code(403);	
	die("Direct access not permitted.");
}

if (!$account->CheckLogin() || $account->UserName() !== 'JCH' || $account->UserID() !== JCH) {
	http_response_code(403);
	die("This is for administrators only.");
}

// Normalize line endings
$text = str_replace(["\r\n", "\r"], "\n", (string)$_POST['text']);

// Write file (create or overwrite)
$result = file_put_contents(NOTESFILE, $text, LOCK_EX);

if ($result === false) {
	http_response_code(500);
	die("Failed to write notes file.");
}

die(json_encode(array('status' => 'ok')));
?>