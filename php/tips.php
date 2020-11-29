<?php
/**
 * DeepSID
 *
 * Returns a randomly chosen block of text for the tips box.
 */

$tips = array(

	"<p>The <b>blue bar above</b> shows the C64 memory from \$0000 to \$FFFF. ".
	"A SID tune is shown as a dark blue blob in it with the right size and location.</p>".
	"<p>If you are using a mouse, hover the pointer on the blob for details.</p>".
	"If you want to see more technical SID info, just click the blue blob. This will ".
	"take you to the <b>MEMO</b> view in the <b>Visuals</b> tab where this is shown.",

	"<p>If you are using a mouse, you can create your own <b>playlists</b>. ".
	"Right-click any SID file row for a context menu where you can add it to a playlist.</p>".
	"<p>Playlists are located in the folder root with a star in the folder icon.</p>".
	"You can also right-click a playlist there to rename or delete it, and you can enter it and ".
	"right-click a SID file row to rename or remove it.",

	"<p>If you have a nice playlist, you can make it a <b>public playlist</b> for ".
	"everyone to see. Just right-click it in the folder root and choose the menu option.</p>".
	"<p>Although everyone can see it, you're still the only one who can edit it.</p>".
	"Published playlists will move up to intermingle with other public folders. However, ".
	"they will still have a star icon so you can easily spot them.",

	"<p>You can easily <b>link to any SID file</b> you're listening to by copying the URL in ".
	"the address bar of the web browser. This also works with folders.</p>".
	'<p>Some places also have <svg style="enable-background:new 0 0 80 80;position:relative;top:2px;width:10px;height:10px;fill:var(--color-text-body);" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg></a>
	permalinks that can be right-clicked and then copied,
	e.g. when searching or when viewing the <b>CSDb</b> or <b>Player</b> tabs.</p>'.
	"For a full list of URL parameters, see the bottom of the <b>FAQ</b> tab.",

	"<p>You have to be <b>logged in</b> to rate, tag, upload files and create playlists.</p>".
	"The user name and password box in top are also used when registering. Just type any user name ".
	"and if it's available, type a password for it.",

	"<p>You can choose another <b>SID handler</b> in the top left drop-down box.</p>".
	"<p>The top <i>WebSid</i> JS emulator emulates cycle-by-cycle but requires a fast computer. The other ".
	"emulator options are faster but also less accurate.</p>".
	"The <i>SOASC</i> options are FLAC/MP3 recordings from a real C64. R2 is the 6581 chip ".
	"with a bright filter, R4 with a deep, and R5 is the 8580 chip.",

	"<p>You can <b>search</b> for any SID file or folder in the text box in the bottom.</p>".
	"<p>The drop-down box decides what you want to search for. Select e.g. <i>Player</i> ".
	'and type "goat" to see all tunes that were made in GoatTracker.</p>'.
	'With <i>rating</i> you can search those you rated 3 stars and up with "3-", '.
	"and with <i>version</i> you can type a number to see what's new in e.g. HVSC.",

	"<p>If you are using the default <i>WebSid emulator</i>, you can turn voices on or off ".
	"with <b>hotkeys</b> <code>1</code>, <code>2</code>, <code>3</code> and <code>4</code> (digi) ".
	"or with <code>q</code>, <code>w</code>, <code>e</code> and <code>r</code>.</p>".
	"<p>You can also hit <code>p</code> to open a tiny version of this player, <code>s</code> ".
	"to toggle showing this box, or hold down the key below <code>Esc</code> to fast forward.</p>".
	"Take a look in the <b>FAQ</b> tab for a full coverage of the hotkeys available.",

);

echo $tips[array_rand($tips)];
?>