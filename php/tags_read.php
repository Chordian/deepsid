<?php
/**
 * DeepSID
 *
 * A procedure for returning the current list of tags and types for a file.
 */

function GetTagsAndTypes($file_id, &$list_of_tags, &$type_of_tags) {

	global $db;

	$tags_origin = array();
	$tags_suborigin = array();
	$tags_mixorigin = array();
	$tags_production = array();
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
			default:
				array_push($tags_other, $tag_info->name);
		}
	}
	sort($tags_origin);
	sort($tags_suborigin);
	sort($tags_mixorigin);
	sort($tags_production);
	sort($tags_other);

	$list_of_tags = array_merge($tags_production, $tags_origin, $tags_suborigin, $tags_mixorigin, $tags_other);

	$type_of_tags = array_merge(
		array_fill(0, count($tags_production),	'production'),
		array_fill(0, count($tags_origin),		'origin'),
		array_fill(0, count($tags_suborigin),	'suborigin'),
		array_fill(0, count($tags_mixorigin),	'mixorigin'),
		array_fill(0, count($tags_other),		'other')
	);
}
?>