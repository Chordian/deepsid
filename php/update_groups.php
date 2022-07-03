<?php
/**
 * DeepSID
 *
 * Update all the file counts in the /GROUPS/ member folders according to the
 * counts of files in the original /MUSICIANS/ letter folders.
 * 
 * The table should be run after the original /MUSICIANS/ letter folders have
 * been updated as part of the HVSC update process.
 * 
 * @used-by		N/A
 */

require_once("class.account.php"); // Includes setup

try {
	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get all fields for all group member rows
	$groups = $db->query('SELECT * FROM groups');
	$groups->setFetchMode(PDO::FETCH_OBJ);

	// Read counts from all redirect paths and update the group member rows accordingly
	foreach($groups as $member) {
		$new = $db->prepare('SELECT files FROM hvsc_folders WHERE fullname = :fullname LIMIT 1');
		$new->execute(array(':fullname'=>$member->redirect));
		$new->setFetchMode(PDO::FETCH_OBJ);

		$count = $new->fetch()->files;
		$target = '_High Voltage SID Collection/GROUPS/'.$member->name.'/'.$member->folder;

		$db->query('UPDATE hvsc_folders SET files = '.$count.' WHERE fullname = "'.$target.'" LIMIT 1');

		echo '<div style="display:inline-block;width:950px;">'.$target.'</div> = <div style="display:inline-block;width:45px;">'.$count.'</div> ('.$member->redirect.')<br />';
	}

	echo "<br />Script 'update_groups.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>