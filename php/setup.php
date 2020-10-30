<?php
/**
 * DeepSID
 *
 * Shared stuff used by other PHP scripts.
 */

define('LOCALHOST',			'chordian');

define('ROOT_HVSC',			$_SERVER['HTTP_HOST'] == LOCALHOST ? '/Users/jchuu/Music/HVSC' : '../hvsc');
define('HOST',              $_SERVER['HTTP_HOST'] == LOCALHOST ? 'http://chordian/deepsid/' : 'http://deepsid.chordian.net/');
define('COOKIE_HOST',       $_SERVER['HTTP_HOST'] == LOCALHOST ? 'localhost_deepsid' : 'deepsid.chordian.net');

define('JCH',               2);                         // Same user ID on both localhost and online

define('DB_LOCALHOST',		'deepsid');				    // MySQL connection strings for localhost
define('HOST_LOCALHOST',	'localhost');
define('PDO_LOCALHOST',		'mysql:host='.HOST_LOCALHOST.';dbname='.DB_LOCALHOST);
define('USER_LOCALHOST',	'root');
define('PWD_LOCALHOST',		'');

define('DB_ONLINE',			'[REDACTED]');              // MySQL connection strings for online (production)
define('HOST_ONLINE',		'[REDACTED]');
define('PDO_ONLINE',		'mysql:host='.HOST_ONLINE.';dbname='.DB_ONLINE);
define('USER_ONLINE',		'[REDACTED]');
define('PWD_ONLINE',		'[REDACTED]');

define('REMIX64_API',       'oDqHpvKZp2fM05JydWY2ylR8bCE8Y2PN');

define('DB_ERROR',          'A database error has been written to a log regularly monitored by Chordian.');

define('TIME_ADJUST',		'+1 hours');				// Added to all use of Date() to match correct time

// Use this instead of 'get_file_contents' as that sometimes returns empty strings from CSDb
function curl($url) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    // echo curl_error($ch);
    curl_close($ch);

    return $data;
}
?>
