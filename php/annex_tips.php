<?php
/**
 * DeepSID
 *
 * Returns a randomly chosen block of HTML for the annex box with tips.
 * 
 * @uses		$_GET['id']			optional
 *
 * @used-by		main.js
 */

function MakeSiteLink($url, $header, $type = '') {

	// Ensure proper escaping
    $escUrl    = htmlspecialchars($url,    ENT_QUOTES, 'UTF-8');
    $escHeader = htmlspecialchars($header, ENT_QUOTES, 'UTF-8');
    $escType   = htmlspecialchars($type,   ENT_QUOTES, 'UTF-8');

	// $escType = !empty($escType) ? '[' . $escType . ']' : '';

    // Pre-encode URL for Microlink
    $encodedUrl = rawurlencode($url);

    return '
        <li class="site-card">
            <a class="site-link"
                href="' . $escUrl . '"
                target="_blank" rel="noopener"
                data-url="' . $escUrl . '">
                <img class="thumb"
                    src="https://api.microlink.io/?url=' . $encodedUrl . '&screenshot=true&meta=false&embed=screenshot.url"
                    alt="' . $escHeader . '" loading="lazy">
                <h3 class="site-header">' . $escHeader . '</h3>
            </a><span class="site-type">' . $escType . '</span>
        </li>';
}

$tips = array(

	'	<h3>Playlists</h3>

		<h4>How to start a new playlist:</h4>
		<ol>
			<li>Right-click any SID song.</li>
			<li>Click <b>Add to New Playlist</b>.</li>
			<li>Rename it if you wish.</li>
			<li>Find it in the root bottom.</li>
		</ol>

		<h4>How to add more tunes to it:</h4>
		<ol>
			<li>Right-click any SID song.</li>
			<li>Hover on <b>Add to Playlist</b>.</li>
			<li>Click it in the context list.</li>
		</ol>

		<h4>Renaming playlist tunes:</h4>
		<ol>
			<li>Enter your playlist in root.</li>
			<li>Right-click any SID row.</li>
			<li>Click <b>Rename</b> and type it.</li>
		</ol>
	',

	'	<h3>Memory bar</h3>

		<h4>See that blue bar to the left?</h4>
		<p>It shows the entire memory of a C64, from $0000 to $FFFF.</p>
		<p>When you click a SID file, a dark blob shows size and location.</p>
		<img src="images/tips_memory_bar.png" alt="Memory bar" />
		Want more info? Click it. This will take you to the <b>MEMO</b> view.
	',

	'	<h3>External linking</h3>

		<h4>Linking to specific SID files</h4>
		<p>Just copy the URL in the address bar of your web browser.</p>
		To show the <b>CSDb</b> tab as the link is clicked, append <b>&tab=csdb</b>.

		<h4>Linking to a search query</h4>
		<ol>
			<li>Search for something.</li>
			<li>Right-click the <svg style="enable-background:new 0 0 80 80;position:relative;top:1.5px;width:12px;height:12px;fill:var(--color-text-body);" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg> icon.</li>
			<li>Choose to copy the link.</li>
		</ol>

		<h4>Linking to an editor page</h4>
		<ol>
			<li>Click <b>PLAYERS</b> in top menu.</li>
			<li>Click an editor thumbnail.</li>
			<li>Right-click the <svg style="enable-background:new 0 0 80 80;position:relative;top:1.5px;width:12px;height:12px;fill:var(--color-text-body);" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg> icon.</li>
			<li>Choose to copy the link.</li>
		</ol>
	',

	'	<h3>Registering</h3>

		<h4>Registering a new user</h4>
		<p>Just click the <b>Register</b> link above the user name and password boxes to begin the registration process.</p>
		<b>So, what can you do when logged in?</b>

		<h4>Rate tunes and folders</h4>
		<p>Clicking the stars is for your eyes only. No one else can see this.</p>

		<h4>Consider if SID happens</h4>
		<p>You can upload and edit SID files in the <b><a href="//deepsid.chordian.net/?file=/SID Happens/">SID Happens</a></b> folder.</p>
		<h4>Manage your own playlists</h4>
		<p>You can create a playlist and add SID files to it. See <a href="0" class="topic">this</a> for more.</p>

		<h4>Add or edit tags</h4>
		<ol>
			<li>Hover on any SID row.</li>
			<li>Click the small [+] icon.</li>
			<li>Use the dialog box.</li>
		</ol>

		<h4>Add or edit YouTube links</h4>
		<ol>
			<li>Change to <b><a href="//deepsid.chordian.net?emulator=youtube">YouTube videos</a></b>.</li>
			<li>Right-click any SID row.</li>
			<li>Yes, even if it\'s disabled.</li>
			<li>Click <b>Edit YouTube Links</b>.</li>
			<li>Use the dialog box.</li>
		</ol>

		<h4>Add or edit composer links</h4>
		<p>When you see a profile page for a composer, click <b>Links</b>.</p>
		<p>This annex box then updates, and you can edit these links too.</p>

		<h4>Adjust your preferences</h4>
		<p>Click the cogwheel tab right next to the top of this box.</p>
		This will show your settings.
	',

	'	<h3>SID handlers</h3>
		<p>If you click the drop-down box in the top left corner, another SID handler can be chosen.</p>

		<h4><a href="//deepsid.chordian.net?emulator=resid">reSID (BETA)</a></h4>
		<p>Uses the renowned reSID engine and offers excellent emulation.</p>
		It is only about 30% slower than the WebSid emulator.

		<h4><a href="//deepsid.chordian.net?emulator=jsidplay2">JSIDPlay2 (reSID)</a></h4>
		<p>Also uses the reSID engine and offers the same emulation.</p>
		Requires a really fast CPU.

		<h4><a href="//deepsid.chordian.net?emulator=websid">WebSid emulator</a></h4>
		<p>The default for computers.</p>
		It processes cycle-by-cycle, and it emulates almost everything.

		<h4><a href="//deepsid.chordian.net?emulator=legacy">WebSid (Legacy)</a></h4>
		<p>The default for mobile devices.</p>
		Faster, but not as efficient. Use this if your computer is slow.

		<h4><a href="//deepsid.chordian.net?emulator=jssid">Hermit\'s (+FM)</a></h4>
		<p>Even faster, but cannot emulate RSID tunes, nor SID with digi.</p>
		This can also play SID+FM tunes, i.e. with OPL synthesis added.

		<h4><a href="//deepsid.chordian.net?emulator=asid">ASID (MIDI)</a></h4>
		<p>Use with MIDI devices such as e.g. SidStation, TherapSID, etc.</p>

		<h4><a href="//deepsid.chordian.net?emulator=asid">WebUSB (Hermit)</a></h4>
		<p>Use with USB devices such as e.g. <a href="https://github.com/LouDnl/USBSID-Pico">USBSID-Pico</a>.</p>

		<h4><a href="//deepsid.chordian.net?emulator=lemon">Lemon\'s MP3 files</a></h4>
		<p>It plays MP3 files recorded from JSIDPlay2 (only
		<a href="https://www.hvsc.c64.org/">HVSC</a> for now).</p>

		<h4><a href="//deepsid.chordian.net?emulator=youtube">YouTube videos</a></h4>
		<p>Plays a YouTube video where SID rows are enabled for clicking.</p>
		One SID row can contain several videos, and you can add more.

		<h4><a href="//deepsid.chordian.net?emulator=download">Download SID file</a></h4>
		<p>Clicking a SID row will download it to your computer.</p>
		Useful if you have associated the SID files to automatically
		run an external program.

		<h4><a href="//deepsid.chordian.net?emulator=silence">No SID handler</a></h4>
		<p>Obviously plays no music at all.</p>
		Useful when you only want to browse for information. The
		auto-play overlay is not shown.
	',

	'	<h3>Searching</h3>
		<p>Type something in the text box in the left side then hit <b>SEARCH</b>.</p>
		<p>If you tick <b>Here</b> too, then only the current folder will be searched.</p>
		<b>The options in the drop-down box:</b>

		<h4>All</h4>
		Searches in (almost) everything. Filenames, STIL, author, etc.

		<h4>Filename</h4>
		Searches in filenames only.

		<h4>Author</h4>
		Typically contain names, handles, or both. No year or affiliation.

		<h4>Copyright</h4>
		Also known as the <i>released</i> field. Contains year and affiliation.

		<h4>Player</h4>
		Expects raw <b>SIDId</b> player names, not the prettified ones.

		<h4>Location</h4>
		Searches for a start location, e.g. 16384, 0x4000, or $4000.

		<h4>Maximum</h4>
		Searches for a maximum size, e.g. 4095, 0x0FFF, or $0FFF.

		<h4>Type</h4>
		Searches for a SID type, e.g. "rsid" for those types only.

		<h4>Tags</h4>
		<p>Searches for one or more tags.</p>
		Enclose in quotes to search a tag with spaces between words.

		<h4>STIL</h4>
		Searches in <a href="https://www.hvsc.c64.org/download/C64Music/DOCUMENTS/STIL.faq" target="_blank">STIL</a> texts.

		<h4>Rating</h4>
		<p>Searches for your ratings. Use 1 to 5, optionally with minus.</p>
		For example, typing <a href="3-" data-type="rating" class="search">3-</a> searches for tunes rated 3 stars or more.

		<h4>Country</h4>
		Searches for composers from a specific country. Only a list of folders is returned here.

		<h4>Focus</h4>
		Searches for focus letters. Use "basic" or "botb" for <b>B</b> types.

		<h4>Version</h4>
		<p>Searches for all files and folders included in a specific update version of <a href="//deepsid.chordian.net/?file=/High%20Voltage%20SID%20Collection/">HVSC</a> or
		<a href="//deepsid.chordian.net/?file=/Compute\'s%20Gazette%20SID%20Collection/">CGSC</a>.</p>

		<h4>Latest</h4>
		<p>Searches for the files added in the latest HVSC update by the specified composer.</p>
		You can also append a different HVSC version, e.g. <b>danko,72</b>.

		<h4>Game</h4>
		Searches for songs made for the specified game.
	',

	'	<h3>Color strips</h3>
		<p>Sometimes you can see a vertical color strip in a SID row. These indicate a common player.</p>
		<div class="annex-strip annex-a"> = <b>GoatTracker</b></div><span class="annex-tiny"><a href="goattracker_v1.x" data-type="player" class="search"><b>v1.x</b></a> or <a href="goattracker_v2.x" data-type="player" class="search"><b>v2.x</b></a></span>
		<div class="annex-strip annex-b"> = <b>NewPlayer</b></div><span class="annex-tiny">JCH\'s <a href="newplayer_-v18_-v19_-v20_-v21" data-type="player" class="search"><b>v2</b></a> / <a href="newplayer_v20" data-type="player" class="search"><b>v3</b></a>, etc.</span>
		<div class="annex-strip annex-c"> = <b>SID-Wizard</b></div><span class="annex-tiny"><a href="sidwizard_v1.x" data-type="player" class="search"><b>v1.x</b></a></span>
		<div class="annex-strip annex-d"> = <b>SID Factory II</b></div><span class="annex-tiny"><a href="sidfactory_ii" data-type="player" class="search"><b>BETA</b></a></span>
		<div class="annex-strip annex-e"> = <b>DMC</b></div><span class="annex-tiny"><a href="dmc_v4.x" data-type="player" class="search"><b>v4.x</b></a>, <a href="dmc_v5.x" data-type="player" class="search"><b>v5.x</b></a>, etc.</span>
		<div class="annex-strip annex-f"> = <b>SidTracker 64</b></div><span class="annex-tiny"><a href="sidtracker64" data-type="player" class="search"><b>iPad</b></a></span>
	',

	'	<h3>Hotkeys</h3>
		
		<h4>General hotkeys</h4>
		<div class="annex-hotkey">Space</div><span class="annex-tiny">Pause/Play toggle</span>
		<div class="annex-hotkey">Left arrow</div><span class="annex-tiny">Previous SID row</span>
		<div class="annex-hotkey">Right arrow</div><span class="annex-tiny">Next SID row</span>
		<div class="annex-hotkey">Backspace</div><span class="annex-tiny">Back to parent folder</span>
		<div class="annex-hotkey">Shift+Backspc</div><span class="annex-tiny">Back to parent tab page</span>
		<div class="annex-hotkey">[<i>Below</i>&nbsp;]&nbsp;Esc</div><span class="annex-tiny">Fast forward</span>
		<div class="annex-hotkey">p</div><span class="annex-tiny">Pop-up tiny DeepSID</span>
		<div class="annex-hotkey">s</div><span class="annex-tiny">Toggle sundry box</span>
		<div class="annex-hotkey">l</div><span class="annex-tiny">Upload SID for testing</span>
		<div class="annex-hotkey">b</div><span class="annex-tiny">Back from plink</span>
		<div class="annex-hotkey">f</div><span class="annex-tiny">Refresh folder</span>
		<div class="annex-hotkey">t</div><span class="annex-tiny">Edit tags</span>
		<div class="annex-hotkey">y</div><span class="annex-tiny">Toggle tags on/off</span>
		<div class="annex-hotkey">u</div><span class="annex-tiny">Cycle factoids</span>
		<div class="annex-hotkey">+</div><span class="annex-tiny">Search command</span>
		<p>Testing a SID is temporary and for you only. No one else can hear it.</p>
		A "plink" is a link with a play icon in front of it, e.g. in the <b>CSDb</b> tab.
		
		<h4>Hotkeys for emulators only</h4>
		<div class="annex-hotkey">1 / q</div><span class="annex-tiny">Toggle voice 1 on/off</span>
		<div class="annex-hotkey">2 / w</div><span class="annex-tiny">Toggle voice 2 on/off</span>
		<div class="annex-hotkey">3 / e</div><span class="annex-tiny">Toggle voice 3 on/off</span>
		<div class="annex-hotkey">4 / r</div><span class="annex-tiny">Toggle digi on/off</span>
		<div class="annex-hotkey">Shift&nbsp;&nbsp;&nbsp;1 / q</div><span class="annex-tiny">Toggle solo voice 1</span>
		<div class="annex-hotkey">Shift&nbsp;&nbsp;&nbsp;2 / w</div><span class="annex-tiny">Toggle solo voice 2</span>
		<div class="annex-hotkey">Shift&nbsp;&nbsp;&nbsp;3 / e</div><span class="annex-tiny">Toggle solo voice 3</span>
		<div class="annex-hotkey">Shift&nbsp;&nbsp;&nbsp;4 / r</div><span class="annex-tiny">Toggle solo digi</span>
		<p>The digi hotkeys only work for the legacy version of WebSid.</p>
	',

	'	<h3>URL parameters</h3>
		<p>Here are the URL parameters you can use for external links:</p>
		<table class="annex-table">
			<tr>
				<td>file</td><td>File to play; folder to show.</td>
			</tr>
			<tr>
				<td>subtune</td><td>Use with <b>file</b>.</td>
			</tr>
			<tr>
				<td>emulator</td><td>Temporarily override the emulator.&nbsp;&nbsp;Options:
					<ul>
						<li><b>resid</b></li>
						<li><b>jsidplay2</b></li>
						<li><b>websid</b></li>
						<li><b>legacy</b></li>
						<li><b>hermit</li>
						<li><b>asid</b>&nbsp;&nbsp;(MIDI)</li>
						<li><b>webusb</li>
						<li><b>lemon</b></li>
						<li><b>youtube</b></li>
						<li><b>download</b></li>
						<li><b>silence</b></li>
					</ul>
				</td>
			</tr>
			<tr>
				<td>search</td><td>A search query.</td>
			</tr>
			<tr>
				<td>type</td><td>Use with <b>search</b>.&nbsp;&nbsp;Options:
					<ul>
						<li><b>fullname</b>&nbsp;&nbsp;(title)</li>
						<li><b>author</b></li>
						<li><b>copyright</b></li>
						<li><b>player</b></li>
						<li><b>location</b>&nbsp;&nbsp;(start)</li>
						<li><b>maximum</b>&nbsp;&nbsp;(size)</li>
						<li><b>tag</b></li>
						<li><b>stil</b></li>
						<li><b>rating</b></li>
						<li><b>country</b></li>
						<li><b>new</b>&nbsp;&nbsp;(version)</li>
						<li><b>latest</b></li>
						<li><b>gb64</b>&nbsp;&nbsp;(game)</li>
					</ul>
				</td>
			</tr>
			<tr>
				<td>here</td><td><b>1</b> to search in the current folder.&nbsp;&nbsp;Use with <b>search</b>.</td>
			</tr>
			<tr>
				<td>tab</td><td>Select a page tab.&nbsp;&nbsp;Options:
					<ul>
						<li><b>csdb</b></li>
						<li><b>remix</b></li>
						<li><b>player</b></li>
						<li><b>stil</b></li>
						<li><b>visuals</b></li>
						<li><b>about</b></li>
						<li><b>faq</b></li>
						<li><b>changes</b></li>
						<li><b>settings</b></li>
					</ul>
				</td>
			</tr>
			<tr>
				<td>sundry</td><td>Select sundry tab.&nbsp;&nbsp;Options:
					<ul>
						<li><b>stil</b>&nbsp;&nbsp;(or <b>lyrics</b>)</li>
						<li><b>tags</b></li>
						<li><b>scope</b>&nbsp;&nbsp;(or <b>osc</b>)</li>
						<li><b>filter</b></li>
						<li><b>stereo</b></li>
					</ul>
				</td>
			</tr>
			<tr>
				<td>player</td><td>Use ID of player page.&nbsp;&nbsp;See a permalink to get it right.</td>
			</tr>
			<tr>
				<td>csdbtype</td><td>Show <b>CSDb</b> entry.&nbsp;&nbsp;Use with <b>csdbid</b> below.&nbsp;&nbsp;Options:
					<ul>
						<li><b>release</b></li>
						<li><b>sid</b></li>
					</ul>
				</td>
			</tr>
			<tr>
				<td>csdbid</td><td>Show <b>CSDb</b> entry.&nbsp;&nbsp;Use with <b>csdbtype</b> above.</td>
			</tr>
			<tr>
				<td>mini</td><td><b>0</b> for normal (desktop and mobile); <b>1</b> for mini player.</td>
			</tr>
			<tr>
				<td>mobile</td><td><b>0</b> to force desktop view; <b>1</b> to force mobile view.</td>
			</tr>
			<tr>
				<td>cover</td><td><b>1</b> to force showing an auto-play cover overlay.</td>
			</tr>
			<tr>
				<td>wait</td><td>Select but do not play the song.&nbsp;&nbsp;Value is <b>ms</b> before pausing.&nbsp;&nbsp;<b>100</b> works well.</td>
			</tr>
			<tr>
				<td>notips</td><td><b>1</b> to avoid showing the annex box with these tips.</td>
			</tr>
		</table>

	',

	'	<h3>Social links</h3>

		<h4><a href="https://www.facebook.com/groups/deepsid/">Facebook</a></h4>
		A group with requests or bugs from other users as well as news about everything SID-related.

		<h4><a href="https://bsky.app/profile/chordian.bsky.social">Bluesky</a></h4>
		Chordian\'s Bluesky account and where all news and changes about DeepSID are always posted.

		<h4><a href="https://mastodon.social/@chordian">Mastodon</a></h4>
		Chordian\'s Mastodon account, where all news and changes about DeepSID are also posted.

		<h4><a href="http://csdb.chordian.net/?type=forums&roomid=14&topicid=129712">CSDb</a></h4>
		A forum thread at CSDb that was created when DeepSID was born, but it\'s a ghost town today.

		<h4><a href="https://www.lemon64.com/forum/viewtopic.php?t=68056">Lemon64</a></h4>
		Probably the most popular forum for C64 and so of course there had to be a forum thread here too.

		<h4><a href="https://chipmusic.org/forums/topic/20510/deepsid-a-new-online-sid-player/">ChipMusic.org</a></h4>
		Although this is a nice forum for chipmusic in general, the forum thread here never took off.

		<h4><a href="https://blog.chordian.net/2018/05/12/deepsid/">Chordian.net</a></h4>
		This is the blog post where I announced when DeepSID was launched, in May 2018.

		<h4><a href="https://github.com/Chordian/deepsid">GitHub</a></h4>
		If you want have a go at setting up your own DeepSID, or just want to look at the source code.

	',

	'	<h3>Handling tags</h3>

		<h4>Viewing tags</h4>
		<p>You can see tags in the second line inside most of the SID rows.</p>
		<p>If you can\'t see them all, hover on them to scroll them into view.</p>
		If you still can\'t see them all, select SID row then click the <b>Tags</b> tab.

		<h4>Color codes</h4>
		<p>The type of tags are divided up into groups that huddle together.</p>
		<p>What the colors indicate:</p>
		<div class="annex-hotkey"><a href="17" class="topic"><div class="tag tag-event">Event</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">Typically a party</span>
		<div class="annex-hotkey"><a href="14" class="topic"><div class="tag tag-production">Production</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">Demos, intros, etc.</span>
		<div class="annex-hotkey"><a href="13" class="topic"><div class="tag tag-origin">Origin</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">Cover, remake, etc.</span>
		<div class="annex-hotkey"><a href="12" class="topic"><div class="tag tag-digi">Digi</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">Digi, what type, etc.</span>
		<div class="annex-hotkey"><a href="11" class="topic"><div class="tag tag-warning">Warning</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">Bugged, hacked, etc.</span>
		<div class="annex-hotkey" style="position:relative;top:3.5px;"><a href="remix64" class="search" data-type="tag"><div class="tag tag-remix64"></div></a></div><span class="annex-tiny" style="position:relative;top:1px;">It has been remixed</span>
		<div class="annex-hotkey" style="position:relative;top:3.5px;"><a href="gamebase64" class="search" data-type="tag"><div class="tag tag-gamebase64"></div></a></div><span class="annex-tiny" style="position:relative;top:1px;">It has a game entry</span>
		<div class="annex-hotkey"><a href="15" class="topic"><div class="tag">General</div></a></div><span class="annex-tiny" style="position:relative;top:1px;">All other tags</span>
		<p>If a SID has been remixed, click the <b>Remix</b> tab in top to check this out.</p>

		<h4>Adding or editing tags</h4>
		<ol>
			<li>Hover on any SID row.</li>
			<li>Click the small [+] icon.</li>
			<li>Use the dialog box.</li>
		</ol>

		<h4>Searching for tags</h4>
		<ol>
			<li>Enter a folder.</li>
			<li>Click the <b>Tags</b> tab.</li>
			<li>Click a tag in the tab box.</li>
		</ol>
		<p>This should show the SID rows (in that folder) that has that tag.</p>
		You can also search globally for tags in the bottom left corner:
		<ol>
			<li>Select <b>Tags</b> in drop-down.</li>
			<li>Type one or more tags.</li>
			<li>Click the <b>SEARCH</b> button.</li>
		</ol>
		Enclose a tag in quotes if there are spaces between its words.

	',

	'	<h3>Warning tags</h3>

		<p>There are a few tags that indicate some kind of warning.</p>

		<ul class="annex-tags-list">
			<li><a href="bug" class="search" data-type="tag"><div class="tag tag-warning">Bug</div></a>= <b>One or more bugs</b>
			<p>This is usually acknowledged by HVSC in a <a href="https://www.hvsc.c64.org/download/C64Music/DOCUMENTS/STIL.faq" target="_blank">STIL</a> comment.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="doubling" class="search" data-type="tag"><div class="tag tag-warning">Doubling</div></a>= <b>Clashing voices</b>
			<p>One or more voices has been copied to play the exact same notes on top of each other. This is usually a lazy editing technique that just makes everything louder.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="hack" class="search" data-type="tag"><div class="tag tag-warning">Hack</div></a>= <b>Been tampered with</b>
			<p>The tune was originally made by someone else, then stolen and edited to change or add something. Like if someone merely added an extra digi track on top of a tune.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="mock" class="search" data-type="tag"><div class="tag tag-warning">Mock</div></a>= <b>Intentionally bad</b>
			<p>The composer deliberately wanted this SID to sound bad.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="recycled" class="search" data-type="tag"><div class="tag tag-warning">Recycled</div></a>= <b>Reused in compos</b>
			<p>The composer reused the SID in multiple competitions, which is generally frowned upon.</p></li>
		</ul>
		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>Digi tags</h3>

		<p>There are a few tags that indicate various kinds of digi effects.</p>

		<h4>Primary digi tags</h4>
		<ul class="annex-tags-list">
			<li><a href="digi" class="search" data-type="tag"><div class="tag tag-digi">Digi</div></a>= <b>Digi has been used</b>
			<p>A "fits all" description of almost anything digi. If there are no other digi tags, it\'s just normal 4-bit $D418 digi.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="digi-org" class="search" data-type="tag"><div class="tag tag-digi">Digi-Org</div></a>= <b>Digi-Organizer used</b>
			<p>Another stand-alone tag. It indicates that <a href="//deepsid.chordian.net/?player=43&type=player&search=digi-organizer">Digi-Organizer</a> was used to add digi to a music player that normally doesn\'t support digi.</p></li>
		</ul>

		<h4>Supporting digi tags</h4>
		These tags are usually only added together with the <b>Digi</b> tag.
		<ul class="annex-tags-list">
			<li><a href="samples" class="search" data-type="tag"><div class="tag tag-digi">Samples</div></a>= <b>Only digi has been used</b>
			<p>The file is all digi only. The SID voices have not been used.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="m418" class="search" data-type="tag"><div class="tag tag-digi">M418</div></a>= <b>Mahoney\'s "8-bit" samples</p></b>
			<p>Both volume and filter are affected, both controlled by $D418. This can produce more than 4-bits, although typically no more than 6-7 bits. The three SID voices cannot be used here.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="8bit" class="search" data-type="tag"><div class="tag tag-digi">8bit</div></a>= <b>8-bit "FM" digi used</p></b>
			<p>This uses an "FM" technique that produces genuine 8-bit samples. This requires one of the three SID channels, but it can be filtered. Also, the two remaining SID channels can be used normally.</p><p>A commonly used tool for this technique is an exclusive add-on coded by <a href="http://csdb.chordian.net/?type=scener&id=9589">THCM</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="pwm" class="search" data-type="tag"><div class="tag tag-digi">PWM</div></a>= <b>Pulse width modulation</p></b>
			<p>Uses a high-frequency $41 pulse waveform and then manipulates both the pulse widths and the test-bit. The result can be routed through the filter afterwards.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="osc" class="search" data-type="tag"><div class="tag tag-digi">OSC</div></a>= <b>Oscillator digi</p></b>
			<p>This uses the oscillator method as demonstrated by e.g. <a href="http://csdb.chordian.net/?type=release&id=131019">FRODIGI</a>. It\'s low on CPU usage but also sounds a bit murky. Uses fast-moving waveform $11 notes.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="gt-echo" class="search" data-type="tag"><div class="tag tag-digi">GT-Echo</div></a>= <b>SID with echo effects</b>
			<p>The echo effect for one of the SID voices actually uses digi to accomplish this.</p></li>
		</ul>

		<h4>Exceptions to digi tags</h4>
		<ul class="annex-tags-list">
			<li><a href="speech" class="search" data-type="tag"><div class="tag">Speech</div></a>= <b>Speech simulation</b>
			<p>This tag is shown if normal SID effects were used to simulate speech. Frequency modulation is typically involved and thus it has nothing to do with digi.</p></li>
		</ul>

		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>Origin tags</h3>

		<p>Origin tags indicate if the SID was derived from somewhere else.</p>

		<h4>Main origin tags</h4>
		There are a few main origin tags that set the scene.
		<ul class="annex-tags-list">
			<li><a href="cover" class="search" data-type="tag"><div class="tag tag-origin">Cover</div></a>= <b>Cover of real music</b>
			<p>If the SID tune is a cover of a real song or a soundtrack from e.g. TV, this tag is used.</p>
			<p>Ensuing tags can be singers, bands, soundtrack, etc.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="conversion" class="search" data-type="tag"><div class="tag tag-origin">Conversion</div></a>= <b>From other format</b>
			<p>The SID tune has been converted from a different format or device, e.g. an arcade game, a console, or a different home computer.</p>
			<p>Ensuing tags can be the file format, device name, etc.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="remake" class="search" data-type="tag"><div class="tag tag-origin">Remake</div></a>= <b>Remake of a SID tune</b>
			<p>This is a remake of another SID tune. Usually the tag demands that the remake follows the structure of the original. If not, a mixed tag should be used.</p></li>
		</ul>

		<h4>Mixed origin tags</h4>
		These fade into a normal tag color to indicate their ambiguity.
		<ul class="annex-tags-list">
			<li><a href="remix" class="search" data-type="tag"><div class="tag tag-mixorigin">Remix</div></a>= <b>Remix of something</b>
			<p>This can be used as both a main or a secondary tag. When used by itself, it means that it\'s a remix of another SID tune.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="pastiche" class="search" data-type="tag"><div class="tag tag-mixorigin">Pastiche</div></a>= <b>Inspired by something</b>
			<p>Also a main or a secondary tag. It indicates that the tune was inspired by something, e.g. it borrows notes, or the style of another composer.</p></li>
		</ul>

		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>Production tags</h3>

		<p>Useful if the SID file was made for a specific production.</p>

		<h4>List of all production tags</h4>
		<ul class="annex-tags-list">
			<li><a href="&quot;4k intro&quot;" class="search" data-type="tag"><div class="tag tag-production">4K Intro</div></a>= <b>Made for a 4K intro</b>
			<p>Sort of a demo, only it cannot take up more than 4K of RAM. Often part of a competition.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="collection" class="search" data-type="tag"><div class="tag tag-production">Collection</div></a>= <b>Added to a collection</b>
			<p>Can sometimes be seen if the tune was added to a music collection. However, the tag has been used sparingly as it multiplies very easily.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="&quot;crack intro&quot;" class="search" data-type="tag"><div class="tag tag-production">Crack Intro</div></a>= <b>Made for a crack intro</b>
			<p>Mostly indicates it was made for a crack intro. Sometimes also applied if the tune was known for being used a lot in crack intros in general.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="demo" class="search" data-type="tag"><div class="tag tag-production">Demo</div></a>= <b>Made for a demo</b>
			<p>Applies to both one-part and multi-part demos.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="diskmag" class="search" data-type="tag"><div class="tag tag-production">Diskmag</div></a>= <b>Made for a diskmag</b>
			<p>Used in a diskmag with scene news, interviews, etc.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="dtv" class="search" data-type="tag"><div class="tag tag-production">DTV</div></a>= <b>Made for C64 DTV</b>
			<p><a href="https://www.c64-wiki.com/wiki/C64DTV">C64 Direct-to-TV</a> is single-chip implementation of the C64, contained in a joystick. DTV1 had 128K RAM, DMA transfer, and 256 possible colors.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="game" class="search" data-type="tag"><div class="tag tag-production">Game</div></a>= <b>Made for a game</b>
			<p>Made for a <i>released</i> game.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="&quot;game prev&quot;" class="search" data-type="tag"><div class="tag tag-production">Game Prev</div></a>= <b>Made for a game</b>
			<p>Made for a preview of a game which <i>may</i> never be finished.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="gtw" class="search" data-type="tag"><div class="tag tag-production">GTW</div></a>= <b>For a <u>G</u>ame <u>T</u>hat <u>W</u>eren\'t</b>
			<p>Related to the game preview, only this is sure to be a game that will <i>never</i> be finished. There\'s a web site dedicated to <a href="https://www.gamesthatwerent.com/gtw64/">Games That Weren\'t</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="intro" class="search" data-type="tag"><div class="tag tag-production">Intro</div></a>= <b>Made for a C64 intro</b>
			<p>It can be an intro for e.g. a demo or a diskmag, or it can be stand-alone, in which case it\'s usually in a competition.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="loader" class="search" data-type="tag"><div class="tag tag-production">Loader</div></a>= <b>Made for a loader</b>
			<p>Loaders were typically seen when loading a game from a cassette tape.</li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="other" class="search" data-type="tag"><div class="tag tag-production">Other</div></a>= <b>Other productions</b>
			<p>For less common productions such as graphics, tools, etc.</li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="seuck" class="search" data-type="tag"><div class="tag tag-production">SEUCK</div></a>= <b>Made for a SEUCK game</b>
			<p>A secondary tag that typically accompanies the <b>Game</b> tag. It indicates that the game was made with the <a href="https://en.wikipedia.org/wiki/Shoot-%27Em-Up_Construction_Kit">Shoot-\'Em-Up Construction Kit</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="vinyl" class="search" data-type="tag"><div class="tag tag-production">Vinyl</div></a>= <b>Made as a vinyl tribute</b>
			<p>Made for a vinyl LP with a collection of SID tunes.</li>
		</ul>
	
		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>General tags</h3>

		<h4>This is an incomplete list</h4>
		<p>It will focus on interesting tags, or tags that need explaining.</p>

		<ul class="annex-tags-list">
			<li><a href="2x" class="search" data-type="tag"><div class="tag">2x</div></a>= <b>2x multispeed tune</b>
			<p>The most common update speed for a C64 player is 50 times a second, i.e. each time the C64 screen is refreshed. Sometimes SID tunes can call the player twice as fast (or more) which allows for some quite interesting sounds.</p>
			<p>There are also tags for <a href="3x" class="search" data-type="tag">3x</a>, <a href="4x" class="search" data-type="tag">4x</a>, <a href="8x" class="search" data-type="tag">8x</a>, etc. Even one for <a href="25hz" class="search" data-type="tag">25hz</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="comeback" class="search" data-type="tag"><div class="tag">Comeback</div></a>= <b>I\'m back, baby!</b>
			<p>The tune was made after the composer took a very long break of several years, maybe even decades.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="edit" class="search" data-type="tag"><div class="tag">Edit</div></a>= <b>Updated and resubmitted</b>
			<p>The tune has been edited to improve or change it.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="first" class="search" data-type="tag"><div class="tag">First</div></a>= <b>First attempt ever</b>
			<p>This is the first tune made (or released) by the composer.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="unearthed" class="search" data-type="tag"><div class="tag">Unearthed</div></a>= <b>Released much later</b>
			<p>Originally composed several years before its release, when it first became known.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="flanging" class="search" data-type="tag"><div class="tag">Flanging</div></a>= <b>A rich detuning effect</b>
			<p>The tune uses two identical notes where one has been slightly detuned to create a rich sound.</p>
			<p>If the notes are not detuned, it\'s called <a href="doubling" class="search" data-type="tag">Doubling</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="fm" class="search" data-type="tag"><div class="tag">FM</div></a>= <b>Melodic use of freq mod</b>
			<p>The tune emulates a ring mod or hard sync relationship for several coherent notes.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="interactive" class="search" data-type="tag"><div class="tag">Interactive</div></a>= <b>Originally interactive</b>
			<p>Part of a production where the tune could be changed in real time. (It is not interactive in DeepSID, however.)</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="nonstandard" class="search" data-type="tag"><div class="tag">Nonstandard</div></a>= <b>Does not use 4/4</b>
			<p>The tune uses a nonstandard time signature other than 4/4.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="pure" class="search" data-type="tag"><div class="tag">Pure</div></a>= <b>No effects, just notes</b>
			<p>The tune uses a player that does not change SID registers in real time, e.g. vibrato, filter sweeping, etc. Just like in the very beginning.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="reverb" class="search" data-type="tag"><div class="tag">Reverb</div></a>= <b>Reverb or echo effect</b>
			<p>Notes slightly displaced across two or three voices to create a reverb/echo effect.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="skydive" class="search" data-type="tag"><div class="tag">Skydive</div></a>= <b>Hubbard\'s "skydive"</b>
			<p>The tune uses an arpeggio effect that toggles between a note and a downwards slide originating at that note.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="solo" class="search" data-type="tag"><div class="tag">Solo</div></a>= <b>There\'s a solo coming up</b>
			<p>The tune has a solo with a lead instrument.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="tiny" class="search" data-type="tag"><div class="tag">Tiny</div></a>= <b>Less than 512 bytes total</b>
			<p>But if you\'re looking for tiny tunes it may be a better idea to use <a href="512" class="search" data-type="maximum">this</a> search method.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="?" class="search" data-type="tag"><div class="tag">?</div></a>= <b>In doubt about those tags</b>
			<p>Means that one or more of the other tags applied to it is ambiguous or uncertain.</p></li>
		</ul>

		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>REST API</h3>

		<p>You can call a REST API for adding info to your own web site. API key or authentication is not needed.</p>
		<p>Click a link below for an example in a new web browser tab.</p>

		<h4>One specific file</h4>
		<b><a href="//deepsid.chordian.net/api/v1/?file=/MUSICIANS/L/Laxity/Alibi.sid" target="_blank">/api/v1/?file=<i>A specific SID file</i></a></b>

		<h4>All files in a folder</h4>
		<b><a href="//deepsid.chordian.net/api/v1/?file=/MUSICIANS/L/Laxity/" target="_blank">/api/v1/?file=<i>A folder</i></a></b>

		<h4>A folder and its subfolders</h4>
		<b><a href="//deepsid.chordian.net/api/v1/?folder=/DEMOS/" target="_blank">/api/v1/?folder=<i>A folder</i></a></b>

		<h4>A composer profile</h4>
		<b><a href="//deepsid.chordian.net/api/v1/?profile=/MUSICIANS/L/Laxity/" target="_blank">/api/v1/?profile=<i>A folder</i></a></b>

		<h4>All players/editors</h4>
		<b><a href="//deepsid.chordian.net/api/v1/?players" target="_blank">/api/v1/?players</a></b>

		<p style="margin-top:20px;">Slashes before or after paths are not needed &ndash; works both ways.</p>
	',

	'	<h3>Event tags</h3>

		<p>This is usually a demo party, a meeting or similar, where the SID file was released.</p>

		<p>Tags before <b>Compo</b> is usually the name of the event.</p>

		<h4>List of generel event tags</h4>
		<ul class="annex-tags-list">
			<li><a href="&quot;compo&quot;" class="search" data-type="tag"><div class="tag tag-event">Compo</div></a>= <b>From a competition</b>
			<p>Participated in a competition at the event, typically a music competition.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="&quot;winner&quot;" class="search" data-type="tag"><div class="tag tag-event tag-winner">Winner</div></a>= <b>Won a competition</b>
			<p>The winner of the competition it participated in.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="&quot;#2&quot;" class="search" data-type="tag"><div class="tag tag-event">#2</div></a>= <b>#2 in a competition</b>
			<p>Second in the competition it participated in. There are also tags for <b>#3</b>, <b>#4</b>, etc.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="&quot;solitary&quot;" class="search" data-type="tag"><div class="tag tag-event">Solitary</div></a>= <b>The only contender</b>
			<p>The only contender of the competition. It may sometimes be seen as #1 in CSDb.</p></li>
		</ul>

		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

    '   <h3 style="margin-bottom:20px;">Useful links</h3>

		<ul class="site-list">'
        . makeSiteLink('https://8bitlegends.com', '8BitLegends', 'Tribute')
        . makeSiteLink('https://arok.intro.hu', 'Arok Party', 'Event')
        // . makeSiteLink('http://www.attitude.c64.org', 'Attitude', 'Diskmag')
        . makeSiteLink('https://c64.ch', 'C64.CH', 'Info')
        . makeSiteLink('https://www.c64.com', 'C64.COM', 'Info')
        // . makeSiteLink('http://c64.sk', 'C64.SK', 'Info')
        . makeSiteLink('https://www.c64forever.com', 'C64 Forever', 'Emulator')
        . makeSiteLink('https://www.c64-wiki.com', 'C64-Wiki', 'Info')
        . makeSiteLink('https://c64audio.com', 'C64Audio.com', 'Music')
        . makeSiteLink('https://c64gfx.com', 'C64GFX', 'Info')
        . makeSiteLink('https://intros.c64.org', 'C64intros', 'Info')
        . makeSiteLink('https://www.ajordison.co.uk', 'CBM .prg Studio', 'Dev')
        . makeSiteLink('https://www.ccs64.com', 'CCS64', 'Emulator')
        . makeSiteLink('https://www.c64music.co.uk', 'CGSC', 'Collection')
        . makeSiteLink('https://codebase64.net', 'Codebase64', 'Dev')
        . makeSiteLink('https://cadaver.github.io', 'Covert Bitops', 'Group')
        . makeSiteLink('https://csdb.dk', 'CSDb', 'Info')
        . makeSiteLink('https://csdb.chordian.net', 'CShellDB', 'Browser')
        . makeSiteLink('https://datastorm.party', 'Datastorm', 'Event')
        . makeSiteLink('https://www.docsnyderspage.com', 'Doc Snyder', 'Intros')
        // . makeSiteLink('http://www.fairlight.to', 'Fairlight', 'Group')
        // . makeSiteLink('https://www.forum64.de', 'Forum64.de', 'Forum')
        . makeSiteLink('https://freeze64.com', 'Freeze64', 'Magazines')
        . makeSiteLink('https://gb64.com', 'GameBase64', 'Games')
        . makeSiteLink('https://www.goto80.com', 'Goto80', 'Scener')
        . makeSiteLink('https://www.gamesthatwerent.com/welcome-to-gtw64', 'GTW', 'Games')
        . makeSiteLink('https://www.gubbdata.se', 'Gubbdata', 'Event')
        . makeSiteLink('https://www.c64-hof.com', 'Hall of Fame', 'Info')
        . makeSiteLink('https://www.hvsc.c64.org', 'HVSC', 'Collection')
        . makeSiteLink('https://lemon64.com', 'Lemon64', 'Info')
        . makeSiteLink('https://onslaught.c64.org', 'Onslaught', 'Group')
        . makeSiteLink('https://www.oxyron.de', 'Oxyron', 'Group')
        . makeSiteLink('https://pressplayontape.com', 'Pr. Play on Tape', 'Music')
        . makeSiteLink('https://project64.c64.org', 'Project 64', 'Manuals')
        . makeSiteLink('https://www.protovision.games', 'Protovision', 'Company')
        . makeSiteLink('https://www.radwar.com', 'Radwar', 'Group')
        . makeSiteLink('https://c64demo.com', 'Raistlin Papers', 'Blog')
        // . makeSiteLink('http://recollection.c64.org', 'Recollection', 'Tribute')
        . makeSiteLink('https://remix64.com', 'Remix64', 'Music')
        . makeSiteLink('https://remix.kwed.org', 'Remix.Kwed.org', 'Music')
        . makeSiteLink('https://2025.revision-party.net', 'Revision', 'Event')
        // . makeSiteLink('http://sid.oth4.com', 'SID.OTH4.COM', '')
        . makeSiteLink('https://sceneworld.org', 'Scene World', 'Diskmag')
        . makeSiteLink('https://sidquake.c64demo.com', 'SIDquake', 'Tool')
        . makeSiteLink('https://www.slayradio.org', 'SLAY Radio', 'Music')
        . makeSiteLink('https://www.6581-8580.com', 'SOASC', 'Collection')
        . makeSiteLink('https://www.scs-trc.net', 'Success & TRC', 'Groups')
        . makeSiteLink('https://tnd64.unikat.sk', 'TND', 'Scener')
        . makeSiteLink('https://www.triad.se', 'Triad', 'Group')
        . makeSiteLink('https://ultimatesid.dk', 'USC', 'Collection')
        . makeSiteLink('https://vandalism.news', 'Vandalism', 'Diskmag')
        . makeSiteLink('https://vice-emu.sourceforge.io', 'VICE', 'Emulator')
    	. '</ul>
	',
);

$id = isset($_GET['id']) ? $_GET['id'] : mt_rand(0, count($tips) - 1);

// $id = 18;

if ($id != -1)
	echo $tips[$id];
else
	echo
	'	<h3>List of annex tips:</h3>
		<ul style="margin-bottom:16px;">
			<li><a href="6" class="topic">Color strips</a></li>
			<li><a href="12" class="topic">Digi tags</a></li>
			<li><a href="17" class="topic">Event tags</a></li>
			<li><a href="2" class="topic">External linking</a></li>
			<li><a href="15" class="topic">General tags</a></li>
			<li><a href="10" class="topic">Handling tags</a></li>
			<li><a href="7" class="topic">Hotkeys</a></li>
			<li><a href="1" class="topic">Memory bar</a></li>
			<li><a href="13" class="topic">Origin tags</a></li>
			<li><a href="0" class="topic">Playlists</a></li>
			<li><a href="14" class="topic">Production tags</a></li>
			<li><a href="3" class="topic">Registering</a></li>
			<li><a href="16" class="topic">REST API</a></li>
			<li><a href="5" class="topic">Searching</a></li>
			<li><a href="4" class="topic">SID handlers</a></li>
			<li><a href="9" class="topic">Social links</a></li>
			<li><a href="8" class="topic">URL parameters</a></li>
			<li><a href="18" class="topic">Useful links</a></li>
			<li><a href="11" class="topic">Warning tags</a></li>
		</ul>
		More tips may be added later.
	';
?>