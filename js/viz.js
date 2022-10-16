
/**
 * DeepSID / Visuals
 */

const PAGESIZE_PLAYER = 512;
const NOT_APPLICABLE = '<span class="m">N/A</span>';

const DOT_ACTIVITY = "&#9679;"; // &#8853; &#10012; &#10022; &#11052; &#128498;

function Viz(emulator) {

	this.emulator = emulator.toLowerCase();

	this.pianoBarBackground = "#111";
	this.slowSpeed = 0.3;
	this.maxVoices = 3;
	this.graphMode = 1;
	this.graphPW = false;
	this.graphMods = true;
	this.lineInGraph = false;

	this.runningPiano = false;
	this.runningMemory = false;

	this.stat_fc = [];
	this.stat_reso = [];
	this.stat_vol = [];
	this.stat_freq_ptr = [0, 0, 0];
	this.stat_freq = [[], [], []];
	this.stat_pw = [[], [], []];
	this.stat_adsr = [[], [], []];

	this.ctx_pw = [];
	this.ctx_fc = [];
	this.ctx_res = [];

	this.prevOctave 		= [0, 0, 0, 0, 0, 0, 0, 0, 0];
	this.prevNote 			= [0, 0, 0, 0, 0, 0, 0, 0, 0];
	this.prevGoodWaveform 	= [0, 0, 0, 0, 0, 0, 0, 0, 0];

	this.prevClockspeed = "Unknown";
	this.prevClockspeedStats = "Unknown";

	this.scopeStereo = [[], [], []];

	this.scopeLineColor = [
		"34, 35, 27",	// For bright color theme
		"255, 255, 255"	// For dark color theme
	];

	this.graphColor = [
		{
			background: "#fff",		// For bright color theme
			line:		"#eee",
			octave:		"#eaeaea",
			pwidth:		"#ffd1cb",
			filterOn:	"#f8f888",
			filterOff:	"#ffffe0",

			modulation:	[
				"#000",				// Off
				"#eef",				// Hard synchronization
				"#fee",				// Ring modulation
				"#fef",				// Both
			]
		},
		{
			background:	"#181818",	// For dark color theme
			line:		"#242424",
			octave:		"#292929",
			pwidth:		"#703028",
			filterOn:	"#383424",
			filterOff:	"#28241c",

			modulation:	[
				"#000",				// Off
				"#242434",			// Hard synchronization
				"#342424",			// Ring modulation
				"#342434",			// Both
			]
		}
	];

	this.bufferSize;
	this.visuals;

	// @link http://codebase64.org/doku.php?id=base:pal_frequency_table
	this.sidFrequenciesPAL = [
		//    0       1       2       3       4       5       6       7       8       9       10      11	   PAL
		//	  C       C#      D       D#      E       F       F#      G       G#      A       A#      B
			0x117,  0x127,  0x139,  0x14B,  0x15F,  0x174,  0x18A,  0x1A1,  0x1BA,  0x1D4,  0x1F0,  0x20E,	// Oct 0
			0x22D,  0x24E,  0x271,  0x296,  0x2BE,  0x2E8,  0x314,  0x343,  0x374,  0x3A9,  0x3E1,  0x41C,	// Oct 1
			0x45A,  0x49C,  0x4E2,  0x52D,  0x57C,  0x5CF,  0x628,  0x685,  0x6E8,  0x752,  0x7C1,  0x837,	// Oct 2
			0x8B4,  0x939,  0x9C5,  0xA5A,  0xAF7,  0xB9E,  0xC4F,  0xD0A,  0xDD1,  0xEA3,  0xF82,  0x106E,	// Oct 3
			0x1168, 0x1271, 0x138A, 0x14B3, 0x15EE, 0x173C, 0x189E, 0x1A15, 0x1BA2, 0x1D46, 0x1F04, 0x20DC,	// Oct 4
			0x22D0, 0x24E2, 0x2714, 0x2967, 0x2BDD, 0x2E79, 0x313C, 0x3429, 0x3744, 0x3A8D, 0x3E08, 0x41B8,	// Oct 5
			0x45A1, 0x49C5, 0x4E28, 0x52CD, 0x57BA, 0x5CF1, 0x6278, 0x6853, 0x6E87, 0x751A, 0x7C10, 0x8371,	// Oct 6
			0x8B42, 0x9389, 0x9C4F, 0xA59B, 0xAF74, 0xB9E2, 0xC4F0, 0xD0A6, 0xDD0E, 0xEA33, 0xF820, 0xFFFF	// Oct 7
	];
	
	// @link http://codebase64.org/doku.php?id=base:ntsc_frequency_table
	this.sidFrequenciesNTSC = [
		//    0       1       2       3       4       5       6       7       8       9       10      11	   NTSC
		//	  C       C#      D       D#      E       F       F#      G       G#      A       A#      B
			0x10C,  0x11C,  0x12D,  0x13F,  0x152,  0x166,  0x17B,  0x192,  0x1AA,  0x1C3,  0x1DE,  0x1FA,	// Oct 0
			0x218,  0x238,  0x25A,  0x27E,  0x2A4,  0x2CC,  0x2F7,  0x324,  0x354,  0x386,  0x3BC,  0x3F5,	// Oct 1
			0x431,  0x471,  0x4B5,  0x4FC,  0x548,  0x598,  0x5EE,  0x648,  0x6A9,  0x70D,  0x779,  0x7EA,	// Oct 2
			0x862,  0x8E2,  0x96A,  0x9F8,  0xA90,  0xB30,  0xBDC,  0xC90,  0xD52,  0xE1A,  0xEF2,  0xFD4,	// Oct 3
			0x10C4, 0x11C4, 0x12D4, 0x13F0, 0x1520, 0x1660, 0x17B8, 0x1920, 0x1AA4, 0x1C34, 0x1DE4, 0x1FA8,	// Oct 4
			0x2188, 0x2388, 0x25A8, 0x27E0, 0x2A40, 0x2CC0, 0x2F70, 0x3240, 0x3548, 0x3868, 0x3BC8, 0x3F50,	// Oct 5
			0x4310, 0x4710, 0x4B50, 0x4FC0, 0x5480, 0x5980, 0x5EE0, 0x6480, 0x6A90, 0x70D0, 0x7790, 0x7EA0,	// Oct 6
			0x8620, 0x8E20, 0x96A0, 0x9F80, 0xA900, 0xB300, 0xBDC0, 0xC900, 0xD520, 0xE1A0, 0xEF20, 0xFD40	// Oct 7
	];

	this.pianoKeyColors = ["#fff", "#000", "#fff", "#000", "#fff", "#fff", "#000", "#fff", "#000", "#fff", "#000", "#fff"];
	
	this.waveformColors = [
		"#000000",			// $00
		"#70f070",			// $10 - Triangle
		"#8888ff",			// $20 - Sawtooth
		"#00ffff",			// $30 - Triangle + Sawtooth			(8580 only)
		"#ff7766",			// $40 - Pulse
		"#eeee22",			// $50 - Pulse + Triangle
		"#ff66ff",			// $60 - Pulse + Sawtooth				(8580 only)
		"#eeeeee",			// $70 - Pulse + Sawtooth + Triangle	(8580 only)
		"#9e9e9e",			// $80 - Noise
		"#000000",			// $90
		"#000000",			// $A0
		"#000000",			// $B0
		"#000000",			// $C0
		"#000000",			// $D0
		"#000000",			// $E0
		"#000000",			// $F0
	];

	// Set buffer size as previously stored or default to 16384 bytes
	this.bufferSize = localStorage.getItem("buffer");
	if (this.bufferSize == null) this.bufferSize = 16384;
	$("#page .dropdown-buffer").val(this.bufferSize);

	this.setEmuButton(this.emulator);

	this.initScope();
	setTimeout(function() {
		this.initGraph(browser.chips);
		//this.animateBufferEnded();
		this.animateFrames();
	}.bind(this), 1);
	this.addEvents();

	// @link https://stackoverflow.com/a/29972322/2242348
	/*var interval = 16; // 16 ms = 60 hz
	var expected = Date.now() + interval;
	setTimeout(step.bind(this), interval);
	function step() {
		var dt = Date.now() - expected; // The drift (positive for overshooting)
		if (dt > interval) {
			// Piano view is lagging
		}
		requestAnimationFrame(this.animateFrames.bind(this));
	
		expected += interval;
		setTimeout(step.bind(this), Math.max(0, interval - dt)); // Take into account drift
	}*/
}

Viz.prototype = {

	/**
	 * Add the events pertinent to this class.
	 */
	addEvents: function() {
		$(window).on("keyup", this.onKeyUp.bind(this));
		$("#visuals-piano,#visuals-graph,#visuals-stats,#sticky-right-buttons").on("click", ".button-toggle,.button-radio,.button-icon", this.onToggleClick.bind(this));
		$("#visuals-piano").on("click", ".piano-voice", this.onVoiceClick.bind(this));
		$("#visuals-piano,#visuals-graph,#topic-settings .dropdown-buffer").on("change", this.onChangeBufferSize.bind(this));
		$("#sticky-visuals").on("click", "button", this.onVisualsClick.bind(this));
		$("#visuals-memory .block-info").on("click", "button", this.onPlayerBrowseClick.bind(this));
	},

	/**
	 * Handle hotkeys for turning voices ON or OFF.
	 * 
	 * @param {*} event 
	 */
	onKeyUp: function(event) {
		if (!$("input[type=text],input[type=password],textarea,select").is(":focus")) {
			if (!event.shiftKey && event.keyCode != 16)
				// Normal voice toggle used to release all SOLO buttons in the STATS view
				$("#visuals-stats .stats-solo").removeClass("button-off button-on").addClass("button-off");
			if (event.keyCode == 49 || event.keyCode == 81) {			// Keyup '1' or 'q'
				if (event.shiftKey) { // Reverse (solo)
					this.reverseVoice(1);
				} else
					// Toggle a SID voice 1 ON/OFF using the piano toggle buttons
					$("#page .pv0").trigger("click");
			} else if (event.keyCode == 50 || event.keyCode == 87) {	// Keyup '2' or 'w'
				if (event.shiftKey) {
					this.reverseVoice(2);
				} else
					$("#page .pv1").trigger("click");
			} else if (event.keyCode == 51 || event.keyCode == 69) {	// Keyup '3' or 'e'
				if (event.shiftKey) {
					this.reverseVoice(3);
				} else
					$("#page .pv2").trigger("click");
			} else if (event.keyCode == 52 || event.keyCode == 82) {	// Keyup '4' or 'r'
				if (event.shiftKey) {
					this.reverseVoice(4);
				} else {
					// Using direct call (piano view doesn't support digi tunes)
					SID.toggleVoice(4);
					$("#scope4").css("opacity", (voiceMask[0] & 0x8 ? "0.3" : "1"));
				}
			}
		}
	},

	/**
	 * Reverse (solo) a voice.
	 * 
	 * @param {number} voice 		Voice (1, 2, 3 or 4).
	 */
	reverseVoice: function(voice) {
		var voiceMask = [SID.voiceMask[0], SID.voiceMask[1], SID.voiceMask[2]];
		this.enableAllPianoVoices();
		switch (parseInt(voice)) {
			case 1:
				if ((browser.chips == 1 && voiceMask[0] != 0x1) ||
					(browser.chips > 1 && (voiceMask[1] && voiceMask[2]) ||
					(browser.chips > 1 && voiceMask[0] != 0xF))) {
					$("#page .pv1,#page .pv2").trigger("click");
					if (browser.chips == 1) {
						SID.toggleVoice(4);
						$("#scope4").css("opacity", "0.3");
					}
				}
				break;
			case 2:
				if ((browser.chips == 1 && voiceMask[0] != 0x2) ||
					(browser.chips > 1 && (voiceMask[0] && voiceMask[2]) || voiceMask[1] != 0xF)) {
					$("#page .pv0,#page .pv2").trigger("click");
					if (browser.chips == 1) {
						SID.toggleVoice(4);
						$("#scope4").css("opacity", "0.3");
					}
				}
				break;
			case 3:
				if ((browser.chips == 1 && voiceMask[0] != 0x4) ||
					(browser.chips > 1 && (voiceMask[0] && voiceMask[1]) || voiceMask[2] != 0xF)) {
					$("#page .pv0,#page .pv1").trigger("click");
					if (browser.chips == 1) {
						SID.toggleVoice(4);
						$("#scope4").css("opacity", "0.3");
					}
				}
				break;
			case 4:
				if (browser.chips == 1 && voiceMask[0] != 0x8)
					$("#page .pv0,#page .pv1,#page .pv2").trigger("click");
				break;
		}
		// SOLO buttons in the STATS view
		var $stateVoice = $("#stats-solo-"+voice);
		var state = $stateVoice.hasClass("button-off");
		$("#visuals-stats .stats-solo").removeClass("button-off button-on").addClass("button-off");
		$stateVoice.removeClass("button-off button-on").addClass("button-"+(state ? "on" : "off"))
	},

	/**
	 * Click a toggle button in a visuals view, checkbox- or radio button style.
	 * 
	 * @param {*} event 
	 */
	onToggleClick: function(event) {
		var $this = $(event.target);
		if ($this.hasClass("button-toggle")) {
			// Checkbox style toggle button
			if ($this.hasClass("stats-solo")) {
				// SOLO buttons are special as they're both radio and toggle buttons at the same time
				this.reverseVoice(event.target.id.slice(-1));
				return;
			} else
				$this.empty().append($this.hasClass("button-off") ? "On" : "Off");
			if (event.target.id === "piano-slow") {
				SID.speed($this.hasClass("button-off") ? this.slowSpeed : 1);
			} else if (event.target.id === "graph-pw") {
				this.graphPW = $this.hasClass("button-off");
			} else if (event.target.id === "graph-mods") {
				this.graphMods = $this.hasClass("button-off");
			} else if (event.target.id === "memory-lc-toggle") {
				// Toggle C64 font letter casing in MEMO visuals view
				if ($this.hasClass("button-off")) {
					$("#visuals-memory .uc").hide();
					$("#visuals-memory .lc").show();
				} else {
					$("#visuals-memory .lc").hide();
					$("#visuals-memory .uc").show();
				}
			} else {
				// Clear piano keyboards to make sure there are no hanging colors on it
				$("#visuals-piano .piano svg .black").css("transition", "none").attr("fill", "#000");
				$("#visuals-piano .piano svg .white").css("transition", "none").attr("fill", "#fff");
				// Also clear canvas bars
				var _ctx_pw_height = $("#piano-pw0").height(), _ctx_pw_width = $("#piano-pw0").width();			
				for (var voice = 0; voice <= 2; voice++) {
					var _ctx_pw = $("#piano-pw"+voice)[0].getContext("2d");
					_ctx_pw.fillStyle = this.pianoBarBackground;
					_ctx_pw.fillRect(0, 0, _ctx_pw_width, _ctx_pw_height);
				}
			}
		} else if ($this.hasClass("button-radio") || $this.hasClass("button-icon")) {
			if ($this.hasClass("disabled")) return false;
			// Radio-button style button
			var groupClass = $this.attr("data-group");
			// First pop all buttons in this group up - there can only be one
			$("#page ."+groupClass).removeClass("button-off button-on").addClass("button-off");
			switch (groupClass) {
				case 'viz-emu':
					// Adjust the top left drop-down box with SID handlers
					var emulator = $this.attr("data-emu");
					if (emulator == "websid" || emulator == "legacy")
						// Select the WebSid version that was last selected (avoids reloading the page)
						emulator = isLegacyWebSid ? "legacy" : "websid";
					$("#dropdown-emulator").styledSetValue(emulator)
						.next("div.styledSelect").trigger("change");
					break;
				case 'viz-layout':
					this.graphMode = $this.hasClass("viz-rows") ? 0 : 1;
					this.initGraph(browser.chips);
					break;
			}
		}
		// Now swap the class state of this button
		var state = $this.hasClass("button-off");
		$this.removeClass("button-off button-on").addClass("button-"+(state ? "on" : "off"))
	},

	/**
	 * Piano: Click a square voice ON/OFF button on a piano keyboard.
	 * 
	 * @param {*} event 
	 */
	onVoiceClick: function(event) {
		var voice = parseInt(event.target.classList[1].substr(-1));
		if (voice == 2 && browser.chips == 2) return; // Third keyboard is disabled for 2SID tunes
		if (this.emulator == "legacy" && browser.chips > 1) return;

		// Release all SOLO buttons in the STATS view
		$("#visuals-stats .stats-solo").removeClass("button-off button-on").addClass("button-off");

		var $this = $(event.target);
		// Swap the class state of this button
		var state = $this.hasClass("voice-off");
		$this.removeClass("voice-off voice-on").addClass("voice-"+(state ? "on" : "off"));

		$("#page .piano"+voice).css("opacity", (state ? "1" : "0.1"));
		if (browser.chips == 1) {
			// Standard voices ON/OFF for one SID chip
			SID.toggleVoice(voice + 1);
			$("#scope"+(voice + 1)).css("opacity", (state ? "1" : "0.3"));
			$("#graph"+voice).css("opacity", (state ? "1" : "0.3"));
		} else {
			// For 2SID and 3SID, toggle all voices ON/OFF on an entire SID chip
			var chip = voice + 1;
			for (var voice = 1; voice <= 4; voice++) {
				SID.toggleVoice(voice, chip);
				if (voice < 4)
					$("#graph"+(((chip * 3) + (voice - 1)) - 3)).css("opacity", (state ? "1" : "0.3"));
			}
			if (chip == 1)
				for (var scope = 1; scope <= 4; scope++)
					$("#scope"+scope).css("opacity", (state ? "1" : "0.3"));
		}
	},

	/**
	 * When a different buffer size has been selected in the dedicated drop-down box.
	 * 
	 * @param {*} event 
	 */
	onChangeBufferSize: function(event) {
		this.bufferSize = $(event.target).val();
		localStorage.setItem("buffer", this.bufferSize);
		// Make sure all drop-down boxes of this kind agree on the new value
		$("#page .dropdown-buffer").val(this.bufferSize);
		// Re-trigger the same emulator again to set the buffer size
		// NOTE: Doing it in a specific visuals view is deliberate (avoids multiple triggering).
		$("#visuals-piano .viz-emu.button-on").trigger("click");
		this.setBufferMessage(this.emulator);
	},

	/**
	 * When a different view of visuals has been selected by clicking a header button.
	 * 
	 * @param {*} event 
	 */
	onVisualsClick: function(event) {
		this.visuals = $(event.target).attr("data-visual");
		if (typeof this.visuals == "undefined") return false;
		$("#topic-visuals .visuals,#sticky-visuals .waveform-colors,#memory-lc").hide();
		$("#sticky-visuals .visuals-buttons .button-on").removeClass("button-on").addClass("button-off");
		$("#sticky-visuals .icon-"+this.visuals).removeClass("button-off").addClass("button-on");
		$("#visuals-"+this.visuals).show();
		switch (this.visuals) {
			case "piano":
				$("#sticky-visuals .waveform-colors").show();
				break;
			case "graph":
				$("#sticky-visuals .waveform-colors").show();
				break;
			case "memory":
				$("#memory-lc").show();
				break;
			case "stats":
				break;
		}
	},

	/**
	 * When clicking a player block browser left/right button.
	 * 
	 * @param {*} event 
	 */
	onPlayerBrowseClick: function(event) {
		var $this = $(event.target);
		if ($this.hasClass("disabled")) return false;

		this.blockPlayer = [];
		$player = $("#visuals-memory .block-player");
		$player.empty();
		if ($this.hasClass("player-to-left")) {
			this.playerAddrCurrent -= PAGESIZE_PLAYER;
			if (this.playerAddrCurrent == this.playerAddrStart)
				$this.addClass("disabled");
			$("#visuals-memory .player-to-right").removeClass("disabled");
			$player.append(this.showMemoryBlock(this.playerAddrCurrent, this.playerAddrCurrent + PAGESIZE_PLAYER - 1, this.blockPlayer));
		} else {
			this.playerAddrCurrent += PAGESIZE_PLAYER;
			if (this.playerAddrCurrent + PAGESIZE_PLAYER  > this.playerAddrEnd) {
				$this.addClass("disabled");
				$player.append(this.showMemoryBlock(this.playerAddrCurrent, this.playerAddrEnd, this.blockPlayer));
			} else
				$player.append(this.showMemoryBlock(this.playerAddrCurrent, this.playerAddrCurrent + PAGESIZE_PLAYER - 1, this.blockPlayer));
			$("#visuals-memory .player-to-left").removeClass("disabled");
		}
	},

	/**
	 * Pop all of the emulator radio buttons up for now.
	 */
	allEmuButtonsOff: function() {
		$("#page .viz-emu").removeClass("button-off button-on").addClass("button-off");		
	},

	/**
	 * Press an emulator button in the visuals views or show a warning about the need.
	 * 
	 * @param {string} emulator 
	 */
	setEmuButton: function(emulator) {
		if (emulator == "websid" || emulator == "legacy" || emulator == "jssid") {
			$("#page .viz-"+emulator).addClass("button-on"); 
			$("#page .viz-msg-emu").hide();
		} else
			$("#page .viz-msg-emu").show();
		this.setBufferMessage(emulator);
	},

	/**
	 * If buffer size is not at lowest value then show a message about it in both
	 * of the visuals views, except in WebSid (HQ) mode.
	 */
	setBufferMessage: function(emulator) {
		this.bufferSize > 1024 && $("#page .viz-msg-emu").css("display") == "none" && emulator != "websid"
			? $("#page .viz-msg-buffer").show()
			: $("#page .viz-msg-buffer").hide();
	},

	/**
	 * Piano: Start or stop animating the piano keyboards.
	 * 
	 * @param {boolean} activate 
	 */
	activatePiano: function(activate) {
		if (miniPlayer || $("body").attr("data-mobile") !== "0") return;

		// Clear all keyboard notes to default piano colors
		$("#visuals-piano .piano svg .black").css("transition", "none").attr("fill", "#000");
		$("#visuals-piano .piano svg .white").css("transition", "none").attr("fill", "#fff");
	
		this.enableAllPianoVoices();

		// Cache the canvas contexts
		for (var keyboard = 0; keyboard <= 2; keyboard++) {
			this.ctx_pw[keyboard] = $("#piano-pw"+keyboard)[0].getContext("2d");
			this.ctx_fc[keyboard] = $("#piano-fc"+keyboard)[0].getContext("2d"),
			this.ctx_res[keyboard] = $("#piano-res"+keyboard)[0].getContext("2d");
		}
	
		// Get the canvas dimensions
		this.ctx_pw_height = this.ctx_fc_height = 8;
		this.ctx_pw_width = this.ctx_fc_width = 200;
		this.ctx_res_height = 30, this.ctx_res_width = 3;

		if (activate) {
	
			this.prevOctave = [0, 0, 0, 0, 0, 0, 0, 0, 0],
			this.prevNote = [0, 0, 0, 0, 0, 0, 0, 0, 0],
			this.prevGoodWaveform = [0, 0, 0, 0, 0, 0, 0, 0, 0],
			this.prevClockspeed = "Unknown";

			this.runningPiano = true; // Start animating

		} else {
	
			this.runningPiano = false;
	
			// Clear canvas bars
			this.ctx_pw_height = $("#piano-pw0").height(), this.ctx_pw_width = $("#piano-pw0").width();
			for (var keyboard = 0; keyboard <= 2; keyboard++) {
				this.ctx_pw[keyboard].fillStyle = this.pianoBarBackground;
				this.ctx_pw[keyboard].fillRect(0, 0, this.ctx_pw_width, this.ctx_pw_height);
				this.ctx_fc[keyboard].fillStyle = this.ctx_res[keyboard].fillStyle = this.pianoBarBackground;
				this.ctx_fc[keyboard].fillRect(0, 0, $("#piano-fc"+keyboard).width(), $("#piano-fc"+keyboard).height());
				this.ctx_res[keyboard].fillRect(0, 0, $("#piano-res"+keyboard).width(), $("#piano-res"+keyboard).height());
			}
	
			// Turn off ring mod and hard sync arrow lamps
			$("#visuals-piano .piano-ringmod").removeClass("pr-on pr-off").addClass("pr-off");
			$("#visuals-piano .piano-hardsync").removeClass("ph-on ph-off").addClass("ph-off");
	
			// Reset filter filet
			$("#visuals-piano .filet").css({
				fill:	"#000",
				stroke:	"#000",
			});
			$("#visuals-piano .piano-top-panel").css("border-bottom", "1px solid #7a7a7a");
	
			// Turn off filter passband LED lamps
			$("#visuals-piano .piano-pb-led").removeClass("pb-on pb-off").addClass("pb-off");
		}
	},

	/**
	 * Piano: Update piano keyboards.
	 * 
	 * This is called by SID.setCallbackBufferEnded().
	 */
	animatePiano: function() {
		// Only if the tab and view are active
		if (!this.runningPiano || $("#tabs .selected").attr("data-topic") !== "visuals" ||
			!$("#sticky-visuals .icon-piano").hasClass("button-on")) return; 

		var useOneKeyboard = $("#piano-combine").hasClass("button-on") || browser.chips > 1;
		var maxChips = useOneKeyboard ? browser.chips : 1;

		// Cache some of the DOM stuff (possibly not that useful anymore since the buffer end overhaul)
		var $piano_ringmod = $("#visuals-piano .piano-ringmod"),
			$piano_hardsync = $("#visuals-piano .piano-hardsync"),
			$piano_rm = [$("#visuals-piano .piano-rm0"), $("#visuals-piano .piano-rm1"), $("#visuals-piano .piano-rm2")],
			$piano_hs = [$("#visuals-piano .piano-hs0"), $("#visuals-piano .piano-hs1"), $("#visuals-piano .piano-hs2")],
			$ff = [$("#visuals-piano .ff0"), $("#visuals-piano .ff1"), $("#visuals-piano .ff2")],
			$ptp = [$("#visuals-piano .ptp0"), $("#visuals-piano .ptp1"), $("#visuals-piano .ptp2")],
			$piano_pb_led = [$("#visuals-piano .piano-pb-led0"), $("#visuals-piano .piano-pb-led1"), $("#visuals-piano .piano-pb-led2")],
			$pb_lp_div = [$("#visuals-piano .pb-lp0 div"), $("#visuals-piano .pb-lp1 div"), $("#visuals-piano .pb-lp2 div")],
			$pb_bp_div = [$("#visuals-piano .pb-bp0 div"), $("#visuals-piano .pb-bp1 div"), $("#visuals-piano .pb-bp2 div")],
			$pb_hp_div = [$("#visuals-piano .pb-hp0 div"), $("#visuals-piano .pb-hp1 div"), $("#visuals-piano .pb-hp2 div")];

		for (var chip = 1; chip <= maxChips; chip++) {
			for (var voice = 0; voice <= 2; voice++) {

				// Combine into top keyboard?
				var keyboard = useOneKeyboard ? chip - 1 : voice;

				// Get the raw frequency
				var freq = SID.readRegister(0xD400 + voice * 7, chip) + SID.readRegister(0xD401 + voice * 7, chip) * 256,
					closest = null, indexMatch = 0, clockspeed;
				try {
					clockspeed = browser.playlist[browser.songPos].clockspeed;
					this.prevClockspeed = clockspeed;
				} catch(e) {
					clockspeed = this.prevClockspeed; // Type error usually happens when leaving a folder while playing
				}
				// Find the closest match in the array of note frequencies (PAL or NTSC table)
				this.sidFrequencies = clockspeed.substr(0, 4) === "NTSC" || browser.path.indexOf("Compute's Gazette SID Collection") !== -1 ? this.sidFrequenciesNTSC : this.sidFrequenciesPAL;
				$.each(this.sidFrequencies, function(index) {
					if (closest == null || Math.abs(this - freq) < Math.abs(closest - freq)) {
						closest = this;
						indexMatch = index;
					}
				});
				var fillColor,
					octave = ~~(indexMatch / 12),
					note = indexMatch % 12,
					waveform = SID.readRegister(0xD404 + voice * 7, chip) >> 4,
					voice_and_chip = ((chip * 3) + voice) - 3,
					pianoGateIsOff = $("#piano-gate").hasClass("button-off");
				waveform = waveform ? this.prevGoodWaveform[voice_and_chip] = waveform : waveform = this.prevGoodWaveform[voice_and_chip];
				if (octave !== this.prevOctave[voice_and_chip] || note !== this.prevNote[voice_and_chip]) {
					// Clear the previous piano key
					$("#v"+keyboard+"_oct"+this.prevOctave[voice_and_chip]+"_"+this.prevNote[voice_and_chip]).css("transition", "none").attr("fill", this.pianoKeyColors[this.prevNote[voice_and_chip]]);
					this.prevOctave[voice_and_chip] = octave;
					this.prevNote[voice_and_chip] = note;
				}
				if ((SID.readRegister(0xD404 + voice * 7, chip) & 1) || pianoGateIsOff) {
					// Gate ON
					if ((waveform >= 1 && waveform <= 7) || (waveform == 8 && $("#piano-noise").hasClass("button-on"))) {
						// The waveform is good so color the key on the piano
						fillColor = SID.emulator == "websid" && !pianoGateIsOff
							? this.getEnvelopeColor(voice, chip, this.pianoKeyColors[note], waveform)
							: this.waveformColors[waveform];
						$("#v"+keyboard+"_oct"+octave+"_"+note).css("transition", "none").attr("fill", fillColor);
					}
				} else {
					// Gate OFF
					fillColor = SID.emulator == "websid"
						? this.getEnvelopeColor(voice, chip, this.pianoKeyColors[note], waveform)
						: this.pianoKeyColors[note];
					$("#v"+keyboard+"_oct"+octave+"_"+note).css("transition", "none").attr("fill", fillColor);
				}

				// Show the pulse width as a horizontal canvas bar
				var pw = SID.readRegister(0xD402 + voice * 7, chip) + (SID.readRegister(0xD403 + voice * 7, chip) & 0xF) * 256;
				if (useOneKeyboard) {
					// Share tinier bars in the top keyboard
					this.ctx_pw[keyboard].fillStyle = "#882f24";
					this.ctx_pw[keyboard].fillRect(0, (this.ctx_pw_height / 3) * voice, (pw * 100 / 4095) * this.ctx_pw_width / 100, 1);
					this.ctx_pw[keyboard].fillRect(0, ((this.ctx_pw_height / 3) * voice) + 1, 1, (this.ctx_pw_height / 3) - 1);
					this.ctx_pw[keyboard].fillStyle = this.waveformColors[4];
					this.ctx_pw[keyboard].fillRect(1, ((this.ctx_pw_height / 3) * voice) + 1, ((pw * 100 / 4095) * this.ctx_pw_width / 100) - 1, (this.ctx_pw_height / 3) - 1);
					this.ctx_pw[keyboard].fillStyle = this.pianoBarBackground;
					this.ctx_pw[keyboard].fillRect(((pw * 100 / 4095) * this.ctx_pw_width / 100) + 1, (this.ctx_pw_height / 3) * voice, this.ctx_pw_width + 1, this.ctx_pw_height / 3);
				} else {
					// Use a bar on each keyboard
					this.ctx_pw[voice].fillStyle = this.waveformColors[4];
					this.ctx_pw[voice].fillRect(0, 0, (pw * 100 / 4095) * this.ctx_pw_width / 100, this.ctx_pw_height);
					this.ctx_pw[voice].fillStyle = this.pianoBarBackground;
					this.ctx_pw[voice].fillRect((pw * 100 / 4095) * this.ctx_pw_width / 100, 0, this.ctx_pw_width, this.ctx_pw_height);
				}

				if (useOneKeyboard) {
					// Indicating ring mod and hard sync doesn't make any sense in combined mode
					$piano_ringmod.removeClass("pr-on pr-off").addClass("pr-off");
					$piano_hardsync.removeClass("ph-on ph-off").addClass("ph-off");
				} else {
					// Indicate ring mod and/or hard sync
					$piano_rm[voice].removeClass("pr-on pr-off");
					SID.readRegister(0xD404 + voice * 7) & 0x4
						? $piano_rm[voice].addClass("pr-on")
						: $piano_rm[voice].addClass("pr-off");
					$piano_hs[voice].removeClass("ph-on ph-off");
					SID.readRegister(0xD404 + voice * 7) & 0x2
						? $piano_hs[voice].addClass("ph-on")
						: $piano_hs[voice].addClass("ph-off");
				}

				// Color the filet above the keys if filter is enabled for this voice
				var filterOn = SID.readRegister(0xD417, chip) & (useOneKeyboard ? 7 : 1 << voice);
				$ff[keyboard].css({
					fill:	(filterOn ? "#a26300" : "#000"),
					stroke:	(filterOn ? "#a26300" : "#000"),
				});
				$ptp[keyboard].css("border-bottom", "1px solid "+(filterOn ? "#cc7c00" : "#7a7a7a"));

				if (useOneKeyboard || voice == 0) {
					// Show the filter cutoff as a horizontal canvas bar
					var fc = SID.readRegister(0xD416, chip) << 3 | (SID.readRegister(0xD415, chip) & 0x7);
					this.ctx_fc[chip - 1].fillStyle = "#cc7c00";
					this.ctx_fc[chip - 1].fillRect(0, 0, (fc * 100 / 2047) * this.ctx_fc_width / 100, this.ctx_fc_height);
					this.ctx_fc[chip - 1].fillStyle = this.pianoBarBackground;
					this.ctx_fc[chip - 1].fillRect((fc * 100 / 2047) * this.ctx_fc_width / 100, 0, this.ctx_fc_width, this.ctx_fc_height);

					// Show the resonance as a small vertical canvas bar
					var res = SID.readRegister(0xD417, chip) >> 4,
						fillHeight = (res * 100 / 15) * this.ctx_res_height / 100;
					this.ctx_res[chip - 1].fillStyle = "#fec700";
					this.ctx_res[chip - 1].fillRect(0, this.ctx_res_height - fillHeight, this.ctx_res_width, fillHeight);
					this.ctx_res[chip - 1].fillStyle = this.pianoBarBackground;
					this.ctx_res[chip - 1].fillRect(0, 0, this.ctx_res_width, this.ctx_res_height - fillHeight);

					// Indicate filter passband in the LED lamps
					$piano_pb_led[chip - 1].removeClass("pb-on pb-off");
					SID.readRegister(0xD418, chip) & 0x10 ? $pb_lp_div[chip - 1].addClass("pb-on") : $pb_lp_div[chip - 1].addClass("pb-off");
					SID.readRegister(0xD418, chip) & 0x20 ? $pb_bp_div[chip - 1].addClass("pb-on") : $pb_bp_div[chip - 1].addClass("pb-off");
					SID.readRegister(0xD418, chip) & 0x40 ? $pb_hp_div[chip - 1].addClass("pb-on") : $pb_hp_div[chip - 1].addClass("pb-off");
				}
			}
		}
	},

	/**
	 * Get the waveform color based on the current ADSR envelope level (HQ WebSid only).
	 * 
	 * @param {number} voice		Voice to read (1-3).
	 * @param {number} chips		Number of SID chips (default is 1).
	 * @param {string} key			Color of piano key (#000 or #FFF).
	 * @param {number} waveform		Waveform (after >> 4).
	 */
	getEnvelopeColor: function(voice, chip, key, waveform) {
		var level = SID.readLevel(voice + 1, chip) * 3;
		if (level > 255) level = 255;
		level ^= 0xFF;
		// Black or white piano key?
		var fillLevel = key == "#000" ? -level : level;
		return this.lightenDarkenColor(this.waveformColors[waveform], fillLevel / 1.75);
	},

	/**
	 * Piano: Enable all voices, both in the emulator and on the piano keyboards.
	 * 
	 * Also sets slow speed and replaces combine button with 2SID/3SID when relevant.
	 */
	enableAllPianoVoices: function() {
		SID.enableAllVoices();
		$("#visuals-piano .piano-voice").removeClass("voice-off voice-on").addClass("voice-on");
		$("#visuals-piano .piano,#graph .graph-area,#scope1,#scope2,#scope3,#scope4").css("opacity", "1");
		// Also snuck this in to set slow speed again if need be
		setTimeout(function() {
			if ($("#piano-slow").hasClass("button-on")) SID.speed(this.slowSpeed);
		}, 1);
		// And also this to replace the piano combine button with 2SID/3SID when relevant
		if (browser.chips == 1) {
			$("#visuals-piano .ptp2").css("opacity", "1");
			$("#visuals-piano .pv-wrap").removeClass("pv-c2sid pv-c3sid");
			$("#piano-combine-area label,#piano-combine-area button").show();
			$("#piano-combine-area span,#visuals-piano .piano-filter1,#visuals-piano .piano-filter2,#visuals-piano .chip-address").hide();
		} else {
			$("#piano-combine-area label,#piano-combine-area button").hide();
			$("#piano-combine-area span")
				.empty()
				.removeClass("color-2sid color-3sid")
				.addClass("color-"+browser.chips+"sid")
				.append(browser.chips+"SID")
				.show();
			$("#visuals-piano .chip1 span").empty().append("$"+SID.getSIDAddress(2).toString(16).toUpperCase());
			$("#visuals-piano .piano-filter1,#visuals-piano .chip0,#visuals-piano .chip1").show();
			if (browser.chips == 3) {
				$("#visuals-piano .chip2 span").empty().append("$"+SID.getSIDAddress(3).toString(16).toUpperCase());
				$("#visuals-piano .piano-filter2,#visuals-piano .chip2").show();
				$("#visuals-piano .ptp2,#page .piano2").css("opacity", "1");
				$("#visuals-piano .pv-wrap").removeClass("pv-c2sid pv-c3sid").addClass("pv-c3sid");
			} else {
				$("#visuals-piano .piano-filter2,#visuals-piano .chip2").hide();
				$("#visuals-piano .ptp2,#page .piano2").css("opacity", "0.25");
				$("#visuals-piano .pv2").removeClass("voice-off voice-on").addClass("voice-off");
				$("#visuals-piano .pv-wrap").removeClass("pv-c2sid pv-c3sid").addClass("pv-c2sid");
			}
		}
	},

	/**
	 * Scope: Initialize and draw the oscilloscope canvas boxes.
	 * 
	 * This makes use of 'sid_tracer.js' (renamed to 'scope.js' in DeepSID) which
	 * was originally written by Jürgen Wothke for the Tiny'R'Sid web site.
	 */
	initScope: function() {
		this.scopeMode = true;
		this.scopeZoom = 5; // Use 1 (closest) to 5 (farthest)

		this.tabOscMode = "";

		// Draw the canvas areas for the oscilloscope voices, as well as the message handler
		var canvas = '';
		for (var voice = 1; voice <= 4; voice++)
			canvas += '<canvas class="scope" id="scope'+voice+'" style="display:none;"></canvas>';
		$("#stopic-osc").empty().append(canvas+'<div class="sundryMsg" style="display:none;"></div>');

		if (isLegacyWebSid) {
			this.scopeVoice1 = new VoiceDisplay("scope1", function() { return scope.getDataVoice1(); }, false);
			this.scopeVoice2 = new VoiceDisplay("scope2", function() { return scope.getDataVoice2(); }, false);
			this.scopeVoice3 = new VoiceDisplay("scope3", function() { return scope.getDataVoice3(); }, false);
			this.scopeVoice4 = new VoiceDisplay("scope4", function() { return scope.getDataVoice4(); }, true);		
		} else {
			this.scopeVoice1 = new VoiceDisplay("scope1", scope, function() { return scope.getData(0); }, false);
			this.scopeVoice2 = new VoiceDisplay("scope2", scope, function() { return scope.getData(1); }, false);
			this.scopeVoice3 = new VoiceDisplay("scope3", scope, function() { return scope.getData(2); }, false);
			this.scopeVoice4 = new VoiceDisplay("scope4", scope, function() { return scope.getData(3); }, true);

			this.scopeVoice1.setSize(512, 70);
			this.scopeVoice2.setSize(512, 70);
			this.scopeVoice3.setSize(512, 70);
			this.scopeVoice4.setSize(512, 70);
		}

		// ToDo!
		/*
			Skip everything LegacyWebSid as it doesn't have stereo panning

			Add button for "Enable WebSid (HQ)" if handler is anything else
		*/

		// WIP STEREO
		for (var chip = 1; chip < 4; chip++) {
			for (var voice = 1; voice < 4; voice++)
				$("#stereo-s"+chip+"v"+voice+"-scope").remove("canvas").append('<canvas class="scope" id="scope-s'+chip+'v'+voice+'"></canvas>');
		}

		if (!isLegacyWebSid) {
			this.scopeStereo[0][0] = new VoiceDisplay("scope-s1v1", scope, function() { return scope.getData(0); }, false);
			this.scopeStereo[0][1] = new VoiceDisplay("scope-s1v2", scope, function() { return scope.getData(1); }, false);
			this.scopeStereo[0][2] = new VoiceDisplay("scope-s1v3", scope, function() { return scope.getData(2); }, false);

			this.scopeStereo[1][0] = new VoiceDisplay("scope-s2v1", scope, function() { return scope.getData(4); }, false);
			this.scopeStereo[1][1] = new VoiceDisplay("scope-s2v2", scope, function() { return scope.getData(5); }, false);
			this.scopeStereo[1][2] = new VoiceDisplay("scope-s2v3", scope, function() { return scope.getData(6); }, false);

			this.scopeStereo[2][0] = new VoiceDisplay("scope-s3v1", scope, function() { return scope.getData(8); }, false);
			this.scopeStereo[2][1] = new VoiceDisplay("scope-s3v2", scope, function() { return scope.getData(9); }, false);
			this.scopeStereo[2][2] = new VoiceDisplay("scope-s3v3", scope, function() { return scope.getData(10); }, false);
		}
	},

	/**
	 * Scope: Animate the oscilloscope canvas boxes in the sundry box.
	 * 
	 * Called by: requestAnimationFrame().
	 * 
	 * This makes use of 'sid_tracer.js' (renamed to 'scope.js' in DeepSID) which
	 * was originally written by Jürgen Wothke for the Tiny'R'Sid web site.
	 */
	animateScope: function() {

		// TAB: Stereo

		if ($("#sundry-tabs .selected").attr("data-topic") == "stereo") {

			// WIP STEREO
			if (SID.isPlaying()) {
				for (var chip = 0; chip < 3; chip++) {
					for (var voice = 0; voice < 3; voice++) {
						if (isLegacyWebSid) {
							this.scopeStereo[chip][voice].redrawGraph(this.scopeMode, parseInt(this.scopeZoom));
						} else {
							scope.setMode(this.scopeMode, parseInt(this.scopeZoom));
			
							this.scopeStereo[chip][voice].redrawGraph();
			
							this.scopeStereo[chip][voice].setStrokeColor("rgba("+(this.scopeLineColor[colorTheme])+", 1.0)");
						}
					}
				}
			}
		}

		// TAB: Scope

		if ($("#sundry-tabs .selected").attr("data-topic") !== "osc") return; // Tab not active
		if (SID.emulator !== "websid" && SID.emulator !== "legacy") {
			if (this.tabOscMode !== "NOTWEBSID") {
				$("#scope1,#scope2,#scope3,#scope4").hide(); // Don't use 'canvas' or '.scope' here
				$("#stopic-osc .sundryMsg").empty().append('This tab requires the <button class="set-websid">WebSid</button> emulator.').show();
				this.tabOscMode = "NOTWEBSID";
			}
			return;
		} else if (isLegacyWebSid && this.bufferSize < 16384) {
			if (this.tabOscMode !== "NOT16K") {
				$("#scope1,#scope2,#scope3,#scope4").hide();
				$("#stopic-osc .sundryMsg").empty().append('A buffer size of <button id="set-16k" style="font-size:13px;line-height:13px;">16384</button> is required.').show();
				this.tabOscMode = "NOT16K";
			}
			return;
		} else if (this.tabOscMode !== "OSC") {
			// Okay to draw oscilloscope voices again now
			if (isLegacyWebSid)
				scope.setOutputSize(this.scopeMode ? 16384 : 246 << this.scopeZoom);
			$("#stopic-osc .sundryMsg").hide();
			$("#scope1,#scope2,#scope3,#scope4").show();
			this.tabOscMode = "OSC";
		}
		if (SID.isPlaying()) {
			if (isLegacyWebSid) {
				this.scopeVoice1.redrawGraph(this.scopeMode, parseInt(this.scopeZoom));
				this.scopeVoice2.redrawGraph(this.scopeMode, parseInt(this.scopeZoom));
				this.scopeVoice3.redrawGraph(this.scopeMode, parseInt(this.scopeZoom));
				this.scopeVoice4.redrawGraph(this.scopeMode, parseInt(this.scopeZoom));
			} else {
				scope.setMode(this.scopeMode, parseInt(this.scopeZoom));

				this.scopeVoice1.redrawGraph();
				this.scopeVoice2.redrawGraph();
				this.scopeVoice3.redrawGraph();
				this.scopeVoice4.redrawGraph();

				this.scopeVoice1.setStrokeColor("rgba("+(this.scopeLineColor[colorTheme])+", 1.0)");
				this.scopeVoice2.setStrokeColor("rgba("+(this.scopeLineColor[colorTheme])+", 1.0)");
				this.scopeVoice3.setStrokeColor("rgba("+(this.scopeLineColor[colorTheme])+", 1.0)");
				this.scopeVoice4.setStrokeColor("rgba("+(this.scopeLineColor[colorTheme])+", 1.0)");
				}
		}
	},

	/**
	 * Scope: Show centered horizontal lines to indicate that the music has stopped.
	 */
	stopScope: function() {

		// TAB: Stereo

		for (var voice = 1; voice <= 4; voice++) {
			var canvas = $("#scope"+voice)[0];
			if (typeof canvas == "undefined") continue;
			var ctx = canvas.getContext("2d");
			ctx.clearRect(0, 0, 512, 70);
			ctx.strokeStyle = "rgba("+(this.scopeLineColor[colorTheme])+", 0.4)"; // Faded color too
			ctx.beginPath();
			ctx.moveTo(0, 35);
			ctx.lineTo(511, 35);
			ctx.stroke();
		}

		// TAB: Scope

		for (var chip = 0; chip <= 3; chip++) {			
			for (var voice = 0; voice <= 3; voice++) {
				// Just clear as the stereo slider has sort of its own center line
				var canvas = $("#scope-s"+chip+"v"+voice)[0];
				if (typeof canvas == "undefined") continue;
				var ctx = canvas.getContext("2d");
				ctx.clearRect(0, 0, 512, 70);
			}
		}
	},

	/**
	 * Graph: Initialize and draw the canvas areas.
	 * 
	 * @param {number} chips		Number of SID chips (default is 1).
	 */
	initGraph: function(chips) {

		if (miniPlayer || $("body").attr("data-mobile") !== "0") return;

		this.canvas_area = [], this.ctx_area = [], this.area_width = [], this.area_height = [];
		this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

		this.maxVoices = typeof chips === "undefined" ? 3 : chips * 3;
		var totalHeight = $("#page").outerHeight() - 127, totalWidth = 834, graphHeight, graphWidth;

		$("#graph .graph-area").hide();
		
		switch (this.graphMode) {
			case 0:
				// Rows
				graphWidth = totalWidth - 0.1;
				graphHeight = (totalHeight / this.maxVoices) - 0.1;
				for (var voice = 0; voice < this.maxVoices; voice++) {
					$("#graph"+voice).css({
						display:	"inline-block",
						top:		((totalHeight / this.maxVoices) * voice) - (0.3 * voice),
						left:		"0",
						zIndex:		this.maxVoices - voice
					});
				}
				break;
			case 1:
				// Columns (original |||)
				graphWidth = totalWidth / this.maxVoices;
				graphHeight = totalHeight - 1;
				for (var voice = 0; voice < this.maxVoices; voice++) {
					$("#graph"+voice).css({
						display:	"inline-block",
						top:		"0",
						left: 		((totalWidth / this.maxVoices) * voice) - (0.3 * voice),
					});
				}
				break;
		}

		$("#graph").height(totalHeight);
		$("#graph .graph-area")
			.height(graphHeight)
			.empty().append('<canvas height="'+graphHeight+'" width="'+graphWidth+'"></canvas><div></div>');

		// Create canvas areas and get their contexts
		for (var voice = 0; voice < this.maxVoices; voice++) {
			this.canvas_area[voice] = $("#graph"+voice+" canvas")[0];
			$("#graph"+voice+" div").append(voice + 1);
			this.ctx_area[voice] = this.canvas_area[voice].getContext("2d");
			this.area_width[voice] = graphWidth;
			this.area_height[voice] = graphHeight - 1;
			// Clear the areas
			this.ctx_area[voice].clearRect(0, 0, this.area_width[voice], this.area_height[voice]);
		}

		// Clone the waveform color array with darker color versions
		this.darkerWaveformColors = this.waveformColors.slice();
		$.each(this.darkerWaveformColors, function(i, color) {
			this.darkerWaveformColors[i] = this.lightenDarkenColor(color, -40);
		}.bind(this));
	},

	/**
	 * Graph: Animate the canvas areas.
	 * 
	 * Called by: requestAnimationFrame() - use 'viz' instead of 'this' here.
	 */
	animateGraph: function() {
		// Not available on mobile devices, and the 'Graph' view and its tab must both be visible
		if (miniPlayer || $("body").attr("data-mobile") !== "0" || $("#tabs .selected").attr("data-topic") !== "visuals"
			|| !$("#sticky-visuals .icon-graph").hasClass("button-on")) return;
		if (colorTheme == null) colorTheme = 0;

		for (var voice = 0; voice < viz.maxVoices; voice++) {

			var chip = ~~(voice / 3) + 1,
				rawVoice = voice - ~~(voice / 3) * 3;

			// Color the top line background to begin with
			viz.ctx_area[voice].fillStyle = viz.lineInGraph ? viz.graphColor[colorTheme].line : viz.graphColor[colorTheme].background;
			viz.ctx_area[voice].fillRect(0, 0, viz.area_width[voice], 1);

			if (!viz.lineInGraph && (SID.readRegister(0xD417, chip) & (1 << rawVoice))) { // Filter on?
				// Strong yellow up to filter cutoff frequency
				var fc = SID.readRegister(0xD416, chip) << 3 | (SID.readRegister(0xD415, chip) & 0x7),
					start = viz.graphMode ? 0 : viz.area_width[voice] / 2,
					max = viz.graphMode ? viz.area_width[voice] : viz.area_width[voice] / 2.666;
				var x = (fc / 0x07FF) * max;
				viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].filterOn;
				viz.ctx_area[voice].beginPath();
				viz.ctx_area[voice].moveTo(start, 0);
				viz.ctx_area[voice].lineTo(start + x, 0);
				viz.ctx_area[voice].stroke();
				// Weaker yellow the rest of the way
				viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].filterOff;
				viz.ctx_area[voice].beginPath();
				viz.ctx_area[voice].moveTo(start + x + 1, 0);
				viz.ctx_area[voice].lineTo(start + max, 0);
				viz.ctx_area[voice].stroke();
			}

			// Add octave dividers
			viz.ctx_area[voice].lineWidth = 1;
			viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].octave;
			for (var freq = 8192; freq < 8192 * 8; freq += 8192) {
				var x = (freq / 0xFFFF) * viz.area_width[voice];
				x = x | 0; // This rounds off to avoiding anti-aliased lines
				viz.ctx_area[voice].beginPath();
				viz.ctx_area[voice].moveTo(x, 0);
				viz.ctx_area[voice].lineTo(x, 1);
				viz.ctx_area[voice].stroke();
			}

			// Find the X coordinate that corresponds to the current SID voice frequency
			var freq = SID.readRegister(0xD400 + rawVoice * 7, chip) + SID.readRegister(0xD401 + rawVoice * 7, chip) * 256;
			var x = (freq / 0xFFFF) * viz.area_width[voice];
			x = x | 0;

			var waveform = SID.readRegister(0xD404 + rawVoice * 7, chip) >> 4,
				thisModulation = (SID.readRegister(0xD404 + rawVoice * 7, chip) & 6) >> 1,
				prevModulation = (SID.readRegister(0xD404 + (rawVoice == 2 ? 0 : rawVoice + 1) * 7, chip) & 6) >> 1;

			// For pulse width, make middle (0x800) king and shrink for a sensible pixel width
			var pw = SID.readRegister(0xD402 + rawVoice * 7, chip) + (SID.readRegister(0xD403 + rawVoice * 7, chip) & 0xF) * 256;
			pw = pw < 2048 ? pw : pw ^ 0xFFF;
			pw /= (viz.graphMode ? 128 : 48); // Smaller value here equals a bigger "coat"

			// Draw the dot representing the frequency
			if (viz.darkerWaveformColors[waveform] != "#000000") {
				viz.ctx_area[voice].lineWidth = 2;
				viz.ctx_area[voice].globalAlpha = SID.readRegister(0xD404 + rawVoice * 7, chip) & 1 ? 1 : 0.5; // Gate ON / OFF
				if (viz.graphMods && thisModulation) {
					// Paint from frequency dot and left towards edge of area ("reaching for previous voice")
					viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].modulation[thisModulation];
					viz.ctx_area[voice].beginPath();
					viz.ctx_area[voice].moveTo(x - 2, 0);
					viz.ctx_area[voice].lineTo(0, 0);
					viz.ctx_area[voice].stroke();
				}
				if (viz.graphMods && prevModulation) {
					// Paint from frequency dot and right towards edge of area ("reaching towards master")
					viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].modulation[prevModulation];
					viz.ctx_area[voice].beginPath();
					viz.ctx_area[voice].moveTo(x + 2, 0);
					viz.ctx_area[voice].lineTo(viz.area_width[voice], 0);
					viz.ctx_area[voice].stroke();
				}
				if (waveform == 4 || waveform == 5) {
					var center = viz.graphPW ? x : (0xF000 / 0xFFFF) * viz.area_width[voice],
						groove = viz.graphPW ? 1 : -0.5;
					// Show pulse width as a "coat" (around frequency dot or in the right side)
					viz.ctx_area[voice].strokeStyle = viz.graphColor[colorTheme].pwidth;
					viz.ctx_area[voice].beginPath();
					viz.ctx_area[voice].moveTo(center - pw, 0);
					viz.ctx_area[voice].lineTo(center - groove, 0);
					viz.ctx_area[voice].moveTo(center + groove, 0);
					viz.ctx_area[voice].lineTo(center + pw, 0);
					viz.ctx_area[voice].stroke();
				}
				viz.ctx_area[voice].strokeStyle = viz.darkerWaveformColors[waveform];
				viz.ctx_area[voice].beginPath();
				viz.ctx_area[voice].moveTo(x, 0);
				viz.ctx_area[voice].lineTo(x, 1);
				viz.ctx_area[voice].stroke();
			}

			// Now scroll the area downwards
			viz.ctx_area[voice].globalAlpha = 1;
			if (viz.isSafari) {
				// Slower but necessary on Mac Safari due to a bug in their drawImage() handling
				var rect = viz.ctx_area[voice].getImageData(1, 0, viz.area_width[voice], viz.area_height[voice] - 1);
				viz.ctx_area[voice].putImageData(rect, 1, 1);
			} else {
				// Fastest (no hitches on my PC) and works in both Firefox, Chrome and Edge
				viz.ctx_area[voice].drawImage(viz.canvas_area[voice],
					1, 0, viz.area_width[voice], viz.area_height[voice] - 1,
					1, 1, viz.area_width[voice], viz.area_height[voice] - 1);
			}
		}
		viz.lineInGraph = false;
	},

	/**
	 * Lighten or darken a CSS-style color.
	 * 
	 * @link https://css-tricks.com/snippets/javascript/lighten-darken-color/
	 * 
	 * @param {string} col	Color string, e.g. "#FF807E".
	 * @param {number} amt	Amount, e.g. -40 to darken.
	 * 
	 * @return {string}		The updated color string.
	 */
	lightenDarkenColor: function(col, amt) {

		var usePound = false;

		if (col[0] == "#") {
			col = col.slice(1);
			usePound = true;
		}

		var num = parseInt(col, 16);
		var r = (num >> 16) + amt;
	 
		if (r > 255) r = 255;
		else if (r < 0) r = 0;

		var b = ((num >> 8) & 0x00FF) + amt;

		if (b > 255) b = 255;
		else if (b < 0) b = 0;

		var g = (num & 0x0000FF) + amt;

		if (g > 255) g = 255;
		else if (g < 0) g = 0;

		// With Frank's fix
		return (usePound ? "#" : "") + String("000000" + (g | (b << 8) | (r << 16)).toString(16)).slice(-6);
	},

	/**
	 * Memory: Build a block for a table cell with a monitor-style memory dump of
	 * the RAM in a Commodore 64.
	 * 
	 * @param {number} addrStart	Start address in C64 RAM.
	 * @param {number} addrEnd		End address in C64 RAM.
	 * @param {array} arrMemory		Array to contain the bytes of the address span.
	 * 
	 * @return {string}				HTML block.
	 */
	showMemoryBlock: function(addrStart, addrEnd, arrMemory) {
		var isLC = $("#memory-lc-toggle").hasClass("button-on"), dispNone = ' style="display:none;"';
		var row = 0, zebra = false, block = hexrow = "",
			petscii_LC = '<span class="lc"'+(isLC ? '' : dispNone)+'>',
			petscii_UC = '<span class="uc"'+(isLC ? dispNone : '')+'>';

		for (var addr = addrStart; addr <= addrEnd; addr++) {
			row++;
			//if (row == 5 || row == 9 || row == 13 || row == 16) {
			if (row == 1 || row == 5 || row == 9 || row == 13)
				// Change color for the next four bytes
				zebra = !zebra;

			var byte = SID.readMemory(addr);
			arrMemory.push(byte);
			var cByte = this.convertByte(byte);

			// Decide zebra striping colors for the byte and PETSCII
			var normal = zebra ? ' class="bt"' : '';
				negated = zebra ? ' class="bt n"' : ' class="n"';

			// Wrap a byte and a PETSCII in a memory address <SPAN> so it can be referred to later
			hexrow += '<span id="h'+addr+'"'+normal+'>'+cByte.hex+'</span>';
			petscii_LC += '<span id="l'+addr+'"'+(byte > 0x80 ? negated : normal)+'>'+cByte.lc+'</span>';
			petscii_UC += '<span id="u'+addr+'"'+(byte > 0x80 ? negated : normal)+'>'+cByte.uc+'</span>';

			if (addr == addrEnd) {
				petscii_LC += '</span>';
				petscii_UC += '</span>';
				// Just pad the rest with nothingness
				for (; row < 16; row++) {
					hexrow += "&nbsp;&nbsp;&nbsp;";
					addr++;
				}
			}

			if (row == 16) {
				// Build the row with C64 address, hexademical bytes and PETSCII characters
				block += this.paddedAddress(addr - 15)+"&nbsp;"+hexrow+petscii_LC+'</span>'+petscii_UC+'</span><br />';
				hexrow = "";
				petscii_LC = '<span class="lc"'+(isLC ? '' : dispNone)+'>',
				petscii_UC = '<span class="uc"'+(isLC ? dispNone : '')+'>';
				row = 0;
			}
		}
		return block;
	},

	/**
	 * Covert a byte to hexadecimal + both lower and upper case PETSCII characters.
	 * 
	 * NOTE: The caller must handle the negative class for PETSCII.
	 * 
	 * @param {number} byte		Byte value 0 to 255.
	 * 
	 * @return {array}			Array with strings for all types.
	 */
	convertByte: function(byte) {

		var half = byte & 0x7F, pLC, pUC;

		// Lower case PETSCII
		if (half == 0)							{ pLC = String.fromCharCode(half + 64); }
		else if (half >= 1 && half <= 26)		{ pLC = String.fromCharCode(half + 96); }
		else if (half >= 27 && half <= 31)		{ pLC = "&#"+(57344 + half + 64)+";"; }
		else if (half == 32)					{ pLC = "&nbsp;"; }
		else if (half >= 33 && half <= 59)		{ pLC = String.fromCharCode(half); }
		else if (half == 60)					{ pLC = "&lt;"; }
		else if (half >= 61 && half <= 63)		{ pLC = String.fromCharCode(half); }
		else if (half == 64)					{ pLC = "&#"+(57344 + half + 32)+";"; }
		else if (half >= 65 && half <= 90)		{ pLC = String.fromCharCode(half); }
		else if (half >= 91 && half <= 93)		{ pLC = "&#"+(57344 + half + 32)+";"; }
		else if (half >= 94 && half <= 95)		{ pLC = "."; } // ??
		else if (half >= 96 && half <= 104)		{ pLC = "&#"+(57344 + half + 64)+";"; }
		else if (half == 105)					{ pLC = "."; } // ??
		else if (half >= 106 && half <= 121)	{ pLC = "&#"+(57344 + half + 64)+";"; }
		else if (half == 122)					{ pLC = "."; } // ??
		else if (half >= 123 && half <= 127)	{ pLC = "&#"+(57344 + half + 64)+";"; }

		// Upper case PETSCII
		if (half >= 0 && half <= 26)			{ pUC = String.fromCharCode(half + 64); }
		else if (half >= 27 && half <= 31)		{ pUC = "&#"+(57344 + half + 64)+";"; }
		else if (half == 32)					{ pUC = "&nbsp;"; }
		else if (half >= 33 && half <= 59)		{ pUC = String.fromCharCode(half); }
		else if (half == 60)					{ pUC = "&lt;"; }
		else if (half >= 61 && half <= 63)		{ pUC = String.fromCharCode(half); }
		else if (half >= 64 && half <= 95)		{ pUC = "&#"+(57344 + half + 32)+";"; }
		else if (half >= 96 && half <= 127)		{ pUC = "&#"+(57344 + half + 64)+";"; }

		return {
			hex:	(byte < 0x10 ? "0" : "")+byte.toString(16).toUpperCase()+"&nbsp;",	// Hexadecimal 00 to FF
			lc:		pLC,																// Lower case PETSCII char
			uc:		pUC,																// Upper case PETSCII char
		};
	},

	/**
	 * Memory: Compare a current block of bytes from previous update, then only
	 * update those hexadecimal bytes and PETSCII characters that changed.
	 * 
	 * @param {number} addrStart	Start address in C64 RAM.
	 * @param {number} addrEnd		End address in C64 RAM.
	 * @param {array} arrMemory		Array to contain the bytes of the address span.
	 */
	patchMemoryBlock: function(addrStart, addrEnd, arrMemory) {
		var byte, cByte;
		for (var addr = addrStart; addr <= addrEnd; addr++) {
			byte = SID.readMemory(addr);
			if (byte != arrMemory[addr - addrStart]) {
				// Byte has changed; update array and <SPAN> addresses in HTML block
				arrMemory[addr - addrStart] = byte;
				cByte = this.convertByte(byte);
				$("#h"+addr).empty().append(cByte.hex).removeClass("ch").addClass("ch"); // Apply red color
				$("#l"+addr).empty().append(cByte.lc).removeClass("ch").addClass("ch");
				$("#u"+addr).empty().append(cByte.uc).removeClass("ch").addClass("ch");
			}
		}
	},

	/**
	 * Memory: Activate or deactive the updating of the monitor-style tables.
	 * 
	 * @param {boolean} activate	TRUE to activate, FALSE to turn off.
	 */
	activateMemory: function(activate) {
		if (miniPlayer || $("body").attr("data-mobile") !== "0") return;

		if (activate && typeof browser.songPos != "undefined") {

			var $zp = $("#visuals-memory .block-zp"),
				$player = $("#visuals-memory .block-player");
			$zp.empty();
			$player.empty();

			this.blockZP = [], this.blockPlayer = [];
			$zp.append(this.showMemoryBlock(0x0000, 0x00FF, this.blockZP));

			this.playerAddrStart = this.playerAddrCurrent = Number(browser.playlist[browser.songPos].address);
			this.playerAddrEnd = this.playerAddrStart + Number(browser.playlist[browser.songPos].size) - 3;

			if (browser.playlist[browser.songPos].fullname.substr(-4) == ".mus") {
				// MUS files in CGSC doesn't have an interesting player block to look at
				$player.append('<div class="msg">N/A</div>');
				this.playerAddrCurrent = 0;
				$("#visuals-memory .player-to-left,#visuals-memory .player-to-right")
					.removeClass("disabled").addClass("disabled");
			} else {
				$("#player-addr").empty().append("$"+this.paddedAddress(this.playerAddrCurrent)+"-$"+this.paddedAddress(this.playerAddrEnd));
				$player.append(this.showMemoryBlock(this.playerAddrCurrent, this.playerAddrCurrent + PAGESIZE_PLAYER - 1, this.blockPlayer));
			}
		}
		this.runningMemory = activate;
	},

	/**
	 * Memory: Update the monitor-style tables.
	 * 
	 * This is called by SID.setCallbackBufferEnded().
	 */
	animateMemory: function() {
		// Only if the tab and view are active
		if (!this.runningMemory || $("#tabs .selected").attr("data-topic") !== "visuals" ||
			!$("#sticky-visuals .icon-memory").hasClass("button-on")) return;

		this.patchMemoryBlock(0x0000, 0x00FF, this.blockZP);
		if (this.playerAddrCurrent)
			this.patchMemoryBlock(this.playerAddrCurrent, this.playerAddrCurrent + PAGESIZE_PLAYER - 1, this.blockPlayer);
	},

	/**
	 * Return a correctly padded hexadecimal memory address for displaying.
	 * 
	 * @param {number} address		Address 0 to 65535.
	 * 
	 * @return {string}				Address 0000 to FFFF.
	 */
	paddedAddress: function(address) {
		address = Number(address).toString(16).toUpperCase();
		return "0000".substr(address.length)+address;
	},

	/**
	 * Show details about the SID file just above the ZP and player table blocks.
	 */
	showSIDInfo: function() {
		if (typeof browser.songPos != "undefined" && browser.playlist[browser.songPos].fullname.substr(-4) != ".mus") {

			var size = browser.playlist[browser.songPos].size - 3,
				load = browser.playlist[browser.songPos].address,
				init = browser.playlist[browser.songPos].init,
				play = browser.playlist[browser.songPos].play;
				sub  = browser.playlist[browser.songPos].startsubtune + 1;
				max  = browser.playlist[browser.songPos].subtunes;
				type = browser.playlist[browser.songPos].type,
				vers = browser.playlist[browser.songPos].version,
				enc  = browser.playlist[browser.songPos].clockspeed,
				chip = browser.playlist[browser.songPos].sidmodel;

			$("#visuals-memory .si-size").empty().append("$"+this.paddedAddress(size)+" ("+size+" bytes)");
			$("#visuals-memory .si-load").empty().append("$"+this.paddedAddress(load)+" ("+load+")");
			$("#visuals-memory .si-init").empty().append("$"+this.paddedAddress(init)+" ("+init+")");
			$("#visuals-memory .si-play").empty().append(Number(play) ? "$"+this.paddedAddress(play)+" ("+play+")" : NOT_APPLICABLE);
			$("#visuals-memory .si-subtune").empty().append(sub+'<span style="font-family:Montserrat,sans-serif;"> <b>/</b> </span>'+max);
			$("#visuals-memory .si-type").empty().append(type+" "+(vers.substr(0, 1) != "v" ? "v" : "")+vers);
			$("#visuals-memory .si-enc").empty().append(enc);
			$("#visuals-memory .si-model").empty().append(chip);

			var timer = NOT_APPLICABLE;
			if (SID.emulatorFlags.returnCIA) {
				var pace = SID.getPace();
				timer = pace ? (pace == 1 ? 'CIA <span class="m">(on a 16-bit interval timer)</span>' : pace+'x <span class="m">(called '+pace+' times per VBI)</span>') : 'VBI <span class="m">(Vertical Blanking Interrupt)</span>';
			}
			$("#visuals-memory .si-pace").empty().append(timer);

			var addr = '$D400';
			for (var chip = 2; chip <= browser.chips; chip++) {
				var sid = SID.getSIDAddress(chip);
				addr += sid ? ',$'+sid.toString(16).toUpperCase() : ','+NOT_APPLICABLE;
			}
			$("#visuals-memory .si-sid").empty().append(addr);
		} else {
			$("#visuals-memory .si").empty().append(NOT_APPLICABLE);
		}
	},

	/**
	 * Stats: Update the harvesting of statistics about the usage of SID.
	 * 
	 * This is called by SID.setCallbackBufferEnded().
	 */
	animateStats: function() {
		var lowByte;
		for (var chip = 1; chip <= browser.chips; chip++) {
			for (var voice = 0; voice <= 2; voice++) {
				for (var register = 0; register <= 6; register++) {
					if (chip == 1) {

						var byte = SID.readRegister(0xD400 + register + (voice * 7), chip);

						switch (register) {
							case 0x00:
								// Low byte of frequency (store for next loop)
								lowByte = byte;
								break;
							case 0x01:
								// High byte of frequency (so now we have both bytes)
								var ptr = this.stat_freq_ptr[voice]++;
								this.stat_freq[voice][ptr] = lowByte + (byte * 256);
								this.showWord("#stats-v"+(voice + 1)+"-1-V", this.stat_freq[voice][ptr]);

								if (ptr > 5) {
									var subtleChanges = true;
									this.stat_freq_ptr[voice] = 0;

									for (var i = 0; i < 5; i++) {

										// Get the clockspeed
										var closest = null, indexMatch = 0, clockspeed;
										try {
											clockspeed = browser.playlist[browser.songPos].clockspeed;
											this.prevClockspeedStats = clockspeed;
										} catch(e) {
											// Type error usually happens when leaving a folder while playing
											clockspeed = this.prevClockspeedStats;
										}
										// Find the closest match in the array of note frequencies (PAL or NTSC table)
										var sidFrequencies = clockspeed.substr(0, 4) === "NTSC" || browser.path.indexOf("Compute's Gazette SID Collection") !== -1 ? this.sidFrequenciesNTSC : this.sidFrequenciesPAL;
										$.each(sidFrequencies, function(index) {
											if (closest == null || Math.abs(this - viz.stat_freq[voice][i]) < Math.abs(closest - viz.stat_freq[voice][i])) {
												closest = this;
												indexMatch = index;
											}
										});
	
										// Calculate the gap between neighbour frequencies
										var gap = indexMatch == 0
											? sidFrequencies[indexMatch] - sidFrequencies[indexMatch + 1]
											: sidFrequencies[indexMatch] - sidFrequencies[indexMatch - 1];

										// The granularity of the distance between frequency changes
										const allowedDistance = Math.abs(gap) / 3;

										// How close are these two frequencies?
										var realDistance = Math.abs(this.stat_freq[voice][i + 1] - this.stat_freq[voice][i]);

										/*if (voice == 1) {
											console.log(this.stat_freq[voice]);
											console.log("gap = "+gap+"; realDistance = "+realDistance+"; allowedDistance = "+allowedDistance);
										}*/

										if (realDistance == 0 || realDistance > allowedDistance) {
											subtleChanges = false;
											break;
										}
									}

									if (subtleChanges)
										// The past frequencies have been close together - so, vibrato or slide?
										this.mark(voice, register, "V", true);
								}
								break;
							case 0x02:
								// Low byte of pulse width (store for next loop)
								lowByte = byte;
								break;
							case 0x03:
								// High byte of pulse width (so now we have both bytes)
								var pw = lowByte + (byte & 0xF) * 256;
								this.showWord("#stats-v"+(voice + 1)+"-3-P", pw);

								if (this.stat_pw[voice].indexOf(pw) === -1)
									// It's a new pulse width not used before
									this.stat_pw[voice].push(pw);
								
								if (this.stat_pw[voice].length > 4)
									// The pulse width has been changed repeatedly
									this.mark(voice, register, "P", true);
								break;
							case 0x04:
								var $headerSpan = $("#stats-h-"+(voice + 1)+" span")
								$headerSpan.empty();
								if (byte & 0x01)
									// Gate ON
									$headerSpan.append('<font>&#8628;</font>'); // &#128498;

								if (byte >> 4 < 9)
									// Acceptable waveform [combination]
									this.mark(voice, register, byte >> 4);
								else
									// Illegal waveform
									this.mark(voice, register, "X");

								if (byte & 0x08)
									// Test bit
									this.mark(voice, register, "T");

								switch (byte & 0x06) {
									case 0x02:
										// Hard synchronization
										this.mark(voice, register, "H");
										break;
									case 0x04:
										// Ring modulation
										this.mark(voice, register, "R");
										break;
									case 0x06:
										// Both combined
										this.mark(voice, register, "M");
										break;
									}
								break;
							case 0x05:
								// High byte of ADSR (store for next loop)
								hiByte = byte;
								break;
							case 0x06:
								// Low byte of ADSR (so now we have both bytes)
								var adsr = byte + (hiByte * 256);
								this.showWord("#stats-v"+(voice + 1)+"-6-A", adsr);

								if (this.stat_adsr[voice].indexOf(adsr) == -1)
									// It's a new ADSR not used before
									this.stat_adsr[voice].push(adsr);
								
								if (this.stat_adsr[voice].length > 3)
									// The ADSR has been changed several times
									this.mark(voice, register, "A", true);
								break;
						}
					}
				}
			}

			// Global SID registers
			for (var register = 0x15; register <= 0x18; register++) {
				if (chip == 1) {

					var byte = SID.readRegister(0xD400 + register, chip);

					switch (register) {
						case 0x15:
							// Low byte of filter cutoff frequency (store for next loop)
							lowByte = byte & 0x7;
							break;
						case 0x16:
							// High byte of filter cutoff frequency (so now we have both bytes)
							var fc = byte << 3 | lowByte;
							this.showWord("#stats-global-C", fc);

							if (this.stat_fc.indexOf(fc) == -1)
								// It's a new filter cutoff not used before
								this.stat_fc.push(fc);

							if (this.stat_fc.length > 4)
								// The filter cutoff has been changed several times
								$("#stats-global-C").removeClass("stats-used").addClass("stats-used");
							break;
						case 0x17:
							// Filter routing
							for (var voice = 0; voice <= 2; voice++) {
								if (byte & (1 << voice)) {
									$("#stats-global-"+(voice + 1)).removeClass("stats-used").addClass("stats-used");
									$("#stats-global-"+(voice + 1)+" span").empty().append('<font>'+DOT_ACTIVITY+'</font>');
									this.setVoiceColor(voice, true);
								} else
									this.setVoiceColor(voice, false);
							}

							// External input
							if (byte & 0x08)
								$("#stats-global-I").removeClass("stats-used").addClass("stats-used");

							// Filter resonance
							var reso = byte >> 4;
							$("#stats-global-R span").empty().append('<u style="visibility:hidden;">000</u>'+reso.toString(16).toUpperCase());

							if (reso > 0x00 && reso < 0x0F)
								$("#stats-global-O").removeClass("stats-used").addClass("stats-used");

							if (this.stat_reso.indexOf(reso) == -1)
								// It's a new filter resonance not used before
								this.stat_reso.push(reso);

							if (this.stat_reso.length > 2)
								// The filter resonance has been changed several times
								$("#stats-global-R").removeClass("stats-used").addClass("stats-used");
							break;
						case 0x18:
							// Filter mode (passbands)
							var $fmode = "#stats-fmode-"+((byte & 0x70) >> 4);
							$($fmode).removeClass("stats-used").addClass("stats-used");
							$($fmode+" span").empty().append('<font>'+DOT_ACTIVITY+'</font>');

							// Mute voice 3
							if (byte & 0x80)
								$("#stats-global-M").removeClass("stats-used").addClass("stats-used");

							// Main volume
							var vol = byte & 0x0f;
							$("#stats-global-V span").empty().append('<u style="visibility:hidden;">000</u>'+vol.toString(16).toUpperCase());

							if (this.stat_vol.indexOf(vol) == -1)
								// It's a new volume level not used before
								this.stat_vol.push(vol);

							if (this.stat_vol.length > 4)
								// The volume level has been changed several times
								$("#stats-global-V").removeClass("stats-used").addClass("stats-used");
							break;
						}
				}
			}
		}
	},

	/**
	 * Stats: Clear all markings and colors in the table, and reset variables.
	 */
	clearStats: function() {
		$("#table-stats div,#table-global-stats div").removeClass("stats-used").find("span").empty();
		$("#table-stats .stats-bg").css("background", "transparent");
		$("#visuals-stats .stats-solo").removeClass("button-off button-on").addClass("button-off");
		this.stat_fc = [];
		this.stat_reso = [];
		this.stat_vol = [];
		this.stat_freq_ptr = [0, 0, 0];
		this.stat_freq = [[], [], []];
		this.stat_pw = [[], [], []];
		this.stat_adsr = [[], [], []];
		this.prevClockspeedStats = "Unknown";
	},

	/**
	 * Stats: Set or clear the background color of a table column.
	 * 
 	 * @param {number} voice		SID voice 0-2.
	 * @param {boolean} set			TRUE to set color, otherwise remove it.
	 */
	setVoiceColor: function(voice, set) {
		$("#table-stats .stats-"+(voice + 1)).css("background", (set ? GetCSSVar("--color-bg-stats-filterbg") : "transparent"));
	},

	/**
	 * Stats: Mark a line in the table.
	 * 
 	 * @param {number} voice		SID voice 0-2.
 	 * @param {*} register			Typically SID root register 0-6, but can also be e.g. a letter.
 	 * @param {*} item				Kind of a subject ID - can be e.g. a number of a letter.
	 * @param {boolean} bit			If specified, do not show the bit.
	 */
	mark: function(voice, register, item, bit) {
		var id = "#stats-v"+(voice + 1)+"-"+register+"-"+item;
		$(id).removeClass("stats-used").addClass("stats-used");

		if (typeof bit == "undefined")
			$(id+" span").empty().append('<font>'+DOT_ACTIVITY+'</font>');
	},

	/**
	 * Stats: Show a hexadecimal word value in a DIV row in the table.
	 * 
	 * @param {string} id			The ID of the DIV.
	 * @param {number} word			The 16-bit value to be shown as hexadecimal.
	 */
	showWord: function(id, word) {
		$(id+" span").empty().append(("000"+word.toString(16).toUpperCase()).slice(-4));
	},

	/**
	 * Start updating the views that use the SID.setCallbackBufferEnded() callback.
	 */
	startBufferEndedEffects: function() {
		$("#visuals-memory .player-to-left").removeClass("disabled").addClass("disabled");
		$("#visuals-memory .player-to-right").removeClass("disabled");
		this.activatePiano(true);
		this.showSIDInfo();
		this.activateMemory(true);
	},

	/**
	 * Stop updating the views that use the SID.setCallbackBufferEnded() callback.
	 */
	stopBufferEndedEffects: function() {
		this.activatePiano(false);
		this.activateMemory(false);
	},

	/**
	 * Set up the continuous call of the SID.setCallbackBufferEnded() callback.
	 */
	animateBufferEnded: function() {
		if (miniPlayer || $("body").attr("data-mobile") !== "0") return;
		SID.setCallbackBufferEnded(function() {
			// All calls have been moved to 'animateFrames' instead
		}.bind(this));
	},

	/**
	 * Set up the continuous call of frames animated at ~60 hz.
	 * 
	 * NOTE: The rate actually differs depending on resolution and monitor. My
	 * 4K mode only runs at about 40 hz.
	 */
	animateFrames: function() {
		requestAnimationFrame(this.animateFrames.bind(this));
		this.animatePiano();
		this.animateScope();
		this.animateGraph();
		this.animateMemory();
		this.animateStats();
	},
}