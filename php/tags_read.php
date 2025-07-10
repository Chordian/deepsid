<?php
/**
 * DeepSID
 * 
 * @used-by		hvsc.php
 * @used-by		tags_write.php
 * @used-by		tags_write_single.php
 * @used-by		tags_remove_game.php
 */

 /**
 * Update the current lists of tags and types for a file.
 * 
 * This function has been refactored by ChatGPT.
 * 
 * @global		object		$db					database connection
 *
 * @param		int			$file_id
 * @param		array		&$list_of_tags		reference to array with list of tags
 * @param		array		&$type_of_tags		reference to array with types of tags
 * @param		array		&$id_of_tags		reference to array with id of tags
 * @param		int			&$id_tag_start		id to start bracket connection
 * @param		int			&$id_tag_end		id to end bracket connection
 */
function GetTagsAndTypes($file_id, &$list_of_tags, &$type_of_tags, &$id_of_tags, &$id_tag_start, &$id_tag_end) {
	global $db;

	$id_tag_start = 0;
	$id_tag_end = 0;

	// Fetch all relevant tags in one go
	$select = $db->prepare(
		'SELECT i.id, i.name, i.type, l.tags_id, l.end_id
		 FROM tags_lookup l
		 JOIN tags_info i ON l.tags_id = i.id
		 WHERE l.files_id = :id'
	);

	$select->execute([':id' => $file_id]);
	$select->setFetchMode(PDO::FETCH_OBJ);
	$all_tags = iterator_to_array($select); // Convert to array for sorting

	// Define type priorities for sorting
	$type_order = [
		'EVENT'      => 1,
		'PRODUCTION' => 2,
		'ORIGIN'     => 3,
		'SUBORIGIN'  => 4,
		'MIXORIGIN'  => 5,
		'DIGI'       => 6,
		'SUBDIGI'    => 7,
		'OTHER'      => 8
	];

	// Sorting logic
	usort($all_tags, function($a, $b) use ($type_order) {
		$a_rank = $type_order[$a->type] ?? 999;
		$b_rank = $type_order[$b->type] ?? 999;

		if ($a_rank !== $b_rank)
			return $a_rank - $b_rank;

		// Sort special EVENT tag names
		if ($a->type === 'EVENT') {
			$event_rank = function($name) {
				if ($name === 'Compo' || $name === '<-') return 1;
				if ($name === 'Winner' || $name === 'Solitary' || str_starts_with($name, '#')) return 2;
				if ($name === '->') return 4;
				return 0; // Party names first
			};
			$a_event_rank = $event_rank($a->name);
			$b_event_rank = $event_rank($b->name);
			if ($a_event_rank !== $b_event_rank)
				return $a_event_rank - $b_event_rank;
		}

		// Sort special PRODUCTION tag name
		if ($a->type === 'PRODUCTION') {
			// Prioritize "Music" first
			if ($a->name === 'Music') return -1;
			if ($b->name === 'Music') return 1;

			// Then prioritize "Collection"
			if ($a->name === 'Collection') return -1;
			if ($b->name === 'Collection') return 1;			
		}

		// Fallback to alphabetical
		return strcasecmp($a->name, $b->name);
	});

	// Extract final arrays
	$list_of_tags = [];
	$type_of_tags = [];
	$id_of_tags   = [];

	foreach ($all_tags as $tag) {
		// Fallback for empty or missing type
		// NOTE: The 'GENRE' type is treated as 'OTHER' for the time being.
		$raw_type = strtoupper(trim($tag->type));
		$allowed_types = ['EVENT', 'PRODUCTION', 'ORIGIN', 'SUBORIGIN', 'MIXORIGIN', 'DIGI', 'SUBDIGI'];
		$type = in_array($raw_type, $allowed_types) ? strtolower($raw_type) : 'other';

		$list_of_tags[] = $tag->name;
		$type_of_tags[] = $type;
		$id_of_tags[]   = $tag->id;

		// Assign start/end tag if end_id is valid and no pair has been set yet
		if ($tag->end_id > 0 && $id_tag_start === 0 && $id_tag_end === 0) {
			$id_tag_start = $tag->tags_id;
			$id_tag_end   = $tag->end_id;
		}		
	}
}
?>