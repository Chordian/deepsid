
/**
 * DeepSID / Viz (tabs 'Piano and 'Flood')
 */

function Viz(emulator) {

	this.emulator = emulator.toLowerCase();

	this.pianoBarBackground = "#111";
	this.slowSpeed = 0.3;
	this.floodZoom = false;
	this.floodPW = true;
	this.lineInFlood = false;

	this.bufferSize;

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

	// Set buffersize as previously stored or default to 1024 bytes
	this.bufferSize = localStorage.getItem("buffer");
	if (this.bufferSize == null) this.bufferSize = 1024;
	$("#page .dropdown-buffer").val(this.bufferSize);

	this.setEmuButton(this.emulator);

	this.initFlood();	
	this.addEvents();
}

Viz.prototype = {

	/**
	 * Add the events pertinent to this class.
	 */
	addEvents: function() {
		$(window).on("keyup", this.onKeyUp.bind(this));
		$("#page").on("click", ".button-toggle,.button-radio", this.onToggleClick.bind(this));
		$("#page").on("click", ".piano-voice", this.onVoiceClick.bind(this));
		$("#page .dropdown-buffer").on("change", this.onChangeBufferSize.bind(this));
	},

	/**
	 * Handle hotkeys for turning voices ON or OFF.
	 * 
	 * @param {*} event 
	 */
	onKeyUp: function(event) {
		if (!$("#search-box,#username,#password,#sym-rename").is(":focus")) {
			var voiceMask = SID.voiceMask & 0xF;
			if (event.keyCode == 49 || event.keyCode == 81) {		// Keyup '1' or 'q'
				if (event.shiftKey) {
					this.enableAllPianoVoices()
					if (voiceMask != 0x1) {
						// Reverse (solo)
						$("#page .pv1,#page .pv2").trigger("click");
						SID.toggleVoice(4);
					}
				} else
					// Toggle a SID voice 1 ON/OFF using the piano toggle buttons
					$("#page .pv0").trigger("click");
			} else if (event.keyCode == 50 || event.keyCode == 87)	// Keyup '2' or 'w'
				if (event.shiftKey) {
					this.enableAllPianoVoices()
					if (voiceMask != 0x2) {
						$("#page .pv0,#page .pv2").trigger("click");
						SID.toggleVoice(4);
					}
				} else
					$("#page .pv1").trigger("click");
			else if (event.keyCode == 51 || event.keyCode == 69)	// Keyup '3' or 'e'
				if (event.shiftKey) {
					this.enableAllPianoVoices()
					if (voiceMask != 0x4) {
						$("#page .pv0,#page .pv1").trigger("click");
						SID.toggleVoice(4);
					}
				} else
					$("#page .pv2").trigger("click");
			else if (event.keyCode == 52 || event.keyCode == 82)	// Keyup '4' or 'r'
				if (event.shiftKey) {
					this.enableAllPianoVoices()
					if (voiceMask != 0x8)
						$("#page .pv0,#page .pv1,#page .pv2").trigger("click");
				} else
					// Using direct call (piano view doesn't support digi tunes)
					SID.toggleVoice(4);
		}
	},

	/**
	 * Click a toggle button in a visualizer tab, checkbox- or radio button style.
	 * 
	 * @param {*} event 
	 */
	onToggleClick: function(event) {
		var $this = $(event.target);
		if ($this.hasClass("button-toggle")) {
			// Checkbox style toggle button
			$this.empty().append($this.hasClass("button-off") ? "On" : "Off");
			if (event.target.id === "piano-slow") {
				SID.speed($this.hasClass("button-off") ? this.slowSpeed : 1);
			} else if (event.target.id === "flood-zoom") {
				this.floodZoom = $this.hasClass("button-off");
				$("#page .flood-river").css("border-right", this.floodZoom ? "1.5px dashed #ced0c0" : "0.5px solid #ced0c0");
			} else if (event.target.id === "flood-pw") {
				this.floodPW = $this.hasClass("button-off");
			} else {
				// Clear piano keyboards to make sure there are no hanging colors on it
				$("#topic-piano .piano svg .black").css("transition", "none").attr("fill", "#000");
				$("#topic-piano .piano svg .white").css("transition", "none").attr("fill", "#fff");
				// Also clear canvas bars
				var ctx_pw_height = $("#piano-pw0").height(), ctx_pw_width = $("#piano-pw0").width();			
				for (var voice = 0; voice <= 2; voice++) {
					var ctx_pw = $("#piano-pw"+voice)[0].getContext("2d");
					ctx_pw.fillStyle = this.pianoBarBackground;
					ctx_pw.fillRect(0, 0, ctx_pw_width, ctx_pw_height);
				}
			}
		} else if ($this.hasClass("button-radio")) {
			if ($this.hasClass("disabled")) return false;
			// Radio-button style button
			var groupClass = $this.attr("data-group");
			// First pop all buttons in this group up - there can only be one
			$("#page ."+groupClass).removeClass("button-off button-on").addClass("button-off");
			// Adjust the top left drop-down box with SID handlers
			$("#dropdown-emulator").styledSetValue($this.attr("data-emu"))
				.next("div.styledSelect").trigger("change");
		}
		// Now swap the class state of this button
		var state = $this.hasClass("button-off");
		$this.removeClass("button-off button-on").addClass("button-"+(state ? "on" : "off"))
	},

	/**
	 * Piano: Click a green voice ON/OFF button on a piano keyboard.
	 * 
	 * @param {*} event 
	 */
	onVoiceClick: function(event) {
		var $this = $(event.target);
		// Swap the class state of this button
		var state = $this.hasClass("voice-off");
		$this.removeClass("voice-off voice-on").addClass("voice-"+(state ? "on" : "off"));
		var voice = parseInt(event.target.classList[1].substr(-1));
		SID.toggleVoice(voice + 1);
		$("#page .piano"+voice).css("opacity", (state ? "1" : "0.1"));
		$("#flood"+voice+" canvas").css("opacity", (state ? "1" : "0.3"));
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
		// NOTE: Doing it in a specific visualizer tab is deliberate (avoids multiple triggering).
		$("#topic-piano .viz-emu.button-on").trigger("click");
	},

	/**
	 * Pop all of the emulator radio buttons up for now.
	 */
	allEmuButtonsOff: function() {
		$("#page .viz-emu").removeClass("button-off button-on").addClass("button-off");		
	},

	/**
	 * Press an emulator button in the visualizer tabs or show a warning about the need.
	 * 
	 * @param {string} emulator 
	 */
	setEmuButton: function(emulator) {
		if (emulator == "websid" || emulator == "jssid") {
			$("#page .viz-"+emulator).addClass("button-on"); 
			$("#page .viz-warning").hide();
		} else
			$("#page .viz-warning").show();
	},

	/**
	 * Piano: Start or stop animating the piano keyboards.
	 * 
	 * This makes use of the SID.setCallbackBufferEnded() callback.
	 * 
	 * @param {boolean} activate 
	 */
	activatePiano: function(activate) {
		if ($("body").attr("data-mobile") !== "0") return;
	
		// Clear all keyboard notes to default piano colors
		$("#topic-piano .piano svg .black").css("transition", "none").attr("fill", "#000");
		$("#topic-piano .piano svg .white").css("transition", "none").attr("fill", "#fff");
	
		this.enableAllPianoVoices();
	
		// Cache the canvas contexts
		var ctx_pw = [];
		for (var voice = 0; voice <= 2; voice++)
			ctx_pw[voice] = $("#piano-pw"+voice)[0].getContext("2d");
		var ctx_fc = $("#piano-fc")[0].getContext("2d"),
			ctx_res = $("#piano-res")[0].getContext("2d");
	
		// Get the canvas dimensions
		var ctx_pw_height = ctx_fc_height = 8, ctx_pw_width = ctx_fc_width = 200, ctx_res_height = 30, ctx_res_width = 3;
	
		// Cache some of the DOM stuff
		var $piano_ringmod = $("#topic-piano .piano-ringmod"),
			$piano_hardsync = $("#topic-piano .piano-hardsync"),
			$piano_rm = [$("#topic-piano .piano-rm0"), $("#topic-piano .piano-rm1"), $("#topic-piano .piano-rm2")],
			$piano_hs = [$("#topic-piano .piano-hs0"), $("#topic-piano .piano-hs1"), $("#topic-piano .piano-hs2")],
			$ff = [$("#topic-piano .ff0"), $("#topic-piano .ff1"), $("#topic-piano .ff2")],
			$ptp = [$("#topic-piano .ptp0"), $("#topic-piano .ptp1"), $("#topic-piano .ptp2")],
			$piano_pb_led = $("#topic-piano .piano-pb-led"),
			$pb_lp_div = $("#topic-piano .pb-lp div"),
			$pb_bp_div = $("#topic-piano .pb-bp div"),
			$pb_hp_div = $("#topic-piano .pb-hp div"); 
	
		if (activate) {
	
			var prevOctave = [0, 0, 0], prevNote = [0, 0, 0], prevGoodWaveform = [0, 0, 0], prevClockspeed = "Unknown";
			
			SID.setCallbackBufferEnded(function() {
	
				if ($("#tabs .selected").attr("data-topic") !== "piano") return; // Only if the tab is active!
	
				var useOneKeyboard = $("#piano-combine").hasClass("button-on");
	
				for (var voice = 0; voice <= 2; voice++) {
	
					// Combine into top keyboard?
					var keyboard = useOneKeyboard ? 0 : voice;
	
					// Get the raw frequency
					var freq = SID.readRegister(0xD400 + voice * 7) + SID.readRegister(0xD401 + voice * 7) * 256,
						closest = null, indexMatch = 0, clockspeed;
					try {
						clockspeed = browser.playlist[browser.songPos].clockspeed;
						prevClockspeed = clockspeed;
					} catch(e) {
						clockspeed = prevClockspeed; // Type error usually happens when leaving a folder while playing
					}
					// Find the closest match in the array of note frequencies (PAL or NTSC table)
					this.sidFrequencies = clockspeed.substr(0, 4) === "NTSC" || browser.path.indexOf("Compute's Gazette SID Collection") !== -1 ? this.sidFrequenciesNTSC : this.sidFrequenciesPAL;
					$.each(this.sidFrequencies, function(index) {
						if (closest == null || Math.abs(this - freq) < Math.abs(closest - freq)) {
							closest = this;
							indexMatch = index;
						}
					});
					var octave = ~~(indexMatch / 12),
						note = indexMatch % 12,
						waveform = SID.readRegister(0xD404 + voice * 7) >> 4;
					waveform = waveform ? prevGoodWaveform[voice] = waveform : waveform = prevGoodWaveform[voice];
					if (octave !== prevOctave[voice] || note !== prevNote[voice]) {
						// Clear the previous piano key
						$("#v"+keyboard+"_oct"+prevOctave[voice]+"_"+prevNote[voice]).css("transition", "none").attr("fill", this.pianoKeyColors[prevNote[voice]]);
						prevOctave[voice] = octave;
						prevNote[voice] = note;
					}
					if ((SID.readRegister(0xD404 + voice * 7) & 1) || $("#piano-gate").hasClass("button-off")) {
						// Gate ON
						if ((waveform >= 1 && waveform <= 7) || (waveform == 8 && $("#piano-noise").hasClass("button-on")))
							// The waveform is good so color the key on the piano
							$("#v"+keyboard+"_oct"+octave+"_"+note).css("transition", "none").attr("fill", this.waveformColors[waveform]);
					} else {
						// Gate OFF
						$("#v"+keyboard+"_oct"+octave+"_"+note).css("transition", "fill .05s linear").attr("fill", this.pianoKeyColors[note]);
					}
	
					// Show the pulse width as a horizontal canvas bar
					var pw = SID.readRegister(0xD402 + voice * 7) + (SID.readRegister(0xD403 + voice * 7) & 0xF) * 256;
					if (useOneKeyboard) {
						// Share tinier bars in the top keyboard
						ctx_pw[0].fillStyle = "#882f24";
						ctx_pw[0].fillRect(0, (ctx_pw_height / 3) * voice, (pw * 100 / 4095) * ctx_pw_width / 100, 1);
						ctx_pw[0].fillRect(0, ((ctx_pw_height / 3) * voice) + 1, 1, (ctx_pw_height / 3) - 1);
						ctx_pw[0].fillStyle = this.waveformColors[4];
						ctx_pw[0].fillRect(1, ((ctx_pw_height / 3) * voice) + 1, ((pw * 100 / 4095) * ctx_pw_width / 100) - 1, (ctx_pw_height / 3) - 1);
						ctx_pw[0].fillStyle = this.pianoBarBackground;
						ctx_pw[0].fillRect(((pw * 100 / 4095) * ctx_pw_width / 100) + 1, (ctx_pw_height / 3) * voice, ctx_pw_width + 1, ctx_pw_height / 3);
					} else {
						// Use a bar on each keyboard
						ctx_pw[voice].fillStyle = this.waveformColors[4];
						ctx_pw[voice].fillRect(0, 0, (pw * 100 / 4095) * ctx_pw_width / 100, ctx_pw_height);
						ctx_pw[voice].fillStyle = this.pianoBarBackground;
						ctx_pw[voice].fillRect((pw * 100 / 4095) * ctx_pw_width / 100, 0, ctx_pw_width, ctx_pw_height);
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
					var filterOn = SID.readRegister(0xD417) & (1 << voice);
					$ff[voice].css({
						fill:	(filterOn ? "#a26300" : "#000"),
						stroke:	(filterOn ? "#a26300" : "#000"),
					});
					$ptp[voice].css("border-bottom", "1px solid "+(filterOn ? "#cc7c00" : "#7a7a7a"));
	
					// Top keyboard only
					if (voice == 0) {
						// Show the filter cutoff as a horizontal canvas bar
						var fc = SID.readRegister(0xD416) << 3 + (SID.readRegister(0xD415) & 0x7);
						ctx_fc.fillStyle = "#cc7c00";
						ctx_fc.fillRect(0, 0, (fc * 100 / 2047) * ctx_fc_width / 100, ctx_fc_height);
						ctx_fc.fillStyle = this.pianoBarBackground;
						ctx_fc.fillRect((fc * 100 / 2047) * ctx_fc_width / 100, 0, ctx_fc_width, ctx_fc_height);
	
						// Show the resonance as a small vertical canvas bar
						var res = SID.readRegister(0xD417) >> 4,
							fillHeight = (res * 100 / 15) * ctx_res_height / 100;
						ctx_res.fillStyle = "#fec700";
						ctx_res.fillRect(0, ctx_res_height - fillHeight, ctx_res_width, fillHeight);
						ctx_res.fillStyle = this.pianoBarBackground;
						ctx_res.fillRect(0, 0, ctx_res_width, ctx_res_height - fillHeight);
	
						// Indicate filter passband in the LED lamps
						$piano_pb_led.removeClass("pb-on pb-off");
						SID.readRegister(0xD418) & 0x10 ? $pb_lp_div.addClass("pb-on") : $pb_lp_div.addClass("pb-off");
						SID.readRegister(0xD418) & 0x20 ? $pb_bp_div.addClass("pb-on") : $pb_bp_div.addClass("pb-off");
						SID.readRegister(0xD418) & 0x40 ? $pb_hp_div.addClass("pb-on") : $pb_hp_div.addClass("pb-off");
					}
				}
			}.bind(this));
	
		} else {
	
			SID.setCallbackBufferEnded(undefined);
	
			// Clear canvas bars
			var ctx_pw_height = $("#piano-pw0").height(), ctx_pw_width = $("#piano-pw0").width();
			for (var voice = 0; voice <= 2; voice++) {
				ctx_pw[voice].fillStyle = this.pianoBarBackground;
				ctx_pw[voice].fillRect(0, 0, ctx_pw_width, ctx_pw_height);
			}
			ctx_fc.fillStyle = ctx_res.fillStyle = this.pianoBarBackground;
			ctx_fc.fillRect(0, 0, $("#piano-fc").width(), $("#piano-fc").height());
			ctx_res.fillRect(0, 0, $("#piano-res").width(), $("#piano-res").height());
	
			// Turn off ring mod and hard sync arrow lamps
			$("#topic-piano .piano-ringmod").removeClass("pr-on pr-off").addClass("pr-off");
			$("#topic-piano .piano-hardsync").removeClass("ph-on ph-off").addClass("ph-off");
	
			// Reset filter filet
			$("#topic-piano .filet").css({
				fill:	"#000",
				stroke:	"#000",
			});
			$("#topic-piano .piano-top-panel").css("border-bottom", "1px solid #7a7a7a");
	
			// Turn off filter passband LED lamps
			$("#topic-piano .piano-pb-led").removeClass("pb-on pb-off").addClass("pb-off");
		}
	},
	
	/**
	 * Piano: Enable all voices, both in the emulator and on the piano keyboards.
	 */
	enableAllPianoVoices: function() {
		SID.enableAllVoices();
		$("#topic-piano .piano-voice").removeClass("voice-off voice-on").addClass("voice-on");
		$("#topic-piano .piano,#flood canvas").css("opacity", "1");
		// Also snuck this in to set slow speed again if need be
		setTimeout(function() {
			if ($("#piano-slow").hasClass("button-on")) SID.speed(this.slowSpeed);
		}, 1);
	},

	/**
	 * Flood: Initialize and draw the canvas rivers.
	 */
	initFlood: function() {
		if ($("body").attr("data-mobile") !== "0") return;

		this.canvas_river = [], this.ctx_river = [], this.river_width = [], this.river_height = [];
		this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

		var floodHeight = $("#page").outerHeight() - 173;
		$("#flood").height(floodHeight);
		$("#flood .flood-river").empty().append('<canvas height="'+(floodHeight - 1)+'" width="268"></canvas>');

		// Create canvas rivers and get their contexts
		for (var voice = 0; voice <= 2; voice++) {
			this.canvas_river[voice] = $("#flood"+voice+" canvas")[0];
			this.ctx_river[voice] = this.canvas_river[voice].getContext("2d");
			this.river_width[voice] = 268;
			this.river_height[voice] = floodHeight - 1;
			// Clear the rivers
			this.ctx_river[voice].clearRect(0, 0, this.river_width[voice], this.river_height[voice]);
		}

		// Clone the waveform color array with darker color versions
		this.darkerWaveformColors = this.waveformColors.slice();
		$.each(this.darkerWaveformColors, function(i, color) {
			this.darkerWaveformColors[i] = this.lightenDarkenColor(color, -40);
		}.bind(this));

		this.animate();
	},

	/**
	 * Animate at 60 hz.
	 */
	animate: function() {
		requestAnimationFrame(this.animate.bind(this));
		this.animateFlood();
	},

	/**
	 * Flood: Animate the canvas rivers.
	 * 
	 * Called by: requestAnimationFrame() - use 'viz' instead of 'this' here.
	 */
	animateFlood: function() {
		// Not available on mobile devices, and the 'Flood' tab must be visible
		if ($("body").attr("data-mobile") !== "0" || $("#tabs .selected").attr("data-topic") !== "flood") return;

		for (var voice = 0; voice <= 2; voice++) {

			// Color the top line background to begin with
			var filterOn = SID.readRegister(0xD417) & (1 << voice);
			viz.ctx_river[voice].fillStyle = viz.lineInFlood ? "#eee" : (filterOn ? "#ffffea" : "#fff");
			viz.ctx_river[voice].fillRect(0, 0, viz.river_width[voice], 1);

			// Find the X coordinate that corresponds to the current SID voice frequency
			var freq = SID.readRegister(0xD400 + voice * 7) + SID.readRegister(0xD401 + voice * 7) * 256;
			if (viz.floodZoom) freq *= 2;
			var x = (freq / 0xFFFF) * viz.river_width[voice];
			x = x | 0; // This rounds off to avoiding anti-aliased lines

			var waveform = SID.readRegister(0xD404 + voice * 7) >> 4;

			// For pulse width, make middle (0x800) king and shrink for a sensible pixel width
			var pw = SID.readRegister(0xD402 + voice * 7) + (SID.readRegister(0xD403 + voice * 7) & 0xF) * 256;
			pw = pw < 2048 ? pw : pw ^ 0xFFF;
			pw /= 128; // Smaller value here equals a bigger "coat"

			// Draw the dot representing the frequency
			if (viz.darkerWaveformColors[waveform] != "#000000") {
				viz.ctx_river[voice].lineWidth = 2;
				viz.ctx_river[voice].globalAlpha = SID.readRegister(0xD404 + voice * 7) & 1 ? 1 : 0.4; // Gate ON / OFF
				if (viz.floodPW && waveform == 4) {
					// Show pulse width as a "coat" around the frequency dot
					viz.ctx_river[voice].strokeStyle = "#ffd1cb";
					viz.ctx_river[voice].beginPath();
					viz.ctx_river[voice].moveTo(x - pw, 0);
					viz.ctx_river[voice].lineTo(x - 1, 0);
					viz.ctx_river[voice].moveTo(x + 1, 0);
					viz.ctx_river[voice].lineTo(x + pw, 0);
					viz.ctx_river[voice].stroke();
				}
				viz.ctx_river[voice].strokeStyle = viz.darkerWaveformColors[waveform];
				viz.ctx_river[voice].beginPath();
				viz.ctx_river[voice].moveTo(x, 0);
				viz.ctx_river[voice].lineTo(x, 1);
				viz.ctx_river[voice].stroke();
			}

			// Now scroll the river downwards
			viz.ctx_river[voice].globalAlpha = 1;
			if (viz.isSafari) {
				// Slower but necessary on Mac Safari due to a bug in their drawImage() handling
				var rect = viz.ctx_river[voice].getImageData(1, 0, viz.river_width[voice], viz.river_height[voice] - 1);
				viz.ctx_river[voice].putImageData(rect, 1, 1);
			} else {
				// Fastest (no hitches on my PC) and works in both Firefox, Chrome and Edge (slow there but ¯\_(ツ)_/¯)
				viz.ctx_river[voice].drawImage(viz.canvas_river[voice],
					1, 0, viz.river_width[voice], viz.river_height[voice] - 1,
					1, 1, viz.river_width[voice], viz.river_height[voice] - 1);
			}
		}
		viz.lineInFlood = false;
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
}