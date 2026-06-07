<?php
/*
 * DeepSID
 * 
 * SEPARATE UTILITY SCRIPT
 * 
 * Detect missing database rows and missing/invalid fields.
 */

require_once("class.account.php"); // Includes setup

ini_set('memory_limit', '1024M'); // Should be safe
set_time_limit(0);

// -----------------------------------------------------------
// CONFIG
// -----------------------------------------------------------

$hvsc_root = __DIR__ . '/../../music/';

// Collections to scan
$collections = [
    "_Compute's Gazette SID Collection",
    "_Exotic SID Tunes Collection",
    "_High Voltage SID Collection",
    "_SID Happens"
];

// File extensions allowed per collection
$allowed_ext = [
    "_Compute's Gazette SID Collection" => 'mus',
    "_Exotic SID Tunes Collection"      => 'sid',
    "_High Voltage SID Collection"      => 'sid',
    "_SID Happens"                      => 'sid'
];

// Mandatory fields for *.sid rows
$required_fields = [
    'id'           		=> 'number',
    'collection_path'	=> 'text',
    'lengths'      		=> 'text',
    'type'         		=> 'text',
    'version'      		=> 'text',
    'player_type'  		=> 'text',
    'player_compat'		=> 'text',
    'clock_speed'  		=> 'text',
    'sid_model'    		=> 'text',
    'data_offset'  		=> 'number',
    'data_size'    		=> 'number',
    'load_addr'    		=> 'number',
    'init_addr'    		=> 'number',
    'play_addr'    		=> 'number',
    'subtunes'    		=> 'number',
    'start_subtune'		=> 'number',
    //'name'       		=> 'text',
    //'author'     		=> 'text',
    //'copyright'  		=> 'text',
    'new'          		=> 'number',
    'updated'      		=> 'number'
];

// If a row has more than this many deviations → we treat it as completely unprepared
$unprepared_threshold = 6;

// URL base
$base_url = $_SERVER['HTTP_HOST'] == LOCALHOST ? "http://chordian/deepsid/?file=" : "https://deepsid.chordian.net/?file=";

// -----------------------------------------------------------
// Helper: Build browser URL from collection_path
// -----------------------------------------------------------
function buildUrl($collection_path, $base_url) {
    // Remove leading underscore from collection name
    $parts = explode('/', $collection_path);
    $parts[0] = ltrim($parts[0], '_'); 

    // URL encode properly, but keep slashes
    $encoded = implode('/', array_map('rawurlencode', $parts));

    return $base_url . '/' . $encoded;
}

// -----------------------------------------------------------
// Helper: Check field types
// -----------------------------------------------------------
function hasCorrectType($value, $type) {
    if ($type === 'number') {
        return is_numeric($value);
    }
    return is_string($value) && $value !== '';
}

// -----------------------------------------------------------
// MAIN SCAN LOOP
// -----------------------------------------------------------

try {

	$db = $account->getDB();

	foreach ($collections as $collection) {

		$folder_path = $hvsc_root . $collection;

		if (!is_dir($folder_path)) {
			echo "[ERROR] Collection folder not found: $collection<br>";
			continue;
		}

		echo "<h3>Scanning: $collection</h3>";

		$ext = $allowed_ext[$collection];
		$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder_path));

		foreach ($rii as $file) {
			if ($file->isDir()) continue;

			if (strtolower($file->getExtension()) !== $ext) continue;

			// Build collection path used in 'files' table
			$relative_path = $collection . '/' . substr($file->getPathname(), strlen($folder_path) + 1);
			$relative_path = str_replace('\\', '/', $relative_path);

			// Query DB for the file
			$select = $db->prepare('SELECT * FROM files WHERE collection_path = :collection_path LIMIT 1');
			$select->execute([':collection_path' => $relative_path]);
			$row = $select->fetch(PDO::FETCH_ASSOC);

			$url = buildUrl($relative_path, $base_url);

			//--------------------------------------------
			// MUS files (only in Compute’s Gazette)
			//--------------------------------------------
			if ($ext === 'mus') {
				if (!$row) {
					echo "<div style='color:red'>[MISSING] $relative_path &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				}
				continue;
			}

			//--------------------------------------------
			// SID files (full validation)
			//--------------------------------------------
			if (!$row) {
				echo "<div style='color:red'>[MISSING ROW] $relative_path &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				continue;
			}

			// Check required fields
			$errors = [];

			foreach ($required_fields as $field => $type) {
				if (!isset($row[$field]) || $row[$field] === '' || $row[$field] === null) {
					$errors[] = "$field EMPTY";
					continue;
				}
				if (!hasCorrectType($row[$field], $type)) {
					$errors[] = "$field WRONG TYPE";
				}
			}

			// Too many deviations → row is unprepared
			if (count($errors) >= $unprepared_threshold) {
				echo "<div style='color:orange'>[UNPREPARED ROW] $relative_path &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				continue;
			}

			// Report specific field issues
			if (!empty($errors)) {
				echo "<div style='color:orange'>[FIELD ERRORS] $relative_path &nbsp; <a href='$url' target='_blank'>Open</a><br>";
				foreach ($errors as $e) {
					echo "&nbsp;&nbsp;- $e<br>";
				}
				echo "</div>";
			}
		}
	}

} catch (Exception $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());
}

echo "<p>Scanning completed.</p>";
?>