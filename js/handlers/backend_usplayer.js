// USBSID-Player for DeepSID
// by LouD - https://github.com/LouDnl/USBSID-Player
//
// A cycle exact C64 in the page: 6510, both CIAs, the VIC and the real KERNAL
// and BASIC ROMs, running the tune's own driver rather than interpreting it.
// The same player runs the command line tool, the USBSID-Pico firmware's onboard
// player and https://usbsid.loudai.nl.
//
// Five outputs, chosen in "Advanced settings" or in top. The "reSIDfp
// (usPlayer)" SID handler ('usplayfp') is this player fixed to audio, listed
// on its own to make plain that it needs no board:
//
//  - webusb  a USBSID-Pico over WebUSB, for real or cloned SID chips.
//  - serial  a USBSID-Pico over Web Serial, for browsers without WebUSB.
//  - asid    ASID over MIDI, so any ASID capable device.
//  - sendsid the file itself to a USBSID-Pico, played by the board's own
//            player. Nothing is emulated in the page, so the visuals have
//            nothing to draw and the time bar is a wall clock.
//  - audio   reSIDfp in the page, through an AudioWorklet. Needs no hardware.
//
// This file is the seam between DeepSID and the player, and holds no emulation
// of its own. The player ships as ES modules in 'js/handlers/usplayer/' and is
// pulled in on demand, which is why everything here has to cope with not being
// ready yet: 'js/player.js' is synchronous from top to bottom and cannot wait
// for a module to arrive. Anything that starts something is queued and replayed
// when the module lands; anything that reads answers with a safe default until
// then.

/**
 * Where the player's modules live.
 *
 * The adapter here is `usplayer-adapter-deepsid.js` and not `usplayer-adapter.js`
 * because it is a fork, not a copy. Every other module in `usplayer/` is byte
 * identical to `player-repo/web`, and is meant to be; the adapter is not, and the
 * name says so rather than leaving the difference to be discovered by a diff.
 *
 * What is different: it imports `usbsid-driver.js`, adopts that driver's
 * `usbsidDevice` singleton in the WebUSB branch, and closes it again on
 * disconnect. DeepSID has no host application to hand the adapter an already
 * connected board, which the other hosts do.
 *
 * A classic script's dynamic import resolves against the *document*, not
 * against the script, so './usplayer/...' from here would look for
 * '/usplayer/...' at the site root and find nothing. document.currentScript is
 * only readable while this file is executing its top level, so it is read now
 * and not later. WASM_SEARCH_PATH is what index.php already sets for Jürgen's
 * emulators and is the fallback if a browser hands us no currentScript.
 */
var USPLAYER_SRC = document.currentScript ? document.currentScript.src : "";

var USPLAYER_PATH = (function() {
	if (USPLAYER_SRC) return USPLAYER_SRC.replace(/[^\/]*$/, "").split("?")[0] + "usplayer/";
	return (typeof window.WASM_SEARCH_PATH === "undefined"
		? "js/handlers/" : window.WASM_SEARCH_PATH) + "usplayer/";
})();

/**
 * DeepSID's own cache buster, passed on to the player.
 *
 * index.php appends "?v=<filemtime>" to every script it loads, this one
 * included, and that is the only version number available here. The adapter
 * puts whatever it is given on the ES module and the wasm as well.
 *
 * Without it those two are fetched by script rather than by the document and can
 * outlive a deploy in the cache. A stale `usbsid.wasm` beside a fresh
 * `usbsid.esm.js` does not fail loudly: it fails as "reSIDfp would not take this
 * rate" and a tune that will not start, which is a long way from the cause. Seen
 * on the live site, and it survived a hard reload.
 */
var USPLAYER_VERSION = (function() {
	var q = USPLAYER_SRC.indexOf("?");
	return q === -1 ? "" : USPLAYER_SRC.substring(q);
})();

/** How often the play time is looked at, for the song end and the loop. */
var USPLAYER_POLL_MS = 200;

/** The modes, in the order they are offered. Kept beside the labels so that the
 *  drop-down boxes in index.php and this file cannot drift apart. */
var USPLAYER_MODES = {
	webusb:	{ adapter: "usplayer",			needs: "a USBSID-Pico",		label: "WebUSB (USBSID-Player)" },
	serial:	{ adapter: "usplayer-serial",		needs: "a USBSID-Pico",		label: "Web Serial (USBSID-Player)" },
	asid:	{ adapter: "usplayer-asid",		needs: "a MIDI output",		label: "ASID (USBSID-Player)" },
	sendsid: { adapter: "usplayer-sendsid",		needs: "a USBSID-Pico",		label: "SendSID (onboard player)" },
	audio:	{ adapter: "usplayer-audio",		needs: "nothing",		label: "reSIDfp (no hardware)" },
};

/**
 * Is an ASID output what is playing, whichever handler is providing it?
 *
 * True for jsSID's own ASID handler and for USBSID-Player in ASID mode. Asked by
 * `browser.js` when it decides which rows can be clicked: ASID carries register
 * writes to a receiver that owns its own chips, and a digi tune is sample data
 * pushed through $d418 far faster than the protocol can carry, so it arrives as
 * noise or as nothing at all.
 */
function usplayerAsidActive() {
	if (typeof SID === "undefined" || !SID) return false;
	if (SID.emulator === "asid") return true;
	return SID.emulator === "usplayer" && !!SID.usplayer && SID.usplayer.mode === "asid";
}

/**
 * Can this browser do what the mode needs?
 *
 * WebUSB and Web Serial are Chromium only, and Web MIDI is missing from Safari.
 * Offering a mode that cannot work gives a tune that plays to nothing and a
 * Connect button that can never succeed, which looks like a broken player rather
 * than an unsupported browser.
 *
 * Audio always answers true: it plays out of the browser and needs nothing
 * attached.
 *
 * @param {string} mode		One of the keys of USPLAYER_MODES
 * @return {boolean}
 */
function usplayerModeAvailable(mode) {
	if (typeof navigator === "undefined") return mode === "audio";
	switch (mode) {
		case "audio":	return true;
		case "webusb":	return !!navigator.usb;
		case "serial":	return !!navigator.serial;
		/* Either interface carries the upload, WebUSB first with Web Serial as
		 * the fallback, so one of the two is enough. */
		case "sendsid":	return !!(navigator.usb || navigator.serial);
		case "asid":	return typeof navigator.requestMIDIAccess === "function";
	}
	return true;
}

/**
 * Grey out the modes this browser cannot do, and say why in the option itself.
 *
 * Called before the player is constructed, so it takes and returns the mode
 * rather than reading it from a SID object that does not exist yet.
 *
 * @param {string} wanted	The stored mode
 * @return {string}		The mode to actually use, audio if the stored one cannot work
 */
function usplayerMarkModeAvailability(wanted) {
	var $sel = $("#select-usplayer-mode");
	if (!$sel.length) return wanted;

	$sel.find("option").each(function() {
		var $opt = $(this), mode = $opt.attr("value");
		if (usplayerModeAvailable(mode)) return;
		$opt.prop("disabled", true);
		/* Guarded, because this can run more than once and the suffix would
		 * otherwise be appended each time. */
		if ($opt.text().indexOf("not in this browser") === -1) {
			$opt.text($opt.text() + " - not in this browser");
		}
	});

	if (usplayerModeAvailable(wanted)) return wanted;

	/* The stored mode cannot work here. Fall back rather than come up in a mode
	 * that plays to nothing, and store it, so the player built after this agrees
	 * with the box. A different browser on the same machine keeps its own choice
	 * because this only rewrites a setting that is unusable where it is read. */
	log("[USBSID-Player] " + wanted + " needs " +
		(USPLAYER_MODES[wanted] ? USPLAYER_MODES[wanted].needs : "support") +
		" this browser does not have, using reSIDfp instead");
	localStorage.setItem("advanced_setting_usplayer_mode", "audio");
	return "audio";
}

/**
 * The DeepSID facing handler.
 *
 * @param {string} mode		One of the keys of USPLAYER_MODES
 */
function USPlayer(mode) {

	this.mode = USPLAYER_MODES[mode] ? mode : "audio";
	this.adapter = null;			// the player's own adapter, once loaded
	this.failed = false;			// true when the module would not load

	this.url = "";				// what to (re)load
	this.subtune = 0;			// 0-based, as DeepSID counts them
	this.playlength = 0;			// seconds, 0 for no timeout
	this.playing = false;
	this.paused = false;
	this.ended = false;			// so the end callback fires once
	this.speedMultiplier = 1;
	this.model = 0;				// 6581 or 8580 when DeepSID has said

	this.loadCallback = null;
	this.endCallback = null;
	this.bufferCallback = null;

	this.header = null;			// the loaded file, for its header
	this.info = {				// answered until the real thing arrives
		maxSubsong:	0,
		songName:	"",
		songAuthor:	"",
		songReleased:	"",
		numSids:	1,
		hasFm:		false,
		sidAddresses:	[],
	};

	this.pollTimer = 0;
	this.queued = null;			// a load that arrived before the module
	this.loadedUrl = "";			// what the adapter is actually holding
	this.loadedSubtune = -1;
	this.freshLoad = false;			// loaded, and nobody has started it yet

	this.ready = this.build();
}

USPlayer.prototype = {

	/**
	 * Import the player and construct its adapter.
	 *
	 * @return {Promise<boolean>} true when the player is usable
	 */
	build: function() {
		return import(USPLAYER_PATH + "usplayer-adapter-deepsid.js" + USPLAYER_VERSION).then(function(module) {
			this.adapter = new module.USPlayerAdapter(USPLAYER_MODES[this.mode].adapter);

			// Restore stored FM/OPL volume; the adapter applies it on load
			var fmVolume = parseInt(localStorage.getItem("volume_fm"), 10);
			if (!isNaN(fmVolume)) {
				this.adapter.setFmVolume(fmVolume);
				$("#volume-fm").val(fmVolume);
			}

			// Our own log and status line, instead of the two panes and the
			// element ids the player's other host owns.
			this.adapter.setHost({
				log: function(line) {
					// Guarded: this can fire from a promise, and main.js owns
					// log(). It has always run by then, but a dead player
					// because of a logging call would be a poor trade.
					if (typeof log === "function") log("[USBSID-Player] " + line);
				},
				status: function(line) {
					// Our own line in top, not '#status-text', which belongs to
					// the WebUSB handler's connect box. It sits under the mode
					// row, so it is also where a refusal to use a board ends up:
					// see the onboard player check in the adapter.
					var warn = line.indexOf("no onboard player") !== -1
						|| line.indexOf("nothing to play through") !== -1;
					$("#usplayer-status").empty().append(line)
						.toggleClass("usplayer-warn", warn);
				},
			});

			// Bring the transport up now rather than at the first tune, so the
			// Connect button and the transport controls can tell the truth
			// about whether there is anything to play through.
			return this.adapter.connect().then(function() {
				this.fillMidiOutputs();
				this.showConnectState();
				this.refreshPlayState();
				return true;
			}.bind(this), function() {
				this.showConnectState();
				this.refreshPlayState();
				return true;	// no board is not a broken player
			}.bind(this));
		}.bind(this)).then(function(ok) {
			if (this.queued) {
				var q = this.queued;
				this.queued = null;
				this._load(q.url, q.subtune, q.playlength, q.notify !== false);
			}
			return ok;
		}.bind(this), function(error) {
			this.failed = true;
			log("[USBSID-Player] could not load the player: " + error);
			return false;
		}.bind(this));
	},

	/**
	 * Change the output mode.
	 *
	 * By reloading the page, which is what changing the SID handler itself does
	 * (see 'div.styledSelect' in main.js). Each mode owns a transport, an audio
	 * graph or a MIDI port, and swapping those under a playing tune is a great
	 * deal of teardown to get right for something that happens once in a
	 * session. The setting has already been stored by the time this is called.
	 *
	 * @param {string} mode		One of the keys of USPLAYER_MODES
	 */
	setMode: function(mode) {
		if (!USPLAYER_MODES[mode] || mode === this.mode) return;
		localStorage.setItem("tab", $("#tabs .selected").attr("data-topic"));
		window.location.reload();
	},

	/** What this mode needs plugged in, for a message that says so. */
	modeNeeds: function() {
		return USPLAYER_MODES[this.mode].needs;
	},

	/**
	 * Is there something for the writes to go to?
	 *
	 * Audio mode always is: it plays out of the browser. The others answer for
	 * their transport, so DeepSID can grey out what would be silence.
	 */
	isConnected: function() {
		return !!(this.adapter && this.adapter.isConnected());
	},

	/**
	 * Is there anything for this mode's writes to reach, right now?
	 *
	 * Not the same question as `isConnected()`. ASID has no link to open: its
	 * transport comes up whether or not the machine has a MIDI output, so a
	 * board mode with nothing attached and an ASID mode with an empty port list
	 * both have to answer no, for different reasons.
	 */
	playable: function() {
		if (this.mode === "audio") return true;
		if (!this.adapter) return false;
		if (this.mode === "asid") {
			var outputs = this.adapter.midiOutputs ? this.adapter.midiOutputs() : [];
			if (!outputs.length) return false;
		}
		return this.isConnected();
	},

	/**
	 * Grey the transport out when pressing it could only produce silence.
	 *
	 * The tune still loads either way, because DeepSID walks a folder on its own
	 * and a handler that refused would stop that dead. But a play button that
	 * plays nothing looks like a broken player rather than an unplugged one, and
	 * the status line under the mode row already says which it is.
	 */
	refreshPlayState: function() {
		if (typeof ctrls === "undefined" || typeof ctrls.state !== "function") return;
		ctrls.state("play/stop", this.playable() ? "enabled" : "disabled");
	},

	/**
	 * Ask for a board or a port, from a user gesture.
	 *
	 * @param {function} [callback]		Called with the new connected state
	 */
	connect: function(callback) {
		if (!this.adapter) return;
		this.adapter.connect().then(function(open) {
			this.showConnectState();
			this.refreshPlayState();
			if (typeof callback === "function") callback(open);
		}.bind(this));
	},

	/**
	 * Let go of the board or the port, so the same button can do both.
	 *
	 * @param {function} [callback]		Called with the new connected state
	 */
	disconnect: function(callback) {
		if (!this.adapter || typeof this.adapter.disconnect !== "function") return;
		this.adapter.disconnect().then(function() {
			this.showConnectState();
			this.refreshPlayState();
			if (typeof callback === "function") callback(this.isConnected());
		}.bind(this));
	},

	/**
	 * Put the transport's real state on the Connect button.
	 *
	 * The button used to be written only by its own click handler, so a board
	 * that was already connected read "CONNECT" until someone pressed it. That
	 * is the normal case rather than the exception: WebUSB and Web Serial both
	 * remember what the origin has been granted, and `_ensure()` takes the
	 * device back without a picker, so on every reload with the board plugged in
	 * the page was playing through hardware while offering to connect to it.
	 */
	showConnectState: function() {
		var $button = $("#usplayer-device-connect");
		if (!$button.length || this.mode === "audio") return;
		/* The button only. The label beside it belongs to
		 * `main.handleTopBox()`, which already says what this mode wants
		 * plugged in, and two places writing one sentence is how they drift. */
		$button.text(this.isConnected() ? "Connected" : "Connect")
			.attr("title", this.isConnected()
				? "Click to disconnect"
				: "Click to connect " + this.modeNeeds());
	},

	/**
	 * List the MIDI outputs ASID mode can play to, and follow the choice.
	 *
	 * Its own picker rather than '#asid-midi-outputs', which the jsSID based ASID
	 * handler fills with indices into its own MIDIAccess: those values mean
	 * nothing here, and rewriting that list would break the other handler.
	 */
	fillMidiOutputs: function() {
		if (this.mode !== "asid" || !this.adapter) return;
		var $sel = $("#select-usplayer-midi");
		if (!$sel.length) return;

		var outputs = this.adapter.midiOutputs();
		$sel.empty();
		if (!outputs.length) {
			$sel.append('<option value="">- no MIDI outputs -</option>');
			this.refreshPlayState();
			return;
		}
		$.each(outputs, function(i, out) {
			$sel.append($("<option></option>").attr("value", out.name).text(out.name));
		});

		// The transport has already picked one; show which, then follow changes.
		$sel.off("change.usplayer").on("change.usplayer", function() {
			this.adapter.selectMidiOutput($sel.val());
			this.refreshPlayState();
		}.bind(this));
		this.adapter.selectMidiOutput($sel.val());
		this.refreshPlayState();
	},

	setLoadCallback:	function(callback) { this.loadCallback = callback; },
	setBufferCallback:	function(callback) { this.bufferCallback = callback; },

	/**
	 * @param {function} callback	Called once when the tune times out
	 * @param {number} seconds		Play length, or 0 to play on for ever
	 */
	setEndCallback: function(callback, seconds) {
		this.endCallback = callback;
		this.playlength = seconds || 0;
		this.ended = false;
	},

	/** Play on for ever, for the loop button. */
	disableTimeout: function() {
		this.playlength = 0;
		this.ended = false;
	},

	/** @param {number} seconds	Play length from the start of the song */
	enableTimeout: function(seconds) {
		this.playlength = seconds || 0;
		this.ended = false;
	},

	/**
	 * Load a tune and start it. This is DeepSID's `SID.load()` arriving here,
	 * and it is the only path that reports back through the load callback.
	 *
	 * @param {string} url			The SID file, as DeepSID names it
	 * @param {number} subtune		0-based, as DeepSID counts them
	 * @param {number} [playlength]		Seconds before the end callback
	 */
	loadTune: function(url, subtune, playlength) {
		this._load(url, subtune, playlength, true);
	},

	/**
	 * @param {boolean} notify	Whether to call the load callback when it lands
	 *
	 * The callback is what browser.js hangs its whole after-a-tune-loaded
	 * sequence on, and that sequence ends in `SID.play(true)`, which comes back
	 * here as `start()`. So a restart must reload **without** reporting a load,
	 * or the two call each other for ever: the tune restarts several times a
	 * second and never plays a note.
	 */
	_load: function(url, subtune, playlength, notify) {
		this.url = url;
		this.subtune = subtune || 0;
		if (typeof playlength !== "undefined") this.playlength = playlength;
		this.ended = false;
		this.freshLoad = false;

		if (!this.adapter) {
			// The module is still on its way. Remember the last thing asked
			// for and do it when it arrives: clicking a tune while the page is
			// still starting up is normal, and losing it looks like a dead
			// player.
			if (!this.failed) this.queued = { url: url, subtune: this.subtune, playlength: this.playlength, notify: notify };
			return;
		}

		// Say so rather than play to nothing. The emulation runs either way and
		// the transport controls all work, so a tune played with no board
		// attached looks exactly like one that is working and makes no sound.
		// The tune is still started: DeepSID moves through a folder on its own
		// and a handler that refused to load would stop that dead.
		if (this.mode !== "audio" && !this.isConnected()) {
			$("#usplayer-status").empty().append("nothing to play through: "
				+ "connect " + this.modeNeeds() + " first");
		}

		this.playing = true;
		this.paused = false;

		// Both count from zero, so the number goes across as it is.
		//
		// It used to have one added to it. `load_sidtune()` is documented as
		// "0 based, so 1 is the second song", and the adapter's SendSID branch
		// was the one place that read the argument as 1 based, so adding one
		// gave the right song over SendSID and the song after the right one in
		// the four modes that emulate here: clicking a row played song 2, and
		// the subtune arrows landed one past wherever the counter said. The
		// adapter now counts from zero in every mode, so this passes it on.
		this.adapter.load(this.subtune, 0, url, function() {
			this.header = this.adapter.bytes();
			var info = this.adapter.getSongInfo();
			this.info = {
				maxSubsong:	info.maxSubsong,
				songName:	info.songName,
				songAuthor:	info.songAuthor,
				songReleased:	info.songReleased,
				numSids:	info.numSids,
				hasFm:		!!info.hasFm,
				sidAddresses:	info.sidAddresses || [],
			};
			this.showFmVolume();
			if (typeof SID !== "undefined" && SID.bufferSize)
				this.setBufferSize(SID.bufferSize.usplayfp);
			// What is loaded, so a restart of the same thing at frame zero can
			// tell there is nothing to do. See start().
			this.loadedUrl = this.url;
			this.loadedSubtune = this.subtune;
			this.freshLoad = true;
			if (this.speedMultiplier > 1) this.setSpeed(this.speedMultiplier);
			this.startPolling();
			if (notify && typeof this.loadCallback === "function") this.loadCallback();
			/* After the host's own callback and not before it: controls.js
			 * enables play/stop as part of finishing a load, so re-asserting
			 * has to happen once that has run. */
			setTimeout(this.refreshPlayState.bind(this), 0);
		}.bind(this));
	},

	/**
	 * Start the loaded tune again, from the subtune it was loaded with.
	 *
	 * A reload rather than a rewind: the emulation has no way back to frame zero
	 * short of loading the tune again, and the file is in the browser's cache by
	 * now. jsSID rewinds by calling the tune's own init routine again; ours
	 * cannot, because a tune that has scribbled over its own data needs the file
	 * back. Silent about it, though: see `_load()`.
	 */
	start: function(subtune) {
		if (typeof subtune !== "undefined") this.subtune = subtune;
		if (!this.url) return;

		// Just loaded and not started by anyone yet: there is nothing to
		// restart, so do not.
		//
		// DeepSID answers a finished load by playing, so one click on a tune
		// arrives here as load-then-start and every tune was fetched, parsed
		// and configured **twice**, which is two reSIDfp builds and two of
		// everything in the log. Rewinding costs a reload for us because the
		// emulation has no way back to frame zero, but a tune that has not
		// been started yet is already at frame zero.
		//
		// A flag and not a look at the clock. This used to ask whether the tune
		// was still under a quarter of a second in, and that is not answerable
		// from here in any of the modes:
		//
		//  - audio  the emulation is in a worker and reports its position three
		//           times a second, so straight after a load the last report is
		//           still the **previous** tune's, and the worker renders ahead
		//           of what is audible in any case.
		//  - board  the emulation is clocked in real time from the load, and
		//           DeepSID's own after-a-load work (the subtune controls, the
		//           info and sundry panes, the CSDb lookup) runs between the
		//           load callback and this call.
		//  - sendsid  the clock is the board's, started when the file was sent.
		//
		// So the test failed, the tune was loaded a second time on top of the
		// first, and the two ran together: audibly fast, then a restart.
		if (this.adapter && this.freshLoad &&
			this.loadedUrl === this.url && this.loadedSubtune === this.subtune) {
			this.freshLoad = false;
			this.playing = true;
			this.paused = false;
			this.ended = false;
			this.adapter.play();
			this.startPolling();
			return;
		}
		this.freshLoad = false;

		this._load(this.url, this.subtune, this.playlength, false);
	},

	/** Carry on from a pause. */
	playCont: function() {
		if (!this.adapter) return;
		this.adapter.play();
		this.paused = false;
		this.playing = true;
		this.startPolling();
	},

	pause: function() {
		if (!this.adapter) return;
		this.adapter.pause();
		this.paused = true;
	},

	stop: function() {
		this.stopPolling();
		if (this.adapter) this.adapter.stop();
		this.playing = false;
		this.paused = false;
		this.ended = false;
		// Stopped is not frame zero: the adapter has torn the tune down, so the
		// next start has to load it again rather than resume nothing.
		this.freshLoad = false;
	},

	isPlaying: function() {
		return this.playing && !this.paused;
	},

	/**
	 * Has the browser refused to let the audio context run yet?
	 *
	 * main.js asks this once, on a "?file=" page load, to decide between playing
	 * straight away and showing the click-to-play cover. In audio mode the
	 * answer before the first tune is yes: the context is opened by the load,
	 * and one opened without a user gesture stays suspended for ever, so an
	 * auto-play would leave a tune running silently with no way to unstick it.
	 * The board modes need no gesture and say no.
	 */
	isSuspended: function() {
		if (!this.adapter) return this.mode === "audio";
		return !!this.adapter.isSuspended();
	},

	/**
	 * Run faster, for the "Faster" button.
	 *
	 * Audibly faster in the board modes, where the writes go out as fast as they
	 * are made. In audio mode nothing can render ahead of a ring that plays at
	 * one times speed, so the extra frames are emulated and their sound dropped:
	 * a fast silent skip forward rather than a chipmunk.
	 *
	 * @param {number} multiplier	1 for normal speed
	 */
	setSpeed: function(multiplier) {
		this.speedMultiplier = multiplier || 1;
		if (!this.adapter) return;
		this.adapter.setSpeed(this.speedMultiplier);
	},

	/**
	 * How loud, 0 to 1.
	 *
	 * Audio mode only, where there is a gain stage after the synthesis. A board
	 * plays at whatever its own output is turned to, and the tune owns $D418, so
	 * there is nothing in the other modes that could be turned down without
	 * changing the tune itself.
	 *
	 * @param {number} value	0 for silence, 1 for full
	 */
	setVolume: function(value) {
		if (!this.adapter) return;
		this.adapter.setVolume(value);
	},

	/** Is there a loudness to change in this mode? */
	hasVolume: function() {
		return this.mode === "audio";
	},

	/**
	 * Set the FM/OPL side of the reSIDfp mix.
	 *
	 * @param {number} percent	0 for silence, 50 is the default, 100 is unity
	 */
	setFmVolume: function(percent) {
		if (!this.adapter) return;
		this.adapter.setFmVolume(percent);
	},

	/**
	 * Set how much audio reSIDfp keeps ready, from DeepSID's "Buffer size".
	 *
	 * A deeper buffer keeps playback steady while the visuals are drawing.
	 * The piano stays in step with the sound, and pausing takes effect that
	 * much later. Only reSIDfp mode has a buffer; the board modes ignore it.
	 *
	 * @param {number} samples	Buffer size in samples, e.g. 16384
	 */
	setBufferSize: function(samples) {
		if (!this.adapter || this.mode !== "audio") return;
		this.adapter.setBufferSamples(samples);
	},

	/** Does the loaded tune have an FM/OPL side to turn? */
	hasFm: function() {
		if (this.mode !== "audio") return false;
		// Tunes older than v5 carry no FM flag: the adapter sees their FM writes
		return !!this.info.hasFm || (!!this.adapter && this.adapter.hasFm());
	},

	/** Show the FM volume slider only for an FM tune in reSIDfp mode. */
	showFmVolume: function() {
		var show = this.hasFm();
		if (show === this.fmVolumeShown) return;
		this.fmVolumeShown = show;
		$("#volume-fm").toggle(show).parent().toggleClass("has-fm", show);
	},

	/** Seconds into the song, the emulation's own count and not wall clock. */
	getPlaytime: function() {
		if (!this.adapter) return 0;
		var ms = this.adapter.playtimeMs();
		return (ms === null || isNaN(ms)) ? 0 : ms / 1000;
	},

	getSubtunes:	function() { return this.info.maxSubsong + 1; },
	getTitle:	function() { return this.info.songName; },
	getAuthor:	function() { return this.info.songAuthor; },
	getReleased:	function() { return this.info.songReleased; },

	/**
	 * The value a SID register was last set to.
	 *
	 * The last write and not a read of the chip, because none of these outputs
	 * can be read: a board over USB cannot be read back at the rate a display
	 * wants, ASID has no read at all, and reSIDfp is behind the audio thread.
	 * Every register a tune uses is write only on real hardware anyway. The two
	 * read only registers, oscillator 3 at $1B and its envelope at $1C, are the
	 * exception and answer 0.
	 *
	 * @param {number} chip		0-based chip number
	 * @param {number} register	0 to 31, so relative to $D400
	 */
	readRegister: function(chip, register) {
		if (!this.adapter) return 0;
		return this.adapter.readRegister((chip || 0) + 1, register);
	},

	/**
	 * A byte of the emulated machine's memory.
	 *
	 * The RAM itself, so an address under I/O answers with what is beneath the
	 * chip. Reading a chip would have side effects, and acknowledging a CIA's
	 * interrupts because a memory view redrew itself would break the tune.
	 *
	 * @param {number} address	$0000 to $FFFF
	 */
	readMemory: function(address) {
		if (!this.adapter) return 0;
		return this.adapter.readMemory(address);
	},

	/** CIA 1 timer A's latch, which is what DeepSID calls the CIA value. */
	getCIA: function() {
		if (!this.adapter) return 0;
		return this.adapter.ciaLatch(1, 0);
	},

	/**
	 * Where a chip is, from the file's own header.
	 *
	 * The same test the other handlers use: a second or third address is only
	 * believed when it is in a range a SID can actually sit at.
	 *
	 * @param {number} chip		0-based chip number
	 * @return {number}		The address, or 0 when there is no such chip
	 */
	getSIDAddress: function(chip) {
		if (!chip) return 0xD400;
		// The emulation's own placement, which also covers v5 multi-SID layouts
		if (this.info.sidAddresses.length)
			return this.info.sidAddresses[chip] || 0;
		if (!this.header || this.header.length < 0x7C) return 0;
		var byte = this.header[chip === 1 ? 0x7A : 0x7B];
		if (byte >= 0x42 && (byte < 0x80 || byte >= 0xE0)) return 0xD000 + byte * 16;
		return 0;
	},

	/**
	 * Hold voices silent, or let them play.
	 *
	 * @param {number} mask		Bits 0 to 2, a set bit being a voice that plays
	 * @param {number} chip		0-based chip number
	 */
	setVoiceMask: function(mask, chip) {
		if (!this.adapter) return;
		for (var voice = 1; voice <= 3; voice++)
			this.adapter.setVoiceMute((chip || 0) + 1, voice, !(mask & (1 << (voice - 1))));
	},

	/**
	 * Which chip model to synthesise.
	 *
	 * Recorded rather than applied: in the board modes it is whatever chip is
	 * socketed and cannot be anything else, and in audio mode reSIDfp is
	 * configured for the model when the tune is loaded, so it takes effect on
	 * the next load. The header's own preference is what is used meanwhile,
	 * which is the right answer for nearly every tune.
	 *
	 * @param {number} model	6581 or 8580
	 */
	setModel: function(model) {
		this.model = model;
	},

	/** The model the file asks for, which is what is being played. */
	getModel: function() {
		if (!this.header || this.header.length < 0x78) return 0;
		return (this.header[0x77] & 0x30) >= 0x20 ? 8580 : 6581;
	},

	/**
	 * The clock the tune asks for, which is the one the machine runs at.
	 *
	 * Bits 2 and 3 of the header's flags word: %01 is PAL, %10 is NTSC and %11
	 * is "either", where PAL is the answer because that is what the tune was
	 * almost certainly written on.
	 */
	getEncoding: function() {
		if (!this.header || this.header.length < 0x78) return "PAL";
		return ((this.header[0x77] & 0x0C) >> 2) === 0x02 ? "NTSC" : "PAL";
	},

	/**
	 * Watch the play time, for the song end and the buffer callback.
	 *
	 * The player has no notion of a play length: it plays until it is stopped.
	 * DeepSID decides how long a song lasts, from HVSC's song lengths or from
	 * its own settings, and expects to be called back. So the clock is read
	 * here, five times a second, which is as often as a display showing seconds
	 * can use.
	 */
	startPolling: function() {
		this.stopPolling();
		this.pollTimer = setInterval(function() {
			if (!this.playing || this.paused) return;
			this.showFmVolume();
			if (typeof this.bufferCallback === "function") this.bufferCallback();
			if (this.playlength > 0 && !this.ended &&
				this.getPlaytime() >= this.playlength) {
				this.ended = true;
				if (typeof this.endCallback === "function") this.endCallback();
			}
		}.bind(this), USPLAYER_POLL_MS);
	},

	stopPolling: function() {
		if (this.pollTimer) {
			clearInterval(this.pollTimer);
			this.pollTimer = 0;
		}
	},
};
