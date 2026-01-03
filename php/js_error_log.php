<?php
/*
 * DeepSID
 * 
 * Logs JavaScript errors.
 * 
 * @used-by		main.php
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

$line =
	"[{$entry['date']}] {$entry['type']}\n" .
	"Message: {$entry['message']}\n" .
	"Source: {$entry['source']} ({$entry['line']}:{$entry['column']})\n" .
	"IP: {$entry['ip']}\n" .
	// "Stack:\n{$entry['stack']}\n" . // This is extremely verbose
	"\n";

file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);
?>