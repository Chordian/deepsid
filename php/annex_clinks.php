<?php
/**
 * DeepSID
 *
 * Returns a block of HTML for the annex box with the composer's links.
 * 
 * @uses		$_GET['id']			link to ID in 'composers' table
 *
 * @used-by		main.js
 */

require_once("setup.php");

try {

	if ($_SERVER['HTTP_HOST'] == LOCALHOST)
		$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
	else
		$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	// Get the list of links for this composer
	$select = $db->prepare('SELECT id, name, url FROM composers_links WHERE composers_id = :id ORDER BY name');
	$select->execute(array(':id'=>$_GET['id']));
	$select->setFetchMode(PDO::FETCH_OBJ);

	// Build the HTML block with the entire list
	$html = '';
	foreach ($select as $row) {
		$html .= '
			<li>
				<a href="'.$row->url.'" target="_blank" class="clink ellipsis" data-id="'.$row->id.'">'.$row->name.'</a>
				<div class="clink-icon clink-edit" title="Edit"></div>
				<div class="clink-icon clink-delete" title="Delete"></div>
			</li>';
	}

	if (empty($html)) {
		$html = '<p><i id="clink-list" data-id="'.$_GET['id'].'">There are no links yet.</i></p>';
		$content = 0;
	 } else {
		$html = '<ul id="clink-list" data-id="'.$_GET['id'].'">'.$html.'</ul>';
		$content = -1;
	 }

} catch(PDOException $e) {
	$account->LogActivityError(basename(__FILE__), $e->getMessage());
	die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
}
die(json_encode(array('status' => 'ok', 'html' => $html, 'clinks' => $content)));
?>