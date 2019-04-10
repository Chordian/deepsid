<?php
/**
 * DeepSID
 *
 * Build the HTML page for the 'Players' tab. Use the 'CSDb' script.
 * 
 * @uses		$_GET['player'] - e.g. "GoatTracker v2.x"
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

if (!isset($_GET['player']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify \'player\' as a GET variable.')));

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	$select = $db->prepare('SELECT csdbid FROM players WHERE player = :player LIMIT 1');
	$select->execute(array(':player'=>$_GET['player']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount())
		$csdb_id = $select->fetch()->csdbid;
	else
		// Not defined (yet)
		die(json_encode(array('status' => 'warning', 'sticky' => '', 'html' => '<p style="margin-top:0;"><i>No editor/player information available.</i></p>')));

} catch(PDOException $e) {
	$account->LogActivityError('player.php', $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}

$_GET['type'] =	'release';
$_GET['id'] = $csdb_id;
$_GET['back'] = 0;

include 'csdb.php';
?>