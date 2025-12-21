<?php
/**
 * DeepSID
 *
 * Read the notes file in the root of the DeepSID site.
 * 
 * For administrators only.
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

$text = '';

// Read file if it exists
if (file_exists(NOTESFILE)) {
	$text = file_get_contents(NOTESFILE);
	if ($text === false) {
		http_response_code(500);
		die("Failed to read notes file.");
	}
}
die(json_encode(array('status' => 'ok', 'text' => $text)));
?>