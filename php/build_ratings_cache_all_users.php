<?php
/**
 * DeepSID
 * 
 * Rebuild the ratings cache for all users.
 */

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
    die("This is for administrators only.");

require_once "class.account.php";

$db = $account->GetDB();

require __DIR__ . '/build_ratings_cache_single_user.php';

echo "Running...\n\n";

try {
    $select_users = $db->query('SELECT id, username FROM users ORDER BY id');
    $users = $select_users->fetchAll(PDO::FETCH_OBJ);
    $total = count($users);

    $i = 0;
    foreach ($users as $u) {
        $i++;

        echo "User {$i}/{$total}: {$u->username} (id={$u->id})\n";

        // Delete old cache rows
        $del = $db->prepare("DELETE FROM ratings_cache WHERE user_id = ?");
        $del->execute([$u->id]);

        // Build new cache
        build_ratings_cache_for_user($db, (int)$u->id);

        echo "  -> Done.\n\n";
    }

    echo "All done.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>