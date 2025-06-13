<?php
/**
 * DeepSID
 *
 * Builds an HTML page with links to Remix64 entries or a specific entry.
 * 
 * @uses		$_GET['fullname']			for a page with links to sub pages
 * 
 * @used-by		browser.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

/**
 * Modified version of 'curl' from the 'setup.php' script.
 *
 * @param	    string		$url                URL to obtain data from
 *
 * @return	    string		$data               data from the URL
 */
function curl2($url) {

    $ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $data = curl_exec($ch);
	$info = curl_getinfo($ch);

	// Strip headers
	$headerSize = $info['header_size'];
	$data = substr($data, $headerSize);

	curl_close($ch);

    return $data;
}

$remixes = array();

if (isset($_GET['fullname'])) {	

	$hvsc_path = str_replace('_High Voltage SID Collection/', '', $_GET['fullname']);
	$encoded = json_encode(array('hvsc_path' => $hvsc_path));
	$hash = md5('deepsid'.$encoded.REMIX64_API.'get_remixes');

	try {
		// Get the entire JSON tree for the SID file from Remix64
		// @example https://www.remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash=d0f7e95f7e2e4ca1f50a8aaf66ca3808&data={%22hvsc_path%22:%22MUSICIANS\/D\/Daglish_Ben\/Cobra.sid%22}
		$data = substr($hvsc_path, -4) != '.mus' && !strpos($hvsc_path, 'Exotic SID Tunes Collection') && !strpos($hvsc_path, 'SID Happens')
			? curl2('https://remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash='.$hash.'&data='.urlencode($encoded))
			: json_encode(array('error_code' => 'SH, CGSC and ESTC not supported'));
	} catch(ErrorException $e) {
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin:0;"><i>Uh... Remix64? Are you there?</i></p><small>Come on, Remix64, old buddy, don\'t let me down.</small>')));
	}

	//die(json_encode(array('status' => 'info', 'html' => 'API call: https://remix64.com/services/api/de/deepsid/?task=get_remixes&api_user=deepsid&hash='.$hash.'&data='.$encoded)));
	//die(json_encode(array('status' => 'info', 'html' => '{'.json_encode($data).'}')));

	$data = json_decode($data);

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

	$rows = '';
	if ($data->error_code == "ok") {

		$amount = count($data->data->data);
		$entries = array();

		foreach($data->data->data as $remix64_entry) {
			$entry = 
				'<tr>'.
					'<td class="action">'.
						'<button class="remix64-action button-big button-idle">'.
							'<svg class="remix64-play" height="40" viewBox="0 0 48 48"><path d="M-838-2232H562v3600H-838z" fill="none"/><path d="M16 10v28l22-14z"/><path d="M0 0h48v48H0z" fill="none"/></svg>'.
							'<svg class="remix64-pause" height="40" viewBox="0 0 48 48" style="display:none;"><path d="M12 38h8V10h-8v28zm16-28v28h8V10h-8z"/><path d="M0 0h48v48H0z" fill="none"/></svg>'.
						'</button>'.
						'<div class="down"></div>'.
					'</td>'.
					'<td class="info">'.
						'<a class="name" href="'.$remix64_entry->link_full.'" target="_blank">'.$remix64_entry->formatted->title.'</a>'.
							'<span class="remix64-length">('.$remix64_entry->formatted->duration.')</span><br />'.
						(isset($remix64_entry->releasedate) ? substr($remix64_entry->releasedate, 0, 10) : '').
							' by '.$remix64_entry->arranger->noob_status->tag.
							' <a href="'.$remix64_entry->arranger->link_full.'" target="_blank"><b>'.$remix64_entry->arranger->formatted->arranger_name.'</b></a><br />'.
						'<div class="remix64-smiley">'.ceil($remix64_entry->total_score).'% '.
							'<a href="#" onclick="window.open(&quot;https://www.remix64.com/box.php?id='.$remix64_entry->id.'&quot;, &quot;votebox&quot;, &quot;toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=630,height=600,left=350,top=100,screenX=450,screenY=300&quot;); return false;" title="Vote">'.
							'<img src="images/sm_'.$remix64_entry->total_smiley.'.png" alt="" /></a>'.
						'</div>'.
						
						'<div class="remix64-rank">'.
							'Rank: '.$remix64_entry->charts_data->position.
						'</div>'.
					'</td>'.
				'</tr>'.
				'<tr class="remix64-more">'.
					'<td colspan="2">'.
						'<div class="remix64-expander" data-download="'.$remix64_entry->download_prim.'" data-lookup="'.$remix64_entry->lookup_url.'">'.
							'<div class="remix64-connect"><div class="up"></div><div class="right"></div></div><div class="remix64-audio"></div>'.
						'</div>'.
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

		foreach($entries as $entry)
			$rows .= $entry['html'];

	} else
		$amount = 0; // No remixes found

// Now build the HTML
$html = '<h2 style="display:inline-block;margin-top:0;">Remix64</h2>'.
	'<h3>'.$amount.' entr'.($amount == 0 || $amount > 1 ? 'ies' : 'y').' found'.
	($amount == 0 ? '</h3><div class="zero-releases-line"></div>': '<div class="remix64-vote"></div></h3>' ).
	'<table class="releases">'.
		$rows.
	'</table>';

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

echo json_encode(array('status' => 'ok', 'html' => $html.'<i><small>Generated using an API for <a href="https://www.remix64.com/" target="_blank">Remix64.com</a></small>', 'count' => $amount));
?>