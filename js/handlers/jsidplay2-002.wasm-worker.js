importScripts("jsidplay2-002.wasm-runtime.js");

function allocateTeaVMbyteArray(array) {
  if (array === null) {
    return undefined;
  }
  let byteArrayPtr = instance.exports.teavm_allocateByteArray(array.length);
  let byteArrayData = instance.exports.teavm_byteArrayData(byteArrayPtr);
  new Uint8Array(instance.exports.memory.buffer, byteArrayData, array.length).set(array);
  return byteArrayPtr;
}

function allocateTeaVMstringArray(array) {
  if (array === null) {
    array = [];
  }
  let stringArrayPtr = instance.exports.teavm_allocateStringArray(array.length);
  let objectArrayData = new Int32Array(
    instance.exports.memory.buffer,
    instance.exports.teavm_objectArrayData(stringArrayPtr),
    array.length
  );
  for (let i = 0; i < array.length; ++i) {
    objectArrayData[i] = allocateTeaVMstring(array[i]);
  }
  return stringArrayPtr;
}

function allocateTeaVMstring(str) {
  if (str === null) {
    return undefined;
  }
  let stringPtr = instance.exports.teavm_allocateString(str.length);
  let stringData = instance.exports.teavm_stringData(stringPtr);
  let charArrayData = new Uint16Array(
    instance.exports.memory.buffer,
    instance.exports.teavm_charArrayData(stringData),
    str.length
  );
  for (let i = 0; i < charArrayData.length; ++i) {
    charArrayData[i] = str.charCodeAt(i);
  }
  return stringPtr;
}

function decodeTeaVMstring(stringPtr) {
  if (stringPtr === null) {
    return undefined;
  }
  let stringData = instance.exports.teavm_stringData(stringPtr);
  let arrayLength = instance.exports.teavm_arrayLength(stringData);
  let charArrayData = new Uint16Array(
    instance.exports.memory.buffer,
    instance.exports.teavm_charArrayData(stringData),
    arrayLength
  );
  let result = "";
  for (let i = 0; i < arrayLength; ++i) {
    result += String.fromCharCode(charArrayData[i]);
  }
  return result;
}

const eventMap = {
  INITIALISE: function (eventData) {
    TeaVM.wasm
      .load("jsidplay2-002.wasm", {
        installImports(o, controller) {
          o.jsidplay2 = {
            processSamples: (leftChannelPtr, rightChannelPtr, length) => (
              (left = instance.exports.memory.buffer.slice(
                instance.exports.teavm_floatArrayData(leftChannelPtr),
                instance.exports.teavm_floatArrayData(leftChannelPtr) + (length << 2)
              )),
              (right = instance.exports.memory.buffer.slice(
                instance.exports.teavm_floatArrayData(rightChannelPtr),
                instance.exports.teavm_floatArrayData(rightChannelPtr) + (length << 2)
              )),
              postMessage(
                {
                  eventType: "SAMPLES",
                  eventData: {
                    left: left,
                    right: right,
                    length: length,
                  },
                },
                [left, right]
              )
            ),
            processPixels: (pixelsPtr, length) => (
              (image = instance.exports.memory.buffer.slice(
                instance.exports.teavm_byteArrayData(pixelsPtr),
                instance.exports.teavm_byteArrayData(pixelsPtr) + length
              )),
              createImageBitmap(
                new ImageData(new Uint8ClampedArray(image), 384, 285, { colorSpace: "srgb" }),
                0,
                0,
                384,
                285
              ).then((bitmap) =>
                postMessage(
                  {
                    eventType: "FRAME",
                    eventData: {
                      image: bitmap,
                    },
                  },
                  [bitmap]
                )
              )
            ),
            processSidWrite: (absTime, relTime, addr, value) =>
              postMessage({
                eventType: "SID_WRITE",
                eventData: {
                  absTime: absTime,
                  relTime: relTime,
                  addr: addr,
                  value: value,
                },
              }),
            timerEnd: (end) =>
              postMessage({
                eventType: "TIMER_END",
                eventData: {
                  end: end,
                },
              }),
            processPrinter: (output) =>
              postMessage({
                eventType: "PRINTER",
                eventData: {
                  output: decodeTeaVMstring(output),
                },
              }),
            whatsSid: (wavPtr, length) => (
              (wav = instance.exports.memory.buffer.slice(
                instance.exports.teavm_byteArrayData(wavPtr),
                instance.exports.teavm_byteArrayData(wavPtr) + length
              )),
              postMessage(
                {
                  eventType: "WHATSSID",
                  eventData: {
                    wav: wav,
                  },
                },
                [wav]
              )
            ),
          };
        },
      })
      .then((teavm) => {
        instance = teavm.instance;
        teavm.main();

        let args = [];
        eventData && Object.entries(eventData).map(([key, value]) => (args.push("--" + key), args.push("" + value)));
        instance.exports.js2main(allocateTeaVMstringArray(args));

        postMessage({
          eventType: "INITIALISED",
        });
      });
  },
  OPEN: function (eventData) {
    instance.exports.js2open(
      allocateTeaVMbyteArray(eventData.contents ?? null),
      allocateTeaVMstring(eventData.tuneName ?? null),
      eventData.startSong,
      eventData.nthFrame,
      eventData.sidWrites,
      allocateTeaVMbyteArray(eventData.cartContents ?? null),
      allocateTeaVMstring(eventData.cartName ?? null),
      allocateTeaVMstring(eventData.command ?? null),
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
    instance.exports.js2samplingRate(allocateTeaVMstring(eventData.samplingRate ?? null));

    postMessage({
      eventType: "SAMPLING_RATE_SET",
    });
  },
  SET_SAMPLING: function (eventData) {
    instance.exports.js2sampling(allocateTeaVMstring(eventData.sampling ?? null));

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
    instance.exports.js2engine(allocateTeaVMstring(eventData.engine ?? null));

    postMessage({
      eventType: "ENGINE_SET",
    });
  },
  SET_DEFAULT_EMULATION: function (eventData) {
    instance.exports.js2defaultEmulation(allocateTeaVMstring(eventData.defaultEmulation ?? null));

    postMessage({
      eventType: "DEFAULT_EMULATION_SET",
    });
  },
  SET_USER_EMULATION: function (eventData) {
    instance.exports.js2userEmulation(
      allocateTeaVMstring(eventData.userEmulation ?? null),
      allocateTeaVMstring(eventData.stereoEmulation ?? null),
      allocateTeaVMstring(eventData.thirdEmulation ?? null)
    );

    postMessage({
      eventType: "USER_EMULATION_SET",
    });
  },
  SET_DEFAULT_CLOCK_SPEED: function (eventData) {
    instance.exports.js2defaultClockSpeed(allocateTeaVMstring(eventData.defaultClockSpeed ?? null));

    postMessage({
      eventType: "DEFAULT_CLOCK_SPEED_SET",
    });
  },
  SET_USER_CLOCK_SPEED: function (eventData) {
    instance.exports.js2userClockSpeed(allocateTeaVMstring(eventData.userClockSpeed ?? null));

    postMessage({
      eventType: "USER_CLOCK_SPEED_SET",
    });
  },
  SET_DEFAULT_CHIP_MODEL: function (eventData) {
    instance.exports.js2defaultChipModel(allocateTeaVMstring(eventData.defaultSidModel ?? null));

    postMessage({
      eventType: "DEFAULT_CHIP_MODEL_SET",
    });
  },
  SET_USER_CHIP_MODEL: function (eventData) {
    instance.exports.js2userChipModel(
      allocateTeaVMstring(eventData.userSidModel ?? null),
      allocateTeaVMstring(eventData.stereoSidModel ?? null),
      allocateTeaVMstring(eventData.thirdSIDModel ?? null)
    );

    postMessage({
      eventType: "USER_CHIP_MODEL_SET",
    });
  },
  HARDSID_MAPPING: function (eventData) {
    let result = decodeTeaVMstring(
      instance.exports.js2hardSidMapping(eventData.chipCount, eventData.hardsid6581, eventData.hardsid8580)
    );

    postMessage({
      eventType: "HARDSID_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  EXSID_MAPPING: function (eventData) {
    let result = decodeTeaVMstring(instance.exports.js2exSidMapping());

    postMessage({
      eventType: "EXSID_MAPPED",
      eventData: {
        mapping: result,
      },
    });
  },
  SIDBLASTER_MAPPING: function (eventData) {
    let result = decodeTeaVMstring(instance.exports.js2sidBlasterMapping());

    postMessage({
      eventType: "SIDBLASTER_MAPPED",
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
    instance.exports.js2filterName(
      allocateTeaVMstring(eventData.emulation ?? null),
      allocateTeaVMstring(eventData.chipModel ?? null),
      eventData.sidNum,
      allocateTeaVMstring(eventData.filterName ?? null)
    );

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
      allocateTeaVMstring(eventData.stereoMode ?? null),
      eventData.dualSidBase,
      eventData.thirdSIDBase,
      eventData.fakeStereo,
      allocateTeaVMstring(eventData.sidToRead ?? null)
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
    instance.exports.js2floppyType(allocateTeaVMstring(eventData.floppyType ?? null));

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
    instance.exports.js2typeInCommand(allocateTeaVMstring(eventData.command ?? null));

    postMessage({
      eventType: "COMMAND_SET",
    });
  },
  TYPE_KEY: function (eventData) {
    instance.exports.js2typeKey(allocateTeaVMstring(eventData.key ?? null));

    postMessage({
      eventType: "KEY_TYPED",
    });
  },
  PRESS_KEY: function (eventData) {
    instance.exports.js2pressKey(allocateTeaVMstring(eventData.key ?? null));

    postMessage({
      eventType: "KEY_PRESSED",
    });
  },
  RELEASE_KEY: function (eventData) {
    instance.exports.js2releaseKey(allocateTeaVMstring(eventData.key ?? null));

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
    let result = decodeTeaVMstring(instance.exports.js2tuneInfo());

    postMessage({
      eventType: "GOT_TUNE_INFO",
      eventData: {
        tuneInfo: result,
      },
    });
  },
  GET_PLAYLIST: function (eventData) {
    let result = decodeTeaVMstring(instance.exports.js2playList());

    postMessage({
      eventType: "GOT_PLAYLIST",
      eventData: {
        playList: result,
      },
    });
  },
  GET_STATUS: function (eventData) {
    let result = decodeTeaVMstring(instance.exports.js2status());

    postMessage({
      eventType: "GOT_STATUS",
      eventData: {
        status: result,
      },
    });
  },
  INSERT_DISK: function (eventData) {
    instance.exports.js2insertDisk(
      allocateTeaVMbyteArray(eventData.contents ?? null),
      allocateTeaVMstring(eventData.diskName ?? null)
    );

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
    instance.exports.js2insertTape(
      allocateTeaVMbyteArray(eventData.contents ?? null),
      allocateTeaVMstring(eventData.tapeName ?? null)
    );

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
    instance.exports.js2controlDatasette(allocateTeaVMstring(eventData.control ?? null));

    postMessage({
      eventType: "DATASETTE_CONTROLLED",
    });
  },
  INSERT_REU_FILE: function (eventData) {
    instance.exports.js2insertREUfile(
      allocateTeaVMbyteArray(eventData.contents ?? null),
      allocateTeaVMstring(eventData.reuName ?? null)
    );

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

// Handle incoming messages
addEventListener("message", (event) => eventMap[event.data.eventType](event.data.eventData), false);
