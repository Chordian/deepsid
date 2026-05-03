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

require_once('class.account.php');

try {
	$db = $account->getDB();

	echo 'Number of songs in each member folder:<br /><br />';

	// Get all fields for all group member rows
	$groups = $db->query('SELECT * FROM `groups`');
	$groups->setFetchMode(PDO::FETCH_OBJ);

	// Read counts from all redirect paths and update the group member rows accordingly
	foreach($groups as $member) {
		$new = $db->prepare('SELECT files FROM hvsc_folders WHERE collection_path = :collection_path LIMIT 1');
		$new->execute(array(':collection_path' => $member->redirect));
		$new->setFetchMode(PDO::FETCH_OBJ);

		$count = $new->fetch()->files;
		$target = '_High Voltage SID Collection/GROUPS/'.$member->name.'/'.$member->folder;

		$db->query('UPDATE hvsc_folders SET files = '.$count.' WHERE collection_path = "'.$target.'" LIMIT 1');

		echo '<div style="display:inline-block;width:950px;">'.$target.'</div> = <div style="display:inline-block;width:45px;">'.$count.'</div> ('.$member->redirect.')<br />';
	}

	echo '<br />Number of members in each group:<br /><br />';

	$unique_groups = $db->query('
		SELECT name, COUNT(*) AS member_count
		FROM `groups`
		GROUP BY name
		ORDER BY member_count DESC;'
	);
	$unique_groups->setFetchMode(PDO::FETCH_OBJ);

	foreach($unique_groups as $group) {
		$collection_path = '_High Voltage SID Collection/GROUPS/' . $group->name;

		$db->query('UPDATE hvsc_folders SET files = '.$group->member_count.' WHERE collection_path = "'.$collection_path.'" LIMIT 1');

		echo '<div style="display:inline-block;width:300px;">'.$group->name.'</div> = <div style="display:inline-block;width:45px;">'.$group->member_count.'</div> ('.$collection_path.')<br />';
	}

	echo "<br />Script 'update_groups.php' has completed.";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>