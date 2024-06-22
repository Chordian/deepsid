<!DOCTYPE html>
<html>
  <head>
    <style lang="scss" scoped>
      @import "/static/teavm/c64jukebox.scss";
    </style>

    <!-- favicon.ico -->
    <link rel="shortcut icon" href="/static/favicon.ico" type="image/x-icon" />
    <link id="favicon" rel="icon" href="/static/favicon.ico" type="image/x-icon" />
    <link id="favicon-16x16" rel="icon" href="/static/favicon-16x16.png" type="image/png" sizes="16x16" />

    <!-- Load required Bootstrap, Icons CSS -->
    <link rel="stylesheet" href="/webjars/bootstrap/5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/webjars/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />

    <!-- Load Vue followed by Bootstrap -->
    <script src="/webjars/vue/3.4.21/dist/vue.global.prod.js"></script>
    <script src="/webjars/bootstrap/5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- helpers -->
    <script src="/webjars/vue-i18n/9.10.1/dist/vue-i18n.global.prod.js"></script>
    <script src="/webjars/nosleep.js/0.12.0/dist/NoSleep.min.js"></script>

    <!-- disable pull reload -->
    <style>
      html,
      body {
        overscroll-behavior: none;
      }
    </style>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <title>C64 Jukebox (JavaScript UMD)</title>
  </head>
  <body>
    <div id="app">
      <form enctype="multipart/form-data">
        <div class="locale-changer">
          <h1 class="c64jukebox" style="width: 100%">C64 Jukebox (JavaScript UMD)</h1>
          <input
            type="button"
            :class="wakeLockEnable ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
            id="toggle"
            value="Wake Lock Off"
            style="height: fit-content"
          />
          <select
            id="localeselector"
            class="form-select form-select-sm"
            @change="updateLanguage"
            v-model="$i18n.locale"
            style="width: auto; margin: 1px; height: fit-content"
          >
            <option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">{{ lang }}</option>
          </select>
        </div>

        <nav class="navbar navbar-expand navbar-dark bg-primary p-0">
          <div class="container-fluid">
            <div class="collapse navbar-collapse" id="main_nav">
              <ul class="navbar-nav">
                <li class="nav-item dropdown" id="myDropdown" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("file") }}</a>
                  <ul class="dropdown-menu" style="width: 200px !important">
                    <li>
                      <a class="dropdown-item" href="#" @click="$refs.formFileSm.click()"> {{ $t("play") }} </a>
                      <input ref="formFileSm" id="file" type="file" @input="startTune()" style="display: none" />
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" @click="reset()"> {{ $t("reset") }} </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item dropdown" id="myDropdown2" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("player") }}</a>
                  <ul class="dropdown-menu" style="width: 200px !important">
                    <li>
                      <div class="dropdown-item form-check">
                        <label class="form-check-label" for="pause" style="cursor: pointer">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="pause"
                            style="float: right; margin-left: 8px"
                            v-model="paused"
                            @click="pauseTune()"
                          />
                          {{ $t("pauseContinue") }}
                        </label>
                      </div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" @click="fastForward()">{{ $t("fastForward") }}</a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" @click="normalSpeed()">{{ $t("normalSpeed") }}</a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          screen = true;
                        "
                        >{{ $t("stop") }}</a
                      >
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          startTune(screen);
                        "
                        >{{ $t("restart") }}</a
                      >
                    </li>
                  </ul>
                </li>
                <li class="nav-item dropdown" id="myDropdown3" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("devices") }}</a>
                  <ul class="dropdown-menu" style="width: 160px !important">
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showFloppy = !showFloppy;
                          showTape = false;
                          showCart = false;
                        "
                        >{{ $t("floppy") }}&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showFloppy
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a class="dropdown-item" href="#" @click="$refs.formDiskFileSm.click()">{{
                            $t("insertDisk")
                          }}</a>
                          <input
                            ref="formDiskFileSm"
                            id="diskFile"
                            type="file"
                            @input="insertDisk()"
                            style="display: none"
                          />
                        </li>

                        <li>
                          <div class="dropdown-item form-check">
                            <label class="form-check-label" for="jiffyDosInstalled" style="cursor: pointer">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="jiffyDosInstalled"
                                style="float: right; margin-left: 8px"
                                v-model="jiffyDosInstalled"
                                @change="reset()"
                              />
                              {{ $t("jiffyDosInstalled") }}
                            </label>
                          </div>
                        </li>

                        <li>
                          <a class="dropdown-item" href="#" @click="ejectDisk()">{{ $t("ejectDisk") }}</a>
                        </li>

                        <li>
                          <div class="dropdown-item form-check">
                            <button
                              type="button"
                              class="btn btn-secondary btn-sm"
                              v-on:click="typeInCommand('LOAD&quot;*&quot;,8,1\rRUN\r')"
                              style="cursor: pointer"
                            >
                              {{ $t("loadDisk") }}
                            </button>
                          </div>
                        </li>
                      </ul>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showTape = !showTape;
                          showFloppy = false;
                          showCart = false;
                        "
                        >{{ $t("tape") }}&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showTape
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a class="dropdown-item" href="#" @click="$refs.formTapeFileSm.click()">{{
                            $t("insertTape")
                          }}</a>
                          <input
                            ref="formTapeFileSm"
                            id="tapeFile"
                            type="file"
                            @input="insertTape()"
                            style="display: none"
                          />
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="ejectTape()">{{ $t("ejectTape") }}</a>
                        </li>

                        <li>
                          <div class="dropdown-item form-check">
                            <button
                              type="button"
                              class="btn btn-secondary btn-sm"
                              v-on:click="typeInCommand('LOAD\rRUN\r')"
                            >
                              {{ $t("loadTape") }}
                            </button>
                          </div>
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="pressPlayOnTape()">{{ $t("pressPlayOnTape") }}</a>
                        </li>
                      </ul>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showCart = !showCart;
                          showTape = false;
                          showFloppy = false;
                        "
                        >{{ $t("cart") }}&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showCart
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a class="dropdown-item" href="#" @click="$refs.formCartFileSm.click()">{{
                            $t("insertCart")
                          }}</a>
                          <input
                            ref="formCartFileSm"
                            id="cartFile"
                            type="file"
                            @input="insertCart()"
                            style="display: none"
                          />
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="ejectCart()">{{ $t("ejectCart") }}</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="freezeCartridge()">{{ $t("freezeCartridge") }}</a>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
            <!-- navbar-collapse.// -->
          </div>
          <!-- container-fluid.// -->
        </nav>

        <nav class="navbar navbar-expand navbar-dark bg-primary p-0">
          <div class="container-fluid">
            <div class="collapse navbar-collapse" id="main_nav">
              <ul class="navbar-nav">
                <li class="nav-item dropdown" id="myDropdown4" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("exampleMusic") }}</a>
                  <ul class="dropdown-menu">
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'Turrican Rise Of the Mashine',
                            '/jsidplay2service/JSIDPlay2REST/download/turrican_rotm.sid?itemId=189430&categoryId=4'
                          )
                        "
                        >Turrican Rise Of the Mashine - Jason Page</a
                      >
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'Only 299.99',
                            '/jsidplay2service/JSIDPlay2REST/download/Only_299_99.sid?itemId=3470375608&categoryId=18'
                          )
                        "
                        >Only 299.99 - Mutetus</a
                      >
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'Banaanin Alle',
                            '/jsidplay2service/JSIDPlay2REST/download/mutetus_banaaninalle.sid?itemId=209406&categoryId=4'
                          )
                        "
                      >
                        Banaanin Alle - Mutetus
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'Rocco_Siffredi_Invades_1541_II.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/Rocco_Siffredi_Invades_1541_II.sid?itemId=73719&categoryId=4'
                          )
                        "
                      >
                        Rocco Siffredi Invades 1541II - Jammer
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'L_E_D_Storm.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/L_E_D_Storm.sid?itemId=2357526530&categoryId=18'
                          )
                        "
                      >
                        L_E_D_Storm - Tim Follin
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'blindsided',
                            '/jsidplay2service/JSIDPlay2REST/download/blindsided.sid?itemId=239345&categoryId=4'
                          )
                        "
                      >
                        Blindsided - Stinsen
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'running_up_that_hill.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/running_up_that_hill.sid?itemId=238798&categoryId=4'
                          )
                        "
                      >
                        Running Up That Hill - Slaxx, Nordischsound
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'stinsen_last_night_of_89.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/stinsen_last_night_of_89.sid?itemId=201399&categoryId=4'
                          )
                        "
                      >
                        Stinsens Last Night of 89 - Bonzai
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'generations.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/generations.sid?itemId=242010&categoryId=4'
                          )
                        "
                      >
                        Generations - Flotsam
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          downloadAndStartTune(
                            'Cauldron_II_Sinus_Milieu_Studie.sid',
                            '/jsidplay2service/JSIDPlay2REST/download/Cauldron_II_Sinus_Milieu_Studie.sid?itemId=61763&categoryId=4'
                          )
                        "
                      >
                        Cauldron II Sinus Milieu Studie - Viruz
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item dropdown" id="myDropdow5" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("exampleOneFiler") }}</a>
                  <ul class="dropdown-menu">
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'fppscroller.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/fppscroller.prg?itemId=230558&categoryId=1'
                          );
                        "
                      >
                        Party Elk 2 - Booze Design
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'copperbooze.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/copperbooze.prg?itemId=197429&categoryId=1'
                          );
                        "
                      >
                        Copper Booze - Booze Design
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'foryourspritesonly.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/foryourspritesonly.prg?itemId=198971&categoryId=1'
                          );
                        "
                      >
                        For Your Sprites Only - Booze
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'layers.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/layers.prg?itemId=242834&categoryId=1'
                          );
                        "
                      >
                        Layers - Finnish Gold
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'atl-lovecats.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/atl-lovecats.prg?itemId=198558&categoryId=1'
                          );
                        "
                      >
                        Lovecats - Atlantis
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'smile-to-the-sky.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/smile-to-the-sky.prg?itemId=172574&categoryId=1'
                          );
                        "
                      >
                        Smile to the Sky - Offence
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'Comajob.t64',
                            '/jsidplay2service/JSIDPlay2REST/download/Comajob.t64?itemId=11653&categoryId=1'
                          );
                        "
                      >
                        Coma Job - Crest,Oxyron
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'rewind...tempest.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/rewind...tempest.prg?itemId=170949&categoryId=1'
                          );
                        "
                      >
                        Rewind - TempesT
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'whitelines2bh.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/whitelines2bh.prg?itemId=232984&categoryId=1'
                          );
                        "
                      >
                        White Lines - Plush
                      </a>
                    </li>
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        @click="
                          stopTune();
                          downloadAndStartProgram(
                            'daah_those_acid_pills.prg',
                            '/jsidplay2service/JSIDPlay2REST/download/daah_those_acid_pills.prg?itemId=118639&categoryId=1'
                          );
                        "
                      >
                        Daah, Those Acid Pills - Censor
                      </a>
                    </li>
                  </ul>
                </li>

                <li class="nav-item dropdown" id="myDropdown6" style="margin-right: 16px">
                  <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $t("exampleDemos") }}</a>

                  <ul class="dropdown-menu">
                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo1 = !showDemo1;
                          showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >1337&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo1
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                '1337-a',
                                '/jsidplay2service/JSIDPlay2REST/download/fairlight-1337-58679b69-a.d64?itemId=242855&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                '1337-a',
                                '/jsidplay2service/JSIDPlay2REST/download/fairlight-1337-58679b69-a.d64?itemId=242855&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                '1337-b',
                                '/jsidplay2service/JSIDPlay2REST/download/fairlight-1337-58679b69-b.d64?itemId=242855&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                '1337-c',
                                '/jsidplay2service/JSIDPlay2REST/download/fairlight-1337-58679b69-c.d64?itemId=242855&categoryId=1'
                              )
                            "
                          >
                            Disk 3
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo2 = !showDemo2;
                          showDemo1 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Next Level&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo2
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'NextLevelImage1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image1.d64?itemId=232976&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'NextLevelImage1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image1.d64?itemId=232976&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'NextLevelImage2.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image2.d64?itemId=232976&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'NextLevelImage3.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image3.d64?itemId=232976&categoryId=1'
                              )
                            "
                          >
                            Disk 3
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'NextLevelImage4.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image4.d64?itemId=232976&categoryId=1'
                              )
                            "
                          >
                            Disk 4
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo3 = !showDemo3;
                          showDemo1 =
                            showDemo2 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Mojo&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo3
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'Mojo_Side1.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side1.D64?itemId=232966&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side1.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side1.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side2.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side2.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side3.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side3.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 3
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side4.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side4.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 4
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo4 = !showDemo4;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Coma Light 13&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo4
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'ComaLight13Side1',
                                '/jsidplay2service/JSIDPlay2REST/download/coma-light-13-by-oxyron/side1.d64?itemId=112378&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'ComaLight13Side1',
                                '/jsidplay2service/JSIDPlay2REST/download/coma-light-13-by-oxyron/side1.d64?itemId=112378&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'ComaLight13Side2',
                                '/jsidplay2service/JSIDPlay2REST/download/coma-light-13-by-oxyron/side2.d64?itemId=112378&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo5 = !showDemo5;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Andropolis&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo5
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'Andropolis.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Instinct%20BoozeDesign%20-%20Andropolis.d64?itemId=81157&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo6 = !showDemo6;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Comaland&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo6
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'ComalandImage1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image1.d64?itemId=139278&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'ComalandImage1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/image1.d64?itemId=139278&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side2.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side2.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side3.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side3.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 3
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Mojo_Side4.D64',
                                '/jsidplay2service/JSIDPlay2REST/download/Mojo_Side4.D64?itemId=232966&categoryId=1'
                              )
                            "
                          >
                            Disk 4
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo7 = !showDemo7;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo8 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >Edge Of Disgrace&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo7
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'EdgeOfDisgrace_0.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/EdgeOfDisgrace_0.d64?itemId=72550&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'EdgeOfDisgrace_0.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/EdgeOfDisgrace_0.d64?itemId=72550&categoryId=1'
                              )
                            "
                          >
                            Disk 0
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'EdgeOfDisgrace_1a.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/EdgeOfDisgrace_1a.d64?itemId=72550&categoryId=1'
                              )
                            "
                          >
                            Disk 1a
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'EdgeOfDisgrace_1b.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/EdgeOfDisgrace_1b.d64?itemId=72550&categoryId=1'
                              )
                            "
                          >
                            Disk 1b
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo8 = !showDemo8;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo9 =
                            showDemo10 =
                              false;
                        "
                        >E2IRA&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo8
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'e2ira_101_A.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/e2ira_101_A.d64?itemId=218343&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'e2ira_101_A.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/e2ira_101_A.d64?itemId=218343&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'e2ira_101_B.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/e2ira_101_B.d64?itemId=218343&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'e2ira_101_C.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/e2ira_101_C.d64?itemId=218343&categoryId=1'
                              )
                            "
                          >
                            Disk 3
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo9 = !showDemo9;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo10 =
                              false;
                        "
                        >Partypopper&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo9
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'Partypopper-Disk1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Partypopper-Disk1.d64?itemId=216277&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Partypopper-Disk1.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Partypopper-Disk1.d64?itemId=216277&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Partypopper-Disk2.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Partypopper-Disk2.d64?itemId=216277&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                      </ul>
                    </li>

                    <li>
                      <a
                        class="dropdown-item"
                        href="#"
                        v-on:click.stop="
                          showDemo10 = !showDemo10;
                          showDemo1 =
                            showDemo2 =
                            showDemo3 =
                            showDemo4 =
                            showDemo5 =
                            showDemo6 =
                            showDemo7 =
                            showDemo8 =
                            showDemo9 =
                              false;
                        "
                        >Amanita (80%)&raquo;
                      </a>
                      <ul
                        class="submenu dropdown-menu"
                        :style="
                          showDemo10
                            ? 'display: block !important; left: auto; right: 100% !important;'
                            : 'left: auto; right: 100% !important;'
                        "
                      >
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              stopTune();
                              downloadAndInsertDisk(
                                'Amanita_by_Samar_Disk_A.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Amanita_by_Samar_Disk_A.d64?itemId=218357&categoryId=1'
                              );
                            "
                          >
                            Autostart
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Amanita_by_Samar_Disk_A.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Amanita_by_Samar_Disk_A.d64?itemId=218357&categoryId=1'
                              )
                            "
                          >
                            Disk 1
                          </a>
                        </li>
                        <li>
                          <a
                            class="dropdown-item"
                            href="#"
                            @click="
                              downloadAndInsertDisk(
                                'Amanita_by_Samar_Disk_B.d64',
                                '/jsidplay2service/JSIDPlay2REST/download/Amanita_by_Samar_Disk_B.d64?itemId=218357&categoryId=1'
                              )
                            "
                          >
                            Disk 2
                          </a>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
            <!-- navbar-collapse.// -->
          </div>
          <!-- container-fluid.// -->
        </nav>

        <div class="container">
          <div class="card">
            <div class="card-header">
              <ul class="nav nav-pills card-header-pills mb-2" role="tablist">
                <li class="nav-item" role="presentation">
                  <button
                    class="nav-link"
                    id="about-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#about"
                    type="button"
                    role="tab"
                    aria-controls="about"
                    aria-selected="false"
                    @click="tabIndex = 0"
                  >
                    {{ $t("ABOUT") }}
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button
                    class="nav-link active"
                    id="video-tab"
                    ref="videoTab"
                    data-bs-toggle="pill"
                    data-bs-target="#video"
                    type="button"
                    role="tab"
                    aria-controls="video"
                    aria-selected="true"
                    @click="tabIndex = 1"
                  >
                    {{ $t("VIDEO") }}
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button
                    class="nav-link"
                    id="cfg-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#cfg"
                    type="button"
                    role="tab"
                    aria-controls="cfg"
                    aria-selected="false"
                    @click="tabIndex = 2"
                  >
                    {{ $t("CFG") }}
                  </button>
                </li>
              </ul>
            </div>
            <div class="tab-content card-body" style="position: relative">
              <div class="tab-pane fade" id="about" role="tabpanel" aria-labelledby="about-tab">
                <p style="text-align: center; font-size: smaller; padding: 16px">
                  C64 Jukebox of JSIDPlay2 - Music Player &amp; C64 SID Chip Emulator<br />
                  JSIDPlay2 is copyrighted to:<br />
                  2007-${year} Ken H&#228;ndel,<br />
                  Antti S. Lankila and Wilfred Bos<br /><br />
                  Distortion Simulation and 6581/8580 emulation:<br />
                  Copyright &#169; 2005-2011 Antti S. Lankila<br />
                  ReSID engine and 6581/8580 emulation:<br />
                  Copyright &#169; 1999-2011 Dag Lem<br />
                  <br />
                  This program is free software; you can redistribute it and/or modify<br />
                  it under the terms of the GNU General Public License as published by<br />
                  the Free Software Foundation; either version 2 of the License, or<br />
                  (at your option) any later version.
                </p>
              </div>
              <div class="tab-pane fade show active" id="video" role="tabpanel" aria-labelledby="video-tab">
                <div class="row">
                  <div class="col screen-parent p-0">
                    <span v-if="$refs.formCartFileSm && $refs.formCartFileSm.files[0]">
                      <span class="ms-2 me-2">{{ $refs.formCartFileSm.files[0].name }}</span>
                      <i class="bi bi-badge-8k-fill"></i>
                    </span>
                    <span v-if="$refs.formTapeFileSm && $refs.formTapeFileSm.files[0]">
                      <span class="ms-2 me-2">{{ $refs.formTapeFileSm.files[0].name }}</span>
                      <i class="bi bi-cassette-fill"></i>
                    </span>
                    <span v-if="$refs.formDiskFileSm && $refs.formDiskFileSm.files[0]">
                      <span class="ms-2 me-2">{{ $refs.formDiskFileSm.files[0].name }}</span>
                      <i class="bi bi-floppy-fill"></i>
                    </span>

                    <span class="p-1 fs-6 fst-italic"
                      >{{ msg }}
                      <span v-show="playing && screen">
                        <span>{{ framesCounter }} / {{ defaultClockSpeed / nthFrame }} {{ $t("fps") }}</span></span
                      ></span
                    >
                    <div style="width: 100%; margin: 0px auto">
                      <canvas
                        id="c64Screen"
                        style="border: 2px solid black; background-color: black; max-width: calc(100vw - 70px)"
                        width="384"
                        height="285"
                      />
                    </div>
                    <button
                      v-show="screen && playing"
                      type="button"
                      class="btn btn-secondary btn-sm"
                      v-on:click="typeKey('SPACE')"
                      style="float: right"
                    >
                      {{ $t("space") }}
                    </button>
                    <button type="button" class="btn btn-success btn-sm" v-on:click="reset()">
                      {{ $t("reset") }}
                    </button>
                  </div>
                  <div class="col">
                    <h2>
                      JavaScript UMD Version powered by <a href="https://teavm.org/" target="_blank">TeaVM</a>
                    </h2>
                    <ol>
                      <li>
                        Run JSIDPlay2 in a browser in
                        <a href="/static/teavm/c64jukebox.vue?teavmFormat=JS">JavaScript UMD</a> or
                        <a href="/static/teavm/c64jukebox.vue?teavmFormat=JS_EM2015">JavaScript ECMAScript 2015</a> or
                        <a href="/static/teavm/c64jukebox.vue?teavmFormat=WASM">Web Assembly</a> (THIS IS NOT JAVA)
                      </li>
                      <li>Runs out-of-the-box in all browsers (Chrome is faster than Firefox)</li>
                      <li>Only 2MB in size, loads very quick</li>
                      <li>Compatible with all SIDs (mono, stereo and 3-SID)</li>
                      <li>Plays mono SIDs and ONEfilers on a middle class mobile phone and multi-disk demos on PC</li>
                      <li>Runs near to native speed, performance only depends on your max. single core speed</li>
                      <li>Runs completely on the client side in a web worker (once in browser's cache)</li>
                      <li>Full emulation quality, no compromise, C64, Floppy and more</li>
                      <li>
                        Developed single source in JSIDPlay2 project, enhancements are automatically available in all
                        versions
                      </li>
                      <li>For the first time, embed music or demos in YOUR web-site</li>
                    </ol>
                    If you want to add C64 content to your web-space, a README and example HTML code of C64jukebox
                    JavaScript UMD Version can be found at:<br />
                    <a href="https://haendel.ddns.net/~ken/jsidplay2-4.10-js.zip"
                      >https://haendel.ddns.net/~ken/jsidplay2-4.10-js.zip</a
                    >
                  </div>
                </div>
              </div>
              <div class="tab-pane fade show" id="cfg" role="tabpanel" aria-labelledby="cfg-tab">
                <div class="settings-box">
                  <div class="button-box">
                    <button
                      type="button"
                      class="btn btn-outline-success btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#setDefaultModal"
                    >
                      <span>{{ $t("setDefault") }}</span>
                    </button>
                    <!-- Modal -->
                    <div
                      class="modal fade"
                      id="setDefaultModal"
                      tabindex="-1"
                      aria-labelledby="setDefaultModalLabel"
                      aria-hidden="true"
                    >
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="setDefaultModalLabel">{{ $t("confirmationTitle") }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p>{{ $t("setDefaultReally") }}</p>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" @click="setDefault">
                              OK
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card">
                  <div class="card-header">
                    <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button
                          class="nav-link active"
                          id="audiocfg-tab"
                          data-bs-toggle="pill"
                          data-bs-target="#audiocfg"
                          type="button"
                          role="tab"
                          aria-controls="audiocfg"
                          aria-selected="true"
                        >
                          {{ $t("audioCfgHeader") }}
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button
                          class="nav-link"
                          id="videocfg-tab"
                          data-bs-toggle="pill"
                          data-bs-target="#videocfg"
                          type="button"
                          role="tab"
                          aria-controls="videocfg"
                          aria-selected="false"
                        >
                          {{ $t("videoCfgHeader") }}
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button
                          class="nav-link"
                          id="emulationcfg-tab"
                          data-bs-toggle="pill"
                          data-bs-target="#emulationcfg"
                          type="button"
                          role="tab"
                          aria-controls="emulationcfg"
                          aria-selected="false"
                        >
                          {{ $t("emulationCfgHeader") }}
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button
                          class="nav-link"
                          id="filtercfg-tab"
                          data-bs-toggle="pill"
                          data-bs-target="#filtercfg"
                          type="button"
                          role="tab"
                          aria-controls="filtercfg"
                          aria-selected="false"
                        >
                          {{ $t("filterCfgHeader") }}
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button
                          class="nav-link"
                          id="mute-tab"
                          data-bs-toggle="pill"
                          data-bs-target="#mute"
                          type="button"
                          role="tab"
                          aria-controls="mute"
                          aria-selected="false"
                        >
                          {{ $t("mutingCfgHeader") }}
                        </button>
                      </li>
                    </ul>
                  </div>
                  <div class="tab-content card-body" style="position: relative">
                    <div class="tab-pane fade show active" id="audiocfg" role="tabpanel" aria-labelledby="audiocfg-tab">
                      <div class="form-check">
                        <div class="settings-box">
                          <span class="setting">
                            <label for="mainVolume"
                              >{{ $t("mainVolume") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="mainVolume"
                                  class="form-control"
                                  min="-6"
                                  max="6"
                                  v-model.number="mainVolume"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> db</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="secondVolume"
                              >{{ $t("secondVolume") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="secondVolume"
                                  class="form-control"
                                  min="-6"
                                  max="6"
                                  v-model.number="secondVolume"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> db</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="thirdVolume"
                              >{{ $t("thirdVolume") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="thirdVolume"
                                  class="form-control"
                                  min="-6"
                                  max="6"
                                  v-model.number="thirdVolume"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> db</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="mainBalance"
                              >{{ $t("mainBalance") }}
                              <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">l(0) ... </span>
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="mainBalance"
                                  class="form-control"
                                  min="0"
                                  max="1"
                                  step="0.1"
                                  v-model.number="mainBalance"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> ... r(1)</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="secondBalance"
                              >{{ $t("secondBalance") }}
                              <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">l(0) ... </span>
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="secondBalance"
                                  class="form-control"
                                  min="0"
                                  max="1"
                                  step="0.1"
                                  v-model.number="secondBalance"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> ... r(1)</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="thirdBalance"
                              >{{ $t("thirdBalance") }}
                              <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">l(0) ... </span>
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="thirdBalance"
                                  class="form-control"
                                  min="0"
                                  max="1"
                                  step="0.1"
                                  v-model.number="thirdBalance"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text"> ... r(1)</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="mainDelay"
                              >{{ $t("mainDelay") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="mainDelay"
                                  class="form-control"
                                  min="0"
                                  max="100"
                                  step="1"
                                  v-model.number="mainDelay"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text">ms</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="secondDelay"
                              >{{ $t("secondDelay") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="secondDelay"
                                  class="form-control"
                                  min="0"
                                  max="100"
                                  step="1"
                                  v-model.number="secondDelay"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text">ms</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="thirdDelay"
                              >{{ $t("thirdDelay") }}
                              <div class="input-group input-group-sm mb-2">
                                <input
                                  class="form-control right"
                                  type="number"
                                  id="thirdDelay"
                                  class="form-control"
                                  min="0"
                                  max="100"
                                  step="1"
                                  v-model.number="thirdDelay"
                                  @change="setVolumeLevels()"
                                />
                                <span class="input-group-text">ms</span>
                              </div>
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <div class="form-check">
                              <label class="form-check-label" for="reverbBypass">
                                {{ $t("reverbBypass") }}
                                <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                                <input
                                  class="form-check-input"
                                  type="checkbox"
                                  id="reverbBypass"
                                  style="float: right; margin-left: 8px"
                                  v-model="reverbBypass"
                                />
                              </label>
                            </div>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="startSong"
                              >{{ $t("startSong") }}
                              <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                              <select class="form-select form-select-sm right" id="startSong" v-model="startSong">
                                <option v-for="n in startSongs" :value="n">{{ n }}</option>
                              </select>
                            </label>
                          </span>
                        </div>

                        <div class="settings-box">
                          <span class="setting">
                            <label class="form-check-label" for="sidWrites">
                              <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="sidWrites"
                                style="float: right; margin-left: 8px"
                                v-model="sidWrites"
                              />
                              {{ $t("sidWrites") }}
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <span class="text-warning">{{ $t("warningRestart") }}</span>
                            <i
                              class="bi bi-exclamation btn btn-sm fw-bold btn-warning fw-bolder"
                              style="float: left"
                            ></i>
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="videocfg" role="tabpanel" aria-labelledby="videocfg-tab">
                      <div class="form-check">
                        <div class="settings-box">
                          <span class="setting">
                            <div class="form-check">
                              <label class="form-check-label" for="palEmulation">
                                {{ $t("palEmulation") }}
                                <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                                <input
                                  class="form-check-input"
                                  type="checkbox"
                                  id="palEmulation"
                                  style="float: right; margin-left: 8px"
                                  v-model="palEmulation"
                                />
                              </label>
                            </div>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <label for="nthFrame">
                              <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                              <select class="form-select form-select-sm right" id="nthFrame" v-model="nthFrame">
                                <option v-for="n in nthFrames" :value="n">{{ n }}</option>
                              </select>
                              {{ $t("nthFrame") }}
                            </label>
                          </span>
                        </div>
                        <div class="settings-box">
                          <span class="setting">
                            <span class="text-warning">{{ $t("warningRestart") }}</span>
                            <i
                              class="bi bi-exclamation btn btn-sm fw-bold btn-warning fw-bolder"
                              style="float: left"
                            ></i>
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="emulationcfg" role="tabpanel" aria-labelledby="emulationcfg-tab">
                      <div class="settings-box">
                        <span class="setting">
                          <label class="form-check-label" for="stereoModeAuto"> {{ $t("stereoMode") }} </label>
                          <div class="input-group" style="justify-content: flex-end">
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="AUTO"
                                id="stereoModeAuto"
                                v-model="stereoMode"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="stereoModeAuto"> Auto </label>
                            </div>
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="STEREO"
                                id="stereoMode2Sid"
                                v-model="stereoMode"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="stereoMode2Sid"> 2-SID </label>
                            </div>
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="THREE_SID"
                                id="stereoMode3Sid"
                                v-model="stereoMode"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="stereoMode3Sid"> 3-SID </label>
                            </div>
                          </div>
                        </span>
                      </div>

                      <div class="settings-box">
                        <span class="setting">
                          <label for="dualSidBase">
                            <select
                              class="form-select form-select-sm right"
                              id="dualSidBase"
                              v-model="dualSidBase"
                              @change="setStereo()"
                            >
                              <option value="54304">0xd420</option>
                              <option value="54336">0xd440</option>
                              <option value="54528">0xd500</option>
                              <option value="56832">0xde00</option>
                              <option value="57088">0xdf00</option>
                            </select>
                            <span>{{ $t("dualSidBase") }}</span>
                          </label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <label for="thirdSIDBase">
                            <select
                              class="form-select form-select-sm right"
                              id="thirdSIDBase"
                              v-model="thirdSIDBase"
                              @change="setStereo()"
                            >
                              <option value="54304">0xd420</option>
                              <option value="54336">0xd440</option>
                              <option value="54528">0xd500</option>
                              <option value="56832">0xde00</option>
                              <option value="57088">0xdf00</option>
                            </select>
                            <span>{{ $t("thirdSIDBase") }}</span>
                          </label>
                        </span>
                      </div>

                      <div class="settings-box">
                        <span class="setting">
                          <label for="defaultClockSpeed">
                            <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                            <select
                              class="form-select form-select-sm right"
                              id="defaultClockSpeed"
                              v-model="defaultClockSpeed"
                            >
                              <option value="50">PAL</option>
                              <option value="60">NTSC</option>
                            </select>
                            <span>{{ $t("defaultClockSpeed") }}</span>
                          </label>
                        </span>
                      </div>

                      <div class="settings-box">
                        <span class="setting">
                          <label for="defaultEmulation">
                            <select
                              class="form-select form-select-sm right"
                              id="defaultEmulation"
                              v-model="defaultEmulation"
                              @change="setDefaultEmulation(defaultEmulation)"
                            >
                              <option value="RESID">RESID</option>
                              <option value="RESIDFP">RESIDFP</option>
                            </select>
                            <span>{{ $t("defaultEmulation") }}</span>
                          </label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <label for="defaultSidModel">
                            <select
                              class="form-select form-select-sm right"
                              id="defaultSidModel"
                              v-model="defaultSidModel"
                              @change="setDefaultSidModel(defaultSidModel)"
                            >
                              <option value="MOS6581">MOS6581</option>
                              <option value="MOS8580">MOS8580</option>
                            </select>
                            <span>{{ $t("defaultSidModel") }}</span>
                          </label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <label for="sampling">
                            <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                            <select class="form-select form-select-sm right" id="sampling" v-model="sampling">
                              <option value="false">DECIMATE</option>
                              <option value="true">RESAMPLE</option>
                            </select>
                            <span>{{ $t("sampling") }}</span>
                          </label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <div class="form-check">
                            <label class="form-check-label" for="fakeStereo">
                              {{ $t("fakeStereo") }}
                              <input
                                class="form-check-input"
                                type="checkbox"
                                id="fakeStereo"
                                style="float: right; margin-left: 8px"
                                v-model="fakeStereo"
                                @change="setStereo()"
                              />
                            </label>
                          </div>
                        </span>
                      </div>

                      <div class="settings-box">
                        <span class="setting">
                          <label class="form-check-label" for="firstSid">
                            {{ $t("sidToRead") }}
                          </label>
                          <div class="input-group" style="justify-content: flex-end">
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="FIRST_SID"
                                id="firstSid"
                                v-model="sidToRead"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="firstSid"> {{ $t("firstSid") }} </label>
                            </div>
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="SECOND_SID"
                                id="secondSid"
                                v-model="sidToRead"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="secondSid"> {{ $t("secondSid") }} </label>
                            </div>
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="radio"
                                value="THIRD_SID"
                                id="thirdSid"
                                v-model="sidToRead"
                                @change="setStereo()"
                              />
                              <label class="form-check-label" for="thirdSid"> {{ $t("thirdSid") }}</label>
                            </div>
                          </div>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <label for="bufferSize"
                            >{{ $t("bufferSize") }}
                            <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                            <input
                              class="right"
                              type="number"
                              id="bufferSize"
                              class="form-control"
                              v-model.number="bufferSize"
                          /></label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <label for="audioBufferSize"
                            >{{ $t("audioBufferSize") }}
                            <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                            <input
                              class="right"
                              type="number"
                              id="audioBufferSize"
                              class="form-control"
                              v-model.number="audioBufferSize"
                          /></label>
                        </span>
                      </div>
                      <div class="settings-box">
                        <span class="setting">
                          <span class="text-warning">{{ $t("warningRestart") }}</span>
                          <i class="bi bi-exclamation btn btn-sm btn-warning fw-bolder" style="float: left"></i>
                        </span>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="filtercfg" role="tabpanel" aria-labelledby="filtercfg-tab">
                      <div class="card">
                        <div class="card-header">
                          <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                            <li class="nav-item" role="presentation">
                              <button
                                class="nav-link active"
                                id="residfpfilter-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#residfpfilter"
                                type="button"
                                role="tab"
                                aria-controls="residfpfilter"
                                aria-selected="true"
                              >
                                {{ $t("residFpFilterCfgHeader") }}
                              </button>
                            </li>
                            <li class="nav-item" role="presentation">
                              <button
                                class="nav-link"
                                id="residfilter-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#residfilter"
                                type="button"
                                role="tab"
                                aria-controls="residfilter"
                                aria-selected="false"
                              >
                                {{ $t("residFilterCfgHeader") }}
                              </button>
                            </li>
                          </ul>
                        </div>
                        <div class="tab-content card-body" style="position: relative">
                          <div
                            class="tab-pane fade show active"
                            id="residfpfilter"
                            role="tabpanel"
                            aria-labelledby="residfpfilter-tab"
                          >
                            <div class="card">
                              <div class="card-header">
                                <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                  <li class="nav-item" role="presentation">
                                    <button
                                      class="nav-link active"
                                      id="residfpfilter6581-tab"
                                      data-bs-toggle="pill"
                                      data-bs-target="#residfpfilter6581"
                                      type="button"
                                      role="tab"
                                      aria-controls="residfpfilter6581"
                                      aria-selected="true"
                                    >
                                      {{ $t("residFpFilter6581CfgHeader") }}
                                    </button>
                                  </li>
                                  <li class="nav-item" role="presentation">
                                    <button
                                      class="nav-link"
                                      id="residfilterfp8580-tab"
                                      data-bs-toggle="pill"
                                      data-bs-target="#residfpfilter8580"
                                      type="button"
                                      role="tab"
                                      aria-controls="residfpfilter8580"
                                      aria-selected="false"
                                    >
                                      {{ $t("residFpFilter8580CfgHeader") }}
                                    </button>
                                  </li>
                                </ul>
                              </div>
                              <div class="tab-content card-body" style="position: relative">
                                <div
                                  class="tab-pane fade show active"
                                  id="residfpfilter6581"
                                  role="tabpanel"
                                  aria-labelledby="residfpfilter6581-tab"
                                >
                                  <div class="card">
                                    <div class="card-header">
                                      <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link active"
                                            id="reSIDfpFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpFiltername6581"
                                            aria-selected="true"
                                          >
                                            {{ $t("reSIDfpFilter6581Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDfpStereoFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpStereoFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpStereoFiltername6581"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDfpStereoFilter6581Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDfpThirdSIDFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpThirdSIDFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpThirdSIDFiltername6581"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDfpThirdSIDFilter6581Header") }}
                                          </button>
                                        </li>
                                      </ul>
                                    </div>
                                    <div class="tab-content card-body" style="position: relative">
                                      <div
                                        class="tab-pane fade show active"
                                        id="reSIDfpFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpFilter6581">
                                              {{ $t("reSIDfpFilter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpFilter6581"
                                                v-model="reSIDfpFilter6581"
                                                @change="setFilterName('RESIDFP', 'MOS6581', 0, reSIDfpFilter6581)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDfpStereoFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpStereoFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpStereoFilter6581">
                                              {{ $t("reSIDfpStereoFilter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpStereoFilter6581"
                                                v-model="reSIDfpStereoFilter6581"
                                                @change="
                                                  setFilterName('RESIDFP', 'MOS6581', 1, reSIDfpStereoFilter6581)
                                                "
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDfpThirdSIDFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpThirdSIDFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpThirdSIDFilter6581">
                                              {{ $t("reSIDfpThirdSIDFilter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpThirdSIDFilter6581"
                                                v-model="reSIDfpThirdSIDFilter6581"
                                                @change="
                                                  setFilterName('RESIDFP', 'MOS6581', 2, reSIDfpThirdSIDFilter6581)
                                                "
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div
                                  class="tab-pane fade show"
                                  id="residfpfilter8580"
                                  role="tabpanel"
                                  aria-labelledby="residfpfilter8580-tab"
                                >
                                  <div class="card">
                                    <div class="card-header">
                                      <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link active"
                                            id="reSIDfpFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpFiltername8580"
                                            aria-selected="true"
                                          >
                                            {{ $t("reSIDfpFilter8580Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDfpStereoFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpStereoFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpStereoFiltername8580"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDfpStereoFilter8580Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDfpThirdSIDFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDfpThirdSIDFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDfpThirdSIDFiltername8580"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDfpThirdSIDFilter8580Header") }}
                                          </button>
                                        </li>
                                      </ul>
                                    </div>
                                    <div class="tab-content card-body" style="position: relative">
                                      <div
                                        class="tab-pane fade show active"
                                        id="reSIDfpFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpFilter8580">
                                              {{ $t("reSIDfpFilter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpFilter8580"
                                                v-model="reSIDfpFilter8580"
                                                @change="setFilterName('RESIDFP', 'MOS8580', 0, reSIDfpFilter8580)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDfpStereoFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpStereoFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpStereoFilter8580">
                                              {{ $t("reSIDfpStereoFilter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpStereoFilter8580"
                                                v-model="reSIDfpStereoFilter8580"
                                                @change="
                                                  setFilterName('RESIDFP', 'MOS8580', 1, reSIDfpStereoFilter8580)
                                                "
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDfpThirdSIDFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDfpThirdSIDFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDfpThirdSIDFilter8580">
                                              {{ $t("reSIDfpThirdSIDFilter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDfpThirdSIDFilter8580"
                                                v-model="reSIDfpThirdSIDFilter8580"
                                                @change="
                                                  setFilterName('RESIDFP', 'MOS8580', 2, reSIDfpThirdSIDFilter8580)
                                                "
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDfpFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div
                            class="tab-pane fade show"
                            id="residfilter"
                            role="tabpanel"
                            aria-labelledby="residfilter-tab"
                          >
                            <div class="card">
                              <div class="card-header">
                                <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                  <li class="nav-item" role="presentation">
                                    <button
                                      class="nav-link active"
                                      id="residfilter6581-tab"
                                      data-bs-toggle="pill"
                                      data-bs-target="#residfilter6581"
                                      type="button"
                                      role="tab"
                                      aria-controls="residfilter6581"
                                      aria-selected="true"
                                    >
                                      {{ $t("residFilter6581CfgHeader") }}
                                    </button>
                                  </li>
                                  <li class="nav-item" role="presentation">
                                    <button
                                      class="nav-link"
                                      id="residfilter8580-tab"
                                      data-bs-toggle="pill"
                                      data-bs-target="#residfilter8580"
                                      type="button"
                                      role="tab"
                                      aria-controls="residfilter8580"
                                      aria-selected="false"
                                    >
                                      {{ $t("residFilter8580CfgHeader") }}
                                    </button>
                                  </li>
                                </ul>
                              </div>
                              <div class="tab-content card-body" style="position: relative">
                                <div
                                  class="tab-pane fade show active"
                                  id="residfilter6581"
                                  role="tabpanel"
                                  aria-labelledby="residfilter6581-tab"
                                >
                                  <div class="card">
                                    <div class="card-header">
                                      <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link active"
                                            id="reSIDFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDFiltername6581"
                                            aria-selected="true"
                                          >
                                            {{ $t("reSIDFilter6581Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDStereoFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDStereoFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDStereoFiltername6581"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDStereoFilter6581Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDThirdSIDFiltername6581-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDThirdSIDFiltername6581"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDThirdSIDFiltername6581"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDThirdSIDFilter6581Header") }}
                                          </button>
                                        </li>
                                      </ul>
                                    </div>
                                    <div class="tab-content card-body" style="position: relative">
                                      <div
                                        class="tab-pane fade show active"
                                        id="reSIDFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDFilter6581">
                                              {{ $t("filter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDFilter6581"
                                                v-model="filter6581"
                                                @change="setFilterName('RESID', 'MOS6581', 0, filter6581)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDStereoFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDStereoFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDStereoFilter6581">
                                              {{ $t("stereoFilter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDStereoFilter6581"
                                                v-model="stereoFilter6581"
                                                @change="setFilterName('RESID', 'MOS6581', 1, stereoFilter6581)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDThirdSIDFiltername6581"
                                        role="tabpanel"
                                        aria-labelledby="reSIDThirdSIDFiltername6581-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDThirdSIDFilter6581">
                                              {{ $t("thirdSIDFilter6581") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDThirdSIDFilter6581"
                                                v-model="thirdSIDFilter6581"
                                                @change="setFilterName('RESID', 'MOS6581', 2, thirdSIDFilter6581)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters6581">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div
                                  class="tab-pane fade show"
                                  id="residfilter8580"
                                  role="tabpanel"
                                  aria-labelledby="residfilter8580-tab"
                                >
                                  <div class="card">
                                    <div class="card-header">
                                      <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link active"
                                            id="reSIDFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDFiltername8580"
                                            aria-selected="true"
                                          >
                                            {{ $t("reSIDFilter8580Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDStereoFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDStereoFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDStereoFiltername8580"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDStereoFilter8580Header") }}
                                          </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button
                                            class="nav-link"
                                            id="reSIDThirdSIDFiltername8580-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#reSIDThirdSIDFiltername8580"
                                            type="button"
                                            role="tab"
                                            aria-controls="reSIDThirdSIDFiltername8580"
                                            aria-selected="false"
                                          >
                                            {{ $t("reSIDThirdSIDFilter8580Header") }}
                                          </button>
                                        </li>
                                      </ul>
                                    </div>
                                    <div class="tab-content card-body" style="position: relative">
                                      <div
                                        class="tab-pane fade show active"
                                        id="reSIDFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDFilter8580">
                                              {{ $t("filter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDFilter8580"
                                                v-model="filter8580"
                                                @change="setFilterName('RESID', 'MOS8580', 0, filter8580)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDStereoFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDStereoFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDStereoFilter8580">
                                              {{ $t("stereoFilter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDStereoFilter8580"
                                                v-model="stereoFilter8580"
                                                @change="setFilterName('RESID', 'MOS8580', 1, stereoFilter8580)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                      <div
                                        class="tab-pane fade show"
                                        id="reSIDThirdSIDFiltername8580"
                                        role="tabpanel"
                                        aria-labelledby="reSIDThirdSIDFiltername8580-tab"
                                      >
                                        <div class="settings-box">
                                          <span class="setting"
                                            ><label for="reSIDThirdSIDFilter8580">
                                              {{ $t("thirdSIDFilter8580") }}
                                              <select
                                                class="form-select form-select-sm right"
                                                id="reSIDThirdSIDFilter8580"
                                                v-model="thirdSIDFilter8580"
                                                @change="setFilterName('RESID', 'MOS8580', 2, thirdSIDFilter8580)"
                                                size="3"
                                              >
                                                <option v-for="filter in reSIDFilters8580">{{ filter }}</option>
                                              </select></label
                                            ></span
                                          >
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade show" id="mute" role="tabpanel" aria-labelledby="mute-tab">
                      <div class="card">
                        <div class="card-header">
                          <ul class="nav nav-pills card-header-pills mb-2 right" role="tablist">
                            <li class="nav-item" role="presentation">
                              <button
                                class="nav-link active"
                                id="muteSid-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#muteSid"
                                type="button"
                                role="tab"
                                aria-controls="muteSid"
                                aria-selected="true"
                              >
                                {{ $t("muteSidHeader") }}
                              </button>
                            </li>
                            <li class="nav-item" role="presentation">
                              <button
                                class="nav-link"
                                id="muteStereoSid-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#muteStereoSid"
                                type="button"
                                role="tab"
                                aria-controls="muteStereoSid"
                                aria-selected="false"
                              >
                                {{ $t("muteStereoSidHeader") }}
                              </button>
                            </li>
                            <li class="nav-item" role="presentation">
                              <button
                                class="nav-link"
                                id="muteThirdSID-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#muteThirdSID"
                                type="button"
                                role="tab"
                                aria-controls="muteThirdSID"
                                aria-selected="false"
                              >
                                {{ $t("muteThirdSidHeader") }}
                              </button>
                            </li>
                          </ul>
                        </div>
                        <div class="tab-content card-body" style="position: relative">
                          <div
                            class="tab-pane fade show active"
                            id="muteSid"
                            role="tabpanel"
                            aria-labelledby="muteSid-tab"
                          >
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteVoice1">
                                    {{ $t("muteVoice1") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteVoice1"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteVoice1"
                                      @change="setMute(0, 0, muteVoice1)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteVoice2">
                                    {{ $t("muteVoice2") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteVoice2"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteVoice2"
                                      @change="setMute(0, 1, muteVoice2)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteVoice3">
                                    {{ $t("muteVoice3") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteVoice3"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteVoice3"
                                      @change="setMute(0, 2, muteVoice3)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteVoice4">
                                    {{ $t("muteVoice4") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteVoice4"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteVoice4"
                                      @change="setMute(0, 3, muteVoice4)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                          </div>
                          <div
                            class="tab-pane fade show"
                            id="muteStereoSid"
                            role="tabpanel"
                            aria-labelledby="muteStereoSid-tab"
                          >
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteStereoVoice1">
                                    {{ $t("muteStereoVoice1") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteStereoVoice1"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteStereoVoice1"
                                      @change="setMute(1, 0, muteStereoVoice1)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteStereoVoice2">
                                    {{ $t("muteStereoVoice2") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteStereoVoice2"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteStereoVoice2"
                                      @change="setMute(1, 1, muteStereoVoice2)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteStereoVoice3">
                                    {{ $t("muteStereoVoice3") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteStereoVoice3"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteStereoVoice3"
                                      @change="setMute(1, 2, muteStereoVoice3)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteStereoVoice4">
                                    {{ $t("muteStereoVoice4") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteStereoVoice4"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteStereoVoice4"
                                      @change="setMute(1, 3, muteStereoVoice4)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                          </div>
                          <div
                            class="tab-pane fade show"
                            id="muteThirdSID"
                            role="tabpanel"
                            aria-labelledby="muteThirdSID-tab"
                          >
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteThirdSIDVoice1">
                                    {{ $t("muteThirdSIDVoice1") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteThirdSIDVoice1"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteThirdSIDVoice1"
                                      @change="setMute(2, 0, muteThirdSIDVoice1)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteThirdSIDVoice2">
                                    {{ $t("muteThirdSIDVoice2") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteThirdSIDVoice2"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteThirdSIDVoice2"
                                      @change="setMute(2, 1, muteThirdSIDVoice2)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteThirdSIDVoice3">
                                    {{ $t("muteThirdSIDVoice3") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteThirdSIDVoice3"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteThirdSIDVoice3"
                                      @change="setMute(2, 2, muteThirdSIDVoice3)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                            <div class="settings-box">
                              <span class="setting">
                                <div class="form-check">
                                  <label class="form-check-label" for="muteThirdSIDVoice4">
                                    {{ $t("muteThirdSIDVoice4") }}
                                    <input
                                      class="form-check-input"
                                      type="checkbox"
                                      id="muteThirdSIDVoice4"
                                      style="float: right; margin-left: 8px"
                                      v-model="muteThirdSIDVoice4"
                                      @change="setMute(2, 3, muteThirdSIDVoice4)"
                                    />
                                  </label>
                                </div>
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <script>
      var size = 0;
      function Queue() {
        var head, tail;
        return Object.freeze({
          enqueue(value) {
            const link = { value, next: undefined };
            tail = head ? (tail.next = link) : (head = link);
            size++;
          },
          dequeue() {
            if (head) {
              var value = head.value;
              head = head.next;
              size--;
              return value;
            }
            return undefined;
          },
          peek() {
            return head?.value;
          },
          clear() {
            tail = head = undefined;
            size = 0;
          },
          isNotEmpty() {
            return head;
          },
        });
      }

      const maxWidth = 384;
      const maxHeight = 312;

      var worker;

      var AudioContext = window.AudioContext || window.webkitAudioContext;
      var audioContext;
      var nextTime, fix;

      var canvasContext;
      var imageData, data;
      var imageQueue = new Queue();
      let msPrev;
      let frames;

      function toC64KeyTableEntry(code) {
        switch (code) {
          case "IntlBackslash":
            return "ARROW_LEFT";
          case "Digit1":
            return "ONE";
          case "Digit2":
            return "TWO";
          case "Digit3":
            return "THREE";
          case "Digit4":
            return "FOUR";
          case "Digit5":
            return "FIVE";
          case "Digit6":
            return "SIX";
          case "Digit7":
            return "SEVEN";
          case "Digit8":
            return "EIGHT";
          case "Digit9":
            return "NINE";
          case "Digit0":
            return "ZERO";
          case "NumpadAdd":
            return "PLUS";
          case "NumpadSubtract":
            return "MINUS";
          case "^":
            return "POUND";
          case "Delete":
            return "CLEAR_HOME";
          case "Backspace":
            return "INS_DEL";
          case "ControlRight":
            return "CTRL";
          case "KeyQ":
            return "Q";
          case "KeyW":
            return "W";
          case "KeyE":
            return "E";
          case "KeyR":
            return "R";
          case "KeyT":
            return "T";
          case "KeyZ":
            return "Y";
          case "KeyU":
            return "U";
          case "KeyI":
            return "I";
          case "KeyO":
            return "O";
          case "KeyP":
            return "P";
          case "@":
            return "AT";
          case "NumpadMultiply":
            return "STAR";
          case "ArrowUp":
            return "ARROW_UP";
          case "Escape":
            return "RUN_STOP";
          case "KeyA":
            return "A";
          case "KeyS":
            return "S";
          case "KeyD":
            return "D";
          case "KeyF":
            return "F";
          case "KeyG":
            return "G";
          case "KeyH":
            return "H";
          case "KeyJ":
            return "J";
          case "KeyK":
            return "K";
          case "KeyL":
            return "L";
          case "Semicolon":
            return "SEMICOLON";
          case "Equal":
            return "EQUALS";
          case "Enter":
            return "RETURN";
          case "ControlLeft":
            return "COMMODORE";
          case "ShiftLeft":
            return "SHIFT_LEFT";
          case "KeyY":
            return "Z";
          case "KeyX":
            return "X";
          case "KeyC":
            return "C";
          case "KeyV":
            return "V";
          case "KeyB":
            return "B";
          case "KeyN":
            return "N";
          case "KeyM":
            return "M";
          case "Comma":
            return "COMMA";
          case "Period":
            return "PERIOD";
          case "Slash":
            return "SLASH";
          case "ShiftRight":
            return "SHIFT_RIGHT";
          case "ArrowDown":
            return "CURSOR_UP_DOWN";
          case "ArrowRight":
            return "CURSOR_LEFT_RIGHT";
          case "Space":
            return "SPACE";
          case "Comma":
            return "COMMA";
          case "F1":
            return "F1";
          case "F3":
            return "F3";
          case "F5":
            return "F5";
          case "F7":
            return "F7";
          case "KeyI":
            return "RESTORE";

          default:
            return undefined;
        }
      }

      function jsidplay2Worker(contents, tuneName, cartContents, cartName, command) {
        audioContext = new AudioContext({
          latencyHint: "interactive",
          sampleRate: 48000,
        });

        if (worker) {
          worker.terminate();
          worker = undefined;
        }
        worker = new Worker("js/jsidplay2-js-worker.js", );

        return new Promise((resolve, reject) => {
          worker.postMessage({
            eventType: "INITIALISE",
            eventData: {
              palEmulation: app.palEmulation,
              bufferSize: app.bufferSize,
              audioBufferSize: app.audioBufferSize,
              samplingRate: audioContext.sampleRate,
              samplingMethodResample: "" + app.sampling === "true",
              reverbBypass: app.reverbBypass,
              defaultClockSpeed: app.defaultClockSpeed,
              jiffyDosInstalled: "" + app.jiffyDosInstalled === "true",
            },
          });

          worker.addEventListener("message", function (event) {
            var { eventType, eventData } = event.data;

            if (eventType === "SAMPLES") {
              var buffer = audioContext.createBuffer(2, eventData.length, audioContext.sampleRate);
              buffer.getChannelData(0).set(eventData.left);
              buffer.getChannelData(1).set(eventData.right);

              var sourceNode = audioContext.createBufferSource();
              sourceNode.buffer = buffer;
              sourceNode.connect(audioContext.destination);

              if (nextTime == 0) {
                fix = app.screen ? 0.005 : 0;
                nextTime = audioContext.currentTime + 0.05; // add 50ms latency to work well across systems
              } else if (nextTime < audioContext.currentTime) {
                nextTime = audioContext.currentTime + 0.005; // if samples are not produced fast enough
              }
              sourceNode.start(nextTime);
              nextTime += eventData.length / audioContext.sampleRate + fix;
            } else if (eventType === "FRAME") {
              imageQueue.enqueue({
                image: eventData.image,
              });
            } else if (eventType === "SID_WRITE") {
              console.log("relTime=" + eventData.relTime + ", addr=" + eventData.addr + ", value=" + eventData.value);
            } else if (eventType === "OPENED" || eventType === "CLOCKED") {
              if (eventType === "OPENED") {
                if (app.screen) {
                  app.insertDisk();
                }
                if (app.screen) {
                  app.insertTape();
                }
              }
              if (!app.paused && (!app.screen || lastTotalFrames != totalFrames) && (nextTime - audioContext.currentTime <= 1 || (app.screen && app.framesCounter < (app.defaultClockSpeed / app.nthFrame)))) {
                worker.postMessage({ eventType: "CLOCK" });
                //document.body.style.backgroundColor = "red";
              } else {
                worker.postMessage({ eventType: "IDLE" });
                //document.body.style.backgroundColor = "yellow";
              }
              lastTotalFrames = totalFrames;
            } else if (eventType === "INITIALISED") {

              app.setStereo();
              app.setVolumeLevels();
              app.setDefaultEmulation(app.defaultEmulation);
              app.setDefaultSidModel(app.defaultSidModel);
              app.setFilterName('RESID', 'MOS6581', 0, app.filter6581);
              app.setFilterName('RESID', 'MOS6581', 1, app.stereoFilter6581);
              app.setFilterName('RESID', 'MOS6581', 2, app.thirdSIDFilter6581);
              app.setFilterName('RESID', 'MOS8580', 0, app.filter8580);
              app.setFilterName('RESID', 'MOS8580', 1, app.stereoFilter8580);
              app.setFilterName('RESID', 'MOS8580', 2, app.thirdSIDFilter8580);
              app.setFilterName('RESIDFP', 'MOS6581', 0, app.reSIDfpFilter6581);
              app.setFilterName('RESIDFP', 'MOS6581', 1, app.reSIDfpStereoFilter6581);
              app.setFilterName('RESIDFP', 'MOS6581', 2, app.reSIDfpThirdSIDFilter6581);
              app.setFilterName('RESIDFP', 'MOS8580', 0, app.reSIDfpFilter8580);
              app.setFilterName('RESIDFP', 'MOS8580', 1, app.reSIDfpStereoFilter8580);
              app.setFilterName('RESIDFP', 'MOS8580', 2, app.reSIDfpThirdSIDFilter8580);
              app.setMute(0, 0, app.muteVoice1);
              app.setMute(0, 1, app.muteVoice2);
              app.setMute(0, 2, app.muteVoice3);
              app.setMute(0, 3, app.muteVoice4);
              app.setMute(1, 0, app.muteStereoVoice1);
              app.setMute(1, 1, app.muteStereoVoice2);
              app.setMute(1, 2, app.muteStereoVoice3);
              app.setMute(1, 3, app.muteStereoVoice4);
              app.setMute(2, 0, app.muteThirdSIDVoice1);
              app.setMute(2, 1, app.muteThirdSIDVoice2);
              app.setMute(2, 2, app.muteThirdSIDVoice3);
              app.setMute(2, 3, app.muteThirdSIDVoice4);

              worker.postMessage({
                eventType: "OPEN",
                eventData: {
                  contents: contents,
                  tuneName: tuneName,
                  startSong: app.startSong,
                  nthFrame: app.screen ? app.nthFrame : 0,
                  sidWrites: app.sidWrites,
                  cartContents: cartContents,
                  cartName: cartName,
                  command: command,
                },
              });

              nextTime = 0;
              imageQueue.clear();
              app.framesCounter = app.defaultClockSpeed / app.nthFrame;
              app.playing = true;
              app.paused = false;
              app.clearScreen();
              frames = totalFrames = lastTotalFrames = actualFrames = 0;
              if (app.screen) {
                msPrev = window.performance.now()
                app.animate();
              }
            }
          });

          worker.addEventListener("error", function (error) {
            reject(error);
          });
        });
      }

      const { createApp, ref } = Vue;

      const { createI18n } = VueI18n;

      let i18n = createI18n({
        legacy: false,
        locale: "en",
        messages: {
          en: {
            FileMenu: "File",
            palEmulation: "PAL emulation",
            jiffyDosInstalled: "JiffyDOS",
            reverbBypass: "Bypass Schroeder reverb",
            sidWrites: "Print SID writes to console",
            startSong: "Start song",
            nthFrame: "Show every nth frame",
            file: "File",
            play: "Load SID/PRG/P00/T64",
            player: "Player",
            pauseContinue: "Pause/Continue",
            fastForward: "Fast Forward",
            normalSpeed: "Normal Speed",
            reset: "Power On / Reset",
            stop: "Stop",
            restart: "Restart",
            devices: "Devices",
            floppy: "Floppy",
            insertDisk: "Insert Disk",
            ejectDisk: "Eject Disk",
            tape: "Tape",
            insertTape: "Insert Tape",
            pressPlayOnTape: "Press Play on Tape",
            ejectTape: "Eject Tape",
            cart: "Cart",
            insertCart: "Insert Cartridge",
            ejectCart: "Eject Cartridge",
            freezeCartridge: "Freeze Cartridge",
            loadDisk: "Load *,8,1",
            loadTape: "Load",
            space: "Space Key",
            exampleMusic: "Music",
            exampleOneFiler: "OneFiler",
            exampleDemos: "Demos",
            fps: "FPS",
            ABOUT: "About",
            VIDEO: "Screen",
            CFG: "Configuration",
            audioCfgHeader: "Audio",
            videoCfgHeader: "Video",
            emulationCfgHeader: "Emulation",
            filterCfgHeader: "Filter",
            residFpFilterCfgHeader: "RESIDFP",
            residFilterCfgHeader: "RESID",
            residFpFilter6581CfgHeader: "MOS6581",
            residFpFilter8580CfgHeader: "MOS8580",
            residFilter6581CfgHeader: "MOS6581",
            residFilter8580CfgHeader: "MOS8580",
            reSIDfpFilter6581Header: "SID",
            reSIDfpStereoFilter6581Header: "Stereo SID",
            reSIDfpThirdSIDFilter6581Header: "3rd SID",
            reSIDfpFilter8580Header: "SID",
            reSIDfpStereoFilter8580Header: "Stereo SID",
            reSIDfpThirdSIDFilter8580Header: "3rd SID",
            reSIDFilter6581Header: "SID",
            reSIDStereoFilter6581Header: "Stereo SID",
            reSIDThirdSIDFilter6581Header: "3rd SID",
            reSIDFilter8580Header: "SID",
            reSIDStereoFilter8580Header: "Stereo SID",
            reSIDThirdSIDFilter8580Header: "3rd SID",
            reSIDfpFilter6581: "Filter name of SID 6581 (RESIDFP)",
            reSIDfpFilter8580: "Filter name of SID 8580 (RESIDFP)",
            reSIDfpStereoFilter6581: "Filter name of Stereo SID 6581 (RESIDFP)",
            reSIDfpStereoFilter8580: "Filter name of Stereo SID 8580 (RESIDFP)",
            reSIDfpThirdSIDFilter6581: "Filter name of 3rd SID 6581 (RESIDFP)",
            reSIDfpThirdSIDFilter8580: "Filter name of 3rd SID 8580 (RESIDFP)",
            filter6581: "Filter name of SID 6581 (RESID)",
            filter8580: "Filter name of SID 8580 (RESID)",
            stereoFilter6581: "Filter name of Stereo SID 6581 (RESID)",
            stereoFilter8580: "Filter name of Stereo SID 8580 (RESID)",
            thirdSIDFilter6581: "Filter name of 3rd SID 6581 (RESID)",
            thirdSIDFilter8580: "Filter name of 3rd SID 8580 (RESID)",
            mutingCfgHeader: "Muting",
            muteSidHeader: "SID",
            muteStereoSidHeader: "Stereo SID",
            muteThirdSidHeader: "3rd SID",
            muteVoice1: "mute voice 1",
            muteVoice2: "mute voice 2",
            muteVoice3: "mute voice 3",
            muteVoice4: "mute samples",
            muteStereoVoice1: "mute voice 1 (stereo-SID)",
            muteStereoVoice2: "mute voice 2 (stereo-SID)",
            muteStereoVoice3: "mute voice 3 (stereo-SID)",
            muteStereoVoice4: "mute samples (stereo-SID)",
            muteThirdSIDVoice1: "mute voice 1 (3-SID)",
            muteThirdSIDVoice2: "mute voice 2 (3-SID)",
            muteThirdSIDVoice3: "mute voice 3 (3-SID)",
            muteThirdSIDVoice4: "mute samples (3-SID)",
            stereoMode: "Stereo Mode",
            dualSidBase: "Dual SID adress",
            thirdSIDBase: "third SID adress",
            defaultClockSpeed: "Set default VIC clock speed PAL or NTSC (to be used, if UNKNOWN)",
            defaultEmulation: "Default Emulation (RESID, RESIDFP)",
            sampling: "Sampling Method (DECIMATE=linear interpolation, RESAMPLE=more efficient SINC from chaining two other SINCs)",
            defaultSidModel: "Default chip model MOS8580 or MOS6581 (to be used, if UNKNOWN)",
            fakeStereo: "Fake stereo",
            sidToRead: "Fake stereo: SID number to process READs",
            firstSid: "Main SID",
            secondSid: "Stereo SID",
            thirdSid: "3-SID",
            bufferSize: "Emulation Buffer Size",
            audioBufferSize: "Audio Buffer Size",
            mainVolume: "Volume of SID in db (-6db..+6db)",
            secondVolume: "Volume of Stereo SID in db (-6db..+6db)",
            thirdVolume: "Volume of 3rd SID in db (-6db..+6db)",
            mainBalance: "Balance of SID l(0)..r(1)",
            secondBalance: "Balance of Stereo SID l(0)..r(1)",
            thirdBalance: "Balance of 3rd SID l(0)..r(1)",
            mainDelay: "Delay of SID in ms (0ms..50ms)",
            secondDelay: "Delay of Stereo SID in ms (0ms..50ms)",
            thirdDelay: "Delay of 3rd SID in ms (0ms..50ms)",
            confirmationTitle: "Confirmation Dialogue",
            setDefault: "Restore Defaults",
            setDefaultReally: "Do you really want to restore defaults?",
            warningRestart: "Note: To apply those changes, please restart tune!",
          },
          de: {
            FileMenu: "Datei",
            palEmulation: "PAL Emulation",
            jiffyDosInstalled: "JiffyDOS",
            reverbBypass: "Schroeder Reverb überbrücken",
            sidWrites: "SID writes in Konsole schreiben",
            startSong: "Start Song",
            nthFrame: "Zeige jedes Nte Bild",
            file: "Datei",
            play: "Lade SID/PRG/P00/T64",
            player: "Player",
            pauseContinue: "Pause/Weiter",
            fastForward: "Schnellvorlauf",
            normalSpeed: "Normale Geschw.",
            reset: "C64 Anschalten / Reset",
            stop: "Stop",
            restart: "Restart",
            devices: "Geräte",
            floppy: "Floppy",
            insertDisk: "Diskette einlegen",
            ejectDisk: "Diskette auswerfen",
            tape: "Datasette",
            insertTape: "Kasette einlegen",
            pressPlayOnTape: "Drücke Play",
            ejectTape: "Kasette auswerfen",
            cart: "Modul",
            insertCart: "Modul einlegen",
            ejectCart: "Modul auswerfen",
            freezeCartridge: "Modul einfrieren",
            loadDisk: "Load *,8,1",
            loadTape: "Load",
            space: "Leertaste",
            exampleMusic: "Musik",
            exampleOneFiler: "Programme",
            exampleDemos: "Demos",
            fps: "FPS",
            ABOUT: "Über",
            VIDEO: "Bildschirm",
            CFG: "Konfiguration",
            audioCfgHeader: "Audio",
            videoCfgHeader: "Video",
            emulationCfgHeader: "Emulation",
            filterCfgHeader: "Filter",
            residFpFilterCfgHeader: "RESIDFP",
            residFilterCfgHeader: "RESID",
            residFpFilter6581CfgHeader: "MOS6581",
            residFpFilter8580CfgHeader: "MOS8580",
            residFilter6581CfgHeader: "MOS6581",
            residFilter8580CfgHeader: "MOS8580",
            reSIDfpFilter6581Header: "SID",
            reSIDfpStereoFilter6581Header: "Stereo SID",
            reSIDfpThirdSIDFilter6581Header: "3. SID",
            reSIDfpFilter8580Header: "SID",
            reSIDfpStereoFilter8580Header: "Stereo SID",
            reSIDfpThirdSIDFilter8580Header: "3. SID",
            reSIDFilter6581Header: "SID",
            reSIDStereoFilter6581Header: "Stereo SID",
            reSIDThirdSIDFilter6581Header: "3. SID",
            reSIDFilter8580Header: "SID",
            reSIDStereoFilter8580Header: "Stereo SID",
            reSIDThirdSIDFilter8580Header: "3. SID",
            reSIDfpFilter6581: "Filter name des SID 6581 (RESIDFP)",
            reSIDfpFilter8580: "Filter name des SID 8580 (RESIDFP)",
            reSIDfpStereoFilter6581: "Filter name des Stereo SID 6581 (RESIDFP)",
            reSIDfpStereoFilter8580: "Filter name des Stereo SID 8580 (RESIDFP)",
            reSIDfpThirdSIDFilter6581: "Filter name des 3. SID 6581 (RESIDFP)",
            reSIDfpThirdSIDFilter8580: "Filter name des 3. SID 8580 (RESIDFP)",
            filter6581: "Filter name des SID 6581 (RESID)",
            filter8580: "Filter name des SID 8580 (RESID)",
            stereoFilter6581: "Filter name des Stereo SID 6581 (RESID)",
            stereoFilter8580: "Filter name des Stereo SID 8580 (RESID)",
            thirdSIDFilter6581: "Filter name des 3. SID 6581 (RESID)",
            thirdSIDFilter8580: "Filter name des 3. SID 8580 (RESID)",
            mutingCfgHeader: "Stummschalten",
            muteSidHeader: "SID",
            muteStereoSidHeader: "Stereo SID",
            muteThirdSidHeader: "3. SID",
            muteVoice1: "Stimme 1 stumm schalten",
            muteVoice2: "Stimme 2 stumm schalten",
            muteVoice3: "Stimme 3 stumm schalten",
            muteVoice4: "Samples stumm schalten",
            muteStereoVoice1: "Stimme 1 stumm schalten  (Stereo-SID)",
            muteStereoVoice2: "Stimme 2 stumm schalten  (Stereo-SID)",
            muteStereoVoice3: "Stimme 3 stumm schalten  (Stereo-SID)",
            muteStereoVoice4: "Samples stumm schalten  (Stereo-SID)",
            muteThirdSIDVoice1: "Stimme 1 stumm schalten (3-SID)",
            muteThirdSIDVoice2: "Stimme 2 stumm schalten (3-SID)",
            muteThirdSIDVoice3: "Stimme 3 stumm schalten (3-SID)",
            muteThirdSIDVoice4: "Samples stumm schalten (3-SID)",
            stereoMode: "Stereo Mode",
            dualSidBase: "Stereo SID Adresse",
            thirdSIDBase: "3. SID Adresse",
            defaultClockSpeed: "Default VIC Takt PAL oder NTSC, falls nicht aus der Musikdatei ermittelbar",
            defaultEmulation: "Default Emulation (RESID, RESIDFP)",
            sampling: "Sampling Methode (DECIMATE=lineare Interpolation, RESAMPLE=effizienterer SINC durch Verkettung zwei anderer SINCs)",
            defaultSidModel: "Default SID Chip MOS8580 oder MOS6581, falls nicht aus der Musikdatei ermittelbar",
            fakeStereo: "Fake Stereo",
            sidToRead: "Fake stereo: SID der Lesezugriffe ausführt",
            firstSid: "Haupt SID",
            secondSid: "Stereo SID",
            thirdSid: "3-SID",
            bufferSize: "Emulations Puffer Grösse",
            audioBufferSize: "Audio Puffer Grösse",
            mainVolume: "Lautstärke des SID in db (-6db..+6db)",
            secondVolume: "Lautstärke des Stereo SID in db (-6db..+6db)",
            thirdVolume: "Lautstärke des 3. SID in db (-6db..+6db)",
            mainBalance: "Balance des SID l(0)..r(1)",
            secondBalance: "Balance des Stereo SID l(0)..r(1)",
            thirdBalance: "Balance des 3. SID l(0)..r(1)",
            mainDelay: "Verzögerung des SID in ms (0ms..50ms)",
            secondDelay: "Verzögerung des Stereo SID in ms (0ms..50ms)",
            thirdDelay: "Verzögerung des 3. SID in ms (0ms..50ms)",
            confirmationTitle: "Sicherheitsabfrage",
            setDefault: "Standardeinstellungen wiederherstellen",
            setDefaultReally: "Wollen sie wirklich die Standardeinstellungen wiederherstellen?",
            warningRestart: "Achtung: Damit diesen Einstellungen angewendet werden können, bitte den Tune neu starten!",
          },
        },
      });

      let app = Vue.createApp({
        data: function () {
          return {
            langs: ["de", "en"],
            msg: "",
            playing: false,
            paused: false,
            screen: true,
            palEmulation: true,
            startSong: 0,
            startSongs: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            nthFrame: 2,
            nthFrames: [1, 2, 4, 10, 25, 30, 50, 60],
            jiffyDosInstalled: false,
            sampling: false,
            reverbBypass: true,
            sidWrites: false,
            framesCounter: 0,
            actualFrames: 0,
            showFloppy: false,
            showDemo1: false,
            showDemo2: false,
            showDemo3: false,
            showDemo4: false,
            showDemo5: false,
            showDemo6: false,
            showDemo7: false,
            showDemo8: false,
            showDemo9: false,
            showDemo10: false,
            showTape: false,
            showCart: false,
            wakeLockEnable: false,
            keyboardEnable: false,
            mainVolume: 0,
            secondVolume: 0,
            thirdVolume: 0,
            mainBalance: 0.3,
            secondBalance: 0.7,
            thirdBalance: 0.5,
            mainDelay: 10,
            secondDelay: 0,
            thirdDelay: 0,
            stereoMode: "AUTO",
            dualSidBase: 54304,
            thirdSIDBase: 54336,
            defaultClockSpeed: 50,
            defaultEmulation: "RESIDFP",
            defaultSidModel: "MOS8580",
            fakeStereo: false,
            sidToRead: "FIRST_SID",
            bufferSize: 3 * 48000,
            audioBufferSize: 48000,
            filter6581: 'FilterAverage6581',
            filter8580: 'FilterAverage8580',
            stereoFilter6581: 'FilterAverage6581',
            stereoFilter8580: 'FilterAverage8580',
            thirdSIDFilter6581: 'FilterAverage6581',
            thirdSIDFilter8580: 'FilterAverage8580',
            reSIDfpFilter6581: 'FilterAlankila6581R4AR_3789',
            reSIDfpFilter8580: 'FilterTrurl8580R5_3691',
            reSIDfpStereoFilter6581: 'FilterAlankila6581R4AR_3789',
            reSIDfpStereoFilter8580: 'FilterTrurl8580R5_3691',
            reSIDfpThirdSIDFilter6581: 'FilterAlankila6581R4AR_3789',
            reSIDfpThirdSIDFilter8580: 'FilterTrurl8580R5_3691',
            reSIDFilters6581: ['FilterLightest6581','FilterLighter6581','FilterLight6581','FilterAverage6581','FilterDark6581','FilterDarker6581','FilterDarkest6581'],
            reSIDFilters8580: ['FilterLight8580','FilterAverage8580','FilterDark8580'],
            reSIDfpFilters6581: ['FilterReSID6581','FilterAlankila6581R4AR_3789','FilterAlankila6581R3_3984_1','FilterAlankila6581R3_3984_2','FilterLordNightmare6581R3_4285','FilterLordNightmare6581R3_4485','FilterLordNightmare6581R4_1986S','FilterZrX6581R3_0384','FilterZrX6581R3_1984','FilterZrx6581R3_3684','FilterZrx6581R3_3985','FilterZrx6581R4AR_2286','FilterTrurl6581R3_0784','FilterTrurl6581R3_0486S','FilterTrurl6581R3_3384','FilterTrurl6581R3_4885','FilterTrurl6581R4AR_3789','FilterTrurl6581R4AR_4486','FilterNata6581R3_2083','FilterGrue6581R4AR_3488',
            'FilterKruLLo','FilterEnigma6581R3_4885','FilterEnigma6581R3_1585'],
            reSIDfpFilters8580: ['FilterTrurl8580R5_1489','FilterTrurl8580R5_3691'],
            muteVoice1: false,
            muteVoice2: false,
            muteVoice3: false,
            muteVoice4: false,
            muteStereoVoice1: false,
            muteStereoVoice2: false,
            muteStereoVoice3: false,
            muteStereoVoice4: false,
            muteThirdSIDVoice1: false,
            muteThirdSIDVoice2: false,
            muteThirdSIDVoice3: false,
            muteThirdSIDVoice4: false,
            tabIndex: 1,
          };
        },
        computed: {},
        methods: {
          updateLanguage() {
            localStorage.locale = this.$i18n.locale;
          },
          reset(command) {
            app.screen = true;
            app.$refs.videoTab.click();
            app.stopTune();
            if (app.$refs.formCartFileSm.files[0]) {
              var reader = new FileReader();
              reader.onload = function () {
                jsidplay2Worker(
                  undefined,
                  undefined,
                  new Uint8Array(this.result),
                  app.$refs.formCartFileSm.files[0].name,
                  command
                );
              };
              reader.readAsArrayBuffer(app.$refs.formCartFileSm.files[0]);
            } else {
              jsidplay2Worker(undefined, undefined, undefined, undefined, command);
            }
          },
          startTune(screen) {
            if (screen) {
              app.$refs.videoTab.click();
            }
            app.screen = screen ? screen : false;
            app.stopTune();
            if (app.$refs.formFileSm.files[0]) {
              var reader = new FileReader();
              reader.onload = function () {
                jsidplay2Worker(new Uint8Array(this.result), app.$refs.formFileSm.files[0].name);
              };
              reader.readAsArrayBuffer(app.$refs.formFileSm.files[0]);
            }
          },
          downloadAndStartTune(name, url, screen) {
            let headers = new Headers();
            headers.set("Authorization", "Basic " + window.btoa("jsidplay2:jsidplay2!"));
            fetch(url, { method: "GET", headers: headers })
              .then((response) => response.blob())
              .then((blob) => {
                let file = new File([blob], name, {
                  type: "application/octet-stream",
                });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                app.$refs.formFileSm.files = dataTransfer.files;
                app.startTune(screen);
              });
          },
          pauseTune() {
            if (app.playing) {
              if (app.paused) {
                audioContext.resume();
                worker.postMessage({ eventType: "CLOCK" });
              } else {
                audioContext.suspend();
              }
              app.paused = !app.paused;
            } else if (app.$refs.formFileSm.files[0]) {
              app.startTune();
            } else {
              app.reset();
            }
          },
          fastForward() {
              if (worker) {
                worker.postMessage({
                  eventType: "FAST_FORWARD",
                  eventData: { },
                });
              }
          },
          normalSpeed() {
              if (worker) {
                worker.postMessage({
                  eventType: "NORMAL_SPEED",
                  eventData: { },
                });
              }
          },
          stopTune() {
            if (worker) {
              worker.terminate();
              worker = undefined;
            }
            if (audioContext) {
              audioContext.close();
              audioContext = undefined;
            }
            imageQueue.clear();
            app.playing = false;
            app.paused = false;
          },
          clearScreen: function () {
            data.set(new Uint8Array((maxWidth * maxHeight) << 2));
            canvasContext.putImageData(imageData, 0, 0);
          },
          animate: function () {
            var msPerFrame = 1000 * app.nthFrame / app.defaultClockSpeed;
            if (app.playing) {
                window.requestAnimationFrame(app.animate)
            }
            const msNow = window.performance.now()
            const msPassed = msNow - msPrev

            if (msPassed < msPerFrame) return

            const excessTime = msPassed % msPerFrame
            msPrev = msNow - excessTime

            if (!app.paused) {
              var elem = imageQueue.dequeue();
              if (elem) {
                data.set(elem.image);
                canvasContext.putImageData(imageData, 0, 0);
                actualFrames++;
              }
            }
            totalFrames++;
            frames++
            if (frames * app.nthFrame >= app.defaultClockSpeed) {
              app.framesCounter = actualFrames;
              frames = 0;
              actualFrames = 0;
            }
          },
          insertDisk() {
            var reader = new FileReader();
            reader.onload = function () {
              if (worker) {
                worker.postMessage({
                  eventType: "INSERT_DISK",
                  eventData: {
                    contents: new Uint8Array(this.result),
                    diskName: app.$refs.formDiskFileSm.files[0].name,
                  },
                });
              }
            };
            if (app.$refs.formDiskFileSm && app.$refs.formDiskFileSm.files[0]) {
              reader.readAsArrayBuffer(app.$refs.formDiskFileSm.files[0]);
            }
          },
          downloadAndStartProgram(name, url) {
            app.ejectTape();
            app.ejectDisk();
            app.downloadAndStartTune(name, url, true);
          },
          downloadAndInsertDisk(name, url) {
            let headers = new Headers();
            headers.set("Authorization", "Basic " + window.btoa("jsidplay2:jsidplay2!"));
            fetch(url, { method: "GET", headers: headers })
              .then((response) => response.blob())
              .then((blob) => {
                let file = new File([blob], name, {
                  type: "application/octet-stream",
                });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                app.$refs.formDiskFileSm.files = dataTransfer.files;
                if (app.playing && app.screen) {
                  app.insertDisk();
                } else {
                  app.reset('LOAD"*",8,1\rRUN\r');
                }
              });
          },
          ejectDisk() {
            if (worker) {
              worker.postMessage({
                eventType: "EJECT_DISK",
              });
            }
            app.$refs.formDiskFileSm.value = "";
          },
          insertTape() {
            var reader = new FileReader();
            reader.onload = function () {
              if (worker) {
                worker.postMessage({
                  eventType: "INSERT_TAPE",
                  eventData: {
                    contents: new Uint8Array(this.result),
                    tapeName: app.$refs.formTapeFileSm.files[0].name,
                  },
                });
              }
            };
            if (app.$refs.formTapeFileSm && app.$refs.formTapeFileSm.files[0]) {
              reader.readAsArrayBuffer(app.$refs.formTapeFileSm.files[0]);
            }
          },
          ejectTape() {
            if (worker) {
              worker.postMessage({
                eventType: "EJECT_TAPE",
              });
            }
            app.$refs.formTapeFileSm.value = "";
          },
          pressPlayOnTape() {
            if (worker) {
              worker.postMessage({
                eventType: "PRESS_PLAY_ON_TAPE",
                eventData: {},
              });
            }
          },
          typeInCommand(command) {
            if (worker) {
              worker.postMessage({
                eventType: "SET_COMMAND",
                eventData: {
                  command: command,
                },
              });
            }
          },
          typeKey(key) {
            if (worker) {
              worker.postMessage({
                eventType: "TYPE_KEY",
                eventData: {
                  key: key,
                },
              });
            }
          },
          pressKey(key) {
            if (worker) {
              worker.postMessage({
                eventType: "PRESS_KEY",
                eventData: {
                  key: key,
                },
              });
            }
          },
          releaseKey(key) {
            if (worker) {
              worker.postMessage({
                eventType: "RELEASE_KEY",
                eventData: {
                  key: key,
                },
              });
            }
          },
          joystick(number, value) {
            if (worker) {
              worker.postMessage({
                eventType: "PRESS_JOYSTICK",
                eventData: {
                  number: number,
                  value: value,
                },
              });
            }
          },
          insertCart() {
            app.reset();
          },
          ejectCart() {
            app.$refs.formCartFileSm.value = "";
            app.reset();
          },
          freezeCartridge() {
              if (worker) {
                worker.postMessage({
                  eventType: "FREEZE_CARTRIDGE",
                  eventData: { },
                });
              }
          },
          setDefaultEmulation(emulation) {
            if (worker) {
              worker.postMessage({
                eventType: "SET_DEFAULT_EMULATION",
                eventData: {
                  emulation: emulation,
                },
              });
            }
          },
          setDefaultSidModel(chipModel) {
            if (worker) {
              worker.postMessage({
                eventType: "SET_DEFAULT_CHIP_MODEL",
                eventData: {
                  chipModel: chipModel,
                },
              });
            }
          },
          setFilterName(emulation, chipModel, sidNum, filterName) {
            if (worker) {
              worker.postMessage({
                eventType: "SET_FILTER_NAME",
                eventData: {
                  emulation: emulation,
                  chipModel: chipModel,
                  sidNum: sidNum,
                  filterName: filterName,
                },
              });
            }
          },
          setMute(sidNum, voice, value) {
            if (worker) {
              worker.postMessage({
                eventType: "SET_MUTE",
                eventData: {
                  sidNum: sidNum,
                  voice: voice,
                  value: value,
                },
              });
            }
          },
          setStereo() {
            if (worker) {
              worker.postMessage({
                eventType: "SET_STEREO",
                eventData: {
                  stereoMode: app.stereoMode,
                  dualSidBase: app.dualSidBase,
                  thirdSIDBase: app.thirdSIDBase,
                  fakeStereo: app.fakeStereo,
                  sidToRead: app.sidToRead,
                },
              });
            }
          },
          setVolumeLevels() {
            if (worker) {
              worker.postMessage({
                eventType: "SET_VOLUME_LEVELS",
                eventData: {
                  mainVolume: app.mainVolume,
                  secondVolume: app.secondVolume,
                  thirdVolume: app.thirdVolume,
                  mainBalance: app.mainBalance,
                  secondBalance: app.secondBalance,
                  thirdBalance: app.thirdBalance,
                  mainDelay: app.mainDelay,
                  secondDelay: app.secondDelay,
                  thirdDelay: app.thirdDelay,
                },
              });
            }
          },
          setDefault() {
            this.palEmulation = true;
            this.startSong = 0;
            this.nthFrame = 2;
            this.reverbBypass = true;
            this.sidWrites = false;

            this.mainVolume = 0;
            this.secondVolume = 0;
            this.thirdVolume = 0;
            this.mainBalance = 0.3;
            this.secondBalance = 0.7;
            this.thirdBalance = 0.5;
            this.mainDelay = 10;
            this.secondDelay = 0;
            this.thirdDelay = 0;
            this.stereoMode = "AUTO";
            this.dualSidBase = 54304;
            this.thirdSIDBase = 54336;
            this.defaultClockSpeed = 50;
            this.defaultEmulation = "RESIDFP";
            this.defaultSidModel = "MOS8580";
            this.sampling = false;
            this.fakeStereo = false;
            this.sidToRead = "FIRST_SID";
            this.bufferSize = 3 * 48000;
            this.audioBufferSize = 48000;
            this.filter6581 = 'FilterAverage6581';
            this.filter8580 = 'FilterAverage8580';
            this.stereoFilter6581 = 'FilterAverage6581';
            this.stereoFilter8580 = 'FilterAverage8580';
            this.thirdSIDFilter6581 = 'FilterAverage6581';
            this.thirdSIDFilter8580 = 'FilterAverage8580';
            this.reSIDfpFilter6581 = 'FilterAlankila6581R4AR_3789';
            this.reSIDfpFilter8580 = 'FilterTrurl8580R5_3691';
            this.reSIDfpStereoFilter6581 = 'FilterAlankila6581R4AR_3789';
            this.reSIDfpStereoFilter8580 = 'FilterTrurl8580R5_3691';
            this.reSIDfpThirdSIDFilter6581 = 'FilterAlankila6581R4AR_3789';
            this.reSIDfpThirdSIDFilter8580 = 'FilterTrurl8580R5_3691';
            this.muteVoice1 = false;
            this.muteVoice2 = false;
            this.muteVoice3 = false;
            this.muteVoice4 = false;
            this.muteStereoVoice1 = false;
            this.muteStereoVoice2 = false;
            this.muteStereoVoice3 = false;
            this.muteStereoVoice4 = false;
            this.muteThirdSIDVoice1 = false;
            this.muteThirdSIDVoice2 = false;
            this.muteThirdSIDVoice3 = false;
            this.muteThirdSIDVoice4 = false;

            app.setStereo();
            app.setVolumeLevels();
            app.setDefaultEmulation(app.defaultEmulation);
            app.setDefaultSidModel(app.defaultSidModel);
            app.setFilterName('RESID', 'MOS6581', 0, app.filter6581);
            app.setFilterName('RESID', 'MOS6581', 1, app.stereoFilter6581);
            app.setFilterName('RESID', 'MOS6581', 2, app.thirdSIDFilter6581);
            app.setFilterName('RESID', 'MOS8580', 0, app.filter8580);
            app.setFilterName('RESID', 'MOS8580', 1, app.stereoFilter8580);
            app.setFilterName('RESID', 'MOS8580', 2, app.thirdSIDFilter8580);
            app.setFilterName('RESIDFP', 'MOS6581', 0, app.reSIDfpFilter6581);
            app.setFilterName('RESIDFP', 'MOS6581', 1, app.reSIDfpStereoFilter6581);
            app.setFilterName('RESIDFP', 'MOS6581', 2, app.reSIDfpThirdSIDFilter6581);
            app.setFilterName('RESIDFP', 'MOS8580', 0, app.reSIDfpFilter8580);
            app.setFilterName('RESIDFP', 'MOS8580', 1, app.reSIDfpStereoFilter8580);
            app.setFilterName('RESIDFP', 'MOS8580', 2, app.reSIDfpThirdSIDFilter8580);
            app.setMute(0, 0, app.muteVoice1);
            app.setMute(0, 1, app.muteVoice2);
            app.setMute(0, 2, app.muteVoice3);
            app.setMute(0, 3, app.muteVoice4);
            app.setMute(1, 0, app.muteStereoVoice1);
            app.setMute(1, 1, app.muteStereoVoice2);
            app.setMute(1, 2, app.muteStereoVoice3);
            app.setMute(1, 3, app.muteStereoVoice4);
            app.setMute(2, 0, app.muteThirdSIDVoice1);
            app.setMute(2, 1, app.muteThirdSIDVoice2);
            app.setMute(2, 2, app.muteThirdSIDVoice3);
            app.setMute(2, 3, app.muteThirdSIDVoice4);
          }
        },
        mounted: function () {
          if (localStorage.locale) {
            this.$i18n.locale = localStorage.locale;
          }
          if (localStorage.mainVolume) {
            this.mainVolume = JSON.parse(localStorage.mainVolume);
          }
          if (localStorage.secondVolume) {
            this.secondVolume = JSON.parse(localStorage.secondVolume);
          }
          if (localStorage.thirdVolume) {
            this.thirdVolume = JSON.parse(localStorage.thirdVolume);
          }
          if (localStorage.mainBalance) {
            this.mainBalance = JSON.parse(localStorage.mainBalance);
          }
          if (localStorage.secondBalance) {
            this.secondBalance = JSON.parse(localStorage.secondBalance);
          }
          if (localStorage.thirdBalance) {
            this.thirdBalance = JSON.parse(localStorage.thirdBalance);
          }
          if (localStorage.mainDelay) {
            this.mainDelay = JSON.parse(localStorage.mainDelay);
          }
          if (localStorage.secondDelay) {
            this.secondDelay = JSON.parse(localStorage.secondDelay);
          }
          if (localStorage.thirdDelay) {
            this.thirdDelay = JSON.parse(localStorage.thirdDelay);
          }
          if (localStorage.stereoMode) {
            this.stereoMode = JSON.parse(localStorage.stereoMode);
          }
          if (localStorage.dualSidBase) {
            this.dualSidBase = JSON.parse(localStorage.dualSidBase);
          }
          if (localStorage.thirdSIDBase) {
            this.thirdSIDBase = JSON.parse(localStorage.thirdSIDBase);
          }
          if (localStorage.defaultClockSpeed) {
            this.defaultClockSpeed = JSON.parse(localStorage.defaultClockSpeed);
          }
          if (localStorage.defaultEmulation) {
            this.defaultEmulation = JSON.parse(localStorage.defaultEmulation);
          }
          if (localStorage.defaultSidModel) {
            this.defaultSidModel = JSON.parse(localStorage.defaultSidModel);
          }
          if (localStorage.sampling) {
            this.sampling = JSON.parse(localStorage.sampling);
          }
          if (localStorage.fakeStereo) {
            this.fakeStereo = JSON.parse(localStorage.fakeStereo);
          }
          if (localStorage.sidToRead) {
            this.sidToRead = JSON.parse(localStorage.sidToRead);
          }
          if (localStorage.bufferSize) {
            this.bufferSize = JSON.parse(localStorage.bufferSize);
          }
          if (localStorage.audioBufferSize) {
            this.audioBufferSize = JSON.parse(localStorage.audioBufferSize);
          }
          if (localStorage.filter6581) {
            this.filter6581 = JSON.parse(localStorage.filter6581);
          }
          if (localStorage.filter8580) {
            this.filter8580 = JSON.parse(localStorage.filter8580);
          }
          if (localStorage.stereoFilter6581) {
            this.stereoFilter6581 = JSON.parse(localStorage.stereoFilter6581);
          }
          if (localStorage.stereoFilter8580) {
            this.stereoFilter8580 = JSON.parse(localStorage.stereoFilter8580);
          }
          if (localStorage.thirdSIDFilter6581) {
            this.thirdSIDFilter6581 = JSON.parse(localStorage.thirdSIDFilter6581);
          }
          if (localStorage.thirdSIDFilter8580) {
            this.thirdSIDFilter8580 = JSON.parse(localStorage.thirdSIDFilter8580);
          }
          if (localStorage.reSIDfpFilter6581) {
            this.reSIDfpFilter6581 = JSON.parse(localStorage.reSIDfpFilter6581);
          }
          if (localStorage.reSIDfpFilter8580) {
            this.reSIDfpFilter8580 = JSON.parse(localStorage.reSIDfpFilter8580);
          }
          if (localStorage.reSIDfpStereoFilter6581) {
            this.reSIDfpStereoFilter6581 = JSON.parse(localStorage.reSIDfpStereoFilter6581);
          }
          if (localStorage.reSIDfpStereoFilter8580) {
            this.reSIDfpStereoFilter8580 = JSON.parse(localStorage.reSIDfpStereoFilter8580);
          }
          if (localStorage.reSIDfpThirdSIDFilter6581) {
            this.reSIDfpThirdSIDFilter6581 = JSON.parse(localStorage.reSIDfpThirdSIDFilter6581);
          }
          if (localStorage.reSIDfpThirdSIDFilter8580) {
            this.reSIDfpThirdSIDFilter8580 = JSON.parse(localStorage.reSIDfpThirdSIDFilter8580);
          }
          if (localStorage.muteVoice1) {
            this.muteVoice1 = JSON.parse(localStorage.muteVoice1);
          }
          if (localStorage.muteVoice2) {
            this.muteVoice2 = JSON.parse(localStorage.muteVoice2);
          }
          if (localStorage.muteVoice3) {
            this.muteVoice3 = JSON.parse(localStorage.muteVoice3);
          }
          if (localStorage.muteVoice4) {
            this.muteVoice4 = JSON.parse(localStorage.muteVoice4);
          }
          if (localStorage.muteStereoVoice1) {
            this.muteStereoVoice1 = JSON.parse(localStorage.muteStereoVoice1);
          }
          if (localStorage.muteStereoVoice2) {
            this.muteStereoVoice2 = JSON.parse(localStorage.muteStereoVoice2);
          }
          if (localStorage.muteStereoVoice3) {
            this.muteStereoVoice3 = JSON.parse(localStorage.muteStereoVoice3);
          }
          if (localStorage.muteStereoVoice4) {
            this.muteStereoVoice4 = JSON.parse(localStorage.muteStereoVoice4);
          }
          if (localStorage.muteThirdSIDVoice1) {
            this.muteThirdSIDVoice1 = JSON.parse(localStorage.muteThirdSIDVoice1);
          }
          if (localStorage.muteThirdSIDVoice2) {
            this.muteThirdSIDVoice2 = JSON.parse(localStorage.muteThirdSIDVoice2);
          }
          if (localStorage.muteThirdSIDVoice3) {
            this.muteThirdSIDVoice3 = JSON.parse(localStorage.muteThirdSIDVoice3);
          }
          if (localStorage.muteThirdSIDVoice4) {
            this.muteThirdSIDVoice4 = JSON.parse(localStorage.muteThirdSIDVoice4);
          }
          if (localStorage.palEmulation) {
            this.palEmulation = JSON.parse(localStorage.palEmulation);
          }
          if (localStorage.nthFrame) {
            this.nthFrame = JSON.parse(localStorage.nthFrame);
          }
          if (localStorage.startSong) {
            this.startSong = JSON.parse(localStorage.startSong);
          }
          if (localStorage.reverbBypass) {
            this.reverbBypass = JSON.parse(localStorage.reverbBypass);
          }
          if (localStorage.sidWrites) {
            this.sidWrites = JSON.parse(localStorage.sidWrites);
          }
          var canvas = document.getElementById("c64Screen");
          canvasContext = canvas.getContext("2d");
          imageData = canvasContext.getImageData(0, 0, maxWidth, maxHeight);
          data = imageData.data;
        },
        watch: {
          mainVolume(newValue, oldValue) {
            localStorage.mainVolume = JSON.stringify(newValue);
          },
          secondVolume(newValue, oldValue) {
            localStorage.secondVolume = JSON.stringify(newValue);
          },
          thirdVolume(newValue, oldValue) {
            localStorage.thirdVolume = JSON.stringify(newValue);
          },
          mainBalance(newValue, oldValue) {
            localStorage.mainBalance = JSON.stringify(newValue);
          },
          secondBalance(newValue, oldValue) {
            localStorage.secondBalance = JSON.stringify(newValue);
          },
          thirdBalance(newValue, oldValue) {
            localStorage.thirdBalance = JSON.stringify(newValue);
          },
          mainDelay(newValue, oldValue) {
            localStorage.mainDelay = JSON.stringify(newValue);
          },
          secondDelay(newValue, oldValue) {
            localStorage.secondDelay = JSON.stringify(newValue);
          },
          thirdDelay(newValue, oldValue) {
            localStorage.thirdDelay = JSON.stringify(newValue);
          },
          stereoMode(newValue, oldValue) {
            localStorage.stereoMode = JSON.stringify(newValue);
          },
          dualSidBase(newValue, oldValue) {
            localStorage.dualSidBase = JSON.stringify(newValue);
          },
          thirdSIDBase(newValue, oldValue) {
            localStorage.thirdSIDBase = JSON.stringify(newValue);
          },
          defaultClockSpeed(newValue, oldValue) {
            localStorage.defaultClockSpeed = JSON.stringify(newValue);
          },
          defaultEmulation(newValue, oldValue) {
            localStorage.defaultEmulation = JSON.stringify(newValue);
          },
          defaultSidModel(newValue, oldValue) {
            localStorage.defaultSidModel = JSON.stringify(newValue);
          },
          sampling(newValue, oldValue) {
            localStorage.sampling = JSON.stringify(newValue);
          },
          fakeStereo(newValue, oldValue) {
            localStorage.fakeStereo = JSON.stringify(newValue);
          },
          sidToRead(newValue, oldValue) {
            localStorage.sidToRead = JSON.stringify(newValue);
          },
          bufferSize(newValue, oldValue) {
            localStorage.bufferSize = JSON.stringify(newValue);
          },
          audioBufferSize(newValue, oldValue) {
            localStorage.audioBufferSize = JSON.stringify(newValue);
          },
          filter6581(newValue, oldValue) {
            localStorage.filter6581 = JSON.stringify(newValue);
          },
          filter8580(newValue, oldValue) {
            localStorage.filter8580 = JSON.stringify(newValue);
          },
          stereoFilter6581(newValue, oldValue) {
            localStorage.stereoFilter6581 = JSON.stringify(newValue);
          },
          stereoFilter8580(newValue, oldValue) {
            localStorage.stereoFilter8580 = JSON.stringify(newValue);
          },
          thirdSIDFilter6581(newValue, oldValue) {
            localStorage.thirdSIDFilter6581 = JSON.stringify(newValue);
          },
          thirdSIDFilter8580(newValue, oldValue) {
            localStorage.thirdSIDFilter8580 = JSON.stringify(newValue);
          },
          reSIDfpFilter6581(newValue, oldValue) {
            localStorage.reSIDfpFilter6581 = JSON.stringify(newValue);
          },
          reSIDfpFilter8580(newValue, oldValue) {
            localStorage.reSIDfpFilter8580 = JSON.stringify(newValue);
          },
          reSIDfpStereoFilter6581(newValue, oldValue) {
            localStorage.reSIDfpStereoFilter6581 = JSON.stringify(newValue);
          },
          reSIDfpStereoFilter8580(newValue, oldValue) {
            localStorage.reSIDfpStereoFilter8580 = JSON.stringify(newValue);
          },
          reSIDfpThirdSIDFilter6581(newValue, oldValue) {
            localStorage.reSIDfpThirdSIDFilter6581 = JSON.stringify(newValue);
          },
          reSIDfpThirdSIDFilter8580(newValue, oldValue) {
            localStorage.reSIDfpThirdSIDFilter8580 = JSON.stringify(newValue);
          },
          muteVoice1(newValue, oldValue) {
            localStorage.muteVoice1 = JSON.stringify(newValue);
          },
          muteVoice2(newValue, oldValue) {
            localStorage.muteVoice2 = JSON.stringify(newValue);
          },
          muteVoice3(newValue, oldValue) {
            localStorage.muteVoice3 = JSON.stringify(newValue);
          },
          muteVoice4(newValue, oldValue) {
            localStorage.muteVoice4 = JSON.stringify(newValue);
          },
          muteStereoVoice1(newValue, oldValue) {
            localStorage.muteStereoVoice1 = JSON.stringify(newValue);
          },
          muteStereoVoice2(newValue, oldValue) {
            localStorage.muteStereoVoice2 = JSON.stringify(newValue);
          },
          muteStereoVoice3(newValue, oldValue) {
            localStorage.muteStereoVoice3 = JSON.stringify(newValue);
          },
          muteStereoVoice4(newValue, oldValue) {
            localStorage.muteStereoVoice4 = JSON.stringify(newValue);
          },
          muteThirdSIDVoice1(newValue, oldValue) {
            localStorage.muteThirdSIDVoice1 = JSON.stringify(newValue);
          },
          muteThirdSIDVoice2(newValue, oldValue) {
            localStorage.muteThirdSIDVoice2 = JSON.stringify(newValue);
          },
          muteThirdSIDVoice3(newValue, oldValue) {
            localStorage.muteThirdSIDVoice3 = JSON.stringify(newValue);
          },
          muteThirdSIDVoice4(newValue, oldValue) {
            localStorage.muteThirdSIDVoice4 = JSON.stringify(newValue);
          },
          palEmulation(newValue, oldValue) {
            localStorage.palEmulation = JSON.stringify(newValue);
          },
          nthFrame(newValue, oldValue) {
            localStorage.nthFrame = JSON.stringify(newValue);
          },
          startSong(newValue, oldValue) {
            localStorage.startSong = JSON.stringify(newValue);
          },
          reverbBypass(newValue, oldValue) {
            localStorage.reverbBypass = JSON.stringify(newValue);
          },
          sidWrites(newValue, oldValue) {
            localStorage.sidWrites = JSON.stringify(newValue);
          },
        },
      })
        .use(i18n)
        .mount("#app");

      var noSleep = new NoSleep();

      var toggleEl = document.querySelector("#toggle");
      toggleEl.addEventListener(
        "click",
        function () {
          if (!app.wakeLockEnable) {
            app.wakeLockEnable = true;
            noSleep.enable(); // keep the screen on!
            toggleEl.value = "Wake Lock On";
            document.body.style.backgroundColor = "lightblue";
          } else {
            app.wakeLockEnable = false;
            noSleep.disable(); // let the screen turn off.
            toggleEl.value = "Wake Lock Off";
            document.body.style.backgroundColor = "";
          }
        },
        false
      );
      var keyDownListener = (event) => {
        if (app.tabIndex != 1) {
          return;
        }
        let key = toC64KeyTableEntry(event.code);
        if (key) {
          app.pressKey(key);
          event.preventDefault();
        }
      };
      var keyUpListener = (event) => {
        if (app.tabIndex != 1) {
          return;
        }
        let key = toC64KeyTableEntry(event.code);
        if (key) {
          app.releaseKey(key);
          event.preventDefault();
        }
      };
      document.addEventListener("keydown", keyDownListener, false);
      document.addEventListener("keyup", keyUpListener, false);
    </script>
  </body>
</html>
