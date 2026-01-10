<?php
/*
 * DeepSID
 * 
 * Logs JavaScript errors.
 * 
 * @used-by main.php
 */

require_once 'setup.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	exit;
}

$logfile = __DIR__ . '/../logs/js_errors.log';

$entry = [
	'date'    => date('Y-m-d H:i:s'),
	'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
	'type'    => $_POST['type'] ?? '',
	'message' => $_POST['message'] ?? '',
	'source'  => $_POST['source'] ?? '',
	'line'    => $_POST['line'] ?? '',
	'column'  => $_POST['column'] ?? '',
	'stack'   => $_POST['stack'] ?? ''
];

/* Ignore 'Script error.' noise */
if (
	$entry['message'] === 'Script error.' &&
	(empty($entry['source']) || $entry['source'] === '') &&
	(int)$entry['line'] === 0 &&
	(int)$entry['column'] === 0
) {
	return;
}

/* Ignore Google bot errors */
if (
	preg_match('/^66\.249\./', $entry['ip']) &&
	strpos($entry['message'], 'is not defined') !== false
) {
	return;
}

/*
 * Filter known browser / extension noise
 * (safe to ignore, not DeepSID bugs)
 */
$noisePatterns = [
	'_AutofillCallbackHandler',
	'Talisman extension',
	'chrome-extension://',
	'moz-extension://'
];

foreach ($noisePatterns as $needle) {
	if (
		($entry['message'] && strpos($entry['message'], $needle) !== false) ||
		($entry['source']  && strpos($entry['source'],  $needle) !== false)
	) {
		return;
	}
}

$line =
	"[{$entry['date']}] {$entry['type']}\n" .
	"Message: {$entry['message']}\n" .
	"Source: {$entry['source']} ({$entry['line']}:{$entry['column']})\n" .
	"IP: {$entry['ip']}\n" .
	// "Stack:\n{$entry['stack']}\n" . // Extremely verbose
	"\n";

file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);
?>