<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Simple standalone CSDb web service test.
 * Adjust $csdb_type and $csdb_id as needed.
 */

// --------------------------------------------------
// Settings
// --------------------------------------------------
$csdb_type = $_GET['type'] ?? 'release'; // release, sid, event, group, etc.
$csdb_id   = isset($_GET['id']) ? (int)$_GET['id'] : 225160;

// Add depth=3 for SID entries, like in your real code
$url = 'https://csdb.dk/webservice/?type=' . urlencode($csdb_type) .
       '&id=' . $csdb_id .
       ($csdb_type === 'sid' ? '&depth=3' : '');

// --------------------------------------------------
// cURL fetch
// --------------------------------------------------
$ch = curl_init($url);

curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_MAXREDIRS      => 5,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT        => 20,
	CURLOPT_USERAGENT      => 'DeepSID test_csdb.php',

	// THIS IS THE IMPORTANT PART
	CURLOPT_REFERER        => 'https://csdb.dk/',

	// Uncomment next two only for temporary debugging if SSL causes trouble
	// CURLOPT_SSL_VERIFYPEER => false,
	// CURLOPT_SSL_VERIFYHOST => false,
]);

$xml = curl_exec($ch);

$curl_errno    = curl_errno($ch);
$curl_error    = curl_error($ch);
$http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$total_time    = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
$namelookup    = curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME);
$connect_time  = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
$starttransfer = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
$primary_ip    = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
$size_download = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

curl_close($ch);

// --------------------------------------------------
// XML checks
// --------------------------------------------------
$has_csdbdata = is_string($xml) && strpos($xml, '<CSDbData>') !== false;

libxml_use_internal_errors(true);
$csdb = false;
$xml_errors = [];

if (is_string($xml) && $xml !== '') {
	$csdb = simplexml_load_string($xml);
	if ($csdb === false) {
		$xml_errors = libxml_get_errors();
	}
}
libxml_clear_errors();

// --------------------------------------------------
// Helper output
// --------------------------------------------------
function h($string): string {
	return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>CSDb Web Service Test</title>
<style>
	body {
		font-family: Arial, sans-serif;
		font-size: 14px;
		line-height: 1.4;
		margin: 20px;
		background: #fff;
		color: #000;
	}
	h1, h2 {
		margin-top: 0;
	}
	table {
		border-collapse: collapse;
		margin: 0 0 20px 0;
		min-width: 700px;
	}
	th, td {
		border: 1px solid #ccc;
		padding: 6px 8px;
		text-align: left;
		vertical-align: top;
	}
	th {
		background: #f3f3f3;
	}
	pre {
		background: #f7f7f7;
		border: 1px solid #ccc;
		padding: 10px;
		overflow: auto;
		white-space: pre-wrap;
		word-break: break-word;
	}
	.ok {
		color: #0a7a0a;
		font-weight: bold;
	}
	.fail {
		color: #b00020;
		font-weight: bold;
	}
	.note {
		color: #666;
	}
	form {
		margin-bottom: 20px;
		padding: 10px;
		background: #f8f8f8;
		border: 1px solid #ddd;
		display: inline-block;
	}
	label {
		margin-right: 10px;
	}
	input[type="text"], input[type="number"] {
		padding: 4px 6px;
	}
	button {
		padding: 4px 10px;
	}
</style>
</head>
<body>

<h1>CSDb Web Service Test</h1>

<form method="get">
	<label>
		Type:
		<input type="text" name="type" value="<?= h($csdb_type) ?>">
	</label>
	<label>
		ID:
		<input type="number" name="id" value="<?= h($csdb_id) ?>">
	</label>
	<button type="submit">Test</button>
</form>

<h2>Request</h2>
<table>
	<tr>
		<th>Type</th>
		<td><?= h($csdb_type) ?></td>
	</tr>
	<tr>
		<th>ID</th>
		<td><?= h($csdb_id) ?></td>
	</tr>
	<tr>
		<th>URL</th>
		<td><a href="<?= h($url) ?>" target="_blank"><?= h($url) ?></a></td>
	</tr>
</table>

<h2>cURL Result</h2>
<table>
	<tr>
		<th>cURL success</th>
		<td><?= $xml !== false ? '<span class="ok">YES</span>' : '<span class="fail">NO</span>' ?></td>
	</tr>
	<tr>
		<th>cURL errno</th>
		<td><?= h($curl_errno) ?></td>
	</tr>
	<tr>
		<th>cURL error</th>
		<td><?= h($curl_error ?: '(none)') ?></td>
	</tr>
	<tr>
		<th>HTTP code</th>
		<td><?= h($http_code) ?></td>
	</tr>
	<tr>
		<th>Content-Type</th>
		<td><?= h($content_type ?: '(unknown)') ?></td>
	</tr>
	<tr>
		<th>Primary IP</th>
		<td><?= h($primary_ip ?: '(unknown)') ?></td>
	</tr>
	<tr>
		<th>Downloaded bytes</th>
		<td><?= h((string)$size_download) ?></td>
	</tr>
	<tr>
		<th>Name lookup time</th>
		<td><?= h((string)$namelookup) ?> sec</td>
	</tr>
	<tr>
		<th>Connect time</th>
		<td><?= h((string)$connect_time) ?> sec</td>
	</tr>
	<tr>
		<th>Start transfer time</th>
		<td><?= h((string)$starttransfer) ?> sec</td>
	</tr>
	<tr>
		<th>Total time</th>
		<td><?= h((string)$total_time) ?> sec</td>
	</tr>
</table>

<h2>XML Checks</h2>
<table>
	<tr>
		<th>Response received</th>
		<td><?= (is_string($xml) && $xml !== '') ? '<span class="ok">YES</span>' : '<span class="fail">NO</span>' ?></td>
	</tr>
	<tr>
		<th>Contains &lt;CSDbData&gt;</th>
		<td><?= $has_csdbdata ? '<span class="ok">YES</span>' : '<span class="fail">NO</span>' ?></td>
	</tr>
	<tr>
		<th>simplexml_load_string()</th>
		<td><?= $csdb !== false ? '<span class="ok">SUCCESS</span>' : '<span class="fail">FAILED</span>' ?></td>
	</tr>
</table>

<?php if (!empty($xml_errors)): ?>
	<h2>XML Parser Errors</h2>
	<pre><?php
	foreach ($xml_errors as $error) {
		echo h(trim($error->message)) . ' (line ' . $error->line . ", col " . $error->column . ")\n";
	}
	?></pre>
<?php endif; ?>

<?php if ($csdb !== false): ?>
	<h2>Parsed XML Root</h2>
	<pre><?= h($csdb->getName()) ?></pre>

	<h2>Parsed XML Summary</h2>
	<pre><?php print_r($csdb); ?></pre>
<?php endif; ?>

<h2>Raw Response</h2>
<pre><?= h($xml === false ? '' : $xml) ?></pre>

</body>
</html>