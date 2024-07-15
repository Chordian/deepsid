/**
* Generic WebAudio Node based player.
*
* version 1.3.1a
*
* Copyright (C) 2023 Juergen Wothke
*
*
* As compared to earlier versions, a few non backward-compatible changes have been made in
* version 1.2: AbstractTicker API was cleaned up and is now called AbstractTicker2 you need an updated
* version of channelstreamer.js that has been adapted to the changes.
*
* SAMPLES_PER_BUFFER var must nolonger be used. Also the bufferSize param of createInstance(..) is now
* completely ignored (use respective setter/getter on ScriptNodeBackendAdapter level instead, see setProcessorBufSize()).
* The doOnUpdate param of createInstance(..) is now also ignored. Respective notification functionality
* has now to be handled in the (very few) BackendAdapters that actually use this (e.g. UADE). The semantic
* of ScriptNodePlayer.getInstance() was changed: the function now returns null while the player is not
* ready. Logic that previously used the separate isReady() check would have to check for null instead and
* isReady() will always return true since only "ready" instances are returned.
*
* The original "poor man's JavaScript classes" hack was replaced with regular ES6 classes, i.e. existing
* backend subclasses must be adapted to the respective syntax (e.g. "class Foo extends ScriptNodeBackendAdapter {..."
* This is merely a syntactic change that must be performed in backend subclasses.
* The "surrogateCtor" used for the old "class impl" has been removed and code that reused that approach elsewhere
* will need to now get that function from elsewhere.
*
* Additional Promise based APIs were introduced (initialize(..), loadMusicFromURL(..), loadFileData(..))
* that allow to replace the use of some of the originally "callback based" functions with respective "Promise/then"
* syntax - which some users seem to prefer.
*
* renamed: createProducerNode -> _createProducerNode
*
* In some cases utility functionality may have moved from ScriptNodeBackendAdapter to respective BaseFileMapper/
* ScopeDataProvider helper classes hierarchies. When overloading default implementations it may be preferable to
* subclass the respective helpers rather than the methods of ScriptNodeBackendAdapter.
*
* The abstract backend base classes now provide more default implementations that allow to reduce the
* number of methods that have to be defined in subclasses (e.g. see SimpleFileMapper - check my various
* backend classes for examples).
*
*
*
* Used naming conventions: A "_" prefix implies that something is private/protected and should not
* be used from UI code.
*
*
* This infrastructure conceptually consists of two primary parts:
*
* 1) ScriptNodePlayer:        The generic player which must be parameterized with a specific AudioBackendAdapterBase
*                             subclass (respective concrete subclasses are not contained in this file)
*
* 2) AudioBackendAdapterBase: An abstract base class for specific backend (i.e. 'sample data producer') integration.
*                             This is not meant for end users but for developers that want to implement respective backends.
*
*
* There are then various helpers/add-ons relevant only to backend developers:
*
* - AbstractTicker2     add-on API allows to handle additional (compared to the main audio data) data streams
*
* - BaseFileMapper      encapsulates "file name mapping" related behavior used in AudioBackendAdapterBase subclasses
*   - SimpleFileMapper  simple mapper subclass used for EMSCRIPTEN based backends
*
* - ScopeDataProvider   encapsulates AbstractTicker2 related behavior used in AudioBackendAdapterBase subclasses
*   - HEAPF32ScopeProvider subclass handling EMSCRIPTEN/float32 based streams
*   - HEAP32ScopeProvider  subclass handling EMSCRIPTEN/int32 based streams
*   - HEAP16ScopeProvider  subclass handling EMSCRIPTEN/int16 based streams
*
* - ScriptNodeBackendAdapter abstract AudioBackendAdapterBase subclass for ScriptProcessorNode based backends
*   - EmsHEAPF32BackendAdapter subclass handling EMSCRIPTEN based backends that produce float32 sample data
*   - EmsHEAP32BackendAdapter  subclass handling EMSCRIPTEN based backends that produce int32 sample data
*   - EmsHEAP16BackendAdapter  subclass handling EMSCRIPTEN based backends that produce int16 sample data
*
*   - OutputTransformer  utility used by ScriptNodeBackendAdapter
*
*
* Terms of Use: This software is licensed under a CC BY-NC-SA
* (http://creativecommons.org/licenses/by-nc-sa/4.0/).
*/

var fetchSamples = function(e) 	// only used for ScriptProcessorNode based impls^
{
	// at some point it had been necessary to keep this explicit reference to the event-handler
	// in order to pervent the dumbshit Chrome GC from detroying it eventually (this might no longer be
	// necessary in recent Chrome versions - but since those idiots already wasted my time having to
	// come up with this silly hack I might just as well keep it)
	let player = ScriptNodePlayer.getInstance();

	if (player) player._backendAdapter._transformer.genSamples(e);
};

var calcTick = function(e) 	// only used by ScriptProcessorNode based impls
{
	let player = ScriptNodePlayer.getInstance();

	if (player && !player.isPaused()) {
		player._backendAdapter._transformer.tick(e);
	}
};

var DbgUtil = {
	_traceSwitch: false,

	/**
	* Turn on debug output to JavaScript console.
	*/
	setTraceMode: function(on)
	{
		this._traceSwitch = on;
	},
	trace: function(str)
	{
		if (this._traceSwitch) {
			console.log(str);
		}
	},
};


var BufferUtil = {
	/*
	* Utility converts array of inputs to array of floats.
	* @input any type of array or pointer that the funcReadFloat function can handle,
	*        e.g. used to convert EMSCRIPTEN mem buffers to JavaScript float arrays..
	*/
	remapToFloat: function(input, len, funcReadFloat, output)
	{
		// just copy the rescaled values so there is no need for special handling in playback loop
		for(let i= 0; i<len; i++){
			output[i]= funcReadFloat(input, i);
		}
		return len;
	},
	
	resampleToFloat: function(channels, channelId, inputPtr, len, funcReadFloat, resampleOutput, resampleLen)
	{
		// Bresenham (line drawing) algorithm based resampling
		let x0 = 0;
		let y0 = 0;
		let x1 = resampleLen - 0;
		let y1 = len - 0;

		let dx =  Math.abs(x1 - x0), sx = x0 < x1 ? 1 : -1;
		let dy = -Math.abs(y1 - y0), sy = y0 < y1 ? 1 : -1;
		let err = dx+dy, e2;

		var i;
		for(;;) {
			i = (x0 * channels) + channelId;
			resampleOutput[i] = funcReadFloat(inputPtr, (y0 * channels) + channelId);

			if (x0>=x1 && y0>=y1) { break; }
			e2 = 2 * err;
			if (e2 > dy) { err += dy; x0 += sx; }
			if (e2 < dx) { err += dx; y0 += sy; }
		}
	},
};

// @deprecated this function was never a part of the public API! todo: ditch this completely
const setGlobalWebAudioCtx = -1;

// @deprecated old code must be migrated to changed AbstractTicker2 API!
const AbstractTicker = -1;

/**
* This abstract base class allows to handle "add-on" data for visualization purposes and similar use cases.
*
* The respective add-on data conceptually corresponds 1:1 to the audio stream data that WebAudio is
* playing. But since the WebAudio infrastructure is NOT handling the add-on data it has to be handled
* separately (i.e. here). The source of the add-on data is the same "backend" call that produces the
* main audio data. And in order to get a view on the add-on data that is synchronized with what WebAudio is
* currently playing the "same" transformations that were used on the audio data are replicated here
* with display specific logic added on top.
*
* Roughly this involves the below sequence of events:
*
* 1) The backend creates a batch of audio data (e.g. 1000 samples designed for a 44.1kHz sampleRate).
*    This may be different from what WebAudio expects (e.g 8192 samples for a 48kHz sampleRate). As
*    a first step the produced data is resampled to the sampleRate required by WebAudio (if necessary).
*
* 2) Then the buffer expected by WebAudio is filled using the resampled chunks produced in step 1
*    (repeating step 1 as often as necessary).
*
* 3) For display purposes there is finally a mismatch between the buffer sizes used by WebAudio and
*    the relevant screen refresh rate: At a typical 60Hz screen redraw rate, 800 audio samples are
*    played between two screen refreshes (at a typical 48kHz sampleRate). But the buffer size used
*    by WebAudio ScriptProcerssorNode may be as large as 16k samples. To display the part of the data
*    that is currently playing, more fine grained positioning information may be needed (which in this
*    context is handled based on a construct called 'tick' - see ScriptNodePlayer.getInstance().getCurrentTick()).
*    Also there is a latency between the production of a new audio buffer to when than buffer is actually
*    played by WebAudio.
*    Depending on the used WebAudio Node type the audio buffer granulatity might already be finer
*    than needed - in which case this 3rd step is not required.
*
*
* The callbacks provided by this API get triggered at respective points in the audio generation process.
* It is then up to the subclass how it wants to deal with the received information.
*/
class AbstractTicker2 {
	constructor()
	{
	}

	/**
	* Basic initialization.
	*
	* @param samplesPerBuffer number of audio samples in the original WebAudio audio buffers
	* @param tickerStepWidth  number of audio samples that are played between "tick events" (e.g. 256 for
	*                         ScriptProcessorrNode based backends, the number of 'tick' events
	*                         associated with each sample buffer is: samplesPerBuffer/tickerStepWidth)
	* @param readFloatFunc    accessor function used to retrieve a value from a specific add-on stream
	*                         (all streams must contain the same type of data)
	*/
	init(samplesPerBuffer, tickerStepWidth, readFloatFunc) {}

	/**
	* Triggered at the start of WebAudio Node's audio buffer generation.
	*
	* Allows the subclass to update its own data structures accordingly.
	*/
	startAudioBuffer() {}

	/**
	* Triggered whenever the backend generates a new batch of sample data.
	*
	* The original audio generation logic will typically resample the audio data at this point.
	*
	* @param sampleRate			WebAudio's sampleRate
	* @param inputSampleRate	backend's sampleRate
	* @param streamLen			length of each stream
	* @param numStreams       	the number of add-on streams currently available
	* @param streams			array of the respective add-on data streams
	*/
	resampleTickerData(sampleRate, inputSampleRate, streamLen, numStreams, streams) {}

	/**
	* Triggered when the original playback logic copies the audio data from
	* its resampled buffer to the actual WebAudio output buffer.
	*
	* @param outputIdx			destination idx in WebAudio output buffer
	* @param inputIdx			source idx in a resampled version of the "streams" last
	*                           passed to resampleTickerData()
	* @param len				number of resampled samples to be copied
	*/
	copyTickerData(outputIdx, inputIdx, len) {}

};

// use respective setProcessorBufSize() / getProcessorBufSize() APIs on ScriptNodeBackendAdapter!
const SAMPLES_PER_BUFFER = -1;


class BaseFileMapper {
	constructor() {
	}
	
	/**
	* Reinitialize the mapper at the start of a new song.
	*/
	init(filename)
	{
	}

	/**
	* Maps the originally input song's filename to what the backend expects that
	* file should be called. 
	*
	* Also the filename is transformed into a path that can be used in a
	* regular filessystem (FS).
	*
	* usecase: Backend may depend on specific filename extension for some
	* song format to be properly detected. If the file is hosted using a 
	* different name, then this mapping allows to present the file to
	* the backend using that different name. (example: on Modland a Zoundmonitor
	* file is called "foo.sng" but the backend only works if it were 
	* called "foo.zn")
	*/
	mapToVirtualFilename(filename)
	{
		return filename;
	}

	/**
	* Maps virtual filenames created by mapToVirtualFilename() back to the
	* original filenames.
	*/
	mapFromVirtualFilename(virtualName)
	{
		return virtualName;
	}

	/**
	* Stores a loaded file in such a way that it can later be found by the backend.
	*
	* For typical Emscripten based backends this means that "FILE* fopen(..)" calls would later be used
	* to access the file and here the data would need to be registered in Emscripten's FS for those
	* calls to work. (The mapping performed in mapBackendFilename() must here be inverted.)
	*
	* @param pathFilenameArray path and filename (virtual name) used by the browser to load the file
	* @return a filehandle meaningful to the used backend
	*/
	registerFileData(pathFilenameArray, data)
	{
	}

	/**
	* This is triggered whenever the backend code tries to load some file.
	*
	* The purpose of the function is to map the filename expected by the backend to whatever
	* the file might be called where the file is hosted. (example: some UADE eagleplayer might expect
	* some sample file to be called "SMP.foo" when in practice that file is actually called "foo.smp"). 
	*
	* In order for the backend to use the data the above registerFileData() API must be used
	* to provide the inverse mapping to store the file data in the correct FS location.
	*
	* @param name   "virtual" filename (backend side)
	* @return "virtual" filename ("real world")
	*/
	mapBackendFilename(virtualName)
	{
		return virtualName;
	}
};

/**
* Simple mapper used for EMSCRIPTEN based backends.
*
* Maps between names used for URLs and names used for file system.
*/
class SimpleFileMapper extends BaseFileMapper {
	constructor(module)
	{
		super();

		this.Module = module;
	}
	
	mapFromVirtualFilename(virtualName)
	{
		let filename = this._mapFs2Uri(virtualName);
		return decodeURI(filename);
	}

	mapToVirtualFilename(filename)
	{
		filename = this._mapUri2Fs(filename);
		return filename;
	}

	mapBackendFilename(virtualName)
	{
		return virtualName;
	}

	normalizePathFilename(pathFilenameArray)
	{
		let path = pathFilenameArray[0];
		let filename = pathFilenameArray[1];

		let tmpPathFilenameArray = new Array(2);
		let p = filename.lastIndexOf("/");
		if (p > 0)
		{
			tmpPathFilenameArray[0] = path + filename.substring(0, p);
			tmpPathFilenameArray[1] = filename.substring(p+1);
		}
		else
		{
			tmpPathFilenameArray[0] = path;
			tmpPathFilenameArray[1] = filename;
		}
		return tmpPathFilenameArray;
	}

	registerFileData(pathFilenameArray, data)
	{
		// MANDATORTY to move any path info still present in the "filename" to "path"
		let normalizedArray = this.normalizePathFilename(pathFilenameArray);
		return this._registerEmscriptenFileData(normalizedArray, data);
	}

	/**
	* Creates a file in Emscripten's virtual file system.
	*
	* Precondition: The Emsctipten module must have been built with FS support enabled.
	*/
	_registerEmscriptenFileData(pathFilenameArray, data)
	{		
		// unfortunately the FS.findObject() or FS.lookupNode() APIs are not exported by 
		// default.. therefore use those that are
		
		try {
			this.Module.FS_createPath("/", pathFilenameArray[0], true, true);
		}
		catch(e) { }

		try {
			// in case file already exists, replace it.. (avoid reliance on garbage 
			// exteption handling provided by FS_createDataFile)
			
			this.Module.FS_unlink(pathFilenameArray[0] + "/" + pathFilenameArray[1]);
		}
		catch(e) { }

		let f;
		try {
			if (typeof this.Module.FS_createDataFile == 'undefined') {
				f = true;	// backend without FS (ignore for drag&drop files)
			}
			else 
			{
				f = this.Module.FS_createDataFile(pathFilenameArray[0], pathFilenameArray[1], data, true, true);

				DbgUtil.trace("_registerEmscriptenFileData: [" +
					pathFilenameArray[0]+ "][" +pathFilenameArray[1]+ "] size: "+ data.length);
			}
		}
		catch(err) {
			// unfortunately Emscripten's exception handling here differs between different versions -
			// and it generally sucks (no stable error "code" available and "message" differs between
			// different versions, 'File exists' vs "FS error", etc?). The above FS_unlink was added 
			// as a workaround to avoid having to deal with respective error handling here
		}
		return f;
	}

	// replace chars that cannot be used in file/foldernames
	// use sequences most likely not used in existing filenames
	_mapUri2Fs(uri)
	{
		var out= uri.replace("//", "{1]");
			out = out.replace("?", "{2]");
			out = out.replace(":", "{3]");
			out = out.replace("*", "{4]");
			out = out.replace("\"", "{5]");
			out = out.replace("<", "{6]");
			out = out.replace(">", "{7]");
			out = out.replace("|", "{8]");
		return out;
	}

	_mapFs2Uri(fs)
	{
		var out= fs.replace("{1]", "//");
			out = out.replace("{2]", "?");
			out = out.replace("{3]", ":");
			out = out.replace("{4]", "*");
			out = out.replace("{5]", "\"");
			out = out.replace("{6]", "<");
			out = out.replace("{7]", ">");
			out = out.replace("{8]", "|");
		return out;
	}
};

/**
* This interface defines how a backend adapter provides "scopes" data.
*
* default: no scope data available
*/
class ScopeDataProvider {
	constructor()
	{
	}

	getNumberTraceStreams()
	{
		return 0;	// MUST NOT change after a song has been loaded
	}

	getTraceStreams()
	{
		return [];
	}

	// @buffer an element from the array returned by getTraceStreams()
	// @idx  index of an entry in the buffer
	readFloatTrace(buffer, idx)
	{
		return 0;
	}

	// hack needed as a workaround for ES6 design stupidity (my original "poor man's"
	// class hack actually worked better than the "ES6 class" garbage!)
	_setAdapter(adapter)
	{
		this._adapter = adapter;
	}
}

/**
* Scope data provider for EMSCRIPTEN/float based streams.
*
* Presumes standard functions to be provided by the EMSCRIPTEN
* backend, as well as
*/
class HEAPF32ScopeProvider extends ScopeDataProvider {
	constructor(backend)
	{
		super();

		this.Module = backend;
	}

	getNumberTraceStreams()
	{
		if (!this._adapter.isAdapterReady()) return 0;
		return this.Module.ccall('emu_number_trace_streams', 'number');
	}

	getTraceStreams()
	{
		let result = [];
		let n = this.getNumberTraceStreams();

		if (!n) return result;

		let ret = this.Module.ccall('emu_get_trace_streams', 'number');
		let array = this.Module.HEAP32.subarray(ret>>2, (ret>>2)+n);

		for (let i= 0; i < n; i++) {
			result.push(array[i] >> 2);	// pointer to float/int32 array
		}

		return result;
	}

	readFloatTrace(buffer, idx)
	{
		// traces are already in the respective format
		return  this.Module.HEAPF32[buffer+idx];
	}
}
/**
* Scope data provider for EMSCRIPTEN/int32 based streams.
*/
class HEAP32ScopeProvider extends HEAPF32ScopeProvider {
	constructor(backend, scale)
	{
		super(backend);

		this._scale = scale;
	}

	readFloatTrace(buffer, idx)
	{
		return  this.Module.HEAP32[buffer+idx] / this._scale;
	}
};

/**
* Scope data provider for EMSCRIPTEN/int16 based streams.
*/
class HEAP16ScopeProvider extends HEAPF32ScopeProvider {
	constructor(backend, scale)
	{
		super(backend);

		this._scale = scale;
	}

	getTraceStreams()
	{
		let result = [];
		let n = this.getNumberTraceStreams();

		if (!n) return result;

		let ret = this.Module.ccall('emu_get_trace_streams', 'number');
		let array = this.Module.HEAP32.subarray(ret>>2, (ret>>2)+n);

		for (let i= 0; i < n; i++) {
			result.push(array[i] >> 1);	// pointer to int16 array
		}

		return result;
	}

	readFloatTrace(buffer, idx)
	{
		return  this.Module.HEAP16[buffer+idx] / this._scale;
	}
};

/**
* Abstract base class that must be subclassed to create concrete backend implementations.
*
* Hook methods that must be overridden are marked with "this.error()" dummy implementations below.
*
* So far this has been used for ScriptProcessorNode and AudioWorkletNode based implementations.
*
* Most backends are pretty straight forward: A music file is input and the backend plays it. Things are
* more complicated if the backend code relies on additional files that must be loaded in order to play
* the music. One problem arises because in the traditional runtime environment files are handled
* synchronously: the code waits until the file is loaded and then uses it (this doesn't work in the
* Web context since respective loads are always asynchronous). There are then difference regarding how
* files are located: In the traditional environment there is typically a "search path" and the file
* may be located in different folders and still be found. Also respective traditional systems often
* are "case insensitive", i.e. files will be found even when they DO NOT match the actual file name
* correctly. Traditional logic may query the directory listing to then use some heuristic or fallback
* strategy to load a suitable file. This approach typically isn't available in the Web context and
* accessing files that do not exist is usually a bad idea since webservers will potentially classify
* such attempts as DOS attacks and block the user.
*
*
* (  reminder: the main functionalities handled by the existing design are:
*
*    1) bi-directional mapping of Web URLs to filesystem paths as a base for
*       the original file access based libs to work in the Web environment
*       (handling the different naming limitations of those two environments)
*
*    2) added tweaking of path information for specific files types (e.g. to load
*       special config files or shared libraries from specific folders).
*       (example: Files required by UADE to play some Amiga song: 1) song file,
*       2) song's sample lib file, 3) EaglePlayer 68k binary player that knows how
*       to play that format, 4) additional 68k shared libraries used by the EaglePlayer
*       binary, etc. 
*
*    3) file caching based on Emscripten's built-in FS (if enabled) as well as
*       caching via a map directly maintained by the player. The redundancy is
*       unnecessarily messy.
*       (fixme: redundant caching should be removed and some cache expiration
*       mechanism should be added in order to not cache files "forever").
*
*    4) remapping of inconsistent garbage names: "public" collections like
*       modland contain files with very poor naming consistency (or atleast their naming
*       is not compatible with the naming conventions expected by certain players). In order 
*       for a backend to be able to play these, names used in the collection have to be
*       mapped to the names expected by the backend logic.
*
*    5) while originally designed to handle "locally" hosted files (e.g. in specific
*       subfolders of the site's root folder) the infrastructure has been later
*       ammended to also handle the 3rd party hosting (e.g. modland) scenario
*       as well as the handling of files that are loaded via drag&drop (i.e. with
*       no path information) - there evolved model may not always be clean.
* )
*
* Note: As a non-standard use the API can be abused to disable/bypass the normal Node
* based implementation and take over control in the backend adapter. The following method is
* intended for this exotic use case and should otherwise be left alone:
* skipFileLoad(). This has been used to control a regular HTML <audio> based player
* through the ScriptNodePlayer API (allowing for a uniform handling of built-in and user supplied players).
* The benefits of using the ScriptNodePlayer as little more than a wrapper are obviously limited (e.g.
* allowing to reuse the same GainNode and AnalyserNode) and features like the AbstractTicker2 won't work.
*/

class AudioBackendAdapterBase {
	constructor(fileMapper, scopeProvider)
	{
		this._externalTicker;
		this._observer;

		this._onTrackEnd = null;

		this._fileMapper = (typeof fileMapper == 'undefined') ? new BaseFileMapper() : fileMapper;
		this._scopeProvider = (typeof scopeProvider == 'undefined') ? new ScopeDataProvider() : scopeProvider;

		// another example of ES6 design stupidity: "this" pointer cannot be accessed in constructor before
		// super constructor has been called, i.e. the subclasses cannot directly pass their "this"
		// pointer to the ScopeDataProvider (that needs the reference) but the super clsss (here) can..

		this._scopeProvider._setAdapter(this);
		
		this._songInfo = {};
	}

//////////// WebAudio pipeline setup ////////////

	/**
	* Creates the WebAudio producer Node used by this backend.
	*
	* The existing player APIs depend on _createProducerNode() being able to synchronously
	* return a Node that can actually be used in the WebAudio pipeline. In "certain modern"
	* scenarios this may no longer be straight forward to achieve and the additional
	* _assertSyncNodeReadiness() API may need to be used for the necessary hacks.
	*/
	_createProducerNode(audioCtx) { this.error("_createProducerNode"); }

	/**
	* This indirection unfortunately had to be introduced to preserve backward
	* compatibility while adding support for "modern" (garbage) AudioWorkletNode infrastructure.
	* (see respective subclasses for more information)
	*
	* Default: The "producer" Node can immediately be used synchronously, i.e. its "readiness" status can always be
	* queried synchronously. So eventhough the "backend" used by the Node may not be ready yet (e.g. due to WASM
	* code still being loaded, etc), that all is handled by the existing "IU-thread side" backend infrastructure.
	*/
	_assertSyncNodeReadiness(delayedPlayerCtor)
	{
		delayedPlayerCtor();	// default: run immediately
	}


	/**
	* Optional: Use of an additional "ticker" Node may provide more fine grained playback timing information
	* (than what the main "producer" Node is capable of; see ScriptProcessorNode based backends).
	*
	* This API is only needed for data visualization purposes (see AbstractTicker2). For "producer" Nodes
	* that already use fine grained buffers (e.g. 128 entries for AudioWorkletNode) a
	* respective add-on "ticker" is not needed.
	*/
	_connectTickerNode(ctx, gainNode) { } // default: no separate ticker


//////////// backend life-cycle related ////////////

	/*
	* Implement if subclass needs additional setup logic.
	*
	* e.g. used WASM code may still be loading asynchronously after the class has "successsfully" been instanciated
	*/
	isAdapterReady()
	{
		return true;
	}

	/**
	* Resets backend after state relevant for audio output has been changed.
	*/
	_resetBuffers() {}

	/**
	* Reinitialize the adapter at the start of a new song.
	*/
	init(filename)
	{
		this._fileMapper.init(filename);
	}

//////////// music file load related ////////////

	/**
	* Loads the song's binary data into the backend as a first step towards playback.
	*
	* The subclass can either use the 'data' directly or use the 'filename' to retrieve the same data
	* indirectly (e.g. when regular file I/O APIs are used).
	*
	* @param sampleRate the required output sample rate (this sample rate may not be directly supported
	*                   by the underlying backend logic - in which case the resetSampleRate() API has to
	*                   be used to setup the necessary resampling.
	* @param path/filename internal format! (e.g. "@" prefix)
	*/
	loadMusicData(sampleRate, path, filename, data, options) { this.error("loadMusicData"); }

	/**
	* Second step towards playback: Selects specific track (sub-song) and settings for the loaded song file.
	*
	* To be overridden/extended by subclasses.
	*/
	evalTrackOptions(options)
	{
		// default timeout handling
		ScriptNodePlayer.getInstance().setPlaybackTimeout((typeof options.timeout != 'undefined') ? options.timeout * 1000 : -1);

		return 0;
	}

	/**
	* Cleanup backend before playing next music file
	*/
	teardown() 
	{ 
		this._songInfo = {};	// note: emu may already trigger handleBackendSongAttributes() from loadMusicData
	}


//////////// meta information about currently selected track ////////////

	/**
	* Advertises the song attributes that can be provided by this backend.
	*/
	getSongInfoMeta() { }

	/**
	* Gets info about currently selected music file and track. Respective info very much depends on
	* the specific backend - use getSongInfoMeta() to check for available attributes.
	*
	* In most cases respective information is immediately available after the track to be
	* played has been selected via evalTrackOptions(). However in rare scenarios that information
	* if "discovered" at a later time (see webUADE) and then "pushed" to the UI (see handleBackendSongAttributes()).
	*/
	updateSongInfo(virtualName) { }

	/**
	* Get backend specific song infos like 'author', 'name', etc.
	*/
	getSongInfo()
	{
		return this._songInfo;
	}
	
	/**
	* Backend may "push" update of song attributes (like author, copyright, etc) after the song is already playing.
	*
	* Unused by most backends, i.e. usually optional.
	*/
	handleBackendSongAttributes(backendAttr) { this.error("handleBackendSongAttributes"); }


//////////// playback related ////////////

	/**
	* Optional: Sets up the resampling.
	*
	* @param sampleRate required output sample rate
	* @param inputSampleRate sample rate actually produced by the backend
	*/
	resetSampleRate(sampleRate, inputSampleRate) { }	// default: the backend directly supports the required sampleRate (see loadMusicData()).


	/**
	* Number of channels, i.e. 1= mono, 2= stereo
	*/
	getChannels()
	{
		return 2;		// most backends produce stereo
	}

	/**
	* Gets the number/index of the most recently produced audio buffer.
	*/
	getBufNum() {	this.error("getBufNum"); }

	/**
	* Change the default 5sec timeout  (0 means no timeout).
	*/
	setSilenceTimeout(silenceTimeout) {}	// default: not supported

	/**
	* Manually defined playback time to use until 'end' of a track (only affects the
	* currently selected track).
	*
	* @param t time in millis
	*/
	setPlaybackTimeout(t) {} // default: not supported

	/**
	* Gets the playback timeout used for the currect song.
	*
	* @return in millis; -1 means no timeout defined
	*/
	getPlaybackTimeout() { return -1;	}

	/**
	* Time in seconds that the song has been playing.
	*
	* Measures the data that has been delivered to WebAudio (i.e. the
	* last produced batch may not have been played yet).
	*
	* This information is redundant to what could be calculated using getBufNum().
	* fixme: cleanup overlaps with getPlaybackPosition() & getBufNum()
	*/
	getCurrentPlaytime() { return -1; }


//////////// Optional: "scope/trace" output ////////////

	// the below methods have to be overwritten in order to use AbstractTicker2 based "scope/trace" output.
	// the size of the data returned here corresponds 1:1 to the data in the audio buffer that was last generated.
	// the format of the data returned by getTraceStreams() is arbitrary and readFloatTrace() is used to
	// extact "normalized" float values

	enableScope(enable) {}	// use this to turn respective data production logic on/off (if necessary as a performance optimization)

	getNumberTraceStreams()
	{
		return this._scopeProvider.getNumberTraceStreams();
	}
	getTraceStreams()
	{
		return this._scopeProvider.getTraceStreams();
	}
	readFloatTrace(buffer, idx)
	{
		return this._scopeProvider.readFloatTrace(buffer, idx);
	}

	// the below default implementation assumes a tick size equal to the buffer size (must be overridden
	// in scenarions where that assumption is incorrect)

	setTicker(ticker)
	{
		this._externalTicker = ticker;
	}

	initTicker() {}
	getMaxTicks() { return 1; }		// default: backend already delivers data in tick sized chunks
	getCurrentTick() { return 0; }	// default: backend already delivers data in tick sized chunks


//////////// Optional: song "position seeking" functionality (only available in backend) ////////////

	/**
	* Time in millis that the song has been playing.
	*
	* Measures the data actually output by the backend's "producer" logic. Due to potentially different
	* buffer sizes used by the original "producer" and what is used by WebAudio, this time may slightly diverge
	* from what is returned by getCurrentPlaytime() - additional data may already have been buffered but not
	* yet sent to WebAudio (i.e. the time here may be larger).
	*/
	getPlaybackPosition() { return 0;}	// -1 = API not ready

	/**
	* Gets song length in millis.
	*
	* fixme: a song's length might be known but the backend might not have "seek" support in general.
	* mixing the two aspects here is not ideal..
	*/
	getMaxPlaybackPosition() { return 0;}	// default 0 = seeking not supported; -1 = API not ready

	/**
	* Move playback to 'ms': must be between 0 and getMaxPlaybackPosition()
	*/
	seekPlaybackPosition(ms) { }


//////////// async file loading related (see BaseFileMapper for API docs) ////////////

	mapToVirtualFilename(filename)
	{
		return this._fileMapper.mapToVirtualFilename(filename);
	}
	mapFromVirtualFilename(virtualName)
	{
		return this._fileMapper.mapFromVirtualFilename(virtualName);
	}
	registerFileData(pathFilenameArray, data)
	{
		return this._fileMapper.registerFileData(pathFilenameArray, data);
	}
	mapBackendFilename(virtualName)
	{
		return this._fileMapper.mapBackendFilename(virtualName);
	}


//////////// default base implementation ////////////

	setObserver(o)
	{
		this._observer = o;

		if (this.isAdapterReady())
		{
			this.notifyAdapterReady();	// otherwise that event never fires
		}
	}

	notifyAdapterReady()
	{
		if (typeof this._observer !== "undefined" )	this._observer.notify();
	}

	/**
	* Default initializations performed after a new track has been selected for playback.
	*/
	initPlayback(silenceTimeout)
	{
		this.setSilenceTimeout(silenceTimeout);
	}

	prepareToPlay(silenceTimeout, virtualName)
	{

		this.initPlayback(silenceTimeout);
		this.initTicker();

		this.updateSongInfo(virtualName);
	}

	/**
	* Allows to disable the file loading/caching by the ScriptNodePlayer (should be left alone in most scenarios).
	*/
	skipFileLoad()
	{
		return false;
	}

	/**
	* Callbacks that allow the backend to react to the respective player events.
	* There is usually no need to use these.
	*/
	play() {}
	pause() {}

	setOnTrackEnd(onTrackEnd)
	{
		this._onTrackEnd = onTrackEnd;
	}

	doOnTrackEnd()
	{
		if (this._onTrackEnd) {
			this._onTrackEnd();
		}
	}

	error(name)
	{
		alert("fatal error: abstract method '"+name+"' must be defined");
	}


//////////// utilities for use in subclases ////////////

	_getFilename(path, name)
	{
		if (path && path.length)
		{
			name = path + (path.endsWith("/") ? "" : "/") + name;
		}
		return name;
	}

	_makeTitleFromPath(path)
	{
		return decodeURI(path).replace(/^.*[\\\/]/, '').split('.').slice(0, -1).join('.');
	}


//////////// backward compatibility ////////////


	// @deprecated use BufferUtil directly
	remapToFloat(input, len, funcReadFloat, output)
	{
		alert("error: AudioBackendAdapterBase.remapToFloat - use BufferUtil.remapToFloat() instead");
	}
	// @deprecated use BufferUtil directly
	resampleToFloat(channels, channelId, inputPtr, len, funcReadFloat, resampleOutput, resampleLen)
	{
		alert("error: AudioBackendAdapterBase.resampleToFloat - use BufferUtil.resampleToFloat() instead");
	}
};


/*
* This utility uses the audio output produced by some EMSCRIPTEN based backend and converts it to the format
* expected by the respective WebAudio infrastructure.
*
* The transformation affects the size of the used buffers as well as the used sampleRate. The primary purpose
* is to create the respective audio outout used by WebAudio. As an optional functionality the same transformations
* can be propagated to available add-on data streams via "ticker" (see _getTicker()).
*
* As add-on functionality it allows to add stereo-panning and "silence detection" based features.
*/
class OutputTransformer {
	constructor(backendAdapter) {
		this._backend = backendAdapter;

		this._silenceStarttime = -1;
		this._silenceTimeout;

		this._currentPlaytime = 0;

		this._numberOfSamplesRendered = 0;
		this._numberOfSamplesToRender = 0;

		// audio buffer handling
		this._sourceBuffer;
		this._sourceBufferLen;
		this._sourceBufferIdx = 0;

		// buffer used if input/output sampleRate is different
		this._resampleBuffer =  new Float32Array();

		// output sample rate used by player (for correct playback this has to match the rate used by WebAudio).
		// tweaking this will make playback slower/faster
		this._sampleRate = 44100;

		// output sample rate actually produced by the backend (backend might use some hard coded sample rate and
		// not what is requested by loadMusicData() call)
		this._inputSampleRate = 44100;

		// in the ProcessorScriptNode scenario the ticker is always driven by a separate
		// node that fires in 256-samples intervals
		this._tickerStepWidth= 256;		// shortest available (i.e. tick every 256 samples)
	}


//////////// public API ////////////

	/**
	* Change the default timeout in seconds (0 means no timeout).
	*/
	setSilenceTimeout(silenceTimeout)
	{
		this._silenceTimeout = silenceTimeout;
		this._silenceStarttime = -1;	// reset silence detection
	}

	initPlayback(silenceTimeout)
	{
		this.setSilenceTimeout(silenceTimeout);

		this._currentPlaytime = 0;
	}

	resetBuffers ()
	{
		this._numberOfSamplesRendered = 0;
		this._numberOfSamplesToRender = 0;
		this._sourceBufferIdx = 0;

		this.resetTick();
	}

	resetSampleRate(sampleRate, inputSampleRate)
	{
		// This API is used to make a song play faster/slower. The UI may use this repeatedly with 
		// different speeds during the playback of a song, i.e. the sampleRate then isn't constant. Calculation
		// of _currentPlaytime has to be adjusted by the respective "multiplier".
		
		if (sampleRate > 0) { this._sampleRate = sampleRate; }
		if (inputSampleRate > 0) { this._inputSampleRate = inputSampleRate; }

		let bufSize = this._backend.getProcessorBufSize();

		let s = Math.round(bufSize * this._sampleRate / this._inputSampleRate) * this._getChannels();

		if (s > this._resampleBuffer.length) {
			this._resampleBuffer = this._allocResampleBuffer(s);
		}

		this.resetBuffers();
		
	}

	/**
	* Gets time measured in number of "played" samples.
	*/
	getPlaytime()
	{
		// measured using "correct" playback speed
		return this._currentPlaytime;
	}

	/**
	* Called via WebAudio ScriptProcessorrNode's onaudioprocess events to fetch a next batch of audio data.
	*/
	genSamples(event)
	{
		let player = ScriptNodePlayer.getInstance();

		let timeout = this._getTimeout();	// measured in samples

		let isStereoOut = event.outputBuffer.numberOfChannels > 1;
		let isStereoIn = this._getChannels() == 2;

		let outputL = event.outputBuffer.getChannelData(0);
		let outputR = isStereoOut ? event.outputBuffer.getChannelData(1) : null;

		if (player._isNotPlaying()) {
			outputL.fill(0);
			if (outputR) outputR.fill(0);
			this._resampleBuffer.fill(0);
		}
		else {
			this.beginTicker();

			let outSize = outputL.length;

			this._numberOfSamplesRendered = 0;

			while (this._numberOfSamplesRendered < outSize)
			{
				if (this._numberOfSamplesToRender === 0)
				{
					let status;
					if ((timeout > 0) && (this._currentPlaytime > timeout))
					{
						DbgUtil.trace("'song end' forced after "+ timeout/ScriptNodePlayer.getWebAudioSampleRate() +" secs");
						status = 1;
					}
					else
					{
						status = this._backend.computeAudioSamples();
					}

					if (status !== 0)
					{
						// no frame left
						this._fillEmpty(outSize, outputL, outputR);
						this._resampleBuffer.fill(0);

						if (status < 0)
						{
							// file-load: emu just discovered that we need to load another file
							player._signalFileNotReady();

							return; // complete init sequence must be repeated
						}
						if (player._isWaitingForFile())
						{
							// this state may just have been set by the backend.. try again later
							return;
						}
						else
						{
							if (status > 1)
							{
								DbgUtil.trace("playback aborted with an error");
							}

							player._setPaused();	// stop playback (or this will retrigger again and again before new song is started)
							this._doOnTrackEnd();
							return;
						}
					}
					// refresh in case backend is not using one fixed buffer..
					this._sourceBuffer = this._backend.getAudioBuffer();
					this._sourceBufferLen = this._backend.getAudioBufferLength();

					this._numberOfSamplesToRender = this._updateResampleBuffer(this._sourceBuffer, this._sourceBufferLen, this._sampleRate, this._inputSampleRate)
					this._resampleTickerData();
					this._sourceBufferIdx = 0;
				}

				if (isStereoOut)
				{
					if (isStereoIn)
						this._copyStereo2Stereo(this._resampleBuffer, outputL, outputR, outSize);
					else
						this._copyMono2Stereo(this._resampleBuffer, outputL, outputR, outSize);

					let pan = player.getPanning();
					if (pan != null)
					{
						this.applyPanning(outputL, outputR, pan + 1.0);
					}
				}
				else
				{
					if (isStereoIn)
						this._copyStereo2Mono(this._resampleBuffer, outputL, outSize);
					else
						this._copyMono2Mono(this._resampleBuffer, outputL, outSize);
				}
			}

			this._increaseCurrentPlaytime(outSize);

			this._detectSongEndSilence();
		}
	}

//////////// private methods ////////////
	/**
	* By default panning is inactive (use setPanning(x) to activate).
	*
	* Certain songs use an unfavorable L/R separation - e.g. bass on one channel - that is
	* not nice to listen to. This "panning" impl allows to "mono"-ify those songs.. (_pan=1
	* creates mono). Only used when WebAudio is creating stereo output.
	*
	* @param pan 0..2 (1 creates mono)
	*/
	applyPanning(leftChan, rightChan, pan)
	{
		let len = leftChan.length;

		pan =  pan * 256.0 / 2.0;
		let l, r, m;
		for (let i = 0; i < len; i++) {
			l = leftChan[i];
			r = rightChan[i];
			m = (r - l) * pan;

			leftChan[i] = ((l *256) + m) / 256;
			rightChan[i] = ((r *256) - m) / 256;
		}
	}

	_increaseCurrentPlaytime(val)
	{
		let playbackRate= ScriptNodePlayer.getWebAudioSampleRate();
		let multiplier = playbackRate / this._sampleRate;
		
		this._currentPlaytime += val * multiplier;
	}

	_detectSongEndSilence() {
		if ((this._silenceTimeout > 0) && (this._silenceStarttime > 0))
		{
			let silenceTime = this._currentPlaytime - this._silenceStarttime;
			let silenceTimeout = this._silenceTimeout * ScriptNodePlayer.getWebAudioSampleRate();

			if ((silenceTime >=  silenceTimeout))
			{
				player._setPaused();	// stop playback (or else this would retrigger again and again before new song is started)
				this._doOnTrackEnd();
			}
		}
	}
	_doOnTrackEnd()
	{
		this._backend.doOnTrackEnd();
	}
	_getTicker()
	{
		return this._backend._externalTicker;
	}
	_getTimeout()
	{
		return this._backend._currentTimeout;
	}
	_getChannels()
	{
		return this._backend.getChannels();
	}

	_allocResampleBuffer(s)
	{
		return new Float32Array(s);
	}

	_updateResampleBuffer(input, len, sampleRate, inputSampleRate)
	{
		let readFunc = this._backend.readFloatSample.bind(this._backend);
		let nChannels = this._getChannels();

		let resampleLen;
		if (sampleRate == inputSampleRate)
		{
			BufferUtil.remapToFloat(input, len * nChannels, readFunc, this._resampleBuffer);
			resampleLen = len;
		}
		else
		{
			resampleLen = Math.round(len * sampleRate / inputSampleRate);
			let bufSize = resampleLen * nChannels;

			if (bufSize > this._resampleBuffer.length) { this._resampleBuffer = this._allocResampleBuffer(bufSize); }

			// only mono and interleaved stereo data is currently implemented..
			BufferUtil.resampleToFloat(nChannels, 0, input, len, readFunc, this._resampleBuffer, resampleLen);
			if (nChannels == 2)
			{
				BufferUtil.resampleToFloat(nChannels, 1, input, len, readFunc, this._resampleBuffer, resampleLen);
			}
		}
		return resampleLen;
	}

	_resampleTickerData()
	{
		let ticker = this._getTicker();

		// triggers "push" of a batch of add-on data (in sync with respective audio )
		if (typeof ticker !== 'undefined')
		{
			ticker.resampleTickerData(this._sampleRate, this._inputSampleRate, this._sourceBufferLen,
										this._backend.getNumberTraceStreams(), this._backend.getTraceStreams());
		}
	}

	_fillEmpty(outSize, outputL, outputR)
	{
		let availableSpace = outSize - this._numberOfSamplesRendered;

		for (let i = 0; i < availableSpace; i++)
		{
			outputL[i+this._numberOfSamplesRendered] = 0;
			if (outputR) { outputR[i + this._numberOfSamplesRendered] = 0; }
		}
		this._numberOfSamplesToRender = 0;
		this._numberOfSamplesRendered = outSize;
	}

	_trackSilenceStart(s)
	{
		// fixme: it might be better to allow for some "jitter" and not check for an idantical level?

		// note: from a performance perspective the added calculation to create "s" is irrelevant!
		// the browers's "normal" performance fluctuations are so much larger that is imposssible
		// to measure any difference even with the logic completely removed.

		if (this.lastVuLevel != s)
		{
			this.lastVuLevel = s;
			this._silenceStarttime = this._currentPlaytime;
		}
		else
		{
			// when output stops "moving" it is probably silence
		}
	}

	_copyStereo2Stereo(resampleBuffer, outputL, outputR, outSize)
	{
		let ticker = this._getTicker();
		let player = ScriptNodePlayer.getInstance();
		let useTicker = (typeof ticker !== 'undefined') && !player.isPaused();

		let s = 0, l = 0, r =  0;

		if ((this._numberOfSamplesRendered + this._numberOfSamplesToRender) > outSize)
		{
			let availableSpace = outSize - this._numberOfSamplesRendered;

			let inputIdx = this._sourceBufferIdx>>1;

			for (let i = 0; i < availableSpace; i++) {
				let j = i + this._numberOfSamplesRendered;
				l = resampleBuffer[this._sourceBufferIdx++];
				r = resampleBuffer[this._sourceBufferIdx++];

				outputL[j] = l;
				outputR[j] = r;

				s += l + r;
			}
			s /= availableSpace;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, availableSpace);
			}

			this._numberOfSamplesToRender -= availableSpace;
			this._numberOfSamplesRendered = outSize;
		} else {
			let inputIdx = this._sourceBufferIdx>>1;

			for (let i = 0; i < this._numberOfSamplesToRender; i++) {
				let j = i + this._numberOfSamplesRendered;
				l = resampleBuffer[this._sourceBufferIdx++];
				r = resampleBuffer[this._sourceBufferIdx++];

				outputL[j] = l;
				outputR[j] = r;

				s += l + r;
			}
			s /= this._numberOfSamplesToRender;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, this._numberOfSamplesToRender);
			}

			this._numberOfSamplesRendered += this._numberOfSamplesToRender;
			this._numberOfSamplesToRender = 0;
		}
		this._trackSilenceStart(s);
	}

	_copyMono2Stereo(resampleBuffer, outputL, outputR, outSize)
	{
		let ticker = this._getTicker();
		let player = ScriptNodePlayer.getInstance();
		let useTicker = (typeof ticker !== 'undefined') && !player.isPaused();

		let s = 0, v =  0;

		if ((this._numberOfSamplesRendered + this._numberOfSamplesToRender) > outSize)
		{
			let availableSpace = outSize - this._numberOfSamplesRendered;

			let inputIdx = this._sourceBufferIdx>>1;

			for (let i = 0; i < availableSpace; i++) {
				let j = i + this._numberOfSamplesRendered;
				v = resampleBuffer[this._sourceBufferIdx++];

				outputL[j] = outputR[j] = v;

				s += v;
			}
			s /= availableSpace;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, availableSpace);
			}

			this._numberOfSamplesToRender -= availableSpace;
			this._numberOfSamplesRendered = outSize;
		} else {
			let inputIdx = this._sourceBufferIdx>>1;

			for (let i = 0; i < this._numberOfSamplesToRender; i++) {
				let j = i + this._numberOfSamplesRendered;
				v = resampleBuffer[this._sourceBufferIdx++];

				outputL[j] = outputR[j] = v;

				s += v;
			}
			s /= this._numberOfSamplesToRender;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, this._numberOfSamplesToRender);
			}

			this._numberOfSamplesRendered += this._numberOfSamplesToRender;
			this._numberOfSamplesToRender = 0;
		}
		this._trackSilenceStart(s);
	}

	_copyMono2Mono(resampleBuffer, output, outSize)
	{
		let ticker = this._getTicker();
		let player = ScriptNodePlayer.getInstance();
		let useTicker = (typeof ticker !== 'undefined') && !player.isPaused();

		let s = 0, o = 0;

		if (this._numberOfSamplesRendered + this._numberOfSamplesToRender > outSize)
		{
			let availableSpace = outSize - this._numberOfSamplesRendered;
			let inputIdx = this._sourceBufferIdx;
			for (let i = 0; i < availableSpace; i++) {
				let j = i + this._numberOfSamplesRendered;
				o = resampleBuffer[this._sourceBufferIdx++];
				output[j] = o;

				s += o;
			}
			s /= availableSpace;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, availableSpace);
			}

			this._numberOfSamplesToRender -= availableSpace;
			this._numberOfSamplesRendered = outSize;
		}
		else {
			let inputIdx = this._sourceBufferIdx;
			for (let i= 0; i < this._numberOfSamplesToRender; i++) {
				let j = i + this._numberOfSamplesRendered;
				o = resampleBuffer[this._sourceBufferIdx++];
				output[j] = o;

				s += o;
			}
			s /= this._numberOfSamplesToRender;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, this._numberOfSamplesToRender);
			}

			this._numberOfSamplesRendered += this._numberOfSamplesToRender;
			this._numberOfSamplesToRender = 0;
		}
		this._trackSilenceStart(s);
	}

	_copyStereo2Mono(resampleBuffer, output, outSize)
	{
		let ticker = this._getTicker();
		let player = ScriptNodePlayer.getInstance();
		let useTicker = (typeof ticker !== 'undefined') && !player.isPaused();

		let s = 0, o = 0;

		if (this._numberOfSamplesRendered + this._numberOfSamplesToRender > outSize)
		{
			let availableSpace = outSize - this._numberOfSamplesRendered;
			let inputIdx = this._sourceBufferIdx;
			for (let i = 0; i < availableSpace; i++) {
				let j = i + this._numberOfSamplesRendered;
				o = (resampleBuffer[this._sourceBufferIdx++]+resampleBuffer[this._sourceBufferIdx++]) * 0.5;
				output[j] = o;

				s += o;
			}
			s /= availableSpace;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, availableSpace);
			}

			this._numberOfSamplesToRender -= availableSpace;
			this._numberOfSamplesRendered = outSize;
		}
		else {
			let inputIdx = this._sourceBufferIdx;
			for (let i= 0; i < this._numberOfSamplesToRender; i++) {
				let j = i + this._numberOfSamplesRendered;
				o = (resampleBuffer[this._sourceBufferIdx++]+resampleBuffer[this._sourceBufferIdx++]) * 0.5;
				output[j] = o;

				s += o;
			}
			s /= this._numberOfSamplesToRender;

			if (useTicker)
			{
				ticker.copyTickerData(this._numberOfSamplesRendered, inputIdx, this._numberOfSamplesToRender);
			}

			this._numberOfSamplesRendered += this._numberOfSamplesToRender;
			this._numberOfSamplesToRender = 0;
		}
		this._trackSilenceStart(s);
	}

//////////// ticker related stuff ////////////

	resetTick ()
	{
		this._cntTick = 0;
		this._baseTick = null;
	}

	tick()
	{
		// this ScriptProcessorNode specific impl is based on separate ticker Node:

		// ticks occur at 256-samples intervals during actual playback - eventhough the exact
		// timing with which WebAudio triggers respective calls is undefined,
		// respective "ticks" should be more or less in sync with the main audio buffer
		// playback - and offer a much more fine grained timing measurement

		if (this._baseTick != null)
		{
			// test result: when used with a 16k buffer, then 64 ticks SHOULD occur for each buffer, but..
			// 1) 6 ticks are triggered before the first buffer is even requested.. (should be ignored)
			// 2) 15% of the time less than 64 ticks occur for a buffer.. (going as low as 61!).. respective
			//    ticks just stay lost, i.e. tick() based time tracking quickly gets out of sync with
			//    the actual audio playback.

			this._cntTick++;	// only approximative: must be re-synced with each audio buffer
		}
	}

	initTicker()
	{
		let ticker = this._getTicker();

		let bufSize = this._backend.getProcessorBufSize();

		if (typeof ticker !== 'undefined')
		{
			ticker.init(bufSize, this._tickerStepWidth, this._backend.readFloatTrace.bind(this._backend));
		}

		this._maxTicks = bufSize / this._tickerStepWidth;
		this._cntTick = 0;
		this._baseTick = null;
	}
	getMaxTicks()
	{
		return this._maxTicks;
	}

	beginTicker()
	{	// called at start of genSamples
		let ticker = this._getTicker();

		this._baseTick = this._baseTick == null ? 0 : this._baseTick + this._maxTicks;
		this._cntTick = this._baseTick;	// re-sync

		if (typeof ticker !== 'undefined')
		{
			ticker.startAudioBuffer();
		}
	}
	getCurrentTick()
	{
		// playback position measured in 'ticks' (a 256-samples block) within
		// the currently played ScriptProcessorNode audio buffer.

		return this._cntTick % this._maxTicks;
	}
	getBufNum()
	{
		return Math.floor(this._cntTick/this._maxTicks);
	}
};



/*
* Abstract 'audio backend adapter' class that is based on ScriptProcessorNode implementation.
*
* Must be subclassed for the integration of a specific backend: It adapts the APIs provided by a
* specific backend to the ones required by the player (e.g. access to raw sample data.)
*
* The adapter has built-in resampling logic so that the sampleRate required by WebAudio is provided
* even if the original producer logic uses a different sampleRate.
*/
class ScriptNodeBackendAdapter extends AudioBackendAdapterBase {
	constructor(channels, bytesPerSample, fileMapper, scopeProvider)
	{
		super(fileMapper, scopeProvider);

		this._transformer = new OutputTransformer(this);

		this._channels = channels;
		this._bytesPerSample = bytesPerSample;
		this._processorBufSize = 2048;			// use below setter to change this default

		this.setPlaybackTimeout(-1);
	}

//////////// WebAudio pipeline setup ////////////

	setProcessorBufSize(size)
	{
		this._processorBufSize = size;
	}

	getProcessorBufSize()
	{
		return this._processorBufSize;
	}

	_createProducerNode(audioCtx)
	{
		let bufSize = this.getProcessorBufSize();

		let scriptNode = audioCtx.createScriptProcessor(bufSize, 0, this.getChannels());
		scriptNode.onaudioprocess = fetchSamples;
	//	scriptNode.onaudioprocess = window.player.genSamples.bind(window.player);	// doesn't work with dumbshit Chrome GC
		return scriptNode;
	}

	_connectTickerNode(ctx, gainNode)
	{
		let tickerScriptNode;
		if (typeof this._externalTicker !== 'undefined')
		{
			// use shortest buffer length available so that onaudioprocess
			// is invoked more frequently than for the above "producer" Node..

			// this script processor does not actually produce any audible output.. it just provides a callback
			// that is synchronized with the actual music playback.. (the alternative would be to manually try and
			// keep track of the playback progress..)
			tickerScriptNode = ctx.createScriptProcessor(256, 0, 1);

			// there is an inherent imprecison to this approach since WebAudio will request the new
			// data *before* it is needed, i.e. respective ticks will likely fire somewhat too early and the
			// exact latency might vary for different audio buffer sizes
			tickerScriptNode.onaudioprocess = calcTick;

			tickerScriptNode.connect(gainNode);
		}
		return tickerScriptNode;
	}

//////////// backend life-cycle related ////////////

	_resetBuffers()
	{
		this._transformer.resetBuffers();
	}


//////////// playback related ////////////

	setPlaybackTimeout(t) 	// final: DO NOT override or its use in ctor breaks!
	{
		let correctSampleRate = ScriptNodePlayer.getWebAudioSampleRate();
		this._currentTimeout = (t < 0) ? -1 : t / 1000 * correctSampleRate;
	}

	getPlaybackTimeout()
	{
		let correctSampleRate = ScriptNodePlayer.getWebAudioSampleRate();

		return (this._currentTimeout < 0) ? -1 : Math.round(this._currentTimeout / correctSampleRate * 1000);
	}

	getCurrentPlaytime()
	{
		return this._transformer.getPlaytime() / ScriptNodePlayer.getWebAudioSampleRate();
	}

	setSilenceTimeout(silenceTimeout)
	{
		this._transformer.setSilenceTimeout(silenceTimeout);
	}

	/**
	* Return size one sample in bytes
	*/
	getBytesPerSample()
	{
		return this._bytesPerSample;
	}

	/**
	* Number of channels, i.e. 1= mono, 2= stereo
	*/
	getChannels()
	{
		return this._channels;
	}

	resetSampleRate(sampleRate, inputSampleRate)
	{
		this._transformer.resetSampleRate(sampleRate, inputSampleRate);
	}


//////////// override default base implementation ////////////

	initPlayback(silenceTimeout)
	{
		this._transformer.initPlayback(silenceTimeout);
	}
	getBufNum()
	{
		return this._transformer.getBufNum();
	}

	// ScriptProcessorNode specific "scope/trace" impl:
	setTicker(ticker)
	{
		this._externalTicker = ticker;
		this.initTicker();
	}
	initTicker()
	{
		this._transformer.initTicker();
	}
	getMaxTicks()
	{
		return this._transformer.getMaxTicks();
	}
	getCurrentTick()
	{
		return this._transformer.getCurrentTick();
	}


//////////// additional hooks used by OutputTransformer for audio generation (to be defined in subclasses)  ////////////

	/**
	* Fills the audio buffer with the next batch of samples
	* Return 0: OK, -1: temp issue - waiting for file, 1: end, 2: error
	*/
	computeAudioSamples() 				{ this.error("computeAudioSamples"); }

	/**
	* Return: pointer to memory buffer that contains the sample data
	*/
	getAudioBuffer() 					{ this.error("getAudioBuffer"); }

	/**
	* Return: length of the audio buffer in 'ticks' (e.g. mono buffer with 1 8-bit
	*         sample= 1; stereo buffer with 1 32-bit * sample for each channel also= 1)
	*/
	getAudioBufferLength() 				{ this.error("getAudioBufferLength"); }

	/**
	* Reads one audio sample from the specified position.
	* Return sample value in range: -1..1
	*/
	readFloatSample(buffer, idx) 		{ this.error("readFloatSample"); }
};



/*
* Emscripten based backends that produce 16-bit sample data.
*
* Requires certain standard APIs to be available in the backend (see "emu_" calls).
*
* NOTE: This impl adds handling for asynchronously initialized 'backends', i.e.
*       the 'backend' that is passed in, may not yet be usable (see WebAssembly based impls:
*       here a respective "onRuntimeInitialized" event will eventually originate from the 'backend').
*       The 'backend' allows to register a "adapterCallback" hook to propagate the event - which is
*       used here. The player typically observes the backend-adapter and when the adapter state changes, a
*       "notifyAdapterReady" is triggered so that the player is notified of the change.
*/
class EmsHEAP16BackendAdapter extends ScriptNodeBackendAdapter {
	constructor(backend, channels, fileMapper, scopeProvider)
	{
		super(channels, 2, fileMapper, scopeProvider);

		this.Module = backend;
	}

//////////// backend life-cycle related ////////////

	// NOTE: this method must be called at the very end of each leaf subclass' constructor! (nowhere else!)
	// It is crucial if WASM (asynchronously loaded) is used in the backend impl, to ensure that the
	// notification is triggered after the subclass constructor has completed AND the WASM is ready!
	ensureReadyNotification()
	{
		if (this.Module.notReady)
		{
			this.Module["adapterCallback"] = function() { 	// when Module is ready
				this.notifyAdapterReady();	// propagate to change to player
			}.bind(this);
		}
		else
		{
			this.notifyAdapterReady();	// in this scenario there is probably no observer yet
		}
	}

	// async Emscripten init means that adapter may not immediately be ready - see async WASM compilation
	// CAUTION: if you want to be able to use WebAssembly this must be checked in all the adapter APIs
	// that the user might call directly (e.g. getMaxPlaybackPosition(), etc) or that call will crash
	// the page whenever  WASM isn't ready yet!
	isAdapterReady()
	{
		if (typeof this.Module.notReady === "undefined") return true; // default for backward compatibility
		return !this.Module.notReady;
	}

//////////// music file load related ////////////

	teardown()
	{
		super.teardown();
		this.Module.ccall('emu_teardown', 'number');	// standard function
	}

//////////// playback related ////////////

	computeAudioSamples()
	{
		return this.Module.ccall('emu_compute_audio_samples', 'number');		// standard function
	}
	getAudioBuffer()
	{
		var ptr =  this.Module.ccall('emu_get_audio_buffer', 'number');			// standard function
		return ptr >> 1;			// make it a this.Module.HEAP16 pointer
	}
	getAudioBufferLength()
	{
		var len = this.Module.ccall('emu_get_audio_buffer_length', 'number');	// standard function
		return len;
	}
	readFloatSample(buffer, idx)
	{
		return this.Module.HEAP16[buffer+idx] / 0x8000;
	}

//////////// song "position seeking" functionality (using standard functions) ////////////

	getMaxPlaybackPosition()
	{
		if (!this.isAdapterReady()) return -1;
		return this.Module.ccall('emu_get_max_position', 'number');
	}
	getPlaybackPosition()
	{
		if (!this.isAdapterReady()) return -1;
		return this.Module.ccall('emu_get_current_position', 'number');
	}
	seekPlaybackPosition(ms)
	{
		if (!this.isAdapterReady()) return;

		// depending on the player, seeking may be a VERY slow operation:
		// suppress any audio output while reset is in progress
		// (ideally this should be done asynchronously to not block the UI)

		let p = ScriptNodePlayer.getInstance();
		if (p)
		{
			let v = p.getVolume();

			p.setVolume(0);	// "stop" does not prevent repeated playback of last buffer..
			this.Module.ccall('emu_seek_position', 'number', ['number'], [ms]);
			p.setVolume(v);

			// fixme: is a song specific timeout has been set then the playTIme should also
			// be reset to match the seek position (flaw isn't very relevant since seekable
			// songs typically provide proper song length information and timeout
			// therefore usually isn't used)
		}
		else
		{
			this.Module.ccall('emu_seek_position', 'number', ['number'], [ms]);
		}
	}

//////////// utilities ////////////
	_decodeBinaryToText(ptr, decoderLabel, len)
	{
		let buf = [];

		for (let i = 0; i < len; i++) {	// no need for longer texts here
			let t = this.Module.HEAPU8[(((ptr)+(i))>>0)];
			if (t != 0 )
			{
				buf.push(t);
			}
			else
			{
				break;
			}
		}
		let decoder = new TextDecoder(decoderLabel);
		return decoder.decode(new Uint8Array(buf));
	}

	_loadMusicDataBuffer(filename, data, preferredSampleRate, preferredOutputSize, enableScopes)
	{
		let buf = this.Module._malloc(data.length);
		this.Module.HEAPU8.set(data, buf);

		let ret = this.Module.ccall('emu_load_file', 'number',
							['string', 'number', 'number', 'number', 'number', 'number'],
							[ filename, buf, data.length, preferredSampleRate, preferredOutputSize, enableScopes]);

		this.Module._free(buf);
		return ret;
	}

	_setupOutputResampling(sampleRate)
	{
		let inputSampleRate = this.Module.ccall('emu_get_sample_rate', 'number');
		this.resetSampleRate(sampleRate, inputSampleRate);
	}
};


/*
* Emscripten based backends that produce 32-bit float sample data.
*/
class EmsHEAPF32BackendAdapter extends EmsHEAP16BackendAdapter {
	constructor(backend, channels, fileMapper, scopeProvider)
	{
		super(backend, channels, fileMapper, scopeProvider);
		this._bytesPerSample = 4;
	}

//////////// playback related ////////////
	getAudioBuffer()
	{
		return super.getAudioBuffer() >> 1;			// make it a this.Module.HEAP32 pointer
	}

	readFloatSample(buffer, idx)
	{
		return (this.Module.HEAPF32[buffer+idx]);
	}
};

/*
* Emscripten based backends that produce 32-bit integer sample data.
*/
class EmsHEAP32BackendAdapter extends EmsHEAPF32BackendAdapter {
	constructor(backend, channels, fileMapper, scopeProvider)
	{
		super(backend, channels, fileMapper, scopeProvider);
	}

//////////// playback related ////////////

	readFloatSample(buffer, idx)
	{
		return (this.Module.HEAP32[buffer+idx]);
	}
};

/**
* Cache used to track progress of file loading activities.
*
* Provides a synchronous API to access files and controls when new asynchronous 
* XHR requests are made.
*
* fixme: the unlimited caching of files should be restricted: currently all loaded 
* song data stays in memory for as long as the page is opened (this not only affects the
* cache here but also EMSCRIPTEN's virtual filesystem where the same data is also cached).
* For old chiptunes the files are negligibly small and the caching doesn't matter, but
* more recent formats reach the size of mp3 files and unnecessarily caching those may 
* be a significant burden on the brower's memory consumption. The browser is probably 
* already cache these loaded files anyway and it might be a good strategy to just 
* flush the extra caches completely before a new song is loaded.
*/
class FileCache {
	constructor() 
	{
		this._binaryFileMap = {};	// cache for loaded "file" binaries
		this._pendingFileMap = {};

		this._isWaitingForFile = false;	// signals that some file loading is still in progress
	}
	
	getFileMap()
	{
		return this._binaryFileMap;
	}
	
	getPendingMap()
	{
		return this._pendingFileMap;
	}
	
	setWaitingForFile(isWaiting)
	{
		this._isWaitingForFile = isWaiting;
	}
	
	isWaitingForFile()
	{
		return this._isWaitingForFile;
	}
	
	getFile(filename)
	{
		let data;
		if (filename in this._binaryFileMap)
		{
			data = this._binaryFileMap[filename];
		}
		return data;
	}

	setFile(filename, data)
	{
		this._binaryFileMap[filename] = data;
		this._isWaitingForFile = false;
	}
};


/**
* Generic WebAudio music player (This is the end user API in this file.).
*
* The player uses a plugin approach where specific "audio producer" implementations have to be
* provived as separate "backendAdapter" (see AudioBackendAdapterBase) classes. Respective
* backends are passed to the player upon construction.
*
* This player is used as a singleton (see getInstance()), i.e. instanciation of a new player
* destroys the previous one (see createInstance()).
*
*
* The main responsibilities of the player are: The setup of a simple WebAudio Node
* pipeline and to provide basic file input facilities to deal with the problems of
* asynchonous file loading.
*
*
* What about "asynchonous file loading"?
*
* Many existing audio generation libs expect to load additional files for their operation. But respective
* synchronous file loading is a problem when that code is expected to run in a Web browser.
*
* There is no blocking file-load available to JavaScript on a web page. So unless some
* virtual filesystem is built-up beforehand (containing every file that the backend could possibly ever
* try to load) the backend code is stuck with an asynchronous file loading scheme, and the original
* backend code must be changed to a model that deals with browser's "file is not yet ready" response.
*
* This player offers a trial & error approach to deal with asynchronous file-loading. The backend code
* is expected (i.e. it must be adapted accordingly) to attempt a file-load call (which is handled by
* an async web request linked to some sort of result cache). If the requested data isn't cached yet,
* then the backend code is expected to fail but return a corresponding error status back to the
* player (i.e. the player then knows that the code failed because some file wasn't available yet - and
* as soon as the file-load is completed it retries the whole initialization sequence).
*  (see "_fileRequestCallback()" for more info).
*
*
* As is still reflected in its legacy name this player was originally (back in ~2012) designed for the
* use of ScriptProcessorNode based audio generation. Much of the implementation logic originally
* located here has meanwhile been moved to the abstract ScriptNodeBackendAdapter base class. The APIs should
* have preserved their backward compatibility with older versions - which accounts for some ugly APIs that
* would look different if redone on a green field today. (Though the code has been "reshuffled" a bit
* the used logic is pretty much identical to the one used in older versions of the player.)
*
* The updated APIs allow to now also use AudioWorkletNode based implementations. However this is still
* in an early experimental stage: Features like "load on demand" from the AudioWorkletProcessor (resampling,
* panning, etc) have not been implemented yet and more changes are to be expected if these ever were to be added.
* (The current version of this file is considered to be a work in progress. Unfortunately it seems that
* the whole AudioWorkletNode infrastructure is still in the green bananas stage today in 2023 (contrary
* to what its proponents might claim..) so there is really no rush to use the "moderen" infrastructure
* (see https://jwothke.wordpress.com/2023/10/12/why-you-should-not-use-audioworklet/ ).
*
*
* It is one of the more annoying limitations of the old style JavaScript still used here, that internal
* implementation details cannot be properly encapsulated (i.e. hidden from external exposure). And the
* PlayerImpl instance currently exposed via getInstance() doesn't make any distiction between what is
* the intended public API and what are "private" implementation details. The original idea had been to
* not expose "getInstance()" at all and expose all the "public API" methods instead at that level. However
* that would habe meant copy/pasting all those methods (with respective added forwarding code). But
* that seems to be a lot of wasted effort since in a few years most of the used browsers should support
* the ES6 "class" and "private" features and at that point I will migrate the code to using those
* features anyway - so for now "_" name prefixes will have to do.
*/

var ScriptNodePlayer = (function () {
	PlayerImpl = function(backendAdapter, requiredFiles, spectrumEnabled, onPlayerReady, onTrackReadyToPlay, onTrackEnd, externalTicker) {

		if(typeof backendAdapter === 'undefined')		{ alert("fatal error: backendAdapter not specified"); }

		if(typeof onPlayerReady === 'undefined')		{ alert("fatal error: onPlayerReady not specified"); }

		// when using new Promise based APIs, these will be set later
		if(typeof onTrackReadyToPlay === 'undefined')	onTrackReadyToPlay = function(){};
		if(typeof onTrackEnd === 'undefined')			onTrackEnd = function(){};

		this._isPlayerReady = false;
		this._producerNode = null;

		backendAdapter._assertSyncNodeReadiness(function() {
			this._ctor(backendAdapter, undefined, requiredFiles, spectrumEnabled, onPlayerReady, onTrackReadyToPlay, onTrackEnd, undefined, externalTicker);
		}.bind(this))
	};

	PlayerImpl.prototype = {

//////////// player life-cycle related ////////////

		/**
		* Due to the additional async garbage introduced by AudioWorkletNode based infrastructure the original
		* PlayerImpl constructor code can no longer be used synchronously.
		* In order to preserve backward compatibility of the existing APIa, use of the constructor is delayed to
		* AFTER the bloody Node impl is actually ready (luckily the callbacks already in place allow for this approach).
		*/
		_ctor: function(backendAdapter, UNUSED_basePath, requiredFiles, spectrumEnabled, onPlayerReady, onTrackReadyToPlay, onTrackEnd, unused1, externalTicker)
		{
			if (backendAdapter.getChannels() > 2) { alert("fatal error: only 1 or 2 output channels supported"); }
			this._backendAdapter = backendAdapter;

			this._spectrumEnabled = typeof spectrumEnabled == 'undefined' ? false : spectrumEnabled;
			
			// hooks that allow to react to specific events
			this._onTrackReadyToPlay = onTrackReadyToPlay;
			this._backendAdapter.setOnTrackEnd(onTrackEnd);

			this._onPlayerReady = onPlayerReady;
			this._onProgress = null;

			// "external ticker" allows to sync separately maintained data with the actual audio playback
			this._externalTicker = externalTicker;
			this._backendAdapter.setTicker(this._externalTicker);

			if (!this._isAutoPlayCripple())
			{
				ScriptNodePlayer._setGlobalWebAudioCtx();

				// fixme: this._sampleRate primarily serves as a "flag" to check if the initialization has been done (see "undefined" checks)
				//        replace with a proper flag and use sampleRate from ctx directly
				this._sampleRate = window._gPlayerAudioCtx.sampleRate;
				this._backendAdapter.resetSampleRate(this._sampleRate, -1);
			}
				// general WebAudio stuff
			this._bufferSource;
			this._gainNode;
			this._analyzerNode;
			this._producerNode;
			this._freqByteData = 0;

			this._pan = null;	// default: inactive

			// player status stuff

			this._isPaused = false;					// 'end' of a song also triggers this state
			this._silenceTimeout = 5;

			// setup asyc completion of initialization
			this._initInProgress = false;
			this._isSongReady = false;		// load of the song file (including dependencies) completed;
											// after track selection it should be ready to play
											// fixme; overlap with _initInProgress and _isPaused! cleanup respective state model

			this._preLoadReady = false;

			let f= this['_preloadFiles'].bind(this);
			f(requiredFiles, function() {
				this._preLoadReady = true;
				if (this._preLoadReady && this._backendAdapter.isAdapterReady())
				{
					this._isPlayerReady = true;
					window.player = this;			// must be set before below callback
					this._onPlayerReady();
				}
			}.bind(this));

			// start observing after the constructor is done
			this._backendAdapter.setObserver(this);
		},

		/**
		* @deprecated Was originally used to check if player wss ready for use (i.e. initialization completed).
		*
		* With the changed getInstance() API semantics this now corresponds to a NULL check on the 
		* respective getInstance() result. This function here no langer makes sense since it will cause an
		* Exception while the instance is not available and always return true if it is available.
		*/
		isReady: function()
		{
			return this._isPlayerReady;
		},

		notify: function() // used to handle asynchronously initialized backend impls
		{
			if ((typeof this.deferredPreload !== "undefined") && this._backendAdapter.isAdapterReady()) {
				// now that the runtime is ready the "_preload" can be started
				let files = this.deferredPreload[0];
				let onCompletionHandler = this.deferredPreload[1];
				delete this.deferredPreload;

				this._preload(files, files.length, onCompletionHandler);
			}

			if (!this._isPlayerReady && this._preLoadReady && this._backendAdapter.isAdapterReady()) {
				this._isPlayerReady = true;
				window.player = this;			// must be set before below callback
				this._onPlayerReady();
			}
		},
		// reminder: the below 2 functions are irrelevant for normal use of the player.
		// they were added as a hack so that <audio> based playback can be hacked in..
		// see Michael Rupp's "html_audio" and "stream" backends...
		notifySongEnd: function()
		{
			this._isPaused = true;

			this._backendAdapter.doOnTrackEnd();
		},
		notifyError: function(error)
		{
			if (this.lastOnFail) {
				this.lastOnFail(error);
			}
		},

		_isAppleShit: function()
		{
			return !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
		},
		_isAutoPlayCripple: function() 	// fixme: meanwhile all browsers have been similarly crippled so this probably has to be adjusted
		{
			return window.chrome || this._isAppleShit();
		},
		_initByUserGesture: function()
		{
			// try to setup as much as possible while it is "directly triggered"
			// by "user gesture" (i.e. here).. seems POS iOS does not correctly
			// recognize any async-indirections started from here.. bloody Apple idiots
			if (typeof this._sampleRate == 'undefined')
			{
				this._sampleRate = window._gPlayerAudioCtx.sampleRate;
				this._backendAdapter.resetSampleRate(this._sampleRate, -1);
			}

			if (typeof this._bufferSource != 'undefined')
			{
				try {
					this._bufferSource.stop(0);
				} catch(err) {}	// ignore for the benefit of Safari(OS X)
			}
			else
			{
				let ctx = window._gPlayerAudioCtx;

				if (this._isAppleShit()) this._iOSHack(ctx);

				this._analyzerNode = ctx.createAnalyser();

				// note: in Worklet scenario the async processor loading must
				// have been completed previously for this sync code to work! (see _assertSyncNodeReadiness())
				this._producerNode = this._backendAdapter._createProducerNode(ctx);

				this._gainNode = ctx.createGain();

				this._producerNode.connect(this._gainNode);

				this._tickerNode = this._backendAdapter._connectTickerNode(ctx, this._gainNode); // optional add-on

				// note: "panning" experiments using StereoPanner, ChannelSplitter / ChannelMerger
				// led to bloody useless results: rather implement respective "panning"
				// logic directly to get the exact effect that is needed here..

				if (this._spectrumEnabled)
				{
					this._gainNode.connect(this._analyzerNode);
					this._analyzerNode.connect(ctx.destination);
				}
				else
				{
					this._gainNode.connect(ctx.destination);
				}

				this._bufferSource = this._createBufferSource(ctx);
			}
		},

		_createBufferSource: function(ctx)
		{
			let bs = ctx.createBufferSource();
			if (!bs.start)
			{
				bs.start = bs.noteOn;
				bs.stop = bs.noteOff;
			}
			return bs;
		},


//////////// playback related ////////////

		/**
		* Sets the sample rate used for the audio output generation.
		*
		* Since this does not change the sample rate actually used by WebAudio,
		* using something different from the "correct" rate here will make the
		* playback faster/slower.
		*/
		resetSampleRate: function(sampleRate)
		{
			if (sampleRate > 0) { this._sampleRate = sampleRate; }

			this._backendAdapter.resetSampleRate(sampleRate, -1);
		},
		/**
		* Changes the default 5sec timeout  (0 means no timeout).
		*/
		setSilenceTimeout: function(silenceTimeout)
		{
			// usecase: user may temporarily turn off output (see DeepSID) and player should not end song
			this._silenceTimeout = silenceTimeout;

			if ((typeof this._backendAdapter !== 'undefined') && this._backendAdapter.isAdapterReady())
			{
				this._backendAdapter.setSilenceTimeout(silenceTimeout);
			}
			else {} // new setting will be used for the next song
		},

		/**
		* Starts audio playback.
		*/
		play: function()
		{
			this._isPaused = false;

			// this function isn't invoked directly from some "user gesture" (but
			// indirectly from "onload" handler) so it might not work on braindead iOS shit
			try { this._bufferSource.start(0); } catch(ignore) {}

			this._backendAdapter.play();
		},

		/**
		* Pauses audio playback.
		*/
		pause: function()
		{
			if ((!this._isWaitingForFile()) && (!this._initInProgress) && this._isSongReady)
			{
				this._isPaused = true;
			}
			this._backendAdapter.pause();
		},

		/**
		* Resumes audio playback.
		*
		* fixme/cleanup: play vs resume
		*/
		resume: function()
		{
			if ((!this._isWaitingForFile()) && (!this._initInProgress) && this._isSongReady)
			{
				this.play();
			}
		},

		_setPaused: function()	// do not use outside of this file
		{
			this._isPaused = true;
		},
		isPaused: function()
		{
			return this._isPaused;
		},

		/**
		* Gets the number/index of the currently playing audio buffer.
		*/
		getBufNum: function()
		{
			return this._backendAdapter.getBufNum();
		},

		/**
		* Sets the playback volume (input between 0 and 1).
		*/
		setVolume: function(value)
		{
			if (typeof this._gainNode != 'undefined')
			{
				this._gainNode.gain.value= value;
			}
		},

		getVolume: function()
		{
			if (typeof this._gainNode != 'undefined')
			{
				return this._gainNode.gain.value;
			}
			return -1;
		},

		/**
		* Sets stereo panning.
		*
		* @value null=inactive; or range; -1 to 1 (-1 is original stereo, 0 creates "mono", 1 is inverted stereo)
		*/
		setPanning: function(value)
		{
			this._pan = value;
		},
		getPanning: function()
		{
			return this._pan;
		},

		/**
		* Sets manually defined playback time to use until 'end' of a track (only affects the
		* currently selected track).
		*
		* @param t time in millis
		*/
		setPlaybackTimeout: function(t)
		{
			this._backendAdapter.setPlaybackTimeout(t);
		},
		/*
		* Gets the manually set playback time (if any).
		*/
		getPlaybackTimeout: function()
		{
			return this._backendAdapter.getPlaybackTimeout();
		},


//////////// Optional: "scope/trace" output ////////////

		/**
		* Gets the index of the 'tick' that is currently playing.
		*
		* Allows to sync separately stored data with the audio playback.
		* note: requires use of a Ticker!
		*/
		getCurrentTick: function()
		{
			return this._backendAdapter.getCurrentTick();
		},

		getMaxTicks: function()
		{
			return this._backendAdapter.getMaxTicks();
		},


		/**
		* Get backend specific song infos like 'author', 'name', etc.
		*/
		getSongInfo: function()
		{
			return this._backendAdapter.getSongInfo();
		},

		/**
		* Get meta info about backend specific song infos, e.g. what attributes are available and what type are they.
		*/
		getSongInfoMeta: function()
		{
			return this._backendAdapter.getSongInfoMeta();
		},

//////////// access to frequency spectrum data (if enabled upon construction) ////////////

		getFreqByteData: function()
		{
			if (this._analyzerNode)
			{
				if (this._freqByteData === 0)
				{
					this._freqByteData = new Uint8Array(this._analyzerNode.frequencyBinCount);
				}
				this._analyzerNode.getByteFrequencyData(this._freqByteData);
			}
			return this._freqByteData;
		},

//////////// song "position seek" related (if available with used backend) ////////////

		/**
		* @return default 0 seeking not supported
		*/
		getMaxPlaybackPosition: function() { return this._backendAdapter.getMaxPlaybackPosition(); },

		/**
		* Time in millis that the song has been playing.
		* @return default 0
		*/
		getPlaybackPosition: function() { return this._backendAdapter.getPlaybackPosition(); },

		/**
		* Time in seconds that the song has been playing.
		*/
		getCurrentPlaytime: function() { return this._backendAdapter.getCurrentPlaytime(); },

		/**
		* Move playback to 'ms': must be between 0 and getMaxSeekPosition()
		* Return: 0 if successful
		*/
		seekPlaybackPosition: function(ms) { return this._backendAdapter.seekPlaybackPosition(ms); },


//////////// file loading related ////////////

	// note: the below file loading related code was designed for the ScriptProcessorNode context were
	// respective data and state changes are synchronously handled within the UI thread. The respective
	// logic is currently unsuitable for the AudioWorklet scenario where additional changes would be needed
	// to bridge the additional asynchronous Node/Processor barrier. Given the rather insignificant
	// benefits and ridiculous limitations of the AudioWorklet infrastructure it is probably not
	// worth the effort to ever go down that road (unless those morons decide to ditch the
	// ScriptProcessorNode API completely)!

		/**
		* Hack used for Worker - see asyncSetFileData below.
		*
		* usecases: NEZplug++, IXS
		*/
		getCached: function(virtualName)
		{
			let data = this._getCache().getFile(virtualName);
			return (typeof data == 'undefined') ? null : data;
		},

		/**
		* Allows to create file data from the backend side, triggering the same name 
		* transformations that are used during loading.
		* testcase: UADE
		*
		* @param virtualNameBackend "virtual" name used on the backend side
		*/
		setFileData: function(virtualNameBackend, data) // data must be Uint8Array   
		{
			let virtualName = this._backendAdapter.mapBackendFilename(virtualNameBackend);				
			
			let pfn = this._getPathAndFilename(virtualName);
			let fileHandle = this._backendAdapter.registerFileData(pfn, data);

			if (typeof fileHandle === 'undefined' )
			{
				return false;
			}
			else
			{
				this._getCache().setFile(virtualName, data);
				return true;
			}
		},

		/**
		* Transforms input filename into pathFilenaneArray param passed to registerFileData().
		*
		* @return array with 2 elements: 0: basePath (backend specific - most don't need one),
		*        1: filename (incl. the remainder of the path)
		*/
		_getPathAndFilename: function(virtualName)
		{
			let sp = virtualName.split('/');
			let fn = sp[sp.length-1];
			let path = virtualName.substring(0, virtualName.lastIndexOf("/"));

			return [path, fn];
		},

		/**
		* Allows to directly feed file data for files that are not loaded via XHR requests.
		*
		* This is a hack to support other asynchronous "sources". todo: generalize basic player
		* design to better support Worker based impls
		*
		* testcase: IXS
		*/
		asyncSetFileData: function(virtualNameBackend, data) // data must be Uint8Array
		{
			this._fileReadyNotify = virtualNameBackend;

			this.setFileData(virtualNameBackend, data);

			this._isSongReady = false;
			this._setWaitingForFile(false);
			this._initIfNeeded(this.lastUsedFilename, this.lastUsedData, this.lastUsedOptions);

			this.lastOnCompletion(virtualNameBackend);
		},

		_isFileNotFound: function(url, xhr)
		{
			if ((xhr.readyState == 4) && (xhr.status >= 400))
			{
				return true;
			}
			else
			{
				// issue: some webservers return a "410 Gone" page with a 200 status
				// rather than using a proper 410 error status
				try {
					let s = new TextDecoder().decode(new Int8Array(xhr.response).subarray(0, 6));
					if (s.toLowerCase() == "<html>")
					{
						console.log("GET " + url + " (Not found)");
						return true;
					}
				} catch(e) {
				}
			}
			return false;
		},

		/**
		* Loads from an URL.
		*
		* note: the onCompletion callback is rather useless and in most cases _onTrackReadyToPlay
		*       is what the UI is interested in. (see "new" Promise based methods for leaner
		*       APIs)
		*/
		loadMusicFromURL: function(url, options, onCompletion, onFail, onProgress, onTrackReadyToPlay)
		{
			if (typeof options == 'undefined') options = {};
			if (typeof onCompletion == 'undefined') onCompletion = function() {};
			if (typeof onFail == 'undefined') onFail = function() {};
			if (typeof onProgress == 'undefined') onProgress = function() {};

			this._onProgress = onProgress;

			if (typeof onTrackReadyToPlay != 'undefined') this._onTrackReadyToPlay = onTrackReadyToPlay

			this._isPaused = true;
			this._initByUserGesture();	// cannot be done from the callbacks below.. see iOS shit
			
			this._backendAdapter.init(url);
			
			let virtualName = this._backendAdapter.mapToVirtualFilename(url);

			this._fileReadyNotify= "";

			if (this._backendAdapter.skipFileLoad())
			{
				if(!this._prepareTrackForPlayback(virtualName, null, options, onCompletion, onFail))
				{
					onFail();
				}
				else
				{
					onCompletion(virtualName);
				}
			}
			else {
				if (this._loadMusicDataFromCache(virtualName, options, onCompletion, onFail)) { return; }

				// WebAudio glitches badly during load and it repeats the last buffer without even
				// trying to fetch a new buffer.. the most simple "solution" seems to temporarily mute the GainNode

				let origVol = this.getVolume();
				if (!origVol) delete origVol; 	// ignore if already muted

				this.setVolume(0);

				let xhr = new XMLHttpRequest();
				xhr.open("GET", url, true);
				xhr.responseType = "arraybuffer";

				xhr.onload = function (oEvent) {
						// seems the idiots changed the behavior of their garbage browsers.. now 404 errors also
						// seem to come here (having thrown an exception earlier due to some attempted responseText access)
						if (this._isFileNotFound(url, xhr))
						{
							DbgUtil.trace("_isFileNotFound: "+ virtualName);

							this._getCache().setFile(virtualName, 0);

							if (typeof origVol != 'undefined') this.setVolume(origVol);

							onFail();
						}
						else
						{
							DbgUtil.trace("loadMusicFromURL successfully loaded: "+ virtualName);

							if(!this._prepareTrackForPlayback(virtualName, xhr.response, options, onCompletion, onFail))
							{
								if (!this._isWaitingForFile())
								{
									onFail();
								}
							}
							else
							{
								onCompletion(virtualName);
							}
						}
						this.setVolume(origVol);
				}.bind(this);

				xhr.onprogress = function (oEvent) {
					this._onProgress(oEvent.total, oEvent.loaded, oEvent);
				}.bind(this);

				xhr.onerror = function(error) {
					// this is still triggered for things like "CORS policy" errors
					// (testcase: url=https://developer.mozilla.org/ )

					onFail(error);

					this.setVolume(origVol);
				}.bind(this);

				xhr.send(null);
			}
		},

		_readFile: function(file) {
			let filename = (typeof file.xname != 'undefined') ? file.xname : file.name;
			let virtualName = this._backendAdapter.mapToVirtualFilename(filename);

			let regDataFunc = function(virtualName, arrayBuffer) {
					let pfn = this._getPathAndFilename(virtualName);
					let data = new Uint8Array(arrayBuffer);

					this._getCache().setFile(virtualName, data);

					return this._backendAdapter.registerFileData(pfn, data);
				}.bind(this);

			return new Promise((resolve, reject) => {

				if (typeof file.fileBuffer == 'undefined')
				{
					let reader = new FileReader();
					reader.onload = () => {
						let fileHandle = regDataFunc(virtualName, reader.result);
						(typeof fileHandle === 'undefined' ) ? reject() : resolve(filename);
					};
					reader.onerror = reject;
					reader.readAsArrayBuffer(file);
				}
				else
				{
					let fileHandle = regDataFunc(virtualName, file.fileBuffer);
					(typeof fileHandle === 'undefined' ) ? reject() : resolve(filename);
				}
			});
		},

		_loadFileData: function(files) {
			return new Promise((resolve, reject) => {
				let pList = [];

				files.forEach((file) => {
					pList.push(this._readFile(file));
				});

				Promise.all(pList).then((values) => {
					resolve(values);
				});
			});
		},


//////////// private/friends only methods ////////////

		/**
		* Load music data and prepare to play a specific track.
		*/
		_prepareTrackForPlayback: function (virtualName, data, options, onCompletion, onFail)
		{
			// hack: so we get back at the options during retry attempts
			this.lastUsedFilename = virtualName;
			this.lastUsedData = data;
			this.lastUsedOptions = options;
			this.lastOnCompletion = onCompletion;
			this.lastOnFail = onFail;

			this._isSongReady = false;
			this._setWaitingForFile(false);

			return this._initIfNeeded(virtualName, data, options);
		},

		// testcase: sc68 replay loading
		setWait: function(isWaiting)
		{
			this._setWaitingForFile(isWaiting);
		},

		// @deprecated use ScriptNodePlayer.getWebAudioSampleRate()
		getDefaultSampleRate: function()
		{
			alert("error: getDefaultSampleRate() must be replaced with ScriptNodePlayer.getWebAudioSampleRate()");
		},
		// @deprecated use ScriptNodePlayer.getWebAudioContext() instead
		getAudioContext: function()
		{
			alert("error: getAudioContext() must be replaced with ScriptNodePlayer.getWebAudioContext()");
		},

		_initIfNeeded: function (virtualName, data, options)
		{
			let status = this._loadMusicData(virtualName, data, options);
			if (status < 0)
			{
				this._isSongReady = false;
				this._setWaitingForFile(true);
				this._initInProgress = false;

			}
			else if (status === 0)
			{
				this._setWaitingForFile(false);
				this._isSongReady = true;
				this._initInProgress = false;

				DbgUtil.trace("successfully completed init");

				// in scenarios where a synchronous file-load is involved this first call will typically fail
				// but trigger the file load
				let ret = this._backendAdapter.evalTrackOptions(options);
				if (ret !== 0)
				{
					DbgUtil.trace("error preparing track options");
					return false;
				}
				this._backendAdapter.prepareToPlay( this._silenceTimeout, virtualName);


				// fixme: below legacy code isn't properly aligned with added "skipFileLoad" scenario!
				if ((this.lastUsedFilename == virtualName))
				{
					if (this._fileReadyNotify == virtualName) {
						// duplicate we already notified about.. probably some retry due to missing load-on-demand files
						this.play();	// user had already expressed his wish to play
					}
					else
					{
						this._onTrackReadyToPlay(virtualName);
					}
					this._fileReadyNotify = virtualName;
				}

				this._isPaused = false;	// fixme: ugly autoplay
				return true;
			}
			else
			{
				this._initInProgress = false;
				// error that cannot be resolved.. (e.g. file not exists)
				DbgUtil.trace("_initIfNeeded - fatal error");
			}
			return false;
		},

		_loadMusicDataFromCache: function(virtualName, options, onCompletion, onFail)
		{
//			this._isPaused = true;	// already done in caller

			var data = this._getCache().getFile(virtualName);

			if (typeof data != 'undefined')
			{
				DbgUtil.trace("_loadMusicDataFromCache found cached file using name: "+ virtualName);

				if (data == 0)
				{
					// file doesn't exist
					onFail();
					return true;
				}
				else
				{
					if(!this._prepareTrackForPlayback(virtualName, data, options, onCompletion, onFail))
					{
						if (!this._isWaitingForFile())
						{
							onFail();
						}
						else {}
					}
					return true;
				}
			}
			else
			{
				DbgUtil.trace("_loadMusicDataFromCache FAILED to find cached file using name: "+ virtualName);
			}
			return false;
		},

		_iOSHack: function(ctx)
		{
			try {
				let source = this._createBufferSource(ctx);

				source.buffer = ctx.createBuffer(1, 1, 22050);	// empty buffer
				source.connect(ctx.destination);

				source.start(0);

			} catch (ignore) {}
		},

		_loadMusicData: function(virtualName, arrayBuffer, options)
		{			
			this._backendAdapter.teardown();

			let data = null;
			let pfn = this._getPathAndFilename(virtualName);

			if (arrayBuffer)
			{
				data = new Uint8Array(arrayBuffer);
				this._backendAdapter.registerFileData(pfn, data);	// in case the backend "needs" to retrieve the file by name

				this._getCache().setFile(virtualName, data);
			}
			else
			{
				// special scenario: backend can disable regular handling and "take over control"
				// this function is then called without an arrayBuffer (all the regular backend
				// calls will be made but without passing any arrayBuffer)
			}


			let ret = this._backendAdapter.loadMusicData(this._sampleRate, pfn[0], pfn[1], data, options);
			if (ret === 0)
			{
				this._backendAdapter._resetBuffers();
			}
			return ret;

		},

		_preload: function(files, id, onCompletionHandler)
		{
			if (id === 0) {
				// we are done preloading
				onCompletionHandler();
			}
			else
			{
				id--;
				let funcCompleted= function() {this._preload(files, id, onCompletionHandler);}.bind(this); // trigger next load
				
				let virtualName = this._backendAdapter.mapToVirtualFilename(files[id]);		
				this._preloadFile(virtualName, funcCompleted, true);
			}
		},
		_preloadFile: function(virtualName, onLoadedHandler, notifyOnCached)
		{			
			// note: function is used for "_preload" and for "backend callback" loading... return values
			// are only used for the later

			let data = this._getCache().getFile(virtualName);

			if (typeof data != 'undefined')
			{
				let retVal = 0;
				// the respective file has already been setup
				if (data == 0)
				{
					retVal = 1;
					DbgUtil.trace("error: _preloadFile could not get cached: " + virtualName);
				}
				else
				{
					DbgUtil.trace("_preloadFile found cached file using name: " + virtualName);

					// but in cases were alias names as used for the same file (see modland shit)
					// the file may NOT yet have been registered in the FS
					// setup data in our virtual FS (the next access should then be OK)
					let pfn = this._getPathAndFilename(virtualName);
					let f = this._backendAdapter.registerFileData(pfn, data);
				}
				if (notifyOnCached)
				{
					onLoadedHandler();	// trigger next in chain	  needed for _preload / but hurts "backend callback"
				}
				return retVal;
			}
			else
			{
				DbgUtil.trace("_preloadFile FAILED to find cached file using name: " + virtualName);
			}

			// backend will be stuck without this file and we better make
			// sure to not use it before it has been properly reinitialized
			this._isPaused = true;
			this._setWaitingForFile(true);
			this._isSongReady = false;

			// requested data not available.. we better load it for next time
			if (!(virtualName in this._getCache().getPendingMap())) 	// avoid duplicate loading
			{
				this._getCache().getPendingMap()[virtualName] = 1;
				
				let url = this._backendAdapter.mapFromVirtualFilename(virtualName);

				let xhr = new XMLHttpRequest();
				xhr.open("GET", url, true);
				xhr.responseType = "arraybuffer";

				xhr.onload = function (oEvent) {
					if (this._isFileNotFound(url, xhr))
					{
						// it seems that nowadays browsers report 404 here - while no longer calling
						// any of the below callbacks..

						this._getCache().setFile(virtualName, 0);
					}
					else
					{
						let arrayBuffer = xhr.response;
						if (arrayBuffer)
						{
							DbgUtil.trace("_preloadFile successfully loaded: " + virtualName);

							// setup data in our virtual FS (the next access should then be OK)
							let pfn = this._getPathAndFilename(virtualName);
							let data = new Uint8Array(arrayBuffer);
							let f = this._backendAdapter.registerFileData(pfn, data);

							DbgUtil.trace("_preloadFile cached file using name: " + virtualName);
							this._getCache().setFile(virtualName, data);
						}
					}

					if(!delete this._getCache().getPendingMap()[virtualName])
					{
						DbgUtil.trace("remove file from pending failed: " + virtualName);
					}
					onLoadedHandler();
				}.bind(this);

				xhr.onreadystatechange = function (oEvent) {
				  if ((xhr.readyState == 4) && (xhr.status >= 400))
				  {
					DbgUtil.trace("_preloadFile failed to load: " + virtualName);
					this._getCache().setFile(virtualName, 0);
				  }
				}.bind(this);

				xhr.onprogress = function (oEvent) {
					// the add-on "sample libs" loaded for songs of the various PSF formats
					// are the largest files loaded and there better be "progress" feedback for those..

					if (this._onProgress) this._onProgress(oEvent.total, oEvent.loaded);
				}.bind(this);

				xhr.onerror = function (oEvent) {
					// what should be a 404 on modland server is reported as
					// "has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is
					// present on the requested resource." and the only place to catch that garbage seems
					// to be here:

					this._getCache().setFile(virtualName, 0);

					if(!delete this._getCache().getPendingMap()[virtualName])
					{
						DbgUtil.trace("remove file from pending failed: " + virtualName);
					}
					onLoadedHandler();
				}.bind(this);

				xhr.send(null);
			}
			return -1;
		},

		// Avoid the async trial&error loading (if available) for those files that
		// we already know we'll be needing
		_preloadFiles: function(files, onCompletionHandler)
		{
			if (typeof files == 'undefined') files = [];

			this._isPaused = true;

			if (this._backendAdapter.isAdapterReady())
			{
				// sync scenario: runtime is ready
				this._preload(files, files.length, onCompletionHandler);
			}
			else
			{
				// async scenario:  runtime is NOT ready (e.g. emscripten WASM)
				this["deferredPreload"] = [files, onCompletionHandler];
			}
		},

		_signalFileNotReady: function()
		{
			this._isPaused = true;
			this._isSongReady = false; 		// previous init is invalid
			this._setWaitingForFile(true);
		},
		_isNotPlaying: function()
		{
			// redundant; while !_isSongReady, this._isPaused should always be set   fixme: cleanup state model
			return (!this._isSongReady) || this._isWaitingForFile() || this._isPaused;
		},
		_setWaitingForFile: function(val)
		{
			this._getCache().setWaitingForFile(val);
		},
		_isWaitingForFile: function()
		{
			return this._getCache().isWaitingForFile();
		},
		_getCache: function()
		{
			if(typeof window._fileCache == 'undefined')
			{
				window._fileCache = new FileCache();
			}
			return window._fileCache;
		},


//////////// callbacks triggered by backend logic ////////////

		// these functions are meant to be called "directly" from the C++ side: examples for how this is
		// done can be found in webUADE (see callback.js in that project)

		/*
		* Synchronous callback used by the backend (e.g. C++ code) just before actually loading a file.
		*
		* This API is called before the backend code performs a "fopen()" (etc), i.e. it advertises 
		* that the code will use that traditional file loading API next. The API allows to fail with a 
		* specific "load in progress" error state - so that this error scenario can be handled *before*
		* the traditional synchronous C-style file loading API is used.
		*
		* The purpose is to let the player asynchronously load the file data and install it at the place
		* in the filesystem (FS) where the backend will look for it.
		*
		* @param namePtr   "virtual" backend filename (EMSCRIPTEN pointer)
		*/
		_fileRequestCallback: function(namePtr)
		{
			let virtualNameBackend = this._backendAdapter.Module.UTF8ToString(namePtr);
			
			let virtualName = this._backendAdapter.mapBackendFilename(virtualNameBackend);
			
			DbgUtil.trace("_fileRequestCallback backend name: " + virtualName + " > FS name: " + virtualName );			
			
			let r= this._preloadFile(virtualName,
									function() {
										this._initIfNeeded(this.lastUsedFilename, this.lastUsedData, this.lastUsedOptions);
									}.bind(this),
									false);
						
			return r;
		},
		/*
		* Convenience API which lets backend directly query the file size - only after the 
		* file is actually available in _fileRequestCallback().
		*/
		_fileSizeRequestCallback: function(namePtr)
		{
			let virtualNameBackend = this._backendAdapter.Module.UTF8ToString(namePtr);
			
			let virtualName = this._backendAdapter.mapBackendFilename(virtualNameBackend);
			
			let data = this._getCache().getFile(virtualName);
			return data.length;
		},

		// may be invoked by backend to "push" updated song attributes (some backends only "learn" about infos
		// like songname, author, etc while the song is actually played..)
		_songUpdateCallback: function(attr)
		{
			// the (very few) backends that actually use this should provide some kind if "onUpdateSongInfo"
			// callback to notify the UI whenever the data is changed here..

			this._backendAdapter.handleBackendSongAttributes(attr);
		},
	};

    return {
		_setGlobalWebAudioCtx: function()
		{
			if (typeof window._gPlayerAudioCtx == 'undefined') 	// cannot be instantiated 2x (so make it global)
			{
				let errText= 'Web Audio API is not supported in this browser';
				try {
					if('AudioContext' in window)
					{
						// note: Chrome's baseLatency seems to be 0.01
						window._gPlayerAudioCtx = new AudioContext({ latencyHint: "playback" });	// increases baseLatency to 0.02 - seems to reduce clicking in AudioWorklet scenario
		//				window._gPlayerAudioCtx = new AudioContext();

					}
					else if('webkitAudioContext' in window)
					{
						window._gPlayerAudioCtx = new webkitAudioContext();		// legacy stuff
					}
					else
					{
						alert(errText + e);
					}
				} catch(e) {
					alert(errText + e);
				}
			}

			// handle Chrome's new bullshit "autoplay policy" - always try to unlock: see processor "reload" issues
			if (window._gPlayerAudioCtx.state == 'suspended')
			{
				try {window._gPlayerAudioCtx.resume();} catch(e) {}
			}
		},


		/**
		*	Initializes the player for use with a specific backend implementation.
		*
		*	note: unused params where left in the signature so as not to break existing code that uses the API. There is
		*	      a "modern" replacement for this method - see initialize(..)
		*
		*	@param requiredFiles files that must have been loaded (in addition to whatever other internal conditions there already are)
		*	       before the player triggers onPlayerReady; pitfalls: a backend may be ready before the player has finished loading
		*		   these files - but prematurely using that backend may result in problems since the backend can expect the completion
		*		   of the preload as a precondition
		*	@param externalTicker must be a subclass of AbstractTicker2
		*	@param bufferSize unused!
		*	@param doOnUpdate unused!
		*/
	    createInstance: function(backendAdapter, UNUSED_basePath, requiredFiles, spectrumEnabled,
								onPlayerReady, onTrackReadyToPlay, onTrackEnd, doOnUpdate, externalTicker, bufferSize)
		{
			if (typeof doOnUpdate != 'undefined')
			{
				console.log("warning: createInstance() no longer uses the 'doOnUpdate' param - backend now has to provide this if necessary!");
			}
			if (typeof bufferSize != 'undefined')
			{
				console.log("warning: createInstance() no longer uses the 'bufferSize' param - backend now has to provide this if necessary!");
			}

			if (typeof window.player != 'undefined' )
			{			// stop existing pipeline
				let p= window.player;
				p._isPaused = true;

				if (typeof p._bufferSource != 'undefined') {
					try {
						p._bufferSource.stop(0);
					} catch(err) {}	// ignore for the benefit of Safari(OS X)
				}
				if (p._producerNode) p._producerNode.disconnect(0);
				if (p._analyzerNode) p._analyzerNode.disconnect(0);
				if (p._gainNode) p._gainNode.disconnect(0);
				if (p._tickerNode) p._tickerNode.disconnect(0);

				window.player = undefined;
			}

			// synchronously assigned to window.player before onPlayerReady is triggered

			new PlayerImpl(backendAdapter, requiredFiles, spectrumEnabled,
							onPlayerReady, onTrackReadyToPlay, onTrackEnd,
							externalTicker, bufferSize);
		},

		/**
		* Returns the player instance if it is ready for use (null if not ready).
		*
		* Use createInstance() or the Promise based initialize() to start the creation of the player instance.
		*/
		getInstance: function()
		{
			if (typeof window.player === 'undefined' )
			{
				if (typeof this.warnedOnce == 'undefined')
				{
					console.log("warning: ScriptNodePlayer.getInstance() called while player instance not ready. Check your initialization code.")
					this.warnedOnce = true;
				}
				return null;
			}
			return window.player;
		},

		/**
		* Getter for global WebAudioContext.
		*
		* Can be used before the player instance has been created, e.g. to access sampleRate.
		*/
		getWebAudioContext: function()
		{
			this._setGlobalWebAudioCtx();
			return window._gPlayerAudioCtx;
		},

		getWebAudioSampleRate: function()
		{
			return this.getWebAudioContext().sampleRate;
		},

// ---- alternative "modern" (Promise based) API variants (same functionality as the
//      original old callback based APIs)

		/**
		* Initialize the player (alternative to old createInstance() API).
		*
		* Using this variant the "onPlayerReady" handling is shifted into the
		* "then" branch of the returned Promise.
		*
		* caution: param order is different to the one of createInstance(..)! all the
		*          params except "backendAdapter" are optional.
		*
		* @param onTrackEnd			callback function to use whenever a song ends; default: noop
		* @param requiredFiles		default: []
		* @param spectrumEnabled	default: false
		* @param externalTicker		default: disabled
		*/
		initialize: function(backendAdapter, onTrackEnd, requiredFiles, spectrumEnabled, externalTicker)
		{
			return new Promise((resolve, reject) => {
				let onTrackReadyToPlay;	// set via below loadMusicFromURL

				let playerReadyCallback = function() {
					resolve("playerIsReady");
				}.bind(this);

				ScriptNodePlayer.createInstance(backendAdapter, undefined, requiredFiles, spectrumEnabled, playerReadyCallback,
												onTrackReadyToPlay, onTrackEnd, undefined, externalTicker, undefined);
			});
		},

		// precondition: player must be ready before this can be used (see initialize(..))
		// note: the loaded song automatically plays
		// @param all the params except "url" are optional
		loadMusicFromURL: function(url, options, onFail, onProgress)
		{
			return new Promise((resolve, reject) => {

				let onTrackReadyToPlay = function(filename) {
					let stat = { "status": "trackReadyToPlay", "file": filename };
					resolve(stat);
				}.bind(this);

				let onCompletion = function() {};	// in hindsight this callback is rather useless
				ScriptNodePlayer.getInstance().loadMusicFromURL(url, options, onCompletion, onFail, onProgress, onTrackReadyToPlay);
			});
		},

		/*
		* Directly loads file data into the caches used by the player (e.g. used for drag&drop).
		*
		* The successful "resolve" of the returned Promise returns the names that the files are
		* now known by in the player. Respective names can then be used with loadMusicFromURL()
		* to play the song files. (Using this new API, the old loadMusicFromTmpFile is obsolete
		* and it has been removed.)
		*
		* @param files   array of file objects: these can either be regular File objects or objects
		*                that provide the following attributes: "xname" (or "name") and "fileBuffer".
		*                Whenever "fileBuffer" is set, this is used as the file's content. If objects
		*                have a non-standard "xname" attribute, then that attribute is used instead of
		*                the standard "name" attribute, allowing to arbitrarily position the files
		*                in the player's virtual file system)
		*/
		loadFileData(files)
		{
			return ScriptNodePlayer.getInstance()._loadFileData(files);
		}

	};
})();

