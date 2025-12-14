
/**
 * DeepSID / Main
 */

var $=jQuery.noConflict();

// Namespace for global variables and functions
// @todo Move globals below into it and adapt where used
var main = {
	factoidTypeTop:		0,
	factoidTypeBottom:	0,
	sundryBoxShow:		true,
};

var cacheCSDb = cacheSticky = cacheStickyBeforeCompo = cacheCSDbProfile = cacheBeforeCompo = cachePlayer = cacheGB64 = cacheRemix = prevFile = sundryTab = reportSTIL = "";
var cacheTabScrollPos = cachePlayerTabScrollPos = cacheGB64TabScrollPos = cacheRemixTabScrollPos = cachePosBeforeCompo = cacheDDCSDbSort = peekCounter = sundryHeight = 0;
var recommended = forum = players = $trAutoPlay = null, showTags, fastForwarding, registering = blockNextEnter = false;
var logCount = 1000, isMobile, miniPlayer, cornerMessageTimer;

var isMobile = $("body").attr("data-mobile") !== "0";
var isNotips = $("body").attr("data-notips") !== "0";
var miniPlayer = $("body").attr("data-mini");

var tabPrevScrollPos = {
	profile:	{ pos: 0, reset: false },
	csdb:		{ pos: 0, reset: false },
	gb64:		{ pos: 0, reset: false },
	remix:		{ pos: 0, reset: false },
	player:		{ pos: 0, reset: false },
	stil:		{ pos: 0, reset: false },
	visuals:	{ pos: 0, reset: false },
	settings:	{ pos: 0, reset: false },
	changes:	{ pos: 0, reset: false },
	faq:		{ pos: 0, reset: false },
	about:		{ pos: 0, reset: false },
	admin:		{ pos: 0, reset: false },
}

// One timer per tracking event type
const trackingTimers = Object.create(null);

// Tracking acceptance timeouts (ms)
const TRACK_DELAY = {
	"enter:folder":		5000,
	"start:sid":		5000,
	"select:emulator":	0
};

const factoidMessage = [
	"0. Show nothing",
	"1. Show tags",
	"2. Internal database ID",
	"3. Song length",
	"4. Type (PSID/RSID) and version",
	"5. Compatibility (e.g. BASIC)",
	"6. Clock speed (PAL/NTSC)",
	"7. SID model (6581/8580)",
	"8. Size in bytes (decimal)",
	"9. Start and end address (hexadecimal)",
	"10. HVSC or CGSC update version",
	"11. CSDb SID ID",
	"12. Game status (RELEASE/PREVIEW)",
	"13. Number of CSDb entries",
	"14. Production label",
];

const isFirefox = typeof InstallTrigger !== "undefined"; // Firefox can scroll more smoothly than Chrome

const PATH_UPLOADS = "_SID Happens";
const PATH_SID_FM = PATH_UPLOADS + "/SID+FM";

$(function() { // DOM ready

	// Background call of the 'backup_tables.php' script (run at most once every 24 hours)
	if (DEEPSID_BACKUP_DUE) {
		$.ajax({
			url: "php/backup_worker.php",
			method: "GET",
			cache: false
		});
	}

	// Make sure the ratings cache is ready
	$.get("php/rating_cache.php", function(data) {
		browser.validateData(data);
	});

	isMobile = $("body").attr("data-mobile") !== "0";
	isNotips = $("body").attr("data-notips") !== "0";
	miniPlayer = parseInt($("body").attr("data-mini"));

	// Get the emulator last used by the visitor
	var storedEmulator = docCookies.getItem("emulator");
	if (storedEmulator == null) {
		// Set a default emulator
		if (isMobile)
			storedEmulator = "legacy";	// Legacy WebSid for mobile devices
		else
			storedEmulator = "websid";	// The best WebSid for desktop computers
	}

	// Don't show tags on mobile devices as the dragging there might give way to sideways scrolling
	showTags = isMobile ? false : localStorage.getItem("showtags") !== "false";

	// However, a URL switch may TEMPORARILY override the stored emulator
	var emulator = GetParam("emulator").toLowerCase();
	if ($.inArray(emulator, [
		"resid",
		"jsidplay2",
		"websid",
		"legacy",
		"hermit",
		"webusb",
		"asid",
		"lemon",
		"youtube",
		"download",
		"silence",
	]) === -1) emulator = storedEmulator;

	HandleTopBox(emulator);

	// Default factoid in top is song length
	main.factoidTypeTop = parseInt(localStorage.getItem("factoidTop") ?? 3, 10);
	// Default factoid in bottom is tags
	main.factoidTypeBottom = parseInt(localStorage.getItem("factoidBottom") ?? 1, 10);
	UpdateFactoidButton(main.factoidTypeBottom);

	SID = new SIDPlayer(emulator);
	ctrls = new Controls();
	browser = new Browser();
	viz = new Viz(emulator);

	// Set the main volume that was used the last time
	var vol = localStorage.getItem("volume");
	if (vol == null) vol = 1;
	SID.setMainVolume(vol);
	$("#volume").val(vol * 100);

	// Currently only influenced by the "?lemon=1" switch in index.php
	if (isNotips) browser.annexNotWanted = true;

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
	
	$("#dropdown-topleft-emulator,#dropdown-settings-emulator")
		.styledSelect("emulator")
		.styledSetValue(emulator);

	// Show the appropriate advanced settings for the SID handler
	$("#topic-settings .settings-advanced").hide();
	$("#topic-settings .settings-advanced-" + emulator).show();

	// Doesn't work correctly and I can't test it as I don't have a MIDI device
	/*$("#asid-midi-outputs")
		.styledSelect("midi-outputs");*/

	// Assume 1SID (most common) thus hide the extra filter sections on the pianos
	$("#visuals-piano .piano-filter1,#visuals-piano .piano-filter2").hide();

	$("#time-bar").addClass(emulator)
		.css("cursor", SID.emulatorFlags.supportSeeking ? "pointer" : "default");

	/**
	 * Handle root tracking. This is not related to event tracking.
	 */

		// 1. Ping once shortly after load
		$(document).ready(function() {
			setTimeout(PingTracking, 1000);
		});

		// 2. Periodic ping
		setInterval(function() {
			PingTracking();
		}, 4 * 60 * 1000); // Every 4 minutes

		// 3. Ping when tab becomes visible again
		$(document).on("visibilitychange", function() {
			if (!document.hidden) PingTracking();
		});

		// 4. Ping on unload
		$(window).on("beforeunload", function() {
			PingTracking();
		});

	/**
	 * Handle hotkeys.
	 * 
	 * NOTE: Hotkeys for voices ON/OFF are handled in 'viz.js' only.
	 * 
	 * @param {*} event 
	 */
	$(window).on("keydown", function(event) {
		if (!$("#dialog-cover").is(":visible") && !$(".styledSelect").hasClass("active")) {
			const tag = document.activeElement.tagName.toLowerCase();
			if (["input", "textarea", "select"].indexOf(tag) === -1) {

				// Key '+' - search command (this version to ensure compatibility)
				if (event.key === '+' || event.code === 'NumpadAdd' || (event.key === '=' && event.shiftKey)) {
					$("#search-box").focus().val("+");
					return false;
				}

				switch (event.keyCode) {

					case 220:	// Keydown key below 'Escape' - fast forward

						// Fast forward ON
						if (!fastForwarding) {
							if (event.shiftKey) {
								// Create a fake middle mouse button down event
								var e = $.Event("mousedown");
								e.which = 2;   // MMB
								e.button = 1;  // MMB (in standard DOM)
								$("#faster").trigger(e);
							} else {
								// Normal left mouse fallback
								$("#faster").trigger("mousedown");
							}
						}
						break;

					case 32:	// Don't use 'Space' to scroll down

						if (document.activeElement.closest("#page")) return; // Allow in pages

						event.preventDefault();
						break;

					case 38:	// Keydown 'ARROW-UP' - move keyboard-selected SID row up

						event.preventDefault();

						var $tr = $("#songs tr"),
							indexUp = browser.kbSelectedRow;

						while (indexUp > 0) {
							indexUp--;
							if (!$tr.eq(indexUp).hasClass("disabled")) {
								browser.kbSelectedRow = indexUp;
								browser.moveKeyboardSelection(indexUp, true);
								break;
							}
						}
						break;

					case 40:	// Keydown 'ARROW-DOWN' - move keyboard-selected SID row down

						event.preventDefault();

						var $tr = $("#songs tr"),
							indexDown = browser.kbSelectedRow;
						const rowCount = $tr.length;

						while (indexDown < rowCount - 1) {
							indexDown++;
							if (!$tr.eq(indexDown).hasClass("disabled")) {
								browser.kbSelectedRow = indexDown;
								browser.moveKeyboardSelection(indexDown, true);
								break;
							}
						}
						break;

					case 36: 	// Keydown 'HOME' - move keyboard-selected SID row to top

						if (document.activeElement.closest("#page")) return; // Ignore if in pages
					
						event.preventDefault();
						browser.moveKeyboardToFirst();
						break;

					case 35: 	// Keydown 'END' - move keyboard-selected SID row to bottom

						if (document.activeElement.closest("#page")) return; // Ignore if in pages

						event.preventDefault();
						var $tr = $("#songs tr");
						for (var i = $tr.length - 1; i >= 0; i--) {
							if (!$tr.eq(i).hasClass("disabled")) {
								browser.kbSelectedRow = i;
								browser.moveKeyboardSelection(i, false);
								break;
							}
						}
						break;					

					case 33: 	// Keydown 'PageUp' - move keyboard-selected SID row one page up
					case 34: 	// Keydown 'PageDown' - move keyboard-selected SID row one page down

						event.preventDefault();

						var $container = $("#folders");
						var $allRows = $container.find("tr");
						var $selectableRows = $allRows.filter(":not(.disabled)");

						// Current selected full row
						const $currentFullRow = $allRows.eq(browser.kbSelectedRow);

						// Find index in filtered selectable rows
						const currentFilteredIndex = $selectableRows.index($currentFullRow);

						// Fallback if somehow not found (e.g., stale state)
						if (currentFilteredIndex === -1) break;

						// Measure row height of current type
						const rowHeight = $currentFullRow.outerHeight();
						const containerHeight = $container.height();
						const rowsPerPage = Math.floor(containerHeight / rowHeight);

						// Calculate new index in the filtered list
						var newFilteredIndex = currentFilteredIndex + (event.keyCode === 34 ? rowsPerPage : -rowsPerPage);
						newFilteredIndex = Math.max(0, Math.min(newFilteredIndex, $selectableRows.length - 1));

						// Get the actual target row and index in full list
						const $newTargetRow = $selectableRows.eq(newFilteredIndex);
						const fullIndex = $allRows.index($newTargetRow);

						// Update and scroll to the new position
						browser.kbSelectedRow = fullIndex;
						browser.moveKeyboardSelection(fullIndex, false);
						break;

					default:
				}
			}
		}
	}).on("keyup", function(event) {
		if (!$("#dialog-cover").is(":visible") && !$(".styledSelect").hasClass("active")) {
			const tag = document.activeElement.tagName.toLowerCase();
			if (["input", "textarea", "select"].indexOf(tag) === -1) {

				switch (event.keyCode) {

					case 220:	// Keyup key below 'Escape' - fast forward

						// Fast forward OFF
						$("#faster").trigger("mouseup");
						fastForwarding = false;
						break;

					case 27:	// Keyup 'Esc' - cancel search mode

						// @todo Escaping custom dialog box should not escape search mode!
						break;
		
					case 32:	// Keyup 'Space' - play/pause

						if (document.activeElement.closest("#page")) return; // Ignore if in pages

						$("#play-pause").trigger("mouseup");
						break;
		
					case 80:	// Keyup 'p' - pop-up window

						// Open a pop-up window with only the width of the #panel area
						window.open("//deepsid.chordian.net/?mobile=1&emulator=websid", "_blank",
							"'left=0,top=0,width=450,height="+(screen.height-150)+",scrollbars=no'");
						break;

					case 67:	// Keyup 'c' - refresh compo cache - ADMIN ONLY

						if (browser.isCompoFolder && $("#logged-username").text() == "JCH") {
							$.post("php/csdb_compo_clear_cache.php",
								{ competition: browser.path.replace("/CSDb Music Competitions/", "") }, function(data) {
								browser.validateData(data, function() {
									// Now reload the folder to automatically refresh the cache
									browser.getFolder();
								});
							}.bind(this));
						}
						break;

					case 83:	// Keyup 's' - open/close sundry box

						// Toggle the sundry box minimized or restored
						ToggleSundry();
						$(window).trigger("resize");
						break;

					case 76:	// Keyup 'l' - load a SID file for local testing

						// Upload and test one or more external SID tune(s)
						$("#upload-test").trigger("click");
						break;

					case 66:	// Keyup 'b' - go back from "plink"

						$("a.redirect").removeClass("playing");
						$("#redirect-back").trigger("click");
						break;

					case 106:	// Keyup 'Keypad Multiply' - edit player info - ADMIN ONLY

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
										RefreshFolder();
									});
								});
							});
						}
						break;

					case 8:		// Keyup 'BACKSPACE' - with or without SHIFT held down

						if (event.shiftKey) {
							// Keyup 'SHIFT+BACKSPACE' - click 'BACK' button on the visible tab page
							// Case-insensitive click only in the currently selected tab
							$(".tab.selected").each(function() {
								$("#sticky-" + $(this).data("topic") + " button").filter(function() {
									return /back/i.test($(this).text());
								}).click();
							});
						} else {
							// Keyup 'BACKSPACE' - browse back to parent folder
							CancelTrackType("enter:folder");
							$("#folders").focus();
							$("#folder-back").trigger("click");
						}
						break;

					case 70:	// Keyup 'f' - refresh current folder

						RefreshFolder();
						break;

					case 84:	// Keyup 't' - open dialog box for editing tags

						$("#songs tr.selected").find(".edit-tags").trigger("click");
						break;

					case 89:	// Keyup 'y' - toggle showing tags on or off

						showTags = !showTags;
						$("#showtags").prop("checked", showTags);
						showTags
							? $("#songs .tags-line").css("visibility", "")			//.show()
							: $("#songs .tags-line").css("visibility", "hidden")	//.hide();

						localStorage.setItem("showtags", showTags); // Boolean is stored as a string

						BrowserMessage("Show tags: "+(showTags ? "ON" : "OFF"));
						break;

					case 73:	// Keyup 'i' - cycle through factoid types (top)

						main.factoidTypeTop++;
						if (main.factoidTypeTop == 1) main.factoidTypeTop++; // Skip tags
						if (main.factoidTypeTop > 14) main.factoidTypeTop = 0;

						localStorage.setItem("factoidTop", main.factoidTypeTop);
						RefreshFolder();
						break;

					case 85:	// Keyup 'u' - cycle through factoid types (bottom)

						main.factoidTypeBottom++;
						if (main.factoidTypeBottom > 14) main.factoidTypeBottom = 0;

						SelectFactoid(main.factoidTypeBottom);
						break;

					case 37:	// Keyup 'ARROW-LEFT' - skip to previous (+ SHIFT to emulate auto-progress)

						$("#folders").focus();
						$("#skip-prev").trigger("mouseup", event.shiftKey ? false : undefined);
						break;

					case 39:	// Keyup 'ARROW-RIGHT' - skip to next (+ SHIFT to emulate auto-progress)

						$("#folders").focus();
						$("#skip-next").trigger("mouseup", event.shiftKey ? false : undefined);
						break;

					case 13:	// Keyup 'ENTER' - click the row keyboard-selected row

						// Blocking next keyup is used by the custom dialog box
						if (!isMobile && !blockNextEnter) {
							$("#songs tr").eq(browser.kbSelectedRow).trigger("click");
						} else {
							blockNextEnter = false;
						}
						break;

					case 65:	// Keyup 'a' - test something

						log("test");

						SID.setVolume(0.3);
						//SID.WebSid.resetSampleRate(5050); // Lowest possible rate for max FF speed = 3277
						SID.speed(10); // Max allowed by WebSid emulator is 14
						setTimeout(() => {
							$("#faster").trigger("mouseup");
						}, 2000); // 2 seconds fast forwards ~20-30 seconds of playtime
						break;

					default:
				}
			}
		}
	});

	/**
	 * Reveal the triangular corner buttons when hovering near the bottom of the
	 * song browser. This includes hovering on the search bar.
	 */
	$(document).on("mousemove", function(event) {
		var $songs = $("#songs");
		var rect = $songs[0].getBoundingClientRect();
		var inX = (event.clientX >= rect.left && event.clientX <= rect.right);
		// Integer = height of '#corner-buttons' + '#search' combined
		var nearBottom = (event.clientY >= (rect.bottom - 110) && event.clientY <= (rect.bottom + 8));

		$songs.toggleClass("cb-prox", inX && nearBottom);
	});

	$("#songs").on("mouseleave", function () {
		$(this).removeClass("cb-prox");
	});	

	/**
	 * When clicking one of the two triangular corner buttons.
	 */
	$("#corner-buttons").on("click", function(event) {
		var $this = $(event.target);
		if ($this.hasClass("corner-right")) {
			// Cycle factoid
			$(window).trigger($.Event("keyup", { key: "u", code: "KeyU", keyCode: 85, which: 85 }));
		} else if ($this.hasClass("corner-left")) {
			// Toggle tags ON/OFF
			$(window).trigger($.Event("keyup", { key: "y", code: "KeyY", keyCode: 89, which: 89 }));
		}
	});

	/**
	 * Handle key presses in the dialog box for editing a file row.
	 * 
	 * @param {*} event 
	 */
	$("#dialog-edit-file").on("keydown", function(event) {
		if (event.keyCode == 13) {
			$("#dialog-edit-file .dialog-button-yes").trigger("click");	// Click 'OK' button
			$("#dialog-edit-file input").blur();
			return false;
		}
	});

	/**
	 * Always lose focus on a button to avoid clashing with a keyboard-
	 * controlled SID row.
	 * 
	 * Doesn't work with VISUALS ON/OFF which has its own blur().
	 */
	$(document).on("click", "button", function() {
		this.blur();
	});

	/**
	 * When scrolling up or down in a dexter page.
	 */
	$("#page").on("scroll", function() {
		tabPrevScrollPos[$("#tabs .selected").attr("data-topic")].reset = false;
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

					$("#dropdown-topleft-emulator,#dropdown-settings-emulator")
						.styledOptionState("resid jsidplay2 websid legacy hermit webusb asid", "enabled")
						.styledOptionState("lemon youtube", "disabled");
					$("#path").css("top", "5px").empty().append("Temporary emulator testing");
					$("#stab-stil,#tab-stil").empty().append("STIL");
					ctrls.showNewsImage(false);

					// Only disable the ".." button
					$("#folder-root,#folder-back").removeClass("disabled");
					$("#folder-back").addClass("disabled");

					// Disable tabs useless to the temporary testing
					$("#tab-csdb,#tab-gb64,#tab-remix,#tab-stil").removeClass("disabled").addClass("disabled");

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

					// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
					$(window).trigger("resize");
					//SetScrollTopInstantly("#folders", scrollPos);
					SetScrollTopInstantly("#folders", 0);
					browser.moveKeyboardToFirst();
					DisableIncompatibleRows();
				});
			}
		});
	});

	/**
	 * Handle settings edit box and button for changing the password.
	 * 
	 * @param {*} event 
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
		return false;
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
		return false;
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
	 */
	$(window).on("resize", function() {
		// Make sure the browser box always take up all screen height upon resizing the window
		$("#folders").height(0).height($("#songs").height() - 100);
		if (!miniPlayer && !isMobile) {
			// Recalculate height for graph area too
			viz.initGraph(browser.chips);
			// And that the web site iframe has the correct height too
			$("#page .deepsid-iframe").height($("#page").outerHeight() - 61); // 24
		}
		$(".dialog-box").center();

		browser.positionFocusExplainer();		
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
			if (!main.sundryBoxShow) {
				main.sundryBoxShow = true;
				$("#sundry-tabs").find(".tab[data-topic='"+sundryTab+"']").addClass("selected");
				$("#sundry-ctrls").show();
			}
			$("#folders").height(0).height($("#songs").height() - 100);

			// Remove the FOLDERS button for the 'Tags' tab if the sundry box is too small
			if (browser.sliderButton && $("#sundry-tabs .selected").attr("data-topic") == "tags")
				sundryHeight < 37 ? $("#slider-button").hide() : $("#slider-button").show();
		});
	});
	$("body").on("mouseup touchend", function() {
		$("body").off("mousemove touchmove");
	});

	/**
	 * Click 'Register' in the login/register response line.
	 */
	$("#response").on("click", "a.reg-new", function() {
		$("#label-username").empty().append('<span class="new-user">New</span>');
		$("#label-password").empty().append('<span class="new-user">Pw</span>');
		$("#response").empty().removeClass("good bad").append('Type the user name and password | <a href="#" class="reg-cancel">Cancel</a>');
		$("#username,#password").val("");
		$("#reg-login-button").prop("disabled", false).removeClass("disabled");
		registering = true;
		return false;
	});

	/**
	 * Click 'Cancel' in the login/register response line.
	 */
	$("#response").on("click", "a.reg-cancel", function() {
		$("#label-username").empty().append('User');
		$("#label-password").empty().append('Pw');
		$("#response").empty().removeClass("good bad").append('Login or <a href="#" class="reg-new">register</a> to rate tunes');
		$("#username,#password").val("");
		$("#reg-login-button").prop("disabled", false).removeClass("disabled");
		registering = false;
		return false;
	});

	/**
	 * Submit a login/register attempt.
	 * 
	 * @param {*} event 
	 */
	$("#userform").submit(function(event) {
		event.preventDefault();
		if ($("#username").val() === "" || $("#password").val() === "" || $("#reg-login-button").hasClass("disabled")) return false;
		if (registering) {
			// Show a dialog confirmation box first
			CustomDialog({
				id: '#dialog-register',
				text: '<h3>Register and Login</h3>'+
					'<p>You are about to register the following user name with the password you just typed:</p>'+
					'<p style="font-size:20px;font-weight:bold;color:#2a2;">'+$("#username").val()+'</p><p>Okay to proceed?</p>',
				width: 389,
				height: 195,
			}, LoginOrRegister, function() {
				$("#response a.reg-cancel").trigger("click");
			});
		} else
			LoginOrRegister();
	});

	/**
	 * Function used just above.
	 */
	function LoginOrRegister() {
		$("#response").empty().removeClass("good bad").append("Hang on");
		// Does this username exist?
		$.post("php/account_exists.php", { username: $("#username").val() }, function(data) {
			browser.validateData(data, function(data) {

				if (data['exists']) {
					if (registering)
						// This situation should not occur
						$("#response a.reg-cancel").trigger("click");
					else
						ExecuteLoginOrRegister(false);	// User name exists so log in
				} else {
					if (registering)
						ExecuteLoginOrRegister(true);	// Register the new user name
					else {
						$("#response").empty().removeClass("good bad").addClass("bad").append('This user name does not exist');
					}
				}
			});
		}.bind(this));
	}

	/**
	 * Perform the registration of login.
	 * 
	 * @param {boolean} register	TRUE if registering, FALSE if just logging in
	 */
	function ExecuteLoginOrRegister(register) {
		$.post("php/account_login_register.php?register="+register, $("#userform").serialize(), function(data) {
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
		if (event.keyCode == 13) {
			$("#password").focus();
			return false;
		} else if (registering) {
			// So does this username exist?
			$.post("php/account_exists.php", { username: $("#username").val() }, function(data) {
				browser.validateData(data, function(data) {
					var $response = $("#response");
					$response.empty().removeClass("good bad");

					if (data['exists']) {
						$response.addClass("bad").append('The user name already exists | <a href="#" class="reg-cancel">Cancel</a>');
						$("#reg-login-button").prop("disabled", true).addClass("disabled");
					} else {
						$response.addClass("good").append('The user name is available | <a href="#" class="reg-cancel">Cancel</a>');
						$("#reg-login-button").prop("disabled", false).removeClass("disabled");
					}

				});
			}.bind(this));
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
	 * 
	 * NOTE: As of July 2024, all SID handlers now refresh the page. Most of the SID
	 * handlers already did this, and it made the code much easier to maintain.
	 * 
	 * @param {boolean} ignore		If specified and TRUE, exit immediately
	 */
	$("div.styledSelect").change(function(event, ignore) {
		if (ignore) return;

		// Get the choice from the drop-down box that was changed
		var emulator = $(this).prev("select").attr("name") == "select-topleft-emulator"
			? $("#dropdown-topleft-emulator").styledGetValue()
			: $("#dropdown-settings-emulator").styledGetValue();
		docCookies.setItem("emulator", emulator, "Infinity", "/");

		// Remember where we parked
		localStorage.setItem("tab", $("#tabs .selected").attr("data-topic"));

		TrackingEvent("select:emulator", emulator, function() {
			// Refresh the page to activate the new emulator
			window.location.reload();
		});
		return false;
	});

	/**
	 * When clicking a link for choosing "Lemon's MP3 Files" SID handler.
	 */
	$("a.set-lemon").click(function() {
		ctrls.selectEmulator("lemon");
	});

	/**
	 * When clicking a title or thumbnail in a list of GameBase64 entries.
	 */
	$("#topic-gb64").on("click", ".gb64-list-entry", function() {
		// First cache the list of releases in case we return to it
		cacheGB64 = $("#topic-gb64").html();
		cacheGB64TabScrollPos = $("#page").scrollTop();
		// Show the page
		browser.getGB64($(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking a GB64 sub-page screenshot to zoom it up.
	 */
	$("#page").on("click", ".zoom-up", function() {
		$("#dialog-cover").show();
		$("#zoomed").attr("src", $(this).attr("data-src")).show();
		return false;
	});

	/**
	 * When clicking a zoomed up screenshot to discard it.
	 */
	$("#zoomed").on("click", function() {
		$("#dialog-cover,#zoomed").hide();
	});

	/**
	 * When clicking the 'BACK' button on a GameBase64 page to show the list of them again.
	 */
	$("#sticky-gb64").on("click", "#go-back-gb64", function() {
		$("#sticky-gb64").empty().append('<h2 style="display:inline-block;margin-top:0;">GameBase64</h2>');
		// Load the cache again
		$("#topic-gb64")/*.css("visibility", "hidden")*/.empty().append(cacheGB64);
		// Also set scroll position to where we clicked last time
		SetScrollTopInstantly("#page", cacheGB64TabScrollPos);
	}),

	/**
	 * When the color theme toggle button is clicked.
	 * 
	 * A data attribute is set in the BODY element. Profile avatars and brand images
	 * are also changed.
	 */
	$("#theme-selector").click(function() {
		colorTheme ^= 1;

		$("#info-composer").find("img").each(function() {
			var $this = $(this);
			if ($this.attr("src") == "images/composer"+(colorTheme ? "" : "_dark")+".png")
				$this.attr("src", "images/composer"+(colorTheme ? "_dark" : "")+".png");
		});

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

		$("body").attr("data-theme", colorTheme ? "dark" : "");

		// Change the brand image too (if available)
		$("#brand-"+(colorTheme ? "light" : "dark")).hide();
		$("#brand-"+(colorTheme ? "dark" : "light")).show();

		localStorage.setItem("theme", colorTheme);
	});


	/**
	 * When clicking 'RETRY' in the CSDb tab where a page load has failed.
	 */
	$("#topic-csdb").on("click", ".csdb-retry", function() {
		browser.getCSDb();
	});

	/**
	 * When one of the "dexter" page tabs are clicked.
	 */
	$("#tabs .tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		// Store the scroll bar position as it is now for the tab we're about to leave
		var oldTopic = $("#tabs .selected").attr("data-topic");
		if (typeof oldTopic != "undefined") {
			tabPrevScrollPos[oldTopic].pos = tabPrevScrollPos[oldTopic].reset ? 0 : $("#page").scrollTop();
			tabPrevScrollPos[oldTopic].reset = false;
		}

		$("#page").removeClass("big-logo");

		var topic = $this.attr("data-topic");

		// Select the new tab
		$("#tabs .tab").removeClass("selected");
		$this.addClass("selected");

		// Show the selected topic
		$("#page .topic,#sticky-admin,#sticky-csdb,#sticky-gb64,#sticky-remix,#sticky-player,#sticky-stil,#sticky-visuals").hide();
		if (topic != "visuals")
			$("#topic-"+topic).show();

		SetScrollTopInstantly("#page", tabPrevScrollPos[topic].pos);

		// Show the 'To Top' button in the bottom on pages where this is nice to have
		// NOTE: Turned off for now - not sure it's needed anymore now the default scrollbar is in use.
		/*if (topic === "csdb" || topic === "profile" || topic === "player" || browser.isCompoFolder)
			$("#topic-"+topic+" button.to-top").show();*/

		// Show the big logo for the informational tabs only
		if (["about", "faq", "changes"].includes(topic) ||
			(topic === "profile" && browser.path == "" && (!browser.isSearching || $("#topic-profile table.root").length)) ||
			(topic === "profile" && $("#topic-profile table.rec-all").length))
				$("#page").addClass("big-logo");

		// If 'CSDb' tab is selected
		if (topic === "csdb") {
			$("#note-csdb").hide()					// Hide notification
			$("#sticky-csdb").show();				// Show sticky header
		};

		// If 'Visuals' tab is selected show the sticky header
		if (topic === "visuals") {
			$("#sticky-visuals").show();
			if (viz.visualsEnabled) {
				$("#topic-visuals").show();
				if (typeof viz.visuals == "undefined")
					$("#sticky-visuals button.icon-piano").trigger("click");
			}
		}

		// If 'GB64' tab is selected
		if (topic === "gb64") {
 			$("#note-gb64").hide();
			$("#sticky-gb64").show();
		}

		// If 'Remix' tab is selected
		if (topic === "remix") {
			$("#note-remix").hide();
			$("#sticky-remix").show();
		}

		// If 'Player' tab is selected
		if (topic === "player") {
			$("#note-player").hide();
			$("#sticky-player").show();
		}

		// If 'STIL' tab is selected
		if (topic === "stil") {
			$("#note-stil").hide();
			$("#sticky-stil").show();
		}

		// If 'Admin' tab is selected (administrators only)
		if (topic === "admin") {
			$("#sticky-admin").show();
			if ($("#topic-admin").html() === "")
				$("#sticky-admin button.ac-info").trigger("click");
		}

		// If 'Profile' tab is selected then refresh the charts if present
		// NOTE: If this is not done the charts will appear "flattened" towards the left side.
		if (topic === "profile" && typeof ctYears !== "undefined") {
			ctYears.update();
			ctPlayers.update();
		}
	});

	/**
	 * When one of the annex tabs are clicked.
	 */
	$("#annex-tabs .annex-tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		var topic = $this.attr("data-topic");

		// Select the new tab
		$("#annex-tabs .annex-tab").removeClass("selected");
		$this.addClass("selected");
		$("#annex-tabs .annex-topics").hide();

		// Show the selected topic
		$("#annex-page .atopic").hide();
		$("#atopic-"+topic).show();

		// If 'Tips' tab is selected
		if (topic === "tips") {
			if ($("#atopic-tips").is(":empty"))
				// Show the topics for the tips
				$.get("php/annex_tips.php", { id: -1 }, function(tips) {
					$("#atopic-tips").empty().append(tips).attr("data-index", "-1");
				});
			else if ($("#atopic-tips").attr("data-index") !== "-1")
				$("#annex-tabs .annex-topics").show();
		}
	});

	/**
	 * When one of the YouTube channel tabs are clicked.
	 * 
	 * @handlers youtube
	 */
	$("#youtube-tabs").on("click", ".tab", function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		// Select the new tab
		$("#youtube-tabs .tab").removeClass("selected");
		$this.addClass("selected");

		if (SID.ytReady) {
			$("#stop").trigger("mouseup");
			$("#time-length").empty().append('<img src="images/loading_threedots.svg" alt="..." style="position:relative;top:-1px;width:28px;">');
			// Handle optional time offset parameter if present
			var video_id = $this.attr("data-video"),
				offset = 0;
			if (video_id.indexOf("?") != -1) {
				var parts = video_id.split("?");
				video_id = parts[0];
				offset = parts[1].substr(2);
			}
			SID.YouTube.loadVideoById(video_id, offset);
			SID.setVolume(1);
			$("#play-pause").trigger("mouseup");
			browser.getLength(0);
		}
	});

	/**
	 * When one of the "sundry" box tabs are clicked.
	 */
	$("#sundry-tabs .tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("disabled")) return false;

		// If clicking the active tab, collapse the box
		if ($this.hasClass("selected")) {
			ToggleSundry(true);
			$("#folders").height(0).height($("#songs").height() - 100);
			return false;
		}

		// If the box was minimized, restore it first
		if (!main.sundryBoxShow) ToggleSundry(false);

		$("#sundry-ctrls").empty(); // Clear corner controls
		$("#slider-button").hide();

		// Select the new tab
		$("#sundry-tabs .tab").removeClass("selected");
		$this.addClass("selected");

		var stopic = $this.attr("data-topic");
		localStorage.setItem("sundrytab", stopic);

		ctrls.showNewsImage(false);

		switch (stopic) {
			case "stil":
				// Show collection version for this song
				ctrls.updateSundryVersion();
				ctrls.showNewsImage(true);
				break;
			case "tags":
				$("#sundry-ctrls").append(
					'<input type="checkbox" id="showtags" name="showtagstoggle" class="unselectable"'+(showTags ? '' : 'un')+'checked />'+
					'<label for="showtags" class="unselectable" style="position:relative;top:-2px;">Show tags in SID rows</label>'
				);
				if (browser.sliderButton)
					$("#slider-button").show();
				break;
			case "osc":
				// The oscilloscope view requires a minimum amount of vertical space
				var $sundry = $("#sundry");
				if ($sundry.css("flex-basis").replace("px", "") < 232)
					$sundry.css("flex-basis", 232);
				AddScopeControls();
				break;
			case "filter":
				// The filter view requires a minimum amount of vertical space
				var $sundry = $("#sundry");
				if ($sundry.css("flex-basis").replace("px", "") < 205)
					$sundry.css("flex-basis", 205);
				$("#sundry-ctrls").append('<span id="filter-6581" class="disable-6581">This requires <button class="set-6581 disabled" disabled="disabled">6581</button> chip mode</span>');
				break;
			case "stereo":
				// The stereo view requires a minimum amount of vertical space
				var $sundry = $("#sundry");
				if ($sundry.css("flex-basis").replace("px", "") < 163)
					$sundry.css("flex-basis", 163);
				AddScopeControls();
				break;
		}

		$("#folders").height(0).height($("#songs").height() - 100);

		// Show the selected topic
		$("#sundry .stopic").hide();
		$("#stopic-"+stopic).show();
	});

	/**
	 * Add sundry controls for scope and stereo tabs.
	 */
	function AddScopeControls() {
		if (SID.emulator == "websid" || (SID.emulator == "legacy" && $("#sundry-tabs .selected").attr("data-topic") == "osc")) {
			$("#sundry-ctrls").append(
				'<label class="unselectable">Min</label>'+
				'<input id="osc-zoom" type="range" min="1" max="5" value="'+viz.scopeZoom+'" step="1" />'+
				'<label class="unselectable">Max</label>'+
				'<div style="display:inline-block;vertical-align:top;margin-left:13px;">'+
					'<input type="checkbox" id="sidwiz" name="sidwiztoggle" class="unselectable" '+(viz.scopeMode ? '' : 'un')+'checked />'+
				'</div>'+
				'<label for="sidwiz" class="unselectable">Sync</label>'
			);
		}
	}

	/**
	 * When a 6581 filter slider is dragged in the sundry box.
	 * 
	 * @handlers websid
	 * 
	 * @param {*} event 
	 */
	$("#stopic-filter").on("input", "input[type='range']", function(event) {
		// Show the slider value in the edit box
		$("#"+event.target.id.replace("-slider", "-edit")).val(event.target.value);
		// Apply this single filter property now
		SID.setFilter(event.target.id.split("-")[1], event.target.value);
	});

	/**
	 * When entering a value in a 6581 filter edit box.
	 * 
	 * @handlers websid
	 * 
	 * @param {*} event 
	 */
	$("#stopic-filter").on("keyup", "input[type='text']", function(event) {
		if (event.keyCode == 13) {
			var $this = $(this);
			// Adjust the slider to reflect the entered value in the edit box
			$("#"+event.target.id.replace("-edit", "-slider")).val($this.val());
			$this.blur();
			return false;
		}
	});

	/**
	 * When a slider is dragged in the stereo sundry box (WebSid).
	 * 
	 * @handlers websid
	 * 
	 * @param {*} event 
	 */
	$("#stereo-websid").on("input", "input[type='range']", function(event) {
		if (event.target.id == "stereo-reverb-slider") {
			// Reverb slider
			SID.setStereoReverb(event.target.value);
		} else {
			// Stereo slider - for a specific chip and voice
			var chip_and_voice = event.target.id.split("-")[1];
			var chip = chip_and_voice.substr(1, 1),
				voice = chip_and_voice.substr(3, 1);
			SID.setStereoPanning(voice, chip, event.target.value);
		}
		 // Enable stereo when the sliders are used first time, or after turning it off
		if (SID.stereoLevel == -1)
			$("#dropdown-stereo-mode").val(0).trigger("change");
	});

	/**
	 * When a slider is dragged in the stereo sundry box (JSIDPlay2).
	 * 
	 * @handlers jsidplay2
	 * 
	 * @param {*} event 
	 */
	$("#stereo-jsidplay2").on("input", "input[type='range']", function(event) {
		var chip = event.target.id.split("-")[2].substring(1) - 1;
		if (event.target.id.indexOf("jp2-b") !== -1) {
			// Balance slider
			SID.setStereoChip(chip, event.target.value);
		} else {
			// Delay slider
			SID.setDelayChip(chip, event.target.value);
		}
	});


	/**
	 * When the drop-down for setting stereo mode is changed (JSIDPlay2).
	 * 
	 * @handlers jsidplay2
	 * 
	 * @param {*} event 
	 */
	$("#dropdown-jp2-stereo-mode").on("change", function(event) {
		// AUTO, STEREO or THREE_SID
		SID.setStereoMode(event.target.value);
	});

	/**
	 * When clicking the 'Fake stereo' check box (JSIDPlay2).
	 * 
	 * @handlers jsidplay2
	 */
	$("#stereo-fake").click(function() {
		SID.setStereoFake($("#stereo-fake").is(":checked"));
	});

	/**
	 * When the drop-down for setting chip being read for fake stereo (JSIDPlay2).
	 * 
	 * @handlers jsidplay2
	 * 
	 * @param {*} event 
	 */
	$("#dropdown-jp2-fake-read").on("change", function(event) {
		// Read FIRST_SID, SECOND_SID or THIRD_SID
		SID.setStereoRead(event.target.value);
	});

	/**
	 * When clicking the 'Headphones' check box for stereo panning.
	 * 
	 * @handlers websid
	 */
	$("#stereo-headphones").click(function() {
		$("#stereo-headphones").is(":checked") ? SID.setStereoHeadphones(1) : SID.setStereoHeadphones(0);
	});

	/**
	 * When changing the enhance stereo mode in its drop-down box.
	 * 
	 * @handlers websid
	 * 
	 * @param {*} event 
	 */
	$("#dropdown-stereo-mode").change(function(event) {
		if (SID.stereoLevel == -1 || event.target.value == -1) SID.resetStereo();
		SID.setStereoMode(event.target.value);
	});

	/**
	 * When clicking one of the admin category buttons.
	 */
	$("#sticky-admin").on("click", "button", function(event) {
		var category = $(event.target).attr("data-category");

		$("#sticky-admin .admin-cat-buttons .button-on").removeClass("button-on").addClass("button-off");
		$(this).removeClass("button-off").addClass("button-on");
		$("#topic-admin").empty();

		switch (category) {
			case "info":
				$.get("php/admin_info.php", function(data) {
					browser.validateData(data, function(data) {
						$("#topic-admin").append(data.html);
					});
				});
				break;
			case "settings":
				$.get("php/admin_settings_read_all.php", function(data) {
					browser.validateData(data, function(data) {
						$("#topic-admin").append(data.html);
					});
				});
				break;
			case "scripts":
				$.get("php/admin_scripts.php", function(data) {
					browser.validateData(data, function(data) {
						$("#topic-admin").append(data.html);
					});
				});
				break;
			default:
		}
	});

	/**
	 * Admin scripts: When clicking the 'RUN' button.
	 */
	$("#topic-admin").on("click", ".run-script", function(event) {
		window.open($(event.target).attr("data-script"), "_blank");
	});

	/**
	 * Admin settings: When clicking the 'Edit' icon.
	 */
	$("#topic-admin").on("click", ".edit", function() {
		var $this = $(this), html = sel = "";
		var type = $this.attr("data-type"), options = $this.attr("data-options").split(","),
			$value = $this.parents(".setting").children(".value"), value = $value.html();

		$("#topic-admin .edit").hide(); // Hide not just this 'Edit' icon but all of them

		switch (type) {
			case 'list':
				// Drop-down box
				html = '<select class="admin-temp-select">';
				options.forEach(function(opt) {
					sel = opt == value ? ' selected' : '';
					html += '<option value="'+opt+'"'+sel+'>' + opt.charAt(0).toUpperCase()+opt.slice(1) + '</option>';
				});
				html += '</select>';
				break;
			default:
				// Text edit box
				html = '<input class="admin-temp-edit" name="temp-edit" value="' + value + '" />';
		}
		$value.empty().append(html);
		$("#topic-admin .admin-temp-edit").focus().each(function() {
			// Place the cursor at the end of the value
			const len = this.value.length;
			this.setSelectionRange(len, len);
		});
	});

	/**
	 * Admin settings: When hitting ENTER in an edit box.
	 * 
	 * @param {*} event 
	 */
	$("#topic-admin").on("keydown", ".admin-temp-edit", function(event) {
		if (event.keyCode == 13) {
			var $parent = $(this).parents(".setting"),
				value = $("#topic-admin .admin-temp-edit").val();

			var title = $parent.children(".title").html();
			$parent.children(".value").empty().append(value);

			$.post("php/admin_settings_write.php", { key: title, value: value }, function(data) {
				browser.validateData(data);
			});

			$("#topic-admin .edit").show(); // Allow editing a row again

			// Prevent 'Enter' from also firing in the browser list
			blockNextEnter = true;
			return false;
		}
	});

	/**
	 * Admin settings: When selecting an option in a drop-down box.
	 * 
	 * @param {*} event 
	 */
	$("#topic-admin").on("change", ".admin-temp-select", function(event) {
		var $parent = $(this).parents(".setting");
		var title = $parent.children(".title").html();
		$parent.children(".value").empty().append(event.target.value);

		$.post("php/admin_settings_write.php", { key: title, value: event.target.value }, function(data) {
			browser.validateData(data);
		});

		$("#topic-admin .edit").show(); // Allow editing a row again

		// Prevent 'Enter' from also firing in the browser list
		blockNextEnter = true;
	});

	/**
	 * User settings: When one of the ON/OFF toggle buttons are clicked.
	 * 
	 * @param {*} event 
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
	 * When clicking any link at all.
	 */
	/*$("body").on("click", "a", function(event) {
		if (GetParam("lemon")) {
			// Make the "&lemon=1" switch even more sticky
			var link = event.currentTarget.href+"&lemon=1";
			link.replace(/&/, "?"); // Replace first occurrence only
			event.currentTarget.href = link;
		}
	});*/

	/**
	 * When clicking a thumbnail/title in a CSDb release row to open it internally.
	 */
	$("#topic-csdb").on("click", "a.internal", function() {
		// First cache the list of releases in case we return to it
		cacheCSDb = $("#topic-csdb").html();
		cacheSticky = $("#sticky-csdb").html();
		cacheTabScrollPos = $("#page").scrollTop();
		cacheDDCSDbSort = $("#dropdown-sort-csdb").val();
		// Now load the actual release page
		browser.getCSDb("release", $(this).attr("data-id"), true);
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
		var $this = $(this);
		// Load the cache again (much faster than calling browser.getCSDb() to regenerate it)
		$("#topic-csdb").empty()
			.append($this.hasClass("compo") ? cacheBeforeCompo : cacheCSDb);
		$("#sticky-csdb").empty().append($this.hasClass("compo") ? cacheStickyBeforeCompo : cacheSticky);
		// Adjust drop-down box to the sort setting
		$("#dropdown-sort-csdb").val(cacheDDCSDbSort);
		// Also set scroll position to where we clicked last time
		SetScrollTopInstantly("#page", $this.hasClass("compo") ? cachePosBeforeCompo : cacheTabScrollPos);
	});

	/**
	 * When clicking the filter toggle button in a CSDb list, for hiding all rows that are
	 * not emphasized in yellow/green/red, or for showing all rows again.
	 */
	$("#topic-csdb").on("click", "#csdb-emp-filter", function() {
		var $this = $(this);
		var state = $this.hasClass("button-off");
		$this.removeClass("button-off button-on").empty();

		if (state) {
			// Hide all CSDb entries that don't have emphasize/empSec/empThird classes
			$this.addClass("button-on").append("On");
			$("#topic-csdb table.releases tr").each(function() {
				var hasHighlight = $(this).find("a.emphasize, a.csdb-group.empSec, a.csdb-scener.empThird").length > 0;
				if (!hasHighlight) $(this).hide();
			});
		} else {
			// Show all CSDb entries again
			$this.addClass("button-off").append("Off");
			$("#topic-csdb table.releases tr").show();
		}
	});

	/**
	 * When clicking the 'BACK' button on a player/editor page to show the list of them again.
	 */
	$("#sticky-player").on("click", "#go-back-player", function() {
		if (cachePlayer == "") {
			// First time?
			$("#players").trigger("click");
		} else {
			$("#sticky-player").empty().height(34).append('<h2 style="display:inline-block;margin-top:0;">Players / Editors</h2>');
			// Load the cache again
			$("#topic-player")/*.css("visibility", "hidden")*/.empty().append(cachePlayer);
			// Also set scroll position to where we clicked last time
			SetScrollTopInstantly("#page", cachePlayerTabScrollPos);
		}
	}),

	/**
	 * When clicking the 'BACK' button on a 'Remix' page to show the list of them again.
	 */
	$("#topic-remix").on("click", "#go-back-remix", function() {
		// Load the cache again
		$("#topic-remix")/*.css("visibility", "hidden")*/.empty().append(cacheRemix);
		// Also set scroll position to where we clicked last time
		SetScrollTopInstantly("#page", cacheRemixTabScrollPos);
	}),

	/**
	 * When clicking the 'SHOW' button on a CSDb page to show the full list of competition results.
	 */
	$("#topic-csdb").on("click", "#show-compo", function() {
		cacheBeforeCompo = $("#topic-csdb").html();
		cacheStickyBeforeCompo = $("#sticky-csdb").html();
		cachePosBeforeCompo = $("#page").scrollTop();
		var $this = $(this);
		browser.getCompoResults($this.attr("data-compo"), $this.attr("data-id"), $this.attr("data-mark"));
	});

	/**
	 * When clicking the arrow up button in the bottom of CSDb pages to scroll back to the top.
	 */
	$("#topic-profile,#topic-csdb,#topic-player").on("click", "button.to-top", function() {
		$("#page").scrollTop(0);
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
	 * When clicking a rating star in a composer or annex profile.
	 * 
	 * @param {*} event 
	 */
	$("#topic-profile,#annex").on("click", ".folder-rating b", function(event) {
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
				$("#topic-profile .folder-rating,#annex .folder-rating").empty().append(stars);

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
	 * When clicking a link for searching without refreshing the page.
	 */
	$("#annex").on("click", "a.search", function() {
		var $this = $(this);
		$("#dropdown-search").val($this.attr("data-type").toLowerCase());
		$("#search-box").val($this.attr("href")).trigger("keyup");
		$("#search-button").trigger("click");
		return false;
	});

	/**
	 * When clicking a "clink" for opening a composer's links in the annex box.
	 * 
	 * @param {*} event 
	 * @param {boolean} internal	If specified and TRUE, it wasn't clicked by a human
	 */
	$("#topic-profile").on("click", "a.clinks", function(event, internal) {
		var $this = $(this);
		$.get("php/annex_clinks.php", { id: $this.attr("data-id") }, function(data) {
			browser.validateData(data, function(data) {
				var handle = $this.attr("data-handle");
				$("#atopic-links").empty().append(
					'<h3 class="ellipsis" style="width:229px;'+(handle != "" ? 'margin-bottom:0;'  : '')+'">'+$this.attr("data-name")+'</h3>'+
					'<h4 class="ellipsis" style="width:170px;margin-top:0;">'+handle+'</h4>'+
					data.html+
					'<a href="" id="edit-add-clink" class="clink-corner-link" style="right:'+(data.clinks ? '44' : '17')+'px;">Add</a>'+
					(data.clinks ? '<a href="" id="edit-cancel-clink" class="clink-corner-link"">Edit</a>' : '')
				);
				if (typeof internal == "undefined") {
					$("#annex").show(); // It was clicked by a human so better make sure the annex box is visible
					$("#atab-links").trigger("click");
				}
			});
		});
		return false;
	});

	/**
	 * When clicking the "Edit" or "Cancel" link for the composer's links.
	 */
	$("#annex").on("click", "#edit-cancel-clink", function() {
		if (!$("#logout").length) {
			alert("Login or register and you will be able to edit composer links.");
			return false;
		}
		if ($("#annex .clink-icon").is(":visible")) {
			// Turn off edit link and restore full width of clinks
			$("#annex .clink-icon").hide();
			$("#annex .clink").css("width", "170px");
			$("#edit-add-clink").show();
			$("#edit-cancel-clink").empty().append("Edit");
		} else {
			// Turn on edit link and reduce the width of clinks to make room
			$("#annex .clink").css("width", "150px");
			$("#annex .clink-icon").fadeIn("fast");
			$("#edit-add-clink").hide();
			$("#edit-cancel-clink").empty().append("Cancel");
		}
		return false;
	});

	/**
	 * When clicking the add link for a new "clink" in the annex box.
	 */
	$("#annex").on("click", "#edit-add-clink", function() {
		if (!$("#logout").length) {
			alert("Login or register and you will be able to add composer links.");
			return false;
		}
		$("#edit-clink-name-input,#edit-clink-url-input").val("");

		CustomDialog({
			id: '#dialog-add-clink',
			text: '<h3>Add a new composer link</h3>'+
				'<p>Note that if the link doesn\'t work or is irrelevant, it will be deleted. All changes are logged.</p>',
			width: 390,
			height: 236,
		}, function() {
			$.post("php/composer_clink_add.php", {
				cid:		$("#clink-list").attr("data-id"),
				name:		$("#edit-clink-name-input").val(),
				url:		$("#edit-clink-url-input").val(),
			}, function(data) {
				browser.validateData(data, function() {
					// Refresh the annex box
					$("#topic-profile a.clinks").trigger("click", true);
				});
			});
		});
		$("#edit-clink-name-input").focus();
		return false;
	});

	/**
	 * When ENTER key is hit in the dialog box above.
	 * 
	 * @param {*} event 
	 */
	$("#edit-clink-name-input,#edit-clink-url-input").keydown(function(event) {
		if (event.keyCode == 13) {
			$("#dialog-add-clink .dialog-button-yes").trigger("click");
			return false;
		}
	});

	/**
	 * When clicking an edit icon for a specific "clink" in the annex box.
	 */
	$("#annex").on("click", "div.clink-edit", function() {

		$clink = $(this).prev();
		$("#edit-clink-name-input").val($clink.text());
		$("#edit-clink-url-input").val($clink.attr("href"));

		CustomDialog({
			id: '#dialog-add-clink',
			text: '<h3>Edit a composer link</h3>'+
				'<p>Note that if the link doesn\'t work or is irrelevant, it will be deleted. All changes are logged.</p>',
			width: 390,
			height: 236,
		}, function() {
			// SAVE was clicked; edit the composer link in the database
			$.post("php/composer_clink_edit.php", {
				cid:		$("#clink-list").attr("data-id"),
				id:			$clink.attr("data-id"),
				name:		$("#edit-clink-name-input").val(),
				url:		$("#edit-clink-url-input").val(),
			}, function(data) {
				browser.validateData(data, function() {
					// Refresh the annex box
					$("#topic-profile a.clinks").trigger("click", true);
				});
			});
		});
		$("#edit-clink-name-input").focus();
	});

	/**
	 * When clicking a delete icon for a specific "clink" in the annex box.
	 */
	$("#annex").on("click", "div.clink-delete", function() {

		$clink = $(this).prev().prev();
		$("#clink-name-delete").empty().append('<b>'+$clink.text()+'</b>');
		$("#clink-url-delete").empty().append('<a href="'+$clink.attr("href")+'" target="_blank">'+$clink.attr("href")+'</a>');

		// Show a dialog confirmation box first
		CustomDialog({
			id: '#dialog-delete-clink',
			text: '<h3>Delete a composer link</h3>'+
				'<p>Are you sure you want to delete this composer link?</p>',
			width: 500,
			height: 196,
		}, function() {
			$.post("php/composer_clink_delete.php", {
				cid:		$("#clink-list").attr("data-id"),
				id:			$clink.attr("data-id"),
				name:		$("#clink-name-delete").text(),
				url:		$("#clink-url-delete").text(),
			}, function(data) {
				browser.validateData(data, function() {
					// Refresh the annex box
					$("#topic-profile a.clinks").trigger("click", true);
				});
			});
		});
	});

	/**
	 * When clicking a recommendation box in the root.
	 * 
	 * @param {*} event 
	 */
	$("#topic-profile").on("mousedown", "table.recommended", function() { return false; });
	$("#topic-profile").on("mouseup", "table.recommended", function(event) {
		var folder = $(this).attr("data-folder");
		if (folder == "cshelldb") {
			if (event.which == 2 && event.button == 1)
				// Middle mouse button for opening it in a new browser tab
				window.open("http://csdb.chordian.net/");
			else
				// Open in same browser tab
				window.open("http://csdb.chordian.net/", "_top");
				// window.location.href = "http://csdb.chordian.net/";
		} else if (folder == "playmod") {
			if (event.which == 2 && event.button == 1)
				// Middle mouse button for opening it in a new browser tab
				window.open("http://www.wothke.ch/playmod/");
			else
				// Open in same browser tab
				window.open("http://www.wothke.ch/playmod/", "_top");
				// window.location.href = "http://www.wothke.ch/playmod/";
		} else {
			var link = "//deepsid.chordian.net/?file=/"+folder.replace("_High Voltage SID Collection/", "")+"/";
			//if (GetParam("lemon")) link += "&lemon=1";
			if (event.which == 2 && event.button == 1)
				// Middle mouse button for opening it in a new browser tab
				window.open(link);
			else
				// Open in same browser tab
				window.location.href = link;
		}
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
		var $this = $(this);
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
	 * @param {boolean} noclick		If specified and TRUE, the 'Player' tab won't be clicked
	 */
	$("#players").click(function(event, noclick){
		$(this).blur();
		if (players) players.abort();
		$("#topic-players").empty().append(browser.loadingSpinner("profile"));
		$("#sticky-player").empty();

		if ($("#tabs .selected").attr("data-topic") !== "player" && typeof noclick == "undefined")
			$("#tab-player").trigger("click");

		var loadingPlayers = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-profile").fadeIn(500);
		}, 250);

		players = $.get("php/player_list.php", function(data) {
			browser.validateData(data, function(data) {
				clearTimeout(loadingPlayers);
				$("#sticky-player").empty().height(34).append(data.sticky);
				$("#topic-player").empty().append(data.html);
				ResetDexterScrollBar("player");
				$("#note-csdb").hide();
			});
		});
		return false;
	});

	/**
	 * When clicking a row in the "PLAYER" list. This shows the page for the specific
	 * player/editor.
	 */
	$("#topic-player").on("click", ".player-entry", function() {
		var $this = $(this);
		// First cache the list of releases in case we return to it
		cachePlayer = $("#topic-player").html();
		cachePlayerTabScrollPos = $("#page").scrollTop();
		// Show the page
		browser.getPlayerInfo({id: $this.attr("data-id")});
		// Also search for the related players
		$("#dropdown-search").val("player");
		$("#search-box").val($this.attr("data-search").toLowerCase()).trigger("keyup");
		$("#search-button").trigger("click");
		return false;
	}),

	/**
	 * When clicking a title or thumbnail in a list of remix entries.
	 */
	$("#topic-remix").on("click", ".remix-list-entry", function() {
		// First cache the list of releases in case we return to it
		cacheRemix = $("#topic-remix").html();
		cacheRemixTabScrollPos = $("#page").scrollTop();
		// Show the page
		browser.getRemix($(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking a home folder icon in a CSDb comment table.
	 */
	$("#topic-profile,#topic-csdb,#topic-player").on("click", ".home-folder", function() {
		browser.gotoFolder($(this).attr("data-home"));
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
	 * @param {string} fullname		The SID filename including folders
	 * @param {boolean} solitary	If specified and FALSE, the tune will continue like in a playlist
	 * 
	 * @return {boolean}			TRUE if the SID was found and is now playing
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
			$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
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
	fileParam = fileParam.replace("/SID FM/", "/SID+FM/");
	if (fileParam.substr(0, 2) === "/_")
		fileParam = "/"+fileParam.substr(2); // Lose custom folder "_" character
	var searchQuery = GetParam("search"),
		selectTab = GetParam("tab"),
		selectSundryTab = GetParam("sundry"),
		playerID = GetParam("player"),
		typeCSDb = GetParam("csdbtype"),
		idCSDb = GetParam("csdbid"),
		forceCover = GetParam("cover");
	// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
	$(window).trigger("resize");
	if (fileParam !== "" && fileParam.indexOf("\\") === -1) {
		// A HVSC folder or file was specified
		fileParam = fileParam.charAt(0) === "/" ? fileParam : "/"+fileParam;
		if (fileParam.substr(0, 6) == "/DEMOS" || fileParam.substr(0, 6) == "/GAMES" || fileParam.substr(0, 10) == "/MUSICIANS" || fileParam.substr(0, 7) == "/GROUPS")
			fileParam = "/High Voltage SID Collection"+fileParam;
		var isFolder = fileParam.indexOf(".sid") === -1 && fileParam.indexOf(".mus") === -1,
			isSymlist = fileParam.substr(0, 2) == "/!" || fileParam.substr(0, 2) == "/$",
			isCompoFolder = fileParam.indexOf("/CSDb Music Competitions/") !== -1;

		// Is a year subfolder specified for the SH folder?
		if (fileParam.indexOf("SID Happens/") !== -1 && (fileParam.match(/\//g) || []).length < 3) {
			// No, does the file exist in the root SH folder, i.e. the current year?
			if (!SidHappensFileExists(fileParam)) {
				// No, figure out if it exists in one of the year subfolders then (backwards from latest)
				for (var shYear = 2024; shYear >= 2020; shYear--) {
					var yearFolder = fileParam.replace("SID Happens/", "SID Happens/"+shYear+"/");
					if (SidHappensFileExists(yearFolder)) {
						fileParam = yearFolder;
						break;
					}
				}
			}
		}

		browser.path = isFolder ? fileParam : fileParam.substr(0, fileParam.lastIndexOf("/"));
		if (browser.path.substr(0, 7).toLowerCase() != "/demos/" && browser.path.substr(0, 7).toLowerCase() != "/games/" && browser.path.substr(0, 11).toLowerCase() != "/musicians/"  && browser.path.substr(0, 8).toLowerCase() != "/groups/" && browser.path.substr(0, 2) != "/!" && browser.path.substr(0, 2) != "/$")
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
				$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
				// The user may have to click a overlay question to satisfy browser auto-play prevention
				// NOTE: Always shown for the YouTube handler. YouTube seems to do their own checking.
				if (forceCover || SID.isSuspended() || SID.emulator == "youtube")
					$("#dialog-cover,#click-to-play-cover").show();
				else
					PlayFromURL(); // Don't need to show the click-to-play cover - play it now

			} else if (GetParam("here") == "1") {
				setTimeout(function() {
					PerformSearchQuery(searchQuery);
					$("#loading").hide();
				}, 200);
			}
			if (isFolder) browser.showFolderTags();
			browser.getComposer()
		});

	} else if (searchQuery !== "")
		PerformSearchQuery(searchQuery);

	/**
	 * When clicking the overlay to satisfy the browser auto-play prevention. This
	 * hides the overlay and plays the SID row that was ready to go.
	 */
	$("#click-to-play-cover").click(function() {
		$("#dialog-cover,#click-to-play-cover").hide();
		PlayFromURL();
	});

	/**
	 * When clicking the 'Edit' link for changing the YouTube tabs for a song.
	 * 
	 * @handlers youtube
	 * 
	 * @param {*} event 
	 */
	$("#youtube-tabs").on("click", "#edityttabs a", function(event) {
		browser.editYouTubeLinks($(event.target).attr("data-name"));
		return false;
	});

	/**
	 * When clicking the 'X' corner icon for closing an annex box.
	 */
	$("#annex").on("click", ".annex-close", function() {
		browser.annexNotWanted = true;
		$("#annex").hide();
	});

	/**
	 * When clicking the annex corner icon for showing the topics.
	 */
	$("#annex").on("click", ".annex-topics", function() {
		$.get("php/annex_tips.php", { id: -1 }, function(topics) {
			$("#atopic-tips").empty().append(topics).attr("data-index", "-1");
			$(".annex-topics").hide();
		});
	});

	/**
	 * When clicking a topic link in the annex box.
	 */
	$("#annex").on("click", ".topic", function() {
		ClickAnnexLink($(this).attr("href"));
		return false;
	});

	/**
	 * When clicking a link for showing a topic in the annex box. Since the annex box
	 * could have been closed earlier, it is made visible again.
	 */
	 $("#page").on("click", "a.annex-link", function() {
		browser.annexNotWanted = false;
		ClickAnnexLink($(this).attr("href"));
		setTimeout(function() {
			$("#annex").show();
		}, 100);
		return false;
	});

	/**
	 * Used by the above two event clicks.
	 * 
	 * @param {string} topic	The topic link
	 */
	function ClickAnnexLink(topic) {
		$.get("php/annex_tips.php", { id: topic }, function(tips) {
			$("#atopic-tips").empty().append(tips).attr("data-index", topic);
			$(".annex-topics").show();
		});
	}

	/**
	 * Select and show a "dexter" page tab.
	 */
	if (selectTab == "") {
		// Did we refresh from changing the SID handler?
		var tab = localStorage.getItem("tab");
		localStorage.removeItem("tab");
		// Select tab from before refreshing otherwise a default
		selectTab = tab ? tab : "profile";
	}
	var selectView = "";
	if (selectTab === "flood") selectTab = "graph";
	if (selectTab === "memo") selectTab = "memory";
	if (selectTab === "piano" || selectTab === "graph" || selectTab === "memory" || selectTab === "stats") {
		selectView = selectTab.toLowerCase();
		selectTab = "visuals";
	}
	$("#tab-"+selectTab).trigger("click");
	if (selectView !== "") $("#sticky-visuals .icon-"+selectView).trigger("click"); // Select a visuals view

	/**
	 * Select and show a "sundry" box tab (an URL parameter overrides the local storage setting).
	 */
	if (selectSundryTab === "") {
		selectSundryTab = localStorage.getItem("sundrytab");
		if (selectSundryTab == null) selectSundryTab = "stil";
	}
	if (selectSundryTab === "lyrics") selectSundryTab = "stil";
	if (selectSundryTab === "scope") selectSundryTab = "osc";
	$("#stab-"+selectSundryTab).trigger("click");

	/**
	 * Show a specific player/editor in the 'Player' tab.
	 */
	if (playerID != "") {
		browser.getPlayerInfo({id: playerID});	// Show the page
		$("#tab-player").trigger("click");
	} else
		$("#players").trigger("click", true);	// Otherwise just load the list of them

	/**
	 * Show a specific CSDb entry (only loads the content of the CSDb tab).
	 */
	if (typeCSDb === "sid" || typeCSDb === "release") {
		browser.getCSDb(typeCSDb, idCSDb);
		$("#sticky-csdb").show(); // Show sticky header
	}

	// Turn off visuals as default for JSIDPlay2 to save on CPU time
	ToggleVisuals();
	
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
 * Enable or disable buttons and sliders in the sundry box for 6581 filter.
 * 
 * @handlers websid
 * 
 * NOTE: Don't call this too early or 'SID.getModel()' fails.
 */
function ShowSundryFilterContents() {
	$("#filter-websid").hide();
	$("#stopic-filter form").show();
	setTimeout(function(){
		if (SID.emulator == "websid" && SID.getModel() == "6581") {
			// Enable filter controls
			$("#stopic-filter form label,#stopic-filter form input,#filter-revisions button")
				.prop("disabled", false).removeClass("disabled");
			$("#filter-6581").addClass("disable-6581");
			$("#filter-6581 button").prop("disabled", true).addClass("disabled");
		} else if (SID.emulator == "websid") {
			// Disable filter controls (since 6581 chip mode is not active)
			$("#stopic-filter form label,#stopic-filter form input,#filter-revisions button")
				.prop("disabled", true).addClass("disabled");
			$("#filter-6581").removeClass("disable-6581");
			$("#filter-6581 button").prop("disabled", false).removeClass("disabled");
		} else {
			// Show "WebSid" button (the HQ version is required for these controls)
			$("#filter-websid").show();
			$("#stopic-filter form").hide();
		}
	}, 0);
}

/**
 * Make sure the visuals (and "SID_WRITE" event) are turned OFF as default for
 * JSIDPlay2, otherwise ON for all other SID handlers. This is of course to
 * save on CPU time for JSIDPlay2 as "SID_WRITE" is a very busy event.
 * 
 * @handlers jsidplay2
 */
function ToggleVisuals() {
	if (SID.emulator == "jsidplay2" && $("#tab-visuals-toggle").hasClass("button-on"))
		// Piano button must be clicked first or the visuals buttons all act funny
		$("#sticky-visuals .icon-piano,#tab-visuals-toggle").trigger("click");
	else if (SID.emulator != "jsidplay2" && $("#tab-visuals-toggle").hasClass("button-off"))
		$("#tab-visuals-toggle").trigger("click");
}

/**
 * Show a floating message for a short period of time in the bottom of the song
 * browser window. Used by e.g. the triangular corner buttons.
 * 
 * @param {string} message 
 */
function BrowserMessage(message) {
	clearTimeout(cornerMessageTimer);
	$("#corner-buttons .message").empty().append(message).css("display", "inline-block");
	cornerMessageTimer = setTimeout(() => {
		$("#corner-buttons .message").hide();
	}, 1500);
}

/**
 * Update the number in the triangle corner button for cycling factoids. It
 * should match the currently selected bottom factoid number.
 * 
 * @param {number} factoid 
 */
function UpdateFactoidButton(factoid) {
	$factoidButton = $("#corner-buttons .corner-right div");
	$factoidButton.empty().append(factoid);
	var style;
	if (factoid < 10)
		style = {
			// Numbers 0 to 9
			"font-size":	"13px",
			"top":			"1px",
			"left":			"0.5px",
		}
	else
		style = {
			// Numbers 10 and up
			"font-size":	"10px",
			"top":			"-1px",
			"left":			"2px",
		}
	$factoidButton.css(style);
}

/**
 * Select bottom factoid number and update folder.
 * 
 * @param {number} factoid 
 */
function SelectFactoid(factoid, showMessage = true) {
	UpdateFactoidButton(factoid);
	if (showMessage)
		BrowserMessage(factoidMessage[factoid]);
	localStorage.setItem("factoidBottom", factoid);

	RefreshFolder();
}

/**
 * Refresh the currently displayed folder, even if searching.
 */
function RefreshFolder() {
	var scrollPosition = $("#folders").scrollTop(),
		kbSelectedBefore = browser.kbSelectedRow,
		isMarkerInView =  browser.isKeyboardMarkerInView();

	if (browser.isSearching) {
		// Something could have changed so have to repeat the same search
		$("#dropdown-search").val(browser.searchType);
		$("#search-here").prop("checked", browser.searchHere === 1);
		$("#search-box").val(browser.searchQuery).trigger("keyup");

		browser.getFolder(0, browser.searchQuery, undefined, function() {
			applyKeyboardSelection(scrollPosition, kbSelectedBefore, isMarkerInView);
		});
	} else {
		// Standard folder refresh
		browser.getFolder(0, undefined, undefined, function() {
			applyKeyboardSelection(scrollPosition, kbSelectedBefore, isMarkerInView);
		});
	}
}

/**
 * Helper function used by RefreshFolder().
 */
function applyKeyboardSelection(scrollPosition, kbSelectedBefore, isMarkerInView) {
	SetScrollTopInstantly("#folders", scrollPosition);
	if (isMarkerInView)
		// Only go to same keyboard marker if it was visible
		browser.kbSelectedRow = kbSelectedBefore;
	else
		// Keyboard marker was not visible to set it in the middle of view
		browser.kbSelectedRow = browser.getIndexClosestToCenter();
	browser.moveKeyboardSelection(browser.kbSelectedRow, false);
}

/**
 * Perform a search query from the URL parameters.
 * 
 * @param {string} searchQuery	The search query string
 */
function PerformSearchQuery(searchQuery) {
	$("#dropdown-search").val(GetParam("type") !== "" ? GetParam("type").toLowerCase() : "#all#");
	$("#search-here").prop("checked", GetParam("here") == "1");
	$("#search-box").val(searchQuery).trigger("keyup");
	$("#search-button").trigger("click");
}

/**
 * Play the tune that was specified in the URL.
 */
function PlayFromURL() {
	if (SID.emulator == "youtube") {
		// Patience; the YouTube IFrame video player can take a while to load
		var i = 0;
		var waitForYouTube = setInterval(function() {
			if (SID.ytReady || ++i == 20) {
				clearInterval(waitForYouTube);
				PlayFromURLNow();
			}
		}, 500);
	} else
		PlayFromURLNow();
}

/**
 * Called by PlayFromURL() above.
 */
function PlayFromURLNow() {
	var paramSubtune = GetParam("subtune"),
		paramWait = GetParam("wait");

	if (paramSubtune == "")
		$trAutoPlay.children("td.sid").trigger("click", [undefined, undefined, undefined, paramWait]);
	else
		$trAutoPlay.children("td.sid").trigger("click", [(paramSubtune == 0 ? 0 : paramSubtune - 1), undefined, undefined, paramWait]);
}

/**
 * Check if a file exists in the "SID Happens" folder.
 * 
 * @param {string} filename		Must be full path
 * 
 * @return {boolean}			TRUE if the file exists
 */
function SidHappensFileExists(filename) {
	var exists;
	$.ajax({
		url:		"php/file_exists.php",
		type:		"get",
		async:		false,
		data:		{ file: browser.ROOT_HVSC+filename.replace("/SID Happens/", "/_SID Happens/") }
	}).done(function(result) {
		exists = result;
	});
	return exists == 1;
}

/**
 * Reset the scrollbar in a "dexter" page to the top.
 * 
 * @param {string} topic	Name of tab, e.g. "profile", "csdb", etc.
 */
function ResetDexterScrollBar(topic) {
	tabPrevScrollPos[topic].pos = 0;
	tabPrevScrollPos[topic].reset = true;
}

/**
 * Settings: Set the state of an ON/OFF toggle button.
 * 
 * @param {string} id		Part of the ID to be appended
 * @param {boolean} state	1 or 0
 */
function SettingToggle(id, state) {
	$("#setting-"+id)
		.empty()
		.append(state ? "On" : "Off")
		.removeClass("button-off button-on")
		.addClass("button-"+(state ? "on" : "off"));
}

/**
 * Settings: Get a value from an ON/OFF toggle button.
 * 
 * @param {string} id		Part of the ID to be appended
 * 
 * @return {*}				The value
 */
function GetSettingValue(id) {
	$setting = $("#setting-"+id);
	if ($setting.hasClass("button-toggle"))
		// Checkbox style toggle button; return boolean
		return $setting.hasClass("button-on");
}

/**
 * Read one setting in the admin table and return its value.
 * 
 * @param {string} key 		Name of setting, e.g. "search-limit"
 * 
 * @returns {string}		The value
 */
function GetAdminSetting(key) {
	if (typeof DeepSID === "undefined" || !DeepSID.adminSettings) {
		console.warn("Admin settings not loaded yet.");
		return null;
	}
	const value = DeepSID.adminSettings[key];
	return value !== undefined ? value : null;
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

// --------------------------------------------------------------------------
// THUMBNAILS FOR LINKS
// --------------------------------------------------------------------------

const DAY_MS = 24*60*60*1000;

function GetMicrolinkSrc(url, force){
	var base = "https://api.microlink.io/"
		+ "?url=" + encodeURIComponent(url)
		+ "&screenshot=true&meta=false&embed=screenshot.url";
	if (force)
		base += "&force=true&ts=" + Date.now(); // Cache-bust
	return base;
}

function GetSiteKey(url){ 
	return "thumb_last_refresh_" + btoa(url); 
}

$(".site-link").on("click", function() {
	var $a   = $(this);
	var $img = $a.find("img.thumb");
	var url  = $a.data("url");
	var k    = GetSiteKey(url);
	var last = parseInt(localStorage.getItem(k) || "0", 10);
	var now  = Date.now();

	// Refresh at most once per 24h per user
	if((now - last) > DAY_MS) {
		var forcedSrc = GetMicrolinkSrc(url, true);
		var fallback  = GetMicrolinkSrc(url, false);

		$img.one("load", function() {
			localStorage.setItem(k, String(now));
		}).attr("src", forcedSrc).on("error", function() {
			// If quota exceeded (429) → revert to cached image
			$(this).attr("src", fallback);
		});
	}
	// Don't preventDefault → link still opens in new tab
});

// --------------------------------------------------------------------------

/**
 * Minimize or maximize the sundry box in case of a small display. When the box
 * is minimized, you can still click a tab to restore its size, or you can drag
 * the slider downwards to expand it.
 * 
 * All sundry tabs become unselected while minimized.
 * 
 * @param {boolean} shrink	TRUE to minimize, FALSE to return to before, or toggle if not specified
 */
function ToggleSundry(shrink) {
	if (typeof shrink === "undefined") shrink = main.sundryBoxShow;
	if (!shrink) {
		// Expand sundry box
		$("#sundry").css({
			"flex-basis":	sundryHeight,
			"padding":		"6px 10px",
		});
		main.sundryBoxShow = true;
		$("#sundry-tabs").find(".tab[data-topic='"+sundryTab+"']").addClass("selected");
		$("#sundry-ctrls").show();
		if (parseInt(sundryHeight) > 37 && browser.sliderButton && $("#sundry-tabs .selected").attr("data-topic") == "tags")
			$("#slider-button").show();
	} else {
		// Collapse sundry box
		sundryHeight = $("#sundry").css("flex-basis");
		sundryTab = $("#sundry-tabs .selected").attr("data-topic");
		$("#sundry").css({
			"flex-basis":	0,
			"padding":		0,
		});
		main.sundryBoxShow = false;
		$("#sundry-tabs .tab").removeClass("selected"); // No tab selected anymore
		$("#sundry-ctrls,#slider-button").hide();
	}
}

/**
 * Show video box in top instead of info box if "YouTube" is the SID handler. Also shows
 * a MIDI drop-down box in top if "ASID" is the SID handler.
 * 
 * @handlers youtube, asid
 * 
 * @param {string} emulator
 */
function HandleTopBox(emulator) {
	if (emulator == "youtube") {
		$("#info-text,#memory-lid").hide();
		$("#youtube,#youtube-tabs").show();
		$("#memory-chunk").css("top", "0");
	} else {
		$("#info-text,#memory-lid").show();
		$("#youtube,#youtube-tabs").hide();
		$("#memory-chunk").css("top", "-2px");
	}

	if (emulator == "webusb") {
		$("#webusb-connect").show();
	}
	else {
		$("#webusb-connect").hide();
	}

	if (emulator == "asid") {
		$("#asid-midi").show();
	}
	else {
		$("#asid-midi").hide();
	}

	$(window).trigger("resize"); // Keeps bottom search box in place
}

/**
 * Disable table rows for folders incompatible with this SID handler. File rows
 * that emulators can't handle (such as BASIC tunes) will also be disabled.
 */
function DisableIncompatibleRows() {
	// The 'YouTube' handler has its own checks in 'hvsc.php'
	if (SID.emulator == "youtube") return;
	
	$("#songs table").children().each(function() {
		var $tr = $(this);
		var isSIDFile = $tr.find("td.sid").length;
		// Skip spacers, dividers and files for the general incompatibility field (folders only)
		if (!$tr.find(".spacer").length && !$tr.find(".divider").length && !isSIDFile) {
			$tr.removeClass("disabled");
			var $span = $tr.find(".name");
			if ($span.is("[data-incompat]") && ($span.attr("data-incompat").indexOf(SID.emulator) !== -1 ||
			($span.attr("data-incompat").indexOf("mobile") !== -1 && isMobile)))
				$tr.addClass("disabled");
		} else if (isSIDFile && $tr.find(".name").attr("data-name").indexOf("BASIC.sid") !== -1) {
			// These emulators can't do tunes made in BASIC
			SID.emulator == "legacy" || SID.emulator == "hermit" || SID.emulator == "webusb" || SID.emulator == "asid"
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
			// Hermit's emulator and ASID can't do neither any RSID tunes nor any MUS files
			SID.emulator == "hermit" || SID.emulator == "webusb" || SID.emulator == "asid"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");
		} else if (isSIDFile && $tr.find(".name").attr("data-name").indexOf(".mus") !== -1) {
			// JSIDPlay2 emulators can't do MUS files
			SID.emulator == "jsidplay2"
				? $tr.addClass("disabled")
				: $tr.removeClass("disabled");
		}
	});
}

/**
 * Update the URL in the web browser address field.
 * 
 * @param {boolean} skipFileCheck	If specified, TRUE to skip file check
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
		urlFile = urlFile.replace("/High Voltage SID Collection", "");

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
	var urlSubtune = ctrls.subtuneMax ? "&subtune="+(ctrls.subtuneCurrent + 1) : "";

	var link = (urlFile+urlSubtune).replace(/&/, "?"); // Replace first occurrence only

	// The "?wait=" switch must be sticky or the effect will be lost in a refreshed IFrame
	var wait = GetParam("wait");
	if (wait) link += "&wait="+wait;

	// Also make sure the following switches are sticky
	if (GetParam("websiddebug")) link += "&websiddebug=1";
	//if (GetParam("lemon")) link += "&lemon=1";
	if (GetParam("mini")) link += "&mini="+miniPlayer;
	
	if (urlFile != prevFile) {
		prevFile = urlFile; // Need a new file clicked before we proceed in the browser history
		history.pushState({}, document.title, link);
	} else
		history.replaceState({}, document.title, link);
}

/**
 * Find all "redirect" classes (plinks) - typically in CSDb pages - and set the
 * small icon to a selected state if corresponding to any playing tune.
 */
function UpdateRedirectPlayIcons() {
	if (browser.playlist.length == 0) return;
	// Set "active" icon on all plinks that has the same tune (HVSC only)
	$("a.redirect").each(function() {
		var $this = $(this);
		if ($this.html() == browser.playlist[browser.songPos].fullname.replace(browser.ROOT_HVSC+"/_High Voltage SID Collection", ""))
			$this.removeClass("playing").addClass("playing");
	});
}

/**
 * Set the scrollbar position instantly without animating a scrolling to it.
 * 
 * @param {object} element			The element to set the scrollbar position for
 * @param {number} pos				The scrollbar position
 */
function SetScrollTopInstantly(element, pos) {
	$(element)
		.css("scroll-behavior", "auto")
		.scrollTop(pos)
		.css("scroll-behavior", "smooth");
}

/**
 * Allow numeric input only (0-9 and dots) for edit boxes.
 * 
 * Add 'onkeypress="NumericInput(event)"' in '<input type="text">' lines.
 * 
 * @link https://stackoverflow.com/a/469419/2242348
 * 
 * @param {*} event 
 */
function NumericInput(event) {
	var key;
	if (event.type === "paste") {
		key = event.clipboardData.getData("text/plain");
	} else {
		key = event.keyCode || event.which;
		key = String.fromCharCode(key);
	}
	var regex = /[0-9]|\./;
	if(!regex.test(key)) {
		event.returnValue = false;
		if (event.preventDefault) event.preventDefault();
	}
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
 * @param {array} data				Associative array with data:
 * 									 - id		Must be set
 * 								 	 - text		Must be set
 * 								 	 - width	A default is used if not set
 * 								 	 - height	A default is used if not set
 * 									 - wizard	Don't fade cover if set and TRUE
 * @param {function} callbackYes	Callback used if YES is clicked
 * @param {function} callbackNo		Callback used if NO is clicked
 */
function CustomDialog(data, callbackYes, callbackNo) {

	$(data.id).off("click", ".dialog-button-yes");
	$(data.id).off("click", ".dialog-button-no");

	var width = typeof data.width != "undefined" ? data.width : 400;
	var height = typeof data.height != "undefined" ? data.height : 200;

	var $dialog = $("#dialog-cover,"+data.id);
	
	$(data.id).css({ width: width, height: height }).center();
	$(data.id+" .dialog-text").empty().append(data.text);
	if (typeof data.wizard !== "undefined" && data.wizard)
		$dialog.show();
	else
		$dialog.fadeIn("fast");

	$(data.id).on("click", ".dialog-button-yes", function() {
		// Prevent 'Enter' from also firing in the browser list
		blockNextEnter = true;
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
 * Track user behaviour for later statistics.
 * 
 * Only an IP address is logged, not a specific user.
 * 
 * This is not related to root tracking. See below for more about that.
 * 
 * @param {string} type			E.g. "start", "enter", "select", etc.
 * @param {string} target		E.g. song ID, folder path, emulator name, etc.
 * @param {function} callback	If specified, the function to call after PHP call
 * @param {number} debounceMs	Override default delay for this call
 */
function TrackingEvent(type, target, callback, debounceMs) {

	const key = type; // Per-type debounce
	const delay = typeof debounceMs === "number"
		? debounceMs
		: (TRACK_DELAY[type] ?? 5000);

	// Clear pending send for this type only
	if (trackingTimers[key]) {
		clearTimeout(trackingTimers[key].id);
		delete trackingTimers[key];
	}

	const fire = () => {

		// log("TRACKING: Type: '"+type+"' Target: '"+target+"'");

		// If a callback is provided, use $.post so we can wait and then call it
		$.post("php/track.php", { type, target })
			.always(function() {
				if (typeof callback === "function") callback();
			});
		delete trackingTimers[key];
	};

	// If delay is 0, send immediately; else debounce
	if (delay <= 0) {
		fire();
	} else {
		trackingTimers[key] = { id: setTimeout(fire, delay) };
	}
}

/**
 * Utility function for 'TrackingEvent' above.
 * 
 * Cancel only a specific type (e.g. when backing out of a folder).
 * 
 * @param {*} type 
 */
function CancelTrackType(type) {
	const t = trackingTimers[type];
	if (t) { clearTimeout(t.id); delete trackingTimers[type]; }
}

// 
/**
 * Utility function for 'TrackingEvent' above.
 * 
 * Cancel all pending tracking.
 */
function CancelAllTracking() {
	for (const k in trackingTimers) {
		clearTimeout(trackingTimers[k].id);
		delete trackingTimers[k];
	}
}

/**
 * Call the root tracking.
 * 
 * This is not related to the event tracking above. This is used for generating
 * a text file that is then parsed and displayed as a separate HTML file.
 */
function PingTracking() {

	// Prefer sendBeacon (even though it's not jQuery, it's passive and ideal here)
	if (navigator.sendBeacon) {
		try {
			navigator.sendBeacon("tracking.php");
			return;
		} catch(e) {
			// Fall through to AJAX fallback
		}
	}

	// jQuery fallback
	$.get("tracking.php");
}

/**
 * Get a custom variable (usually a color) from the CSS file, according to the
 * currently selected color scheme.
 * 
 * If the variable is not present in the CSS file for the dark theme, it will
 * automatically default to the ":root" variable.
 * 
 * @param {string} cssVar	The custom variable name
 */
function GetCSSVar(cssVar) {
	return $(parseInt(colorTheme) ? "[data-theme='dark']" : ":root").css(cssVar);
}

/**
 * Plugin with one easing copied from "jquery.easing.1.3.js" by Robert Penner
 * and George McGinley Smith. Use it in the .animate() jQuery method.
 *
 * @link http://gsgd.co.uk/sandbox/jquery/easing/
 */
$.extend($.easing, {
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
 * @param {string} name		Parameter to search for
 * 
 * @return {string}			Value (empty if non-existent or equal to nothing)
 */
function GetParam(name) {
	return decodeURIComponent((RegExp(name + '=' + '(.+?)(&|$)').exec(location.search.replace(/\+/g, " "))||[,""])[1]);
}

/**
 * Log a line in the console.
 * 
 * @param {string} text				The text to be logged
 * @param {boolean} preventHuddle	Optional; set to TRUE to make each log output unique
 */
function log(text, preventHuddle) {
	if (window.console) {
		if (typeof preventHuddle !== "undefined" && preventHuddle)
			console.log("DeepSID "+(logCount++)+": " + text);
		else
			console.log(text);
	}
}