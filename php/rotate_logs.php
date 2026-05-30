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
$project_root   = realpath(__DIR__ . '/..');   // /deepsid
$logs_dir       = $project_root . '/logs';
$activity       = $logs_dir . '/activity.txt';
$tags           = $logs_dir . '/tags.txt';
$archives_dir   = $logs_dir . '/archives';

// Ensure archives dir exists
if (!is_dir($archives_dir)) {
    @mkdir($archives_dir, 0775, true);
}

// Label for the month we just closed (e.g. 2025_09 on Oct 1st)
$label  = date('Y_m', strtotime('first day of previous month'));
$marker = $archives_dir . "/.rotated_{$label}.flag";

// How many monthly ZIPs to keep
$KEEP_MONTHS = 24;

// Already rotated this month?
if (file_exists($marker)) {
    return;
}

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Rotate one active log into *_YYYY_MM.txt.
 * Returns backup path or null.
 */
function rotateOne(string $active_file, string $label): ?string {
    if (!file_exists($active_file) || filesize($active_file) === 0) {
        if (!file_exists($active_file)) touch($active_file);
        return null;
    }
    $backup = preg_replace('/\.txt$/', "_{$label}.txt", $active_file);

    // Already exists? Just truncate active
    if (file_exists($backup)) {
        file_put_contents($active_file, '');
        return $backup;
    }

    if (@rename($active_file, $backup)) {
        touch($active_file);
        return $backup;
    }

    if (@copy($active_file, $backup)) {
        file_put_contents($active_file, '');
        return $backup;
    }

    return null;
}

// --------------------------------------------------------------------------
// START
// --------------------------------------------------------------------------

$backups = [];
$b1 = rotateOne($activity, $label); if ($b1) $backups[] = $b1;
$b2 = rotateOne($tags,     $label); if ($b2) $backups[] = $b2;

// Bundle backups into one ZIP inside archives/
if ($backups) {
    $zip_path = "{$archives_dir}/deepsid_logs_{$label}.zip";

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
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
$zips = glob($archives_dir . '/deepsid_logs_*.zip');
if ($zips && count($zips) > $KEEP_MONTHS) {
    sort($zips, SORT_STRING); // lexical sort works with YYYY_MM
    $to_delete = array_slice($zips, 0, count($zips) - $KEEP_MONTHS);
    foreach ($to_delete as $z) { @unlink($z); }
}

// Mark rotation done
touch($marker);
?>