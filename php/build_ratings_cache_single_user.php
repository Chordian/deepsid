<?php
/**
 * DeepSID
 * 
 * Build ratings_cache rows for a single user.
 * 
 * @used-by		build_ratings_cache_all_users.php
 * @used-by		build_ratings_cache_specific_user.php
 */

// --------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------

/**
 * Build the ratings cache for the specified user ID.
 * 
 * @param PDO $db
 * @param int $user_id
 */
function buildRatingsCacheForUser(PDO $db, int $user_id): void
{
    // 1. Get ALL FOLDERS from 'folders' table
    $select_folders = $db->query('SELECT collection_path FROM folders ORDER BY collection_path');
    $folders = $select_folders->fetchAll(PDO::FETCH_OBJ);

    if (!$folders) {
        return;
    }
    
    // 2. Get ALL RATED FILES for this user (direct ratings only)
    $select_ratings = $db->prepare('
        SELECT f.collection_path
        FROM ratings r
        JOIN files f ON f.id = r.table_id
        WHERE r.user_id = :user_id
          AND r.type    = "FILE"
          AND r.rating  > 0
    ');
    $select_ratings->execute([':user_id' => $user_id]);
    $rated_files = $select_ratings->fetchAll(PDO::FETCH_COLUMN, 0);

    $folder_map = []; // Folder => rated_files

    foreach ($rated_files as $collection_path) {
        $pos = strrpos($collection_path, '/');
        if ($pos === false) continue; // Safety

        $folder = substr($collection_path, 0, $pos);

        if (!isset($folder_map[$folder])) {
            $folder_map[$folder] = 0;
        }

        $folder_map[$folder] += 1; // One rated file in this folder
    }

    // 3. Prepare insertion
    $select_insert = $db->prepare('
        INSERT INTO ratings_cache (user_id, folder, rated_files)
        VALUES (:user, :folder, :rated)
    ');

    // 4. Insert ALL folders — zeros included
    foreach ($folders as $f) {
        $folder = $f->collection_path;

        // Get count or default to 0
        $rated_files_count = $folder_map[$folder] ?? 0;

        $select_insert->execute([
            ':user'   => $user_id,
            ':folder' => $folder,
            ':rated'  => $rated_files_count
        ]);
    }
}
?>