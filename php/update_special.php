<?php
/**
 * DeepSID
 *
 * Read SQL query lines from a text file and update the database accordingly.
 * This is used to tweak and finetune information, for example with a precise
 * version number for a specific song player instead of just v1.x.
 * 
 * This script is referred to in a 'howto_update' text file.
 */

require_once("setup.php");

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	foreach (file('../utility/special_updating.sql') as $line) {

		// Remove newlines and stuff
		$line = preg_replace('/\s+/', ' ', trim($line));
		if (empty($line)) continue;

		echo $line.'<br />';
		$db->query($line);
	}

	echo '<br />Done.';

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>