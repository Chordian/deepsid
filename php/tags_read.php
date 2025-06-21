<?php
/**
 * DeepSID
 * 
 * @used-by		hvsc.php
 * @used-by		tags_write.php
 * @used-by		tags_write_single.php
 */

 /**
 * Update the current lists of tags and types for a file.
 * 
 * @global		object		$db					database connection
 *
 * @param		int			$file_id
 * @param		array		&$list_of_tags		reference to array with list of tags
 * @param		array		&$type_of_tags		reference to array with types of tags
 */
function GetTagsAndTypes($file_id, &$list_of_tags, &$type_of_tags) {

	global $db;

	$tags_event_p1 = array();
	$tags_event_p2 = array();
	$tags_event_p3 = array();
	$tags_event_p4 = array();
	$tags_origin = array();
	$tags_suborigin = array();
	$tags_mixorigin = array();
	$tags_production = array();
	$tags_digi = array();
	$tags_subdigi = array();
	$tags_other = array();

	$tag_ids = $db->prepare('SELECT tags_id FROM tags_lookup WHERE files_id = :id');
	$tag_ids->execute(array(':id'=>$file_id));
	$tag_ids->setFetchMode(PDO::FETCH_OBJ);

	foreach($tag_ids as $row) {
		$tag = $db->query('SELECT name, type FROM tags_info WHERE id = '.$row->tags_id.' LIMIT 1');
		$tag->setFetchMode(PDO::FETCH_OBJ);
		$tag_info = $tag->fetch();
		switch ($tag_info->type) {
			case 'EVENT':
				if ($tag_info->name == "Compo" || $tag_info->name == "<-")
					// Must come before the competition ranking
					array_push($tags_event_p2, $tag_info->name);
				else if ($tag_info->name == "Winner"  || substr($tag_info->name, 0, 1) == "#")
					// Competition ranking
					array_push($tags_event_p3, $tag_info->name);
				else if ($tag_info->name == "->")
					// Arrow to right is always last
					array_push($tags_event_p4, $tag_info->name);
				else
					// Party names should always come first (before the other two above)
					array_push($tags_event_p1, $tag_info->name);
				break;
			case 'ORIGIN':
				array_push($tags_origin, $tag_info->name);
				break;
			case 'SUBORIGIN':
				array_push($tags_suborigin, $tag_info->name);
				break;
			case 'MIXORIGIN':
				array_push($tags_mixorigin, $tag_info->name);
				break;
			case 'PRODUCTION':
				array_push($tags_production, $tag_info->name);
				break;
			case 'DIGI':
				array_push($tags_digi, $tag_info->name);
				break;
			case 'SUBDIGI':
				array_push($tags_subdigi, $tag_info->name);
				break;
			default:
				array_push($tags_other, $tag_info->name);
		}
	}
	sort($tags_event_p1);
	sort($tags_event_p2);
	sort($tags_event_p3);
	sort($tags_event_p4);
	sort($tags_origin);
	sort($tags_suborigin);
	sort($tags_mixorigin);
	sort($tags_production);
	sort($tags_digi);
	sort($tags_subdigi);
	sort($tags_other);

	$list_of_tags = array_merge($tags_event_p1, $tags_event_p2, $tags_event_p3, $tags_event_p4, $tags_production, $tags_origin, $tags_suborigin, $tags_mixorigin, $tags_digi, $tags_subdigi, $tags_other);

	$type_of_tags = array_merge(
		array_fill(0, count($tags_event_p1),	'event'),
		array_fill(0, count($tags_event_p2),	'event'),
		array_fill(0, count($tags_event_p3),	'event'),
		array_fill(0, count($tags_event_p4),	'event'),
		array_fill(0, count($tags_production),	'production'),
		array_fill(0, count($tags_origin),		'origin'),
		array_fill(0, count($tags_suborigin),	'suborigin'),
		array_fill(0, count($tags_mixorigin),	'mixorigin'),
		array_fill(0, count($tags_digi),		'digi'),
		array_fill(0, count($tags_subdigi),		'subdigi'),
		array_fill(0, count($tags_other),		'other')
	);
}
?>