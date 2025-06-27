<?php
/**
 * DeepSID.com
 *
 * Shortens a full DeepSID URL using the DeepSID.com domain.
 */

require_once("setup.php");

try {
	$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES UTF8");

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$url = trim($_POST['url']);
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			die('Invalid URL.');
		}

		// Generate a unique 5-character hash
		do {
			$hash = substr(md5(uniqid()), 0, 5);

			// Check if hash is already used
			$select = $db->prepare("SELECT 1 FROM short_urls WHERE hash = ?");
			$select->execute([$hash]);
			$exists = $select->fetchColumn();
		} while ($exists);		

		// Store the hash and full URL
		$insert = $db->prepare("INSERT INTO short_urls (hash, full_url) VALUES (?, ?)");
		$insert->execute([$hash, $url]);

		$shortUrl = "https://deepsid.com/$hash";
		echo "Short URL: <a href=\"$shortUrl\">$shortUrl</a>";
	}
} catch(PDOException $e) {
	die(DB_ERROR);
}
?>

<form method="post">
    <input type="text" name="url" placeholder="Paste your DeepSID link here" size="160">
    <button type="submit">Shorten</button>
</form>