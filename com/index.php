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
	
	$hash = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

	// If no path and query params exist, just redirect to the home page
	if ($hash === '' && empty($_SERVER['QUERY_STRING'])) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: https://deepsid.chordian.net");
		exit;
	}

	// If no path, but query string exists (e.g. ?file=/GAMES/...), pass it through to deepsid.chordian.net
	if ($hash === '' && !empty($_SERVER['QUERY_STRING'])) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: https://deepsid.chordian.net/?" . $_SERVER['QUERY_STRING']);
		exit;
	}

	// Otherwise, treat it as a possible short link
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