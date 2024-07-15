/**
* This subclasss of AbstractTicker2 can be used to render additional data streams (e.g. internal
* music player channels) in sync with the actual WebAudio music playback.
*
* version 1.2.1
*
* The old Wiz based impl has been replaced with a new impl. The API is mostly backward compatible
* to the older 1.0.6 version. Except: zoomLevel display range semantics have changed and useSidWiz has been
* replaced with useSyncMode semantics. (In "useSyncMode" the logic tries to align the displayed data
* such that the oscillation shown in the x-axis center is "fixed in place" .. creating the impression
* of a standing wave. As before this only works for simple waveforms, e.g. pulse, sine, etc)
*
* note: This impl relies on always caching 2*16384 samples per stream. If WebAudio
* buffering is using the maximum buffer size of 16384 then this results in a simple
* double buffering scheme. But actually the implementation uses a circular buffer approach
* and smaller WebAudio buffers will cause more slots to be used in the circular
* buffer (e.g. a buffer size of 8192 would use 4 slots, etc).
*
*
* Copyright (C) 2023 Juergen Wothke
*
* Terms of Use: This software is licensed under a CC BY-NC-SA
* (http://creativecommons.org/licenses/by-nc-sa/4.0/).
*/

class ChannelStreamer extends AbstractTicker2 {
	constructor(zoomLevel, vuOutEnabled)
	{
		super();

		this._outputSize = 0;
		this._changeOutputSize = 0;

		if(typeof zoomLevel == 'undefined') zoomLevel = 3;
		this.setZoom(zoomLevel);

		this._outputV= null;				// "sliding window" output arrays by voice

		this._numStreams = 0;				// currently allocated
		this._smplBufLen = 0;				// currently allocated

		this._usedNumStreams = 0;

		this._vuOutEnabled = typeof vuOutEnabled == 'undefined' ? true : vuOutEnabled;
	}

//////////// implementation of AbstractTicker2 API ////////////
	init(samplesPerBuffer, tickerStepWidth, readFloatFunc)
	{
		this._readFloatFunc = readFloatFunc;
		this._samplesPerBuffer = samplesPerBuffer;

		let slotsOld = this._circularBufSlots;
		this._circularBufSlots = 2 * 16384 / samplesPerBuffer;	// i.e. 256 slots for AudioWorklet

		this._currentBufIdx = this._circularBufSlots - 1;
		this._tickerStepWidth = tickerStepWidth;

		this._reset(slotsOld);
	}

	startAudioBuffer()
	{
		this._stepCircularBuffer();
	}

	resampleTickerData(sampleRate, inputSampleRate, streamLen, numStreams, streams)
	{
		this._usedNumStreams =  numStreams;
		try { this._assertBuffers(); } catch(e) { return; /* not ready.. do nothing */ }

		// make sure that the raw data is still in sync after the resampling of the audio data (emulator output)
		const resampleLen = (sampleRate == inputSampleRate) ? streamLen : Math.round(streamLen * sampleRate / inputSampleRate);

		// cache resampled data in more accessible form (allocated size will quickly stabilize)
		for (let i = 0; i < this.getNumStreams(); i++) {
			this._resampleV[i] = this._assertBuffer(this._resampleV[i], resampleLen);
		}

		// resample the add-on data if necessary
		for (let i = 0; i < this.getNumStreams(); i++) {
			this._resample(streams[i], streamLen, this._resampleV[i], resampleLen);
		}
	}

	copyTickerData(outputIdx, inputIdx, len)
	{
		try { this._assertBuffers(); } catch(e) { return; /* not ready.. do nothing */ }

		let buf = this._buffers[this._currentBufIdx]; // target slot was selected in "startAudioBuffer"

		for (let i = 0; i < this.getNumStreams(); i++) {
			let outBuf = buf[i];
			let inBuf = this._resampleV[i];

			for (let j = outputIdx, k = inputIdx; j < (outputIdx + len); j++, k++) {
				outBuf[j] = inBuf[k];
			}
		}
	}


//////////// APIs specifically provided by ChannelStreamer ////////////

	/**
	* @param zoomLevel range 1..5; higher means x-times more sample data
	*/
	setZoom(zoomLevel)
	{
		const sampleRate = ScriptNodePlayer.getWebAudioSampleRate();

		if (zoomLevel < 1 || zoomLevel > 5)
		{
			console.log("error: invalid zoom level " + zoomLevel + " (settings unchanged!)");
		}
		else
		{
			this._zoomLevel = zoomLevel;

			let samplesPerFrame = sampleRate / 60; // i.e. ca 800-1600 depending on soundcard settings
			this._setOutputSize(samplesPerFrame * (zoomLevel + 1));	// must be big enough to allow for reserved section
		}
	}

	getZoomLevel()
	{
		return this._zoomLevel;
	}

	getDataLength()
	{
		try {
			this._assertBuffers();
			this._assertOutputV();

			return this._outputSize;
		}
		catch(e) {
			return  Math.max(this._outputSize, this._changeOutputSize);
		}
	}

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
	getData(voice)
	{
		try {
			this._assertBuffers();
			this._assertOutputV();
		}
		catch(e) {
			let len = Math.max(this._outputSize, this._changeOutputSize);

			if ((typeof this.phantomChannel == 'undefined') || this.phantomChannel.length < len)
			{
				this.phantomChannel = new Float32Array(len);
			}
			return this.phantomChannel;
		}

		if (voice >= this.getNumStreams())
		{
			return this.phantomChannel;
		}
		else
		{
			let destBuffer = this._outputV[voice];
			if (typeof destBuffer != 'undefined')
			{
				this._copySlidingWindow(voice, destBuffer);

				if (this._vuOutEnabled) this._updateVuLevel(voice, destBuffer);
			}
			return destBuffer;
		}
	}

	enableVuOutput(on)
	{
		this._vuOutEnabled = on;
	}

	/*
	* This is only updated by the actual scope data being rendered (i.e.
	* when the above getData/_copySlidingWindow() is used and _vuOutEnabled is 'true')
	*/
	getVuMeterLevel(voice)
	{
		if (typeof this._vuMeters == 'undefined') return 0;

		return this._vuMeters[voice];
	}

	getOverallVuMeterLevel()
	{
		const n = this.getNumStreams();
		if (n)
		{
			let vu = 0;

			for(let i = 0; i < n; i++) {
				vu += this.getVuMeterLevel(i);
			}
			return vu/= n >> 1;	// might need to rescale this..
		}
		return 0;
	}

	/**
	* @deprecated functionality moved to VoiceDisplay
	*/
	isSidWizMode()
	{	// keeping this for backward compatibility
		alert("isSidWizMode() no longer available");
		return null;
	}
	/**
	* @deprecated use setZoom() instead
	*/
	setMode(unused, zoomLevel)
	{
		alert("error: ChannelStreamer.setMode() no longer available - use setZoom()");
		this.setZoom(zoomLevel);
	}

	getNumStreams()
	{
		return this._numStreams;
	}


//////////// private methods ////////////

	_reset(slotsOld)
	{
		// clear scope streams
		if (typeof this._buffers != 'undefined')
		{
			if (slotsOld != this._circularBufSlots)
			{
				// scenario: switched backend.. new one uses different buffer size
				this._buffers = undefined;
			}
			else
			{
				for (let j = 0; j < this._circularBufSlots; j++) {
					for (let i = 0; i < this._numStreams; i++) {
						let buf = this._buffers[j][i];

						for (let k = 0; k<this._samplesPerBuffer; k++) {
							buf[k] = 0;
						}
					}
				}
				this._currentBufIdx = this._circularBufSlots-1;
			}
		}
		for (let i= 0; i < this._numStreams; i++) {
			this._vuMeters[i] = 0;
		}
	}

	_assertBuffers()
	{
		// 1st step: make sure same resampling is used for "add-on data" (as was used for the audio data:
		//           the input scope streams use the same sample rate that the original audio generator is
		//           using; that sample rate may be different from the one used by WebAudio and the same
		//           resampling that the ScriptNodePlayer applied to the audio data - before passing it
		//           to WebAudio - is applied to the scope data)

		let n = this._usedNumStreams;

		if ((this._smplBufLen != this._samplesPerBuffer) || (n != this._numStreams))
		{
			this._vuMeters = new Float32Array(n);

			this._resampleV = [];
			for (let i = 0; i < n; i++) {
				this._vuMeters[i] = 0;

				let fa = new Float32Array(this._samplesPerBuffer);
				for (let j = 0; j < this._samplesPerBuffer; j++) {
					fa[j] = 0;
				}
				this._resampleV.push( fa );
			}

			// 2nd step: "add-on" buffers in sync with the WebAudio audio buffer.
			// 			 use double buffering to allow smooth transitions at buffer boundaries

			this._buffers = [];
			for (let j = 0; j < this._circularBufSlots; j++) {
				let a = [];
				for (let i = 0; i < n; i++) {
					a.push( new Float32Array(this._samplesPerBuffer) );
				}
				this._buffers.push( a );
			}

			this._smplBufLen = this._samplesPerBuffer;
			this._numStreams = n;

			// 3rd step: use "sliding window" approach to fill the actual output buffers
			// (respective buffers habe already been allocated via _setOutputSize() from the constructor)
		}
	}

	_stepCircularBuffer()
	{
		this._currentBufIdx += 1;
		if (this._currentBufIdx >= this._circularBufSlots) this._currentBufIdx = 0;
	}

	_assertOutputV()
	{
		const n = this._usedNumStreams;

		const streamsChanged = (this._outputV == null) || (this._outputV.length != n) || (this._outputV.length == 0);

		if (streamsChanged)
		{
			this._outputV = new Array(n);
		}

		if (this._changeOutputSize)
		{
			this._outputSize = this._changeOutputSize;
			this._changeOutputSize = 0;
		}

		if (n && (streamsChanged || (this._outputV[0].length != this._outputSize)))
		{
			for (let i= 0; i < n; i++) {
				this._outputV[i] = new Float32Array(this._outputSize);
			}

			// backward compatibility: e.g. WebSid expects to access more streams than
			// are currently used (see multi-SID scenarios)
			this.phantomChannel = new Float32Array(this._outputSize);
		}
	}

	_setOutputSize(s)
	{
		if (s > 16384)
		{
			console.log("error: max output size is " + 16384);
			s = 16384;
		}
		this._changeOutputSize = s;
	}

	// assumption: all the available zoomLevels produce at least 1/60th second length of
	// sample data
	// (in Worklet scenario this logic could be moved to the "processor" side
	// to remove the load from the UI thread.. however that impl then would not work for
	// old ScriptNodeProcessor based backends..)
	_updateVuLevel(voice, buffer)
	{
		const sampleRate = ScriptNodePlayer.getWebAudioSampleRate();
		let n = sampleRate / 60;

		if (buffer.length < n)
		{
			console.log("error in _updateVuLevel");
			return;
		}
		let startIdx = buffer.length - n;	// take the most recent data from the end of the buffer

		const opt = 3;	// performance opt: do not use every sample
		n >>= opt;

		let s = 0;
		for (let i = 0; i < n; i += (1 << opt)) {
			s += Math.abs(buffer[startIdx + i]);
		}

		this._vuMeters[voice] = ((this._vuMeters[voice] * 0.8 ) + (s << opt) / n ) * 0.5;	// just a quickhack that might still be improved
	}

	_resample(inputPtr, inputLen, resampledBuffer, resampleLen)
	{
		if (resampleLen == inputLen)
		{
			BufferUtil.remapToFloat(inputPtr, inputLen, this._readFloatFunc, resampledBuffer);
		}
		else
		{
			BufferUtil.resampleToFloat(1, 0, inputPtr, inputLen, this._readFloatFunc, resampledBuffer, resampleLen);
		}
	}
	_allocResampleBuffer(s)
	{
		// note: use of typed array has a significant performance benefit over normal Array in this context, e.g.
		// 12% "CPU use for scripting" vs 16.5%. Float32Array is somewhat faster that Float64Array (12% vs 12.25%) -
		// in spite of the additional float<->double conversions that this probably causes..

		// FIXME: Ideally the scope data should probably always be represented as "signed 32-bit integer" so that
		// the silly JavaScript hacks ( ">>> 0") can be used to avoid any unnecessary back and forth double
		// conversions and always use fast 32-bit integer handling. todo: some performance profiling needs to
		// be done to check how much of a benefit (if any) a respective approach might provide.

		return new Float32Array(s);
	}

	_assertBuffer(buf, size)
	{
		if (size > buf.length)
		{
			buf = this._allocResampleBuffer(size);
		}
		return buf;
	}

	_copySlidingWindow(voiceId, destBuffer)
	{

		// note: in the original scenario ScriptNodeProcessor renders audio buffer that is a multiple the size of the
		// minimum measurable interval of 256 samples and the "tick" construct is used to achieve higher precision positioning
		// within a buffer that already contains data "for the future". But in the AudioWorklet context respective
		// buffers are already only 128 samples long, and the precision is already higher than what the tick based
		// logic can handle. The "tick" there has a 1:1 relation to the rendered audio buffers

		let tick = 0;

		// find the end position of the "last played" data and then copy the needed amount of data before that position..
		// (buffer is copied starting from the end)

		let ticksPerBuffer = ScriptNodePlayer.getInstance().getMaxTicks();

		// "current" position within the audio buffer (this is not precise since the latency with which
		// WebAudio used the buffers is not defined)
		tick = ScriptNodePlayer.getInstance().getCurrentTick();

		let srcEndOffset = (tick + 1) * this._tickerStepWidth;	// is also how much data remains in this slot

		let outputLen = destBuffer.length;

		let srcSlotIdx = ScriptNodePlayer.getInstance().getBufNum();
		srcSlotIdx = (srcSlotIdx == -1) ? this._circularBufSlots-1 : srcSlotIdx % this._circularBufSlots;

		let srcBufs = this._buffers[srcSlotIdx];
		let srcBuf = srcBufs[voiceId];

		while (outputLen > 0) {
			// _arrayCopy(src, srcOffset, len, dest, destOffset)
			if (outputLen >= srcEndOffset)
			{
				// copy all that remains in this slot
				outputLen -= srcEndOffset;
				this._arrayCopy(srcBuf, 0, srcEndOffset, destBuffer, outputLen);

				srcSlotIdx = srcSlotIdx ? srcSlotIdx-1 : this._circularBufSlots-1;

				srcBufs = this._buffers[srcSlotIdx];
				srcBuf = srcBufs[voiceId];

				srcEndOffset = ticksPerBuffer * this._tickerStepWidth;
			}
			else
			{
				// copy used part of initial slot
				this._arrayCopy(srcBuf, srcEndOffset-outputLen, outputLen, destBuffer, 0);
				break;
			}
		}
	}

	// caution: does not check boundary violations - you better know what you are doing
	_arrayCopy(src, srcOffset, len, dest, destOffset)
	{
		// riddiculous that this kind of function does not come with this joke of a
		// language (and I am not talking about the expensive reallocation workarounds..)

		for (let i = 0; i < len; i++) {
			dest[destOffset+i] = src[srcOffset+i];
		}
	}
};


/*
* Example for basic rendering of streamed "add-on" data.
*
* Can be used to directly render to canvas or to fill BufferGeometry data for WEBGL use.
*
* When _useSyncMode is used the impl tries to make sure that the wave shown in the center of
* the image (x-axis) is always nicely centered. This is only useful for "simple" waveforms
* (e.g. pulse, sin, etc) where it may create the impression of a standing waveform.
*
* The impl reserves a fixed portion at the start of the imput buffer as a margin that can then
* be used to align the window that is actually drawn. The size of the reserved portion is based
* on the number of samples that would be used for one oscsillation of the lowest relevant
* note (e.g. 45Hz - allowing for some added margin). By default it is the remaining buffer
* (after the reserved portion) that is drawn - but for alignment purposes the start position
* may be shifted backwards into the reserved area.
*
* @param target either a divId to directly use canvas rendering or a float array as a base for WEGBL use
*/
class VoiceDisplay {
	/**
	* @param target either the String id of an HTMLCanvasElement or an HTMLCanvasElement object to
	*               render to; anything else selects "vertex rendering" mode
	*/
	constructor(target, ticker, getDataFunc, useSyncMode)
	{
		this._useSyncMode = useSyncMode;

		this._ticker = ticker;
		this._getData = getDataFunc;

		this._drawOnCanvas = false;

		if (typeof target == "string")
		{
			this._canvas = document.getElementById(target);
			this._drawOnCanvas = true;
		}
		else if(target && (typeof target == "object") && (target.constructor.name == 'HTMLCanvasElement'))
		{
			this._canvas = target;
			this._drawOnCanvas = true;
		}

		if (this._drawOnCanvas)
		{
			this._ctx = this._canvas.getContext('2d');
			this.setStrokeColor("rgba(1, 0, 0, 1.0)");
			this._outVertices = null;
		}
		else
		{
			this._outVertices =  new Float32Array(0);
			this._canvas = this._ctx = null;
		}

		this.setSize(512, 80);
	}

	/**
	* If "vertex rendering" mode is used (see "target" param of constructor) this method
	* returns the respective output buffer.
	*/
	getOutputVertices()
	{
		return this._outVertices;
	}

	setSyncMode(useSyncMode)
	{
		this._useSyncMode = useSyncMode;
	}

	setSize(width, height)
	{
		this._width = width;
		this._height = height;

		if (this._canvas)
		{
			this._canvas.width = width;
			this._canvas.height = height;
		}
	}

	setStrokeColor(color)
	{
		this._strokeColor = color;
	}

	_reqAnimationFrame()
	{
		window.requestAnimationFrame(this.redraw.bind(this));
	}

	redraw()
	{
		this.redrawGraph();
		this._reqAnimationFrame();
	}

	_getMinMax(data, startOffset, len)
	{
		let min = -1;
		let max = 1;

		for (let i = 1; i < len; i++) {
			let v = data[startOffset + i];

			if (v < min) min = v;
			if (v > max) max = v;
		}
		return [min, max];
	}

	_getBounds(lo)
	{
		let m = Math.abs(lo * 0.1);	// allow for some jitter
		return [lo - m, lo + m];
	}

	_getIdxLo(data, startOffset, scanLen)
	{
		// detect right-most wave low point (scans backwards towards reserved space)

		// issue: here also the min of repeated oscillations may be marginally
		// smaller (leading to a later oscillation to be picked) and it would be
		// preferable to just ignore those irrelevant differences.. however for the
		// display of pulse WFs it is important to properly pick the end point of
		// the pulse

		// issues testcase Credits.sndh: on the macro level the waveform looks like
		// a filled triangle WF but on the micro level the filling actually consists
		// of something like a high frequency pulse. This means that inside the triangle
		// there are minima everywhere and the only chance to "align" this waveform
		// nicely is when detection starts outside of the triangle..

		let slide = true;

		// use average of multiple samples to avoid the above issue
		let lo = data[startOffset] +  data[startOffset-1] + data[startOffset-2];
		let [lB, uB] = this._getBounds(lo);

		let offset = 0;

		for (let i = 1; i<scanLen; i++) {
			let v = data[startOffset - i] + data[startOffset - i - 1] + data[startOffset - i - 2];

			if(slide && (v <= lo))
			{
				 // try to find the lowest leftmost point (see pulse WF)
				offset = i;
				lo = v;
				[lB, uB] = this._getBounds(lo);
			}
			else if (v < lB)
			{
				slide = true;
				offset = i;
				lo = v;
				[lB, uB] = this._getBounds(lo);
			}
			else if (v > uB)
			{
				slide = false;
			}
		}
		return offset;
	}

	_getHiLimit(hi)
	{
		// wave may be totally in negative range..
		return hi + Math.abs(hi * 0.25);	// hand tuned with Xenon2
	}

	_getIdxHi(data, startOffset, scanLen)
	{
		// detect right-most wave high point  (scans backwards towards reserved space)

		// issue: even for repeated wave patterns there may be minimal differences between
		// the min/max of respective oscillations.. this may lead to the matching of points
		// that belong to different oscillations.. which than causes ugly flickering

		let slide = true;
		let hi = data[startOffset];
		let limit = this._getHiLimit(hi);

		let offset = 0;

		for (let i = 1; i<scanLen; i++) {
			let v = data[startOffset - i];

			if(slide && (v >= hi))
			{
				// find the highest point as long as it's going up
				offset = i;
				hi = v;
				limit = this._getHiLimit(hi);
			}
			else if(v > limit)
			{
				// start a new detection only if the starting
				// point is significantly higher than what we already have

				offset = i;
				hi = v;
				limit = this._getHiLimit(hi);
				slide = true;
			}
			else
			{
				slide = false;
			}
		}
		return offset;
	}



	_getMaxScanRange()
	{
		// number of samples for the lowest handled frequency (ca >872 depending on sampleRate)
		// (this portion of the data is reserved for potential alignment shift)

		const sampleRate = ScriptNodePlayer.getWebAudioSampleRate();
		return Math.floor(sampleRate / 45);
	}

	redrawGraph()
	{
		if (ScriptNodePlayer.getInstance().isPaused() || !this._ticker.getNumStreams()) return;

		const debug = false;

		const maxScanRange = this._getMaxScanRange();

		// reminder: The input data is always float32. An attempt to improve performance by
		// forcing use of "cheaper" 32-bit operations in the below logic (via Math.fround)
		// did not yield any measurable improvement

		let data = this._getData();

		if (data.length == 0) return;

		let shownLen = data.length - maxScanRange;	// length of subset that is actually displayed

		let centerOffset = Math.floor(maxScanRange + (shownLen >> 1));

		let idxLo;
		let idxHi;
		let startOffset;

		if (this._useSyncMode)
		{
			idxLo = this._getIdxLo(data, centerOffset, maxScanRange);
			idxHi = idxLo + this._getIdxHi(data, centerOffset-idxLo, maxScanRange-idxLo);

			// adjusted start position so that center wave is aligned
			startOffset = maxScanRange - ((idxLo + idxHi) >> 1);
		}
		else
		{
			startOffset = maxScanRange;
		}


		// testcase V2M: the range of the used data here sometimes goes from -6 to +6 and the
		// original assumption that the input range is always -1..1 is incorrect

		const [min, max] = this._getMinMax(data, startOffset, shownLen);

		let s = this._height / (max - min);
		let step = this._width/shownLen;

		if (this._drawOnCanvas)
		{
			try {
				// seems that dumbshit Safari (11.0.1 OS X) uses the fillStyle for "clearRect"!
				this._ctx.fillStyle = "rgba(0, 0, 0, 0.0)";
			} catch (err) {}
			this._ctx.clearRect(0, 0, this._width, this._height);

			this._ctx.strokeStyle = this._strokeColor;
			this._ctx.save();
			this._ctx.beginPath();


			if (debug) startOffset = maxScanRange; // use original unaligned input


			for (let i = 0; i < shownLen; i++) {
				// translate to 0..1 range
				let magnitude = (data[startOffset+i] - min) * s;

				// invert Y or graphs will be upside down
				if (i == 0)
				{
					this._ctx.moveTo(Math.floor(i * step), this._height - magnitude);
				}
				else
				{
					this._ctx.lineTo(Math.floor(i * step), this._height - magnitude);
				}
			}

			this._ctx.stroke();
			this._ctx.restore();

			if (debug && this._useSyncMode)
			{
					// mark search window
				this._redrawDbgLine("red", Math.floor(this._width / 2));
				this._redrawDbgLine("red", Math.floor(this._width / 2 - maxScanRange / shownLen * this._width));
					// detected low point
				this._redrawDbgLine("blue", Math.floor(this._width / 2 - idxLo / shownLen * this._width));
					// detected high point
				this._redrawDbgLine("green", Math.floor(this._width / 2 - idxHi / shownLen * this._width));
			}
		}
		else
		{
			// store coordinate in array for use in THREE.js BufferGeometry
			// z values remain unchanged.. presumably 0
			let vertexArrayLen = shownLen * 3;

			if (this._outVertices.length != vertexArrayLen)
			{
				this._outVertices = new Float32Array(vertexArrayLen);
				this._outVertices.fill(0);
			}

			for (let i = 0; i < shownLen; i++) {
				let magnitude = (data[startOffset+i] - min) * s;

				this._outVertices[3*i] = Math.floor(i * step);			// x
				this._outVertices[3*i+1] = this._height - magnitude;	// y
			}
		}
	}

	_redrawDbgLine(color, x)
	{
		this._ctx.strokeStyle = color;
		this._ctx.save();
		this._ctx.beginPath();

		this._ctx.moveTo(x, this._height);
		this._ctx.lineTo(x, 0);

		this._ctx.stroke();
		this._ctx.restore();
	}
};
