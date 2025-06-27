<?php
/**
 * DeepSID.com
 *
 * Parses a short DeepSID.com link and redirects to the full link.
 */

require_once("setup.php");

try {

	$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");
	
	// Get the requested path
	$hash = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

	if (empty($hash))
		// @todo Consider just redirecting to front page if empty?
		die('Welcome to DeepSID Short Links. Nothing to redirect.');

	// Look up the hash
	$select = $db->prepare("SELECT full_url FROM short_urls WHERE hash = ?");
	$select->execute([$hash]);

	if ($row = $select->fetch()) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: " . $row['full_url']);
		exit;
	} else {
		http_response_code(404);
		echo "Short link not found.";
	}

} catch(PDOException $e) {
	die(DB_ERROR);
}
?>