<?php
/**
 * DeepSID / REST API v1
 *
 * Simple REST API for use by external sources.
 * 
 * @uses		$_GET['file']				file or folder
 * @uses		$_GET['folder']				a folder and its folders
 * @uses		$_GET['profile']			composer
 * @uses		$_GET['players']			all players/editors
 * 
 * Don't add support for Compute's Gazette files; there is no useful info in
 * the database for those tunes.
 */

require_once("../php/setup.php");

header("Content-Type:application/json");

$response = array();

// @link https://stackoverflow.com/a/14270161/2242348
if (isset($_SERVER['HTTPS']) &&
		($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
	$protocol = 'https://';
} else {
	$protocol = 'http://';
}

try {

	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if (isset($_GET['file']) && $_GET['file'] != "") {

		$file = trim($_GET['file'], '/');
		$fullname = '_High Voltage SID Collection/'.$file;

		if (strtolower(substr($file, -4)) == ".mus" || strtolower(substr($file, 0, 9)) == "compute's") {

			$response['error'] = 'Requests for files in the "Compute\'s Gazette SID Collection" folder is not supported';

		} else if (strtolower(substr($file, 0, 11)) == "sid happens") {

			$response['error'] = 'Requests for files in the "SID Happens" folder is not supported';

		} else if (strtolower(substr($file, 0, 27)) == "exotic sid tunes collection") {

			$response['error'] = 'Requests for files in the "Exotic SID Tunes Collection" folder is not supported';

		} else if (strtolower(substr($file, 0, 23)) == "csdb music competitions") {

			$response['error'] = 'Requests for files in the "CSDb Music Competitions" folder is not supported';

		} else if (strtolower(substr($file, -4)) == ".sid") {

			/***** SINGLE FILE *****/

			$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));	
			$select->setFetchMode(PDO::FETCH_OBJ);
			$row = $select->fetch();
	
			if ($select->rowCount()) {
	
				$response['fullname'] 		= $row->fullname;
				$response['player'] 		= $row->player;
				$response['lengths'] 		= $row->lengths;
				$response['type'] 			= $row->type;
				$response['version'] 		= $row->version;
				$response['playertype'] 	= $row->playertype;
				$response['playercompat'] 	= $row->playercompat;
				$response['clockspeed'] 	= $row->clockspeed;
				$response['sidmodel'] 		= $row->sidmodel;
				$response['dataoffset'] 	= $row->dataoffset;
				$response['datasize'] 		= $row->datasize;
				$response['loadaddr'] 		= $row->loadaddr;
				$response['initaddr'] 		= $row->initaddr;
				$response['playaddr'] 		= $row->playaddr;
				$response['subtunes'] 		= $row->subtunes;
				$response['startsubtune'] 	= $row->startsubtune;
				$response['name'] 			= $row->name;
				$response['author'] 		= $row->author;
				$response['copyright'] 		= $row->copyright;
				$response['hash'] 			= $row->hash;
				$response['stil'] 			= $row->stil;
				$response['new'] 			= $row->new;
				$response['updated'] 		= $row->updated;
				$response['csdbtype'] 		= $row->csdbtype;
				$response['csdbid'] 		= $row->csdbid;
				$response['application'] 	= $row->application;
				$response['gb64'] 			= $row->gb64;
			}

		} else {

			/***** FOLDER AND FILES *****/

			$folder = $fullname;

			$select_folder = $db->prepare('SELECT * FROM hvsc_folders WHERE fullname = :fullname LIMIT 1');
			$select_folder->execute(array(':fullname'=>$folder));	
			$select_folder->setFetchMode(PDO::FETCH_OBJ);
			$row_folder = $select_folder->fetch();
	
			if ($select_folder->rowCount()) {

				$response['folder'] 		= $row_folder->fullname;
				$response['type'] 			= $row_folder->type;
				$response['files'] 			= $row_folder->files;
				//$response['user_id'] 			= $row->user_id;
				$response['hash'] 			= $row_folder->hash;
				$response['incompatible'] 	= $row_folder->incompatible;
				$response['new'] 			= $row_folder->new;
				$response['flags'] 			= $row_folder->flags;

				// Get array of files in folder, remove unwanted entries, then re-index with 0 as start
				$files = array_values(array_diff(scandir(ROOT_HVSC.'/'.$fullname), [
					'.',
					'..',
					'DOCUMENTS',			// HVSC
					'UPDATE',
					'update',
					'10_Years_HVSC.d71',
					'10_Years_HVSC.d81',
					'10_Years_HVSC.dfi',
					'10_Years_HVSC_1.d64',
					'10_Years_HVSC_2.d64',
					'20_Years_HVSC.d64',
					'HVSC_Intro_41.d64',
					'HVSC_Intro_42.d64',
					'HVSC_Intro_43.d64',
					'HVSC_Intro_44.d64',
					'readme.1st',
					'sid.bat',
					'sidinfo.csv',
					'sidinfo.exe',
					'00_Commodore64',		// CGSC
					'00_Documents',
					'00_SIDfests',
					'00_SidNews',
					'00_Utils',
				]));

				foreach($files as $i => $sid) {

					$select = $db->prepare('SELECT * FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
					$select->execute(array(':fullname'=>$folder.'/'.$sid));	
					$select->setFetchMode(PDO::FETCH_OBJ);
					$row = $select->fetch();

					if ($select->rowCount()) {
			
						$response[$i]['fullname'] 		= $row->fullname;
						$response[$i]['player'] 		= $row->player;
						$response[$i]['lengths'] 		= $row->lengths;
						$response[$i]['type'] 			= $row->type;
						$response[$i]['version'] 		= $row->version;
						$response[$i]['playertype'] 	= $row->playertype;
						$response[$i]['playercompat']	= $row->playercompat;
						$response[$i]['clockspeed'] 	= $row->clockspeed;
						$response[$i]['sidmodel'] 		= $row->sidmodel;
						$response[$i]['dataoffset'] 	= $row->dataoffset;
						$response[$i]['datasize'] 		= $row->datasize;
						$response[$i]['loadaddr'] 		= $row->loadaddr;
						$response[$i]['initaddr'] 		= $row->initaddr;
						$response[$i]['playaddr'] 		= $row->playaddr;
						$response[$i]['subtunes'] 		= $row->subtunes;
						$response[$i]['startsubtune']	= $row->startsubtune;
						$response[$i]['name'] 			= $row->name;
						$response[$i]['author'] 		= $row->author;
						$response[$i]['copyright'] 		= $row->copyright;
						$response[$i]['hash'] 			= $row->hash;
						$response[$i]['stil'] 			= $row->stil;
						$response[$i]['new'] 			= $row->new;
						$response[$i]['updated'] 		= $row->updated;
						$response[$i]['csdbtype'] 		= $row->csdbtype;
						$response[$i]['csdbid'] 		= $row->csdbid;
						$response[$i]['application'] 	= $row->application;
						$response[$i]['gb64'] 			= $row->gb64;
					}
				}
			}
		}

	} else if (isset($_GET['folder']) && $_GET['folder'] != "") {

		$folder = trim($_GET['folder'], '/');

		if (strtolower(substr($folder, 0, 23)) == "csdb music competitions") {

			$response['error'] = 'Requests for sub folders in "CSDb Music Competitions" is not supported';

		} else {

			/***** ONLY FOLDER *****/

			$prefix = strtolower(substr($folder, 0, 9)) == "compute's" ||
				strtolower(substr($folder, 0, 11)) == "sid happens" ||
				strtolower(substr($folder, 0, 27)) == "exotic sid tunes collection"
					? '_'
					: '_High Voltage SID Collection/';

			$fullname = $prefix.$folder;

			$select_folder = $db->prepare('SELECT * FROM hvsc_folders WHERE fullname = :fullname LIMIT 1');
			$select_folder->execute(array(':fullname'=>$fullname));
			$select_folder->setFetchMode(PDO::FETCH_OBJ);
			$row_folder = $select_folder->fetch();

			if ($select_folder->rowCount()) {

				$response['folder'] 		= $row_folder->fullname;
				$response['type'] 			= $row_folder->type;
				$response['files'] 			= $row_folder->files;
				//$response['user_id'] 			= $row_folder->user_id;
				$response['hash'] 			= $row_folder->hash;
				$response['incompatible'] 	= $row_folder->incompatible;
				$response['new'] 			= $row_folder->new;
				$response['flags'] 			= $row_folder->flags;

				// Get all subfolders too (if any)
				$select_subfolder = $db->prepare('SELECT * FROM hvsc_folders WHERE fullname LIKE :fullname');
				$select_subfolder->execute(array(':fullname'=>'%'.$fullname.'/%'));
				$select_subfolder->setFetchMode(PDO::FETCH_OBJ);

				$response['subfolders'] 	= $select_subfolder->rowCount();

				foreach($select_subfolder as $i => $row_subfolder) {

					$response[$i]['folder'] 		= $row_subfolder->fullname;
					$response[$i]['type'] 			= $row_subfolder->type;
					$response[$i]['files'] 			= $row_subfolder->files;
					//$response[$i]['user_id'] 			= $row_subfolder->user_id;
					$response[$i]['hash'] 			= $row_subfolder->hash;
					$response[$i]['incompatible'] 	= $row_subfolder->incompatible;
					$response[$i]['new'] 			= $row_subfolder->new;
					$response[$i]['flags'] 			= $row_subfolder->flags;
				}
			}
		}

	} else if (isset($_GET['profile']) && $_GET['profile'] != "") {

		/***** COMPOSER *****/

		$folder = trim($_GET['profile'], '/');

		$prefix = strtolower(substr($folder, 0, 9)) == "compute's" ||
			strtolower(substr($folder, 0, 11)) == "sid happens" ||
			strtolower(substr($folder, 0, 27)) == "exotic sid tunes collection"
				? '_'
				: '_High Voltage SID Collection/';

		$fullname = $prefix.$folder;				

		$select = $db->prepare('SELECT * FROM composers WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$fullname));	
		$select->setFetchMode(PDO::FETCH_OBJ);
		$row = $select->fetch();

		if ($select->rowCount()) {

			// Use 'fullname' parameter to figure out the name of the thumbnail (if it exists)
			$fn = str_replace('_High Voltage SID Collection/', '', $fullname);
			$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
			$fn = str_replace('_Exotic SID Tunes Collection', 'estc', $fn);
			$fn = strtolower(str_replace('/', '_', $fn));
			$thumbnail = '/images/composers/'.$fn.'.jpg';
			if (!file_exists('../'.$thumbnail)) $thumbnail = '/images/composer.png';

			$response['fullname'] 		= $row->fullname;
			$response['focus'] 			= $row->focus;
			$response['name'] 			= $row->name;
			$response['shortname'] 		= $row->shortname;
			$response['handles'] 		= $row->handles;
			$response['shorthandle'] 	= $row->shorthandle;
			$response['active'] 		= $row->active;
			$response['born'] 			= $row->born;
			$response['died'] 			= $row->died;
			$response['cause'] 			= $row->cause;
			$response['notable'] 		= $row->notable;
			$response['country'] 		= $row->country;
			$response['employment'] 	= $row->employment;
			$response['affiliation'] 	= $row->affiliation;
			$response['brand'] 			= empty($row->brand) ? '' : $protocol.'deepsid.chordian.net/images/brands/'.$row->brand;
			$response['branddark'] 		= empty($row->branddark) ? '' : $protocol.'deepsid.chordian.net/images/brands/'.$row->branddark;
			$response['csdbtype'] 		= $row->csdbtype;
			$response['csdbid'] 		= $row->csdbid;
			$response['thumbnail']		= $protocol.'deepsid.chordian.net'.$thumbnail;
			$response['imagesource'] 	= $row->imagesource;
		}

	} else if (isset($_GET['players'])) {

		/***** ALL PLAYERS/EDITORS *****/

		$select = $db->query('SELECT * FROM players_info');
		$select->setFetchMode(PDO::FETCH_OBJ);

		foreach($select as $i => $row) {

			$response[$i]['title'] 				= $row->title;
			$response[$i]['search']				= $row->search;
			$response[$i]['description']		= $row->description;
			$response[$i]['developer']			= $row->developer;
			$response[$i]['startyear']			= $row->startyear;
			$response[$i]['endyear']			= $row->endyear;
			$response[$i]['site']				= $row->site;
			$response[$i]['csdbid']				= $row->csdbid;
			$response[$i]['platform']			= $row->platform;
			$response[$i]['distribution']		= $row->distribution;
			$response[$i]['encoding']			= $row->encoding;
			$response[$i]['sourcecode']			= $row->sourcecode;
			$response[$i]['docs']				= $row->docs;
			$response[$i]['exampletunes']	 	= $row->exampletunes;
			$response[$i]['sidchipcount']	 	= $row->sidchipcount;
			$response[$i]['channelsvisible']	= $row->channelsvisible;
			$response[$i]['speeds']				= $row->speeds;
			$response[$i]['digi']				= $row->digi;
			$response[$i]['auxsupport']			= $row->auxsupport;
			$response[$i]['importfrom']			= $row->importfrom;
			$response[$i]['saveto']				= $row->saveto;
			$response[$i]['packer']				= $row->packer;
			$response[$i]['relocator']			= $row->relocator;
			$response[$i]['loadsavesnd']		= $row->loadsavesnd;
			$response[$i]['instruments']		= $row->instruments;
			$response[$i]['subtunes']			= $row->subtunes;
			$response[$i]['noteworthy']			= $row->noteworthy;
			$response[$i]['playersize']			= $row->playersize;
			$response[$i]['zeropages']			= $row->zeropages;
			$response[$i]['cputime']			= $row->cputime;
			$response[$i]['arpeggio']			= $row->arpeggio;
			$response[$i]['pulsating']			= $row->pulsating;
			$response[$i]['filtering']			= $row->filtering;
			$response[$i]['vibrato']			= $row->vibrato;
			$response[$i]['hardrestart']		= $row->hardrestart;
			$response[$i]['tracksystem']		= $row->tracksystem;
			$response[$i]['patterns']			= $row->patterns;
			$response[$i]['followplay']			= $row->followplay;
			$response[$i]['copypaste']			= $row->copypaste;
			$response[$i]['undoing']			= $row->undoing;
			$response[$i]['trackcmds']			= $row->trackcmds;
			$response[$i]['noteinput']			= $row->noteinput;
		}

	} else {
		header('HTTP/1.1 400 Bad Request', true, 400);
		$response['error'] = 'Please use parameters "file", "folder", "profile" or "players"';
	}

} catch(PDOException $e) {
	$response['error'] = 'Could not connect to the DeepSID database';
}

$json_response = json_encode($response);
echo $json_response;
?>