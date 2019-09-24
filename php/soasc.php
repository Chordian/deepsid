<?php
/**
 * DeepSID
 *
 * Build URL's for SOASC, then request through http://www.se2a1.net. The
 * HVSC version, which is part of the URL's, is retrieved from the database.
 * For CGSC, version 133 is just used for the time being (no other version
 * available at the mirrors anyway).
 * 
 * If the file is located in a custom folder, the script will try to find the
 * HVSC counterpart and play that instead, if it exists.
 * 
 * Request goes to http://www.se2a1.net/dl.php?d=/soasc/.../&url=1 which
 * then returns the full URL to a SOASC mirror site.
 * 
 * Mirrors for testing:
 * 
 * http://www.se2a1.net:40000/files/index.php
 * http://anorien.csc.warwick.ac.uk/mirrors/oakvalley/soasc/
 * http://ftp.acc.umu.se/mirror/media/Oakvalley/soasc/
 * 
 * @uses		$_GET['file']			fullname path to SID file
 * @uses		$_GET['sidModel']		a key in $soasc_models, or 'auto'
 * @uses		$_GET['subtune']		subtune number
 */

require_once("class.account.php"); // Includes setup

$soasc_models = array(
	'r2' 	=> 'MOS6581R2',
	'r3' 	=> 'MOS6581R3',
	'r4' 	=> 'MOS6581R4',
	'r5' 	=> 'CSG8580R5',
	'auto'	=> 'MOS6581R2', // May be set to 'r5' instead in the code below
);

if (!isset($_GET['file']) || !isset($_GET['sidModel']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'file\', \'sidModel\' and \'subtune\' as GET variables.')));

$debug = '';

$file = str_replace('hvsc', '', $_GET['file']);
$subtune = $_GET['subtune'] + 1;
$model = $soasc_models[$_GET['sidModel']];

function RequestURL($path) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'DeepSID');
	curl_setopt($ch, CURLOPT_URL, 'http://www.se2a1.net/dl.php?d=/soasc/'.$path.'&url=1');
	// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$data = curl_exec($ch);
	if ($data == false)
		// die(json_encode(array('status' => 'error', 'message' => curl_error($ch))));
		die(json_encode(array('status' => 'ok', 'url' => 'ERROR')));
	curl_close($ch);

	return $data;
}

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if ($_GET['sidModel'] == 'auto') {
		// Decide the SID model to use depending on the meta data setting in the database
		$select = $db->prepare('SELECT sidmodel FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>ltrim($file, '/')));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount())
			// Always bump to 6581 if not explicitly set to 8580
			$model = $select->fetch()->sidmodel == 'MOS8580' ? $soasc_models['r5'] : $soasc_models['r2'];
	}
	
	// What kind of folder is it?
	$root_folder = explode('/', $file)[1];
	if ($root_folder == "_Compute's Gazette SID Collection") {

		// Compute's Gazette SID Collection

		$url = RequestURL('cgsc/133/FLAC'.str_replace('.mus', '_T001.mus_'.$model.'.flac',
			str_replace("/_Compute's Gazette SID Collection", "", $file)));
		die(json_encode(array('status' => 'ok', 'url' => $url, 'model' => $model)));

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
		$select = $db->prepare('SELECT new, updated FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>ltrim($file, '/')));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$hvsc = 0;
		$ext = '.mp3';
		$format = '';

		if ($select->rowCount()) {
			$row = $select->fetch();
			$hvsc = $row->updated;
			if ($hvsc == 0) $hvsc = $row->new;
		}

		if ($hvsc < 50) {
			// SOASC started at HVSC version 49 with the MP3 format only
			$hvsc = 'hvsc/049';
			$subtune = str_pad($subtune, 2, '0', STR_PAD_LEFT);
		} else {
			// SOASC changed the path rules from version 50 and up while also favoring the FLAC format
			$hvsc = 'hvsc/0'.$hvsc;
			$format = '/FLAC';
			$ext = '.flac';
			$subtune = str_pad($subtune, 3, '0', STR_PAD_LEFT);
		}

		$url = RequestURL($hvsc.$format.str_replace('.sid', '_T'.$subtune.'.sid_'.$model.$ext,
			str_replace('/_High Voltage SID Collection', '', $file)));
		die(json_encode(array('status' => 'ok', 'url' => $url, 'model' => $model)));
	}

} catch(PDOException $e) {
	$account->LogActivityError('soasc.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
?>