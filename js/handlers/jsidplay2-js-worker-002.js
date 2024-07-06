importScripts("jsidplay2.js");

// Handle incoming messages
addEventListener(
  "message",
  function (event) {
    var { eventType, eventData } = event.data;

    if (eventType === "CLOCK") {
      js2clock();

      postMessage({
        eventType: "CLOCKED",
      });
    } else if (eventType === "IDLE") {
      postMessage({
        eventType: "CLOCKED",
      });
    } else if (eventType === "OPEN") {
      js2open(
        eventData.contents ?? null,
        eventData.tuneName ?? null,
        eventData.startSong,
        eventData.nthFrame,
        eventData.sidWrites,
        eventData.cartContents ?? null,
        eventData.cartName ?? null,
        eventData.command ?? null
      );

      postMessage({
        eventType: "OPENED",
      });
    } else if (eventType === "SET_DEFAULT_PLAY_LENGTH") {
      js2setDefaultPlayLength(eventData.timeInS);

      postMessage({
        eventType: "DEFAULT_PLAY_LENGTH_SET",
      });
    } else if (eventType === "INSERT_DISK") {
      js2insertDisk(eventData.contents ?? null, eventData.diskName ?? null);

      postMessage({
        eventType: "DISK_INSERTED",
      });
    } else if (eventType === "EJECT_DISK") {
      js2ejectDisk();

      postMessage({
        eventType: "DISK_EJECTED",
      });
    } else if (eventType === "INSERT_TAPE") {
      js2insertTape(eventData.contents ?? null, eventData.tapeName ?? null);

      postMessage({
        eventType: "TAPE_INSERTED",
      });
    } else if (eventType === "EJECT_TAPE") {
      js2ejectTape();

      postMessage({
        eventType: "TAPE_EJECTED",
      });
    } else if (eventType === "PRESS_PLAY_ON_TAPE") {
      js2pressPlayOnTape();

      postMessage({
        eventType: "PRESSED_PLAY_ON_TAPE",
      });
    } else if (eventType === "INSERT_REU_FILE") {
      js2insertREUfile(eventData.contents ?? null, eventData.reuName ?? null);

      postMessage({
        eventType: "REU_FILE_INSERTED",
      });
    } else if (eventType === "INSERT_REU") {
      js2insertREU(eventData.sizeKb);

      postMessage({
        eventType: "REU_INSERTED",
      });
    } else if (eventType === "SET_COMMAND") {
      js2typeInCommand(eventData.command ?? null);

      postMessage({
        eventType: "COMMAND_SET",
      });
    } else if (eventType === "TYPE_KEY") {
      js2typeKey(eventData.key ?? null);

      postMessage({
        eventType: "KEY_TYPED",
      });
    } else if (eventType === "PRESS_KEY") {
      js2pressKey(eventData.key ?? null);

      postMessage({
        eventType: "KEY_PRESSED",
      });
    } else if (eventType === "RELEASE_KEY") {
      js2releaseKey(eventData.key ?? null);

      postMessage({
        eventType: "KEY_RELEASED",
      });
    } else if (eventType === "PRESS_JOYSTICK") {
      js2joystick(eventData.number, eventData.value);

      postMessage({
        eventType: "JOYSTICK_PRESSED",
      });
    } else if (eventType === "SET_DEFAULT_EMULATION") {
      js2defaultEmulation(eventData.emulation);

      postMessage({
        eventType: "DEFAULT_EMULATION_SET",
      });
    } else if (eventType === "SET_DEFAULT_CHIP_MODEL") {
      js2defaultChipModel(eventData.chipModel);

      postMessage({
        eventType: "DEFAULT_CHIP_MODEL_SET",
      });
    } else if (eventType === "SET_FILTER_NAME") {
      js2filterName(eventData.emulation, eventData.chipModel, eventData.sidNum, eventData.filterName);

      postMessage({
        eventType: "FILTER_NAME_SET",
      });
    } else if (eventType === "SET_MUTE") {
      js2mute(eventData.sidNum, eventData.voice, eventData.value);

      postMessage({
        eventType: "MUTE_SET",
      });
    } else if (eventType === "SET_STEREO") {
      js2stereo(
        eventData.stereoMode,
        eventData.dualSidBase,
        eventData.thirdSIDBase,
        eventData.fakeStereo,
        eventData.sidToRead
      );

      postMessage({
        eventType: "STEREO_SET",
      });
    } else if (eventType === "SET_VOLUME_LEVELS") {
      js2volumeLevels(
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
    } else if (eventType === "FAST_FORWARD") {
      js2fastForward();

      postMessage({
        eventType: "FAST_FORWARD_SET",
      });
    } else if (eventType === "NORMAL_SPEED") {
      js2normalSpeed();

      postMessage({
        eventType: "NORMAL_SPEED_SET",
      });
    } else if (eventType === "FREEZE_CARTRIDGE") {
      js2freezeCartridge();

      postMessage({
        eventType: "CARTRIDGE_FREEZED",
      });
    } else if (eventType === "INITIALISE") {
      main(
        [
          eventData.palEmulation,
          eventData.bufferSize,
          eventData.audioBufferSize,
          eventData.samplingRate,
          eventData.samplingMethodResample,
          eventData.reverbBypass,
          eventData.defaultClockSpeed,
          eventData.jiffyDosInstalled,
        ].map((item) => "" + item)
      );

      postMessage({
        eventType: "INITIALISED",
      });
    }
  },
  false
);
