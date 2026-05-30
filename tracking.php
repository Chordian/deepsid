<?php
/**
 * DeepSID / Tracking (overhauled)
 *
 * Tracks IP addresses visiting the web site in (semi) real time. Called
 * once upon load by 'index.php' and then periodically by 'main.js'.
 *
 * Output format in visitors.txt is kept identical:
 *   ip_address, user_agent, user_name, time_created, time_updated
 *
 * @used-by     index.php
 * @used-by     main.js
 */

require_once("php/class.account.php"); // Includes setup

define('TRACKFILE', 'visitors.txt');

// How long (in minutes) a visitor is considered "active"
define('ACTIVE_WINDOW_MINUTES', 30);

try {

    // Normalized "now", same style as the existing scripts
    $now = strtotime(date('Y-m-d H:i:s', strtotime(TIME_ADJUST)));

    $user_name  = $account->checkLogin() ? $account->userName() : '';
    $ip         = isset($_SERVER['REMOTE_ADDR'])      ? $_SERVER['REMOTE_ADDR']      : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT'])  ? $_SERVER['HTTP_USER_AGENT']  : '';

    // Basic sanity: if we don't have IP or UA, just bail quietly
    if ($ip === '' || $user_agent === '') {
        return;
    }

    // Skip external hits from Facebook (as in the original code),
    // but use strict comparison to avoid the 0 == false pitfall.
    if (strpos($user_agent, 'www.facebook.com') !== false) {
        return;
    }

    // Load existing visitors, if any
    $visitors = array();

    if (file_exists(TRACKFILE)) {
        $handle = fopen(TRACKFILE, 'r');
        if ($handle !== false) {
            while (($line = fgetcsv($handle)) !== false) {
                // Expecting 5 columns: ip, ua, user_name, time_created, time_updated
                if (count($line) < 5) {
                    // Broken / placeholder line; ignore it
                    continue;
                }

                $line_ip        = $line[0];
                $line_ua        = $line[1];
                $line_user      = $line[2];
                $time_created   = (int)$line[3];
                $time_updated    = (int)$line[4];

                // Drop expired visitors
                $minutes_since_update = round(($now - $time_updated) / 60);
                if ($minutes_since_update > ACTIVE_WINDOW_MINUTES) {
                    continue;
                }

                $visitors[] = array(
                    'ip_address'    => $line_ip,
                    'user_agent'    => $line_ua,
                    'user_name'     => $line_user,
                    'time_created'  => $time_created,
                    'time_updated'  => $time_updated,
                );
            }
            fclose($handle);
        }
    }

    // Look for current visitor (IP + UA combo)
    $exists         = false;
    $existing_index = null;

    foreach ($visitors as $index => $visitor) {
        if ($visitor['ip_address'] === $ip && $visitor['user_agent'] === $user_agent) {
            $exists        = true;
            $existing_index = $index;
            break;
        }
    }

    if ($exists && $existing_index !== null) {
        // Update existing visitor
        $visitors[$existing_index]['time_updated'] = $now;

        // If user_name changed (e.g. logged in after anon), update it
        if ($user_name !== '' && $visitors[$existing_index]['user_name'] !== $user_name) {
            $visitors[$existing_index]['user_name'] = $user_name;
        }

    } else {
        // Optional: keep only one entry per IP at a time, as in the old script
        // (skip repeated IP addresses if already present with any UA).
        $ip_already_present = false;
        foreach ($visitors as $v) {
            if ($v['ip_address'] === $ip) {
                $ip_already_present = true;
                break;
            }
        }

        if (!$ip_already_present) {
            // New visitor
            $visitors[] = array(
                'ip_address'    => $ip,
                'user_agent'    => $user_agent,
                'user_name'     => $user_name,
                'time_created'  => $now,
                'time_updated'  => $now,
            );
        }
    }

    // Write back to file with locking, preserving the same CSV structure
    $handle = fopen(TRACKFILE, 'c+');
    if ($handle === false) {
        throw new Exception('Unable to open tracking file for writing.');
    }

    // Obtain exclusive lock
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new Exception('Unable to obtain lock on tracking file.');
    }

    // Truncate and rewrite
    ftruncate($handle, 0);
    rewind($handle);

    foreach ($visitors as $visitor) {
        fputcsv($handle, array(
            $visitor['ip_address'],
            $visitor['user_agent'],
            $visitor['user_name'],
            $visitor['time_created'],
            $visitor['time_updated'],
        ));
    }

    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

} catch (Throwable $e) {
    // Log and reset tracking file gracefully
    if (isset($account)) {
        $account->logActivityError(basename(__FILE__), $e->getMessage());
    }
    @unlink(TRACKFILE);
    // Recreate with placeholder, as in the original script
    @file_put_contents(TRACKFILE, '0');
}
?>