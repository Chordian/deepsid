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

	$tags_origin = array();
	$tags_suborigin = array();
	$tags_mixorigin = array();
	$tags_production = array();
	$tags_digi = array();
	$tags_subdigi = array();
	$tags_first = array();		// Not a type, just a few tags that needs to come first among 'other'
	$tags_other = array();

	$tag_ids = $db->prepare('SELECT tags_id FROM tags_lookup WHERE files_id = :id');
	$tag_ids->execute(array(':id'=>$file_id));
	$tag_ids->setFetchMode(PDO::FETCH_OBJ);

	foreach($tag_ids as $row) {
		$tag = $db->query('SELECT name, type FROM tags_info WHERE id = '.$row->tags_id.' LIMIT 1');
		$tag->setFetchMode(PDO::FETCH_OBJ);
		$tag_info = $tag->fetch();
		switch ($tag_info->type) {
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
				if ($tag_info->name == "Compo" || $tag_info->name == "Winner")
					array_push($tags_first, $tag_info->name);
				else
					array_push($tags_other, $tag_info->name);
		}
	}
	sort($tags_origin);
	sort($tags_suborigin);
	sort($tags_mixorigin);
	sort($tags_production);
	sort($tags_digi);
	sort($tags_subdigi);
	sort($tags_first);
	sort($tags_other);

	$list_of_tags = array_merge($tags_production, $tags_origin, $tags_suborigin, $tags_mixorigin, $tags_digi, $tags_subdigi, $tags_first, $tags_other);

	$type_of_tags = array_merge(
		array_fill(0, count($tags_production),	'production'),
		array_fill(0, count($tags_origin),		'origin'),
		array_fill(0, count($tags_suborigin),	'suborigin'),
		array_fill(0, count($tags_mixorigin),	'mixorigin'),
		array_fill(0, count($tags_digi),		'digi'),
		array_fill(0, count($tags_subdigi),		'subdigi'),
		array_fill(0, count($tags_first),		'other'),
		array_fill(0, count($tags_other),		'other')
	);
}
?>