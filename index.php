<?php
	if (false) die('DeepSID is being updated. Please return again in a few minutes.');

	require_once("php/class.account.php"); // Includes setup
	$user_id = $account->CheckLogin() ? $account->UserID() : 0;

	function isMobile() {
		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}
?>
<!DOCTYPE html>
<html lang="en-US" style="overflow:scroll-x;">

	<head>

		<meta charset="utf-8" />
		<meta name="viewport" content="width=450, initial-scale=1, shrink-to-fit=no" />
		<meta name="description" content="A modern online SID player for the High Voltage and Compute's Gazette SID collections." /> <!-- Max 150 characters -->
		<meta name="keywords" content="c64,commodore 64,sid,6581,8580,hvsc,high voltage,cgsc,compute's gazette,visualizer,stil,websid,jssid,hermit,soasc" />
		<meta name="author" content="Jens-Christian Huus" />
		<title>DeepSID | Chordian.net</title>
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans%3A400%2C700%2C400italic%2C700italic%7CQuestrial%7CMontserrat&#038;subset=latin%2Clatin-ext" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Asap+Condensed" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Kanit" />
		<link rel="stylesheet" type="text/css" href="//chordian.net/wordpress/wp-content/themes/olivi/style.css" />
		<link rel="stylesheet" type="text/css" href="//chordian.net/deepsid/css/jquery.mCustomScrollbar.min.css" />
		<link rel="stylesheet" type="text/css" href="css/chartist.css" />
		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

		<?php if (isset($_GET['websiddebug'])): ?>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/scriptprocessor_player.js"></script>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/backend_tinyrsid.js"></script>
		<?php else: ?>
			<script type="text/javascript" src="js/handlers/scriptprocessor_player.js"></script>
			<?php if (preg_match('/iPhone|iPad|iPod/', $_SERVER['HTTP_USER_AGENT'])): ?>
				<script type="text/javascript" src="js/handlers/backend_tinyrsid_ios.js"></script>
			<?php else: ?>
				<script type="text/javascript" src="js/handlers/backend_tinyrsid.js"></script>
			<?php endif ?>
		<?php endif ?>

		<script type="text/javascript" src="js/handlers/jsSID-modified.js"></script>
		<script type="text/javascript" src="js/handlers/howler.core.js"></script>
		<script type="text/javascript" src="js/jquery.mCustomScrollbar.concat.min.js"></script>
		<script type="text/javascript" src="js/chartist.min.js"></script>
		<script type="text/javascript" src="js/select.js"></script>
		<script type="text/javascript" src="js/player.js"></script>
		<script type="text/javascript" src="js/controls.js"></script>
		<script type="text/javascript" src="js/browser.js"></script>
		<script type="text/javascript" src="js/viz.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<link rel="icon" href="//chordian.net/images/avatar_c_olivi_32x32.png" sizes="32x32" />
		<link rel="apple-touch-icon-precomposed" href="//chordian.net/images/avatar_c_olivi_128x128.png" />
		<meta name="msapplication-TileImage" content="//chordian.net/images/avatar_c_olivi_128x128.png" />
		<?php // @link https://developers.facebook.com/tools/debug/sharing/ and https://cards-dev.twitter.com/validator ?>
		<meta property="og:title" content="DeepSID" />
		<meta property="og:type" content="website" />
		<meta property="og:image" content="http://chordian.net/deepsid/images/example.png" />
		<meta property="og:url" content="http://deepsid.chordian.net" />
		<meta property="og:description" content="A modern online SID player for the High Voltage and Compute's Gazette SID collections." />
		<meta name="twitter:card" content="summary" />

	</head>

	<body class="entry-content" style="background:#e7e8e0;" data-mobile="<?php echo isMobile(); ?>">

		<iframe id="download" style="display:none;"></iframe>

		<div id="panel">
			<div id="top">
				<div id="logo">D e e p S I D</div>
				<select id="dropdown-emulator" name="select-emulator" style="visibility:hidden;">
					<option value="websid">WebSid emulator</option>
					<option value="jssid">Hermit's emulator</option>
					<option value="soasc_auto">SOASC Automatic</option>
					<option value="soasc_r2">SOASC 6581 R2</option>
					<option value="soasc_r4">SOASC 6581 R4</option>
					<option value="soasc_r5">SOASC 8580 R5</option>
					<option value="download">Download SID file</option>
				</select>

				<?php if ($user_id) : ?>
					<div id="logged-in">
						<span id="logged-username"><?php echo $account->UserName(); ?></span>
						<button id="logout" title="Log out">
							<svg height="14" fill="#f5f5f1" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M704 1440q0 4 1 20t.5 26.5-3 23.5-10 19.5-20.5 6.5h-320q-119 0-203.5-84.5t-84.5-203.5v-704q0-119 84.5-203.5t203.5-84.5h320q13 0 22.5 9.5t9.5 22.5q0 4 1 20t.5 26.5-3 23.5-10 19.5-20.5 6.5h-320q-66 0-113 47t-47 113v704q0 66 47 113t113 47h312l11.5 1 11.5 3 8 5.5 7 9 2 13.5zm928-544q0 26-19 45l-544 544q-19 19-45 19t-45-19-19-45v-288h-448q-26 0-45-19t-19-45v-384q0-26 19-45t45-19h448v-288q0-26 19-45t45-19 45 19l544 544q19 19 19 45z"/></svg>
						</button>
					</div>
				<?php else : ?>
					<form id="userform" action="<?php echo $account->Self(); ?>" method="post" accept-charset="UTF-8">
						<fieldset>
							<div id="response">Login or register to rate tunes</div>
							<input type="hidden" name="submitted" value="1" />
							<input type="text" class="spmhidip" name="<?php echo $account->SpamTrapName(); ?>" style="display:none;" />

							<label for="username">User</label>
							<input type="text" name="username" id="username" value="<?php echo $account->PostValue('username'); ?>" maxlength="64" />

							<label for="password">Pw</label>
							<input type="password" name="password" id="password" maxlength="32" />

							<label>
								<input type="submit" name="submit" value="Submit" style="display:none;" />
								<button title="Log in or register">
									<svg height="14" fill="#f5f5f1" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1312 896q0 26-19 45l-544 544q-19 19-45 19t-45-19-19-45v-288h-448q-26 0-45-19t-19-45v-384q0-26 19-45t45-19h448v-288q0-26 19-45t45-19 45 19l544 544q19 19 19 45zm352-352v704q0 119-84.5 203.5t-203.5 84.5h-320q-13 0-22.5-9.5t-9.5-22.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q66 0 113-47t47-113v-704q0-66-47-113t-113-47h-312l-11.5-1-11.5-3-8-5.5-7-9-2-13.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q119 0 203.5 84.5t84.5 203.5z"/></svg>
								</button>
							</label>
						</fieldset>
					</form>
				<?php endif; ?>
			</div>

			<div id="info">
				<div id="info-text">
					<div style="text-align:center;font-size:12px;">
						<span style="position:relative;top:2px;">DeepSID is an online SID player for the High Voltage SID Collection and<br />
						more. It plays music created for the <a href="https://en.wikipedia.org/wiki/Commodore_64">Commodore 64</a> home computer.</span><br />
						<span style="position:relative;top:8px;">Click any of the folder items below to start browsing the collection.</span>
					</div>
				</div>
				<div id="memory-bar"><div id="memory-lid"></div><div id="memory-chunk"></div></div>
			</div>
			<div id="stil"></div>
			<div id="slider"></div>

			<div id="interactive">
				<div id="controls">
					<button id="play-pause" class="button-ctrls button-big button-idle disabled">
						<svg id="play" height="40" viewBox="0 0 48 48"><path d="M-838-2232H562v3600H-838z" fill="none"/><path d="M16 10v28l22-14z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
						<svg id="pause" height="40" viewBox="0 0 48 48" style="display:none;"><path d="M12 38h8V10h-8v28zm16-28v28h8V10h-8z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
					</button>

					<button id="stop" class="button-ctrls button-big button-selected disabled">
						<svg height="40" viewBox="0 0 48 48"><path d="M0 0h48v48H0z" fill="none"/><path d="M12 12h24v24H12z"/></svg>
					</button>
					<div class="divider"></div>
					<div class="button-area">
						<div class="button-tag">Faster</div>
						<button id="faster" class="button-ctrls button-lady button-idle disabled">
							<svg height="28" viewBox="0 0 48 48"><path d="M8 36l17-12L8 12v24zm18-24v24l17-12-17-12z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
						</button>
					</div>
					<div class="divider"></div>
					<div class="button-area thinner">
						<button id="subtune-plus" class="button-ctrls button-tiny button-idle disabled">
							<svg height="20" viewBox="0 0 48 48"><path d="M14.83 30.83l9.17-9.17 9.17 9.17 2.83-2.83-12-12-12 12z"/><path d="M0 0h48v48h-48z" fill="none"/></svg>
						</button>
						<div id="subtune-value" class="button-counter disabled"></div>
						<button id="subtune-minus" class="button-ctrls button-tiny button-idle disabled">
							<svg height="20" viewBox="0 0 48 48"><path d="M14.83 16.42l9.17 9.17 9.17-9.17 2.83 2.83-12 12-12-12z"/><path d="M0-.75h48v48h-48z" fill="none"/></svg>
						</button>
					</div>
					<div class="divider"></div>
					<div class="button-area">
						<div class="button-tag">Prev</div>
						<button id="skip-prev" class="button-ctrls button-lady button-idle disabled">
							<svg height="28" viewBox="0 0 48 48"><path d="M12 12h4v24h-4zm7 12l17 12V12z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
						</button>
					</div>
					<div class="button-area">
						<div class="button-tag">Next</div>
						<button id="skip-next" class="button-ctrls button-lady button-idle disabled">
							<svg height="28" viewBox="0 0 48 48"><path d="M12 36l17-12-17-12v24zm20-24v24h4V12h-4z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
						</button>
					</div>
					<div class="divider"></div>
					<div class="button-area">
						<div class="button-tag">Loop</div>
						<button id="loop" class="button-ctrls button-lady button-off disabled">
							<svg width="23" style="enable-background:new 0 0 42 28;position:relative;top:-1px;" version="1.1" viewBox="0 0 90 60"><path d="M80,11H61v14h15v21H14V25h21v11l20-18L35,0v11H10C4.477,11,0,15.477,0,21v29c0,5.523,4.477,10,10,10h70  c5.523,0,10-4.477,10-10V21C90,15.477,85.523,11,80,11z"/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg>
						</button>
						<input id="volume" type="range" min="0" max="100" value="100" step="1" disabled="disabled" />
					</div>
				</div>
				<div id="time"><span id="time-current">0:00</span> <div id="time-bar"><div></div></div> <span id="time-length">0:00</span></div>
			</div>

			<div id="songs">
				<div id="songs-buttons">
					<button id="folder-root" class="button-lady browser-ctrls">
						<svg height="26" viewBox="0 0 48 48"><path d="M12 12h4v24h-4zm7 12l17 12V12z"/><path d="M0 0h48v48H0z" fill="none"/></svg>
					</button>
					<button id="folder-back" class="button-lady browser-ctrls">
						<b style="font-size:18px;position:relative;top:-6px;">..</b>
					</button> <div id="path" class="ellipsis"></div>
					<div id="sort">
						<select id="dropdown-sort" name="sort"><!-- browser.js --></select>
					</div>
				</div>
				<div id="folders"><table></table></div>
				<img id="loading" src="images/loading.svg" style="display:none;" alt="" />
				<div id="search">
					<select id="dropdown-search" name="search-type">
						<option value="fullname">Filename</option>
						<option value="author">Author</option>
						<option value="copyright">Copyright</option>
						<option value="player">Player</option>
						<option value="stil">STIL</option>
						<option value="rating">Rating</option>
						<option value="country">Country</option>
						<option value="new">Version</option>
					</select>
					<input type="text" name="search-box" id="search-box" maxlength="32" />
					<button id="search-button" class="medium disabled" disabled="disabled">Search</button>
				</div>
			</div>
		</div>

		<div id="dexter">
			<div id="sites">
				<a href="http://chordian.net/2018/05/12/deepsid/">Blog Post</a>
					<span>&#9642</span>
				<a href="https://csdb.dk/forums/?roomid=14&topicid=129712">CSDb</a>
					<span>&#9642</span>
				<!--<a href="https://www.lemon64.com/forum/viewtopic.php?t=68056">Lemon64</a>
					<span>&#9642</span>-->
				<a href="https://twitter.com/jchuus">Twitter</a>
					<span>&#9642</span>
				<a href="https://www.facebook.com/groups/deepsid/">Facebook</a>
					<span>&#9642</span>
				<a href="https://github.com/Chordian/deepsid">GitHub</a>
				</div>
			<div id="tabs">
				<div class="tab unselectable" data-topic="profile" id="tab-profile">Profile</div>
				<div class="tab unselectable" data-topic="csdb" id="tab-csdb">CSDb<div id="note-csdb" class="notification csdbcolor"></div></div>
				<div class="tab unselectable" data-topic="gb64" id="tab-gb64">GB64<div id="note-gb64" class="notification gb64color"></div></div>
				<div class="tab unselectable" data-topic="stil" id="tab-stil">STIL</div>
				<?php if (!isMobile()): ?>
					<div class="tab unselectable" data-topic="piano">Piano</div>
					<div class="tab unselectable" data-topic="flood">Flood</div>
					<div class="tab unselectable" data-topic="disqus">Disqus<div id="note-disqus" class="notification"></div></div>
				<?php endif ?>
				<div class="tab right unselectable" data-topic="changes" style="width:80px;">Changes</div>
				<div class="tab right unselectable" data-topic="faq">FAQ</div>
				<div class="tab right unselectable" data-topic="about">About</div>
			</div>
			<div id="page">

				<!--<img id="loading-dexter" src="images/loading.svg" style="display:none;" alt="" />-->

				<?php if (!isMobile()): ?>

					<div id="topic-piano" class="topic ext" style="display:none;">
						<img id="waveform-colors" src="images/waveform_colors.png" alt="Waveform Colors" />
						<h2 style="margin-top:0;">Piano View<span class="h2-note">(Emulators only)</span></h2>
						<div class="edit" style="height:42px;width:683px;">
							<label class="unselectable" style="margin-right:2px;">Emulator</label>
							<button class="button-edit button-radio button-off viz-emu viz-websid" data-group="viz-emu" data-emu="websid">WebSid</button>
							<button class="button-edit button-radio button-off viz-emu viz-jssid" data-group="viz-emu" data-emu="jssid">Hermit</button>
							<span class="viz-warning" style="position:relative;top:1px;color:#a00;margin-left:12px;font-size:14px;display:none;">You need to enable one of these emulators</span>
							<div class="viz-buffer">
								<label for="dropdown-piano-buffer" class="unselectable">Buffer size</label>
								<select id="dropdown-piano-buffer" class="dropdown-buffer" name="sort">
									<!--<option value="256">256</option>
									<option value="512">512</option>-->
									<option value="1024" selected="selected">1024</option>
									<option value="2048">2048</option>
									<option value="4096">4096</option>
									<option value="8192">8192</option>
									<option value="16384">16384</option>
								</select>
							</div>
						</div>
						<div class="edit" style="height:42px;width:683px;">
							<button id="piano-gate" class="button-edit button-toggle button-on">On</button>
							<label for="piano-gate" class="unselectable">Gate bit</label>
							<button id="piano-noise" class="button-edit button-toggle button-off">Off</button>
							<label for="piano-noise" class="unselectable">Noise waveform</label>
							<button id="piano-slow" class="button-edit button-toggle button-off">Off</button>
							<label for="piano-slow" class="unselectable">Slow speed</label>
							<span style="float:right;">
								<label for="piano-combine" class="unselectable" style="margin-right:1px;">Combine into top piano</label>
								<button id="piano-combine" class="button-edit button-toggle button-off">Off</button>
							</span>
						</div>
						<?php require_once("php/piano.php"); ?>
						<h3 style="margin-top:16px;">A few words...</h3>
						<p>
							If the playback is choppy, try increasing the buffer size. Smaller values mean faster and
							smoother updating (default is 1024 which is the lowest possible) but also require a fast
							computer with a nifty web browser.
						</p>
						<p>
							The top right waveform legend explains the colors of notes on the keyboards. Red is pulse,
							green is triangle and blue is sawtooth. Gray is noise. Waveforms may be combined, but 31, 61
							and 71 are only audible on the 8580 SID chip.
						</p>
						<p>
							The numbers above the bars are <a href="https://simple.wikipedia.org/wiki/Hexadecimal_numeral_system" target="_blank">hexadecimal</a>.
							Pulse width has 12 bits and goes from 0 to 4095.
							The triangle indicates that it's most audible in the middle. The filter cutoff has 11 bits
							and thus goes from 0 to 2047.
						</p>
						<p>
							The small yellow bar is the filter resonance. It can go from 0 to 15 (maximum resonance).
							Resonance is a peaking effect which emphasizes frequency components at the cutoff frequency
							of the filter, causing a sharper sound.
						</p>
						<p>
							RM is ring modulation (non-harmonic overtones) and HS is hard synchronization (complex
							harmonic structures). Both effects require two voices &ndash; the previous voice as the
							carrier and the current voice as the modulator.
						</p>
						<p>
							Sometimes the use of gate bit (i.e. when the piano key is depressed then later released) make
							notes too quick to sense, or it may in some cases even hide them. Turning it off with the
							toggle button in top can amend this.
						</p>
						<p>
							Click the green buttons to toggle voices ON or OFF. You can also type
							<code>1</code>, <code>2</code> and <code>3</code> or alternatively <code>q</code>, <code>w</code>
							and <code>e</code>. (You can also use <code>4</code> and <code>r</code> for digi if you are using
							WebSid, but it is not reflected on this page.)
						</p>
						<p>If you want to "solo" a voice, hold down <code>Shift</code> while pressing the hotkey.</p>
					</div>

					<div id="topic-flood" class="topic ext" style="display:none;">
						<img id="waveform-colors" src="images/waveform_colors.png" alt="Waveform Colors" />
						<h2 style="margin-top:0;">Flood View<span class="h2-note">(Emulators only)</span></h2>
						<div class="edit" style="height:42px;width:683px;">
							<label class="unselectable" style="margin-right:2px;">Emulator</label>
							<button class="button-edit button-radio button-off viz-emu viz-websid" data-group="viz-emu" data-emu="websid">WebSid</button>
							<button class="button-edit button-radio button-off viz-emu viz-jssid" data-group="viz-emu" data-emu="jssid">Hermit</button>
							<span class="viz-warning" style="position:relative;top:1px;color:#a00;margin-left:12px;font-size:14px;display:none;">You need to enable one of these emulators</span>
							<div class="viz-buffer">
								<label for="dropdown-flood-buffer" class="unselectable">Buffer size</label>
								<select id="dropdown-flood-buffer" class="dropdown-buffer" name="sort">
									<!--<option value="256">256</option>
									<option value="512">512</option>-->
									<option value="1024" selected="selected">1024</option>
									<option value="2048">2048</option>
									<option value="4096">4096</option>
									<option value="8192">8192</option>
									<option value="16384">16384</option>
								</select>
							</div>
						</div>
						<div class="edit" style="height:42px;width:683px;">
							<button id="flood-zoom" class="button-edit button-toggle button-off">Off</button>
							<label for="flood-zoom" class="unselectable">Zoom</label>
							<button id="flood-pw" class="button-edit button-toggle button-on">On</button>
							<label for="flood-pw" class="unselectable">Pulse width</label>
						</div>
						<div id="flood">
							<div id="flood0" class="flood-river"></div>
							<div id="flood1" class="flood-river"></div>
							<div id="flood2" class="flood-river"></div>
						</div>
					</div>

					<div id="topic-disqus" class="topic" style="display:none;">
						<input type="checkbox" id="disqus-toggle" name="dtoggle" class="unselectable" checked />
        				<label for="disqus-toggle" class="unselectable">Enable Disqus</label>
						<b id="disqus-title">File: /</b>
						<!-- DISQUS BEGIN -->
						<div id="disqus_thread" style="margin-right:2px;"></div>
						<script>
							/**
							 * If refreshing a page with a '?file=' URL parameter in it, we have to adapt the code below
							 * to use the path. The reason for this is that after about 3-5 minutes of inactivity, Disqus
							 * somehow clears a cache that will make the code below take longer to load. Long enough for
							 * the 'browser.reloadDisqus()' function not to get handled. That could have been solved with
							 * a timer, but adapting the code below instead seemed more elegant.
							 */
							hashExcl = decodeURIComponent(location.hash); // Any Disqus link characters "#!" used?
							rootFile = hashExcl !== "" ? hashExcl.substr(2) : GetParam("file");
							rootFile = rootFile.replace("/_High Voltage SID Collection", "")
							if (rootFile.substr(0, 2) === "/_")
								rootFile = "/"+rootFile.substr(2); // Lose custom folder "_" character
							rootFile = rootFile.indexOf(".sid") === -1 && rootFile.indexOf(".mus") === -1 ? "" : "/#!"+rootFile;

							// @link https://disqus.com/admin/universalcode/#configuration-variables
							var disqus_config = function () {
								this.page.url = "http://deepsid.chordian.net"+rootFile;;
								this.page.identifier = "http://deepsid.chordian.net"+rootFile;;
								this.page.title = rootFile.substr(3);
								$("#disqus-title").empty().append("File: "+rootFile.substr(3));

								/*this.callbacks.onReady = [function() { 
									// This can be used to do something when all comments have loaded...
								}];*/
							};
							(function() { // DON'T EDIT BELOW THIS LINE
								var d = document, s = d.createElement('script');
								s.src = 'https://deepsid.disqus.com/embed.js';
								s.setAttribute('data-timestamp', +new Date());
								(d.head || d.body).appendChild(s);
							})();
						</script>
						<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
						<!-- DISQUS END -->
					</div>
				<?php endif ?>

				<div id="topic-profile" class="topic ext" style="display:none;">
				</div>

				<div id="topic-csdb" class="topic ext" style="display:none;">
					<h2>CSDb</h2>
					<p>This tab will show release lists and pages from CSDb as you click SID files.</p>
					<p>
						CSDb, short for <a href="https://csdb.dk/help.php?section=intro" target="_blank">The Commodore
						64 Scene Database</a>, is the largest and most comprehensive database about C64 releases
						pertaining to the demo scene. It's where all the cool dudes go to hang out.
					</p>
					<br />
					<p>
						<i>This does not work in
						<a href="http://www.c64music.co.uk/" target="_blank">Compute's Gazette SID Collection</a> as
						CSDb has almost no data for it.</i>
					</p>
				</div>

				<div id="topic-gb64" class="topic ext" style="display:none;">
					<h2>GameBase64</h2>
					<p>This tab will show links to game entries in GameBase64 as you click SID files that were
						used in at least one C64 game, released or unreleased (these are listed as a preview).</p>
					<p>
						<a href="http://www.gamebase64.com/" target="_blank">GameBase64</a> is a large database 
						for C64 games with credits, details and screenshots.
					</p>
					<br />
					<p>
						<i>This does not work in
						<a href="http://www.c64music.co.uk/" target="_blank">Compute's Gazette SID Collection</a>.</i>
					</p>
				</div>

				<div id="topic-stil" class="topic" style="display:none;">
				</div>

				<div id="topic-about" class="topic" style="display:none;">
					<h2>About</h2>
					<p>
						DeepSID is an online SID player that can play music originally composed for the
						<a href="https://en.wikipedia.org/wiki/Commodore_64">Commodore 64</a>, a home computer
						that was very popular back in the 80's and 90's. This computer had an amazing sound chip
						called <a href="https://en.wikipedia.org/wiki/MOS_Technology_SID">SID</a>.
					</p>
					<p>
						The SID chip was really ahead of its time. Although it only had 3 voices, it offered
						oscillators of 8 octaves, ADSR, four waveforms, pulse width modulation, multi mode filtering,
						ring modulation, and hard synchronization. It really was like a tiny synthesizer, and you
						could even make it play digi samples along with the SID voices.
					</p>

					<h2>Credits</h2>

					<h3>UI design and programming</h3>
					<p>
						Jens-Christian Huus (<a href="//chordian.net/">Chordian</a>)<br />
						<a href="//chordian.net/2018/05/12/deepsid/">http://chordian.net/2018/05/12/deepsid/</a>
					</p>

					<h3>SID emulators for JavaScript</h3>
					<p>
						WebSid by Jürgen Wothke (<a href="http://www.wothke.ch/tinyrsid/index.php">Tiny'R'Sid</a>)<br />
						<a href="http://www.wothke.ch/websid/">http://www.wothke.ch/websid/</a><br />
						<a href="https://github.com/wothke/websid">https://github.com/wothke/websid</a><br />
						<a href="https://github.com/wothke/webaudio-player">https://github.com/wothke/webaudio-player</a>
					</p>
					<p>
						jsSID by Mihály Horváth (<a href="https://csdb.dk/scener/?id=18806">Hermit</a>)<br />
						<a href="http://hermit.uw.hu/index.php">http://hermit.uw.hu/index.php</a>
					</p>

					<h3>Audio API library for SOASC</h3>
					<p>
						Howler by James Simpson (<a href="https://goldfirestudios.com/">GoldFire Studios</a>)<br />
						<a href="https://github.com/goldfire/howler.js">https://github.com/goldfire/howler.js</a>
					</p>

					<h3>Libraries of SID tunes</h3>
					<p>
						High Voltage SID Collection #70<br />
						<a href="https://www.hvsc.c64.org/">https://www.hvsc.c64.org/</a>
					</p>
					<p>
						Compute's Gazette SID Collection #136<br />
						<a href="http://www.c64music.co.uk/">http://www.c64music.co.uk/</a>
					</p>
					<p>
						Stone Oakvalley's Authentic SID Collection<br />
						<a href="http://www.6581-8580.com/">http://www.6581-8580.com/</a>
					</p>

					<h3>Other resources used</h3>
					<p>
						SIDId by Lasse Öörni (<a href="https://cadaver.github.io/">Cadaver</a>)<br />
						<a href="http://csdb.dk/release/?id=112201">http://csdb.dk/release/?id=112201</a>
					</p>
					<p>
						SIDInfo by Matti Hämäläinen (ccr)<br />
						<a href="https://csdb.dk/release/?id=164751">https://csdb.dk/release/?id=164751</a><br />
						<a href="https://tnsp.org/hg/sidinfo/">https://tnsp.org/hg/sidinfo/</a>
					</p>
					<p>
						Chartist.js by Gion Kunz (<a href="https://github.com/gionkunz">GitHub</a>)<br />
						<a href="https://gionkunz.github.io/chartist-js/">https://gionkunz.github.io/chartist-js/</a>
					</p>
					<p>
						jQuery custom scrollbar by Manolis Malihutsakis (<a href="http://manos.malihu.gr/">malihu</a>)<br />
						<a href="http://manos.malihu.gr/jquery-custom-content-scroller/">http://manos.malihu.gr/jquery-custom-content-scroller/</a>
					</p>
				</div>

				<div id="topic-faq" class="topic" style="display:none;">
					<h2>Frequently Asked Questions</h2>

					<h3>How do I register?</h3>
					<p>
						The user name and password boxes are used for both registering and logging in. To register,
						just type the user name you want. If it is available (a status message tells you) then type a
						password and hit the button.
					</p>

					<h3>Can you please make an app or an offline version?</h3>
					<p>
						I wanted to make an awesome online web player for SID tunes and I believe I have accomplished
						that. It was never my intention to make an app or an offline player. An online player gives me
						immediate access to e.g. HVSC and CGSC without having to download anything first. Just
						everything ready to play, search through and rate no matter if I'm on my desktop, on my iPhone
						or on my iPad.
					</p>
					<p>
						However, it's possible to use an offline player with DeepSID. Just select the
						<code>Download</code> option in the top drop-down box and start clicking rows. Make sure you
						associate your offline player with automatically playing the tunes.
					</p>

					<h3>How do I make my own playlists?</h3>
					<p>
						You need to be using a mouse to create and manage playlists. This cannot be done on a mobile
						device (although you can enjoy your existing playlists there). Also, you must of course be
						logged in (in the top, not Disqus).</p>
					<ol>
						<li>Start finding awesome SID tunes in the HVSC or CGSC folders.</li>
						<li>When you find one you like, right-click it. A context menu appears. Choose to add it to a new
							playlist.</li>
						<li>Browse to the root. Your new playlist should now be there in the bottom with the SID file
							name.</li>
						<li>Right-click your playlist folder and choose to rename it.</li>
						<li>Continue with other awesome SID tunes, only this time choose to add them to your existing
							playlist.</li>
						<li>Inside your playlist folder, you can right-click SID files there and either rename or
							remove them.</li>
						<li>If you later want to share your playlist, you can right-click the playlist folder and choose
							to publish it.</li>
						<li>Or, if you later hate your playlist, you can right-click the playlist folder and choose to
							delete it.</li>
					</ol>
					<p>
						Published playlists appear further up in the root and can be seen by everyone (even those that
						are not logged in) but you're still the only one that may edit it. When you enter a public
						playlist, you can see who made it.
					</p>

					<h3>What are those options in the top left drop-down box?</h3>
					<p>
						It's where you choose a handler for the SID files. Some are JavaScript emulators, some are
						real C64 recordings played with a normal audio player, and one can use your favorite offline
						player.
					</p>
					<table style="xwidth:610px;font-size:14px;">
						<tr>
							<th style="width:150px;">Handler</th><th>Description</th>
						</tr>
						<tr>
							<td>WebSid emulator</td><td>The default option is the JS emulator originally used
							by <a href="http://www.wothke.ch/tinyrsid/index.php">Tiny'R'Sid</a>. It emulates standard
							SID as well as digi tunes, 2SID and 3SID, and even MUS files in Compute's Gazette SID
							Collection.</td>
						</tr>
						<tr>
							<td>Hermit's emulator</td><td>Hermit's jsSID emulator is extremely compact and can
							play standard SID as well as 2SID and 3SID. Unfortunately it can't do digi tunes, but it
							makes up for that by being a steadfast emulator.</td>
						</tr>
						<tr>
							<td>SOASC R2, R4, R5</td><td>These are real C64 recordings streamed from
							<a href="http://www.6581-8580.com/">Stone Oakvalley's Authentic SID Collection</a>. R2 is
							bright filter, R4 is deep filter (think drowning radio) and R5 is 8580 with its improved
							filter.</td>
						</tr>
						<tr>
							<td>SOASC Automatic</td><td>This option automatically chooses the recording type that is
							correct for the SID tune. R2 is chosen for 6581 tunes and R5 is chosen for 8580 tunes.</td>
						</tr>
						<tr>
							<td>Download SID file</td><td>This makes the browser download the tunes. This is especially
							useful if an offline player has been associated with automatically playing it. Then it's like
							having an extra play option.</td>
						</tr>
					</table>
					<p>Note that for the SOASC options, playing SID tunes may be delayed 1-3 seconds due to handshaking
						with a download script on Stone Oakvalley's server. Also, none of the emulators can play BASIC
						tunes as that requires ROM code.</p>

					<h3>Why can't I see the right text area on mobile devices?</h3>
					<p>
						That's by design, actually. Only the player pane is supposed to be visible on mobile devices
						because of the limited screen space. This right text area is only really available for desktop
						computers.
					</p>

					<h3>Can you add a playlist randomizer?</h3>
					<p>
						You can achieve the same thing by selecting the <code>Shuffle</code> option in the sort
						drop-down box.
					</p>

					<h3>Can I search for a range of ratings?</h3>
					<p>
						Yes, if you type e.g. <code>3-</code> for a search for ratings, you will get a list of all the
						SID tunes and folders you have rated three stars or more. If you type <code>1-</code>, you will
						see <i>all</i> of your ratings.
					</p>

					<h3>Can I turn voices on and off?</h3>
					<p>
						Yes. Use keys <code>1</code>, <code>2</code>, <code>3</code>
						and <code>4</code> or alternatively <code>q</code>, <code>w</code>, <code>e</code> and
						<code>r</code>. The first three are for the normal SID voices and the fourth is for toggling any
						digi stuff (WebSid emulator only).
					</p>
					<p>If you want to "solo" a voice, hold down <code>Shift</code> while pressing the hotkey.</p>
					<p>In the piano view tab, you can also click the green number buttons.</p>

					<h3>Any other hotkeys worth knowing about?</h3>
					<p>
						Hit <code>p</code> in desktop web browsers to pop up a tiny version of the player.
					</p>
					<p>
						Hit <code>s</code> to toggle the STIL box on or off.
					</p>
					<p>
						You can hold down the key just below the <code>ESC</code> key to fast forward.
					</p>
					<p>
						If you hold down <code>Shift</code> while clicking rating stars, you will clear them. (However,
						it's usually easier just to click the same star again if you want to clear the rating.)
					</p>

					<h3>Why doesn't this work in Internet Explorer?</h3>
					<p>
						The audio handlers all use an API called <i>Web Audio</i> which is
						<a href="https://caniuse.com/#search=web%20audio">not supported by Internet Explorer</a>.
						You need a modern web browser to use this site.
					</p>

					<?php if (false): // All handlers should now work fine on iOS ?>
						<h3>Why doesn't this work perfectly on iPhone or iPad?</h3>
						<p>
							Apple hacked their iOS to avoid autoplaying sound on their mobile devices unless the user
							touches the screen first. This sometimes makes it difficult to make things work 100%. We're
							revisiting this problem from time to time.
						</p>
						<ul>
							<li><b>WebSid emulator:</b> Currently does not work on iPhone and iPad.</li>
							<li><b>Hermit's emulator:</b> Since iOS patch 11.4, Hermit's emulator works on iPhone and iPad.</li>
							<li><b>Howler for SOASC:</b> Fixed 2018-07-15 and thus also works on iPhone and iPad.</li>
						</ul>
					<?php endif ?>

					<h3>Why can't I see the load/end addresses and size of the SID tune?</h3>
					<p>
						See that blue bar just below the top box with the title, author and copyright lines? It's the C64
						memory, from $0000 to $FFFF. The dark blue blob that appears there is the SID tune as it takes up
						space. If you hover your mouse pointer on it, the tooltip will tell you the memory boundaries in
						hex and the size in bytes.
					</p>

					<h3>What URL parameters are available?</h3>
					<p>
						The following URL parameters currently work:
					</p>
					<table style="font-size:14px;">
						<tr>
							<th style="width:85px;">Parameter</th><th>Description</th>
						</tr>
						<tr>
							<td>file</td><td>A file to play or a folder to show (use full root paths for both)</td>
						</tr>
						<tr>
							<td>subtune</td><td>The subtune to play; must be used together with <code>file</code></td>
						</tr>
						<tr>
							<td>emulator</td><td>Set to <code>websid</code>, <code>jssid</code>, <code>soasc_auto</code>,
								<code>soasc_r2</code>,<code>soasc_r4</code>, <code>soasc_r5</code> or <code>download</code></td>
						</tr>
						<tr>
							<td>search</td><td>A search query (just like when typed in the bottom)</td>
						</tr>
						<tr>
							<td>type</td><td>Search type; <code>fullname</code>, <code>author</code>,
								<code>copyright</code>, <code>player</code>, <code>stil</code>, <code>rating</code>,
								<code>country</code> or <code>new</code> (HVSC)</td>
						</tr>
						<tr>
							<td>tab</td><td>Set to <code>csdb</code>, <code>gb64</code>, <code>stil</code>, <code>piano</code>, <code>flood</code>,
								<code>disqus</code>, <code>about</code>, <code>faq</code> or <code>changes</code> to select that tab</td>
						</tr>
						<tr>
							<td>csdbtype</td><td>Set to <code>sid</code> or <code>release</code> to show a CSDb entry;
								must be used together with <code>csdbid</code></td>
						</tr>
						<tr>
							<td>csdbid</td><td>Set to an ID value to show a CSDb entry;
								must be used together with <code>csdbtype</code></td>
						</tr>
					</table>
					<p>
						An example to show a specific folder:<br />
						<a href="//deepsid.chordian.net?file=/MUSICIANS/J/JCH/">http://deepsid.chordian.net?file=/MUSICIANS/J/JCH/</a>
					</p>
					<p>
						An example to play a SID tune:<br />
						<a href="//deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=jssid&subtune=2">http://deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=jssid&subtune=2</a>
					</p>
					<p>
						An example to show a CSDb entry:<br />
						<a href="//deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519">http://deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519</a>
					</p>

				</div>

				<div id="topic-changes" class="topic" style="display:none;">
					<h2>Changes</h2>

					<h3>January 7, 2019</h3>
					<ul>
						<li>Fixed a bug where iOS devices didn't show the correct number of maximum sub tunes.</li>
					</ul>

					<h3>January 6, 2019</h3>
					<ul>
						<li>Multiple occurrences of the same SID file are now possible in playlists, to support different sub tunes for each.</li>
						<li>Playlists now show the sub tune that will be played for a SID tune instead of the maximum amount available.</li>
						<li>Increased the size of CSDb comment avatars slightly.</li>
					</ul>

					<h3>January 5, 2019</h3>
					<ul>
						<li>Fixed a name sorting discrepancy in playlists.</li>
						<li>The formatting for the HVSC/CGSC shortcodes are now preserved when editing playlist entries.</li>
					</ul>

					<h3>January 4, 2019</h3>
					<ul>
						<li>You can now specify a default sub tune for a playlist SID entry. Just right-click the SID row in your
							playlist and choose <code>Select Subtune</code> to edit it.
						</li>
						<li>Fixed the following tunes that were not updated properly during the HVSC #70 update:</li>
						<ul>
							<li><a href="#" class="redirect">/MUSICIANS/S/Scarzix/Real_Wat_d_fuc.sid</a></li>
							<li><a href="#" class="redirect">/MUSICIANS/S/Steel/Danger_Dawg_Intro.sid</a></li>
							<li><a href="#" class="redirect">/MUSICIANS/S/Steel/Party_Pirates_Part_One.sid</a></li>
							<li><a href="#" class="redirect">/MUSICIANS/S/Steel/Party_Pirates_Part_Two.sid</a></li>
							<li><a href="#" class="redirect">/MUSICIANS/C/Cruz_Debby/Muss_I_Denn.sid</a></li>
						</ul>
					</ul>

					<h3>January 3, 2019</h3>
					<ul>
						<li>The <code>Decent</code> and <code>Good</code> modes are now ready for letter folder <code>J</code> in MUSICIANS.</li>
					</ul>

					<h3>January 1, 2019</h3>
					<ul>
						<li>The top list for number of composers in countries now have search links.</li>
						<li>Added another top list for most popular start address in memory.</li>
						<li>Added another top list for total playing time composed (hours and minutes).</li></li>
						<li>Number of rows for the top lists can now be adjusted with drop-down boxes.
							The default was reduced to 10.</li>
					</ul>

					<h3>December 31, 2018</h3>
					<ul>
						<li>The profile tab in the root now serves as a welcome page with two top lists. The contents
							for these are chosen randomly, but you can select other lists in the drop-down boxes.</li>
						<li>Added another top list for number of composers in countries.</li>
					</ul>

					<h3>December 29, 2018</h3>
					<ul>
						<li>Composer folders in letter folders of MUSICIANS now show the correct total of files that
							includes sub folders.</li>
						<li>The <code>Decent</code> and <code>Good</code> modes are now ready for letter folder <code>I</code> in MUSICIANS.</li>
					</ul>

					<h3>December 28, 2018</h3>
					<ul>
						<li>Avatar images can now be seen in CSDb comments for composers with a HVSC folder.</li>
						<li>The <code>Decent</code> and <code>Good</code> modes are now ready for letter folder <code>H</code> in MUSICIANS.</li>
						<li>Fixed a bug where user handles were often unknown in CSDb comments for SID entries.</li>
					</ul>

					<h3>December 27, 2018</h3>
					<ul>
						<li>New files and folders in HVSC and CGSC now have a yellow indicator; a <b>*</b> for folders
							and <b>NEW</b> for files.</li>
						<li>All new files in HVSC #70 are now connected to CSDb entries.</li>
					</ul>

					<h3>December 26, 2018</h3>
					<ul>
						<li>Searching for HVSC/CGSC versions now also return new folders.</li>
						<li>Fixed a bug that prevented
							<a href="http://deepsid.chordian.net/?file=/MUSICIANS/S/Syboxez/Back_to_BASICs.sid" class="redirect">Back to BASICs.sid</a>
							from being played by the emulators.</li>
						<li>Added a <code>Common</code> filter option in the root. Select this to show the HVSC and CGSC
							collection folders along with common collection/competition playlists, plus of course your
							personal playlists. In other words, the new option filters out public playlists that are
							personal in nature, such as e.g. lists of favorites.</li>
					</ul>

					<h3>December 24, 2018</h3>
					<ul>
						<li>Added composer profiles for the new folders in HVSC #70.</li>
						<li>Deleted the two server folders for "Datastorm 2018" and "From JCH's Special Collection" as their
							contents can now be found in HVSC #70 instead. Everything below HVSC and CGSC are now only playlists.</li>
					</ul>

					<h3>December 23, 2018</h3>
					<ul>
						<li>The <a href="https://www.hvsc.c64.org/">High Voltage SID Collection</a> has been upgraded to the latest version #70.</li>
					</ul>

					<h3>December 22, 2018</h3>
					<ul>
						<li>Added a <code>Good</code> filter option to the letter folders in MUSICIANS. The <code>Decent</code>
						option will show folders that JCH gave two stars or more, and <code>Good</code> will show folders that
						JCH gave three stars or more.</li>
					</ul>

					<h3>December 21, 2018</h3>
					<ul>
						<li>The <code>Decent</code> mode is now ready for letter folder <code>G</code> in MUSICIANS.</li>
					</ul>

					<h3>December 20, 2018</h3>
					<ul>
						<li>The source codes for DeepSID are now on <a href="https://github.com/Chordian/deepsid">GitHub</a>.
							Feel free to use the issue tracker there.</li>
					</ul>

					<h3>December 18, 2018</h3>
					<ul>
						<li>The sort drop-down box in the root has been changed into a filter mode. Select <code>Personal</code>
							to filter out all public playlists from other users. The mode chosen is sticky.</li>
						<li>The <code>Decent</code> mode is now ready for letter folder <code>F</code> in MUSICIANS.</li>
					</ul>

					<h3>December 16, 2018</h3>
					<ul>
						<li>Using the middle mouse button on the <code>Faster</code> button now fast forwards even faster.</li>
						<li>Repeatedly pressing a voice hotkey while holding down <code>Shift</code> now toggles solo versus all voices on.</li>
					</ul>

					<h3>December 14, 2018</h3>
					<ul>
						<li>A flood river now has a yellow background if filter is turned on for that voice.</li>
					</ul>

					<h3>December 13, 2018</h3>
					<ul>
						<li>The <code>Decent</code> mode is now ready for letter folder <code>E</code> in MUSICIANS.</li>
					</ul>

					<h3>December 12, 2018</h3>
					<ul>
						<li>The red dots in the flood view for pulse waveforms now also show the pulse width as a "coat" around
							them. The biggest "coat" equals the loudest pulse width. This can be turned off with a new toggle button.</li>
						<li>Added a toggle button in the flood view for zooming the rivers. When this is enabled, only the lower half
							of the frequencies are shown (which is usually where most of the action takes place anyway).</li>
						<li>The flood rivers are now dimmed when turning voices off.</li>
						<li>Split lines are now shown in the flood rivers when starting and stopping tunes.</li>
						<li>Fixed a bug where the flood view wouldn't scroll on Mac Safari.</li>
					</ul>


					<h3>December 11, 2018</h3>
					<ul>
						<li>A new flood view tab has been introduced (emulators only). It's still in BETA and may get more features.
							Set buffer size to 1024 to get the most out of it.</li>
					</ul>

					<h3>December 10, 2018</h3>
					<ul>
						<li>The <code>Decent</code> mode is now ready for letter folder <code>D</code> in MUSICIANS.</li>
					</ul>

					<h3>December 4, 2018</h3>
					<ul>
						<li>If a SID file from HVSC is not played in its home folder, the song name and author now become links.</li>
						<li>Fixed a bug where the previous links were not applied while searching.</li>
					</ul>

					<h3>December 3, 2018</h3>
					<ul>
						<li>DeepSID's copy of <a href="http://www.c64music.co.uk/">Compute's Gazette SID Collection</a> has now been upgraded to version 1.36.</li>
						<li>Renamed the <code>HVSC</code> search type to <code>Version</code> as the field now works for
							both HVSC and CGSC. Type <code>69</code> to see new files in the latest HVSC, or <code>1.36</code>
							(<code>136</code> also works) to see new files in the latest CGSC.</li>
						<li>The bottom right corner of the info box now correctly shows the CGSC version, when available.</li>
					</ul>

					<h3>December 2, 2018</h3>
					<ul>
						<li>The <code>Decent</code> mode is now ready for letter folder <code>C</code> in MUSICIANS.</li>
					</ul>

					<h3>November 29, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed filter signal inversion, PAL/NTSC timing precision,
							ADSR thresholds, licensing inconsistency and an additional delay-bug "Plan B" case.</li>
						<li>Slowed down the <code>Faster</code> button to make it audible and also avoid Hermit's
							emulator going out of sync.</li>
					</ul>

					<h3>November 28, 2018</h3>
					<ul>
						<li>Fixed a known bug where subsequently "soloing" emulator voices got messed up.</li>
						<li>Renamed the new <code>Quality</code> mode to <code>Decent</code> instead as it could be
							erroneously construed as a <i>high quality</i> option. That's not its purpose; it is just
							supposed to filter out the worst folders.</li>
						<li>The buffer size value in the piano tab is now remembered between browser sessions.</li>
						<li>The <code>Decent</code> mode is now also remembered between browser sessions. However, if you
							enter a folder that is not ready yet, it will be bumped back to <code>All</code> again.</li>
						<li>Added external links in the top right corner of the web site.</li>
					</ul>

					<h3>November 27, 2018</h3>
					<ul>
						<li>Changed the sort drop-down box into a filter mode inside any letter folder in MUSICIANS. Select
							<code>Quality</code> to filter out the bad apples. The folders are based on my own assessment
							of tunes in each composer folder. I have tried to be as fair as possible, listening to about
							half a dozen or more in each composer folder.</li>
						<li>Evaluating quality takes time and not all folders are ready yet. Folders ready so far:
							<code>0-9</code>, <code>A</code>, <code>B</code>, <code>L</code> and <code>Q</code></li>
						<li>Fixed a new bug that prevented Hermit's emulator from playing 2SID and 3SID tunes properly.</li>
						<li>Fixed a known bug where your latest ratings were not shown after filtering or sorting.</li>
					</ul>

					<h3>November 26, 2018</h3>
					<ul>
						<li>Fixed a bug that sometimes skewed the first line in MUS info graphics. Thanks again to Peter Weighill.</li>
					</ul>

					<h3>November 25, 2018</h3>
					<ul>
						<li>The STIL tab now changes name to "Lyrics" for folders and files in Compute's Gazette SID Collection.</li>
						<li>The CSDb and GB64 tabs are now disabled when entering Compute's Gazette SID Collection.</li>
						<li>A sub folder of a composer in HVSC now re-use the same profile unless a unique one has been created for it.</li>
						<li>Fixed a bug where number of sub tunes reported for a MUS file was wrong for both emulators.</li>
					</ul>

					<h3>November 24, 2018</h3>
					<ul>
						<li>Improved the info text for MUS files in Compute's Gazette SID Collection considerably, now
							utilizing proper C64 layout and colors. Thanks to Peter Weighill for helping out with this.</li>
					</ul>

					<h3>November 23, 2018</h3>
					<ul>
						<li>Added a drop-down box in the piano tab for setting the buffer size. The smallest value of 1024
							is the default and makes for the smoothest updating. Increase the value if playback is choppy.</li>
						<li>Upgraded the WebSid emulator. Fixed a bug introduced in the ported version of Hermit's anti-aliasing.</li>
						<li>The WebSid emulator can now also show VBI, CIA, or quickspeed values 2x, 3x, 4x, etc.</li>
						<li>A new button in the piano tab can now slow down the tune in case you want to play along.</li>
					</ul>

					<h3>November 22, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Improved ADSR delay-bug "Plan B" rules and removed envelope flip hack.</li>
						<li>You can now "solo" an emulator voice by holding down <code>Shift</code> while pressing its hotkey.</li>
						<li>Added ring modulation and hard synchronization arrow indicators in the piano tab.</li>
						<li>Minor optimization of the DOM handling in the piano tab.</li>
					</ul>

					<h3>November 21, 2018</h3>
					<ul>
						<li>In the piano tab, voices being filtered are now indicated by the filet above the keys turning brown.</li>
						<li>Upgraded WebSid and its ScriptProcessor to enable support for custom buffer sizes. The size
							was then reduced from 8192 to 1024 bytes, thereby making it just as smooth in the piano tab as Hermit's emulator.</li>
						<li>Mobile devices (which can't see the piano tab anyway) now use a buffer size of 16384 bytes for both of the
							emulators. This should reduce the chance of choppy playback on mobile devices.</li>
						<li>You can now scroll horizontally on smaller desktop displays.</li>
					</ul>

					<h3>November 20, 2018</h3>
					<ul>
						<li>Added a small vertical bar for filter resonance in the piano tab.</li>
						<li>Fixed a bug where the pulse width bars in the piano tab were not calculated properly.</li>
						<li>Fixed various bugs in the piano tab pertaining to re-enabling voices after being turned off.</li>
						<li>Fixed a bug that caused the three info tab pages to scroll badly.</li>
						<li>Upgraded the WebSid emulator. Tuned "Plan B" proper delay-bug triggering and bug fixed <a href="//deepsid.chordian.net?file=/MUSICIANS/B/Beyond_Reproach/Super_Carling_the_Spider_credits.sid" class="redirect">this tune</a>.</li>
					</ul>

					<h3>November 19, 2018</h3>
					<ul>
						<li>A new piano view tab has been introduced (emulators only).</li>
						<li>To support the new piano view mode, turning voices ON and OFF is now also possible with Hermit's emulator.</li>
					</ul>

					<h3>November 17, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed a PETSCII parsing bug in MUS files and added regular MUS player init.</li>
					</ul>

					<h3>November 15, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Added "Plan B" ADSR delay-bug handling, fixed a bug in the MUS
							loader, and improved special cases of digi tune handling.</li>
					</ul>

					<h3>November 12, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. New noise waveform handling, added end filter, and improved stability.</li>
					</ul>

					<h3>November 1, 2018</h3>
					<ul>
						<li>Deleted all my Disqus comments with links to CSDb and GB64 entries as they are now redundant
							because of the corresponding tabs. This should make the comment indicators in SID rows more
							valuable.</li>
					</ul>

					<h3>October 31, 2018</h3>
					<ul>
						<li>Fixed a bug where only one of multiple shared entries in a competition page was listed.</li>
					</ul>

					<h3>October 30, 2018</h3>
					<ul>
						<li>Fixed a bug where CSDb comments were not shown for a SID tune with 0 releases found.</li>
						<li>Fixed a rare case where a <code>[url]</code> shortcode wasn't translated properly in CSDb comments.</li>
					</ul>

					<h3>October 29, 2018</h3>
					<ul>
						<li>Improved the conversion of raw URL types in CSDb comments into clickable links.</li>
					</ul>

					<h3>October 28, 2018</h3>
					<ul>
						<li>You can now clear a rating by clicking the same rating it already has. The old method of
							holding down <code>Shift</code> is still there, but the new method should also work on mobile devices.</li>
						<li>Extremely long words should no longer be able to skew the CSDb comments table.</li>
					</ul>

					<h3>October 27, 2018</h3>
					<ul>
						<li>The handling of URL parameters should now be a little more robust.</li>
						<li>Added flag icons after countries in both profiles and competition pages.</li>
					</ul>

					<h3>October 26, 2018</h3>
					<ul>
						<li>The new competition pages now have CSDb comments too.</li>
						<li>The profile tab and previous CSDb page are now updated properly after having redirected.</li>
						<li>Folder icons now indicate if a profile has a photo by showing the corner of a photograph sticking up.</li>
					</ul>

					<h3>October 25, 2018</h3>
					<ul>
						<li>You can now view competition results from CSDb and even play the other tunes. Just click the
							SHOW button next to the achievement line in the CSDb page for a SID tune whenever it's available.</li>
					</ul>

					<h3>October 24, 2018</h3>
					<ul>
						<li>Size of tunes in the memory bar are now two bytes less, skipping the file load address.</li>
						<li>The HVSC and CGSC folders now use the normal size font for SID files again.</li>
						<li>An external <code>?file=</code> link to an actual SID file now also displays the composer profile.</li>
						<li>Removed the page reload on the logo to avoid accidents when trying to select a different handler.</li>
						<li>Fixed a bug where the HVSC and CGSC path names were not shortened while searching in the root.</li>
						<li>Fixed a bug that replaced too much in STIL comments when making links out of SID references.</li>
						<li>Fixed a bug preventing some external <code>?file=</code> links from playing the tune it was supposed to.</li>
					</ul>

					<h3>October 23, 2018</h3>
					<ul>
						<li>All stereo MUS files in Compute's Gazette SID Collection can now be played with the WebSid
							emulator as they are in fact also viable as mono files. Note that the SOASC handlers still
							won't play these, however.</li>
						<li>Long HVSC and CGSC path names are now automatically shortened in playlists.</li>
						<li>Removed the HVSC web site from its main folder as the statistics are probably more interesting.</li>
					</ul>

					<h3>October 21, 2018</h3>
					<ul>
						<li>The three HVSC folders DEMOS, GAMES and MUSICIANS now have a parent folder lined up next to
							the CGSC folder. All outside links that uses the old folder standard as well as all playlists
							should still be compatible.</li>
						<li><del>Entering the main folder for the High Voltage SID Collection shows the web site in the Profile tab.</del></li>
						<li>Shortened the HVSC and CGSC folder names when searching which makes it easier to read long names.</li>
						<li>Restored all the Disqus comments inside the HVSC folder tree.</li>
						<li>Fixed a bug that prevented the profile pictures from being displayed.</li>
					</ul>

					<h3>October 18, 2018</h3>
					<ul>
						<li>Profiles for groups now also show CSDb data as well as an external corner link.</li>
						<li>Most single release CSDb pages that appear without a list first now have a BACK button too.</li>
						<li>The profile charts are no longer shown for the sub folders in Compute's Gazette SID Collection.</li>
						<li>Entering the main folder for Compute's Gazette SID Collection shows the web site in the Profile tab.</li>
					</ul>

					<h3>October 17, 2018</h3>
					<ul>
						<li>You can now also search for a country. For example, click <a href="//deepsid.chordian.net?search=denmark&type=country">here</a> for a list of the Danish C64 composers.</li>
					</ul>

					<h3>October 16, 2018</h3>
					<ul>
						<li>Added unique folder icons for single composers and groups of composers.</li>
					</ul>

					<h3>October 15, 2018</h3>
					<ul>
						<li>Public playlists now also use a smaller font.</li>
					</ul>

					<h3>October 13, 2018</h3>
					<ul>
						<li>Some profile pages may now also show a brand or logo that the composer is known for.</li>
					</ul>

					<h3>October 12, 2018</h3>
					<ul>
						<li>The profile page for a composer/folder now has its own tab which is also the default.</li>
						<li>A notification count will be seen on the unselected CSDb tab if there are entries. If there is
							a release page instead, the notification will show a dot character instead.</li>
						<li>Fixed a bug where the profile charts would sometimes appear flattened towards the left side.</li>
					</ul>

					<h3>October 11, 2018</h3>
					<ul>
						<li>DeepSID should now be much more responsive when using an SOASC handler on a desktop computer.</li>
						<li>There is now a small bar in the bottom of the STIL box that can drag it smaller or larger.</li>
					</ul>

					<h3>October 10, 2018</h3>
					<ul>
						<li>Added the <code>SOASC Automatic</code> handler option. This will let the database determine
							if the SID tune should play the recording made on the 6581 (R2) or the 8580 (R5) SID chip.</li>
						<li>Added SID model flag boxes for the new <code>SOASC Automatic</code> handler option, so you
							can see what it chose. You can even click it to try out the other chip version.</li>
					</ul>

					<h3>October 8, 2018</h3>
					<ul>
						<li>The STIL box is now used to show randomly chosen tips when loading the site.</li>
						<li>Replaced the custom server folder of "$11 Music Compo 2018" with a
							<a href="//deepsid.chordian.net/?file=/$$11%20Music%20Compo%202018">public playlist</a> instead.</li>
					</ul>

					<h3>October 7, 2018</h3>
					<ul>
						<li>You can now publish a playlist so that everyone can enjoy it. Ownership is retained and
							only you can still edit the playlist. Public playlists appear further up together with the
							custom server folders. To publish a personal playlist, just right-click it in the root and
							select the new option on the context menu.</li>
						<li>Personal playlists as well as public playlists <i>that you manage</i> now have a star in the
							folder icon.</li>
						<li>Inside a public playlist folder, the path line in top now displays both its name as
							well as its creator.</li>
						<li>Plugged a security hole that made it possible to hack and create a playlist when not
							logged in.</li>
						<li>Personal playlists by other users should no longer appear in search results.</li>
						<li>Replaced the custom server folder of "HVSC Favorite Top 100" with a
							<a href="//deepsid.chordian.net/?file=/$HVSC%20Favorite%20Top%20100">public playlist</a> instead.</li>
					</ul>

					<h3>October 5, 2018</h3>
					<ul>
						<li>Fixed a bug that prevented the user account system from remembering your login.</li>
					</ul>

					<h3>October 3, 2018</h3>
					<ul>
						<li>Personal playlists are now available. Right-click a SID file to add it to a new or an
							existing playlist. Playlists are located in the root, named after the first SID file you
							added to it.</li>
						<li>Right-click a playlist folder in the root to rename or delete it.</li>
						<li>Right-click an entry inside a playlist to rename or remove it.</li>
					</ul>

					<h3>September 27, 2018</h3>
					<ul>
						<li>Empty table cells are now handled more gracefully in the composer profile.</li>
						<li>Sorting by year has now been replaced with options for oldest and newest instead.</li>
					</ul>

					<h3>September 26, 2018</h3>
					<ul>
						<li>References to other SID tunes in STIL comments are now intrinsic hyperlinks.</li>
						<li>In the composer profile, the list of groups now indicates if the composer was a founder.</li>
					</ul>

					<h3>September 25, 2018</h3>
					<ul>
						<li>Added a GB64 tab. This tab will show entries from
							<a href="http://www.gamebase64.com/">GameBase64</a> if a SID tune was used in one or more
							games. Clicking the title or the thumbnails will open the GB64 page in a new web
							browser tab.
						</li>
						<li>A notification number will be seen on the unselected GB64 tab if there are entries.</li>
					</ul>

					<h3>September 23, 2018</h3>
					<ul>
						<li>DeepSID now works on both HTTP and HTTPS.</li>
					</ul>

					<h3>September 21, 2018</h3>
					<ul>
						<li>During most of September, basic profiles have been added to everyone in the MUSICIANS folder.
							This means proper names, handles, CSDb info, and sometimes a thumbnail. Old "retro" thumbnails
							are mostly from GameBase64, but I have also procured a lot of hi-res images on my own.</li>
					</ul>

					<h3>September 4, 2018</h3>
					<ul>
						<li>In a composer profile, the number of games covered (released or previews) are now calculated
							using a new application column in the database. This information was previously entered
							manually.</li>
					</ul>

					<h3>September 2, 2018</h3>
					<ul>
						<li>Fixed a bug where Disqus sometimes loaded the root comments for a SID file.</li>
						<li>Fixed a bug where backing out of a folder would send you to the bottom of the parent folder.</li>
						<li>In addition to keys 1 to 4, you can now also use <code>q</code>, <code>w</code>,
						<code>e</code> and <code>r</code> to turn voices on and off while using the WebSid emulator.
						(This was added because Opera use keys 1 to 4 for something else.)</li>
						<li>Added a link icon to CSDb on the composer profile itself.</li>
					</ul>

					<h3>September 1, 2018</h3>
					<ul>
						<li>In a composer profile, the list of active years was changed to show a detailed graph
							instead.</li>
					</ul>

					<h3>August 31, 2018</h3>
					<ul>
						<li>If a commenting user in a CSDb comment thread has a composer profile in the database, a
							small folder icon is shown that you can click to see their HVSC folder.</li>
					</ul>

					<h3>August 30, 2018</h3>
					<ul>
						<li>You can now remove rating stars on desktop computers by holding down
							<code>Shift</code> while clicking.</li>
						<li>Profiles may now show a notable line for composers that have achieved something special.</li>
						<li>Shrunk most of the tabs and moved About, FAQ and Changes to the right side.</li>
						<li>A notification number will be seen on the unselected Disqus tab if there are comments.</li>
					</ul>

					<h3>August 29, 2018</h3>
					<ul>
						<li>Added link icons on CSDb pages, including one to see the composer profile again.</li>
						<li>Also added a close icon to the composer profile for returning to the previous CSDb page.</li>
					</ul>

					<h3>August 28, 2018</h3>
					<ul>
						<li>The CSDb tab now also show composer profiles. A composer folder will display a basic template
							with statistics about players used and active years, and not much else. If a profile has been
							added in the database, there may be a picture along with more precise information and a
							couple of tables.</li>
						<li>To start with there are profiles for 31 composers. Try
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/D/Daglish_Ben">Ben Daglish</a>
							for an example. More to come.</li>
						<li>DeepSID now uses HTTPS.</li>
					</ul>

					<h3>August 23, 2018</h3>
					<ul>
						<li>Added ID and permalink in the top right corner of CSDb release lists.</li>
					</ul>

					<h3>August 22, 2018</h3>
					<ul>
						<li>You can now load a specific entry in the CSDb tab with two new URL parameters. Specify either 
							<code>?csdbtype=sid</code> or <code>?csdbtype=release</code> along with <code>?csdbid=</code>
							set to the ID you want. You can figure out these two switches by clicking the [CSDb] link in
							a CSDb release page and study the URL. Here's an example that loads the
							<a href="//deepsid.chordian.net/?csdbtype=release&csdbid=153519">T.P.C.T.S.</a>
							music collection, ready to click on used files there.</li>
						<li>Improved how user names in user comments for CSDb release lists are obtained. Also reversed
							the order to comply with modern forum standards. See example in
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/L/Linus/Ashes_to_Ashes.sid">Ashes to Ashes</a>.</li>
						<li>Added a permalink to CSDb release entries, ready to be copied. It uses the new URL parameters.</li>
						<li>You can now click a COMMENT button on CSDb release lists and pages. This opens a form page at
							CSDb in a new web browser tab where you can write your comment.</li>
					</ul>

					<h3>August 21, 2018</h3>
					<ul>
						<li>The CSDb release list is now cached between entry clicks for increased speed.</li>
						<li>Improved how credits are obtained for CSDb release lists.</li>
						<li>Raw URL types in CSDb comments are now converted into clickable links.</li>
						</ul>

					<h3>August 20, 2018</h3>
					<ul>
						<li>All CSDb release types are now viewed internally.</li>
						<li>Release lists from the SID entry in CSDb can now show user comments too.</li>
						<li>CSDb comments now utilize line breaks.</li>
					</ul>

					<h3>August 19, 2018</h3>
					<ul>
						<li>You can now click and play SID files used in a CSDb <code>C64 Music Collection</code>
							while staying put there. For an example, try
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/T/Tel_Jeroen/Traumatic.sid">Traumatic</a> here,
							click the music collection, then click used SID files in the list there.</li>
						<li>Further improved how user names in CSDb comments are obtained.</li>
						<li>All CSDb comments now show the oldest post in top, which complies with modern forum
							standards.</li>
						<li>CSDb comments for trivia are now also shown. Try
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/J/Jammer/Shodan.sid">Shodan</a>
							for an example.</li>
						<li>CSDb comments for hidden parts are now also shown. Try
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/H/Holt_Hein/Rock_Sid_compo_version.sid">Rock Sid</a>
							for an example.</li>
						<li>BBCode shortcodes are now converted in CSDb comments.</li>
					</ul>

					<h3>August 18, 2018</h3>
					<ul>
						<li>You can now also view a <code>C64 Music Collection</code> CSDb type internally.</li>
						<li>Improved how user names in CSDb comments are obtained. Those <code>[ID:1234]</code> user
							names should now hopefully be a rare thing, although they're still not completely
							eliminated.</li>
						<li>CSDb comments for goofs are now also shown. Try
							<a href="//deepsid.chordian.net/?file=/MUSICIANS/J/Jammer/Dr_Analban.sid">Dr Analban</a>
							for an example.</li>
					</ul>

					<h3>August 17, 2018</h3>
					<ul>
						<li>The custom folders (except CGSC) now also have CSDb entries whenever available.</li>
						<li>Completely rebuilt the list of CSDb ID values in the database. This should get rid of
							most of the annoying music rip pages, instead showing a nice list of releases.</li>
						<li>Clicking CSDb thumbnails for a <code>C64 Music</code> type is now shown directly in the
							CSDb tab instead of opening it in CSDb itself in a new web browser tab.</li>
						<li>An external icon has been added in the right side of CSDb release lists. When this
							icon is present, you'll know that clicking the thumbnail will open a web browser tab.</li>
					</ul>

					<h3>August 16, 2018</h3>
					<ul>
						<li>Added a CSDb tab. This tab shows information from <a href="https://csdb.dk/">CSDb</a> using
							their <a href="https://csdb.dk/webservice/">web service</a>. A CSDb page may be either a
							list of releases or a dedicated music page with user comments. All links open in a new
							web browser tab.
						</li>
						<li>The new CSDb tab is now default instead of the Disqus tab.</li>
						<li>DeepSID now has a <a href="https://www.facebook.com/Chordian.net/">Facebook page</a> where
							changes (such as the above) are announced.</li>
					</ul>

					<h3>August 12, 2018</h3>
					<ul>
						<li>Added a main volume slider.</li>
					</ul>

					<h3>August 11, 2018</h3>
					<ul>
						<li>The address line in your web browser is now dynamically updated to reflect the folder, file,
							emulator and sub tune chosen, ready to be copied and used as a link. Reloading the page
							should also work with this.</li>
						<li>For now, using the browser history buttons doesn't actually perform the transition. This
							makes it easier to browse back fast then refresh the page to sort of activate the spot.</li>
						<li>Because of now updating the address line, the permalink was removed.</li>
					</ul>

					<h3>August 10, 2018</h3>
					<ul>
						<li>Added a STIL tab for a better overview. When this tab is clicked, the STIL box is hidden.</li>
						<li>The Disqus tab now uses the default scrollbar to ensure that the mouse wheel works properly.</li>
						<li>A timed out tune no longer auto-centers the playlist on the next tune below.</li>
					</ul>

					<h3>August 8, 2018</h3>
					<ul>
						<li>Added a custom folder for the <a href="//deepsid.chordian.net/?file=/Datastorm%202018">C64 music competition at Datastorm 2018</a>.</li>
					</ul>

					<h3>July 27, 2018</h3>
					<ul>
						<li>Hermit's emulator can now show VBI, CIA, or quickspeed values 2x, 3x, 4x, etc.</li>
					</ul>

					<h3>July 26, 2018</h3>
					<ul>
						<li>Fixed my modifications in Hermit's emulator to correctly handle fast forward in tunes
							that use CIA timer.</li>
					</ul>

					<h3>July 22, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed bugs with slow playing tunes and pitch bending in
							Virtuoso.</li>
					</ul>

					<h3>July 20, 2018</h3>
					<ul>
						<li>Disqus has been added as comment system for DeepSID. There's a discussion thread
							available for every single SID and MUS file. The number of comments can also be seen
							below star ratings in the playlist, but note that it takes a few minutes before Disqus
							updates this after a new comment has been added.</li>
						<li>A check box has been added for turning Disqus off. Disqus reloads a thread page for every
							tune and this can be distracting. Also, the reloading sometimes make the audio from the
							emulators stutter.</li>
					</ul>

					<h3>July 17, 2018</h3>
					<ul>
						<li>The WebSid emulator now also works correctly on iPhone and iPad.</li>
						<li>Added the <code>Download</code> handler option. This makes the browser download the
							tune when clicked. This is especially useful if an offline player has been associated
							with automatically playing it.</li>
						<li>If the SID chip model forced for Hermit's emulator is not specifically 8580, it now always
							defaults to 6581 regardless if the database indicates it's both or unknown.</li>
					</ul>

					<h3>July 16, 2018</h3>
					<ul>
						<li>Clicking or skipping tunes now show an animated spinner for the slower loading SOASC
							handlers.</li>
						<li>Fixed a bug where the CGSC font was shown for HVSC entries, and vice versa.</li>
					</ul>

					<h3>July 15, 2018</h3>
					<ul>
						<li>Improved the synchronization of ratings for SID files cloned across folders.</li>
						<li>The SOASC handlers now also work correctly on iPhone and iPad. Note that it may
							take a few seconds for a tune to start playing after touching a row on a
							mobile device.</li>
					</ul>

					<h3>July 14, 2018</h3>
					<ul>
						<li>Added the three missing SID files in <a href="//deepsid.chordian.net?file=$11%20Music%20Compo%202018">$11 Music Compo 2018</a> and synchronized it with HVSC.</li>
					</ul>

					<h3>July 13, 2018</h3>
					<ul>
						<li>Fixed a bug where the buttons were double-triggered on mobile devices.</li>
						<li>Sorting by the year now shows the oldest entry in top instead of the newest.</li>
						<li>Added the new sort option <code>Shuffle</code> for randomizing a playlist.</li>
					</ul>

					<h3>July 12, 2018</h3>
					<ul>
						<li>A tab can now be selected with the <code>?tab=</code> URL parameter.</li>
						<li>You can now also fast forward when using Hermit's emulator. This also marks the first
							modification of the emulator source code specifically for use with DeepSID.</li>
						<li>Holding down the fast forward button now also works on mobile devices.</li>
					</ul>

					<h3>July 11, 2018</h3>
					<ul>
						<li>You can now use the <code>s</code> hotkey to toggle the STIL box. Furthermore, the STIL
							box is automatically hidden if the display is tiny (or the browser window is resized
							to be small). This can be overridden with the new hotkey.</li>
						<li>Changed the pop-up hotkey from <code>F9</code> to <code>p</code> instead (Firefox
							is using it to toggle reader view).</li>
					</ul>

					<h3>July 10, 2018</h3>
					<ul>
						<li>Fixed a bug where the <code>&subtune=</code> URL parameter was too persistent.</li>
					</ul>

					<h3>July 9, 2018</h3>
					<ul>
						<li>Added a page with frequently asked questions. Click the FAQ tab above to see it.</li>
					</ul>

					<h3>July 8, 2018</h3>
					<ul>
						<li>Added this bigger page area for desktop web browsers, i.e. where you're reading this line right now.</li>
						<li>This list of changes has been imported from the
							<a href="//chordian.net/2018/05/12/deepsid/">blog post</a> and can now only be found here.</li>
						<li>After iOS patch 11.4, Hermit's emulator now works on iPhone and iPad.</li>
					</ul>

					<h3>July 7, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Added a cycle limit for problematic PSID tunes.</li>
					</ul>

					<h3>July 5, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Added digi volume boost for D418/6581 mode.</li>
						<li>Experimental: It is now possible to toggle voices with keys 1, 2 or 3 (or 4 for digi channels). Only works for the WebSid emulator. When you click a new tune, all voices are of course on again by default.</li>
						<li>Added aborting of SOASC requests. Strange clusters of red error rows should no longer occur.</li>
					</ul>

					<h3>July 4, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed a speed bug and improved the ADSR-bug handling.</li>
						<li>Searching for filenames or your star ratings now also return results for folders.</li>
						<li>The previous/next buttons are now disabled correctly at the top or bottom of a playlist.</li>
					</ul>

					<h3>July 3, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed a few bugs and added support for 2SID and 3SID tunes.</li>
					</ul>

					<h3>July 1, 2018</h3>
					<ul>
						<li>The <a href="https://www.hvsc.c64.org/">High Voltage SID Collection</a> has now been upgraded to the latest version #69.</li>
						<li>Upgraded the WebSid emulator. Fixed a problem running on older versions of Google Chrome.</li>
						<li>You can now check what's new in a HVSC version. So, <a href="//deepsid.chordian.net?search=69&type=new">what's new in HVSC #69?</a></li>
					</ul>

					<h3>June 30, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. Fixed a ring modulation bug, improved ADSR-bug handling, and added a new combined pulse-triangle waveform.</li>
					</ul>

					<h3>June 20, 2018</h3>
					<ul>
						<li>All SOASC handler requests now passes through a script on the SOASC server that returns a viable mirror site URL. This is more flexible, however, SID files will delay a little before playing.</li>
					</ul>

					<h3>June 18, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. The chord sound in the beginning of MUS tunes should be gone.</li>
						<li>You can now also vote and search for files in <a href="//deepsid.chordian.net?file=/Compute%27s%20Gazette%20SID%20Collection">Compute's Gazette SID Collection</a>.</li>
					</ul>

					<h3>June 17, 2018</h3>
					<ul>
						<li>Folders or emulator/handler options may now be disabled if the combination is not viable.</li>
						<li><a href="//deepsid.chordian.net?file=/Compute%27s%20Gazette%20SID%20Collection">Compute's Gazette SID Collection</a> has been added. It's only supported by WebSid and SOASC, <del>and you can only vote for folders</del>. The INFO box show free text and the STIL box sometimes contain lyrics.</li>
					</ul>

					<h3>June 10, 2018</h3>
					<ul>
						<li>The mouse cursor is now a pointer when hovering on the time bar in SOASC handler modes.</li>
						<li>The browser event for autocompleting the user name text box is now also handled.</li>
						<li>You can now search for a range of ratings, e.g. <code>3-</code> for all ratings of three stars or more.</li>
					</ul>

					<h3>June 9, 2018</h3>
					<ul>
						<li>Duplicate files and folders will now receive the same rating across HVSC and custom folders.</li>
						<li>You can now play most of the tunes in the <a href="//deepsid.chordian.net?file=HVSC%20Favorite%20Top%20100">HVSC Favorite Top 100</a> folder with SOASC.</li>
					</ul>

					<h3>June 4, 2018</h3>
					<ul>
						<li>Spaces between words in search queries should now work properly.</li>
					</ul>

					<h3>June 3, 2018</h3>
					<ul>
						<li>SOASC R2 + R4 (6581) and R5 (8580) have been added as options for real C64 recordings.</li>
						<li>When using SOASC as a SID handler, you can click the time bar to seek the position.</li>
						<li>The emulator toggle button has been replaced with a custom drop-down box in top left.</li>
						<li>If the SID tune playing was added in a HVSC version later than #49, it will be indicated.</li>
						<li>Permalink now also remembers the exact sub tune in addition to the SID handler.</li>
						<li>If a SID tune row doesn't work (e.g. file not found for SOASC) it will go red and skip it.</li>
						<li>The time bar now has a unique color depending on the SID handler chosen.</li>
					</ul>

					<h3>May 23, 2018</h3>
					<ul>
						<li>Added a custom folder for <a href="//deepsid.chordian.net?file=$11%20Music%20Compo%202018">$11 Music Compo 2018</a>. This time with images.</li>
						<li>Various bug fixes and improvements. Added <code>?subtune=</code> for use with <code>?file=</code>.</li>
						<li>You can now also hold down the top left key (below <code>Esc</code>) to play faster.</li>
						<li>Upgraded the WebSid emulator. Output volume is reduced to avoid overflows.</li>
					</ul>

					<h3>May 20, 2018</h3>
					<ul>
						<li>Custom folders are now possible. Added the first one for <a href="//deepsid.chordian.net?file=HVSC%20Favorite%20Top%20100">HVSC Favorite Top 100</a>.</li>
					</ul>

					<h3>May 19, 2018</h3>
					<ul>
						<li><del>You can now toggle the SID emulator with a corner button in the bottom search area.</del></li>
					</ul>

					<h3>May 18, 2018</h3>
					<ul>
						<li>The <code>?search=</code> and (optional) <code>&type=</code> parameters can now also be used to search.</li>
					</ul>

					<h3>May 17, 2018</h3>
					<ul>
						<li>Search has been added in the bottom along with a choice between six different types.</li>
					</ul>

					<h3>May 16, 2018</h3>
					<ul>
						<li>Added Hermit's jsSID emulator. <del>Click the "Hermit" link in the credits box to enable.</del></li>
						<li>The info box now shows a permalink for copying the URL of the SID currently playing.</li>
					</ul>

					<h3>May 15, 2018</h3>
					<ul>
						<li>Upgraded the WebSid emulator. It fixes a bug with flawed ring mod and sync.</li>
					</ul>

					<h3>May 14, 2018</h3>
					<ul>
						<li>The PAL/NTSC and 6581/8580 flag boxes can now be clicked to toggle emulator settings.</li>
					</ul>

					<h3>May 13, 2018</h3>
					<ul>
						<li>Upgraded the ScriptProcessor to v1.03c. This should fix issues in Google Chrome.</li>
						<li>Added a toggle button for looping a tune indefinitely.</li>
						<li>A <code>?file=</code> parameter can now go to a folder (case sensitive) or play a SID file.</li>
					</ul>

					<h3>May 12, 2018</h3>
					<ul>
						<li>First version released.</li>
					</ul>
				</div>

			</div>
		</div>

		<?php if (!isMobile()): ?>
			<script id="dsq-count-scr" src="//deepsid.disqus.com/count.js" async></script> <!-- DISQUS -->
		<?php endif ?>

	</body>

</html>