<?php
/**
 * DeepSID
 *
 * Show the test page in the 'Admin' tab.
 * 
 * For administrators only.
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");
if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

$html = '';
$baseURL = $_SERVER['HTTP_HOST'] == LOCALHOST ? "http://chordian/deepsid/" : "https://deepsid.chordian.net/";

$home = '~';

$html = '
	<h3>Test links</h3>
		<h4>Long headers in CSDb tab</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/J/Jammer/Rippin_Off_the_Most_Jazzy_Lads.sid&tab=csdb">'.$home.'/MUSICIANS/J/Jammer/Rippin_Off_the_Most_Jazzy_Lads.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/F/Fegolhuzz/Emergent_Behavior_of_Hydrogen.sid&tab=csdb">'.$home.'/MUSICIANS/F/Fegolhuzz/Emergent_Behavior_of_Hydrogen.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/B/Bakker_Nantco/When_Im_64.sid&tab=csdb">'.$home.'/MUSICIANS/B/Bakker_Nantco/When_Im_64.sid</a>&nbsp;&nbsp;(list entry)</li>
			</ul>
		
		<h4>Long headers in SHOW compo in CSDb tab</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/T/The_Dungeon_Master/Fairy_Well.sid&subtune=1&tab=csdb">'.$home.'/MUSICIANS/T/The_Dungeon_Master/Fairy_Well.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/W/Wijnhoven_Joachim/Devil_Ronin.sid&tab=csdb">'.$home.'/MUSICIANS/W/Wijnhoven_Joachim/Devil_Ronin.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/C/Chiummo_Aldo/Unreadibility.sid&tab=csdb">'.$home.'/MUSICIANS/C/Chiummo_Aldo/Unreadibility.sid</a></li>
			</ul>

		<h4>Long headers in GB64 tab</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/B/Bogas_Ed/Murder_on_the_Mississippi.sid&tab=gb64">'.$home.'/MUSICIANS/B/Bogas_Ed/Murder_on_the_Mississippi.sid</a></li>
			</ul>

		<h4>Long group names in group box</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/L/Luca/">'.$home.'/MUSICIANS/L/Luca/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/M/Mr_Mouse/">'.$home.'/MUSICIANS/M/Mr_Mouse/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/N/Nightbreed/">'.$home.'/MUSICIANS/N/Nightbreed/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/D/Deathangels_Shadow/">'.$home.'/MUSICIANS/D/Deathangels_Shadow/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/D/Demosic/">'.$home.'/MUSICIANS/D/Demosic/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Galancy/">'.$home.'/MUSICIANS/G/Galancy/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Gaston/">'.$home.'/MUSICIANS/G/Gaston/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Gillgrass_Dan/">'.$home.'/MUSICIANS/G/Gillgrass_Dan/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Gillies_Ewen/">'.$home.'/MUSICIANS/G/Gillies_Ewen/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Gilmore_Adam/">'.$home.'/MUSICIANS/G/Gilmore_Adam/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Green_David/">'.$home.'/MUSICIANS/G/Green_David/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Groepaz/">'.$home.'/MUSICIANS/G/Groepaz/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/Harlequin/">'.$home.'/MUSICIANS/H/Harlequin/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/Hero/">'.$home.'/MUSICIANS/H/Hero/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/HIC/">'.$home.'/MUSICIANS/H/HIC/</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/Hulten_Jonas/">'.$home.'/MUSICIANS/H/Hulten_Jonas/</a></li>
			</ul>

		<h4>Too many DL links on a CSDb list page (deprecated)</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/O/Onebitman/Walking_in_the_Air.sid&tab=csdb">'.$home.'/MUSICIANS/O/Onebitman/Walking_in_the_Air.sid</a></li>
			</ul>

		<h4>Legacy CSDb cache data with no \'BACK\' button for sticky header</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/G-Fellow/A_Message_for_You.sid&tab=csdb">'.$home.'/MUSICIANS/G/G-Fellow/A_Message_for_You.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/G-Fellow/Neural_Consciousness_V2.sid&tab=csdb">'.$home.'/MUSICIANS/G/G-Fellow/Neural_Consciousness_V2.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/G-Fellow/Wigwam.sid&tab=csdb">'.$home.'/MUSICIANS/G/G-Fellow/Wigwam.sid</a></li>
			</ul>

		<h4>Testing max-width on block-wrap for song before inline factoid</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/Hermit/Song_of_the_Second_Moon_Delta_2SID.sid">'.$home.'/MUSICIANS/H/Hermit/Song_of_the_Second_Moon_Delta_2SID.sid</a></li>
			</ul>

		<h4>Dark background on event logo graphics that wants light background</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FDecrunch%202117">'.$home.'%2FCSDb%20Music%20Competitions%2FDecrunch%202117</a></li>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FWorld%20Wide%20ZOO">'.$home.'%2FCSDb%20Music%20Competitions%2FWorld%20Wide%20ZOO</a></li>
			</ul>

		<h4>Light background on event logo graphics that wants dark background</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FEdison%202017">'.$home.'%2FCSDb%20Music%20Competitions%2FEdison%202017</a></li>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FP%C3%A5gadata%202023">'.$home.'%2FCSDb%20Music%20Competitions%2FP%C3%A5gadata%202023</a></li>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FRevision%202017">'.$home.'%2FCSDb%20Music%20Competitions%2FRevision%202017</a></li>
				<li><a href="'.$baseURL.'?file=%2FCSDb%20Music%20Competitions%2FXenium%202022%20%5BMixed%5D">'.$home.'%2FCSDb%20Music%20Competitions%2FXenium%202022%20%5BMixed%5D</a></li>
			</ul>

		<h4>Unknown years</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/T/TCC/">'.$home.'/MUSICIANS/T/TCC/</a>&nbsp;&nbsp;(199?)</li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/T/The_Invincible_Cracker/">'.$home.'/MUSICIANS/T/The_Invincible_Cracker/</a>&nbsp;&nbsp;(198?)</li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/H/Hogg_Steve/">'.$home.'/MUSICIANS/H/Hogg_Steve/</a>&nbsp;&nbsp;(19??)</li>
			</ul>

		<h4>Tons of links in STIL text</h4>
			<ul>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/B/Bulldog/E_X_I_S.sid&subtune=1">'.$home.'/MUSICIANS/B/Bulldog/E_X_I_S.sid</a></li>
				<li><a href="'.$baseURL.'?file=/MUSICIANS/G/Greg/Leppard.sid">'.$home.'/MUSICIANS/G/Greg/Leppard.sid</a></li>
			</ul>
';

die(json_encode(array('status' => 'ok', 'html' => $html)));
?>