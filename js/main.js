
/**
 * DeepSID / Main
 */

var $=jQuery.noConflict();

var cacheCSDb = cacheSticky = cacheStickyBeforeCompo = cacheCSDbProfile = cacheBeforeCompo = cachePlayer = cacheGB64 = cacheRemix = prevFile = sundryTab = reportSTIL = "";
var cacheTabScrollPos = cachePlayerTabScrollPos = cacheGB64TabScrollPos = cacheRemixTabScrollPos = tabScrollPos = cachePosBeforeCompo = cacheDDCSDbSort = peekCounter = sundryHeight = 0;
var sundryToggle = true, recommended = forum = players = $trAutoPlay = null, isDebug, showTags;

var isLegacyWebSid = $("script[src='js/handlers/backend_tinyrsid_legacy.js']").length;

var tabPrevScrollPos = {
	profile:	{ pos: 0, reset: false },
	csdb:		{ pos: 0, reset: false },
	gb64:		{ pos: 0, reset: false },
	remix:		{ pos: 0, reset: false },
	player:		{ pos: 0, reset: false },
	stil:		{ pos: 0, reset: false },
	visuals:	{ pos: 0, reset: false },
	disqus:		{ pos: 0, reset: false },
	debug:		{ pos: 0, reset: false },
	settings:	{ pos: 0, reset: false },
	changes:	{ pos: 0, reset: false },
	faq:		{ pos: 0, reset: false },
	about:		{ pos: 0, reset: false },
}

const PATH_UPLOADS = "_SID Happens";

const _SECTION	= 1;
const _HEADER	= 2;
const _PARAM	= 3;
const _PLAYLIST	= 4;
const _OBJECT	= 5;
const _DATA		= 6;
const _RESULTS	= 7;

$(function() { // DOM ready

	var userExists = false;
	isDebug = $("#tab-debug").length;

	// Get the emulator last used by the visitor
	var storedEmulator = docCookies.getItem("emulator");
	if (storedEmulator == null) {
		// Set a default emulator
		if ($("body").attr("data-mobile") !== "0")
			storedEmulator = "legacy";	// Legacy WebSid for mobile devices
		else
			storedEmulator = "websid";	// The best WebSid for desktop computers
	}

	// Don't show tags on mobile devices as the non-custom scroll there might give way to sideways dragging
	showTags = $("body").attr("data-mobile") == "0";

	// However, a URL switch may TEMPORARILY override the stored emulator
	var emulator = GetParam("emulator").toLowerCase();
	if ($.inArray(emulator, [
		"websid",
		"legacy",
		"jssid",
		// "soasc_auto",
		// "soasc_r2",
		// "soasc_r4",
		// "soasc_r5",
		"download",
	]) === -1) emulator = storedEmulator;

	// Lower buffer size values may freeze DeepSID
	scope = isLegacyWebSid ? new SidTracer(16384) : new Tracer(16384, 40);

	viz = new Viz(emulator);
	SID = new SIDPlayer(emulator);
	ctrls = new Controls();
	browser = new Browser();

	// Get the user's settings
	$.post("php/settings.php", function(data) {
		browser.validateData(data, function(data) {
			SettingToggle("first-subtune",	data.settings.firstsubtune);
			SettingToggle("skip-tune",		data.settings.skiptune);
			SettingToggle("mark-tune",		data.settings.marktune);
			SettingToggle("skip-bad",		data.settings.skipbad);
			SettingToggle("skip-long",		data.settings.skiplong);
			SettingToggle("skip-short",		data.settings.skipshort);
		});
	}.bind(this));
	
	$("#dropdown-emulator")
		.styledSelect("emulator")
		.styledSetValue(emulator);

	// Assume 1SID (most common) thus hide the extra filter sections on the pianos
	$("#visuals-piano .piano-filter1,#visuals-piano .piano-filter2").hide();

	// Show a random tip in the sundry box
	$.post("php/tips.php", function(tips) {
		$("#stopic-stil").append('<div id="tips">'+tips+'</div>');
	});

	$("#time-bar").addClass(emulator)
		.css("cursor", SID.emulatorFlags.supportSeeking ? "pointer" : "default");

	// Update tracking every 5 minutes (also called once by 'index.php' upon load)
	setInterval(function() {
		$.get("tracking.php");
	}, 300000);

	/**
	 * Handle hotkeys.
	 * 
	 * NOTE: Hotkeys for voices ON/OFF are handled in 'viz.js' only.
	 * 
	 * @param {*} event 
	 */
	$(window).on("keydown", function(event) {
		if (!$("#search-box,#username,#password,#old-password,#new-password,#sym-rename,#sym-specify-subtune,#new-tag,#dialog-all-tags,#dialog-song-tags,#upload-csdb-id,#upload-lengths-list,#upload-stil-text,#form-edit-file input,#form-upload-file input").is(":focus")) {
			if (event.keyCode == 220)									// Keydown key below 'Escape'
				// Fast forward
				$("#faster").trigger("mousedown");
		}
	}).on("keyup", function(event) {
		if (!$("#search-box,#username,#password,#old-password,#new-password,#sym-rename,#sym-specify-subtune,#new-tag,#dialog-all-tags,#dialog-song-tags,#upload-csdb-id,#upload-lengths-list,#upload-stil-text,#form-edit-file input,#form-upload-file input").is(":focus")) {
			if (event.keyCode == 220) {									// Keyup key below 'Escape'
				// Fast forward
				$("#faster").trigger("mouseup");
			} else if (event.keyCode == 32)	{							// Keyup 'Space'
				$("#play-pause").trigger("mouseup");
			} else if (event.keyCode == 80) {							// Keyup 'p' (pop-up window)
				// Open a pop-up window with only the width of the #panel area
				window.open("//deepsid.chordian.net/", "_blank",
					"left=0,top=0,width=450,height="+(screen.height-150)+",scrollbars=no");
			} else if (event.keyCode == 67 && browser.isCompoFolder) {	// Keyup 'c'
				// Refresh the competition cache if inside a single competition folder
				// NOTE: This is undocumented to the public but if you are reading this and wondering about it,
				// it's used to refresh the cache in case an HVSC path has been added to a CSDb release page of
				// a SID file, thereby making it visible to the cache script. This can improve compo lists.
				$.post("php/csdb_compo_clear_cache.php",
					{ competition: browser.path.replace("/CSDb Music Competitions/", "") }, function(data) {
					browser.validateData(data, function() {
						// Now reload the folder to automatically refresh the cache
						browser.getFolder();
					});
				}.bind(this));
			} else if (event.keyCode == 83) {							// Keyup 's' (sundry)
				// Toggle the sundry box minimized or restored
				ToggleSundry();
				$(window).trigger("resize", true);
			} else if (event.keyCode == 76) {							// Keyup 'l' (load)
				// Upload and test one or more external SID tune(s)
				$("#upload-test").trigger("click");
			} else if (event.keyCode == 66) {							// Keyup 'b' (back before redirect)
				$("a.redirect").removeClass("playing");
				$("#redirect-back").trigger("click");
			} else if (event.keyCode == 106) {							// Keyup 'Keypad Multiply'
				var $selected = $("#folders tr.selected");
				var name = decodeURIComponent($selected.find(".name").attr("data-name"));
				if (name != "undefined" && $("#logged-username").text() == "JCH") {
					// Prepare some edit boxes with current data
					var playerInfo = SID.getSongInfo("info");
					$("#edit-file-name-input").val(name.split("/").slice(-1)[0]);
					$("#edit-file-player-input").val(browser.playlist[browser.songPos].playerraw);
					$("#edit-file-author-input").val(playerInfo.songAuthor);
					$("#edit-file-copyright-input").val(playerInfo.songReleased);
					// Show dialog box for editing the file (only the year for now)
					CustomDialog({
						id: '#dialog-edit-file',
						text: '<h3>Edit file</h3>',
						width: 390,
						height: 258,
					}, function() {
						// OK was clicked; make the changes to the file row in the database
						$.post("php/update_file.php", {
							fullname:	browser.playlist[browser.songPos].fullname.replace(browser.ROOT_HVSC+"/", ""),
							name:		$("#edit-file-name-input").val(),
							player:		$("#edit-file-player-input").val(),
							author:		$("#edit-file-author-input").val(),
							copyright:	$("#edit-file-copyright-input").val(),
						}, function(data) {
							browser.validateData(data, function() {
								browser.getFolder();
							});
						});
					});
				}
			} else if (event.keyCode == 84) {							// Keyup 't' (test something)
				console.log(browser.playlist[browser.songPos].address);
			}
		}
	});

	/**
	 * Handle key presses in the dialog box for editing a file row.
	 */
	$("#dialog-edit-file").on("keydown", function(event) {
		if (event.keyCode == 13) {
			$("#dialog-edit-file .dialog-button-yes").trigger("click");	// Click 'OK' button
			$("#dialog-edit-file input").blur();
		}
	});

	/**
	 * Upload the external SID file(s) for temporary emulator testing.
	 */
	$("#upload-test").change(function() {
		var sidFile = new FormData(), files = "";
		$.each($("#upload-test")[0].files, function(i, file) {
			sidFile.append(i, file);
		});
		browser.folders = browser.extra = browser.symlists = "";
		browser.playlist = [];
		browser.subFolders = 0;

		$("#dropdown-sort").empty().append(
			'<option value="name">Name</option>'+
			'<option value="oldest">Oldest</option>'+
			'<option value="newest">Newest</option>'
		).val("name");

		$("#tab-visuals").trigger("click");
		$("#sundry-ctrls").empty();
		history.replaceState({}, document.title, "");

		$.ajax({
			url:			"php/upload_test.php",
			type: 			"POST",
			cache:			false,
			processData:	false,
			contentType:	false,
			data:			sidFile,
			success: function(data) {
				browser.validateData(data, function() {
					data = $.parseJSON(data);

					ctrls.state("root/back", "enabled");
					if (!browser.isMobile) $("#folders").mCustomScrollbar("destroy");

					$("#dropdown-emulator").styledOptionState("websid legacy jssid", "enabled");
					$("#dropdown-emulator").styledOptionState("soasc_auto soasc_r2 soasc_r4 soasc_r5", "disabled");
					$("#path").css("top", "5px").empty().append("Temporary emulator testing");
					$("#stab-stil,#tab-stil").empty().append("STIL");

					// Only disable the ".." button
					$("#folder-root,#folder-back").removeClass("disabled");
					$("#folder-back").addClass("disabled");

					// Disable tabs useless to the temporary testing
					$("#tab-csdb,#tab-gb64,#tab-remix,#tab-stil,#tab-disqus").removeClass("disabled").addClass("disabled");

					// Sort the list of files first
					data.files.sort(function(obj1, obj2) {
						var o1 = browser.adaptBrowserName(obj1.filename, true);
						var o2 = browser.adaptBrowserName(obj2.filename, true);
						return o1.toLowerCase() > o2.toLowerCase() ? 1 : -1;
					});

					$.each(data.files, function(i, file) {
						var year = isNaN(file.copyright.substr(0, 4)) ? "unknown year" : file.copyright.substr(0, 4);
						files +=
							'<tr>'+
								'<td class="sid temp unselectable"><div class="block-wrap"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes">'+file.subtunes+'</div>' : '')+
								'<div class="entry name file" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'">'+browser.adaptBrowserName(file.filename.replace(/^\_/, ''))+'</div></div></div><br />'+
								'<span class="info">'+year+' in file format '+file.type+' v'+file.version+'</span></td>'+
								'<td></td>'+
							'</tr>';

						browser.playlist.push({
							filename:		file.filename,
							substname:		"",
							fullname:		"temp/test/" + file.filename,
							player: 		file.player,
							tags:			"",
							length: 		file.lengths,
							type:			file.type,
							version:		file.version,
							clockspeed:		"(Skipped)",
							sidmodel:		"(Skipped)",
							subtunes:		file.subtunes,
							startsubtune:	file.startsubtune == 0 ? 0 : file.startsubtune - 1,
							size:			file.datasize,
							address:		file.loadaddr,
							init:			file.initaddr,
							play:			file.playaddr,
							copyright:		file.copyright,
							stil:			file.stil,
							rating:			file.rating,
							hvsc:			"",
							symid:			0,
						});
					});
					$("#songs table").empty().append(files);

					// Let mobile devices use their own touch scrolling stuff
					if (browser.isMobile) {
						// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
						$(window).trigger("resize");
					} else {
						// Ugly hack to make custom scroll bar respect flexbox height
						$("#folders").height($("#folders").height())
							.mCustomScrollbar({
								axis: "y",
								theme: (parseInt(colorTheme) ? "light-3" : "dark-3"),
								setTop: (typeof scrollPos !== "undefined" ? scrollPos+"px" : "0"),
								scrollButtons:{
									enable: true,
									scrollAmount: 6,
								},
								mouseWheel:{
									scrollAmount: 150,
								},
								callbacks: {
									whileScrolling: function() {
										browser.currentScrollPos = this.mcs.top;
									}
								}
							});
					}
					DisableIncompatibleRows();
				});
			}
		});
	});

	/**
	 * Handle settings edit box and button for changing the password.
	 */
	$("#old-password").keydown(function(event) {
		if (event.keyCode == 13 && $("#old-password").val() !== "")
			$("#new-password").focus(); // Just go to next edit box
	}).keyup(function() {
		$("#new-password-button").removeClass("disabled");
		if ($("#old-password").val() !== "" && $("#new-password").val() !== "")
			$("#new-password-button").prop("disabled", false);
		else
			$("#new-password-button").prop("enabled", false).addClass("disabled");
	});

	$("#new-password").keydown(function(event) {
		if (event.keyCode == 13 && $("#old-password").val() !== "" && $("#new-password").val() !== "")
			$("#new-password-button").trigger("click");
	}).keyup(function() {
		$("#new-password-button").removeClass("disabled");
		if ($("#old-password").val() !== "" && $("#new-password").val() !== "")
			$("#new-password-button").prop("disabled", false);
		else
			$("#new-password-button").prop("enabled", false).addClass("disabled");
	});

	$("#new-password-button").click(function() {
		if ($("#old-password").val() == "" || $("#new-password").val() == "") return false;
		$(this).prop("enabled", false).addClass("disabled");
		$.post("php/account_new_password.php", {
			oldpwd: $("#old-password").val(),
			newpwd: $("#new-password").val()
		}, function(data) {
			browser.validateData(data, function(data) {
				$("#new-password-msg")
					.empty()
					.css("color", (data.message.toLowerCase() == "saved" ? GetCSSVar("--color-pwd-good") : GetCSSVar("--color-pwd-bad")))
					.append(data.message)
					.show();
				setTimeout(function() {
					$("#new-password-msg").fadeOut(250);
					$("#new-password-button").removeClass("disabled").prop("disabled", false);
				}, (data.message.toLowerCase() == "saved" ? 350 : 1000));
			});
		}.bind(this));
	});

	/**
	 * When exporting to a CSV file in settings.
	 */
	$("#export").click(function(){
		window.location.href = "export.php";
	});

	/**
	 * When resizing the window. Also affected by toggling the developer pane.
	 * 
	 * @param {*} event 
	 * @param {boolean} sundryIgnore	If specified and TRUE, ignores the sundry box.
	 */
	$(window).on("resize", function(event, sundryIgnore) {
		if (!sundryIgnore) {
			if ($(window).height() > 840 && !sundryToggle)
				ToggleSundry(false);
			else if ($(window).height() <= 840 && sundryToggle)
				ToggleSundry(true);
		}
		// Make sure the browser box always take up all screen height upon resizing the window
		$("#folders").height(0).height($("#songs").height() - 100);
		if (!browser.isMobile) {
			// Also make sure the scrollbar for dexter has the correct height
			$("#page .mCSB_scrollTools").css("height", $("#page").height() + 13);
			// Recalculate height for graph area too
			viz.initGraph(browser.chips);
			// And that the web site iframe has the correct height too
			$("#page .deepsid-iframe").height($("#page").outerHeight() - 61); // 24
		}
		$(".dialog-box").center();
	});

	/**
	 * When dragging the white line to resize the sundry box smaller or larger.
	 * 
	 * @param {*} event 
	 */
	$("#slider").on("mousedown touchstart", function(event) {
		event.preventDefault();
		$("body").on("mousemove touchmove", function(event) {
			event.preventDefault();
			var $sundry = $("#sundry"), diff = $("#slider").offset().top + 5 - event.pageY;
			var newHeight = $sundry.css("flex-basis").replace("px", "") - diff;
			$sundry.css({
				"flex-basis":	newHeight,
				"padding":		"6px 10px",
			});
			sundryHeight = newHeight;
			if (!sundryToggle) {
				sundryToggle = true;
				$("#sundry-tabs").find(".tab[data-topic='"+sundryTab+"']").addClass("selected");
				$("#sundry-ctrls").show();
			}
			$("#stopic-stil .mCSB_scrollTools").css("height", $("#stopic-stil").height() + 7);
			$("#stopic-tags .mCSB_scrollTools").css("height", $("#stopic-tags").height() + 7);
			$("#folders").height(0).height($("#songs").height() - 100);
		});
	});
	$("body").on("mouseup touchend", function() {
		$("body").off("mousemove touchmove");
	});

	/**
	 * Submit a login/register attempt.
	 * 
	 * @param {*} event 
	 */
	$("#userform").submit(function(event) {
		event.preventDefault();
		if ($("#username").val() === "" || $("#password").val() === "") return false;
		if (!userExists) {
			// Show a dialog confirmation box first
			CustomDialog({
				id: '#dialog-register',
				text: '<h3>Register and Login</h3>'+
					'<p>You are about to register the following user name with the password you just typed:</p>'+
					'<p style="font-size:20px;font-weight:bold;color:#2a2;">'+$("#username").val()+'</p><p>Okay to proceed?</p>',
				width: 389,
				height: 195,
			}, LoginOrRegister, function() {
				$("#response").empty().removeClass("good bad").append("Login or register to rate tunes");
				$("#username,#password").val("");
			});
		} else
			LoginOrRegister();
	});

	/**
	 * Function used just above.
	 */
	function LoginOrRegister() {
		$("#response").empty().removeClass("good bad").append("Hang on");
		$.post("php/account_login_register.php?register="+!userExists, $("#userform").serialize(), function(data) {
			browser.validateData(data, function(data) {
				if (data['result'] === false) {
					// PHP login script reported an error
					$("#response").empty().removeClass("good bad").addClass("bad").append(data['error']);
					return false;
				} else
					window.location.reload();
			});
		}.bind(this));
	}

	/**
	 * When typing a user name.
	 * 
	 * @param {*} event 
	 */
	$("#username").keyup(function(event) {
		if (event.keyCode == 13)
			$("#password").focus();
		else {
			// Throttle the reaction to the typing
			setTimeout(function() {
				// So does this username exist?
				$.post("php/account_exists.php", { username: $("#username").val() }, function(data) {
					browser.validateData(data, function(data) {

						userExists = data['exists'];
						$("#response").empty().removeClass("good bad").addClass("good").append(userExists
							? "User exists; click to log in"
							: "User name is available; type a password and log in");

					});
				}.bind(this));
			}, 350);
		}
	}).on("input", function() {
		// This event triggers if the field has been autocompleted
		$("#username").trigger("keyup");
	});

	/**
	 * When logging out.
	 * 
	 * @param {*} event 
	 */
	$("#logout").click(function(event) {
		event.preventDefault();
		$.post("php/account_logout.php",function() {
			window.location.reload();
		});
	});

	/**
	 * When changing one of the *STYLED* drop-down boxes.
	 * 
	 * Used here by the SID handler.
	 */
	$("div.styledSelect").change(function() {
		switch ($(this).prev("select").attr("name")) {
			case "select-emulator":
				// Selecting a different SID handler (emulator or SOASC)
				var $selected = $("#folders tr.selected");
				var isRowSelected = $selected.length,
					wasPlaying = ctrls.isPlaying(),
					mainVol = SID.mainVol;
				SID.unload();
				ctrls.selectButton($("#stop"));
				viz.allEmuButtonsOff();

				var emulator = $("#dropdown-emulator").styledGetValue();
				docCookies.setItem("emulator", emulator, "Infinity", "/");
				viz.setEmuButton(emulator);

				// If a different version of WebSid was used last then we need to refresh the browser
				if ((emulator == "websid" && isLegacyWebSid) || (emulator == "legacy") && !isLegacyWebSid) {
					window.location.reload();
					return false;
				}

				SID = null;
				delete SID;
				SID = new SIDPlayer(emulator);
				SID.mainVol = mainVol;
				SID.setVolume(1);

				// The color of the time bar should be unique for the chosen SID handler
				$("#time-bar").removeClass("websid legacy jssid soasc_auto soasc_r2 soasc_r4 soasc_r5").addClass(emulator)
					.css("cursor", SID.emulatorFlags.supportSeeking ? "pointer" : "default");

				$("#faster").removeClass("disabled");
				if (!SID.emulatorFlags.supportFaster) $("#faster").addClass("disabled");

				// Clear all red error rows
				var $tr = $("#folders tr");
				$tr.find(".entry").css("color", "");
				$tr.find("span.info").css("color", "");
				$tr.css("background", "");

				DisableIncompatibleRows();

				if ($selected.hasClass("disabled")) {
					// The new handler can't play this row so unselect it
					isRowSelected = wasPlaying = false;
					$selected.removeClass("selected");
				}

				if (SID.emulatorFlags.offline) {
					// Using the player buttons doesn't make sense for the "download" option
					ctrls.state("play/stop", "disabled");
					ctrls.state("subtunes", "disabled");
					ctrls.state("faster", "disabled");
					ctrls.state("loop", "disabled");
					$("#volume").prop("disabled", true);
				}

				if (isRowSelected && wasPlaying) {
					// Clicking the same row again is safest
					$selected.children("td.sid").trigger("click", ctrls.subtuneCurrent);
				} else if (wasPlaying) {
					ctrls.togglePlayOrPause();
					ctrls.selectButton($("#play-pause"));
					ctrls.setButtonPlay();
				} else if (!isRowSelected) {
					ctrls.state("play/stop", "disabled");
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					ctrls.state("faster", "disabled");
					ctrls.state("loop", "disabled");
					$("#volume").prop("disabled", true);
				}
				ctrls.emulatorChanged = true;
				UpdateURL();
				viz.animateBufferEnded();
				viz.startBufferEndedEffects();
				break;
		}
	});

	/**
	 * When the color theme toggle button is clicked.
	 * 
	 * A data attribute is set in the BODY element, and the theme class is toggled
	 * for all of the currently existing custom scrollbars.
	 */
	$("#theme-selector").click(function() {
		colorTheme ^= 1;
		$("#topic-profile").find("img.composer").each(function() {
			var $this = $(this);
			if ($this.attr("src") == "images/composer"+(colorTheme ? "" : "_dark")+".png")
				$this.attr("src", "images/composer"+(colorTheme ? "_dark" : "")+".png");
		});
		$("#dexter table.comments").find("img.avatar").each(function() {
			var $this = $(this);
			if ($this.attr("src") == "images/composer"+(colorTheme ? "" : "_dark")+".png")
				$this.attr("src", "images/composer"+(colorTheme ? "_dark" : "")+".png");
		});
		$("body").attr("data-theme", colorTheme ? "dark" : "")
			.find(colorTheme ? ".mCS-dark-3" : ".mCS-light-3")
			.removeClass(colorTheme ? "mCS-dark-3" : ".mCS-light-3")
			.addClass(colorTheme ? "mCS-light-3" : "mCS-dark-3");
		// Change the brand image too (if available)
		$("#brand-"+(colorTheme ? "light" : "dark")).hide();
		$("#brand-"+(colorTheme ? "dark" : "light")).show();
		// Disqus was implemented before the main folder for HVSC was so it doesn't know it exists
		/*browser.reloadDisqus((typeof browser.songPos != "undefined"
				? browser.playlist[browser.songPos].fullname.replace("/_High Voltage SID Collection", "")
				: ""), "");*/
		localStorage.setItem("theme", colorTheme);
	});

	/**
	 * When one of the "dexter" page tabs are clicked.
	 */
	$("#tabs .tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		// Store the custom scroll bar position as it is now for the tab we're about to leave
		var oldTopic = $("#tabs .selected").attr("data-topic");
		if (typeof oldTopic != "undefined") {
			tabPrevScrollPos[oldTopic].pos = tabPrevScrollPos[oldTopic].reset ? 0 : tabScrollPos;
			tabPrevScrollPos[oldTopic].reset = false;
		}

		$("#page").mCustomScrollbar("destroy").removeClass("big-logo");

		var topic = $this.attr("data-topic");

		// Select the new tab
		$("#tabs .tab").removeClass("selected");
		$this.addClass("selected");

		// Show the selected topic
		$("#page .topic,#sticky-csdb,#sticky-visuals").hide();
		$("#topic-"+topic).show();
		ShowDexterScrollbar(topic);

		// Show the big logo for the informational tabs only
		if (["about", "faq", "changes"].includes(topic) ||
			(topic == "profile" && browser.path == "" && (!browser.isSearching || $("#topic-profile table.root").length)) ||
			(topic == "profile" && $("#topic-profile table.rec-all").length))
				$("#page").addClass("big-logo");

		// If 'Disqus' tab is selected then hide the notification on it
		// if (topic === "disqus") $("#note-disqus").hide();

		// If 'CSDb' tab is selected
		if (topic === "csdb") {
			$("#note-csdb").hide()					// Hide notification
			$("#sticky-csdb").show();				// Show sticky header
		};

		// If 'Visuals' tab is selected show the sticky header
		if (topic === "visuals") {
			$("#sticky-visuals").show();
			if (typeof viz.visuals == "undefined")
				$("#sticky-visuals button.icon-piano").trigger("click");
		}

		// If 'GB64' tab is selected then hide the notification on it
		if (topic === "gb64") $("#note-gb64").hide();

		// If 'Remix' tab is selected then hide the notification on it
		if (topic === "remix") $("#note-remix").hide();

		// If 'Player' tab is selected then hide the notification on it
		if (topic === "player") $("#note-player").hide();

		// Always go to the bottom of the 'Debug' tab to show the latest stuff
		if (topic === "debug")
			$("#page").mCustomScrollbar("scrollTo", "bottom", { scrollInertia: 0 });

		// If 'Piano' view is selected then make the custom scroll bar transparent
		// NOTE: Must be hidden in other tabs or scrolling may become erratic.
		$("#dexter .mCSB_container").css("overflow", topic === "visuals" && $("#sticky-visuals .icon-piano").hasClass("button-on") ? "visible" : "hidden");

		// If 'Profile' tab is selected then refresh the charts if present
		// NOTE: If this is not done the charts will appear "flattened" towards the left side.
		if (topic === "profile" && typeof ctYears !== "undefined") {
			ctYears.update();
			ctPlayers.update();
		}
	});

	/**
	 * When one of the "sundry" box tabs are clicked.
	 */
	$("#sundry-tabs .tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		// If the box was minimized, restore it first
		if (!sundryToggle) ToggleSundry(false);

		$("#sundry-ctrls").empty(); // Clear corner controls
		$("#slider-button").hide();

		var prevTopic = $("#sundry-tabs .selected").attr("data-topic");

		// Select the new tab
		$("#sundry-tabs .tab").removeClass("selected");
		$this.addClass("selected");

		var stopic = $this.attr("data-topic");
		localStorage.setItem("sundrytab", stopic);

		switch (stopic) {
			case "stil":
				// See the 'UpdateURL()' function below
				if (!browser.isCGSC() && !browser.isTempTestFile()) $("#sundry-ctrls").append(reportSTIL);
				break;
			case "tags":
				$("#sundry-ctrls").append(
					'<input type="checkbox" id="showtags" name="showtagstoggle" class="unselectable"'+(showTags ? '' : 'un')+'checked />'+
					'<label for="showtags" class="unselectable" style="position:relative;top:-2px;">Show tags in SID rows</label>'
				);
				if ($("#songs tr.selected").length > 0)
					$("#slider-button").show();
				break;
			case "osc":
				// The oscilloscope view requires a minimum amount of vertical space
				var $sundry = $("#sundry");
				if ($sundry.css("flex-basis").replace("px", "") < 232)
					$sundry.css("flex-basis", 232);
				// Add corner controls
				$("#sundry-ctrls").append(
					'<label for"osc-zoom" class="unselectable">Min</label>'+
					'<input id="osc-zoom" type="range" min="1" max="5" value="'+viz.scopeZoom+'" step="1" />'+
					'<label for"osc-zoom" class="unselectable">Max</label>'+
					'<div style="display:inline-block;vertical-align:top;margin-left:13px;">'+
						'<input type="checkbox" id="sidwiz" name="sidwiztoggle" class="unselectable" '+(viz.scopeMode ? '' : 'un')+'checked />'+
					'</div>'+
					'<label for="sidwiz" class="unselectable">SidWiz</label>'
				);
				break;
		}

		$("#stopic-stil .mCSB_scrollTools,#stopic-tags .mCSB_scrollTools")
			.css("height", $("#stopic-"+prevTopic).height() + 7);
		$("#folders").height(0).height($("#songs").height() - 100);

		// Show the selected topic
		$("#sundry .stopic").hide();
		$("#stopic-"+stopic)
			.css("visibility", "hidden")
			.show();
		setTimeout(function() {
			// This small delay hides the "hiccup" that happens when the custom scrollbar is re-applied
			$("#stopic-"+stopic).css("visibility", "visible");
		}, 125);
	});

	/**
	 * When one of the ON/OFF toggle buttons are clicked in the settings page.
	 */
	$("#topic-settings .button-toggle").click(function(event) {
		var $this = $(event.target);
		if ($this.hasClass("button-toggle")) {
			// Checkbox style toggle button
			var state = $this.hasClass("button-off");
			$this.empty().append(state ? "On" : "Off");
			$this.removeClass("button-off button-on").addClass("button-"+(state ? "on" : "off"))

			var settings = {};
			if (event.target.id === "setting-first-subtune")
				settings.firstsubtune = state ? 1 : 0;
			else if (event.target.id === "setting-skip-tune")
				settings.skiptune = state ? 1 : 0;
			else if (event.target.id === "setting-mark-tune")
				settings.marktune = state ? 1 : 0;
			else if (event.target.id === "setting-skip-bad")
				settings.skipbad = state ? 1 : 0;
			else if (event.target.id === "setting-skip-long")
				settings.skiplong = state ? 1 : 0;
			else if (event.target.id === "setting-skip-short")
				settings.skipshort = state ? 1 : 0;

			$.post("php/settings.php", settings, function(data) {
				browser.validateData(data);
			});
		}
	});

	/**
	 * When the check box for turning Disqus ON or OFF is clicked.
	 * 
	 * This check box is only visible in the 'Disqus' tab.
	 */
	/*$("#disqus-toggle").click(function() {
		if ($(this).is(":checked")) {
			// Turn Disqus ON
			$("#disqus-title,#disqus_thread").show();
			// Disqus was implemented before the main folder for HVSC was so it doesn't know it exists
			browser.reloadDisqus(browser.playlist[browser.songPos].fullname.replace("/_High Voltage SID Collection", ""));
			browser.updateDisqusCounts();
		} else {
			// Turn Disqus OFF
			$("#disqus-title,#disqus_thread").hide();
		}
	});*/

	/**
	 * When clicking a thumbnail/title in a CSDb release row to open it internally.
	 */
	$("#topic-csdb").on("click", "a.internal", function() {
		// First cache the list of releases in case we return to it
		cacheCSDb = $("#topic-csdb").html();
		cacheSticky = $("#sticky-csdb").html();
		cacheTabScrollPos = tabScrollPos;
		cacheDDCSDbSort = $("#dropdown-sort-csdb").val();
		// Now load the new content
		browser.getCSDb("release", $(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking the 'BACK' button on a specific CSDb page to show the releases again.
	 */
	$("#topic-csdb,#sticky-csdb").on("click", "#go-back", function() {
		if (cacheBeforeCompo === "" && cacheCSDb === "") {
			// We have been redirecting recently so the tab must be refreshed properly
			browser.getCSDb();
			return;
		}
		$this = $(this);
		// Load the cache again (much faster than calling browser.getCSDb() to regenerate it)
		$("#topic-csdb").css("visibility", "hidden").empty()
			.append($this.hasClass("compo") ? cacheBeforeCompo : cacheCSDb);
		$("#sticky-csdb").empty().append($this.hasClass("compo") ? cacheStickyBeforeCompo : cacheSticky);
		// Adjust drop-down box to the sort setting
		$("#dropdown-sort-csdb").val(cacheDDCSDbSort);
		// Also set scroll position to where we clicked last time
		$("#page").mCustomScrollbar("scrollTo",
			($this.hasClass("compo") ? cachePosBeforeCompo : cacheTabScrollPos), { scrollInertia: 0 });
		// The 'onScroll' callback is not good enough and this is actually more safe
		setTimeout(function() {
			$("#topic-csdb").css("visibility", "visible");
		}, 150);
	});

	/**
	 * When clicking the 'BACK' button on a CSDb page to show the releases for the first time.
	 * 
	 * NOTE: This version is used where the release page had a link to the SID tune page.
	 */
	$("#topic-csdb,#sticky-csdb").on("click", "#go-back-init", function() {
		browser.getCSDb("sid", $(this).attr("data-id"));
	});

	/**
	 * When clicking the 'BACK' button on a player/editor page to show the list of them again.
	 */
	$("#topic-player").on("click", "#go-back-player", function() {
		if (cachePlayer == "") {
			// First time?
			$("#players").trigger("click");
		} else {
			$this = $(this);
			// Load the cache again
			$("#topic-player").css("visibility", "hidden").empty().append(cachePlayer);
			// Also set scroll position to where we clicked last time
			$("#page").mCustomScrollbar("scrollTo", cachePlayerTabScrollPos, { scrollInertia: 0 });
			// The 'onScroll' callback is not good enough and this is actually more safe
			setTimeout(function() {
				$("#topic-player").css("visibility", "visible");
			}, 150);
		}
	}),

	/**
	 * When clicking the 'BACK' button on a GameBase64 page to show the list of them again.
	 */
	$("#topic-gb64").on("click", "#go-back-gb64", function() {
		// Load the cache again
		$("#topic-gb64").css("visibility", "hidden").empty().append(cacheGB64);
		// Also set scroll position to where we clicked last time
		$("#page").mCustomScrollbar("scrollTo", cacheGB64TabScrollPos, { scrollInertia: 0 });
		// The 'onScroll' callback is not good enough and this is actually more safe
		setTimeout(function() {
			$("#topic-gb64").css("visibility", "visible");
		}, 150);
	}),

	/**
	 * When clicking the 'BACK' button on a GameBase64 page to show the list of them again.
	 */
	$("#topic-remix").on("click", "#go-back-remix", function() {
		// Load the cache again
		$("#topic-remix").css("visibility", "hidden").empty().append(cacheRemix);
		// Also set scroll position to where we clicked last time
		$("#page").mCustomScrollbar("scrollTo", cacheRemixTabScrollPos, { scrollInertia: 0 });
		// The 'onScroll' callback is not good enough and this is actually more safe
		setTimeout(function() {
			$("#topic-remix").css("visibility", "visible");
		}, 150);
	}),

	/**
	 * When clicking the 'SHOW' button on a CSDb page to show the full list of competition results.
	 */
	$("#topic-csdb").on("click", "#show-compo", function() {
		cacheBeforeCompo = $("#topic-csdb").html();
		cacheStickyBeforeCompo = $("#sticky-csdb").html();
		cachePosBeforeCompo = tabScrollPos;
		$this = $(this);
		browser.getCompoResults($this.attr("data-compo"), $this.attr("data-id"), $this.attr("data-mark"));
	});

	/**
	 * When clicking the arrow up button in the bottom of CSDb pages to scroll back to the top.
	 */
	$("#topic-profile,#topic-csdb,#topic-player").on("click", "button.to-top", function() {
		$("#page").mCustomScrollbar("scrollTo", "top");
	});

	/**
	 * When clicking an HVSC link in a competition results list to play a different SID tune.
	 */
	$("#topic-csdb").on("click", "a.compo-go", function() {
		// Move the SVG arrow to the HVSC path clicked in the competition results table
		$("#topic-csdb .compo-pos").remove();
		$("#topic-csdb .compo-bold").removeClass("compo-bold");
		$(this)
			.parents("td")
			.siblings("td.compo-arrow")
			.append('<span class="compo-pos"></span>')
			.parents("tr")
			.addClass("compo-bold");
	});

	/**
	 * When clicking the 'COMMENT' button to add a comment in a CSDb comment section.
	 * 
	 * NOTE: This opens a new web browser tab.
	 */
	$("#topic-profile,#topic-csdb,#topic-player").on("click", "#csdb-comment", function() {
		window.open("https://csdb.dk/"+$(this).attr("data-type")+"/addcomment.php?"+
			$(this).attr("data-type")+"_id="+$(this).attr("data-id"), "_blank");
	});

	/**
	 * When clicking the 'POST REPLY' button to add a post in a CSDb forum thread.
	 * 
	 * NOTE: This opens a new web browser tab.
	 */
	$("#topic-csdb").on("click", "#csdb-post-reply", function() {
		window.open("https://csdb.dk/forums/?action=reply&roomid="+$(this).attr("data-roomid")+
			"&topicid="+$(this).attr("data-topicid"), "_blank");
	});

	/**
	 * When clicking the SHOW/HIDE button for information text in the piano view.
	 */
	$("#info-piano-button").click(function() {
		var $this = $(this);
		if ($this.html() == "SHOW") {
			$this.empty().append("HIDE");
			$("#info-piano-text").slideDown();
		} else {
			$this.empty().append("SHOW");
			$("#info-piano-text").slideUp();
		}
	});

	/**
	 * When clicking a rating star in a composer profile.
	 */
	$("#topic-profile").on("click", ".folder-rating b", function(event) {
		// Clicked a star to set a rating for a folder or SID file
		if (!$("#logout").length) {
			// But must be logged in to do that
			alert("Login or register and you can click these stars to vote for this folder/composer.");
			return false;
		}
		var rating = event.shiftKey ? 0 : 5 - $(event.target).index();	// Remember stars are backwards (RTL; see CSS)
		var homePath = browser.path.substr(1);							// Assume no searching to begin with

		var $selected = $("#folders tr.selected");
		if (browser.isSearching && $selected.length) {
			// The user is searching and has clicked a specific song so the path is now specific to that
			var fullname = decodeURIComponent($selected.find(".entry").attr("data-name"));
			var sidFile = fullname.split("/").slice(-1)[0];
			homePath = fullname.replace(sidFile, "").slice(0, -1); // Lose trailing slash
		}

		$.post("php/rating_write.php", { fullname: homePath, rating: rating }, function(data) {
			browser.validateData(data, function(data) {
				var stars = browser.buildStars(data.rating);
				$("#topic-profile .folder-rating").empty().append(stars);

				if (browser.path.indexOf("Compute's Gazette SID Collection") !== -1 && browser.cache.folder !== "") {
					// Update the folder cache for CGSC too
					var $folders = $(browser.cache.folder),
						endName = homePath.indexOf("/") == -1 ? homePath : homePath.split("/").slice(-1)[0];
					$($folders).find('.name[data-name="'+encodeURIComponent(endName)+'"]')
					.parents("td").next().find(".rating")
					.empty().append(stars);
					// Has to be wrapped to get everything back
					browser.cache.folder = $("<div>").append($folders.clone()).html();
				}
			});
		});
		return false;
	});

	/**
	 * When clicking a 'redirect' plink to open an arbitrary SID file without reloading DeepSID.
	 * 
	 * NOTE: If the 'redirect' class is accompanied by a 'continue' class too, the skip buttons
	 * are not disabled as is usually the default.
	 */
	$("#topic-csdb,#sundry,#topic-stil,#topic-changes,#topic-player").on("click", "a.redirect", function() {
		var $this = $(this);
		if ($this.html() == "") return false;

		var solitary = !$this.hasClass("continue"),
			prevRedirect = typeof browser.songPos != "undefined"
				? browser.playlist[browser.songPos].fullname.replace(browser.ROOT_HVSC+"/_High Voltage SID Collection", "")
				: "";

		// Make the small play icon "active" bright
		if (!$this.hasClass("playing")) {
			$("a.redirect").removeClass("playing");
			$this.addClass("playing");
		}

		var fullname = $this.html();
		var path = "/_High Voltage SID Collection"+fullname.substr(0, fullname.lastIndexOf("/"));

		// @todo If using redirect for custom folders later then copy the 'browser.path' lines from 'fileParam' below.
		ctrls.state("root/back", "enabled");
		if (path != browser.path) {
			browser.path = path;
			browser.getFolder(0, undefined, undefined, function() {
				if (!ClickAndScrollToSID(fullname, solitary))
					$this.wrap('<del class="redirect"></del>').contents().unwrap();
			});
		} else if (!ClickAndScrollToSID(fullname, solitary))
			$this.wrap('<del class="redirect"></del>').contents().unwrap();
		// Clear caches to force proper refresh of CSDb tab after redirecting 
		cacheBeforeCompo = cacheCSDb = cacheSticky = cacheStickyBeforeCompo = "";
		UpdateURL();
		// Store SID location before redirecting in case the user wants to go back afterwards
		$("#redirect-back").empty().append(prevRedirect);
		return false;
	});

	/**
	 * When clicking recommendation box in the root.
	 */
	$("#topic-profile").on("mousedown", "table.recommended", function() { return false; });
	$("#topic-profile").on("mouseup", "table.recommended", function(event) {
		var link = "http://deepsid.chordian.net/?file=/"+$(this).attr("data-folder").replace("_High Voltage SID Collection/", "")+"/";
		if (event.which == 2 && event.button == 1)
			// Middle mouse button for opening it in a new browser tab
			window.open(link);
		else
			// Open in same browser tab
			window.location.href = link;
		return false;
	});

	/**
	 * When clicking the "RECOMMENDED" link in top.
	 */
	$("#recommended").click(function() {
		$(this).blur();
		if (recommended) recommended.abort();
		$("#topic-profile").empty().append(browser.loadingSpinner("profile"));

		if ($("#tabs .selected").attr("data-topic") !== "profile")
			$("#tab-profile").trigger("click");

		var loadingRecommended = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-profile").fadeIn(500);
		}, 250);

		recommended = $.get("php/root_recommended.php", function(data) {
			browser.validateData(data, function(data) {
				$("#page").removeClass("big-logo").addClass("big-logo");
				clearTimeout(loadingRecommended);
				if (parseInt(colorTheme))
					data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
				$("#topic-profile").empty().append(data.html);
				ResetDexterScrollBar("profile");
			});
		});
		return false;
	});

	/**
	 * When clicking the "FORUM" link in top to show a list of topics. Also
	 * used when clicking the 'BACK' button in a topic page.
	 */
	$("#sites,#sticky-csdb").on("click", "#forum,#topics", function() {
		$(this).blur();
		$("#topic-csdb").empty().append(browser.loadingSpinner("csdb"));
		$("#sticky-csdb").empty();

		if ($("#tabs .selected").attr("data-topic") !== "csdb")
			$("#tab-csdb").trigger("click");

		var loadingForum = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-csdb").fadeIn(500);
		}, 250);

		forum = $.get("php/csdb_forum_root.php", function(data) {
			browser.validateData(data, function(data) {
				clearTimeout(loadingForum);
				$("#sticky-csdb").empty().append(data.sticky);
				$("#topic-csdb").empty().append(data.html);
				ResetDexterScrollBar("csdb");
			});
		});
	});

	/**
	 * When clicking one of the topic thread links in the "FORUM" page.
	 */
	$("#topic-csdb").on("click", "a.thread", function() {
		$this = $(this);
		if (forum) forum.abort();
		$("#topic-csdb").empty().append(browser.loadingSpinner("csdb"));
		$("#loading-csdb").fadeIn(500);

		forum = $.get("php/csdb_forum.php", { room: $this.attr("data-roomid"), topic: $this.attr("data-topicid") }, function(data) {
			browser.validateData(data, function(data) {
				$("#sticky-csdb").empty().append(data.sticky);
				if (parseInt(colorTheme))
					data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
				$("#topic-csdb").empty().append(data.html);
				ResetDexterScrollBar("csdb");

				// Populate all "[type]/?id=" anchor links with HVSC path "plinks" instead
				$.each(["sid", "release"], function(index, type) {
					$("#topic-csdb table.comments").find("a[href*='"+type+"/?id=']").each(function() {
						var $this = $(this);
						$.get("php/csdb_sid_path.php", { type: type, id: $this.attr("href").split("=")[1] }, function(data) {
							browser.validateData(data, function(data) {
								if (data.path != "")
									$this.empty().append(data.path[0]).addClass("redirect"); // It is now a "plink"
								else if (data.name != "")
									$this.empty().append(data.name[0]); // At least set the name then
							});
						});
					});
				});
			});
		});
		return false;
	});

	/**
	 * When clicking the "PLAYERS" link in top.
	 * 
	 * @param {*} event 
	 * @param {boolean} noclick		If specified and TRUE, the 'Player' tab won't be clicked.
	 */
	$("#players").click(function(event, noclick){
		$(this).blur();
		if (players) players.abort();
		$("#topic-players").empty().append(browser.loadingSpinner("profile"));

		if ($("#tabs .selected").attr("data-topic") !== "player" && typeof noclick == "undefined")
			$("#tab-player").trigger("click");

		var loadingPlayers = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-profile").fadeIn(500);
		}, 250);

		players = $.get("php/player_list.php", function(data) {
			browser.validateData(data, function(data) {
				clearTimeout(loadingPlayers);
				$("#topic-player").empty().append(data.html);
				ResetDexterScrollBar("player");
				$("#note-csdb").hide();
			});
		});
		return false;
	});

	/**
	 * When clicking a row in the "PLAYER" list. This shows the page for the
	 * specific player/editor.
	 */
	$("#topic-player").on("click", ".player-entry", function() {
		$this = $(this);
		// First cache the list of releases in case we return to it
		cachePlayer = $("#topic-player").html();
		cachePlayerTabScrollPos = tabScrollPos;
		// Show the page
		browser.getPlayerInfo({id: $this.attr("data-id")});
		// Also search for the related players
		$("#dropdown-search").val("player");
		$("#search-box").val($this.attr("data-search").toLowerCase()).trigger("keyup");
		$("#search-button").trigger("click");
		return false;
	}),

	/**
	 * When clicking a title or thumbnail in a list of GameBase64 entries.
	 */
	$("#topic-gb64").on("click", ".gb64-list-entry", function() {
		// First cache the list of releases in case we return to it
		cacheGB64 = $("#topic-gb64").html();
		cacheGB64TabScrollPos = tabScrollPos;
		// Show the page
		browser.getGB64($(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking a title or thumbnail in a list of remix entries.
	 */
	$("#topic-remix").on("click", ".remix-list-entry", function() {
		// First cache the list of releases in case we return to it
		cacheRemix = $("#topic-remix").html();
		cacheRemixTabScrollPos = tabScrollPos;
		// Show the page
		browser.getRemix($(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking a home folder icon in a CSDb comment table.
	 */
	$("#topic-profile,#topic-csdb,#topic-player").on("click", ".home-folder", function() {
		browser.path = "/"+$(this).attr("data-home");
		ctrls.state("root/back", "enabled");
		browser.getFolder(0, undefined, undefined, function() {
			browser.getComposer(); // Comment this out to keep comment thread (not sure what users prefer here?)
		});
	});

	/**
	 * When clicking a square action button (play or pause) in the 'Remix' tab.
	 */
	$("#topic-remix").on("click", ".remix64-action", function() {
		var $this = $(this);
		var $expander = $this.parents("tr").next("tr").find(".remix64-expander");

		if ($this.find(".remix64-play").css("display") !== "none") {
			// Prepare an audio bar for the remix
			// NOTE: When it appears, the audio bar will get the song length from the MP3 file. This counts against
			// the hourly download limit at remix.kwed.org. This is why there's a check for previous visit first.
			if (!$expander.find("audio").length)
				$expander.find(".remix64-audio").empty().append(
					'<audio controls="" controlslist="nodownload">'+
						'<source src="'+$expander.attr("data-download")+'" type="audio/mpeg">'+
					'</audio><a href="'+$expander.attr("data-lookup")+'" target="_blank"><img src="images/download_remix.png" alt="Download at Remix.Kwed.Org" /></a>'
				);

			// Fade in the part of the connect pin just below the square button
			$this.parents("td").find(".down").fadeIn("fast");

			// Slide open some more space below the remix row itself (just calls the function if already open)
			$expander.animate({"height": "38px"}, 300, function() {
				// Start playing the audio remix (an event also takes care of the square button)
				$expander.find("audio")[0].play();
			});
		} else {
			// Pause the audio remix (an event also takes care of the square button)
			$expander.find("audio")[0].pause();
		}
	});

	/**
	 * A jQuery plugin required for the <AUDIO> play event below to work.
	 */
	$.createEventCapturing = (function() {
		var special = $.event.special;
		return function(names) {
			if (!document.addEventListener) return;
			if (typeof names == 'string') names = [names];
			$.each(names, function (i, name) {
				var handler = function(e) {
					e = $.event.fix(e);
					return $.event.dispatch.call(this, e);
				};
				special[name] = special[name] || {};
				if (special[name].setup || special[name].teardown) return;
				$.extend(special[name], {
					setup: function() {
						this.addEventListener(name, handler, true);
					},
					teardown: function() {
						this.removeEventListener(name, handler, true);
					}
				});
			});
		};
    })();

	/**
	 * When clicking play in an <AUDIO> element in the 'Remix' tab.
	 */
	$.createEventCapturing(["play"]);
	$("#topic-remix").on("play", "audio", function() {
		var $this = $(this)[0];
		// Stop any SID tune playing to avoid layering sound
		$("#stop").trigger("mouseup");
		SID.stop();
		$("#topic-remix audio").each(function() {
			var $sound = $(this)[0];
			if ($sound != $this) {
				// Stop all the other <AUDIO> elements too
				$sound.pause();
				$sound.currentTime = 0;
			}
		});
		// Hide the 'Play' button and show 'Pause' instead
		var $button = $(this).parents("tr").prev("tr").find("button"),
			$all = $("#topic-remix .remix64-action");
		$all.removeClass("button-idle button-selected").addClass("button-idle").find(".remix64-pause").hide();
		$all.find(".remix64-play").show();
		$button.addClass("button-selected").find(".remix64-play").hide();
		$button.find(".remix64-pause").show();
	});

	/**
	 * When clicking pause in an <AUDIO> element in the 'Remix' tab.
	 */
	$.createEventCapturing(["pause"]);
	$("#topic-remix").on("pause", "audio", function() {
		// Hide the 'Pause' button and show 'Play' instead
		$button = $(this).parents("tr").prev("tr").find("button");
		$button.removeClass("button-idle button-selected").addClass("button-idle").find(".remix64-pause").hide();
		$button.find(".remix64-play").show();
	});

	/**
	 * Click a SID file row and then scroll to center it in the browser list.
	 * 
	 * Only used by redirect "plinks" for now.
	 * 
	 * @param {string} fullname		The SID filename including folders.
	 * @param {boolean} solitary	If specified and FALSE, the tune will continue like in a playlist.
	 * 
	 * @return {boolean}			TRUE if the SID was found and is now playing.
	 */
	function ClickAndScrollToSID(fullname, solitary) {
		if (typeof solitary == "undefined") solitary = true;
		// Isolate the SID name, e.g. "music.sid"
		var sidFile = fullname.split("/").slice(-1)[0];
		var $tr = $("#folders tr").filter(function() {
			return $(this).find(".name").text().toLowerCase() == sidFile.toLowerCase();
		}).closest("tr");
		// Did we find the SID file?
		if ($tr.length) {
			// Yes; this is the <TR> row with the SID file we need to play
			var $trPlay = $("#folders tr").eq($tr.index());
			$trPlay.children("td.sid").trigger("click", [undefined, true, solitary]); // Don't refresh CSDb + [Stop when done]
			// Scroll the row into the middle of the list
			var rowPos = $trPlay[0].offsetTop,
				halfway = $("#folders").height() / 2 - 26; // Last value is half of SID file row height
			if (browser.isMobile)
				$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
			else
				$("#folders").mCustomScrollbar("scrollTo", rowPos > halfway ? rowPos - halfway : "top");
			return true;
		} else {
			// No; just stop playing
			$("#stop").trigger("mouseup").trigger("click");
			return false;
		}
	}

	/**
	 * Handle URL parameters.
	 */
	hashExcl = decodeURIComponent(location.hash); // Any Disqus link characters "#!" used?
	fileParam = hashExcl !== "" ? hashExcl.substr(2) : GetParam("file");
	if (fileParam.substr(0, 2) === "/_")
		fileParam = "/"+fileParam.substr(2); // Lose custom folder "_" character
	var searchQuery = GetParam("search"),
		selectTab = GetParam("tab"),
		selectSundryTab = GetParam("sundry"),
		playerID = GetParam("player"),
		typeCSDb = GetParam("csdbtype"),
		idCSDb = GetParam("csdbid");
		// Let mobile devices use their own touch scrolling stuff
	if (browser.isMobile) {
		// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
		$(window).trigger("resize");
	} else {
		// Hack to make custom scroll bar respect flexbox height
		$("#folders").height($("#folders").height())
			.mCustomScrollbar({
				axis: "y",
				theme: (parseInt(colorTheme) ? "light-3" : "dark-3"),
				scrollButtons:{
					enable: true,
					scrollAmount: 6,
				},
				mouseWheel:{
					scrollAmount: 150,
				}
			});
	}
	if (fileParam !== "" && fileParam.indexOf("\\") === -1) {
		// A HVSC folder or file was specified
		fileParam = fileParam.charAt(0) === "/" ? fileParam : "/"+fileParam;
		if (fileParam.substr(0, 6) == "/DEMOS" || fileParam.substr(0, 6) == "/GAMES" || fileParam.substr(0, 10) == "/MUSICIANS")
			fileParam = "/High Voltage SID Collection"+fileParam;
		var isFolder = fileParam.indexOf(".sid") === -1 && fileParam.indexOf(".mus") === -1,
			isSymlist = fileParam.substr(0, 2) == "/!" || fileParam.substr(0, 2) == "/$",
			isCompoFolder = fileParam.indexOf("/CSDb Music Competitions/") !== -1;
		browser.path = isFolder ? fileParam : fileParam.substr(0, fileParam.lastIndexOf("/"));
		if (browser.path.substr(0, 7).toLowerCase() != "/demos/" && browser.path.substr(0, 7).toLowerCase() != "/games/" && browser.path.substr(0, 11).toLowerCase() != "/musicians/" && browser.path.substr(0, 2) != "/!" && browser.path.substr(0, 2) != "/$")
			browser.path = "/_"+browser.path.substr(1); // It's an "extra" folder
		if (browser.path.substr(-1) === "/") browser.path = browser.path.slice(0, -1); // Remove "/" at end of folder
		if (isSymlist) browser.path = "/"+browser.path.split("/")[1]; // Symlist SID names could be using "/" chars
		if (isCompoFolder && !isFolder) browser.path = "/CSDb Music Competitions/"+browser.path.split("/")[2];
		ctrls.state("root/back", "enabled");

		browser.getFolder(0, undefined, undefined, function() {

			if (!isFolder) {

				// Isolate the SID name, e.g. "music.sid"
				var sidFile = isSymlist || isCompoFolder
					? fileParam.substr(fileParam.substr(1).indexOf("/") + 2)
					: fileParam.split("/").slice(-1)[0];
				if (isCompoFolder)
					sidFile = sidFile.substr(sidFile.substr(1).indexOf("/") + 2); // Skip also competition name

				var $tr = $("#folders tr").filter(function() {
					var $name = $(this).find(".name");
					if (!$name.length) return false;
					// First try to match the original SID name
					var decodedName = decodeURIComponent($name.attr("data-name")).toLowerCase().replace(/^\_/, '');
					var found = isCompoFolder
						// A compo folder is ALWAYS a HVSC path so it should be safe to search like this
						? decodedName.indexOf(sidFile.toLowerCase()) !== -1
						: decodedName == sidFile.toLowerCase();
					if (!found)
						// If still not found, try one more time with the table name (it could be a renamed playlist entry)
						found = $name.text().toLowerCase() == sidFile.toLowerCase();
					return found;
				}).closest("tr");
				// This is the <TR> row with the SID file we need to play
				$trAutoPlay = $("#folders tr").eq($tr.index());
				// Scroll the row into the middle of the list
				var rowPos = $trAutoPlay[0].offsetTop;
				var halfway = $("#folders").height() / 2 - 26; // Last value is half of SID file row height
				if (browser.isMobile)
					$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
				else
					$("#folders").mCustomScrollbar("scrollTo", rowPos > halfway ? rowPos - halfway : "top");
				// Before we play the user needs to click a overlay question to satisfy browser auto-play prevention
				$("#dialog-cover,#click-to-play-cover").show();

			} else if (GetParam("here") == "1") {
				setTimeout(function() {
					PerformSearchQuery(searchQuery);
					$("#loading").hide();
				}, 200);
			}
			if (isFolder) browser.showFolderTags();
			browser.getComposer();

		});
	} else if (searchQuery !== "")
		PerformSearchQuery(searchQuery);

	/**
	 * When clicking the overlay to satisfy the browser auto-play prevention. This
	 * hides the overlay and plays the SID row that was ready to go.
	 */
	$("#click-to-play-cover").click(function() {
		$("#dialog-cover,#click-to-play-cover").hide();
		var paramSubtune = GetParam("subtune");
		if (paramSubtune == "")
			$trAutoPlay.children("td.sid").trigger("click");
		else
			$trAutoPlay.children("td.sid").trigger("click", paramSubtune == 0 ? 0 : paramSubtune - 1);
	});

	// Select and show a "dexter" page tab	
	selectTab = selectTab !== "" ? selectTab : "profile";
	var selectView = "";
	if (selectTab === "flood") selectTab = "graph";
	if (selectTab === "piano" || selectTab === "graph") {
		selectView = selectTab.toLowerCase();
		selectTab = "visuals";
	}
	$("#tab-"+selectTab).trigger("click");
	if (selectView !== "") $("#sticky-visuals .icon-"+selectView).trigger("click"); // Select a visuals view

	// Select and show a "sundry" box tab (an URL parameter overrides the local storage setting)
	if (selectSundryTab === "") {
		selectSundryTab = localStorage.getItem("sundrytab");
		if (selectSundryTab == null) selectSundryTab = "stil";
	}
	if (selectSundryTab === "lyrics") selectSundryTab = "stil";
	if (selectSundryTab === "scope") selectSundryTab = "osc";
	$("#stab-"+selectSundryTab).trigger("click");

	// Show a specific player/editor in the 'Player' tab
	if (playerID != "") {
		browser.getPlayerInfo({id: playerID});	// Show the page
		$("#tab-player").trigger("click");
	} else
		$("#players").trigger("click", true);	// Otherwise just load the list of them

	// Show a specific CSDb entry (only loads the content of the CSDb tab)
	if (typeCSDb === "sid" || typeCSDb === "release") {
		browser.getCSDb(typeCSDb, idCSDb, false);
		$("#sticky-csdb").show(); // Show sticky header
	}

});

/**
 * Late stuff to be done after the page has loaded.
 */
$(window).on("load", function() {
	// Just in case the web browser prefilled the username and password fields
	setTimeout(function() {
		$("#username,#password").trigger("keydown");
	}, 350);
});

/**
 * Perform a search query, optionally with a type too.
 * 
 * @param {string} searchQuery	The search query string.
 */
function PerformSearchQuery(searchQuery) {
	$("#dropdown-search").val(GetParam("type") !== "" ? GetParam("type").toLowerCase() : "#all#");
	$("#search-here").prop('checked', GetParam("here") == "1");
	$("#search-box").val(searchQuery).trigger("keyup");
	$("#search-button").trigger("click");
}

/**
 * Show the custom scrollbar in a "dexter" page.
 * 
 * @param {string} topic	If specified, the tab topic.
 */
function ShowDexterScrollbar(topic) {
	// The 'Disqus' tab needs to use the default scrollbar to make sure the mouse wheel works
	if ($("#tabs .selected").attr("data-topic") !== "disqus") {
		$("#page").mCustomScrollbar({
			axis: "y",
			theme: (parseInt(colorTheme) ? "light-3" : "dark-3"),
			autoHideScrollbar: typeof topic !== "undefined"
				&& topic === "visuals" && $("#sticky-visuals .icon-piano").hasClass("button-on"), // Hide on piano
			scrollButtons:{
				enable: true,
				scrollAmount: 6,
			},
			mouseWheel:{
				scrollAmount: 150,
			},
			setTop: tabPrevScrollPos[topic].pos+"px",
			callbacks: {
				onCreate: function() {
					// Adjust scrollbar height to fit the up/down arrows perfectly
					$("#page .mCSB_scrollTools").css("height", $("#page").height() + 13);
					// Also trigger a resize to be sure it fits
					setTimeout(function() {
						$(window).trigger("resize");
					}, 1);
					tabScrollPos = 0;
				},
				onOverflowY: function() {
					// Enable the arrow button in the bottom of CSDb pages (for scrolling back to the top)
					var topic = $("#tabs .selected").attr("data-topic");
					if (topic === "csdb" || topic === "profile" || topic === "player" || browser.isCompoFolder)
						$("#topic-"+topic+" button.to-top").show();
				},
				whileScrolling: function() {
					tabScrollPos = this.mcs.top;
					tabPrevScrollPos[topic].reset = false;
				},
			},
		});
	}
}

/**
 * Reset the custom scrollbar in a "dexter" page to the top.
 */
function ResetDexterScrollBar(topic) {
	tabPrevScrollPos[topic].pos = 0;
	tabPrevScrollPos[topic].reset = true;
}

/**
 * Set the state of an ON/OFF toggle button in the settings tab.
 * 
 * @param {string} id		Part of the ID to be appended.
 * @param {boolean} state	1 or 0.
 */
function SettingToggle(id, state) {
	$("#setting-"+id)
		.empty()
		.append(state ? "On" : "Off")
		.removeClass("button-off button-on")
		.addClass("button-"+(state ? "on" : "off"));
}

/**
 * Get a value from the settings tab.
 * 
 * @param {string} id		Part of the ID to be appended.
 * 
 * @return {*}				The value.
 */
function GetSettingValue(id) {
	$setting = $("#setting-"+id);
	if ($setting.hasClass("button-toggle"))
		// Checkbox style toggle button; return boolean
		return $setting.hasClass("button-on");
}

/**
 * Resize the web site IFRAME then show it.
 * 
 * Called by an IFRAME inserted by the 'composer.php' script.
 */
function ResizeIframe() {
	$(window).trigger("resize");
	$("#page .deepsid-iframe").show();
}

/**
 * Minimize or maximize the sundry box in case of a small display. When the box
 * is minimized, you can still click a tab to restore its size or you can drag
 * the slider downwards to expand it.
 * 
 * All sundry tabs become unselected while minimized.
 * 
 * @param {boolean} shrink	TRUE to minimize, FALSE to return to before, or toggle if not specified.
 */
function ToggleSundry(shrink) {
	if (typeof shrink === "undefined") shrink = sundryToggle;
	if (!shrink) {
		$("#sundry").css({
			"flex-basis":	sundryHeight,
			"padding":		"6px 10px",
		});
		sundryToggle = true;
		$("#sundry-tabs").find(".tab[data-topic='"+sundryTab+"']").addClass("selected");
		$("#sundry-ctrls").show();
	} else {
		sundryHeight = $("#sundry").css("flex-basis");
		sundryTab = $("#sundry-tabs .selected").attr("data-topic");
		$("#sundry").css({
			"flex-basis":	0,
			"padding":		0,
		});
		sundryToggle = false;
		$("#sundry-tabs .tab").removeClass("selected"); // No tab selected anymore
		$("#sundry-ctrls").hide();
	}
}

/**
 * Disable table rows for folders incompatible with this SID handler. File rows
 * that emulators can't handle (such as BASIC tunes) will also be disabled.
 */
function DisableIncompatibleRows() {
	$("#songs table").children().each(function() {
		var $tr = $(this);
		var isSIDFile = $tr.find("td.sid").length;
		// Skip spacers, dividers and files for the general incompatibility field (folders only)
		if (!$tr.find(".spacer").length && !$tr.find(".divider").length && !isSIDFile) {
			$tr.removeClass("disabled");
			var $span = $tr.find(".name");
			if ($span.is("[data-incompat]") && ($span.attr("data-incompat").indexOf(SID.emulator) !== -1 ||
			($span.attr("data-incompat").indexOf("mobile") !== -1 && browser.isMobile)))
				$tr.addClass("disabled");
		} else if (isSIDFile && $tr.find(".name").attr("data-name").indexOf("BASIC.sid") !== -1) {
			// These emulators can't do tunes made in BASIC
			SID.emulator == "legacy" || SID.emulator == "jssid"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");
		/*} else if (isSIDFile && (SID.emulator == "websid" || SID.emulator == "legacy") &&
			($tr.find(".name").attr("data-name").indexOf("Comaland_tune_3.sid") !== -1 ||
			 $tr.find(".name").attr("data-name").indexOf("Fantasmolytic_tune_2.sid") !== -1)) {
			// @todo Replace this with a proper incompatibility system later.
			SID.emulator == "websid" || SID.emulator == "legacy"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");*/
		} else if (isSIDFile && SID.emulator == "legacy" &&
			$tr.find(".name").attr("data-name").indexOf("Acid_Flashback.sid") !== -1) { // WebSid HQ now supports this
			// @todo Replace this with a proper incompatibility system later.
			SID.emulator == "websid" || SID.emulator == "legacy"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");
		} else if (isSIDFile && ($tr.find(".name").attr("data-type") === "RSID" || $tr.find(".name").attr("data-name").indexOf(".mus") !== -1)) {
			// Hermit's emulator can't do neither any RSID tunes nor any MUS files
			SID.emulator == "jssid"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");
		}
	});
}

/**
 * Update the URL in the web browser address field.
 * 
 * @param {boolean} id		If specified, TRUE to skip file check.
 */
function UpdateURL(skipFileCheck) {
	if (browser.isTempTestFile()) return;

	var urlFile = browser.isSearching || browser.path == "" ? "&file=" : "&file="+browser.path.replace(/^\/_/, '/')+"/";
	// For competition folders, the 'encodeURIComponent()' makes the URL look ugly but it has to be done
	// or things might start falling apart when using special characters such as "&" or "#", etc.
	if (browser.path.indexOf("CSDb Music Competitions") !== -1 && !browser.isSearching)
		urlFile = "&file="+encodeURIComponent(browser.path);

	// Special case for HVSC as its collection name is not necessary (except in the HVSC root)
	if (urlFile.split("/").length - 1 > 2)
		urlFile = urlFile.replace("/High Voltage SID Collection", "")

	if (typeof skipFileCheck === "undefined" || !skipFileCheck) {
		try {
			urlFile = browser.playlist[browser.songPos].substname !== "" && !browser.isCompoFolder
				? urlFile += browser.playlist[browser.songPos].substname
				: urlFile += browser.playlist[browser.songPos].filename.replace(/^\_/, '');
		} catch(e) { /* Type error means no SID file clicked */ }
	}

	if (browser.isSearching || browser.isCompoFolder)
		urlFile = urlFile.replace("High Voltage SID Collection", "");

	// ?subtune=
	var urlSubtune = ctrls.subtuneCurrent ? "&subtune="+(ctrls.subtuneCurrent + 1) : "";

	var link = (urlFile+urlSubtune).replace(/&/, "?"); // Replace first occurrence only

	if (urlFile != prevFile) {
		prevFile = urlFile; // Need a new file clicked before we proceed in the browser history
		history.pushState({}, document.title, link);
	} else
		history.replaceState({}, document.title, link);

	// Update STIL change report link
	reportSTIL = '<a href="mailto:hvsc@c64.org?subject=STIL%20change&body=I%20have%20a%20STIL%20change%20request%20for:%0D%0A'+window.location.href+'%0D%0A%0D%0A" style="position:relative;top:-3px;font-size:11px;">Report a STIL change</a>';
	if ($("#sundry-tabs .selected").attr("data-topic") === "stil") {
		var $ctrls = $("#sundry-ctrls");
		$ctrls.empty();
		if (!browser.isCGSC()) $ctrls.append(reportSTIL);
	}
}

/**
 * Find all "redirect" classes (plinks) - typically in CSDb pages - and set the
 * small icon to a selected state if corresponding to any playing tune.
 */
function UpdateRedirectPlayIcons() {
	// Set "active" icon on all plinks that has the same tune (HVSC only)
	$("a.redirect").each(function() {
		var $this = $(this);
		if ($this.html() == browser.playlist[browser.songPos].fullname.replace(browser.ROOT_HVSC+"/_High Voltage SID Collection", ""))
			$this.removeClass("playing").addClass("playing");
	});
}

/**
 * Small plugin that centers an element in the middle of the entire window.
 */
$.fn.center = function () {
	return this.each(function(){
		var top = ($(window).height() - $(this).outerHeight()) / 2,
			left = ($(window).width() - $(this).outerWidth()) / 2;
		$(this).css({position:"fixed", margin:0, top: (top > 0 ? top : 0)+"px", left: (left > 0 ? left : 0)+"px"});
	});
};

/**
 * Show a custom dialog box.
 * 
 * @param {array} data				Associative array with data.
 * 									 - id		Must be set.
 * 								 	 - text		Must be set.
 * 								 	 - width	A default is used if not set.
 * 								 	 - height	A default is used if not set.
 * 									 - wizard	Don't fade cover if set and TRUE.
 * @param {function} callbackYes	Callback used if YES is clicked.
 * @param {function} callbackNo		Callback used if NO is clicked.
 */
function CustomDialog(data, callbackYes, callbackNo) {
	$(data.id).off("click", ".dialog-button-yes");
	$(data.id).off("click", ".dialog-button-no");

	var width = typeof data.width != "undefined" ? data.width : 400;
	var height = typeof data.height != "undefined" ? data.height : 200;

	var $dialog = $("#dialog-cover,"+data.id);

	$(data.id).css({ width: width, height: height }).center();
	$(data.id+" .dialog-text").empty().append(data.text);
	if (typeof data.wizard == "undefined" || !data.wizard)
		$dialog.fadeIn("fast");
	else
		$dialog.show();

	$(data.id).on("click", ".dialog-button-yes", function() {
		$dialog.hide();
		if (typeof callbackYes === "function")
			callbackYes.call(this);
		return false;
	});

	$(data.id).on("click", ".dialog-button-no", function() {
		$dialog.hide();
		if (typeof callbackNo === "function")
			callbackNo.call(this);
		return false;
	});

	$(data.id).on("click", ".dialog-cancel", function() {
		$("#dialog-cover,.dialog-box").hide();
		return false;
	});
}

/**
 * Get a custom variable (usually a color) from the CSS file, according to the
 * currently selected color scheme.
 * 
 * If the variable is not present in the CSS file for the dark theme, it will
 * automatically default to the ":root" variable.
 * 
 * @param {string} cssVar	The custom variable name.
 */
function GetCSSVar(cssVar) {
	return $(parseInt(colorTheme) ? "[data-theme='dark']" : ":root").css(cssVar);
}

/**
 * Display some text in the debug tab.
 * 
 * @param {number} format	Format constant (defined in the top of this file).
 * @param {string} text		String to display.
 * @param {*} value			If specified, value associated with text.
 */
function _(format, text, value) {
	if (!isDebug) return;

	switch (format) {
		case _SECTION:
			text = ($("#topic-debug tr").length ? '<tr><td colspan="20" class="top1"></td></tr><tr><td colspan="20" class="top2"></td></tr>': '')+
				'<tr><th colspan="20">'+text+'</th></tr>';
			break;
		case _HEADER:
			text = '<tr><th>&nbsp;&nbsp;&nbsp;</th><th colspan="19">'+text+'</th></tr>';
			break;
		case _RESULTS:
			if (typeof text == "undefined") text = "Data";
			text = '<tr><th>&nbsp;&nbsp;&nbsp;</th><th colspan="19">&#11206; '+text+'</th></tr>';
			break;
		case _PARAM:
			text = _Value("param", text, value);
			break;
		case _PLAYLIST:
			text = _Value("playlist", text, value);
			break;
		case _OBJECT:
			text = '<tr><td>&nbsp;&nbsp;&nbsp;</td><td class="first">object '+text+'</td><td class="second">'+JSON.stringify(value)+'</td></tr>';
			break;
		case _DATA:
			text = _Value("data", text, value);
			break;
		default:
	}
	$("#topic-debug table").append(text);
	$("#page").mCustomScrollbar("scrollTo", "bottom", { scrollInertia: 300 });
}

/**
 * Return the most typical piece of HTML used for displaying debug info.
 * 
 * @param {string} type		Type of variable.
 * @param {string} text		Text to expand on type.
 * @param {string} value	The value of the variable.
 */
function _Value(type, text, value) {
	if (value === "") value = '<span class="dim">empty</span>';
	return '<tr><td>&nbsp;&nbsp;&nbsp;</td><td class="first">'+type+'.'+text+'</td><td class="second">'+value+'</td></tr>';
}

/**
 * Plugin with one easing copied from "jquery.easing.1.3.js" by Robert Penner
 * and George McGinley Smith. Use it in the .animate() jQuery method.
 *
 * @link http://gsgd.co.uk/sandbox/jquery/easing/
 */
$.extend($.easing,{
	easeOutQuint: function (x, t, b, c, d) {
		return c*((t=t/d-1)*t*t*t*t + 1) + b;
	},
});

/**
 * Get a parameter value from the current URL (or optional custom string).
 * 
 * NOTE: This function is compact but has one flaw; it tends to find words
 * inside other words. For example, if you have "tab" and "stab" as URL
 * parameters, using "stab" may also invoke "tab" as well. Make sure you
 * only use unique parameter names that can't be confused like that.
 * 
 * @param {string} name		Parameter to search for.
 * 
 * @return {string}			Value (empty if non-existent or equal to nothing).
 */
function GetParam(name) {
	return decodeURIComponent((RegExp(name + '=' + '(.+?)(&|$)').exec(location.search.replace(/\+/g, " "))||[,""])[1]);
}