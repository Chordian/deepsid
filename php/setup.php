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

define('HVSC_VERSION',      '75');

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

		case 'creamd':
		case 'zyron':
		case 'dishy':
		case 'the_communist':
		case 'hedning':
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
		case 'mace':
		case 'wacek':
		case 'a3':
        case 'ian_coog':
        case 'jch':
		case 'acidchild':
        case 'scooby':
			$color = ' class="forum-user-trusted" title="Trusted User"';    // Color for trusted users
			break;

		default:
			$color = '';                                                    // Default color for silly mortals
	}
	return $color;
}
?>