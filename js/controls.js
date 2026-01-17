
/**
 * DeepSID / Controls
 */

 function Controls() {

	this.buttonTimer = 0;
	this.emulatorChanged = false;

	this.subtuneCurrent = 0;
	this.subtuneMax = 0;

	this.currentFileID = 0;

	this.addEvents();
}

Controls.prototype = {

	/**
	 * Add the events pertinent to this class.
	 */
	addEvents: function() {
		$(".button-ctrls")
			.on("mousedown", this.onMouseDown.bind(this))
			.on("mouseup", this.onMouseUp.bind(this))
			.on("touchstart", this.onTouchStart.bind(this))
			.on("touchend", this.onTouchEnd.bind(this))

		$("#stop,#loop,#time-bar").click(this.onClick.bind(this));
		$("#info").on("click", "#sid-model,#clockspeed,#info-rating", this.onClick.bind(this));
		$("#sundry,#topic-stil").on("click", ".subtune", this.onClick.bind(this));
		$("#sundry").on("click", "canvas,.tag", this.onClick.bind(this));
		$("#stopic-osc,#stopic-filter,#stopic-stereo").on("click", "button", this.onClick.bind(this));
		$("#sundry-ctrls").on("click", "#sidwiz,#showtags,#filter-6581", this.onClick.bind(this));

		$("#volume,#sundry-ctrls").on("input", this.onInput.bind(this));

		$("#memory-chunk").on("click", function() {
			// Go to 'Visuals > MEMO' when the blue memory chunk is clicked
			$("#tab-visuals").trigger("click");
			$("#sticky-visuals button.icon-memory").trigger("click");
		});

		setInterval(this.pace.bind(this), 150); // VBI, 2x, 4x, digi, etc.
	},

	/**
	 * When clicking/holding a mouse button down.
	 * 
	 * @param {*} event 
	 */
	onMouseDown: function(event) {
		var target = event.target;

		if ($(target).hasClass("disabled") || this.buttonTimer) return false;

		if (target.id == "faster") {
			// Fast forward
			this.selectButton($(target));
			SID.setVolume(0.3);
			// Want the faster bar to be more smooth? Try finetuning these milliseconds!
			if (event.which == 2 && event.button == 1) {
				// Middle mouse button
				$("#time-bar div").css("transition", "all 250ms linear");
				SID.speed(8);
			} else {
				// Normal
				$("#time-bar div").css("transition", "all 500ms linear");
				SID.speed(4);
			}
			return false;
		} else if (target.id == "loop") {
			// Toggle the appearance state of the loop button
			var state = $("#loop").hasClass("button-off");
			$("#loop").removeClass("button-off button-on").addClass("button-"+(state ? "on" : "off"));
			state
				? SID.disableTimeout()
				: SID.enableTimeout(browser.getLength(this.subtuneCurrent, true));
		}
	},
 
	/**
	 * When releasing a mouse button.
	 * 
	 * @param {*} event 
	 * @param {boolean} autoProgress	If specified; auto-progress did the request, not a human
	 */
	onMouseUp: function(event, autoProgress) {
		var target = event.target;
		var id = target.id;
		var isAutoProgress = typeof autoProgress !== "undefined";

		if ($(target).hasClass("disabled") || this.buttonTimer) return false;

		if (id == "faster") {
			// Back to normal speed
			this.selectButton($("#play-pause"));
			SID.speed($("#piano-slow").hasClass("button-on") ? viz.slowSpeed : 1);
			$("#time-bar div").css("transition", "all 1s linear");
			SID.setVolume(1);
			return false;
		} else if (id != "loop") {
			this.selectButton($(target));
			this.toggleStateFaster();
			this.togglePlayOrPause(id === "stop");
		}

		if (id.substr(0, 7) == "subtune") {
			SID.setVolume(0);
			browser.clearSpinner();

			// Pick a subtune
			if (event.which == 2 && event.button == 1)
				// Middle mouse button for absolute ends
				id == "subtune-plus" ? this.subtuneCurrent = this.subtuneMax : this.subtuneCurrent = 0;
			else
				// Normal mouse click
				id == "subtune-plus" ? this.subtuneCurrent++ : this.subtuneCurrent--;

			if (id == "subtune-plus") {
				// Remove the mouse pointer for a very small time for the user to spot new subtune info
				$("#subtune-plus").css("cursor", "none");
				setTimeout(function() {
					$("#subtune-plus").css("cursor", "pointer");
				}, 450);
			}

			$("#time-bar").empty().append('<div></div>');

			// Keep skipping subtunes if a setting is set to ignore those of less than 10 seconds
			if (isAutoProgress && main.getSettingValue("skip-short"))  {
				while (browser.getLength(this.subtuneCurrent) < 10) {
					this.subtuneCurrent++;
					if (this.subtuneCurrent >= this.subtuneMax) {
						$("#skip-next").trigger("mouseup", false);
						return false;
					}
				}
			}

			browser.showSpinner($("#folders tr").eq(browser.subFolders + browser.songPos).find("td.sid"));
			SID.load(this.subtuneCurrent, browser.getLength(this.subtuneCurrent), undefined, function() {
				browser.clearSpinner();
				if (SID.emulatorFlags.forcePlay) SID.play();
				main.updateURL();
				browser.chips = 1;
				if (browser.playlist[browser.songPos].fullname.indexOf("_2SID") != -1) browser.chips = 2;
				else if (browser.playlist[browser.songPos].fullname.indexOf("_3SID") != -1) browser.chips = 3;
				this.resetStereoPanning();
				viz.initGraph(browser.chips);
				viz.enableAllPianoVoices();
			}.bind(this));
			this.updateSubtuneText();
			$(id == "subtune-plus" && !SID.emulatorFlags.offline ? "#subtune-minus" : "#subtune-plus").removeClass("disabled");
		}

		if (id.substr(0, 4) == "skip") {
			SID.setVolume(0);
			browser.clearSpinner();
	 		// Skip to previous or next tune
			$("#time-bar").empty().append('<div></div>');
			this.state("subtunes", "disabled");
			this.state("prev/next", "disabled");

			// The DO blocks below makes sure disabled rows are skipped until
			// a playable row is found (unless a list boundary is hit first)
			var songRating = songLength = 0, moreSubtunes = false;
			if (id == "skip-next") {
				do {
					browser.songPos++;
					songRating = browser.playlist[browser.songPos].rating;
					songLength = browser.getLength(browser.playlist[browser.songPos].startsubtune);
					moreSubtunes = browser.playlist[browser.songPos].startsubtune < browser.playlist[browser.songPos].subtunes - 1;

					if (browser.songPos == browser.playlist.length - 1) {
						// At the end of the list
						$("#skip-next").addClass("disabled");
						// Don't play the song in the bottom if a setting is supposed to skip it
						if (isAutoProgress) {
							if ((main.getSettingValue("skip-bad") && (songRating == 1 || songRating == 2)) ||
								(main.getSettingValue("skip-short") && songLength < 10 && !moreSubtunes)) {
								$("#stop").trigger("mouseup");
								SID.stop();
								return false;
							} else if (main.getSettingValue("skip-short") && songLength < 10) {
								// The default is too short, but what about the subsequent sub tunes in it?
								$("#subtune-plus").trigger("mouseup", false);
								return false;
							}
						}	
						if (SID.emulator == "youtube") {
							SID.setSeek(0);
							$("#time-length").empty().append("0:00");
						}
						break;
					}
				} while ($("#songs tr").eq(browser.songPos + browser.subFolders).hasClass("disabled") || 
					(isAutoProgress && main.getSettingValue("skip-bad") && (songRating == 1 || songRating == 2)) ||
					(isAutoProgress && main.getSettingValue("skip-short") && songLength < 10 && !moreSubtunes));
			} else {
				do {
					browser.songPos--;
					if (browser.songPos == 0) {
						// At the beginning of the list
						$("#skip-prev").addClass("disabled");
						if (SID.emulator == "youtube") {
							SID.setSeek(0);
							$("#time-length").empty().append("0:00");
						}
						break;
					}
				} while ($("#songs tr").eq(browser.songPos + browser.subFolders).hasClass("disabled"));
			}

			if ($("#songs tr").eq(browser.songPos + browser.subFolders).hasClass("disabled")) return false;

			// Override default sub tune to first if demanded by a setting
			var subtune = main.getSettingValue("first-subtune") ? 0 : browser.playlist[browser.songPos].startsubtune;
			// The default is too short, but what about the subsequent sub tunes in it?
			if (isAutoProgress && main.getSettingValue("skip-short") && songLength < 10) {
				while (browser.getLength(subtune) < 10) {
					subtune++;
					if (subtune >= browser.playlist[browser.songPos].subtunes - 1) {
						// The rest of the sub tunes were all too short - NEXT!
						$("#skip-next").removeClass("disabled").trigger("mouseup", false);
						return false;
					}
				}
			}

			// Show loading spinner on the new row we're trying to skip to
			browser.showSpinner($("#folders tr").eq(browser.subFolders + browser.songPos).find("td.sid"));

			SID.load(subtune, browser.getLength(subtune), browser.playlist[browser.songPos].fullname, function(error) {

				main.trackingEvent("start:sid", browser.playlist[browser.songPos].id);

				browser.clearSpinner();

				if (!error) {

					if (!SID.emulatorFlags.offline) {
						this.state("play/stop", "enabled");
						$("#volume").prop("disabled", false);
					}

					// Only enable PREV or NEXT if not at list boundaries
					if (browser.songPos != browser.playlist.length - 1)
						$("#skip-next").removeClass("disabled");
					if (browser.songPos != 0)
						$("#skip-prev").removeClass("disabled");

					this.subtuneMax = SID.getSongInfo().maxSubsong;
					this.subtuneCurrent = subtune;
					this.updateSubtuneText();
					if (this.subtuneMax && !SID.emulatorFlags.offline) $("#subtune-value").removeClass("disabled");
					if (subtune < this.subtuneMax && !SID.emulatorFlags.offline) $("#subtune-plus").removeClass("disabled");
					if (subtune > 0 && !SID.emulatorFlags.offline) $("#subtune-minus").removeClass("disabled");

					this.updateInfo();
					this.updateSundry();

					browser.getCSDb();
					if (typeof browser.playlist[browser.songPos].profile != "undefined")
						if (browser.playlist[browser.songPos].profile != "") {
							browser.getComposer(browser.playlist[browser.songPos].profile, true);
						} else {
							// If composers_id = 0 then do this
							$("#topic-profile").empty().append('<i>No profile available.</i>');
							$("#atopic-profile").empty().append('<div class="annexMsg">No profile to show.</div>');
							$("#atopic-links").empty();
							browser.previousOverridePath = "_SID Happens";
						}
					else if (browser.isSearching || browser.path.substr(0, 2) === "/$" || browser.path.substr(0, 2) === "/!")
						browser.getComposer(browser.playlist[browser.songPos].fullname);
					browser.getGB64();
					browser.getRemix();
					browser.getPlayerInfo({player: browser.playlist[browser.songPos].player});
					main.updateURL();
				}

				browser.chips = 1;
				if (browser.playlist[browser.songPos].fullname.indexOf("_2SID") != -1) browser.chips = 2;
				else if (browser.playlist[browser.songPos].fullname.indexOf("_3SID") != -1) browser.chips = 3;
				this.resetStereoPanning();
				viz.initGraph(browser.chips);
				viz.startBufferEndedEffects();

				// Mark the next row in the browser list
				$("#songs tr").removeClass("selected");
				var $tr = $("#folders tr").eq(browser.subFolders + browser.songPos);
				$tr.addClass("selected");
				browser.kbSelectedRow = $tr.index();
				browser.moveKeyboardSelection(browser.kbSelectedRow, false);

				// A timed out tune should only auto-center if a setting demands it
				if (!isAutoProgress || main.getSettingValue("mark-tune")) {
					var rowPos = $("#folders tr").eq($("tr.selected").index())[0].offsetTop;
					var halfway = $("#folders").height() / 2 - 26; // Last value is half of SID file row height
					$("#folders").scrollTop(rowPos > halfway ? rowPos - halfway : 0);
				}

				if (error) browser.errorRow();
				else if (SID.emulatorFlags.forcePlay) SID.play();

			}.bind(this));
		}
		if (id.substr(0, 7) == "subtune" || id.substr(0, 4) == "skip") {
			// Update all buttons
			this.buttonTimer = setTimeout(function() {
				this.selectButton($("#play-pause"));
				this.toggleStateFaster();
				this.togglePlayOrPause();
				if (id == "subtune-plus" && this.subtuneCurrent == this.subtuneMax)
					$("#subtune-plus").addClass("disabled");
				else if (id == "subtune-minus" && this.subtuneCurrent == 0)
					$("#subtune-minus").addClass("disabled");
				this.buttonTimer = 0;
			}.bind(this), 150, id);
		}
	},

	/**
	 * When touching/holding a button on the screen of a mobile device.
	 * 
	 * @param {*} event 
	 */
	onTouchStart: function(event) {
		if (event.target.id == "faster") {
			$("#faster").trigger("mousedown");
			return false;
		}
	},

	/**
	 * When no longer touching/holding a button on the screen of a mobile device.
	 * 
	 * @param {*} event 
	 */
	onTouchEnd: function(event) {
		if (event.target.id == "faster") {
			$("#faster").trigger("mouseup");
			return false;
		}
	},

	/**
	 * Click the left mouse button somewhere on the controls, the time bar, or in the boxes above.
	 * 
	 * @param {*} event 
	 */
	onClick: function(event) {
		switch(event.target.id) {
			case "stop":
				// STOP button
				$("#time-bar").empty().append('<div></div>');
				SID.stop();
				$("a.redirect").removeClass("playing");
				// Also stop any <AUDIO> element playing
				$("#topic-remix audio").each(function() {
					var $sound = $(this)[0];
					$sound.pause();
					$sound.currentTime = 0;
				});
				break;
			case "sid-model":
				// Toggle between SID model 6581 or 8580
				$("#sid-model").remove();
				browser.showSpinner($("#folders tr").eq(browser.subFolders + browser.songPos).find("td.sid"));
				if ($(event.target).hasClass("MOS6581")) {
					$("#info").append('<div id="sid-model" class="MOS8580" title="SID chip model set to MOS 8580">8580</div>');
					SID.setModel("8580");
				} else {
					$("#info").append('<div id="sid-model" class="MOS6581" title="SID chip model set to MOS 6581">6581</div>');
					SID.setModel("6581");
				}
				main.showSundryFilterContents();
				break;
			case "clockspeed":
				// Toggle between PAL or NTSC
				$("#clockspeed").remove();
				if ($(event.target).hasClass("PAL")) {
					$("#info").append('<div id="clockspeed" class="NTSC" title="American NTSC standard (60 Hz)">NTSC</div>');
					SID.setEncoding("NTSC");
				} else {
					$("#info").append('<div id="clockspeed" class="PAL" title="European PAL standard (50 Hz)">PAL</div>');
					SID.setEncoding("PAL");
				}
				break;
			case "loop":
				// LOOP toggle button
				break;
			case "time-bar":
				if (typeof browser.songPos !== "undefined" && SID.emulatorFlags.supportSeeking) {
					// Clicking the time bar for a different seek position (if supported by the handler)
					var maxSeconds = browser.getLength(this.subtuneCurrent, true),
						barWidth = $("#time-bar").width(),
						clickPos = event.originalEvent.layerX;
					var clickSeconds = (clickPos / barWidth) * maxSeconds;
					// Temporarily disable transition to change the bar width instantaneously
					$("#time-bar div").css("transition", "all 100ms linear");
					SID.setSeek(clickSeconds);
					setTimeout(function(){
						$("#time-bar div").css("transition", "all 1s linear");
					}, 250);
				}
				break;
			case "scope1":
			case "scope2":
			case "scope3":
			case "scope4":
				// Toggle voice 1 to 4 (by clicking on scope canvas boxes)
				// NOTE: The "keyup" event in 'viz.js' catches this.
				var e = $.Event("keyup");
				e.which = e.keyCode = 48 + parseInt(event.target.id.slice(-1));
				e.shiftKey = event.shiftKey;
				$(window).trigger(e);
				break;
			case "set-16k":
				// Button in scope sundry box for forcing a buffer size of 16384
				// NOTE: This is now only used by the legacy WebSid handler.
				$("#visuals-piano .dropdown-buffer").val("16384").trigger("change");
				break;				
			case "sidwiz":
				// Toggle 'SidWiz' mode ON or OFF for the oscilloscope voices
				// NOTE: Don't add the DOM element check in 'animateScope()' as it needs to be fast.
				viz.scopeMode = $("#sidwiz").is(":checked");
				break;
			case "showtags":
				// Toggle tags shown in SID rows ON or OFF
				main.showTags = $("#showtags").is(":checked");
				$("#songs .tags-line").css("visibility", main.showTags ? "" : "hidden");
				break;
			case "filter-r2":
			case "filter-r3":
			case "filter-r4":
				// Set 6581 filter settings to R2, R3, or R4
				SID.setRevision(event.target.id.split("-")[1]);
				break;
			default:
				if (event.target.tagName === "B") {
					// Clicked a star to set a rating for a file
					browser.registerStarRating(event, SID.getFullName());
				} else if (event.target.className == "subtune") {
					// Play the subtune clicked in the STIL tab of the sundry box
					this.subtuneCurrent = event.target.innerHTML - 1;
					$("#subtune-plus,#subtune-minus").removeClass("disabled").addClass("disabled");
					$("#time-bar").empty().append('<div></div>');
					SID.load(this.subtuneCurrent, browser.getLength(this.subtuneCurrent), browser.playlist[browser.songPos].fullname, function(){
						this.resetStereoPanning();
						this.updateSubtuneText();
						if (this.subtuneCurrent < this.subtuneMax && !SID.emulatorFlags.offline) $("#subtune-plus").removeClass("disabled");
						if (this.subtuneCurrent > 0 && !SID.emulatorFlags.offline) $("#subtune-minus").removeClass("disabled");
						this.updateInfo();
						if (SID.emulatorFlags.forcePlay) SID.play();
					}.bind(this));
				} else if (event.target.className.substr(0, 3) == "tag") {
					// Clicked a tag in the sundry box; search "here" for it now
					var tag = event.target.innerHTML.toLowerCase();
					if ($(event.target).hasClass("tag-remix64"))
						tag = "remix64";
					else if ($(event.target).hasClass("tag-music"))
						tag = "music";
					else if ($(event.target).hasClass("tag-collection"))
						tag = "collection";
					/*else if ($(event.target).hasClass("tag-compo"))
						tag = "compo";*/
					else if ($(event.target).hasClass("tag-winner"))
						tag = "winner";
					else if ($(event.target).hasClass("tag-gamebase64"))
						tag = "gamebase64";
					$("#dropdown-search").val("tag");
					$("#search-here").prop('checked', true);
					$("#search-box").val('"'+tag+'"').trigger("keyup");
					$("#search-button").trigger("click");
				} else if (event.target.className == "set-websid") {
					this.selectEmulator("websid");
				} else if (event.target.className == "set-jsidplay2") {
					this.selectEmulator("jsidplay2");
				} else if (event.target.className == "set-6581") {
					$("#sid-model").trigger("click");
				}
		}
	},

	/**
	 * When dragging a range slider.
	 * 
	 * @param {*} event 
	 */
	onInput: function(event) {
		switch (event.target.id) {
			case "volume":
				// Main volume; between 0 and 1
				var vol = event.currentTarget.value / 100;
				SID.setMainVolume(vol);
				localStorage.setItem("volume", vol);
				break;
			case "osc-zoom":
				// Oscilloscope zoom; 1 (closest) to 5 (farthest)
				viz.scopeZoom = event.target.value;
				break;
		}
	},

	/**
	 * Select one of the control buttons.
	 * 
	 * @param {object} $element		The jQuery element with the button to select
	 */
	selectButton: function($element) {
		$(".button-ctrls").removeClass("button-idle button-selected").addClass("button-idle");
		$element.removeClass("button-idle").addClass("button-selected");
	},

	/**
	 * Toggle disabling/enabling the 'Faster' button.
	 */
	toggleStateFaster: function() {
		if (SID.emulatorFlags.supportFaster)
			$("#play-pause").hasClass("button-selected") && $("#play").css("display") != "none" ? $("#faster").removeClass("disabled") : $("#faster").addClass("disabled");
	},

	/**
	 * Toggle playing or stopping a SID tune.
	 * 
	 * @param {boolean} buttonsOnly		If specified and TRUE, only handle the buttons
	 */
	togglePlayOrPause: function(buttonsOnly) {
		if (typeof buttonsOnly !== "undefined" && !buttonsOnly) {
			if ($("#play").css("display") !== "none") {
				if (this.emulatorChanged) {
					// Clicking the same row again is safest
					$("#folders tr.selected").children("td.sid").trigger("click", ctrls.subtuneCurrent);
					this.emulatorChanged = false;
				} else
					SID.play(); // Has the power to resume after pause
			} else {
				SID.pause();
				$("a.redirect").removeClass("playing");
			}
		}
		if ($("#play-pause").hasClass("button-selected") || ($("#play-pause").hasClass("button-idle") && $("#play").css("display") === "none"))
			$("#play-pause svg").toggle();
	},

	/**
	 * Hide 'Play' and show the 'Pause' button.
	 */
	setButtonPlay: function() {
		$(".button-ctrls").removeClass("button-idle button-selected").addClass("button-idle");
		$("#play-pause").addClass("button-selected");
		$("#play").hide();
		$("#pause").show();
	},

	/**
	 * Show 'Play' and hide the 'Pause' button.
	 */
	setButtonPause: function() {
		$(".button-ctrls").removeClass("button-idle button-selected").addClass("button-idle");
		$("#play-pause").addClass("button-selected");
		$("#play").show();
		$("#pause").hide();
	},

	/**
	 * Is a SID tune currently playing?
	 * 
	 * @return {boolean}	TRUE if still playing
	 */
	isPlaying: function() {
		return $("#play-pause").hasClass("button-selected") && $("#play").css("display") === "none";
	},

	/**
	 * Update current and maximum number of subtunes between the UP/DOWN buttons.
	 */
	updateSubtuneText: function() {
		$("#subtune-value").empty().append((this.subtuneCurrent + 1) + "/" + (this.subtuneMax + 1));
	},

	/**
	 * Update the contents of the top info box, including the blue memory bar. This also
	 * includes the toggle flags in the top left corner.
	 * 
	 * HVSC: Name, author and copyright lines.
	 * CGSC: A colorful PETSCII box using a C64 font.
	 */
	updateInfo: function() {
		var fullname = browser.playlist[browser.songPos].fullname,
			profile = browser.playlist[browser.songPos].profile;
		var isCGSC = fullname.substr(-4) == ".mus";
		var info = SID.getSongInfo(isCGSC ? "info" : false), // Always parse .mus files
			unknown = '<small class="u1">?</small>?<small class="u2">?</small>';
			$infoText = $("#info-text");
		$("#sid-model,#clockspeed").remove();
		$infoText.empty().append(isCGSC && SID.emulatorFlags.hasFlags ? '<div id="corner"></div>' : '');

		if (isCGSC) {

			this.convertC64Text();
			$("#info-composer").hide();

		} else {

			// Show a smaller avatar image in the left side of the info box (HVSC and SH only)
			if ((fullname.indexOf("/MUSICIANS/") !== -1 || fullname.indexOf("/_SID Happens/") !== -1) && SID.emulator !== "youtube") {
				// Construct the image filename out of fullname (or profile if in SH folder)
				var homeFolder = "", thumbnail;
				if (fullname.indexOf("/MUSICIANS/") !== -1) {
					homeFolder = fullname
						.replace("hvsc/", "")
						.replace("_High Voltage SID Collection/", "")
						.replace(/\/[^/]+$/, ""); // Get rid of the SID filename
					thumbnail = "images/composers/"+fullname.substring(fullname.indexOf("MUSICIANS"))
						.split("/").slice(0, -1).join("_").toLowerCase() + ".jpg";
				} else {
					homeFolder = profile
						.replace("_High Voltage SID Collection/", "");
					thumbnail = "images/composers/"+profile.substring(profile.indexOf("MUSICIANS"))
						.replace(/\//g, "_").toLowerCase() + ".jpg";				
				}
				// Prepare a link to composer's home folder if it exists
				var homeStart = homeFolder !== ""
					? '<a href="?file=/'+homeFolder+'" class="redirect">'
					: '';
				var homeEnd = homeFolder !== ""
					? '</a>'
					: '';
				let img = new Image();
				img.onload = function() {
					// The image file exists so show it in the info box
					$("#info-composer").empty().append(homeStart+'<img src="'+thumbnail+'" alt="" />'+homeEnd).show();
				};
				img.onerror = function() {
					// No image file so just show a generic placeholder image
					$("#info-composer").empty().append(homeStart+'<img src="images/composer'+
						(parseInt(colorTheme) ? "_dark" : "")+'.png" alt="" />'+homeEnd).show();
				};
				// The "onerror" event will show a red error line in console log; this is harmless
				img.src = thumbnail;
			} else {
				$("#info-composer").hide();
			}

			// If the SID tune is not played in its home folder, add links to song name and author
			var songName = info.songName.replace("<?>", unknown),
				songAuthor = info.songAuthor.replace("<?>", unknown);
			if ((fullname.indexOf(browser.path) === -1 || browser.isSearching) && !browser.isTempTestFile()) {
				var homePath = decodeURIComponent($("#songs tr").eq(browser.songPos + browser.subFolders).find(".entry").attr("data-name")).replace("_High Voltage SID Collection/", ""),
					sidFile = fullname.split("/").slice(-1)[0];
				songName = '<a href="?file=/'+homePath+'" class="redirect">'+songName+'</a>';
				songAuthor = '<a href="?file=/'+homePath.replace(sidFile, "")+'" class="redirect">'+songAuthor+'</a>';
			} else if (typeof profile != "undefined" && profile != "" && !main.miniPlayer)
				// It's a 'SID Happens' file that points to a profile so change the author to that
				songAuthor = '<a href="?file=/'+profile+'" class="redirect">'+songAuthor+'</a>';
			$infoText.append(
				songName+'<br />'+
				songAuthor+'<br />'+
				info.songReleased.replace("<?>", unknown));
		}
		// Memory bar
		var address = parseInt(browser.playlist[browser.songPos].address),
			size = parseInt(browser.playlist[browser.songPos].size) - 3;
		var hexStart = address.toString(16).toUpperCase(),
			hexEnd = (address + size).toString(16).toUpperCase();
		$("#memory-chunk").css({
			left:	((address / 65536) * 430)+"px",
			width:	((size / 65536) * 430)+"px",
		}).prop("title", "Location: $"+(hexStart.length === 4 ? hexStart : "0"+hexStart)+"-$"+(hexEnd.length === 4 ? hexEnd : "0"+hexEnd)+" ("+size+" bytes)");
		// Getting SID model and clock speed must have a small delay as some SID handlers need to play first
		setTimeout(function() {
			// SID model (values "unknown" and "MOS6581 / MOS858" (sic) will not be shown)
			if (SID.emulatorFlags.forceModel)
				browser.playlist[browser.songPos].sidmodel === "MOS6581" ? SID.setModel("6581") : SID.setModel("8580");
			var sidModel = "MOS"+SID.getModel();
			if (sidModel === "MOS6581" || sidModel === "MOS8580")
				$("#info").append('<div id="sid-model" class="'+sidModel+'" title="Originally made for the '+sidModel+' SID chip model">'+sidModel.substr(3)+'</div>');
			if (SID.emulatorFlags.supportEncoding) {
				// Clock speed (PAL or NTSC)
				var clockSpeed = SID.getEncoding();	// Relying on the emulator now
				if (clockSpeed === "PAL" || clockSpeed === "NTSC") {
					var clockMsg = clockSpeed === "PAL" ? "European PAL standard (50 Hz)" : "American NTSC standard (60 Hz)";
					$("#info").append('<div id="clockspeed" class="'+clockSpeed+'" title="'+clockMsg+'">'+clockSpeed+'</div>');
				}
			}
		}, 150);
		// Remember unique file ID in case the user clicks the star rating in the info box
		this.currentFileID = browser.playlist[browser.songPos].id;
		this.updateInfoRating();
		// Collection version
		if ($("#stopic-stil").css("display") != "none")
			// Only show collection version when the 'STIL/Lyrics' tab is selected
			this.updateSundryVersion();

		main.showSundryFilterContents();
	},
	
	/**
	 * Obtain the song info from a MUS file in CGSC and convert it to a colorful PETSCII
	 * box. The info is not returned but updated directly in the info box.
	 * 
	 * @author Peter Weighill (Compute's Gazette SID Collection)
	 */
	convertC64Text: function() {

		fetch(browser.playlist[browser.songPos].fullname)
		.then(response => response.blob())
		.then(function(blob) {
			var reader = new FileReader();

			reader.onloadend = function() {
				filedata = reader.result;

				var finalpost = 8;
				finalpost += (filedata.charCodeAt(3) & 0xff) * 256 + (filedata.charCodeAt(2) & 0xff);
				finalpost += (filedata.charCodeAt(5) & 0xff) * 256 + (filedata.charCodeAt(4) & 0xff);
				finalpost += (filedata.charCodeAt(7) & 0xff) * 256 + (filedata.charCodeAt(6) & 0xff);

				var strContents = filedata.substring(finalpost);

				var cnow = clast = 14,
					revnow = revlast = 0;

				var out = '<span class=\"c14\">', charin, charouttext;
				var i = 0, len = strContents.length;
				while (i < len) {
					charin = strContents.charCodeAt(i) & 0xff;
					// if (charin == 0) break;
					charnext = strContents.charCodeAt(i + 1) & 0xff;
					charouttext = "";

					if (charin == 146)	{ revnow = 0; }
					if (charin == 18)	{ revnow = 128; }
					if (charin == 157)	{ /* todo CRSR-LEFT */ }
					if (charin == 13)	{ charouttext = "<br>"; revnow = 0; }

					if (charin == 29)	{ charouttext = "&nbsp;"; }   
					if (charin == 32)	{ charouttext = "&nbsp;"; }   
					if (charin == 160)	{ charouttext = "&nbsp;"; }   

					if (charin == 5)	{ cnow = 1;  }
					if (charin == 28)	{ cnow = 2;  }
					if (charin == 30)	{ cnow = 5;  }
					if (charin == 31)	{ cnow = 6;  }
					if (charin == 129)	{ cnow = 8;  }
					if (charin == 144)	{ cnow = 0;  }
					if (charin == 149)	{ cnow = 9;  }
					if (charin == 150)	{ cnow = 10; }
					if (charin == 151)	{ cnow = 11; }
					if (charin == 152)	{ cnow = 12; }
					if (charin == 153)	{ cnow = 13; }
					if (charin == 154)	{ cnow = 14; }
					if (charin == 155)	{ cnow = 15; }
					if (charin == 156)	{ cnow = 4;  }
					if (charin == 158)	{ cnow = 7;  }
					if (charin == 159)	{ cnow = 3;  }

					if (charin >= 33 && charin <= 63)	{
						charouttext=String.fromCharCode(charin);
						if (charin == 60) charouttext=String.fromCharCode("&lt;");
						if (charin == 62) charouttext=String.fromCharCode("&gt;");
					}
					if (charin >= 64 && charin <= 90)	{ charouttext=String.fromCharCode(charin); }   
					if (charin >= 91 && charin <= 127)	{ charouttext="&#"+(57344+charin)+";"; }   
					if (charin >= 161 && charin <= 255)	{ charouttext="&#"+(57344+charin)+";"; }   

					if (clast != cnow || revlast != revnow) {
						out = out + '</span>';
						if (revnow == 0)
							out = out + '<span class=\"c'+cnow+'\">';
						else
							out = out + '<span class=\"b'+cnow+'\">';
					}

					if (charnext == 157) { /* CRSR-LEFT */ charouttext = ""; }

					out += charouttext;

					clast = cnow;
					revlast = revnow;

					i++;
				}
				out += "</span>";
				$("#info-text").append('<div class="c64blackbg c64font" style="white-space:nowrap">'+out+'</div>');
			}
			reader.readAsBinaryString(blob);
		});
	},

	/**
	 * Set the pace of the tune (VBI or multiplier for quickspeed). The value is shown
	 * in a field in the top info box. If digi is used by the song, the type string
	 * and sample is prepended too.
	 * 
	 * This is called periodically by a 'setInterval' loop when adding events.
	 */
	pace: function() {
		$("#pace").remove();
		if (this.isPlaying()) {
			// Constantly poll the handler in case digi stuff comes up
			var digi = SID.getDigiType() !== "" && SID.getDigiType() !== "NONE"
				? 'Digi ('+SID.getDigiType()+') <div>'+SID.getDigiRate()+'</div> Hz / '
				: "";
			// Now for how the player is actually called
			if (SID.emulatorFlags.returnCIA) {
				var pace = SID.getPace();
				var timer = pace ? (pace == 1 ? "CIA" : '<div style="width:34.5px;">'+pace+'x</div>') : "VBI";
				$("#info").append('<span id="pace">'+digi+timer+'</span>');
			}
		}
	},

	/**
	 * Update the sundry box below the top info box.
	 * 
	 * Also updates the STIL page tab.
	 * 
	 * HVSC: Adapted STIL information for the selected SID file, if available.
	 * CGSC: Lyrics for the MUS file, if available.
	 */
	updateSundry: function() {
		$("#topic-stil,#stopic-stil")
			.empty()
			.removeClass("c64font");

		var file = browser.playlist[browser.songPos].filename,
			stil = browser.playlist[browser.songPos].stil,
			isCGSC = browser.playlist[browser.songPos].fullname.substr(-4) == ".mus",
			isSidHappens = browser.playlist[browser.songPos].fullname.indexOf("/"+PATH_UPLOADS) !== -1;
			//isRoot = $("#folder-root").hasClass("disabled");

		var tabName = "STIL";
		if (isCGSC)
			tabName = "Lyrics";
		else if (isSidHappens)
			tabName = "Notes";
		$("#stab-stil").empty().append(tabName); // Set for both sundry and dexter tabs
		$("#tab-stil").empty().append(tabName+'<div id="note-stil" class="notification stilcolor"></div>');

		if (isCGSC) {
			$("#topic-stil,#stopic-stil").addClass("c64font");
			// If there's a .wds file (with lyrics) for the .mus file then read and process it
			var fullname = file.indexOf("/") !== -1 ? "/"+file : browser.path.substr(1)+"/"+file;
			fullname = browser.ROOT_HVSC+"/"+fullname.replace(".mus", ".wds");

			$.get("php/file_exists.php", { file: fullname }, function(wdsExists) {
				if (wdsExists) {
					// WDS file exists
					fetch(fullname)
					.then(response => response.blob())
					.then(function(blob) {
						var reader = new FileReader();
			
						reader.onloadend = function() {
							var strContents = reader.result,
								rep = 1,
								cnow = clast = 14,
								i = revnow = revlast = 0,
								isExtended = isMatrix = false,
								out = '<span class=\"c14\">',
								len = strContents.length,
								charin, charouttext;

							while (i < len) {
								charin = strContents.charCodeAt(i) & 0xff;
								if (i + 1 < len) charnext = strContents.charCodeAt(i + 1) & 0xff;
								else charnext = 0;
								charouttext = "";

								// Look for extended WDS and line/matrix part
								if (charin == 0xFF) {
									isExtended = true;  
									if (i == 0) isMatrix = false;
									i++;
									continue;
								}

								if (!isExtended) {
									if (charin > 127) charin -= 128;
									out += (charin < 32 || charin > 126)
										? (charin == 13 ? "<br />" : " ")
										: String.fromCharCode(charin);
									i++;
									continue;
								}

								if (isExtended && !isMatrix) {
									if (charin == 0) isMatrix = true;
									i++;
									continue;
								}    

								// Special repeat char: 01 NN CC (repeat NN times the CC char)
								if (charin == 1 || (charin == 0 && charnext == 5)) {
									rep = charnext;
									i += 2; 
									continue;
								}   

								if (charin == 146)	{ revnow = 0; }
								if (charin == 18)	{ revnow = 128; }
								if (charin == 13)	{ charouttext = "<br>"; revnow = 0; }
			
								if (charin == 29)	{ charouttext = "&nbsp;"; }   
								if (charin == 32)	{ charouttext = "&nbsp;"; }   
								if (charin == 160)	{ charouttext = "&nbsp;"; }   
			
								if (charin == 5)	{ cnow = 1;  }
								if (charin == 28)	{ cnow = 2;  }
								if (charin == 30)	{ cnow = 5;  }
								if (charin == 31)	{ cnow = 6;  }
								if (charin == 129)	{ cnow = 8;  }
								if (charin == 144)	{ cnow = 0;  }
								if (charin == 149)	{ cnow = 9;  }
								if (charin == 150)	{ cnow = 10; }
								if (charin == 151)	{ cnow = 11; }
								if (charin == 152)	{ cnow = 12; }
								if (charin == 153)	{ cnow = 13; }
								if (charin == 154)	{ cnow = 14; }
								if (charin == 155)	{ cnow = 15; }
								if (charin == 156)	{ cnow = 4;  }
								if (charin == 158)	{ cnow = 7;  }
								if (charin == 159)	{ cnow = 3;  }
			
								if (charin >= 33 && charin <= 63)	{
									charouttext=String.fromCharCode(charin);
									if (charin == 60) charouttext=String.fromCharCode("&lt;");
									if (charin == 62) charouttext=String.fromCharCode("&gt;");
								}
								if (charin >= 64 && charin <= 90)	{ charouttext=String.fromCharCode(charin); }   
								if (charin >= 91 && charin <= 127)	{ charouttext="&#"+(57344+charin)+";"; }   
								if (charin >= 161 && charin <= 255)	{ charouttext="&#"+(57344+charin)+";"; }   

								if (isExtended && !isMatrix) {
									if (charin >= 192 && charin <= 218)
										charouttext="&#"+String.fromCharCode(57600+charin-128)+";"; 
								 }

								if (clast != cnow || revlast != revnow) {
									out += '</span>';
									if (revnow == 0)
										out += '<span class=\"c'+cnow+'\">';
									else
										out += '<span class=\"b'+cnow+'\">';
								}
			
								if (charnext == 157) { /* CRSR-LEFT */ charouttext = ""; }
			
								for (var j = 0; j < rep; j++)
									out += charouttext;
			
								clast = cnow;
								revlast = revnow;
			
								rep = 1;
								i++;
							}
							out += "</span>";
							$("#topic-stil,#stopic-stil").append('<div class="c64blackbg">'+out+'</div>');
						}
						reader.readAsBinaryString(blob);
					});
					this.handleStilNotification();				
				} else {
					// WDS file does not exist
					$("#stopic-stil")
						.css("overflow", "none")
						.empty().append('<div id="tips" class="no-info">No lyrics</div>');
					$("#topic-stil").empty().append("No lyrics available for this MUS file.");
				}
			}.bind(this));
		} else {
			// Standard .sid file so show STIL information from HVSC
			if (stil === "") {
				$("#stopic-stil")
					.css("overflow", "none")
					.append('<div id="tips" class="no-info">'+(isSidHappens ? 'No notes for this SID file' : 'No STIL information')+'</div>');
				$("#topic-stil").empty().append("<i>No information available.</i>");
			} else {
				$("#topic-stil,#stopic-stil").empty().append(stil);
				this.handleStilNotification();				
			}
		}

		// Tab 'Tags'
		this.updateSundryTags(browser.playlist[browser.songPos].tags);
	},

	/**
	 * Show a special notification character (if not in focus).
	 */
	handleStilNotification: function() {
		if ($("#tabs .selected").attr("data-topic") !== "stil")
			$("#note-stil").empty().append("&#9679;").show();
		else
			$("#note-stil").hide();
	},

	/**
	 * Show a message in the first sundry tab (typically a tip).
	 * 
	 * The type of message is controlled with an administrator setting.
	 */	
	showSundryMessage: function() {
		if (browser.path !== "" || main.miniPlayer) return;

		$("#stopic-stil").empty().append('<div id="sundry-news"></div>');
		$.get("php/sundry_message.php", (data) => {
			browser.validateData(data, (data) => {
				$("#sundry-news").append(data.html);
				// Capitalize the first letter of the tab word
				$("#stab-stil").empty().append(data.type.charAt(0).toUpperCase()+data.type.slice(1));
			});
		});
	},

	/**
	 * Update the sundry box with tags.
	 * 
	 * @param {string} tags		An HTML list of the tags
	 */
	updateSundryTags: function(tags) {
		$("#stopic-tags")
			.empty()
			.append(tags === "" || tags === "0" || $(tags).html() === "&nbsp;"
				? '<div class="sundryMsg no-info">No tags found</div>'
				: tags
			);
	},

	/**
	 * Update the filter controls in the sundry box.
	 * 
	 * @handlers websid
	 */
	updateFilterControls: function() {
		$("#filter-base-edit,#filter-base-slider").val(SID.filterWebSid.base);
		$("#filter-max-edit,#filter-max-slider").val(SID.filterWebSid.max);
		$("#filter-steepness-edit,#filter-steepness-slider").val(SID.filterWebSid.steepness);
		$("#filter-x_offset-edit,#filter-x_offset-slider").val(SID.filterWebSid.x_offset);
		$("#filter-distort-edit,#filter-distort-slider").val(SID.filterWebSid.distort);
		$("#filter-distortOffset-edit,#filter-distortOffset-slider").val(SID.filterWebSid.distortOffset);
		$("#filter-distortScale-edit,#filter-distortScale-slider").val(SID.filterWebSid.distortScale);
		$("#filter-distortThreshold-edit,#filter-distortThreshold-slider").val(SID.filterWebSid.distortThreshold);
		$("#filter-kink-edit,#filter-kink-slider").val(SID.filterWebSid.kink);
	},

	/**
	 * Update the collection version just above the sundry box.
	 */
	updateSundryVersion: function() {
		if (!main.isSongSelected()) return;
		
		var version = browser.playlist[browser.songPos].hvsc,
			isCGSC = browser.playlist[browser.songPos].fullname.substr(-4) == ".mus",
			$sundryCtrls = $("#sundry-ctrls");
		$sundryCtrls.empty();
		if (version >= 50 && SID.emulator != "youtube")
			$sundryCtrls.append('<span id="hvsc-version">'+
				(isCGSC ? 'CGSC v'+String(version).substr(0, 1)+'.'+String(version).substr(1) : 'HVSC #'+String(version))+'</span>');
	},

	/**
	 * Update the rating stars in the info box.
	 * 
	 * This is now shown for the YouTube handler and CGSC.
	 * 
	 * CGSC:     The colored info box overlaps ratings stars - solution could be to shrink the stars?
	 * YouTube:  Video takes up all space - stars could be on top but this handler is rarely used anyway.
	 */
	updateInfoRating: function() {
		var isCGSC = browser.playlist[browser.songPos].fullname.substr(-4) == ".mus";
		$("#info-rating").remove();
		if (!isCGSC && SID.emulator != "youtube")
			$("#info").append('<div id="info-rating">'+browser.buildStars(browser.playlist[browser.songPos].rating)+'</div>');
	},

	/**
	 * Reset all stereo panning to center and enable sliders for more chips.
	 * 
	 * @handlers websid
	 */
	resetStereoPanning: function() {

		// SID.resetStereo(); // Now disabled below to retain settings across songs

		// Assume one chip to begin with
		$("#stereo-sh2,.stereo-s2 label,.stereo-s2 input,#stereo-sh3,.stereo-s3 label,.stereo-s3 input")
			.removeClass("disabled").addClass("disabled");
		$(".stereo-s2 input,.stereo-s3 input").prop("disabled", true);

		// $("#dropdown-stereo-mode").val(0).trigger("change");

		if (browser.chips > 1) this.enableStereoChip(2);
		if (browser.chips > 2) this.enableStereoChip(3);
	},

	/**
	 * Enable stereo panning sliders for a specific chip.
	 * 
	 * @handlers websid
	 */
	enableStereoChip: function(chip) {
		$("#stereo-sh"+chip+",.stereo-s"+chip+" label,.stereo-s"+chip+" input").removeClass("disabled");
		$(".stereo-s"+chip+" input").prop("disabled", false);
	},

	/**
	 * Select SID handler. This changes the top left drop-down box, which in return
	 * refreshes the web site in order for the new SID handler to be activated.
	 * 
	 * @handlers all
	 * 
	 * @param {string} emulator		Emulator, e.g. "resid", "jsidplay2", etc.
	 */
	selectEmulator: function(emulator) {
		$("#dropdown-topleft-emulator")
			.styledSetValue(emulator)
			.next("div.styledSelect")
			.trigger("change");
		$("#dropdown-settings-emulator")
			.styledSetValue(emulator)
			.next("div.styledSelect")
			.trigger("change", true); // Ignore event function call
	},

	/**
	 * Set target to "disabled" (i.e. grayed out) or "enabled" (interactive). Affects
	 * green buttons in controls and browser.
	 * 
	 * @param {string} target	An identifier string
	 * @param {string} state 	Set to "disabled" or "enabled"
	 */
	state: function(target, state) {
		var $this;
		switch (target) {
			case "play/stop":
				// The big play/pause and stop buttons
				$this = $("#play-pause,#stop");
				break;
			case "prev/next":
				// Buttons for skipping to previous or next tune in the list
				$this = $("#skip-next,#skip-prev");
				break;
			case "root/back":
				// Buttons for going back to root or just to the previous folder
				$this = $("#folder-root,#folder-back");
				break;
			case "subtunes":
				// Buttons for next or previous subtune 
				$this = $("#subtune-plus,#subtune-minus,#subtune-value");
				break;
			case "faster":
				// The fast forward button
				$this = $("#faster");
				break;
			case "loop":
				// Button for looping a tune indefinitely
				$this = $("#loop");
				break;
		}
		$this.removeClass("disabled");
		if (state == "disabled") $this.addClass("disabled");
	},
}