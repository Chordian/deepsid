/**
 * Web Worker for JSIDPlay2 (TeaVM / WASM GC version)
 *
 * This worker runs the SID emulation logic compiled to WebAssembly.
 * It communicates with the main thread via `postMessage()`, transferring
 * audio, video, and control data. Transferable objects (ArrayBuffers, ImageBitmaps)
 * are used to minimize memory copies between the worker and the main thread.
 *
 * Author: Ken Händel
 */
importScripts("jsidplay2-005.wasm_gc-runtime.js");

// $DEVTOOLS_SECTION_1

function processSamples(absTime, left, right, length) {
    postMessage(
        {
            eventType: "SAMPLES",
            eventData: {
                absTime,
                left: left.buffer,
                right: right.buffer,
                length,
            },
        },
        [left.buffer, right.buffer]
    );
}

function processFrame(absTime, pixels, length) {
    createImageBitmap(new ImageData(new Uint8ClampedArray(pixels.buffer, pixels.byteOffset, pixels.byteLength), 384, length / 1536)).then(
        (bitmap) =>
            postMessage(
                {
                    eventType: "FRAME",
                    eventData: {
                        absTime,
                        image: bitmap,
                    },
                },
                [bitmap]
            )
    );
}

function processOsc(
    sidNum,
    absTime,
    wav0, wav0Txt,
    wav1, wav1Txt,
    wav2, wav2Txt,
    env0, env0Txt,
    env1, env1Txt,
    env2, env2Txt,
    frq0, frq0Txt,
    frq1, frq1Txt,
    frq2, frq2Txt,
    vol, volTxt,
    res, resTxt,
    fil, filTxt
) {
    const wav0S = wav0.buffer.slice(4, 4 + (new DataView(wav0.buffer).getInt32(0, true) - 1) * 4);
    const wav1S = wav1.buffer.slice(4, 4 + (new DataView(wav1.buffer).getInt32(0, true) - 1) * 4);
    const wav2S = wav2.buffer.slice(4, 4 + (new DataView(wav2.buffer).getInt32(0, true) - 1) * 4);

    const env0S = env0.buffer.slice(4, 4 + (new DataView(env0.buffer).getInt32(0, true) - 1) * 4);
    const env1S = env1.buffer.slice(4, 4 + (new DataView(env1.buffer).getInt32(0, true) - 1) * 4);
    const env2S = env2.buffer.slice(4, 4 + (new DataView(env2.buffer).getInt32(0, true) - 1) * 4);

    const frq0S = frq0.buffer.slice(4, 4 + (new DataView(frq0.buffer).getInt32(0, true) - 1) * 4);
    const frq1S = frq1.buffer.slice(4, 4 + (new DataView(frq1.buffer).getInt32(0, true) - 1) * 4);
    const frq2S = frq2.buffer.slice(4, 4 + (new DataView(frq2.buffer).getInt32(0, true) - 1) * 4);

    const volS = vol.buffer.slice(4, 4 + (new DataView(vol.buffer).getInt32(0, true) - 1) * 4);
    const resS = res.buffer.slice(4, 4 + (new DataView(res.buffer).getInt32(0, true) - 1) * 4);
    const filS = fil.buffer.slice(4, 4 + (new DataView(fil.buffer).getInt32(0, true) - 1) * 4);

    postMessage(
        {
            eventType: "OSC",
            eventData: {
                sidNum,
                absTime,
                wav0: wav0S,
                wav0Txt,
                wav1: wav1S,
                wav1Txt,
                wav2: wav2S,
                wav2Txt,
                env0: env0S,
                env0Txt,
                env1: env1S,
                env1Txt,
                env2: env2S,
                env2Txt,
                frq0: frq0S,
                frq0Txt,
                frq1: frq1S,
                frq1Txt,
                frq2: frq2S,
                frq2Txt,
                vol: volS,
                volTxt,
                res: resS,
                resTxt,
                fil: filS,
                filTxt,
            },
        },
        [
            wav0S, wav1S, wav2S,
            env0S, env1S, env2S,
            frq0S, frq1S, frq2S,
            volS, resS, filS
        ]
    );
}

function whatsSid(wav) {
    postMessage(
        {
            eventType: "WHATSSID",
            eventData: {
                wav: wav.buffer,
            },
        },
        [wav.buffer]
    );
}

function processSidWrite(sidNum, absTime, relTime, addr, value) {
    postMessage({
        eventType: "SID_WRITE",
        eventData: {
            sidNum,
            absTime,
            relTime,
            addr,
            value,
        },
    });
}

function timerEnd(end) {
    postMessage({
        eventType: "TIMER_END",
        eventData: {
            end,
        },
    });
}

function processPrinter(output) {
    postMessage({
        eventType: "PRINTER",
        eventData: {
            output,
        },
    });
}

let wasmExports;

const eventMap = {
    INITIALISE: function (eventData) {
        TeaVM.wasmGC.load("jsidplay2-005.wasm").then((teavm) => {
            wasmExports = teavm.exports;

            let args = [];
            eventData && Object.entries(eventData).forEach(([key, value]) => (args.push("--" + key), args.push("" + value)));
            wasmExports.main(args);

            postMessage({
                eventType: "INITIALISED",
            });
        });
    },
    OPEN: function (eventData) {
        wasmExports.js2open(
            eventData.contents ?? null,
            eventData.tuneName ?? null,
            eventData.startSong,
            eventData.nthFrame,
            eventData.sidWrites,
            eventData.cartContents ?? null,
            eventData.cartName ?? null,
            eventData.command ?? null,
            eventData.songLength || 0,
            eventData.sfxSoundExpander,
            eventData.sfxSoundExpanderType || 0
        );

        postMessage({
            eventType: "OPENED",
        });
    },
    CLOCK: function () {
        wasmExports.js2clock();

        postMessage({
            eventType: "CLOCKED",
        });
    },
    IDLE: function (eventData) {
        if ((eventData?.sleepTime ?? 0) === 0) {
            postMessage({eventType: "CLOCKED"});
        } else {
            setTimeout(() => postMessage({eventType: "CLOCKED"}), eventData.sleepTime);
        }
    },
    //
    // ISidPlay2Section methods
    //
    SET_DEFAULT_PLAY_LENGTH: function (eventData) {
        wasmExports.js2defaultPlayLength(eventData.defaultPlayLength);

        postMessage({
            eventType: "DEFAULT_PLAY_LENGTH_SET",
        });
    },
    SET_LOOP: function (eventData) {
        wasmExports.js2loop(eventData.loop);

        postMessage({
            eventType: "LOOP_SET",
        });
    },
    SET_SINGLE: function (eventData) {
        wasmExports.js2single(eventData.single);

        postMessage({
            eventType: "SINGLE_SET",
        });
    },
    SET_PAL_EMULATION_ENABLE: function (eventData) {
        wasmExports.js2palEmulationEnable(eventData.palEmulationEnable);

        postMessage({
            eventType: "PAL_EMULATION_ENABLE_SET",
        });
    },
    SET_TURBO_TAPE: function (eventData) {
        wasmExports.js2turboTape(eventData.turboTape);

        postMessage({
            eventType: "TURBO_TAPE_SET",
        });
    },
    SET_FADE_TIME: function (eventData) {
        wasmExports.js2fade(eventData.fadeInTime, eventData.fadeOutTime);

        postMessage({
            eventType: "FADE_TIME_SET",
        });
    },
    //
    // IAudioSection methods
    //
    SET_SAMPLING_RATE: function (eventData) {
        wasmExports.js2samplingRate(eventData.samplingRate);

        postMessage({
            eventType: "SAMPLING_RATE_SET",
        });
    },
    SET_SAMPLING: function (eventData) {
        wasmExports.js2sampling(eventData.sampling);

        postMessage({
            eventType: "SAMPLING_SET",
        });
    },
    SET_VOLUME_LEVELS: function (eventData) {
        wasmExports.js2volumeLevels(
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
        wasmExports.js2bufferSize(eventData.bufferSize);

        postMessage({
            eventType: "BUFFER_SIZE_SET",
        });
    },
    SET_AUDIO_BUFFER_SIZE: function (eventData) {
        wasmExports.js2audioBufferSize(eventData.audioBufferSize);

        postMessage({
            eventType: "AUDIO_BUFFER_SIZE_SET",
        });
    },
    SET_DELAY: function (eventData) {
        wasmExports.js2delay(
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
        wasmExports.js2reverb(
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
        wasmExports.js2engine(eventData.engine);

        postMessage({
            eventType: "ENGINE_SET",
        });
    },
    SET_DEFAULT_EMULATION: function (eventData) {
        wasmExports.js2defaultEmulation(eventData.defaultEmulation);

        postMessage({
            eventType: "DEFAULT_EMULATION_SET",
        });
    },
    SET_USER_EMULATION: function (eventData) {
        wasmExports.js2userEmulation(eventData.userEmulation, eventData.stereoEmulation, eventData.thirdEmulation);

        postMessage({
            eventType: "USER_EMULATION_SET",
        });
    },
    SET_DEFAULT_CLOCK_SPEED: function (eventData) {
        wasmExports.js2defaultClockSpeed(eventData.defaultClockSpeed);

        postMessage({
            eventType: "DEFAULT_CLOCK_SPEED_SET",
        });
    },
    SET_USER_CLOCK_SPEED: function (eventData) {
        wasmExports.js2userClockSpeed(eventData.userClockSpeed);

        postMessage({
            eventType: "USER_CLOCK_SPEED_SET",
        });
    },
    SET_DEFAULT_CHIP_MODEL: function (eventData) {
        wasmExports.js2defaultChipModel(eventData.defaultSidModel);

        postMessage({
            eventType: "DEFAULT_CHIP_MODEL_SET",
        });
    },
    SET_USER_CHIP_MODEL: function (eventData) {
        wasmExports.js2userChipModel(eventData.userSidModel, eventData.stereoSidModel, eventData.thirdSIDModel);

        postMessage({
            eventType: "USER_CHIP_MODEL_SET",
        });
    },
    SET_OVERRIDE_CHIP_MODEL: function (eventData) {
        wasmExports.js2overrideChipModel(eventData.chipModel);

        postMessage({
            eventType: "OVERRIDE_CHIP_MODEL_SET",
        });
    },
    HARDSID_MAPPING: function (eventData) {
        let result = wasmExports.js2hardSidMapping(eventData.chipCount, eventData.hardsid6581, eventData.hardsid8580);

        postMessage({
            eventType: "HARDSID_MAPPED",
            eventData: {
                mapping: result,
            },
        });
    },
    EXSID_MAPPING: function () {
        let result = wasmExports.js2exSidMapping();

        postMessage({
            eventType: "EXSID_MAPPED",
            eventData: {
                mapping: result,
            },
        });
    },
    SIDBLASTER_MAPPING: function () {
        let result = wasmExports.js2sidBlasterMapping();

        postMessage({
            eventType: "SIDBLASTER_MAPPED",
            eventData: {
                mapping: result,
            },
        });
    },
    USBSID_MAPPING: function () {
        let result = wasmExports.js2usbSidMapping();

        postMessage({
            eventType: "USBSID_MAPPED",
            eventData: {
                mapping: result,
            },
        });
    },
    SET_FILTER_ENABLE: function (eventData) {
        wasmExports.js2filterEnable(eventData.sidNum, eventData.filterEnable);

        postMessage({
            eventType: "FILTER_ENABLE_SET",
        });
    },
    SET_FILTER_NAME: function (eventData) {
        wasmExports.js2filterName(eventData.emulation, eventData.chipModel, eventData.sidNum, eventData.filterName);

        postMessage({
            eventType: "FILTER_NAME_SET",
        });
    },
    SET_DIGI_BOOSTED_8580: function (eventData) {
        wasmExports.js2digiBoosted8580(eventData.digiBoosted8580);

        postMessage({
            eventType: "DIGI_BOOSTED_8580_SET",
        });
    },
    SET_STEREO: function (eventData) {
        wasmExports.js2stereo(
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
        wasmExports.js2mute(eventData.sidNum, eventData.voice, eventData.value);

        postMessage({
            eventType: "MUTE_SET",
        });
    },
    SET_DETECT_PSID64_CHIP_MODEL: function (eventData) {
        wasmExports.js2detectPSID64ChipModel(eventData.detectPSID64ChipModel);

        postMessage({
            eventType: "DETECT_PSID64_CHIP_MODEL_SET",
        });
    },
    SET_STEREO_SNIFFER: function (eventData) {
        wasmExports.js2stereoSniffer(eventData.stereoSniffer);

        postMessage({
            eventType: "STEREO_SNIFFER_SET",
        });
    },
    //
    // IC1541Section methods
    //
    TURN_DRIVE_ON: function (eventData) {
        wasmExports.js2turnDriveOn(eventData.driveOn);

        postMessage({
            eventType: "DRIVE_TURNED_ON",
        });
    },
    SET_PARALLEL_CABLE: function (eventData) {
        wasmExports.js2parallelCable(eventData.parallelCable);

        postMessage({
            eventType: "PARALLEL_CABLE_SET",
        });
    },
    SET_JIFFY_DOS_INSTALLED: function (eventData) {
        wasmExports.js2jiffyDosInstalled(eventData.jiffyDosInstalled);

        postMessage({
            eventType: "JIFFY_DOS_INSTALLED_SET",
        });
    },
    SET_RAM_EXPANSION: function (eventData) {
        wasmExports.js2ramExpansion(
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
        wasmExports.js2floppyType(eventData.floppyType);

        postMessage({
            eventType: "FLOPPY_TYPE_SET",
        });
    },
    //
    // IPrinterSection methods
    //
    TURN_PRINTER_ON: function (eventData) {
        wasmExports.js2printerOn(eventData.printerOn);

        postMessage({
            eventType: "PRINTER_TURNED_ON",
        });
    },
    //
    // IWhatsSidSection methods
    //
    SET_WHATSSID: function (eventData) {
        wasmExports.js2whatsSID(
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
        wasmExports.js2typeInCommand(eventData.command ?? null);

        postMessage({
            eventType: "COMMAND_SET",
        });
    },
    TYPE_KEY: function (eventData) {
        wasmExports.js2typeKey(eventData.key ?? null);

        postMessage({
            eventType: "KEY_TYPED",
        });
    },
    PRESS_KEY: function (eventData) {
        wasmExports.js2pressKey(eventData.key ?? null);

        postMessage({
            eventType: "KEY_PRESSED",
        });
    },
    RELEASE_KEY: function (eventData) {
        wasmExports.js2releaseKey(eventData.key ?? null);

        postMessage({
            eventType: "KEY_RELEASED",
        });
    },
    PRESS_JOYSTICK: function (eventData) {
        wasmExports.js2joystick(eventData.number, eventData.value);

        postMessage({
            eventType: "JOYSTICK_PRESSED",
        });
    },
    FAST_FORWARD: function () {
        wasmExports.js2fastForward();

        postMessage({
            eventType: "FAST_FORWARD_SET",
        });
    },
    NORMAL_SPEED: function () {
        wasmExports.js2normalSpeed();

        postMessage({
            eventType: "NORMAL_SPEED_SET",
        });
    },
    GET_TUNE_INFO: function () {
        let result = wasmExports.js2tuneInfo();

        postMessage({
            eventType: "GOT_TUNE_INFO",
            eventData: {
                tuneInfo: result,
            },
        });
    },
    GET_PLAYER_INFO: function () {
        let result = wasmExports.js2playerInfo();

        postMessage({
            eventType: "GOT_PLAYER_INFO",
            eventData: {
                playerInfo: result,
            },
        });
    },
    GET_PLAYLIST: function () {
        let result = wasmExports.js2playList();

        postMessage({
            eventType: "GOT_PLAYLIST",
            eventData: {
                playList: result,
            },
        });
    },
    GET_STATUS: function () {
        let result = wasmExports.js2status();

        postMessage({
            eventType: "GOT_STATUS",
            eventData: {
                status: result,
            },
        });
    },
    GET_ACTIVE_SETTINGS: function () {
        let result = wasmExports.js2activeSettings();

        postMessage({
            eventType: "GOT_ACTIVE_SETTINGS",
            eventData: {
                activeSettings: result,
            },
        });
    },
    INSERT_DISK: function (eventData) {
        wasmExports.js2insertDisk(eventData.contents ?? null, eventData.diskName ?? null);

        postMessage({
            eventType: "DISK_INSERTED",
        });
    },
    EJECT_DISK: function () {
        wasmExports.js2ejectDisk();

        postMessage({
            eventType: "DISK_EJECTED",
        });
    },
    INSERT_TAPE: function (eventData) {
        wasmExports.js2insertTape(eventData.contents ?? null, eventData.tapeName ?? null);

        postMessage({
            eventType: "TAPE_INSERTED",
        });
    },
    EJECT_TAPE: function () {
        wasmExports.js2ejectTape();

        postMessage({
            eventType: "TAPE_EJECTED",
        });
    },
    CONTROL_DATASETTE: function (eventData) {
        wasmExports.js2controlDatasette(eventData.control ?? null);

        postMessage({
            eventType: "DATASETTE_CONTROLLED",
        });
    },
    INSERT_REU_FILE: function (eventData) {
        wasmExports.js2insertREUfile(eventData.contents ?? null, eventData.reuName ?? null);

        postMessage({
            eventType: "REU_FILE_INSERTED",
        });
    },
    INSERT_REU: function (eventData) {
        wasmExports.js2insertREU(eventData.sizeKb);

        postMessage({
            eventType: "REU_INSERTED",
        });
    },
    FREEZE_CARTRIDGE: function () {
        wasmExports.js2freezeCartridge();

        postMessage({
            eventType: "CARTRIDGE_FREEZED",
        });
    },
    SET_OSCILLOSCOPE: function (eventData) {
        wasmExports.js2oscilloscope(
            eventData.enable,
            eventData.width,
            eventData.height
        );

        postMessage({
            eventType: "OSCILLOSCOPE_SET",
        });
    },
    SET_FORCE_CHECK_SONG_LENGTH: function (eventData) {
        wasmExports.js2forceCheckSongLength(eventData.force);

        postMessage({
            eventType: "FORCE_CHECK_SONG_LENGTH_SET",
        });
    },
    SET_SID_LISTENER_ENABLE: function (eventData) {
        wasmExports.js2sidListenerEnable(eventData.sidListenerEnable);

        postMessage({
            eventType: "SID_LISTENER_ENABLE_SET",
        });
    },
    AUTO_DISK_CHANGE: function (eventData) {
        wasmExports.js2autoDiskChange(eventData.contents, eventData.diskName, eventData.ticks);

        postMessage({
            eventType: "AUTO_DISK_CHANGED",
        });
    },
    AUTO_KEY_TYPE: function (eventData) {
        wasmExports.js2autoKeyType(
            eventData.keyEventType,
            eventData.key ?? null,
            eventData.ticks ?? 0);

        postMessage({
            eventType: "AUTO_KEY_TYPED",
        });
    }
};

// $DEVTOOLS_SECTION_2

// Handle incoming messages
addEventListener("message", (event) => eventMap[event.data.eventType](event.data.eventData), false);
