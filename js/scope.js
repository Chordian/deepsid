/**
* version 1.0.1
*
* 	Copyright (C) 2019 Juergen Wothke
*	Version from PlayMOD site with streams
*	Slightly modified by JCH for DeepSID
*
* Terms of Use: This file is free software. (Other licenses may apply to the remaining 
* files used in this example.)
*/

/**
* @param numberStreams maximum number of used streams (for initial memory allocation)
*/
Tracer = (function(){ var $this = function(outputSize, numberStreams) { 
		$this.base.call(this);
		
		this._outputSize= outputSize;
		this._numberStreams= numberStreams;
		
	}; 
	extend(AbstractTicker, $this, {
	// ------------ APIs expected by the base infractructure - see doc in AbstractTicker parent class -----------------
		
		/*
		* Basic initialization: for each of the streams
		*                       3  buffers are used during precessing (see "step" comments below)
		*/
		init: function(samplesPerBuffer, tickerStepWidth) {
			this._tickerStepWidth= tickerStepWidth;
			this._samplesPerBuffer= samplesPerBuffer;
			this._backendAdapter; 	// initialized later

			// 1st step: make sure same resampling is used for "add-on data" (as is used for the audio data)
			this._resampleV= [];
			for (var i= 0; i<this._numberStreams; i++) {
				this._resampleV.push( new Float32Array(samplesPerBuffer) );					
			}
			
			// 2nd step: "add-on" buffers in sync with the WebAudio audio buffer.
			// 			 use double buffering to allow smooth transitions at buffer boundaries
			//           (2 buffers are sufficient for the sliding window impl of a "past & present" view )
			this._activeBufIdx = 0;
			
			this._buffers= [];			
			for (var j= 0; j<2; j++) {	// double buffering
				var a= [];
				for (var i= 0; i<this._numberStreams; i++) {
					a.push( new Float32Array(samplesPerBuffer) );					
				}
				this._buffers.push( a );
			}
			
			// 3rd step: use "sliding window" approach to fill the actual output buffers
			// this.setOutputSize(this._outputSize);					
			this.setOutputSize(16384); // Added by JCH as the output size needs to be 16384 for SidWiz mode
		},	
		/**
		* Marks the start of a new audio buffer, i.e. a new audio buffer is about to be generated.
		*/
		start: function() {
			// alternate used output buffers (as base for "sliding window" data access) 			
			this._activeBufIdx = (this._activeBufIdx ? 0 : 1);	
			Ticker.start(); // Added by JCH	for other visualizers in DeepSID
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
		*/
		copyTickerData: function(outBufferIdx, inBufferIdx, backendAdapter) {
			// the buffers filled here then are in sync with the respective audio data buffer used by WebAudio 
			var buf= this._buffers[this._activeBufIdx];	

			var nStreams= backendAdapter.getNumberTraceStreams();
			for (var i= 0; i<nStreams; i++) {
				buf[i][outBufferIdx]= this._resampleV[i][inBufferIdx];
			}
		},
		
	// ------------ API specifically provided by Tracer (invent whatever accessors you need here) -----------------

		assertOutputV: function() {
			if ((typeof this._outputV == 'undefined') || (this._outputV[0].length != this._outputSize)) {
				if (typeof this._outputV == 'undefined') this._outputV= new Array(this._numberStreams);
				
				for (var i= 0; i<this._numberStreams; i++) {
					this._outputV[i]= new Float32Array(this._outputSize);
				}
			}
		},
		/*
		* Sets the size of the backward-view (in number of samples). Cannnot be larger than this._samplesPerBuffer
		*/
		setOutputSize(s) {
			if (s > this._samplesPerBuffer) {
				// console.log("error: max output size is " + this._samplesPerBuffer);
				s= this._samplesPerBuffer;
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
			var bufs= this._buffers[this._activeBufIdx];	
			var prevBufs= this._buffers[this._activeBufIdx ? 0 : 1];	// previous buffers
			
			var inputBuf= bufs[voiceId];
			var prevInputBuf= prevBufs[voiceId];
			
			var outputLen= destBuffer.length;
			
			// this is the crucial bit that tells where in the data is *NOW* 			
			var tick= ScriptNodePlayer.getInstance().getCurrentTick();	// e.g. 0..63 at 16384 buffer size			
			
			// (below is just an example of how that information might be used)
			var endOffset= (tick+1)*this._tickerStepWidth;				// e.g. width 256 at  16384 buffer size
			if (endOffset > outputLen) {
				// no buffer boundary crossed (simple copy from the current buffer does the job)
				this._arrayCopy(inputBuf, endOffset-outputLen, outputLen, destBuffer, 0);
			} else {
				// some data still must be fetched from the previous buffer
				var sizePrevious= outputLen - endOffset;
				this._arrayCopy(prevInputBuf, prevInputBuf.length-sizePrevious, sizePrevious, destBuffer, 0);
				
				this._arrayCopy(inputBuf, 0, endOffset, destBuffer, sizePrevious);
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
SidWiz = function(width, height, altsync) {
	// graphics context to draw in:
	this._resX= width;
	this._resY= height;
	
	this._voiceData= null;	// new data is fed in for each frame
	
	this._altSync= altsync;	// enable "alt sync" impl for the voice

	this._scales = [0.125, 0.25, 0.5, 1, 2, 4,   8,   16  ];
	this._centers= [32,    16,   8,   4, 2, 1,   0.5, 0.25];
};

SidWiz.prototype = {
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
				// if (qx === 800) break; // JCH: Enable this for debugging if it starts freezing

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
*/
VoiceDisplay = function(divId, getDataFunc, altsync) {
	this.WIDTH= 512;
	this.HEIGHT= 70;	// Changed by JCH (originally 80)

	this.divId= divId;
	this.getData= getDataFunc;
	
	this.canvas = document.getElementById(this.divId);
	this.ctx = this.canvas.getContext('2d');
	this.canvas.width = this.WIDTH;
	this.canvas.height = this.HEIGHT;

	this.sidWiz= new SidWiz(this.WIDTH, this.HEIGHT, altsync);	
};

VoiceDisplay.prototype = {
	reqAnimationFrame: function() {
		window.requestAnimationFrame(this.redraw.bind(this));
	},
	redraw: function() {
		this.redrawGraph();		
		this.reqAnimationFrame();	
	},
	redrawGraph: function(osciloscopeMode, zoom) {
		var data= this.getData();
		if (osciloscopeMode && data.length < 16384) return; // Added by JCH to avoid freezing
		
		try {
			// seems that dumbshit Safari (11.0.1 OS X) uses the fillStyle for "clearRect"!
			this.ctx.fillStyle = "rgba(0, 0, 0, 0.0)";
		} catch (err) {}
		this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
				
		this.ctx.strokeStyle = "rgba("+(viz.scopeLineColor[colorTheme])+", 1.0)"; // Color modified by JCH
		this.ctx.save();

		if (!osciloscopeMode) {
			// zooming performed by changing abount of delivered data
			var rescale= this.WIDTH/data.length;
			this.ctx.scale(rescale, 1);	// fit all the data into the visible area
		}			
		
		this.ctx.beginPath();
				
		if (osciloscopeMode) {
			this.sidWiz.draw(data, 2+zoom, this.ctx, undefined);
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

