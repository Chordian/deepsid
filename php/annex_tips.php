<?php
/**
 * DeepSID
 *
 * Returns a randomly chosen block of HTML for the annex box with tips.
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
		<p>You can create a playlist and add SID files to it. See this for more.</p>

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
		The drop-down box can narrow down your search:

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


	',

	'
	
	',

	'
	
	',
);

//////////echo $tips[mt_rand(0, 5)];
echo $tips[5];
?>