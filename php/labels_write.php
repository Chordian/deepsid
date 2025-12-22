<?php
/**
 * DeepSID
 *
 * Add a label to a specific file. (A label is a production title factoid.)
 * 
 * Data will be returned stating if the entry was added, or if an existing
 * matching label was just linked to the file.
 * 
 * @uses		$_POST['id']			ID of the SID file
 * @uses		$_POST['name']			Name (e.g. "Dutch Breeze")
 * @uses		$_POST['type']			Type (e.g. "C64 Demo")
 * @uses		$_POST['csdbid']		CSDb ID (e.g. 11584)
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
	http_response_code(403);	
	die("Direct access not permitted.");
}

if (!$account->CheckLogin() || $account->UserName() !== 'JCH' || $account->UserID() !== JCH) {
	http_response_code(403);
	die("This is for administrators only."); // At least for now...
}

if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['type']) || !isset($_POST['csdbid']))
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper POST variables.')));

/**
 * Writes a line to a CSV log file describing an activity regarding labels.
 * 
 * The tags log file is used to avoid yet another log file.
 * 
 * @uses		$_POST['fileID']			file ID of the song being affected
 *
 * @param		string		$action
 * @param		string		$tag_id
 * @param		string		$tag_name
 */
function LogTagActivity($action, $labels_id, $labels_name, $labels_type) {
	global $db, $account;

	// Get the fullname of this ID
	$select = $db->prepare('SELECT fullname FROM hvsc_files WHERE id = :id LIMIT 1');
	$select->execute(array(':id'=>$_POST['id']));
	$select->setFetchMode(PDO::FETCH_OBJ);
	if ($select->rowCount() == 0)
		die(json_encode(array('status' => 'error', 'message' => 'Could not find ID '.$_POST['id'].' in the database')));
	$fullname = $select->fetch()->fullname;

	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/deepsid/logs/tags.txt',
			date('Y-m-d H:i:s', strtotime(TIME_ADJUST)).','.
			$_SERVER['REMOTE_ADDR'].','.
			$account->UserID().','.
			$account->UserName().','.
			$_POST['id'].','.
			$fullname.','.
			$action.','.
			$labels_id.','.
			$labels_name.','.
			$labels_type.		// Extra field
		PHP_EOL, FILE_APPEND);
}

/***** START *****/

try {
	$db = $account->GetDB();

	$created = false;
	$params = [
		':name'		=> $_POST['name'],
		':type'		=> $_POST['type'],
		':csdbid'	=> $_POST['csdbid']
	];

	// Try to get label info
	$select = $db->prepare('
		SELECT id FROM labels_info
		WHERE name = :name AND type = :type AND csdbid = :csdbid
		LIMIT 1'
	);
	$select->execute($params);
	$select->setFetchMode(PDO::FETCH_OBJ);

	if ($select->rowCount()) {
		// The label already exists so get its ID now
		$labels_id = $select->fetch()->id;
	} else {
		// The label doesn't exist so create it first
		$label = $db->prepare('INSERT INTO labels_info (name, type, csdbid)
			VALUES(:name, :type, :csdbid)');
		$label->execute($params);
		$labels_id = $db->lastInsertId();
		$created = true;
		LogTagActivity('LABELS:CREATE', $labels_id, $_POST['name'], $_POST['type']);
	}

	// Link the SID file to the label
	$link = $db->prepare('INSERT INTO labels_lookup (files_id, labels_id)
		VALUES(:files_id, :labels_id)');
	$link->execute([
		':files_id'  => $_POST['id'],
		':labels_id' => $labels_id
	]);
	LogTagActivity('LABELS:LINK', $labels_id, $_POST['name'], $_POST['type']);

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
echo json_encode(array('status' => 'ok', 'created' => $created));
?>