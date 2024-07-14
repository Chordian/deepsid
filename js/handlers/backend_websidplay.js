// "fix-chrome-consolelog" (or else some idiot chrome versions may crash with "Illegal invokation")
(function(){ var c = window.console.log; window.console.log = function() {c.apply(window.console, arguments); };
window.printErr= window.console.log;
})();

// create separate namespace for all the Emscripten stuff.. otherwise naming clashes may occur especially when 
// optimizing using closure compiler..

window.spp_backend_state_SIDPlay= {
	locateFile: function(path, scriptDirectory) { return (typeof window.WASM_SEARCH_PATH == 'undefined') ? path : window.WASM_SEARCH_PATH + path; },
	notReady: true,
	adapterCallback: function(){}	// overwritten later	
};
window.spp_backend_state_SIDPlay["onRuntimeInitialized"] = function() {	// emscripten callback needed in case async init is used (e.g. for WASM)
	this.notReady= false;
	this.adapterCallback();
}.bind(window.spp_backend_state_SIDPlay);

var backend_SIDPlay = (function(Module) {
var e;e||(e=typeof Module !== 'undefined' ? Module : {});var aa={},k;for(k in e)e.hasOwnProperty(k)&&(aa[k]=e[k]);e.arguments=[];e.thisProgram="./this.program";e.quit=function(a,b){throw b;};e.preRun=[];e.postRun=[];var ba=!1,m=!1,p=!1,ca=!1;ba="object"===typeof window;m="function"===typeof importScripts;p="object"===typeof process&&"function"===typeof require&&!ba&&!m;ca=!ba&&!p&&!m;var r="";function da(a){return e.locateFile?e.locateFile(a,r):r+a}
if(p){r=__dirname+"/";var ea,fa;e.read=function(a,b){ea||(ea=require("fs"));fa||(fa=require("path"));a=fa.normalize(a);a=ea.readFileSync(a);return b?a:a.toString()};e.readBinary=function(a){a=e.read(a,!0);a.buffer||(a=new Uint8Array(a));assert(a.buffer);return a};1<process.argv.length&&(e.thisProgram=process.argv[1].replace(/\\/g,"/"));e.arguments=process.argv.slice(2);"undefined"!==typeof module&&(module.exports=e);process.on("uncaughtException",function(a){throw a;});process.on("unhandledRejection",
t);e.quit=function(a){process.exit(a)};e.inspect=function(){return"[Emscripten Module object]"}}else if(ca)"undefined"!=typeof read&&(e.read=function(a){return read(a)}),e.readBinary=function(a){if("function"===typeof readbuffer)return new Uint8Array(readbuffer(a));a=read(a,"binary");assert("object"===typeof a);return a},"undefined"!=typeof scriptArgs?e.arguments=scriptArgs:"undefined"!=typeof arguments&&(e.arguments=arguments),"function"===typeof quit&&(e.quit=function(a){quit(a)});else if(ba||m)m?
r=self.location.href:document.currentScript&&(r=document.currentScript.src),r=0!==r.indexOf("blob:")?r.substr(0,r.lastIndexOf("/")+1):"",e.read=function(a){var b=new XMLHttpRequest;b.open("GET",a,!1);b.send(null);return b.responseText},m&&(e.readBinary=function(a){var b=new XMLHttpRequest;b.open("GET",a,!1);b.responseType="arraybuffer";b.send(null);return new Uint8Array(b.response)}),e.readAsync=function(a,b,c){var d=new XMLHttpRequest;d.open("GET",a,!0);d.responseType="arraybuffer";d.onload=function(){200==
d.status||0==d.status&&d.response?b(d.response):c()};d.onerror=c;d.send(null)},e.setWindowTitle=function(a){document.title=a};var ha=e.print||("undefined"!==typeof console?console.log.bind(console):"undefined"!==typeof print?print:null),u=e.printErr||("undefined"!==typeof printErr?printErr:"undefined"!==typeof console&&console.warn.bind(console)||ha);for(k in aa)aa.hasOwnProperty(k)&&(e[k]=aa[k]);aa=void 0;function ia(a){var b=v;v=v+a+15&-16;return b}
function ja(a){var b;b||(b=16);return Math.ceil(a/b)*b}var la={"f64-rem":function(a,b){return a%b},"debugger":function(){debugger}},ma=!1;function assert(a,b){a||t("Assertion failed: "+b)}var qa={stackSave:function(){x()},stackRestore:function(){y()},arrayToC:function(a){var b=na(a.length);oa.set(a,b);return b},stringToC:function(a){var b=0;if(null!==a&&void 0!==a&&0!==a){var c=(a.length<<2)+1;b=na(c);pa(a,C,b,c)}return b}},ra={string:qa.stringToC,array:qa.arrayToC};
function D(a){var b;if(0===b||!a)return"";for(var c=0,d,f=0;;){d=C[a+f>>0];c|=d;if(0==d&&!b)break;f++;if(b&&f==b)break}b||(b=f);d="";if(128>c){for(;0<b;)c=String.fromCharCode.apply(String,C.subarray(a,a+Math.min(b,1024))),d=d?d+c:c,a+=1024,b-=1024;return d}return sa(a)}var ta="undefined"!==typeof TextDecoder?new TextDecoder("utf8"):void 0;
function ua(a,b){for(var c=b;a[c];)++c;if(16<c-b&&a.subarray&&ta)return ta.decode(a.subarray(b,c));for(c="";;){var d=a[b++];if(!d)return c;if(d&128){var f=a[b++]&63;if(192==(d&224))c+=String.fromCharCode((d&31)<<6|f);else{var g=a[b++]&63;if(224==(d&240))d=(d&15)<<12|f<<6|g;else{var h=a[b++]&63;if(240==(d&248))d=(d&7)<<18|f<<12|g<<6|h;else{var l=a[b++]&63;if(248==(d&252))d=(d&3)<<24|f<<18|g<<12|h<<6|l;else{var n=a[b++]&63;d=(d&1)<<30|f<<24|g<<18|h<<12|l<<6|n}}}65536>d?c+=String.fromCharCode(d):(d-=
65536,c+=String.fromCharCode(55296|d>>10,56320|d&1023))}}else c+=String.fromCharCode(d)}}function sa(a){return ua(C,a)}
function pa(a,b,c,d){if(!(0<d))return 0;var f=c;d=c+d-1;for(var g=0;g<a.length;++g){var h=a.charCodeAt(g);if(55296<=h&&57343>=h){var l=a.charCodeAt(++g);h=65536+((h&1023)<<10)|l&1023}if(127>=h){if(c>=d)break;b[c++]=h}else{if(2047>=h){if(c+1>=d)break;b[c++]=192|h>>6}else{if(65535>=h){if(c+2>=d)break;b[c++]=224|h>>12}else{if(2097151>=h){if(c+3>=d)break;b[c++]=240|h>>18}else{if(67108863>=h){if(c+4>=d)break;b[c++]=248|h>>24}else{if(c+5>=d)break;b[c++]=252|h>>30;b[c++]=128|h>>24&63}b[c++]=128|h>>18&63}b[c++]=
128|h>>12&63}b[c++]=128|h>>6&63}b[c++]=128|h&63}}b[c]=0;return c-f}function va(a){for(var b=0,c=0;c<a.length;++c){var d=a.charCodeAt(c);55296<=d&&57343>=d&&(d=65536+((d&1023)<<10)|a.charCodeAt(++c)&1023);127>=d?++b:b=2047>=d?b+2:65535>=d?b+3:2097151>=d?b+4:67108863>=d?b+5:b+6}return b}"undefined"!==typeof TextDecoder&&new TextDecoder("utf-16le");var buffer,oa,C,wa,E;
function xa(){e.HEAP8=oa=new Int8Array(buffer);e.HEAP16=wa=new Int16Array(buffer);e.HEAP32=E=new Int32Array(buffer);e.HEAPU8=C=new Uint8Array(buffer);e.HEAPU16=new Uint16Array(buffer);e.HEAPU32=new Uint32Array(buffer);e.HEAPF32=new Float32Array(buffer);e.HEAPF64=new Float64Array(buffer)}var ya,v,za,Aa,Ba,Ca,Da,F;ya=v=Aa=Ba=Ca=Da=F=0;za=!1;
function Ea(){t("Cannot enlarge memory arrays. Either (1) compile with  -s TOTAL_MEMORY=X  with X higher than the current value "+G+", (2) compile with  -s ALLOW_MEMORY_GROWTH=1  which allows increasing the size at runtime, or (3) if you want malloc to return NULL (0) instead of this abort, compile with  -s ABORTING_MALLOC=0 ")}var Fa=e.TOTAL_STACK||5242880,G=e.TOTAL_MEMORY||134217728;G<Fa&&u("TOTAL_MEMORY should be larger than TOTAL_STACK, was "+G+"! (TOTAL_STACK="+Fa+")");
e.buffer?buffer=e.buffer:("object"===typeof WebAssembly&&"function"===typeof WebAssembly.Memory?(e.wasmMemory=new WebAssembly.Memory({initial:G/65536,maximum:G/65536}),buffer=e.wasmMemory.buffer):buffer=new ArrayBuffer(G),e.buffer=buffer);xa();function Ga(a){for(;0<a.length;){var b=a.shift();if("function"==typeof b)b();else{var c=b.K;"number"===typeof c?void 0===b.ea?e.dynCall_v(c):e.dynCall_vi(c,b.ea):c(void 0===b.ea?null:b.ea)}}}var Ha=[],Ia=[],Ja=[],Ka=[],La=[],Ma=!1;
function Na(){var a=e.preRun.shift();Ha.unshift(a)}var H=0,Oa=null,Pa=null;function Qa(){H++;e.monitorRunDependencies&&e.monitorRunDependencies(H)}function Ra(){H--;e.monitorRunDependencies&&e.monitorRunDependencies(H);if(0==H&&(null!==Oa&&(clearInterval(Oa),Oa=null),Pa)){var a=Pa;Pa=null;a()}}e.preloadedImages={};e.preloadedAudios={};function Sa(a){return String.prototype.startsWith?a.startsWith("data:application/octet-stream;base64,"):0===a.indexOf("data:application/octet-stream;base64,")}
(function(){function a(){try{if(e.wasmBinary)return new Uint8Array(e.wasmBinary);if(e.readBinary)return e.readBinary(f);throw"both async and sync fetching of the wasm failed";}catch(w){t(w)}}function b(){return e.wasmBinary||!ba&&!m||"function"!==typeof fetch?new Promise(function(b){b(a())}):fetch(f,{credentials:"same-origin"}).then(function(a){if(!a.ok)throw"failed to load wasm binary file at '"+f+"'";return a.arrayBuffer()}).catch(function(){return a()})}function c(a){function c(a){l=a.exports;
if(l.memory){a=l.memory;var b=e.buffer;a.byteLength<b.byteLength&&u("the new buffer in mergeMemory is smaller than the previous one. in native wasm, we should grow memory here");b=new Int8Array(b);(new Int8Array(a)).set(b);e.buffer=buffer=a;xa()}e.asm=l;e.usingWasm=!0;Ra()}function d(a){c(a.instance)}function g(a){b().then(function(a){return WebAssembly.instantiate(a,h)}).then(a,function(a){u("failed to asynchronously prepare wasm: "+a);t(a)})}if("object"!==typeof WebAssembly)return u("no native wasm support detected"),
!1;if(!(e.wasmMemory instanceof WebAssembly.Memory))return u("no native wasm Memory in use"),!1;a.memory=e.wasmMemory;h.global={NaN:NaN,Infinity:Infinity};h["global.Math"]=Math;h.env=a;Qa();if(e.instantiateWasm)try{return e.instantiateWasm(h,c)}catch(I){return u("Module.instantiateWasm callback failed with error: "+I),!1}e.wasmBinary||"function"!==typeof WebAssembly.instantiateStreaming||Sa(f)||"function"!==typeof fetch?g(d):WebAssembly.instantiateStreaming(fetch(f,{credentials:"same-origin"}),h).then(d,
function(a){u("wasm streaming compile failed: "+a);u("falling back to ArrayBuffer instantiation");g(d)});return{}}var d="websidplay.wast",f="websidplay.wasm",g="websidplay.temp.asm.js";Sa(d)||(d=da(d));Sa(f)||(f=da(f));Sa(g)||(g=da(g));var h={global:null,env:null,asm2wasm:la,parent:e},l=null;e.asmPreload=e.asm;var n=e.reallocBuffer;e.reallocBuffer=function(a){if("asmjs"===q)var b=n(a);else a:{var c=e.usingWasm?65536:16777216;0<a%c&&(a+=c-a%c);c=e.buffer.byteLength;if(e.usingWasm)try{b=-1!==e.wasmMemory.grow((a-
c)/65536)?e.buffer=e.wasmMemory.buffer:null;break a}catch(z){b=null;break a}b=void 0}return b};var q="";e.asm=function(a,b){if(!b.table){a=e.wasmTableSize;void 0===a&&(a=1024);var d=e.wasmMaxTableSize;b.table="object"===typeof WebAssembly&&"function"===typeof WebAssembly.Table?void 0!==d?new WebAssembly.Table({initial:a,maximum:d,element:"anyfunc"}):new WebAssembly.Table({initial:a,element:"anyfunc"}):Array(a);e.wasmTable=b.table}b.memoryBase||(b.memoryBase=e.STATIC_BASE);b.tableBase||(b.tableBase=
0);b=c(b);assert(b,"no binaryen method succeeded.");return b}})();ya=1024;v=ya+47600;Ia.push({K:function(){Ta()}},{K:function(){Ua()}},{K:function(){Va()}},{K:function(){Wa()}},{K:function(){Xa()}},{K:function(){Ya()}});e.STATIC_BASE=ya;e.STATIC_BUMP=47600;v+=16;var Za=0,$a=[],J={};function ab(a){if(!a||J[a])return a;for(var b in J){var c=+b;if(J[c].ra===a)return c}return a}function ___cxa_free_exception(a){try{return bb(a)}catch(b){}}
function cb(){var a=Za;if(!a)return(db(0),0)|0;var b=J[a],c=b.type;if(!c)return(db(0),a)|0;var d=Array.prototype.slice.call(arguments);e.___cxa_is_pointer_type(c);eb||(eb=fb(4));E[eb>>2]=a;a=eb;for(var f=0;f<d.length;f++)if(d[f]&&e.___cxa_can_catch(d[f],c,a))return a=E[a>>2],b.ra=a,(db(d[f]),a)|0;a=E[a>>2];return(db(c),a)|0}
var eb,K={H:1,B:2,Rc:3,Nb:4,F:5,qa:6,gb:7,lc:8,A:9,ub:10,ma:11,ad:11,Ka:12,$:13,Gb:14,xc:15,aa:16,na:17,bd:18,da:19,oa:20,N:21,h:22,fc:23,Ja:24,G:25,Yc:26,Hb:27,tc:28,S:29,Oc:30,Zb:31,Hc:32,Db:33,Lc:34,pc:42,Kb:43,vb:44,Qb:45,Rb:46,Sb:47,Yb:48,Zc:49,jc:50,Pb:51,Ab:35,mc:37,mb:52,pb:53,cd:54,hc:55,qb:56,rb:57,Bb:35,sb:59,vc:60,kc:61,Vc:62,uc:63,qc:64,rc:65,Nc:66,nc:67,jb:68,Sc:69,wb:70,Ic:71,ac:72,Eb:73,ob:74,Cc:76,nb:77,Mc:78,Tb:79,Ub:80,Xb:81,Wb:82,Vb:83,wc:38,pa:39,bc:36,ba:40,Dc:95,Gc:96,zb:104,
ic:105,kb:97,Kc:91,Ac:88,sc:92,Pc:108,yb:111,hb:98,xb:103,ec:101,cc:100,Wc:110,Ib:112,Jb:113,Mb:115,lb:114,Cb:89,$b:90,Jc:93,Qc:94,ib:99,dc:102,Ob:106,yc:107,Xc:109,$c:87,Fb:122,Tc:116,Bc:95,oc:123,Lb:84,Ec:75,tb:125,zc:131,Fc:130,Uc:86};function gb(a){e.___errno_location&&(E[e.___errno_location()>>2]=a);return a}
var hb={0:"Success",1:"Not super-user",2:"No such file or directory",3:"No such process",4:"Interrupted system call",5:"I/O error",6:"No such device or address",7:"Arg list too long",8:"Exec format error",9:"Bad file number",10:"No children",11:"No more processes",12:"Not enough core",13:"Permission denied",14:"Bad address",15:"Block device required",16:"Mount device busy",17:"File exists",18:"Cross-device link",19:"No such device",20:"Not a directory",21:"Is a directory",22:"Invalid argument",23:"Too many open files in system",
24:"Too many open files",25:"Not a typewriter",26:"Text file busy",27:"File too large",28:"No space left on device",29:"Illegal seek",30:"Read only file system",31:"Too many links",32:"Broken pipe",33:"Math arg out of domain of func",34:"Math result not representable",35:"File locking deadlock error",36:"File or path name too long",37:"No record locks available",38:"Function not implemented",39:"Directory not empty",40:"Too many symbolic links",42:"No message of desired type",43:"Identifier removed",
44:"Channel number out of range",45:"Level 2 not synchronized",46:"Level 3 halted",47:"Level 3 reset",48:"Link number out of range",49:"Protocol driver not attached",50:"No CSI structure available",51:"Level 2 halted",52:"Invalid exchange",53:"Invalid request descriptor",54:"Exchange full",55:"No anode",56:"Invalid request code",57:"Invalid slot",59:"Bad font file fmt",60:"Device not a stream",61:"No data (for no delay io)",62:"Timer expired",63:"Out of streams resources",64:"Machine is not on the network",
65:"Package not installed",66:"The object is remote",67:"The link has been severed",68:"Advertise error",69:"Srmount error",70:"Communication error on send",71:"Protocol error",72:"Multihop attempted",73:"Cross mount point (not really error)",74:"Trying to read unreadable message",75:"Value too large for defined data type",76:"Given log. name not unique",77:"f.d. invalid for this operation",78:"Remote address changed",79:"Can   access a needed shared lib",80:"Accessing a corrupted shared lib",81:".lib section in a.out corrupted",
82:"Attempting to link in too many libs",83:"Attempting to exec a shared library",84:"Illegal byte sequence",86:"Streams pipe error",87:"Too many users",88:"Socket operation on non-socket",89:"Destination address required",90:"Message too long",91:"Protocol wrong type for socket",92:"Protocol not available",93:"Unknown protocol",94:"Socket type not supported",95:"Not supported",96:"Protocol family not supported",97:"Address family not supported by protocol family",98:"Address already in use",99:"Address not available",
100:"Network interface is not configured",101:"Network is unreachable",102:"Connection reset by network",103:"Connection aborted",104:"Connection reset by peer",105:"No buffer space available",106:"Socket is already connected",107:"Socket is not connected",108:"Can't send after socket shutdown",109:"Too many references",110:"Connection timed out",111:"Connection refused",112:"Host is down",113:"Host is unreachable",114:"Socket already connected",115:"Connection already in progress",116:"Stale file handle",
122:"Quota exceeded",123:"No medium (in tape drive)",125:"Operation canceled",130:"Previous owner died",131:"State not recoverable"};function ib(a,b){for(var c=0,d=a.length-1;0<=d;d--){var f=a[d];"."===f?a.splice(d,1):".."===f?(a.splice(d,1),c++):c&&(a.splice(d,1),c--)}if(b)for(;c;c--)a.unshift("..");return a}function jb(a){var b="/"===a.charAt(0),c="/"===a.substr(-1);(a=ib(a.split("/").filter(function(a){return!!a}),!b).join("/"))||b||(a=".");a&&c&&(a+="/");return(b?"/":"")+a}
function kb(a){var b=/^(\/?|)([\s\S]*?)((?:\.{1,2}|[^\/]+?|)(\.[^.\/]*|))(?:[\/]*)$/.exec(a).slice(1);a=b[0];b=b[1];if(!a&&!b)return".";b&&(b=b.substr(0,b.length-1));return a+b}function lb(a){if("/"===a)return"/";var b=a.lastIndexOf("/");return-1===b?a:a.substr(b+1)}function mb(){var a=Array.prototype.slice.call(arguments,0);return jb(a.join("/"))}function L(a,b){return jb(a+"/"+b)}
function nb(){for(var a="",b=!1,c=arguments.length-1;-1<=c&&!b;c--){b=0<=c?arguments[c]:"/";if("string"!==typeof b)throw new TypeError("Arguments to path.resolve must be strings");if(!b)return"";a=b+"/"+a;b="/"===b.charAt(0)}a=ib(a.split("/").filter(function(a){return!!a}),!b).join("/");return(b?"/":"")+a||"."}var ob=[];function pb(a,b){ob[a]={input:[],output:[],M:b};rb(a,sb)}
var sb={open:function(a){var b=ob[a.node.rdev];if(!b)throw new M(K.da);a.tty=b;a.seekable=!1},close:function(a){a.tty.M.flush(a.tty)},flush:function(a){a.tty.M.flush(a.tty)},read:function(a,b,c,d){if(!a.tty||!a.tty.M.Da)throw new M(K.qa);for(var f=0,g=0;g<d;g++){try{var h=a.tty.M.Da(a.tty)}catch(l){throw new M(K.F);}if(void 0===h&&0===f)throw new M(K.ma);if(null===h||void 0===h)break;f++;b[c+g]=h}f&&(a.node.timestamp=Date.now());return f},write:function(a,b,c,d){if(!a.tty||!a.tty.M.ka)throw new M(K.qa);
for(var f=0;f<d;f++)try{a.tty.M.ka(a.tty,b[c+f])}catch(g){throw new M(K.F);}d&&(a.node.timestamp=Date.now());return f}},ub={Da:function(a){if(!a.input.length){var b=null;if(p){var c=new Buffer(256),d=0,f=process.stdin.fd;if("win32"!=process.platform){var g=!1;try{f=fs.openSync("/dev/stdin","r"),g=!0}catch(h){}}try{d=fs.readSync(f,c,0,256,null)}catch(h){if(-1!=h.toString().indexOf("EOF"))d=0;else throw h;}g&&fs.closeSync(f);0<d?b=c.slice(0,d).toString("utf-8"):b=null}else"undefined"!=typeof window&&
"function"==typeof window.prompt?(b=window.prompt("Input: "),null!==b&&(b+="\n")):"function"==typeof readline&&(b=readline(),null!==b&&(b+="\n"));if(!b)return null;a.input=tb(b,!0)}return a.input.shift()},ka:function(a,b){null===b||10===b?(ha(ua(a.output,0)),a.output=[]):0!=b&&a.output.push(b)},flush:function(a){a.output&&0<a.output.length&&(ha(ua(a.output,0)),a.output=[])}},vb={ka:function(a,b){null===b||10===b?(u(ua(a.output,0)),a.output=[]):0!=b&&a.output.push(b)},flush:function(a){a.output&&0<
a.output.length&&(u(ua(a.output,0)),a.output=[])}},N={s:null,m:function(){return N.createNode(null,"/",16895,0)},createNode:function(a,b,c,d){if(24576===(c&61440)||4096===(c&61440))throw new M(K.H);N.s||(N.s={dir:{node:{v:N.c.v,l:N.c.l,lookup:N.c.lookup,O:N.c.O,rename:N.c.rename,unlink:N.c.unlink,rmdir:N.c.rmdir,readdir:N.c.readdir,symlink:N.c.symlink},stream:{C:N.f.C}},file:{node:{v:N.c.v,l:N.c.l},stream:{C:N.f.C,read:N.f.read,write:N.f.write,sa:N.f.sa,Ga:N.f.Ga,V:N.f.V}},link:{node:{v:N.c.v,l:N.c.l,
readlink:N.c.readlink},stream:{}},wa:{node:{v:N.c.v,l:N.c.l},stream:wb}});c=xb(a,b,c,d);O(c.mode)?(c.c=N.s.dir.node,c.f=N.s.dir.stream,c.b={}):32768===(c.mode&61440)?(c.c=N.s.file.node,c.f=N.s.file.stream,c.g=0,c.b=null):40960===(c.mode&61440)?(c.c=N.s.link.node,c.f=N.s.link.stream):8192===(c.mode&61440)&&(c.c=N.s.wa.node,c.f=N.s.wa.stream);c.timestamp=Date.now();a&&(a.b[b]=c);return c},Ra:function(a){if(a.b&&a.b.subarray){for(var b=[],c=0;c<a.g;++c)b.push(a.b[c]);return b}return a.b},ed:function(a){return a.b?
a.b.subarray?a.b.subarray(0,a.g):new Uint8Array(a.b):new Uint8Array},za:function(a,b){a.b&&a.b.subarray&&b>a.b.length&&(a.b=N.Ra(a),a.g=a.b.length);if(!a.b||a.b.subarray){var c=a.b?a.b.length:0;c>=b||(b=Math.max(b,c*(1048576>c?2:1.125)|0),0!=c&&(b=Math.max(b,256)),c=a.b,a.b=new Uint8Array(b),0<a.g&&a.b.set(c.subarray(0,a.g),0))}else for(!a.b&&0<b&&(a.b=[]);a.b.length<b;)a.b.push(0)},Xa:function(a,b){if(a.g!=b)if(0==b)a.b=null,a.g=0;else{if(!a.b||a.b.subarray){var c=a.b;a.b=new Uint8Array(new ArrayBuffer(b));
c&&a.b.set(c.subarray(0,Math.min(b,a.g)))}else if(a.b||(a.b=[]),a.b.length>b)a.b.length=b;else for(;a.b.length<b;)a.b.push(0);a.g=b}},c:{v:function(a){var b={};b.dev=8192===(a.mode&61440)?a.id:1;b.ino=a.id;b.mode=a.mode;b.nlink=1;b.uid=0;b.gid=0;b.rdev=a.rdev;O(a.mode)?b.size=4096:32768===(a.mode&61440)?b.size=a.g:40960===(a.mode&61440)?b.size=a.link.length:b.size=0;b.atime=new Date(a.timestamp);b.mtime=new Date(a.timestamp);b.ctime=new Date(a.timestamp);b.I=4096;b.blocks=Math.ceil(b.size/b.I);return b},
l:function(a,b){void 0!==b.mode&&(a.mode=b.mode);void 0!==b.timestamp&&(a.timestamp=b.timestamp);void 0!==b.size&&N.Xa(a,b.size)},lookup:function(){throw yb[K.B];},O:function(a,b,c,d){return N.createNode(a,b,c,d)},rename:function(a,b,c){if(O(a.mode)){try{var d=zb(b,c)}catch(g){}if(d)for(var f in d.b)throw new M(K.pa);}delete a.parent.b[a.name];a.name=c;b.b[c]=a;a.parent=b},unlink:function(a,b){delete a.b[b]},rmdir:function(a,b){var c=zb(a,b),d;for(d in c.b)throw new M(K.pa);delete a.b[b]},readdir:function(a){var b=
[".",".."],c;for(c in a.b)a.b.hasOwnProperty(c)&&b.push(c);return b},symlink:function(a,b,c){a=N.createNode(a,b,41471,0);a.link=c;return a},readlink:function(a){if(40960!==(a.mode&61440))throw new M(K.h);return a.link}},f:{read:function(a,b,c,d,f){var g=a.node.b;if(f>=a.node.g)return 0;a=Math.min(a.node.g-f,d);assert(0<=a);if(8<a&&g.subarray)b.set(g.subarray(f,f+a),c);else for(d=0;d<a;d++)b[c+d]=g[f+d];return a},write:function(a,b,c,d,f,g){if(!d)return 0;a=a.node;a.timestamp=Date.now();if(b.subarray&&
(!a.b||a.b.subarray)){if(g)return a.b=b.subarray(c,c+d),a.g=d;if(0===a.g&&0===f)return a.b=new Uint8Array(b.subarray(c,c+d)),a.g=d;if(f+d<=a.g)return a.b.set(b.subarray(c,c+d),f),d}N.za(a,f+d);if(a.b.subarray&&b.subarray)a.b.set(b.subarray(c,c+d),f);else for(g=0;g<d;g++)a.b[f+g]=b[c+g];a.g=Math.max(a.g,f+d);return d},C:function(a,b,c){1===c?b+=a.position:2===c&&32768===(a.node.mode&61440)&&(b+=a.node.g);if(0>b)throw new M(K.h);return b},sa:function(a,b,c){N.za(a.node,b+c);a.node.g=Math.max(a.node.g,
b+c)},Ga:function(a,b,c,d,f,g,h){if(32768!==(a.node.mode&61440))throw new M(K.da);c=a.node.b;if(h&2||c.buffer!==b&&c.buffer!==b.buffer){if(0<f||f+d<a.node.g)c.subarray?c=c.subarray(f,f+d):c=Array.prototype.slice.call(c,f,f+d);a=!0;d=fb(d);if(!d)throw new M(K.Ka);b.set(c,d)}else a=!1,d=c.byteOffset;return{Wa:d,Na:a}},V:function(a,b,c,d,f){if(32768!==(a.node.mode&61440))throw new M(K.da);if(f&2)return 0;N.f.write(a,b,0,d,c,!1);return 0}}},P={U:!1,$a:function(){P.U=!!process.platform.match(/^win/);var a=
process.binding("constants");a.fs&&(a=a.fs);P.Aa={1024:a.O_APPEND,64:a.O_CREAT,128:a.O_EXCL,0:a.O_RDONLY,2:a.O_RDWR,4096:a.O_SYNC,512:a.O_TRUNC,1:a.O_WRONLY}},ta:function(a){return Buffer.j?Buffer.from(a):new Buffer(a)},m:function(a){assert(p);return P.createNode(null,"/",P.Ca(a.ja.root),0)},createNode:function(a,b,c){if(!O(c)&&32768!==(c&61440)&&40960!==(c&61440))throw new M(K.h);a=xb(a,b,c);a.c=P.c;a.f=P.f;return a},Ca:function(a){try{var b=fs.lstatSync(a);P.U&&(b.mode=b.mode|(b.mode&292)>>2)}catch(c){if(!c.code)throw c;
throw new M(K[c.code]);}return b.mode},o:function(a){for(var b=[];a.parent!==a;)b.push(a.name),a=a.parent;b.push(a.m.ja.root);b.reverse();return mb.apply(null,b)},Qa:function(a){a&=-2656257;var b=0,c;for(c in P.Aa)a&c&&(b|=P.Aa[c],a^=c);if(a)throw new M(K.h);return b},c:{v:function(a){a=P.o(a);try{var b=fs.lstatSync(a)}catch(c){if(!c.code)throw c;throw new M(K[c.code]);}P.U&&!b.I&&(b.I=4096);P.U&&!b.blocks&&(b.blocks=(b.size+b.I-1)/b.I|0);return{dev:b.dev,ino:b.ino,mode:b.mode,nlink:b.nlink,uid:b.uid,
gid:b.gid,rdev:b.rdev,size:b.size,atime:b.atime,mtime:b.mtime,ctime:b.ctime,I:b.I,blocks:b.blocks}},l:function(a,b){var c=P.o(a);try{void 0!==b.mode&&(fs.chmodSync(c,b.mode),a.mode=b.mode),void 0!==b.size&&fs.truncateSync(c,b.size)}catch(d){if(!d.code)throw d;throw new M(K[d.code]);}},lookup:function(a,b){var c=L(P.o(a),b);c=P.Ca(c);return P.createNode(a,b,c)},O:function(a,b,c,d){a=P.createNode(a,b,c,d);b=P.o(a);try{O(a.mode)?fs.mkdirSync(b,a.mode):fs.writeFileSync(b,"",{mode:a.mode})}catch(f){if(!f.code)throw f;
throw new M(K[f.code]);}return a},rename:function(a,b,c){a=P.o(a);b=L(P.o(b),c);try{fs.renameSync(a,b)}catch(d){if(!d.code)throw d;throw new M(K[d.code]);}},unlink:function(a,b){a=L(P.o(a),b);try{fs.unlinkSync(a)}catch(c){if(!c.code)throw c;throw new M(K[c.code]);}},rmdir:function(a,b){a=L(P.o(a),b);try{fs.rmdirSync(a)}catch(c){if(!c.code)throw c;throw new M(K[c.code]);}},readdir:function(a){a=P.o(a);try{return fs.readdirSync(a)}catch(b){if(!b.code)throw b;throw new M(K[b.code]);}},symlink:function(a,
b,c){a=L(P.o(a),b);try{fs.symlinkSync(c,a)}catch(d){if(!d.code)throw d;throw new M(K[d.code]);}},readlink:function(a){var b=P.o(a);try{return b=fs.readlinkSync(b),b=Ab.relative(Ab.resolve(a.m.ja.root),b)}catch(c){if(!c.code)throw c;throw new M(K[c.code]);}}},f:{open:function(a){var b=P.o(a.node);try{32768===(a.node.mode&61440)&&(a.R=fs.openSync(b,P.Qa(a.flags)))}catch(c){if(!c.code)throw c;throw new M(K[c.code]);}},close:function(a){try{32768===(a.node.mode&61440)&&a.R&&fs.closeSync(a.R)}catch(b){if(!b.code)throw b;
throw new M(K[b.code]);}},read:function(a,b,c,d,f){if(0===d)return 0;try{return fs.readSync(a.R,P.ta(b.buffer),c,d,f)}catch(g){throw new M(K[g.code]);}},write:function(a,b,c,d,f){try{return fs.writeSync(a.R,P.ta(b.buffer),c,d,f)}catch(g){throw new M(K[g.code]);}},C:function(a,b,c){if(1===c)b+=a.position;else if(2===c&&32768===(a.node.mode&61440))try{b+=fs.fstatSync(a.R).size}catch(d){throw new M(K[d.code]);}if(0>b)throw new M(K.h);return b}}};v+=16;v+=16;v+=16;
var Bb=null,Cb={},Q=[],Db=1,R=null,Eb=!0,S={},M=null,yb={};
function T(a,b){a=nb("/",a);b=b||{};if(!a)return{path:"",node:null};var c={Ba:!0,la:0},d;for(d in c)void 0===b[d]&&(b[d]=c[d]);if(8<b.la)throw new M(K.ba);a=ib(a.split("/").filter(function(a){return!!a}),!1);var f=Bb;c="/";for(d=0;d<a.length;d++){var g=d===a.length-1;if(g&&b.parent)break;f=zb(f,a[d]);c=L(c,a[d]);f.P&&(!g||g&&b.Ba)&&(f=f.P.root);if(!g||b.ga)for(g=0;40960===(f.mode&61440);)if(f=Fb(c),c=nb(kb(c),f),f=T(c,{la:b.la}).node,40<g++)throw new M(K.ba);}return{path:c,node:f}}
function U(a){for(var b;;){if(a===a.parent)return a=a.m.Ha,b?"/"!==a[a.length-1]?a+"/"+b:a+b:a;b=b?a.name+"/"+b:a.name;a=a.parent}}function Gb(a,b){for(var c=0,d=0;d<b.length;d++)c=(c<<5)-c+b.charCodeAt(d)|0;return(a+c>>>0)%R.length}function Hb(a){var b=Gb(a.parent.id,a.name);a.L=R[b];R[b]=a}function zb(a,b){var c;if(c=(c=Ib(a,"x"))?c:a.c.lookup?0:K.$)throw new M(c,a);for(c=R[Gb(a.id,b)];c;c=c.L){var d=c.name;if(c.parent.id===a.id&&d===b)return c}return a.c.lookup(a,b)}
function xb(a,b,c,d){Jb||(Jb=function(a,b,c,d){a||(a=this);this.parent=a;this.m=a.m;this.P=null;this.id=Db++;this.name=b;this.mode=c;this.c={};this.f={};this.rdev=d},Jb.prototype={},Object.defineProperties(Jb.prototype,{read:{get:function(){return 365===(this.mode&365)},set:function(a){a?this.mode|=365:this.mode&=-366}},write:{get:function(){return 146===(this.mode&146)},set:function(a){a?this.mode|=146:this.mode&=-147}},Ua:{get:function(){return O(this.mode)}},Ta:{get:function(){return 8192===(this.mode&
61440)}}}));a=new Jb(a,b,c,d);Hb(a);return a}function O(a){return 16384===(a&61440)}var Kb={r:0,rs:1052672,"r+":2,w:577,wx:705,xw:705,"w+":578,"wx+":706,"xw+":706,a:1089,ax:1217,xa:1217,"a+":1090,"ax+":1218,"xa+":1218};function Lb(a){var b=["r","w","rw"][a&3];a&512&&(b+="w");return b}function Ib(a,b){if(Eb)return 0;if(-1===b.indexOf("r")||a.mode&292){if(-1!==b.indexOf("w")&&!(a.mode&146)||-1!==b.indexOf("x")&&!(a.mode&73))return K.$}else return K.$;return 0}
function Mb(a,b){try{return zb(a,b),K.na}catch(c){}return Ib(a,"wx")}function Nb(a){var b=4096;for(a=a||0;a<=b;a++)if(!Q[a])return a;throw new M(K.Ja);}function Ob(a,b){Pb||(Pb=function(){},Pb.prototype={},Object.defineProperties(Pb.prototype,{object:{get:function(){return this.node},set:function(a){this.node=a}}}));var c=new Pb,d;for(d in a)c[d]=a[d];a=c;b=Nb(b);a.fd=b;return Q[b]=a}var wb={open:function(a){a.f=Cb[a.node.rdev].f;a.f.open&&a.f.open(a)},C:function(){throw new M(K.S);}};
function rb(a,b){Cb[a]={f:b}}function Qb(a,b){var c="/"===b,d=!b;if(c&&Bb)throw new M(K.aa);if(!c&&!d){var f=T(b,{Ba:!1});b=f.path;f=f.node;if(f.P)throw new M(K.aa);if(!O(f.mode))throw new M(K.oa);}b={type:a,ja:{},Ha:b,Va:[]};a=a.m(b);a.m=b;b.root=a;c?Bb=a:f&&(f.P=b,f.m&&f.m.Va.push(b))}function Rb(a,b,c){var d=T(a,{parent:!0}).node;a=lb(a);if(!a||"."===a||".."===a)throw new M(K.h);var f=Mb(d,a);if(f)throw new M(f);if(!d.c.O)throw new M(K.H);return d.c.O(d,a,b,c)}
function V(a,b){return Rb(a,(void 0!==b?b:511)&1023|16384,0)}function Sb(a,b,c){"undefined"===typeof c&&(c=b,b=438);return Rb(a,b|8192,c)}function Tb(a,b){if(!nb(a))throw new M(K.B);var c=T(b,{parent:!0}).node;if(!c)throw new M(K.B);b=lb(b);var d=Mb(c,b);if(d)throw new M(d);if(!c.c.symlink)throw new M(K.H);return c.c.symlink(c,b,a)}
function Ub(a){var b=T(a,{parent:!0}).node,c=lb(a),d=zb(b,c);a:{try{var f=zb(b,c)}catch(h){f=h.u;break a}var g=Ib(b,"wx");f=g?g:O(f.mode)?K.N:0}if(f)throw new M(f);if(!b.c.unlink)throw new M(K.H);if(d.P)throw new M(K.aa);try{S.willDeletePath&&S.willDeletePath(a)}catch(h){console.log("FS.trackingDelegate['willDeletePath']('"+a+"') threw an exception: "+h.message)}b.c.unlink(b,c);b=Gb(d.parent.id,d.name);if(R[b]===d)R[b]=d.L;else for(b=R[b];b;){if(b.L===d){b.L=d.L;break}b=b.L}try{if(S.onDeletePath)S.onDeletePath(a)}catch(h){console.log("FS.trackingDelegate['onDeletePath']('"+
a+"') threw an exception: "+h.message)}}function Fb(a){a=T(a).node;if(!a)throw new M(K.B);if(!a.c.readlink)throw new M(K.h);return nb(U(a.parent),a.c.readlink(a))}function Vb(a,b){var c;"string"===typeof a?c=T(a,{ga:!0}).node:c=a;if(!c.c.l)throw new M(K.H);c.c.l(c,{mode:b&4095|c.mode&-4096,timestamp:Date.now()})}
function Wb(a,b,c,d){if(""===a)throw new M(K.B);if("string"===typeof b){var f=Kb[b];if("undefined"===typeof f)throw Error("Unknown file open mode: "+b);b=f}c=b&64?("undefined"===typeof c?438:c)&4095|32768:0;if("object"===typeof a)var g=a;else{a=jb(a);try{g=T(a,{ga:!(b&131072)}).node}catch(n){}}f=!1;if(b&64)if(g){if(b&128)throw new M(K.na);}else g=Rb(a,c,0),f=!0;if(!g)throw new M(K.B);8192===(g.mode&61440)&&(b&=-513);if(b&65536&&!O(g.mode))throw new M(K.oa);if(!f){var h=g?40960===(g.mode&61440)?K.ba:
O(g.mode)&&("r"!==Lb(b)||b&512)?K.N:Ib(g,Lb(b)):K.B;if(h)throw new M(h);}if(b&512){c=g;var l;"string"===typeof c?l=T(c,{ga:!0}).node:l=c;if(!l.c.l)throw new M(K.H);if(O(l.mode))throw new M(K.N);if(32768!==(l.mode&61440))throw new M(K.h);if(c=Ib(l,"w"))throw new M(c);l.c.l(l,{size:0,timestamp:Date.now()})}b&=-641;d=Ob({node:g,path:U(g),flags:b,seekable:!0,position:0,f:g.f,fb:[],error:!1},d);d.f.open&&d.f.open(d);!e.logReadFiles||b&1||(Xb||(Xb={}),a in Xb||(Xb[a]=1,h("read file: "+a)));try{S.onOpenFile&&
(h=0,1!==(b&2097155)&&(h|=1),0!==(b&2097155)&&(h|=2),S.onOpenFile(a,h))}catch(n){console.log("FS.trackingDelegate['onOpenFile']('"+a+"', flags) threw an exception: "+n.message)}return d}function Yb(a){if(null===a.fd)throw new M(K.A);a.ha&&(a.ha=null);try{a.f.close&&a.f.close(a)}catch(b){throw b;}finally{Q[a.fd]=null}a.fd=null}function Zb(a,b,c){if(null===a.fd)throw new M(K.A);if(!a.seekable||!a.f.C)throw new M(K.S);a.position=a.f.C(a,b,c);a.fb=[]}
function $b(a,b,c,d,f,g){if(0>d||0>f)throw new M(K.h);if(null===a.fd)throw new M(K.A);if(0===(a.flags&2097155))throw new M(K.A);if(O(a.node.mode))throw new M(K.N);if(!a.f.write)throw new M(K.h);a.flags&1024&&Zb(a,0,2);var h="undefined"!==typeof f;if(!h)f=a.position;else if(!a.seekable)throw new M(K.S);b=a.f.write(a,b,c,d,f,g);h||(a.position+=b);try{if(a.path&&S.onWriteToFile)S.onWriteToFile(a.path)}catch(l){console.log("FS.trackingDelegate['onWriteToFile']('"+path+"') threw an exception: "+l.message)}return b}
function ac(){M||(M=function(a,b){this.node=b;this.Za=function(a){this.u=a;for(var b in K)if(K[b]===a){this.code=b;break}};this.Za(a);this.message=hb[a];this.stack&&Object.defineProperty(this,"stack",{value:Error().stack,writable:!0})},M.prototype=Error(),M.prototype.constructor=M,[K.B].forEach(function(a){yb[a]=new M(a);yb[a].stack="<generic error, no stack>"}))}var bc;function cc(a,b){var c=0;a&&(c|=365);b&&(c|=146);return c}
function dc(a,b,c,d){a=L("string"===typeof a?a:U(a),b);return V(a,cc(c,d))}function ec(a,b){a="string"===typeof a?a:U(a);for(b=b.split("/").reverse();b.length;){var c=b.pop();if(c){var d=L(a,c);try{V(d)}catch(f){}a=d}}return d}function fc(a,b,c,d){a=L("string"===typeof a?a:U(a),b);c=cc(c,d);return Rb(a,(void 0!==c?c:438)&4095|32768,0)}
function hc(a,b,c,d,f,g){a=b?L("string"===typeof a?a:U(a),b):a;d=cc(d,f);f=Rb(a,(void 0!==d?d:438)&4095|32768,0);if(c){if("string"===typeof c){a=Array(c.length);b=0;for(var h=c.length;b<h;++b)a[b]=c.charCodeAt(b);c=a}Vb(f,d|146);a=Wb(f,"w");$b(a,c,0,c.length,0,g);Yb(a);Vb(f,d)}return f}
function W(a,b,c,d){a=L("string"===typeof a?a:U(a),b);b=cc(!!c,!!d);W.Fa||(W.Fa=64);var f=W.Fa++<<8|0;rb(f,{open:function(a){a.seekable=!1},close:function(){d&&d.buffer&&d.buffer.length&&d(10)},read:function(a,b,d,f){for(var g=0,h=0;h<f;h++){try{var l=c()}catch(B){throw new M(K.F);}if(void 0===l&&0===g)throw new M(K.ma);if(null===l||void 0===l)break;g++;b[d+h]=l}g&&(a.node.timestamp=Date.now());return g},write:function(a,b,c,f){for(var g=0;g<f;g++)try{d(b[c+g])}catch(w){throw new M(K.F);}f&&(a.node.timestamp=
Date.now());return g}});return Sb(a,b,f)}function ic(a,b,c){a=L("string"===typeof a?a:U(a),b);return Tb(c,a)}
function jc(a){if(a.Ta||a.Ua||a.link||a.b)return!0;var b=!0;if("undefined"!==typeof XMLHttpRequest)throw Error("Lazy loading should have been performed (contents set) in createLazyFile, but it was not. Lazy loading only works in web workers. Use --embed-file or --preload-file in emcc on the main thread.");if(e.read)try{a.b=tb(e.read(a.url),!0),a.g=a.b.length}catch(c){b=!1}else throw Error("Cannot load without read() or XMLHttpRequest.");b||gb(K.F);return b}
function kc(a,b,c,d,f){function g(){this.ia=!1;this.T=[]}g.prototype.get=function(a){if(!(a>this.length-1||0>a)){var b=a%this.chunkSize;return this.Ea(a/this.chunkSize|0)[b]}};g.prototype.Ya=function(a){this.Ea=a};g.prototype.ua=function(){var a=new XMLHttpRequest;a.open("HEAD",c,!1);a.send(null);if(!(200<=a.status&&300>a.status||304===a.status))throw Error("Couldn't load "+c+". Status: "+a.status);var b=Number(a.getResponseHeader("Content-length")),d,f=(d=a.getResponseHeader("Accept-Ranges"))&&"bytes"===
d;a=(d=a.getResponseHeader("Content-Encoding"))&&"gzip"===d;var g=1048576;f||(g=b);var h=this;h.Ya(function(a){var d=a*g,f=(a+1)*g-1;f=Math.min(f,b-1);if("undefined"===typeof h.T[a]){var z=h.T;if(d>f)throw Error("invalid range ("+d+", "+f+") or no bytes requested!");if(f>b-1)throw Error("only "+b+" bytes available! programmer error!");var l=new XMLHttpRequest;l.open("GET",c,!1);b!==g&&l.setRequestHeader("Range","bytes="+d+"-"+f);"undefined"!=typeof Uint8Array&&(l.responseType="arraybuffer");l.overrideMimeType&&
l.overrideMimeType("text/plain; charset=x-user-defined");l.send(null);if(!(200<=l.status&&300>l.status||304===l.status))throw Error("Couldn't load "+c+". Status: "+l.status);d=void 0!==l.response?new Uint8Array(l.response||[]):tb(l.responseText||"",!0);z[a]=d}if("undefined"===typeof h.T[a])throw Error("doXHR failed!");return h.T[a]});if(a||!b)g=b=1,g=b=this.Ea(0).length,console.log("LazyFiles on gzip forces download of the whole file when length is accessed");this.Ma=b;this.La=g;this.ia=!0};if("undefined"!==
typeof XMLHttpRequest){if(!m)throw"Cannot do synchronous binary XHRs outside webworkers in modern browsers. Use --embed-file or --preload-file in emcc";var h=new g;Object.defineProperties(h,{length:{get:function(){this.ia||this.ua();return this.Ma}},chunkSize:{get:function(){this.ia||this.ua();return this.La}}});var l=void 0}else l=c,h=void 0;var n=fc(a,b,d,f);h?n.b=h:l&&(n.b=null,n.url=l);Object.defineProperties(n,{g:{get:function(){return this.b.length}}});var q={};Object.keys(n.f).forEach(function(a){var b=
n.f[a];q[a]=function(){if(!jc(n))throw new M(K.F);return b.apply(null,arguments)}});q.read=function(a,b,c,d,f){if(!jc(n))throw new M(K.F);a=a.node.b;if(f>=a.length)return 0;d=Math.min(a.length-f,d);assert(0<=d);if(a.slice)for(var g=0;g<d;g++)b[c+g]=a[f+g];else for(g=0;g<d;g++)b[c+g]=a.get(f+g);return d};n.f=q;return n}
function lc(a,b,c,d,f,g,h,l,n,q){function w(c){function z(c){q&&q();l||hc(a,b,c,d,f,n);g&&g();Ra()}var I=!1;e.preloadPlugins.forEach(function(a){!I&&a.canHandle(A)&&(a.handle(c,A,z,function(){h&&h();Ra()}),I=!0)});I||z(c)}Browser.gd();var A=b?nb(L(a,b)):a;Qa();"string"==typeof c?Browser.dd(c,function(a){w(a)},h):w(c)}var FS={},Jb,Pb,Xb,mc={},X=0;function Y(){X+=4;return E[X-4>>2]}function nc(){var a=Q[Y()];if(!a)throw new M(K.A);return a}var oc={};
function pc(a){if(0===a)return 0;a=D(a);if(!oc.hasOwnProperty(a))return 0;pc.j&&bb(pc.j);a=oc[a];var b=va(a)+1,c=fb(b);c&&pa(a,oa,c,b);pc.j=c;return pc.j}function Z(){Z.j||(Z.j=[]);Z.j.push(x());return Z.j.length-1}var qc={},rc=1;function sc(a,b){sc.j||(sc.j={});a in sc.j||(e.dynCall_v(b),sc.j[a]=1)}function tc(a){return 0===a%4&&(0!==a%100||0===a%400)}function uc(a,b){for(var c=0,d=0;d<=b;c+=a[d++]);return c}var vc=[31,29,31,30,31,30,31,31,30,31,30,31],wc=[31,28,31,30,31,30,31,31,30,31,30,31];
function xc(a,b){for(a=new Date(a.getTime());0<b;){var c=a.getMonth(),d=(tc(a.getFullYear())?vc:wc)[c];if(b>d-a.getDate())b-=d-a.getDate()+1,a.setDate(1),11>c?a.setMonth(c+1):(a.setMonth(0),a.setFullYear(a.getFullYear()+1));else{a.setDate(a.getDate()+b);break}}return a}
function yc(a,b,c,d){function f(a,b,c){for(a="number"===typeof a?a.toString():a||"";a.length<b;)a=c[0]+a;return a}function g(a,b){return f(a,b,"0")}function h(a,b){function c(a){return 0>a?-1:0<a?1:0}var d;0===(d=c(a.getFullYear()-b.getFullYear()))&&0===(d=c(a.getMonth()-b.getMonth()))&&(d=c(a.getDate()-b.getDate()));return d}function l(a){switch(a.getDay()){case 0:return new Date(a.getFullYear()-1,11,29);case 1:return a;case 2:return new Date(a.getFullYear(),0,3);case 3:return new Date(a.getFullYear(),
0,2);case 4:return new Date(a.getFullYear(),0,1);case 5:return new Date(a.getFullYear()-1,11,31);case 6:return new Date(a.getFullYear()-1,11,30)}}function n(a){a=xc(new Date(a.i+1900,0,1),a.Z);var b=l(new Date(a.getFullYear()+1,0,4));return 0>=h(l(new Date(a.getFullYear(),0,4)),a)?0>=h(b,a)?a.getFullYear()+1:a.getFullYear():a.getFullYear()-1}var q=E[d+40>>2];d={cb:E[d>>2],bb:E[d+4>>2],Y:E[d+8>>2],J:E[d+12>>2],D:E[d+16>>2],i:E[d+20>>2],Ia:E[d+24>>2],Z:E[d+28>>2],kd:E[d+32>>2],ab:E[d+36>>2],eb:q?D(q):
""};c=D(c);q={"%c":"%a %b %d %H:%M:%S %Y","%D":"%m/%d/%y","%F":"%Y-%m-%d","%h":"%b","%r":"%I:%M:%S %p","%R":"%H:%M","%T":"%H:%M:%S","%x":"%m/%d/%y","%X":"%H:%M:%S"};for(var w in q)c=c.replace(new RegExp(w,"g"),q[w]);var A="Sunday Monday Tuesday Wednesday Thursday Friday Saturday".split(" "),B="January February March April May June July August September October November December".split(" ");q={"%a":function(a){return A[a.Ia].substring(0,3)},"%A":function(a){return A[a.Ia]},"%b":function(a){return B[a.D].substring(0,
3)},"%B":function(a){return B[a.D]},"%C":function(a){return g((a.i+1900)/100|0,2)},"%d":function(a){return g(a.J,2)},"%e":function(a){return f(a.J,2," ")},"%g":function(a){return n(a).toString().substring(2)},"%G":function(a){return n(a)},"%H":function(a){return g(a.Y,2)},"%I":function(a){a=a.Y;0==a?a=12:12<a&&(a-=12);return g(a,2)},"%j":function(a){return g(a.J+uc(tc(a.i+1900)?vc:wc,a.D-1),3)},"%m":function(a){return g(a.D+1,2)},"%M":function(a){return g(a.bb,2)},"%n":function(){return"\n"},"%p":function(a){return 0<=
a.Y&&12>a.Y?"AM":"PM"},"%S":function(a){return g(a.cb,2)},"%t":function(){return"\t"},"%u":function(a){return(new Date(a.i+1900,a.D+1,a.J,0,0,0,0)).getDay()||7},"%U":function(a){var b=new Date(a.i+1900,0,1),c=0===b.getDay()?b:xc(b,7-b.getDay());a=new Date(a.i+1900,a.D,a.J);return 0>h(c,a)?g(Math.ceil((31-c.getDate()+(uc(tc(a.getFullYear())?vc:wc,a.getMonth()-1)-31)+a.getDate())/7),2):0===h(c,b)?"01":"00"},"%V":function(a){var b=l(new Date(a.i+1900,0,4)),c=l(new Date(a.i+1901,0,4)),d=xc(new Date(a.i+
1900,0,1),a.Z);return 0>h(d,b)?"53":0>=h(c,d)?"01":g(Math.ceil((b.getFullYear()<a.i+1900?a.Z+32-b.getDate():a.Z+1-b.getDate())/7),2)},"%w":function(a){return(new Date(a.i+1900,a.D+1,a.J,0,0,0,0)).getDay()},"%W":function(a){var b=new Date(a.i,0,1),c=1===b.getDay()?b:xc(b,0===b.getDay()?1:7-b.getDay()+1);a=new Date(a.i+1900,a.D,a.J);return 0>h(c,a)?g(Math.ceil((31-c.getDate()+(uc(tc(a.getFullYear())?vc:wc,a.getMonth()-1)-31)+a.getDate())/7),2):0===h(c,b)?"01":"00"},"%y":function(a){return(a.i+1900).toString().substring(2)},
"%Y":function(a){return a.i+1900},"%z":function(a){a=a.ab;var b=0<=a;a=Math.abs(a)/60;return(b?"+":"-")+String("0000"+(a/60*100+a%60)).slice(-4)},"%Z":function(a){return a.eb},"%%":function(){return"%"}};for(w in q)0<=c.indexOf(w)&&(c=c.replace(new RegExp(w,"g"),q[w](d)));w=tb(c,!1);if(w.length>b)return 0;oa.set(w,a);return w.length-1}ac();R=Array(4096);Qb(N,"/");V("/tmp");V("/home");V("/home/web_user");
(function(){V("/dev");rb(259,{read:function(){return 0},write:function(a,b,f,g){return g}});Sb("/dev/null",259);pb(1280,ub);pb(1536,vb);Sb("/dev/tty",1280);Sb("/dev/tty1",1536);if("undefined"!==typeof crypto){var a=new Uint8Array(1);var b=function(){crypto.getRandomValues(a);return a[0]}}else p?b=function(){return require("crypto").randomBytes(1)[0]}:b=function(){t("random_device")};W("/dev","random",b);W("/dev","urandom",b);V("/dev/shm");V("/dev/shm/tmp")})();V("/proc");V("/proc/self");V("/proc/self/fd");
Qb({m:function(){var a=xb("/proc/self","fd",16895,73);a.c={lookup:function(a,c){var b=Q[+c];if(!b)throw new M(K.A);a={parent:null,m:{Ha:"fake"},c:{readlink:function(){return b.path}}};return a.parent=a}};return a}},"/proc/self/fd");
Ia.unshift(function(){if(!e.noFSInit&&!bc){assert(!bc,"FS.init was previously called. If you want to initialize later with custom parameters, remove any earlier calls (note that one is automatically added to the generated code)");bc=!0;ac();e.stdin=e.stdin;e.stdout=e.stdout;e.stderr=e.stderr;e.stdin?W("/dev","stdin",e.stdin):Tb("/dev/tty","/dev/stdin");e.stdout?W("/dev","stdout",null,e.stdout):Tb("/dev/tty","/dev/stdout");e.stderr?W("/dev","stderr",null,e.stderr):Tb("/dev/tty1","/dev/stderr");var a=
Wb("/dev/stdin","r");assert(0===a.fd,"invalid handle for stdin ("+a.fd+")");a=Wb("/dev/stdout","w");assert(1===a.fd,"invalid handle for stdout ("+a.fd+")");a=Wb("/dev/stderr","w");assert(2===a.fd,"invalid handle for stderr ("+a.fd+")")}});Ja.push(function(){Eb=!1});Ka.push(function(){bc=!1;var a=e._fflush;a&&a(0);for(a=0;a<Q.length;a++){var b=Q[a];b&&Yb(b)}});e.FS_createFolder=dc;e.FS_createPath=ec;e.FS_createDataFile=hc;e.FS_createPreloadedFile=lc;e.FS_createLazyFile=kc;e.FS_createLink=ic;
e.FS_createDevice=W;e.FS_unlink=Ub;Ia.unshift(function(){});Ka.push(function(){});if(p){var fs=require("fs"),Ab=require("path");P.$a()}F=ia(4);Aa=Ba=ja(v);Ca=Aa+Fa;Da=ja(Ca);E[F>>2]=Da;za=!0;function tb(a,b){var c=Array(va(a)+1);a=pa(a,c,0,c.length);b&&(c.length=a);return c}e.wasmTableSize=1498;e.wasmMaxTableSize=1498;e.Oa={};
e.Pa={abort:t,enlargeMemory:function(){Ea()},getTotalMemory:function(){return G},abortOnCannotGrowMemory:Ea,invoke_ffi:function(a,b,c){var d=x();try{return e.dynCall_ffi(a,b,c)}catch(f){y(d);if("number"!==typeof f&&"longjmp"!==f)throw f;e.setThrew(1,0)}},invoke_i:function(a){var b=x();try{return e.dynCall_i(a)}catch(c){y(b);if("number"!==typeof c&&"longjmp"!==c)throw c;e.setThrew(1,0)}},invoke_ii:function(a,b){var c=x();try{return e.dynCall_ii(a,b)}catch(d){y(c);if("number"!==typeof d&&"longjmp"!==
d)throw d;e.setThrew(1,0)}},invoke_iii:function(a,b,c){var d=x();try{return e.dynCall_iii(a,b,c)}catch(f){y(d);if("number"!==typeof f&&"longjmp"!==f)throw f;e.setThrew(1,0)}},invoke_iiii:function(a,b,c,d){var f=x();try{return e.dynCall_iiii(a,b,c,d)}catch(g){y(f);if("number"!==typeof g&&"longjmp"!==g)throw g;e.setThrew(1,0)}},invoke_iiiii:function(a,b,c,d,f){var g=x();try{return e.dynCall_iiiii(a,b,c,d,f)}catch(h){y(g);if("number"!==typeof h&&"longjmp"!==h)throw h;e.setThrew(1,0)}},invoke_iiiiii:function(a,
b,c,d,f,g){var h=x();try{return e.dynCall_iiiiii(a,b,c,d,f,g)}catch(l){y(h);if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_iiiiiii:function(a,b,c,d,f,g,h){var l=x();try{return e.dynCall_iiiiiii(a,b,c,d,f,g,h)}catch(n){y(l);if("number"!==typeof n&&"longjmp"!==n)throw n;e.setThrew(1,0)}},invoke_iiiiiiii:function(a,b,c,d,f,g,h,l){var n=x();try{return e.dynCall_iiiiiiii(a,b,c,d,f,g,h,l)}catch(q){y(n);if("number"!==typeof q&&"longjmp"!==q)throw q;e.setThrew(1,0)}},invoke_iiiiiiiii:function(a,
b,c,d,f,g,h,l,n){var q=x();try{return e.dynCall_iiiiiiiii(a,b,c,d,f,g,h,l,n)}catch(w){y(q);if("number"!==typeof w&&"longjmp"!==w)throw w;e.setThrew(1,0)}},invoke_iiiiiiiiiiii:function(a,b,c,d,f,g,h,l,n,q,w,A){var B=x();try{return e.dynCall_iiiiiiiiiiii(a,b,c,d,f,g,h,l,n,q,w,A)}catch(z){y(B);if("number"!==typeof z&&"longjmp"!==z)throw z;e.setThrew(1,0)}},invoke_v:function(a){var b=x();try{e.dynCall_v(a)}catch(c){y(b);if("number"!==typeof c&&"longjmp"!==c)throw c;e.setThrew(1,0)}},invoke_vi:function(a,
b){var c=x();try{e.dynCall_vi(a,b)}catch(d){y(c);if("number"!==typeof d&&"longjmp"!==d)throw d;e.setThrew(1,0)}},invoke_viddd:function(a,b,c,d,f){var g=x();try{e.dynCall_viddd(a,b,c,d,f)}catch(h){y(g);if("number"!==typeof h&&"longjmp"!==h)throw h;e.setThrew(1,0)}},invoke_vidddd:function(a,b,c,d,f,g){var h=x();try{e.dynCall_vidddd(a,b,c,d,f,g)}catch(l){y(h);if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_vididd:function(a,b,c,d,f,g){var h=x();try{e.dynCall_vididd(a,b,c,d,f,g)}catch(l){y(h);
if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_vidiii:function(a,b,c,d,f,g){var h=x();try{e.dynCall_vidiii(a,b,c,d,f,g)}catch(l){y(h);if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_vii:function(a,b,c){var d=x();try{e.dynCall_vii(a,b,c)}catch(f){y(d);if("number"!==typeof f&&"longjmp"!==f)throw f;e.setThrew(1,0)}},invoke_viid:function(a,b,c,d){var f=x();try{e.dynCall_viid(a,b,c,d)}catch(g){y(f);if("number"!==typeof g&&"longjmp"!==g)throw g;e.setThrew(1,
0)}},invoke_viiddd:function(a,b,c,d,f,g){var h=x();try{e.dynCall_viiddd(a,b,c,d,f,g)}catch(l){y(h);if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_viii:function(a,b,c,d){var f=x();try{e.dynCall_viii(a,b,c,d)}catch(g){y(f);if("number"!==typeof g&&"longjmp"!==g)throw g;e.setThrew(1,0)}},invoke_viiii:function(a,b,c,d,f){var g=x();try{e.dynCall_viiii(a,b,c,d,f)}catch(h){y(g);if("number"!==typeof h&&"longjmp"!==h)throw h;e.setThrew(1,0)}},invoke_viiiii:function(a,b,c,d,f,g){var h=
x();try{e.dynCall_viiiii(a,b,c,d,f,g)}catch(l){y(h);if("number"!==typeof l&&"longjmp"!==l)throw l;e.setThrew(1,0)}},invoke_viiiiii:function(a,b,c,d,f,g,h){var l=x();try{e.dynCall_viiiiii(a,b,c,d,f,g,h)}catch(n){y(l);if("number"!==typeof n&&"longjmp"!==n)throw n;e.setThrew(1,0)}},invoke_viiiiiii:function(a,b,c,d,f,g,h,l){var n=x();try{e.dynCall_viiiiiii(a,b,c,d,f,g,h,l)}catch(q){y(n);if("number"!==typeof q&&"longjmp"!==q)throw q;e.setThrew(1,0)}},invoke_viiiiiiiiii:function(a,b,c,d,f,g,h,l,n,q,w){var A=
x();try{e.dynCall_viiiiiiiiii(a,b,c,d,f,g,h,l,n,q,w)}catch(B){y(A);if("number"!==typeof B&&"longjmp"!==B)throw B;e.setThrew(1,0)}},invoke_viiiiiiiiiiiiiii:function(a,b,c,d,f,g,h,l,n,q,w,A,B,z,I,ka){var Bc=x();try{e.dynCall_viiiiiiiiiiiiiii(a,b,c,d,f,g,h,l,n,q,w,A,B,z,I,ka)}catch(qb){y(Bc);if("number"!==typeof qb&&"longjmp"!==qb)throw qb;e.setThrew(1,0)}},invoke_viijii:function(a,b,c,d,f,g,h){var l=x();try{e.dynCall_viijii(a,b,c,d,f,g,h)}catch(n){y(l);if("number"!==typeof n&&"longjmp"!==n)throw n;
e.setThrew(1,0)}},___assert_fail:function(a,b,c,d){t("Assertion failed: "+D(a)+", at: "+[b?D(b):"unknown filename",c,d?D(d):"unknown function"])},___cxa_allocate_exception:function(a){return fb(a)},___cxa_begin_catch:function(a){var b=J[a];b&&!b.va&&(b.va=!0,zc.fa--);b&&(b.X=!1);$a.push(a);(b=ab(a))&&J[b].W++;return a},___cxa_end_catch:function(){e.setThrew(0);var a=$a.pop();if(a){if(a=ab(a)){var b=J[a];assert(0<b.W);b.W--;0!==b.W||b.X||(b.ya&&e.dynCall_vi(b.ya,a),delete J[a],___cxa_free_exception(a))}Za=
0}},___cxa_find_matching_catch_2:function(){return cb.apply(null,arguments)},___cxa_find_matching_catch_3:function(){return cb.apply(null,arguments)},___cxa_find_matching_catch_4:function(){return cb.apply(null,arguments)},___cxa_free_exception:___cxa_free_exception,___cxa_pure_virtual:function(){ma=!0;throw"Pure virtual function called!";},___cxa_rethrow:function(){var a=$a.pop();a=ab(a);J[a].X||($a.push(a),J[a].X=!0);Za=a;throw a;},___cxa_throw:function(a,b,c){J[a]={Wa:a,ra:a,type:b,ya:c,W:0,va:!1,
X:!1};Za=a;"uncaught_exception"in zc?zc.fa++:zc.fa=1;throw a;},___cxa_uncaught_exception:function(){return!!zc.fa},___lock:function(){},___map_file:function(){gb(K.H);return-1},___resumeException:function(a){Za||(Za=a);throw a;},___setErrNo:gb,___syscall140:function(a,b){X=b;try{var c=nc();Y();var d=Y(),f=Y(),g=Y();Zb(c,d,g);E[f>>2]=c.position;c.ha&&0===d&&0===g&&(c.ha=null);return 0}catch(h){return"undefined"!==typeof FS&&h instanceof M||t(h),-h.u}},___syscall145:function(a,b){X=b;try{var c=nc(),
d=Y();a:{var f=Y();for(b=a=0;b<f;b++){var g=E[d+(8*b+4)>>2],h=c,l=E[d+8*b>>2],n=g,q=void 0,w=oa;if(0>n||0>q)throw new M(K.h);if(null===h.fd)throw new M(K.A);if(1===(h.flags&2097155))throw new M(K.A);if(O(h.node.mode))throw new M(K.N);if(!h.f.read)throw new M(K.h);var A="undefined"!==typeof q;if(!A)q=h.position;else if(!h.seekable)throw new M(K.S);var B=h.f.read(h,w,l,n,q);A||(h.position+=B);var z=B;if(0>z){var I=-1;break a}a+=z;if(z<g)break}I=a}return I}catch(ka){return"undefined"!==typeof FS&&ka instanceof
M||t(ka),-ka.u}},___syscall146:function(a,b){X=b;try{var c=nc(),d=Y();a:{var f=Y();for(b=a=0;b<f;b++){var g=$b(c,oa,E[d+8*b>>2],E[d+(8*b+4)>>2],void 0);if(0>g){var h=-1;break a}a+=g}h=a}return h}catch(l){return"undefined"!==typeof FS&&l instanceof M||t(l),-l.u}},___syscall221:function(a,b){X=b;try{var c=nc();switch(Y()){case 0:var d=Y();return 0>d?-K.h:Wb(c.path,c.flags,0,d).fd;case 1:case 2:return 0;case 3:return c.flags;case 4:return d=Y(),c.flags|=d,0;case 12:case 12:return d=Y(),wa[d+0>>1]=2,
0;case 13:case 14:case 13:case 14:return 0;case 16:case 8:return-K.h;case 9:return gb(K.h),-1;default:return-K.h}}catch(f){return"undefined"!==typeof FS&&f instanceof M||t(f),-f.u}},___syscall5:function(a,b){X=b;try{var c=D(Y()),d=Y(),f=Y();return Wb(c,d,f).fd}catch(g){return"undefined"!==typeof FS&&g instanceof M||t(g),-g.u}},___syscall54:function(a,b){X=b;try{var c=nc(),d=Y();switch(d){case 21509:case 21505:return c.tty?0:-K.G;case 21510:case 21511:case 21512:case 21506:case 21507:case 21508:return c.tty?
0:-K.G;case 21519:if(!c.tty)return-K.G;var f=Y();return E[f>>2]=0;case 21520:return c.tty?-K.h:-K.G;case 21531:a=f=Y();if(!c.f.Sa)throw new M(K.G);return c.f.Sa(c,d,a);case 21523:return c.tty?0:-K.G;case 21524:return c.tty?0:-K.G;default:t("bad ioctl syscall "+d)}}catch(g){return"undefined"!==typeof FS&&g instanceof M||t(g),-g.u}},___syscall6:function(a,b){X=b;try{var c=nc();Yb(c);return 0}catch(d){return"undefined"!==typeof FS&&d instanceof M||t(d),-d.u}},___syscall91:function(a,b){X=b;try{var c=
Y(),d=Y(),f=mc[c];if(!f)return 0;if(d===f.hd){var g=Q[f.fd],h=f.flags,l=new Uint8Array(C.subarray(c,c+d));g&&g.f.V&&g.f.V(g,l,0,d,h);mc[c]=null;f.Na&&bb(f.jd)}return 0}catch(n){return"undefined"!==typeof FS&&n instanceof M||t(n),-n.u}},___unlock:function(){},_abort:function(){e.abort()},_ems_request_file:function(a){var b=window.ScriptNodePlayer.getInstance();if(b.isReady())return b._fileRequestCallback(a);window.console.log("error: ems_request_file not ready")},_emscripten_memcpy_big:function(a,
b,c){C.set(C.subarray(b,b+c),a);return a},_getenv:pc,_llvm_eh_typeid_for:function(a){return a},_llvm_stackrestore:function(a){var b=Z.j[a];Z.j.splice(a,1);y(b)},_llvm_stacksave:Z,_llvm_trap:function(){t("trap!")},_pthread_cond_wait:function(){return 0},_pthread_getspecific:function(a){return qc[a]||0},_pthread_key_create:function(a){if(0==a)return K.h;E[a>>2]=rc;qc[rc]=0;rc++;return 0},_pthread_once:sc,_pthread_setspecific:function(a,b){if(!(a in qc))return K.h;qc[a]=b;return 0},_strftime_l:function(a,
b,c,d){return yc(a,b,c,d)},_time:function(a){var b=Date.now()/1E3|0;a&&(E[a>>2]=b);return b},DYNAMICTOP_PTR:F,STACKTOP:Ba};var Ac=e.asm(e.Oa,e.Pa,buffer);e.asm=Ac;
var Ta=e.__GLOBAL__I_000101=function(){return e.asm.__GLOBAL__I_000101.apply(null,arguments)},Xa=e.__GLOBAL__sub_I_Adapter_cpp=function(){return e.asm.__GLOBAL__sub_I_Adapter_cpp.apply(null,arguments)},Ua=e.__GLOBAL__sub_I_FilterModelConfig6581_cpp=function(){return e.asm.__GLOBAL__sub_I_FilterModelConfig6581_cpp.apply(null,arguments)},Va=e.__GLOBAL__sub_I_FilterModelConfig8580_cpp=function(){return e.asm.__GLOBAL__sub_I_FilterModelConfig8580_cpp.apply(null,arguments)},Wa=e.__GLOBAL__sub_I_WaveformCalculator_cpp=
function(){return e.asm.__GLOBAL__sub_I_WaveformCalculator_cpp.apply(null,arguments)},Ya=e.__GLOBAL__sub_I_iostream_cpp=function(){return e.asm.__GLOBAL__sub_I_iostream_cpp.apply(null,arguments)},zc=e.__ZSt18uncaught_exceptionv=function(){return e.asm.__ZSt18uncaught_exceptionv.apply(null,arguments)};e.___cxa_can_catch=function(){return e.asm.___cxa_can_catch.apply(null,arguments)};e.___cxa_is_pointer_type=function(){return e.asm.___cxa_is_pointer_type.apply(null,arguments)};
e.___errno_location=function(){return e.asm.___errno_location.apply(null,arguments)};e._emu_compute_audio_samples=function(){return e.asm._emu_compute_audio_samples.apply(null,arguments)};e._emu_enable_voice=function(){return e.asm._emu_enable_voice.apply(null,arguments)};e._emu_get_audio_buffer=function(){return e.asm._emu_get_audio_buffer.apply(null,arguments)};e._emu_get_audio_buffer_length=function(){return e.asm._emu_get_audio_buffer_length.apply(null,arguments)};
e._emu_get_current_position=function(){return e.asm._emu_get_current_position.apply(null,arguments)};e._emu_get_max_position=function(){return e.asm._emu_get_max_position.apply(null,arguments)};e._emu_get_sample_rate=function(){return e.asm._emu_get_sample_rate.apply(null,arguments)};e._emu_get_sid_base=function(){return e.asm._emu_get_sid_base.apply(null,arguments)};e._emu_get_sid_register=function(){return e.asm._emu_get_sid_register.apply(null,arguments)};
e._emu_get_track_info=function(){return e.asm._emu_get_track_info.apply(null,arguments)};e._emu_is_6581=function(){return e.asm._emu_is_6581.apply(null,arguments)};e._emu_is_ntsc=function(){return e.asm._emu_is_ntsc.apply(null,arguments)};e._emu_load_file=function(){return e.asm._emu_load_file.apply(null,arguments)};e._emu_read_ram=function(){return e.asm._emu_read_ram.apply(null,arguments)};e._emu_seek_position=function(){return e.asm._emu_seek_position.apply(null,arguments)};
e._emu_set_6581=function(){return e.asm._emu_set_6581.apply(null,arguments)};e._emu_set_ntsc=function(){return e.asm._emu_set_ntsc.apply(null,arguments)};e._emu_set_subsong=function(){return e.asm._emu_set_subsong.apply(null,arguments)};e._emu_sid_count=function(){return e.asm._emu_sid_count.apply(null,arguments)};e._emu_teardown=function(){return e.asm._emu_teardown.apply(null,arguments)};
var bb=e._free=function(){return e.asm._free.apply(null,arguments)},fb=e._malloc=function(){return e.asm._malloc.apply(null,arguments)},db=e.setTempRet0=function(){return e.asm.setTempRet0.apply(null,arguments)};e.setThrew=function(){return e.asm.setThrew.apply(null,arguments)};var na=e.stackAlloc=function(){return e.asm.stackAlloc.apply(null,arguments)},y=e.stackRestore=function(){return e.asm.stackRestore.apply(null,arguments)},x=e.stackSave=function(){return e.asm.stackSave.apply(null,arguments)};
e.dynCall_ffi=function(){return e.asm.dynCall_ffi.apply(null,arguments)};e.dynCall_i=function(){return e.asm.dynCall_i.apply(null,arguments)};e.dynCall_ii=function(){return e.asm.dynCall_ii.apply(null,arguments)};e.dynCall_iii=function(){return e.asm.dynCall_iii.apply(null,arguments)};e.dynCall_iiii=function(){return e.asm.dynCall_iiii.apply(null,arguments)};e.dynCall_iiiii=function(){return e.asm.dynCall_iiiii.apply(null,arguments)};
e.dynCall_iiiiii=function(){return e.asm.dynCall_iiiiii.apply(null,arguments)};e.dynCall_iiiiiii=function(){return e.asm.dynCall_iiiiiii.apply(null,arguments)};e.dynCall_iiiiiiii=function(){return e.asm.dynCall_iiiiiiii.apply(null,arguments)};e.dynCall_iiiiiiiii=function(){return e.asm.dynCall_iiiiiiiii.apply(null,arguments)};e.dynCall_iiiiiiiiiiii=function(){return e.asm.dynCall_iiiiiiiiiiii.apply(null,arguments)};e.dynCall_v=function(){return e.asm.dynCall_v.apply(null,arguments)};
e.dynCall_vi=function(){return e.asm.dynCall_vi.apply(null,arguments)};e.dynCall_viddd=function(){return e.asm.dynCall_viddd.apply(null,arguments)};e.dynCall_vidddd=function(){return e.asm.dynCall_vidddd.apply(null,arguments)};e.dynCall_vididd=function(){return e.asm.dynCall_vididd.apply(null,arguments)};e.dynCall_vidiii=function(){return e.asm.dynCall_vidiii.apply(null,arguments)};e.dynCall_vii=function(){return e.asm.dynCall_vii.apply(null,arguments)};
e.dynCall_viid=function(){return e.asm.dynCall_viid.apply(null,arguments)};e.dynCall_viiddd=function(){return e.asm.dynCall_viiddd.apply(null,arguments)};e.dynCall_viii=function(){return e.asm.dynCall_viii.apply(null,arguments)};e.dynCall_viiii=function(){return e.asm.dynCall_viiii.apply(null,arguments)};e.dynCall_viiiii=function(){return e.asm.dynCall_viiiii.apply(null,arguments)};e.dynCall_viiiiii=function(){return e.asm.dynCall_viiiiii.apply(null,arguments)};
e.dynCall_viiiiiii=function(){return e.asm.dynCall_viiiiiii.apply(null,arguments)};e.dynCall_viiiiiiiiii=function(){return e.asm.dynCall_viiiiiiiiii.apply(null,arguments)};e.dynCall_viiiiiiiiiiiiiii=function(){return e.asm.dynCall_viiiiiiiiiiiiiii.apply(null,arguments)};e.dynCall_viijii=function(){return e.asm.dynCall_viijii.apply(null,arguments)};e.asm=Ac;
e.ccall=function(a,b,c,d){var f=e["_"+a];assert(f,"Cannot call unknown function "+a+", make sure it is exported");var g=[];a=0;if(d)for(var h=0;h<d.length;h++){var l=ra[c[h]];l?(0===a&&(a=x()),g[h]=l(d[h])):g[h]=d[h]}c=f.apply(null,g);c="string"===b?D(c):"boolean"===b?!!c:c;0!==a&&y(a);return c};e.getMemory=function(a){if(za)if(Ma)var b=fb(a);else{b=E[F>>2];a=b+a+15&-16;E[F>>2]=a;if(a=a>=G)Ea(),a=!0;a&&(E[F>>2]=b,b=0)}else b=ia(a);return b};e.UTF8ToString=sa;e.addRunDependency=Qa;
e.removeRunDependency=Ra;e.FS_createFolder=dc;e.FS_createPath=ec;e.FS_createDataFile=hc;e.FS_createPreloadedFile=lc;e.FS_createLazyFile=kc;e.FS_createLink=ic;e.FS_createDevice=W;e.FS_unlink=Ub;Pa=function Cc(){e.calledRun||Dc();e.calledRun||(Pa=Cc)};
function Dc(){function a(){if(!e.calledRun&&(e.calledRun=!0,!ma)){Ma||(Ma=!0,Ga(Ia));Ga(Ja);if(e.onRuntimeInitialized)e.onRuntimeInitialized();if(e.postRun)for("function"==typeof e.postRun&&(e.postRun=[e.postRun]);e.postRun.length;){var a=e.postRun.shift();La.unshift(a)}Ga(La)}}if(!(0<H)){if(e.preRun)for("function"==typeof e.preRun&&(e.preRun=[e.preRun]);e.preRun.length;)Na();Ga(Ha);0<H||e.calledRun||(e.setStatus?(e.setStatus("Running..."),setTimeout(function(){setTimeout(function(){e.setStatus("")},
1);a()},1)):a())}}e.run=Dc;function t(a){if(e.onAbort)e.onAbort(a);void 0!==a?(ha(a),u(a),a=JSON.stringify(a)):a="";ma=!0;throw"abort("+a+"). Build with -s ASSERTIONS=1 for more info.";}e.abort=t;if(e.preInit)for("function"==typeof e.preInit&&(e.preInit=[e.preInit]);0<e.preInit.length;)e.preInit.pop()();e.noExitRuntime=!0;Dc();
  return {
	Module: Module,  // expose original Module
  };
})(window.spp_backend_state_SIDPlay);
/*
 websidplay_adapter.js: Adapts libsidplayfp backend to generic WebAudio/ScriptProcessor player.

 version 1.0
 
 This is the main API to interact with the backend from JavaScript.
 
 Copyright (C) 2024 Juergen Wothke
*/
class SIDPlayBackendAdapter extends EmsHEAP16BackendAdapter {
	constructor(basicROM, charROM, kernalROM, nextFrameCB, enableMd5)
	{
		super(backend_SIDPlay.Module, 2, new SimpleFileMapper(backend_SIDPlay.Module),
				new HEAP16ScopeProvider(backend_SIDPlay.Module, 0x8000));	// use stereo (for the benefit of multi-SID songs)

		this._ROM_SIZE = 0x2000;
		this._CHAR_ROM_SIZE = 0x1000;

		this._nextFrameCB = (typeof nextFrameCB == 'undefined') ? function() {} : nextFrameCB;

		this._basicROM = this._base64DecodeROM(basicROM, this._ROM_SIZE);
		this._charROM = this._base64DecodeROM(charROM, this._CHAR_ROM_SIZE);
		this._kernalROM = this._base64DecodeROM(kernalROM, this._ROM_SIZE);

		this._enableMd5 = (typeof enableMd5 == 'undefined') ? false : enableMd5;

		this._digiShownLabel = "";
		this._digiShownRate = 0;


		this.ensureReadyNotification();
	}

	computeAudioSamples()
	{
		if (typeof window.sid_measure_runs == 'undefined' || !window.sid_measure_runs)
		{
			window.sid_measure_sum = window.sid_measure_runs = 0;
		}
		this._nextFrameCB(this);	// used for "interactive mode"

		let t = performance.now();
//			console.profile(); // if compiled using "emcc.bat --profiling"

		if (this.Module.ccall('emu_compute_audio_samples', 'number'))
		{
			return 1; // >0 means "end song"
		}
//			console.profileEnd();
		window.sid_measure_sum+= performance.now() - t;

		if (window.sid_measure_runs++ == 100)
		{
			window.sid_measure = window.sid_measure_sum / window.sid_measure_runs;

			window.sid_measure_sum = window.sid_measure_runs = 0;

			if (typeof window.sid_measure_avg_runs == 'undefined' || !window.sid_measure_avg_runs)
			{
				window.sid_measure_avg_sum = window.sid_measure;
				window.sid_measure_avg_runs = 1;
			}
			else
			{
				window.sid_measure_avg_sum += window.sid_measure;
				window.sid_measure_avg_runs += 1;
			}
			window.sid_measure_avg = window.sid_measure_avg_sum / window.sid_measure_avg_runs;
		}
		return 0;
	}

	loadMusicData(sampleRate, path, filename, data, options)
	{
		filename = path + "/" + filename; // sidplay loads using filename
		
		let basicBuf= 0;
		if (this._basicROM) { basicBuf = this.Module._malloc(this._ROM_SIZE); this.Module.HEAPU8.set(this._basicROM, basicBuf);}

		let charBuf= 0;
		if (this._charROM) { charBuf = this.Module._malloc(this._CHAR_ROM_SIZE); this.Module.HEAPU8.set(this._charROM, charBuf);}

		let kernalBuf= 0;
		if (this._kernalROM) { kernalBuf = this.Module._malloc(this._ROM_SIZE); this.Module.HEAPU8.set(this._kernalROM, kernalBuf);}

		let buf = this.Module._malloc(data.length);
		this.Module.HEAPU8.set(data, buf);

		let ret = this.Module.ccall('emu_load_file', 'number',
							['string', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number'],
							[ filename, buf, data.length, ScriptNodePlayer.getWebAudioSampleRate(), basicBuf, charBuf, kernalBuf, this._enableMd5]);

		this.Module._free(buf);

		if (kernalBuf) this.Module._free(kernalBuf);
		if (charBuf) this.Module._free(charBuf);
		if (basicBuf) this.Module._free(basicBuf);

		if (ret == 0)
		{
			this._setupOutputResampling(sampleRate);
		}
		return ret;
	}

	evalTrackOptions(options)
	{
		super.evalTrackOptions(options);
		
		// keep webSID's numbering scheme (different from what libsidplayfp uses internally)
		let track = (typeof options.track == 'undefined') ? -1 : options.track;
		return this.Module.ccall('emu_set_subsong', 'number', ['number'], [track]);
	}

	getSongInfoMeta()
	{
		return {
			loadAddr: Number,
			playSpeed: Number,
			maxSubsong: Number,
			actualSubsong: Number,
			songName: String,
			songAuthor: String,
			songReleased: String,
			md5: String				// optional: must be enabled via enableMd5 param
		};
	}

	updateSongInfo(filename)
	{
		let result = this._songInfo;
		
		let numAttr = 8;
		let ret = this.Module.ccall('emu_get_track_info', 'number');

		let array = this.Module.HEAP32.subarray(ret>>2, (ret>>2) + numAttr);

		result.loadAddr = this.Module.HEAP32[((array[0])>>2)]; // i32
		result.playSpeed = this.Module.HEAP32[((array[1])>>2)]; // i32
		result.maxSubsong = this.Module.HEAP8[(array[2])]; // i8
		result.actualSubsong = this.Module.HEAP8[(array[3])]; // i8
		result.songName = this._getExtAsciiString(array[4]);
		result.songAuthor = this._getExtAsciiString(array[5]);
		result.songReleased = this._getExtAsciiString(array[6]);
		result.md5 = this.Module.UTF8ToString(array[7]);
	}

	_getExtAsciiString(heapPtr)
	{
		// Pointer_stringify cannot be used here since UTF-8 parsing
		// messes up original extASCII content
		let text = "";
		for (let j = 0; j < 32; j++) {
			let b = this.Module.HEAP8[heapPtr+j] & 0xff;
			if(b == 0) break;

			text += (b < 128) ? String.fromCharCode(b) : ("&#" + b + ";");
		}
		return text;
	}

	_base64DecodeROM(encoded, romSize)
	{
		if (typeof encoded == 'undefined') return 0;

		const binString = atob(encoded);
		let r = Uint8Array.from(binString, (m) => m.codePointAt(0));

		return (r.length == romSize) ? r : 0;
	}



// ---- manipulation of emulator state

	enableVoice(sidIdx, voice, on)
	{
		if (!this.isAdapterReady()) return;
		this.Module.ccall('emu_enable_voice', 'number', ['number', 'number', 'number'], [sidIdx, voice, on]);
	}


	isSID6581()
	{
		if (!this.isAdapterReady()) return 0;
		return this.Module.ccall('emu_is_6581', 'number');
	}

	setSID6581(is6581)
	{
		if (!this.isAdapterReady()) return;
		this.Module.ccall('emu_set_6581', 'number', ['number'], [is6581]);
	}

	isNTSC()
	{
		if (!this.isAdapterReady()) return 0;
		return this.Module.ccall('emu_is_ntsc', 'number');
	}

	setNTSC(ntsc)
	{
		if (!this.isAdapterReady()) return;
		this.Module.ccall('emu_set_ntsc', 'number', ['number'], [ntsc]);
	}

	countSIDs()
	{
		if (!this.isAdapterReady()) return 0;
		return this.Module.ccall('emu_sid_count', 'number');
	}

	getSIDBaseAddr(sidIdx)
	{
		if (!this.isAdapterReady()) return 0;
		return this.Module.ccall('emu_get_sid_base', 'number', ['number'], [sidIdx]);
	}

	getRAM(offset)
	{
		if (!this.isAdapterReady()) return 0;
		return this.Module.ccall('emu_read_ram', 'number', ['number'], [offset]);
	}

// ---- digi playback status

	getDigiType()
	{
		if (!this.isAdapterReady()) return 0;
		return 0;									// not implemented yet
	}

	getDigiTypeDesc()
	{
		if (!this.isAdapterReady()) return 0;
		return this._digiShownLabel;				// not implemented yet
	}

	getDigiRate()
	{
		if (!this.isAdapterReady()) return 0;
		return this._digiShownRate;					// not implemented yet
	}
// ---- access to "historic" SID data

	/**
	* note: is is not properly available in libplaysidfp.. not implemented yet
	*/
/* 
	readVoiceLevel(sidIdx, voiceIdx)
	{
		if (!this.isAdapterReady()) return 0;

		return this.Module.ccall('emu_read_voice_level', 'number', ['number', 'number'], [sidIdx, voiceIdx]);
	}
*/
	/**
	* note: is is not properly available in libplaysidfp.. just returns the last written value
	*/
	getSIDRegister(sidIdx, reg)
	{
		if (!this.isAdapterReady()) return 0;

		return this.Module.ccall('emu_get_sid_register', 'number', ['number', 'number'], [sidIdx, reg]);
	}

	
// ---- util for debugging

	printMemDump(name, startAddr, endAddr)
	{
		let text = "const unsigned char "+name+"[] =\n{\n";
		let line = "";
		let j = 0;
		for (let i = 0; i < (endAddr - startAddr + 1); i++) {
			let d = this.Module.ccall('emu_read_ram', 'number', ['number'], [startAddr+i]);
			line += "0x"+(("00" + d.toString(16)).substr(-2).toUpperCase())+", ";
			if (j  == 11)
			{
				text += (line + "\n");
				line = "";
				j = 0;
			}
			else
			{
				j++;
			}
		}
		text += (j?(line+"\n"):"")+"}\n";
		console.log(text);
	}
};
