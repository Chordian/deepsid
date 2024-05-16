<?php
/**
 * DeepSID
 *
 * Build URL's for Lemon's MP3 files.
 * 
 * CDN file example:
 * 
 * https://lemon.ams3.cdn.digitaloceanspaces.com/c64/music/hvsc/mp3/GAMES/M-R/Nebulus-01.mp3
 * 
 * @uses		$_GET['file']				fullname path to SID file
 * @uses		$_GET['subtune']			subtune number
 * 
 * @used-by		players.js
 */

require_once("class.account.php"); // Includes setup

define('LEMON', 'https://lemon.ams3.cdn.digitaloceanspaces.com/c64/music/hvsc/mp3');

if (!isset($_GET['file']) || !isset($_GET['subtune']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'file\' and \'subtune\' as GET variables.')));

$debug = '';

$file = str_replace('hvsc', '', $_GET['file']);
$subtune = $_GET['subtune'] + 1;

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// What kind of folder is it?
	$root_folder = explode('/', $file)[1];
	if ($root_folder == "_Compute's Gazette SID Collection") {

		die(json_encode(array('status' => 'error', 'message' => 'Compute\'s Gazette SID Collection is currently not supported.')));

	} else {

		// High Voltage SID Collection

		if (!in_array($root_folder, array('DEMOS', 'GAMES', 'MUSICIANS'))) {
			// It's in a custom folder - SOASC can't play this one, but get its hash (MD5)
			$select = $db->prepare('SELECT hash FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>ltrim($file, '/')));
			$select->setFetchMode(PDO::FETCH_OBJ);
			// Now get all SID files that share the same hash (MD5)
			$twins = $db->query('SELECT fullname FROM hvsc_files WHERE hash = "'.$select->fetch()->hash.'"');
			$twins->setFetchMode(PDO::FETCH_OBJ);

			// So, does this file have a duplicate in HVSC that SOASC can play?
			foreach ($twins as $twin) {
				$root_folder = explode('/', $twin->fullname)[0];
				if (in_array($root_folder, array('DEMOS', 'GAMES', 'MUSICIANS')))
					// Yep, here it is!
					$file = '/'.$twin->fullname;
			}
		}

		// Does this tune have multiple subtunes?
		$select = $db->prepare('SELECT subtunes FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>ltrim($file, '/')));
		$select->setFetchMode(PDO::FETCH_OBJ);
		
		$subtune = $select->fetch()->subtunes > 1
			? '-'.str_pad($subtune, 2, '0', STR_PAD_LEFT)
			: '';

		$url = LEMON.str_replace('.sid', $subtune.'.mp3', str_replace('/_High Voltage SID Collection', '', $file));
		die(json_encode(array('status' => 'ok', 'url' => $url)));
	}

} catch(PDOException $e) {
	$account->LogActivityError('soasc.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
?>