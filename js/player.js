
/**
 * DeepSID / SIDPlayer
 */

function SIDPlayer(emulator) {

	this.paused = false;

	this.emulator = emulator.toLowerCase();
	this.chip = "";
	this.voiceMask = 0xF;
	this.mainVol = 1;
	
	this.file = "";

	this.emulatorFlags = {
		supportFaster:		true,	// True if the handler supports the "Faster" button
		supportEncoding:	true,	// True if the handler supports toggling between PAL and NTSC
		supportSeeking:		true,	// True if the handler supports seek-clicking the time bar
		forceModel:			true,	// True if SID chip model must be set according to the database
		forcePlay:			true,	// True to force start playing in all load calls
		hasFlags:			true,	// True if showing corner flags in the info box
		slowLoading:		true,	// True if the handler is relatively slow at loading tunes
		returnCIA:			true,	// True if the handler can return the 16-bit CIA value
		offline:			true,	// True if only the skip buttons should be accessible
	}

	this.modelSOASC = "MOS6581R2";

	switch (this.emulator) {

		case "websid":

			/**
			 * WebSid by Jürgen Wothke (Tiny'R'Sid)
			 * 
			 * + Can play many types of digi tunes
			 * + SID model and encoding
			 * + Can play 2SID and 3SID tunes
			 * + Can play MUS files in CGSC
			 * - Emulation quality varies at times
			 * - Cannot play BASIC program tunes
			 */
			if (typeof SIDBackend === "undefined") SIDBackend = new SIDBackendAdapter();
			if (typeof Ticker === "undefined") Ticker = new AbstractTicker();
			if (typeof player !== "undefined") {
				// The audio context must be recreated to avoid choppy updating in the oscilloscope voices
				_gPlayerAudioCtx.close();
				_gPlayerAudioCtx.ctx = null;
				try {			
					if("AudioContext" in window) {
						_gPlayerAudioCtx = new AudioContext();
					} else if('webkitAudioContext' in window) {
						_gPlayerAudioCtx = new webkitAudioContext(); // Legacy
					} else {
						alert(errText + e);
					}			
				} catch(e) {
					alert(errText + e);
				}
			}
			ScriptNodePlayer.createInstance(SIDBackend, '', [], false, this.onPlayerReady.bind(this), this.onTrackReadyToPlay.bind(this), this.onTrackEnd.bind(this), undefined, scope,
				($("body").attr("data-mobile") !== "0" ? 16384 : viz.bufferSize));
			this.WebSid = ScriptNodePlayer.getInstance();

			this.emulatorFlags.supportFaster	= true;
			this.emulatorFlags.supportEncoding	= true;
			this.emulatorFlags.supportSeeking	= false;
			this.emulatorFlags.forceModel		= false;
			this.emulatorFlags.forcePlay		= false;
			this.emulatorFlags.hasFlags			= true;
			this.emulatorFlags.slowLoading		= false;
			this.emulatorFlags.returnCIA		= true;
			this.emulatorFlags.offline			= false;
			break;

		case "jssid":

			/**
			 * jsSID by Hermit
			 * 
			 * + Very small and compact JS code
			 * + Sometimes emulates better than WebSid
			 * + Can play 2SID and 3SID tunes
			 * - Cannot play MUS files in CGSC
			 * - No encoding options
			 * - Cannot play BASIC and digi tunes (RSID)
			 * - Some CIA tunes doesn't work either
			 */
			this.jsSID = new jsSID(($("body").attr("data-mobile") !== "0" ? 16384 : viz.bufferSize), 0.0005);

			this.emulatorFlags.supportFaster	= true;
			this.emulatorFlags.supportEncoding	= false;
			this.emulatorFlags.supportSeeking	= false;
			this.emulatorFlags.forceModel		= true;
			this.emulatorFlags.forcePlay		= false;
			this.emulatorFlags.hasFlags			= true;
			this.emulatorFlags.slowLoading		= false;
			this.emulatorFlags.returnCIA		= true;
			this.emulatorFlags.offline			= false;
			break;

		case "soasc_r2":
		case "soasc_r4":
		case "soasc_r5":
		case "soasc_auto":

			/**
			 * Howler by James Simpson (Goldfire Studios)
			 * 
			 * + Can play anything; used for SOASC only
			 * + For SOASC, both MP3 and FLAC are played
			 * + Multiplier (only to 4.0 but still)
			 * 
			 * Stone Oakvalley's Authentic SID Collection
			 * 
			 * + MP3/FLAC recordings from real C64!
			 * + SID chip revisions R2, R4 and R5
			 * + All kinds of SID tunes are supported
			 * - Except 2SID, 3SID and STR tunes
			 * - Depends on external FTP mirror sites
			 * - SOASC files are slower starters
			 */
			this.chip = this.emulator.substr(6);
			this.emulator = "soasc";

			this.emulatorFlags.supportFaster	= true;
			this.emulatorFlags.supportEncoding	= false;
			this.emulatorFlags.supportSeeking	= true;
			this.emulatorFlags.forceModel		= false;
			this.emulatorFlags.forcePlay		= true;
			this.emulatorFlags.hasFlags			= false;
			this.emulatorFlags.slowLoading		= true;
			this.emulatorFlags.returnCIA		= false;
			this.emulatorFlags.offline			= false;
			break;

		case "download":

			/**
			 * Download option
			 * 
			 * + Can download a SID file
			 * + Can play with associated offline player
			 * - Most controls are not available then
			 */
			this.emulatorFlags.supportFaster	= false;
			this.emulatorFlags.supportEncoding	= false;
			this.emulatorFlags.supportSeeking	= false;
			this.emulatorFlags.forceModel		= false;
			this.emulatorFlags.forcePlay		= false;
			this.emulatorFlags.hasFlags			= false;
			this.emulatorFlags.slowLoading		= false;
			this.emulatorFlags.returnCIA		= false;
			this.emulatorFlags.offline			= true;
			break;

		default:

			alert("ERROR: Invalid SID handler specified");
	}
}

SIDPlayer.prototype = {

	onPlayerReady: function() {
		if (typeof this.callbackPlayerReady === "function") this.callbackPlayerReady();
		this.callbackPlayerReady = null;
	},

	onTrackReadyToPlay: function() {
		if (typeof this.callbackTrackReadyToPlay === "function") this.callbackTrackReadyToPlay();
		this.callbackTrackReadyToPlay = null;
	},

	onTrackEnd: function() {
		if (typeof this.callbackTrackEnd === "function") this.callbackTrackEnd();
	},

	setCallbackPlayerReady: function(callback) { this.callbackPlayerReady = callback; },
	setCallbackTrackReadyToPlay: function(callback) { this.callbackTrackReadyToPlay = callback; },
	setCallbackTrackEnd: function(callback) { this.callbackTrackEnd = callback; },
	setCallbackBufferEnded: function(callback) { this.callbackBufferEnded = callback; },

	/**
	 * Load a SID file but do not play it yet. Also handles callbacks to when the file
	 * has loaded, and in some cases also when the music has timed out.
	 * 
  	 * @param {number} subtune		The subtune to be played.
	 * @param {number} timeout		Number of seconds before the music times out.
	 * @param {string} file			Fullname (including prepended HVSC root).
	 * @param {function} callback 	Function to call after the SID file has loaded.
	 */
	load: function(subtune, timeout, file, callback) {

		this.voiceMask = 0xF;
		viz.lineInGraph = true;

		subtune = this.subtune = typeof subtune === "undefined" ? this.subtune : subtune;
		timeout = this.timeout = typeof timeout === "undefined" ? this.timeout : timeout;
		file = this.file = typeof file === "undefined" ? this.file : file;

		// Show the raw SID filename in the title
		$(document).attr("title", "DeepSID | "+file.split("/").slice(-1)[0]);

		switch (this.emulator) {

			case "websid":

				var error = file.indexOf("_BASIC.") !== -1;
				if (error) this.setVolume(0);

				var options = {};
				options.track = subtune;
				options.timeout = timeout;
				options.traceSID = true;	// Needed for the oscilloscope sundry box view

				// Since 'onCompletion' and 'onProgress' (below) are only utilized when loading
				// the file for the first time, 'onTrackReadyToPlay' is used instead for callback.
				this.setCallbackTrackReadyToPlay(function() {
					// Reset volume in case the "Faster" button-slip trick was used
					if (!error) this.setVolume(1);
					if (typeof callback === "function") {
						callback.call(this, error);
					}
				}.bind(this));

				// Called at the start of each WebAudio buffer
				// NOTE: Since the introduction of the oscilloscope code, this is actually called
				// by the 'start()' function in the scope.js (sid_tracer.js) script.
				Ticker.start = function() {
					if (typeof this.callbackBufferEnded === "function")
						this.callbackBufferEnded();
				}.bind(this);

				// The three callbacks here: onCompletion, onFail, onProgress
				this.WebSid.loadMusicFromURL(file, options, (function(){}), (function(){}), (function(){}));

				if (error) {
					setTimeout(function() {
						// After half a second just go to the next row
						if (typeof this.callbackTrackEnd === "function")
							this.callbackTrackEnd();
					}.bind(this), 500);
				}
				break;

			case "jssid":

				// @todo Maybe catch most digi/speech stuff via the 'player' field?
				var error = file.indexOf("_BASIC.") !== -1;
				if (error) this.setVolume(0);

				this.jsSID.setloadcallback(function() {
					// Reset volume just to be on the safe side
					if (!error) this.setVolume(1);
					if (typeof callback === "function")
						callback.call(this, error);
				}.bind(this));
				this.jsSID.setendcallback(function() {
					if (typeof this.callbackTrackEnd === "function")
						this.callbackTrackEnd();
				}.bind(this), timeout);
				this.jsSID.setbuffercallback(function() {
					if (typeof this.callbackBufferEnded === "function")
						this.callbackBufferEnded();
				}.bind(this), timeout);
				this.jsSID.playcont(); // Added as a hack to avoid a nasty console error
				this.jsSID.loadinit(file, subtune);

				if (error) {
					setTimeout(function() {
						// After half a second just go to the next row
						if (typeof this.callbackTrackEnd === "function")
							this.callbackTrackEnd();
					}.bind(this), 500);
				}
				break;

			case "soasc":
				if (this.howler) this.howler.stop();	// Prevents the time bar from going crazy
				if (this.soasc) this.soasc.abort();		// Allows for premature row off-clicks

				if (this.howler) this.howler.unload();

				if ($("body").attr("data-mobile") !== "0") {
					// NOTE: The AJAX and the Howler code is in this short timeout function to give the loading
					// spinner time to be displayed first. Without the timeout, the synchronous AJAX call would
					// block most web browsers from executing the spinner display until it is moot.
					setTimeout(function() {
						// AJAX is called synchronously to avoid iOS muting the audio upon row click
						this.soasc = $.ajax({
							url:		"php/soasc.php",
							type:		"get",
							async:		false,
							data:		{
								file: 		file,
								sidModel:	this.chip,
								subtune:	subtune,
							}
						}).done(function(data) {
							try {
								data = $.parseJSON(data);
							} catch(e) {
								if (document.location.hostname == "chordian")
									$("body").empty().append(data);
								else
									alert("An error occurred. If it keeps popping up please tell me about it: chordian@gmail.com");
								return false;
							}
							if (data.status == "error") {
								alert(data.message);
								return false;
							}
							this.url = data.url;
							this.modelSOASC = data.model;
						}.bind(this));

						this.howler = new Howl({
							src:	[this.url],
							loop:	$("#loop").hasClass("button-on"),
							html5:	true, // Must use this or files won't play immediately on row click on iOS devices
							onload:	function() {
								// Reset volume in case the "Faster" button-slip trick was used
								this.setVolume(1);
								if (typeof callback === "function")
									callback.call(this);
							}.bind(this),
							onloaderror: function() {
								// ERROR: File not found
								if (typeof callback === "function")
									callback.call(this, true);
								setTimeout(function() {
									// After half a second just go to the next row
									if (typeof this.callbackTrackEnd === "function")
										this.callbackTrackEnd();
								}.bind(this), 500);
							}.bind(this),
							onend: function() {
								// When the song has ended
								if (typeof this.callbackTrackEnd === "function")
									this.callbackTrackEnd();
							}.bind(this),
						});
					}.bind(this), 20);
				} else {
					// NOTE: Not playing on a mobile device makes for a lot more flexibility. The timeout is
					// not necessary anymore and the PHP script can be called asynchronously.
					this.soasc = $.get("php/soasc.php", {
						file: 		file,
						sidModel:	this.chip,
						subtune:	subtune,
					}, function(data) {
						try {
							data = $.parseJSON(data);
						} catch(e) {
							if (document.location.hostname == "chordian")
								$("body").empty().append(data);
							else
								alert("An error occurred. If it keeps popping up please tell me about it: chordian@gmail.com");
							return false;
						}
						if (data.status == "error") {
							alert(data.message);
							return false;
						}
						this.modelSOASC = data.model;
						this.howler = new Howl({
							src:	[data.url],
							loop:	$("#loop").hasClass("button-on"),
							onload:	function() {
								// Reset volume in case the "Faster" button-slip trick was used
								this.setVolume(1);
								if (typeof callback === "function")
									callback.call(this);
							}.bind(this),
							onloaderror: function() {
								// ERROR: File not found
								if (typeof callback === "function")
									callback.call(this, true);
								setTimeout(function() {
									// After half a second just go to the next row
									if (typeof this.callbackTrackEnd === "function")
										this.callbackTrackEnd();
								}.bind(this), 500);
							}.bind(this),
							onend: function() {
								// When the song has ended
								if (typeof this.callbackTrackEnd === "function")
									this.callbackTrackEnd();
							}.bind(this),
						});
					}.bind(this));
				}
				break;

			case "download":
				// Force the browser to download it using an invisible <iframe>
				$("#download").prop("src", file);
				if (typeof callback === "function")
					callback.call(this, error);
				break;
		}
	},

	/**
	 * Unload and destroy object. Not all handlers support this.
	 */
	unload: function() {
		switch (this.emulator) {
			case "websid":
			case "jssid":
				// At least stop the tune
				this.stop();
				break;
			case "soasc":
				if (this.howler) this.howler.unload();
				break;
			case "download":
				break;
		}
	},

	/**
	 * Play the SID tune. Some handlers differ between continuing after a paused state
	 * or a cold start. This too is handled whenever necessary.
	 * 
	 * @param {boolean} forcePlay	TRUE if forcing play state (cold start).
	 */
	 play: function(forcePlay) {
		if (!this.paused) {
			this.voiceMask = 0xF;
			viz.activatePiano(true);
		}
		switch (this.emulator) {
			case "websid":
				if (typeof forcePlay !== "undefined")
					this.WebSid.play();
				else
					this.WebSid.isPaused() ? this.WebSid.resume() : this.WebSid.play();
				break;
			case "jssid":
				if (typeof forcePlay !== "undefined")
					this.jsSID.start(this.subtune);
				else {
					this.paused ? this.jsSID.playcont() : this.jsSID.start(this.subtune);
					this.paused = false;
				}
				break;
			case "soasc":
				if (this.howler) this.howler.play();
				break;
			case "download":
				break;
		}
	},

	/**
	 * Is a song currently playing?
	 * 
	 * @return {boolean}	TRUE if currently playing.
	 */
	isPlaying: function() {
		var playing;
		switch (this.emulator) {
			case "websid":
				playing = !this.WebSid.isPaused();
				break;
			case "jssid":
				// @todo
				break;
			case "soasc":
				// @todo
				break;
			case "download":
				// Unknown
				break;
		}
		return playing;
	},

	/**
	 * Pause the SID tune.
	 */
	pause: function() {
		this.paused = true;
		switch (this.emulator) {
			case "websid":
				this.WebSid.pause();
				break;
			case "jssid":
				this.jsSID.pause();
				break;
			case "soasc":
				if (this.howler) this.howler.pause();
				break;
			case "download":
				break;
		}
	},

	/**
	 * Stop the SID tune.
	 */
	stop: function() {
		this.paused = false;
		viz.activatePiano(false);
		switch (this.emulator) {
			case "websid":
				this.load(); // Dirty hack to make sure the tune is restarted next time it is played
				this.WebSid.pause();
				break;
			case "jssid":
				this.jsSID.playcont(); // Added as a hack to avoid a nasty console error
				this.jsSID.stop();
				this.paused = false;
				break;
			case "soasc":
				if (this.howler) this.howler.stop();
				break;
			case "download":
				break;
		}
		viz.stopScope();
	},

	/**
	 * Speed up the SID tune according to a multiplier.
	 * 
	 * Not all handlers may support this, others may have a multiplier cap.
	 * 
	 * @param {number} multiplier	Multipler (1 = normal speed).
	 */
	speed: function(multiplier) {
		switch (this.emulator) {
			case "websid":
				var normalSampleRate = this.WebSid.getDefaultSampleRate();
				this.WebSid.resetSampleRate(normalSampleRate / multiplier);
				break;
			case "jssid":
				this.jsSID.setSpeedMultiplier(multiplier);
				break;
			case "soasc":
				if (this.howler) this.howler.rate(multiplier !== 1 ? 4.0 : 1.0);
				break;
			case "download":
				break;
		}
	},

	/**
	 * Return an array with various information about the SID tune. This is retrieved
	 * from the SID file itself when possible, otherwise from the database.
	 * 
	 * @param {string} override		Override the current emulator/handler string.
	 * 
	 * @return {array}				The information array.
	 */
	getSongInfo: function(override) {
		var result = {},
			isCGSC = this.file.indexOf("_Compute's Gazette SID Collection") !== -1;
		switch (override || this.emulator) {
			case "websid":
				SIDBackend.updateSongInfo(this.file, result);
				// iOS uses an older WebSID scriptprocessor script
				var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
				if (!iOS) result.maxSubsong = isCGSC ? 0 : result.maxSubsong - 1;
				break;
			case "jssid":
				result.actualSubsong	= this.subtune;
				result.maxSubsong		= isCGSC ? 0 : this.jsSID.getsubtunes() - 1;
				result.songAuthor		= this.jsSID.getauthor();
				result.songName			= this.jsSID.gettitle();
				result.songReleased		= this.jsSID.getinfo();
				break;
			case "soasc":
			case "download":
			case "info":
				// HVSC: We have to ask the server (look in database or parse the file)
				$.ajax("php/info.php", {
					data:		{fullname: this.file.replace(browser.ROOT_HVSC+"/", "")},
					async:		false, // Have to wait to make sure the array is returned correctly
					success:	function(data) {
						try {
							data = $.parseJSON(data);
						} catch(e) {
							if (document.location.hostname == "chordian")
								$("body").empty().append(data);
							else
								alert("An error occurred. If it keeps popping up please tell me about it: chordian@gmail.com");
							return false;
						}
						if (data.status == "error") {
							alert(data.message);
						} else {
							result.actualSubsong	= this.subtune;
							result.loadAddr			= data.info.loadaddr;
							result.maxSubsong		= data.info.subtunes - 1;
							result.songAuthor		= data.info.author;
							result.songName			= data.info.name;
							result.songReleased		= data.info.copyright;
						}
					}
				});
				break;
		}
		return result;
	},

	/**
	 * Set the main volume (usually controlled by a volume slider).
	 * 
	 * @param {float} value		Volume (0 to 1; e.g. half is 0.5).
	 */
	setMainVolume: function(value) {
		this.mainVol = value;
		switch (this.emulator) {
			case "websid":
				this.WebSid.setVolume(value);
				break;
			case "jssid":
				this.jsSID.setvolume(value);
				break;
			case "soasc":
				if (this.howler) this.howler.volume(value);
				break;
			case "download":
				break;
		}
	},

	/**
	 * Set the volume of the SID tune within the span of the main volume.
	 *
	 * @param {float} value		Volume (0 to 1; e.g. half is 0.5).
	 */
	setVolume: function(value) {
		switch (this.emulator) {
			case "websid":
				this.WebSid.setVolume(value * this.mainVol);
				break;
			case "jssid":
				this.jsSID.setvolume(value * this.mainVol);
				break;
			case "soasc":
				if (this.howler) this.howler.volume(value * this.mainVol);
				break;
			case "download":
				break;
		}
	},

	/**
	 * Return the current play time of the SID tune being played.
	 * 
	 * @return {array}	Number of seconds passed so far.
	 */
	getCurrentPlaytime: function() {
		var time = 0;
		switch (this.emulator) {
			case "websid":
				time = this.WebSid.getCurrentPlaytime();
				break;
			case "jssid":
				time = this.jsSID.getplaytime();
				break;
			case "soasc":
				var seek = this.howler ? parseFloat(this.howler.seek()) || 0 : 0;
				time = Math.round(seek);
				break;
			case "download":
				break;
		}
		return isNaN(time) ? 0 : time;
	},

	/**
	 * Return the currently active handler/emulator.
	 * 
	 * @return {string}		Handler in lower case, e.g. "soasc_r5".
	 */
	getHandler: function() {
		return this.emulator === "soasc" ? this.emulator+"_"+this.chip : this.emulator;
	},

	/**
	 * Set the seek of the song. Ignored by emulators.
	 * 
	 * @param {number} seconds	Number of seconds to move the seek to.
	 */
	setSeek: function(seconds) {
		if (this.emulator === "soasc" && this.howler)
			this.howler.seek(seconds);
	},

	/**
	 * Disable the timeout of a SID tune. Used for infinite looping.
	 */
	disableTimeout: function() {
		switch (this.emulator) {
			case "websid":
				this.WebSid._currentTimeout = -1;
				break;
			case "jssid":
				break;
			case "soasc":
				this.howler.loop(true);
				break;
			case "download":
				break;
		}
	},

	/**
	 * Enable the timeout of a SID tune.
	 * 
	 * @param {number} length	Number of seconds before the music times out.
	 */
	enableTimeout: function(length) {
		switch (this.emulator) {
			case "websid":
				this.WebSid._currentTimeout = length * this.WebSid._sampleRate;
				break;
			case "jssid":
				break;
			case "soasc":
				this.howler.loop(false);
				break;
			case "download":
				break;
		}
	},

	/**
	 * Force the SID chip model to be used. Not all handlers support this.
	 * 
	 * @param {string} model	Use "6581" or "8580".
	 */
	setModel: function(model) {
		switch (this.emulator) {
			case "websid":
				SIDBackend.setSID6581(model === "6581" ? 1 : 0);
				break;
			case "jssid":
				this.jsSID.setmodel(model === "6581" ? 6581.0 : 8580.0);
				break;
			case "soasc":
				if (this.chip == "auto") {
					this.chip = model === "6581" ? "r2" : "r5";
					this.load(undefined, undefined, undefined, function() {
						browser.clearSpinner();
						this.chip = "auto";
						this.play();
					}.bind(this));
				}
				break;
			case "download":
				break;
		}
	},

	/**
	 * Return the SID chip model currently used. Not all handlers support this.
	 * 
	 * @return {*}		Returns "6581" or "8580" (or FALSE if not supported).
	 */
	getModel: function() {
		switch (this.emulator) {
			case "websid":
				return SIDBackend.isSID6581() ? "6581" : "8580";
			case "jssid":
				return this.jsSID.getmodel() === 6581.0 ? "6581" : "8580";
			case "soasc":
				return this.chip == "auto" ? this.modelSOASC.substr(3, 4) : false;
			case "download":
				return false;
		}
	},

	/**
	 * Force the encoding to be used. Not all handlers support this.
	 * 
	 * @param {string} encoding		Use "NTSC" or "PAL".
	 */
	setEncoding: function(encoding) {
		switch (this.emulator) {
			case "websid":
				SIDBackend.setNTSC(encoding === "NTSC" ? 1 : 0);
				break;
			case "jssid":
				// jsSID doesn't support this
				// @todo Try changing: this.jsSID.C64_PAL_CPUCLK + this.jsSID.PAL_FRAMERATE
				break;
			case "soasc":
				// Not possible
				break;
			case "download":
				break;
		}
	},

	/**
	 * Return the encoding currently used. Not all handlers support this.
	 * 
	 * @return {*}		Returns "NTSC" or "PAL" (or FALSE if not supported).
	 */
	getEncoding: function() {
		switch (this.emulator) {
			case "websid":
				return SIDBackend.isNTSC() ? "NTSC" : "PAL";
			case "jssid":
				// jsSID always defaults to PAL
				return "PAL";
			case "soasc":
			case "download":
				return false;
		}
	},

	/**
	 * Toggle a a SID voice on or off. This uses a local mask variable which
	 * is reset to 1111 every time a new tune is loaded and played. There are
	 * 4 bits as some emulators also support toggling a digi channel.
	 * 
	 * @param {number} voice	Voice to toggle (1-4).
	 */
	toggleVoice: function(voice) {
		this.voiceMask ^= 1 << (voice - 1); // Toggle a bit in the '1111' mask
		switch (this.emulator) {
			case "websid":
				SIDBackend.enableVoices(this.voiceMask);
				break;
			case "jssid":
				// Don't touch voices in 2SID and 3SID
				this.jsSID.enableVoices(this.voiceMask | 0x1F8);
				break;
			case "soasc":
				// Not possible
				break;
			case "download":
				break;
		}
	},

	/**
	 * Enable all SID voices.
	 */
	enableAllVoices: function() {
		this.voiceMask = 0xF;
		switch (this.emulator) {
			case "websid":
				SIDBackend.enableVoices(0xF);
				break;
			case "jssid":
				// Don't touch voices in 2SID and 3SID
				this.jsSID.enableVoices(0x1FF);
				break;
			case "soasc":
				// Not possible
				break;
			case "download":
				break;
		}
	},

	/**
	 * Return the speed relative to 50hz. Not all handlers support this. If
	 * 0 is returned, the tune uses VBI. If > 0, it uses CIA.
	 * 
	 * @return {*}		Returns the multiplier value (4 = 4x speed), or FALSE.
	 */
	getPace: function() {
		switch (this.emulator) {
			case "websid":
				var cia = SIDBackend.getRAM(0xDC04) + SIDBackend.getRAM(0xDC05) * 256;
				// 19654 relates to 1x; lower values speed up the tune
				return cia ? Math.round(19654 / cia) : 0;
			case "jssid":
				var cia = this.jsSID.getcia();
				return cia ? Math.round(19654 / cia) : 0;
			case "soasc":
			case "download":
				return false;
		}
	},

	/**
	 * Return the type of digi, if used by the song. Not all handlers support this.
	 * 
	 * @return {string}		Returns a short ID string, or empty if digi is not used.
	 */
	getDigiType: function() {
		switch (this.emulator) {
			case "websid":
				return SIDBackend.getDigiTypeDesc();
			case "jssid":
			case "soasc":
			case "download":
				return "";
		}
	},

	/**
	 * Return the sample rate used by the digi samples, if used by the song. Not all
	 * handlers support this.
	 * 
	 * @return {number}		Returns the sample rate, or 0 if digi is not used.
	 */
	getDigiRate: function() {
		switch (this.emulator) {
			case "websid":
				return SIDBackend.getDigiRate();
			case "jssid":
			case "soasc":
			case "download":
				return 0;
		}
	},

	/**
	 * Return the current 8-bit value of a SID register.
	 * 
	 * @param {number} register		Register $D400 to $D41C.
	 * @param {number} chip			SID chip number (default is 1).
	 * 
	 * @return {*}					Byte value of the register, or FALSE.
	 */
	readRegister: function(register, chip) {
		if (register < 0xD400 && register > 0xD41C) return false;
		register -= 0xD400;
		if (typeof chip === "undefined") chip = 0; else chip = --chip;
		switch (this.emulator) {
			case "websid":
				if (chip && typeof SIDBackend.sidFileHeader != "undefined")
					// Use the SID file header to figure out the SID chip address
					// NOTE: A line must be inserted in 'backend_tinyrsid.js' for this to work!
					register += (SIDBackend.sidFileHeader[chip == 1 ? 0x7A : 0x7B] << 4) - 0x400;
				return SIDBackend.getRegisterSID(register);
			case "jssid":
				return this.jsSID.readregister(register + this.jsSID.getSIDAddress(chip));
			case "soasc":
			case "download":
				// Not possible
				return false;
		}
	},
}