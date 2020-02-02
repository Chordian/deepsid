<?php
	if (false) die('DeepSID is being updated. Please return again in a few minutes.');

	require_once("php/class.account.php"); // Includes setup
	$user_id = $account->CheckLogin() ? $account->UserID() : 0;

	require_once("tracking.php"); // Also called every 5 minutes by 'main.js'

	function isMobile() {
		return isset($_GET['mobile'])
			? $_GET['mobile']
			: preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}

	function isLegacyWebSid() {
		return (isset($_GET['emulator']) && strtolower($_GET['emulator']) == 'legacy') ||
			(isset($_COOKIE['emulator']) && strtolower($_COOKIE['emulator']) == 'legacy');
	}
?>
<!DOCTYPE html>
<html lang="en-US" style="overflow:scroll-x;">

	<head>

		<meta charset="utf-8" />
		<meta name="viewport" content="width=450" />
		<meta name="description" content="A modern online SID player for the High Voltage and Compute's Gazette SID collections." /> <!-- Max 150 characters -->
		<meta name="keywords" content="c64,commodore 64,sid,6581,8580,hvsc,high voltage,cgsc,compute's gazette,visualizer,stil,websid,jssid,hermit,soasc" />
		<meta name="author" content="Jens-Christian Huus" />
		<title>DeepSID | Chordian.net</title>
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans%3A400%2C700%2C400italic%2C700italic%7CQuestrial%7CMontserrat&#038;subset=latin%2Clatin-ext" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Asap+Condensed" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Kanit" />
		<link rel="stylesheet" type="text/css" href="//olivi.chordian.net/wordpress/wp-content/themes/olivi/style.css" />
		<link rel="stylesheet" type="text/css" href="//chordian.net/deepsid/css/jquery.mCustomScrollbar.min.css" />
		<link rel="stylesheet" type="text/css" href="css/chartist.css" />
		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

		<?php if (isset($_GET['websiddebug'])): ?>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/scriptprocessor_player.js"></script>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/backend_tinyrsid.js"></script>
		<?php else: ?>
			<script type="text/javascript" src="js/handlers/scriptprocessor_player.js"></script>
			<?php if (isLegacyWebSid()): ?>
				<script type="text/javascript" src="js/handlers/backend_tinyrsid_legacy.js"></script>
			<?php else: ?>
				<script type="text/javascript" src="js/handlers/backend_tinyrsid.js"></script>
			<?php endif ?>
		<?php endif ?>

		<script type="text/javascript" src="js/handlers/jsSID-modified.js"></script>
		<script type="text/javascript" src="js/handlers/howler.core.js"></script>
		<script type="text/javascript" src="js/jquery.mCustomScrollbar.concat.min.js"></script>
		<script type="text/javascript" src="js/chartist.min.js"></script>
		<?php // @link https://github.com/madmurphy/cookies.js ?>
		<script type="text/javascript" src="js/cookies.min.js"></script>
		<script type="text/javascript" src="js/select.js"></script>
		<script type="text/javascript" src="js/player.js"></script>
		<script type="text/javascript" src="js/controls.js"></script>
		<script type="text/javascript" src="js/browser.js"></script>
		<?php if (isLegacyWebSid()): ?>
			<script type="text/javascript" src="js/scope_legacy.js"></script>
		<?php else : ?>
			<script type="text/javascript" src="js/scope.js"></script> <!-- <= JW's sid_tracer.js -->
		<?php endif ?>
		<script type="text/javascript" src="js/viz.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<script type="text/javascript">
			var colorTheme = 0;
			function setTheme() {
				colorTheme = localStorage.getItem("theme");
				if (colorTheme == 1)
					$("body").attr("data-theme", "dark");
			}
		</script>
		<link rel="icon" href="images/deepsid_icon_32x32.png" sizes="32x32" />
		<link rel="apple-touch-icon-precomposed" href="//chordian.net/images/avatar_c_olivi_128x128.png" />
		<meta name="msapplication-TileImage" content="//chordian.net/images/avatar_c_olivi_128x128.png" />
		<?php // @link https://developers.facebook.com/tools/debug/sharing/ and https://cards-dev.twitter.com/validator ?>
		<meta property="fb:app_id" content="285373918828438" />
		<meta property="og:title" content="<?php
			// Example: Rob Hubbard - Commando
			$file = isset($_GET['file']) ? $_GET['file'] : '';
			if (empty($file) || substr($file, 0, 2) == '/!' || substr($file, 0, 2) == '/$') {
				echo 'DeepSID';
			} else {
				if (substr($file, 0, 6) == '/DEMOS' || substr($file, 0, 6) == '/GAMES' || substr($file, 0, 10) == '/MUSICIANS')
					$file = '_High Voltage SID Collection'.$file;

				try {
					if ($_SERVER['HTTP_HOST'] == LOCALHOST)
						$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
					else
						$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->exec("SET NAMES UTF8");

					if (substr($file, -4) == '.sid' || substr($file, -4) == '.mus') {
						// It's a specific file
						$select = $db->query('SELECT name, author FROM hvsc_files WHERE fullname = "'.$file.'" LIMIT 1');
						$select->setFetchMode(PDO::FETCH_OBJ);
						if ($select->rowCount()) {
							// Rob Hubbard - Commando
							$row = $select->fetch();
							$author = $row->author;
							if (substr($author, -1) == ')')
								// If the handle is present in brackets, only show that
								$author = substr($author, strrpos($author, '(') + 1, -1);
							$title = $author.' - '.$row->name;
						} else {
							// Fallback: Commando.sid
							$array = explode('/', $file);
							$title = substr(end($array), 0);
						}
					} else {
						// It's a composer folder
						$select = $db->query('SELECT name FROM composers WHERE fullname = "'.substr($file, 0, -1).'" LIMIT 1');
						$select->setFetchMode(PDO::FETCH_OBJ);
						if ($select->rowCount()) {
							// Rob Hubbard
							$title = $select->fetch()->name;
						} else {
							// Fallback: Composer
							$title = 'Composer';
						}
					}
				} catch(PDOException $e) {
					// Use default then
					$title = 'DeepSID';
				}
				echo $title;
			}
		?>" />
		<meta property="og:type" content="website" />
		<meta property="og:image" content="http://chordian.net/deepsid/images/example<?php
			if (isset($_GET['file']) && (substr($_GET['file'], -4) == '.sid' || substr($_GET['file'], -4) == '.mus'))
				echo '_play';
		?>.png" />
		<meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" />
		<meta property="og:description" content="<?php
			// Example: /MUSICIANS/H/Hubbard_Rob/Commando.sid
			$hvsc = 'High Voltage SID Collection';
			$cgsc = "Compute's Gazette SID Collection";
			if (empty($_GET['file']))
				echo "A modern online SID player for the High Voltage and Compute's Gazette SID collections.";
			else if (strpos($_GET['file'], $hvsc))
				echo substr($_GET['file'], strpos($_GET['file'], $hvsc) + strlen($hvsc));
			else if (strpos($_GET['file'], $cgsc))
				echo substr($_GET['file'], strpos($_GET['file'], $cgsc) + strlen($cgsc));
			else
				echo $_GET['file'];
		?>" />
		<meta name="twitter:card" content="summary" />

	</head>

	<body class="entry-content" data-mobile="<?php echo isMobile(); ?>">
		<script type="text/javascript">setTheme();</script>

		<div id="dialog-cover"></div>
		<div id="click-to-play-cover">
			<div class="center">
				<div class="play"></div>
				<span class="text-below"><?php echo isMobile() ? 'Touch' : 'Click'; ?> to play</span>
			</div>
		</div>

		<div id="dialog-register" class="dialog-box">
			<div class="dialog-text"></div>
			<div class="dialog-buttons"><button class="dialog-button-yes">Yes</button><button class="dialog-button-no">No</button></div>
		</div>

		<div id="dialog-tags" class="dialog-box">
			<a href="tags.htm" target="_blank" style="position:absolute;top:20px;right:21px;font-size:14px;">Guidelines</a>
			<div class="dialog-text"></div>
			<select id="dialog-all-tags" name="all-tags" multiple></select>
			<div class="dialog-transfer">
				<button id="dialog-tags-left" class="dialog-to-left">
					<svg height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M30.83 32.67l-9.17-9.17 9.17-9.17-2.83-2.83-12 12 12 12z"/><path d="M0-.5h48v48h-48z" fill="none"/></svg>
				</button>
				<button id="dialog-tags-right" class="dialog-to-right" style="margin-left:2px;">
					<svg height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M17.17 32.92l9.17-9.17-9.17-9.17 2.83-2.83 12 12-12 12z"/><path d="M0-.25h48v48h-48z" fill="none"/></svg>
				</button>
			</div>
			<select id="dialog-song-tags" name="song-tags" size="6" multiple></select>
			<div class="dialog-new">
				<label for="new-tag">New tag:</label><br />
				<form onsubmit="return false;" autocomplete="off"><input type="text" name="new-tag" id="new-tag" maxlength="32" />
				<button id="dialog-tags-plus" class="disabled" style="float:right;">
					<svg height="16" style="enable-background:new 0 0 512 512;" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><polygon points="448,224 288,224 288,64 224,64 224,224 64,224 64,288 224,288 224,448 288,448 288,288 448,288 "/></svg>
				</button></form>
			</div>
			<div class="dialog-buttons" style="width:136px;"><button class="dialog-button-yes dialog-auto" style="float:left;margin:0;">OK</button><button class="dialog-button-no dialog-auto" style="float:right;margin:0;">Cancel</button></div>
		</div>

		<input id="upload-new" type="file" accept=".sid" style="display:none;" />
		<div id="dialog-upload-wiz2" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>
		<div id="dialog-upload-wiz3" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<label for="upload-profile">Connect <b>profile</b> page:</label>
			<select id="dropdown-upload-profile" name="upload-profile"></select>
			<label for="upload-csdb">Connect <b>CSDb</b> ID:</label><form onsubmit="return false;" autocomplete="off" style="float:right;"><span class="url">https://csdb.dk/release/?id<span style="margin:0 2px;">=</span></span><input type="text" name="upload-csdb" id="upload-csdb-id" onkeypress='return event.charCode >= 48 && event.charCode <= 57;' maxlength="6" value="0" /></form>
			<label id="label-lengths" for="upload-lengths" style="white-space:nowrap;">Define <b>lengths</b> of tunes:</label><br />
			<form id="form-lengths" onsubmit="return false;" autocomplete="off"><input type="text" name="upload-lengths" id="upload-lengths-list" onkeypress='return event.charCode >= 48 && event.charCode <= 57 || event.key == ":" || event.charCode == 32;' /></form>
			<p>If you don't know the <span id="span-lengths">lengths just leave them</span> as is for now. You can edit the file again later.</p>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>
		<div id="dialog-upload-wiz4" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<label for="upload-stil">Custom text for the <b>STIL</b> tabs:</label>
			<textarea id="upload-stil-text" name="upload-stil" maxlength="8192"></textarea>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Finish</button></div>
		</div>

		<iframe id="download" style="display:none;"></iframe>
		<input id="upload-test" type="file" accept=".sid" style="display:none;" multiple required />

		<div id="panel">
			<div id="top">
				<div id="logo" class="unselectable">D e e p S I D</div>
				<select id="dropdown-emulator" name="select-emulator" style="visibility:hidden;">
					<option value="websid">WebSid emulator</option>
					<option value="legacy">WebSid (Legacy)</option>
					<option value="jssid">Hermit's emulator</option>
					<option value="soasc_auto">SOASC Automatic</option>
					<option value="soasc_r2">SOASC 6581 R2</option>
					<option value="soasc_r4">SOASC 6581 R4</option>
					<option value="soasc_r5">SOASC 8580 R5</option>
					<option value="download">Download SID file</option>
				</select>

				<div id="theme-selector" title="Click here to toggle the color theme"><div></div></div>

				<?php if ($user_id) : ?>
					<div id="logged-in">
						<span id="logged-username"><?php echo $account->UserName(); ?></span>
						<button id="logout" title="Log out">
							<svg height="14" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M704 1440q0 4 1 20t.5 26.5-3 23.5-10 19.5-20.5 6.5h-320q-119 0-203.5-84.5t-84.5-203.5v-704q0-119 84.5-203.5t203.5-84.5h320q13 0 22.5 9.5t9.5 22.5q0 4 1 20t.5 26.5-3 23.5-10 19.5-20.5 6.5h-320q-66 0-113 47t-47 113v704q0 66 47 113t113 47h312l11.5 1 11.5 3 8 5.5 7 9 2 13.5zm928-544q0 26-19 45l-544 544q-19 19-45 19t-45-19-19-45v-288h-448q-26 0-45-19t-19-45v-384q0-26 19-45t45-19h448v-288q0-26 19-45t45-19 45 19l544 544q19 19 19 45z"/></svg>
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
									<svg height="14" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1312 896q0 26-19 45l-544 544q-19 19-45 19t-45-19-19-45v-288h-448q-26 0-45-19t-19-45v-384q0-26 19-45t45-19h448v-288q0-26 19-45t45-19 45 19l544 544q19 19 19 45zm352-352v704q0 119-84.5 203.5t-203.5 84.5h-320q-13 0-22.5-9.5t-9.5-22.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q66 0 113-47t47-113v-704q0-66-47-113t-113-47h-312l-11.5-1-11.5-3-8-5.5-7-9-2-13.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q119 0 203.5 84.5t84.5 203.5z"/></svg>
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
			<div id="sundry-tabs">
				<div class="tab unselectable selected" data-topic="stil" id="stab-stil">Tips</div>
				<div class="tab unselectable" data-topic="tags" id="stab-tags">Tags</div>
				<div class="tab unselectable" data-topic="osc" id="stab-osc">Scope</div>
				<div id="sundry-ctrls"></div>
			</div>
			<div id="sundry">
				<div id="stopic-stil" class="stopic"></div>
				<div id="stopic-tags" class="stopic" style="display:none;"></div>
				<div id="stopic-osc" class="stopic" style="display:none;"></div>
				<a id="redirect-back" class="redirect continue" href="#" style="display:none"></a>
			</div>
			<div id="slider">
				<div id="slider-button" style="display:none;">
					<button id="get-all-tags" class="rect">Folder</button>
				</div>
			</div>

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
				<div id="time"><span id="time-current">0:00</span> <div id="time-bar"><div></div></div> <span id="time-length" style="position:relative;">0:00</span></div>
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
				<img id="loading" class="loading-spinner" src="images/loading.svg" style="display:none;" alt="" />
				<div id="search">
					<select id="dropdown-search" name="search-type">
						<option value="#all#">All</option>
						<option value="fullname">Filename</option>
						<option value="author">Author</option>
						<option value="copyright">Copyright</option>
						<option value="player">Player</option>
						<option value="location">Location</option>
						<option value="tag">Tags</option>
						<option value="stil">STIL</option>
						<option value="rating">Rating</option>
						<option value="country">Country</option>
						<option value="new">Version</option>
						<option value="gb64">Game</option>
					</select>
					<form onsubmit="return false;" autocomplete="off"><input type="text" name="search-box" id="search-box" maxlength="64" /></form>
					<div id="search-here-container">
						<input type="checkbox" id="search-here" name="shtoggle" class="unselectable" unchecked />
						<label for="search-here" class="unselectable">Here</label>
					</div>
					<button id="search-button" class="medium disabled" disabled="disabled">Search</button>
				</div>
			</div>
		</div>

		<?php if (!isMobile()): ?>

			<div id="dexter">
				<div id="sites">
					<div style="float:left;margin-left:1px;text-align:left;">
						<a href="<?php echo HOST; ?>">Home</a>
							<span>&#9642</span>
						<a id="recommended" href="#">Recommended</a>
							<span>&#9642</span>
						<a id="players" href="#">Players</a>
							<span>&#9642</span>
						<a id="forum" href="#">Forum</a>
					</div>

					<span class="soasc-status">
						SOASC Status <div id="soasc-status-led"></div><span id="soasc-status-word">?</span>
					</span>

					<a href="https://olivi.chordian.net/2018/05/12/deepsid/">Blog Post</a>
						<span>&#9642</span>
					<a href="https://csdb.dk/forums/?roomid=14&topicid=129712">CSDb</a>
						<span>&#9642</span>
					<!--<a href="https://www.lemon64.com/forum/viewtopic.php?t=68056">Lemon64</a>
						<span>&#9642</span>-->
					<a href="https://twitter.com/chordian">Twitter</a>
						<span>&#9642</span>
					<a href="https://www.facebook.com/groups/deepsid/">Facebook</a>
						<span>&#9642</span>
					<a href="https://github.com/Chordian/deepsid">GitHub</a>
					</div>
				<div id="tabs">
					<div class="tab unselectable" data-topic="profile" id="tab-profile">Profile</div>
					<div class="tab unselectable" data-topic="csdb" id="tab-csdb">CSDb<div id="note-csdb" class="notification csdbcolor"></div></div>
					<div class="tab unselectable" data-topic="gb64" id="tab-gb64">GB64<div id="note-gb64" class="notification gb64color"></div></div>
					<div class="tab unselectable" data-topic="remix" id="tab-remix">Remix<div id="note-remix" class="notification remixcolor"></div></div>
					<div class="tab unselectable" data-topic="player" id="tab-player">Player<div id="note-player" class="notification playercolor"></div></div>
					<div class="tab unselectable" data-topic="stil" id="tab-stil">STIL</div>
					<div class="tab unselectable" data-topic="visuals" id="tab-visuals">Visuals</div>
					<div class="tab unselectable" data-topic="disqus" id="tab-disqus">Disqus<div id="note-disqus" class="notification"></div></div>
					<?php if (isset($_GET['debug'])) : ?>				
						<div class="tab unselectable" data-topic="debug" id="tab-debug" style="color:#f66;">Debug</div>
					<?php endif ?>
					<div class="tab right unselectable" data-topic="settings" id="tab-settings" style="width:26px;">
						<svg height="12px" width="12px" style="position:relative;top:-5px;" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill="none" fill-rule="evenodd" stroke="none" stroke-width="1"><g class="g2" transform="translate(-464.000000, -380.000000)"><g transform="translate(464.000000, 380.000000)"><path d="M17.4,11 C17.4,10.7 17.5,10.4 17.5,10 C17.5,9.6 17.5,9.3 17.4,9 L19.5,7.3 C19.7,7.1 19.7,6.9 19.6,6.7 L17.6,3.2 C17.5,3.1 17.3,3 17,3.1 L14.5,4.1 C14,3.7 13.4,3.4 12.8,3.1 L12.4,0.5 C12.5,0.2 12.2,0 12,0 L8,0 C7.8,0 7.5,0.2 7.5,0.4 L7.1,3.1 C6.5,3.3 6,3.7 5.4,4.1 L3,3.1 C2.7,3 2.5,3.1 2.3,3.3 L0.3,6.8 C0.2,6.9 0.3,7.2 0.5,7.4 L2.6,9 C2.6,9.3 2.5,9.6 2.5,10 C2.5,10.4 2.5,10.7 2.6,11 L0.5,12.7 C0.3,12.9 0.3,13.1 0.4,13.3 L2.4,16.8 C2.5,16.9 2.7,17 3,16.9 L5.5,15.9 C6,16.3 6.6,16.6 7.2,16.9 L7.6,19.5 C7.6,19.7 7.8,19.9 8.1,19.9 L12.1,19.9 C12.3,19.9 12.6,19.7 12.6,19.5 L13,16.9 C13.6,16.6 14.2,16.3 14.7,15.9 L17.2,16.9 C17.4,17 17.7,16.9 17.8,16.7 L19.8,13.2 C19.9,13 19.9,12.7 19.7,12.6 L17.4,11 L17.4,11 Z M10,13.5 C8.1,13.5 6.5,11.9 6.5,10 C6.5,8.1 8.1,6.5 10,6.5 C11.9,6.5 13.5,8.1 13.5,10 C13.5,11.9 11.9,13.5 10,13.5 L10,13.5 Z"/></g></g></g></svg>
					</div>
					<div class="tab right unselectable" data-topic="changes" id="tab-changes" style="width:80px;">Changes</div>
					<div class="tab right unselectable" data-topic="faq" id="tab-faq">FAQ</div>
					<div class="tab right unselectable" data-topic="about" id="tab-about">About</div>
				</div>
				<div id="sticky-csdb"><h2 style="margin-top:0;">CSDb</h2></div>
				<div id="sticky-visuals"><h2 style="margin-top:0;">Visuals</h2>
					<div class="visuals-buttons">
						<button class="icon-piano button-off" data-visual="piano">Piano</button>
						<button class="icon-graph button-on" data-visual="graph">Graph</button>
						<button class="icon-memory button-on" data-visual="memory">Memo</button>
					</div>
					<img class="waveform-colors" src="images/waveform_colors.png" alt="Waveform Colors" />
					<div id="sticky-right-buttons">
						<span id="memory-lc">
							<label for="memory-lc-toggle" class="unselectable" style="margin-right:1px;">Lower case C64 font</label>
							<button id="memory-lc-toggle" class="button-edit button-toggle button-on">On</button>
						</span>
					</div>
				</div>
				<div id="page">

					<div id="topic-visuals" class="topic ext" style="display:none;">
						<div id="visuals-piano" class="visuals" style="display:none;">
							<div class="edit" style="height:42px;width:683px;">
								<label class="unselectable" style="margin-right:2px;">Emulator</label>
								<button class="button-edit button-radio button-off viz-emu viz-websid viz-legacy" data-group="viz-emu" data-emu="websid">WebSid</button>
								<button class="button-edit button-radio button-off viz-emu viz-jssid" data-group="viz-emu" data-emu="jssid">Hermit</button>
								<span class="viz-warning viz-msg-emu">You need to enable one of these emulators</span>
								<span class="viz-warning viz-msg-buffer">Decrease this if too slow <img src="images/composer_arrowright.svg" style="position:relative;top:4px;height:18px;" alt="" /></span>
								<div class="viz-buffer">
									<label for="dropdown-piano-buffer" class="unselectable">Buffer size</label>
									<select id="dropdown-piano-buffer" class="dropdown-buffer">
										<!--<option value="256">256</option>
										<option value="512">512</option>-->
										<option value="1024">1024</option>
										<option value="2048">2048</option>
										<option value="4096">4096</option>
										<option value="8192">8192</option>
										<option value="16384" selected="selected">16384</option>
									</select>
								</div>
							</div>
							<div class="edit" style="height:42px;width:683px;">
								<button id="piano-gate" class="button-edit button-toggle button-on">On</button>
								<label for="piano-gate" class="unselectable">Gate bit</label>
								<button id="piano-noise" class="button-edit button-toggle button-on">On</button>
								<label for="piano-noise" class="unselectable">Noise waveform</label>
								<button id="piano-slow" class="button-edit button-toggle button-off">Off</button>
								<label for="piano-slow" class="unselectable">Slow speed</label>
								<span id="piano-combine-area" style="float:right;">
									<label for="piano-combine" class="unselectable" style="margin-right:1px;">Combine into top piano</label>
									<button id="piano-combine" class="button-edit button-toggle button-off">Off</button>
									<span>2SID</span>
								</span>
							</div>
							<?php require_once("php/piano.php"); ?>
							<h3 style="margin-top:16px;">A few words...</h3>
							<p>
								<b>NEW:</b> 2SID and 3SID tunes are now supported. Each keyboard will automatically combine
								to host an entire chip (i.e. 3 voices). The square voice buttons will toggle entire SID
								chips ON or OFF when playing these types of tunes.
							</p>
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

						<div id="visuals-graph" class="visuals" style="display:none;">
							<div class="edit" style="height:42px;width:683px;">
								<label class="unselectable" style="margin-right:2px;">Emulator</label>
								<button class="button-edit button-radio button-off viz-emu viz-websid viz-legacy" data-group="viz-emu" data-emu="websid">WebSid</button>
								<button class="button-edit button-radio button-off viz-emu viz-jssid" data-group="viz-emu" data-emu="jssid">Hermit</button>
								<span class="viz-warning viz-msg-emu">You need to enable one of these emulators</span>
								<span class="viz-warning viz-msg-buffer">Decrease this if too slow <img src="images/composer_arrowright.svg" style="position:relative;top:4px;height:18px;" alt="" /></span>
								<div class="viz-buffer">
									<label for="dropdown-graph-buffer" class="unselectable">Buffer size</label>
									<select id="dropdown-graph-buffer" class="dropdown-buffer">
										<!--<option value="256">256</option>
										<option value="512">512</option>-->
										<option value="1024">1024</option>
										<option value="2048">2048</option>
										<option value="4096">4096</option>
										<option value="8192">8192</option>
										<option value="16384" selected="selected">16384</option>
									</select>
								</div>
							</div>
							<div class="edit" style="height:42px;width:683px;">
								<button id="graph-pw" class="button-edit button-toggle button-off">Off</button>
								<label for="graph-pw" class="unselectable">Pulse coat</label>
								<button id="graph-mods" class="button-edit button-toggle button-on">On</button>
								<label for="graph-mods" class="unselectable">Modulations</label>
								<span style="float:right;">
									<label class="unselectable" style="margin-right:2px;">Layout</label>
									<button class="button-edit button-icon button-left button-on viz-layout viz-cols" data-group="viz-layout"><img src="images/visuals_graph_bold.svg" alt="" /></button><button
										class="button-edit button-icon button-right button-off viz-layout viz-rows" data-group="viz-layout"><img src="images/visuals_graph_bold.svg" style="transform:rotate(90deg);" alt="" /></button>
								</span>
							</div>
							<div id="graph">
								<div id="graph0" class="graph-area"></div>
								<div id="graph1" class="graph-area"></div>
								<div id="graph2" class="graph-area"></div>
								<div id="graph3" class="graph-area"></div>
								<div id="graph4" class="graph-area"></div>
								<div id="graph5" class="graph-area"></div>
								<div id="graph6" class="graph-area"></div>
								<div id="graph7" class="graph-area"></div>
								<div id="graph8" class="graph-area"></div>
							</div>
						</div>

						<div id="visuals-memory" class="visuals" style="display:none;">
							<div class="edit sid-info sid-info-left">
								<div class="label">Player size</div><span class="si si-size"></span><br />
								<div class="label">Load address</div><span class="si si-load"></span><br />
								<div class="label">Init address</div><span class="si si-init"></span><br />
								<div class="label">Play address</div><span class="si si-play"></span><br />
								<div class="label">Default subtune</div><span class="si si-subtune"></span>
							</div>
							<div class="edit sid-info sid-info-right">
								<div class="label">SID file type</div><span class="si si-type"></span><br />
								<div class="label">Encoding</div><span class="si si-enc"></span><br />
								<div class="label">Pace (Speed)</div><span class="si si-pace"></span><br />
								<div class="label">SID model</div><span class="si si-model"></span><br />
								<div class="label">SID addresses</div><span class="si si-sid"></span>
							</div>
							<div class="monitor">
								<table id="block-memory">
									<tr>
										<td class="block-info">
											<b>Zero Page</b><br />$0000-$00FF</td>
										<td class="block-data block-zp"></td>
									</tr>
									<tr>
										<td class="block-info">
											<b>Player Block</b><br />
											<span id="player-addr"></span>
											<button class="player-to-left disabled">
												<svg height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M30.83 32.67l-9.17-9.17 9.17-9.17-2.83-2.83-12 12 12 12z"/><path d="M0-.5h48v48h-48z" fill="none"/></svg>
											</button>
											<button class="player-to-right" style="margin-left:4px;">
												<svg height="24" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M17.17 32.92l9.17-9.17-9.17-9.17 2.83-2.83 12 12-12 12z"/><path d="M0-.25h48v48h-48z" fill="none"/></svg>
											</button>
										</td>
										<td class="block-data block-player"></td>
									</tr>
								</table>
							</div>
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
							<?php if ($_SERVER['HTTP_HOST'] != LOCALHOST) : // To avoid seeing the CSP error ?>
								(function() { // DON'T EDIT BELOW THIS LINE
									var d = document, s = d.createElement('script');
									s.src = 'https://deepsid.disqus.com/embed.js';
									s.setAttribute('data-timestamp', +new Date());
									(d.head || d.body).appendChild(s);
								})();
							<?php endif ?>
						</script>
						<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
						<!-- DISQUS END -->
					</div>

					<div id="topic-profile" class="topic ext" style="display:none;"></div>

					<div id="topic-csdb" class="topic ext" style="display:none;">
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

					<div id="topic-player" class="topic ext" style="display:none;">
						<h2>Player</h2>
						<p>If available, this tab will show information about the editor/player that made the song.</p>
					</div>

					<div id="topic-stil" class="topic" style="display:none;">
						<h2>STIL / Lyrics</h2>
						<p>This tab will sometimes show one of two things depending on the SID collection you're browsing.
							It will display the same contents as the first tab in the box just above the player controls.
						</p>

						<h3>STIL</h3>
						<p>	
							If you're clicking a song in the <b>High Voltage SID Collection</b> that has a STIL entry, this will be
							shown here as well as in the box. Any sub tunes mentioned have green buttons that you can click.
						</p>
						<p>
							STIL stands for <i>SID Tune Information List</i> and contains information beyond the standard
							<b>TITLE</b>, <b>AUTHOR</b>, and <b>RELEASED</b> fields. This includes cover information,
							interesting facts, useless trivia, comments by the composers themselves, etc. The STIL, though,
							is limited to factual data and does not try to provide an encyclopedia about every original artist.
						</p>
						<p>
							For more information about STIL, please refer to <a href="https://www.hvsc.c64.org/download/C64Music/DOCUMENTS/STIL.faq">this FAQ</a>.
						</p>

						<h3>Lyrics</h3>
						<p>	
							If you're clicking a song in <b>Compute's Gazette SID Collection</b> that has lyrics, this will
							be shown here and in the box.
						</p>
						<p>
							Technically, lyrics are always added in a separate WDS file that accompanies the MUS file that
							contains the actual music data. However, not all MUS files have a WDS file. Roughly one third
							of the MUS files in the collection have lyrics.
						</p>
					</div>

					<div id="topic-remix" class="topic ext" style="display:none;">
						<h2>Remix64</h2>
						<p>If you click a SID file that has been remixed into modern forms, this tab will show
							those entries from Remix64.</p>
						<p>
							<a href="http://www.remix64.com/" target="_blank">Remix64</a> is a portal to the unified world
								of Commodore 64 and Amiga music remixing, containing news, reviews, charts and chat. Remixes
								can be uploaded and rated here. It's maintained by Markus Klein, also known as 
								<a href="//deepsid.chordian.net/?file=/MUSICIANS/L/LMan/">LMan</a>.
						</p>
						<br />
						<p>
							<i>This does not work in
							<a href="http://www.c64music.co.uk/" target="_blank">Compute's Gazette SID Collection</a>.</i>
						</p>
					</div>

					<?php if (isset($_GET['debug'])) : ?>				
						<div id="topic-debug" class="topic ext" style="display:none;">
							<h2>Debug</h2>
							<table></table>
						</div>
					<?php endif ?>

					<div id="topic-settings" class="topic ext" style="display:none;">
						<h2>Settings</h2>
						<?php if (!$user_id) : ?>
							<i>If you register and log in, you can adjust your settings here.</i>
						<?php else : ?>
							<p>Changing a setting here will save it immediately.</p>
							<div class="edit">

								<h3>Properties</h3>

								<label for="old-password" class="unselectable" style="margin:0 2px 0 0;">Change old </label>
								<input type="password" name="old-password" id="old-password" maxlength="32" />
								<label for="new-password" class="unselectable" style="margin: 0 2px;">password to</label>
								<input type="password" name="new-password" id="new-password" maxlength="32" />
								<label for="new-password" class="unselectable" style="margin: 0 6px 0 2px;">instead</label>
								<button id="new-password-button" class="medium disabled">Go</button>
								<b id="new-password-msg" style="display:none;font-size:12px;margin-left:6px;"></b>

								<div class="space"></div>

								<button id="export" class="medium">Export</button>
								<label for="export" class="unselectable">Click this button to export your ratings to
									a <b>CSV file</b> that can be loaded into e.g. Excel</label>

								<div class="space splitline"></div>

								<h3>Buffer size</h3>
								<p>Setting the buffer size affects WebSid or Hermit's emulator. If you like viewing the
									<b>Visuals</b> tab, decrease the value towards 1024 for smoother
									updating. If the playback is stuttering, increase it until it doesn't anymore.</p>
								<p style="margin-top:-10px;">You need to leave it at 16384 for the <b>Scope</b> tab to work.</p>

								<select id="dropdown-settings-buffer" class="dropdown-buffer">
									<!--<option value="256">256</option>
									<option value="512">512</option>-->
									<option value="1024">1024</option>
									<option value="2048">2048</option>
									<option value="4096">4096</option>
									<option value="8192">8192</option>
									<option value="16384" selected="selected">16384</option>
								</select>
								<label for="dropdown-settings-buffer" class="unselectable">Buffer size</label>

								<div class="space splitline"></div>

								<h3>Defaults</h3>

								<button id="setting-first-subtune" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-first-subtune" class="unselectable">Always start at the <b>first sub tune</b> in a song instead of the default set by HVSC</label>

								<div class="space splitline"></div>

								<h3>Auto-progress</h3>
								<p>Determine what will happen when a tune has finished playing.</p>

								<button id="setting-skip-tune" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-skip-tune" class="unselectable">Auto-progress should proceed to the <b>next song</b> instead of the next sub tune</label>

								<div class="space"></div>

								<button id="setting-mark-tune" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-mark-tune" class="unselectable">Auto-progress should <b>select and center</b> the next song as it proceeds to it</label>

								<div class="space"></div>

								<button id="setting-skip-bad" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-skip-bad" class="unselectable">Auto-progress should automatically skip the songs I have rated <b>two stars or less</b></label>

								<!--div class="space"></div>

								<button id="setting-skip-long" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-skip-long" class="unselectable">Auto-progress should leave for the next song if playing for <b>more than ten minutes</b></label>-->

								<div class="space"></div>

								<button id="setting-skip-short" class="button-edit button-toggle button-off">Off</button>
								<label for="setting-skip-short" class="unselectable">Auto-progress should automatically skip songs and sub tunes that lasts <b>less than ten seconds</b></label>
							</div>
						<?php endif ?>
					</div>

					<div id="topic-about" class="topic" style="display:none;">
						<h2>About</h2>
						<p>
							DeepSID is an online SID player that can play music originally composed for the
							<a href="https://en.wikipedia.org/wiki/Commodore_64">Commodore 64</a>, a home computer
							that was very popular back in the 80's and 90's. This computer had an amazing sound chip
							called <a href="https://en.wikipedia.org/wiki/MOS_Technology_SID">SID</a>.
						</p>

						<img src="images/6581.jpg" alt="6581" />

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
							High Voltage SID Collection #72<br />
							<a href="https://www.hvsc.c64.org/">https://www.hvsc.c64.org/</a>
						</p>
						<p>
							Compute's Gazette SID Collection #137<br />
							<a href="http://www.c64music.co.uk/">http://www.c64music.co.uk/</a>
						</p>
						<p>
							Stone Oakvalley's Authentic SID Collection<br />
							<a href="http://www.6581-8580.com/">http://www.6581-8580.com/</a>
						</p>

						<h3>Remixes of SID tunes</h3>
						<p>
							Remix64 API by Markus Klein (<a href="https://markus-klein-artwork.de/music/">LMan</a>)<br />
							<a href="https://www.remix64.com/">https://www.remix64.com/</a>
						</p>
						<p>
							Hosting by Jan Lund Thomsen (QED)<br />
							<a href="http://remix.kwed.org/">http://remix.kwed.org/</a>
						</p>


						<h3>Composer profile images</h3>
						<p>
							The images for composer profiles come from all over the internet. I have tried
							to be fair and not use images that the composer did not already have available on a personal
							web site, social media, interview, or another public place.
						</p>
						<ul>
							<li>Most are publically available profile images from Facebook or LinkedIn.</li>
							<li>A lot of older retro images (typically lo-res) are from the musicians photos download at <a href="http://www.gamebase64.com/downloads.php">GameBase64</a>.</li>
							<li>Some were originally taken by Andreas Wallström (<a href="http://www.c64.com/">C64.com</a>).</li>
							<li>A few were taken from the <a href="http://www.vgmpf.com/Wiki/index.php">Video Game Music Preservation Foundation</a> wikipedia.</li>
							<li>Some from the <a href="https://8bitlegends.com/">8BitLegends.com</a> web site.</li>
							<li>And several other places I can't remember anymore.</li>
						</ul>
						<p>
							If you feel you should be credited, let me know and I will add you to this section. Also, if
							you don't like an image of you here, just let me know and I will of course remove it. You are
							also welcome to send me a replacement image.
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
							<li>Inside your playlist, you can right-click SID files to rename or
								remove them, or set a different default sub tune.</li>
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
						<table style="font-size:14px;">
							<tr>
								<th style="width:150px;">Handler</th><th>Description</th>
							</tr>
							<tr>
								<td>WebSid emulator</td><td>This is the JS emulator originally used by the
								<a href="http://www.wothke.ch/tinyrsid/index.php">Tiny'R'Sid</a> web site. It emulates standard
								SID as well as digi tunes, 2SID and 3SID, and even MUS files in Compute's Gazette SID
								Collection. This is the best quality version of WebSid with cycle-by-cycle processing.</td>
							</tr>
							<tr>
								<td>WebSid (Legacy)</td><td>This is an older version of WebSid from before it was
								overhauled to cycle-by-cycle processing. It's faster and has clearer digi sound, but it
								doesn't emulate quite as faithfully. If your computer has trouble keeping up with the
								above WebSid version, try this one instead.</td>
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
						<p>In the piano view, you can also click the green number buttons.</p>

						<h3>Any other hotkeys worth knowing about?</h3>
						<p>
							Hit <code>p</code> in desktop web browsers to pop up a tiny version of the player.
						</p>
						<p>
							Hit <code>s</code> to toggle minimizing or restoring the sundry box.
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

						<h3>Why can't I see the load/end addresses and size of the SID tune?</h3>
						<p>
							See that blue bar just below the top box with the title, author and copyright lines? It's the C64
							memory, from $0000 to $FFFF. The dark blue blob that appears there is the SID tune as it takes up
							space. If you hover your mouse pointer on it, the tooltip will tell you the memory boundaries in
							hex and the size in bytes.
						</p>
						<p>
							If you want a box with this information and more, click the <b>Visuals</b> tab and then the
							<b>MEMO</b> button. And nifty shortcut to this is to just click the dark blue blob in that blue
							bar mentioned before.
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
								<td>emulator</td><td>Set to <code>websid</code>, <code>legacy</code>, <code>jssid</code>,
									<code>soasc_auto</code>, <code>soasc_r2</code>,<code>soasc_r4</code>, <code>soasc_r5</code>
									or <code>download</code> to temporarily override the SID handler (reloading the web site
									returns to the previous SID handler)</td>
							</tr>
							<tr>
								<td>search</td><td>A search query (just like when typed in the bottom)</td>
							</tr>
							<tr>
								<td>type</td><td>Search type; <code>fullname</code> (title), <code>author</code>,
									<code>copyright</code>, <code>player</code>, <code>stil</code>, <code>rating</code>,
									<code>country</code>, <code>new</code> (HVSC or CGSC version number) or
									<code>gb64</code> (game)</td>
							</tr>
							<tr>
								<td>tab</td><td>Set to <code>csdb</code>, <code>gb64</code>, <code>remix</code>, <code>stil</code>, <code>visuals</code>,
									<code>disqus</code>, <code>about</code>, <code>faq</code>, <code>changes</code> or <code>settings</code>
									(the gear icon) to select that page tab</td>
							</tr>
							<tr>
								<td>sundry</td><td>Set to <code>stil</code> (or <code>lyrics</code>) or <code>scope</code> (or <code>osc</code>) to select that sundry box tab</td>
							</tr>
							<tr>
								<td>player</td><td>Set to the ID of the player/editor page. Use a permalink from one to get it right.</td>
							</tr>
							<tr>
								<td>csdbtype</td><td>Set to <code>sid</code> or <code>release</code> to show a CSDb entry;
									must be used together with <code>csdbid</code></td>
							</tr>
							<tr>
								<td>csdbid</td><td>Set to an ID value to show a CSDb entry;
									must be used together with <code>csdbtype</code></td>
							</tr>
							<tr>
								<td>mobile</td><td>Set it to <code>0</code> on a mobile device to use desktop view, or <code>1</code> on a desktop computer to use mobile view</td>
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

						<h3>February 2, 2020</h3>
						<ul>
							<li>DeepSID's copy of <a href="http://www.c64music.co.uk/">Compute's Gazette SID Collection</a> has now been upgraded to version 1.37.</li>
						</ul>

						<h3>February 1, 2020</h3>
						<ul>
							<li>All brand logos now have a counterpart in the dark color theme.</li>
						</ul>

						<h3>January 29, 2020</h3>
						<ul>
							<li>Tags can now have longer names (upped from 80 to 100 pixels).</li>
						</ul>

						<h3>January 19, 2020</h3>
						<ul>
							<li>You can now also search for a start location. Choose <code>Location</code> and then type the address.
								Use raw numbers for decimal or prepend hexadecimal numbers with either <code>$</code> or <code>0x</code>.
								(For example, <code>16384</code> or <code>0x4000</code>.)</li>
							<li>Fixed another UTF-8 encoding bug in download filenames in some CSDb pages.</li>
							<li>Fixed not enabling the <code>Download SID file</code> handler option after leaving the exotic folders.</li>
							<li>To make sure you can always spot it, the <code>Remix64</code> tag is now always the very first tag in line.</li>
						</ul>

						<h3>January 18, 2020</h3>
						<ul>
							<li>The SH folder now displays a notification count for the Disqus tab too, if there are comments.</li>
							<li>Fixed a bug where the Disqus comment counts below stars were no longer seen after sorting differently.</li>
						</ul>

						<h3>January 15, 2020</h3>
						<ul>
							<li>Fixed a bug preventing the in-site context menu for some of the bottom SID rows from appearing.</li>
							<li>The author field for a SH row now turns into a HVSC link if there's a profile that points to it.</li>
							<li>A file uploaded to the SH folder will now have its filename adapted to conform with the HVSC naming
								standard, i.e. mostly capitalized words and underscore characters instead of spaces.</li>
						</ul>

						<h3>January 14, 2020</h3>
						<ul>
							<li>Fixed a database bug when reading a folder added after the introduction of the SH folder.</li>
						</ul>

						<h3>January 13, 2020</h3>
						<ul>
							<li>Upgraded the WebSid (HQ) emulator. An issue with the BASIC loader has been fixed.</li>
							<li>Songs with no profile set in the SH folder now show the default folder profile.</li>
						</ul>

						<h3>January 12, 2020</h3>
						<ul>
							<li>The new major folder <a href="//deepsid.chordian.net?file=/SID Happens/">SID Happens</a> has
								been added where users can upload and edit new SID files.</li>
							<li>Upgraded the WebSid (HQ) emulator. Added support for ROM images; i.e. it can now play
								BASIC tunes.</li>
						</ul>

						<h3>January 2, 2020</h3>
						<ul>
							<li>The red <b>Boost</b> tag has been renamed to the more apt name <b>Doubling</b> instead.</li>
						</ul>

						<h3>December 31, 2019</h3>
						<ul>
							<li>Fixed a UTF-8 encoding bug in download filenames in some CSDb pages.</li>
						</ul>

						<h3>December 30, 2019</h3>
						<ul>
							<li>Added the new red tag <b>Mock</b>. This can be used to indicate a song that has been
								deliberately made to be bad, for example to be used in a fake demo.</li>
						</ul>

						<h3>December 29, 2019</h3>
						<ul>
							<li>If a SID row has too many tags to show at once, hover on one of the tags to
								temporarily slide them to the left side so that more tags are revealed.
							</li>
						</ul>

						<h3>December 27, 2019</h3>
						<ul>
							<li>The noise waveform is now on by default in the piano view.</li>
							<li>All new files in HVSC #72 are now connected to CSDb entries.</li>
							<li>Added the CSDb music competitions related to HVSC #72.</li>
						</ul>

						<h3>December 26, 2019</h3>
						<ul>
							<li>In the profile tab, the table with groups now show affiliations for all of the handles that
								the user have registered at CSDb. Also, the counts of credits and releases in the bottom is
								a sum of all these handles combined.</li>
						</ul>

						<h3>December 25, 2019</h3>
						<ul>
							<li>Tags are no longer shown by default on mobile devices. This is to prevent sideways dragging
								of the browser list. However, you can still turn them on with the check box in the sundry
								tab for viewing tags.</li>
						</ul>

						<h3>December 24, 2019</h3>
						<ul>
							<li>Fixed a bug that prevented "plinks" from playing in the CSDb forum threads.</li>
							<li>Toggling between two redirecting "plinks" with the <code>b</code> hotkey no longer disables
								the skip buttons.</li>
						</ul>

						<h3>December 22, 2019</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/">High Voltage SID Collection</a> has been upgraded to the latest version #72.</li>
							<li>Added composer profiles for the new folders in HVSC #72.</li>
						</ul>

						<h3>December 5, 2019</h3>
						<ul>
							<li>The <b>Boost</b> and <b>Hack</b> tags now have a vivid red color to serve as warnings.
								Please refer to <a href="tags.htm" target="_blank">this list of guidelines</a> for an
								explanation of these (and other) tags.</li>
						</ul>

						<h3>December 3, 2019</h3>
						<ul>
							<li>Clicking a link for playing a DeepSID tune from another site now invokes a big overlay
								with a button that must first be clicked. This is necessary because most web browsers
								today won't allow auto-playing audio.</li>
						</ul>

						<h3>December 2, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Fixed proper playback of two specific SID files.</li>
						</ul>

						<h3>November 28, 2019</h3>
						<ul>
							<li>Some profile pages may now show the brand or logo in both color themes.</li>
						</ul>

						<h3>November 26, 2019</h3>
						<ul>
							<li>After clicking a "plink" (a redirecting HVSC path link) you can now go back by pressing
								the <code>b</code> hotkey. You can also keep hitting it to toggle between the two locations
								if you want to compare the songs.</li>
						</ul>

						<h3>November 24, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Added 6581 voltage offset handling to improve respective $D418
								digis and fixed a bug in the IRQ ROM routine.</li>
						</ul>

						<h3>November 22, 2019</h3>
						<ul>
							<li>Fixed a database bug that occurred when searching for quoted tags in playlists.</li>
						</ul>

						<h3>November 19, 2019</h3>
						<ul>
							<li>Tag names with spaces in them can now be searched for when enclosed in quotes.</li>
							<li>Fixed not getting results when clicking tags with multiple words in the sundry tab.</li>
						</ul>

						<h3>November 18, 2019</h3>
						<ul>
							<li>Entering a folder with SID files now shows all their tags in the new sundry tab,
								ready to be clicked for filtering. When a SID file is clicked and only its tags are shown,
								a corner button can bring you back to the overview.</li>
						</ul>

						<h3>November 17, 2019</h3>
						<ul>
							<li>Added a new sundry tab for viewing all tags. This is particularly useful for songs that
								have so many tags that the SID row fails to display all of them.</li>
							<li>Clicking one of the tags shown in the new sundry tab performs a "here" search in the
								current folder.</li>
							<li>A check box by the new sundry tab for tags allows you to avoid displaying tags in
								SID rows. This is useful if you find the tags there too "noisy" and only want to rely
								on what the new sundry tab shows you.</li>
							<li>Fixed two bugs regarding the <code>Remix64</code> tag; one where it failed to search
								"here" and one where it didn't show up in the sundry box when being the only tag.</li>
						</ul>


						<h3>November 15, 2019</h3>
						<ul>
							<li>Added information about zero page usage in almost all of the player/editor pages.</li>
						</ul>

						<h3>November 13, 2019</h3>
						<ul>
							<li>Tags for digi (and its supplemental tags) now have a stark contrast color and are huddled
								together.</li>
						</ul>

						<h3>November 12, 2019</h3>
						<ul>
							<li>External SID files can now be temporarily loaded into DeepSID for testing against the JS
								emulators by clicking the <code>l</code> hotkey. You can load several SID files at once.
								Only you have temporary access to these files.</li>
						</ul>

						<h3>November 9, 2019</h3>
						<ul>
							<li>The WebSid emulators no longer skips ahead when a song is playing silence.</li>
							<li>The search permalink now also includes the <code>Here</code> check box when it has been ticked.</li>
						</ul>

						<h3>November 8, 2019</h3>
						<ul>
							<li>You can now search for multiple tags to narrow down your search.</li>
						</ul>

						<h3>November 6, 2019</h3>
						<ul>
							<li>Added an additional SID handler for legacy WebSid. This is the version of WebSid from
								before adding cycle-by-cycle processing. It's faster and has clearer digi sound, but
								it doesn't emulate quite as faithfully.</li>
							<li>Selecting a SID handler is now stored to make the choice sticky between sessions.</li>
							<li>Because of now storing the SID handler, the <code>?emulator=</code> switch is no longer
								appended to the URL when switching around. However, you can still specify the switch and
								it will then temporarily override the stored setting. See the table in the FAQ tab for a
								list of the switch values.</li>
							<li>The default SID handler for first time visitors now depends on the device. The best
								WebSid emulator is set for desktop computers, while the legacy WebSid emulator is set for
								mobile devices.</li>
						</ul>

						<h3>October 31, 2019</h3>
						<ul>
							<li>If you click a song and DeepSID detects that it has entries in the remix tab, a <b>Remix64</b>
								tag will automatically be added to it if it doesn't already have it.</li>
						</ul>

						<h3>October 30, 2019</h3>
						<ul>
							<li>Spiced up the tag for <b>Remix64</b> to make it more recognizable.</li>
						</ul>

						<h3>October 29, 2019</h3>
						<ul>
							<li>The legacy iOS version of the WebSid emulator broke in the latest iOS 13 update and thus
								has been replaced by the latest WebSid version also used on desktop computers. Yes, it is
								very demanding &ndash; but at least it works.</li>
							<li>The <b>Remix</b> and <b>Pastiche</b> tags now have a faded color (origin to normal).</li>
						</ul>

						<h3>October 25, 2019</h3>
						<ul>
							<li>Parent folders in CGSC now also remember an updated profile rating when going back.</li>
							<li>Composer profiles reused in other locations (such as in the exotic stereo 2SID folder or
								in sub folders with work tunes) no longer show star ratings as it doesn't directly relate
								to the parent folder in those situations.</li>
						</ul>

						<h3>October 24, 2019</h3>
						<ul>
							<li>Composer and folder profiles now also show star ratings that you can click. They are the
								same as is normally shown for a folder before entering it.
							</li>
						</ul>

						<h3>October 18, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Increased the initialization timeout for ALiH type players.</li>
						</ul>

						<h3>October 14, 2019</h3>
						<ul>
							<li>Different colors are now applied to production tags (blue) and origin tags (magenta)
								while all other types of tags still use the default tag color.</li>
							<li>Tags are now sorted correctly in the browser row after editing them.</li>
						</ul>

						<h3>October 9, 2019</h3>
						<ul>
							<li>Tags are now partly sorted in groups. Productions always come first (demo, game, etc.)
								followed by origin (cover, remake, conversion and their subtypes) and then other stuff
								sorted alphabetically for now.</li>
						</ul>

						<h3>October 4, 2019</h3>
						<ul>
							<li>Made the right list box taller (6 lines instead of 5) in the dialog box for editing tags.</li>
							<li>Added a link in the dialog box to a separate tab page with
								<a href="tags.htm" target="_blank">guidelines</a> for editing tags.</li>
						</ul>

						<h3>October 2, 2019</h3>
						<ul>
							<li>Tidied up tags for songs converted from other devices or formats, such as Amiga, Game Boy,
								Spectrum, Atari ST, Arcade, etc. These kind of covers now always use the tag
								<a href="//deepsid.chordian.net/?search=conversion&type=tag">Conversion</a>.</li>
						</ul>

						<h3>September 20, 2019</h3>
						<ul>
							<li>Sub folders in <a href="//deepsid.chordian.net?file=%2FCSDb%20Music%20Competitions">CSDb Music Competitions</a>
								now show both the competition name and its type.</li>
						</ul>

						<h3>September 18, 2019</h3>
						<ul>
							<li>Exotic SID folders for specific composers now have the same profiles as in HVSC.</li>
						</ul>

						<h3>September 16, 2019</h3>
						<ul>
							<li>Fixed SID info in the memo view showing nonsense for MUS files in CGSC.</li>
							<li>Fixed non-emulator SID handlers showing <code>FALSE</code> words all over the memo view blocks.</li>
						</ul>

						<h3>September 15, 2019</h3>
						<ul>
							<li><del>Android users now use the older iOS version of the WebSid emulator for performance testing.</del></li>
							<li>Added general SID info in the memo view which may be useful to programmers.</li>
						</ul>

						<h3>September 14, 2019</h3>
						<ul>
							<li>Mobile devices can no longer enter the folder with exotic SID files. Due to performance
								reasons, mobile devices use an older version of the WebSid emulator that is not
								compatible with the SID format used in this folder.</li>
							<li>Fixed a memo view bug where the beginning of C64 memory was shown for MUS files in CGSC.</li>
							<li>The WebSid emulator now changes its drop-down box text depending on the device. Desktop computers
								will continue to use <code>WebSid emulator</code> for the latest cycle-by-cycle version. Most
								mobile devices will use <code>WebSid (Legacy)</code> which is older but faster. And finally, iOS
								will use <code>WebSid (iOS)</code> to ensure continuous play.</li>
						</ul>

						<h3>September 13, 2019</h3>
						<ul>
							<li>Improved the performance of the updating of the memory view tables.</li>
							<li>Replaced the cutting of the the player block with page browsing instead. This makes it
								possible to see everything in pages of 512 bytes each.</li>
							<li>Both the zero page block and the player block are now continuously updated.</li>
							<li>All bytes updated in the memory view by the player code now turns red for easy spotting.</li>
						</ul>

						<h3>September 12, 2019</h3>
						<ul>
							<li>Fixed a bug where the piano view wasn't animating when clicking another song.</li>
							<li>Overhauled the way the piano and memory views are updated. If anything suddenly looks
								strange or doesn't work right (especially in the piano view) then please
								<a href="https://about.me/chordian" target="_blank">let me know</a>.</li>
							<li>Upgraded the WebSid emulator. Fixed handling of NTSC for PSID v2 and above.</li>
							<li>Added David Youd's <a href="//deepsid.chordian.net/?file=/Exotic%20SID%20Tunes%20Collection/Nutcracker_10SID.sid">Dance of the Sugar Plum Fairy</a> composed for ten SID chips.</li>
						</ul>

						<h3>September 11, 2019</h3>
						<ul>
							<li>A memo view button has been added in the visuals tab. Click it to see two parts of the
								C64 memory &ndash; one continuously updating zero page block, and a static view of the player
								block with code and music data. <del>Note that the latter will be cut short if the block is
								too big (more than 8K) to maintain performance.</del></li>
							<li>You can now click the dark blue memory chunk to jump to the new memo view.</li>
						</ul>

						<h3>September 8, 2019</h3>
						<ul>
							<li>Added almost 140 tunes in the <a href="//deepsid.chordian.net/?file=/Exotic%20SID%20Tunes%20Collection/Stereo%202SID/">Stereo 2SID</a>
								folder, converted from existing HVSC files.</li>
						</ul>

						<h3>September 7, 2019</h3>
						<ul>
							<li>You can now also search for tags inside playlists only.</li>
							<li>SID tunes should play properly on iOS devices (i.e. iPhone, iPad and iPod) again.</li>
						</ul>

						<h3>September 6, 2019</h3>
						<ul>
							<li>The legacy WebSid emulator (from before the cycle-by-cycle overhaul) is now used by mobile devices.</li>
						</ul>

						<h3>September 3, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Added n-SID stereo support, fixed D41B read bug, better performance.</li>
							<li>Imported the new GameBase64 collection v16 with new game entries and screenshots.</li>
						</ul>

						<h3>September 2, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Added support for a custom SID format that can play an
								arbitrary number of SID chips, with optional stereo support.</li>
							<li>The fourth major folder <a href="//deepsid.chordian.net/?file=%2FExotic%20SID%20Tunes%20Collection">Exotic SID Tunes Collection</a>
								has been added with a small selection of special SID tunes that uses the custom SID
								format now supported by the WebSid emulator.</li>
							<li>The technical document about the custom SID format can now be read in the new folder.</li>
						</ul>

						<h3>August 31, 2019</h3>
						<ul>
							<li>You can now also use <code>ENTER</code> to transfer list entries in the dialog box for editing tags.</li>
							<li>Hitting <code>ENTER</code> in the dialog box without any entries marked is now like clicking <code>OK</code>.
								This can make for really fast tag editing. Just open the dialog box, type-to-find a tag,
								<code>ENTER</code> to transfer it, then <code>ENTER</code> again to accept.</li>
							<li>It is no longer possible to add a new tag name that already exists.</li>
						</ul>

						<h3>August 29, 2019</h3>
						<ul>
							<li>Tags can now be edited for songs when you're logged in. When hovering on a SID row, a
								small <code>+</code> icon button appears at the end of the second line. Click this to
								open a dialog box where you can edit its tags. Alternatively, you can also select the
								new context menu option <code>Edit Tags</code> to open this dialog box.</li>
							<li>To accommodate the impending editing of tags, a ton of genres has been added to the list
								of available tags.</li>
							<li>Tweaked the color of tags in the dark color theme to have a more greenish tone.</li>
							<li>The <code>RSID</code> field is no longer shown in a SID row, to make more room for tags.</li>
						</ul>

						<h3>August 25, 2019</h3>
						<ul>
							<li>Added basic support for tags, shown in the line with year and player. To begin with only
								a few tags have been added. More tags will come, and later it will also be possible for
								logged in users to edit tags as well.</li>
							<li>You can search for a tag when the corresponding type is set in the drop-down box.</li>
						</ul>

						<h3>August 22, 2019</h3>
						<ul>
							<li>All page tabs now remember their scroll bar positions when clicking around among them.</li>
						</ul>

						<h3>August 20, 2019</h3>
						<ul>
							<li>Songs started from a "plink" will now stop when done instead of proceeding to next sub tune or song.</li>
							<li>Clicking an illegal "plink" path will now stop playing and strike the link itself through.</li>
						</ul>

						<h3>August 19, 2019</h3>
						<ul>
							<li>Trying to register a new user name now invokes a confirmation dialog box first.</li>
						</ul>

						<h3>August 18, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Fixed CNT-pin related issue.</li>
							<li>Added a <code>FORUM</code> link in the top, listing a few interesting forum threads
								from CSDb. These threads have been adapted with "plinks" whenever possible. Click
								the <code>FORUM</code> link for more about this.</li>
						</ul>

						<h3>August 16, 2019</h3>
						<ul>
							<li>HVSC path links in CSDb pages (e.g. those listed in music collections) now have a
								small play icon prepended.</li>
						</ul>

						<h3>August 14, 2019</h3>
						<ul>
							<li>The top table with the longest SID playing times now show sub tune numbers when relevant.
								This was particularly needed for songs with multiple long sub tunes in them.</li>
						</ul>

						<h3>August 12, 2019</h3>
						<ul>
							<li>Added <a href="//deepsid.chordian.net/?file=/MUSICIANS/W/Walker_Martin/">Martin Walker</a>,
								<a href="//deepsid.chordian.net/?file=/MUSICIANS/F/Fanta/">Fanta</a>,
								<a href="//deepsid.chordian.net/?file=/MUSICIANS/D/Detert_Thomas/">Thomas Detert</a> and
								<a href="//deepsid.chordian.net/?file=/MUSICIANS/V/Vincenzo/">Vincenzo</a>
								to the list of recommended folders.</li>
						</ul>

						<h3>August 11, 2019</h3>
						<ul>
							<li>The song length is now shown after a title in the remix tab.</li>
							<li>Added corner graphics for pointing out that you can also vote in the remix tab.</li>
						</ul>

						<h3>August 10, 2019</h3>
						<ul>
							<li>Added a new tab for listing and playing remixes of SID songs. The list is built using an
								API for <a href="https://www.remix64.com/">Remix64.com</a> and may play audio from
								<a href="http://remix.kwed.org/">Remix.Kwed.Org</a>. Both sites have given DeepSID
								permission to access their resources.
							</li>
						</ul>

						<h3>August 3, 2019</h3>
						<ul>
							<li>MUS files are now disabled for Hermit's emulator when searching.</li>
							<li>Tightened the handling of toggling and soloing voices for 2SID and 3SID tunes.</li>
						</ul>

						<h3>August 2, 2019</h3>
						<ul>
							<li>Fixed WebSid no longer playing 2SID and 3SID tunes after the script upgrades.</li>
							<li>The 16384 buffer size button in the scope tab now also works when you're not logged in.</li>
							<li>The song length at the end of the time bar now has a small dot added to it when the HVSC data
								includes milliseconds. You can then hover on the length to see the full time in a tooltip.</li>
							<li>Fixed a bug where the SID handlers stopped responding after SOASC couldn't find a song.</li>
						</ul>

						<h3>August 1, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Fixed broken support for MUS files in CGSC.</li>
							<li>Created a dark color theme for the graph view channels.</li>
							<li>Upgraded the script processor and oscilloscope scripts for the WebSid emulator. This was
								primarily done to eliminate the use of deprecated functions and should not be detectable.</li>
						</ul>

						<h3>July 30, 2019</h3>
						<ul>
							<li>The SID chip addresses are now shown when playing 2SID or 3SID tunes in the piano view.</li>
							<li>The piano view voice buttons now toggle entire SID chips ON or OFF for 2SID and 3SID tunes.
								This also covers the numeric hotkeys, and the graph view is similarly affected.</li>
						</ul>

						<h3>July 29, 2019</h3>
						<ul>
							<li>The piano view now supports 2SID and 3SID too &ndash; i.e. songs with 6 or 9 voices. Each keyboard
								will automatically combine to host an entire chip (i.e. 3 voices).</li>
						</ul>

						<h3>July 28, 2019</h3>
						<ul>
							<li>Replaced the view drop-down box in the visuals tab with big buttons instead.</li>
							<li>Changed the graph view layout toggle button into two radio buttons and moved them to the right side.</li>
						</ul>

						<h3>July 26, 2019</h3>
						<ul>
							<li>The graph view now also supports 2SID and 3SID &ndash; i.e. songs with 6 or 9 voices.</li>
						</ul>

						<h3>July 25, 2019</h3>
						<ul>
							<li>The pulse width button in the graph view now toggles between a "coat" or showing it in the right side.</li>
							<li>The graph view now show the filter cutoff frequency with a transition from strong to brighter yellow colors.</li>
						</ul>

						<h3>July 24, 2019</h3>
						<ul>
							<li>Modulations are now visible in the graph view, and it can be turned off if you find it too obtrusive.</li>
							<li>Removed the spacing between voices in the graph view. Might as well make use of all of the available space.</li>
							<li>Added a voice number to each voice in the graph view.</li>
						</ul>

						<h3>July 23, 2019</h3>
						<ul>
							<li>Upgraded the WebSid emulator. Fixed a PSID timer issue with Fred Gray's
								<a href="http://deepsid.chordian.net/?file=/MUSICIANS/G/Gray_Fred/Madballs.sid">Madballs</a>.</li>
							<li>Removed the zoom option in the graph view and instead added a choice between row or column layouts.</li>
							<li>Fixed a bug where the graph view was updated twice as fast as it needed to be.</li>
						</ul>

						<h3>July 22, 2019</h3>
						<ul>
							<li>The WebSid emulator has been significantly updated. The previous version of it used a 
								predictive emulation technique and required hacks for some SID tunes to work. The newly
								updated version has been overhauled to use a cycle-by-cycle approach and no longer need
								these hacks. It also emulates digi more faithfully and now supports more
								difficult SID tunes than ever before. The downside is that the new emulation takes
								about 50-100% more CPU time, depending on the SID tune itself.</li>
						</ul>

						<h3>July 20, 2019</h3>
						<ul>
							<li>Created a new visuals tab and moved the piano and graph views into a drop-down box inside of it.</li>
						</ul>

						<h3>July 18, 2019</h3>
						<ul>
							<li>Fixed a bug where the browser context menu appeared together with the custom context menu.</li>
						</ul>

						<h3>July 14, 2019</h3>
						<ul>
							<li>You can now click a small button near the logo to toggle between a bright or a dark color theme.</li>
							<li>The SID handler option <code>SOASC Automatic</code> now shows a magenta color instead of black in the time bar.</li>
						</ul>

						<h3>July 6, 2019</h3>
						<ul>
							<li>Fixed a bug where default settings were not created for guests.</li>
						</ul>

						<h3>July 2, 2019</h3>
						<ul>
							<li>Fixed a data discrepancy in the top 20 tables with longest and total SID playing times.</li>
							<li>The top 20 table with the longest SID tunes no longer show milliseconds.</li>
							<li>All new files in HVSC #71 are now connected to CSDb entries.</li>
							<li>Added the CSDb music competitions related to HVSC #71.</li>
						</ul>

						<h3>July 1, 2019</h3>
						<ul>
							<li>Added composer profiles for the new folders in HVSC #71.</li>
						</ul>

						<h3>June 30, 2019</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/">High Voltage SID Collection</a> has been upgraded to the latest version #71.</li>
							<li>The maximum song length at the end of the time bar now cuts off the milliseconds shown.</li>
							<li>Fixed a bug where some STIL entries had letters cut off in the end of the text. This was a bug in a Python
								script used for importing them. My apologies to the HVSC crew for being accused by users for causing this.</li>
							<li>Fixed a new search bug that sometimes gave no results.</li>
						</ul>

						<h3>June 29, 2019</h3>
						<ul>
							<li>Fixed two minor bugs when clicking the time bar while using an SOASC handler to play a song.</li>
						</ul>

						<h3>June 28, 2019</h3>
						<ul>
							<li>Fixed a problem that erroneously reported down time from the SOASC file servers.</li>
							<li>The SOASC options in the handler drop-down box will now be red too if SOASC is down.</li>
						</ul>

						<h3>June 27, 2019</h3>
						<ul>
							<li>New toggle in settings: Always start at the first sub tune in a song instead of the default set by HVSC.</li>
							<li>The status of the SOASC file servers can now be viewed in the top. This is checked regularly by a cron job.</li>
						</ul>

						<h3>June 23, 2019</h3>
						<ul>
							<li>The web hotel have upgraded MySQL to a version that requires default values for all database
								fields. I have now added these for all tables. This should fix the recent
								issue about not being able to add tunes to playlists.</li>
						</ul>

						<h3>June 17, 2019</h3>
						<ul>
							<li>You can now click the middle mouse button on the subtune buttons, for first and last subtune.</li>
							<li>Fixed a minor character display bug in the GB64 tab.</li>
							<li>You can now also click a link to report a profile change for a composer. This uses the
								"mailto" link method and it automatically prepares the body text with a link to the profile.</li>
						</ul>

						<h3>June 15, 2019</h3>
						<ul>
							<li>Tightened the handling of ratings when sorting in the competition folders.</li>
						</ul>

						<h3>June 14, 2019</h3>
						<ul>
							<li>You can now sort the <a href="//deepsid.chordian.net?file=%2FCSDb%20Music%20Competitions">CSDb Music Competitions</a> folder.</li>
						</ul>

						<h3>June 13, 2019</h3>
						<ul>
							<li>Fixed a bug where the wrong sub tune was set when adding another tune to a playlist.</li>
						</ul>

						<hr />
						<i>Click <a href="changes.htm">here</a> to see archived changes going back to the launch of DeepSID.</i>

					</div>

				</div>
			</div>

			<script id="dsq-count-scr" src="//deepsid.disqus.com/count.js" async></script> <!-- DISQUS -->

		<?php endif ?>

	</body>

</html>