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

	$db = new PDO(
		'mysql:host='.$config['db_deepsid_host'].';dbname='.$config['db_deepsid_name'],
		$config['db_deepsid_user'],
		$config['db_deepsid_pwd']);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if (isset($_GET['file']) && $_GET['file'] != "") {

		$file = trim($_GET['file'], '/');
		$collection_path = '_High Voltage SID Collection/'.$file;

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

			$select = $db->prepare('SELECT * FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
			$select->execute(array(':collection_path' => $collection_path));	
			$select->setFetchMode(PDO::FETCH_OBJ);
			$row = $select->fetch();
	
			if ($select->rowCount()) {
	
				$response['collection_path']	= $row->collection_path;
				$response['player'] 			= $row->player;
				$response['lengths'] 			= $row->lengths;
				$response['type'] 				= $row->type;
				$response['version'] 			= $row->version;
				$response['player_type'] 		= $row->player_type;
				$response['player_compat'] 		= $row->player_compat;
				$response['clock_speed'] 		= $row->clock_speed;
				$response['sid_model'] 			= $row->sid_model;
				$response['data_offset'] 		= $row->data_offset;
				$response['data_size'] 			= $row->data_size;
				$response['load_addr'] 			= $row->load_addr;
				$response['init_addr'] 			= $row->init_addr;
				$response['play_addr'] 			= $row->play_addr;
				$response['subtunes'] 			= $row->subtunes;
				$response['start_subtune'] 		= $row->start_subtune;
				$response['name'] 				= $row->name;
				$response['author'] 			= $row->author;
				$response['copyright'] 			= $row->copyright;
				$response['hash'] 				= $row->hash;
				$response['stil'] 				= $row->stil;
				$response['new'] 				= $row->new;
				$response['updated'] 			= $row->updated;
				$response['csdb_type'] 			= $row->csdb_type;
				$response['csdb_id'] 			= $row->csdb_id;
			}

		} else {

			/***** FOLDER AND FILES *****/

			$folder = $collection_path;

			$select_folder = $db->prepare('SELECT * FROM hvsc_folders WHERE collection_path = :collection_path LIMIT 1');
			$select_folder->execute(array(':collection_path' => $folder));	
			$select_folder->setFetchMode(PDO::FETCH_OBJ);
			$row_folder = $select_folder->fetch();
	
			if ($select_folder->rowCount()) {

				$response['folder'] 		= $row_folder->collection_path;
				$response['type'] 			= $row_folder->type;
				$response['files'] 			= $row_folder->files;
				//$response['user_id'] 			= $row->user_id;
				$response['hash'] 			= $row_folder->hash;
				$response['incompatible'] 	= $row_folder->incompatible;
				$response['new'] 			= $row_folder->new;
				$response['flags'] 			= $row_folder->flags;

				// Get array of files in folder, remove unwanted entries, then re-index with 0 as start
				$files = array_values(array_diff(scandir(ROOT_HVSC.'/'.$collection_path), [
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

					$select = $db->prepare('SELECT * FROM hvsc_files WHERE collection_path = :collection_path LIMIT 1');
					$select->execute(array(':collection_path' => $folder.'/'.$sid));	
					$select->setFetchMode(PDO::FETCH_OBJ);
					$row = $select->fetch();

					if ($select->rowCount()) {
			
						$response[$i]['collection_path']	= $row->collection_path;
						$response[$i]['player'] 			= $row->player;
						$response[$i]['lengths'] 			= $row->lengths;
						$response[$i]['type'] 				= $row->type;
						$response[$i]['version'] 			= $row->version;
						$response[$i]['player_type'] 		= $row->player_type;
						$response[$i]['player_compat']		= $row->player_compat;
						$response[$i]['clock_speed'] 		= $row->clock_speed;
						$response[$i]['sid_model'] 			= $row->sid_model;
						$response[$i]['data_offset'] 		= $row->data_offset;
						$response[$i]['data_size'] 			= $row->data_size;
						$response[$i]['load_addr'] 			= $row->load_addr;
						$response[$i]['init_addr'] 			= $row->init_addr;
						$response[$i]['play_addr'] 			= $row->play_addr;
						$response[$i]['subtunes'] 			= $row->subtunes;
						$response[$i]['start_subtune']		= $row->start_subtune;
						$response[$i]['name'] 				= $row->name;
						$response[$i]['author'] 			= $row->author;
						$response[$i]['copyright'] 			= $row->copyright;
						$response[$i]['hash'] 				= $row->hash;
						$response[$i]['stil'] 				= $row->stil;
						$response[$i]['new'] 				= $row->new;
						$response[$i]['updated'] 			= $row->updated;
						$response[$i]['csdb_type'] 			= $row->csdb_type;
						$response[$i]['csdb_id'] 			= $row->csdb_id;
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

			$collection_path = $prefix.$folder;

			$select_folder = $db->prepare('SELECT * FROM hvsc_folders WHERE collection_path = :collection_path LIMIT 1');
			$select_folder->execute(array(':collection_path' => $collection_path));
			$select_folder->setFetchMode(PDO::FETCH_OBJ);
			$row_folder = $select_folder->fetch();

			if ($select_folder->rowCount()) {

				$response['folder'] 		= $row_folder->collection_path;
				$response['type'] 			= $row_folder->type;
				$response['files'] 			= $row_folder->files;
				//$response['user_id'] 			= $row_folder->user_id;
				$response['hash'] 			= $row_folder->hash;
				$response['incompatible'] 	= $row_folder->incompatible;
				$response['new'] 			= $row_folder->new;
				$response['flags'] 			= $row_folder->flags;

				// Get all subfolders too (if any)
				$select_subfolder = $db->prepare('SELECT * FROM hvsc_folders WHERE collection_path LIKE :collection_path');
				$select_subfolder->execute(array(':collection_path' => '%'.$collection_path.'/%'));
				$select_subfolder->setFetchMode(PDO::FETCH_OBJ);

				$response['subfolders'] 	= $select_subfolder->rowCount();

				foreach($select_subfolder as $i => $row_subfolder) {

					$response[$i]['folder'] 		= $row_subfolder->collection_path;
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

		$collection_path = $prefix.$folder;				

		$select = $db->prepare('SELECT * FROM composers WHERE collection_path = :collection_path LIMIT 1');
		$select->execute(array(':collection_path' => $collection_path));	
		$select->setFetchMode(PDO::FETCH_OBJ);
		$row = $select->fetch();

		if ($select->rowCount()) {

			// Use 'collection_path' parameter to figure out the name of the thumbnail (if it exists)
			$fn = str_replace('_High Voltage SID Collection/', '', $collection_path);
			$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
			$fn = str_replace('_Exotic SID Tunes Collection', 'estc', $fn);
			$fn = strtolower(str_replace('/', '_', $fn));
			$thumbnail = '/images/composers/'.$fn.'.jpg';
			if (!file_exists('../'.$thumbnail)) $thumbnail = '/images/composer.png';

			$response['collection_path']	= $row->collection_path;
			$response['focus1'] 			= $row->focus1;
			$response['focus2'] 			= $row->focus2;
			$response['full_name']			= $row->full_name;
			$response['short_name'] 		= $row->short_name;
			$response['handles'] 			= $row->handles;
			$response['short_handle'] 		= $row->short_handle;
			$response['active'] 			= $row->active;
			$response['date_birth'] 		= $row->date_birth;
			$response['date_death'] 		= $row->date_death;
			$response['death_cause'] 		= $row->death_cause;
			$response['notable'] 			= $row->notable;
			$response['country'] 			= $row->country;
			$response['employment'] 		= $row->employment;
			$response['affiliation'] 		= $row->affiliation;
			$response['brand_light'] 		= empty($row->brand_light) ? '' : $protocol.'deepsid.chordian.net/images/brands/'.$row->brand_light;
			$response['brand_dark'] 		= empty($row->brand_dark) ? '' : $protocol.'deepsid.chordian.net/images/brands/'.$row->brand_dark;
			$response['csdb_type'] 			= $row->csdb_type;
			$response['csdb_id'] 			= $row->csdb_id;
			$response['thumbnail']			= $protocol.'deepsid.chordian.net'.$thumbnail;
			$response['image_source'] 		= $row->image_source;
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
			$response[$i]['start_year']			= $row->start_year;
			$response[$i]['end_year']			= $row->end_year;
			$response[$i]['site']				= $row->site;
			$response[$i]['csdb_id']			= $row->csdb_id;
			$response[$i]['platform']			= $row->platform;
			$response[$i]['distribution']		= $row->distribution;
			$response[$i]['encoding']			= $row->encoding;
			$response[$i]['source_code']		= $row->source_code;
			$response[$i]['docs']				= $row->docs;
			$response[$i]['example_tunes']	 	= $row->example_tunes;
			$response[$i]['sid_chip_count']	 	= $row->sid_chip_count;
			$response[$i]['channels_visible']	= $row->channels_visible;
			$response[$i]['speeds']				= $row->speeds;
			$response[$i]['digi']				= $row->digi;
			$response[$i]['aux_support']		= $row->aux_support;
			$response[$i]['import_from']		= $row->import_from;
			$response[$i]['save_to']			= $row->save_to;
			$response[$i]['packer']				= $row->packer;
			$response[$i]['relocator']			= $row->relocator;
			$response[$i]['load_save_snd']		= $row->load_save_snd;
			$response[$i]['instruments']		= $row->instruments;
			$response[$i]['subtunes']			= $row->subtunes;
			$response[$i]['noteworthy']			= $row->noteworthy;
			$response[$i]['player_size']		= $row->player_size;
			$response[$i]['zero_pages']			= $row->zero_pages;
			$response[$i]['cpu_time']			= $row->cpu_time;
			$response[$i]['arpeggio']			= $row->arpeggio;
			$response[$i]['pulsating']			= $row->pulsating;
			$response[$i]['filtering']			= $row->filtering;
			$response[$i]['vibrato']			= $row->vibrato;
			$response[$i]['hard_restart']		= $row->hard_restart;
			$response[$i]['track_system']		= $row->track_system;
			$response[$i]['patterns']			= $row->patterns;
			$response[$i]['follow_play']		= $row->follow_play;
			$response[$i]['copy_paste']			= $row->copy_paste;
			$response[$i]['undoing']			= $row->undoing;
			$response[$i]['track_cmds']			= $row->track_cmds;
			$response[$i]['note_input']			= $row->note_input;
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