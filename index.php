<?php
	if (false) die('DeepSID is being updated. Please return again in a few minutes.');

	require_once("php/class.account.php"); // Includes setup
	$user_id = $account->CheckLogin() ? $account->UserID() : 0;

	require_once("tracking.php"); // Also called every 5 minutes by 'main.js'

	// @link https://stackoverflow.com/a/60199374/2242348
	$inside_iframe = isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe';

	// Detect and block if the URL contains unwanted characters
	// Example: http://deepsid.chordian.net/?file=%22%3E%3Ch1%3Efoobarbaz
	$special_chars = array('[', ']', '<', '>', ';', ',', '"', '*');
	$url = urldecode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
	foreach ($special_chars as $char)
		if (strpos($url, $char) !== false)
			die("Malignant switch contents detected. Please fix the URL and try again.");

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
		<script type="text/javascript">
			var viewport = document.createElement("meta");
			viewport.setAttribute("name", "viewport");
			viewport.setAttribute("content", "width="+(screen.width < 450 ? "450" : "1320"));
			document.head.appendChild(viewport);
		</script>
		<meta name="description" content="A modern online SID player for the High Voltage and Compute's Gazette SID collections." /> <!-- Max 150 characters -->
		<meta name="keywords" content="c64,commodore 64,sid,6581,8580,hvsc,high voltage,cgsc,compute's gazette,visualizer,stil,websid,jssid,hermit" />
		<meta name="author" content="Jens-Christian Huus" />
		<title>DeepSID | Chordian.net</title>
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans%3A400%2C700%2C400italic%2C700italic%7CQuestrial%7CMontserrat&#038;subset=latin%2Clatin-ext" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Asap+Condensed" />
		<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Kanit" />
		<link rel="stylesheet" type="text/css" href="//blog.chordian.net/wordpress/wp-content/themes/olivi/style.css" />
		<link rel="stylesheet" type="text/css" href="css/chartist.css" />
		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<?php if ($inside_iframe): ?>
			<link rel="stylesheet" type="text/css" href="https://www.lemonamiga.localhost/assets/external/deepsid/style.css" />
		<?php endif ?>
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
		<?php if ($inside_iframe): ?>
			<script type="text/javascript" src="https://www.lemonamiga.localhost/assets/external/deepsid/main.js"></script>
		<?php endif ?>
		<!-- For Lemon64: START -->
		<?php if (isset($_GET['lemonamiga'])): ?>
			<link rel="stylesheet" type="text/css" href="https://www.lemonamiga.localhost/assets/external/deepsid/style.css" />
			<script type="text/javascript" src="https://www.lemonamiga.localhost/assets/external/deepsid/main.js"></script>
		<?php elseif (isset($_GET['lemon64com'])): ?>
			<link rel="stylesheet" type="text/css" href="https://www.lemon64.com/assets/external/deepsid/style.css" />
			<script type="text/javascript" src="https://www.lemon64.com/assets/external/deepsid/main.js"></script>
		<?php elseif (isset($_GET['lemon64dev'])): ?>
			<link rel="stylesheet" type="text/css" href="https://www.lemon64.dev/assets/external/deepsid/style.css" />
			<script type="text/javascript" src="https://www.lemon64.dev/assets/external/deepsid/main.js"></script>
		<?php endif ?>
		<!-- For Lemon64: END -->
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
						echo 'http://chordian.net/deepsid/images/composers/'.$image;
				else if (substr($_GET['file'], -4) == '.sid')
					echo 'http://chordian.net/deepsid/images/example_play.png';
				else
					echo 'http://chordian.net/deepsid/images/composer.png';
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
					echo 'http://chordian.net/deepsid/images/example_play.png';
				else
					echo 'http://chordian.net/deepsid/images/composers/_sh.png';
			} else 
				echo 'http://chordian.net/deepsid/images/example.png';
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

		<div id="dialog-ev-subtunes" class="dialog-box dialog-wizard">
			<div class="dialog-text"></div>
			<div style="margin-top:16px;">
				<label for="dd-subtune">Subtune&nbsp;&nbsp;</label>
				<select id="ev-dd-subtune" name="dd-subtune"></select>
			</div>
			<div class="dialog-buttons"><a href="#" class="dialog-cancel">Cancel</a><button class="dialog-button-yes dialog-auto">Next</button></div>
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
				<div id="logo" class="unselectable">D e e p S I D</div>
				<select id="dropdown-emulator" name="select-emulator" style="visibility:hidden;">
					<option value="websid">WebSid emulator</option>
					<option value="legacy">WebSid (Legacy)</option>
					<option value="jssid">Hermit's emulator</option>
					<option value="youtube">YouTube videos</option>
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

			<div id="youtube-tabs">
				<div class="tab unselectable selected">DeepSID</div>
			</div>
			<div id="info">
				<div id="info-text">
					<div style="text-align:center;font-size:12px;">
						<span style="position:relative;top:2px;">DeepSID is an online SID player for the High Voltage SID Collection and<br />
						more. It plays music created for the <a href="https://en.wikipedia.org/wiki/Commodore_64" target="_top">Commodore 64</a> home computer.</span><br />
						<span style="position:relative;top:8px;">Click any of the folder items below to start browsing the collection.</span>
					</div>
				</div>
				<div id="youtube">
					<div id="youtube-loading">Initializing YouTube...</div>
					<div id="youtube-player"></div>
				</div>
				<div id="memory-bar"><div id="memory-lid"></div><div id="memory-chunk"></div></div>
			</div>
			<div id="sundry-tabs">
				<div class="tab unselectable selected" data-topic="stil" id="stab-stil">News</div>
				<div class="tab unselectable" data-topic="tags" id="stab-tags">Tags</div>
				<div class="tab unselectable" data-topic="osc" id="stab-osc">Scope</div>
				<div class="tab unselectable" data-topic="filter" id="stab-filter">Filter</div>
				<div id="sundry-ctrls"></div>
			</div>
			<div id="sundry">
				<div id="stopic-stil" class="stopic">
					<div id="sundry-news">
						<span>The High Voltage SID Collection has been upgraded to the latest version #76.</span>
					</div>
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
						<option value="maximum">Maximum</option>
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

		<?php if (!isMobile()): ?>

			<div id="dexter">
				<div id="sites" class="unselectable">
					<div style="float:left;margin-left:1px;text-align:left;">
						<a href="<?php echo HOST; ?>" target="_top">Home</a>
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
					<a href="https://twitter.com/chordian" target="_blank">Twitter</a>
						<span>&#9642</span>
					<a href="https://discord.gg/n5w85GMbVu" target="_blank">Discord</a>
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
							<h3 style="display:inline-block;margin-top:16px;">Help</h3><button id="info-piano-button" style="position:relative;top:-2px;left:8px;width:60px;">SHOW</button>
							<div id="info-piano-text" style="display:none;">
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
							<a href="//chordian.net/2018/05/12/deepsid/" target="_top">http://chordian.net/2018/05/12/deepsid/</a>
						</p>

						<h3>SID emulators for JavaScript</h3>
						<p>
							WebSid by Jürgen Wothke (<a href="http://www.wothke.ch/tinyrsid/index.php" target="_top">Tiny'R'Sid</a>)<br />
							<a href="http://www.wothke.ch/websid/" target="_top">http://www.wothke.ch/websid/</a><br />
							<a href="https://github.com/wothke/websid" target="_top">https://github.com/wothke/websid</a><br />
							<a href="https://github.com/wothke/webaudio-player" target="_top">https://github.com/wothke/webaudio-player</a>
						</p>
						<p>
							jsSID by Mihály Horváth (<a href="http://csdb.chordian.net/?type=scener&id=18806" target="_top">Hermit</a>)
						</p>

						<h3>Libraries of SID tunes</h3>
						<p>
							High Voltage SID Collection #76<br />
							<a href="https://www.hvsc.c64.org/" target="_top">https://www.hvsc.c64.org/</a>
						</p>
						<p>
							Compute's Gazette SID Collection #141<br />
							<a href="http://www.c64music.co.uk/" target="_top">http://www.c64music.co.uk/</a>
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
							<li>A lot of older retro images (typically lo-res) are from the musicians photos download at <a href="http://www.gamebase64.com/downloads.php" target="_top">GameBase64</a>.</li>
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
							The user name and password boxes are used for both registering and logging in. To register,
							just type the user name you want. If it is available (a status message tells you) then type a
							password and hit the button.
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
							<a href="//deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=jssid&subtune=2">//deepsid.chordian.net?file=/MUSICIANS/H/Hubbard_Rob/Commando.sid&emulator=jssid&subtune=2</a>
						</p>
						<p>
							An example to show a CSDb entry:<br />
							<a href="//deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519">//deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid=153519</a>
						</p>

					</div>

					<div id="topic-changes" class="topic" style="display:none;">
						<h2>Changes</h2>

						<h3>December 28, 2021</h3>
						<ul>
							<li>The 8 new 2SID files in HVSC #76 (i.e. that uses 6 voices) has now been added as
								<a href="//deepsid.chordian.net/?file=/_Exotic%20SID%20Tunes%20Collection/Stereo%202SID&here=1&search=76&type=new&tab=csdb">exotic stereo files</a>.</li>
							<li>Upgraded the WebSid (HQ) emulator. A hack (not a real fix) has been implemented that allows it to play
								Adam Morton's problematic <a href="//deepsid.chordian.net/?file=/MUSICIANS/M/Morton_Adam/Synthmeld.sid">Synthmeld.sid</a> song.</li>
						</ul>

						<h3>December 25, 2021</h3>
						<ul>
							<li>Added composer profiles for the new folders in HVSC #76.</li>
						</ul>

						<h3>December 24, 2021</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #76.</li>
						</ul>

						<h3>December 23, 2021</h3>
						<ul>
							<li>Improved when the folder button for tags in the sundry box is actually supposed to be visible.</li>
						</ul>

						<h3>December 12, 2021</h3>
						<ul>
							<li>If the annex box is showing external composer links, it is now automatically refreshed when
								another composer profile page is shown.</li>
							<li>The list of external links for a composer is now sorted alphabetically.</li>
						</ul>

						<h3>December 11, 2021</h3>
						<ul>
							<li>You can now add external links to a composer profile. Click the new link in the line with the star ratings to
								open these in the annex box. All users logged in are free to add, edit and delete these links.</li>
						</ul>

						<h3>November 29, 2021</h3>
						<ul>
							<li>In the tips pages for the annex box, all search links are now performed without refreshing the site.
								Also, a tag in the tips pages about these can now be clicked to search for it.</li>
						</ul>

						<h3>November 27, 2021</h3>
						<ul>
							<li>Improved the handling of how the STIL tab is named, depending on the context.</li>
							<li>The STIL tab is now called <code>Notes</code> whenever a file from the SH folder is played.</li>
						</ul>

						<h3>November 24, 2021</h3>
						<ul>
							<li>Changed the logic of showing the filter settings depending on 6581 chip mode. The controls are
								now disabled instead of invisible, and the chip mode button has been placed next to the tab.</li>
						</ul>
						
						<h3>November 23, 2021</h3>
						<ul>
							<li>Fixed not being able to alternate between WebSid (HQ) and WebSid (Legacy) properly.</li>
							<li>Various filter settings can now be adjusted in a new filter tab in the sundry box. You need
								to be using the WebSid (HQ) emulator for this, and the file needs to be in 6581 chip mode.</li>
						</ul>

						<h3>November 20, 2021</h3>
						<ul>
							<li>The default 6581 filter for WebSid (HQ) has now been set to R2, which is much brighter than the previous R4.</li>
							<li>The vertical scrollbar should no longer appear in some web browsers, such as e.g. Firefox.</li>
							<li>Hitting <code>p</code> to pop-up a tiny DeepSID now opens it in mobile mode, although it's still using WebSid (HQ).</li>
							<li>Overhauled the FAQ page with links to tips in the annex box, to avoid duplicate information.</li>
						</ul>

						<h3>November 18, 2021</h3>
						<ul>
							<li>Upgraded the WebSid (HQ) emulator. It has a new filter implementation for the 6581 version, and the
								centering of the scopes has been fixed.</li>
							<li>Added a new <code>?notips=1</code> URL switch to avoid showing the annex box with tips.</li>
						</ul>

						<h3>November 16, 2021</h3>
						<ul>
							<li>Created an annex box in the void area to the right where random tips can now be seen. The tips in the STIL
								box are no longer shown. Click the corner icon for a list of the annex tips you can choose from.</li>
						</ul>

						<h3>November 14, 2021</h3>
						<ul>
							<li>Fixed a bug that stopped autoplaying if the subtune was shorter than one second.
								Thank you for spotting that one, Andreas Wallström. Try Jason Brooke's
								<a href="//deepsid.chordian.net/?file=/MUSICIANS/B/Brooke_Jason/Out_Run.sid&subtune=4">Out Run</a>
								or David Whittaker's <a href="//deepsid.chordian.net/?file=/MUSICIANS/W/Whittaker_David/Paddle_Mania.sid&subtune=18">Paddle Mania</a> as examples.</li>
						</ul>

						<h3>November 7, 2021</h3>
						<ul>
							<li>Fixed a bug that showed the folder button in the tags sundry box when it wasn't supposed to be.</li>
							<li>Removed redundant debug code that I never really used anyway.</li>
						</ul>

						<h3>October 30, 2021</h3>
						<ul>
							<li>Removed the top line for CShellDB and PlayMOD, and instead added an invite link for Discord.</li>
							<li>Fixed a bug that enabled the visuals tab when entering the SH folder while using the
								YouTube SID handler.</li>
							<li>Fixed empty drop-down box when entering the competitions folder.</li>
							<li>Added a magenta color strip for SidTracker64 tunes.</li>
							<li>Search shortcuts now show a small magnifying glass inside of their folder icons.</li>
						</ul>

						<h3>October 24, 2021</h3>
						<ul>
							<li>Overhauled how sticky sorting of SID files is handled. This also fixed a bug where a SID row
								was not marked by an external link if sorting by anything other than name.</li>
							<li>A recommended box in the root page now sometimes advertise for my other site,
								<a href="http://csdb.chordian.net/">CShellDB</a>.</li>
						</ul>

						<h3>October 23, 2021</h3>
						<ul>
							<li>The <a href="http://ogp.me/">Open Graph</a> title for a SID file in the SH folder should now
								show the proper composer name (or handle) and title, instead of just the filename.</li>
							<li>External links to a SID file that shows an avatar image now also add a small play icon in the
								bottom right corner of this image, to make it clear that clicking the link will play it.</li>
						</ul>

						<h3>October 22, 2021</h3>
						<ul>
							<li>If the composer of a SID file specified in an external link has an avatar image, this will now be
								displayed instead of the default play image. This also works in the SH folder.</li>
						</ul>

						<h3>October 18, 2021</h3>
						<ul>
							<li>Songs with more than one subtune now always maintain the relevant switch in the URL, regardless
								of what number it is. This should fix the issue where the first subtune can't be played from an
								external link.</li>
						</ul>

						<h3>October 13, 2021</h3>
						<ul>
							<li>Sorting a folder with SID files (other than the SH folder) is now sticky.</li>
							<li>Fixed a bug that made the sort options in the SH folder sticky for other folders.</li>
						</ul>

						<h3>October 9, 2021</h3>
						<ul>
							<li>It is now possible to proceed to the next subtune after saving video links. Just mark the checkbox
								(and optionally adjust the subtune number in the drop-down box) in the bottom left corner of the dialog box.</li>
						</ul>

						<h3>October 2, 2021</h3>
						<ul>
							<li>DeepSID's copy of <a href="http://www.c64music.co.uk/" target="_top">Compute's Gazette SID Collection</a> has now been upgraded to version 1.41.</li>
							<li>SID names are now prefixed with a tiny icon (with three horizontal lines) whenever there's a STIL text entry.</li>
						</ul>

						<h3>September 25, 2021</h3>
						<ul>
							<li>In the video links dialog box, the corner link for opening multiple search tabs now
								search directly in the four specific channels. This should yield more precise results.</li>
							<li>Small color strips can now be seen in the beginning of SID rows for certain specific player
								types. GoatTracker is gray, NewPlayer is green, Sid-Wizard is cyan, SID Factory II is blue,
								and DMC is yellow.</li>
						</ul>

						<h3>September 24, 2021</h3>
						<ul>
							<li>Fixed a bug that in rare cases mixed data from multiple composers when showing both the
								chart with active years and the bars with players used.</li>
							<li>It is no longer possible to mark and select folders as well as context menu options.
								If you want to copy a folder name, enter it and copy it from the URL box instead.</li>
						</ul>

						<h3>September 18, 2021</h3>
						<ul>
							<li>Removed the avatar images from the three big lists in the bottom of the root page, as the loading
								of these images bogged down the site too much. With more space available, the full names of the
								composers are now shown instead of the slightly shortened versions.</li>
						</ul>

						<h3>September 17, 2021</h3>
						<ul>
							<li>Added a new <code>?cover=1</code> URL switch to force showing the auto-play cover overlay upon refresh.</li>
							<li>The <code>?wait=X</code> switch is now sticky when specified in the URL.</li>
						</ul>

						<h3>September 16, 2021</h3>
						<ul>
							<li>Changed the <code>?wait=X</code> URL switch to now wait <code>X</code> number of milliseconds
								before stopping.</li>
							<li>DeepSID now waits up to 10 seconds for a YouTube channel tab to start playing its video. If it
								doesn't and there are more channel tabs, it will try the next one instead. (If it was the last tab,
								it will try the first one.)</li>
						</ul>

						<h3>September 12, 2021</h3>
						<ul>
							<li>Added a corner link in the video links dialog box for opening multiple search tabs at once.</li>
						</ul>

						<h3>September 8, 2021</h3>
						<ul>
							<li>The dialog box for editing video links now discard rows with an empty video ID.</li>
						</ul>

						<h3>September 5, 2021</h3>
						<ul>
							<li>The video ID in the dialog box for editing video links now accept the time offset parameter.
								For example, typing <code>Gro7n1oqHUo?t=63</code> will start the video 63 seconds later.</li>
						</ul>

						<h3>September 3, 2021</h3>
						<ul>
							<li>Searching for YouTube videos now also include the name of the author.</li>
							<li>Opening the dialog box for editing video links now always offer three commonly used
								channel options.</li>
						</ul>

						<h3>August 31, 2021</h3>
						<ul>
							<li>Added a new <code>?wait=1</code> URL switch for selecting a specific song but not play it yet.</li>
						</ul>

						<h3>August 29, 2021</h3>
						<ul>
							<li>A new SID handler for <b>YouTube videos</b> has been added. You can select it in the top left
								drop-down box. Note that only SID rows that have video links are enabled. Since this is a new
								feature in DeepSID, there are of course an abundance of disabled rows for now. More video
								links will be added over time.</li>
							<li>Most of the the standard DeepSID controls will control a YouTube video. You can even
								click somewhere in the time progress bar to jump to another spot in the video.</li>
							<li>A dialog box has been added for editing video links. To open it, right-click a SID row
								(disabled or not) and choose <b>Edit YouTube Links</b>. You can add up to five tabs with
								YouTube video links for the same song or subtune, and you can also choose which one should
								be the default tab when a SID row is clicked.</li>
						</ul>

						<h3>August 15, 2021</h3>
						<ul>
							<li>All scrollable areas now use the default web browser scrollbar. The custom scrollbar
								jQuery plugin has been removed completely. Thanks
								<a href="http://manos.malihu.gr/jquery-custom-content-scroller/" target="_top">malihu</a>
								for years of service back when the default scrollbars were crude.</li>
						</ul>

						<h3>August 13, 2021</h3>
						<ul>
							<li>The browser pane with folders and files now uses a thin version of the default web
								browser scrollbar instead of the custom scrollbar jQuery plugin.</li>
						</ul>

						<h3>August 6, 2021</h3>
						<ul>
							<li>Fixed search for competition folders showing incorrect results.</li>
							<li>Removed the search shortcut for game composers. Apart from not being able to show all
								tunes in one big list before hitting the search cap, the new big list in the root page
								also makes the search shortcut redundant.</li>
							<li>It is no longer possible to search for search shortcuts.</li>
						</ul>

						<h3>August 5, 2021</h3>
						<ul>
							<li>The root page now shows three big lists in the bottom &ndash; one for active composers
								(that made music this year), procrastinators (made music last year) and all professional
								game composers. The two first lists for this and last year may have a few "noise" entries
								in them because of the way HVSC adds old tunes as new releases. I'll see what I can do
								to iron these out over time.</li>
							<li>Added the CSDb music competitions related to HVSC #75.</li>
						</ul>

						<h3>August 2, 2021</h3>
						<ul>
							<li>All new files in HVSC #75 are now connected to CSDb entries.</li>
						</ul>

						<h3>August 1, 2021</h3>
						<ul>
							<li>Fixed a bug where the table cell with star ratings was sometimes pushed too small.</li>
							<li>Improved the handling of pretty player names which fixed a few odd adaptations of the tiny ones.</li>
							<li>The pretty player names for JCH's NewPlayer series now also show if the tune is unpacked.</li>
							<li>On CSDb release pages, all download links for PRG files now also offer an alternative C64 link.
								The file is exactly the same, only the extension is different. The advantage is that you can
								associate it with an offline emulator such as VICE and run it directly from your web browser.
								This is not always possible with PRG files.</li>
							<li>Fixed a bug that sometimes added commas in the copyright field when uploading tunes.</li>
							<li>Improved the handling of copyright year when uploading tunes.</li>
						</ul>

						<h3>July 31, 2021</h3>
						<ul>
							<li>Added three search shortcuts in the first level of the HVSC folder:
								<ul>
							<li>A search shortcut for multispeed finds all tags with "Multispeed" or "2x" and up to "16x" speeds.</li>
							<li>A search shortcut for multisid finds all "2SID", "3SID", "4SID", "8SID" and "10SID" tunes.</li>
							<li><del>A search shortcut for game composers finds all soundtracks from popular game composers.</del></li>
						</ul>
							</li>
						</ul>

						<h3>July 20, 2021</h3>
						<ul>
							<li>You can now also search for the files added in the latest HVSC update by someone. Choose
								<code>Latest</code> and then type the name to do this. If you want to be certain that only a
								specific composer is matched, add a slash in the end. For example, "tomas/" will only show
								hits for Danko. Append e.g. ",72" for that HVSC version.</li>
							<li>A new type of folder item called a <i>search shortcut</i> has been added to DeepSID. This
								has been used inside the first level folder of HVSC to search for new stuff in the latest
								five HVSC updates.</li>
							<li>The second search shortcut for folders is a special search that lists folders in HVSC (mostly by composers)
								that contains files added in the latest HVSC update. Each folder shown in the search results
								are actually new search shortcuts. Clicking one shows only those new files.</li>
						</ul>

						<h3>July 17, 2021</h3>
						<ul>
							<li>The cover overlay for auto-playing a tune is no longer shown if not necessary.</li>
						</ul>

						<h3>July 13, 2021</h3>
						<ul>
							<li>The 4 new 2SID files in HVSC #75 (i.e. that uses 6 voices) has now been added as
								<a href="//deepsid.chordian.net/?file=/_Exotic%20SID%20Tunes%20Collection/Stereo%202SID&here=1&search=75&type=new&tab=csdb">exotic stereo files</a>.</li>
						</ul>

						<h3>July 12, 2021</h3>
						<ul>
							<li>The <a href="https://www.hvsc.c64.org/" target="_top">High Voltage SID Collection</a> has been upgraded to the latest version #75.</li>
							<li>Added composer profiles for the new folders in HVSC #75.</li>
						</ul>

						<h3>June 26, 2021</h3>
						<ul>
							<li>Upgraded the WebSid (HQ) emulator. Updated noise-WF implementation (with combined-WF handling)
								and floating-WF handling. Check <a href="//deepsid.chordian.net/?file=/MUSICIANS/M/Mixer/Soundcheck.sid">Soundcheck.sid</a>
								and <a href="//deepsid.chordian.net/?file=/SID%20Happens/Cubase64.sid">Cubase64.sid</a> to hear these improvements.</li>
						</ul>

						<h3>May 13, 2021</h3>
						<ul>
							<li>The GB64 tab is now available in the SH folder as I may add links there to new game soundtracks.</li>
						</ul>

						<h3>May 2, 2021</h3>
						<ul>
							<li>Imported the new GameBase64 collection v17 with new game entries and screenshots.</li>
						</ul>

						<h3>January 26, 2021</h3>
						<ul>
							<li>Fixed database errors for <a href="//deepsid.chordian.net/?file=/MUSICIANS/A/Acrouzet/Puzzled-Background_Music_IV.sid">Puzzled-Background_Music_IV.sid</a>
							and <a href="//deepsid.chordian.net/?file=/GAMES/A-F/Desert_Decision.sid">Desert_Decision.sid</a>.</li>
						</ul>

						<h3>January 24, 2021</h3>
						<ul>
							<li>Fixed a bug that caused the WebSid (HQ) emulator to report using CIA when in fact it was using VBI.</li>
						</ul>

						<h3>January 6, 2021</h3>
						<ul>
							<li>Upgraded the WebSid (HQ) emulator. Fixed a pulse initialization bug that affected PollyTracker songs.</li>
						</ul>

						<hr />
						<i>Click <a href="changes.htm" target="_top">here</a> to see archived changes going back to the launch of DeepSID.</i>

					</div>

				</div>
			</div>

			<div id="annex"<?php if (isset($_GET['notips'])) echo ' style="display:none;"'; ?>>
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