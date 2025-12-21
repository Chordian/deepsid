<?php
/**
 * DeepSID
 *
 * Show the notes page in the 'Admin' tab.
 * 
 * For administrators only.
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");
if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

$html = '<h3>Notes</h3>
			<h4>General</h4>
			<textarea id="admin-notes-text" name="admin-notes"></textarea>
			<button id="admin-notes-save">Save</button><span id="admin-notes-info"></span>
	';

die(json_encode(array('status' => 'ok', 'html' => $html)));
?>