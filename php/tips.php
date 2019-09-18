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
	"Some SID tunes may show thumbnails in the <b>CSDb</b> tab. You can then also click the ".
	"top right external link icon to see technical SID info.",

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
	"<p>If you choose a different emulator/handler (the top left drop-down box) or sub tune, this ".
	"too will be included in the URL line you're copying.</p>".
	"For a full list of URL parameters, see the bottom of the <b>FAQ</b> tab.",

	"<p>You have to be <b>logged in</b> to start rating with stars and create playlists.</p>".
	"<p>The user name and password box in top are also used when registering. Just type any user name ".
	"and if it's available, type a password for it.</p>".
	"<b>Disqus</b> has its own user management system and you will have to register for that separately ".
	"if you want to write a comment in that tab.",

	"<p>You can choose another <b>SID handler</b> in the top left drop-down box.</p>".
	"<p>The default is a JS emulator that can also play digi tunes. The second, ".
	"<i>Hermit's emulator</i>, can't do that but it's sometimes more accurate.</p>".
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
	"If you hold down <code>Shift</code> while clicking rating stars, you will clear them. ",

);

echo $tips[array_rand($tips)];
?>