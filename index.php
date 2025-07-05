<?php
	if (false) die('DeepSID is being updated. Please return again in a few minutes.');

	require_once("php/class.account.php"); // Includes setup
	$user_id = $account->CheckLogin() ? $account->UserID() : 0;

	require_once("tracking.php"); // Also called every 5 minutes by 'main.js'

	// @link https://stackoverflow.com/a/60199374/2242348
	// $inside_iframe = isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe';

	// Detect and block if the URL contains unwanted characters
	// Example: https://deepsid.chordian.net/?file=%22%3E%3Ch1%3Efoobarbaz
	$special_chars = array('[', ']', '<', '>', ';', ',', '"', '*');
	$url = urldecode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
	foreach ($special_chars as $char)
		if (strpos($url, $char) !== false)
			die("Malignant switch contents detected. Please fix the URL and try again.");

	function MiniPlayer() {
		return isset($_GET['mini'])
			? $_GET['mini']
			: 0;
	}

	function isMobile() {
		return isset($_GET['mobile'])
			? $_GET['mobile']
			: preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}

	function isEmulator($emulator) {
		return (isset($_GET['emulator']) && strtolower($_GET['emulator']) == $emulator) ||
			(isset($_COOKIE['emulator']) && strtolower($_COOKIE['emulator']) == $emulator);
	}

	function isLemon() {
		$lemon = isset($_GET['lemon']) ? $_GET['lemon'] : 0;
		if ($lemon) {
			if (!isset($_SESSION)) session_start();
			$_SESSION['lemon'] = true;
			return true;
		} else if (!isset($_GET['lemon']) && isset($_SESSION) && isset($_SESSION['lemon'])) {
			return true;
		} else {
			if (!isset($_SESSION)) session_start();
			$_SESSION['lemon'] = null;
			unset($_SESSION['lemon']);
			return false;
		}
	}
?>
<!DOCTYPE html>
<html lang="en-US" style="overflow:scroll-x;">

	<head>

		<meta charset="utf-8" />
		<script type="text/javascript">
			var viewport = document.createElement("meta");
			viewport.setAttribute("name", "viewport");
			viewport.setAttribute("content", "width="+(screen.width < 450 ? "450" : "1320"));
			document.head.appendChild(viewport);
		</script>
		<meta name="description" content="A modern online SID player for the High Voltage and Compute's Gazette SID collections." /> <!-- Max 150 characters -->
		<meta name="keywords" content="c64,commodore 64,sid,6581,8580,hvsc,high voltage,cgsc,compute's gazette,visualizer,stil,websid,hermit,asid,webusb,usbsid" />
		<meta name="author" content="Jens-Christian Huus" />
		<title>DeepSID | Chordian.net</title>
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans%3A400%2C700%2C400italic%2C700italic%7CQuestrial%7CMontserrat&#038;subset=latin%2Clatin-ext" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Asap+Condensed" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Kanit" />
		<link rel="stylesheet" type="text/css" href="//blog.chordian.net/wordpress/wp-content/themes/olivi/style.css" />
		<link rel="stylesheet" type="text/css" href="css/chartist.css?v=<?php echo filemtime('css/chartist.css') ?>" />
		<link rel="stylesheet" type="text/css" href="css/style.css?v=<?php echo filemtime('css/style.css') ?>" />
		<?php if (isLemon()): ?>
			<!-- For Lemon64: START -->
			<link rel="stylesheet" type="text/css" href="https://www.lemon64.com/assets/external/deepsid/style.css" />
			<!-- For Lemon64: END -->
		<?php endif ?>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script type="text/javascript">
			var supportsWASM = false;
			try { // @link https://stackoverflow.com/a/47880734/2242348
				if (typeof WebAssembly === "object"
					&& typeof WebAssembly.instantiate === "function") {
					const module = new WebAssembly.Module(Uint8Array.of(0x0, 0x61, 0x73, 0x6d, 0x01, 0x00, 0x00, 0x00));
					if (module instanceof WebAssembly.Module)
						supportsWASM = new WebAssembly.Instance(module) instanceof WebAssembly.Instance;
				}
			} catch (e) { /* Nothing */ }
			if (!supportsWASM) {
				document.write('\
					<div style="padding-left:24px;">\
						<h2>WebAssembly not detected</h2>\
						<p>DeepSID needs WASM for its most advanced SID emulators.</p>\
					</div>\
				');
				window.stop();
			}
			window.WASM_SEARCH_PATH = "js/handlers/"; // Used by all of JW's emulators
		</script>
		<?php if (isset($_GET['websiddebug'])): ?>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/scriptprocessor_player.min.js"></script>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/backend_websid.js"></script>
		<?php else: ?>
			<script type="text/javascript" src="js/handlers/scriptprocessor_player.min.js?v=<?php echo filemtime('js/handlers/scriptprocessor_player.min.js') ?>"></script>
			<script type="text/javascript" src="js/handlers/backend_tinyrsid.js?v=<?php echo filemtime('js/handlers/backend_tinyrsid.js') ?>"></script>
			<script type="text/javascript" src="js/handlers/backend_websid.js?v=<?php echo filemtime('js/handlers/backend_websid.js') ?>"></script>
			<script type="text/javascript" src="js/handlers/backend_websidplay.js?v=<?php echo filemtime('js/handlers/backend_websidplay.js') ?>"></script>
		<?php endif ?>

		<!--<script type="text/javascript" src="js/handlers/jsidplay2.js"></script>-->
		<script type="text/javascript" src="js/handlers/jsSID-modified.js?v=<?php echo filemtime('js/handlers/jsSID-modified.js') ?>"></script>
		<script type="text/javascript" src="js/handlers/howler.min.js?v=<?php echo filemtime('js/handlers/howler.min.js') ?>"></script>
		<script type="text/javascript" src="js/chartist.min.js?v=<?php echo filemtime('js/chartist.min.js') ?>"></script>
		<?php // @link https://github.com/madmurphy/cookies.js ?>
		<script type="text/javascript" src="js/cookies.min.js?v=<?php echo filemtime('js/cookies.min.js') ?>"></script>
		<script type="text/javascript" src="js/select.js?v=<?php echo filemtime('js/select.js') ?>"></script>
		<script type="text/javascript" src="js/player.js?v=<?php echo filemtime('js/player.js') ?>"></script>
		<script type="text/javascript" src="js/controls.js?v=<?php echo filemtime('js/controls.js') ?>"></script>
		<script type="text/javascript" src="js/browser.js?v=<?php echo filemtime('js/browser.js') ?>"></script>
		<script type="text/javascript" src="js/lib/opl.js?v=<?php echo filemtime('js/lib/opl.js') ?>"></script>
		<script type="text/javascript" src="js/opljs-if.js?v=<?php echo filemtime('js/opljs-if.js') ?>"></script>

		<?php if (isset($_GET['websiddebug'])): ?>
			<script type="text/javascript" src="http://www.wothke.ch/tmp/channelstreamer.js"></script>
		<?php else: ?>
			<script type="text/javascript" src="js/handlers/channelstreamer.min.js?v=<?php echo filemtime('js/handlers/channelstreamer.min.js') ?>"></script>
		<?php endif ?>
		<script type="text/javascript" src="js/viz.js?v=<?php echo filemtime('js/viz.js') ?>"></script>
		<script type="text/javascript" src="js/main.js?v=<?php echo filemtime('js/main.js') ?>"></script>
		<?php if (isLemon()): ?>
			<!-- For Lemon64: START -->
			<script type="text/javascript" src="https://www.lemon64.com/assets/external/deepsid/main.js"></script>
			<!-- For Lemon64: END -->
		<?php endif ?>
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
				else if (substr($file, 0, 12) == '/SID Happens')
					$file = str_replace('/SID', '_SID', $file);

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
		<meta property="og:image" content="<?php

			function MergeImage($image) {

				// @link https://stackoverflow.com/a/2269459/2242348
				$png = imagecreatefrompng('images/og_overlay.png');
				$jpeg = imagecreatefromjpeg('images/composers/'.$image);

				list($width, $height) = getimagesize('images/composers/'.$image);
				list($newwidth, $newheight) = getimagesize('images/og_overlay.png');
				$out = imagecreatetruecolor($newwidth, $newheight);
				imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
				imagecopyresampled($out, $png, 0, 0, 0, 0, $newwidth, $newheight, $newwidth, $newheight);

				// 100 is best quality
				imagejpeg($out, $_SERVER['DOCUMENT_ROOT'].'/deepsid/images/composers/play/'.$image, 100);
				echo HOST.'images/composers/play/'.$image;
			}

			if (isset($_GET['file']) && (strtolower(substr($_GET['file'], 0, 10))) == '/musicians') {
				$file = substr($_GET['file'], -4) == '.sid'
					? substr($_GET['file'], 0, strrpos($_GET['file'], '/'))
					: $_GET['file'];
				$image = strtolower(str_replace('/', '_', trim($file, '/'))).'.jpg';
				if (file_exists('images/composers/'.$image))
					if (substr($_GET['file'], -4) == '.sid' || substr($_GET['file'], -4) == '.mus')
						MergeImage($image);
					else
						echo 'https://chordian.net/deepsid/images/composers/'.$image;
				else if (substr($_GET['file'], -4) == '.sid')
					echo 'https://chordian.net/deepsid/images/example_play.png';
				else
					echo 'https://chordian.net/deepsid/images/composer.png';
			} else if (isset($_GET['file']) && (strtolower(substr($_GET['file'], 0, 12))) == '/sid happens') {
				$image = '';
				try {
					if ($_SERVER['HTTP_HOST'] == LOCALHOST)
						$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
					else
						$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
					$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db->exec("SET NAMES UTF8");

					// Get the ID of the SH file
					$select = $db->prepare('SELECT id FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
					$select->execute(array(':fullname'=>str_replace('/SID', '_SID', $_GET['file'])));
					$select->setFetchMode(PDO::FETCH_OBJ);

					if ($select->rowCount()) {

						// Get the ID of the composer profile
						$select_upload = $db->query('SELECT composers_id FROM uploads WHERE files_id = '.$select->fetch()->id.' LIMIT 1');
						$select_upload->setFetchMode(PDO::FETCH_OBJ);

						if ($select_upload->rowCount()) {

							// Get the full path to the real composer profile
							$select_comp = $db->query('SELECT fullname FROM composers WHERE id = '.$select_upload->fetch()->composers_id.' LIMIT 1');
							$select_comp->setFetchMode(PDO::FETCH_OBJ);

							if ($select_comp->rowCount()) {

								// Figure out the name of the thumbnail (if it exists)
								$fullname = str_replace('_High Voltage SID Collection/', '', $select_comp->fetch()->fullname);
								$fullname = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fullname);
								$fullname = strtolower(str_replace('/', '_', $fullname));
								$image = $fullname.'.jpg';
							}
						}
					}
				} catch(PDOException $e) {
					// Never mind then
				}

				if (!empty($image) && file_exists('images/composers/'.$image))
					MergeImage($image);
				else if (substr($_GET['file'], -4) == '.sid')
					echo 'https://chordian.net/deepsid/images/example_play.png';
				else
					echo 'https://chordian.net/deepsid/images/composers/_sh.png';
			} else
				echo 'https://chordian.net/deepsid/images/example.png';
		?>" />
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

	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-8WGW8WKDN4"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'G-8WGW8WKDN4');
	</script>

	<body class="entry-content" data-mobile="<?php echo isMobile(); ?>" data-theme="" data-mini="<?php echo MiniPlayer(); ?>" data-notips="<?php echo isLemon() ? 1 : 0; ?>">
		<?php if (!isLemon()): ?>
			<script type="text/javascript">setTheme();</script>
		<?php endif ?>

		<div id="dialog-cover"></div>
		<div id="click-to-play-cover">
			<div class="center">
				<div class="play"></div>
				<span class="text-below"><?php echo isMobile() ? 'Touch' : 'Click'; ?> to play</span>
			</div>
		</div>
		<img id="zoomed" src="" alt="Zoomed screenshot" />

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
			<div class="dialog-connect">
				<label for="start-tags">Connect this tag:</label><br />
				<select id="dialog-list-start-tag" name="start-tags"></select>
				<label for="end-tags">With this tag:</label><br />
				<select id="dialog-list-end-tag" name="end-tags"></select>
			</div>
			<div class="dialog-buttons" style="width:136px;"><button class="dialog-button-yes dialog-auto" style="float:left;margin:0;">OK</button><button class="dialog-button-no dialog-auto" style="float:right;margin:0;">Cancel</button></div>
		</div>

		<div id="dialog-ev-subtunes" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<div style="margin-top:16px;">
				<label for="dd-subtune">Subtune&nbsp;&nbsp;</label>
				<select id="ev-dd-subtune" name="dd-subtune"></select>
			</div>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>

		<div id="dialog-playlist-rename" class="dialog-box">
			<div class="dialog-text"></div>
			<form id="form-edit-file" onsubmit="return false;" autocomplete="off">
				<div style="margin-top:16px;">
					<label for="dd-newplname" style="width:100px;">New name&nbsp;&nbsp;</label>
					<input type="text" name="dd-newplname" id="pr-newplname" maxlength="128" value="My favorites" />
				</div>
			</form>
			<div style="font-size:13px;margin-top:16px;">If you skip this, it will just have the filename. However, you can rename it later by right-clicking it in the root.</div>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Skip</a><button class="dialog-button-yes dialog-auto">Rename</button></div>
		</div>

		<div id="dialog-edit-videos" class="dialog-box">
			<a id="ev-corner-link" href="#" target="_blank" style="position:absolute;top:24px;right:24px;font-size:13px;">Open Tabs</a>
			<small>Use <b>Firefox</b> for this</small>
			<div class="dialog-text"></div>
			<fieldset style="height:200px;"><legend>Song</legend>
				<div class="ev-tabs">
					<span>Tab</span>
					<span><input type="checkbox" id="ev-cb-1" /></span>
					<span><input type="checkbox" id="ev-cb-2" /></span>
					<span><input type="checkbox" id="ev-cb-3" /></span>
					<span><input type="checkbox" id="ev-cb-4" /></span>
					<span><input type="checkbox" id="ev-cb-5" /></span>
				</div>
				<div class="ev-default">
					<span>Default</span>
					<span><input type="radio" name="ev-rb" id="ev-rb-1" disabled /></span>
					<span><input type="radio" name="ev-rb" id="ev-rb-2" disabled /></span>
					<span><input type="radio" name="ev-rb" id="ev-rb-3" disabled /></span>
					<span><input type="radio" name="ev-rb" id="ev-rb-4" disabled /></span>
					<span><input type="radio" name="ev-rb" id="ev-rb-5" disabled /></span>
				</div>
				<div class="ev-channel">
					<span>Channel name</span>
					<form onsubmit="return false;" autocomplete="off">
						<span><input type="text" id="ev-tb-cn-1" maxlength="32" disabled /> <button id="ev-se-1" type="button" class="disabled"><img src="images/search.svg" alt="" /></button></span>
						<span><input type="text" id="ev-tb-cn-2" maxlength="32" disabled /> <button id="ev-se-2" type="button" class="disabled"><img src="images/search.svg" alt="" /></button></span>
						<span><input type="text" id="ev-tb-cn-3" maxlength="32" disabled /> <button id="ev-se-3" type="button" class="disabled"><img src="images/search.svg" alt="" /></button></span>
						<span><input type="text" id="ev-tb-cn-4" maxlength="32" disabled /> <button id="ev-se-4" type="button" class="disabled"><img src="images/search.svg" alt="" /></button></span>
						<span><input type="text" id="ev-tb-cn-5" maxlength="32" disabled /> <button id="ev-se-5" type="button" class="disabled"><img src="images/search.svg" alt="" /></button></span>
					</form>
				</div>
				<div class="ev-video">
					<span>Video ID</span>
					<form onsubmit="return false;" autocomplete="off">
						<span><input type="text" id="ev-tb-vi-1" maxlength="20" disabled /></span>
						<span><input type="text" id="ev-tb-vi-2" maxlength="20" disabled /></span>
						<span><input type="text" id="ev-tb-vi-3" maxlength="20" disabled /></span>
						<span><input type="text" id="ev-tb-vi-4" maxlength="20" disabled /></span>
						<span><input type="text" id="ev-tb-vi-5" maxlength="20" disabled /></span>
					</form>
				</div>
				<div class="ev-position">
					<span>Position</span>
					<span><button id="ev-up-1" type="button" class="disabled">Up</button> <button id="ev-dn-1" type="button" class="disabled">Dn</button></span>
					<span><button id="ev-up-2" type="button" class="disabled">Up</button> <button id="ev-dn-2" type="button" class="disabled">Dn</button></span>
					<span><button id="ev-up-3" type="button" class="disabled">Up</button> <button id="ev-dn-3" type="button" class="disabled">Dn</button></span>
					<span><button id="ev-up-4" type="button" class="disabled">Up</button> <button id="ev-dn-4" type="button" class="disabled">Dn</button></span>
					<span><button id="ev-up-5" type="button" class="disabled">Up</button> <button id="ev-dn-5" type="button" class="disabled">Dn</button></span>
				</div>
				<div id="ev-dd2">
					<input type="checkbox" id="ev-dd2-checkbox" />
					<label for="ev-dd2-checkbox" class="disabled">Edit subtune&nbsp;</label>
					<select id="ev-dd2-subtune" class="disabled" disabled></select>
					<label for="ev-dd2-checkbox" class="disabled">&nbsp;next</label>
				</div>
			</fieldset>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-yes dialog-auto">Save</button></div>
		</div>

		<div id="dialog-edit-file" class="dialog-box">
			<div class="dialog-text"></div>
			<form id="form-edit-file" onsubmit="return false;" autocomplete="off">
				<label id="label-edit-file-name" for="edit-file-name" style="margin-bottom:15px;">Name</label>
				<input type="text" name="edit-file-name" id="edit-file-name-input" maxlength="64" style="margin-bottom:11px;" /><br />
				<label id="label-edit-file-player" for="edit-file-player" style="margin-bottom:15px;">Player</label>
				<input type="text" name="edit-file-player" id="edit-file-player-input" maxlength="48" style="margin-bottom:11px;" /><br />
				<label id="label-edit-file-author" for="edit-file-author">Author</label>
				<input type="text" name="edit-file-author" id="edit-file-author-input" maxlength="128" /><br />
				<label id="label-edit-file-copyright" for="edit-file-copyright">Copyright</label>
				<input type="text" name="edit-file-copyright" id="edit-file-copyright-input" maxlength="128" />
			</form>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-yes dialog-auto">OK</button></div>
		</div>

		<div id="dialog-delete-file" class="dialog-box">
			<div class="dialog-text"></div>
			<div id="file-name-delete" class="clink-text ellipsis"></div>
			<div class="dialog-buttons"><button class="dialog-button-yes">Yes</button><button class="dialog-button-no">No</button></div>
		</div>

		<div id="dialog-add-clink" class="dialog-box">
			<div class="dialog-text"></div>
			<form onsubmit="return false;" autocomplete="off">
				<label id="label-clink-name" for="edit-clink-name">Name</label>
				<input type="text" name="edit-clink-name" id="edit-clink-name-input" maxlength="128" /><br />
				<label id="label-clink-url" for="edit-clink-url">URL</label>
				<input type="text" name="edit-clink-url" id="edit-clink-url-input" maxlength="512" /><br />
			</form>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-yes dialog-auto">Save</button></div>
		</div>

		<div id="dialog-delete-clink" class="dialog-box">
			<div class="dialog-text"></div>
			<div id="clink-name-delete" class="clink-text ellipsis"></div>
			<div id="clink-url-delete" class="clink-text ellipsis"></div>
			<div class="dialog-buttons"><button class="dialog-button-yes">Yes</button><button class="dialog-button-no">No</button></div>
		</div>

		<input id="upload-new" type="file" accept=".sid" style="display:none;" />
		<div id="dialog-upload-wiz2" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>
		<div id="dialog-upload-wiz3" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<label for="upload-profile">Connect <b>profile</b> page from HVSC/MUSICIANS:</label>
			<select id="dropdown-upload-profile" name="upload-profile"></select>
			<label for="upload-csdb">Connect <b>CSDb</b> ID:</label><form onsubmit="return false;" autocomplete="off" style="float:right;"><span class="url">https://csdb.dk/release/?id<span style="margin:0 2px;">=</span></span><input type="text" name="upload-csdb" id="upload-csdb-id" onkeypress='return event.charCode >= 48 && event.charCode <= 57;' maxlength="6" value="0" /></form>
			<label id="label-lengths" for="upload-lengths" style="white-space:nowrap;">Define <b>lengths</b> of tunes:</label><br />
			<form id="form-lengths" onsubmit="return false;" autocomplete="off"><input type="text" name="upload-lengths" id="upload-lengths-list" onkeypress='return event.charCode >= 48 && event.charCode <= 57 || event.key == ":" || event.charCode == 32;' /></form>
			<p>If you don't know the <span id="span-lengths">lengths just leave them</span> as is for now. You can edit the file again later.</p>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>
		<div id="dialog-upload-wiz4" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<form id="form-upload-file" onsubmit="return false;" autocomplete="off">
				<label id="label-upload-file-name" for="upload-file-name">Filename</label>
				<input type="text" name="upload-file-name" id="upload-file-name-input" maxlength="64" /><br />
				<div style="margin-top:16px;">
					<label id="label-upload-file-player" for="upload-file-player">Player</label>
					<input type="text" name="upload-file-player" id="upload-file-player-input" maxlength="48" /><br />
				</div>
				<div style="margin-top:16px;">
					<label id="label-upload-file-author" for="upload-file-author">Author</label>
					<input type="text" name="upload-file-author" id="upload-file-author-input" maxlength="128" /><br />
				</div>
				<div style="margin-top:6px;">
					<label id="label-upload-file-copyright" for="upload-file-copyright">Copyright</label>
					<input type="text" name="upload-file-copyright" id="upload-file-copyright-input" maxlength="128" />
				</div>
			</form>
			<p>This only affects the lines you see in the folder list as the top left box reflects the SID file itself.</p>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Next</button></div>
		</div>
		<div id="dialog-upload-wiz5" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<label for="upload-stil">Custom text for the <b>STIL</b> tabs:</label>
			<textarea id="upload-stil-text" name="upload-stil" maxlength="8192"></textarea>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-no dialog-auto">Back</button><button class="dialog-button-yes dialog-auto">Finish</button></div>
		</div>

		<iframe id="download" style="display:none;"></iframe>
		<input id="upload-test" type="file" accept=".sid" style="display:none;" multiple required />

		<div id="panel">
			<div id="top">
				<div id="logo" class="unselectable">D e e p S I D
					<?php if (MiniPlayer()) echo '<div style="position:absolute;top:24px;left:200px;white-space:nowrap;">mini-player</div>'; ?>
				</div>
				<select id="dropdown-topleft-emulator" name="select-topleft-emulator" style="visibility:hidden;">
					<option value="resid">reSID (BETA)</option>
					<!--<option value="resid">reSID (WebSidPlay)</option>-->
					<option value="jsidplay2">JSIDPlay2 (reSID)</option>
					<option value="websid">WebSid emulator</option>
					<option value="legacy">WebSid (Legacy)</option>
					<option value="hermit">Hermit's (+FM)</option>
					<option value="webusb">WebUSB (Hermit)</option>
					<option value="asid">ASID (MIDI)</option>
					<option value="lemon">Lemon's MP3 files</option>
					<option value="youtube">YouTube videos</option>
					<option value="download">Download SID file</option>
					<option value="silence">No SID handler</option>
				</select>
				<div id="theme-selector" title="Click here to toggle the color theme"><div></div></div>

				<?php if (!MiniPlayer()): ?>
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
								<div id="response">Login or <a href="#" class="reg-new">register</a> to rate tunes</div>
								<input type="hidden" name="submitted" value="1" />
								<input type="text" class="spmhidip" name="<?php echo $account->SpamTrapName(); ?>" style="display:none;" />

								<label for="username" id="label-username">User</label>
								<input type="text" name="username" id="username" value="<?php echo $account->PostValue('username'); ?>" maxlength="64" />

								<label for="password" id="label-password">Pw</label>
								<input type="password" name="password" id="password" maxlength="32" />

								<label>
									<input type="submit" name="submit" value="Submit" style="display:none;" />
									<button title="Log in or register" id="reg-login-button">
										<svg height="14" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1312 896q0 26-19 45l-544 544q-19 19-45 19t-45-19-19-45v-288h-448q-26 0-45-19t-19-45v-384q0-26 19-45t45-19h448v-288q0-26 19-45t45-19 45 19l544 544q19 19 19 45zm352-352v704q0 119-84.5 203.5t-203.5 84.5h-320q-13 0-22.5-9.5t-9.5-22.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q66 0 113-47t47-113v-704q0-66-47-113t-113-47h-312l-11.5-1-11.5-3-8-5.5-7-9-2-13.5q0-4-1-20t-.5-26.5 3-23.5 10-19.5 20.5-6.5h320q119 0 203.5 84.5t84.5 203.5z"/></svg>
									</button>
								</label>
							</fieldset>
						</form>
					<?php endif; ?>
				<?php endif ?>
			</div>
			<div id="webusb-connect" style="display:none;">
				<button id="device-connect">Connect</button>
				<label id="connect-text" for="device-connect">to USB device</label>
				<label id="status-text" for="device-connect"></label>
			</div>
			<div id="asid-midi" style="display:none;">
				<label for="select-midi-outputs">MIDI port for ASID</label>
				<!--<select id="asid-midi-outputs" name="select-midi-outputs" style="visibility:hidden;"></select>-->
				<select id="asid-midi-outputs" name="select-midi-outputs"></select>
			</div>

			<div id="youtube-tabs">
				<div class="tab unselectable selected">DeepSID</div>
			</div>
			<div id="info">
				<div id="info-text">
					<?php if (MiniPlayer()): ?>
						<div style="text-align:center;font-size:12px;">
							<span style="position:relative;top:2px;">Please specify a tune to play with the <code style="font-size:12px;">?file=</code> URL parameter.<br />
							Optionally add <code style="font-size:12px;">&wait=100</code> to prepare the tune but not play it yet.<br />
							Optionally add <code style="font-size:12px;">&sundry=scope</code> to select the <b>Scope</b> tab as default.<br /></span>
						</div>
					<?php else : ?>
						<div style="text-align:center;font-size:12px;">
							<span style="position:relative;top:2px;">DeepSID is an online SID player for the High Voltage SID Collection and<br />
							more. It plays music created for the <a href="https://en.wikipedia.org/wiki/Commodore_64" target="_top">Commodore 64</a> home computer.</span><br />
							<span style="position:relative;top:8px;">Click any of the folder items below to start browsing the collection.</span>
						</div>
					<?php endif ?>
				</div>
				<div id="youtube">
					<div id="youtube-loading">Initializing YouTube...</div>
					<div id="youtube-player"></div>
				</div>
				<div id="memory-bar"><div id="memory-lid"></div><div id="memory-chunk"></div><div id="memory-screen"></div><div id="memory-basic">BASIC</div><div id="memory-kernel">KERNEL</div></div>
			</div>
			<div id="sundry-tabs">
				<div class="tab unselectable" data-topic="stil" id="stab-stil">News</div>
				<?php if (!MiniPlayer()): ?>
					<div class="tab unselectable" data-topic="tags" id="stab-tags">Tags</div>
				<?php endif ?>
				<div class="tab unselectable" data-topic="osc" id="stab-osc">Scope</div>
				<?php if (!MiniPlayer()): ?>
					<div class="tab unselectable" data-topic="filter" id="stab-filter">Filter</div>
				<?php endif ?>
				<div class="tab unselectable" data-topic="stereo" id="stab-stereo">Stereo</div>
				<div id="sundry-ctrls"></div>
			</div>
			<div id="sundry">
				<div id="stopic-stil" class="stopic">
					<?php if (!MiniPlayer()): ?>
						<div id="sundry-news">
							<span>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #83. Click <a href="//deepsid.chordian.net/?search=83&type=new">here</a> to see what's new in this update.</span>
							<!--<span><a href="http://www.c64music.co.uk/" target="_top">Compute's Gazette SID Collection</a> has been upgraded to the latest version #146. Click <a href="//deepsid.chordian.net/?search=146&type=new">here</a> to see what's new in this update.</span>-->
							<!--   SHOULD NOT BE NECESSARY ANYMORE: <span>I changed some Javascript files so make sure your browser cache is up to date. On Windows, hit <b style="color:#77c;">Ctrl+F5</b> while viewing the site, on Mac, hit <b style="color:#77c;">Cmd+Shift+R</b>.</span>-->
							<!--<span>Want to learn how to make SID tunes? Check out <a href="https://www.youtube.com/watch?v=nXNtLetxFUg">this tutorial video</a> now on YouTube.</span>-->
							<!--   See in controls.js: "showNewsImage" and "clickNews" for how to set up a news image -->
							<!--<pre><b style="font-size:16px;">Coming up<br></b><b style="position:relative;top:-3px;font-size:13px;">17 - 19 Nov.</b><br><br><i style="position:absolute;bottom:-38px;right:4px;font-size:13px;line-height:17px;text-align:right;">New SID tunes<br><font style="font-size:17px;">incoming!</font></i></pre>-->
						</div>
					<?php endif ?>
				</div>
				<div id="stopic-tags" class="stopic" style="display:none;"></div>
				<div id="stopic-osc" class="stopic" style="display:none;"></div>
				<div id="stopic-filter" class="stopic" style="display:none;">
					<form onsubmit="return false;" autocomplete="off">
						<div style="float:left;width:48%;padding-bottom:2px;">
							<div class="sundry-control">
								<label class="disabled unselectable">Minimum</label>
								<input id="filter-base-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-base-slider" class="disabled" type="range" min="0" max="0.3" value="0" step="0.0012" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Maximum</label>
								<input id="filter-max-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-max-slider" class="disabled" type="range" min="0" max="1" value="0" step="0.004" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Steepness</label>
								<input id="filter-steepness-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-steepness-slider" class="disabled" type="range" min="1" max="1000" value="1" step="3.996" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">X-Offset</label>
								<input id="filter-x_offset-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-x_offset-slider" class="disabled" type="range" min="0" max="3000" value="0" step="12" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Kink</label>
								<input id="filter-kink-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-kink-slider" class="disabled" type="range" min="0" max="2000" value="0" step="8" disabled="disabled" />
							</div>
						</div>
						<div style="float:right;width:48%;">
							<div class="sundry-control">
								<label class="disabled unselectable">Distortion</label>
								<input id="filter-distort-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-distort-slider" class="disabled" type="range" min="0" max="20" value="0" step="0.08" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Dist. Offset</label>
								<input id="filter-distortOffset-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-distortOffset-slider" class="disabled" type="range" min="0" max="200000" value="0" step="800" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Dist. Scale</label>
								<input id="filter-distortScale-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-distortScale-slider" class="disabled" type="range" min="0" max="300" value="0" step="0.16" disabled="disabled" />
							</div>
							<div class="sundry-control">
								<label class="disabled unselectable">Dist. Threshold</label>
								<input id="filter-distortThreshold-edit" class="disabled" type="text" maxlength="12" onkeypress="NumericInput(event)" disabled="disabled" />
								<input id="filter-distortThreshold-slider" class="disabled" type="range" min="0" max="4000" value="0" step="16" disabled="disabled" />
							</div>
						</div>
						<div id="filter-revisions">
							<button id="filter-r2" class="disabled" disabled="disabled">R2</button>
							<button id="filter-r3" class="disabled" disabled="disabled">R3</button>
							<button id="filter-r4" class="disabled" disabled="disabled">R4</button>
						</div>
					</form>
					<div id="filter-websid" class="sundryMsg" style="display:none;">This tab requires the <button class="set-websid">WebSid</button> emulator.</div>
				</div>
				<div id="stopic-stereo" class="stopic" style="display:none;">
					<?php // Sundry tab: Stereo controls for WebSid ?>
					<div id="stereo-websid">
						<table>
							<tr>
								<td class="stereo-header">
									<span id="stereo-sh1"><b>SID 1</b></span>
								</td>
								<td class="stereo-s1">
									<label class="voice unselectable">Voice 1</label><br />
									<div id="stereo-s1v1-scope" class="stereo-scope"><label class="stereo-letter left unselectable">L</label><input id="stereo-s1v1-slider" type="range" min="0" max="100" value="50" step="1" /><label class="stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s1">
									<label class="voice unselectable">Voice 2</label><br />
									<div id="stereo-s1v2-scope" class="stereo-scope"><label class="stereo-letter left unselectable">L</label><input id="stereo-s1v2-slider" type="range" min="0" max="100" value="50" step="1" /><label class="stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s1">
									<label class="voice unselectable">Voice 3</label><br />
									<div id="stereo-s1v3-scope" class="stereo-scope"><label class="stereo-letter left unselectable">L</label><input id="stereo-s1v3-slider" type="range" min="0" max="100" value="50" step="1" /><label class="stereo-letter right unselectable">R</label></div>
								</td>
							</tr>
							<tr>
								<td class="stereo-header">
									<span id="stereo-sh2" class="disabled"><b>SID 2</b></span>
								</td>
								<td class="stereo-s2">
									<label class="disabled voice unselectable">Voice 1</label><br />
									<div id="stereo-s2v1-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s2v1-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s2">
									<label class="disabled voice unselectable">Voice 2</label><br />
									<div id="stereo-s2v2-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s2v2-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s2">
									<label class="disabled voice unselectable">Voice 3</label><br />
									<div id="stereo-s2v3-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s2v3-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
							</tr>
							<tr>
								<td class="stereo-header">
									<span id="stereo-sh3" class="disabled"><b>SID 3</b></span>
								</td>
								<td class="stereo-s3">
									<label class="disabled voice unselectable">Voice 1</label><br />
									<div id="stereo-s3v1-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s3v1-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s3">
									<label class="disabled voice unselectable">Voice 2</label><br />
									<div id="stereo-s3v2-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s3v2-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
								<td class="stereo-s3">
									<label class="disabled voice unselectable">Voice 3</label><br />
									<div id="stereo-s3v3-scope" class="stereo-scope"><label class="disabled stereo-letter left unselectable">L</label><input id="stereo-s3v3-slider" class="disabled" type="range" min="0" max="100" value="50" step="1" disabled="disabled" /><label class="disabled stereo-letter right unselectable">R</label></div>
								</td>
							</tr>
						</table>
						<div class="edit" style="margin-top:6px;padding-right:0;">
							<label class="unselectable" style="position:relative;top:-1px;margin-right:4px;">Mode</label>
							<select id="dropdown-stereo-mode" name="select-stereo-mode" style="position:relative;top:-1px;">
								<option value="-1" selected="selected">No stereo</option>
								<option value="0">Enhance off</option>
								<option value="16384">Low enhance</option>
								<option value="24576">Medium enhance</option>
								<option value="32767">High enhance</option>
							</select>
							<div style="display:inline-block;margin-left:20px;">
								<input type="checkbox" id="stereo-headphones" name="hptoggle" class="unselectable" unchecked /><label for="stereo-headphones" class="unselectable" title="Headphones"><svg id="svg-headphones" height="16" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 32C114.52 32 0 146.496 0 288v48a32 32 0 0 0 17.689 28.622l14.383 7.191C34.083 431.903 83.421 480 144 480h24c13.255 0 24-10.745 24-24V280c0-13.255-10.745-24-24-24h-24c-31.342 0-59.671 12.879-80 33.627V288c0-105.869 86.131-192 192-192s192 86.131 192 192v1.627C427.671 268.879 399.342 256 368 256h-24c-13.255 0-24 10.745-24 24v176c0 13.255 10.745 24 24 24h24c60.579 0 109.917-48.098 111.928-108.187l14.382-7.191A32 32 0 0 0 512 336v-48c0-141.479-114.496-256-256-256z"/></svg></label>
							</div>
							<label class="unselectable" style="margin-right:8px;">Reverb</label><input id="stereo-reverb-slider" style="position:relative;top:3px;width:60px;" type="range" min="0" max="100" value="100" step="1" />
						</div>
					</div>
					<?php // Sundry tab: Stereo controls for JSIDPLAY2 ?>
					<div id="stereo-jsidplay2">
						<table>
							<tr>
								<td class="stereo-header">
									<span id="stereo-jp2-bh"><b>Balance</b></span>
								</td>
								<td class="stereo-jp2-b1 stereo-cell">
									<label class="voice unselectable">SID chip 1</label><br />
									<label class="stereo-letter left unselectable">L</label><input id="stereo-jp2-b1-slider" type="range" min="0" max="1" value="0.3" step="0.1" /><label class="stereo-letter right unselectable">R</label>
								</td>
								<td class="stereo-jp2-b2 stereo-cell">
									<label class="voice unselectable">SID chip 2</label><br />
									<label class="stereo-letter left unselectable">L</label><input id="stereo-jp2-b2-slider" type="range" min="0" max="1" value="0.7" step="0.1" /><label class="stereo-letter right unselectable">R</label>
								</td>
								<td class="stereo-jp2-b3 stereo-cell">
									<label class="voice unselectable">SID chip 3</label><br />
									<label class="stereo-letter left unselectable">L</label><input id="stereo-jp2-b3-slider" type="range" min="0" max="1" value="0.5" step="0.1" /><label class="stereo-letter right unselectable">R</label>
								</td>
							</tr>
							<tr>
								<td class="stereo-header">
									<span id="stereo-jp2-dh"><b>Delay</b></span>
								</td>
								<td class="stereo-jp2-d1 stereo-cell">
									<label class="voice unselectable">SID chip 1</label><br />
									<label class="stereo-letter-dense left-dense unselectable">0 ms&nbsp;&nbsp;</label><input id="stereo-jp2-d1-slider" type="range" min="0" max="50" value="10" step="1" /><label class="stereo-letter-dense right unselectable">50</label>
								</td>
								<td class="stereo-jp2-d2 stereo-cell">
									<label class="voice unselectable">SID chip 2</label><br />
									<label class="stereo-letter-dense left-dense unselectable">0 ms&nbsp;&nbsp;</label><input id="stereo-jp2-d2-slider" type="range" min="0" max="50" value="0" step="1" /><label class="stereo-letter-dense right unselectable">50</label>
								</td>
								<td class="stereo-jp2-d3 stereo-cell">
									<label class="voice unselectable">SID chip 3</label><br />
									<label class="stereo-letter-dense left-dense unselectable">0 ms&nbsp;&nbsp;</label><input id="stereo-jp2-d3-slider" type="range" min="0" max="50" value="0" step="1" /><label class="stereo-letter-dense right unselectable">50</label>
								</td>
							</tr>
						</table>
						<div class="edit" style="margin-top:6px;padding-right:0;">
							<label class="unselectable" style="position:relative;top:0;margin-right:4px;">Stereo mode</label>
							<select id="dropdown-jp2-stereo-mode" name="select-jp2-stereo-mode">
								<option value="AUTO" selected="selected">Auto</option>
								<option value="STEREO">Stereo</option>
								<option value="THREE_SID">3SID</option>
							</select>
							<div style="display:inline-block;position:relative;top:2px;margin-left:15px;">
								<input type="checkbox" id="stereo-fake" name="faketoggle" class="unselectable" unchecked /><label for="stereo-fake" class="unselectable">Fake stereo, using</label>
							</div>
							<select id="dropdown-jp2-fake-read" name="select-jp2-fake-read" style="position:relative;left:-13px;">
								<option value="FIRST_SID" selected="selected">1st SID chip</option>
								<option value="SECOND_SID">2nd SID chip</option>
								<option value="THIRD_SID">3rd SID chip</option>
							</select>
						</div>
					</div>
					<div id="stereo-message" class="sundryMsg" style="display:none;">This tab requires the <button class="set-websid">WebSid</button> or the <button class="set-jsidplay2">JSIDPlay2</button> emulator.</div>
				</div>
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
					<?php if (MiniPlayer() == 2): // Wider subtune buttons for Chris Abbott ?>
						<div class="button-area">
							<button id="subtune-minus" class="button-ctrls button-lady button-idle disabled">
								<svg height="28" style="position:relative;top:-1px;left:-2px;transform:rotate(90deg);" viewBox="0 0 48 48"><path d="M14.83 16.42l9.17 9.17 9.17-9.17 2.83 2.83-12 12-12-12z"/><path d="M0-.75h48v48h-48z" fill="none"/></svg>
							</button>
						</div>
						<div class="button-area">
							<div class="button-tag" style="position:relative;left:-36px;white-space:nowrap;">&mdash;&mdash; Subtune &mdash;&mdash;</div>
							<div id="subtune-value" class="button-counter disabled" style="position:relative;top:18px;font-size:14px;"></div>
						</div>
						<div class="button-area">
							<button id="subtune-plus" class="button-ctrls button-lady button-idle disabled" style="position:absolute;bottom:0;">
								<svg height="28"  style="position:relative;top:-1px;right:-2px;transform:rotate(90deg);" viewBox="0 0 48 48"><path d="M14.83 30.83l9.17-9.17 9.17 9.17 2.83-2.83-12-12-12 12z"/><path d="M0 0h48v48h-48z" fill="none"/></svg>
							</button>
						</div>
					<?php else : // Normal subtune buttons and also Prev/Next buttons ?>
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
					<?php endif ?>
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

			<div id="songs"<?php if (MiniPlayer()) echo ' style="visibility:hidden;"'; ?>>
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
				<div id="folders" tabindex="0"><table></table></div>
				<img id="loading" class="loading-spinner" src="images/loading.svg" style="display:none;" alt="" />
				<div id="search">
					<select id="dropdown-search" name="search-type">
						<option value="#all#">All</option>
						<option value="fullname">Filename</option>
						<option value="author">Author</option>
						<option value="copyright">Copyright</option>
						<option value="player">Player</option>
						<option value="location">Location</option>
						<option value="maximum">Maximum</option>
						<option value="type">Type</option>
						<option value="tag">Tags</option>
						<option value="stil">STIL</option>
						<option value="rating">Rating</option>
						<option value="country">Country</option>
						<option value="new">Version</option>
						<option value="latest">Latest</option>
						<option value="folders" style="display:none;">Folders</option>
						<option value="gb64">Game</option>
						<option value="special" style="display:none;">Special</option>
					</select>
					<form onsubmit="return false;" autocomplete="off"><input type="text" name="search-box" id="search-box" maxlength="64" placeholder="Search..." /></form>
					<div id="search-here-container">
						<input type="checkbox" id="search-here" name="shtoggle" class="unselectable" unchecked />
						<label for="search-here" class="unselectable">Here</label>
					</div>
					<button id="search-button" class="medium disabled" disabled="disabled">Search</button>
				</div>
			</div>
		</div>

		<?php if (!isMobile() && !MiniPlayer()): ?>

			<div id="dexter">
				<div id="sites" class="unselectable">
					<div style="float:left;margin-left:1px;text-align:left;">
						<a id="home" href="<?php echo HOST; ?>" target="_top">Home</a>
							<span>&#9642</span>
						<a id="recommended" href="#">Recommended</a>
							<span>&#9642</span>
						<a id="players" href="#">Players</a>
							<span>&#9642</span>
						<a id="forum" href="#">Forum</a>
					</div>
					<!--<a href="https://blog.chordian.net/2018/05/12/deepsid/" target="_blank">Blog Post</a>
						<span>&#9642</span>-->
					<a href="https://www.facebook.com/groups/deepsid/" target="_blank">Facebook</a>
						<span>&#9642</span>
					<!--<a href="https://www.lemon64.com/forum/viewtopic.php?t=68056" target="_blank">Lemon64</a>
						<span>&#9642</span>-->
					<a href="https://bsky.app/profile/chordian.bsky.social" target="_blank">Bluesky</a>
						<span>&#9642</span>
					<a rel="me" href="https://mastodon.social/@chordian" target="_blank">Mastodon</a>
						<span>&#9642</span>
					<a href="http://csdb.chordian.net/?type=forums&roomid=14&topicid=129712" target="_blank">CSDb</a>
						<span>&#9642</span>
					<a href="https://github.com/Chordian/deepsid" target="_blank">GitHub</a>
				</div>
				<div id="tabs">
					<div class="tab unselectable" data-topic="profile" id="tab-profile">Profile</div>
					<div class="tab unselectable" data-topic="csdb" id="tab-csdb">CSDb<div id="note-csdb" class="notification csdbcolor"></div></div>
					<div class="tab unselectable" data-topic="gb64" id="tab-gb64">GB64<div id="note-gb64" class="notification gb64color"></div></div>
					<div class="tab unselectable" data-topic="remix" id="tab-remix">Remix<div id="note-remix" class="notification remixcolor"></div></div>
					<div class="tab unselectable" data-topic="player" id="tab-player">Player<div id="note-player" class="notification playercolor"></div></div>
					<div class="tab unselectable" data-topic="stil" id="tab-stil">STIL</div>
					<div class="tab unselectable" data-topic="visuals" id="tab-visuals">Visuals</div>
					<div class="tab right unselectable" data-topic="settings" id="tab-settings" style="width:26px;">
						<svg height="12px" width="12px" style="position:relative;top:-5px;" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill="none" fill-rule="evenodd" stroke="none" stroke-width="1"><g class="g2" transform="translate(-464.000000, -380.000000)"><g transform="translate(464.000000, 380.000000)"><path d="M17.4,11 C17.4,10.7 17.5,10.4 17.5,10 C17.5,9.6 17.5,9.3 17.4,9 L19.5,7.3 C19.7,7.1 19.7,6.9 19.6,6.7 L17.6,3.2 C17.5,3.1 17.3,3 17,3.1 L14.5,4.1 C14,3.7 13.4,3.4 12.8,3.1 L12.4,0.5 C12.5,0.2 12.2,0 12,0 L8,0 C7.8,0 7.5,0.2 7.5,0.4 L7.1,3.1 C6.5,3.3 6,3.7 5.4,4.1 L3,3.1 C2.7,3 2.5,3.1 2.3,3.3 L0.3,6.8 C0.2,6.9 0.3,7.2 0.5,7.4 L2.6,9 C2.6,9.3 2.5,9.6 2.5,10 C2.5,10.4 2.5,10.7 2.6,11 L0.5,12.7 C0.3,12.9 0.3,13.1 0.4,13.3 L2.4,16.8 C2.5,16.9 2.7,17 3,16.9 L5.5,15.9 C6,16.3 6.6,16.6 7.2,16.9 L7.6,19.5 C7.6,19.7 7.8,19.9 8.1,19.9 L12.1,19.9 C12.3,19.9 12.6,19.7 12.6,19.5 L13,16.9 C13.6,16.6 14.2,16.3 14.7,15.9 L17.2,16.9 C17.4,17 17.7,16.9 17.8,16.7 L19.8,13.2 C19.9,13 19.9,12.7 19.7,12.6 L17.4,11 L17.4,11 Z M10,13.5 C8.1,13.5 6.5,11.9 6.5,10 C6.5,8.1 8.1,6.5 10,6.5 C11.9,6.5 13.5,8.1 13.5,10 C13.5,11.9 11.9,13.5 10,13.5 L10,13.5 Z"/></g></g></g></svg>
					</div>
					<div class="tab right unselectable" data-topic="changes" id="tab-changes" style="width:80px;">Changes</div>
					<div class="tab right unselectable" data-topic="faq" id="tab-faq">FAQ</div>
					<div class="tab right unselectable" data-topic="about" id="tab-about">About</div>
				</div>
				<div id="sticky-csdb"><h2 style="margin-top:0;">CSDb</h2></div>
				<div id="sticky-gb64"><h2 style="margin-top:0;">GameBase64</h2></div>
				<div id="sticky-remix"><h2 style="margin-top:0;">Remix64</h2></div>
				<div id="sticky-player"><h2 style="margin-top:0;">Players / Editors</h2></div>
				<div id="sticky-stil"><h2 style="margin-top:0;">STIL / Lyrics</h2></div>
				<div id="sticky-visuals"><h2 style="margin-top:0;">Visuals</h2>
					<div class="visuals-buttons" data-selected-visual="">
						<button class="visuals-button icon-piano button-off" data-visual="piano">Piano</button>
						<button class="visuals-button icon-graph button-on" data-visual="graph">Graph</button>
						<button class="visuals-button icon-memory button-on" data-visual="memory">Memo</button>
						<button class="visuals-button icon-stats button-on" data-visual="stats">Stats</button>
					</div>
					<img class="waveform-colors" src="images/waveform_colors.png" alt="Waveform Colors" />
					<div id="sticky-right-buttons">
						<span id="visuals-toggle">
							<label for="tab-visuals-toggle" class="unselectable" style="margin-right:1px;">Enabled</label>
							<button id="tab-visuals-toggle" class="button-edit button-toggle button-on">On</button>
							<span class="viz-warning viz-msg-enable" style="position:relative;top:-1px;"> <img src="images/composer_arrowleft.svg" style="position:relative;top:5px;height:18px;" alt="" /> Click this to enable the visuals</span>
						</span>
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
								<button class="button-edit button-radio button-off viz-emu viz-resid" data-group="viz-emu" data-emu="resid">ReSID</button>
								<button class="button-edit button-radio button-off viz-emu viz-jsidplay2" data-group="viz-emu" data-emu="jsidplay2">JSIDPl2</button>
								<button class="button-edit button-radio button-off viz-emu viz-websid" data-group="viz-emu" data-emu="websid">WebSid</button>
								<button class="button-edit button-radio button-off viz-emu viz-legacy" data-group="viz-emu" data-emu="legacy">Legacy</button>
								<button class="button-edit button-radio button-off viz-emu viz-hermit" data-group="viz-emu" data-emu="hermit">Hermit</button>
								<span class="viz-warning viz-msg-emu" style="position:relative;top:-1px;"> <img src="images/composer_arrowleft.svg" style="position:relative;top:5px;height:18px;" alt="" /> You need one of these</span>
								<span class="viz-warning viz-msg-buffer" style="position:relative;top:-1px;">Decrease if too slow <img src="images/composer_arrowright.svg" style="position:relative;top:4px;height:18px;" alt="" /></span>
								<div class="viz-buffer">
									<label for="dropdown-piano-buffer" class="dropdown-buffer-label unselectable">Buffer size</label>
									<select id="dropdown-piano-buffer" class="dropdown-buffer">
										<!--<option value="256">256</option>
										<option value="512">512</option>-->
										<option value="1024">1024</option>
										<option value="2048">2048</option>
										<option value="4096">4096</option>
										<option value="8192">8192</option>
										<option value="16384" selected="selected">16384</option>
										<option class="jsidplay2" value="24000" style="display:none;">24000</option>
										<option class="jsidplay2" value="32000" style="display:none;">32000</option>
										<option class="jsidplay2" value="40000" style="display:none;">40000</option>
										<option class="jsidplay2" value="48000" style="display:none;">48000</option>
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
							<h3 style="display:inline-block;margin-top:16px;">Help</h3><button id="info-piano-button" style="position:relative;top:-2px;left:8px;width:60px;">SHOW</button>
							<div id="info-piano-text" style="display:none;">
								<p>
									If the playback is choppy, try increasing the buffer size. Smaller values mean faster and
									smoother updating (1024 is the lowest possible) but also require a fast computer with a nifty web browser.
								</p>
								<p>
									The piano is always updated as fast as possible when using the normal WebSid emulator, regardless of buffer
									size. However, larger buffer sizes may introduce flickering notes. Decrease the buffer size to avoid this.
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
									legacy WebSid, but it is not reflected on this page.)
								</p>
								<p>
									2SID and 3SID tunes are supported. Each keyboard will automatically combine
									to host an entire chip (i.e. 3 voices). The square voice buttons will toggle entire SID
									chips ON or OFF when playing these types of tunes.
								</p>
								<p>If you want to "solo" a voice/chip, hold down <code>Shift</code> while pressing the hotkey.</p>
							</div>
						</div>

						<div id="visuals-graph" class="visuals" style="display:none;">
							<div class="edit" style="height:42px;width:683px;">
								<label class="unselectable" style="margin-right:2px;">Emulator</label>
								<button class="button-edit button-radio button-off viz-emu viz-resid" data-group="viz-emu" data-emu="resid">ReSID</button>
								<button class="button-edit button-radio button-off viz-emu viz-jsidplay2" data-group="viz-emu" data-emu="jsidplay2">JSIDPl2</button>
								<button class="button-edit button-radio button-off viz-emu viz-websid" data-group="viz-emu" data-emu="websid">WebSid</button>
								<button class="button-edit button-radio button-off viz-emu viz-legacy" data-group="viz-emu" data-emu="legacy">Legacy</button>
								<button class="button-edit button-radio button-off viz-emu viz-hermit" data-group="viz-emu" data-emu="hermit">Hermit</button>
								<span class="viz-warning viz-msg-emu" style="position:relative;top:-1px;"> <img src="images/composer_arrowleft.svg" style="position:relative;top:5px;height:18px;" alt="" /> You need one of these</span>
								<span class="viz-warning viz-msg-buffer" style="position:relative;top:-1px;">Decrease if too slow <img src="images/composer_arrowright.svg" style="position:relative;top:4px;height:18px;" alt="" /></span>
								<div class="viz-buffer">
									<label for="dropdown-graph-buffer" class="dropdown-buffer-label unselectable">Buffer size</label>
									<select id="dropdown-graph-buffer" class="dropdown-buffer">
										<!--<option value="256">256</option>
										<option value="512">512</option>-->
										<option value="1024">1024</option>
										<option value="2048">2048</option>
										<option value="4096">4096</option>
										<option value="8192">8192</option>
										<option value="16384" selected="selected">16384</option>
										<option class="jsidplay2" value="24000" style="display:none;">24000</option>
										<option class="jsidplay2" value="32000" style="display:none;">32000</option>
										<option class="jsidplay2" value="40000" style="display:none;">40000</option>
										<option class="jsidplay2" value="48000" style="display:none;">48000</option>
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

						<div id="visuals-stats" class="visuals" style="display:none;">
							<table id="table-stats">
								<tr>
									<th class="stats-bg stats-1">Voice 1<button id="stats-solo-1" class="stats-solo button-edit button-toggle button-off">Solo</button><div id="stats-h-1"><span></span></div></th>
									<th class="stats-bg stats-2">Voice 2<button id="stats-solo-2" class="stats-solo button-edit button-toggle button-off">Solo</button><div id="stats-h-2"><span></span></div></th>
									<th class="stats-bg stats-3">Voice 3<button id="stats-solo-3" class="stats-solo button-edit button-toggle button-off">Solo</button><div id="stats-h-3"><span></span></div></th>
								</tr>
								<tr>
									<td class="stats-bg stats-1">
										<div id="stats-v1-1-V">Probably uses vibrato (or slide)<span></span></div>
										<div id="stats-v1-3-P">Repeatedly changes pulse width<span></span></div>

										<div id="stats-v1-4-1" class="stats-space">Uses $1x waveform (triangle)<span></span></div>
										<div id="stats-v1-4-2">Uses $2x waveform (sawtooth)<span></span></div>
										<div id="stats-v1-4-3">Uses $3x waveform (tri+saw)<span></span></div>
										<div id="stats-v1-4-4">Uses $4x waveform (pulse)<span></span></div>
										<div id="stats-v1-4-5">Uses $5x waveform (tri+pulse)<span></span></div>
										<div id="stats-v1-4-6">Uses $6x waveform (saw+pulse)<span></span></div>
										<div id="stats-v1-4-7">Uses $7x waveform (tri+saw+pulse)<span></span></div>
										<div id="stats-v1-4-8">Uses $8x waveform (noise)<span></span></div>

										<div id="stats-v1-4-T" class="stats-space">Uses the test bit<span></span></div>
										<div id="stats-v1-4-X">Uses an illegal waveform<span></span></div>

										<div id="stats-v1-4-H" class="stats-space">Uses hard synchronization<span></span></div>
										<div id="stats-v1-4-R">Uses ring modulation<span></span></div>
										<div id="stats-v1-4-M">Uses both combined<span></span></div>

										<div id="stats-v1-6-A" class="stats-space">Repeatedly changes the ADSR<span></span></div>
									</td>
									<td class="stats-bg stats-2">
										<div id="stats-v2-1-V">Probably uses vibrato (or slide)<span></span></div>
										<div id="stats-v2-3-P">Repeatedly changes pulse width<span></span></div>

										<div id="stats-v2-4-1" class="stats-space">Uses $1x waveform (triangle)<span></span></div>
										<div id="stats-v2-4-2">Uses $2x waveform (sawtooth)<span></span></div>
										<div id="stats-v2-4-3">Uses $3x waveform (tri+saw)<span></span></div>
										<div id="stats-v2-4-4">Uses $4x waveform (pulse)<span></span></div>
										<div id="stats-v2-4-5">Uses $5x waveform (tri+pulse)<span></span></div>
										<div id="stats-v2-4-6">Uses $6x waveform (saw+pulse)<span></span></div>
										<div id="stats-v2-4-7">Uses $7x waveform (tri+saw+pulse)<span></span></div>
										<div id="stats-v2-4-8">Uses $8x waveform (noise)<span></span></div>

										<div id="stats-v2-4-T" class="stats-space">Uses the test bit<span></span></div>
										<div id="stats-v2-4-X">Uses an illegal waveform<span></span></div>

										<div id="stats-v2-4-H" class="stats-space">Uses hard synchronization<span></span></div>
										<div id="stats-v2-4-R">Uses ring modulation<span></span></div>
										<div id="stats-v2-4-M">Uses both combined<span></span></div>

										<div id="stats-v2-6-A" class="stats-space">Repeatedly changes the ADSR<span></span></div>
									</td>
									<td class="stats-bg stats-3">
										<div id="stats-v3-1-V">Probably uses vibrato (or slide)<span></span></div>
										<div id="stats-v3-3-P">Repeatedly changes pulse width<span></span></div>

										<div id="stats-v3-4-1" class="stats-space">Uses $1x waveform (triangle)<span></span></div>
										<div id="stats-v3-4-2">Uses $2x waveform (sawtooth)<span></span></div>
										<div id="stats-v3-4-3">Uses $3x waveform (tri+saw)<span></span></div>
										<div id="stats-v3-4-4">Uses $4x waveform (pulse)<span></span></div>
										<div id="stats-v3-4-5">Uses $5x waveform (tri+pulse)<span></span></div>
										<div id="stats-v3-4-6">Uses $6x waveform (saw+pulse)<span></span></div>
										<div id="stats-v3-4-7">Uses $7x waveform (tri+saw+pulse)<span></span></div>
										<div id="stats-v3-4-8">Uses $8x waveform (noise)<span></span></div>

										<div id="stats-v3-4-T" class="stats-space">Uses the test bit<span></span></div>
										<div id="stats-v3-4-X">Uses an illegal waveform<span></span></div>

										<div id="stats-v3-4-H" class="stats-space">Uses hard synchronization<span></span></div>
										<div id="stats-v3-4-R">Uses ring modulation<span></span></div>
										<div id="stats-v3-4-M">Uses both combined<span></span></div>

										<div id="stats-v3-6-A" class="stats-space">Repeatedly changes the ADSR<span></span></div>
									</td>
								</tr>
							</table>
							<table id="table-global-stats">
								<tr>
									<th colspan="2">Global</th>
								</tr>
								<tr>
									<td>
										<div id="stats-global-C">Repeatedly changes filter cutoff<span></span></div>

										<div id="stats-global-1" class="stats-space">Uses filtering in voice 1<span></span></div>
										<div id="stats-global-2">Uses filtering in voice 2<span></span></div>
										<div id="stats-global-3">Uses filtering in voice 3<span></span></div>

										<div id="stats-global-R" class="stats-space">Repeatedly changes filter resonance<span></span></div>
										<div id="stats-global-O">Uses resonance values other than 0 or F<span></span></div>

										<div id="stats-global-V" class="stats-space">Repeatedly changes volume<span></span></div>
									</td>
									<td>
										<div id="stats-fmode-1">Uses $1x filter mode (Low-Pass)<span></span></div>
										<div id="stats-fmode-2">Uses $2x filter mode (Band-Pass)<span></span></div>
										<div id="stats-fmode-3">Uses $3x filter mode (LP+BP)<span></span></div>
										<div id="stats-fmode-4">Uses $4x filter mode (High-Pass)<span></span></div>
										<div id="stats-fmode-5">Uses $5x filter mode (LP+HP)<span></span></div>
										<div id="stats-fmode-6">Uses $6x filter mode (BP+HP)<span></span></div>
										<div id="stats-fmode-7">Uses $7x filter mode (LP+BP+HP)<span></span></div>

										<div id="stats-global-M" class="stats-space">Mutes voice 3<span></span></div>
										<div id="stats-global-I">Sets the external input bit<span></span></div>
									</td>
								</tr>
							</table>
							<div id="stats-notes">
								<h3 style="display:inline-block;margin:0;">Notes</h3>
								<p style="margin-top:10px;">Use the smallest buffer size (1024) for best effect. Especially the vibrato detector needs this.</p>
								<p>Lines detecting repeated changes typically requires about four unique values to occur.</p>
								<p>Using illegal waveform to lock noise, then unlock it with test bit, can be used to create <a href="//deepsid.chordian.net/?file=SID%20Happens/2020/Example_Test_Bit_Noise.sid">unique sounds</a>.</p>
								<p>Only the first SID chip is examined.</p>
							</div>
						</div>

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
							<a href="http://www.c64music.co.uk/" target="_blank">Compute's Gazette SID Collection</a>.</i>
						</p>
					</div>

					<div id="topic-gb64" class="topic ext" style="display:none;">
						<p>This tab will show links to game entries in GameBase64 as you click SID files that were
							used in at least one C64 game, released or unreleased (these are listed as a preview).</p>
						<p>
							<a href="https://gb64.com/" target="_blank">GameBase64</a> is a large database
							for C64 games with credits, details and screenshots.
						</p>
						<br />
						<p>
							<i>This does not work in
							<a href="http://www.c64music.co.uk/" target="_blank">Compute's Gazette SID Collection</a>.</i>
						</p>
					</div>

					<div id="topic-player" class="topic ext" style="display:none;">
						<p>If available, this tab will show information about the editor/player that made the song.</p>
					</div>

					<div id="topic-stil" class="topic ext" style="display:none;">
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
							For more information about STIL, please refer to <a href="https://www.hvsc.c64.org/download/C64Music/DOCUMENTS/STIL.faq" target="_blank">this FAQ</a>.
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

								<div class="space splitline"></div>

								<h3>SID handler
									<select id="dropdown-settings-emulator" name="select-settings-emulator">
										<option value="resid">reSID (BETA)</option>
										<!--<option value="resid">reSID (WebSidPlay)</option>-->
										<option value="jsidplay2">JSIDPlay2</option>
										<option value="websid">WebSid emulator</option>
										<option value="legacy">WebSid (Legacy)</option>
										<option value="hermit">Hermit's (+FM)</option>
										<option value="webusb">WebUSB (Hermit)</option>
										<option value="asid">ASID (MIDI)</option>
										<option value="lemon">Lemon's MP3 files</option>
										<option value="youtube">YouTube videos</option>
										<option value="download">Download SID file</option>
										<option value="silence">No SID handler</option>
									</select>
								</h3>

								<p>These are settings that applies to the currently selected SID handler.</p>

								<h4>Buffer size</h4>
								<p>If the currently selected SID handler supports it, you can change its buffer size here.
									Higher values can reduce stuttering or eliminate it entirely.
									However, some emulators also update the <b>Visuals</b> tab slowly when using higher values.
								</p>

								<select id="dropdown-settings-buffer" class="dropdown-buffer">
									<!--<option value="256">256</option>
									<option value="512">512</option>-->
									<option value="1024">1024</option>
									<option value="2048">2048</option>
									<option value="4096">4096</option>
									<option value="8192">8192</option>
									<option value="16384" selected="selected">16384</option>
									<option class="jsidplay2" value="24000" style="display:none;">24000</option>
									<option class="jsidplay2" value="32000" style="display:none;">32000</option>
									<option class="jsidplay2" value="40000" style="display:none;">40000</option>
									<option class="jsidplay2" value="48000" style="display:none;">48000</option>
								</select>
								<label for="dropdown-settings-buffer" class="dropdown-buffer-label unselectable">Buffer size <span id="settings-emu-msg" style="display:none;">for <span id="settings-emu-type">?</span> emulator<span></label>

								<h4>Advanced settings</h4>
								<p>This section will change if you select a different SID handler.</p>
								<div class="settings-advanced-resid settings-advanced-websid settings-advanced-legacy settings-advanced-hermit settings-advanced-asid settings-advanced-lemon settings-advanced-youtube settings-advanced-download settings-advanced-silence settings-advanced">
									<label class="dropdown-unstyled-label unselectable">There are no advanced settings for this SID handler.</label>
								</div>
								<div class="settings-advanced-jsidplay2 settings-advanced">
									<select id="dropdown-adv-jsidplay2-defemu" class="dropdown-unstyled">
										<option value="RESID" selected="selected">reSID</option>
										<option value="RESIDFP">reSIDfp</option>
									</select>
									<label for="dropdown-adv-jsidplay2-defemu" class="dropdown-unstyled-label unselectable">Default emulation</label>

									<select id="dropdown-adv-jsidplay2-sampmethod" class="dropdown-unstyled" style="margin-left:10px;">
										<option value="DECIMATE" selected="selected">Decimate</option>
										<option value="RESAMPLE">Resample</option>
									</select>
									<label for="dropdown-adv-jsidplay2-sampmethod" class="dropdown-unstyled-label unselectable">Sampling method</label><span style="font-family:'Asap Condensed',sans-serif;font-size:14px;">(Resample is better quality but consumes more CPU time)</span>

									<div class="space"></div>

									<div id="filname-jsidplay2-resid" style="display:none;">
										<select id="dropdown-adv-jsidplay2-fil6581resid" class="dropdown-unstyled">
											<option>FilterLightest6581</option>
											<option>FilterLighter6581</option>
											<option>FilterLight6581</option>
											<option selected="selected">FilterAverage6581</option>
											<option>FilterDark6581</option>
											<option>FilterDarker6581</option>
											<option>FilterDarkest6581</option>
										</select>
										<label for="dropdown-adv-jsidplay2-fil6581resid" class="dropdown-unstyled-label unselectable">Filter name (6581)</label>

										<select id="dropdown-adv-jsidplay2-fil8580resid" class="dropdown-unstyled" style="margin-left:10px;">
											<option>FilterLight8580</option>
											<option selected="selected">FilterAverage8580</option>
											<option>FilterDark8580</option>
										</select>
										<label for="dropdown-adv-jsidplay2-fil8580resid" class="dropdown-unstyled-label unselectable">Filter name (8580)</label>
									</div>

									<div id="filname-jsidplay2-residfp" style="display:none;">
										<select id="dropdown-adv-jsidplay2-fil6581residfp" class="dropdown-unstyled">
											<option>FilterReSID6581</option>
											<option selected="selected">FilterAlankila6581R4AR_3789</option>
											<option>FilterAlankila6581R3_3984_1</option>
											<option>FilterAlankila6581R3_3984_2</option>
											<option>FilterLordNightmare6581R3_4285</option>
											<option>FilterLordNightmare6581R3_4485</option>
											<option>FilterLordNightmare6581R4_1986S</option>
											<option>FilterZrX6581R3_0384</option>
											<option>FilterZrX6581R3_1984</option>
											<option>FilterZrx6581R3_3684</option>
											<option>FilterZrx6581R3_3985</option>
											<option>FilterZrx6581R4AR_2286</option>
											<option>FilterTrurl6581R3_0784</option>
											<option>FilterTrurl6581R3_0486S</option>
											<option>FilterTrurl6581R3_3384</option>
											<option>FilterTrurl6581R3_4885</option>
											<option>FilterTrurl6581R4AR_3789</option>
											<option>FilterTrurl6581R4AR_4486</option>
											<option>FilterNata6581R3_2083</option>
											<option>FilterGrue6581R4AR_3488</option>
											<option>FilterKruLLo</option>
											<option>FilterEnigma6581R3_4885</option>
											<option>FilterEnigma6581R3_1585</option>
										</select>
										<label for="dropdown-adv-jsidplay2-fil6581residfp" class="dropdown-unstyled-label unselectable">Filter name (6581)</label>

										<select id="dropdown-adv-jsidplay2-fil8580residfp" class="dropdown-unstyled" style="margin-left:10px;">
											<option>FilterTrurl8580R5_1489</option>
											<option selected="selected">FilterTrurl8580R5_3691</option>
										</select>
										<label for="dropdown-adv-jsidplay2-fil8580residfp" class="dropdown-unstyled-label unselectable">Filter name (8580)</label>
									</div>
								</div>
							</div>
						<?php endif ?>
					</div>

					<div id="topic-about" class="topic" style="display:none;">
						<h2>About</h2>
						<p>
							DeepSID is an online SID player that can play music originally composed for the
							<a href="https://en.wikipedia.org/wiki/Commodore_64" target="_top">Commodore 64</a>, a home computer
							that was very popular back in the 80's and 90's. This computer had an amazing sound chip
							called <a href="https://en.wikipedia.org/wiki/MOS_Technology_SID" target="_top">SID</a>.
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
							Jens-Christian Huus (<a href="//chordian.net/" target="_top">Chordian</a>)<br />
							<a href="//blog.chordian.net/2018/05/12/deepsid/" target="_top">https://blog.chordian.net/2018/05/12/deepsid/</a><br />
							<a href="//blog.chordian.net/2022/05/07/the-story-of-deepsid/" target="_top">https://blog.chordian.net/2022/05/07/the-story-of-deepsid/</a>
						</p>
						<p>
							Scopes by Jürgen Wothke (<a href="http://www.wothke.ch/tinyrsid/index.php" target="_top">Tiny'R'Sid</a>)<br />
						</p>

						<h3>SID handlers</h3>
						<p>
							WebSid HQ and Legacy emulators by Jürgen Wothke (Tiny'R'Sid)<br />
							<a href="http://www.wothke.ch/tinyrsid/index.php" target="_top">http://www.wothke.ch/tinyrsid/index.php</a><br />
							<a href="http://www.wothke.ch/websid/" target="_top">http://www.wothke.ch/websid/</a>
						</p>
						<p>
							Web port of 'libsidplayfp' (WebSidPlay a.k.a. reSID) by Jürgen Wothke<br />
							<a href="https://github.com/libsidplayfp/libsidplayfp" target="_top">https://github.com/libsidplayfp/libsidplayfp</a><br />
							<a href="https://www.wothke.ch/websidplayfp/" target="_top">https://www.wothke.ch/websidplayfp/</a>
						</p>
						<p>
							JSIDPlay2 emulator by Ken Händel, Antti S. Lankila and Wilfred Bos<br />
							<a href="https://sourceforge.net/projects/jsidplay2/" target="_top">https://sourceforge.net/projects/jsidplay2/</a><br />
							<a href="https://haendel.ddns.net:8443/static/teavm/c64jukebox.vue" target="_top">https://haendel.ddns.net:8443/static/teavm/c64jukebox.vue</a><br />
						</p>
						<p>
							reSID engine (used by WebSidPlay and JSIDPlay2) by Dag Lem<br />
							SidTune work by Michael Schwendt<br />
							Main libsidplay2 code by Simon White<br />
							Distortion Simulation by Antti Lankila<br />
							Code refactoring by Leandro Nini
						</p>
						<p>
							jsSID emulator by Mihály Horváth (<a href="http://csdb.chordian.net/?type=scener&id=18806" target="_top">Hermit</a>)
						</p>
						<p>
							ASID (MIDI) implementation by Thomas Jansson<br />
							FM playback for Hermit's emulator by Thomas Jansson<br />
							<a href="https://github.com/thomasj" target="_top">https://github.com/thomasj</a><br />
							<a href="https://www.youtube.com/@tubesockor" target="_top">https://www.youtube.com/@tubesockor</a><br />
						</p>
						<p>
							WebUSB implementation by LouD<br/>
							<a href="https://github.com/LouDnl" target="_top">https://github.com/LouDnl</a><br />
							<a href="https://www.youtube.com/@LouDnl" target="_top">https://www.youtube.com/@LouDnl</a><br />
						</p>

						<p>
							OPL3 emulator (FM playback) by Adam Nielsen<br />
							<a href="https://github.com/Malvineous/opljs" target="_top">https://github.com/Malvineous/opljs</a><br />
						</p>

						<h3>Audio API library for MP3 files</h3>
						<p>
							Howler by James Simpson (<a href="https://goldfirestudios.com/">GoldFire Studios</a>)<br />
							<a href="https://github.com/goldfire/howler.js">https://github.com/goldfire/howler.js</a>
						</p>

						<h3>Libraries of SID tunes</h3>
						<p>
							High Voltage SID Collection #83<br />
							<a href="https://www.hvsc.c64.org/" target="_top">https://www.hvsc.c64.org/</a>
						</p>
						<p>
							Compute's Gazette SID Collection #146<br />
							<a href="http://www.c64music.co.uk/" target="_top">http://www.c64music.co.uk/</a>
						</p>

						<p>
							Kim Lemon's MP3 files (JSIDPlay2)<br />
							<a href="https://www.lemon64.com/" target="_top">https://www.lemon64.com/</a>
						</p>

						<h3>Remixes of SID tunes</h3>
						<p>
							Remix64 API by Markus Klein (<a href="https://markus-klein-artwork.de/music/" target="_top">LMan</a>)<br />
							<a href="https://www.remix64.com/" target="_top">https://www.remix64.com/</a>
						</p>
						<p>
							Hosting by Jan Lund Thomsen (QED)<br />
							<a href="http://remix.kwed.org/" target="_top">http://remix.kwed.org/</a>
						</p>


						<h3>Composer profile images</h3>
						<p>
							The images for composer profiles come from all over the internet. I have tried
							to be fair and not use images that the composer did not already have available on a personal
							web site, social media, interview, or another public place.
						</p>
						<ul>
							<li>Most are publically available profile images from Facebook or LinkedIn.</li>
							<li>A lot of older retro images (typically lo-res) are from the musicians photos download at <a href="https://gb64.com/downloads.php" target="_top">GameBase64</a>.</li>
							<li>Some were originally taken by Andreas Wallström (<a href="http://www.c64.com/" target="_top">C64.com</a>).</li>
							<li>A few were taken from the <a href="http://www.vgmpf.com/Wiki/index.php" target="_top">Video Game Music Preservation Foundation</a> wikipedia.</li>
							<li>Some from the <a href="https://8bitlegends.com/" target="_top">8BitLegends.com</a> web site.</li>
							<li>And several other places I can't remember anymore.</li>
						</ul>
						<p>
							If you feel you should be credited, let me know and I will add you to this section. Also, if
							you don't like an image of you here, just let me know and I will of course remove it. You are
							also welcome to send me a replacement image.
						</p>

						<h3>Other resources used</h3>
						<p>
							SIDId by Lasse Öörni (<a href="https://cadaver.github.io/" target="_top">Cadaver</a>)<br />
							<a href="http://csdb.chordian.net/?type=release&id=112201" target="_top">http://csdb.dk/release/?id=112201</a>
						</p>
						<p>
							SIDInfo by Matti Hämäläinen (ccr)<br />
							<a href="http://csdb.chordian.net/?type=release&id=164751" target="_top">https://csdb.dk/release/?id=164751</a><br />
							<a href="https://tnsp.org/hg/sidinfo/" target="_top">https://tnsp.org/hg/sidinfo/</a>
						</p>
						<p>
							Chartist.js by Gion Kunz (<a href="https://github.com/gionkunz" target="_top">GitHub</a>)<br />
							<a href="https://gionkunz.github.io/chartist-js/" target="_top">https://gionkunz.github.io/chartist-js/</a>
						</p>
					</div>

					<div id="topic-faq" class="topic" style="display:none;">
						<h2>Frequently Asked Questions</h2>

						<h3>How do I register?</h3>
						<p>
							The previous method of registering has been reworked for clarity. Now just click the 'Register' link
							above the user name and password boxes to begin the registration process.
						</p>
						<p>
							The annex box also has information about what you can do when logged in: <a class="annex-link" href="3">Registering</a>
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

						<h3>Where did the audio handlers for SOASC go?</h3>
						<p>
							The audio handlers for Stone Oakvalley's Authentic SID Collection were removed in September 2020.
							The connections to these real-time recordings were always spotty at best and later the reaction
							times also became painfully slow.
						</p>
						<p>
							I have repeatedly tried to fix the reaction times to no avail. It's a shame having to leave this
							library behind as it would have been nice with real-time recordings to complement the emulations,
							but I finally decided that the quality of the SOASC implementation was inadequate for DeepSID.
						</p>
						<p>
							As an alternative, try the <a class="set-lemon" href="#">Lemon's MP3 Files</a> handler instead.
						</p>

						<h3>Where did the Disqus tab go?</h3>
						<p>
							It was removed in late November 2020 together with all of its script code. It affected the performance
							of DeepSID, especially when triggering new SID tunes. Because this comment system was already rarely
							used by users, I decided to remove it altogether. The comments are still stored on their side and can
							be exported.
						</p>

						<h3>How do I make my own playlists?</h3>
						<p>
							You need to be using a mouse to create and manage playlists. This cannot be done on a mobile
							device (although you can enjoy your existing playlists there). Also, you must of course be
							logged in.
						</p>
						<p>
							The annex box has the basic instructions: <a class="annex-link" href="0">Playlists</a>
						</p>
						<p>
							Published playlists appear further up in the root and can be seen by everyone (even those that
							are not logged in) but you're still the only one that may edit it. When you enter a public
							playlist, you can see who made it.
						</p>

						<h3>What are those options in the top left drop-down box?</h3>
						<p>
							The annex box has a complete list: <a class="annex-link" href="4">SID handlers</a>
						</p>

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
						<p>
							The annex box can show you a list of all the search options: <a class="annex-link" href="5">Searching</a>
						</p>

						<h3>Can I turn voices on and off?</h3>
						<p>
							Yes. Use keys <code>1</code>, <code>2</code>, <code>3</code>
							and <code>4</code> or alternatively <code>q</code>, <code>w</code>, <code>e</code> and
							<code>r</code>. The first three are for the normal SID voices and the fourth is for toggling any
							digi stuff (WebSid (Legacy) emulator only).
						</p>
						<p>If you want to "solo" a voice, hold down <code>Shift</code> while pressing the hotkey.</p>
						<p>In the piano view, you can also click the green number buttons.</p>

						<h3>Any other hotkeys worth knowing about?</h3>
						<p>
							The annex box can show you a complete list: <a class="annex-link" href="7">Hotkeys</a>
						</p>
						<p>
							If you hold down <code>Shift</code> while clicking rating stars, you will clear them. (However,
							it's usually easier just to click the same star again if you want to clear the rating.)
						</p>

						<h3>Why doesn't this work in Internet Explorer?</h3>
						<p>
							The audio handlers all use an API called <i>Web Audio</i> which is
							<a href="https://caniuse.com/#search=web%20audio" target="_top">not supported by Internet Explorer</a>.
							You need a modern web browser to use this site.
						</p>

						<h3>Why can't I see the load/end addresses and size of the SID tune?</h3>
						<p>
							Actually, you can. The annex box has the answer: <a class="annex-link" href="1">Memory bar</a>
						</p>

						<h3>What URL parameters are available?</h3>
						<p>
							The annex box can show you a complete list: <a class="annex-link" href="8">URL parameters</a>
						</p>
						<p>
							An example to show a specific folder:<br />
							<a href="//deepsid.chordian.net?file=/MUSICIANS/J/JCH/">//deepsid.chordian.net?file=/MUSICIANS/J/JCH/</a>
						</p>
						<p>
							An example to play a SID tune:<br />
							<a href="//deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=hermit&subtune=2">//deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=hermit&subtune=2</a>
						</p>
						<p>
							An example to show a CSDb entry:<br />
							<a href="//deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519">//deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519</a>
						</p>

					</div>

					<div id="topic-changes" class="topic" style="display:none;">
						<h2>Changes</h2>

						<h3>July 1, 2025</h3>
						<ul>
							<li>All new files in HVSC #83 are now connected to CSDb entries.</li>
						</ul>

						<h3>June 30, 2025</h3>
						<ul>
							<li>Fixed missing database information for <a href="https://deepsid.chordian.net/?file=/MUSICIANS/B/Blues_Muz/Gallefoss_Glenn/N0s_Intro_83.sid">N0s_Intro_83.sid</a> by Glenn Rune Gallefoss.</li>
						</ul>

						<h3>June 29, 2025</h3>
						<ul>
							<li>The blue "Music" tag (used for music releases) is now displayed as a double note icon without its name.
								It also always appears first among the blue production tags. (To add the tag in the dialog box or search
								for it in the bottom, just use the "Music" name as usual.)</li>
							<li>Validated the look-up of all players with a player/editor page and fixed those that didn't work.</li>
						</ul>

						<h3>June 28, 2025</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #83.</li>
							<li>Added composer profiles for the new folders in HVSC #83.</li>
						</ul>

						<h3>June 27, 2025</h3>
						<ul>
							<li>In the CSDb tab, in addition to the usual rule where group names are highlighted in yellow or blue when they
								match the group in the tune's copyright line, group names may now also be highlighted in green if they match
								any other group the user has been a member of. This can sometimes make it easier to find the right entries.</li>
						</ul>

						<h3>June 26, 2025</h3>
						<ul>
							<li>Fixed FlexSID tunes not showing the relevant player/editor page.</li>
						</ul>

						<h3>June 25, 2025</h3>
						<ul>
							<li>The blue "Collection" tag now shows a double note icon too, since it's for music competitions.</li>
						</ul>

						<h3>June 23, 2025</h3>
						<ul>
							<li>The left and right arrow tag icons (introduced June 19) have been revoked, as they were too confusing.</li>
							<li>The "Compo" tag now shows a double note icon to make it clear this tag is for music competitions only.</li>
						</ul>

						<h3>June 22, 2025</h3>
						<ul>
							<li>Headers on the CSDb and GB64 tabs now handle long titles without overflowing.</li>
							<li>All page tabs now use the same sticky header bar as the CSDb and GB64 tabs.</li>
							<li>The "No SID Handler" option has been added. It obviously plays no music, but can be handy when
								you just want to browse for information. The auto-play overlay is not shown either.</li>
						</ul>

						<h3>June 21, 2025</h3>
						<ul>
							<li>The dialog box for editing tags now scrolls both lists to the top when opened.</li>
							<li>After clicking a SID row in the folder list, you can now use standard navigation keys such as
								Home, End, Page Up, Page Down, and the arrow keys.</li>
							<li>Made the "Winner" tag a bit snazzier so it stands out more.</li>
						</ul>

						<h3>June 20, 2025</h3>
						<ul>
							<li>The scroll position is now remembered when refreshing the current folder with the "f" key.</li>
						</ul>

						<h3>June 19, 2025</h3>
						<ul>
							<li><del>Left and right arrow tag icons have been added. These always appear between event (green) and production (blue) tags.
								A green event tag with a right-pointing arrow toward a blue production tag (e.g. a demo) indicates that the SID file was
								created specifically for that production, which was released at the event.</del></li>
							<li><del>If the arrow points to the left instead, it means the SID file was released at the event but not made for a particular
								production and did not participate in a music competition.</del></li>
							<li>You can now refresh the current folder by hitting the "f" key. <del>This will also go back to the top of the list.</del></li>
						</ul>

						<h3>June 15, 2025</h3>
						<ul>
							<li>You can now click any screenshot (not just in the GB64 tab) to view it at three times its original size.</li>
							<li>Fixed temporary emulating testing (hotkey "l") not showing the correct number of subtunes for a song.</li>
							<li>The GB64 tab now uses the same sticky header bar as the CSDb tab.</li>
						</ul>

						<h3>June 14, 2025</h3>
						<ul>
							<li>The registration process has been reworked. There is now a 'Register' link above the user name and password fields that
								you have to click in order to register a new user. The removal of the previously automatic registration should also mean that
								automatic login on mobile devices should work properly.</li>
							<li>Upgraded the JSIDPlay2 emulator. It now uses WASM_GC (Web Assembly Garbage Collector) and is estimated to be 10%
								faster than the previous version.</li>
						</ul>

						<h3>June 13, 2025</h3>
						<ul>
							<li>The "Game" search type now searches the imported GB64 database directly. Note that you will only get results if the game
								you're looking for actually uses a SID file in the High Voltage SID Collection.</li>
							<li>You can now click a GB64 screenshot on a game page to view it at three times its original size.</li>
							<li>Fixed scroll position not being restored when browsing back from a search state.</li>
							<li>Fixed SID row not centering correctly when skipping to the previous or next tune.</li>
						</ul>

						<h3>June 9, 2025</h3>
						<ul>
							<li>The "Game" and "Game Prev" tags will automatically be removed if a "GameBase64" tag either
								exists or is added when GB64 entries are detected. The tags will not be affected if the SID row does not have any GB64 entries.</li>
						</ul>

						<h3>June 8, 2025</h3>
						<ul>
							<li>Fixed an issue where the GB64 tab sometimes showed irrelevant screenshots for a game.</li>
							<li>The remix tab is working again.</li>
							<li>If you click a song and DeepSID detects that it has entries in the GB64 tab, a "GameBase64" tag
								will automatically be added to it if it doesn't already have it.</li>
						</ul>

						<h3>June 7, 2025</h3>
						<ul>
							<li>The GB64 tab is back, showing game information from <a href="https://gb64.com/">GameBase64</a> whenever a SID file is used in one or more games.
								DeepSID now uses an imported database instead of scraping the site, which is both safer and faster.</li>
							<li>Clicking a game in a list of multiple GB64 entries now shows the game page instead of jumping to the site.</li>
						</ul>

						<h3>June 6, 2025</h3>
						<ul>
							<li>Improved phrase searching. Now searching e.g. filenames for "the party" (including the quotes) only return
								the entries where the words are truly consecutive.</li>
							<li>Fixed a detection bug where the filter controls didn't show or hide correctly depending on whether the SID tune clicked was
								made for 6581 or 8580.</li>
						</ul>

						<h3>May 24, 2025</h3>
						<ul>
							<li>The JSIDPlay2 SID handler now has its own unique time bar color.</li>
						</ul>

						<h3>March 15, 2025</h3>
						<ul>
							<li>Thomas Jansson (tubesockor) fixed a 6510 emulation issue in Hermit's driver causing incompatibility with some tunes.
								Some instructions were push/popping the stack in a reverse order than expected on 6510.
								Huge thanks to Anthony Bybell who discovered this anomaly.</li>
						</ul>

						<h3>February 2, 2025</h3>
						<ul>
							<li>Fixed too many bytes showing red colors in the MEMO view, when starting a tune.</li>
							<li>Fixed the pace field in the MEMO view not always showing the correct information.</li>
						</ul>

						<h3>January 12, 2025</h3>
						<ul>
							<li>Added the CSDb music competitions related to HVSC #82.</li>
						</ul>

						<h3>January 11, 2025</h3>
						<ul>
							<li>All new files in HVSC #82 are now connected to CSDb entries.</li>
						</ul>

						<h3>December 27, 2024</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #82.</li>
							<li>JSIDPlay2 has been changed to WebAssembly and it should now be fast enough to play stereo tunes on mobile devices.
								It should play at least 40% faster.</li>
						</ul>

						<h3>November 8, 2024</h3>
						<ul>
							<li>A new SID handler with WebUSB support for <a href="https://github.com/LouDnl/USBSID-Pico">USBSID-Pico</a> has been added to Hermit's emulator by LouD.
								Note that WebUSB can be quite demanding resource wise.</li>
						</ul>

						<h3>October 19, 2024</h3>
						<ul>
							<li>Fixed sid model and clock speed info flags not always showing the correct information.</li>
						</ul>

						<h3>October 13, 2024</h3>
						<ul>
							<li>Ratings stars for a song are now shown in the info box too, so you can see and rate the song even when you have browsed away from it.
								The collection version number has been moved down above the sundry box.</li>
						</ul>

						<h3>October 6, 2024</h3>
						<ul>
							<li>Added <a href="//deepsid.chordian.net/?player=130&type=player&search=quantum">Quantum Soundtracker</a> to the list of music editors.</li>
						</ul>

						<h3>October 3, 2024</h3>
						<ul>
							<li>Fixed an UTF-8 issue due to an update on CSDb. All CSDb pages should display properly again.</li>
						</ul>

						<h3>September 28, 2024</h3>
						<ul>
							<li>A new tag group type for events has been created. These tags have a green color and are shown before all other tag types.
								They are used for events such as demo parties. The event name always come first, then optionally "Compo"
								which can then optionally be followed by the ranking such as "Winner" and "#1" to "#9" tags.</li>
						</ul>

						<h3>August 4, 2024</h3>
						<ul>
							<li>Fixed temporary emulating testing (hotkey "l") not playing the uploaded tune when clicked.</li>
						</ul>

						<h3>July 25, 2024</h3>
						<ul>
							<li>Added the CSDb music competitions related to HVSC #81.</li>
						</ul>

						<h3>July 21, 2024</h3>
						<ul>
							<li>The "Compo", "Winner" and "#1" to "#9" tags now always huddle together in a logical manner.</li>
						</ul>

						<h3>July 20, 2024</h3>
						<ul>
							<li>The sundry tab for stereo is now also available for JSIDPlay2. Here you can set sliders for balance and delay,
								change the stereo mode, and toggle fake stereo along with the SID chip to read from.</li>
							<li>You can now hit the BACKSPACE key to go back to the parent folder.</li>
						</ul>

						<h3>July 18, 2024</h3>
						<ul>
							<li>Fixed main volume not being remembered when refreshing the site.</li>
						</ul>

						<h3>July 17, 2024</h3>
						<ul>
							<li>Advanced settings has been added in the settings tab. Its contents will depend on the SID handler chosen. There
								are only advanced settings for JSIDPlay2 to begin with.</li>
							<li>Moved the buffer size in the settings tab into the new section for advanced settings.</li>
							<li>Added some advanced settings for the JSIDPlay2 emulator &ndash; default emulation (<i>reSID</i> or <i>reSIDfp</i>) and
								sampling method (<i>Decimate</i> or <i>Resample</i>), plus a ton of filter names for 6581 and 8580 chips.</li>
							<li>Selecting a different SID handler now always refreshes the site, instead of just a select few. To make up for this, the tab
								you're in is remembered every time you change the SID handler.</li>
							<li>All new files in HVSC #81 are now connected to CSDb entries.</li>
						</ul>

						<h3>July 15, 2024</h3>
						<ul>
							<li>Fixed a bug when refreshing the site while using other SID handlers than WebSid HQ, Legacy or reSID.</li>
						</ul>

						<h3>July 14, 2024</h3>
						<ul>
							<li>Added a SID handler for another reSID emulator, this time 'WebSidPlay' by Jürgen Wothke (called 'reSID' here
								to avoid confusion). It's a port of <a href="https://github.com/libsidplayfp/libsidplayfp" target="_top">libsidplayfp</a>
								and is only 30% slower than WebSid HQ, making it a great choice for excellent emulation.
								The new reSID handler is in BETA and still have the following issues:
								<ul>
									<li>Reading the digi type and rate is not supported yet.</li>
									<li>Reading the SID registers is slow on big buffer sizes and only supports 1SID.</li>
									<li>The oscilloscope is not supported yet.</li>
									<li>Showing the PAL/NTSC and 6581/8580 flags is not ready yet.</li>
									<li>Advanced reSID settings are not available.</li>
									<li>For 2SID and 3SID tunes, the visuals only show the first SID chip.</li>
									<li>Exotic SID tunes (4SID and more) are not supported.</li>
								</ul>
							</li>
							<li>Another emulator button for the new reSID emulator has been added in the visuals tab.</li>
							<li>Added logic for disabling view buttons (piano, graph, etc.) when a SID handler doesn't support it.</li>
							<li>All three emulators by Jürgen Wothke (WebSid HQ, WebSid Legacy and reSID) now share a new and streamlined script player.
								DeepSID has been overhauled accordingly to support this.</li>
							<li>The scopes for WebSid HQ and WebSid Legacy have been significantly improved, using a new channel streamer script.
								Setting a specific buffer size for the scope to work is no longer necessary.</li>
							<li>A bug has been fixed in a player script for WebSid HQ. Now the visuals are perfectly synchronized.</li>
						</ul>

						<h3>July 6, 2024</h3>
						<ul>
							<li>Upgraded the JSIDPlay2 emulator. An "open" method has been renamed to fix a bug in DeepSID that prevented
								certain links to PlayMOD and CShellDB from working, as well as the "p" hotkey for opening a tiny DeepSID.</li>
							<li>Fixed the tune timer not keeping up when using fast forward in JSIDPlay2.</li>
							<li>Fixed when the tags line for a SID row was not reappearing after showing a loading spinner.</li>
						</ul>

						<h3>June 30, 2024</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #81.</li>
							<li>Added composer profiles for the new folders in HVSC #81.</li>
						</ul>

						<h3>June 29, 2024</h3>
						<ul>
							<li>Fixed a bug where the piano visuals would appear in the other tabs.</li>
						</ul>

						<h3>June 28, 2024</h3>
						<ul>
							<li>Upgraded the JSIDPlay2 emulator. It now has tune length events.</del></li>
							<li>Stopping a tune with JSIDPlay2 now kills the worker thread to save on mobile battery power.</li>
							<li>Changed the logic for the loading spinner when using JSIDPlay2. It now clears earlier than before.</li>
							<li>The visuals tab is now turned off as default for JSIDPlay2, to save on CPU time.</li>
						</ul>

						<h3>June 22, 2024</h3>
						<ul>
							<li>Added a SID handler for <a href="https://haendel.ddns.net:8443/static/teavm/c64jukebox.vue" target="_top">JSIDPlay2</a>.
								This uses the renowned reSID engine and offers excellent emulation, but it's also considerably
								more demanding. You need a powerful CPU to run this one. Only the playback and visuals are supported in this "BETA" update.
								Support for stereo and filter presets are coming in a later update.</li>
							<li>Because of the new SID handler, an ON/OFF toggle button has been added in the visuals tab. If your CPU is having trouble making the
							    playback sound coherent, try turning the visuals off. This may especially be helpful with digi, 2SID and 3SID tunes. Turning the visuals
								off not only saves time on having to update this, it also turns off SID register output in the JSIDPlay2 emulator. </li>
							<li>Fixed tags not being removed too when a loading spinner is shown in a SID row.</li>
							<li>Buffer size is now unique for each of the emulators supported by the visuals tab.</li>
						</ul>

						<h3>May 16, 2024</h3>
						<ul>
							<li>Hermit's emulator is now capable of playing back SID+FM files. These are SID files combined with
								FM emulation and have been accomplished by using either the <a href="https://www.c64-wiki.com/wiki/Commodore_Sound_Expander" target="_top">SFX Sound Expander</a> or the <a href="https://c64.xentax.com/index.php/fm-yam" target="_top">FM-YAM</a>
								cartridge. Thank you to Thomas Jansson (tubesockor) for this update.</li>
							<li>A new <a href="//deepsid.chordian.net/?file=/SID%20Happens/SID+FM/">SID+FM</a> subfolder has been created in the <a href="//deepsid.chordian.net/?file=/SID%20Happens/">SID Happens</a> folder.
								You can only enter this subfolder when you have selected the <b>Hermit's (+FM)</b> SID handler in the top left drop-down box.
								It's possible to upload new files to this subfolder, but they must of course be of the SID+FM type or they will be deleted.</li>
						</ul>

						<h3>January 6, 2024</h3>
						<ul>
							<li>Added the CSDb music competitions related to HVSC #80.</li>
						</ul>

						<hr />
						<i>Click <a href="changes.htm" target="_top">here</a> to see archived changes going back to the launch of DeepSID.</i>

					</div>

				</div>
			</div>

			<div id="annex" style="display:none;">
				<div class="annex-tabs">
					<div class="annex-tab selected">Tips
						<div class="annex-close"></div>
					</div>
					<div class="annex-topics" title="Topics"></div>
				</div>
				<div id="annex-tips">
					<div style="padding:6px 0;text-align:center;">
						<img src="images/loading_threedots.svg" style="margin:0;border:none;" alt="Loading" />
					</div>
				</div>
			</div>

		<?php endif ?>

	</body>

</html>
