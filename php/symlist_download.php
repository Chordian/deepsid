<?php
/**
 * DeepSID
 *
 * Download all the files of a symlist folder as one ZIP file.
 * 
 * Since files can come from various different folders, it's possible to end up
 * with several files of the same name. The script deals with this by adding a
 * padded number to each of such files.
 * 
 * @uses		$_POST['symlist']
 * 
 * @used-by		browser.js
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Sanitize the playlist name so it can be used as a ZIP filename
	// @link https://stackoverflow.com/a/2021729/2242348
	$playlist = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', substr($_POST['symlist'], 1));
	$playlist = mb_ereg_replace("([\.]{2,})", '', $playlist);

	$playlist = str_replace(' ', '_', $playlist);

	$filename = $_SERVER['DOCUMENT_ROOT'].'/deepsid/temp/'.$playlist.'.zip';
	if (file_exists($filename)) unlink($filename);

	$zip = new ZipArchive();
	$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// Get the ID of this symlist
	$select = $db->prepare('SELECT id FROM hvsc_folders WHERE fullname = :folder LIMIT 1');
	$select->execute(array(':folder'=>$_POST['symlist']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "Couldn't find the '".$_POST['symlist']."' playlist.")));

	$folder_id = $select->fetch()->id;

	// Get the ID of all the files in this symlist folder
	$select = $db->query('SELECT file_id FROM symlists WHERE folder_id = '.$folder_id);
	$select->setFetchMode(PDO::FETCH_OBJ);

	if (!$select->rowCount())
		die(json_encode(array('status' => 'error', 'message' => "The '".$_POST['symlist']."' playlist appears to be empty.")));

	$all_sid_files = array();

	// Parse through each SID file in the symlist folder
	foreach ($select as $row) {

		$files = $db->query('SELECT fullname FROM hvsc_files WHERE id = '.$row->file_id.' LIMIT 1');
		$files->setFetchMode(PDO::FETCH_OBJ);

		if (!$files->rowCount())
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find file ID '.$row->file_id.'; the download has failed.")));

		$fullname = $files->fetch()->fullname;

		// Add the file to the ZIP archive
		$parts = explode('/', $fullname);
		$org_file = $sid_file = end($parts);
		$count = 1;
		while (true) {
			if (in_array(strtolower($sid_file), $all_sid_files))
				// There's another SID file of the same name so add a number to it
				$sid_file = substr($org_file, 0, -4).str_pad($count, 2, '0', STR_PAD_LEFT).substr($org_file, strlen($org_file) - 4);
			else
				break;
			$count++;
			if ($count > 99)
				die(json_encode(array('status' => 'error', 'message' => "Too many files of the same name; the download has failed.")));
		}
		$all_sid_files[] = strtolower($sid_file);
		$zip->addFile(ROOT_HVSC.'/'.$fullname, $sid_file);
	}

	$zip->close();

} catch(PDOException $e) {
	$account->LogActivityError('symlist_download.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$time_ip = date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).' - '.$_SERVER['REMOTE_ADDR'].' - ';
$message = 'The playlist "'.$_POST['symlist'].'" was downloaded as a ZIP file';
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/activity.txt', '<span style="color:#999;">'.$time_ip.$message.'</span>'.PHP_EOL, FILE_APPEND);

echo json_encode(array('status' => 'ok', 'file' => HOST.'/temp/'.basename($filename)));
?>