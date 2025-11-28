<?php
/**
 * DeepSID
 * 
 * Rebuild ratings cache for a specific user.
 */

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
    die("This is for administrators only.");

require_once "class.account.php";

$db = $account->GetDB();

require __DIR__ . '/build_ratings_cache_single_user.php';

echo "Running...\n\n";

try {
    // Just get it for *JCH* for now
    $select_user = $db->query('SELECT id, username FROM users WHERE id = '.JCH.' LIMIT 1');
    $select_user->setFetchMode(PDO::FETCH_OBJ);
    if ($select_user->rowCount())
        $u = $select_user->fetch();

    echo "Specific user: {$u->username} (id={$u->id})\n";

    // Delete old cache rows
    $del = $db->prepare("DELETE FROM ratings_cache WHERE user_id = ?");
    $del->execute([$u->id]);

    // Build new cache
    build_ratings_cache_for_user($db, (int)$u->id);

    echo "  -> Done.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>