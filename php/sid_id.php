<?php
/**
 * DeepSID
 *
 * Detect the type of player in a SID file.
 * 
 * This was inspired by the SIDId script by Cadaver that HVSC uses. This also
 * requires the external 'sidid.cfg' file with identification bytes.
 * 
 * Many things have been simplified for the PHP version. It doesn't handle the
 * many command-line options, and it also doesn't handle multiple files.
 * 
 * @used-by		upload_new.php
 */

define('T_END',	-1);
define('T_ANY',	-2);
define('T_AND',	-3);
define('T_NAME',-4);

/**
 * Try to identify the player used by this SID file.
 *
 * @param		string		$fullname			fullname of SID file
 *
 * @return		string							name of player (empty if not identified)
 */
function IdentifyPlayer($fullname) {

	$sid = file_get_contents($fullname);
	if (empty($sid)) return '';
	$sid_size = filesize($fullname);

	$player = '';
	$config_array = array();

	/***** READ CFG FILE *****/

	$cfg = fopen('../sidid.cfg', 'r');
	if ($cfg === false) return '';

	while (!feof($cfg)) {

		$line = trim(fgets($cfg));
		if (!empty($line)) {
			$strings = explode(' ', $line);
			$hex_array = array();

			foreach($strings as $string) {

				$token = T_NAME;
				if ($string == '??') $token = T_ANY;
				if (strtolower($string) == 'end') $token = T_END;
				if (strtolower($string) == 'and') $token = T_AND;
				if (strlen($string) == 2 && ctype_xdigit($string)) $token = hexdec($string);

				switch ($token) {
					case T_NAME:
						$player = $string;
						break;
						
					case T_END:
						$hex_array[] = T_END;
						$config_array[$player][] = $hex_array;
						break;

					default:
						$hex_array[] = $token;
				}
			}
		}
	}

	fclose($cfg);

	/**
	 * Parse SID file.
	 *
	 * @param		array		&$chars				reference to chars array
	 * @param		array		&$signature			reference to signature array
	 * @param		int			&$sid_size			reference to size of SID file
	 *
	 * @return		bool							true if identified
	 */
	function IdentifyBytes(&$chars, &$signature, &$sid_size) {

		$c = 1;
		$d = 0;
		$rc = 1;
		$rd = 0;

		while ($c < $sid_size) {
			if ($d == $rd) {
				if ($chars[$c] == $signature[$d]) {
					$rc = $c + 1;
					$d++;
				}
				$c++;
			} else {
				if ($signature[$d] == T_END) return true;
				if ($signature[$d] == T_AND) {
					$d++;
					while ($c < $sid_size) {
						if ($chars[$c] == $signature[$d]) {
							$rc = $c + 1;
							$rd = $d;
							break;
						}
						$c++;
					}
					if ($c >= $sid_size) return false;
				}
				if (($signature[$d] != T_ANY) && ($chars[$c] != $signature[$d])) {
					$c = $rc;
					$d = $rd;
				} else {
					$c++;
					$d++;
				}
			}
		}
		if ($signature[$d] == T_END) return true;
		return false;
	}

	$chars = unpack('C*', $sid);
	foreach ($config_array as $player => $signatures) {
		foreach ($signatures as $signature) {
			if (IdentifyBytes($chars, $signature, $sid_size))
				return $player;
		}
	}
	return '';
}
?>