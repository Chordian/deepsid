<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and build a list of forum topics.
 */

require_once("setup.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$html = '<h3 style="margin-top:10px;">Forum threads from CSDb</h3>
	<p>Here\' s a list of interesting forum threads from CSDb with lots of juicy links to SID tunes. Normally, the
		original thread sometimes required you to hunt for these tunes yourself or at best rely on an already installed
		offline SID player. These threads have been adapted with "plinks" which are HVSC path links that, when clicked,
		will automatically find and play the relevant tune here in DeepSID while still reading the forum thread.
	</p>
	<table style="font-size:14px;">
		<tr><th style="width:125px;">Room</th><th style="width:340px;">Topic</th><th>Description</th></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="131591">sid sound hunt</a></td><td>SID tunes with guitar-like sounds</td></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="40934">Best SID instrument?</a></td><td>SID tunes with great instruments (no digi samples)</td></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="82192">The most moody c64 tune, ever!</a></td><td>SID tunes that invoke a moody feeling</td></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="127786">Multi timberal SIDs</a></td><td>Overcoming the limitation of three channels</td></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="115038">Recommendations for sad SIDs (tearjerkers)?</a></td><td>Eye wateringly sad SID tunes</td></tr>
		<tr><td>C64 Composing</td><td><a href="#" class="thread" data-roomid="14" data-topicid="44909">Useless(?) facts about sids</a></td><td>Mostly about multi-speed SID tunes</td></tr>
	</table>';

$sticky = '<h2 style="margin-top:0;">Forums</h2>';

echo json_encode(array('status' => 'ok', 'sticky' => $sticky, 'html' => $html));
?>