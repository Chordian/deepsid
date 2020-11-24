
/**
 * DeepSID / Browser
 */

 function Browser() {

	this.ROOT_HVSC = 'hvsc';
	this.HVSC_VERSION = 73;
	this.CGSC_VERSION = 139;

	this.path = "";
	this.search = "";
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

	this.currentScrollPos = 0;
	this.scrollPositions = [];

	this.secondsLength = 0;
	this.chips = 1;

	this.isMobile = $("body").attr("data-mobile") !== "0";

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
			.on("click", "button,tr", this.onClick.bind(this))
			.on("mouseover", "tr", this.onMouseOver.bind(this))
			.on("mouseleave", "tr", this.onMouseLeave.bind(this))
		$("#dialog-tags").on("click", "button", this.onClickDialogBox.bind(this));
		$("#dropdown-sort").change(this.onChange.bind(this));
		$("#topic-csdb").on("change", "#dropdown-sort-csdb", this.onChangeCSDb.bind(this));
		$("#upload-new").change(this.onUpload.bind(this));

		$("#folders table").on("contextmenu", "tr", this.contextMenu.bind(this));
		$("#panel")
			.on("click", ".context", this.onContextClick.bind(this))
			.on("contextmenu", "#contextmenu", function() { return false; })
			.on("mouseenter", "#contextmenu .submenu", this.contextSubMenu.bind(this))
			.on("mouseleave", "#contextmenu .submenu,#contextsubmenu", function() {
				if (!$("#contextsubmenu").is(":hover"))
					$("#contextsubmenu").remove();
			})

		setInterval(function() {
			// Update clock
			var secondsCurrent = SID.getCurrentPlaytime();
			$("#time-current").empty().append((Math.floor(secondsCurrent / 60)+":"+(secondsCurrent % 60 < 10 ? "0" : "")+(secondsCurrent % 60)).split(".")[0] /* No MS */ );
			// Update time bar
			$("#time-bar div").css("width", ((secondsCurrent / this.secondsLength) * 346)+"px");
		}.bind(this), 200);

		$("#search-box").keydown(function(event) {
			if (event.keyCode == 13 && $("#search-box").val() !== "")
				$("#search-button").trigger("click");
		}).keyup(function() {
			$("#search-button").removeClass("disabled");
			if ($("#search-box").val() !== "")
				$("#search-button").prop("disabled", false);
			else
				$("#search-button").prop("enabled", false).addClass("disabled");
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

		$(document).on("click", function(event) {
			$target = $(event.target);
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
					$("#dialog-cover,.dialog-box").hide();
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
			if ($($tagsLine[0].lastChild).offset().left > 420) {
				// The edit tags "+" button is hard to get at thus the line is ripe for sliding
				var dataLeft = $tagsLine.data("left");
				if (typeof dataLeft == "undefined" || dataLeft == 0) {
					$tagsLine.data("left", 1) // It is now processed
						.stop(true)
						.animate({
							left: "-"+($tagsLine[0].offsetLeft + 6)+"px",
						}, 600, "easeOutQuint");
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
		$("#songs .tags-line").animate({
				left: "0",
			}, 600, "easeOutQuint").data("left", 0);
		// Hide all edit tag "+" buttons
		$("#songs .edit-tags").hide();
	},

	/**
	 * Click the left mouse button somewhere below the control buttons.
	 * 
	 * @param {*} event 
	 * @param {number} paramSubtune		If specified, override subtune number with a URL parameter.
	 * @param {boolean} paramSkipCSDb	If specified and TRUE, skip generating the 'CSDb' tab contents.
	 * @param {boolean} paramSolitary	If specified and TRUE, just stop the tune when it's done.
	 */
	onClick: function(event, paramSubtune, paramSkipCSDb, paramSolitary) {
		this.clearSpinner();

		switch (event.target.id) {
			case "folder-root":
				if (!$("#folder-root").hasClass("disabled")) {
					// Go to HVSC root folder
					this.path = "";
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					this.getFolder(this.scrollPositions[0]);
					this.scrollPositions = [this.scrollPositions[0]];
					this.getComposer();
					UpdateURL();
				}
				break;
			case "folder-back":
				if (!$("#folder-back").hasClass("disabled")) {
					// Go back one folder in the HVSC tree
					this.path = this.path.substr(0, this.path.lastIndexOf("/"));
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					this.getFolder(this.scrollPositions.pop(), undefined,
						(this.path === "/CSDb Music Competitions" || this.path === "/_Compute's Gazette SID Collection")
							&& this.cache.folder !== "" /* <- Boolean parameter */ );
					this.getComposer();
					UpdateURL(true);
				}
				break;
			case "search-button":
				// Perform a search query
				this.setupSortBox();
				ctrls.state("prev/next", "disabled");
				ctrls.state("subtunes", "disabled");
				ctrls.state("loop", "disabled");

				this.scrollPositions.push(this.currentScrollPos); // Remember where we parked
				this.getFolder(0, $("#search-box").val().replace(/\s/g, "_"));
				break;
			case "search-cancel":
				// Cancel the search results and return to the previous normal folder view
				ctrls.state("prev/next", "disabled");
				ctrls.state("subtunes", "disabled");

				this.getFolder(this.scrollPositions.pop());
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
							this.allTags	= data.all;
							this.fileTags	= data.sid;
							this.newTagID	= 60000;
							this.updateTagLists(this.allTags, this.fileTags);
							$("#new-tag").val("");
							// Show the dialog box
							CustomDialog({
								id: '#dialog-tags',
								text: '<h3>Edit tags</h3><p>'+name.split("/").slice(-1)[0]+'</p>'+
									'<span class="dialog-label-top" style="float:left;">All tags available:</span>'+
									'<span class="dialog-label-top" style="float:right;width:136px;">Tags for this file:</span>',
								width: 390,
								height: 345,
							}, function() {
								// OK was clicked; make all the tag changes
								$.post("php/tags_write.php", {
									fileID:		browser.fileID,
									allTags:	browser.allTags,
									fileTags:	browser.fileTags
								}, function(data) {
									browser.validateData(data, function(data) {
										var htmlTags = browser.buildTags(data.tags, data.tagtypes);
										browser.updateStickyTags(
											$(event.target).parents("td"),
											htmlTags,
											(browser.isSymlist || browser.isCompoFolder ? thisFullname : thisFullname.split("/").slice(-1)[0])
										);
										ctrls.updateSundryTags(htmlTags);
									});
								}.bind(this));
							});
							$("#dialog-all-tags").focus();
						});
					}.bind(this));
					return false;
				}

				if (event.target.tagName === "B") {
					// Clicked a star to set a rating for a folder or SID file
					if (!$("#logout").length) {
						// But must be logged in to do that
						alert("Login or register and you can click these stars to vote for a file or folder.");
						return false;
					}
					var rating = event.shiftKey ? 0 : 5 - $(event.target).index(); // Remember stars are backwards (RTL; see CSS)

					// Star rating for a folder or a SID file (PHP script figures this out by itself)
					$.post("php/rating_write.php", { fullname: thisFullname, rating: rating }, function(data) {
						this.validateData(data, function(data) {

							var stars = this.buildStars(data.rating);

							// Make the rating sticky without refreshing the page
							var $td = $(event.target).parents("td");
							$td.find(".rating").empty().append(stars);

							// But also update the relevant array for later filtering/sorting
							var isFile = $td.parent("tr").find(".name").hasClass("file"),
								endName = this.isSymlist || this.isCompoFolder ? thisFullname : thisFullname.split("/").slice(-1)[0];
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
						});
					}.bind(this));
					return false;
				}

				// A row was clicked, but was it a folder or a SID file?
				if (name.indexOf(".sid") === -1 && name.indexOf(".mus") === -1) {

					// ENTER FOLDER

					if ($(event.target).find(".entry").hasClass("search"))
						this.path = "/"+name; // Search folders already have the full path
					else
						this.path += "/"+name;
					ctrls.state("prev/next", "disabled");
					ctrls.state("subtunes", "disabled");
					ctrls.state("loop", "disabled");

					this.scrollPositions.push(this.currentScrollPos); // Remember where we parked
					this.currentScrollPos = 0;
					this.getFolder(0, undefined, undefined, function() {
						this.cache.folderTags = this.showFolderTags();
					});
					this.getComposer();

					UpdateURL();

				} else {

					// LOAD AND PLAY FILE

					// NOTE: Don't add a SID.pause() here, it creates an error for jsSID on stop then re-click.
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
							if (!SID.emulatorFlags.offline) ctrls.state("loop", "enabled");

							ctrls.updateInfo();
							ctrls.updateSundry();

							if ($("#sundry-tabs .selected").attr("data-topic") == "tags")
								$("#slider-button").show();

							SID.play(true);
							setTimeout(ctrls.setButtonPlay, 75); // For nice pause-to-play delay animation
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
								this.getComposer((this.playlist[this.songPos].profile != ""
									? this.playlist[this.songPos].profile
									: "_SID Happens" // If composers_id = 0 then just show the upload folder profile
								), true);
							else if (this.isSearching || this.path.substr(0, 2) === "/$" || this.path.substr(0, 2) === "/!")
								this.getComposer(this.playlist[this.songPos].fullname);
						} else
							this.getComposer();
						this.getGB64();
						this.getRemix();
						this.getPlayerInfo({player: this.playlist[this.songPos].player});
						this.reloadDisqus(this.playlist[this.songPos].fullname);

						UpdateURL();
						this.chips = 1;
						if (this.playlist[this.songPos].fullname.indexOf("2SID.sid") != -1) this.chips = 2;
						else if (this.playlist[this.songPos].fullname.indexOf("3SID.sid") != -1) this.chips = 3;
						viz.initGraph(this.chips);
						viz.startBufferEndedEffects();

						// Tab 'STIL' is called 'Lyrics' in CGSC
						$("#tab-stil").empty().append(this.isCGSC() ? "Lyrics" : "STIL");

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
				break;
			case "dialog-tags-left":
				// Edit tags: Transfer items from right to left list
				$("#dialog-song-tags option").each(function() {
					if (this.selected) // Remove ID
						var index = browser.fileTags.indexOf(parseInt(this.value));
						if (index > -1) browser.fileTags.splice(index, 1);
				});
				this.updateTagLists(this.allTags, this.fileTags);
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
	 * When selecting an option in the 'Sort by' drop-down box (CSDb tab).
	 * 
	 * @param {*} event 
	 */
	onChangeCSDb: function(event) {
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
				// MUSICIANS: Show only decent or good folders (assessed by JCH) in the letter folder
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
							filterFolders = '<tr class="disabled"><td class="spacer" colspan="2"></tr>'+
								filterFolders+'<tr class="disabled"><td class="divider" colspan="2"></tr>';
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
				}
				break;
			case "player":
				// Sort playlist according to music player
				// NOTE: This is not available in 'SID Happens' because all players are undetermined there.
				this.playlist.sort(function(obj1, obj2) {
					return obj1.player.toLowerCase() > obj2.player.toLowerCase() ? 1 : -1;
				});
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
				}
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
			// Rebuild the reordered table list (files only; the folders in top are just preserved)
			var files = adaptedName = "";
			$.each(this.playlist, function(i, file) {
				var isNew = file.hvsc == this.HVSC_VERSION || file.hvsc == this.CGSC_VERSION ||
					(typeof file.uploaded != "undefined" && file.uploaded.substr(0, 10) == this.today.substr(0, 10));
				adaptedName = file.substname == "" ? file.filename.replace(/^\_/, '') : file.substname;
				adaptedName = this.adaptBrowserName(adaptedName);
				files += '<tr>'+
						'<td class="sid unselectable"><div class="block-wrap"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes'+(this.isSymlist ? ' specific' : '')+(isNew ? ' newst' : '')+'">'+(this.isSymlist ? file.startsubtune + 1 : file.subtunes)+'</div>' : (isNew ? '<div class="newsid"></div>' : ''))+
						'<div class="entry name file'+(this.isSearching || this.isCompoFolder || this.path.substr(0, 2) === "/$" ? ' search' : '')+'" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'" data-symid="'+file.symid+'">'+adaptedName+'</div></div></div><br />'+
						'<span class="info">'+file.copyright.substr(0, 4)+file.infosec+'<div class="tags-line"'+(showTags ? '' : ' style="display:none"')+'>'+file.tags+'</div></span></td>'+
						'<td class="stars filestars"><span class="rating">'+this.buildStars(file.rating)+'</span>'+
						'<span class="disqus-comment-count'+(typeof file.uploaded != "undefined" ? ' disqus-sh' : '')+'" data-disqus-url="http://deepsid.chordian.net/#!'+this.path.replace("/_High Voltage SID Collection", "")+"/"+file.filename.replace("/_High Voltage SID Collection", "")+'"></span>'+(typeof file.uploaded != "undefined" ? '<span class="uploaded-time">'+file.uploaded.substr(0, 10)+'</span>' : '')+
						'</td>'+
					'</tr>';
			}.bind(this));
			$("#songs table").append(this.folders+files);
			this.updateDisqusCounts();
			DisableIncompatibleRows();
		} else if (this.isBigCompoFolder()) {
			// Rebuild the big CSDb music competitions folder
			var folders = "";
			$.each(this.compolist, function(i, folder) {
				var isMobileDenied = folder.incompatible.indexOf("mobile") !== -1 && this.isMobile;
				folders +=
					'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
						'<td class="folder compo"><div class="block-wrap"><div class="block slimfont">'+
							(folder.filescount > 0 ? '<div class="filescount">'+folder.filescount+'</div>' : '')+
						'<span class="name entry compo'+(this.isSearching ? ' search' : '')+'" data-name="'+(this.isSearching ? 'CSDb Music Competitions%2F' : '')+encodeURIComponent(folder.foldername)+'" data-incompat="'+folder.incompatible+'">'+
						folder.foldername+'</span></div></div><br />'+
						'<span class="info compo-year compo-'+folder.compo_type.toLowerCase()+'">'+folder.compo_year+(folder.compo_country.substr(0, 1) == "_" ? ' at ' : ' in ')+folder.compo_country.replace("_", "")+'</span></td>'+
						'</td>'+
						'<td class="stars"><span class="rating">'+this.buildStars(folder.rating)+'</span><br /></td>'+
					'</tr>';
			}.bind(this));
			folders = '<tr class="disabled"><td class="spacer" colspan="2"></tr>'+
				folders+'<tr class="disabled"><td class="divider" colspan="2"></tr>';
			$("#songs table").append(folders);
			this.cache.folder = folders;
			this.cache.compolist = this.compolist;
		}

		if (this.isMobile)
			$("#folders").scrollTop(0);
		else
			$("#folders").mCustomScrollbar("scrollTo", "top");
	},

	/**
	 * Get the folders and files in 'this.path' and show them in the browser panel.
	 * 
	 * @param {number} scrollPos	If specified, jump to position in list (otherwise just stay in top).
	 * @param {string} searchQuery	If specified, search results will be shown instead.
	 * @param {boolean} readCache	If specified, TRUE will load from a cache instead.
	 * @param {function} callback 	If specified, the function to call after showing the contents.
	 */
	getFolder: function(scrollPos, searchQuery, readCache, callback) {
		ctrls.state("root/back", "disabled");
		$("#dropdown-sort").prop("disabled", true);
		$("#search-here").prop("disabled", false);
		$("#search-here-container label").removeClass("disabled");
		$("#songs table").empty();
		this.isSearching = typeof searchQuery !== "undefined";
		this.isSymlist = this.path.substr(0, 2) === "/!" || this.path.substr(0, 2) === "/$";

		if (isDebug) {
			_(_SECTION, "browser.js: getFolder()");
			_(_PARAM, "scrollPos", scrollPos);
			_(_PARAM, "searchQuery", searchQuery);
			_(_PARAM, "readCache", readCache);
			_(_PARAM, "callback", typeof callback != "undefined");
		}

		if (typeof readCache !== "undefined" && readCache) {

			// LOAD FROM CACHE

			ctrls.state("root/back", "enabled");
			if (!this.isMobile) $("#folders").mCustomScrollbar("destroy");

			// Disable emulators/handlers in the drop-down according to parent folder attributes
			$("#dropdown-emulator").styledOptionState("websid legacy jssid soasc_auto soasc_r2 soasc_r4 soasc_r5 download", "enabled");
			$("#page .viz-emu").removeClass("disabled");
			$("#dropdown-emulator").styledOptionState(this.cache.incompatible, "disabled");
			if (this.cache.incompatible.indexOf("websid") !== -1) $("#page .viz-websid").addClass("disabled");
			if (this.cache.incompatible.indexOf("jssid") !== -1) $("#page .viz-jssid").addClass("disabled");

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

				// Let mobile devices use their own touch scrolling stuff
				if (this.isMobile) {
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
				if (this.isBigCompoFolder()) $("#dropdown-sort").prop("disabled", false);
			}.bind(this), 1);

		} else {

			// LOAD FROM HVSC.PHP

			var loading = setTimeout(function() {
				// Fade in a GIF loading spinner if the AJAX call takes longer than usual
				$("#loading").css("top", $("#songs").height() / 2 - 50 /* Half size of SVG */).fadeIn(350);
			}, 150);

			this.playlist = [];		// Every folder we enter will become its own local playlist
			this.compolist = [];	// For the big CSDb music competitions folder list
			this.subFolders = 0;
			this.path = this.path.replace("/_CSDb", "/CSDb");

			// Call the AJAX PHP script that delivers the list of files and folders
			if (isDebug) {
				_(_HEADER, "GET hvsc.php");
				_(_PARAM, "folder", this.path);
				_(_PARAM, "searchType", $("#dropdown-search").val());
				_(_PARAM, "searchQuery", this.isSearching ? searchQuery : "");
				_(_PARAM, "searchHere", $("#search-here").is(":checked"));
			}
			$.get("php/hvsc.php", {
					folder:			this.path,
					searchType:		$("#dropdown-search").val(),
					searchQuery:	this.isSearching ? searchQuery : "",
					searchHere:		($("#search-here").is(":checked") ? 1 : 0),
			}, function(data) {
				this.validateData(data, function(data) {
					if (isDebug) {
						_(_RESULTS);
						_(_DATA, "status", data.status);
						if (data.status != "ok") {
							_(_DATA, "message", data.message);
						} else {
							_(_DATA, "files", "[see console]");
							console.log("data.files", data.files);
							_(_DATA, "folders", "[see console]");
							console.log("data.folders", data.folders);
							_(_DATA, "results", data.results);
							_(_DATA, "incompatible", data.incompatible);
							_(_DATA, "owner", data.owner);
							_(_DATA, "compo", data.compo);
							_(_DATA, "today", data.today);
							_(_DATA, "uploads", data.uploads);
							_(_DATA, "debug", data.debug);
						}
					}
					clearTimeout(loading);
					$("#loading").hide();
					ctrls.state("root/back", "enabled");
					if (!this.isMobile) $("#folders").mCustomScrollbar("destroy");
					this.folders = this.extra = this.symlists = "";
					var files = "";

					// Disable emulators/handlers in the drop-down according to parent folder attributes
					$("#dropdown-emulator").styledOptionState("websid legacy jssid soasc_auto soasc_r2 soasc_r4 soasc_r5 download", "enabled");
					$("#page .viz-emu").removeClass("disabled");
					$("#dropdown-emulator").styledOptionState(data.incompatible, "disabled");
					if (data.incompatible.indexOf("websid") !== -1) $("#page .viz-websid").addClass("disabled");
					if (data.incompatible.indexOf("jssid") !== -1) $("#page .viz-jssid").addClass("disabled");

					$("#path").css("top", "5px");
					var pathText = this.path == "" ? "/" : this.path
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
					} else if (this.isUploadFolder()) {
						pathText = '<button id="upload-wizard">Upload New SID File</button>';
					}
					$("#path").empty().append(pathText);

					// Tab 'STIL' is called 'Lyrics' in CGSC
					$("#tab-stil").empty().append(this.isCGSC() ? "Lyrics" : "STIL");

					$("#tabs .tab").removeClass("disabled");
					if (this.isCGSC()) {
						// The 'CSDb', 'GB64' and 'Remix' tabs are useless to CGSC
						$("#tab-csdb,#tab-gb64,#tab-remix").addClass("disabled");
						$("#note-csdb,#note-gb64,#note-remix").hide();
						var $selected = $("#tabs .selected");
						if ($selected.attr("data-topic") === "csdb" || $selected.attr("data-topic") === "gb64" || $selected.attr("data-topic") === "remix")
							$("#tab-profile").trigger("click");
					} else if (this.isUploadFolder()) {
						// The 'GB64' and 'Remix' tabs are useless to 'SID Happens'
						$("#tab-gb64,#tab-remix").addClass("disabled");
						$("#note-gb64,#note-remix").hide();
						var $selected = $("#tabs .selected");
						if ($selected.attr("data-topic") === "gb64" || $selected.attr("data-topic") === "remix")
							$("#tab-profile").trigger("click");
						this.previousOverridePath = "_SID Happens";
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

						var isMobileDenied = folder.incompatible.indexOf("mobile") !== -1 && this.isMobile;

						if (folder.foldertype == "COMPO") {

							// COMPETITION FOLDERS

							if (this.cache.compolist.length)
								// The cache has the correct order if sorted recently
								folder = this.cache.compolist[i];

							this.compolist.push({
								incompatible:	folder.incompatible,
								filescount:		folder.filescount,
								foldername:		folder.foldername,
								compo_type:		folder.compo_type,
								compo_year:		folder.compo_year,
								compo_country:	folder.compo_country,
								rating:			folder.rating,
							});

							var folderEntry =
								'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
									'<td class="folder compo"><div class="block-wrap"><div class="block slimfont">'+
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
								myPublic = false;
							if (isPublicSymlist) {
								var result = $.grep(this.symlistFolders, function(entry) {
									return entry.fullname == folder.foldername;
								}.bind(this));
								if (result.length) myPublic = true;
							}
							var adaptedName = folder.foldername.replace(/^(\_|\!|\$)/, '');
							adaptedName = this.adaptBrowserName(adaptedName);
							var folderEntry =
								'<tr'+(folder.incompatible.indexOf(SID.emulator) !== -1 || isMobileDenied ? ' class="disabled"' : '')+'>'+
									'<td class="folder '+
										(isPersonalSymlist || (isPublicSymlist && myPublic)
											? 'playlist'
											: folder.foldertype.toLowerCase()+(folder.hasphoto ? '-photo' : ''))+
											(folder.hvsc == this.HVSC_VERSION || folder.hvsc == this.CGSC_VERSION ? ' new' : '')+
										'"><div class="block-wrap"><div class="block">'+
									(folder.filescount > 0 ? '<div class="filescount">'+folder.filescount+'</div>' : '')+
									(folder.foldername == "_SID Happens" ? '<div class="new-uploads'+(data.uploads.substr(0, 6) == "NO NEW" ? ' no-new' : '')+'">'+data.uploads+'</div>' : '')+
									'<span class="name entry'+(this.isSearching ? ' search' : '')+'" data-name="'+encodeURIComponent(folder.foldername)+'" data-incompat="'+folder.incompatible+'">'+
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
								publicUploadFolder = folderEntry+'<tr class="disabled"><td class="divider" colspan="2"></td></tr>'+
									'<tr class="disabled"><td class="spacer" colspan="2"></td></tr>';
							else if ((folder.foldername.substr(0, 1) == "_" || isPublicSymlist) &&
								(!onlyShowPersonal || (onlyShowPersonal && myPublic)) &&
								(!onlyShowCommon || (onlyShowCommon && folder.flags & 0x1)))	// Public symlist or custom?
								this.extra += folderEntry;
							else if (isPersonalSymlist)											// Personal symlist folder?
								this.symlists += folderEntry;
							else
								this.folders += folderEntry;									// Normal folder
						}
						this.subFolders++;

					}.bind(this));

					if (this.subFolders) {
						if (this.extra !== "") {
							this.extra = '<tr class="disabled"><td class="spacer" colspan="2"></td></tr>'+this.extra+
								'<tr class="disabled"><td class="divider" colspan="2"></td></tr>';
							this.subFolders += 2;
						}
						if (this.symlists !== "") {
							this.symlists = '<tr class="disabled"><td class="spacer" colspan="2"></td></tr>'+this.symlists+
								'<tr class="disabled"><td class="divider" colspan="2"></td></tr>';
							this.subFolders += 2;
						}
						if (collections.length)
							this.folders = publicUploadFolder+collections[1]+collections[0]; // HVSC should be before CGSC
						this.folders += csdbCompoEntry;
						this.folders += exoticCollection;
						this.folders = '<tr class="disabled"><td class="spacer" colspan="2"></td></tr>'+this.folders;
						this.folders += '<tr class="disabled"><td class="divider" colspan="2"></td></tr>'+this.extra;
						this.folders += this.symlists;
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
						// All other folders sort files by name to begin with
						data.files.sort(function(obj1, obj2) {
							var o1 = obj1.substname !== "" ? obj1.substname : this.adaptBrowserName(obj1.filename, true);
							var o2 = obj2.substname !== "" ? obj2.substname : this.adaptBrowserName(obj2.filename, true);
							return o1.toLowerCase() > o2.toLowerCase() ? 1 : -1;
						}.bind(this));
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
							isNew = file.hvsc == this.HVSC_VERSION || file.hvsc == this.CGSC_VERSION ||
								(typeof file.uploaded != "undefined" && file.uploaded.substr(0, 10) == this.today.substr(0, 10));
						var adaptedName = file.substname == "" ? file.filename.replace(/^\_/, '') : file.substname;
						adaptedName = this.adaptBrowserName(adaptedName);
						var list_of_tags = this.buildTags(file.tags, file.tagtypes),
							infoSecondary = typeof file.uploaded != "undefined" ? ' by '+file.author : ' in '+player;
						files +=
							'<tr>'+
								'<td class="sid unselectable"><div class="block-wrap"><div class="block">'+(file.subtunes > 1 ? '<div class="subtunes'+(this.isSymlist ? ' specific' : '')+(isNew ? ' newst' : '')+'">'+(this.isSymlist ? file.startsubtune : file.subtunes)+'</div>' : (isNew ? '<div class="newsid"></div>' : ''))+
								'<div class="entry name file'+(this.isSearching || this.isCompoFolder || this.path.substr(0, 2) === "/$" ? ' search' : '')+'" data-name="'+encodeURIComponent(file.filename)+'" data-type="'+file.type+'" data-symid="'+file.symid+'">'+adaptedName+'</div></div></div><br />'+
								'<span class="info">'+file.copyright.substr(0, 4)+infoSecondary+'<div class="tags-line"'+(showTags ? '' : ' style="display:none"')+'>'+list_of_tags+'</div></span></td>'+
								'<td class="stars filestars"><span class="rating">'+this.buildStars(file.rating)+'</span>'+
								'<span class="disqus-comment-count'+(typeof file.uploaded != "undefined" ? ' disqus-sh' : '')+'" data-disqus-url="http://deepsid.chordian.net/#!'+rootFile.replace("/_High Voltage SID Collection", "")+'"></span>'+(typeof file.uploaded != "undefined" ? '<span class="uploaded-time">'+file.uploaded.substr(0, 10)+'</span>' : '')+
								'</td>'+
							'</tr>'; // &#9642; is the dot character if needed

						// If the STIL text starts with a <BR> newline or a <HR> line, get rid of it
						var stil = file.stil;
						if (stil.substr(2, 4) == "r />") stil = stil.substr(6);

						this.playlist.push({
							filename:		file.filename,
							substname:		file.substname,	// Symlists can have renamed SID files
							fullname:		this.ROOT_HVSC + rootFile,
							playerraw:		file.playerraw,
							player: 		player,
							tags:			list_of_tags,
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
							profile:		file.profile,	// Only files from 'SID Happens'
							uploaded:		file.uploaded,	// Only files from 'SID Happens'
						});
					}.bind(this));

					if (files !== "" || this.path === "" || this.isBigCompoFolder() || this.isMusiciansLetterFolder()) $("#dropdown-sort").prop("disabled", false);
					/*var pos = this.folders.lastIndexOf('<tr>');
					this.folders = this.folders.slice(0, pos) + this.folders.slice(pos).replace('<tr>', '<tr class="last">');*/
					$("#songs table").append(this.folders+files);
					this.updateDisqusCounts();

					if (this.path == "/CSDb Music Competitions" || this.path == "/_Compute's Gazette SID Collection") {
						// Cache this big folder for fast back-browsing
						this.cache.folder = this.folders+files;
						this.cache.incompatible = data.incompatible;
						if (this.cache.compolist.length == 0) this.cache.compolist = this.compolist;
					}

					// Let mobile devices use their own touch scrolling stuff
					if (this.isMobile) {
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
					if (typeof callback === "function") callback.call(this);
				});
				if (this.path == "")
					ctrls.state("root/back", "disabled");

				DisableIncompatibleRows();

			}.bind(this));
		}
	},

	/**
	 * Get the length of the SID (sub) tune and convert it to just seconds.
	 * 
	 * @param {number} subtune		Subtune number.
	 * @param {boolean} noReset		If specified and TRUE, skip resetting the bar fields.
	 * 
	 * @return {number}				The total number of seconds.
	 */
	getLength: function(subtune, noReset) {
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
		$("#sid-model,#clockspeed,#hvsc-version").remove();
		$("#memory-chunk").css({left: "0", width: "0"});
		$("#info-text").empty();
		$("#stopic-stil,#stopic-tags").mCustomScrollbar("destroy").empty();

		ctrls.state("play/stop", "disabled");
		ctrls.state("prev/next", "enabled"); // Still need to skip it
		ctrls.state("subtunes", "disabled");
		ctrls.state("faster", "disabled");
		ctrls.state("loop", "disabled");
		$("#volume").prop("disabled", true);
	},

	/**
	 * Build the HTML elements needed to show the marked stars in the SID file row.
	 * 
	 * @param {number} rating	The rating; 0 to 5.
	 * 
	 * @return {string}			The HTML string to put into the SID row.
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
	 * @param {array} tags		Array with (sorted) tag names only.
	 * @param {array} types		Array with (sorted) tag types only.
	 * 
	 * @return {string}			The HTML string to put into the SID row.
	 */
	buildTags: function(tags, types) {
		var list_of_tags = remix64 = '';
		$.each(tags, function(i, tag) {
			if (tag == "Remix64")
				// A special look for the "Remix 64" tag
				remix64 = '<div class="tag tag-remix64">&nbsp;&nbsp;</div>';
			else if (tag == "Doubling" || tag == "Hack" || tag == "Mock")
				// A unique color for tags that serves as a warning
				list_of_tags += '<div class="tag tag-warning">'+tag+'</div>';
			else
				// NOTE: Don't change the order of tags or the collector for a folder will break!
				list_of_tags += '<div class="tag tag-'+types[i]+'">'+tag+'</div>';
		});
		list_of_tags += '<div class="edit-tags" title="Edit tags">&nbsp;</div>';

		return remix64+list_of_tags;
	},

	/**
	 * Hide the rating stars and show a spinner to show that the SID tune is loading.
	 * 
	 * @param {object} $td	The jQuery element with the SID filename.
	 */
	showSpinner: function($td) {
		if (SID.emulatorFlags.slowLoading) {
			// Temporarily hide the rating stars and show a loading spinner instead
			$stars = $($td).next("td.stars");
			$stars.children("span").hide();
			$stars.append('<span id="spinner"></span>');
		}
	},

	/**
	 * Clear the SID tune loading spinner and show the ratings stars again.
	 */
	clearSpinner: function() {
		$("#songs td.stars span").show();
		$("#spinner").remove();
	},

	/**
	 * Let Disqus know that it's time to load comments for a different SID file.
	 * 
	 * @param {string} file		SID fullname string.
	 */
	reloadDisqus: function(file) {
		if (this.isMobile) return;
		if ($("#topic-disqus").length && $("#disqus-toggle").is(":checked") && typeof DISQUS !== "undefined") {
			// Disqus was implemented before the main folder for HVSC was so it doesn't know it exists
			var rootFile = file.replace("hvsc", "").replace("/_High Voltage SID Collection", "");
			DISQUS.reset({
				reload: true,
				config: function() {  
					this.page.url = "http://deepsid.chordian.net/#!"+rootFile;
					this.page.identifier = "http://deepsid.chordian.net/#!"+rootFile;
					this.page.title = rootFile;
					$("#disqus-title").empty().append("File: "+rootFile);
				}
			});
		}
		this.rowDisqusCount();
	},

	/**
	 * If there are any Disqus comments then show a notification number on the 'Disqus' tab (if not in focus).
	 */
	rowDisqusCount: function() {
		if (this.isMobile) return;
		var count = $("#folders tr").eq(this.subFolders + this.songPos).find(".disqus-comment-count")
			.text().trim().split(" ")[0];
		if (count !== "" && $("#tabs .selected").attr("data-topic") !== "disqus")
			$("#note-disqus").empty().append(count).show();
		else
			$("#note-disqus").hide();
	},

	/**
	 * Show number of Disqus comments for each SID file (if any).
	 */
	updateDisqusCounts: function() {
		if (this.isMobile) return;
		if ($("#topic-disqus").length && $("#disqus-toggle").is(":checked") && typeof DISQUSWIDGETS !== "undefined")
			DISQUSWIDGETS.getCount({reset: true});
	},

	/**
	 * Show the composer page in the 'Profile' tab.
	 * 
	 * @param {string} overridePath		If specified, fullname for profile (including file).
	 * @param {boolean} rawPath			Unless, if specified, this is set to TRUE (path only).
	 */
	getComposer: function(overridePath, rawPath) {
		if (this.isMobile) return;
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
					var composerFolder = "http://deepsid.chordian.net/?file=/"+(overridePath == "" ? this.path.substr(1) : overridePath);
					$("#profilechange").append('<a href="mailto:chordian@gmail.com?subject=DeepSID%20profile%20change&body=I%20have%20a%20profile%20change%20request%20for:%0D%0A'+composerFolder+'%0D%0A%0D%0A">Report a profile change</a>');

					// Enable the brand image (if available) for the correct color theme
					$("#brand-"+(parseInt(colorTheme) ? "dark" : "light")).show();

					this.groupsFullname = overridePath == "" ? this.path.substr(1) : overridePath;
					this.getGroups(this.groupsFullname);
				});
			}.bind(this));
		}
	},

	/**
	 * Get the contents of the groups table and display it in the composer profile.
	 * 
	 * @param {string} fullname		The SID filename including folders.
	 */
	getGroups: function(fullname) {
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
	 * @param {string} type		E.g. "release" (only used for permalinks).
	 * @param {number} id		ID number used by CSDb (only used for permalinks).
	 * @param {boolean} back	If specified and TRUE, show a 'BACK' button.
	 */
	getCSDb: function(type, id, back) {
		if (this.isMobile || this.isTempTestFile()) return;
		if (this.csdb) this.csdb.abort();
		$("#topic-csdb").empty().append(this.loadingSpinner("csdb"));
		$("#sticky-csdb").empty();

		var loadingCSDb = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-csdb").fadeIn(500);
		}, 250);

		var args = typeof type !== "undefined" && typeof id !== "undefined"
			? { type: type, id: id, back: (typeof back === "undefined" ? 1 : 0) }
			: { fullname: browser.playlist[browser.songPos].fullname.substr(5) };

		this.csdb = $.get("php/csdb.php", args, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingCSDb);
				$("#sticky-csdb").empty().append(data.sticky);
				if (parseInt(colorTheme)) data.html = data.html.replace(/composer\.png/g, "composer_dark.png");
				$("#topic-csdb").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("csdb");

				UpdateRedirectPlayIcons();

				if (data.entries != "") this.sidEntries = data.entries; // Array used for sorting

				// Add rows sorted by newest by triggering the drop-down box (if present)
				$("#dropdown-sort-csdb").trigger("change");

				// If there are any entries then show a notification number on the 'CSDb' tab (if not in focus)
				if (data.count != 0 && $("#tabs .selected").attr("data-topic") !== "csdb" && !this.isCGSC())
					// If it's a release page then show a special character instead of a count
					$("#note-csdb").empty().append(data.count > 0 ? data.count : "&#9679;").show(); // 8901, 9679
				else
					$("#note-csdb").hide();

			});
		}.bind(this));
	},

	/**
	 * Show contents in the 'Player' tab about the editor/player used to create the
	 * song, if available.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {array} params	player: {string} or id: {number}.
	 */
	getPlayerInfo: function(params) {
		if (this.isMobile || JSON.stringify(params) == JSON.stringify(this.playerParams)) return;
		if (this.playerInfo) this.playerInfo.abort();
		$("#topic-player").empty().append(this.loadingSpinner("player"));

		this.playerParams = params; // Prevents reloading of the same page (not 100% perfect)

		var loadingPlayer = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-player").fadeIn(500);
		}, 250);

		this.playerInfo = $.get("php/player.php", params, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingPlayer);
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
	 * @param {string} compo	Type, e.g. "C64 Music" (obtained from a CSDb page).
	 * @param {number} id 		The CSDb event ID.
	 * @param {number} mark		ID of the release page to mark on the competition results list.
	 */
	getCompoResults: function(compo, id, mark) {
		if (this.isMobile) return;
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
	 * @param {number} optionalID		If specified, the ID to show a specific sub page.
	 */
	getGB64: function(optionalID) {
		if (this.isMobile || this.isTempTestFile()) return;
		if (this.gb64) this.gb64.abort();
		$("#topic-gb64").empty().append(this.loadingSpinner("gb64"));

		var loadingGB64 = setTimeout(function() {
			// Fade in a GIF loading spinner if the AJAX call takes a while
			$("#loading-gb64").fadeIn(500);
		}, 250);

		var params = typeof optionalID === "undefined"
			? { fullname: browser.playlist[browser.songPos].fullname.substr(5) }
			: { id: optionalID };

		this.gb64 = $.get("php/gb64.php", params, function(data) {
			this.validateData(data, function(data) {

				clearTimeout(loadingGB64);
				$("#topic-gb64").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("gb64");
	
				// If there are any entries then show a notification number on the 'GB64' tab (if not in focus)
				if (data.count > 0 && $("#tabs .selected").attr("data-topic") !== "gb64" && !this.isCGSC())
					$("#note-gb64").empty().append(data.count).show();
				else
					$("#note-gb64").hide();
	
			});
		}.bind(this));
	},

	/**
	 * Show contents in the 'Remix' tab pertinent to the selected SID tune. A spinner is
	 * shown while calling the PHP script.
	 * 
	 * Also handles the tab notification counter. 
	 * 
	 * @param {number} optionalID		If specified, the ID to show a specific entry.
	 */
	getRemix: function(optionalID) {
		if (this.isMobile || this.isTempTestFile()) return;
		if (this.remix) this.remix.abort();
		$("#topic-remix").empty().append(this.loadingSpinner("remix"));

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
				$("#topic-remix").empty().append(data.html)
					.css("visibility", "visible");
				ResetDexterScrollBar("remix");

				// If there are any entries then show a notification number on the 'Remix' tab (if not in focus)
				if (data.count > 0 && $("#tabs .selected").attr("data-topic") !== "remix" && !this.isCGSC())
					$("#note-remix").empty().append(data.count).show();
				else
					$("#note-remix").hide();

				// IF there are entries but no "Remix64" tag then add it now
				if (data.count > 0 && browser.playlist[browser.songPos].tags.indexOf("tag-remix64") == -1) {
					$.post("php/tags_write_single.php", {
						fullname:	thisFullname,
						tag:		"Remix64",
					}, function(data) {
						browser.validateData(data, function(data) {
							browser.updateStickyTags(
								$("#songs tr.selected"),
								browser.buildTags(data.tags, data.tagtypes),
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

			if (typeof this.playlist[thisRow].uploaded != "undefined") {
				// It's a SID row from the 'Sid Happens' folder and thus can be edited
				contents += '<div class="divider"></div>'+
					'<div class="line" data-action="edit-upload">Edit Uploaded File</div>';
			}

		} else if ($target.hasClass("folder") && (this.contextSID.substr(0, 1) == "!" || this.contextSID.substr(0, 1) == "$")) {
			var ifAlreadyPublic = "";

			if (this.contextSID.substr(0, 1) == "$") {
				var result = $.grep(this.symlistFolders, function(entry) {
					return entry.fullname == this.contextSID;
				}.bind(this));
				if (result.length === 0) return; // Not your public symlist
				ifAlreadyPublic = " disabled";
			}

			contents = // Symlist folder in root
				'<div class="line" data-action="symentry-rename">Rename Playlist</div>'+
				'<div class="line" data-action="symlist-delete">Delete Playlist</div>'+
				(ifAlreadyPublic
					? '<div class="line" data-action="symlist-unpublish">Unpublish Playlist</div>'
					:'<div class="line" data-action="symlist-publish">Publish Playlist</div>');
		} else
			return;

		// Create the hidden menu and assume coordinates for going downwards
		$panel.prepend('<div id="contextmenu" class="context">'+contents+'</div>');
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
			case 'copy-link':
				var url = window.location.href,
					more = url.indexOf("&") != -1;
				var path = url.indexOf(".sid") != -1 || url.indexOf(".mus") != -1
					? url.substr(0, url.lastIndexOf("/") + 1)
					: (more ? url.substr(0, url.indexOf("&")): url);
				url = path+this.contextSID+(more ? url.substr(url.indexOf("&")) : "");
				// Copy it to the clipboard
				// @link https://stackoverflow.com/a/30905277/2242348
				var $temp = $("<input>");
				$("body").append($temp);
				$temp.val(url).select();
				document.execCommand("copy");
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
					fullname:	(this.isSearching || this.path.substr(1, 1) == "!" ||  this.path.substr(1, 1) == "$" ? this.contextSID : this.path.substr(1)+"/"+this.contextSID)
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
			case "symlist-add":
			case "symlist-new":
				// Add the SID file to a symlist (existing or creating with unique version of SID file name)
				$.post("php/symlist_write.php", {
					fullname:	(this.isSearching || this.isCompoFolder || this.path.substr(1, 1) == "$" ? this.contextSID : this.path.substr(1)+"/"+this.contextSID),
					symlist:	(action === "symlist-add" ? (event.target.textContent.indexOf(" [PUBLIC]") !== -1 ? "$" : "!")+event.target.textContent : ''),
					subtune:	(ctrls.subtuneCurrent && this.contextSelected ? ctrls.subtuneCurrent + 1 : 0)
				}, function(data) {
					this.validateData(data);
				}.bind(this));
				if (action === "symlist-new")
					this.getSymlists();
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
	 * @param {string} name		The original SID filename.
	 * @param {boolean} raw		TRUE to use raw HVSC/CGSC/ESTC collection names
	 * 
	 * @return {string}			The shortened SID filename.
	 */
	adaptBrowserName: function(name, raw) {
		underscore = typeof raw !== "undefined" ? "_" : "";
		return this.path === "" && !this.isSearching ? name : name
			.replace(underscore+"High Voltage SID Collection", '<font class="dim">HVSC</font>')
			.replace("HVSC</font>/DEMOS", "HVSC/D</font>")
			.replace("HVSC</font>/GAMES", "HVSC/G</font>")
			.replace("HVSC</font>/MUSICIANS", "HVSC/M</font>")
			.replace(underscore+"Compute's Gazette SID Collection", '<font class="dim">CGSC</font>')
			.replace(underscore+"Exotic SID Tunes Collection", '<font class="dim">ESTC</font>');
	},

	/**
	 * Handle any errors after returning from an AJAX call.
	 * 
	 * @param {object} data			The data returned from the PHP script.
	 * @param {function} callback	Function to call if no errors.
	 * 
	 * @return {boolean}			TRUE if no errors.
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
	 * @param {string} tags		List of tags from a previous collection to be shown now.
	 * 
	 * @return {string}			Tags collected this time.
	 */
	showFolderTags: function(tags) {
		var allTags = tags;
		$("#slider-button").hide();
		if (typeof tags == "undefined" || this.cache.folderTags == "0") {
			var tagType = {
				production:	"",
				origin:		"",
				suborigin:	"",
				mixorigin:	"",
				digi:		"",
				subdigi:	"",
				remix64:	"",
				other:		"",
				warning:	"",
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
				tagType.origin+
				tagType.suborigin+
				tagType.mixorigin+
				tagType.production+
				tagType.digi+
				tagType.subdigi+
				tagType.remix64+
				tagType.other+
				tagType.warning;
		}
		ctrls.updateSundryTags(allTags);
		return allTags;
	},

	/**
	 * Prepare a loading SVG spinner for showing if a page takes time to load.
	 * 
	 * @param {string} id		CSS ID name.
	 * 
	 * @return {string}			The HTML string with the SVG image.
	 */
	loadingSpinner: function(id) {
		return '<div style="height:400px;"><img id="loading-'+id+'" class="loading-spinner" src="images/loading.svg" style="display:none;" alt="" /></div>';
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
		return this.path == "/"+PATH_UPLOADS;
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
	 * @param {object} $selected		The DOM object with the <TD> SID row.
	 * @param {string} list_of_tags		HTML list of tags.
	 * @param {string} endName			The SID name without prepended path.
	 */
	updateStickyTags: function($selected, list_of_tags, endName) {
	 
		// Make the tags sticky without refreshing the page
		$selected.find(".tags-line").empty().append(list_of_tags);

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
	 * @param {array} arrAll		Associative array with ID's and names.
	 * @param {array} arrSong		Standard array with ID's used by file.
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
	 * Empty and then refill the contextual SORT/FILTER drop-down box.
	 * 
	 * @return {string}		Currently selected item (FILTER only).
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
			// Sort box for everything else
			$("#dropdown-sort").empty().append(
				'<option value="name">Name</option>'+
				'<option value="player">Player</option>'+
				'<option value="rating">Rating</option>'+
				'<option value="oldest">Oldest</option>'+
				'<option value="newest">Newest</option>'+
				'<option value="shuffle">Shuffle</option>'
			).val("name");
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
	 * @param {array} data		Array with SID file information.
	 * @param {string} value	Optional option value to set.
	 */
	getProfiles: function(data, value) {
		$("#dropdown-upload-profile").empty();
		this.profileValue = typeof value == "undefined" ? "unset" : value;
		$.get("php/upload_get_profiles.php", function(data) {
			this.validateData(data, function(data) {
				var profiles = '<option value="unset">Not connected to a profile page yet</option>';
				for (var i = 0; i < data.profiles.length; i++)
					profiles += '<option value="'+data.profiles[i]+'">'+data.profiles[i]+'</option>';
				// NOTE: Don't use the styled drop-down box; it is too slow to handle a list this big.
				$("#dropdown-upload-profile").append(profiles).val(this.profileValue);
			});
		}.bind(this));
	},

	/**
	 * Show the wizard dialog box for uploading a new SID file.
	 * 
	 * @param {number} step		Wizard step to be shown.
	 * @param {array} data		Array with SID file information.
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
					wizard: true,
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
						$("#upload-file-author-input").val(data.info.author);
						$("#upload-file-copyright-input").val(data.info.copyright);
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
					$.post("php/upload_final.php", { info: data.info }, function(data) {
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