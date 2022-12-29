/* ------------------------------------------------------ */
/* SPECIAL DEEPSID DB UPDATING                            */
/* ------------------------------------------------------ */
/* CAVEAT: ID numbers are not always the same across the  */
/* localhost and online databases!                        */
/* ------------------------------------------------------ */

/* Update various players with correct or more precise information (be careful with long strings as together with tags they may mess with the layout) */
UPDATE hvsc_files SET player = "FC + Martin Galway's Digi player" WHERE id = 619			-- _High Voltage SID Collection/DEMOS/G-L/Kinetix_Blasting_Power_Mix.sid
UPDATE hvsc_files SET player = "Galway's player + Hubbard's players" WHERE id = 1249		-- _High Voltage SID Collection/DEMOS/S-Z/Wize_Mixes.sid
UPDATE hvsc_files SET player = "JCH IntroPlayer" WHERE id = 24152							-- _High Voltage SID Collection/MUSICIANS/J/JCH/Fooled.sid
UPDATE hvsc_files SET player = "a player by JO of Visage Studios" WHERE id = 66100			-- _High Voltage SID Collection/MUSICIANS/J/JCH/JCH_in_Visages_Editor.sid
UPDATE hvsc_files SET player = "Zoolook's player" WHERE id = 24177							-- _High Voltage SID Collection/MUSICIANS/J/JCH/Test_in_Zoolooks_Player.sid
UPDATE hvsc_files SET player = "JammicroV0" WHERE id = 67945								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Aye_Contact.sid
UPDATE hvsc_files SET player = "JammicroV0" WHERE id = 67946								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Tillax.sid
UPDATE hvsc_files SET player = "JO's player + THCM" WHERE id = 22304						-- _High Voltage SID Collection/MUSICIANS/H/HJE/Megademo_part_2.sid
UPDATE hvsc_files SET player = "Vibrants/JO" WHERE id = 22322								-- _High Voltage SID Collection/MUSICIANS/H/HJE/Woody_the_Worm.sid

UPDATE hvsc_files SET player = "JammicroV1" WHERE id = 73319								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Ambience.sid
UPDATE hvsc_files SET player = "JammicroV1" WHERE id = 73322								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Sneaky_Billy.sid
UPDATE hvsc_files SET player = "JammicroV1" WHERE id = 73627								-- _High Voltage SID Collection/MUSICIANS/T/That8BitChiptuneGuy/1_Byte_Under_512.sid
UPDATE hvsc_files SET player = "JammicroV1" WHERE id = 73575								-- _High Voltage SID Collection/MUSICIANS/S/Shogoon/512_Rap.sid

UPDATE hvsc_files SET player = "Steve_Turner" WHERE id = 45979								-- _High Voltage SID Collection/MUSICIANS/T/Turner_Steve/Bushido.sid
UPDATE hvsc_files SET player = "Steve_Turner" WHERE id = 45981								-- _High Voltage SID Collection/MUSICIANS/T/Turner_Steve/Intensity.sid
UPDATE hvsc_files SET player = "Steve_Turner" WHERE id = 45984								-- _High Voltage SID Collection/MUSICIANS/T/Turner_Steve/Soldier_of_Fortune.sid

/* Tags */
DELETE FROM tags_lookup WHERE files_id = 66429 AND tags_id = 9								-- Tag ID 9 = "Coop" + _High Voltage SID Collection/DEMOS/M-R/Mr_Brightside.sid
DELETE FROM tags_lookup WHERE files_id = 2429 AND tags_id = 9								-- Tag ID 9 = "Coop" + _High Voltage SID Collection/GAMES/A-F/Athanor.sid

/* Replace 'Music Assembler' with 'Padua's Music Mixer' which used the same player */
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32528						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Catman.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32543						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Flodder.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32588						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_1.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32590						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_3.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32591						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_4.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32592						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_5.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32597						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Sockendurst.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32609						-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Urlaubskurs.sid

/* Lengths */
UPDATE hvsc_files SET lengths = "1:47" WHERE id = 68061										-- _High Voltage SID Collection/MUSICIANS/P/Proton/Mellow_Bite.sid
UPDATE hvsc_files SET lengths = "12:08" WHERE id = 39135									-- _High Voltage SID Collection/MUSICIANS/S/Scarzix/Singularity_2SID.sid
UPDATE hvsc_files SET lengths = "12:08" WHERE id = 67664									-- _Exotic SID Tunes Collection/Stereo 2SID/Scarzix/Singularity_2SID.sid

/* Amend "created in 1989, released in 2020" situations in HVSC */
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/B/Bjerregaard_Johannes/STII8.sid"
UPDATE hvsc_files SET copyright = "198?" WHERE fullname LIKE "%/MUSICIANS/B/Bjerregaard_Johannes/Dragon_Sword.sid"
UPDATE hvsc_files SET copyright = "2014" WHERE fullname LIKE "%/MUSICIANS/D/Daglish_Ben/Japanese.sid"
UPDATE hvsc_files SET copyright = "1991" WHERE fullname LIKE "%/MUSICIANS/D/Deek/Codename_Desert_Storm.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/D/Deek/Endtune.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/D/Deek/Lazity.sid"
UPDATE hvsc_files SET copyright = "1990" WHERE fullname LIKE "%/MUSICIANS/L/Link/Throw-Ups.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/A/Ahz_The_Demon/Sincerity.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/A/Ahz_The_Demon/Mindblended.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/A/Ahz_The_Demon/Lunardive.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/A/Ahz_The_Demon/Bachmix.sid"
UPDATE hvsc_files SET copyright = "2000" WHERE fullname LIKE "%/MUSICIANS/A/Alien_WOW/Platinum.sid"
UPDATE hvsc_files SET copyright = "2011" WHERE fullname LIKE "%/MUSICIANS/B/Behdad_Arman/Desolate_Ways.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Fantastic.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Wesola.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/D_J_Bobo_Cover_2.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Innym_Razem.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Jimmy_Dean.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Koniec.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Nareszcie.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Niezla.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Polo_Mix.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Potem_plus_plus.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/C/Cleve/Technowata_33.sid"
UPDATE hvsc_files SET copyright = "2019" WHERE fullname LIKE "%/MUSICIANS/C/Crowley_Owen/Ancient_Walk.sid"
UPDATE hvsc_files SET copyright = "2010" WHERE fullname LIKE "%/MUSICIANS/C/Crowley_Owen/Elekfunk.sid"
UPDATE hvsc_files SET copyright = "2012" WHERE fullname LIKE "%/MUSICIANS/C/Crowley_Owen/Psychopunks_of_Uproar.sid"
UPDATE hvsc_files SET copyright = "2009" WHERE fullname LIKE "%/MUSICIANS/C/Crowley_Owen/Clarke_2.sid"
UPDATE hvsc_files SET copyright = "2009" WHERE fullname LIKE "%/MUSICIANS/C/Crowley_Owen/Intrinsic_v2.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/D/Dave_Ocean_Studios/Metall_Glimmer_preview.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/Rambo_2.sid"
UPDATE hvsc_files SET copyright = "198?" WHERE fullname LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/Last_Ninja_2.sid"
UPDATE hvsc_files SET copyright = "198?" WHERE fullname LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/China.sid"
UPDATE hvsc_files SET copyright = "198?" WHERE fullname LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/220_Volt_Classic.sid"
UPDATE hvsc_files SET copyright = "2002" WHERE fullname LIKE "%/MUSICIANS/D/Demux/Crow_Boy.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/United_Suns.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Thunder_Mix.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Tears_of_Delta.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Slow_to_Fast.sid"
UPDATE hvsc_files SET copyright = "1990" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Silent_Vision.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Say_Good_Bye.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Power_Smash.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Happy_News.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/D/Dokmatik/Darker.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/D/DOS/25_Years_Later.sid"
UPDATE hvsc_files SET copyright = "1993" WHERE fullname LIKE "%/MUSICIANS/D/DOS/Happy_End.sid"
UPDATE hvsc_files SET copyright = "1991" WHERE fullname LIKE "%/MUSICIANS/D/Dr_Coldcut/Twentyfive.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/D/Drutten/Othello.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/G/Gilmore_Adam/AtomicMegaToonX.sid"
UPDATE hvsc_files SET copyright = "1991" WHERE fullname LIKE "%/MUSICIANS/H/Huelsbeck_Chris/Bugbomber.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/H/Huelsbeck_Chris/Metro_Dance.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Audiostim.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Green_Hill.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Interesting.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/No_Joy.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Oxygene.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Tempest.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/I/Intense/Test_of_Time.sid"
UPDATE hvsc_files SET copyright = "2014" WHERE fullname LIKE "%/MUSICIANS/J/Jangler/Buzz_Cut.sid"
UPDATE hvsc_files SET copyright = "1991" WHERE fullname LIKE "%/MUSICIANS/J/JCH/JCH_in_Visages_Editor.sid"
UPDATE hvsc_files SET copyright = "1995" WHERE fullname LIKE "%/MUSICIANS/K/Kochan_Maciej/Eine_kleine_umc_umc_umc.sid"
UPDATE hvsc_files SET copyright = "2012" WHERE fullname LIKE "%/MUSICIANS/K/Kribust/Bender.sid"
UPDATE hvsc_files SET copyright = "1993" WHERE fullname LIKE "%/MUSICIANS/K/Krolzig_Jan/X-Out_Level_8.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Ninja_Eyes.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Vrolijke_Vier.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Bellydancer.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Darkroom.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Dismembrd_Pixels.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Ingame_Tunes.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Parallax.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Speedmachine.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Terminal_Despair.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Times_o_Combat.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Twosome.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/Martins_Mix.sid"
UPDATE hvsc_files SET copyright = "1994" WHERE fullname LIKE "%/MUSICIANS/M/MCA/X2000_Compo_Tune.sid"
UPDATE hvsc_files SET copyright = "1990" WHERE fullname LIKE "%/MUSICIANS/M/Mixer/Iisibiisi.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/M/Moerkrid_Olav/Twilight.sid"
UPDATE hvsc_files SET copyright = "2017" WHERE fullname LIKE "%/MUSICIANS/M/Mr_Mouse/Maya_De_Baya_intro_and_title.sid"
UPDATE hvsc_files SET copyright = "2017" WHERE fullname LIKE "%/MUSICIANS/M/Mr_Mouse/Maya_De_Baya_level_1.sid"
UPDATE hvsc_files SET copyright = "2017" WHERE fullname LIKE "%/MUSICIANS/M/Mr_Mouse/Sheepish_Tunes.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/N/Necrophobic/Heavy.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/N/Necrophobic/Quickie.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/N/Necrophobic/Seduction.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/N/Necrophobic/Whipme.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/N/Nice/How_Gee.sid"
UPDATE hvsc_files SET copyright = "1993" WHERE fullname LIKE "%/MUSICIANS/O/Odi/Vectormania_2_tune_2.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/P/Phase_2/Heaven.sid"
UPDATE hvsc_files SET copyright = "1988" WHERE fullname LIKE "%/MUSICIANS/R/Ray/Long_Way_to_Go.sid"
UPDATE hvsc_files SET copyright = "1996" WHERE fullname LIKE "%/MUSICIANS/R/Red_Devil/Welcome_in_the_Sunshine.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/R/Replay/Paradise.sid"
UPDATE hvsc_files SET copyright = "1991" WHERE fullname LIKE "%/MUSICIANS/R/Rokling_Henning/Antimon_Tune_1991.sid"
UPDATE hvsc_files SET copyright = "2002" WHERE fullname LIKE "%/MUSICIANS/S/Shapie/Back_on_Track_intro.sid"
UPDATE hvsc_files SET copyright = "2002" WHERE fullname LIKE "%/MUSICIANS/S/Shapie/Real_Slim_Shady.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/S/Shavitt_Guy/Dont_Get_Excited.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/S/Shavitt_Guy/Loader_Music.sid"
UPDATE hvsc_files SET copyright = "1992" WHERE fullname LIKE "%/MUSICIANS/S/Shavitt_Guy/Plotter_Tune.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/S/Shavitt_Guy/64ever-4k.sid"
UPDATE hvsc_files SET copyright = "1996" WHERE fullname LIKE "%/MUSICIANS/S/Simon_Laszlo/Illusion.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/S/Southern_Shaun/Otherworld.sid"
UPDATE hvsc_files SET copyright = "1997" WHERE fullname LIKE "%/MUSICIANS/S/Starlost/Shuffle.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Apollo_64.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Astrognome.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Battleaxe_Demo.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Beater_Blocker.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Bubbleblues.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Cosmonauts.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Dog_Star_32.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/E_Equals_MC64.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Herman_Munster_Electrosound.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Howzat_64_Electrosound.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Lobotomy_Electrosound.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Make_it_in_America.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Match_64.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Paralized.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Sax_Demo.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Saxophones.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Smurf_Party.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/S/Stormont_John/Strangled_Electrosound.sid"
UPDATE hvsc_files SET copyright = "2011" WHERE fullname LIKE "%/MUSICIANS/T/Taxim/Heroes_and_Cowards_Extro.sid"
UPDATE hvsc_files SET copyright = "1987" WHERE fullname LIKE "%/MUSICIANS/T/Tel_Jeroen/JT_in_Robs.sid"
UPDATE hvsc_files SET copyright = "1996" WHERE fullname LIKE "%/MUSICIANS/T/The_Blue_Ninja/Imploder.sid"
UPDATE hvsc_files SET copyright = "1996" WHERE fullname LIKE "%/MUSICIANS/T/The_Blue_Ninja/Imploder_Save_Jingle.sid"
UPDATE hvsc_files SET copyright = "199?" WHERE fullname LIKE "%/MUSICIANS/T/The_Flying_Dutchman/Quix_preview.sid"
UPDATE hvsc_files SET copyright = "1999" WHERE fullname LIKE "%/MUSICIANS/V/V-12/Acid_Runner_Remix.sid"
UPDATE hvsc_files SET copyright = "2005" WHERE fullname LIKE "%/MUSICIANS/V/Vegeta/Heniek.sid"
UPDATE hvsc_files SET copyright = "1998" WHERE fullname LIKE "%/MUSICIANS/V/Vegeta/Kazik.sid"
UPDATE hvsc_files SET copyright = "1989" WHERE fullname LIKE "%/MUSICIANS/W/Whittaker_David/Infection.sid"
UPDATE hvsc_files SET copyright = "2001" WHERE fullname LIKE "%/MUSICIANS/Z/Zyron/Trapdoor_Part_2_remix.sid"
UPDATE hvsc_files SET copyright = "2010" WHERE fullname LIKE "%/MUSICIANS/B/Blues_Muz/Gallefoss_Glenn/Enjoy_the_Silence.sid"
UPDATE hvsc_files SET copyright = "1995" WHERE fullname LIKE "%/MUSICIANS/T/The_Magical_Garfield/TMG_26.sid"
