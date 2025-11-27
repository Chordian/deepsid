<?php
/**
 * Build ratings_cache rows for a single user.
 * Now creates rows for ALL folders, even if rated_files = 0.
 */

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

/**
 * @param PDO $db
 * @param int $userId
 */
function build_ratings_cache_for_user(PDO $db, int $userId): void
{
    // 1) Get ALL FOLDERS from hvsc_folders
    $select_folders = $db->query('SELECT fullname FROM hvsc_folders ORDER BY fullname');
    $folders = $select_folders->fetchAll(PDO::FETCH_OBJ);

    if (!$folders) {
        return;
    }
    
    // 2) Get ALL RATED FILES for this user (direct ratings only)
    $select_ratings = $db->prepare('
        SELECT f.fullname
        FROM ratings r
        JOIN hvsc_files f ON f.id = r.table_id
        WHERE r.user_id = :user_id
          AND r.type    = "FILE"
          AND r.rating  > 0
    ');
    $select_ratings->execute([':user_id' => $userId]);
    $ratedFiles = $select_ratings->fetchAll(PDO::FETCH_COLUMN, 0);

    // Build folder => count map from rated files
    $folderMap = []; // folder => rated_files

    foreach ($ratedFiles as $fullname) {
        $pos = strrpos($fullname, '/');
        if ($pos === false) continue; // safety

        $folder = substr($fullname, 0, $pos);

        if (!isset($folderMap[$folder])) {
            $folderMap[$folder] = 0;
        }

        $folderMap[$folder] += 1; // one rated file in this folder
    }

    // 3) Prepare insertion
    $stmtInsert = $db->prepare('
        INSERT INTO ratings_cache (user_id, folder, rated_files)
        VALUES (:user, :folder, :rated)
    ');

    // 4) Insert ALL folders — zeros included
    foreach ($folders as $f) {
        $folder = $f->fullname;

        // get count or default to 0
        $ratedFilesCount = $folderMap[$folder] ?? 0;

        $stmtInsert->execute([
            ':user'   => $userId,
            ':folder' => $folder,
            ':rated'  => $ratedFilesCount
        ]);
    }
}
?>