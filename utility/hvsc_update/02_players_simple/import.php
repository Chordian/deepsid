<?php

/*
 * Command-line PHP does not normally define HTTP_HOST because there is no
 * HTTP request. DeepSID's setup.php expects it, so provide a localhost value
 * before loading class.account.php.
 */
if (PHP_SAPI === 'cli' && !isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../../../php/class.account.php'; // Includes setup

/*
 * The BAT file passes the CSV filename as the first command-line argument.
 * Fall back to output.csv in this folder when running the script manually.
 */
$csvFile = $argv[1] ?? __DIR__ . '/output.csv';

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "ERROR: This script must be run from the command line.\n");
	exit(1);
}

if (!is_file($csvFile)) {
	fwrite(STDERR, "ERROR: File not found: $csvFile\n");
	exit(1);
}

$handle = null;

try {
	$db = $account->getDB();

	/*
	 * Create the import table if it does not already exist.
	 *
	 * The collection path is used as the primary key because every SID file
	 * should occur only once in the player-id output.
	 */
	$db->exec("
		CREATE TABLE IF NOT EXISTS files_import (
			collection_path VARCHAR(512) NOT NULL,
			player VARCHAR(255) NOT NULL,
			PRIMARY KEY (collection_path)
		) ENGINE=InnoDB
		  DEFAULT CHARSET=utf8mb4
		  COLLATE=utf8mb4_unicode_ci
	");

	/*
	 * Clear results from any previous import.
	 */
	$db->exec("TRUNCATE TABLE files_import");

	$insert = $db->prepare("
		INSERT INTO files_import (
			collection_path,
			player
		) VALUES (
			:collection_path,
			:player
		)
		ON DUPLICATE KEY UPDATE
			player = VALUES(player)
	");

	$handle = fopen($csvFile, 'rb');

	if ($handle === false) {
		throw new RuntimeException("Could not open file: $csvFile");
	}

	$imported = 0;
	$ignored = 0;
	$lineNumber = 0;

	$db->beginTransaction();

	while (($line = fgets($handle)) !== false) {
		$lineNumber++;

		/*
		 * Match output lines such as:
		 *
		 * MUSICIANS/Z/Zzap69/Thumb_Mitten.sid     Music_Assembler
		 *
		 * Match 1: HVSC-relative SID path
		 * Match 2: player name without whitespace
		 *
		 * The config line, blank lines, summary heading and player counts
		 * do not match this pattern and are therefore ignored.
		 */
		if (!preg_match('/^\s*(.+?\.sid)\s+(\S+)\s*$/i', $line, $matches)) {
			$ignored++;
			continue;
		}

		$relativePath = trim($matches[1]);
		$player = trim($matches[2]);

		/*
		 * Store paths using forward slashes, matching DeepSID's database.
		 */
		$relativePath = str_replace('\\', '/', $relativePath);

		$collectionPath =
			'_High Voltage SID Collection/' .
			ltrim($relativePath, '/');

		$insert->execute([
			':collection_path' => $collectionPath,
			':player' => $player
		]);

		$imported++;
	}

	if (!feof($handle)) {
		throw new RuntimeException(
			"An error occurred while reading line $lineNumber from: $csvFile"
		);
	}

	$db->commit();

	fclose($handle);
	$handle = null;

	echo PHP_EOL;
	echo "Import completed successfully." . PHP_EOL;
	echo "Imported rows: $imported" . PHP_EOL;
	echo "Ignored lines: $ignored" . PHP_EOL;
	echo "Source file: $csvFile" . PHP_EOL;

	exit(0);

} catch (Throwable $e) {
	if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
		$db->rollBack();
	}

	if (is_resource($handle)) {
		fclose($handle);
	}

	fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
	exit(1);
}
?>