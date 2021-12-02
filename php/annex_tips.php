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
		For example, typing <a href="3-" data-type="rating" class="search">3-</a> searches for tunes rated 3 stars or more.

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
						<li><b>filter</b></li>
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

	'	<h3>Social links</h3>

		<h4><a href="https://www.facebook.com/groups/deepsid/">Facebook</a></h4>
		A group with requests or bugs from other users as well as news about everything SID-related.

		<h4><a href="https://twitter.com/chordian">Twitter</a></h4>
		Chordian\'s Twitter account and where all news and changes about DeepSID are always posted.

		<h4><a href="https://discord.gg/n5w85GMbVu">Discord</a></h4>
		Discusses changes, bugs, features, etc. Much younger than the other options. Need more members.
		
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
		<div class="annex-hotkey"><div class="tag tag-production">Production</div></div><span class="annex-tiny">Demos, intros, etc.</span>
		<div class="annex-hotkey"><div class="tag tag-origin">Origin</div></div><span class="annex-tiny">Cover, conversion, etc.</span>
		<div class="annex-hotkey"><a href="12" class="topic"><div class="tag tag-digi">Digi</div></a></div><span class="annex-tiny">Digi, what type, etc.</span>
		<div class="annex-hotkey"><a href="11" class="topic"><div class="tag tag-warning">Warning</div></a></div><span class="annex-tiny">Bugged, hacked, etc.</span>
		<div class="annex-hotkey" style="position:relative;top:3.5px;"><a href="remix64" class="search" data-type="tag"><div class="tag tag-remix64"></div></a></div><span class="annex-tiny">It has been remixed</span>
		<div class="annex-hotkey"><div class="tag">Other</div></div><span class="annex-tiny">All other tags</span>
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
		If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.

	',

	'	<h3>Digi tags</h3>

		<p>There are a few tags that indicate various kinds of digi effects.</p>

		<h4>Primary digi tags</h4>
		<ul class="annex-tags-list">
			<li><a href="digi" class="search" data-type="tag"><div class="tag tag-digi">Digi</div></a>= <b>Digi has been used</b>
			<p>A "fits all" description of almost anything digi. If there are no other digi tags, it\'s just normal 4-bit $D418 used as one or more assisting channels.</p></li>
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
			<li><a href="8bit" class="search" data-type="tag"><div class="tag tag-digi">8bit</div></a>= <b>8-bit digi used</p></b>
			<p>The file uses an advanced 8-bit technique. A commonly used tool for this is an exclusive add-on coded by <a href="http://csdb.chordian.net/?type=scener&id=9589">THCM</a>.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="osc" class="search" data-type="tag"><div class="tag tag-digi">OSC</div></a>= <b>Oscillator digi</p></b>
			<p>Uses the oscillator method as demonstrated by e.g. <a href="http://csdb.chordian.net/?type=release&id=131019">FRODIGI</a>. It\'s low on CPU usage but also sounds a bit murky. Uses fast-moving waveform $11 notes.</p></li>
		</ul>
		<ul class="annex-tags-list">
			<li><a href="pwm" class="search" data-type="tag"><div class="tag tag-digi">PWM</div></a>= <b>Pulse width modulation</p></b>
			<p>Pulse widths for waveform $41 is doing the hard work here.</p></li>
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


	',

	/*'	<h3>List of general tags</h3>

		<h4>This is an incomplete list</h4>
		<p>There are a ton of tags in DeepSID and everyone can add more.</p>
		<p>This list will focus on interesting tags, or tags that need explaining.</p>

		<h4>Warning tags</h4>
		<p>There are red tags that indicate a warning of some kind.</p>

		<i>If you want to know more about handling tags, see <a href="10" class="topic">this</a> topic.</i>

	',*/
);

$id = isset($_GET['id']) ? $_GET['id'] : mt_rand(0, count($tips) - 1);

if ($id != -1)
	echo $tips[$id];
else
	echo
	'	<h3>List of annex tips:</h3>
		<ul style="margin-bottom:16px;">
			<li><a href="6" class="topic">Color strips</a></li>
			<li><a href="12" class="topic">Digi tags</a></li>
			<li><a href="2" class="topic">External links</a></li>
			<li><a href="10" class="topic">Handling tags</a></li>
			<li><a href="7" class="topic">Hotkeys</a></li>
			<li><a href="1" class="topic">Memory bar</a></li>
			<li><a href="13" class="topic">Origin tags</a></li>
			<li><a href="0" class="topic">Playlists</a></li>
			<li><a href="3" class="topic">Registering</a></li>
			<li><a href="5" class="topic">Searching</a></li>
			<li><a href="4" class="topic">SID handlers</a></li>
			<li><a href="9" class="topic">Social links</a></li>
			<li><a href="8" class="topic">URL parameters</a></li>
			<li><a href="11" class="topic">Warning tags</a></li>
		</ul>
		More tips may be added later.
	';
?>