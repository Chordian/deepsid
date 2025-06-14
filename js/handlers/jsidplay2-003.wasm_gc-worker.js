importScripts("jsidplay2-003.wasm_gc-runtime.js");

// $DEVTOOLS_SECTION_1

function processSamples(lf, ri, le) {
  var left = new Float32Array(lf).buffer;
  var right = new Float32Array(ri).buffer;
  postMessage(
    {
      eventType: "SAMPLES",
      eventData: {
        left: left,
        right: right,
        length: le,
      },
    },
    [left, right]
  );
}

function processPixels(pi, le) {
  createImageBitmap(new ImageData(new Uint8ClampedArray(new Uint8Array(pi)), 384, le / 1536)).then((bitmap) =>
    postMessage(
      {
        eventType: "FRAME",
        eventData: {
          image: bitmap,
        },
      },
      [bitmap]
    )
  );
}

function processSidWrite(at, ti, ad, va) {
  postMessage({
    eventType: "SID_WRITE",
    eventData: {
      absTime: at,
      relTime: ti,
      addr: ad,
      value: va,
    },
  });
}

function timerEnd(ed) {
  postMessage({
    eventType: "TIMER_END",
    eventData: {
      end: ed,
    },
  });
}

function processPrinter(op) {
  postMessage({
    eventType: "PRINTER",
    eventData: {
      output: op,
    },
  });
}

function whatsSid(ar, le) {
  var wav = new Int8Array(ar).buffer;
  postMessage(
    {
      eventType: "WHATSSID",
      eventData: {
        wav: wav,
      },
    },
    [wav]
  );
}

const eventMap = {
  INITIALISE: function (eventData) {
    TeaVM.wasmGC.load("jsidplay2-003.wasm", { installImports(o) {} }).then((teavm) => {
      instance = teavm;

      let args = [];
      eventData && Object.entries(eventData).map(([key, value]) => (args.push("--" + key), args.push("" + value)));
      instance.exports.main(args);

      postMessage({
        eventType: "INITIALISED",
      });
    });
  },
  OPEN: function (eventData) {
    instance.exports.js2open(
      eventData.contents ?? null,
      eventData.tuneName ?? null,
      eventData.startSong,
      eventData.nthFrame,
      eventData.sidWrites,
      eventData.cartContents ?? null,
      eventData.cartName ?? null,
      eventData.command ?? null,
      eventData.songLength || 0
    );

    postMessage({
      eventType: "OPENED",
    });
  },
  CLOCK: function (eventData) {
    instance.exports.js2clock();

    postMessage({
      eventType: "CLOCKED",
    });
  },
  IDLE: function (eventData) {
    if (((eventData && eventData.sleepTime) ?? 0) == 0) {
      postMessage({ eventType: "CLOCKED" });
    } else {
      setTimeout(() => postMessage({ eventType: "CLOCKED" }), eventData.sleepTime);
    }
  },
  //
  // ISidPlay2Section methods
  //
  SET_DEFAULT_PLAY_LENGTH: function (eventData) {
    instance.exports.js2defaultPlayLength(eventData.defaultPlayLength);

    postMessage({
      eventType: "DEFAULT_PLAY_LENGTH_SET",
    });
  },
  SET_LOOP: function (eventData) {
    instance.exports.js2loop(eventData.loop);

    postMessage({
      eventType: "LOOP_SET",
    });
  },
  SET_SINGLE: function (eventData) {
    instance.exports.js2single(eventData.single);

    postMessage({
      eventType: "SINGLE_SET",
    });
  },
  SET_PAL_EMULATION_ENABLE: function (eventData) {
    instance.exports.js2palEmulationEnable(eventData.palEmulationEnable);

    postMessage({
      eventType: "PAL_EMULATION_ENABLE_SET",
    });
  },
  SET_TURBO_TAPE: function (eventData) {
    instance.exports.js2turboTape(eventData.turboTape);

    postMessage({
      eventType: "TURBO_TAPE_SET",
    });
  },
  SET_FADE_TIME: function (eventData) {
    instance.exports.js2fade(eventData.fadeInTime, eventData.fadeOutTime);

    postMessage({
      eventType: "FADE_TIME_SET",
    });
  },
  //
  // IAudioSection methods
  //
  SET_SAMPLING_RATE: function (eventData) {
    instance.exports.js2samplingRate(eventData.samplingRate);

    postMessage({
      eventType: "SAMPLING_RATE_SET",
    });
  },
  SET_SAMPLING: function (eventData) {
    instance.exports.js2sampling(eventData.sampling);

    postMessage({
      eventType: "SAMPLING_SET",
    });
  },
  SET_VOLUME_LEVELS: function (eventData) {
    instance.exports.js2volumeLevels(
      eventData.mainVolume,
      eventData.secondVolume,
      eventData.thirdVolume,
      eventData.mainBalance,
      eventData.secondBalance,
      eventData.thirdBalance,
      eventData.mainDelay,
      eventData.secondDelay,
      eventData.thirdDelay
    );

    postMessage({
      eventType: "VOLUME_LEVELS_SET",
    });
  },
  SET_BUFFER_SIZE: function (eventData) {
    instance.exports.js2bufferSize(eventData.bufferSize);

    postMessage({
      eventType: "BUFFER_SIZE_SET",
    });
  },
  SET_AUDIO_BUFFER_SIZE: function (eventData) {
    instance.exports.js2audioBufferSize(eventData.audioBufferSize);

    postMessage({
      eventType: "AUDIO_BUFFER_SIZE_SET",
    });
  },
  SET_DELAY: function (eventData) {
    instance.exports.js2delay(
      eventData.delayBypass,
      eventData.delay,
      eventData.delayWetLevel,
      eventData.delayDryLevel,
      eventData.delayFeedbackLevel
    );

    postMessage({
      eventType: "DELAY_SET",
    });
  },
  SET_REVERB: function (eventData) {
    instance.exports.js2reverb(
      eventData.reverbBypass,
      eventData.reverbComb1Delay,
      eventData.reverbComb2Delay,
      eventData.reverbComb3Delay,
      eventData.reverbComb4Delay,
      eventData.reverbAllPass1Delay,
      eventData.reverbAllPass2Delay,
      eventData.reverbSustainDelay,
      eventData.reverbDryWetMix
    );

    postMessage({
      eventType: "REVERB_SET",
    });
  },
  //
  // IEmulationSection methods
  //
  SET_ENGINE: function (eventData) {
    instance.exports.js2engine(eventData.engine);

    postMessage({
      eventType: "ENGINE_SET",
    });
  },
  SET_DEFAULT_EMULATION: function (eventData) {
    instance.exports.js2defaultEmulation(eventData.defaultEmulation);

    postMessage({
      eventType: "DEFAULT_EMULATION_SET",
    });
  },
  SET_USER_EMULATION: function (eventData) {
    instance.exports.js2userEmulation(eventData.userEmulation, eventData.stereoEmulation, eventData.thirdEmulation);

    postMessage({
      eventType: "USER_EMULATION_SET",
    });
  },
  SET_DEFAULT_CLOCK_SPEED: function (eventData) {
    instance.exports.js2defaultClockSpeed(eventData.defaultClockSpeed);

    postMessage({
      eventType: "DEFAULT_CLOCK_SPEED_SET",
    });
  },
  SET_USER_CLOCK_SPEED: function (eventData) {
    instance.exports.js2userClockSpeed(eventData.userClockSpeed);

    postMessage({
      eventType: "USER_CLOCK_SPEED_SET",
    });
  },
  SET_DEFAULT_CHIP_MODEL: function (eventData) {
    instance.exports.js2defaultChipModel(eventData.defaultSidModel);

    postMessage({
      eventType: "DEFAULT_CHIP_MODEL_SET",
    });
  },
  SET_USER_CHIP_MODEL: function (eventData) {
    instance.exports.js2userChipModel(eventData.userSidModel, eventData.stereoSidModel, eventData.thirdSIDModel);

    postMessage({
      eventType: "USER_CHIP_MODEL_SET",
    });
  },
  HARDSID_MAPPING: function (eventData) {
    let result = instance.exports.js2hardSidMapping(eventData.chipCount, eventData.hardsid6581, eventData.hardsid8580);

    postMessage({
      eventType: "HARDSID_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  EXSID_MAPPING: function (eventData) {
    let result = instance.exports.js2exSidMapping();

    postMessage({
      eventType: "EXSID_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  SIDBLASTER_MAPPING: function (eventData) {
    let result = instance.exports.js2sidBlasterMapping();

    postMessage({
      eventType: "SIDBLASTER_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  USBSID_MAPPING: function (eventData) {
    let result = instance.exports.js2usbSidMapping();

    postMessage({
      eventType: "USBSID_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  SET_FILTER_ENABLE: function (eventData) {
    instance.exports.js2filterEnable(eventData.sidNum, eventData.filterEnable);

    postMessage({
      eventType: "FILTER_ENABLE_SET",
    });
  },
  SET_FILTER_NAME: function (eventData) {
    instance.exports.js2filterName(eventData.emulation, eventData.chipModel, eventData.sidNum, eventData.filterName);

    postMessage({
      eventType: "FILTER_NAME_SET",
    });
  },
  SET_DIGI_BOOSTED_8580: function (eventData) {
    instance.exports.js2digiBoosted8580(eventData.digiBoosted8580);

    postMessage({
      eventType: "DIGI_BOOSTED_8580_SET",
    });
  },
  SET_STEREO: function (eventData) {
    instance.exports.js2stereo(
      eventData.stereoMode,
      eventData.dualSidBase,
      eventData.thirdSIDBase,
      eventData.fakeStereo,
      eventData.sidToRead
    );

    postMessage({
      eventType: "STEREO_SET",
    });
  },
  SET_MUTE: function (eventData) {
    instance.exports.js2mute(eventData.sidNum, eventData.voice, eventData.value);

    postMessage({
      eventType: "MUTE_SET",
    });
  },
  SET_DETECT_PSID64_CHIP_MODEL: function (eventData) {
    instance.exports.js2detectPSID64ChipModel(eventData.detectPSID64ChipModel);

    postMessage({
      eventType: "DETECT_PSID64_CHIP_MODEL_SET",
    });
  },
  //
  // IC1541Section methods
  //
  TURN_DRIVE_ON: function (eventData) {
    instance.exports.js2turnDriveOn(eventData.driveOn);

    postMessage({
      eventType: "DRIVE_TURNED_ON",
    });
  },
  SET_PARALLEL_CABLE: function (eventData) {
    instance.exports.js2parallelCable(eventData.parallelCable);

    postMessage({
      eventType: "PARALLEL_CABLE_SET",
    });
  },
  SET_JIFFY_DOS_INSTALLED: function (eventData) {
    instance.exports.js2jiffyDosInstalled(eventData.jiffyDosInstalled);

    postMessage({
      eventType: "JIFFY_DOS_INSTALLED_SET",
    });
  },
  SET_RAM_EXPANSION: function (eventData) {
    instance.exports.js2ramExpansion(
      eventData.ramExpansion0,
      eventData.ramExpansion1,
      eventData.ramExpansion2,
      eventData.ramExpansion3,
      eventData.ramExpansion4
    );

    postMessage({
      eventType: "RAM_EXPANSION_SET",
    });
  },
  SET_FLOPPY_TYPE: function (eventData) {
    instance.exports.js2floppyType(eventData.floppyType);

    postMessage({
      eventType: "FLOPPY_TYPE_SET",
    });
  },
  //
  // IPrinterSection methods
  //
  TURN_PRINTER_ON: function (eventData) {
    instance.exports.js2printerOn(eventData.printerOn);

    postMessage({
      eventType: "PRINTER_TURNED_ON",
    });
  },
  //
  // IWhatsSidSection methods
  //
  SET_WHATSSID: function (eventData) {
    instance.exports.js2whatsSID(
      eventData.enable,
      eventData.captureTime,
      eventData.matchStartTime,
      eventData.matchRetryTime,
      eventData.minimumRelativeConfidence
    );

    postMessage({
      eventType: "WHATSSID_SET",
    });
  },
  //
  // Business methods
  //
  SET_COMMAND: function (eventData) {
    instance.exports.js2typeInCommand(eventData.command ?? null);

    postMessage({
      eventType: "COMMAND_SET",
    });
  },
  TYPE_KEY: function (eventData) {
    instance.exports.js2typeKey(eventData.key ?? null);

    postMessage({
      eventType: "KEY_TYPED",
    });
  },
  PRESS_KEY: function (eventData) {
    instance.exports.js2pressKey(eventData.key ?? null);

    postMessage({
      eventType: "KEY_PRESSED",
    });
  },
  RELEASE_KEY: function (eventData) {
    instance.exports.js2releaseKey(eventData.key ?? null);

    postMessage({
      eventType: "KEY_RELEASED",
    });
  },
  PRESS_JOYSTICK: function (eventData) {
    instance.exports.js2joystick(eventData.number, eventData.value);

    postMessage({
      eventType: "JOYSTICK_PRESSED",
    });
  },
  FAST_FORWARD: function (eventData) {
    instance.exports.js2fastForward();

    postMessage({
      eventType: "FAST_FORWARD_SET",
    });
  },
  NORMAL_SPEED: function (eventData) {
    instance.exports.js2normalSpeed();

    postMessage({
      eventType: "NORMAL_SPEED_SET",
    });
  },
  GET_TUNE_INFO: function (eventData) {
    let result = instance.exports.js2tuneInfo();

    postMessage({
      eventType: "GOT_TUNE_INFO",
      eventData: {
        tuneInfo: result,
      },
    });
  },
  GET_PLAYLIST: function (eventData) {
    let result = instance.exports.js2playList();

    postMessage({
      eventType: "GOT_PLAYLIST",
      eventData: {
        playList: result,
      },
    });
  },
  GET_STATUS: function (eventData) {
    let result = instance.exports.js2status();

    postMessage({
      eventType: "GOT_STATUS",
      eventData: {
        status: result,
      },
    });
  },
  INSERT_DISK: function (eventData) {
    instance.exports.js2insertDisk(eventData.contents ?? null, eventData.diskName ?? null);

    postMessage({
      eventType: "DISK_INSERTED",
    });
  },
  EJECT_DISK: function (eventData) {
    instance.exports.js2ejectDisk();

    postMessage({
      eventType: "DISK_EJECTED",
    });
  },
  INSERT_TAPE: function (eventData) {
    instance.exports.js2insertTape(eventData.contents ?? null, eventData.tapeName ?? null);

    postMessage({
      eventType: "TAPE_INSERTED",
    });
  },
  EJECT_TAPE: function (eventData) {
    instance.exports.js2ejectTape();

    postMessage({
      eventType: "TAPE_EJECTED",
    });
  },
  CONTROL_DATASETTE: function (eventData) {
    instance.exports.js2controlDatasette(eventData.control ?? null);

    postMessage({
      eventType: "DATASETTE_CONTROLLED",
    });
  },
  INSERT_REU_FILE: function (eventData) {
    instance.exports.js2insertREUfile(eventData.contents ?? null, eventData.reuName ?? null);

    postMessage({
      eventType: "REU_FILE_INSERTED",
    });
  },
  INSERT_REU: function (eventData) {
    instance.exports.js2insertREU(eventData.sizeKb);

    postMessage({
      eventType: "REU_INSERTED",
    });
  },
  FREEZE_CARTRIDGE: function (eventData) {
    instance.exports.js2freezeCartridge();

    postMessage({
      eventType: "CARTRIDGE_FREEZED",
    });
  },
};

// $DEVTOOLS_SECTION_2

// Handle incoming messages
addEventListener("message", (event) => eventMap[event.data.eventType](event.data.eventData), false);
