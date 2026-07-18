/* ------------------------------------------------------ */
/* SPECIAL DEEPSID DB UPDATING                            */
/* ------------------------------------------------------ */
/* CAVEAT: ID numbers are not always the same across the  */
/* localhost and online databases!                        */
/* ------------------------------------------------------ */

/* Update various players with correct or more precise information (be careful with long strings as together with tags they may mess with the layout) */
UPDATE files SET player = "FC + Martin Galway's Digi player" WHERE id = 619			-- _High Voltage SID Collection/DEMOS/G-L/Kinetix_Blasting_Power_Mix.sid
UPDATE files SET player = "Galway's player + Hubbard's players" WHERE id = 1249		-- _High Voltage SID Collection/DEMOS/S-Z/Wize_Mixes.sid
UPDATE files SET player = "JCH IntroPlayer" WHERE id = 24152						-- _High Voltage SID Collection/MUSICIANS/J/JCH/Fooled.sid
UPDATE files SET player = "a player by JO of Visage Studios" WHERE id = 66100		-- _High Voltage SID Collection/MUSICIANS/J/JCH/JCH_in_Visages_Editor.sid
UPDATE files SET player = "Zoolook's player" WHERE id = 24177						-- _High Voltage SID Collection/MUSICIANS/J/JCH/Test_in_Zoolooks_Player.sid
UPDATE files SET player = "JO's player + THCM" WHERE id = 22304						-- _High Voltage SID Collection/MUSICIANS/H/HJE/Megademo_part_2.sid

-- This is handled in 'pretty_player_names.php' instead
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 82082						-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/41_Neurons.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 68094						-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/VandaliSID.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 68158						-- _High Voltage SID Collection/MUSICIANS/T/Tjelta_Geir/X-Rated_Red_Wine_GT_Remix.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 70163						-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/Move.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 73226 					-- _High Voltage SID Collection/MUSICIANS/D/Detert_Thomas/Spectrum.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 73560						-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/Legacy.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 81091 					-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/Harmony.sid
-- UPDATE files SET player = "Compotech-X v2.4" WHERE id = 82083 					-- _High Voltage SID Collection/MUSICIANS/S/Schneider_Markus/Next_Round_tune_1.sid

/* Players not detected by Player-ID */
UPDATE files SET player = "JammicroV1" WHERE id = 81935								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Have_Some_Faith.sid (code change?)

/* Tags */
DELETE FROM tags_lookup WHERE files_id = 66429 AND tags_id = 9						-- Tag ID 9 = "Coop" + _High Voltage SID Collection/DEMOS/M-R/Mr_Brightside.sid
DELETE FROM tags_lookup WHERE files_id = 2429 AND tags_id = 9						-- Tag ID 9 = "Coop" + _High Voltage SID Collection/GAMES/A-F/Athanor.sid

/* Lengths */
UPDATE files SET lengths = "1:47" WHERE id = 68061									-- _High Voltage SID Collection/MUSICIANS/P/Proton/Mellow_Bite.sid
UPDATE files SET lengths = "12:08" WHERE id = 39135									-- _High Voltage SID Collection/MUSICIANS/S/Scarzix/Singularity_2SID.sid

/* Amend "created in 1989, released in 2020" situations in HVSC */
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/B/Bjerregaard_Johannes/STII8.sid"
UPDATE files SET copyright = "198?" WHERE collection_path LIKE "%/MUSICIANS/B/Bjerregaard_Johannes/Dragon_Sword.sid"
UPDATE files SET copyright = "2014" WHERE collection_path LIKE "%/MUSICIANS/D/Daglish_Ben/Japanese.sid"
UPDATE files SET copyright = "1991" WHERE collection_path LIKE "%/MUSICIANS/D/Deek/Codename_Desert_Storm.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/D/Deek/Endtune.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/D/Deek/Lazity.sid"
UPDATE files SET copyright = "1990" WHERE collection_path LIKE "%/MUSICIANS/L/Link/Throw-Ups.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/A/Ahz_The_Demon/Sincerity.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/A/Ahz_The_Demon/Mindblended.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/A/Ahz_The_Demon/Lunardive.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/A/Ahz_The_Demon/Bachmix.sid"
UPDATE files SET copyright = "2000" WHERE collection_path LIKE "%/MUSICIANS/A/Alien_WOW/Platinum.sid"
UPDATE files SET copyright = "2011" WHERE collection_path LIKE "%/MUSICIANS/B/Behdad_Arman/Desolate_Ways.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Fantastic.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Wesola.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/D_J_Bobo_Cover_2.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Innym_Razem.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Jimmy_Dean.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Koniec.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Nareszcie.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Niezla.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Polo_Mix.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Potem_plus_plus.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/C/Cleve/Technowata_33.sid"
UPDATE files SET copyright = "2019" WHERE collection_path LIKE "%/MUSICIANS/C/Crowley_Owen/Ancient_Walk.sid"
UPDATE files SET copyright = "2010" WHERE collection_path LIKE "%/MUSICIANS/C/Crowley_Owen/Elekfunk.sid"
UPDATE files SET copyright = "2012" WHERE collection_path LIKE "%/MUSICIANS/C/Crowley_Owen/Psychopunks_of_Uproar.sid"
UPDATE files SET copyright = "2009" WHERE collection_path LIKE "%/MUSICIANS/C/Crowley_Owen/Clarke_2.sid"
UPDATE files SET copyright = "2009" WHERE collection_path LIKE "%/MUSICIANS/C/Crowley_Owen/Intrinsic_v2.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/D/Dave_Ocean_Studios/Metall_Glimmer_preview.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/Rambo_2.sid"
UPDATE files SET copyright = "198?" WHERE collection_path LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/Last_Ninja_2.sid"
UPDATE files SET copyright = "198?" WHERE collection_path LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/China.sid"
UPDATE files SET copyright = "198?" WHERE collection_path LIKE "%/MUSICIANS/D/Deenen_Charles/Worktunes/220_Volt_Classic.sid"
UPDATE files SET copyright = "2002" WHERE collection_path LIKE "%/MUSICIANS/D/Demux/Crow_Boy.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/United_Suns.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Thunder_Mix.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Tears_of_Delta.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Slow_to_Fast.sid"
UPDATE files SET copyright = "1990" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Silent_Vision.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Say_Good_Bye.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Power_Smash.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Happy_News.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/D/Dokmatik/Darker.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/D/DOS/25_Years_Later.sid"
UPDATE files SET copyright = "1993" WHERE collection_path LIKE "%/MUSICIANS/D/DOS/Happy_End.sid"
UPDATE files SET copyright = "1991" WHERE collection_path LIKE "%/MUSICIANS/D/Dr_Coldcut/Twentyfive.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/D/Drutten/Othello.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/G/Gilmore_Adam/AtomicMegaToonX.sid"
UPDATE files SET copyright = "1991" WHERE collection_path LIKE "%/MUSICIANS/H/Huelsbeck_Chris/Bugbomber.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/H/Huelsbeck_Chris/Metro_Dance.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Audiostim.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Green_Hill.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Interesting.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/No_Joy.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Oxygene.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Tempest.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/I/Intense/Test_of_Time.sid"
UPDATE files SET copyright = "2014" WHERE collection_path LIKE "%/MUSICIANS/J/Jangler/Buzz_Cut.sid"
UPDATE files SET copyright = "1991" WHERE collection_path LIKE "%/MUSICIANS/J/JCH/JCH_in_Visages_Editor.sid"
UPDATE files SET copyright = "1995" WHERE collection_path LIKE "%/MUSICIANS/K/Kochan_Maciej/Eine_kleine_umc_umc_umc.sid"
UPDATE files SET copyright = "2012" WHERE collection_path LIKE "%/MUSICIANS/K/Kribust/Bender.sid"
UPDATE files SET copyright = "1993" WHERE collection_path LIKE "%/MUSICIANS/K/Krolzig_Jan/X-Out_Level_8.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Ninja_Eyes.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Vrolijke_Vier.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Bellydancer.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Darkroom.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Dismembrd_Pixels.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Ingame_Tunes.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Parallax.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Speedmachine.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Terminal_Despair.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Times_o_Combat.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Twosome.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/Martins_Mix.sid"
UPDATE files SET copyright = "1994" WHERE collection_path LIKE "%/MUSICIANS/M/MCA/X2000_Compo_Tune.sid"
UPDATE files SET copyright = "1990" WHERE collection_path LIKE "%/MUSICIANS/M/Mixer/Iisibiisi.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/M/Moerkrid_Olav/Twilight.sid"
UPDATE files SET copyright = "2017" WHERE collection_path LIKE "%/MUSICIANS/M/Mr_Mouse/Maya_De_Baya_intro_and_title.sid"
UPDATE files SET copyright = "2017" WHERE collection_path LIKE "%/MUSICIANS/M/Mr_Mouse/Maya_De_Baya_level_1.sid"
UPDATE files SET copyright = "2017" WHERE collection_path LIKE "%/MUSICIANS/M/Mr_Mouse/Sheepish_Tunes.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/N/Necrophobic/Heavy.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/N/Necrophobic/Quickie.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/N/Necrophobic/Seduction.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/N/Necrophobic/Whipme.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/N/Nice/How_Gee.sid"
UPDATE files SET copyright = "1993" WHERE collection_path LIKE "%/MUSICIANS/O/Odi/Vectormania_2_tune_2.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/P/Phase_2/Heaven.sid"
UPDATE files SET copyright = "1988" WHERE collection_path LIKE "%/MUSICIANS/R/Ray/Long_Way_to_Go.sid"
UPDATE files SET copyright = "1996" WHERE collection_path LIKE "%/MUSICIANS/R/Red_Devil/Welcome_in_the_Sunshine.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/R/Replay/Paradise.sid"
UPDATE files SET copyright = "1991" WHERE collection_path LIKE "%/MUSICIANS/R/Rokling_Henning/Antimon_Tune_1991.sid"
UPDATE files SET copyright = "2002" WHERE collection_path LIKE "%/MUSICIANS/S/Shapie/Back_on_Track_intro.sid"
UPDATE files SET copyright = "2002" WHERE collection_path LIKE "%/MUSICIANS/S/Shapie/Real_Slim_Shady.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/S/Shavitt_Guy/Dont_Get_Excited.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/S/Shavitt_Guy/Loader_Music.sid"
UPDATE files SET copyright = "1992" WHERE collection_path LIKE "%/MUSICIANS/S/Shavitt_Guy/Plotter_Tune.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/S/Shavitt_Guy/64ever-4k.sid"
UPDATE files SET copyright = "1996" WHERE collection_path LIKE "%/MUSICIANS/S/Simon_Laszlo/Illusion.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/S/Southern_Shaun/Otherworld.sid"
UPDATE files SET copyright = "1997" WHERE collection_path LIKE "%/MUSICIANS/S/Starlost/Shuffle.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Apollo_64.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Astrognome.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Battleaxe_Demo.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Beater_Blocker.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Bubbleblues.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Cosmonauts.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Dog_Star_32.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/E_Equals_MC64.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Herman_Munster_Electrosound.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Howzat_64_Electrosound.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Lobotomy_Electrosound.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Make_it_in_America.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Match_64.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Paralized.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Sax_Demo.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Saxophones.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Smurf_Party.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/S/Stormont_John/Strangled_Electrosound.sid"
UPDATE files SET copyright = "2011" WHERE collection_path LIKE "%/MUSICIANS/T/Taxim/Heroes_and_Cowards_Extro.sid"
UPDATE files SET copyright = "1987" WHERE collection_path LIKE "%/MUSICIANS/T/Tel_Jeroen/JT_in_Robs.sid"
UPDATE files SET copyright = "1996" WHERE collection_path LIKE "%/MUSICIANS/T/The_Blue_Ninja/Imploder.sid"
UPDATE files SET copyright = "1996" WHERE collection_path LIKE "%/MUSICIANS/T/The_Blue_Ninja/Imploder_Save_Jingle.sid"
UPDATE files SET copyright = "199?" WHERE collection_path LIKE "%/MUSICIANS/T/The_Flying_Dutchman/Quix_preview.sid"
UPDATE files SET copyright = "1999" WHERE collection_path LIKE "%/MUSICIANS/V/V-12/Acid_Runner_Remix.sid"
UPDATE files SET copyright = "2005" WHERE collection_path LIKE "%/MUSICIANS/V/Vegeta/Heniek.sid"
UPDATE files SET copyright = "1998" WHERE collection_path LIKE "%/MUSICIANS/V/Vegeta/Kazik.sid"
UPDATE files SET copyright = "1989" WHERE collection_path LIKE "%/MUSICIANS/W/Whittaker_David/Infection.sid"
UPDATE files SET copyright = "2001" WHERE collection_path LIKE "%/MUSICIANS/Z/Zyron/Trapdoor_Part_2_remix.sid"
UPDATE files SET copyright = "2010" WHERE collection_path LIKE "%/MUSICIANS/B/Blues_Muz/Gallefoss_Glenn/Enjoy_the_Silence.sid"
UPDATE files SET copyright = "1995" WHERE collection_path LIKE "%/MUSICIANS/T/The_Magical_Garfield/TMG_26.sid"
UPDATE files SET copyright = "1996" WHERE collection_path LIKE "%/MUSICIANS/T/Trident/Journey.sid"
