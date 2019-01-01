<?php
/**
 * DeepSID
 *
 * Build the inside contents for a top list box.
 * 
 * @uses		$_GET['type'] - see 'root.php' for type options
 * @uses		$_GET['rows'] - see 'root.php' for row options
 */

require_once("root_generate.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

echo json_encode(array('status' => 'ok', 'list' => GenerateList($_GET['rows'], $_GET['type'])));
?>