<?php
/*
 * DeepSID
 * 
 * Automatic piggyback backups for One.com where cron is unavailable.
 * Backs up selected tables once per day, compresses with PHP (no exec),
 * and keeps a rolling history for a limited number of days.
 * 
 * @used-by		index.php
 */

$backupDir     = __DIR__ . '/../backups/';
$timestampFile = $backupDir . 'last_backup.txt';
$maxDays       = 7;

$tables = [
    'admin_settings',
    'competitions',
    'composers',
    'composers_links',
    'csdb',
    'folders_map',
    'groups',
    'hvsc_files',
    'hvsc_folders',
    'hvsc_lengths',
    'players_info',
    'players_lookup',
    'ratings',
    'sid_release_map',
    'symlists',
    'tags_info',
    'tags_lookup',
    'tracking',
    'uploads',
    'users',
    'youtube'
];

// Ensure directory exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Run at most once every 24 hours
if (file_exists($timestampFile) && (time() - filemtime($timestampFile)) < 86400) {
    return; // Already backed up today
}

// Touch the timestamp file *before* backup to avoid double-run during traffic burst
file_put_contents($timestampFile, "Backup started at " . date('Y-m-d H:i:s'));

require_once("class.account.php"); // Includes setup

try {
    $db = $account->GetDB();

    $date = date('Y-m-d');
    $backupFile = "$backupDir/backup_$date.sql";

    // Open file for writing raw SQL
    $fp = fopen($backupFile, 'w');

    foreach ($tables as $table) {
        // Create table structure
        $show = $db->query("SHOW CREATE TABLE `$table`");
        $create = $show->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";\n\n";
        fwrite($fp, $create);

        // Dump table data
        $rows = $db->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $vals = array_map([$db, 'quote'], array_values($row));
            $insert = "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
            fwrite($fp, $insert);
        }

        fwrite($fp, "\n\n");
    }

    fclose($fp);

    // GZIP compression
    $gzPath = "$backupFile.gz";
    $gz = gzopen($gzPath, 'w9');
    $fp = fopen($backupFile, 'r');

    // Chunked copy (low memory footprint)
    while (!feof($fp)) {
        gzwrite($gz, fread($fp, 1024 * 512));
    }

    fclose($fp);
    gzclose($gz);

    unlink($backupFile); // Remove uncompressed file

    // ROTATION — remove files older than X days
    foreach (glob("$backupDir/backup_*.sql.gz") as $file) {
        if (filemtime($file) < time() - ($maxDays * 86400)) {
            unlink($file);
        }
    }

} catch (Exception $e) {
    $account->LogActivityError(basename(__FILE__), $e->getMessage());
}
?>