<?php
/**
 * DeepSID
 *
 * Returns a randomly chosen message for the first tab of the sundry box.
 * 
 * @uses		$_GET['lemon']			1 = Site is used in an iframe at Lemon64.com
 * 
 * @used-by		controls.js
 */

require_once("class.account.php"); // Includes setup

$random = array(

	// LINK: Vincenzo's YouTube tutorial for SF2

	array(
		'type'		=> 'link',
		'lemon'		=> true,
		'message'	=> '
			Want to learn how to make SID tunes? Check out
			<a href="https://www.youtube.com/watch?v=nXNtLetxFUg">this tutorial video</a>
			now on YouTube. There are two parts in this series so far.
		'
	),

	// LINK: Raistlin's SIDquake

	array(
		'type'		=> 'link',
		'lemon'		=> true,
		'message'	=> '
			<p style="margin:0 0 4px 0;">Have you made a new SID tune and need to slap a cool
			visualizer with a logo on it?</p><b style="font-size:22px;">Check out
			<a href="https://sidquake.c64demo.com/">SIDquake</a> by Raistlin!</b>
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			<p style="margin:0 0 4px 0;">A small star icon in front of a folder means that everything
			inside has been rated.</p><b style="font-size:20px;">Handy for completionists.</b>
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			WebSid, the default emulator, provides the most complete support for DeepSID\'s UI
			features, including ADSR-affected piano keys.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			reSID offers the most faithful emulation, at the cost of a few minor UI features. You can
			select it in the drop-down box above.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			Press hotkeys 1, 2, or 3 (or Q, W, E) to toggle individual SID voices on and off. Use
			Shift + 1, 2, 3 (or Shift + Q, W, E) to solo a voice.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			Press \'L\' to temporarily load a SID tune for emulator testing. It\'s private to you
			and it\'s deleted when you exit the mode.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			Most emulators support two fast-forward speeds. Left-click the button for normal speed,
			or middle-click it for double speed.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			Press \'+\' anywhere to focus the search box, then type a partial composer name or handle
			to instantly jump to their folder.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			Notice the thick edge just below this text? You can drag it up or down to resize the box.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			You can also fast-forward by holding down the key just below Escape. Hold Shift as well
			for double fast-forward speed.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> true,
		'message'	=> '
			The blue bar shows the total C64 memory. When a SID tune is playing, the dark blue area
			shows where the music resides. Hover to see memory usage, or click for more details.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			To see a list of all the hotkeys supported by DeepSID, click this link to open the topic
			in the right-side annex box: <a class="annex-link" href="7">Hotkeys</a>
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			A vertical color strip on the left side of a SID row indicates a typical player used.
			The most common color is gray, which represents GoatTracker 2.
			More: <a class="annex-link" href="6">Color strips</a>
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			You can create your own playlists. To start one, right-click a SID tune and select \'Add
			to New Playlist\' from the context menu.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			Use the round button below the top-left logo to toggle between bright and dark color
			themes. The setting is remembered.
		'
	),

	array(
		'type'		=> 'tips',
		'lemon'		=> false,
		'message'	=> '
			To search by your ratings, select \'Rating\' in the bottom drop-down box and enter a number
			from 1 to 5. Add a dash before or after the number to search a range.
		'
	),
);

switch ($account->GetAdminSetting('sundry_message')) {
	case 'random':
		// Are we on Lemon64.com?
		if (isset($_GET['lemon']) && $_GET['lemon'] == "true") {
			$random = array_values(array_filter(
				$random,
				function ($tip) {
					return !empty($tip['lemon']);
				}
			));
		}
		// Show a random message
		$index = mt_rand(0, count($random) - 1);

		$html = '<span>'.$random[$index]['message'].'</span>';
		$type = $random[$index]['type'];
		break;
	case 'hvsc':
		// New HVSC update
		$html = '
			<span>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a>
			has been upgraded to the latest version #'.HVSC_VERSION.'.
			Click <a href="//deepsid.chordian.net/?search='.HVSC_VERSION.'&type=new">here</a>
			to see what\'s new in this update.</span>';
		$type = 'news';
		break;
	case 'cgsc':
		// New CGSC update
		$html = '
			<span><a href="http://www.c64music.co.uk/" target="_top">Compute\'s Gazette SID Collection</a>
			has been upgraded to the latest version #'.CGSC_VERSION.'.
			Click <a href="//deepsid.chordian.net/?search='.CGSC_VERSION.'&type=new">here</a>
			to see what\'s new in this update.</span>';
		$type = 'news';
		break;
}
echo json_encode(array('status' => 'ok', 'html' => $html, 'type' => $type));
?>