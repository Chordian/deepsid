// "fix-chrome-consolelog" (or else some idiot chrome versions may crash with "Illegal invokation")
(function(){ var c = window.console.log; window.console.log = function() {c.apply(window.console, arguments); };
window.printErr= window.console.log;
})();

// create separate namespace for all the Emscripten stuff.. otherwise naming clashes may occur especially when 
// optimizing using closure compiler..

window.spp_backend_state_SID= {
	notReady: true,
	adapterCallback: function(){}	// overwritten later	
};
window.spp_backend_state_SID["onRuntimeInitialized"] = function() {	// emscripten callback needed in case async init is used (e.g. for WASM)
	this.notReady= false;
	this.adapterCallback();
}.bind(window.spp_backend_state_SID);

var backend_SID = (function(Module) {
var a;a||(a=typeof Module !== 'undefined' ? Module : {});var l={},m;for(m in a)a.hasOwnProperty(m)&&(l[m]=a[m]);a.arguments=[];a.thisProgram="./this.program";a.quit=function(b,c){throw c;};a.preRun=[];a.postRun=[];var n=!1,p=!1,q=!1,r=!1;n="object"===typeof window;p="function"===typeof importScripts;q="object"===typeof process&&"function"===typeof require&&!n&&!p;r=!n&&!q&&!p;var t="";function u(b){return a.locateFile?a.locateFile(b,t):t+b}
if(q){t=__dirname+"/";var v,w;a.read=function(b,c){v||(v=require("fs"));w||(w=require("path"));b=w.normalize(b);b=v.readFileSync(b);return c?b:b.toString()};a.readBinary=function(b){b=a.read(b,!0);b.buffer||(b=new Uint8Array(b));assert(b.buffer);return b};1<process.argv.length&&(a.thisProgram=process.argv[1].replace(/\\/g,"/"));a.arguments=process.argv.slice(2);"undefined"!==typeof module&&(module.exports=a);process.on("uncaughtException",function(b){throw b;});process.on("unhandledRejection",x);
a.quit=function(b){process.exit(b)};a.inspect=function(){return"[Emscripten Module object]"}}else if(r)"undefined"!=typeof read&&(a.read=function(b){return read(b)}),a.readBinary=function(b){if("function"===typeof readbuffer)return new Uint8Array(readbuffer(b));b=read(b,"binary");assert("object"===typeof b);return b},"undefined"!=typeof scriptArgs?a.arguments=scriptArgs:"undefined"!=typeof arguments&&(a.arguments=arguments),"function"===typeof quit&&(a.quit=function(b){quit(b)});else if(n||p)p?t=
self.location.href:document.currentScript&&(t=document.currentScript.src),t=0!==t.indexOf("blob:")?t.substr(0,t.lastIndexOf("/")+1):"",a.read=function(b){var c=new XMLHttpRequest;c.open("GET",b,!1);c.send(null);return c.responseText},p&&(a.readBinary=function(b){var c=new XMLHttpRequest;c.open("GET",b,!1);c.responseType="arraybuffer";c.send(null);return new Uint8Array(c.response)}),a.readAsync=function(b,c,e){var d=new XMLHttpRequest;d.open("GET",b,!0);d.responseType="arraybuffer";d.onload=function(){200==
d.status||0==d.status&&d.response?c(d.response):e()};d.onerror=e;d.send(null)},a.setWindowTitle=function(b){document.title=b};var y=a.print||("undefined"!==typeof console?console.log.bind(console):"undefined"!==typeof print?print:null),z=a.printErr||("undefined"!==typeof printErr?printErr:"undefined"!==typeof console&&console.warn.bind(console)||y);for(m in l)l.hasOwnProperty(m)&&(a[m]=l[m]);l=void 0;function A(b){var c;c||(c=16);return Math.ceil(b/c)*c}
var aa={"f64-rem":function(b,c){return b%c},"debugger":function(){debugger}},B=!1;function assert(b,c){b||x("Assertion failed: "+c)}
var H={stackSave:function(){C()},stackRestore:function(){D()},arrayToC:function(b){var c=E(b.length);F.set(b,c);return c},stringToC:function(b){var c=0;if(null!==b&&void 0!==b&&0!==b){var e=(b.length<<2)+1;c=E(e);var d=c,g=G;if(0<e){e=d+e-1;for(var h=0;h<b.length;++h){var f=b.charCodeAt(h);if(55296<=f&&57343>=f){var k=b.charCodeAt(++h);f=65536+((f&1023)<<10)|k&1023}if(127>=f){if(d>=e)break;g[d++]=f}else{if(2047>=f){if(d+1>=e)break;g[d++]=192|f>>6}else{if(65535>=f){if(d+2>=e)break;g[d++]=224|f>>12}else{if(2097151>=
f){if(d+3>=e)break;g[d++]=240|f>>18}else{if(67108863>=f){if(d+4>=e)break;g[d++]=248|f>>24}else{if(d+5>=e)break;g[d++]=252|f>>30;g[d++]=128|f>>24&63}g[d++]=128|f>>18&63}g[d++]=128|f>>12&63}g[d++]=128|f>>6&63}g[d++]=128|f&63}}g[d]=0}}return c}},ba={string:H.stringToC,array:H.arrayToC};
function ca(b){var c;if(0===c||!b)return"";for(var e=0,d,g=0;;){d=G[b+g>>0];e|=d;if(0==d&&!c)break;g++;if(c&&g==c)break}c||(c=g);d="";if(128>e){for(;0<c;)e=String.fromCharCode.apply(String,G.subarray(b,b+Math.min(c,1024))),d=d?d+e:e,b+=1024,c-=1024;return d}a:{c=G;for(e=b;c[e];)++e;if(16<e-b&&c.subarray&&J)b=J.decode(c.subarray(b,e));else for(e="";;){d=c[b++];if(!d){b=e;break a}if(d&128)if(g=c[b++]&63,192==(d&224))e+=String.fromCharCode((d&31)<<6|g);else{var h=c[b++]&63;if(224==(d&240))d=(d&15)<<
12|g<<6|h;else{var f=c[b++]&63;if(240==(d&248))d=(d&7)<<18|g<<12|h<<6|f;else{var k=c[b++]&63;if(248==(d&252))d=(d&3)<<24|g<<18|h<<12|f<<6|k;else{var I=c[b++]&63;d=(d&1)<<30|g<<24|h<<18|f<<12|k<<6|I}}}65536>d?e+=String.fromCharCode(d):(d-=65536,e+=String.fromCharCode(55296|d>>10,56320|d&1023))}else e+=String.fromCharCode(d)}}return b}var J="undefined"!==typeof TextDecoder?new TextDecoder("utf8"):void 0;"undefined"!==typeof TextDecoder&&new TextDecoder("utf-16le");var buffer,F,G,K;
function da(){a.HEAP8=F=new Int8Array(buffer);a.HEAP16=new Int16Array(buffer);a.HEAP32=K=new Int32Array(buffer);a.HEAPU8=G=new Uint8Array(buffer);a.HEAPU16=new Uint16Array(buffer);a.HEAPU32=new Uint32Array(buffer);a.HEAPF32=new Float32Array(buffer);a.HEAPF64=new Float64Array(buffer)}var L,M,N,O,P,Q,R;L=M=N=O=P=Q=R=0;
function ea(){x("Cannot enlarge memory arrays. Either (1) compile with  -s TOTAL_MEMORY=X  with X higher than the current value "+S+", (2) compile with  -s ALLOW_MEMORY_GROWTH=1  which allows increasing the size at runtime, or (3) if you want malloc to return NULL (0) instead of this abort, compile with  -s ABORTING_MALLOC=0 ")}var T=a.TOTAL_STACK||5242880,S=a.TOTAL_MEMORY||16777216;S<T&&z("TOTAL_MEMORY should be larger than TOTAL_STACK, was "+S+"! (TOTAL_STACK="+T+")");
a.buffer?buffer=a.buffer:("object"===typeof WebAssembly&&"function"===typeof WebAssembly.Memory?(a.wasmMemory=new WebAssembly.Memory({initial:S/65536,maximum:S/65536}),buffer=a.wasmMemory.buffer):buffer=new ArrayBuffer(S),a.buffer=buffer);da();function U(b){for(;0<b.length;){var c=b.shift();if("function"==typeof c)c();else{var e=c.b;"number"===typeof e?void 0===c.a?a.dynCall_v(e):a.dynCall_vi(e,c.a):e(void 0===c.a?null:c.a)}}}var fa=[],ha=[],ia=[],ja=[],ka=!1;
function la(){var b=a.preRun.shift();fa.unshift(b)}var V=0,W=null,X=null;a.preloadedImages={};a.preloadedAudios={};function Y(b){return String.prototype.startsWith?b.startsWith("data:application/octet-stream;base64,"):0===b.indexOf("data:application/octet-stream;base64,")}
(function(){function b(){try{if(a.wasmBinary)return new Uint8Array(a.wasmBinary);if(a.readBinary)return a.readBinary(g);throw"both async and sync fetching of the wasm failed";}catch(oa){x(oa)}}function c(){return a.wasmBinary||!n&&!p||"function"!==typeof fetch?new Promise(function(c){c(b())}):fetch(g,{credentials:"same-origin"}).then(function(b){if(!b.ok)throw"failed to load wasm binary file at '"+g+"'";return b.arrayBuffer()}).catch(function(){return b()})}function e(b){function d(b){k=b.exports;
if(k.memory){b=k.memory;var c=a.buffer;b.byteLength<c.byteLength&&z("the new buffer in mergeMemory is smaller than the previous one. in native wasm, we should grow memory here");c=new Int8Array(c);(new Int8Array(b)).set(c);a.buffer=buffer=b;da()}a.asm=k;a.usingWasm=!0;V--;a.monitorRunDependencies&&a.monitorRunDependencies(V);0==V&&(null!==W&&(clearInterval(W),W=null),X&&(b=X,X=null,b()))}function e(b){d(b.instance)}function h(b){c().then(function(b){return WebAssembly.instantiate(b,f)}).then(b,function(b){z("failed to asynchronously prepare wasm: "+
b);x(b)})}if("object"!==typeof WebAssembly)return z("no native wasm support detected"),!1;if(!(a.wasmMemory instanceof WebAssembly.Memory))return z("no native wasm Memory in use"),!1;b.memory=a.wasmMemory;f.global={NaN:NaN,Infinity:Infinity};f["global.Math"]=Math;f.env=b;V++;a.monitorRunDependencies&&a.monitorRunDependencies(V);if(a.instantiateWasm)try{return a.instantiateWasm(f,d)}catch(pa){return z("Module.instantiateWasm callback failed with error: "+pa),!1}a.wasmBinary||"function"!==typeof WebAssembly.instantiateStreaming||
Y(g)||"function"!==typeof fetch?h(e):WebAssembly.instantiateStreaming(fetch(g,{credentials:"same-origin"}),f).then(e,function(b){z("wasm streaming compile failed: "+b);z("falling back to ArrayBuffer instantiation");h(e)});return{}}var d="tinyrsid.wast",g="tinyrsid.wasm",h="tinyrsid.temp.asm.js";Y(d)||(d=u(d));Y(g)||(g=u(g));Y(h)||(h=u(h));var f={global:null,env:null,asm2wasm:aa,parent:a},k=null;a.asmPreload=a.asm;var I=a.reallocBuffer;a.reallocBuffer=function(b){if("asmjs"===qa)var c=I(b);else a:{var d=
a.usingWasm?65536:16777216;0<b%d&&(b+=d-b%d);d=a.buffer.byteLength;if(a.usingWasm)try{c=-1!==a.wasmMemory.grow((b-d)/65536)?a.buffer=a.wasmMemory.buffer:null;break a}catch(wa){c=null;break a}c=void 0}return c};var qa="";a.asm=function(b,c){if(!c.table){b=a.wasmTableSize;void 0===b&&(b=1024);var d=a.wasmMaxTableSize;c.table="object"===typeof WebAssembly&&"function"===typeof WebAssembly.Table?void 0!==d?new WebAssembly.Table({initial:b,maximum:d,element:"anyfunc"}):new WebAssembly.Table({initial:b,
element:"anyfunc"}):Array(b);a.wasmTable=c.table}c.memoryBase||(c.memoryBase=a.STATIC_BASE);c.tableBase||(c.tableBase=0);c=e(c);assert(c,"no binaryen method succeeded.");return c}})();
var ma=[function(){console.log("info cannot be retrieved  from corrupt .mus file")},function(){console.log("FATAL ERROR: This BASIC song requires emulator to be configured with optional KERNAL ROM and BASIC ROM")},function(){console.log("FATAL ERROR: no free memory for driver")},function(){console.log("ERROR: PSID INIT hangs")},function(b){console.log("JAM 0:  $"+b.toString(16))}];L=1024;M=L+2347328;ha.push({b:function(){na()}},{b:function(){ra()}});a.STATIC_BASE=L;a.STATIC_BUMP=2347328;M+=16;
function sa(b){return Math.pow(2,b)}var ta=M;M=M+4+15&-16;R=ta;N=O=A(M);P=N+T;Q=A(P);K[R>>2]=Q;a.wasmTableSize=64;a.wasmMaxTableSize=64;a.c={};
a.f={abort:x,enlargeMemory:function(){ea()},getTotalMemory:function(){return S},abortOnCannotGrowMemory:ea,___cxa_pure_virtual:function(){B=!0;throw"Pure virtual function called!";},___setErrNo:function(b){a.___errno_location&&(K[a.___errno_location()>>2]=b);return b},_emscripten_asm_const_i:function(b){return ma[b]()},_emscripten_asm_const_ii:function(b,c){return ma[b](c)},_emscripten_memcpy_big:function(b,c,e){G.set(G.subarray(c,c+e),b);return b},_llvm_exp2_f64:function(){return sa.apply(null,arguments)},
_llvm_trap:function(){x("trap!")},DYNAMICTOP_PTR:R,STACKTOP:O};var ua=a.asm(a.c,a.f,buffer);a.asm=ua;var ra=a.__GLOBAL__sub_I_sid_cpp=function(){return a.asm.__GLOBAL__sub_I_sid_cpp.apply(null,arguments)},na=a.__GLOBAL__sub_I_wavegenerator_cpp=function(){return a.asm.__GLOBAL__sub_I_wavegenerator_cpp.apply(null,arguments)};a._computeAudioSamples=function(){return a.asm._computeAudioSamples.apply(null,arguments)};a._countSIDs=function(){return a.asm._countSIDs.apply(null,arguments)};
a._enableVoice=function(){return a.asm._enableVoice.apply(null,arguments)};a._enableVoices=function(){return a.asm._enableVoices.apply(null,arguments)};a._envIsNTSC=function(){return a.asm._envIsNTSC.apply(null,arguments)};a._envIsSID6581=function(){return a.asm._envIsSID6581.apply(null,arguments)};a._envSetNTSC=function(){return a.asm._envSetNTSC.apply(null,arguments)};a._envSetSID6581=function(){return a.asm._envSetSID6581.apply(null,arguments)};
a._free=function(){return a.asm._free.apply(null,arguments)};a._getBufferVoice1=function(){return a.asm._getBufferVoice1.apply(null,arguments)};a._getBufferVoice2=function(){return a.asm._getBufferVoice2.apply(null,arguments)};a._getBufferVoice3=function(){return a.asm._getBufferVoice3.apply(null,arguments)};a._getBufferVoice4=function(){return a.asm._getBufferVoice4.apply(null,arguments)};a._getCutoff6581=function(){return a.asm._getCutoff6581.apply(null,arguments)};
a._getDigiRate=function(){return a.asm._getDigiRate.apply(null,arguments)};a._getDigiType=function(){return a.asm._getDigiType.apply(null,arguments)};a._getDigiTypeDesc=function(){return a.asm._getDigiTypeDesc.apply(null,arguments)};a._getFilterConfig6581=function(){return a.asm._getFilterConfig6581.apply(null,arguments)};a._getGlobalDigiRate=function(){return a.asm._getGlobalDigiRate.apply(null,arguments)};a._getGlobalDigiType=function(){return a.asm._getGlobalDigiType.apply(null,arguments)};
a._getGlobalDigiTypeDesc=function(){return a.asm._getGlobalDigiTypeDesc.apply(null,arguments)};a._getMusicInfo=function(){return a.asm._getMusicInfo.apply(null,arguments)};a._getNumberTraceStreams=function(){return a.asm._getNumberTraceStreams.apply(null,arguments)};a._getRAM=function(){return a.asm._getRAM.apply(null,arguments)};a._getRegisterSID=function(){return a.asm._getRegisterSID.apply(null,arguments)};a._getSIDBaseAddr=function(){return a.asm._getSIDBaseAddr.apply(null,arguments)};
a._getSIDRegister=function(){return a.asm._getSIDRegister.apply(null,arguments)};a._getSIDRegister2=function(){return a.asm._getSIDRegister2.apply(null,arguments)};a._getSampleRate=function(){return a.asm._getSampleRate.apply(null,arguments)};a._getSoundBuffer=function(){return a.asm._getSoundBuffer.apply(null,arguments)};a._getSoundBufferLen=function(){return a.asm._getSoundBufferLen.apply(null,arguments)};a._getTraceStreams=function(){return a.asm._getTraceStreams.apply(null,arguments)};
a._loadSidFile=function(){return a.asm._loadSidFile.apply(null,arguments)};a._malloc=function(){return a.asm._malloc.apply(null,arguments)};a._playTune=function(){return a.asm._playTune.apply(null,arguments)};a._readVoiceLevel=function(){return a.asm._readVoiceLevel.apply(null,arguments)};a._setFilterConfig6581=function(){return a.asm._setFilterConfig6581.apply(null,arguments)};a._setRAM=function(){return a.asm._setRAM.apply(null,arguments)};
a._setRegisterSID=function(){return a.asm._setRegisterSID.apply(null,arguments)};a._setSIDRegister=function(){return a.asm._setSIDRegister.apply(null,arguments)};var E=a.stackAlloc=function(){return a.asm.stackAlloc.apply(null,arguments)},D=a.stackRestore=function(){return a.asm.stackRestore.apply(null,arguments)},C=a.stackSave=function(){return a.asm.stackSave.apply(null,arguments)};a.dynCall_v=function(){return a.asm.dynCall_v.apply(null,arguments)};
a.dynCall_vi=function(){return a.asm.dynCall_vi.apply(null,arguments)};a.asm=ua;a.ccall=function(b,c,e,d){var g=a["_"+b];assert(g,"Cannot call unknown function "+b+", make sure it is exported");var h=[];b=0;if(d)for(var f=0;f<d.length;f++){var k=ba[e[f]];k?(0===b&&(b=C()),h[f]=k(d[f])):h[f]=d[f]}e=g.apply(null,h);e="string"===c?ca(e):"boolean"===c?!!e:e;0!==b&&D(b);return e};X=function va(){a.calledRun||Z();a.calledRun||(X=va)};
function Z(){function b(){if(!a.calledRun&&(a.calledRun=!0,!B)){ka||(ka=!0,U(ha));U(ia);if(a.onRuntimeInitialized)a.onRuntimeInitialized();if(a.postRun)for("function"==typeof a.postRun&&(a.postRun=[a.postRun]);a.postRun.length;){var b=a.postRun.shift();ja.unshift(b)}U(ja)}}if(!(0<V)){if(a.preRun)for("function"==typeof a.preRun&&(a.preRun=[a.preRun]);a.preRun.length;)la();U(fa);0<V||a.calledRun||(a.setStatus?(a.setStatus("Running..."),setTimeout(function(){setTimeout(function(){a.setStatus("")},1);
b()},1)):b())}}a.run=Z;function x(b){if(a.onAbort)a.onAbort(b);void 0!==b?(y(b),z(b),b=JSON.stringify(b)):b="";B=!0;throw"abort("+b+"). Build with -s ASSERTIONS=1 for more info.";}a.abort=x;if(a.preInit)for("function"==typeof a.preInit&&(a.preInit=[a.preInit]);0<a.preInit.length;)a.preInit.pop()();a.noExitRuntime=!0;Z();
  return {
	Module: Module,  // expose original Module
  };
})(window.spp_backend_state_SID);
/*
 tinyrsid_adapter.js: Adapts Tiny'R'Sid backend to generic WebAudio/ScriptProcessor player.
 
 version 1.02
 
 	Copyright (C) 2020 Juergen Wothke

 LICENSE
 
 This software is licensed under a CC BY-NC-SA 
 (http://creativecommons.org/licenses/by-nc-sa/4.0/).
*/
SIDBackendAdapter = (function(){ var $this = function (basicROM, charROM, kernalROM, nextFrameCB) { 
		$this.base.call(this, backend_SID.Module, 2);	// use stereo (for the benefit of multi-SID songs)
		this.playerSampleRate;

		this.cutoffSize = 1024;
		
		this._scopeEnabled= false;

		this._chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
		this._ROM_SIZE= 0x2000;
		this._CHAR_ROM_SIZE= 0x1000;
		
		this._nextFrameCB= (typeof nextFrameCB == 'undefined') ? this.nopCB : nextFrameCB;
		
		this._basicROM= this.base64DecodeROM(basicROM, this._ROM_SIZE);
		this._charROM= this.base64DecodeROM(charROM, this._CHAR_ROM_SIZE);
		this._kernalROM= this.base64DecodeROM(kernalROM, this._ROM_SIZE);
		
		this._digiShownLabel= "";
		this._digiShownRate= 0;
		
		this.resetDigiMeta();
	}; 
	// TinyRSid's sample buffer contains 2-byte (signed short) sample data 
	// for 1 channel
	extend(EmsHEAP16BackendAdapter, $this, {
		nopCB: function() {
		},
		resetDigiMeta: function() {
			this._digiTypes= {};
			this._digiRate= 0;
			this._digiBatches= 0;
			this._digiEmuCalls= 0;
		},
		enableScope: function(enable) {
			this._scopeEnabled= enable;
		},		
		getAudioBuffer: function() {
			var ptr=  this.Module.ccall('getSoundBuffer', 'number');			
			return ptr>>1;	// 16 bit samples			
		},
		getAudioBufferLength: function() {
			var len= this.Module.ccall('getSoundBufferLen', 'number');
			return len;
		},
		printMemDump: function(name, startAddr, endAddr) {	// util for debugging
			var text= "const unsigned char "+name+"[] =\n{\n";
			var line= "";
			var j= 0;
			for (var i= 0; i<(endAddr-startAddr+1); i++) {
				var d= this.Module.ccall('getRAM', 'number', ['number'], [startAddr+i]);
				line += "0x"+(("00" + d.toString(16)).substr(-2).toUpperCase())+", ";
				if (j  == 11) {						
					text+= (line + "\n");
					line= "";
					j= 0;
				}else {
					j++;
				}
			}		
			text+= (j?(line+"\n"):"")+"}\n";
			console.log(text);
		},
		updateDigiMeta: function() {
			// get a "not so jumpy" status describing digi output
			
			var dTypeStr= this.getExtAsciiString(this.Module.ccall('getDigiTypeDesc', 'number'));
			var dRate= this.Module.ccall('getDigiRate', 'number');
			// "computeAudioSamples" correspond to 50/60Hz, i.e. to show some
			// status for at least half a second, labels should only be updated every
			// 25/30 calls..

			if (!isNaN(dRate) && dRate) {
				this._digiBatches++;
				this._digiRate+= dRate;
				this._digiTypes[dTypeStr]= 1;	// collect labels
			}
			
			this._digiEmuCalls++;
			if (this._digiEmuCalls == 20) {
				this._digiShownLabel= "";
				
				if (!this._digiBatches) {
					this._digiShownRate= 0;
				} else {
					this._digiShownRate= Math.round(this._digiRate/this._digiBatches);
					
					const arr = Object.keys(this._digiTypes).sort();
					var c = 0;
					for (const key of arr) {
						if (key.length && (key != "NONE"))
							c++;
					}
					for (const key of arr) {
						if (key.length && (key != "NONE")  && ((c == 1) || (key != "D418")))	// ignore combinations with D418
							this._digiShownLabel+= (this._digiShownLabel.length ? "&"+key : key);
					}
				}
				this.resetDigiMeta();
			}
		},
		computeAudioSamples: function() {
			if (typeof window.sid_measure_runs == 'undefined' || !window.sid_measure_runs) {
				window.sid_measure_sum= 0;
				window.sid_measure_runs= 0;
			}
			this._nextFrameCB(this);	// used for "interactive mode"
			
			var t = performance.now();
//			console.profile(); // if compiled using "emcc.bat --profiling"
						
			var len= this.Module.ccall('computeAudioSamples', 'number');
			if (len <= 0) {
				this.resetDigiMeta();
				return 1; // >0 means "end song"
			}			
//			console.profileEnd();
			window.sid_measure_sum+= performance.now() - t;
			if (window.sid_measure_runs++ == 100) {
				window.sid_measure = window.sid_measure_sum/window.sid_measure_runs;
				
//				console.log("time; " + window.sid_measure_sum/window.sid_measure_runs);
				window.sid_measure_sum = window.sid_measure_runs = 0;
				
				
				if (typeof window.sid_measure_avg_runs == 'undefined' || !window.sid_measure_avg_runs) {
					window.sid_measure_avg_sum= window.sid_measure;
					window.sid_measure_avg_runs= 1;
				} else {
					window.sid_measure_avg_sum+= window.sid_measure;
					window.sid_measure_avg_runs+= 1;
				}
				window.sid_measure_avg= window.sid_measure_avg_sum/window.sid_measure_avg_runs;
			}
			this.updateDigiMeta();
			return 0;	
		},
		getPathAndFilename: function(filename) {
			return ['/', filename];
		},
		registerFileData: function(pathFilenameArray, data) {
			return 0;	// FS not used in Tiny'R'Sid
		},
		loadMusicData: function(sampleRate, path, filename, data, options) {
			var buf = this.Module._malloc(data.length);
			this.Module.HEAPU8.set(data, buf);
			
			var basicBuf= 0;
			if (this._basicROM) { basicBuf = this.Module._malloc(this._ROM_SIZE); this.Module.HEAPU8.set(this._basicROM, basicBuf);}
			
			var charBuf= 0;
			if (this._charROM) { charBuf = this.Module._malloc(this._CHAR_ROM_SIZE); this.Module.HEAPU8.set(this._charROM, charBuf);}
			
			var kernalBuf= 0;
			if (this._kernalROM) { kernalBuf = this.Module._malloc(this._ROM_SIZE); this.Module.HEAPU8.set(this._kernalROM, kernalBuf);}
			
			// try to use native sample rate to avoid resampling
			this.playerSampleRate= (typeof window._gPlayerAudioCtx == 'undefined') ? 0 : window._gPlayerAudioCtx.sampleRate;	
			
			var isMus= filename.endsWith(".mus") || filename.endsWith(".str");	// Compute! Sidplayer file (stereo files not supported)
			var ret = this.Module.ccall('loadSidFile', 'number', ['number', 'number', 'number', 'number', 'string', 'number', 'number', 'number'], 
										[isMus, buf, data.length, this.playerSampleRate, filename, basicBuf, charBuf, kernalBuf]);

			if (kernalBuf) this.Module._free(kernalBuf);
			if (charBuf) this.Module._free(charBuf);
			if (basicBuf) this.Module._free(basicBuf);
			
			this.Module._free(buf);

			if (ret == 0) {
				this.playerSampleRate = this.Module.ccall('getSampleRate', 'number');
				this.resetSampleRate(sampleRate, this.playerSampleRate); 
			}
			return ret;			
		},
		evalTrackOptions: function(options) {
			if (typeof options.timeout != 'undefined') {
				ScriptNodePlayer.getInstance().setPlaybackTimeout(options.timeout*1000);
			}
			var traceSID= this._scopeEnabled;
			if (typeof options.traceSID != 'undefined') {
				traceSID= options.traceSID;
			}
			if (typeof options.track == 'undefined') {
				options.track= -1;
			}
			this.resetDigiMeta();
		
			var procBufSize= ScriptNodePlayer.getInstance().getScriptProcessorBufSize();
			return this.Module.ccall('playTune', 'number', ['number', 'number', 'number'], [options.track, traceSID, procBufSize]);
		},
		setFilterConfig6581: function(base, max, steepness, x_offset, distort, distortOffset, distortScale, distortThreshold, kink) {
			return this.Module.ccall('setFilterConfig6581', 'number', 
										['number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number'], 
										[base, max, steepness, x_offset, distort, distortOffset, distortScale, distortThreshold, kink]);
		},
		getFilterConfig6581: function() {
			var heapPtr = this.Module.ccall('getFilterConfig6581', 'number') >> 3;	// 64-bit

			var result= {
				"base": this.Module.HEAPF64[heapPtr+0],
				"max": this.Module.HEAPF64[heapPtr+1],
				"steepness": this.Module.HEAPF64[heapPtr+2],
				"x_offset": this.Module.HEAPF64[heapPtr+3],
				"distort": this.Module.HEAPF64[heapPtr+4],
				"distortOffset": this.Module.HEAPF64[heapPtr+5],
				"distortScale": this.Module.HEAPF64[heapPtr+6],
				"distortThreshold": this.Module.HEAPF64[heapPtr+7],
				"kink": this.Module.HEAPF64[heapPtr+8],
			};
			return result;
		},
		getCutoffsLength: function() {
			return this.cutoffSize;
		},
		fetchCutoffs6581: function(distortLevel, destinationArray) {
			var heapPtr = this.Module.ccall('getCutoff6581', 'number', ['number'], [distortLevel]) >> 3;	// 64-bit

			for (var i= 0; i<this.cutoffSize; i++) {
				destinationArray[i]= this.Module.HEAPF64[heapPtr+i];
			}
		},
		teardown: function() {
			// nothing to do
		},
		getSongInfoMeta: function() {
			return {			
					loadAddr: Number,
					playSpeed: Number,
					maxSubsong: Number,
					actualSubsong: Number,
					songName: String,
					songAuthor: String, 
					songReleased: String 
					};
		},
		getExtAsciiString: function(heapPtr) {
			// Pointer_stringify cannot be used here since UTF-8 parsing 
			// messes up original extASCII content
			var text="";
			for (var j= 0; j<32; j++) {
				var b= this.Module.HEAP8[heapPtr+j] & 0xff;
				if(b ==0) break;
				
				if(b < 128){
					text = text + String.fromCharCode(b);
				} else {
					text = text + "&#" + b + ";";
				}
			}
			return text;
		},
		updateSongInfo: function(filename, result) {
		// get song infos (so far only use some top level module infos)
			var numAttr= 7;
			var ret = this.Module.ccall('getMusicInfo', 'number');
						
			var array = this.Module.HEAP32.subarray(ret>>2, (ret>>2)+7);
			result.loadAddr= this.Module.HEAP32[((array[0])>>2)]; // i32
			result.playSpeed= this.Module.HEAP32[((array[1])>>2)]; // i32
			result.maxSubsong= this.Module.HEAP8[(array[2])]; // i8
			result.actualSubsong= this.Module.HEAP8[(array[3])]; // i8
			result.songName= this.getExtAsciiString(array[4]);			
			result.songAuthor= this.getExtAsciiString(array[5]);
			result.songReleased= this.getExtAsciiString(array[6]);			
		},
		
		// C64 emu specific accessors (that might be useful in GUI)
		isSID6581: function() {
			return this.Module.ccall('envIsSID6581', 'number');
		},
		setSID6581: function(is6581) {
			this.Module.ccall('envSetSID6581', 'number', ['number'], [is6581]);
		},
		isNTSC: function() {
			return this.Module.ccall('envIsNTSC', 'number');
		},
		setNTSC: function(ntsc) {
			this.Module.ccall('envSetNTSC', 'number', ['number'], [ntsc]);
		},
		
		// access used SID chips meta information
		countSIDs: function() {
			return this.Module.ccall('countSIDs', 'number');
		},
		getSIDBaseAddr: function(sidIdx) {
			return this.Module.ccall('getSIDBaseAddr', 'number', ['number'], [sidIdx]);
		},

		/**
		* Gets a SID's register with about ~1 frame precison - using the actual position played
		* by the WebAudio infrastructure.
		*
		* prerequisite: ScriptNodePlayer must be configured with an "external ticker" for precisely timed access.
		*/
		getSIDRegister: function(sidIdx, reg) {
			
			var p= ScriptNodePlayer.getInstance();
			var bufIdx= p.getTickToggle();
			var tick= p.getCurrentTick(); // playback position in currently played WebAudio buffer (in 256-samples steps)
			
			return this.Module.ccall('getSIDRegister2', 'number', ['number', 'number', 'number', 'number'], [sidIdx, reg, bufIdx, tick]);
		},
		/**
		* Gets a specific SID voice's output level (aka envelope) with about ~1 frame precison - using the actual position played
		* by the WebAudio infrastructure.
		*
		* prerequisite: ScriptNodePlayer must be configured with an "external ticker" for precisely timed access.
		*/
		readVoiceLevel: function(sidIdx, voiceIdx) {
			
			var p= ScriptNodePlayer.getInstance();
			var bufIdx= p.getTickToggle();
			var tick= p.getCurrentTick(); // playback position in currently played WebAudio buffer (in 256-samples steps)
			
			return this.Module.ccall('readVoiceLevel', 'number', ['number', 'number', 'number', 'number'], [sidIdx, voiceIdx, bufIdx, tick]);
		},
		setSIDRegister: function(sidIdx, reg, value) {
			return this.Module.ccall('setSIDRegister', 'number', ['number', 'number', 'number'], [sidIdx, reg, value]);
		},
		
		// To activate the below output a song must be started with the "traceSID" option set to 1:
		// At any given moment the below getters will then correspond to the output of getAudioBuffer
		// and what has last been generated by computeAudioSamples. They expose some of the respective
		// underlying internal SID state (the "filter" is NOT reflected in this data).
		getNumberTraceStreams: function() {
			return this.Module.ccall('getNumberTraceStreams', 'number');			
		},
		getTraceStreams: function() {
			var result= [];
			var n= this.getNumberTraceStreams();

			var ret = this.Module.ccall('getTraceStreams', 'number');			
			var array = this.Module.HEAP32.subarray(ret>>2, (ret>>2)+n);
			
			for (var i= 0; i<n; i++) {
				result.push(array[i] >> 1);	// pointer to int16 array
			}
			return result;
		},

		readFloatTrace: function(buffer, idx) {
			return (this.Module.HEAP16[buffer+idx])/0x8000;
		},
		getCopiedScopeStream: function(input, len, output) {
			for(var i= 0; i<len; i++){
				output[i]=  this.Module.HEAP16[input+i]; // will be scaled later anyway.. avoid the extra division here /0x8000;
			}
			return len;
		},

		getRAM: function(offset) {
			return this.Module.ccall('getRAM', 'number', ['number'], [offset]);
		},
		setRAM: function(offset, value) {
			this.Module.ccall('setRAM', 'number', ['number', 'number'], [offset, value]);
		},
		/**
		* Diagnostics digi-samples (if any).
		*/
		getDigiType: function() {
			return this.Module.ccall('getDigiType', 'number');
		},
		getDigiTypeDesc: function() {
			return this._digiShownLabel;
		},
		getDigiRate: function() {
			return this._digiShownRate;
		},
		enableVoice: function(sidIdx, voice, on) {
			this.Module.ccall('enableVoice', 'number', ['number', 'number', 'number'], [sidIdx, voice, on]);
		},

		
		/**
		* @deprecated use getSIDRegister instead
		*/
		getRegisterSID: function(offset) {
			return this.Module.ccall('getRegisterSID', 'number', ['number'], [offset]);
		},
		/**
		* @deprecated use setSIDRegister instead
		*/
		setRegisterSID: function(offset, value) {
			this.Module.ccall('setRegisterSID', 'number', ['number', 'number'], [offset, value]);
		},
		/*
		* @deprecated APIs below - use getTraceStreams/getNumberTraceStreams instead
		*/
		// disable voices (0-3) by clearing respective bit
		enableVoices: function(mask) {
			this.Module.ccall('enableVoices', 'number', ['number'], [mask]);
		},
		getBufferVoice1: function() {
			var ptr=  this.Module.ccall('getBufferVoice1', 'number');			
			return ptr>>1;	// 16 bit samples			
		},
		getBufferVoice2: function() {
			var ptr=  this.Module.ccall('getBufferVoice2', 'number');			
			return ptr>>1;	// 16 bit samples			
		},
		getBufferVoice3: function() {
			var ptr=  this.Module.ccall('getBufferVoice3', 'number');			
			return ptr>>1;	// 16 bit samples			
		},
		getBufferVoice4: function() {
			var ptr=  this.Module.ccall('getBufferVoice4', 'number');			
			return ptr>>1;	// 16 bit samples			
		},
		// base64 decoding util
		findChar: function(str, c) {
			for (var i= 0; i<str.length; i++) {
				if (str.charAt(i) == c) {
					return i;
				}
			}
			return -1;
		},
		alphanumeric: function(inputtxt) {
			var letterNumber = /^[0-9a-zA-Z]+$/;
			return inputtxt.match(letterNumber);
		},
		is_base64: function(c) {
		  return (this.alphanumeric(""+c) || (c == '+') || (c == '/'));
		},
		base64DecodeROM: function(encoded, romSize) {
			if (typeof encoded == 'undefined') return 0;
			
			var in_len= encoded.length;
			var i= j= in_= 0;
			var arr4= new Array(4);
			var arr3= new Array(3);
			
			var ret= new Uint8Array(romSize);
			var ri= 0;

			while (in_len-- && ( encoded.charAt(in_) != '=') && this.is_base64(encoded.charAt(in_))) {
				arr4[i++]= encoded.charAt(in_); in_++;
				if (i ==4) {
					for (i = 0; i <4; i++) {
						arr4[i] = this.findChar(this._chars, arr4[i]);
					}
					arr3[0] = ( arr4[0] << 2       ) + ((arr4[1] & 0x30) >> 4);
					arr3[1] = ((arr4[1] & 0xf) << 4) + ((arr4[2] & 0x3c) >> 2);
					arr3[2] = ((arr4[2] & 0x3) << 6) +   arr4[3];

					for (i = 0; (i < 3); i++) {
						var val= arr3[i];
						ret[ri++]= val;
					}
					i = 0;
				}
			}
			if (i) {
				for (j = 0; j < i; j++) {
					arr4[j] = this.findChar(this._chars, arr4[j]);
				}
				arr3[0] = (arr4[0] << 2) + ((arr4[1] & 0x30) >> 4);
				arr3[1] = ((arr4[1] & 0xf) << 4) + ((arr4[2] & 0x3c) >> 2);

				for (j = 0; (j < i - 1); j++) {
					var val= arr3[j];
					ret[ri++]= val;
				}
			}
			if (ri == romSize) {
				return ret;
			}
			return 0;
		},
	});	return $this; })();
	