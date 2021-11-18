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

 $tips = array(

	'	<h3>Playlists</h3>

		<h4>How to start a new playlist:</h4>
		<ol>
			<li>Right-click any SID row.</li>
			<li>Click <b>Add to New Playlist</b>.</li>
			<li>Browse back to root.</li>
			<li>Right-click your playlist.</li>
			<li>Click <b>Rename Playlist</b>.</li>
			<li>Type a great name for it.</li>
		</ol>

		<h4>How to add more tunes to it:</h4>
		<ol>
			<li>Right-click any SID row.</li>
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

	'	<h3>External links</h3>

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
		<p>Just type a user and password. If the user is unknown, DeepSID will accept it as a new user.</p>
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

		<h4>Adjust your preferences</h4>
		<p>Click the cogwheel tab right next to the top of this box.</p>
		This will show your settings.
	',

	'	<h3>SID handlers</h3>
		<p>If you click the drop-down box in the top left corner, another SID handler can be chosen.</p>
		
		<h4><a href="//deepsid.chordian.net?emulator=websid">WebSid emulator</a></h4>
		<p>The default for computers.</p>
		It processes cycle-by-cycle, and it emulates almost everything.

		<h4><a href="//deepsid.chordian.net?emulator=legacy">WebSid (Legacy)</a></h4>
		<p>The default for mobile devices.</p>
		Faster, but not as efficient. Use this if your computer is slow.

		<h4><a href="//deepsid.chordian.net?emulator=jssid">Hermit\'s emulator</a></h4>
		<p>Even faster, but cannot emulate RSID tunes, nor SID with digi.</p>

		<h4><a href="//deepsid.chordian.net?emulator=youtube">YouTube videos</a></h4>
		<p>Plays a YouTube video where SID rows are enabled for clicking.</p>
		One SID row can contain several videos, and you can add more.

		<h4><a href="//deepsid.chordian.net?emulator=download">Download SID file</a></h4>
		<p>Clicking a SID row will download it to your computer.</p>
		Useful if you have associated the SID files to automatically
		run an external program.
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

		<h4>Tags</h4>
		<p>Searches for one or more tags.</p>
		Enclose in quotes to search a tag with spaces between words.

		<h4>STIL</h4>
		Searches in <a href="https://www.hvsc.c64.org/download/C64Music/DOCUMENTS/STIL.faq" target="_blank">STIL</a> texts.

		<h4>Rating</h4>
		<p>Searches for your ratings. Use 1 to 5, optionally with minus.</p>
		For example, typing <a href="//deepsid.chordian.net/?search=3-&type=rating">3-</a> searches for tunes rated 3 stars or more.

		<h4>Country</h4>
		Searches for composers from a specific country. Only a list of folders is returned here.

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
		<div class="annex-strip annex-a"> = <b>GoatTracker</b></div><span class="annex-tiny"><a href="//deepsid.chordian.net/?player=19&type=player&search=goattracker_v1.x"><b>v1.x</b></a> or <a href="//deepsid.chordian.net/?player=1&type=player&search=goattracker_v2.x"><b>v2.x</b></a></span>
		<div class="annex-strip annex-b"> = <b>NewPlayer</b></div><span class="annex-tiny">JCH\'s <a href="//deepsid.chordian.net/?player=2&type=player&search=newplayer_-v18_-v19_-v20_-v21"><b>v2</b></a> / <a href="//deepsid.chordian.net/?player=3&type=player&search=newplayer_v20"><b>v3</b></a>, etc.</span>
		<div class="annex-strip annex-c"> = <b>SID-Wizard</b></div><span class="annex-tiny"><a href="//deepsid.chordian.net/?player=5&type=player&search=sidwizard_v1.x"><b>v1.x</b></a></span>
		<div class="annex-strip annex-d"> = <b>SID Factory II</b></div><span class="annex-tiny"><a href="//deepsid.chordian.net/?player=122&type=player&search=sidfactory_ii"><b>BETA</b></a></span>
		<div class="annex-strip annex-e"> = <b>DMC</b></div><span class="annex-tiny"><a href="//deepsid.chordian.net/?player=18&type=player&search=dmc_v4.x"><b>v4.x</b></a>, <a href="//deepsid.chordian.net/?player=12&type=player&search=dmc_v5.x"><b>v5.x</b></a>, etc.</span>
		<div class="annex-strip annex-f"> = <b>SidTracker 64</b></div><span class="annex-tiny"><a href="http://deepsid.chordian.net/?player=13&type=player&search=sidtracker64"><b>iPad</b></a></span>
	',

	'	<h3>Hotkeys</h3>
		
		<h4>General hotkeys</h4>
		<div class="annex-hotkey">Space</div><span class="annex-tiny">Pause/Play toggle</span>
		<div class="annex-hotkey">[<i>Below</i>&nbsp;]&nbsp;Esc</div><span class="annex-tiny">Fast forward</span>
		<div class="annex-hotkey">p</div><span class="annex-tiny">Pop-up tiny DeepSID</span>
		<div class="annex-hotkey">s</div><span class="annex-tiny">Toggle sundry box</span>
		<div class="annex-hotkey">l</div><span class="annex-tiny">Upload SID for testing</span>
		<div class="annex-hotkey">b</div><span class="annex-tiny">Back from plink</span>
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
						<li><b>websid</b></li>
						<li><b>legacy</b></li>
						<li><b>jssid</b>&nbsp;&nbsp;(Hermit\'s)</li>
						<li><b>youtube</b></li>
						<li><b>download</b></li>
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
						<li><b>gb64</b></li>
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
);

$id = isset($_GET['id']) ? $_GET['id'] : mt_rand(0, count($tips) - 1);

if ($id != -1)
	echo $tips[$id];
else
	echo
	'	<h3>List of annex tips:</h3>
		<ul style="margin-bottom:16px;">
			<li><a href="6" class="topic">Color strips</a></li>
			<li><a href="2" class="topic">External links</a></li>
			<li><a href="7" class="topic">Hotkeys</a></li>
			<li><a href="1" class="topic">Memory bar</a></li>
			<li><a href="0" class="topic">Playlists</a></li>
			<li><a href="3" class="topic">Registering</a></li>
			<li><a href="5" class="topic">Searching</a></li>
			<li><a href="4" class="topic">SID handlers</a></li>
			<li><a href="8" class="topic">URL parameters</a></li>
		</ul>
		More tips may be added later.
	';
?>