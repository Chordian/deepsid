<?php
/**
 * DeepSID
 *
 * One-shot CSDb label updater using HTML search
 * ---------------------------------------------
 * - Only updates rows with csdbid = 0
 * - Searches CSDb via normal search page
 * - Requires EXACT title match
 * - Updates ONLY if exactly one release matches
 */

require_once("class.account.php"); // Includes setup

$dryRun = true;		// Set to TRUE to test without updating DB

function csdbFindExactRelease(string $name): array
{
    $url  = 'https://csdb.dk/search/?seinsel=all&search=' . urlencode($name);
    $html = @file_get_contents($url);

    if ($html === false) {
        return [];
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xp  = new DOMXPath($dom);

    /* -------------------------------------------------
     * CASE 1: Direct release page (auto-redirect)
     * ------------------------------------------------- */
    if (strpos($html, '<b>Type :</b>') !== false) {

        // Extract ID from any known link
        if (!preg_match('/release\/\?id=(\d+)/', $html, $idm)) {
            return [];
        }

        $id = (int)$idm[1];

        // Extract type
        $typeNode = $xp->query('//b[text()="Type :"]/following::a[1]')->item(0);
        if (!$typeNode) {
            return [];
        }

        $type = trim($typeNode->textContent);

        // Extract title
        $titleNode = $xp->query('//font[@size="6"]')->item(0);
        if (!$titleNode) {
            return [];
        }

        $title = trim($titleNode->textContent);

        // Extra safety: exact title match
        if (strcasecmp($title, $name) !== 0) {
            return [];
        }

        return [[
            'id'    => $id,
            'type'  => $type,
            'title' => $title
        ]];
    }

    /* -------------------------------------------------
     * CASE 2: Normal search result page
     * ------------------------------------------------- */
    $matches = [];

    $nodes = $xp->query('//ol/li[a[contains(@href,"/release/?id=")]]');

    foreach ($nodes as $li) {

        $a = $xp->query('.//a[contains(@href,"/release/?id=")]', $li)->item(0);
        if (!$a) {
            continue;
        }

        $title = trim($a->textContent);
        if (strcasecmp($title, $name) !== 0) {
            continue;
        }

        $text = trim(preg_replace('/\s+/', ' ', $li->textContent));

        if (!preg_match('/\(([^)]+)\)/', $text, $m)) {
            continue;
        }

        $type = trim($m[1]);

        if (!preg_match('/id=(\d+)/', $a->getAttribute('href'), $idm)) {
            continue;
        }

        $matches[] = [
            'id'    => (int)$idm[1],
            'type'  => $type,
            'title' => $title
        ];
    }

    return $matches;
}

try {
	
	$db = $account->GetDB();

	// Fetch candidates
	$stmt = $db->query("
		SELECT id, name
		FROM labels_info
		WHERE csdbid = 0
		ORDER BY id
	");

	$update = $db->prepare("
		UPDATE labels_info
		SET csdbid = ?, type = ?
		WHERE id = ?
	");

	$updated = 0;
	$skipped = 0;

	foreach ($stmt as $row) {

		$labelId = (int)$row['id'];
		$name    = trim($row['name']);

		if ($name === '') {
			echo "[SKIP] #{$labelId} empty name<br />";
			$skipped++;
			continue;
		}

		echo "Searching \"{$name}\" ... ";

		$matches = csdbFindExactRelease($name);
		$count   = count($matches);

		if ($count === 1) {

			$m = $matches[0];

			echo "MATCH → ID {$m['id']} ({$m['type']})";

			if (!$dryRun) {
				$update->execute([
					$m['id'],
					$m['type'],
					$labelId
				]);
			}

			echo " ✔<br />";
			$updated++;

		} else {
			echo "SKIPPED ({$count} exact matches)<br />";
			$skipped++;
		}

		usleep(300000);
	}

	echo "<br />DONE<br />";
	echo "Updated: {$updated}<br />";
	echo "Skipped: {$skipped}<br />";

} catch(PDOException $e) {
	echo 'ERROR: '.$e->getMessage();
}
?>