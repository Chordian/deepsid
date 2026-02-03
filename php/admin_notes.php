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
			<textarea id="admin-notes-1" class="admin-notes-text" name="admin-notes-1"></textarea>
			<textarea id="admin-notes-2" class="admin-notes-text" name="admin-notes-2"></textarea>
			<textarea id="admin-notes-3" class="admin-notes-text" name="admin-notes-3"></textarea>
			<textarea id="admin-notes-4" class="admin-notes-text" name="admin-notes-4"></textarea>
			<textarea id="admin-notes-5" class="admin-notes-text" name="admin-notes-5"></textarea>
			<button id="admin-notes-save">Save</button><span id="admin-notes-info"></span>
	';

die(json_encode(array('status' => 'ok', 'html' => $html)));
?>