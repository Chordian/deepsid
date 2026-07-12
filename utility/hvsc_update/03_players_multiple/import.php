<?php

/*
 * PHP CLI does not normally define HTTP_HOST because there is no HTTP
 * request. DeepSID's setup.php expects it, so provide a localhost value.
 */
if (PHP_SAPI === 'cli' && !isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../../../php/class.account.php'; // Includes setup

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "ERROR: This script must be run from the command line." . PHP_EOL);
	exit(1);
}

/*
 * The BAT file passes the complete path to specific_players.csv.
 */
$csvFile = $argv[1] ?? null;

if ($csvFile === null) {
	fwrite(STDERR, "ERROR: No CSV file was specified." . PHP_EOL);
	exit(1);
}

if (!is_file($csvFile)) {
	fwrite(STDERR, "ERROR: File not found: $csvFile" . PHP_EOL);
	exit(1);
}

$handle = null;

try {
	$db = $account->getDB();

	/*
	 * Create the import table if it does not already exist.
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
	 * Clear any results left by the previous import stage.
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

	while (($row = fgetcsv($handle)) !== false) {
		$lineNumber++;

		/*
		 * Expected format:
		 *
		 * _High Voltage SID Collection/path/file.sid,Player/Variant
		 */
		if (count($row) !== 2) {
			$ignored++;
			echo "Ignored malformed line $lineNumber." . PHP_EOL;
			continue;
		}

		$collectionPath = trim($row[0]);
		$player = trim($row[1]);

		if ($collectionPath === '' || $player === '') {
			$ignored++;
			echo "Ignored incomplete line $lineNumber." . PHP_EOL;
			continue;
		}

		/*
		 * Normalize path separators to match DeepSID's database paths.
		 */
		$collectionPath = str_replace('\\', '/', $collectionPath);

		/*
		 * Only accept rows that look like complete HVSC SID paths.
		 */
		if (
			!str_starts_with(
				$collectionPath,
				'_High Voltage SID Collection/'
			) ||
			!str_ends_with(strtolower($collectionPath), '.sid')
		) {
			$ignored++;
			echo "Ignored invalid path on line $lineNumber: $collectionPath" . PHP_EOL;
			continue;
		}

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

	if ($imported === 0) {
		throw new RuntimeException(
			"No valid rows were found. The database transaction was cancelled."
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

	fwrite(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
	exit(1);
}
?>