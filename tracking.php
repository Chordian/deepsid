<?php
/**
 * DeepSID / Tracking
 *
 * Tracks IP addresses visiting the web site in real time. Called once upon
 * load by 'index.php' and then every 5 minutes by 'main.js'.
 * 
 * The resulting file, 'visitors.txt', can then be examined and its data output
 * to e.g. another web page to keep track of visitors to the site.
 */

require_once("php/class.account.php"); // Includes setup

define('TRACKFILE', 'visitors.txt');

$now = strtotime(date('Y-m-d H:i:s', strtotime(TIME_ADJUST)));
$lines = array();
$user_name = $account->CheckLogin() ? $account->UserName() : '';

if (($handle = fopen(TRACKFILE, 'r')) != false) {
	// Get an array of lines and their CSV fields
	while (($line = fgetcsv($handle)) != false) {
		if (!isset($line[1])) break; // Empty file
		array_push($lines, array(
			'ip_address'	=> $line[0],
			'user_agent'	=> $line[1], // Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0
			'user_name'		=> $line[2], // Empty if not logged in
			'time_created'	=> $line[3],
			'time_updated'	=> $line[4], // Unix timestamps
		));
	}
}
fclose($handle);

// Delete expired lines
foreach($lines as $index => $visitor) {
	$minutes = round(($now - $visitor['time_updated']) / 60);
	if ($minutes > 10)
		unset($lines[$index]); // 10 minutes has passed; remove it
}

$exists = false;
foreach($lines as $index => $visitor) {
	if ($visitor['ip_address'] == $_SERVER['REMOTE_ADDR'] &&
		$visitor['user_agent'] == $_SERVER['HTTP_USER_AGENT']) {
		// Still at it; update time
		$lines[$index]['time_updated'] = $now;
		// Update user name too if changed
		$lines[$index]['user_name'] = $user_name;
		$exists = true;
		break;
	}
}

if (!$exists && strpos($_SERVER['HTTP_USER_AGENT'], 'www.facebook.com') == false) {
	// Add new visitor to array (except external hits from Facebook as they can be spammy)
	array_push($lines, array(
		'ip_address'	=> $_SERVER['REMOTE_ADDR'],
		'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
		'user_name'		=> $user_name,
		'time_created'	=> $now,
		'time_updated'	=> $now,
	));
}

if (($handle = fopen(TRACKFILE, 'w+')) != false) {
	// Write array to purged file
	foreach($lines as $visitor) {
		fputcsv($handle, $visitor);
	}
}
fclose($handle);
?>