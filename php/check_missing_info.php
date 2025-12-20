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

$hvscRoot = __DIR__ . '/../hvsc/';

// Collections to scan
$collections = [
    "_Compute's Gazette SID Collection",
    "_Exotic SID Tunes Collection",
    "_High Voltage SID Collection",
    "_SID Happens"
];

// File extensions allowed per collection
$allowedExt = [
    "_Compute's Gazette SID Collection" => 'mus',
    "_Exotic SID Tunes Collection"      => 'sid',
    "_High Voltage SID Collection"      => 'sid',
    "_SID Happens"                      => 'sid'
];

// Mandatory fields for *.sid rows
$requiredFields = [
    'id'           	=> 'number',
    'fullname'     	=> 'text',
    'lengths'      	=> 'text',
    'type'         	=> 'text',
    'version'      	=> 'text',
    'playertype'   	=> 'text',
    'playercompat' 	=> 'text',
    'clockspeed'   	=> 'text',
    'sidmodel'     	=> 'text',
    'dataoffset'   	=> 'number',
    'datasize'     	=> 'number',
    'loadaddr'     	=> 'number',
    'initaddr'     	=> 'number',
    'playaddr'     	=> 'number',
    'subtunes'     	=> 'number',
    'startsubtune'	=> 'number',
    //'name'         	=> 'text',
    //'author'       	=> 'text',
    //'copyright'    	=> 'text',
    'new'          	=> 'number',
    'updated'      	=> 'number'
];

// If a row has more than this many deviations → we treat it as completely unprepared
$unpreparedThreshold = 6;

// URL base
$baseURL = $_SERVER['HTTP_HOST'] == LOCALHOST ? "http://chordian/deepsid/?file=" : "https://deepsid.chordian.net/?file=";

// -----------------------------------------------------------
// Helper: Build browser URL from fullname
// -----------------------------------------------------------
function buildUrl($fullname, $baseURL) {
    // Remove leading underscore from collection name
    $parts = explode('/', $fullname);
    $parts[0] = ltrim($parts[0], '_'); 

    // URL encode properly, but keep slashes
    $encoded = implode('/', array_map('rawurlencode', $parts));

    return $baseURL . '/' . $encoded;
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

	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	foreach ($collections as $collection) {

		$folderPath = $hvscRoot . $collection;

		if (!is_dir($folderPath)) {
			echo "[ERROR] Collection folder not found: $collection<br>";
			continue;
		}

		echo "<h3>Scanning: $collection</h3>";

		$ext = $allowedExt[$collection];
		$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));

		foreach ($rii as $file) {
			if ($file->isDir()) continue;

			if (strtolower($file->getExtension()) !== $ext) continue;

			// Build fullname used in hvsc_files table
			$relativePath = $collection . '/' . substr($file->getPathname(), strlen($folderPath) + 1);
			$relativePath = str_replace('\\', '/', $relativePath);

			// Query DB for the file
			$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
			$select->execute([':fullname' => $relativePath]);
			$row = $select->fetch(PDO::FETCH_ASSOC);

			$url = buildUrl($relativePath, $baseURL);

			//--------------------------------------------
			// MUS files (only in Compute’s Gazette)
			//--------------------------------------------
			if ($ext === 'mus') {
				if (!$row) {
					echo "<div style='color:red'>[MISSING] $relativePath &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				}
				continue;
			}

			//--------------------------------------------
			// SID files (full validation)
			//--------------------------------------------
			if (!$row) {
				echo "<div style='color:red'>[MISSING ROW] $relativePath &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				continue;
			}

			// Check required fields
			$errors = [];

			foreach ($requiredFields as $field => $type) {
				if (!isset($row[$field]) || $row[$field] === '' || $row[$field] === null) {
					$errors[] = "$field EMPTY";
					continue;
				}
				if (!hasCorrectType($row[$field], $type)) {
					$errors[] = "$field WRONG TYPE";
				}
			}

			// Too many deviations → row is unprepared
			if (count($errors) >= $unpreparedThreshold) {
				echo "<div style='color:orange'>[UNPREPARED ROW] $relativePath &nbsp; <a href='$url' target='_blank'>Open</a></div>";
				continue;
			}

			// Report specific field issues
			if (!empty($errors)) {
				echo "<div style='color:orange'>[FIELD ERRORS] $relativePath &nbsp; <a href='$url' target='_blank'>Open</a><br>";
				foreach ($errors as $e) {
					echo "&nbsp;&nbsp;- $e<br>";
				}
				echo "</div>";
			}
		}
	}

} catch (Exception $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
}

echo "<p>Scanning completed.</p>";
?>