<?php
/**
 * DeepSID
 *
 * Show the settings page in the 'Admin' tab.
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

$html = '';

try {
	$db = $account->GetDB();

	// Get all the admin settings
	$select = $db->query('SELECT * FROM admin_settings ORDER BY setting_key');
	$settings = $select->fetchAll(PDO::FETCH_OBJ);

	$html = '<h3>Settings</h3>';

	// Build the rows for each setting
	foreach ($settings as $s) {
		$html .= '
			<div class="setting">
				<div class="title">' . $s->setting_key . '</div>
				<span> ' . $s->description . '</span>
				<div class="value">' . htmlspecialchars($s->setting_value) . '</div>
				<div class="edit" data-type="' . $s->setting_type . '" data-options="' . $s->setting_options . '"></div>
			</div>
		';
	}

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
die(json_encode(array('status' => 'ok', 'html' => $html)));
?>