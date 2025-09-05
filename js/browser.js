
/**
 * DeepSID / Browser
 */

const TR_SPACER 		= '<tr class="disabled"><td class="spacer" colspan="2"></td></tr>';
const TR_DIVIDER		= '<tr class="disabled"><td class="divider" colspan="2"></td></tr>';

const TAGS_BRACKET		= '<div class="tags-bracket"></div>';

const playerStrips = [
	{
		type:	"GoatTracker",
		class:	"pl-a",
	},
	{
		type:	"NewPlayer",
		class:	"pl-b",
	},
	{
		type:	"SidWizard",
		class:	"pl-c",
	},
	{
		type:	"SidFactory_II",
		class:	"pl-d",
	},
	{
		type:	"SidFactory II",
		class:	"pl-d",
	},
	{
		type:	"DMC",
		class:	"pl-e",
	},
	{
		type:	"SidTracker64",
		class:	"pl-f",
	},
];

function Browser() {

	this.ROOT_HVSC = 'hvsc';
	this.HVSC_VERSION = 83;
	this.CGSC_VERSION = 147;

	this.path = "";
	this.search = "";
	this.hvsc = null;
	this.previousOverridePath = "";

	this.cache = {
		folder:			"",
		incompatible:	"",
		compolist:		[],
		composort:		"name",
		folderTags:		"0",
	};

	this.symlistFolders = [];
	this.fileTags = [];

	this.kbSelectedRow = 0;
	this.currentScrollPos = 0;
	this.scrollPositions = [];
	this.kbPositions = [];
	this.sliderButton = false;
	this.annexNotWanted = GetParam("notips") !== ""

	this.secondsLength = 0;
	this.chips = 1;
	this.subtunes = 0;

	this.redirectFolder = "";

	this.slideTags = false;

	this.init();
}

Browser.prototype = {

	/**
	 * Initialize.
	 */
	init: function() {
		this.setupSortBox();
		this.getSymlists();
		this.getComposer();

		if (GetParam("file") === "" && GetParam("search") === "") this.getFolder();
		this.addEvents();
	},

	/**
	 * Add the events pertinent to this class.
	 */
	addEvents: function() {
		$("#songs").on("click", "#sym-rename,#sym-specify-subtune", function() {
			return false; // Prevent SID row from playing if an edit box in a SID row is clicked
		});

		$("#songs")
			.on("click", "button", this.onClickButton.bind(this))
			.on("click", "tr", this.onClickRow.bind(this))
			.on("mouseover", "tr", this.onMouseOver.bind(this))
			.on("mouseleave", "tr", this.onMouseLeave.bind(this));
		$("#dialog-tags").on("click", "button", this.onClickDialogBox.bind(this));
		$("#dialog-tags").on("mouseup", "button", this.onMouseUpDialogBox.bind(this));
		$("#dropdown-sort").change(this.onChange.bind(this));
		$("#topic-csdb").on("change", "#dropdown-sort-csdb", this.onChangeCSDb.bind(this));
		$("#upload-new").change(this.onUpload.bind(this));

		$("#folders table").on("contextmenu", "tr", this.contextMenu.bind(this));
		$("#corner-buttons").on("contextmenu", "button", this.contextCorner.bind(this));
		$("#panel")
			.on("click", ".context", this.onContextClick.bind(this))
			.on("contextmenu", "#contextmenu", function() { return false; })
			.on("mouseenter", "#contextmenu .submenu", this.contextSubMenu.bind(this))
			.on("mouseleave", "#contextmenu .submenu,#contextsubmenu", function() {
				if (!$("#contextsubmenu").is(":hover"))
					$("#contextsubmenu").remove();
			});

		$("#dialog-edit-videos").click(this.onYouTubeLinksClick.bind(this));

		setInterval(function() {
			// Update clock
			if (typeof SID != "undefined") {
				var secondsCurrent = SID.getCurrentPlaytime();
				$("#time-current").empty().append((Math.floor(secondsCurrent / 60)+":"+(secondsCurrent % 60 < 10 ? "0" : "")+(secondsCurrent % 60)).split(".")[0] /* No MS */ );
				// Update time bar
				$("#time-bar div").css("width", ((secondsCurrent / this.secondsLength) * 346)+"px");
			}
		}.bind(this), 200);

		$("#search-box").keydown(function(event) {
			if (event.keyCode == 13 && $("#search-box").val() !== "") {
				event.target.blur();
				$("#search-button").trigger("click");
			}
		}).keyup(function() {
			$("#search-button").removeClass("disabled");
			if ($("#search-box").val() !== "")
				$("#search-button").prop("disabled", false);
			else
				$("#search-button").prop("enabled", false).addClass("disabled");
		});

		// Turn "82 83 84..." years in composer chart into search links
		$("#topic-profile").on("click", "#ct-years .ct-horizontal", function(event) {
			var year = event.currentTarget.innerHTML;
			year = year.substr(0, 1) == "8" || year.substr(0, 1) == "9" ? "19"+year : "20"+year;
			$("#dropdown-search").val("copyright");
			$("#search-box").val(year);
			$("#search-here").prop("checked", true);
			$("#search-button").prop("disabled", false).trigger("click");
		});

		$("#get-all-tags").click(function() {
			this.showFolderTags(this.cache.folderTags);
		}.bind(this));

		$("#new-tag").keyup(function() {
			$("#dialog-tags-plus").removeClass("disabled");
			if ($("#new-tag").val() !== "")
				$("#dialog-tags-plus").prop("disabled", false);
			else
				$("#dialog-tags-plus").prop("enabled", false).addClass("disabled");
		});

		$("#dialog-all-tags")
			.on("dblclick", "option", function() {
				$("#dialog-tags-right").trigger("click");
			})
			.on("keydown", function(event) {
				if (event.keyCode == 13) {
					if ($("#dialog-all-tags option:selected").length) {
						$("#dialog-tags-right").trigger("click");				// Transfer entry
					} else {
						$("#dialog-tags .dialog-button-yes").trigger("click");	// Click 'OK' button
						$("#dialog-all-tags").blur();
					}
				}
			});

		$("#dialog-song-tags")
			.on("dblclick", "option", function() {
				$("#dialog-tags-left").trigger("click");
			})
			.on("keydown", function(event) {
				if (event.keyCode == 13) {
					if ($("#dialog-song-tags option:selected").length) {
						$("#dialog-tags-left").trigger("click");				// Transfer entry
					} else {
						$("#dialog-tags .dialog-button-yes").trigger("click");	// Click 'OK' button
						$("#dialog-song-tags").blur();
					}
				}
			});

		// Arrow functions preserve the outer 'this'
		// @todo Consider adapting all events that end in 'bind(this)'
		$("#dialog-list-start-tag").on("change", (event) => {
			this.startTag = $(event.target).val();
		});

		$("#dialog-list-end-tag").on("change", (event) => {
			this.endTag = $(event.target).val();
		});

		$("#pr-newplname").on("keydown", function(event) {
			var $renameButton = $("#dialog-playlist-rename .dialog-button-yes");
			if ($(this).val() == "")
				$renameButton.prop("disabled", true).removeClass("disabled").addClass("disabled");
			else {
				$renameButton.prop("disabled", false).removeClass("disabled");
				if (event.keyCode == 13)
					 $renameButton.trigger("click");
			}
		});

		$(document).on("click", function(event) {
			var $target = $(event.target);
			if (!$target.hasClass("line") || ($target.hasClass("line") && !$target.hasClass("disabled"))) {
				$("#contextmenu,#contextsubmenu").remove();
				if (typeof this.contextTR !== "undefined")
					this.contextTR.css("background", "");
			}
			if (!$target.hasClass("line"))
				this.restoreSIDRow();
		}.bind(this));

		$(document).keyup(function(event) {
			switch (event.keyCode) {
				case 27: // ESC
					$("#dialog-cover,.dialog-box,#zoomed").hide();
					$("#contextmenu,#contextsubmenu").remove();
					$("#dialog-all-tags").blur();
					if (typeof this.contextTR != "undefined")
						this.contextTR.css("background", "");
					this.restoreSIDRow();
					break;
				case 13: // Enter
					var $rename = $("#sym-rename"),
						$specifySubtune = $("#sym-specify-subtune");
					if ($rename.length) {
						// If the user maintained the collection shortcodes, restore their HTML formatting
						var newName =  $rename.val().replace(/(^HVSC\/(D|G|M)|CGSC|ESTC)/, '<font class="dim">$1</font>');
						$.post("php/symlist_rename.php", {
							symlist:	(this.isFileRenamed ? this.path.substr(1) : this.contextSID),
							fullname:	(this.isFileRenamed ? this.contextSID : ''),
							symid:		this.contextSymID,
							new:		newName,
						}, function(data) {
							this.validateData(data, function() {
								/*this.contextEntry.empty().append(newName+(this.isFileRenamed ? this.prevSymName.substr(-4) : ''));
								$("#sym-rename").remove();*/
								this.getFolder();
							});
						}.bind(this));
					} else if ($specifySubtune.length) {
						var newSubtune = $specifySubtune.val();
						if (parseInt(newSubtune) > this.contextMaxSubtunes)
							// If too big a number was specifed then just set it to the maximum
							newSubtune = this.contextMaxSubtunes;
						$.post("php/symlist_subtune.php", {
							fullname:	this.contextSID,
							symlist:	this.path.substr(1),
							symid:		this.contextSymID,
							subtune:	newSubtune,
						}, function(data) {
							this.validateData(data, function() {
								// Could just store it in the proper place here, but I think users would
								// like to see the folder updating as some sort of confirmation.
								this.getFolder();
							});
						}.bind(this));
					}
					break;
			}
		}.bind(this));
	},

	/**
	 * Move the mouse over a SID row.
	 * 
	 * @param {*} event 
	 */
	onMouseOver: function(event) {
		if (event.target.className.substr(0, 4) == "tag ") {
			// Slide a tag line to the left to show more tags at once
			var $tagsLine = $(event.target).parents(".tags-line");
			var lastLeft = $($tagsLine[0].lastChild).offset().left;
			if (lastLeft > 420) {
				// The edit tags "+" button is hard to get at thus the line is ripe for sliding
				var dataLeft = $tagsLine.data("left");
				if (typeof dataLeft == "undefined" || dataLeft == 0) {
					$tagsLine.data("left", 1) // It is now processed
						.stop(true)
						.animate({
							// left: "-"+($tagsLine[0].lastChild.offsetLeft + 6)+"px",
							left: "-"+(lastLeft - 412)+"px",
						}, 600, "easeOutQuint");
					this.slideTags = true;
				}
			}
		}
		if (event.target.className != "edit-tags") {
			// Show the edit tag "+" button on that SID row only
			$("#songs .edit-tags").hide();
			$(event.target).parents("tr").find("div.edit-tags").css("display", "inline-block");
		}
	},

	/**
	 * Move the mouse away from a SID row.
	 * 
	 * @param {*} event 
	 */
	onMouseLeave: function() {
		// Slide previously moved tag lines back to their default spot
		if (this.slideTags) {
			$("#songs .tags-line").animate({
					left: "0",
				}, 600, "easeOutQuint").data("left", 0);
			this.slideTags = false;
		}
		// Hide all edit tag "+" buttons
		$("#songs .edit-tags").hide();
	},

	/**
	 * Click the left mouse button somewhere below the control buttons.
	 * 
	 * @param {*} event 
	 */
	onClickButton: function(event) {

		this.clearSpinner();

		// Lose focus on the button to avoid hotkeys triggering it again
		event.target.blur();

		switch (event.target.id) {
			case "folder-root":
				if (!$("#folder-root").hasClass("disabled")) {
					// Go to HVSC root folder
					this.path = "";
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					this.getFolder(this.scrollPositions[0], undefined, undefined,
						function() {
							this.kbSelectedRow = [this.kbPositions[0]];
							if (typeof this.kbSelectedRow == "undefined")
								this.moveKeyboardToFirst();
							this.moveKeyboardSelection(this.kbSelectedRow, false);
					}.bind(this));
					this.scrollPositions = [this.scrollPositions[0]];
					this.getComposer();
					// Go to home URL and clear subtune switch
					ctrls.subtuneCurrent = ctrls.subtuneMax = 0;
					history.replaceState({}, document.title, $("#home").attr("href"));
					this.redirectFolder = "";
				}
				break;
			case "folder-back":
				if (!$("#folder-back").hasClass("disabled")) {
					if (this.redirectFolder == "") {
						// Go back one folder in the HVSC tree
						this.path = this.path.substr(0, this.path.lastIndexOf("/"));
					} else {
						// Jump back to origin
						this.path = this.redirectFolder;
						this.redirectFolder = "";
					}
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					if (this.isSearching) {
						this.scrollPositions.pop(); // First pop out of search state
						this.kbPositions.pop();
					}
					this.getFolder(this.scrollPositions.pop(), undefined,
						(this.path === "/CSDb Music Competitions" || this.path === "/_Compute's Gazette SID Collection")
							&& this.cache.folder !== "" /* <- Boolean parameter */ ,
						function() {
							this.kbSelectedRow = this.kbPositions.pop();
							if (typeof this.kbSelectedRow == "undefined")
								this.moveKeyboardToFirst();
							this.moveKeyboardSelection(this.kbSelectedRow, false);
					}.bind(this));
					this.getComposer();
					ctrls.subtuneCurrent = ctrls.subtuneMax = 0; // Clear subtune switch
					UpdateURL(true);
				}
				break;
			case "search-button":
				// Perform a search query
				this.setupSortBox();
				ctrls.state("prev/next", "disabled");
				ctrls.state("subtunes", "disabled");
				ctrls.state("loop", "disabled");

				this.scrollPositions.push($("#folders").scrollTop()); // Remember where we parked
				this.kbPositions.push(this.kbSelectedRow);

				var searchValue = $("#search-box").val();
				switch (searchValue) {
					case "+":
						// Raw "+" goes to root
						this.gotoFolder("");
						break;
					case "+sh":
						this.gotoFolder("/_SID Happens");
						break;
					case "+cgsc":
						this.gotoFolder("/_Compute's Gazette SID Collection");
						break;
					case "+misc":
						this.gotoFolder("/_Compute's Gazette SID Collection/Misc");
						break;
					default:
						// Search the query unless a search command was entered
						cmds.handlePlusCommand.call(this, searchValue).then(handled => {
							if (!handled)
								this.getFolder(0, searchValue.replace(/\s/g, "_"));
						});
				}
				break;
			case "search-cancel":
				// Cancel the search results and return to the previous normal folder view
				ctrls.state("prev/next", "disabled");
				ctrls.state("subtunes", "disabled");

				this.getFolder(this.scrollPositions.pop());
				this.kbSelectedRow = [this.kbPositions[0]];
				this.moveKeyboardSelection(this.kbSelectedRow, false);
				this.getComposer();
				break;
			case "upload-wizard":
				// Clicked the button for uploading a new public SID file
				if (!$("#logout").length) {
					// But must be logged in to do that
					alert("Login or register and you can upload new SID files here.");
					return false;
				}
				this.uploadWizard();
				break;
			default:
				// TR handling has been moved into the 'onClickRow' event handler
		}
	},

	/**
	 * Click the left mouse button on a table row.
	 * 
	 * @param {*} event 
	 * @param {number} paramSubtune		If specified, override subtune number with a URL parameter
	 * @param {boolean} paramSkipCSDb	If specified and TRUE, skip generating the 'CSDb' tab contents
	 * @param {boolean} paramSolitary	If specified and TRUE, just stop the tune when it's done
	 * @param {boolean} paramWait		If specified, mark/play the tune then stop after X milliseconds
	 */
	onClickRow: function(event, paramSubtune, paramSkipCSDb, paramSolitary, paramWait) {

		this.clearSpinner();

		// A TD element was clicked (folder, SID file, star rating)
		var $tr = $(event.currentTarget);
		if ($($tr).hasClass("disabled")) return false;

		// Get the unmodified name of this entry
		// NOTE: Elsewhere, "extra" folders have their prefixed "_" removed for displaying.
		var name = decodeURIComponent($tr.find(".name").attr("data-name"));
		var thisFullname = ((this.isSearching || this.isSymlist || this.isCompoFolder ? "/" : this.path+"/")+name).substr(1);

		if (event.target.className === "edit-tags") {
			// Clicked the "+" icon button to edit tags for a SID file
			if (!$("#logout").length) {
				// But must be logged in to do that
				alert("Login or register and you can edit the tags for this file.");
				return false;
			}

			$.get("php/tags_get.php", {
				fullname: thisFullname
			}, function(data) {
				this.validateData(data, function(data) {
					this.fileID		= data.id;
					this.allTags	= data.all; // Now also includes the type
					this.fileTags	= data.sid;
					this.startTag	= data.start;
					this.endTag		= data.end;
					this.newTagID	= 60000;
					this.updateTagLists(this.allTags, this.fileTags);
					this.updateConnectTagLists(this.allTags, this.fileTags);
					$("#new-tag").val("");
					$("#dialog-list-start-tag").val(this.startTag);
					$("#dialog-list-end-tag").val(this.endTag);
					// Show the dialog box
					CustomDialog({
						id: '#dialog-tags',
						text: '<h3>Edit tags</h3><p>'+name.split("/").slice(-1)[0]+'</p>'+
							'<span class="dialog-label-top" style="float:left;">All tags available:</span>'+
							'<span class="dialog-label-top" style="float:right;width:136px;">Tags for this file:</span>',
						width: 390,
						height: 448,
					}, function() {
						// OK was clicked; make all the tag changes
						$.post("php/tags_write.php", {
							fileID:		browser.fileID,
							allTags:	browser.allTags,
							fileTags:	browser.fileTags,
							startTag:	browser.startTag,
							endTag:		browser.endTag
						}, function(data) {
							browser.validateData(data, function(data) {
								var htmlTags = browser.buildTags(data.tags, data.tagtypes, data.tagids);
								browser.updateStickyTags(
									$(event.target).parents("td"),
									htmlTags,
									(browser.isSymlist || browser.isCompoFolder ? thisFullname : thisFullname.split("/").slice(-1)[0])
								);
								// Make sure sorting also works
								var $filteredRows = $tr.parent().children("tr:has(td.sid)");
								var index = $filteredRows.index($tr);										
								browser.playlist[index].tagstart = browser.startTag;
								browser.playlist[index].tagend = browser.endTag;
								ctrls.updateSundryTags(htmlTags);
							});
						}.bind(this));
					});
					SetScrollTopInstantly("#dialog-all-tags", 0);
					SetScrollTopInstantly("#dialog-song-tags", 0);
					$("#dialog-all-tags").focus();
				});
			}.bind(this));
			return false;
		}

		if (event.target.tagName === "B") {
			this.registerStarRating(event, thisFullname);
			return false;
		}

		// A row was clicked, but was it a folder or a SID file?
		if (name.indexOf(".sid") === -1 && name.indexOf(".mus") === -1) {

			// ENTER FOLDER

			var $target = $(event.target).find(".entry");
			var searchType = $target.attr("data-search-type");
			var redirectFolder = $target.attr("data-redirect-folder");

			ctrls.subtuneCurrent = ctrls.subtuneMax = 0; // Clear subtune switch

			if (typeof searchType != "undefined") {

				// SEARCH SHORTCUT

				$("#dropdown-search").val(searchType);
				$("#search-box").val($target.attr("data-search-query"));

				var $searchButton = $("#search-button");
				$searchButton.removeClass("disabled");
				if ($("#search-box").val() !== "")
					$searchButton.prop("disabled", false);
				else
					$searchButton.prop("enabled", false).addClass("disabled");
				$searchButton.trigger("click"); // Perform the search now

			} else {

				// OTHER FOLDERS

				this.redirectFolder = "";
				if (typeof redirectFolder != "undefined") {
					if (this.isSearching)
						// Origin path is a group parent folder
						this.redirectFolder = "/"+name.substr(0, name.lastIndexOf("/"));
					else
						// Remember origin path
						this.redirectFolder = this.path;
					this.groupMember = name;
					this.path = "/"+redirectFolder 		// Folder to jump to instead
				} else if ($target.hasClass("search"))
					this.path = "/"+name; // Search folders already have the full path
				else
					this.path += "/"+name;

				ctrls.state("prev/next", "disabled");
				ctrls.state("subtunes", "disabled");
				ctrls.state("loop", "disabled");

				this.scrollPositions.push($("#folders").scrollTop()); // Remember where we parked
				this.kbPositions.push($tr.index());

				this.currentScrollPos = 0;
				this.getFolder(0, undefined, undefined, function() {
					this.cache.folderTags = this.showFolderTags();
				});
				this.getComposer();

				UpdateURL();
			}

		} else {

			// LOAD AND PLAY FILE

			// NOTE: Don't add a SID.pause() here, it creates an error for Hermit's on stop then re-click.
			SID.setVolume(0);
			ctrls.setButtonPause();

			this.songPos = $tr.index() - this.subFolders;

			if (!SID.emulatorFlags.offline) {
				$("#play-pause,#stop,#subtune-plus,#subtune-minus,#subtune-value").removeClass("disabled");
				$("#volume").prop("disabled", false);
			}
			if (SID.emulatorFlags.supportFaster) $("#faster").removeClass("disabled");
			ctrls.state("subtunes", "disabled");

			$("#time-bar").empty().append('<div></div>');
			
			this.showSpinner($(event.target).parents("tr").children("td.sid"));

			// Override default sub tune to first if demanded by a setting
			var subtuneStart = GetSettingValue("first-subtune") ? 0 : this.playlist[this.songPos].startsubtune;
			// Either default start subtune, or an override from a "?subtune=" URL parameter
			var subtune = typeof paramSubtune !== "undefined" ? paramSubtune : subtuneStart,
				subtuneMax = this.playlist[this.songPos].subtunes - 1;
			// Make sure the overridden value is within what is available for that SID tune
			subtune = subtune < 0 ? 0 : subtune;
			subtune = subtune > subtuneMax ? subtuneMax : subtune;

			// NOTE: These two lines used to be placed below SID.load(). Placing them up here instead
			// fixed a row marking bug on iOS in playlists with duplicate use of songs.
			$("#songs tr").removeClass("selected");
			$tr.addClass("selected");
			this.kbSelectedRow = $tr.index();
			this.moveKeyboardSelection(this.kbSelectedRow, false, false);

			SID.load(subtune, this.getLength(subtune), this.playlist[this.songPos].fullname, function(error) {

				this.clearSpinner();

				if (error) {

					this.errorRow();

				} else {

					ctrls.subtuneMax = SID.getSongInfo().maxSubsong;
					ctrls.subtuneCurrent = subtune;
					ctrls.updateSubtuneText();
					if (ctrls.subtuneMax > 0 && !SID.emulatorFlags.offline) $("#subtune-value").removeClass("disabled");
					if (subtune < ctrls.subtuneMax && !SID.emulatorFlags.offline) $("#subtune-plus").removeClass("disabled");
					if (subtune > 0 && !SID.emulatorFlags.offline) $("#subtune-minus").removeClass("disabled");
					ctrls.state("prev/next", "enabled");
					ctrls.state("loop", !SID.emulatorFlags.offline && SID.emulatorFlags.supportLoop
						? "enabled"
						: "disabled"
					);

					ctrls.updateInfo();
					ctrls.updateSundry();

					this.sliderButton = true;
					if ($("#sundry-tabs .selected").attr("data-topic") == "tags" && $("#sundry").css("flex-basis").replace("px", "") > 37) {
						$("#slider-button").show();
					}

					if (!paramWait) {
						SID.play(true);
						setTimeout(ctrls.setButtonPlay, 75); // For nice pause-to-play delay animation
					}
				}

				// Disable PREV or NEXT if at list boundaries, or if it's a solitary playing
				if (this.songPos == this.playlist.length - 1 || paramSolitary)
					$("#skip-next").addClass("disabled");
				if (this.songPos == 0 || paramSolitary)
					$("#skip-prev").addClass("disabled");

				ctrls.emulatorChanged = false;

				if (typeof paramSkipCSDb === "undefined" || !paramSkipCSDb) {
					this.getCSDb();
					if (typeof this.playlist[this.songPos].profile != "undefined")
						if (this.playlist[this.songPos].profile != "") {
							this.getComposer(this.playlist[this.songPos].profile, true);
						} else {
							// If composers_id = 0 then do this
							$("#topic-profile").empty().append('<i>No profile available.</i>');
							this.previousOverridePath = "_SID Happens";
						}
					else if (this.isSearching || this.path.substr(0, 2) === "/$" || this.path.substr(0, 2) === "/!")
						this.getComposer(this.playlist[this.songPos].fullname);
				} else
					this.getComposer();
				this.getGB64();
				this.getRemix();
				this.getPlayerInfo({player: this.playlist[this.songPos].player});

				UpdateURL();
				this.chips = 1;
				if (this.playlist[this.songPos].fullname.indexOf("_2SID") != -1) this.chips = 2;
				else if (this.playlist[this.songPos].fullname.indexOf("_3SID") != -1) this.chips = 3;
				ctrls.resetStereoPanning();
				viz.initGraph(this.chips);
				viz.startBufferEndedEffects();

				// Stop the tune after X milliseconds if a "?wait=X" URL parameter is specified
				// NOTE: A bit of a nasty hack. Because of the way the SID.load() function ties into
				// playing immediately, the alternative would have cost a lot more code and effort.
				if (paramWait) {
					SID.setVolume(0);
					setTimeout(function() {
						$("#stop").trigger("mouseup");
						SID.stop();
						SID.setVolume(1);
					}, paramWait);
				}

			}.bind(this));

			SID.setCallbackTrackEnd(function() {
				if ($("#loop").hasClass("button-off")) {
					// Play the next subtune, or if no more subtunes, the next tune in the list
					$("#faster").trigger("mouseup"); // Easy there cowboy
					if (!paramSolitary && !GetSettingValue("skip-tune") && (ctrls.subtuneCurrent < ctrls.subtuneMax && !$("#subtune-plus").hasClass("disabled")))
						// Next subtune
						$("#subtune-plus").trigger("mouseup", false);
					else if (this.songPos < (this.playlist.length - 1) && !$("#skip-next").hasClass("disabled"))
						// Next song
						$("#skip-next").trigger("mouseup", false);
					else
						// At the end of everything
						$("#stop").trigger("mouseup").trigger("click");
				}
			}.bind(this));
		}
	},

	/**
	 * Click a button in a dialog box.
	 * 
	 * NOTE: For now only bound to the dialog box for editing tags.
	 * 
	 * @param {*} event 
	 */
	onClickDialogBox: function(event) {
		switch (event.target.id) {
			case "dialog-tags-right":
				// Edit tags: Transfer items from left to right list
				$("#dialog-all-tags option").each(function() {
					if (this.selected) // Add ID
						browser.fileTags.push(parseInt(this.value));		
				});
				this.updateTagLists(this.allTags, this.fileTags);
				this.updateConnectTagLists(this.allTags, this.fileTags);
				break;
			case "dialog-tags-left":
				// Edit tags: Transfer items from right to left list
				$("#dialog-song-tags option").each(function() {
					if (this.selected) // Remove ID
						var index = browser.fileTags.indexOf(parseInt(this.value));
						if (index > -1) browser.fileTags.splice(index, 1);
				});
				this.updateTagLists(this.allTags, this.fileTags);
				this.updateConnectTagLists(this.allTags, this.fileTags);
				break;
			case "dialog-tags-music":
				// Edit tags: Transfer "Music" tag
				this.toggleTag("Music");
			    break;
			case "dialog-tags-collection":
				// Edit tags: Transfer "Collection" tag
				this.toggleTag("Collection");
				break;
			case "dialog-tags-magic-wand":
				// Edit tags: Select event and production tag as bracket start and end
				const $startTag = $("#dialog-list-start-tag");
				const $endTag = $("#dialog-list-end-tag");

				// --- 1. Select EVENT type for the top drop-down ---
				const eventTag = this.fileTags
					.map(tagID => this.allTags.find(tag => tag.id == tagID))
					.filter(tag => tag && tag.type === "EVENT")	// Only EVENT tags
					.filter(tag => !["Winner", "Solitary", "Compo", "<-", "->"].includes(tag.name))
					.filter(tag => !tag.name.startsWith("#"))	// Skip tags starting with '#'
					.shift(); // Take the first matching event

				const startTagValue = eventTag ? eventTag.id : 0;
				$startTag.val(startTagValue);
				this.startTag = startTagValue;

				// --- 2. Select PRODUCTION type for the bottom drop-down ---
				const productionTags = this.fileTags
					.map(tagID => this.allTags.find(tag => tag.id == tagID))
					.filter(tag => tag && tag.type === "PRODUCTION"); // Only PRODUCTION tags

				// Apply priority: Music > Demo > first production tag
				let productionId = 0;
				if (productionTags.length) {
					let music = productionTags.find(tag => tag.name === "Music");
					let demo = productionTags.find(tag => tag.name === "Demo");

					if (music) productionId = music.id;
					else if (demo) productionId = demo.id;
					else productionId = productionTags[0].id;
				}

				$endTag.val(productionId);
				this.endTag = productionId;
				break;
			case "dialog-tags-plus":
				// Edit tags: Add a new tag in the right list
				var newTag = $("#new-tag").val();
				if (newTag == "")
					$("#dialog-tags .dialog-button-yes").trigger("click");	// Click 'OK' button

				// I apologize in advance for the following words but I have to test for them! =)
				if (["sid", "c64", "fuck", "crap", "shit", "cunt", "piss", "dick", "rubbish", "arse", "chiptune"].indexOf(newTag.toLowerCase()) != -1) {
					alert("Sorry, that tag name is not allowed.\n\nLook, if you really want to I'm sure you can find a way to circumvent this check, but please be nice.\n\nAlso, if I see in my log that you have added a bad tag name, I will most likely undo your work.");
					return false;
				}

				var isDuplicate = false;
				$.each(this.allTags, function(i, tag) {
					if (newTag.toLowerCase() == tag.name.toLowerCase()) {
						alert("That tag already exists.");
						isDuplicate = true;
					}
				});
				if (isDuplicate) return false;

				if (!$(event.target).hasClass("disabled")) {
					// Add "fake" ID for now
					this.allTags.push({
						id:		this.newTagID,
						name:	newTag
					});
					browser.fileTags.push(this.newTagID);
					this.newTagID++;
					this.updateTagLists(this.allTags, this.fileTags);
					$("#new-tag").val("");
				}
				return false;
		}
	},

	/**
	 * Mouse up in a dialog box. This is required in order to detect a middle mouse
	 * button click.
	 * 
	 * NOTE: For now only bound to the dialog box for editing tags.
	 * 
	 * @param {*} event 
	 */
	onMouseUpDialogBox: function(event) {
		switch (event.target.id) {
			case "dialog-tags-music":
				if (event.which === 2) {
					this.toggleTag("Music");
					$("#dialog-tags .dialog-button-yes").trigger("click");
				}
			    break;
			case "dialog-tags-collection":
				if (event.which === 2) {
					this.toggleTag("Collection");
					$("#dialog-tags .dialog-button-yes").trigger("click");
				}
			    break;
			case "dialog-tags-magic-wand":
				if (event.which === 2) {
					$("#dialog-tags-magic-wand").trigger("click");
					$("#dialog-tags .dialog-button-yes").trigger("click");
				}
			    break;
		}
	},

	/**
	 * Toggle a tag (i.e. move it between the left/right lists in the dialog box
	 * for editing tags).
	 *
	 * @param {string} tagName - The name of the tag to toggle (e.g. "Music")
	 */
	toggleTag: function(tagName) {
		// Find the tag ID in allTags
		var tagEntry = $.grep(this.allTags, function(entry) {
			return entry.name === tagName;
		})[0];

		if (!tagEntry) return; // Fail-safe if not found

		var tagId = parseInt(tagEntry.id);
		var index = this.fileTags.indexOf(tagId);

		if (index > -1) {
			// Tag is in right list, remove it
			this.fileTags.splice(index, 1);
		} else {
			// Tag is in left list, add it
			this.fileTags.push(tagId);
		}

		// Refresh both lists
		this.updateTagLists(this.allTags, this.fileTags);
		this.updateConnectTagLists(this.allTags, this.fileTags);
	},

	/**
	 * When selecting an option in the 'Sort by' drop-down box (CSDb tab).
	 * 
	 * @param {*} event 
	 */
	onChangeCSDb: function(event) {

		var isEmpOn = $("#csdb-emp-filter").hasClass("button-on");

		$("#topic-csdb table.releases").empty();
		sortedList = "";
		switch (event.target.value) {
			case "title":
				// Sort playlist according to release title
				this.sidEntries.sort(function(obj1, obj2) {
					return obj1.title > obj2.title ? 1 : -1;	// A to Z (already lower case)
				});
				break;
			case "type":
				// Sort playlist according to release type
				this.sidEntries.sort(function(obj1, obj2) {
					return obj1.type > obj2.type ? 1 : -1;		// A to Z (already lower case)
				});
				break;
			case "high-id":
				// Sort CSDb entries according to the ID
				this.sidEntries.sort(function(obj1, obj2) {
					return obj2.id - obj1.id;					// Highest ID in top
				});
				break;
			case "low-id":
				this.sidEntries.sort(function(obj1, obj2) {
					return obj1.id - obj2.id;					// Lowest ID in top
				});
				break;
			case "newest":
				// Sort CSDb entries according to the date string (YYYY-MM-DD)
				this.sidEntries.sort(function(obj1, obj2) {
					return obj1.date < obj2.date ? 1 : -1;		// Newest year in top
				});
				break;
			case "oldest":
				this.sidEntries.sort(function(obj1, obj2) {
					return obj1.date > obj2.date ? 1 : -1;		// Oldest year in top
				});
				break;
		}
		$.each(this.sidEntries, function(i, entry) {
			sortedList += entry.html;
		});
		$("#topic-csdb table.releases").append(sortedList);
		this.additionalEmphasizing(isEmpOn);
	},

	/**
	 * When selecting an option in the SORT/FILTER drop-down box (browser).
	 * 
	 * See 'setupSortBox' regarding setting up its contents.
	 * 
	 * @param {*} event 
	 */
	onChange: function(event) {
		// Another sorting method chosen in the top right drop-down box
		var isTempFolder = this.isTempFolder();
		$("#songs table").empty();
		ctrls.state("prev/next", "disabled");
		ctrls.state("subtunes", "disabled");
		var filterFolders = false;
		switch (event.target.value) {
			case "all":
				// MUSICIANS and ROOT: Show all folders in the letter folder
				filterFolders = true;
				if (this.path === "") {
					this.getFolder();
					localStorage.setItem("personal", "all");
				} else {
					$("#songs table").append(this.folders);
					localStorage.setItem("letter", "all");
				}
				break;
			case "common":
				// ROOT: Show collections, varied "official" playlists, and own public/private playlists
				filterFolders = true;
				this.getFolder();
				localStorage.setItem("personal", "common");
				break;
			case "personal":
				// ROOT: Show only collections and own public/private playlists
				filterFolders = true;
				this.getFolder();
				localStorage.setItem("personal", "personal");
				break;
			case "decent":
			case "good":
				// MUSICIANS: Show only decent or good folders (assessed by the 'Ratings' user) in the letter folder
				filterFolders = true;
				$.get("php/rating_quality.php", { folder: this.path }, function(data) {
					this.validateData(data, function(data) {
						// Is the folder ready (i.e. all folders have ratings)?
						if (data.ready) {
							var stars = event.target.value == "decent" ? 1 : 2;
							$(this.folders+" tr").each(function(i, element) {
								$this = $(element);
								// Rating must be more than one star for "decent" or two stars for "good"
								if (data.results[$this.find(".name").text()] > stars)
									filterFolders += '<tr>'+$this.html()+'</tr>';
							}.bind(this));
							filterFolders = TR_SPACER+filterFolders+TR_DIVIDER;
							$("#songs table").append(filterFolders);
							localStorage.setItem("letter", event.target.value);
						} else {
							alert("The filter option for this folder has not been updated yet.");
							$("#dropdown-sort").val("all").trigger("change");
						}
					});
				}.bind(this));
				break;
			case "name":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the folder name
					this.compolist.sort(function(obj1, obj2) {
						return obj1.foldername.toLowerCase() > obj2.foldername.toLowerCase() ? 1 : -1;
					});
					this.cache.composort = "name";
				} else {
					// Sort playlist according to the SID filename
					this.playlist.sort(function(obj1, obj2) {
						var o1 = obj1.substname !== "" ? obj1.substname : this.adaptBrowserName(obj1.filename, true);
						var o2 = obj2.substname !== "" ? obj2.substname : this.adaptBrowserName(obj2.filename, true);
						return o1.toLowerCase() > o2.toLowerCase() ? 1 : -1;
					}.bind(this));
					if (!this.isUploadFolder())
						localStorage.setItem("sort", "name");
				}
				break;
			case "player":
				// Sort playlist according to music player
				// NOTE: This is not available in 'SID Happens' because players are not visible in the rows.
				this.playlist.sort(function(obj1, obj2) {
					return obj1.player.toLowerCase() > obj2.player.toLowerCase() ? 1 : -1;
				});
				if (!this.isUploadFolder())
					localStorage.setItem("sort", "player");
				break;
			case "rating":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to rating
					this.compolist.sort(function(obj1, obj2) {
						return obj2.rating - obj1.rating;
					});
					this.cache.composort = "rating";
				} else {
					// Sort playlist according to rating
					this.playlist.sort(function(obj1, obj2) {
						return obj2.rating - obj1.rating;
					});
					if (!this.isUploadFolder())
						localStorage.setItem("sort", "rating");
				}
				break;
			case "oldest":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the year
					this.compolist.sort(function(obj1, obj2) {
						return obj1.compo_year > obj2.compo_year ? 1 : -1; // Oldest year in top
					});
					this.cache.composort = "oldest";
				} else if (this.isUploadFolder()) {
					// Sort 'SID Happens' folder according to upload date/time
					this.playlist.sort(function(obj1, obj2) {
						return obj1.uploaded > obj2.uploaded ? 1 : -1;
					});
				} else {
					// Sort playlist according to the 'copyright' string (the year in start is used)
					this.playlist.sort(function(obj1, obj2) {
						return obj1.copyright > obj2.copyright ? 1 : -1;
					});
					localStorage.setItem("sort", "oldest");
				}
				break;
			case "newest":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the year
					this.compolist.sort(function(obj1, obj2) {
						return obj1.compo_year < obj2.compo_year ? 1 : -1; // Newest year in top
					});
					this.cache.composort = "newest";
				} else if (this.isUploadFolder()) {
					// Sort 'SID Happens' folder according to upload date/time
					this.playlist.sort(function(obj1, obj2) {
						return obj1.uploaded < obj2.uploaded ? 1 : -1;
					});
				} else {
					// Sort playlist according to the 'copyright' string (the year in start is used)
					this.playlist.sort(function(obj1, obj2) {
						return obj1.copyright < obj2.copyright ? 1 : -1;
					});
					localStorage.setItem("sort", "newest");
				}
				break;
			case "factoid":
				// Sort according to factoid (doesn't make sense with all factoid types)
				this.playlist.sort(function(obj1, obj2) {
					return obj1.fvalue < obj2.fvalue ? 1 : -1;
				});
				localStorage.setItem("sort", "factoid");
				break;
			case "shuffle":
				// Sort playlist in a random manner (randomize)
				// NOTE: Previous "Math.random() >= 0.5" method didn't work in Chrome; this fix by JW.
				for (var i = 0; i < this.playlist.length; i++) {
					this.playlist[i].shuffle = Math.random();
				}
				this.playlist.sort(function(obj1, obj2) {
					return obj1.shuffle > obj2.shuffle ? 1 : -1;
				});
				if (!this.isUploadFolder())
					localStorage.setItem("sort", "shuffle");
				break;
			case "type":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the competition type
					this.compolist.sort(function(obj1, obj2) {
						return obj1.compo_type.toLowerCase() > obj2.compo_type.toLowerCase() ? 1 : -1;
					});
					this.cache.composort = "type";
				}
				break;
			case "country":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the country
					this.compolist.sort(function(obj1, obj2) {
						return obj1.compo_country.toLowerCase() > obj2.compo_country.toLowerCase() ? 1 : -1;
					});
					this.cache.composort = "country";
				}
				break;
			case "amount":
				if (this.isBigCompoFolder()) {
					// Sort compo list according to the amount of entries in each folder
					this.compolist.sort(function(obj1, obj2) {
						return parseInt(obj1.filescount) < parseInt(obj2.filescount) ? 1 : -1;
					});
					this.cache.composort = "amount";
				}
			}

		if (isTempFolder) {

			var files = "";
			$.each(this.playlist, function(i, file) {
				var year = isNaN(file.copyright.substr(0, 4)) ? "unknown year" : file.copyright.substr(0, 4);
				files += '<tr>'+
						'<td class="sid temp unselectable"><div class="block-wrap"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes">'+file.subtunes+'</div>' : '')+
						'<div class="entry name file" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'">'+browser.adaptBrowserName(file.filename.replace(/^\_/, ''))+'</div></div></div><br />'+
						'<span class="info">'+year+' in file format '+file.type+' v'+file.version+'</span></td>'+
						'<td></td>'+
					'</tr>';
			}.bind(this));
			$("#songs table").append(files);
			DisableIncompatibleRows();

		} else if (!filterFolders && !this.isBigCompoFolder()) {

			// SORT/FILTER: Rebuild the reordered table list (files only; the folders in top are just preserved)
			var files = adaptedName = "";
			$.each(this.playlist, function(i, file) {
				var isNew = file.hvsc == this.HVSC_VERSION || file.hvsc == this.CGSC_VERSION ||
					(typeof file.uploaded != "undefined" && file.uploaded.substr(0, 10) == this.today.substr(0, 10));
				adaptedName = file.substname == "" ? file.filename.replace(/^\_/, '') : file.substname;
				adaptedName = this.adaptBrowserName(adaptedName);
				var tag_start_end = file.tagend ? ' data-tag-start-id="'+file.tagstart+'" data-tag-end-id="'+file.tagend+'"': '';
				var playerType = "",
					hasStil = file.stil != "" ? "<div></div><div></div><div></div>" : "";
				$.each(playerStrips, function(i, strip) {
					if (file.player.indexOf(strip.type) != -1) {
						playerType = " "+strip.class;
						return false;
					}
				});
				files += '<tr>'+ // SORT/FILTER SID ROW
						'<td class="sid unselectable">'+file.sidspecial+
						'<div class="pl-strip'+playerType+'"><div class="has-stil">'+hasStil+'</div></div>'+
						'<div class="block-wrap'+(file.sidspecial !== "" ? ' bw-sidsp' : '')+'"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes'+(this.isSymlist ? ' specific' : '')+(isNew ? ' newst' : '')+'">'+(this.isSymlist ? file.startsubtune + 1 : file.subtunes)+'</div>' : (isNew ? '<div class="newsid"></div>' : ''))+
						'<div class="entry name file'+(this.isSearching || this.isCompoFolder || this.path.substr(0, 2) === "/$" ? ' search' : '')+'" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'" data-id="'+file.id+'" data-symid="'+file.symid+'">'+adaptedName+'</div></div></div><br />'+
						'<span class="info">'+file.copyright.substr(0, 4)+file.infosec+'<div class="tags-line"'+(showTags ? '' : ' style="visibility:hidden;"')+tag_start_end+'>'+TAGS_BRACKET+file.tags+'</div></span></td>'+
						'<td class="stars filestars"><span class="rating">'+this.buildStars(file.rating)+'</span>'+
						(typeof file.uploaded != "undefined" ? '<span class="uploaded-time">'+file.uploaded.substr(0, 10)+'</span>' : '<div class="fdiv"><div class="fbar" style="width:'+file.fbarwidth+'px"></div><span class="factoid">'+file.factoid+'</span></div>')+
						'</td>'+
					'</tr>';					
			}.bind(this));
			$("#songs table").append(this.folders+files);
			this.showTagsBrackets();
			DisableIncompatibleRows();

		} else if (this.isBigCompoFolder()) {

			// SORT/FILTER: Rebuild the big CSDb music competitions folder
			var folders = "";
			$.each(this.compolist, function(i, folder) {
				var isMobileDenied = folder.incompatible.indexOf("mobile") !== -1 && isMobile;
				folders += // SORT/FILTER: COMPETITIONS
					'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
						'<td class="folder compo unselectable"><div class="block-wrap"><div class="block slimfont">'+
							(folder.filescount > 0 ? '<div class="filescount">'+folder.filescount+'</div>' : '')+
						'<span class="name entry compo'+(this.isSearching ? ' search' : '')+'" data-name="'+(this.isSearching ? 'CSDb Music Competitions%2F' : '')+encodeURIComponent(folder.foldername)+'" data-incompat="'+folder.incompatible+'">'+
						folder.foldername+'</span></div></div><br />'+
						'<span class="info compo-year compo-'+folder.compo_type.toLowerCase()+'">'+folder.compo_year+(folder.compo_country.substr(0, 1) == "_" ? ' at ' : ' in ')+folder.compo_country.replace("_", "")+'</span></td>'+
						'</td>'+
						'<td class="stars"><span class="rating">'+this.buildStars(folder.rating)+'</span><br /></td>'+
					'</tr>';
			}.bind(this));
			folders = TR_SPACER+folders+TR_DIVIDER;
			$("#songs table").append(folders);
			this.cache.folder = folders;
			this.cache.compolist = this.compolist;
		}

		$("#folders").scrollTop(0);
	},

	/**
	 * Get the folders and files in 'this.path' and show them in the browser panel.
	 * 
	 * @param {number} scrollPos	If specified, jump to position in list (otherwise just stay in top)
	 * @param {string} searchQuery	If specified, search results will be shown instead
	 * @param {boolean} readCache	If specified, TRUE will load from a cache instead
	 * @param {function} callback 	If specified, the function to call after showing the contents
	 */
	getFolder: function(scrollPos, searchQuery, readCache, callback) {

		if (this.hvsc) this.hvsc.abort();

		$("#kb-marker").hide();

		ctrls.state("root/back", "disabled");
		$("#dropdown-sort").prop("disabled", true);
		$("#search-here").prop("disabled", false);
		$("#search-here-container label").removeClass("disabled");
		$("#songs table").empty();
		this.isSearching = typeof searchQuery !== "undefined";
		this.isSymlist = this.path.substr(0, 2) === "/!" || this.path.substr(0, 2) === "/$";

		if (typeof readCache !== "undefined" && readCache) {

			// LOAD FROM CACHE

			ctrls.state("root/back", "enabled");

			// Disable emulators/handlers in the drop-down according to parent folder attributes
			$("#dropdown-topleft-emulator,#dropdown-settings-emulator").styledOptionState("resid jsidplay2 websid legacy hermit webusb asid lemon youtube download silence", "enabled");
			$("#page .viz-emu").removeClass("disabled");
			$("#dropdown-topleft-emulator,#dropdown-settings-emulator").styledOptionState(this.cache.incompatible, "disabled");
			if (this.cache.incompatible.indexOf("resid") !== -1) $("#page .viz-resid").addClass("disabled");
			if (this.cache.incompatible.indexOf("jsidplay2") !== -1) $("#page .viz-jsidplay2").addClass("disabled");
			if (this.cache.incompatible.indexOf("websid") !== -1) $("#page .viz-websid").addClass("disabled");
			if (this.cache.incompatible.indexOf("hermit") !== -1) $("#page .viz-hermit").addClass("disabled");
			if (this.cache.incompatible.indexOf("webusb") !== -1) $("#page .viz-hermit").addClass("disabled");
			if (this.cache.incompatible.indexOf("asid") !== -1) $("#page .viz-asid").addClass("disabled");

			$("#path").css("top", "5px").empty().append(
				this.path
					.replace(/^\/_/, '/')
					.replace("/Compute's Gazette SID Collection", '<span class="dim">CGSC</span>')
					.replace("/High Voltage SID Collection", '<span class="dim">HVSC</span>')
					.replace("/Exotic SID Tunes Collection", '<span class="dim">ESTC</span>')
					.replace("/CSDb Music Competitions/", '')
			);

			this.setupSortBox();

			setTimeout(function() {
				// Must be in this timer or the emptying above will not be visible
				$("#songs table").append(this.cache.folder);
				this.compolist = this.cache.compolist;

				this.moveKeyboardToFirst();

				// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
				$(window).trigger("resize");
				SetScrollTopInstantly("#folders", scrollPos);
				DisableIncompatibleRows();
				if (this.isBigCompoFolder()) $("#dropdown-sort").prop("disabled", false);

				if (typeof callback === "function") callback.call(this);
			}.bind(this), 1);

		} else {

			// LOAD FROM HVSC.PHP

			var loading = setTimeout(function() {
				// Fade in a GIF loading spinner if the AJAX call takes longer than usual
				$("#loading").css("top", $("#songs").height() / 2 - 50 /* Half size of SVG */).fadeIn(350);
			}, 150);

			// Remember the search settings in case the folder is refreshed later
			this.searchType = $("#dropdown-search").val();
			this.searchQuery = this.isSearching ? searchQuery : "";
			this.searchHere = $("#search-here").is(":checked") ? 1 : 0;

			this.playlist = [];		// Every folder we enter will become its own local playlist
			this.compolist = [];	// For the big CSDb music competitions folder list
			this.subFolders = 0;
			this.path = this.path.replace("/_CSDb", "/CSDb");
			$("#path").empty();

			// Call the AJAX PHP script that delivers the list of files and folders
			this.hvsc = $.get("php/hvsc.php", {
					folder:			this.path,
					searchType:		this.searchType,
					searchQuery:	this.searchQuery,
					searchHere:		this.searchHere,
					factoid:		main.factoidType,
			}, function(data) {
				this.validateData(data, function(data) {

					if (data.debug !== "") console.log(data.debug);

					clearTimeout(loading);
					$("#loading").hide();
					ctrls.state("root/back", "enabled");
					this.folders = this.extra = this.symlists = this.searchShortcutNew = this.searchShortcutOther = "";
					var files = "";

					// Disable emulators/handlers in the drop-down according to parent folder attributes
					$("#dropdown-topleft-emulator,#dropdown-settings-emulator").styledOptionState("resid jsidplay2 websid legacy hermit webusb asid lemon youtube download silence", "enabled");
					$("#page .viz-emu").removeClass("disabled");
					$("#dropdown-topleft-emulator,#dropdown-settings-emulator").styledOptionState(data.incompatible, "disabled");
					if (data.incompatible.indexOf("resid") !== -1) $("#page .viz-resid").addClass("disabled");
					if (data.incompatible.indexOf("jsidplay2") !== -1) $("#page .viz-jsidplay2").addClass("disabled");
					if (data.incompatible.indexOf("websid") !== -1) $("#page .viz-websid").addClass("disabled");
					if (data.incompatible.indexOf("hermit") !== -1) $("#page .viz-hermit").addClass("disabled");
					if (data.incompatible.indexOf("webusb") !== -1) $("#page .viz-hermit").addClass("disabled");
					if (data.incompatible.indexOf("asid") !== -1) $("#page .viz-asid").addClass("disabled");

					$("#path").css("top", "5px");
					var pathAppend = "", pathText = this.path == "" ? "/" : this.path
						.replace(/^\/_/, '/')
						.replace("/Compute's Gazette SID Collection", '<span class="dim">CGSC</span>')
						.replace("/High Voltage SID Collection", '<span class="dim">HVSC</span>')
						.replace("/Exotic SID Tunes Collection", '<span class="dim">ESTC</span>');
					if (this.isSearching) {
						var searchType = $("#dropdown-search").val(),
							searchHere = $("#search-here").is(":checked") ? "file="+this.path+"&here=1&" : "",
							searchQuery = encodeURIComponent($("#search-box").val()); // Need it to be untampered here
						searchQuery = searchQuery.replace(/%20/g, "+");
						pathText = data.results+' results found'+
							'<a href="//deepsid.chordian.net?'+searchHere+'search='+searchQuery+(searchType !== '#all#' ? '&type='+searchType : '')+'" title="Permalink"><svg class="permalink" style="enable-background:new 0 0 80 80;" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg></a>'+
							'<button id="search-cancel" class="medium">Cancel</button>';
					} else if (this.isSymlist) {
						$("#path").css("top", "0.5px");
						pathText = '<span class="playlist">'+this.path.substr(2)+'</span><br />'+
							(this.path.substr(0, 2) === "/!"
								? '<span class="maintainer">Personal playlist</span>'
								: '<span class="maintainer">Playlist by '+data.owner+'</span>');
					} else if (data.compo) {
						$("#path").css("top", "0.5px");
						pathText = '<span class="playlist">'+this.path.replace("/CSDb Music Competitions/", '')+'</span><br />'+
							'<span class="maintainer">'+data.owner+'</span>'; // Competition type
					} else if (this.path == "/"+PATH_UPLOADS || this.path == "/"+PATH_UPLOADS+"/SID+FM") {
						pathText = '<button id="upload-wizard">Upload New SID File</button>';
					} else if (this.redirectFolder != "") {
						pathText = this.groupMember.indexOf("/") === false
							? this.groupMember
							: this.groupMember.split("/").slice(-1)[0];
					}

					// Show number of folders and files for certain folder types
					if (/hvsc|cgsc|estc|sid\ happens/i.test(pathText) && (data.folders.length || data.files.length)) {
						$("#path").css("top", "-2.5px");
						pathAppend = '<br /><span class="counts">'+
							(data.folders.length ? data.folders.length+' folder'+(data.folders.length == 1 ? '' : 's') : '')+
							(data.folders.length && data.files.length ? ' and ' : '')+
							(data.files.length ? data.files.length+' file'+(data.files.length == 1 ? '' : 's') : '')+
							'</span>';
					}

					$("#path").append(pathText+pathAppend);

					$("#tabs .tab").removeClass("disabled");
					var $selected = $("#tabs .selected");
					if (this.isCGSC()) {
						// The 'CSDb', 'GB64' and 'Remix' tabs are useless to CGSC
						$("#tab-csdb,#tab-gb64,#tab-remix").addClass("disabled");
						$("#note-csdb,#note-gb64,#note-remix").hide();
						if ($selected.attr("data-topic") === "csdb" || $selected.attr("data-topic") === "gb64" || $selected.attr("data-topic") === "remix")
							$("#tab-profile").trigger("click");
					} else if (this.isUploadFolder()) {
						// The 'GB64' and 'Remix' tabs are useless to 'SID Happens'
						$("#tab-gb64,#tab-remix").addClass("disabled");
						$("#note-gb64,#note-remix").hide();
						if ($selected.attr("data-topic") === "gb64" || $selected.attr("data-topic") === "remix")
							$("#tab-profile").trigger("click");
						this.previousOverridePath = "_SID Happens";
					}

					if (["lemon", "youtube", "download", "silence"].includes(SID.emulator)) {
						// The 'Visuals' tab is useless to these SID handlers
						$("#tab-visuals").addClass("disabled");
						if ($selected.attr("data-topic") === "visuals")
							$("#tab-profile").trigger("click");
					}

					// FOLDERS

					// Sort the list of folders first
					data.folders.sort(function(obj1, obj2) {
						o1 = obj1.prefix != null && obj1.prefix != "" ? obj1.prefix : obj1.foldername;
						o2 = obj2.prefix != null && obj2.prefix != "" ? obj2.prefix : obj2.foldername;
						return o1.replace(/^(\_|\!|\$)/, '').toLowerCase() > o2.replace(/^(\_|\!|\$)/, '').toLowerCase() ? 1 : -1;
					});

					var filter = this.setupSortBox();
					var collections = [], csdbCompoEntry = exoticCollection = publicUploadFolder = "",
						onlyShowPersonal = this.path === "" && filter === "personal",
						onlyShowCommon = this.path === "" && filter === "common";

					$.each(data.folders, function(i, folder) {

						var isMobileDenied = folder.incompatible.indexOf("mobile") !== -1 && isMobile;

						if (folder.foldertype == "COMPO") {

							// COMPETITION FOLDERS

							/*if (this.cache.compolist.length)
								// The cache has the correct order if sorted recently
								folder = this.cache.compolist[i];*/

							this.compolist.push({
								incompatible:	folder.incompatible,
								filescount:		folder.filescount,
								foldername:		folder.foldername,
								compo_type:		folder.compo_type,
								compo_year:		folder.compo_year,
								compo_country:	folder.compo_country,
								rating:			folder.rating,
							});

							var folderEntry = // GET FOLDER: COMPETITIONS
								'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
									'<td class="folder compo unselectable"><div class="block-wrap"><div class="block slimfont">'+
										(folder.filescount > 0 ? '<div class="filescount">'+folder.filescount+'</div>' : '')+
									'<span class="name entry compo'+(this.isSearching ? ' search' : '')+'" data-name="'+(this.isSearching ? 'CSDb Music Competitions%2F' : '')+encodeURIComponent(folder.foldername)+'" data-incompat="'+folder.incompatible+'">'+
									folder.foldername+'</span></div></div><br />'+
									'<span class="info compo-year compo-'+folder.compo_type.toLowerCase()+'">'+folder.compo_year+(folder.compo_country.substr(0, 1) == "_" ? ' at ' : ' in ')+folder.compo_country.replace("_", "")+'</span></td>'+
									'</td>'+
									'<td class="stars"><span class="rating">'+this.buildStars(folder.rating)+'</span><br /></td>'+
								'</tr>';
							this.folders += folderEntry;

						} else {

							// OTHER KINDS OF FOLDERS

							var isPersonalSymlist = folder.foldername.substr(0, 1) == "!",
								isPublicSymlist = folder.foldername.substr(0, 1) == "$",
								isSearchShortcut = folder.ss_type != "",
								isRedirectFolder = folder.rf_path != "",
								myPublic = false;
							if (isPublicSymlist) {
								var result = $.grep(this.symlistFolders, function(entry) {
									return entry.fullname == folder.foldername;
								}.bind(this));
								if (result.length) myPublic = true;
							}
							var adaptedName = folder.foldername.replace(/^(\_|\!|\$|\^...)/, '');
							adaptedName = this.adaptBrowserName(adaptedName);
							var search_shortcut_or_redirect_folder = isSearchShortcut
								? ' data-search-type="'+folder.ss_type+'" data-search-query="'+folder.ss_query+'"'
								: (isRedirectFolder
									? ' data-redirect-folder="'+folder.rf_path+'"'
									: '');
							// Assume one of the standard folder type icons to begin with
							var folderIcon = folder.foldertype.toLowerCase()+(folder.hasphoto ? '-photo' : '');
							if (isPersonalSymlist || (isPublicSymlist && myPublic))
								folderIcon = 'playlist';
							else if (isSearchShortcut)
								folderIcon = 'search-shortcut';

							// Show focus icons (composer folders in a MUSICIANS letter folder only)
							var folderFocus = focusLeft = focusRight = "";
							if (folder.focus1 !== 'N/A' && folder.foldername.toLowerCase() !== "worktunes" && folder.foldername.toLowerCase() !== "unreleased") {
								switch (folder.focus1) {
									case "PRO":
										focusLeft = '<div class="p"></div>';
										break;
									case "BOTB":
										focusLeft = '<div class="b"></div>';
										break;
									default:
										focusLeft = '<div class="none"></div>';
								}
								switch (folder.focus2) {
									case "SCENER":
										focusRight = '<div class="s"></div>';
										break;
									case "CNET":
										focusRight = '<div class="c"></div>';
										break;
									default:
										focusRight = '<div class="none"></div>';
								}
								folderFocus = '<div class="folder-focus">'+focusLeft+focusRight+'</div>'
							}

							var folderEntry = // GET FOLDER: GENERAL FOLDERS
								'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
									'<td class="folder unselectable '+folderIcon+
										(folder.hvsc == this.HVSC_VERSION || folder.hvsc == this.CGSC_VERSION ? ' new' : '')+
										'">'+folderFocus+'<div class="block-wrap"><div class="block'+(isRedirectFolder ? " slimfont" : "")+'">'+
									(folder.foldername == "SID+FM" ? '<div class="sid_fm">Use Hermit\'s (+FM) emulator</div>' : '')+
									(folder.filescount > 0 ? '<div class="filescount">'+folder.filescount+'</div>' : '')+
									(folder.foldername == "_SID Happens" ? '<div class="new-uploads'+(data.uploads.substr(0, 6) == "NO NEW" ? ' no-new' : '')+'">'+data.uploads+'</div>' : '')+
									'<span class="name entry'+(this.isSearching ? ' search' : '')+'" data-name="'+encodeURIComponent(folder.foldername)+'" data-incompat="'+folder.incompatible+'"'+search_shortcut_or_redirect_folder+'>'+
									adaptedName+'</span></div></div></td>'+
									'<td class="stars"><span class="rating">'+this.buildStars(folder.rating)+'</span></td>'+
								'</tr>';
							if (folder.foldername == "_High Voltage SID Collection" || 			// HVSC or CGSC
									folder.foldername == "_Compute's Gazette SID Collection")
								collections.push(folderEntry); // Need to swap the below
							else if (folder.foldername == 'CSDb Music Competitions')
								csdbCompoEntry = folderEntry;
							else if (folder.foldername == '_Exotic SID Tunes Collection')
								exoticCollection = folderEntry;
							else if (folder.foldername == PATH_UPLOADS)
								publicUploadFolder = folderEntry+TR_DIVIDER+TR_SPACER;
							else if ((folder.foldername.substr(0, 1) == "_" || isPublicSymlist) &&
								(!onlyShowPersonal || (onlyShowPersonal && myPublic)) &&
								(!onlyShowCommon || (onlyShowCommon && folder.flags & 0x1)))	// Public symlist or custom?
								this.extra += folderEntry;
							else if (isPersonalSymlist)											// Personal symlist folder?
								this.symlists += folderEntry;
							else if (isSearchShortcut) {										// Search shortcut folder?
								if (folder.foldername.substr(0, 3) == "^00" || folder.foldername.substr(0, 3) == "^01")
									// The "new in HVSC update" stuff
									this.searchShortcutNew += folderEntry;
								else
									// Other types of search shortcuts
									this.searchShortcutOther += folderEntry;
							} else
								this.folders += folderEntry;									// Normal folder
						}
						this.subFolders++;

					}.bind(this));

					if (this.subFolders) {
						if (this.extra !== "") {
							this.extra = TR_SPACER+this.extra+TR_DIVIDER;
							this.subFolders += 2;
						}
						if (this.symlists !== "") {
							this.symlists = TR_SPACER+this.symlists+TR_DIVIDER;
							this.subFolders += 2;
						}
						if (collections.length)
							// HVSC should be before CGSC
							this.folders = publicUploadFolder+collections[1]+collections[0];
						this.folders += csdbCompoEntry;
						this.folders += exoticCollection;
						this.folders = TR_SPACER+this.folders;
						this.folders += TR_DIVIDER+this.extra;
						this.folders += this.symlists;
						if (this.searchShortcutNew !== "")
							this.folders += TR_SPACER+this.searchShortcutNew+TR_DIVIDER;
						if (this.searchShortcutOther !== "")
							this.folders += TR_SPACER+this.searchShortcutOther+TR_DIVIDER;
						this.subFolders += 2;
					}

					// FILES

					// Sort the list of files first
					if (this.isUploadFolder()) {
						// Sort the 'SID Happens' folder by newest upload date/time
						data.files.sort(function(obj1, obj2) {
							return obj1.uploaded < obj2.uploaded ? 1 : -1;
						}.bind(this));
					} else {
						// All other folders should...
						switch (filter) {
							case "player":
								// Sort playlist according to music player
								data.files.sort(function(obj1, obj2) {
									return obj1.player.toLowerCase() > obj2.player.toLowerCase() ? 1 : -1;
								});
								break;
							case "rating":
								// Sort playlist according to rating
								data.files.sort(function(obj1, obj2) {
									return obj2.rating - obj1.rating;
								});
								break;
							case "oldest":
								// Sort playlist according to the 'copyright' string (the year in start is used)
								data.files.sort(function(obj1, obj2) {
									return obj1.copyright > obj2.copyright ? 1 : -1;
								});
								break;
							case "newest":
								// Sort playlist according to the 'copyright' string (the year in start is used)
								data.files.sort(function(obj1, obj2) {
									return obj1.copyright < obj2.copyright ? 1 : -1;
								});
								break;
							case "factoid":
								// Sort playlist according to the 'factoid' string
								data.files.sort(function(obj1, obj2) {
									return obj1.fvalue < obj2.fvalue ? 1 : -1;
								});
								break;
							case "shuffle":
								// Sort playlist in a random manner (randomize)
								for (var i = 0; i < data.files.length; i++) {
									data.files[i].shuffle = Math.random();
								}
								data.files.sort(function(obj1, obj2) {
									return obj1.shuffle > obj2.shuffle ? 1 : -1;
								});
								break;
							default:
								// Sort playlist according to the SID filename ("name")
								data.files.sort(function(obj1, obj2) {
									var o1 = obj1.substname !== "" ? obj1.substname : this.adaptBrowserName(obj1.filename, true);
									var o2 = obj2.substname !== "" ? obj2.substname : this.adaptBrowserName(obj2.filename, true);
									return o1.toLowerCase() > o2.toLowerCase() ? 1 : -1;
								}.bind(this));
						}
						$("#dropdown-sort").val(filter);
					}

					this.isCompoFolder = data.compo;
					this.today = data.today;

					// Can't use the above boolean here because this also needs to work in search mode
					if (this.path.substr(0, 24) === "/CSDb Music Competitions" && this.path.length > 25) {
						// Searching 'Here' in an a single compo folder is currently not supported
						$("#search-here").prop("checked", false).prop("disabled", true);
						$("#search-here-container label").addClass("disabled");
					}

					$.each(data.files, function(i, file) {
						// Player: Replace "_" with space + "V" with "v" for versions
						var player = file.player.replace(/_/g, " ").replace(/(V)(\d)/g, "v$2"),
							rootFile = (this.isSearching || this.isSymlist || this.isCompoFolder ? "" : this.path) + "/" + file.filename,
							countVideos = file.videos, playerType = "",
							isNew = file.hvsc == this.HVSC_VERSION || file.hvsc == this.CGSC_VERSION ||
								(typeof file.uploaded != "undefined" && file.uploaded.substr(0, 10) == this.today.substr(0, 10));
						var adaptedName = file.substname == "" ? file.filename.replace(/^\_/, '') : file.substname;
						adaptedName = this.adaptBrowserName(adaptedName);
						var list_of_tags = this.buildTags(file.tags, file.tagtypes, file.tagids),
							infoSecondary = typeof file.uploaded != "undefined" ? ' by '+file.author : ' in '+player;
						var tag_start_end = file.tagidend ? ' data-tag-start-id="'+file.tagidstart+'" data-tag-end-id="'+file.tagidend+'"': '';
						var stil = file.stil;
						var hasStil = stil != "" ? "<div></div><div></div><div></div>" : "";
						$.each(playerStrips, function(i, strip) {
							if (file.player.indexOf(strip.type) != -1) {
								playerType = " "+strip.class;
								return false;
							}
						});

						// Need to show a special flag box for e.g. "2SID"?
						var sidSpecial = "";
						if (file.filename.toLowerCase().indexOf("_2sid.sid") !== -1)
							sidSpecial = '<div class="sid-special sidsp-2sid">2SID</div>';
						else if (file.filename.toLowerCase().indexOf("_3sid.sid") !== -1)
							sidSpecial = '<div class="sid-special sidsp-3sid">3SID</div>';

						// Define a bar width for size-type factoids
						var fbarWidth = 0;
						const maxBarSize = 200;
						if (file.fvalue > 0) {
							switch (parseInt(main.factoidType)) {
								case 2:		// Song length
									const maxMinutes = 10;
									const full = maxMinutes * 60 * 1000;
									const ratio = Math.min(1, Math.max(0, file.fvalue / full));
									fbarWidth = Math.round(ratio * maxBarSize) + 23; // Text width
									break;
								case 7:		// Size in bytes (decimal)
									fbarWidth = this.bytesToBarWidthPivotLog(file.fvalue); // No text width
									break;
								case 12:	// Number of CSDb entries
									fbarWidth = (file.fvalue * 1.25) + 54; // Text width
									break;
							}
						}

						files += // GET FOLDER: SID ROW
							'<tr'+(SID.emulator == "youtube" && countVideos == 0 ? ' class="disabled"' : '')+'>'+
								'<td class="sid unselectable">'+sidSpecial+
								'<div class="pl-strip'+playerType+'"><div class="has-stil">'+hasStil+'</div></div>'+
								'<div class="block-wrap'+(sidSpecial !== "" ? ' bw-sidsp' : '')+'"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes'+(this.isSymlist ? ' specific' : '')+(isNew ? ' newst' : '')+'">'+(this.isSymlist ? file.startsubtune : file.subtunes)+'</div>' : (isNew ? '<div class="newsid"></div>' : ''))+
								'<div class="entry name file'+(this.isSearching || this.isCompoFolder || this.path.substr(0, 2) === "/$" ? ' search' : '')+'" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'" data-id="'+file.id+'" data-symid="'+file.symid+'">'+adaptedName+'</div></div></div><br />'+
								'<span class="info">'+file.copyright.substr(0, 4)+infoSecondary+'<div class="tags-line"'+(showTags ? '' : ' style="visibility:hidden;"')+tag_start_end+'>'+TAGS_BRACKET+list_of_tags+'</div></span></td>'+
								'<td class="stars filestars"><span class="rating">'+this.buildStars(file.rating)+'</span>'+
								(typeof file.uploaded != "undefined" ? '<span class="uploaded-time">'+file.uploaded.substr(0, 10)+'</span>' : '<div class="fdiv"><div class="fbar" style="width:'+fbarWidth+'px"></div><span class="factoid">'+file.factoid+'</span></div>')+
								'</td>'+
							'</tr>'; // &#9642; is the dot character if needed

						// If the STIL text starts with a <BR> newline or a <HR> line, get rid of it
						if (stil.substr(2, 4) == "r />") stil = stil.substr(6);

						this.playlist.push({
							id:				file.id,
							filename:		file.filename,
							substname:		file.substname,	// Symlists can have renamed SID files
							fullname:		this.ROOT_HVSC + rootFile,
							sidspecial:		sidSpecial,
							playerraw:		file.playerraw,
							player: 		player,
							tags:			list_of_tags,
							tagstart:		file.tagidstart,
							tagend:			file.tagidend,
							length: 		file.lengths,
							type:			file.type,
							version:		file.version,
							clockspeed:		file.clockspeed,
							sidmodel:		file.sidmodel,
							subtunes:		file.subtunes,
							startsubtune:	file.startsubtune == 0 ? 0 : file.startsubtune - 1, // If 0 then SIDId skipped it
							infosec:		infoSecondary,
							size:			file.datasize,
							address:		file.loadaddr,
							init:			file.initaddr,
							play:			file.playaddr,
							copyright:		file.copyright,
							stil:			stil,
							rating:			file.rating,
							hvsc:			file.hvsc,
							symid:			file.symid,
							videos:			file.videos,
							factoid:		file.factoid,
							fvalue:			file.fvalue,
							fbarwidth:		fbarWidth,
							profile:		file.profile,	// Only files from 'SID Happens'
							uploaded:		file.uploaded,	// Only files from 'SID Happens'
						});
					}.bind(this));

					if (files !== "" || this.path === "" || this.isBigCompoFolder() || this.isMusiciansLetterFolder()) $("#dropdown-sort").prop("disabled", false);
					/*var pos = this.folders.lastIndexOf('<tr>');
					this.folders = this.folders.slice(0, pos) + this.folders.slice(pos).replace('<tr>', '<tr class="last">');*/
					$("#songs table").append(this.folders+files);

					this.showTagsBrackets();
					this.moveKeyboardToFirst();

					// Show question mark icon for focus icons (but only where these are shown)
					if (!this.isSearching && this.isMusiciansLetterFolder()) {
						$("#path").append('<div id="focus-explainer" title="P = Professional\nS = Scener\nC = Compunet\nB = Battle of the Bits">?</div>');
						this.positionFocusExplainer();
					}

					if ((this.path == "/CSDb Music Competitions" || this.path == "/_Compute's Gazette SID Collection") && !this.isSearching) {
						// Cache this big folder for fast back-browsing
						this.cache.folder = this.folders+files;
						this.cache.incompatible = data.incompatible;
						if (this.cache.compolist.length == 0) this.cache.compolist = this.compolist;
					}

					// Hack to make sure the bottom search bar sits in the correct bottom of the viewport
					$(window).trigger("resize");
					SetScrollTopInstantly("#folders", scrollPos);
					if (typeof callback === "function") callback.call(this);
				});
				if (this.path == "")
					ctrls.state("root/back", "disabled");

				DisableIncompatibleRows();

			}.bind(this));
		}
	},

	/**
	 * Make sure the '?' explainer lines up with the focus icons.
	 */
	positionFocusExplainer: function() {
		const $path = $("#path")
			$firstIcon = $("#songs .folder-focus:visible").first(),
			$expl = $("#focus-explainer");

		if ($firstIcon.length && $path.length && $expl.length) {
			const iconLeft = $firstIcon.offset().left;
			const pathLeft = $path.offset().left;
			const leftInPath = Math.round(iconLeft - pathLeft) + 8;

			$expl.css({ left: leftInPath + "px" });
		}
	},

	/**
	 * Use pivoted log scale for size of SID files in bytes.
	 * 
	 * - floorBytes: Lower bound for log (prevents -Inf)
	 * - smallBoostBytes: Bytes up to here keep the pure log mapping (so tiny tunes still stand out)
	 * - gamma: >1 compresses mid/high region; e.g. 1.3 to 1.7
	 */
	bytesToBarWidthPivotLog: function (
		bytes,
		maxPx = 200,
		fullScaleBytes = 65536,
		floorBytes = 54,		// Default 128
		smallBoostBytes = 300,  // Keep tiny sizes boosted
		gamma = 1.5             // Compress mid (2–8 KB) a bit
	) {

		let b = Math.max(0, Number(bytes) || 0);
		if (b === 0) return 0;

		// Clamp to [floor, full] for the log math
		const full = Math.max(fullScaleBytes, floorBytes + 1);
		b = Math.min(Math.max(b, floorBytes), full);

		// Base log ratio in [0..1]
		const logDen = Math.log(full / floorBytes);
		const ratio = logDen > 0 ? Math.log(b / floorBytes) / logDen : 0;

		// Split point for the “tiny boost” region
		const rSmall = Math.log(Math.max(smallBoostBytes, floorBytes) / floorBytes) / logDen;

		let rAdj;
		if (ratio <= rSmall) {
			// Keep tiny region as-is (pure log)
			rAdj = ratio;
		} else {
			// Compress the rest using gamma, but keep continuity at rSmall
			const t = (ratio - rSmall) / (1 - rSmall); // Normalize to [0..1]
			rAdj = rSmall + (1 - rSmall) * Math.pow(t, gamma);
		}

		return Math.round(rAdj * maxPx);
	},

	/**
	 * Go to the specified folder and show its contents.
	 * 
	 * @param {string} path 		Full path to the folder
	 */
	gotoFolder: function(path) {
		if (path.substring(0, 6) == "DEMOS/" || path.substring(0, 6) == "GAMES/" || path.substring(0, 10) == "MUSICIANS/")
			this.path = '/_High Voltage SID Collection/' + path;
		else if (path == "")
			this.path = ""; // Root
		else
			this.path = (path.startsWith("/") ? path : "/" + path);

		ctrls.state("prev/next", "disabled");
		ctrls.state("subtunes", "disabled");

		blockNextEnter = true;

		this.getFolder();
		this.scrollPositions = [this.scrollPositions[0]];
		this.kbSelectedRow = [this.kbPositions[0]];
		this.moveKeyboardSelection(this.kbSelectedRow, false);
		this.getComposer();
		ctrls.subtuneCurrent = ctrls.subtuneMax = 0; // Clear subtune switch
		this.redirectFolder = "";

		UpdateURL(true);
	},

	/**
	 * Get the length of the SID (sub) tune and convert it to just seconds.
	 * 
	 * @param {number} subtune		Subtune number
	 * @param {boolean} noReset		If specified and TRUE, skip resetting the bar fields
	 * 
	 * @return {number}				The total number of seconds
	 */
	getLength: function(subtune, noReset) {

		if (SID.emulator == "youtube") {
			// Get the raw length from the YouTube IFrame API when it is actually playing
			// NOTE: Not resetting 'this.secondsLength' to 0 fixed seeking.
			var i = 0;
			$("#time-length").empty().append('<img src="images/loading_threedots.svg" alt="..." style="position:relative;top:-1px;width:28px;">');
			var waitForYTPlaying = setInterval(function() {
				if (++i == 20 || (SID.ytReady && SID.isPlaying())) {
					clearInterval(waitForYTPlaying);
					this.secondsLength = SID.ytReady
						? SID.YouTube.getDuration() // Only set correctly when it is playing
						: 0;
					$("#time-current").empty().append("0:00");
					var minutes = ~~((this.secondsLength % 3600) / 60),
						seconds = ~~this.secondsLength % 60;
					if (seconds < 10) seconds = "0"+seconds;
					$("#time-length").empty().append(minutes+":"+seconds);

					// If the channel tab's video timed out then try the next channel tab
					if (i == 20 && $("#play").css("display") == "none") {
						var $ytTabs = $("#youtube-tabs");
						var $ytChain = $ytTabs.find("div.tab"),
							thisTab = $ytTabs.children("div.selected").index();
						var newTab = thisTab < $ytChain.length - 1 ? ++thisTab : 0;
						$ytChain.eq(newTab).trigger("click");
					}
				}
			}.bind(this), 500);

		} else {

			// Example of a DB length string for four subtunes: "4:03 0:07 0:03 1:41"
			var length = this.playlist[this.songPos].length.split(" ")[subtune];
			if (typeof length === "undefined") length = "0:00";

			this.secondsLength = length.split(":");
			this.secondsLength = parseInt(this.secondsLength[0]) * 60 + parseInt(this.secondsLength[1]);

			if (typeof noReset === "undefined") {
				$("#time-current").empty().append("0:00");
				var msInLength = length.indexOf(".") != -1;
				$("#time-length").empty().append(length.split(".")[0] + (msInLength ? '<div>&#9642;</div>' : ''))
					.attr("title", msInLength ? length : "");
				return $("#loop").hasClass("button-on") ? 0 : this.secondsLength;
			}
		}
		return this.secondsLength;
	},

	/**
	 * Turn the SID file row red to indicate that the handler can't play this.
	 */
	errorRow: function() {
		// Can't rely on .selected as it might not be updated yet 
		var $tr = $("#folders tr").eq(this.subFolders + this.songPos);

		// Turn the row all red
		$tr.find(".entry").css("color", GetCSSVar("--color-sid-row-error-entry"));
		$tr.find("span.info").css("color", GetCSSVar("--color-sid-row-error-info"));
		$tr.css("background", GetCSSVar("--color-sid-row-error-bg"));

		// Remove stuff, clear boxes, disable buttons
		$("#sid-model,#clockspeed").remove();
		$("#memory-chunk").css({left: "0", width: "0"});
		$("#info-text").empty();
		$("#stopic-stil,#stopic-tags").empty();

		ctrls.state("play/stop", "disabled");
		ctrls.state("prev/next", "enabled"); // Still need to skip it
		ctrls.state("subtunes", "disabled");
		ctrls.state("faster", "disabled");
		ctrls.state("loop", "disabled");
		$("#volume").prop("disabled", true);
	},

	/**
	 * Clicked a star to set a rating for a file.
	 * 
	 * @param {*} event				Event when clicking the star (a <B> element)
	 * @param {string} fullname		The SID filename including folders
	 */
	registerStarRating: function(event, fullname) {

		if (!$("#logout").length) {
			// But must be logged in to do that
			alert("Login or register and you can click these stars to vote for a file or folder.");
			return false;
		}

		var isInfoRating = event.currentTarget.tagName == "DIV",
			rating = event.shiftKey ? 0 : 5 - $(event.target).index(); // Remember stars are backwards (RTL; see CSS)

		// Star rating for a folder or a SID file (PHP script figures this out by itself)
		$.post("php/rating_write.php", { fullname: fullname, rating: rating }, function(data) {
			this.validateData(data, function(data) {

				var stars = this.buildStars(data.rating);

				if (isInfoRating) {
					// Star ratings clicked in the info box
					$("#info-rating").empty().append(stars);
					var $relevant = $("#songs table").find("[data-id='"+ctrls.currentFileID+"']")
					if ($relevant.length) {
						// The current browse list contains the current song so adapt its ratings
						$relevant.parents("tr").find("span.rating").empty().append(stars);
						// Update the playlist array too so sorting also works
						$.each(this.playlist, function(i, file) {
							if (file.id == ctrls.currentFileID) {
								file.rating = data.rating;
								return false;
							}
						});
					}
				} else {
					// Star ratings clicked in a SID row
					var $tr = $(event.target).parents("tr");
					$tr.find("span.rating").empty().append(stars);
					if ($tr.find(".name").attr("data-id") == ctrls.currentFileID)
						// It's the current song so adapt the info box ratings too
						$("#info-rating").empty().append(stars);

					// But also update the relevant array for later filtering/sorting
					var isFile = $tr.find(".name").hasClass("file"),
						endName = this.isSymlist || this.isCompoFolder ? fullname : fullname.split("/").slice(-1)[0];
					if (isFile) {
						// Update the playlist array
						$.each(this.playlist, function(i, file) {
							if (file.filename == endName) {
								file.rating = data.rating;
								return false;
							}
						});
					} else {
						// Update the cache HTML directly
						var isCacheFolder = (this.path === "/CSDb Music Competitions" || this.path === "/_Compute's Gazette SID Collection") && this.cache.folder !== "";
						// Temporarily make the HTML string of folders into a jQuery object
						var $folders = $(isCacheFolder ? this.cache.folder : this.folders);
						$($folders).find('.name[data-name="'+encodeURIComponent(endName)+'"]')
							.parents("td").next().find(".rating")
							.empty().append(stars);
						// Has to be wrapped to get everything back
						var wrapped = $("<div>").append($folders.clone()).html();
						if (isCacheFolder)
							this.cache.folder = wrapped;
						else
							this.folders = wrapped;
					}
					if (this.isBigCompoFolder()) {
						// Update the compolist arrays
						$.each([this.compolist, this.cache.compolist], function() {
							$.each(this, function(i, file) {
								if (file.foldername == endName) {
									file.rating = data.rating;
									return false;
								}
							});
						});
					}
				}
			});
		}.bind(this));
	},

	/**
	 * Build the HTML elements needed to show the marked stars in the SID file row.
	 * 
	 * @param {number} rating	The rating; 0 to 5
	 * 
	 * @return {string}			The HTML string to put into the SID row
	 */
	buildStars: function(rating) {
		var s = $("#logout").length ? "sh " : "s "; // Only allow stars lighting up on hover if logged in

		if (!rating || rating === "0")
			return '<b class="'+s+'eu"></b><b class="'+s+'eu"></b><b class="'+s+'eu"></b><b class="'+s+'eu"></b><b class="'+s+'eu"></b>';

		var stars = "";
		for (var i = rating; i < 5; i++)
			stars += '<b class="'+s+'ev"></b>';
		for (var i = 0; i < rating; i++)
			stars += '<b class="'+s+'xv"></b>';

		return stars;
	},

	/**
	 * Build the HTML elements needed to show the tags in the SID file row. 
	 * 
	 * @param {array} tags		Array with (sorted) tag names only
	 * @param {array} types		Array with (sorted) tag types only
	 * @param {array} ids		Array with (sorted) tag ids only
	 * 
	 * @return {string}			The HTML string to put into the SID row
	 */
	buildTags: function(tags, types, ids) {
		var list_of_tags = remix64 = gamebase64 = id = "";
		$.each(tags, function(i, tag) {
			id = ' data-id="'+ids[i]+'"';
			if (tag == "Remix64") {
				// A special look for the "Remix64" tag
				remix64 = '<div class="tag tag-remix64"'+id+'>&nbsp;&nbsp;</div>';
			} else if (tag == "GameBase64") {
				// A special look for the "GameBase64" tag
				gamebase64 = '<div class="tag tag-gamebase64"'+id+'>&nbsp;&nbsp;</div>';
			} else if (tag == "Doubling" || tag == "Hack" || tag == "Mock" || tag == "Bug" || tag == "Recycled") {
				// A unique color for tags that serves as a warning
				list_of_tags += '<div class="tag tag-warning"'+id+'>'+tag+'</div>';
			} else if (tag == "Music") {
				// Change music tag into just a double note icon
				list_of_tags += '<div class="tag tag-production tag-notes tag-music"'+id+'><img src="images/composer_doublenote.svg" /><span></span></div>';
			} else if (tag == "Collection") {
				// Change collection tag into a double note icon followed by a list icon
				list_of_tags += '<div class="tag tag-production tag-notes tag-collection"'+id+'><img src="images/composer_doublenote.svg" /><img style="margin-left:12px;" src="images/visuals_memory.svg" /><span>&nbsp;&nbsp;&nbsp;&nbsp&nbsp</span></div>';
			/*} else if (tag == "Compo") {
				// Add a double note to make it clear this is for music competitions only
				list_of_tags += '<div class="tag tag-event tag-notes tag-compo"'+id+'><img src="images/composer_doublenote.svg" /><span>Compo</span></div>';*/
			} else if (tag == "Winner") {
				// Add a class that turns the tag into gold
				list_of_tags += '<div class="tag tag-event tag-winner"'+id+'>Winner</div>';
			} else if (tag == "<-") {
				// Replace "<-" with a pretty unicode arrow instead
				// Disabled as perhaps users find them too confusing.
				//list_of_tags += '<div class="tag tag-transparent"'+id+'>🡨</div>';
			} else if (tag == "->") {
				// Replace "->" with a pretty unicode arrow instead
				// Disabled as perhaps users find them too confusing.
				//list_of_tags += '<div class="tag tag-transparent"'+id+'>🡪</div>';
			} else if (tag == "$31" || tag == "$61" || tag == "$71" || tag == "2SID" || tag == "3SID" || tag == "# ?" || tag.indexOf("Small Event") !== -1) {
				// These tags will not be shown for various reasons:
				// Waveforms: Too commonly used in SID tunes and just adds noise.
				// Small Event: Just don't add an event tag if it's tiny and rare.
				// 2SID/3SID: No longer needed because of SID special labels.
			} else {
				// NOTE: Don't change the order of tags or the collector for a folder will break!
				// If you want to change the order of tags, see GetTagsAndTypes() in 'tags_read.php'
				list_of_tags += '<div class="tag tag-'+types[i]+'"'+id+'>'+tag+'</div>';
			}
		});
		list_of_tags += '<div class="edit-tags" title="Edit tags">&nbsp;</div>';

		return gamebase64+remix64+list_of_tags;
	},

	/**
	 * Hide the rating stars and show a spinner to show that the SID tune is loading.
	 * 
	 * @param {object} $td	The jQuery element with the SID filename
	 */
	showSpinner: function($td) {
		if (SID.emulatorFlags.slowLoading) {
			// Temporarily hide the rating stars and tags, show a loading spinner instead
			$($td).children("span.info").children("div.tags-line").css("visibility", "hidden");
			$stars = $($td).next("td.stars");
			$stars.children("span").hide();
			$stars.append('<span id="spinner"></span>');
			$stars.children("div.fdiv").hide();
		}
	},

	/**
	 * Clear the SID tune loading spinner and show the ratings stars again.
	 */
	clearSpinner: function() {
		$("#songs td.stars span,#songs td.stars div.fdiv").show();
		if (showTags)
			$("#songs .tags-line").css("visibility", "");
		$("#spinner").remove();
	},

	/**
	 * If the annex box is not visible, show it with links or tips.
	 */
	 showAnnexBox: function() {
		if (!$("#annex").is(":visible") && !this.annexNotWanted) {
			if ($("#topic-profile a.clinks").length) {
				// Show links in the annex box for the currently shown profile
				$("#topic-profile a.clinks").trigger("click");
			} else {
				// Show a random tip in an annex box
				$.get("php/annex_tips.php", /*{ id: 15 },*/ function(tips) {
					$("#annex-tips").empty().append(tips);
					$(".annex-topics,#annex").show();
				});
			}
		}
	},

	/**
	 * Show the composer page in the 'Profile' tab.
	 * 
	 * @param {string} overridePath		If specified, fullname for profile (including file)
	 * @param {boolean} rawPath			Unless, if specified, this is set to TRUE (path only)
	 */
	getComposer: function(overridePath, rawPath) {
		if (miniPlayer || isMobile) return;
		if (this.composer) this.composer.abort();
		if (this.groups) this.groups.abort();

		if (typeof overridePath == "undefined")
			overridePath = "";
		else {
			// We have an override path for a search entry
			if (typeof rawPath == "undefined" || !rawPath) {
				overridePath = overridePath.substr(overridePath.indexOf("/") + 1);
				overridePath = overridePath.substr(0, overridePath.lastIndexOf("/"));
			}
			// Don't reload the same profile again and again
			if (overridePath == this.previousOverridePath) return;
			this.previousOverridePath = overridePath;
		}

		if (overridePath == "" && (this.path.substr(0, 2) == "/!" || this.path.substr(0, 2) == "/$")) {
			// Symlists won't get a composer page (for now at least)
			$("#topic-profile").empty();
			this.showAnnexBox();
			return;
		}

		$("#topic-profile").empty().append(this.loadingSpinner("profile"));

		this.composerCache = "";

		var loadingComposer = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-profile").fadeIn(500);
		}, 250);

		if (this.path == "" && overridePath == "") {
			// Welcome page for the root
			if ($("#tabs .selected").attr("data-topic") === "profile")
				$("#page").addClass("big-logo");

			this.composer = $.get("php/root.php", function(data) {
				this.validateData(data, function(data) {

					clearTimeout(loadingComposer);
					if (parseInt(colorTheme)) data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
					$("#topic-profile").empty().append(data.html);

					$("#page .dropdown-top-list").styledSelect("toplist");
					$("#page .dropdown-top-list-left").styledSetValue(data.left);
					$("#page .dropdown-top-list-right").styledSetValue(data.right);

					$("#page .dropdown-top-rows")
						.styledSelect("toprows")
						.styledSetValue("10");

					// Event handler must be set here
					$("div.styledSelect").change(function() {
						var side = $(this).prev("select").attr("name").split("-")[3];
						if (side == "left" || side == "right") {
							$.get("php/root_get.php", {
								type: $("#page .dropdown-top-list-"+side).styledGetValue(),
								rows: $("#page .dropdown-top-rows-"+side).styledGetValue(),
							}, function(data) {
								data = $.parseJSON(data);
								$("#page .top-list-"+side).empty().append(data.list);
							});
						}
					});

					this.showAnnexBox();

					// Update avatar images of the three quick shortcut columns
					setTimeout(function() {
						var $qs = $("#topic-profile img.quick-thumbnail"), i = 0;
						var qsTimer = setInterval(function() {
							if (i >= $qs.length) {
								clearInterval(qsTimer);
								return;
							}
							var element = $qs.eq(i++)[0];
							element.setAttribute("src", element.dataset.src);
						}, 1);
					}, 1);

				});
			}.bind(this));
		} else {
			// Composer profile page
			if ($("#tabs .selected").attr("data-topic") === "profile")
				$("#page").removeClass("big-logo");
			this.composer = $.get("php/composer.php", {
				fullname: (overridePath == "" ? this.path.substr(1) : overridePath)
			}, function(data) {
				this.validateData(data, function(data) {

					clearTimeout(loadingComposer);
					if (parseInt(colorTheme)) data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
					$("#topic-profile").empty().append(data.html);
					ResetDexterScrollBar("profile");

					// Add star rating for this composer profile
					$("#topic-profile .folder-rating").append(this.buildStars(data.rating));

					// Add report profile change link
					var composerFolder = "https://deepsid.chordian.net/?file=/"+(overridePath == "" ? this.path.substr(1) : overridePath);
					// Commented out as nobody was using it and it clashed with long names
					// $("#profilechange").append('<a href="mailto:chordian@gmail.com?subject=DeepSID%20profile%20change&body=I%20have%20a%20profile%20change%20request%20for:%0D%0A'+composerFolder+'%0D%0A%0D%0A">Report a profile change</a>');

					// Enable the brand image (if available) for the correct color theme
					$("#brand-"+(parseInt(colorTheme) ? "dark" : "light")).show();

					this.showAnnexBox();

					// If the "Links" tab in the annex box is present then refresh the box
					if ($("#annex .annex-tabs").text().indexOf("Links") != -1)
						$("#topic-profile a.clinks").trigger("click", true);					

					this.groupsFullname = overridePath == "" ? this.path.substr(1) : overridePath;
					this.getGroups(this.groupsFullname);
				});
			}.bind(this));
		}
	},

	/**
	 * Get the contents of the groups table and display it in the composer profile.
	 * 
	 * @param {string} fullname		The SID filename including folders
	 */
	getGroups: function(fullname) {
		if (miniPlayer) return;
		clearTimeout(this.groupsTimer);
		this.groups = $.get("php/groups.php", { fullname: fullname }, function(data) {
			try {
				data = $.parseJSON(data);
			} catch(e) {
				alert("An error occurred. If it keeps popping up please tell me about it: chordian@gmail.com");
				return false;
			}
			if (data.status == "error" || data.status == "warning") {
				$("#table-message").empty().append('<div class="no-profile">Could not read the group data from CSDb. Retrying again in a few seconds.</div>');
				// CSDb is acting up; retry after a minute
				this.groupsTimer = setTimeout(function() {
					// Show the spinner again before retrying
					$("#table-message").empty().append('<img class="loading-dots" src="images/loading_threedots.svg" alt="" style="margin-top:10px;" />');
					this.getGroups(this.groupsFullname);
				}.bind(this), 20000);
			} else if (data.html !== "") {
				$("#table-groups").empty().append(data.html);
				var html = $("#topic-profile").html();
				// Don't include the script or the chart stuff will be shown twice
				this.composerCache = html.substr(0, html.indexOf("<script"));
			}
		}.bind(this));
	},

	/**
	 * Show contents in the 'CSDb' tab pertinent to the selected SID tune. A spinner is
	 * shown while getting contents through the CSDb web service.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {string} type			Optional; e.g. 'sid' or 'release'
	 * @param {number} id			Optional; ID number used by CSDb
	 * @param {boolean} canReturn	Optional; if TRUE, coming from list
	 */
	getCSDb: function(type, id, canReturn) {
		if (miniPlayer || isMobile || this.isTempTestFile()) return;
		if (this.csdb) this.csdb.abort();
		$("#topic-csdb").empty().append(this.loadingSpinner("csdb"));
		$("#sticky-csdb").empty();

		var loadingCSDb = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-csdb").fadeIn(500);
		}, 250);

		// Get the group or user in the copyright text
		var lines = $("#info-text").html().split("<br>");
		var lastLine = lines[lines.length - 1];
		var copyright = lastLine.substring(lastLine.indexOf(" ") + 1);

		// Determine the arguments to be sent to the PHP file
		var args = typeof type !== "undefined" && typeof id !== "undefined"
			? { type: type, id: id, copyright: copyright }
			: { fullname: browser.playlist[browser.songPos].fullname.substr(5) };

		this.csdb = $.get("php/csdb.php", args, function(data) {
			this.validateData(data, function(data) {

				if (data.debug !== "") console.log(data.debug);

				// Gather the group names used by the scener (if applicable)
				var groupTexts = [];
				$("a.group").each(function() {
					let clone = $(this).clone();
					clone.find("del").contents().unwrap();
					groupTexts.push(clone.text().trim());
				});
				this.groupNames = [...new Set(groupTexts)];

				clearTimeout(loadingCSDb);
				if ((typeof canReturn !== "undefined" && canReturn) && data.status !== "warning") {
					// Make sure legacy cache files always use the default 'BACK' button
					data.sticky = data.sticky.replace("go-back-init", "go-back");
					// Sometimes a 'BACK' button is missing (e.g. if a link chain icon click was cached)
					if (data.sticky.indexOf("go-back") === -1)
						data.sticky = data.sticky.replace('</h2>', '</h2><button id="go-back">Back</button>');
				} else {
					// A single release page should never show a 'BACK' button
					data.sticky = $("<div>").html(data.sticky).find("#go-back, #go-back-init").remove().end().html();
				}
				$("#sticky-csdb").empty().append(data.sticky);
				if (parseInt(colorTheme))
					data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
				$("#topic-csdb").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("csdb");

				this.additionalEmphasizing(false);
				UpdateRedirectPlayIcons();

				// Enable highlighting button and its label if any emphasizing is present
				setTimeout(() => {
					if ($("table.releases tr").find("a.emphasize, a.empSec, a.empThird").length > 0)
						$("#csdb-emp-filter, #csdb-emp-filter-label").removeClass('disabled').prop('disabled', false);
				}, 0);

				if (data.entries != "") this.sidEntries = data.entries; // Array used for sorting

				// Add rows sorted by newest by triggering the drop-down box (if present)
				$("#dropdown-sort-csdb").trigger("change");

				// If there are any entries then show a notification number on the 'CSDb' tab (if not in focus)
				if (typeof data.count != "undefined" && data.count != 0 && $("#tabs .selected").attr("data-topic") !== "csdb" && !this.isCGSC())
					// If it's a release page then show a special character instead of a count
					$("#note-csdb").empty().append(data.count > 0 ? data.count : "&#9679;").show(); // 8901, 9679
				else
					$("#note-csdb").hide();

			});
		}.bind(this));
	},

	/**
	 * Add additional highlighting of group names, real name or handle, in green and
	 * red in the list of CSDb entries.
	 */
	additionalEmphasizing: function(alsoSort) {
		this.alsoSortEmp = alsoSort;
		setTimeout(function() {

			// Get the name and/or handle of the composer
			var songAuthor = SID.getSongInfo("info").songAuthor;
			var nameMatch = songAuthor.match(/^([^(]+?)(?: \(([^)]+)\))?$/);
			var realName = nameMatch ? nameMatch[1].trim().toLowerCase() : "";
			var handle = nameMatch && nameMatch[2] ? nameMatch[2].trim().toLowerCase() : "";

			// Emphasize other group names in green that might also be relevant productions
			$("a.csdb-group").each(function(i, element) {
				var currentText = $(element).text().trim().toLowerCase();
				browser.groupNames.forEach(function(name) {
					if (name.toLowerCase() === currentText) {
						$(element).addClass("empSec");
					}
				});
			});

			// Also emphasize if the composer used a real name or handle for the release
			$("a.csdb-scener").each(function(i, element) {
				var currentText = $(element).text().trim().toLowerCase();
			if ((realName && realName === currentText) || (handle && handle === currentText))
					$(element).addClass("empThird");
			});

			if (typeof browser.alsoSortEmp !== "undefined" && browser.alsoSortEmp) {
				// We need to filter highlighted entries only again after sorting
				$("#topic-csdb table.releases tr").each(function() {
					var hasHighlight = $(this).find("a.emphasize, a.csdb-group.empSec, a.csdb-scener.empThird").length > 0;
					if (!hasHighlight) $(this).hide();
				});
			}

		}, 0);
	},

	/**
	 * Show contents in the 'Player' tab about the editor/player used to create the
	 * song, if available.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {array} params	player: {string} or id: {number}
	 */
	getPlayerInfo: function(params) {
		if (miniPlayer || isMobile || JSON.stringify(params) == JSON.stringify(this.playerParams)) return;
		if (this.playerInfo) this.playerInfo.abort();
		$("#topic-player").empty().append(this.loadingSpinner("player"));
		$("#sticky-player").empty();

		this.playerParams = params; // Prevents reloading of the same page (not 100% perfect)

		var loadingPlayer = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-player").fadeIn(500);
		}, 250);

		this.playerInfo = $.get("php/player.php", params, function(data) {
			this.validateData(data, function(data) {

				var height = data.info ? 58 : 34;

				clearTimeout(loadingPlayer);
				$("#sticky-player").empty().height(height).append(data.sticky);
				$("#topic-player").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("player");

				// If there are any entries then show a special notification character (if not in focus)
				if (data.count != 0 && $("#tabs .selected").attr("data-topic") !== "player" && data.status !== "warning")
					$("#note-player").empty().append("&#9679;").show();
				else
					$("#note-player").hide();

			});
		}.bind(this));
	},

	/**
	 * Show a competition results list in the 'CSDb' tab.
	 * 
	 * @param {string} compo	Type, e.g. "C64 Music" (obtained from a CSDb page)
	 * @param {number} id 		The CSDb event ID
	 * @param {number} mark		ID of the release page to mark on the competition results list
	 */
	getCompoResults: function(compo, id, mark) {
		if (miniPlayer || isMobile) return;
		if (this.compo) this.compo.abort();
		$("#topic-csdb").empty().append(this.loadingSpinner("csdb"));
		$("#sticky-csdb").empty();

		var loadingCSDb = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-csdb").fadeIn(500);
		}, 250);

		this.compo = $.get("php/csdb_compo_table.php", { compo: compo, id: id, mark: mark }, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingCSDb);
				$("#sticky-csdb").empty().append(data.sticky);
				if (parseInt(colorTheme))
					data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
				$("#topic-csdb").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("csdb");

				// Populate all path table cells with HVSC plinks (when available in the CSDb release pages)
				$("#topic-csdb .compo-path").each(function() {
					var $this = $(this);
					$.get("php/csdb_compo_path.php", { id: $this.attr("data-id") }, function(data) {
						browser.validateData(data, function(data) {
							if (parseInt($this.attr("data-id")) == parseInt(mark))
								data.path = data.path.replace('redirect"', 'redirect playing"');
							$this.append(data.path);
						});
					});
				});
			});
		}.bind(this));
	},

	/**
	 * Show contents in the 'GB64' tab pertinent to the selected SID tune. A spinner is
	 * shown while calling the PHP script.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {number} optionalID		If specified, the ID to show a specific sub page
	 */
	getGB64: function(optionalID) {

		if (miniPlayer || isMobile || this.isTempTestFile()) return;
		if (this.gb64) this.gb64.abort();
		$("#topic-gb64").empty().append(this.loadingSpinner("gb64"));
		$("#sticky-gb64").empty();

		var loadingGB64 = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-gb64").fadeIn(500);
		}, 250);

		thisFullname = browser.playlist[browser.songPos].fullname.substr(5); 

		var params = typeof optionalID === "undefined"
			? { fullname: thisFullname }
			: { id: optionalID };

		this.gb64 = $.get("php/gb64.php", params, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingGB64);
				$("#sticky-gb64").empty().append(data.sticky);
				$("#topic-gb64").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("gb64");
	
				// If there are any entries then show a notification number on the 'GB64' tab (if not in focus)
				if (data.count > 0 && $("#tabs .selected").attr("data-topic") !== "gb64" && !this.isCGSC())
					$("#note-gb64").empty().append(data.count).show();
				else
					$("#note-gb64").hide();

				// If there are entries then a "GameBase64" tag is already there or will be added below
				// which means that the redundant "Game" and "Game Prev" tags should be removed
				if (data.count > 0) {
					$.post("php/tags_remove_game.php", {
						fullname:	thisFullname,
					}, function(data) {
						browser.validateData(data, function(data) {
							browser.updateStickyTags(
								$("#songs tr.selected"),
								browser.buildTags(data.tags, data.tagtypes, data.tagids),
								thisFullname.split("/").slice(-1)[0]
							);
						});
					}.bind(this));

					// If there is no "GameBase64" tag then add it now
					if (browser.playlist[browser.songPos].tags.indexOf("tag-gamebase64") == -1) {
						$.post("php/tags_write_single.php", {
							fullname:	thisFullname,
							tag:		"GameBase64",
						}, function(data) {
							browser.validateData(data, function(data) {
								// Both updates may be called asynchronously but it shouldn't break anything
								browser.updateStickyTags(
									$("#songs tr.selected"),
									browser.buildTags(data.tags, data.tagtypes, data.tagids),
									thisFullname.split("/").slice(-1)[0]
								);
							});
						}.bind(this));
					}
				}
			});
		}.bind(this));
	},

	/**
	 * Show contents in the 'Remix' tab pertinent to the selected SID tune. A spinner is
	 * shown while calling the PHP script.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {number} optionalID		If specified, the ID to show a specific entry
	 */
	getRemix: function(optionalID) {
		if (miniPlayer || isMobile || this.isTempTestFile()) return;
		if (this.remix) this.remix.abort();
		$("#topic-remix").empty().append(this.loadingSpinner("remix"));
		$("#sticky-remix").empty();

		var loadingRemix = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-remix").fadeIn(500);
		}, 250);

		var thisFullname = browser.playlist[browser.songPos].fullname.substr(5); 

		var params = typeof optionalID === "undefined"
			? { fullname: thisFullname }
			: { id: optionalID };

		this.remix = $.get("php/remix.php", params, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingRemix);
				$("#sticky-remix").empty().append(data.sticky);
				$("#topic-remix").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("remix");

				// If there are any entries then show a notification number on the 'Remix' tab (if not in focus)
				if (data.count > 0 && $("#tabs .selected").attr("data-topic") !== "remix" && !this.isCGSC())
					$("#note-remix").empty().append(data.count).show();
				else
					$("#note-remix").hide();

				// If there are entries but no "Remix64" tag then add it now
				if (data.count > 0 && browser.playlist[browser.songPos].tags.indexOf("tag-remix64") == -1) {
					$.post("php/tags_write_single.php", {
						fullname:	thisFullname,
						tag:		"Remix64",
					}, function(data) {
						browser.validateData(data, function(data) {
							browser.updateStickyTags(
								$("#songs tr.selected"),
								browser.buildTags(data.tags, data.tagtypes, data.tagids),
								thisFullname.split("/").slice(-1)[0]
							);
						});
					}.bind(this));
				}
			});
		}.bind(this));
	},

	/**
	 * Fill an array with the personal and public symlist folders the user currently have.
	 */
	getSymlists: function() {
		$.ajax({
			url:	"php/symlist_folders.php",
			type:	"get",
			async:	false, // Have to wait to make sure this.getFolder() afterwards include the data
		}).done(function(data) {
			this.validateData(data, function(data) {
				data.symlists.sort(function(obj1, obj2) {
					return obj1.fullname.substr(1).toLowerCase() > obj2.fullname.substr(1).toLowerCase() ? 1 : -1;
				});
				this.symlistFolders = data.symlists;
			});
		}.bind(this));
	},

	/**
	 * Show the main context menu. This is shown when right-clicking a SID file row
	 * or a symlist folder.
	 * 
	 * @param {*} event 
	 */
	contextMenu: function(event) {
		if (this.isTempTestFile()) return false;
		this.getSymlists();

		var $panel = $("#panel"),
			$target = $(event.target);

		$("#contextmenu").remove();
		if (typeof this.contextTR !== "undefined")
			this.contextTR.css("background", "");

		var contents = "";
		this.contextEntry = $target.find(".entry");
		this.contextSID = decodeURIComponent(this.contextEntry.attr("data-name"));
		this.contextSymID = this.contextEntry.attr("data-symid");
		this.contextSelected = $target.parents("tr").hasClass("selected");

		// Maintain hover background color while showing the context menu
		this.contextTR = $target.parent("tr");
		this.contextTR.css("background", GetCSSVar("--color-bg-sid-hover"));

		if ($target.hasClass("sid")) {
			var notSidRows = $target.parents("table").find("td.folder,td.spacer,td.divider").length;
			var isPersonalSymlist = this.path.substr(0, 2) == "/!",
				isPublicSymlist = this.path.substr(0, 2) == "/$",
				thisRow = $target.parent("tr").index() - notSidRows;

			if (isPublicSymlist && !this.isSearching) {
				var result = $.grep(this.symlistFolders, function(entry) {
					return entry.fullname == this.path.substr(1);
				}.bind(this));
				var isMyPublicSymlist = result.length !== 0;
			}

			contents = (isPersonalSymlist || isMyPublicSymlist) && !this.isSearching

				? '<div class="line" data-action="symentry-rename">Rename</div>'+			// SID in symlist folder
				  '<div class="line" data-action="symentry-remove">Remove</div>'+
				  '<div class="line'+(this.playlist[thisRow].subtunes > 1 ? '' : ' disabled')+'" data-action="symentry-subtune">Select Subtune</div>'
					
				: '<div class="line" data-action="symlist-new">Add to New Playlist</div>'+	// SID in normal folder
				  '<div class="line submenu'+(this.symlistFolders.length === 0 ? ' disabled' : '')+'">Add to Playlist</div>';

			// Divider to more common SID file actions
			contents += '<div class="divider"></div>';

			contents +=
				'<div class="line" data-action="download-file">Download File</div>'+
				'<div class="line" data-action="edit-tags">Edit Tags</div>'+
				'<div class="line'+(this.isSearching || this.isCompoFolder || isPersonalSymlist || isPublicSymlist ? " disabled" : "")+'" data-action="copy-link">Copy Link</div>';

			var dividerForYouTube = '<div class="divider"></div>';
			if (typeof this.playlist[thisRow].uploaded !== "undefined") {
				// It's a SID row from the 'SID Happens' folder and thus can be edited
				contents += '<div class="divider"></div>'+
					'<div class="line" data-action="edit-upload">Edit Uploaded File</div>';
				if ($("#logged-username").text() == "JCH")
					// The administrator can delete files in the 'SID Happens' folder
					contents += '<div class="line" data-action="delete-file">Delete File</div>';
				dividerForYouTube = '';
			}

			if (SID.emulator == "youtube") {
				// A YouTube video link can be added to this SID row
				contents += dividerForYouTube+
					'<div class="line" data-action="edit-videos" data-subtunes="'+this.playlist[thisRow].subtunes+'">Edit YouTube Links</div>';
			}

		} else if ($target.hasClass("folder") && (this.contextSID.substr(0, 1) == "!" || this.contextSID.substr(0, 1) == "$")) {
			var ifAlreadyPublic = notYourPublicPlaylist = "";

			if (this.contextSID.substr(0, 1) == "$") {
				var result = $.grep(this.symlistFolders, function(entry) {
					return entry.fullname == this.contextSID;
				}.bind(this));
				if (result.length === 0) notYourPublicPlaylist = " disabled";
				ifAlreadyPublic = " disabled";
			}

			contents = // Symlist folder in root
				'<div class="line'+notYourPublicPlaylist+'" data-action="symentry-rename">Rename Playlist</div>'+
				'<div class="line'+notYourPublicPlaylist+'" data-action="symlist-delete">Delete Playlist</div>'+
				(ifAlreadyPublic
					? '<div class="line'+notYourPublicPlaylist+'" data-action="symlist-unpublish">Unpublish Playlist</div>'
					:'<div class="line'+notYourPublicPlaylist+'" data-action="symlist-publish">Publish Playlist</div>')+
				'<div class="line" data-action="symlist-download">Download Playlist</div>';
		} else
			return;

		// Create the hidden menu and assume coordinates for going downwards
		$panel.prepend('<div id="contextmenu" class="context unselectable">'+contents+'</div>');
		var $contextMenu = $("#contextmenu");
		$contextMenu
			.css("top", event.pageY - 2)
			.css("left", event.pageX - ($panel.offset().left - 8));

		// Flip the menu upwards if the bottom of it goes off screen
		// NOTE: Need "visibility:hidden" and not "display:none" for this to work.
		var win = $(window);
		var viewportBottom = win.scrollTop() + win.height();
		var boundsBottom = $contextMenu.offset().top + $contextMenu.outerHeight();
		if (boundsBottom > viewportBottom)
			$contextMenu.css("top", event.pageY - $contextMenu.outerHeight());

		// Show the menu
		$contextMenu.css("visibility","visible");

		return false;
	},

	/**
	 * Show a context menu when right-clicking a triangular corner button.
	 * 
	 * @param {*} event 
	 */
	contextCorner: function(event) {
		let contents = "", $panel = $("#panel");
		const indent = ' style="margin-left:8px;"';
		$("#contextmenu").remove();

		if (event.target.className.indexOf("corner-right") !== -1)
			// Factoid choices
			contents = 
				'<div class="line" data-action="factoid"'+indent+'>0. Nothing</div>'+
				'<div class="line" data-action="factoid"'+indent+'>1. Internal database ID</div>'+
				'<div class="line" data-action="factoid"'+indent+'>2. Song length</div>'+
				'<div class="line" data-action="factoid"'+indent+'>3. Type (PSID/RSID) and version</div>'+
				'<div class="line" data-action="factoid"'+indent+'>4. Compatibility (e.g. BASIC)</div>'+
				'<div class="line" data-action="factoid"'+indent+'>5. Clock speed (PAL/NTSC)</div>'+
				'<div class="line" data-action="factoid"'+indent+'>6. SID model (6581/8580)</div>'+
				'<div class="line" data-action="factoid"'+indent+'>7. Size in bytes (decimal)</div>'+
				'<div class="line" data-action="factoid"'+indent+'>8. Start and end address (hexadecimal)</div>'+
				'<div class="line" data-action="factoid"'+indent+'>9. HVSC or CGSC update version</div>'+
				'<div class="line" data-action="factoid">10. CSDb SID ID</div>'+
				'<div class="line" data-action="factoid">11. Game status (RELEASE/PREVIEW)</div>'+
				'<div class="line" data-action="factoid">12. Number of CSDb entries</div>';
		else
			// @todo toggling tag types
			return;

		// Create the context menu
		$panel.prepend('<div id="contextmenu" class="context unselectable">'+contents+'</div>');
		let $contextMenu = $("#contextmenu");

		$contextMenu
			.css("top", event.pageY - $contextMenu.outerHeight()) // Always flip this menu upwards
			.css("left", event.pageX - ($panel.offset().left - 8))
			.css("visibility","visible");

		return false;
	},

	/**
	 * Show a sub context menu attached to a main context menu. Typically used to show
	 * playlist folders available that the user can add a SID file to.
	 * 
	 * @param {*} event 
	 */
	contextSubMenu: function(event) {
		var $panel = $("#panel");
		$("#contextsubmenu").remove();

		if ($(event.target).hasClass("disabled")) return;

		// NOTE: For now, this is HARDWIRED to just show a list of playlist entries.
		var contents = "";
		$.each(this.symlistFolders, function(i, symlist) {
			contents += '<div class="line" data-action="symlist-add">'+symlist['fullname'].substr(1)+
				(symlist['public'] ? ' [PUBLIC]' : '')+'</div>';
		});

		// Create the hidden menu and assume coordinates for going downwards
		$panel.prepend('<div id="contextsubmenu" class="context">'+contents+'</div>');
		var $contextMenu = $("#contextmenu"),
			$contextSubMenu = $("#contextsubmenu");
		$contextSubMenu
			.css("top", $contextMenu.children(".submenu").offset().top)
			.css("left", $contextMenu.offset().left + $contextMenu.outerWidth() - 7);

		// Flip the menu upwards if the bottom of it goes off screen
		// NOTE: Need "visibility:hidden" and not "display:none" for this to work.
		var win = $(window);
		var viewportBottom = win.scrollTop() + win.height();
		var boundsBottom = $contextSubMenu.offset().top + $contextSubMenu.outerHeight();
		if (boundsBottom > viewportBottom)
			$contextSubMenu.css("top", event.pageY - ($contextSubMenu.outerHeight() - $(".context .line").height() + 1));

		// Show the menu
		$contextSubMenu.css("visibility","visible");
	},

	/**
	 * When clicking an item on a (sub) context menu.
	 * 
	 * @param {*} event 
	 */
	onContextClick: function(event) {
		var $target = $(event.target);
		if ($target.hasClass("disabled")) return;
		var action = $target.attr("data-action");

		// Handle choice of factoid
		if (action == "factoid") {
			main.factoidType = $target.html().split(".")[0];
			SelectFactoid(main.factoidType, false);
			return;
		}

		// Handle other context menu options
		switch (action) {
			case "download-file":
				// Stop playing in DeepSID in case an external SID player is going to take over now
				$("#stop").trigger("mouseup");
				SID.stop();
				var symChar = this.path.substr(1, 1);
				// Force the browser to download it using an invisible <iframe>
				$("#download").prop("src", this.ROOT_HVSC + '/' + (this.isSearching || this.isCompoFolder || symChar == "!" || symChar == "$" ? this.contextSID : this.path.substr(1)+"/"+this.contextSID));
				break;
			case 'edit-tags':
				// Just click the "+" button (it may be hidden but it should still react to this)
				this.contextEntry.parents("tr").find(".edit-tags").trigger("click");
				break;
			case 'copy-link':
				var url = window.location.href,
					more = url.indexOf("&") != -1;
				var path = url.indexOf(".sid") != -1 || url.indexOf(".mus") != -1
					? url.substr(0, url.lastIndexOf("/") + 1)
					: (more ? url.substr(0, url.indexOf("&")): url);
				url = path+this.contextSID+(more ? url.substr(url.indexOf("&")) : "");
				url += "&tab=csdb";
				// Copy it to the clipboard
				// @link https://stackoverflow.com/a/30905277/2242348
				var $temp = $("<input>");
				$("body").append($temp);
				$temp.val(url).select();
				document.execCommand("copy"); // Deprecated but there is no replacement yet
				$temp.remove();
				break;
			case 'edit-upload':
				// Clicked the button for editing a public SID file
				if (!$("#logout").length) {
					// But must be logged in to do that
					alert("Login or register and you can edit this SID file.");
					return false;
				}
				// Get a full array of the SID row
				$.get("php/upload_edit.php", {
					fullname:	(this.isSearching || this.path.substr(1, 1) == "!" ||  this.path.substr(1, 1) == "$" ? this.contextSID : this.path.substr(1)+"/"+this.contextSID),
					path:		(this.isSidFmFolder() ? PATH_SID_FM : PATH_UPLOADS)
				}, function(data) {
					this.validateData(data, function(data) {
						// Going back to earlier wizard steps should not be allowed
						$("#dialog-upload-wiz3 .dialog-button-no")
							.prop("disabled", true)
							.removeClass("disabled")
							.addClass("disabled");
						// Set controls to show what was previously accepted
						this.UploadEdit = "Edit";
						this.getProfiles(data, data.info.profile.replace("_High Voltage SID Collection/", "HVSC/"));
						$("#upload-file-name-input").val(data.info.fullname.split("/").slice(-1)[0]);
						$("#upload-file-player-input").val(data.info.player);
						$("#upload-file-author-input").val(data.info.author);
						$("#upload-file-copyright-input").val(data.info.copyright);
						$("#upload-csdb-id").val(data.info.csdbid);
						$("#upload-lengths-list").css("background", "").val(data.info.lengths);
						$("#upload-stil-text").val(data.info.stil);
						this.uploadWizard(2, data);
					});
				}.bind(this));
				break;
			case 'delete-file':
				if ($("#logged-username").text() == "JCH") {
					$("#file-name-delete").empty().append('<b>'+this.contextSID+'</b>');
					CustomDialog({
						id: '#dialog-delete-file',
						text: '<h3>Delete an uploaded file</h3>'+
							'<p>Are you sure you want to delete this SID file?</p>',
						width: 500,
						height: 196,
					}, function() {
						// Please don't try to hack - the PHP file only allows for deleting by an administrator
						$.post("php/upload_delete.php", {
							fullname:	(this.isSidFmFolder() ? PATH_SID_FM : PATH_UPLOADS)+"/"+this.contextSID
						}, function(data) {
							this.validateData(data, function() {
								browser.getFolder();
							});
						}.bind(this));
					}.bind(this));
				}
				break;
			case 'edit-videos':
				this.editYouTubeLinks((this.isSearching || this.isCompoFolder || this.path.substr(1, 1) == "$"
					? this.contextSID
					: this.path.substr(1)+"/"+this.contextSID),
					$target.attr("data-subtunes"));
				break;
			case "symlist-add":
			case "symlist-new":
				// Add the SID file to a symlist (existing or creating with unique version of SID file name)
				$.post("php/symlist_write.php", {
					fullname:	(this.isSearching || this.isCompoFolder || this.path.substr(1, 1) == "$" ? this.contextSID : this.path.substr(1)+"/"+this.contextSID),
					symlist:	(action === "symlist-add" ? (event.target.textContent.indexOf(" [PUBLIC]") !== -1 ? "$" : "!")+event.target.textContent : ''),
					subtune:	(ctrls.subtuneCurrent && this.contextSelected ? ctrls.subtuneCurrent + 1 : 0)
				}, function(data) {
					this.validateData(data, function(data) {
						this.writeName = data.name;
					});
				}.bind(this));
				if (action === "symlist-new" && $("#logout").length) {
					// Offer to let the user rename the new playlist on the fly
					CustomDialog({
						id: '#dialog-playlist-rename',
						text: 'Would you like to rename your new playlist?',
						height: 210,
					}, function() {
						$.post("php/symlist_rename.php", {
							symlist:	this.writeName,
							fullname:	'',
							symid:		0,
							new:		$("#pr-newplname").val(),
						}, function(data) {
							this.validateData(data);
						}.bind(this));
					}.bind(this));
					this.getSymlists();
				}
				break;
			case "symlist-delete":
				// Delete the symlist and all of its entries
				$.post("php/symlist_delete.php", {
					symlist:	this.contextSID
				}, function(data) {
					this.validateData(data, function() {
						this.getFolder();
					});
				}.bind(this));
				break;
			case "symlist-publish":
				if (this.contextSID.substr(0, 1) === "$") return;
				// Publish the playlist so that everyone can see it (and edit it if logged in)
				$.post("php/symlist_publish.php", {
					publish:	1,
					symlist:	this.contextSID
				}, function(data) {
					this.validateData(data, function() {
						this.getFolder();
					});
				}.bind(this));
				break;
			case "symlist-download":
				$.post("php/symlist_download.php", {
					symlist:	this.contextSID
				}, function(data) {
					this.validateData(data, function(data) {
						window.location.href = data.file;
					});
				}.bind(this));
				break;
			case "symlist-unpublish":
				if (this.contextSID.substr(0, 1) === "!") return;
				// Unpublish the playlist so that only the user can see it again (if logged in)
				$.post("php/symlist_publish.php", {
					publish:	0,
					symlist:	this.contextSID
				}, function(data) {
					this.validateData(data, function() {
						this.getFolder();
					});
				}.bind(this));
				break;
			case "symentry-remove":
				// Remove the SID file from the symlist
				$.post("php/symlist_remove.php", {
					fullname:	this.contextSID,		// Fullname of physical SID file
					symlist:	this.path.substr(1),
					symid:		this.contextSymID,
				}, function(data) {
					this.validateData(data, function() {
						this.getFolder();
					});
				}.bind(this));
				break;
			case "symentry-rename":
				this.prevSymName = this.contextEntry.text();
				this.isFileRenamed = this.prevSymName.substr(-4) == ".sid" || this.prevSymName.substr(-4) == ".mus";
				// Create the edit box
				this.contextEntry.empty().append('<input type="text" id="sym-rename"'+
					(this.isFileRenamed ? '' : ' style="position:absolute;top:6px;width:268px;"')+' maxlength="128" value="" />');
				var nameBeingEdited = this.isFileRenamed
					? this.prevSymName.substr(0, this.prevSymName.length - 4)
					: this.prevSymName;
				// Value must be set here for the cursor to be placed in the end
				$renameBox = $("#sym-rename");
				$renameBox.focus().val(nameBeingEdited);
				// @link https://css-tricks.com/snippets/jquery/move-cursor-to-end-of-textarea-or-input/
				if ($renameBox[0].setSelectionRange) {
					var len = $renameBox.val().length * 2; // Opera issue
					setTimeout(function() {
						$renameBox[0].setSelectionRange(len, len);
					}, 1); // Blink wants a timeout
				}
				break;
			case "symentry-subtune":
				var $starField = this.contextEntry.parents("td").next();
				this.prevStars = $starField.html();
				// Create the edit box
				$starField.empty().append(
					'<div id="small-edit">'+
						'<label class="slimfont unselectable" style="margin-right:4px;" for="sym-specify-subtune">Subtune</label>'+
						'<input type="text" id="sym-specify-subtune" maxlength="3" value="" />'+
					'</div>');
				$subtuneBox = $("#sym-specify-subtune");
				var index = $starField.parent("tr").index();
				var subtuneBeingEdited = this.playlist[index].startsubtune + 1;
				this.contextMaxSubtunes = this.playlist[index].subtunes;
				$subtuneBox.focus().val(subtuneBeingEdited);
				if ($subtuneBox[0].setSelectionRange) {
					var len = $subtuneBox.val().length * 2; // Opera issue
					setTimeout(function() {
						$subtuneBox[0].setSelectionRange(len, len);
					}, 1); // Blink wants a timeout
				}
				break;
			default:
				break;
		}
	},

	/**
	 * Edit the YouTube video links for a SID file. If there are multiple
	 * subtunes, a small dialog box first asks for which one.
	 * 
	 * @handlers youtube
	 * 
	 * @param {string} fullname		The SID filename including folders
	 * @param {number} subtunes		Maximum number of subtunes
	 */
	editYouTubeLinks: function(fullname, subtunes) {

		this.subtunes = subtunes;

		// Turn off drop-down box for editing the next subtune
		$("#ev-dd2-checkbox").prop("checked", false);
		$("#ev-dd2-subtune").prop("disabled", true).addClass("disabled");
		$("#ev-dd2 label").addClass("disabled");

		// If there are multiple subtunes then ask which one
		if (subtunes > 1) {

			// Populate the drop-down boxes with subtune choices
			var $dd = $("#ev-dd-subtune,#ev-dd2-subtune");
			$dd.empty();
			for (var subtune = 1; subtune <= subtunes; subtune++)
				$dd.append('<option value="'+(subtune - 1)+'">'+subtune+'</option>');

			CustomDialog({
				id: '#dialog-ev-subtunes',
				text: 'There are multiple subtunes in this file. Which one are you going to edit video links for now?',
				height: 150,
			}, function() {
				var subtune = parseInt($("#ev-dd-subtune").val());
				$("#dialog-edit-videos legend").empty().append("Subtune "+(subtune + 1)+" / "+subtunes);
				$("#ev-dd2-subtune").val(subtune + 1 < subtunes ? subtune + 1 : 0);
				$("#ev-dd2-checkbox").prop("disabled", false);
				this.mainEditYouTube(fullname, subtune, true);
			}.bind(this));
		} else {
			$("#dialog-edit-videos legend").empty().append("Song");
			$("#ev-dd2-checkbox").prop("disabled", true);
			this.mainEditYouTube(fullname, 0, false); // No subtunes
		}
	},

	/**
	 * Show the main dialog box for editing YouTube video links.
	 * 
	 * @handlers youtube
	 * 
	 * @param {string} fullname		The SID filename including folders
	 * @param {number} subtune		The subtune involved
	 * @param {boolean} noFade		If true, the dialog cover will not be faded
	 */ 
	mainEditYouTube: function(fullname, subtune, noFade) {

		// First clean up after the last party
		$("#dialog-edit-videos input:checkbox,#dialog-edit-videos input:radio").prop("checked", false);
		$("#dialog-edit-videos input:radio,#dialog-edit-videos input:text").prop("disabled", true);
		$("#dialog-edit-videos input:text").val("");
		$("#dialog-edit-videos fieldset button").removeClass("disabled").addClass("disabled");

		if (this.youTubeGetInfo) this.youTubeGetInfo.abort();

		this.youTubeGetInfo = $.get("php/youtube.php", {
			fullname:	fullname,
			subtune:	subtune,
		}, function(data) {
			browser.validateData(data, function(data) {
				if (data.count) {
					// Apply the existing data in the rows
					$.each(data.videos, function(i, video) {
						var row = parseInt(i) + 1;
						$("#ev-rb-"+row+",#ev-tb-cn-"+row+",#ev-tb-vi-"+row).prop("disabled", false);
						$("#ev-se-"+row+",#ev-up-"+row+",#ev-dn-"+row).removeClass("disabled");

						$("#ev-cb-"+row).prop("checked", true);
						if (video.tab_default == 1) $("#ev-rb-"+row).prop("checked", true);

						$("#ev-tb-cn-"+row).val(video.channel);
						$("#ev-tb-vi-"+row).val(video.video_id);
					});
				} else {
					// Starting afresh; enable four rows with common suggestions
					$("#ev-cb-1,#ev-rb-1,#ev-cb-2,#ev-cb-3,#ev-cb-4").prop("checked", true);
					$("#ev-rb-1,#ev-tb-cn-1,#ev-tb-vi-1,#ev-rb-2,#ev-tb-cn-2,#ev-tb-vi-2,#ev-rb-3,#ev-tb-cn-3,#ev-tb-vi-3,#ev-rb-4,#ev-tb-cn-4,#ev-tb-vi-4").prop("disabled", false);
					$("#ev-se-1,#ev-up-1,#ev-dn-1,#ev-se-2,#ev-up-2,#ev-dn-2,#ev-se-3,#ev-up-3,#ev-dn-3,#ev-se-4,#ev-up-4,#ev-dn-4").removeClass("disabled");
					$("#ev-tb-cn-1").val("Unepic").focus();
					$("#ev-tb-cn-2").val("demoscenes");
					$("#ev-tb-cn-3").val("acrouzet");
					$("#ev-tb-cn-4").val("EverythingSid");
				}
			}.bind(this));
		}.bind(this));

		// Get the author folder name if present
		var author = fullname.indexOf("/MUSICIANS/") !== -1
			? this.path.split("/").slice(-1)[0]
			: "";

		CustomDialog({
			id: '#dialog-edit-videos',
			text: '<h3>Edit video links</h3><p id="ev-sid" data-author="'+author+'">'+fullname.split("/").slice(-1)[0]+'</p>',
			width: 600,
			height: 362,
			wizard: noFade,
		}, function() {
			// SAVE was clicked; make all the video link changes
			var rbVisible = false;
			arrayYouTube = [];
			for (var row = 1; row <= 5; row++) {
				if ($("#ev-cb-"+row).is(":checked") && $("#ev-tb-vi-"+row).val() != "") {
					// This is an enabled row so append its data to the array
					var rbOn = $("#ev-rb-"+row).is(":checked");
					if (rbOn) rbVisible = true;
					arrayYouTube.push({
						channel:		$("#ev-tb-cn-"+row).val(),
						video_id:		$("#ev-tb-vi-"+row).val(),
						tab_default:	(rbOn ? 1 : 0),
					});
				}
			}

			// If the radio button is missing at this point just put it on the first one
			if (!rbVisible && arrayYouTube.length > 0)
				arrayYouTube[0]["tab_default"] = 1;

			$.post("php/youtube_write.php", {
				fullname:	fullname,
				subtune:	subtune,
				videos:		(arrayYouTube.length ? arrayYouTube : 0),
			}, function(data) {
				browser.validateData(data, function(data) {
					// Assume all video links were deleted to begin with
					this.contextTR.removeClass("selected disabled").addClass("disabled");
					for (var r = 1; r <= 5; r++) {
						if ($("#ev-cb-"+r).is(":checked") && $("#ev-tb-vi-"+r).val() != "") {
							// Something was applied in the dialog box so enable the SID row
							this.contextTR.removeClass("disabled").addClass("selected");
							break;
						}
					}

					// Do the next subtune if requested in the bottom of the dialog box
					if ($("#ev-dd2-checkbox").is(":checked")) {
						var subtune = parseInt($("#ev-dd2-subtune").val());
						$("#dialog-edit-videos legend").empty().append("Subtune "+(subtune + 1)+" / "+this.subtunes);
						$("#ev-dd2-subtune").val(subtune + 1 < this.subtunes ? subtune + 1 : 0);
						$("#ev-dd2-checkbox").prop("disabled", false);
						this.mainEditYouTube(fullname, subtune, true);
					}
				});
			}.bind(this));
		});
	},

	/**
	 * When clicking an item in the dialog box for editing YouTube video links.
	 * 
	 * @handlers youtube
	 * 
	 * @param {*} event 
	 */
	 onYouTubeLinksClick: function(event) {
		var $target = $(event.target);
		if ($target.hasClass("disabled")) return;

		// Information needed for search buttons and links
		var $evSid = $("#ev-sid");
		var author = $evSid.attr("data-author").replace(/[_.]/g, " "),
			name = $evSid.text().replace(/[_.]/g, " ");
		var nameNoSid = name.substr(0, name.length - 4);

		if (event.target.id == "ev-corner-link") {
			// Open multiple web browser tabs
			window.open("https://www.youtube.com/results?search_query="+encodeURIComponent(author+" "+name.substr(0, name.length - 4), "_blank"));
			window.open("https://www.youtube.com/channel/UCDbAWy2ArsTKso-A0sFv_hA/search?query="+encodeURIComponent(author+" "+nameNoSid, "_blank"));
			window.open("https://www.youtube.com/c/acrouzet/search?query="+encodeURIComponent(author+" "+nameNoSid, "_blank"));
			window.open("https://www.youtube.com/user/demoscenes/search?query="+encodeURIComponent(author+" "+nameNoSid, "_blank"));
			window.open("https://www.youtube.com/c/UnepicStonedHighSIDCollection/search?query="+encodeURIComponent(author+" "+nameNoSid, "_blank"));
			return false;
		} else if (event.target.id == "ev-dd2-checkbox") {
			if ($target.is(":checked")) {
				// Turn on drop-down box for editing the next subtune
				$("#ev-dd2-subtune").prop("disabled", false).removeClass("disabled");
				$("#ev-dd2 label").removeClass("disabled");
			} else {
				// Turn off drop-down box for editing the next subtune
				$("#ev-dd2-subtune").prop("disabled", true).addClass("disabled");
				$("#ev-dd2 label").addClass("disabled");
			}
		}

		// Last character of all ID names is always the row index
		var rowIndex = parseInt(event.target.id.substr(-1));

		switch (event.target.type) {
			case "checkbox":
				if ($target.is(":checked")) {
					// Switch the row of controls ON
					$("#ev-rb-"+rowIndex+",#ev-tb-cn-"+rowIndex+",#ev-tb-vi-"+rowIndex).prop("disabled", false);
					$("#ev-se-"+rowIndex+",#ev-up-"+rowIndex+",#ev-dn-"+rowIndex).removeClass("disabled");
					// Check the radio button if it was orphaned
					if (!$("#ev-rb-1").is(":checked") && !$("#ev-rb-2").is(":checked") && !$("#ev-rb-3").is(":checked") && !$("#ev-rb-4").is(":checked") && !$("#ev-rb-5").is(":checked"))
						$("#ev-rb-"+rowIndex).prop("checked", true);
				} else {
					// Switch the row of controls OFF
					$("#ev-rb-"+rowIndex+",#ev-tb-cn-"+rowIndex+",#ev-tb-vi-"+rowIndex).prop("disabled", true);
					$("#ev-se-"+rowIndex+",#ev-up-"+rowIndex+",#ev-dn-"+rowIndex).addClass("disabled");
					// Find a new home for the radio button if it was checked there
					$("#ev-rb-"+rowIndex).prop("checked", false);
					for (var r = 1; r <= 5; r++) {
						if (!$("#ev-rb-"+r).is(":disabled")) {
							$("#ev-rb-"+r).prop("checked", true);
							break;
						}
					}
				}
				break;
			case "button":
				switch (event.target.id.substr(0, 5)) {
					case "ev-up":
						// Move the row UP
						if (rowIndex == 1) break;
						this.moveVideoLinkRow(rowIndex, rowIndex - 1);
						break;
					case "ev-dn":
						// Move the row DOWN
						if (rowIndex == 5) break;
						this.moveVideoLinkRow(rowIndex, rowIndex + 1);
						break;
					case "ev-se":
						var channel = $("#ev-tb-cn-"+rowIndex).val();
						// Search for both channel, SID author, and SID name in YouTube (in a new browser tab)
						window.open("https://www.youtube.com/results?search_query="+encodeURIComponent(author+" "+name+" "+channel), "_blank");
						break;
					default:
						break;
				}
				break;
			default:
				break;
		}
	 },

	/**
	 * Move the video link row in the dialog box up or down.
	 * 
	 * @handlers youtube
	 * 
	 * @param {number} indexSource
	 * @param {number} indexDest
	 */
	moveVideoLinkRow: function(indexSource, indexDest) {
		var cbChecked = $("#ev-cb-"+indexSource).is(":checked"),
			rbChecked = $("#ev-rb-"+indexSource).is(":checked"),
			cnText = $("#ev-tb-cn-"+indexSource).val(),
			viText = $("#ev-tb-vi-"+indexSource).val();

		$("#ev-cb-"+indexSource).prop("checked", $("#ev-cb-"+indexDest).is(":checked"));
		$("#ev-rb-"+indexSource).prop("checked", $("#ev-rb-"+indexDest).is(":checked"))
			.prop("disabled", $("#ev-rb-"+indexDest).is(":disabled"));
		$("#ev-tb-cn-"+indexSource).val($("#ev-tb-cn-"+indexDest).val())
			.prop("disabled", $("#ev-tb-cn-"+indexDest).is(":disabled"));
		$("#ev-tb-vi-"+indexSource).val($("#ev-tb-vi-"+indexDest).val())
			.prop("disabled", $("#ev-tb-vi-"+indexDest).is(":disabled"));
		if ($("#ev-se-"+indexDest).hasClass("disabled"))
			$("#ev-se-"+indexSource+",#ev-up-"+indexSource+",#ev-dn-"+indexSource).addClass("disabled");

		$("#ev-cb-"+indexDest).prop("checked", cbChecked);
		$("#ev-rb-"+indexDest).prop("checked", rbChecked).prop("disabled", false);
		$("#ev-tb-cn-"+indexDest).val(cnText).prop("disabled", false);
		$("#ev-tb-vi-"+indexDest).val(viText).prop("disabled", false);
		$("#ev-se-"+indexDest+",#ev-up-"+indexDest+",#ev-dn-"+indexDest).removeClass("disabled");
	},

	/**
	 * Restore the original display name of the file or folder, or the rating stars.
	 * Used when no longer using an edit box on a SID file or folder row.
	 */
	restoreSIDRow: function() {
		if ($("#sym-rename").length) {
			this.contextEntry.empty().append(this.prevSymName);
			$("#sym-rename").remove();
		} else if ($("#sym-specify-subtune").length) {
			this.contextEntry.parents("td").next().empty().append(this.prevStars);
			$("#sym-specify-subtune").remove();
		}
	},

	/**
	 * Shorten a SID filename by abbreviating long collection names.
	 * 
	 * @param {string} name		The original SID filename
	 * @param {boolean} raw		TRUE to use raw HVSC/CGSC/ESTC collection names
	 * 
	 * @return {string}			The shortened SID filename
	 */
	adaptBrowserName: function(name, raw) {
		underscore = typeof raw !== "undefined" ? "_" : "";
		return this.path === "" && !this.isSearching ? name : name
			.replace(underscore+"High Voltage SID Collection", '<font class="dim">HVSC</font>')
			.replace("HVSC</font>/DEMOS", "HVSC/D</font>")
			.replace("HVSC</font>/GAMES", "HVSC/G</font>")
			.replace("HVSC</font>/MUSICIANS", "HVSC/M</font>")
			.replace("HVSC</font>/GROUPS", "HVSC/G</font>")
			.replace(underscore+"Compute's Gazette SID Collection", '<font class="dim">CGSC</font>')
			.replace(underscore+"Exotic SID Tunes Collection", '<font class="dim">ESTC</font>');
	},

	/**
	 * Handle any errors after returning from an AJAX call.
	 * 
	 * @param {object} data			The data returned from the PHP script
	 * @param {function} callback	Function to call if no errors
	 * 
	 * @return {boolean}			TRUE if no errors
	 */
	validateData: function(data, callback) {
		try {
			data = $.parseJSON(data);
		} catch(e) {
			if (document.location.hostname == "chordian")
				if (data == "")
					alert(e);
				else
					$("body").empty().append(data);
			else
				alert("An error occurred. If it keeps popping up please tell me about it: chordian@gmail.com");
			return false;
		}
		if (data.status == "error") {
			alert(data.message);
			return false;
		} else {
			if (typeof callback === "function")
				callback.call(this, data);
			return true;
		}
	},

	/**
	 * Collect tags for all files and present them in the relevant sundry tab.
	 * 
	 * @param {string} tags		List of tags from a previous collection to be shown now
	 * 
	 * @return {string}			Tags collected this time
	 */
	showFolderTags: function(tags) {
		var allTags = tags;
		$("#slider-button").hide();
		this.sliderButton = false;
		if (typeof tags == "undefined" || this.cache.folderTags == "0") {
			var tagType = {
				event:			"",
				production:		"",
				origin:			"",
				suborigin:		"",
				mixorigin:		"",
				digi:			"",
				subdigi:		"",
				remix64:		"",
				gamebase64:		"",
				other:			"",
				warning:		"",
				transparent:	"", // The arrow tag icons are not included below
			};

			$.each(browser.playlist, function(i, file) {
				// Parse each DIV with one tag each							
				$(file.tags).each(function() {
					if (this.className.indexOf("tag-") != -1) {
						var typeName = this.className.split(" ")[1].substr(4);
						if (tagType[typeName].indexOf(">"+this.innerHTML+"<") == -1)
							tagType[typeName] += this.outerHTML; // No duplicates
					}
				});
			});
			allTags =
				tagType.event+
				tagType.origin+
				tagType.suborigin+
				tagType.mixorigin+
				tagType.production+
				tagType.digi+
				tagType.subdigi+
				tagType.remix64+
				tagType.gamebase64+
				tagType.other+
				tagType.warning;
		}
		ctrls.updateSundryTags(allTags);
		return allTags;
	},

	/**
	 * Run through all SID rows and add underlining horizontal "bracket" lines
	 * from start to end tags, thereby showing their mutual connection.
	 * 
	 * @param {element} optionalTdElement		If specified, only update this SID row
	 */
	showTagsBrackets: function(optionalTdElement) {

		var $lines;

		if (optionalTdElement) {
			const $row = $(optionalTdElement).closest('tr');
			if ($row.length === 0) return;

			$lines = $row.find('.tags-line');

			// They may have been changed when editing the tags
			$lines.eq(0).attr("data-tag-start-id", this.startTag);
			$lines.eq(0).attr("data-tag-end-id", this.endTag);

		} else {
			$lines = $('#songs .tags-line');
		}

		$lines.each(function() {
			const $line = $(this);
			const startID = $line.attr('data-tag-start-id');
			const endID = $line.attr('data-tag-end-id');

			const $startTag = $line.find(`.tag[data-id="${startID}"]`);
			const $endTag = $line.find(`.tag[data-id="${endID}"]`);

			if ($startTag.length && $endTag.length) {
				const offsetA = $startTag.position().left + 6 + $startTag.outerWidth() / 2;
				const offsetB = $endTag.position().left + 8 + $endTag.outerWidth() / 2;

				const left = Math.min(offsetA, offsetB);
				const width = Math.abs(offsetB - offsetA);

				const $indicator = $line.find('.tags-bracket');
				$indicator.css({
					left: `${left}px`,
					width: `${width}px`,
					display: 'block'
				});
			} else {
				$line.find('.tags-bracket').hide(); // Hide if one is missing
			}			
		});
	},

	/**
	 * Prepare a loading SVG spinner for showing if a page takes time to load.
	 * 
	 * @param {string} id		CSS ID name
	 * 
	 * @return {string}			The HTML string with the SVG image
	 */
	loadingSpinner: function(id) {
		return '<div style="height:400px;"><img id="loading-'+id+'" class="loading-spinner" src="images/loading.svg" style="display:none;" alt="" /></div>';
	},

	/**
	 * Are we inside the High Voltage SID Collection?
	 * 
	 * @return {boolean}
	 */
	isHVSC: function() {
		return this.path.indexOf("_High Voltage SID Collection") !== -1;
	},
	
	/**
	 * Are we located in a CGSC folder, or at least playing a MUS file?
	 * 
	 * @return {boolean}
	 */
	isCGSC: function() {
		return (!this.isSearching && this.path.indexOf("_Compute's Gazette SID Collection") !== -1) ||
			(typeof this.playlist[this.songPos] !== "undefined" &&
				this.playlist[this.songPos].fullname.indexOf("_Compute's Gazette SID Collection") !== -1);
	},

	/**
	 * Are we inside a letter folder in the 'MUSICIANS' folder in HVSC?
	 * 
	 * @return {boolean}
	 */
	isMusiciansLetterFolder: function() {
		return this.path.indexOf("_High Voltage SID Collection/MUSICIANS") !== -1 &&
			this.path.split("/").length === 4;
	},

	/**
	 * Are we inside the big CSDb music competitions folder?
	 * 
	 * @return {boolean}
	 */
	isBigCompoFolder: function() {
		return this.path == "/CSDb Music Competitions";
	},

	/**
	 * Are we inside the 'SID Happens' upload folder?
	 * 
	 * @return {boolean}
	 */
	isUploadFolder: function() {
		return this.path.indexOf("/"+PATH_UPLOADS) !== -1;
	},

	/**
	 * Are we inside the 'SID+FM' upload folder?
	 * 
	 * @return {boolean}
	 */
	isSidFmFolder: function() {
		return this.path.indexOf("/"+PATH_UPLOADS+"/SID+FM") !== -1;
	},
	
	/**
	 * Is this a temporary test SID file thus is has no entries in the database?
	 * 
	 * @return {boolean}
	 */
	isTempTestFile: function() {
		return typeof this.playlist[this.songPos] !== "undefined"
			? this.playlist[this.songPos].fullname.indexOf("temp/test/") !== -1
			: false;
	},

	/**
	 * Is a list of temporary SID files currently present?
	 * 
	 * @return {boolean}
	 */
	isTempFolder: function() {
		return $("#songs td.temp").length;
	},

	/**
	 * Update the tags directly in the SID row. Also updates arrays.
	 * 
	 * @param {object} $selected		The DOM object with the <TD> SID row
	 * @param {string} list_of_tags		HTML list of tags
	 * @param {string} endName			The SID name without prepended path
	 */
	updateStickyTags: function($selected, list_of_tags, endName) {
	 
		// Make the tags sticky without refreshing the page
		$selected.find(".tags-line").empty().append(TAGS_BRACKET+list_of_tags);
		this.showTagsBrackets($selected);

		// Update the playlist array
		$.each(this.playlist, function(i, file) {
			if (file.filename == endName) {
				file.tags = list_of_tags;
				return false;
			}
		});

		if (browser.isBigCompoFolder()) {
			// Update the compolist arrays
			$.each([this.compolist, this.cache.compolist], function() {
				$.each(this, function(i, file) {
					if (file.foldername == endName) {
						file.tags = list_of_tags;
						return false;
					}
				});
			});
		}
	},

	/**
	 * Update the two list boxes in the dialog box for editing tags.
	 * 
	 * @param {array} arrAll		Associative array with ID's, names, and types
	 * @param {array} arrSong		Standard array with ID's used by file
	 */
	updateTagLists: function(arrAll, arrSong) {
		var allTags = songTags = "";

		$.each(arrSong, function(i, tagID) {
			var tagName = $.grep(arrAll, function(entry) {
				return entry.id == tagID;
			})[0];
			songTags += '<option value="'+tagID+'">'+tagName.name+'</option>';
		});
		$("#dialog-song-tags").empty().append(songTags);

		$.each(arrAll, function(i, tag) {
			if (arrSong.indexOf(parseInt(tag.id)) == -1) // Exclude those also chosen
				allTags += '<option value="'+tag.id+'">'+tag.name+'</option>';
		});
		$("#dialog-all-tags").empty().append(allTags);
	},

	/**
	 * Update the two list boxes in the dialog box for connecting two tags.
	 * 
	 * @param {array} arrAll		Associative array with ID's, names, and types
	 * @param {array} arrSong		Standard array with ID's used by file
	 */
	updateConnectTagLists: function(arrAll, arrSong) {
		var songTags = "",
			$startTag = $("#dialog-list-start-tag"),
			$endTag = $("#dialog-list-end-tag");

		// Get the current (i.e. old) tag selections
		var oldStart = $startTag.find("option:selected").val(),
			oldEnd = $endTag.find("option:selected").val();

		$.each(arrSong, function(i, tagID) {
			var tagName = $.grep(arrAll, function(entry) {
				return entry.id == tagID;
			})[0];
			songTags += '<option value="'+tagID+'">'+tagName.name+'</option>';
		});

		$("#dialog-list-start-tag,#dialog-list-end-tag").empty().append(
			'<option value="0">Not selected</option>'+songTags);

		// Try to select the previous selections again (if still there)
		$startTag.find(`option[value="${oldStart}"]`).length
			? $startTag.val(oldStart)
			: $startTag.val(0);
		$endTag.find(`option[value="${oldEnd}"]`).length
			? $endTag.val(oldEnd)
			: $endTag.val(0);
	},

	/**
	 * Moves the [smooth] keyboard selection to the specified row.
	 * 
	 * Ignoring scrolling into view can be important in some situations; for
	 * example when refreshing the site with a SID file in the URL. This
	 * performs its own scrolling to the SID row.
	 * 
	 * @param {int} row						Index of the current song row (move marker to it)
	 * @param {boolean} moveSmoothly		Optional; True = Smoothly; False = Instantly
	 * @param {boolean} scrollIntoView		Optional; False = Ignore scrolling into view
	 */
	moveKeyboardSelection: function(row, moveSmoothly, scrollIntoView) {
		if (isMobile) return;

		const $kb = $("#kb-marker");
		const $rows = $("#folders tr");
		const $targetRow = $rows.eq(row);

		if (!$targetRow.length || !$targetRow.position()) return;

		if (typeof moveSmoothly === "undefined") moveSmoothly = true;
		moveSmoothly ? $kb.show() : $kb.hide();

		// Move the keyboard marker
		$kb.css({
			top: $targetRow.position().top,
			height: $targetRow.outerHeight()
		});

		if (scrollIntoView !== false) {
			$targetRow[0].scrollIntoView({
				behavior: 'auto',
				block: 'nearest'
			});
		}

		setTimeout(() => {
			$kb.show();
		}, 250);
	},

	/**
	 * Select the first viable item for keyboard selection, i.e. ignores if
	 * this is a spacer or a divider.
	 */
	moveKeyboardToFirst: function() {
		if (isMobile) return;

		var $tr = $("#songs tr");
		for (var i = 0; i < $tr.length; i++) {
			if (!$tr.eq(i).hasClass("disabled")) {
				this.kbSelectedRow = i;
				this.moveKeyboardSelection(i, false);
				break;
			}
		}
	},

	/**
	 * Empty and then refill the contextual SORT/FILTER drop-down box.
	 * 
	 * @return {string}		Currently selected item (FILTER only)
	 */
	setupSortBox: function() {
		var stickyMode = null;
		if (!this.isSearching && this.path === "") {
			// Sort box becomes a filter box in the root
			stickyMode = localStorage.getItem("personal");
			if (stickyMode == null)
				stickyMode = "all";
			else if (stickyMode == "personal")
				stickyMode = "personal";
			$("#dropdown-sort").empty().append(
				'<option value="all">All</option>'+
				'<option value="common">Common</option>'+
				'<option value="personal">Personal</option>'
			).val(stickyMode);
		} else if (!this.isSearching && this.isMusiciansLetterFolder()) {
			// Sort box becomes a filter box when inside letter folders in MUSICIANS
			$("#dropdown-sort").empty().append(
				'<option value="all">All</option>'+
				'<option value="decent">Decent</option>'+
				'<option value="good">Good</option>'
			).val("all");
			stickyMode = localStorage.getItem("letter");
			if (stickyMode == null)
				stickyMode = "all";
			else if (stickyMode == "decent" || stickyMode == "good")
				setTimeout(function() {
					$("#dropdown-sort").val(stickyMode).trigger("change");
				}, 1);
		} else if (!this.isSearching && this.isBigCompoFolder()) {
			// Sort box for the big CSDb music competitions folder
			$("#dropdown-sort").empty().append(
				'<option value="name">Name</option>'+
				'<option value="rating">Rating</option>'+
				'<option value="oldest">Oldest</option>'+
				'<option value="newest">Newest</option>'+
				'<option value="type">Type</option>'+
				'<option value="country">Country</option>'+
				'<option value="amount">Amount</option>'
			).val(this.cache.composort);
			stickyMode = this.cache.composort;
		} else if (!this.isSearching && this.isUploadFolder()) {
			// Sort box for 'SID Happens' folder
			$("#dropdown-sort").empty().append(
				'<option value="name">Name</option>'+
				'<option value="rating">Rating</option>'+
				'<option value="oldest">Oldest</option>'+
				'<option value="newest">Newest</option>'+
				'<option value="shuffle">Shuffle</option>'
			).val("newest");
		} else {
			// The option is set by 'getFolder()' after doing its own sorting of files
			$("#dropdown-sort").empty().append(
				'<option value="name">Name</option>'+
				'<option value="player">Player</option>'+
				'<option value="rating">Rating</option>'+
				'<option value="oldest">Oldest</option>'+
				'<option value="newest">Newest</option>'+
				'<option value="factoid">Factoid</option>'+
				'<option value="shuffle">Shuffle</option>'
			);
			// Sort box for everything else
			stickyMode = localStorage.getItem("sort");
		}
		return stickyMode;
	},

	/**
	 * Ask to upload a SID file (this is sort of wizard step 0).
	 */
	onUpload: function() {
		$("#dialog-upload-wiz3 .dialog-button-no").prop("disabled", false).removeClass("disabled");

		this.sidFile = new FormData();
		this.sidFile.append(0, $("#upload-new")[0].files[0]); // Only a single SID file at a time
		this.sidFile.append("path", this.isSidFmFolder() ? PATH_SID_FM : PATH_UPLOADS);

		$.ajax({
			url:			"php/upload_new.php",
			type: 			"POST",
			cache:			false,
			processData:	false,
			contentType:	false,
			data:			this.sidFile,
			success: function(data) {
				browser.validateData(data, function() {
					data = $.parseJSON(data);
					this.uploadWizard(1, data);
				});
			}
		});
	},

	/**
	 * Fill the drop-down box with profile paths with options.
	 * 
	 * @param {array} data		Array with SID file information
	 * @param {string} value	Optional option value to set
	 */
	getProfiles: function(data, value) {
		$("#dropdown-upload-profile").empty();
		this.profileValue = typeof value == "undefined" ? "unset" : value;
		var profiles = '<option value="unset">Not connected to a profile page yet</option>';

		$.get("php/upload_get_profiles.php", {
			active: 1
		}, function(data) {
			this.validateData(data, function(data) {

				profiles += '<optgroup label="Most common">';
				for (var i = 0; i < data.profiles.length; i++)
					profiles += '<option value="'+data.profiles[i]['fullname']+'" data-author="'+data.profiles[i]['author']+'">'+data.profiles[i]['fullname'].replace("HVSC/MUSICIANS/", "")+'</option>';
				profiles += '</optgroup>';

				$.get("php/upload_get_profiles.php", {
					active: 0
				}, function(data) {
					this.validateData(data, function(data) {
						profiles += '<optgroup label="All musicians">';
						for (var i = 0; i < data.profiles.length; i++)
							profiles += '<option value="'+data.profiles[i]['fullname']+'" data-author="'+data.profiles[i]['author']+'">'+data.profiles[i]['fullname'].replace("HVSC/MUSICIANS/", "")+'</option>';
						profiles += '</optgroup>';

						// NOTE: Don't use the styled drop-down box; it is too slow to handle a list this big.
						$("#dropdown-upload-profile").append(profiles).val(this.profileValue);
					});
				}.bind(this));

			});
		}.bind(this));
	},

	/**
	 * Show the wizard dialog box for uploading a new SID file.
	 * 
	 * @param {number} step		Wizard step to be shown
	 * @param {array} data		Array with SID file information
	 */
	uploadWizard: function(step, data) {
		if (typeof step == "undefined") {
			this.wizardContinued = false;
			$("#upload-new").trigger("click"); // Calls 'onUpload()' above
			return;
		}
		switch (step) {
			case 1:
				// Present the SID file format information
				CustomDialog({
					id: '#dialog-upload-wiz2',
					text: '<h3>Upload SID File Wizard</h3><div class="top-right-corner">1/4</div><p>The SID file contains the following information:</p>'+
						'<table class="sid-info">'+
							'<tr><td>Type</td><td>'+data.info.type+' v'+data.info.version+'</td></tr>'+
							'<tr><td>Clock</td><td>'+data.info.clockspeed+'</td></tr>'+
							'<tr><td>SID Model</td><td>'+data.info.sidmodel+'</td></tr>'+
							'<tr><td>Name</td><td>'+data.info.name+'</td></tr>'+
							'<tr><td>Author</td><td>'+data.info.author+'</td></tr>'+
							'<tr><td>Copyright</td><td>'+data.info.copyright+'</td></tr>'+
							'<tr><td>Subtune</td><td>'+data.info.startsubtune+' / '+data.info.subtunes+'</td></tr>'+
						'</table>'+
						'<p>If you wish to edit the SID file itself, cancel and use a SID tool to do so, then upload again.</p>',
					height: 378,
					wizard: this.wizardContinued,
				}, function() {
					if (!this.wizardContinued) {
						// First time continuing to next wizard step so set these things up
						this.UploadEdit = "Upload";
						this.getProfiles(data);
						$("#upload-csdb-id").val("0");
						$("#upload-lengths-list")
							.css("background", "")
							.val("5:00 ".repeat(data.info.subtunes).trim());
						$("#upload-stil-text").val("");
						$("#upload-file-name-input").val(data.info.filename);
						$("#upload-file-player-input").val(data.info.player);
						// Try to move appended year to the beginning instead
						var copyright = data.info.copyright;
						var parts = copyright.split(" ");
						var endWord = parts[parts.length - 1];
						if (!isNaN(endWord) && endWord.length == 4 && (endWord.substr(0, 2) == "19" || endWord.substr(0, 2) == "20"))
							// Year is in the end; move it to the beginning
							copyright = endWord+" "+parts.slice(0, -1).join(" ");
						else if ((parts[0].match(/-/g) || []).length == 2) {
							var numbers = parts[0].split("-");
							endWord = numbers[numbers.length - 1];
							if (!isNaN(endWord) && endWord.length == 4 && (endWord.substr(0, 2) == "19" || endWord.substr(0, 2) == "20"))
								// It starts with the MM-DD-YYYY format (Eric Dobek); move the year to the beginning
								copyright = endWord+"-"+numbers[0]+"-"+numbers[1]+" "+parts.slice(1).join(" ");
						}
						$("#upload-file-copyright-input").val(copyright);
					}
					browser.uploadWizard(2, data);
				}.bind(this), function() {
					// Go back to the wizard step where the file itself is selected
					browser.uploadWizard();
				});
				break;
			case 2:
				// Edit composer profile, CSDb ID and lengths
				this.wizardContinued = true;
				if (data.info.subtunes > 1) {
					$("#label-lengths").empty().append("Define <b>lengths</b> of tunes:");
					$("#span-lengths").empty().append("lengths then just leave them");
				} else {
					$("#label-lengths").empty().append("Define the <b>length</b> of the tune:");
					$("#span-lengths").empty().append("length then just leave it");
				}
				CustomDialog({
					id: '#dialog-upload-wiz3',
					text: '<h3><span class="upload-edit">'+this.UploadEdit+'</span> SID File Wizard</h3><div class="top-right-corner">2/4</div><p>You can optionally connect a profile, a CSDb page, and edit the song length'+(data.info.subtunes > 1 ? 's' : '')+'.</p>',
					height: 378,
					wizard: true,
				}, function() {
					// Validate that the lengths are correct (don't let the user leave if they are not)
					var lengths = $("#upload-lengths-list").val().split(" "), nextStep = 3;
					var correctCount = lengths.length == data.info.subtunes;
					$.each(lengths, function(i, length) {
						// Check that the format of minutes and seconds are acceptable
						if (!/(\d+):(\d\d\s)/.test(length+" ") || length.split(":")[0].length > 2 || length.split(":")[1] > 59 || !correctCount) {
							var $lengths = $("#upload-lengths-list");
							// Flash the edit box red a few times indicating a fix is required
							$lengths.css({
								border:		"#800",
								background:	"#f88",
							});
							setTimeout(function() {
								$lengths.css({
									border: 	"",
									background:	"",
								});
								setTimeout(function() {
									$lengths.css({
										border:		"#800",
										background:	"#f88",
									});
									setTimeout(function() {
										$lengths.css({
											border: 	"",
											background:	"",
										});
									}, 150);
								}, 100);
							}, 150);
							nextStep = 2;
							return false;
						}
					});
					browser.uploadWizard(nextStep, data);
				}, function() {
					// Go back to the wizard step with the SID format info
					browser.uploadWizard(1, data);
				});
				break;
			case 3:
				// Edit filename, player, author and copyright in database
				var author = $("#dropdown-upload-profile").find("option:selected").attr("data-author");
				if (author == "" || typeof author == "undefined") author = data.info.author;
				$("#upload-file-author-input").val(author);
				CustomDialog({
					id: '#dialog-upload-wiz4',
					text: '<h3><span class="upload-edit">'+this.UploadEdit+'</span> SID File Wizard</h3><div class="top-right-corner">3/4</div><p>You can optionally rename the file and edit the info that goes into the database.</p>',
					height: 378,
					wizard: true,
				}, function() {
					// Make sure the extension is still there
					browser.uploadWizard(4, data);
				}, function() {
					// Go back to the wizard step with the SID format info
					browser.uploadWizard(2, data);
				});
				break;
			case 4:
				// Edit custom STIL box text
				CustomDialog({
					id: '#dialog-upload-wiz5',
					text: '<h3><span class="upload-edit">'+this.UploadEdit+'</span> SID File Wizard</h3><div class="top-right-corner">4/4</div><p>You can optionally write a custom text entry for the STIL tabs too. HTML tags are allowed.</p>',
					height: 378,
					wizard: true,
				}, function() {
					// Wizard is closed; add the new file and its database entry
					data.info.newname	= $("#upload-file-name-input").val();
					data.info.player	= $("#upload-file-player-input").val();
					data.info.author	= $("#upload-file-author-input").val();
					data.info.copyright	= $("#upload-file-copyright-input").val();
					data.info.profile	= $("#dropdown-upload-profile").val();
					data.info.csdbid	= parseInt($("#upload-csdb-id").val());
					data.info.lengths	= $("#upload-lengths-list").val();
					data.info.stil		= $("#upload-stil-text").val();
					// The PHP script figures out on its own whether to edit or upload
					$.post("php/upload_final.php", {
						info: data.info,
						path: (this.isSidFmFolder() ? PATH_SID_FM : PATH_UPLOADS)
					}, function(data) {
						this.validateData(data, function() {
							this.getFolder();
						});
					}.bind(this));
				}.bind(this), function() {
					// Go back to the wizard step with the database info
					browser.uploadWizard(3, data);
				});
				break;
			}
	},
}