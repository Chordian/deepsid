<?php
/**
 * DeepSID
 *
 * Read SQL query lines from a text file and update the database accordingly.
 * This is used to tweak and finetune information, for example with a precise
 * version number for a specific song player instead of just v1.x.
 * 
 * This script is referred to in a 'howto_update' text file.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

try {
	$db = $account->GetDB();

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