<?php
/**
 * DeepSID
 *
 * Rotates the activity and tags logs each month.
 *
 * This is called by the LogActivity() function.
 * 
 * @used-by		class.account.php
 */

declare(strict_types=1);

// Use local timezone
date_default_timezone_set('Europe/Copenhagen');

// Project root and log dirs
$projectRoot = realpath(__DIR__ . '/..');   // /deepsid
$logsDir     = $projectRoot . '/logs';
$activity    = $logsDir . '/activity.txt';
$tags        = $logsDir . '/tags.txt';
$archivesDir = $logsDir . '/archives';

// Ensure archives dir exists
if (!is_dir($archivesDir)) {
    @mkdir($archivesDir, 0775, true);
}

// Label for the month we just closed (e.g. 2025_09 on Oct 1st)
$label  = date('Y_m', strtotime('first day of previous month'));
$marker = $archivesDir . "/.rotated_{$label}.flag";

// How many monthly ZIPs to keep
$KEEP_MONTHS = 24;

// Already rotated this month?
if (file_exists($marker)) {
    return;
}

/**
 * Rotate one active log into *_YYYY_MM.txt.
 * Returns backup path or null.
 */
function rotate_one(string $activeFile, string $label): ?string {
    if (!file_exists($activeFile) || filesize($activeFile) === 0) {
        if (!file_exists($activeFile)) touch($activeFile);
        return null;
    }
    $backup = preg_replace('/\.txt$/', "_{$label}.txt", $activeFile);

    // Already exists? Just truncate active
    if (file_exists($backup)) {
        file_put_contents($activeFile, '');
        return $backup;
    }

    if (@rename($activeFile, $backup)) {
        touch($activeFile);
        return $backup;
    }

    if (@copy($activeFile, $backup)) {
        file_put_contents($activeFile, '');
        return $backup;
    }

    return null;
}

$backups = [];
$b1 = rotate_one($activity, $label); if ($b1) $backups[] = $b1;
$b2 = rotate_one($tags,     $label); if ($b2) $backups[] = $b2;

// Bundle backups into one ZIP inside archives/
if ($backups) {
    $zipPath = "{$archivesDir}/deepsid_logs_{$label}.zip";

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($backups as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->setArchiveComment("DeepSID logs {$label}");
            $zip->close();

            // Delete plain .txt backups
            foreach ($backups as $file) {
                @unlink($file);
            }
        }
    }
}

// Retention: keep only the latest N zips
$zips = glob($archivesDir . '/deepsid_logs_*.zip');
if ($zips && count($zips) > $KEEP_MONTHS) {
    sort($zips, SORT_STRING); // lexical sort works with YYYY_MM
    $toDelete = array_slice($zips, 0, count($zips) - $KEEP_MONTHS);
    foreach ($toDelete as $z) { @unlink($z); }
}

// Mark rotation done
touch($marker);
?>