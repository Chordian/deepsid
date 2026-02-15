<?php
/**
 * DeepSID
 * 
 * SEPARATE UTILITY SCRIPT
 * 
 * Walks all collections and counts files along the way. Each count is stored
 * in the relevant 'hvsc_folders' row in the database.
 * 
 * The folders themselves are not counted.
 */

require_once("class.account.php"); // Includes setup

$ROOT = realpath(__DIR__ . '/../hvsc');
if (!$ROOT)
    exit("ERROR: Cannot locate the music folder. Expected at ../hvsc relative to this script.\n");
$ROOT .= '/';

// ---------------------------------------------------------------------------
// CONFIG
// ---------------------------------------------------------------------------

// Folders that must be skipped entirely
$IGNORE_FOLDERS = [
    '_High Voltage SID Collection/DOCUMENTS',
    '_High Voltage SID Collection/Update',
    '_Compute\'s Gazette SID Collection/00_Commodore64',
    '_Compute\'s Gazette SID Collection/00_Documents',
    '_Compute\'s Gazette SID Collection/00_SIDfests',
    '_Compute\'s Gazette SID Collection/00_SidNews',
];

// File extensions to count in each collection
$ALLOWED_EXT = [
    '_SID Happens'                            => 'sid',
    '_High Voltage SID Collection'            => 'sid',
    '_Compute\'s Gazette SID Collection'      => 'mus',
    '_Exotic SID Tunes Collection'            => 'sid',
];

// ---------------------------------------------------------------------------
// Helper: Normalize slashes
// ---------------------------------------------------------------------------
function norm($path) {
    return str_replace('\\', '/', $path);
}

// ---------------------------------------------------------------------------
// Detect collection type from fullname
// ---------------------------------------------------------------------------
function getCollectionExt($fullname) {
    global $ALLOWED_EXT;

    foreach ($ALLOWED_EXT as $folder => $ext) {
        if (strpos($fullname, $folder) === 0) {
            return $ext;
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// Recursive counting
// ---------------------------------------------------------------------------
function countItems($absPath, $relativePath) {
    global $IGNORE_FOLDERS;

    $rel = norm($relativePath);

    // Skip ignored folders completely
    foreach ($IGNORE_FOLDERS as $skip) {
        if ($rel === $skip || strpos($rel, $skip . '/') === 0) {
            return 0;
        }
    }

    // Determine which file extension to count for THIS folder
    $ext = getCollectionExt($rel);

    $count = 0;

    $dh = @opendir($absPath);
    if (!$dh) return 0;

    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $full = $absPath . '/' . $file;
        $childRel = ltrim($rel . '/' . $file, '/');

        if (is_dir($full)) {
            // Always recurse, but never count the folder itself
            $count += countItems($full, $childRel);
        } else {
            // Count files only — no folder counts anywhere
            if ($ext !== null) {
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === $ext) {
                    $count++;
                }
            }
        }
    }

    closedir($dh);
    return $count;
}

// ---------------------------------------------------------------------------
// MAIN PROCESS
// ---------------------------------------------------------------------------
echo "<pre>";

try {

    $db = $account->GetDB();

	$q = $db->query('SELECT id, fullname FROM hvsc_folders ORDER BY fullname');
	$q->setFetchMode(PDO::FETCH_OBJ);

	foreach ($q as $row) {

		$fullname = $row->fullname;				// e.g. "_High Voltage SID Collection/MUSICIANS/M/Merlin"
		$absPath  = $ROOT . $fullname;

		if (!is_dir($absPath)) {
			echo "Missing dir: $fullname\n";
			continue;
		}

		echo "Processing: $fullname ... ";

		$count = countItems($absPath, $fullname);

		echo "count = $count\n";

		$u = $db->prepare("UPDATE hvsc_folders SET files = :c WHERE id = :id");
		$u->execute([
			':c'  => $count,
			':id' => $row->id
		]);
	}

} catch (Exception $e) {
    $account->LogActivityError(basename(__FILE__), $e->getMessage());
}

echo "\n✓ All folder/file counts updated.\n";
echo "</pre>";
?>