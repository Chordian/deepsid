
/**
 * DeepSID / Main
 */

var $=jQuery.noConflict();
var cacheCSDb = cacheCSDbProfile = cacheBeforeCompo = prevFile = "";
var cacheTabScrollPos = tabScrollPos = cachePosBeforeCompo = peekCounter = 0;

$(function() { // DOM ready

	var userExists = false;
	var emulator = GetParam("emulator").toLowerCase();
	if ($.inArray(emulator, [
		"websid",
		"jssid",
		"soasc_auto",
		"soasc_r2",
		"soasc_r4",
		"soasc_r5",
		"download",
	]) === -1) emulator = "websid";

	viz = new Viz(emulator);
	SID = new SIDPlayer(emulator);
	ctrls = new Controls();
	browser = new Browser();

	$("#dropdown-emulator")
		.styledSelect("emulator")
		.styledSetValue(emulator);

	if ($(window).height() <= 840) {
		$("#stil").hide(); // Hide STIL box on small displays
	} else {
		// Otherwise use it upon page load to show random tips
		$.post("php/tips.php", function(tips) {
			$("#stil").append('<div id="tips">'+tips+'</div>');
		});
	}

	$("#time-bar").addClass(emulator)
		.css("cursor", SID.emulatorFlags.supportSeeking ? "pointer" : "default");

	/**
	 * Handle hotkeys.
	 * 
	 * NOTE: Hotkeys for voices ON/OFF are handled in 'viz.js' only.
	 * 
	 * @param {*} event 
	 */
	$(window).on("keydown", function(event) {
		if (!$("#search-box,#username,#password,#sym-rename,#sym-specify-subtune").is(":focus")) {
			if (event.keyCode == 220)								// Keydown key below 'Escape'
				// Fast forward
				$("#faster").trigger("mousedown");
		}
	}).on("keyup", function(event) {
		if (!$("#search-box,#username,#password,#sym-rename,#sym-specify-subtune").is(":focus")) {
			if (event.keyCode == 220)								// Keyup key below 'Escape'
				// Fast forward
				$("#faster").trigger("mouseup");
			else if (event.keyCode == 32)							// Keyup 'Space'
				$("#play-pause").trigger("mouseup");
			else if (event.keyCode == 80)							// Keyup 'p'
				// Open a pop-up window with only the width of the #panel area
				window.open("//deepsid.chordian.net/", "_blank",
					"left=0,top=0,width=450,height="+(screen.height-150)+",scrollbars=no");
			else if (event.keyCode == 83) {							// Keyup 's'
				// Toggle the STIL box on/off
				$("#stil").toggle();
				$(window).trigger("resize", true);
			}
		}
	});

	/**
	 * When resizing the window. Also affected by toggling the developer pane.
	 * 
	 * @param {*} event 
	 * @param {boolean} stilToggle	If specified and TRUE, ignores the STIL box.
	 */
	$(window).on("resize", function(event, stilToggle) {
		if (!stilToggle && $("#tabs .selected").attr("data-topic") !== "stil")
			$(window).height() > 840 ? $("#stil").show() : $("#stil").hide();
		// Make sure the browser box always take up all screen height upon resizing the window
		$("#folders").height(0).height($("#songs").height() - 100);
		if (!browser.isMobile) {
			// Also make sure the scrollbar for dexter has the correct height
			$("#page .mCSB_scrollTools").css("height", $("#page").height() + 13);
			// Correct height for flood river too
			var floodHeight = $("#page").outerHeight() - 173;
			$("#flood").height(floodHeight);
			$("#flood .flood-river canvas").attr("height", floodHeight - 1);
			viz.river_height[0] = viz.river_height[1] = viz.river_height[2] = floodHeight - 1;
			// And that the web site iframe has the correct height too
			$("#page .deepsid-iframe").height($("#page").outerHeight() - 61); // 24
		}
	});

	/**
	 * When dragging the white line to resize the STIL box smaller or larger.
	 * 
	 * @param {*} event 
	 */
	$("#slider").on("mousedown touchstart", function(event) {
		event.preventDefault();
		$("body").on("mousemove touchmove", function(event) {
			event.preventDefault();
			var $stil = $("#stil"), diff = $("#slider").offset().top + 5 - event.pageY;
			$stil.css("flex-basis", $stil.css("flex-basis").replace("px", "") - diff);
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
	});

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
				$.post("php/account_exists.php", {username: $("#username").val()}, function(data) {
					browser.validateData(data, function(data) {

						userExists = data['exists'];
						$("#response").empty().removeClass("good bad").addClass("good").append(userExists
							? "User exists; click to log in"
							: "User name is available; click to register and log in");

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
	 * Used by the SID handler and the top 20 lists.
	 */
	$("div.styledSelect").change(function() {
		switch ($(this).prev("select").attr("name")) {
			case "select-emulator":
				// Selecting a different SID handler (emulator or SOASC)
				var isRowSelected = $("#folders tr.selected").length,
					wasPlaying = ctrls.isPlaying();
					mainVol = SID.mainVol;
				SID.unload();
				ctrls.selectButton($("#stop"));
				viz.allEmuButtonsOff();

				var emulator = $("#dropdown-emulator").styledGetValue();
				viz.setEmuButton(emulator);

				SID = null;
				delete SID;
				SID = new SIDPlayer(emulator);
				SID.mainVol = mainVol;

				// The color of the time bar should be unique for the chosen SID handler
				$("#time-bar").removeClass("websid jssid soasc_r2 soasc_r4 soasc_r5").addClass(emulator)
					.css("cursor", SID.emulatorFlags.supportSeeking ? "pointer" : "default");

				$("#faster").removeClass("disabled");
				if (!SID.emulatorFlags.supportFaster) $("#faster").addClass("disabled");

				// Clear all red error rows
				var $tr = $("#folders tr");
				$tr.find(".entry").css("color", "");
				$tr.find("span.info").css("color", "");
				$tr.css("background", "");

				// Disable table rows for folders incompatible with this SID handler
				$("#songs table").children().each(function() {
					var $tr = $(this);
					 // Skip spacers, dividers and files
					if (!$tr.find(".spacer").length && !$tr.find(".divider").length && !$tr.find("td.sid").length) {
						$tr.removeClass("disabled");
						var $span = $tr.find(".name");
						if ($span.is("[data-incompat]") && $span.attr("data-incompat").indexOf(SID.emulator) !== -1)
							$tr.addClass("disabled");
					}
				});

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
					$("#folders tr.selected").children("td.sid").trigger("click", ctrls.subtuneCurrent);
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
				viz.activatePiano(true);
				break;
		}
	});

	/**
	 * When one of the "dexter" page tabs are clicked.
	 */
	$("#tabs .tab").click(function() {
		var $this = $(this);
		if ($this.hasClass("selected") || $this.hasClass("disabled")) return false;

		$("#page").mCustomScrollbar("destroy");

		// Select the new tab
		$("#tabs .tab").removeClass("selected");
		$this.addClass("selected");

		var topic = $this.attr("data-topic");

		// Show the selected topic
		$("#page .topic").hide();
		$("#topic-"+topic).show();
		ShowDexterScrollbar(topic);

		// Toggle the STIL box depending on whether the 'STIL' tab was clicked or not
		topic === "stil" ? $("#stil").hide() : $("#stil").show();
		$(window).trigger("resize", true);

		// If 'Disqus' tab is selected then hide the notification on it
		if (topic === "disqus") $("#note-disqus").hide();

		// If 'CSDb' tab is selected then hide the notification on it
		if (topic === "csdb") $("#note-csdb").hide();

		// If 'GB64' tab is selected then hide the notification on it
		if (topic === "gb64") $("#note-gb64").hide();

		// If 'Piano' tab is selected then make the custom scroll bar transparent
		// NOTE: Must be hidden in other tabs or scrolling may become erratic.
		$("#dexter .mCSB_container").css("overflow", topic === "piano" ? "visible" : "hidden");

		// If 'Profile' tab is selected then refresh the charts if present
		// NOTE: If this is not done the charts will appear "flattened" towards the left side.
		if (topic === "profile" && typeof ctYears !== "undefined") {
			ctYears.update();
			ctPlayers.update();
		}

		if (topic === "settings") {
			// Get the user's settings
			$.post("php/settings.php", function(data) {
				browser.validateData(data, function(data) {
					SettingToggle("skip-tune", data.settings.skiptune);
					SettingToggle("mark-tune", data.settings.marktune);
				});
			}.bind(this));
		}
	});

	/**
	 * When the check box for turning Disqus ON or OFF is clicked.
	 * 
	 * This check box is only visible in the 'Disqus' tab.
	 */
	$("#disqus-toggle").click(function() {
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
	});

	/**
	 * When clicking a thumbnail/title in a CSDb release row to open it internally.
	 */
	$("#topic-csdb").on("click", "a.internal", function() {
		// First cache the list of releases in case we return to it
		cacheCSDb = $("#topic-csdb").html();
		cacheTabScrollPos = tabScrollPos;
		// Now load the new content
		browser.getCSDb("release", $(this).attr("data-id"));
		return false;
	});

	/**
	 * When clicking the 'BACK' button on a specific CSDb page to show the releases again.
	 */
	$("#topic-csdb").on("click", "#go-back", function() {
		if (cacheBeforeCompo === "" && cacheCSDb === "") {
			// We have been redirecting recently so the tab must be refreshed properly
			browser.getCSDb();
			return;
		}
		$this = $(this);
		// Load the cache again (much faster than calling browser.getCSDb() to regenerate it)
		$("#topic-csdb").css("visibility", "hidden").empty()
			.append($this.hasClass("compo") ? cacheBeforeCompo : cacheCSDb);
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
	$("#topic-csdb").on("click", "#go-back-init", function() {
		browser.getCSDb("sid", $(this).attr("data-id"));
	});

	/**
	 * When clicking the 'SHOW' button on a CSDb page to show the full list of competition results.
	 */
	$("#topic-csdb").on("click", "#show-compo", function() {
		cacheBeforeCompo = $("#topic-csdb").html();
		cachePosBeforeCompo = tabScrollPos;
		$this = $(this);
		browser.getCompoResults($this.attr("data-compo"), $this.attr("data-id"), $this.attr("data-mark"));
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
	$("#topic-csdb").on("click", "#csdb-comment", function() {
		window.open("https://csdb.dk/"+$(this).attr("data-type")+"/addcomment.php?"+
			$(this).attr("data-type")+"_id="+$(this).attr("data-id"), "_blank");
	});

	/**
	 * When clicking a 'redirect' link to open an arbitrary SID file without reloading DeepSID.
	 */
	$("#topic-csdb,#stil,#topic-stil,#topic-changes").on("click", "a.redirect", function() {
		var fullname = $(this).html();
		var path = "/_High Voltage SID Collection"+fullname.substr(0, fullname.lastIndexOf("/"));
		// @todo If using redirect for custom folders later then copy the 'browser.path' lines from 'fileParam' below.
		ctrls.state("root/back", "enabled");
		if (path != browser.path) {
			browser.path = path;
			browser.getFolder(0, undefined, function() {
				ClickAndScrollToSID(fullname);
			});
		} else
			ClickAndScrollToSID(fullname);
		// Clear caches to force proper refresh of CSDb tab after redirecting 
		cacheBeforeCompo = cacheCSDb = "";
		UpdateURL();
		return false;
	});

	/**
	 * When clicking a home folder icon in a CSDb comment table.
	 */
	$("#topic-csdb").on("click", ".home-folder", function() {
		browser.path = "/"+$(this).attr("data-home");
		ctrls.state("root/back", "enabled");
		browser.getFolder(0, undefined, function() {
			browser.getComposer(); // Comment this out to keep comment thread (not sure what users prefer here?)
		});
	});

	/**
	 * Click a SID file row and then scroll to center it in the browser list.
	 * 
	 * @param {string} fullname		The SID filename including folders.
	 */
	function ClickAndScrollToSID(fullname) {
		// Isolate the SID name, e.g. "music.sid"
		var sidFile = fullname.split("/").slice(-1)[0];
		var $tr = $("#folders tr").filter(function() {
			return $(this).find(".name").text().toLowerCase() == sidFile.toLowerCase();
		}).closest("tr");
		// This is the <TR> row with the SID file we need to play
		var $trPlay = $("#folders tr").eq($tr.index());
		$trPlay.children("td.sid").trigger("click", [undefined, true]);
		// Scroll the row into the middle of the list
		var rowPos = $trPlay[0].offsetTop,
			halfway = $("#folders").height() / 2 - 26; // Last value is half of SID file row height
		if (browser.isMobile)
			$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
		else
			$("#folders").mCustomScrollbar("scrollTo", rowPos > halfway ? rowPos - halfway : "top");
	}

	/**
	 * Handle URL parameters.
	 */
	hashExcl = decodeURIComponent(location.hash); // Any Disqus link characters "#!" used?
	fileParam = hashExcl !== "" ? hashExcl.substr(2) : GetParam("file");
	fileParam = decodeURIComponent(fileParam.replace(/\+/g, "%20"));
	if (fileParam.substr(0, 2) === "/_")
		fileParam = "/"+fileParam.substr(2); // Lose custom folder "_" character
	var searchQuery = GetParam("search"),
		paramSubtune = GetParam("subtune"),
		selectTab = GetParam("tab"),
		typeCSDb = GetParam("csdbtype"),
		idCSDb = GetParam("csdbid");
	if (fileParam !== "" || searchQuery !== "") {
		// Let mobile devices use their own touch scrolling stuff
		if (browser.isMobile) {
			// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
			$(window).trigger("resize");
		} else {
			// Hack to make custom scroll bar respect flexbox height
			$("#folders").height($("#folders").height())
				.mCustomScrollbar({
					axis: "y",
					theme: "dark-3",
					scrollButtons:{
						enable: true,
					},
					mouseWheel:{
						scrollAmount: 150,
					}
				});
		}
	}
	if (fileParam !== "" && fileParam.indexOf("\\") === -1) {
		// A HVSC folder or file was specified
		fileParam = fileParam.charAt(0) === "/" ? fileParam : "/"+fileParam;
		if (fileParam.substr(0, 6) == "/DEMOS" || fileParam.substr(0, 6) == "/GAMES" || fileParam.substr(0, 10) == "/MUSICIANS")
			fileParam = "/High Voltage SID Collection"+fileParam;
		var isSidFile = fileParam.indexOf(".sid") === -1 && fileParam.indexOf(".mus") === -1,
			isSymlist = fileParam.substr(0, 2) == "/!" || fileParam.substr(0, 2) == "/$";
		browser.path = isSidFile ? fileParam : fileParam.substr(0, fileParam.lastIndexOf("/"));
		if (browser.path.substr(0, 7).toLowerCase() != "/demos/" && browser.path.substr(0, 7).toLowerCase() != "/games/" && browser.path.substr(0, 11).toLowerCase() != "/musicians/" && browser.path.substr(0, 2) != "/!" && browser.path.substr(0, 2) != "/$")
			browser.path = "/_"+browser.path.substr(1); // It's an "extra" folder
		if (browser.path.substr(-1) === "/") browser.path = browser.path.slice(0, -1); // Remove "/" at end of folder
		if (isSymlist) browser.path = "/"+browser.path.split("/")[1]; // Symlist SID names could be using "/" chars
		ctrls.state("root/back", "enabled");
		browser.getFolder(0, undefined, function() {
			if (!isSidFile) {
				// Isolate the SID name, e.g. "music.sid"
				var sidFile = isSymlist
					? fileParam.substr(fileParam.substr(1).indexOf("/") + 2)
					: fileParam.split("/").slice(-1)[0];
				var $tr = $("#folders tr").filter(function() {
					var $name = $(this).find(".name");
					if (!$name.length) return false;
					// First try to match the original SID name
					var found = $name.attr("data-name").toLowerCase().replace(/^\_/, '') == sidFile.toLowerCase();
					if (!found)
						// If not found, try one more time with the table name (it could be a renamed playlist entry)
						found = $name.text().toLowerCase() == sidFile.toLowerCase();
					return found;
				}).closest("tr");
				// This is the <TR> row with the SID file we need to play
				var $trPlay = $("#folders tr").eq($tr.index());
				if (paramSubtune == "")
					$trPlay.children("td.sid").trigger("click");
				else
					$trPlay.children("td.sid").trigger("click", paramSubtune == 0 ? 0 : paramSubtune - 1);
				// Scroll the row into the middle of the list
				var rowPos = $trPlay[0].offsetTop;
				var halfway = $("#folders").height() / 2 - 26; // Last value is half of SID file row height
				if (browser.isMobile)
					$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
				else
					$("#folders").mCustomScrollbar("scrollTo", rowPos > halfway ? rowPos - halfway : "top");
			}
			browser.getComposer();
		});
	} else if (searchQuery !== "") {
		// A search query was specified (optionally with a type too)
		$("#dropdown-search").val(GetParam("type") !== "" ? GetParam("type").toLowerCase() : "fullname");
		$("#search-box").val(searchQuery).trigger("keyup");
		$("#search-button").trigger("click");
	}

	// Select and show a "dexter" page tab	
	selectTab = selectTab !== "" ? selectTab : "profile";
	$("#dexter .tab[data-topic='"+selectTab+"']").addClass("selected");
	ShowDexterScrollbar();
	$("#topic-"+selectTab).show(); // Must be after the custom scrollbar has been created

	// Show a specific CSDb entry (only loads the content of the CSDb tab)
	if (typeCSDb === "sid" || typeCSDb === "release") {
		browser.getCSDb(typeCSDb, idCSDb, false);
	}

	// Turn off the STIL box if the STIL tab was selected
	if (selectTab === "stil") {
		$("#stil").hide();
		$(window).trigger("resize", true);
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
 * Show the custom scrollbar in a "dexter" page.
 * 
 * @param {string} topic	If specified, the tab topic.
 */
function ShowDexterScrollbar(topic) {
	// The 'Disqus' tab needs to use the default scrollbar to make sure the mouse wheel works
	if ($("#tabs .selected").attr("data-topic") !== "disqus") {
		$("#page").mCustomScrollbar({
			axis: "y",
			theme: "dark-3",
			autoHideScrollbar: typeof topic !== "undefined" && topic === "piano", // Must hide on piano view page
			scrollButtons:{
				enable: true,
			},
			mouseWheel:{
				scrollAmount: 150,
			},
			callbacks: {
				onCreate: function() {
					// Adjust scrollbar height to fit the up/down arrows perfectly
					$("#page .mCSB_scrollTools").css("height", $("#page").height() + 13);
				},
				whileScrolling: function() {
					tabScrollPos = this.mcs.top;
				},
			},
		});
	}
}

/**
 * Set the state of an ON/OFF toggle button in the settings tab.
 * 
 * @param {boolean} id		Part of ID to be appended.
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
 * Resize the web site IFRAME then show it.
 * 
 * Called by an IFRAME inserted by the 'composer.php' script.
 */
function ResizeIframe() {
	$(window).trigger("resize");
	$("#page .deepsid-iframe").show();
}

/**
 * Update the URL in the web browser address field.
 */
function UpdateURL() {
	var urlFile = browser.isSearching || browser.path == "" ? "&file=" : "&file="+browser.path.replace(/^\/_/, '/')+"/";
	try {
		urlFile = browser.playlist[browser.songPos].substname !== ""
			? urlFile += browser.playlist[browser.songPos].substname
			: urlFile += browser.playlist[browser.songPos].filename.replace(/^\_/, '');
	} catch(e) { /* Type error means no SID file clicked */ }

	// ?subtune=
	var urlSubtune = ctrls.subtuneCurrent ? "&subtune="+(ctrls.subtuneCurrent + 1) : "";

	// ?emulator=
	var urlEmulator = SID.getHandler() == "websid" ? "" : "&emulator="+SID.getHandler();

	var link = (urlFile+urlSubtune+urlEmulator).replace(/&/, "?"); // Replace first occurrence only

	if (urlFile != prevFile) {
		prevFile = urlFile; // Need a new file clicked before we proceed in the browser history
		history.pushState({}, document.title, link);
	} else
		history.replaceState({}, document.title, link);
}

/**
 * Get a parameter value from the current URL (or optional custom string).
 * 
 * @param {string} name		Parameter to search for.
 * @param {string} alt		If specified, search this custom URL instead.
 * 
 * @return {string}			Value (empty if non-existent or equal to nothing).
 */
function GetParam(name, alt) {
	return decodeURIComponent((RegExp(name + '=' + '(.+?)(&|$)').exec(typeof alt !== "undefined" ? alt : location.search)||[,""])[1]);
}