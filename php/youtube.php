<?php
/**
 * DeepSID
 *
 * Look up the specified SID file in the database and return the information
 * for YouTube video(s), such as e.g. the channel, video ID, etc.
 * 
 * @uses		$_GET['fullname']
 * @uses		$_GET['subtune']
 * 
 * @used-by		player.js
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

try {

	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// First find the ID of the 'fullname' text
	$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
	$select->execute(array(':fullname'=>$_GET['fullname']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	// Now get the YouTube info
	$count = 0;
	if ($select->rowCount()) {
		$select_youtube = $db->prepare('
			SELECT channel, video_id, tab_order, tab_default FROM youtube
			WHERE file_id = '.$select->fetch()->id.'
			AND subtune = :subtune
		');
		$select_youtube->execute(array(':subtune'=>$_GET['subtune']));
		$select_youtube->setFetchMode(PDO::FETCH_OBJ);
		$count = $select_youtube->rowCount();
	}

	if ($count == 0)
		die(json_encode(array('status' => 'ok', 'count' => 0))); // No videos found for that file

	$videos = array();
	foreach($select_youtube as $row) {
		$videos[$row->tab_order] = array(
			'channel'		=> $row->channel,
			'video_id'		=> $row->video_id,
			'tab_default'	=> $row->tab_default,
		);
	}

} catch(PDOException $e) {
	$account->LogActivityError('youtube.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

echo json_encode(array('status'	=> 'ok', 'count' => $count, 'videos' => $videos));
?>