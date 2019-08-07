<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to Remix64 entries or a specific entry.
 * 
 * @uses		$_GET['fullname']	for a page with links to sub pages
 * @uses		$_GET['id']			optional; for a specific entry
 */

// BEFORE UPLOAD TO GITHUB!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! MAKE API IN SETUP.PHP "REDACTED".

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$remixes = array();

if (isset($_GET['fullname'])) {	

	$hvsc_path = str_replace('_High Voltage SID Collection/', '', $_GET['fullname']);
	$encoded = json_encode(array('hvsc_path' => $hvsc_path));
	$hash = md5('deepsid'.$encoded.REMIX64_API.'get_remixes');

	try {
		// Get the entire JSON tree for the SID file from Remix64
		// @example https://www.remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash=d0f7e95f7e2e4ca1f50a8aaf66ca3808&data={%22hvsc_path%22:%22MUSICIANS\/D\/Daglish_Ben\/Cobra.sid%22}
		$data = file_get_contents('https://www.remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash='.$hash.'&data='.$encoded);
	} catch(ErrorException $e) {
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin:0;"><i>Uh... Remix64? Are you there?</i></p><small>Come on, Remix64, old buddy, don\'t let me down.</small>')));
	}

	$data = json_decode($data);

	if ($data->error_code != "ok")
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin:0;"><i>Remix64 responded with the following error message:</i></p>'.$data->error_message)));

	/* Example data: $data->data->[0 to MAX]

		formatted->duration				3:02
		formatted->platform				c64 										Skip if not this value
				   releasedate			2001-10-04 00:36:40							Cut out time of day
		formatted->composer_name		Ben Daglish
				   lookup_url			https://remix.kwed.org/?search_id=852		Disregard?
		           id					85200										Useful to find single entries?
		formatted->title				Cobra										Can be long and silly
				   arranger_id			3900										Disregard?
		formatted->comments				?											Need to see example
				   sid_subtune			1											Might need to show this
				   download_prim		https://remix.kwed.org/download...			Use this one (need permission)
				   has_info				false										Disregard?
				   has_review			false										Disregard?
				   total_average		56.8627										Disregard?
				   total_score			56.8627										Disregard?
				   total_smiley			4
				   total_voters			17
				   mix_page				track/wolk/cobra/							Disregard?
				   link_full			https://www.remix64.com/track/wolk/cobra/	For external link to Remix64?
				   info->about			Okay peeps...								Arranger's info about remix
				   image1				/images/members/...							Optional; appears in top
				   image2				/images/members/...							Optional; appears in bottom
				   itemprop_duration	PT3M2S										?
				   download_url			https://remix.kwed.org/download...			Sometimes links to archive.org!

		Credits:

			- arranger_id		Relates to the platform from which it originates (RKO, AmigaRemix, etc.)
			- act_id 			Relates to the artist; so LMan RKO and LMan AmigaRemix are combined under act_id "lman"
			- member			Relates to the user account at Remix64

	*/

	$entries = array();
	foreach($data->data->data as $remix64_entry) {


		$entry = 
			'<tr>'.
				'<td class="rank">'.
					'<div class="remix64-rank">RANK<div>'.$remix64_entry->charts_data->position.'</div></div>'.
				'</td>'.
				'<td class="info">'.
					'<a class="name" href="'.$remix64_entry->link_full.'" target="_blank">'.$remix64_entry->formatted->title.'</a><br />'.
					substr($remix64_entry->releasedate, 0, 10).
						' by '.$remix64_entry->arranger->noob_status->tag.
						' <a href="'.$remix64_entry->arranger->link_full.'" target="_blank">'.$remix64_entry->arranger->formatted->arranger_name.'</a>'.
					'<div class="remix64-smiley">'.ceil($remix64_entry->total_score).'% '.
						'<a href="#" onclick="window.open(&quot;https://www.remix64.com/box.php?id='.$remix64_entry->id.'&quot;, &quot;votebox&quot;, &quot;toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=630,height=600,left=350,top=100,screenX=450,screenY=300&quot;); return false;" title="Vote">'.
						'<img src="https://www.remix64.com/gfx/remix4/remix4/sizes-32x32/sm_'.$remix64_entry->total_smiley.'.png" alt="" /></a></div>'.
				'</td>'.
			'</tr>';

		array_push($entries, array(
			'rank'		=> $remix64_entry->charts_data->position,
			'html'		=> $entry,
		));
	}
	usort($entries, function($a, $b) {
		return $a['rank'] - $b['rank'];
	});
	$rows = '';
	foreach($entries as $entry)
		$rows .= $entry['html'];





	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-top:0;">Remix64</h2>'.
		'<h3>'.count($data->data->data).' entr'.(count($data->data->data) > 1 ? 'ies' : 'y').' found</h3>'.
		'<table class="releases">'.
			$rows.
		'</table>';

	//$html = 'https://www.remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash='.$hash.'&data='.$encoded;






	//$remixes = [1];

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

echo json_encode(array('status' => 'ok', 'html' => $html, 'count' => count($data->data->data)));
?>