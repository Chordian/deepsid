<?php
/**
 * DeepSID
 *
 * Calculates the difference between the current time and the timestamp that
 * was specified, then returns the difference in minutes.
 * 
 * This could have been done in jQuery, but the original timestamp was saved by
 * the 'soasc_status.php' and I wanted to make sure this was solid, including
 * the use of daylight saving time.
 * 
 * @uses		$_GET['timestamp']			time in 'YYYY-MM-DD HH:MM:SS' format
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

if (!isset($_GET['timestamp']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'timestamp\' as a GET variable.')));

$now = strtotime(date('Y-m-d H:i:s', strtotime(TIME_ADJUST)));
$timestamp = strtotime($_GET['timestamp']);

die(json_encode(array('status' => 'ok', 'minutes' => ($now - $timestamp) / 60)));
?>