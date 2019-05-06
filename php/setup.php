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

define('DB_ERROR',          'A database error has been written to a log regularly monitored by Chordian.');

define('TIME_ADJUST',		'+1 hours');				// Added to all use of Date() to match correct time
?>