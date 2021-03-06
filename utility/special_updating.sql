/* ------------------------------------------------------ */
/* SPECIAL DEEPSID DB UPDATING                            */
/* ------------------------------------------------------ */
/* See a 'howto_update' text file for when to parse this. */
/* Don't specify HVSC/CGSC paths directly as they could   */
/* be modified by the official HVSC/CGSC updates. Use an  */
/* ID instead and then add the path as a SQL comment.     */
/* ------------------------------------------------------ */

/* Update various players with correct or more precise information (be careful with long strings as together with tags they may mess with the layout) */
UPDATE hvsc_files SET player = "FC + Martin Galway's Digi player" WHERE id = 619			-- _High Voltage SID Collection/DEMOS/G-L/Kinetix_Blasting_Power_Mix.sid
UPDATE hvsc_files SET player = "Galway's player + Hubbard's players" WHERE id = 1249		-- _High Voltage SID Collection/DEMOS/S-Z/Wize_Mixes.sid
UPDATE hvsc_files SET player = "JCH IntroPlayer" WHERE id = 24152							-- _High Voltage SID Collection/MUSICIANS/J/JCH/Fooled.sid
UPDATE hvsc_files SET player = "a player by JO of Visage Studios" WHERE id = 66100			-- _High Voltage SID Collection/MUSICIANS/J/JCH/JCH_in_Visages_Editor.sid
UPDATE hvsc_files SET player = "Zoolook's player" WHERE id = 24177							-- _High Voltage SID Collection/MUSICIANS/J/JCH/Test_in_Zoolooks_Player.sid
UPDATE hvsc_files SET player = "JammicroV0" WHERE id = 67945								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Aye_Contact.sid
UPDATE hvsc_files SET player = "JammicroV0" WHERE id = 67946								-- _High Voltage SID Collection/MUSICIANS/J/Jammer/Tillax.sid
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 67984								-- _High Voltage SID Collection/MUSICIANS/L/Laxity/Pipe_and_Pearls.sid
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68893								-- _High Voltage SID Collection/MUSICIANS/J/JCH/All_Around_the_World.sid
UPDATE hvsc_files SET player = "JO's player + THCM" WHERE id = 22304						-- _High Voltage SID Collection/MUSICIANS/H/HJE/Megademo_part_2.sid
UPDATE hvsc_files SET player = "Vibrants/JO" WHERE id = 22322								-- _High Voltage SID Collection/MUSICIANS/H/HJE/Woody_the_Worm.sid

/* Laxity tunes in SID Player II */
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68914
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68915
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68916
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68917
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68918
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68919
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68920
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68921
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68922
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68923
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68924
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68925
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68926
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68927
UPDATE hvsc_files SET player = "SidFactory II" WHERE id = 68928

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
