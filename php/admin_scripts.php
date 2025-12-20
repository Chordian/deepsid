<?php
/**
 * DeepSID
 *
 * Show the scripts page in the 'Admin' tab.
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

$html = $section = '';
$baseURL = $_SERVER['HTTP_HOST'] == LOCALHOST ? "http://chordian/deepsid/php/" : "https://deepsid.chordian.net/php/";

try {
	$db = $account->GetDB();

	// Get all the admin scripts rows
	$select = $db->query('SELECT * FROM admin_scripts ORDER BY section, name');
	$scripts = $select->fetchAll(PDO::FETCH_OBJ);

	$html = '<h3>Scripts</h3>';

	// Build the rows for each setting
	foreach ($scripts as $s) {
		if ($s->section !== $section) {
			$section = $s->section;
			$html .= '<h4>' . $section . '</h4>';
		}

		$html .= '
			<div class="script">
				<div class="name">' . $s->name . '</div>
				<span>' . $s->description . '</span>
				<button class="run-script" data-script="'.$baseURL.'run_shell.php?script=' . $s->script . '" title="' . $baseURL.$s->script . '">RUN</button>
			</div>
		';
	}

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
die(json_encode(array('status' => 'ok', 'html' => $html)));
?>