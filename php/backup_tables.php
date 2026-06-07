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

require_once("class.account.php"); // Includes setup

$backup_dir     = __DIR__ . '/../backups/';
$timestamp_file = $backup_dir . 'last_backup.txt';

$max_days = (int)$account->getAdminSetting('db_backup_retention_days');
if ($max_days < 1) $max_days = 7;

$tables = [
    'admin_settings',
    'competitions',
    'composers',
    'composers_links',
    'csdb',
    'folders_map',
    'groups',
    'files',
    'folders',
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
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Run at most once every 24 hours
if (file_exists($timestamp_file) && (time() - filemtime($timestamp_file)) < 86400) {
    return; // Already backed up today
}

// Touch the timestamp file *before* backup to avoid double-run during traffic burst
file_put_contents($timestamp_file, "Backup started at " . date('Y-m-d H:i:s'));

try {
    $db = $account->getDB();

    $date = date('Y-m-d');
    $backup_file = "$backup_dir/backup_$date.sql";

    // Open file for writing raw SQL
    $fp = fopen($backup_file, 'w');

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
    $gz_path = "$backup_file.gz";
    $gz = gzopen($gz_path, 'w9');
    $fp = fopen($backup_file, 'r');

    // Chunked copy (low memory footprint)
    while (!feof($fp)) {
        gzwrite($gz, fread($fp, 1024 * 512));
    }

    fclose($fp);
    gzclose($gz);

    unlink($backup_file); // Remove uncompressed file

    // ROTATION — remove files older than X days
    foreach (glob("$backup_dir/backup_*.sql.gz") as $file) {
        if (filemtime($file) < time() - ($max_days * 86400)) {
            unlink($file);
        }
    }

} catch (Exception $e) {
    $account->logActivityError(basename(__FILE__), $e->getMessage());
}
?>