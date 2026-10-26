/*
 * USBSID-Player: a cycle exact C64 SID player for USBSID-Pico,
 * command line playback, websites and for embedding on RP2040/RP2350.
 *
 * js/handlers/usplayer/usplayer-adapter-deepsid.js
 *
 * DeepSID fork of usplayer-adapter.js
 * Only file in the DeepSID usplayer/ directory that is not byte identical to the shared set
 *
 * This file is the source, it lives in player-repo/web
 * Deployed copies must stay byte identical to it:
 *
 *   git.deepsid/js/handlers/usplayer/usplayer-adapter-deepsid.js
 *   deepsid/public_html/deepsid/js/handlers/usplayer/usplayer-adapter-deepsid.js
 *
 * DeepSID has no host app handing over a connected board, differences:
 *
 *   - imports usbsid-driver.js
 *   - _ensure() adopts the usbsidDevice singleton of that driver (WebUSB branch)
 *   - disconnect() closes that driver, connect() takes it back
 *
 * Do not merge back over the shared file, other hosts have no usbsid-driver.js
 * in their usplayer/ directory and the import would fail
 * Fixes not about the above belong in both
 *
 * Wraps USBSIDPlayerWeb in the shape repo/examples/config-tool-web expects of
 * a player backend (load / play / pause / stop / setVolume / getSongInfo / paused / emulator)
 *
 * WebUSB mode reuses the USBSIDDevice the app connected, injected by the constructor
 * ASID mode opens Web MIDI and follows the output picker of the app by name
 *
 * Loaded as an ES module, registers window.USPlayerAdapter
 * The WASM factory (window.USBSIDPlayer) comes from usbsid.js, loaded as a classic script
 *
 * Reports player status to the status line of the app, load and stop to the log panes
 * Periodic output goes to the status line, see _report()
 *
 * This file is part of USBSID-Player (https://github.com/LouDnl/USBSID-Player)
 * File author: LouD
 *
 * Copyright (c) 2026 LouD
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import { USBSIDPlayerWeb, NullTransport, isSidHeader, countSids, sidModel }
  from './usplayer-web.js';
import './usbsid-driver.js';
import { USBSIDWebUSBTransport } from './usbsid-webusb.js';
import { USBSIDWebSerialTransport } from './usbsid-webserial.js';
import { ASIDMIDITransport } from './asid-midi.js';
import { UsPlayerAudio } from './usplayer-audio.js';
import { applySoftAudio } from './usplayer-softaudio.js';

/* Build artefacts sit beside this file, resolved from import.meta.url (page relative
 * paths differ per host, DeepSID uses js/handlers/usplayer/) */
const WASM_DIR = new URL('./', import.meta.url).href;

/* Cache buster of the host, empty when none was given
 * Also applied to the wasm and ES module, a stale usbsid.wasm beside a fresh
 * usbsid.esm.js shows up as usp_audio_configure() returning 0 */
const VERSION = new URL(import.meta.url).search;

/** Name with the cache buster of the host on it */
function versioned(name) { return WASM_DIR + name + VERSION; }

/** Milliseconds, monotonic if available */
function _now() {
  return (typeof performance !== 'undefined') ? performance.now() : Date.now();
}

/* Register grid push interval in ms, 20 per second
 * The highlight of the app lasts 180 ms
 * Slower looks like a recording, faster adds load to the emulation thread without
 * visible gain */
const GRID_MS = 50;

/* Status line once a second (every 20 grid ticks) */
const STATUS_EVERY = 20;

/* readMemory() page watch, worker mode: idle time before a page drops out of the
 * report, recheck interval and maximum reported pages */
const MEM_WATCH_IDLE_MS = 1000;
const MEM_WATCH_CHECK_MS = 250;
const MEM_WATCH_MAX = 16;

let _modulePromise = null;
/**
 * Get the wasm module, instantiated once per page
 *
 * Factory comes from window.USBSIDPlayer (usbsid.js as classic script) or is imported
 * from usbsid.esm.js beside this file
 */
function getModule() {
  if (!_modulePromise) {
    _modulePromise = (async () => {
      let factory = (typeof window !== 'undefined') ? window.USBSIDPlayer : null;
      if (typeof factory !== 'function') {
        const mod = await import(versioned('usbsid.esm.js'));
        factory = mod.default;
      }
      return factory({ locateFile: (p) => versioned(p) });
    })();
  }
  return _modulePromise;
}

/* Chip count from the tune header: version 3 has a second SID address at 0x7a, version
 * 4 a third at 0x7b
 * Non zero byte means present */
export class USPlayerAdapter {
  /**
   * @param {string} emulator  'usplayer' (WebUSB) or 'usplayer-asid'
   * @param {object} device    the app's connected USBSIDDevice, WebUSB mode
   */
  constructor(emulator, device) {
    this.emulator = emulator;
    this._device = device || null;
    this._isAsid = (emulator === 'usplayer-asid');
    /* Web Serial uses the CDC interface of the board, the app driver uses the vendor
     * interface
     * Opens a separate port, only mode working without WebUSB */
    this._isSerial = (emulator === 'usplayer-serial');
    /* No board: reSIDfp in the same wasm, out through an AudioWorklet */
    this._isAudio = (emulator === 'usplayer-audio');
    /* Whole file goes to the board and its embedded player plays it
     * Nothing is emulated: no register writes, no play position to read back
     * Same transport as usplayer-serial */
    this._isSendsid = (emulator === 'usplayer-sendsid');
    /* Board playback start in performance.now() terms and time played
     * The board reports no position, see playtimeMs() */
    this._boardStart = 0;
    this._boardPlayed = 0;
    /* Board position of the last successful poll, null if unanswered, see _pollBoardTime() */
    this._boardMs = null;
    this._boardPoll = null;
    this._boardPollFails = 0;
    this._audio = null;
    this._player = null;
    this._driver = null;
    this._transport = null;
    this._bytes = null;
    this._info = { maxSubsong: 0, songName: '', songAuthor: '', songReleased: '', numSids: 1 };
    this._ready = null;
    this._paused = false;
    this._midiWired = false;
    this._subtune = 0;
    this._reportTimer = 0;
    this._prefix = 'Playing';
    this._lastStatus = '';
    this._volume = 1;
    /* Register grid shadow copy, see onWrite in _ensure() */
    this._shadow = new Uint8Array(128).fill(0xff);
    this._dirty = new Uint8Array(128);
    /* Slots a tune has written
     * The shadow starts as 0xff, first write of any value counts as a change, see
     * readRegister() */
    this._seen = new Uint8Array(128);
    this._anyDirty = false;
    this._tick = 0;
    /* Software audio runs the emulation in a worker, see _startWorker()
     * The page player serves metadata and Songlengths lookups and never steps _snap is
     * the last state report of the worker */
    this._worker = null;
    this._workerReady = null;
    this._snap = null;
    /* Worker register reports waiting to be heard, see _queueRegisters(), and the CIA 1
     * timer A latch of the latest one */
    this._regQueue = [];
    this._workerCiaLatch = 0;
    /* RAM mirror of the worker machine for readMemory(), filled from the pages the host
     * reads, see _watchPage(). _memValid marks pages held, _memPages maps page to last
     * read time */
    this._mem = new Uint8Array(65536);
    this._memValid = new Uint8Array(256);
    this._memPages = new Map();
    this._memSent = '';
    /* Scope, panning and filter state, see usplayer-softaudio.js */
    this._softAudioInit();
    this._memCheck = 0;
    /* SID and FM mix in percent, undefined until the host sets one (wasm defaults are
     * 100 and 50) */
    this._sidVolume = undefined;
    this._fmVolume = undefined;
    /* DeepSID "Buffer size" in samples, ring depth while the page is visible
     * Undefined keeps the UsPlayerAudio default */
    this._bufferSamples = undefined;
    this._onBufferVisibility = null;
    this._onResumeVisibility = null;
    this._msgId = 0;
    this._pending = new Map();
    /* Host overrides for log, status, registers and SID count, see setHost()
     * All null means config-tool-web defaults */
    this._host = { log: null, status: null, registers: null, sidCount: null };
  }

  /**
   * Set how the host wants to be talked to
   *
   * Defaults are config-tool-web shaped: log lines go to #player-log and #debug-log,
   * status to #status-text, registers to window.updateSIDReg
   * Callbacks replace the defaults
   *
   *
   * @param {object} host
   * @param {(line: string) => void}                  [host.log]
   * @param {(line: string) => void}                  [host.status]
   * @param {(chip: number, reg: number, val: number) => void} [host.registers]
   * @param {(count: number) => void}                 [host.sidCount]
   */
  setHost(host) {
    if (!host) return;
    for (const k of ['log', 'status', 'registers', 'sidCount']) {
      if (typeof host[k] === 'function') this._host[k] = host[k];
    }
  }

  get paused() { return this._paused; }
  get stopped() { return !this._player || !this._player.isPlaying(); }

  /**
   * Put a line in the log panes of the app
   *
   * Writes the elements directly, usbsidLog is not reachable from a module
   * Only for occasional events (load, stop)
   * Periodic output goes to the status line, see _report()
   */
  _log(line) {
    if (this._host.log) { this._host.log(line); return; }
    for (const id of ['player-log', 'debug-log']) {
      const el = (typeof document !== 'undefined') ? document.getElementById(id) : null;
      if (!el) continue;
      el.textContent += '[USPLAYER] ' + line + '\n';
      el.scrollTop = el.scrollHeight;
    }
  }

  /**
   * Show the running status in the status line of the app
   *
   * Appending to the log rereads and rewrites the whole buffer and forces a layout,
   * which shows up as gaps in the writes
   * The app writes "Playing: <name>", kept as prefix with the numbers appended
   *
   * drain = mean and worst gap between frame writes going out (frame ~20 ms) queue =
   * transfers waiting dropped = write ring overflow (should be 0)
   */
  _report() {
    if (!this._player || !this._player.isPlaying()) return;
    /* No host callback and no element, leave early (building the line queries the transport) */
    if (!this._host.status &&
        (typeof document === 'undefined' ||
         !document.getElementById('status-text'))) return;

    const p = this._player;

    /* SendSID: the board plays the file, frames and transport counters would read zero
     * Show the time since the file was sent */
    if (this._isSendsid) {
      const secs = (this.playtimeMs() / 1000).toFixed(1);
      this._status(`${this._prefix} | playing on the board` +
                   `${this._paused ? ', paused' : ''} | ${secs}s in`);
      return;
    }

    const s = (typeof p.stats === 'function') ? p.stats() : null;

    /* Worker mode: numbers come from the latest worker snapshot */
    const w = (this._isAudio && this._worker) ? this._snap : null;
    let line = `${this._prefix} | ${(w ? (w.refreshHz || 0) : p.refreshHz()).toFixed(2)} fps` +
               ` | ${w ? (w.frames || 0) : p.frames()} frames`;

    /* Software audio has no transport, board numbers are zero
     * Show ms/frame against the 20 ms budget and starve (silence the worklet had to
     * make up, heard as crackle)
     * Both stay flat when it works */
    if (this._isAudio && this._audio) {
      const a = this._audio.stats();
      if (w) {
        a.msPerFrame = w.msPerFrame || 0;
        a.fmWrites = w.fmWrites || 0;
        a.clipped = w.clipped || 0;
      }
      /* Silent lead-in: empty ring and faster than 1x on purpose, numbers mean nothing */
      if (a.skipping) {
        this._status(`${this._prefix} | running through the loader` +
                     ` | ${(p.playtimeMs() / 1000).toFixed(1)}s in`);
        return;
      }
      line += ` | ${a.msPerFrame.toFixed(1)}/20 ms per frame` +
              ` | buffer ${a.queuedMs} ms`;
      /* Only for tunes with an FM side */
      if (a.fmWrites > 0) line += ` | FM ${a.fmWrites}`;
      if (a.starvedMs) line += ` | STARVED ${a.starvedMs} ms`;
      if (a.clipped > 0) line += ` | CLIPPED ${a.clipped}`;
      this._status(line);
      return;
    }

    if (s) {
      line += ` | drain ${s.meanDrainGap.toFixed(1)}/${s.maxDrainGap.toFixed(0)} ms` +
              ` | queue ${s.maxQueue}` +
              (s.usb ? ` | ${s.usb.transfersPerSecond.toFixed(0)} xfer/s` : '');
      if (s.dropped) line += ` | DROPPED ${s.dropped}`;
      if (s.starved || s.blocked) {
        line += ` | STALLS ${s.starved}/${s.blocked}`;
      }
    }

    this._status(line);
  }

  /** Set the status line, only when changed */
  _status(line) {
    if (line === this._lastStatus) return;
    this._lastStatus = line;
    if (this._host.status) { this._host.status(line); return; }
    if (typeof document === 'undefined') return;
    const el = document.getElementById('status-text');
    if (el) el.textContent = line;
  }

  /**
   * Fill the shadow from the register mirror of the emulation
   *
   * Software audio only: usp_audio_configure hands the SID to reSIDfp and the transport
   * sees no writes, leaving register grid, piano and scope dead
   * Board modes stay on the write side (record of what was sent)
   * Worker mode reads the worker reports, see _queueRegisters()
   */
  _pollRegisters() {
    if (this._isAudio && this._worker) { this._drainRegisters(); return; }
    if (!this._isAudio) return;
    if (!this._player || typeof this._player.sidRegister !== 'function') return;
    const chips = Math.min(4, Math.max(1, this._info.numSids | 0));
    for (let c = 0; c < chips; c++) {
      for (let r = 0; r < 32; r++) {
        let v;
        try { v = this._player.sidRegister(c + 1, r) & 0xff; } catch (_) { return; }
        const i = (c << 5) | r;
        this._seen[i] = 1;
        if (this._shadow[i] === v) continue;
        this._shadow[i] = v;
        this._dirty[i] = 1;
        this._anyDirty = true;
      }
    }
  }

  /** Push the registers that changed */
  _flushRegisters() {
    if (!this._anyDirty) return;
    this._anyDirty = false;
    const fn = this._host.registers ||
               ((typeof window !== 'undefined') ? window.updateSIDReg : null);
    if (typeof fn !== 'function') { this._dirty.fill(0); return; }
    for (let i = 0; i < 128; i++) {
      if (!this._dirty[i]) continue;
      this._dirty[i] = 0;
      fn((i >> 5) & 0x03, i & 0x1f, this._shadow[i]);
    }
  }

  _startReporting() {
    this._stopReporting();
    if (typeof setInterval !== 'function') return;
    /* Take the tune name the app puts in the status line right after load, once */
    if (this._host.status) {
      /* Host owns the status line, nothing to read back */
      this._prefix = 'Playing';
    } else {
      setTimeout(() => {
        const el = (typeof document !== 'undefined')
          ? document.getElementById('status-text') : null;
        this._prefix = (el && el.textContent) ? el.textContent : 'Playing';
      }, 250);
    }
    /* One timer: grid 20 per second, status line once a second */
    this._tick = 0;
    this._reportTimer = setInterval(() => {
      try {
        this._pollRegisters();
        this._flushRegisters();
        if ((++this._tick % STATUS_EVERY) === 0) this._report();
      } catch (_) { this._stopReporting(); }
    }, GRID_MS);
  }

  _stopReporting() {
    if (this._reportTimer) { clearInterval(this._reportTimer); this._reportTimer = 0; }
    this._anyDirty = false;
    this._dirty.fill(0);
  }

  async _ensure() {
    if (this._ready) return this._ready;
    this._ready = (async () => {
      const M = await getModule();
      if (this._isAudio) {
        /* No transport to connect, usp_audio_configure() takes over the emulation
         * backend and no write reaches the write ring
         * AudioContext opens in load(): needs the tune clock and chip count, and a
         * context created outside a user gesture stays suspended */
        this._transport = new NullTransport();
        /* Start the worker early, it fetches and instantiates a wasm module while the
         * user picks a tune */
        this._startWorker().catch((e) => this._log('worker init: ' + e.message));
      } else if (this._isAsid) {
        this._transport = new ASIDMIDITransport();
        try {
          await this._transport.connect(null);
          this._wireMidiPicker();
        } catch (e) { console.warn('ASID connect:', e); }
      } else if (this._isSendsid) {
        /* Vendor interface and CDC take the same packets
         * WebUSB first: no second dialog when the board is granted for another mode,
         * and only the vendor interface can be probed for the firmware build flags
         * Web Serial is the fallback, the only route without WebUSB */
        this._transport = new USBSIDWebUSBTransport({ device: this._device });
        try {
          if (this._device) await this._transport.connect();
          else await this._transport.connectGranted();
        } catch (e) { this._log('WebUSB: ' + e.message); }
        if (!this._transport.isOpen) {
          this._transport = new USBSIDWebSerialTransport();
          try {
            const ok = await this._transport.connectGranted();
            if (!ok) this._log('no board granted yet, press Connect');
          } catch (e) { this._log('Web Serial: ' + e.message); }
        }
        await this._checkSidPlayer();
      } else if (this._isSerial) {
        /* Both use the CDC interface. serial streams register writes, sendsid sends the
         * whole file once
         *
         * Take only a port that is already granted, connect() calls requestPort() which
         * needs a user gesture
         * The picker belongs to the Connect button of the host, via connect() */
        this._transport = new USBSIDWebSerialTransport();
        try {
          const ok = await this._transport.connectGranted();
          if (!ok) this._log('no serial port granted yet, press Connect');
        } catch (e) { this._log('Web Serial: ' + e.message); }
      } else {
        /* Driver owned by the adapter
         *
         * config-tool-web hands over a connected USBSIDDevice
         * DeepSID has no such object, this branch makes one. _extDev puts board reads
         * on the serialised config path of the driver
         *
         * reconnect() takes only a granted device and answers false when there is none.
         * connect() calls requestDevice() which needs a user gesture, this runs at page
         * load
         * The picker belongs to the Connect button, via connect() below */
        if (!this._device && globalThis.usbsidDevice) {
          try {
            /* Use the driver singleton of config-tool-web, two drivers on one board
             * would take each other's replies */
            const drv = globalThis.usbsidDevice;
            if (drv.isOpen || await drv.reconnect()) {
              this._driver = drv;
              this._device = drv;
            } else {
              this._driver = drv;   /* kept for Connect to open it */
              this._log('no board granted yet, press Connect');
            }
          } catch (e) {
            this._log('USBSID driver: ' + (e && e.message ? e.message : e));
          }
        }
        this._transport = new USBSIDWebUSBTransport({ device: this._device });
        try {
          /* With a device handed in, connect() only confirms it
           * Without one take what is granted: connect() calls requestDevice() which
           * shows a picker, and that belongs to a button the user pressed
           * The Connect button of the host reaches it via connect() below */
          if (this._device) await this._transport.connect();
          else await this._transport.connectGranted();
        } catch (_) {}
      }
      this._player = new USBSIDPlayerWeb(M, this._transport);

      /* Mirror the writes into the register display of the app
       *
       * Goes through a shadow copy. updateSIDReg per write is a DOM update per write
       * and a digi writes ~30000 registers a second (measured in the config tool: tab
       * at 102% of a core, grew to 2 GB)
       * Only changed values are pushed, 20 times a second */
      /* Board config, read through the connected device of the app
       * Without it
       * $df40/$df50 reach nothing and an FM/OPL tune plays no OPL */
      try {
        /* No board to ask, asking would time out */
        const board = this._isAudio ? null : await this._player.applyBoardConfig();
        if (board) {
          this._log(`board: socket one ${board.sidsSocketOne} SID(s), ` +
                    `socket two ${board.sidsSocketTwo}, FM/OPL on ` +
                    (board.fmoplSid > 0 ? `SID ${board.fmoplSid}` : 'nothing'));
        }
      } catch (e) { this._log('could not read the board config: ' + e); }

      this._transport.onWrite = (reg, val) => {
        const i = reg & 0x7f;
        this._seen[i] = 1;
        if (this._shadow[i] === val) return;
        this._shadow[i] = val;
        this._dirty[i] = 1;
        this._anyDirty = true;
      };
    })();
    return this._ready;
  }

  /**
   * Follow the shared MIDI output list of the app
   *
   * Option values are indices into the MIDIAccess of the app, replacing them with port
   * ids of this adapter breaks the other player
   * Read the name of the selected option and map it onto the transport
   */
  _wireMidiPicker() {
    const sel = document.getElementById('asid-midi-outputs');
    if (!sel || !this._transport.selectOutputByName || this._midiWired) return;
    const apply = () => {
      const opt = sel.options[sel.selectedIndex];
      if (opt) this._transport.selectOutputByName(opt.textContent);
    };
    apply();
    sel.addEventListener('change', apply);
    this._midiWired = true;
  }

  async _fetch(url) {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    return new Uint8Array(await resp.arrayBuffer());
  }

  /**
   * Entry point of the app: load(subtune, timeout, url, callback), subtune counted from
   * zero, zero meaning the default song of the file
   */
  /**
   * Bring the transport up before the first tune loads, returns true when open
   *
   * Web Serial requestPort() needs a user gesture, call this from the click of the
   * Connect button of the host
   */
  async connect() {
    await this._ensure();
    /* Still shut. _ensure() takes granted devices only and is memoized, the retry is in
     * this method
     * The Connect button gives the user gesture a picker needs */
    if (!this.isConnected() && this._transport &&
        typeof this._transport.connect === 'function') {
      /* Board reached through the adopted driver: the picker is the driver's
       * The connect() of the transport only confirms a handed device */
      if (this._driver && !this._driver.isOpen) {
        /* Granted first, picker second
         * A granted board comes back via reconnect() without a dialog, a never granted
         * one needs the picker */
        try {
          if (!await this._driver.reconnect()) await this._driver.connect();
        } catch (e) { this._log('driver connect: ' + (e && e.message ? e.message : e)); }
        if (this._driver.isOpen) {
          this._device = this._driver;
          if (this._transport) this._transport._extDev = this._driver;
        }
      }
      try { await this._transport.connect(); }
      catch (e) { this._log('connect: ' + (e && e.message ? e.message : e)); }
      /* A board is chosen in this call, ask whether it can do the job */
      await this._checkSidPlayer();
    }
    return this.isConnected();
  }

  /**
   * Let go of the board, port or MIDI output, returns true when the link is shut
   *
   * Counterpart of connect(), a host can offer one button for both
   * Software audio has nothing to release and returns false
   * The transport is kept for the following connect(), _ready stays memoized (the
   * WebUSB or Web Serial choice does not change)
   * The following connect() shows the picker again, the granted device was released
   */
  async disconnect() {
    if (this._isAudio) return false;
    /* SendSID plays on the board, closing the link would leave it playing with nothing
     * to stop it
     * Stop first
     * Other modes only send writes, which a shut transport drops */
    if (this._isSendsid) { try { this.stop(); } catch (_) { /* going anyway */ } }
    this._stopBoardPoll();
    if (this._transport && typeof this._transport.disconnect === 'function') {
      try { await this._transport.disconnect(); }
      catch (e) { this._log('disconnect: ' + (e && e.message ? e.message : e)); }
    }
    /* The transport does not close a device it was handed (right when the host app owns
     * it, as config-tool-web)
     * This driver was adopted by _ensure(), nobody else closes it
     * Left open, isOpen keeps answering true via _extDev and the Connect button of the
     * host stays on "Connected"
     * _driver is only set when adopted */
    if (this._driver && typeof this._driver.close === 'function') {
      try { await this._driver.close(); }
      catch (e) { this._log('driver close: ' + (e && e.message ? e.message : e)); }
      /* Drop the device too, the following connect() re-acquires */
      this._device = null;
      if (this._transport) this._transport._extDev = null;
    }
    this._log('disconnected');
    this._status('disconnected');
    return !this.isConnected();
  }

  /**
   * Refuse a board that cannot play what SendSID sends
   *
   * The firmware reports its build flags in one byte, bit 7 is the embedded
   * SID player
   * Without it the board takes the upload, answers every packet and plays nothing
   * Asked once at connect, a board saying no is disconnected
   * A board that does not answer is left alone, older firmware predates the question
   */
  /**
   * Poll the board position every half second
   *
   * Each poll is a write plus a read with timeout, _configRead() leaves a timed out
   * read pending (a late reply would satisfy the following one)
   * Gives up after a few failures, _boardMs stays null and playtimeMs() falls back to
   * the wall clock
   */
  _startBoardPoll() {
    if (this._boardPoll || !this._isSendsid) return;
    this._boardPollFails = 0;
    this._boardPoll = setInterval(() => this._pollBoardTime(), 500);
  }

  _stopBoardPoll() {
    if (this._boardPoll) { clearInterval(this._boardPoll); this._boardPoll = null; }
  }

  async _pollBoardTime() {
    const t = this._transport;
    if (!t || !t.isOpen || typeof t.playerTime !== 'function') return;
    if (this._paused) return;
    const ms = await t.playerTime();
    if (ms == null) {
      if (++this._boardPollFails >= 5) {
        this._stopBoardPoll();
        this._log('the board will not report its position, using the wall clock');
      }
      return;
    }
    this._boardPollFails = 0;
    this._boardMs = ms;
  }

  /** Send the length of the current subtune to the board, when known */
  async _sendBoardPlaytime(subtune) {
    const t = this._transport;
    if (!t || typeof t.playerSetPlaytime !== 'function') return;
    let ms = 0;
    try {
      /* External Songlengths database first, then the embedded table of a v5 tune */
      let lens = this.songLengths(this.md5());
      if ((!lens || !lens.length) && typeof this._player.embeddedSongLengths === 'function') {
        lens = this._player.embeddedSongLengths();
      }
      if (lens && lens.length) {
        ms = lens[Math.min(lens.length, Math.max(1, subtune)) - 1] || 0;
      }
    } catch (_) { /* no lengths, board keeps its default */ }
    if (!ms) return;
    try {
      await t.playerSetPlaytime(ms);
      this._log(`board max playtime set to ${(ms / 1000).toFixed(1)}s`);
    } catch (_) { /* not fatal, board falls back to five minutes */ }
  }

  /* Onboard player mute controls */

  /** Mute or unmute one voice of one chip on the board. */
  async playerMute(chip, voice, mute) {
    const t = this._transport;
    if (!t || typeof t.playerMute !== 'function') return false;
    return await t.playerMute(chip, voice, mute);
  }

  /** Mute or unmute all voices of all chips on the board */
  async playerMuteAll(mute) {
    const t = this._transport;
    if (!t || typeof t.playerMuteAll !== 'function') return false;
    return await t.playerMuteAll(mute);
  }

  /** Mute or unmute one chip, sent as its three voices (firmware has no chip form) */
  async playerMuteChip(chip, mute) {
    const t = this._transport;
    if (!t || typeof t.playerMuteChip !== 'function') return false;
    return await t.playerMuteChip(chip, mute);
  }

  /** Board mute state, one bitmask per chip, null if unknown */
  async playerMuteState() {
    const t = this._transport;
    if (!t || typeof t.playerMuteState !== 'function') return null;
    return await t.playerMuteState();
  }

  async _checkSidPlayer() {
    if (!this._isSendsid || !this._transport || !this._transport.isOpen) return;
    if (typeof this._transport.hasSidPlayer !== 'function') return;
    let has = null;
    try { has = await this._transport.hasSidPlayer(); } catch (_) { return; }
    if (has === null) {
      this._log('this firmware does not say whether it has the onboard player');
      return;
    }
    if (has) return;
    this._log('this board has no onboard SID player: SendSID has nothing to ' +
              'play the file. Disconnected. Build the firmware with ' +
              'ONBOARD_SIDPLAYER=1, or use one of the other modes.');
    this._status('no onboard player on this board, disconnected');
    try { await this._transport.disconnect(); } catch (_) {}
  }

  /**
   * Bring up the worker running the emulation for software audio
   *
   * The main thread is throttled hard in a hidden page, on Android with the screen off
   * it barely runs
   * The audio thread is not throttled
   * A bigger buffer or keep-alive timer did not stop the stutter
   * A worker is throttled far less and holds one end of a MessageChannel, the other end
   * sits in the AudioWorkletProcessor and the main thread leaves the audio path
   */
  /**
   * Is the worker wanted?
   *
   * On by default
   * Cannot be exercised in a headless browser (audioWorklet.addModule never completes
   * there)
   *
   * ?worker=0 turns it off and is remembered, ?worker=1 turns it on again
   * While on: audio survives a backgrounded tab, register grid, piano and memory view
   * of the host show a still picture (the page player is not the one stepping)
   */
  _workerWanted() {
    try {
      const q = new URLSearchParams(location.search).get('worker');
      if (q === '1' || q === '0') {
        localStorage.setItem('usbsid_audio_worker', q);
      }
      const stored = localStorage.getItem('usbsid_audio_worker');
      return stored === null ? true : stored === '1';
    } catch (_) { return true; }
  }

  _startWorker() {
    if (this._workerReady) return this._workerReady;
    if (!this._workerWanted()) {
      this._workerReady = Promise.resolve(false);
      return this._workerReady;
    }
    this._workerReady = (async () => {
      /* Not fatal: no module workers, a page that cannot start one or an unfetchable
       * wasm all leave the main thread playing (bad only while hidden) */
      try {
        /* Versioned like the wasm, an older cached worker lacks the messages this adapter sends */
        this._worker = new Worker(versioned('usplayer-worker.js'), { type: 'module' });
        this._worker.onmessage = (e) => this._onWorkerMessage(e.data);
        this._worker.onerror = (e) => this._log('worker error: ' + (e.message || e));
        /* With a deadline: a worker that never answers (wasm fetch or instantiate
         * fails) would leave load() waiting for good. 5 s is far above the
         * instantiation time of a 150 kB module on a phone */
        await Promise.race([
          this._call('audioInit', {
          /* Named .esm.js: nginx mime.types has no entry for .mjs, module import
           * refuses octet-stream
           * See CMakeLists.txt */
          wasmUrl: versioned('usbsid.esm.js'),
          }),
          new Promise((_, reject) =>
            setTimeout(() => reject(new Error('worker did not start in 5s')), 5000)),
        ]);
        this._log('audio runs in a worker, off the main thread');
        return true;
      } catch (e) {
        this._log('no audio worker (' + (e && e.message ? e.message : e) +
                  '), playing from the main thread');
        if (this._worker) { try { this._worker.terminate(); } catch (_) {} }
        this._worker = null;
        return false;
      }
    })();
    return this._workerReady;
  }

  _onWorkerMessage(d) {
    if (!d) return;
    if (d.type === 'log') { this._log(d.payload.message); return; }
    if (d.type === 'state') { this._snap = d.payload; return; }
    if (d.type === 'regs') { this._queueRegisters(d.payload); return; }
    const p = this._pending.get(d.id);
    if (!p) return;
    this._pending.delete(d.id);
    if (d.type === 'error') p.reject(new Error(d.payload.message));
    else p.resolve(d.payload);
  }

  /** One request to the worker, as a promise. */
  _call(type, payload, transfer) {
    const id = ++this._msgId;
    return new Promise((resolve, reject) => {
      this._pending.set(id, { resolve, reject });
      this._worker.postMessage({ type, payload, id }, transfer || []);
    });
  }

  /** Is the transport open */
  isConnected() {
    /* Software audio has nothing to open and is ready, false would grey out the host controls for good */
    if (this._isAudio) return true;
    return !!(this._transport && this._transport.isOpen);
  }

  /** Why the last connect failed, when the transport can say. */
  get lastError() {
    return (this._transport && this._transport.lastError) || null;
  }

  /**
   * Fetch a file and play it
   *
   * @param {number} subtune  0 based like load_sidtune(): 0 is the default
   *                          song of the file, 1 the second. Hosts counting
   *                          from one convert first
   * @param {number} timeout  unused, kept for the host signature
   * @param {string} url      what to fetch
   * @param {function} callback  called once the tune is loaded and running
   */
  async load(subtune, timeout, url, callback) {
    await this._ensure();
    /* Stop clocking the playing tune before the fetch. _fill() runs on the main thread
     * and emulates up to eight frames each time the worklet asks, fetch, parse and
     * loadSID get in between and picking a tune during playback took visibly longer
     * than stop then pick. unrun() frees the main thread
     * Also silences at click time and closes the window where the fill loop could step
     * a player holding different bytes */
    if (this._isAudio && this._audio) {
      this._audio.unrun();
      if (typeof this._audio.flush === 'function') this._audio.flush();
    }
    try {
      const bytes = await this._fetch(url);
      this._bytes = bytes;

      const sid = isSidHeader(bytes) && !/\.(prg|p00)(\?|$)/i.test(url);
      const nsids = sid ? countSids(bytes) : 1;
      /* ASID emits only the chips it is told about, as does the register grid of the app */
      if (this._transport && 'nosids' in this._transport) this._transport.nosids = nsids;
      if (this._host.sidCount) this._host.sidCount(nsids);
      else if (typeof window.updateRegGridSIDCount === 'function') {
        window.updateRegGridSIDCount(nsids);
      }

      /* The wasm module is shared by the page, an earlier software audio play left
       * the machine on reSIDfp, a board or ASID transport needs the web backend back
       * (older wasm has no release) */
      if (!this._isAudio && typeof this._player.M._usp_audio_release === 'function') {
        this._player.M._usp_audio_release();
      }
      const ok = sid ? this._player.loadSID(bytes, subtune || 0)
                     : this._player.loadPRG(bytes);
      if (!ok) throw new Error('neither a SID nor a program');

      /* Take the metadata from the player, it has read the header */
      const i = this._player.info();
      this._info = {
        maxSubsong: Math.max(0, i.songs - 1),
        songName: i.name,
        songAuthor: i.author,
        songReleased: i.released,
        numSids: nsids,
        /* A program has no subtunes and no header, a page needs to tell it from a one song tune */
        isPrg: !sid,
        /* Song actually playing
         * Subtune 0 means the default song of the file, often not song 1 (Mechanicus
         * starts at song 3 of 18)
         * Read it back for the right number on the page and the right song length */
        song: i.song,
        /* v5 FM/OPL tune, for a host offering an FM volume. */
        hasFm: !!i.hasFmOpl,
        /* Chip addresses as placed by the emulation, v5 layouts included */
        sidAddresses: (i.sids || []).map((c) => c.addr),
      };

      this._subtune = subtune || 0;

      /* A new tune starts from an unwritten chip, else a host reading the shadow shows
       * registers of the previous tune */
      this._shadow.fill(0xff);
      this._seen.fill(0);
      this._dirty.fill(0);
      this._anyDirty = false;
      this._regQueue.length = 0;
      this._scopeClear();
      this._workerCiaLatch = 0;
      this._memValid.fill(0);

      if (this._isAudio) {
        /* Started in load(), synthesis needs the clock, chip count and model of this tune */
        if (!this._audio) {
          this._audio = new UsPlayerAudio(this._player);
          /* Report a silent lead-in skip only when there was one (every tune reports a
           * skip end, most of a single frame) */
          this._audio.onSkipEnd = (why, frames) => {
            const secs = frames / (this._player.refreshHz() || 50);
            if (secs >= 1) {
              this._log(`ran through ${secs.toFixed(1)}s of silent loader (${why})`);
            }
          };
        }
        /* Volume the host asked for before the graph existed */
        if (this._volume !== undefined) this._audio.setVolume(this._volume);
        const started = await this._audio.start({
          chips: nsids,
          quality: 1,
          model: sid ? sidModel(bytes) : 0,
        });
        if (!started) throw new Error('reSIDfp would not take this rate');

        /* Emulation goes in a worker and the ring is handed to it, taking the main
         * thread out of the audio path
         * The page player stays loaded for metadata and Songlengths lookups
         * (synchronous host calls) and never steps */
        const inWorker = await this._startWorker();
        if (inWorker) {
          if (!this._audio.handedOver) {
            const port = this._audio.handOver();
            if (port) await this._call('clock', { port }, [port]);
            /* Hidden and visible want different ring depths, the worker fills the ring
             * and must hear of changes */
            this._audio.onTargetChange = (target, steps) => {
              if (this._worker) this._call('audioTarget', { target, steps });
            };
            /* Deeper ring while hidden, passed to the worker via onTargetChange
             * UsPlayerAudio does this only from run() */
            if (typeof this._audio._watchVisibility === 'function') {
              this._audio._watchVisibility();
            }
            this._watchResume();
          }
          this._applyBufferSamples();
          const copy = bytes.slice();
          await this._call('loadSID',
                           { bytes: copy.buffer, subtune: subtune || 0 },
                           [copy.buffer]);
          await this._call('audioConfigure', {
            chips: nsids,
            rate: this._audio.ctx.sampleRate | 0,
            quality: 1,
            model: sid ? sidModel(bytes) : 0,
            target: this._audio._target,
            steps: this._audio._maxSteps || 24,
            /* Always stereo, mono is every chip Center and needs no reconfigure, see setPanning() */
            stereo: true,
          });
          await this._softAudioReplay();
          if (this._sidVolume !== undefined) {
            await this._call('sidVolume', { percent: this._sidVolume }).catch(() => {});
          }
          if (this._fmVolume !== undefined) {
            await this._call('fmVolume', { percent: this._fmVolume }).catch(() => {});
          }
          /* A speed set before the worker was up has not reached it */
          if (this._player.speed !== 1) {
            await this._call('speed', { mult: this._player.speed }).catch(() => {});
          }
          await this._call('start', {});
        } else {
          /* Main thread fallback */
          await this._player.start({ externalClock: true });
          if (this._filter) this.setFilter(this._filter);
          if (this._sidVolume !== undefined) this._audio.setSidVolume(this._sidVolume);
          if (this._fmVolume !== undefined) this._audio.setFmVolume(this._fmVolume);
          this._audio.run(this._player);
          this._applyBufferSamples();
        }
        this._log(`software audio${inWorker ? ' in a worker' : ''}: ` +
                  `${this._audio.ctx.sampleRate} Hz, ` +
                  `${nsids} chip${nsids === 1 ? '' : 's'}, ` +
                  `${sid && sidModel(bytes) ? '8580' : '6581'}, sinc`);
      } else if (this._isSendsid) {
        /* The board plays the file
         * The local player still reads it for title, song count and Songlengths key
         * (synchronous host calls) but never starts or steps: loadSID() primes the
         * emulation, start() in the branch below clocks it
         *
         * uploadSIDFile() does the whole sequence: stop, START, file in 62 byte pieces,
         * END, SIZE, pick subtune, start
         * It counts songs from one, the argument of this method is 0 based like
         * load_sidtune()
         * Convert */
        if (!this._transport || !this._transport.isOpen) {
          throw new Error('no board on the serial port to send it to');
        }
        const song = (subtune || 0) + 1;
        const sent = await this._transport.uploadSIDFile(bytes, song);
        if (!sent) {
          throw new Error(this._transport.lastError || 'the board would not take the file');
        }
        this._boardStart = _now();
        this._boardPlayed = 0;
        this._boardMs = null;
        /* Tell the board how long this subtune runs to stop it there, default is five
         * minutes
         * Lengths come from the database of the host */
        await this._sendBoardPlaytime(song);
        this._log(`sent to the board: ${bytes.length} bytes, ` +
                  `song ${song} of ${i.songs}, playing on its own player`);
      } else {
        await this._player.start();
      }
      this._paused = false;

      this._log(`${sid ? 'SID' : 'program'} loaded: ${i.name || '(untitled)'}` +
                (i.author ? ` - ${i.author}` : '') +
                (i.released ? ` (${i.released})` : '') +
                `, ${i.songs} song${i.songs === 1 ? '' : 's'}, ${nsids} SID` +
                `${nsids === 1 ? '' : 's'}, ${bytes.length} bytes`);
      if (typeof this._player.resetStats === 'function') this._player.resetStats();
      this._startReporting();
      if (this._isSendsid) this._startBoardPoll();

      if (typeof callback === 'function') callback();
    } catch (e) {
      this._log('load failed: ' + (e && e.message ? e.message : e));
      console.error('USPlayerAdapter.load:', e);
    }
  }

  play() {
    if (this._isSendsid) {
      if (!this._paused) return;
      /* Board transport, nothing plays locally to resume */
      if (this._transport && this._transport.playerStart) this._transport.playerStart();
      this._boardStart = _now();
      this._paused = false;
      this._startBoardPoll();
      return;
    }
    if (!this._player) return;
    if (this._paused) {
      this._player.pause(false);
      this._paused = false;
      if (this._isAudio && this._worker) this._call('pause', { on: false });
    }
  }

  pause() {
    if (this._isSendsid) {
      if (this._paused) return;
      if (this._transport && this._transport.playerPause) this._transport.playerPause();
      /* Bank what has played, the clock only measures the running part */
      this._boardPlayed += _now() - this._boardStart;
      this._paused = true;
      this._log('paused on the board');
      return;
    }
    if (!this._player) return;
    this._player.pause(true);
    this._paused = true;
    if (this._isAudio && this._worker) this._call('pause', { on: true });
    this._log('paused');
  }

  stop() {
    this._stopReporting();
    if (this._isSendsid) {
      if (this._transport && this._transport.playerStop) this._transport.playerStop();
      this._stopBoardPoll();
      this._boardStart = 0;
      this._boardPlayed = 0;
      /* Reset to zero, a stop reads 0:00 and the board figure freezes at the stop position */
      this._boardMs = null;
      this._paused = false;
      this._lastStatus = '';
      this._log('stopped on the board');
      return;
    }
    /* Release the player before stopping, nothing steps after teardown
     * flush drops audio rendered but not yet played (worklet ring too), discard drops
     * only what the page has not posted */
    if (this._audio) {
      this._audio.unrun();
      if (typeof this._audio.flush === 'function') this._audio.flush();
      else this._audio.discard();
    }
    if (this._isAudio && this._worker) {
      this._call('stop', {});
      this._call('audioDiscard', {});
      this._snap = null;
      this._regQueue.length = 0;
      this._scopeClear();
    }
    if (this._player) this._player.stop();
    this._paused = false;
    this._lastStatus = '';
    this._log('stopped');
  }

  /**
   * Set the volume, 0 to 1, returns true when this mode has a volume
   *
   * Software audio only, a gain stage follows the synthesis
   * A board plays at its output setting and a tune writes $d418, nothing turns it down
   * without changing the tune
   * Returns whether it was applied, letting a host grey the slider
   *
   * @param {number} value 0 for silence, 1 for full
   */
  setVolume(value) {
    this._volume = value;
    if (!this._isAudio) return false;
    /* Before load() there is no graph, kept and applied in start() (a host restoring a
     * stored volume does this at startup) */
    if (this._audio && typeof this._audio.setVolume === 'function') {
      this._audio.setVolume(value);
    }
    return true;
  }

  /** Can the volume of this mode be changed */
  hasVolume() { return this._isAudio; }

  /**
   * Set the SID side of the software mix (before SID and FM are summed), returns false
   * outside software audio
   *
   * @param {number} percent 100 is unity, 0 silences the SID side
   */
  setSidVolume(percent) {
    this._sidVolume = Math.max(0, Number(percent) || 0);
    if (!this._isAudio) return false;
    if (this._worker) this._call('sidVolume', { percent: this._sidVolume }).catch(() => {});
    else if (this._audio) this._audio.setSidVolume(this._sidVolume);
    return true;
  }

  /**
   * Set the FM/OPL side of the software mix (before SID and FM are summed), returns
   * false outside software audio
   *
   * @param {number} percent 100 is unity, 0 silences the FM side
   */
  setFmVolume(percent) {
    this._fmVolume = Math.max(0, Number(percent) || 0);
    if (!this._isAudio) return false;
    if (this._worker) this._call('fmVolume', { percent: this._fmVolume }).catch(() => {});
    else if (this._audio) this._audio.setFmVolume(this._fmVolume);
    return true;
  }

  /**
   * Does the loaded tune have an FM/OPL side
   *
   * True for the v5 header flag, and once a tune has written to the FM/OPL in software
   * audio (older SID+FM tunes have no flag)
   */
  hasFm() {
    if (this._info.hasFm) return true;
    if (!this._isAudio) return false;
    if (this._worker) return !!this._snap && this._snap.fmWrites > 0;
    return !!this._audio && this._audio.stats().fmWrites > 0;
  }

  /**
   * Set the software audio ring depth while the page is visible
   *
   * A deeper ring keeps playback steady when the host page is busy drawing, register
   * reports are delayed as much
   *
   * @param {number} samples ring depth, clamped to 2048
   */
  setBufferSamples(samples) {
    const n = Number(samples) | 0;
    this._bufferSamples = n > 0 ? n : undefined;
    this._applyBufferSamples();
    if (!this._onBufferVisibility && typeof document !== 'undefined') {
      /* UsPlayerAudio resets a visible ring to its default depth, restore the host depth after its handler */
      this._onBufferVisibility = () => setTimeout(() => this._applyBufferSamples(), 0);
      document.addEventListener('visibilitychange', this._onBufferVisibility);
    }
  }

  /**
   * Resume an audio context the OS suspended or interrupted while the page was hidden
   * (mobile browsers do this on tab or app switch)
   */
  _watchResume() {
    if (this._onResumeVisibility || typeof document === 'undefined') return;
    this._onResumeVisibility = () => {
      if (document.visibilityState !== 'visible' || this._paused) return;
      const ctx = this._audio && this._audio.ctx;
      if (!ctx || ctx.state === 'running' || ctx.state === 'closed') return;
      this._log(`audio context ${ctx.state} on return, resuming`);
      ctx.resume().catch((e) => this._log('resume failed: ' + e));
    };
    document.addEventListener('visibilitychange', this._onResumeVisibility);
  }

  /** Apply the host's ring depth to a running software audio graph */
  _applyBufferSamples() {
    if (this._bufferSamples === undefined || !this._isAudio) return;
    if (!this._audio || !this._audio.ctx || !this._audio.node) return;
    if (typeof document !== 'undefined' && document.visibilityState === 'hidden') return;
    const rate = this._audio.ctx.sampleRate || 48000;
    /* No floor, setTarget() keeps 2048 samples (worklet minimum)
     * Every DeepSID buffer size from 2048 up takes effect */
    this._audio.setTarget(this._bufferSamples / rate, this._audio._maxSteps);
  }

  /**
   * MIDI outputs ASID mode could play to, [{ id, name }]
   *
   * For a host offering a picker. _wireMidiPicker() follows a list the host has, by the
   * name shown
   * Empty outside ASID mode and before Web MIDI is granted
   */
  midiOutputs() {
    const t = this._transport;
    if (!t || typeof t.outputs !== 'function') return [];
    try { return t.outputs(); } catch (_) { return []; }
  }

  /**
   * Play ASID to the output with this name, returns false when there is none
   *
   * By name: host lists are built from names and ids change between visits in some
   * browsers
   *
   * @param {string} name as in midiOutputs()
   */
  selectMidiOutput(name) {
    const t = this._transport;
    if (!t || typeof t.selectOutputByName !== 'function') return false;
    try { return !!t.selectOutputByName(name); } catch (_) { return false; }
  }

  /**
   * Name of the output ASID mode plays to now, empty when there is none
   *
   * A host picker shows this rather than its first entry: the transport prefers a
   * port matching its device name hint
   */
  currentMidiOutput() {
    const t = this._transport;
    if (!t || typeof t.outputs !== 'function') return '';
    try { return t.productName || ''; } catch (_) { return ''; }
  }

  getSongInfo() { return this._info; }

  /**
   * What drives the tune and how it was started, for a page that wants to show it
   * Null when the player cannot say (older copy)
   */
  timing() {
    if (this._isAudio && this._worker && this._snap) return this._snap.timing;
    if (!this._player || typeof this._player.timing !== 'function') return null;
    try { return this._player.timing(); } catch (_) { return null; }
  }

  /**
   * Emulated play time in milliseconds, null when nothing is loaded
   *
   * All modes run the emulation in the page and can answer
   * SendSID uses the board position poll with the wall clock as fallback
   */
  playtimeMs() {
    /* Audio mode: the worker is playing, _snap is its last report (3 per second) */
    if (this._isAudio && this._worker && this._snap) return this._snap.playtimeMs || 0;
    /* SendSID: nothing is emulated
     * The board reports its position (SID_PLAYER_TIME), the real clock of the player,
     * which also catches an early finish. _boardMs is the last poll reading
     * The wall clock is the fallback for a silent board or firmware: time since the
     * file was sent minus pauses, drift against the board crystal is far below display
     * resolution */
    if (this._isSendsid) {
      if (this._boardMs != null) return this._boardMs;
      if (this._boardStart === 0) return 0;
      return this._paused ? this._boardPlayed
                          : this._boardPlayed + (_now() - this._boardStart);
    }
    if (!this._player || typeof this._player.playtimeMs !== 'function') return null;
    try { return this._player.playtimeMs(); } catch (_) { return null; }
  }

  /**
   * Songlengths key of the loaded tune, MD5 of the whole file ('' when nothing is
   * loaded)
   *
   * Tunes from the served library have lengths in sidfilelist.json
   * This is for a local folder, upload or URL. Computed on demand, a few kilobytes of
   * MD5
   */
  md5() {
    if (!this._player || !this._bytes) return '';
    if (typeof this._player.md5 !== 'function') return '';
    try { return this._player.md5(this._bytes); } catch (_) { return ''; }
  }

  /**
   * Hand the Songlengths database to the player, once
   *
   * @param {string} text the file as fetched
   */
  loadSonglengths(text) {
    if (!this._player || typeof this._player.loadSonglengths !== 'function') return false;
    try { return this._player.loadSonglengths(text); } catch (_) { return false; }
  }

  get hasSonglengths() {
    return !!(this._player && this._player.hasSonglengths);
  }

  /**
   * Song lengths in ms for a key, null when absent
   *
   * @param {string} key from md5()
   */
  songLengths(key) {
    if (!this._player || typeof this._player.songLengths !== 'function') return null;
    try { return this._player.songLengths(key); } catch (_) { return null; }
  }

  /**
   * Last value written to a SID register, from the write shadow (0 if never written)
   *
   * Pull side of setHost({ registers }), same shadow
   * A real chip cannot be read back over USB at display rate, ASID has no read and the
   * synthesis of software audio lives in the audio thread
   * SID registers are write only: the last write is the state, bar the read only $1b
   * and $1c (oscillator 3 and its envelope) which answer 0
   *
   * @param {number} chip 1 to 4
   * @param {number} reg  0 to 31, $d400 relative
   */
  readRegister(chip, reg) {
    const i = (((chip | 0) - 1) << 5) | (reg & 0x1f);
    if (i < 0 || i > 127) return 0;
    /* Software audio has no writes to watch, ask the emulation at once (a host
     * redrawing a piano every frame wants this frame), see _pollRegisters() */
    if (this._isAudio && !this._worker &&
        this._player && typeof this._player.sidRegister === 'function') {
      try { return this._player.sidRegister(chip | 0, reg & 0x1f) & 0xff; }
      catch (_) { /* fall through to the shadow */ }
    }
    if (this._isAudio && this._worker) this._drainRegisters();
    const v = this._shadow[i];
    /* 0xff is the initial fill (never written), a quiet chip should look quiet */
    return this._dirtyEver(i) ? v : 0;
  }

  /**
   * Hold a worker register report until its audio is heard
   *
   * The worker emulates ahead of the playhead by the ring depth, reported as leadMs
   *
   * @param {object} p { regs: Uint8Array, ciaLatch, leadMs, mem?, memPages? }
   */
  _queueRegisters(p) {
    if (!p || !p.regs) return;
    const due = _now() + Math.max(0, Number(p.leadMs) || 0);
    this._regQueue.push({ due, regs: p.regs, ciaLatch: p.ciaLatch | 0,
                          mem: p.mem, memPages: p.memPages,
                          scope: p.scope, scopeVoices: p.scopeVoices | 0 });
    /* Bound the queue for a page that does not read it */
    if (this._regQueue.length > 256) this._applyRegisters(this._regQueue.shift());
  }

  /** Apply the queued worker register reports that are due */
  _drainRegisters() {
    const now = _now();
    while (this._regQueue.length && this._regQueue[0].due <= now) {
      this._applyRegisters(this._regQueue.shift());
    }
  }

  /**
   * Copy one worker register report into the shadow
   *
   * @param {object} e { regs, ciaLatch, mem?, memPages? }
   */
  _applyRegisters(e) {
    const n = Math.min(128, e.regs.length);
    for (let i = 0; i < n; i++) {
      const v = e.regs[i];
      this._seen[i] = 1;
      if (this._shadow[i] === v) continue;
      this._shadow[i] = v;
      this._dirty[i] = 1;
      this._anyDirty = true;
    }
    this._workerCiaLatch = e.ciaLatch;
    if (e.scope && e.scopeVoices > 0) this._scopeAppend(e.scope, e.scopeVoices, e.due);
    if (e.mem && e.memPages) {
      const pages = e.memPages;
      for (let k = 0; k < pages.length; k++) {
        const pg = pages[k];
        this._mem.set(e.mem.subarray(k << 8, (k + 1) << 8), pg << 8);
        this._memValid[pg] = 1;
      }
    }
  }

  /** Has this shadow slot been written */
  _dirtyEver(i) {
    if (!this._seen) return false;
    return this._seen[i] === 1;
  }

  /**
   * One byte of the emulated machine's RAM, or 0 when nothing is loaded
   *
   * @param {number} address 0 to 65535
   */
  readMemory(address) {
    /* Worker mode: the page player never steps and its RAM is the tune as loaded,
     * answer from the mirror filled by the worker reports */
    if (this._isAudio && this._worker) {
      const a = address & 0xffff;
      this._watchPage(a >> 8);
      this._drainRegisters();
      if (this._memValid[a >> 8]) return this._mem[a];
    }
    if (!this._player || typeof this._player.readMemory !== 'function') return 0;
    try { return this._player.readMemory(address); } catch (_) { return 0; }
  }

  /**
   * Mark a RAM page as read, tell the worker when the watched set changes
   *
   * Pages unread for MEM_WATCH_IDLE_MS drop out of the set and the mirror
   *
   * @param {number} page 0 to 255
   */
  _watchPage(page) {
    const now = _now();
    const fresh = !this._memPages.has(page);
    this._memPages.set(page, now);
    if (!fresh && now - this._memCheck < MEM_WATCH_CHECK_MS) return;
    this._memCheck = now;
    for (const [pg, t] of this._memPages) {
      if (now - t <= MEM_WATCH_IDLE_MS) continue;
      this._memPages.delete(pg);
      this._memValid[pg] = 0;
    }
    /* Most recently read first, capped at the worker limit */
    const pages = [...this._memPages.entries()]
      .sort((x, y) => y[1] - x[1])
      .slice(0, MEM_WATCH_MAX)
      .map(([pg]) => pg)
      .sort((x, y) => x - y);
    const key = pages.join(',');
    if (key === this._memSent) return;
    this._memSent = key;
    this._call('memWatch', { pages }).catch(() => {});
  }

  /**
   * A CIA timer latch, how often a CIA driven tune is called
   *
   * @param {number} cia   1 or 2
   * @param {number} timer 0 for A, 1 for B
   */
  ciaLatch(cia = 1, timer = 0) {
    if (this._isAudio && this._worker) {
      this._drainRegisters();
      return (cia === 1 && timer === 0) ? this._workerCiaLatch : 0;
    }
    if (!this._player || typeof this._player.ciaLatch !== 'function') return 0;
    try { return this._player.ciaLatch(cia, timer); } catch (_) { return 0; }
  }

  /**
   * Mute or unmute one voice
   *
   * @param {number} chip  1 to 4
   * @param {number} voice 1 to 3
   * @param {boolean} muted
   */
  setVoiceMute(chip, voice, muted) {
    /* SendSID: the board plays, the local player only read the file for title and song
     * count
     * Muting it would change nothing audible
     * The board takes
     * SID_PLAYER_MUTE, the voice controls of the host work unchanged */
    if (this._isSendsid) {
      const t = this._transport;
      if (t && typeof t.playerMute === 'function') {
        /* Not awaited, host calls this from a click handler and wants no USB round trip
         * Failures are reported by the transport */
        t.playerMute(chip, voice, muted);
      }
      return;
    }
    if (!this._player || typeof this._player.setVoiceMute !== 'function') return;
    this._player.setVoiceMute(chip, voice, muted);
    /* Audio mode: the worker is playing and needs telling too */
    if (this._isAudio && this._worker) {
      this._call('voiceMute', { chip, voice, muted: !!muted });
    }
  }

  /**
   * Mute or unmute a whole chip
   *
   * Counterpart of setVoiceMute(): board command in SendSID mode, the emulation
   * elsewhere, plus the worker when it is sounding
   *
   * A voice mute masks gate and sustain and lets other writes through
   * A chip mute drops the writes, the only one reaching $18, needed to silence a tune
   * playing samples through the volume register
   *
   *
   * @param {number} chip 1 to 4
   * @param {boolean} muted
   */
  setChipMute(chip, muted) {
    if (this._isSendsid) {
      const t = this._transport;
      if (t && typeof t.playerMuteChip === 'function') {
        /* Not awaited, host calls this from a click and wants no USB round trip */
        t.playerMuteChip(chip, muted);
      }
      return;
    }
    if (!this._player || typeof this._player.setChipMute !== 'function') return;
    this._player.setChipMute(chip, muted);
    if (this._isAudio && this._worker) {
      this._call('chipMute', { chip, muted: !!muted });
    }
  }

  /** Muted chips as a bitmask, bit 0 is chip 1, 0 when unknown */
  chipMute() {
    if (!this._player || typeof this._player.chipMute !== 'function') return 0;
    try { return this._player.chipMute(); } catch (_) { return 0; }
  }

  /** Mute bits of one chip, bit 0 is voice 1, 0 when unknown */
  voiceMute(chip = 1) {
    if (!this._player || typeof this._player.voiceMute !== 'function') return 0;
    try { return this._player.voiceMute(chip); } catch (_) { return 0; }
  }

  /** File bytes of what is loaded, null if nothing, for hosts that parse the header */
  bytes() { return this._bytes; }

  /**
   * Is the browser holding the audio context suspended?
   *
   * Only software audio has a context, other modes say no
   * A host dimming its display while the sound is stopped must tell a paused tune from
   * a page the browser has not let make a sound yet
   *
   * In audio mode a missing context counts as suspended: it opens at first load (needs
   * the tune rate, and a context opened outside a user gesture stays suspended)
   * A host asking before any tune played is asking whether it may start one without a
   * click, answer no
   */
  isSuspended() {
    const ctx = this._audio && this._audio.ctx;
    if (!ctx) return this._isAudio;
    return ctx.state === 'suspended';
  }

  /* Extras the app may not call */
  /**
   * Run faster than the tune asks for
   *
   * @param {boolean} on
   * @param {number} [mult] how much faster, player default if omitted
   */
  fastForward(on, mult) {
    if (this._player) this._player.fastForward(on, mult);
    if (this._isAudio && this._worker) {
      this._call('fastForward', { on: !!on, mult }).catch(() => {});
    }
  }

  /**
   * Play at a multiple of the tune speed, 1 is normal
   *
   * fastForward() is a seek and stops writes reaching the board
   * This leaves the output alone, a board is audibly faster
   * Software audio changes the synthesis output rate, pitch follows
   *
   * @param {number} mult 0.1 to 8
   */
  setSpeed(mult) {
    if (this._player) this._player.setSpeed(mult);
    if (this._isAudio && this._worker) {
      this._call('speed', { mult }).catch(() => {});
    }
  }

  /**
   * Mono/stereo audio switch of the board, v1.3+ PCBs
   *
   * Board transports only, ASID and software receivers have no switch
   * Returns false when the active transport cannot, letting a caller grey the control
   *
   * @param {boolean|undefined} stereo true or false to set it, undefined to flip
   */
  setAudioSwitch(stereo) {
    const t = this._transport;
    if (!t) return false;
    if (stereo === undefined) {
      if (typeof t.toggleAudioSwitch !== 'function') return false;
      t.toggleAudioSwitch();
      return true;
    }
    if (typeof t.setAudioSwitch !== 'function') return false;
    t.setAudioSwitch(!!stereo);
    return true;
  }

  /** Can the active transport switch the board between mono and stereo? */
  hasAudioSwitch() {
    return !!(this._transport &&
              typeof this._transport.setAudioSwitch === 'function');
  }
  nextSubtune() {
    if (!this._player) return;
    this._player.nextSubtune();
    this._subtune = Math.min(this._subtune + 1, this._info.maxSubsong);
  }
  prevSubtune() {
    if (!this._player) return;
    this._player.prevSubtune();
    this._subtune = Math.max(this._subtune - 1, 0);
  }
}

applySoftAudio(USPlayerAdapter);

if (typeof window !== 'undefined') {
  window.USPlayerAdapter = USPlayerAdapter;
}
