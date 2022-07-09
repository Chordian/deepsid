/**
* version 1.0.2
*
* 	Copyright (C) 2022 Juergen Wothke
*	enhanced version derived from the one used on my PlayMOD site
*
* note: This updated version relies on always caching 2*16384 samples per stream. If WebAudio
* is using the maximum buffer size of 16384 then this results in a simple double buffering
* scheme (like the one used in the old impl). But actually the implementation uses a circular 
* buffer approach and smaller WebAudio buffers will cause more slots to be used in the circular
* buffer (e.g. a buffer size of 8192 would use 4 slots). This improved approach ensures that
* enough data is always available for the purpose of copying the "sliding window" data (the
* old impl was fragile as the sliding window could actually be larger than what was available
* in the double buffered source).
*
* See "changed API" comments below for instructions on how to migrate your existing code to
* use this updated impl.
*
* Terms of Use: This file is free software. (Other licenses may apply to the remaining 
* files used in this example.)
*/

/**
* changed API: bufferSize param was eliminated
*
* @param numberStreams maximum number of used streams (for initial memory allocation)
*/
Tracer = (function(){ var $this = function(numberStreams) {
		$this.base.call(this);
		
		this.onStartCallback= function(){};
		
		this.setMode(true, 5);
		
		this._outputV= null;				// "sliding window" output arrays by voice
		
		this._numberStreams= numberStreams;
	}; 
	extend(AbstractTicker, $this, {
	// ------------ APIs expected by the base infrastructure - see doc in AbstractTicker parent class -----------------
		setOnStartCallback: function(onStartCallback) {
			this.onStartCallback= typeof onStartCallback == 'undefined' ? function(){} : onStartCallback;
		},
		isSidWizMode: function() {
			return this._useSidWiz;
		},
		getZoomLevel: function() {
			return this._zoomLevel;
		},
		reset: function() {
			
			// clear scope streams			
			if (typeof this._buffers != 'undefined') {
				for (var j= 0; j<this._circularBufSlots; j++) {
					for (var i= 0; i<this._numberStreams; i++) {
						var buf= this._buffers[j][i];
						
						for (var k= 0; k<this._samplesPerBuffer; k++) {
							buf[k]= 0;
						}
					}
				}
			}
		},
		/*
		* Basic initialization performed once on startup.
		*/
		init: function(samplesPerBuffer, tickerStepWidth) {
			this._tickerStepWidth= tickerStepWidth;
			this._samplesPerBuffer= samplesPerBuffer;
			this._circularBufSlots = 2 * 16384 / samplesPerBuffer;
			this._currentBufIdx = this._circularBufSlots-1;
			
			this._backendAdapter= null; 	// initialized later
		
			// for each of the streams 3 buffers are used during precessing (see "step" comments below)
			
			// 1st step: make sure same resampling is used for "add-on data" (as was used for the audio data:
			//           the input scope streams use the same sample rate that the original audio generator is
			//           using; that sample rate may be different from the one used by WebAudio and the same 
			//           resampling that the ScriptNodePlayer applied to the audio data - before passing it
			//           to WebAudio - is applied to the scope data)
			this._resampleV= [];
			for (var i= 0; i<this._numberStreams; i++) {
				this._resampleV.push( new Float32Array(samplesPerBuffer) );
			}
			
			// 2nd step: "add-on" buffers in sync with the WebAudio audio buffer.
			// 			 use double buffering to allow smooth transitions at buffer boundaries
			
			this._buffers= [];			
			for (var j= 0; j<this._circularBufSlots; j++) {
				var a= [];
				for (var i= 0; i<this._numberStreams; i++) {
					a.push( new Float32Array(samplesPerBuffer) );					
				}
				this._buffers.push( a );
			}
			
			// 3rd step: use "sliding window" approach to fill the actual output buffers
			// (respective buffers habe already been allocated via _setOutputSize() from the constructor)
		},
		stepCircularBuffer: function() {
			this._currentBufIdx += 1;
			if (this._currentBufIdx >= this._circularBufSlots) this._currentBufIdx = 0;
		},
		/**
		* Called from genSamples() before a new audio buffer containung this._samplesPerBuffer samples is generated.
		*/
		start: function() {
			this.stepCircularBuffer();
			
			this.onStartCallback();
		},
		/**
		* Corresponds to one emulator call - after which resampling of its output is required.
		*/
		resampleData: function(sampleRate, inputSampleRate, inputLen, backendAdapter) {
						
			this._backendAdapter= backendAdapter; // not the best place for this ...
			
			// this makes sure that the raw data is still in sync after the resampling of the audio data (emulator output)			
			var resampleLen;
			if (sampleRate == inputSampleRate) {		
				resampleLen= inputLen;
			} else {
				resampleLen= Math.round(inputLen * sampleRate / inputSampleRate);	
			}
			
			var nStreams= backendAdapter.getNumberTraceStreams();

			// cache resampled data in more accessible form (allocated size will quickly stabilize)
			for (var i= 0; i<nStreams; i++) {
				this._resampleV[i]= this._assertBuffer(backendAdapter, this._resampleV[i], resampleLen);
			}
			
			// resample the add-on data if necessary
			var streams= backendAdapter.getTraceStreams();
			
			for (var i= 0; i<nStreams; i++) {
				this._resample(streams[i], inputLen, this._resampleV[i], resampleLen, backendAdapter);
			}
		},
		/**
		* Mirrors the transfer of the resampled audio data into WebAudio's audio buffer.
		* fixme: avoid repeated checks/data access within loop..
		*/
		copyTickerData: function(outBufferIdx, inBufferIdx, backendAdapter) {
			// the buffers filled here then are in sync with the respective audio data buffer used by WebAudio 
			
			// target slot was selected in "start"			
			var buf= this._buffers[this._currentBufIdx];	

			var nStreams= backendAdapter.getNumberTraceStreams();
			for (var i= 0; i<nStreams; i++) {
				buf[i][outBufferIdx]= this._resampleV[i][inBufferIdx];
			}
		},
		
	// ------------ API specifically provided by Tracer (invent whatever accessors you need here) -----------------

		assertOutputV: function() {
			if ((this._outputV == null) || (this._outputV[0].length != this._outputSize)) {
				if (this._outputV == null) this._outputV= new Array(this._numberStreams);
				
				for (var i= 0; i<this._numberStreams; i++) {
					this._outputV[i]= new Float32Array(this._outputSize);
				}
			}
		},
		// changed API: replaces respective manipulations that had to be done on the app side with the old version
		setMode: function(useSidWiz, zoomLevel) {
			if (zoomLevel < 1 || zoomLevel > 5) {
				console.log("error: invalid zoom level " + zoomLevel + " (settings unchanged!)");
			} else {
				this._useSidWiz= useSidWiz;
				this._zoomLevel= zoomLevel;

				if (useSidWiz) {
					this._setOutputSize(16384);	// always use max
				} else {
					this._setOutputSize(246 << zoomLevel);
				}		
			}
		},
		setOutputSize(s) {
			alert("changed API: setOutputSize() must no longer be used");
			// the Tracker will automatically select a suitable size based on what is passed to setMode()
		},
		/*
		* private helper: do not use in user side code
		*/
		_setOutputSize(s) {
			if (s > 16384) {
				console.log("error: max output size is " + 16384);
				s= 16384;
			}		
			this._outputSize= s;
			
			this.assertOutputV();			
		},
				
		/*
		* NOTE: due to the fact that the below functions return a fixed size "sliding window" of 
		* data, the data returned by successive calls may overlap - or be disjoint when calls are 
		* spaced too far apart.. 
		*
		* In order to let the caller collect/append data (e.g. to display some zoomed-out
		* range) it might be useful to have other APIs, e.g. that deliver only new data or
		* give some feedback with "tick" related info - so that the caller may perform the
		* respective calculations himself, etc. But for now I leave that as an exercise to the reader.
		*/
		getData: function(voice) {
			this.assertOutputV();			
			this._copySlidingWindow(voice, this._outputV[voice]);
			return this._outputV[voice];
		},
			
	// ------------------------- private utility functions ---------------------------
		
		_resample: function(inputPtr, inputLen, resampledBuffer, resampleLen, backendAdapter) {
			// FIXME the shared readFloatTrace impl means that all the streams are currently expected to use the same type & range of data
			if (resampleLen == inputLen) {
				backendAdapter.getCopiedAudio(inputPtr, inputLen, backendAdapter.readFloatTrace.bind(backendAdapter), resampledBuffer);
			} else {
				backendAdapter.resampleToFloat(1, 0, inputPtr, inputLen, backendAdapter.readFloatTrace.bind(backendAdapter), resampledBuffer, resampleLen);
			}
		},		
		_assertBuffer: function(backendAdapter, buf, size) {
			if (size > buf.length) { buf= backendAdapter.allocResampleBuffer(size); }
			return buf;
		},
		_copySlidingWindow: function(voiceId, destBuffer) {

			var outputLen= destBuffer.length;	// end of the destination that still needs filling..
					
			var offset; // hack: to improve sync of sidWiz visuals			
			switch (this._zoomLevel) {
				case 1: 
					offset= 3;
					break
				case 2: 
				case 4: 
				case 5: 
					offset= 0;
					break
				default:
					offset= 6;			
			}
			
			var srcIdx= (ScriptNodePlayer.getInstance().getBufNum() + offset) % this._circularBufSlots;

			var srcBufs= this._buffers[srcIdx];	
			var srcBuf= srcBufs[voiceId];
		
			// this is the crucial bit that tells where in the current buffer data is *NOW*: 
			// the tick measures the relative time *within* the buffer that WebAudio is currently playing.
			var tick= ScriptNodePlayer.getInstance().getCurrentTick();	// e.g. 0..63 at 16384 buffer size			
			var endSrc= (tick + 1) * this._tickerStepWidth;	// _tickerStepWidth is usually 256
		
			while (outputLen > 0) {
				if (endSrc >= outputLen) {
					// enough data available in current slot
					this._arrayCopy(srcBuf, endSrc-outputLen, outputLen, destBuffer, 0);	
					break;
				} else {
					// need more data than available in the current slot: copy what is available then
					// fetch remaining data from previous slot(s)

					outputLen -= endSrc;	// fill destination from the end
					this._arrayCopy(srcBuf, 0, endSrc, destBuffer, outputLen);
					
					// continue with previous slot in circular buffer
					srcIdx = srcIdx ? srcIdx-1 : this._circularBufSlots-1;
					endSrc = this._samplesPerBuffer; // previous buffer end

					srcBufs= this._buffers[srcIdx];
					srcBuf= srcBufs[voiceId];
				}
			}
		},
		// caution: does not check boundary violations - you better know what you are doing
		_arrayCopy(src, srcOffset, len, dest, destOffset) {
			// riddiculous that this kind of function does not come with this joke of a 
			// language (and I am not talking about the expensive reallocation workarounds..)
			
			for (var i=0; i<len; i++) {
				dest[destOffset+i]= src[srcOffset+i];
			}
		}		
});	return $this; })();	


/**
* Code losely based on C# implementation of SidWiz2 by RushJet1 & Rolf R Bakke (see respective Form1.cs).
*
* note: original implementation included logic for multi-voice/-column layout whereas this implementation
* renders exactely one voice and always uses 1 column. Instead of rendering a
* complete sample file this implementation only renders the frame corresponding to the currently played music.
*
* note: the maximum "scale" that can be used is limited by the size of the sample data array passed to "draw()".
* In order to use the maximum scale make sure the player delivers 16k of sample data..
* 
* note: original implementation used 0-255 integer range sample data - while a -1 to 1 float range is used here.
*/
SidWiz = function(altsync) {
	// graphics context to draw in:
	this.setSize(100, 30);
	
	this._voiceData= null;	// new data is fed in for each frame
	
	this._altSync= altsync;	// enable "alt sync" impl for the voice

	this._scales = [0.125, 0.25, 0.5, 1, 2, 4,   8,   16  ];
	this._centers= [32,    16,   8,   4, 2, 1,   0.5, 0.25];
};

SidWiz.prototype = {
	setSize: function(width, height) {
		this._resX= width;
		this._resY= height;
	},		
	setHeight: function(height) {
		this._resY= height;
	},		
	getTriggerLevel: function(jua, jac, offset) {
		// scan for peak values
		var yMax= -1;    	// was 0
		var yMin= 1;		// was 255
		var juaHalf= Math.floor(jua/2);
		var s= juaHalf-jac;
		for (var h = s; h <= (juaHalf+jac); h++) {	// uses 2 center frames.. ?
			var value = this._voiceData[offset+h];
			if (value > yMax) { yMax = value; }
			if (value < yMin) { yMin = value; }
		}
		return ((yMin + yMax) / 2);   //the middle line of the waveform
	},

	/**
	* Gets the number of coordinates that will be output by draw().
	*/
	getNumberOfOuptutCoords: function(scaleIdx) {
		return this._resX*(this._scales[scaleIdx] / 2);	
	},
	/**
	* @param outCtx canvas context directly used for line drawing (if outVertices param is NOT supplied)
	* @param outVertices if optional Float32Array (which must be big enough) is supplied then result line is not drawn to 
	*                     canvas but instead coordinates are stored as 3d coordinates (z=0) in that array. The size requirement
	*                     can be calculated via getNumberOfOuptutCoords()
	*/
	draw: function(data, scaleIdx, outCtx, outVertices) {
		var sampleRate= window._gPlayerAudioCtx.sampleRate;	// anyways depends on 'ticker' infrastructure from scriptprocessor_player.js (so this addition doesn't hurt)
		
		this._voiceData= data;
	
		var scale= this._scales[scaleIdx];
		var center= this._centers[scaleIdx];

		// note: the crappy variable naming of the original implementation has been
		// largely preserved to ease comparisons..
		
		var jumpAmount = (sampleRate / 60);         	// samples per frame (no need to use browser's actual framerate)		
		var jua = jumpAmount * Math.floor(1+ scale);	// jua is the size of sample data used per frame
				
		var oldY2 = 0;
		var newY2 = 0;
		var newX = 0;
		var oldX = 0;	// was called "oldZ" in original impl (probably to reflect its use in 3-byte pixel logic)
			
		// offset to the position of the 1st sample
		// note: the below logic expects that "jua" samples can be read starting at that position
		var offset = (data.length - jua);

		//jac is the search window
		var jac = jumpAmount;
		
		var triggerLevel= this.getTriggerLevel(jua, jac, offset);
		
		var c= scaleIdx <=4 ? 0 : 2*jac;	// correction seems to be needed to properly position the displayed range...
		
		var frameTriggerOffset = 0;
		
		// syncronization
		if (this._altSync == false) {
			var one= 2.0 / 255;		// adjust original logic to the sample data range used here..
			var triggerLevelM= triggerLevel - one;
			var triggerLevelP= triggerLevel + one;
			
			frameTriggerOffset = jac;
			
			while (this._voiceData[offset+c+frameTriggerOffset] < (triggerLevelP) && frameTriggerOffset < jac * 2) frameTriggerOffset++;
			while (this._voiceData[offset+c+frameTriggerOffset] >= (triggerLevelM) && frameTriggerOffset < jac * 2) frameTriggerOffset++;
			if (frameTriggerOffset == jac * 2) frameTriggerOffset = 0;
			
		} else {
			var distances = [];	// array of arrays
			var qx = jac;
			while ((this._voiceData[offset+qx] >= triggerLevel) && (qx < jac * 2)) qx++;
			var ctr;
			while (qx < jac * 2) {
				ctr = qx;
				var isUp = false;
				//find point where crosses midline
				if (this._voiceData[offset+qx] < triggerLevel) {
					while ((this._voiceData[offset+qx] < triggerLevel) && (qx < jac * 2)) qx++;
					isUp = true;
				} else {
					while ((this._voiceData[offset+qx] >= triggerLevel) && (qx < jac * 2)) qx++;
				}

				//add point to data
				if (!isUp) {
					var data = [qx - ctr, qx];	// difference, position of the offset
					distances.push(data);
				}
			}
			
			ctr = 0; //count of highest values
			var highest = [0, 0]; //this will be the highest value
			
			var data;
			for (data of distances) {
				if (data[0] > highest[0]) {
					highest= [data[0], data[1]];
					ctr = 1;
				} else if (data[0] == highest[0]) {
					highest.push(data[1]);
					ctr++;
				}
			}
			//at this point "highest" should be a list where the first value is the difference, and the rest are points in order where the difference occurred
			//ctr is the number of same values. if more than 95% it's probably a square wave
			if (ctr != 1) ctr = Math.ceil(ctr / 2.0);
			frameTriggerOffset = highest[ctr];
		}
	
		// draw waveform
		var oldY2;		// previous y coord
		for (var x = 0; (x / (scale / 2)) < this._resX; x++) {
			var vdPos = frameTriggerOffset + c + x - Math.floor(this._resX / center); // note: stabilization causes first "c" samples to be "unusable", i.e. skip them

			if (vdPos < 0) { vdPos = 0; }
			var vdSet = this._voiceData[offset+vdPos];
			
			var y = Math.floor((vdSet+1)/2 * this._resY);	// use full available height (calc adjusted to sample range used here)

			if (x == 0) {
				oldY2 = y;
			}
			
			newY2 = y;
			
			if (oldY2 > this._resY) oldY2 = this._resY;
			if (newY2 > this._resY) newY2 = this._resY;
			if (newY2 < 0) newY2 = 0;
			if (oldY2 < 0) oldY2 = 0;

			newX = Math.floor(2*x/scale);	// called "z" in original code
			
			if (oldY2 > newY2) { //waveform moved down
				var t = oldY2;
				oldY2 = newY2;
				newY2 = t;
			}
						
			if (typeof outVertices != 'undefined') {
				// store coordinate in array for use in THREE.js BufferGeometry
				outVertices[3*x]= newX;				// x
				outVertices[3*x+1]= this._resY-newY2;	// y
				// z values are unchanged.. presumably 0
				
			} else {
				// draw line
				if (x == 0) {
					outCtx.moveTo(newX, this._resY-newY2);
				} else {
					outCtx.lineTo(newX, this._resY-newY2);
				}			
			}
			
			oldX = newX;
			oldY2 = y;
		}
	}
};


/*
* Example for basic canvas rendering of streamed "add-on" data.
*
* changed API: added "tracer" param
*/
VoiceDisplay = function(divId, tracer, getDataFunc, altsync) {

	this.divId= divId;
	this.tracer= tracer;
	
	this.getData= getDataFunc;
	
	this.canvas= document.getElementById(this.divId);
	this.ctx= this.canvas.getContext('2d');

	this.sidWiz= new SidWiz(altsync);

	this.setSize(512, 80);
	this.setStrokeColor("rgba(1, 0, 0, 1.0)");
};

VoiceDisplay.prototype = {
	// changed API: new method allows to override default size
	setSize: function(width, height) {
		this.WIDTH= width;
		this.HEIGHT= height;
		
		this.canvas.width = width;
		this.canvas.height = height;
		
		this.sidWiz.setSize(width, height);
	},
	// changed API: new method allows to override used line color
	// @JCH use display.setStrokeColor("rgba("+(viz.scopeLineColor[colorTheme])+", 1.0)")
	setStrokeColor: function(color) {
		this.strokeColor= color;
	},
	reqAnimationFrame: function() {
		window.requestAnimationFrame(this.redraw.bind(this));
	},
		
	redraw: function() {
		this.redrawGraph();		
		this.reqAnimationFrame();	
	},
	// changed API: original params are now stored in Tracer - see new  setMode()
	redrawGraph: function() {
		var data= this.getData();

		try {
			// seems that dumbshit Safari (11.0.1 OS X) uses the fillStyle for "clearRect"!
			this.ctx.fillStyle = "rgba(0, 0, 0, 0.0)";
		} catch (err) {}
		this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
			
		this.ctx.strokeStyle = this.strokeColor;			
		this.ctx.save();

		if (!this.tracer.isSidWizMode()) {
			// zooming performed by changing abount of delivered data
			var rescale= this.WIDTH/data.length;
			this.ctx.scale(rescale, 1);	// fit all the data into the visible area
		}			
		
		this.ctx.beginPath();
				
		if (this.tracer.isSidWizMode()) {
			this.sidWiz.draw(data, 2+this.tracer.getZoomLevel(), this.ctx, undefined);
		} else {
			for (var i = 0; i < data.length; i++) {
				var scale= (data[i]+1)/2;
				var magnitude = scale*this.HEIGHT;

				// invert Y or graphs will be upside down
				if (i == 0) {
					this.ctx.moveTo(i, this.canvas.height-magnitude);
				} else {
					this.ctx.lineTo(i, this.canvas.height-magnitude);
				}			
			}
		}
		this.ctx.stroke();		
		this.ctx.restore();
	}
};

