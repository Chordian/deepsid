<?php
/**
 * DeepSID
 *
 * Get the site type and site ID from the labels, if this exists for the
 * specified collection file ID.
 * 
 * @uses		$_GET['id']				file id of the SID song
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

try {
	$db = $account->getDB();

	$select = $db->prepare(
		'SELECT li.site, li.site_id
		 FROM labels_lookup ll
		 INNER JOIN labels_info li ON li.id = ll.labels_id
		 WHERE ll.files_id = :id LIMIT 1'
	);
	$select->execute(array(':id' => $_GET['id']));
	$row = $select->fetch(PDO::FETCH_ASSOC);

	$type = $row ? strtolower($row['site']) : null; 
	$id = $row ? $row['site_id'] : null; 

	echo json_encode(array('status' => 'ok', 'type' => $type, 'id' => $id));

} catch(PDOException $e) {
	$account->logActivityError(basename(__FILE__), $e->getMessage());		
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
?>