<?php
/**
 * DeepSID
 * 
 * SEPARATE UTILITY SCRIPT
 * 
 * Walks all collections and counts files along the way. Each count is stored
 * in the relevant 'folders' row in the database.
 * 
 * The folders themselves are not counted.
 */

require_once("class.account.php"); // Includes setup

$ROOT = realpath(__DIR__ . '/../../music');
if (!$ROOT)
    die("ERROR: Cannot locate the music folder. Expected at '../music' relative to this script.\n");
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

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

// Helper: Normalize slashes
function norm($path) {
    return str_replace('\\', '/', $path);
}

// Detect collection type from collection path
function getCollectionExt($collection_path) {
    global $ALLOWED_EXT;

    foreach ($ALLOWED_EXT as $folder => $ext) {
        if (strpos($collection_path, $folder) === 0) {
            return $ext;
        }
    }
    return null;
}

// Recursive counting
function countItems($abs_path, $relative_path) {
    global $IGNORE_FOLDERS;

    $rel = norm($relative_path);

    // Skip ignored folders completely
    foreach ($IGNORE_FOLDERS as $skip) {
        if ($rel === $skip || strpos($rel, $skip . '/') === 0) {
            return 0;
        }
    }

    // Determine which file extension to count for THIS folder
    $ext = getCollectionExt($rel);

    $count = 0;

    $dh = @opendir($abs_path);
    if (!$dh) return 0;

    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $full = $abs_path . '/' . $file;
        $child_rel = ltrim($rel . '/' . $file, '/');

        if (is_dir($full)) {
            // Always recurse, but never count the folder itself
            $count += countItems($full, $child_rel);
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

// --------------------------------------------------------------------------
// START
// --------------------------------------------------------------------------

echo "<pre>";

try {

    $db = $account->getDB();

	$q = $db->query('SELECT id, collection_path FROM folders ORDER BY collection_path');
	$q->setFetchMode(PDO::FETCH_OBJ);

	foreach ($q as $row) {

		$collection_path = $row->collection_path;   // e.g. "_High Voltage SID Collection/MUSICIANS/M/Merlin"
		$abs_path  = $ROOT . $collection_path;

		if (!is_dir($abs_path)) {
			echo "Missing dir: $collection_path\n";
			continue;
		}

		echo "Processing: $collection_path ... ";

		$count = countItems($abs_path, $collection_path);

		echo "count = $count\n";

		$u = $db->prepare("UPDATE folders SET files = :c WHERE id = :id");
		$u->execute([
			':c'  => $count,
			':id' => $row->id
		]);
	}

} catch (Exception $e) {
    $account->logActivityError(basename(__FILE__), $e->getMessage());
}

echo "\n✓ All folder/file counts updated.\n";
echo "</pre>";
?>