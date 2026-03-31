<?php
/**
 * DeepSID
 *
 * Shared stuff used by other PHP scripts.
 */

define('LOCALHOST',			'chordian');

define('ROOT_HVSC',			$_SERVER['HTTP_HOST'] == LOCALHOST ? '/Users/jchuu/Music/HVSC' : '../hvsc');
define('HOST',              $_SERVER['HTTP_HOST'] == LOCALHOST ? 'http://chordian/deepsid/' : '//deepsid.chordian.net/');
define('COOKIE_HOST',       $_SERVER['HTTP_HOST'] == LOCALHOST ? 'localhost_deepsid' : 'deepsid.chordian.net');

define('HVSC_VERSION',      '84');
define('CGSC_VERSION',      '147');

define('JCH',               2);                         // Same user ID on both localhost and online
define('USER_RATINGS',      3);                         // Same user ID on both localhost and online

define('DB_ERROR',          'A database error has been written to a log regularly monitored by Chordian.');

define('TIME_ADJUST',		'+1 hours');				// Added to all use of Date() to match correct time

// Handle configuration file

$host = $_SERVER['HTTP_HOST'] ?? '';
$isCli = (PHP_SAPI === 'cli');
$isLocal = $isCli || in_array($host, [LOCALHOST, 'localhost', '127.0.0.1'], true);

$generalFile = __DIR__ . '/../config/general.php';
$envFile  = __DIR__ . ($isLocal ? '/../config/localhost.php' : '/../config/online.php');

if (!file_exists($generalFile) || !file_exists($envFile)) {
    die('Missing configuration file.');
}

$generalConfig = require $generalFile;
$envConfig  = require $envFile;

$config = array_merge($generalConfig, $envConfig);      // See PHP files in 'config' folder for keys

/**
 * Use this instead of 'file_get_contents' as that sometimes returns empty
 * strings from CSDb.
 *
 * @param	    string		$url                URL to obtain data from
 *
 * @return	    string		$data               data from the URL
 */
function curl($url) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_REFERER, 'https://csdb.dk/'); // Added 2026-03-28

    $data = curl_exec($ch);
    // clog($ch);
    curl_close($ch);

    return $data;
}

/**
 * Convert a CSDb user name to a more file friendly format.
 *
 * @param	    string		$name               CSDb user name
 *
 * @return	    string		$fn                 file friendly user name
 */
function GetFriendlyName($name) {

	$fn = preg_replace('/[^a-z0-9]+/i', ' ', $name);
	$fn = trim($fn);
	$fn = str_replace(' ', '_', $fn);
	$fn = strtolower($fn);

	return $fn;
}

/**
 * Return the avatar image path for the user, if one exists.
 *
 * @param	    int 		$id                 ID for a CSDb scener (can be 0)
 * @param	    string		$name               name of scener/composer
 * @param	    string		$hvsc_folder        the scener's HVSC folder (can be empty)
 *
 * @return	    string		$avatar             image path for avatar
 */
function GetAvatar($id, $name, $hvsc_folder) {

    if (!empty($hvsc_folder)) {

        // Figure out the name of the thumbnail (if it exists) for the composer
        $fn = str_replace('_High Voltage SID Collection/', '', $hvsc_folder);
        $fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
        $fn = strtolower(str_replace('/', '_', $fn));
        $avatar = 'images/composers/'.$fn.'.jpg';
        if (!file_exists('../'.$avatar)) $avatar = 'images/composer.png';

    } else {

        // It's a scener, not a composer
        $undefined_image = $avatar = 'images/composer.png';

        if ($id) {
            // Try to use the ID number to fetch image (reliable)
            // Example: "000848_Rastlin.jpg" - the part after "_" can be changed and it will still work (but see below)
            $file = glob($_SERVER['DOCUMENT_ROOT'].'/deepsid/images/csdb/'.str_pad($id, 6, '0', STR_PAD_LEFT).'_*.jpg');
            $avatar = isset($file[0]) ? substr($file[0], strpos($file[0], 'images/csdb/')) : $undefined_image;
        }

        if ($avatar == $undefined_image) {
            // Must use handle name to figure it out (not entirely reliable)
            $fn = GetFriendlyName($name);

            // First try to match the handle name after the 6-digit ID part
            $file = glob($_SERVER['DOCUMENT_ROOT'].'/deepsid/images/csdb/??????_'.$fn.'.jpg');
            if (isset($file[0]))
                $avatar = substr($file[0], strpos($file[0], 'images/csdb/'));
            else {
                // Try again with the avatars for composers
                $avatar = '/deepsid/images/composers/musicians_'.$fn[0].'_'.$fn.'.jpg';
                if (!file_exists($_SERVER['DOCUMENT_ROOT'].$avatar)) {
                    if (strpos($fn, '_') !== false) {
                        // Try again with the avatars for composers where the first and second names are swapped
                        $parts = explode('_', $fn);
                        $avatar = '/deepsid/images/composers/musicians_'.$parts[1][0].'_'.$parts[1].'_'.$parts[0].'.jpg';
                        if (!file_exists($_SERVER['DOCUMENT_ROOT'].$avatar))
                            $avatar = $undefined_image;
                        else if ($_SERVER['HTTP_HOST'] != LOCALHOST)
                            $avatar = substr($avatar, 8);
                    } else
                        $avatar = $undefined_image;
                } else if ($_SERVER['HTTP_HOST'] != LOCALHOST)
                    $avatar = substr($avatar, 8);
            }
        }
    }

	return $avatar;
}

/**
 * Return the correct color code for the CSDb user.
 * 
 * IF YOU UPDATE THIS THEN REMEMBER TO DO SO IN CSHELLDB TOO!
 *
 * @param	    string		$name               CSDb scener handle
 *
 * @return	    string		$color              a HTML snippet
 */
function GetUserColor($name) {

	$fn = GetFriendlyName($name);

	// @link https://csdb.dk/help.php?section=intro
	switch ($fn) {
		case 'perff':
		case 'cyberbrain':
		case 'kbs':
		case 'celtic':
			$color = ' class="forum-user-admin" title="Administrator"';     // Color for site admins
			break;

		case 'raistlin':
		case 'didi':
		case 'moloch':
		case 'e_g':
		case 'count_zero':
		case 'bugjam':
        case 'theryk':
			$color = ' class="forum-user-moderator" title="Moderator"';     // Color for site moderators
			break;

		case 'cba':
		case 'fred':
		case 'dymo':
		case 'hedning':
		case 'mace':
		case 'wacek':
		case 'a3':
        case 'ian_coog':
        case 'jch':
		case 'acidchild':
        case 'scooby':
        case 'zyron':
			$color = ' class="forum-user-trusted" title="Trusted User"';    // Color for trusted users
			break;

		default:
			$color = '';                                                    // Default color for silly mortals
	}
	return $color;
}

/**
 * Log to the browser console log.
 * 
 * NOTE: A jQuery snippet logs it in main.js.
 */
function clog(string $key, $value) {
    if (headers_sent()) return;

    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Headers should be ASCII-ish and not huge
    $json = substr($json, 0, 4000);

    header("X-DeepSID-Debug-$key: " . $json);
}
?>