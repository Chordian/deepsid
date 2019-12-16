/* ------------------------------------------------------ */
/* SPECIAL DEEPSID DB UPDATING                            */
/* ------------------------------------------------------ */
/* See a 'howto_update' text file for when to parse this. */
/* Don't specify HVSC/CGSC paths directly as they could   */
/* be modified by the official HVSC/CGSC updates. Use an  */
/* ID instead and then add the path as a SQL comment.     */
/* ------------------------------------------------------ */

/* Update various players with correct or more precise information */
UPDATE hvsc_files SET player = "MoN/FutureComposer + Martin Galway's Digi player" WHERE id = 619		-- _High Voltage SID Collection/DEMOS/G-L/Kinetix_Blasting_Power_Mix.sid
UPDATE hvsc_files SET player = "Martin Galway's player + Rob Hubbard's players" WHERE id = 1249			-- _High Voltage SID Collection/DEMOS/S-Z/Wize_Mixes.sid
DELETE FROM tags_lookup WHERE files_id = 66429 AND tags_id = 9											-- _High Voltage SID Collection/DEMOS/M-R/Mr_Brightside.sid + Tag ID 9 = "Coop"

/* Replace 'Music Assembler' with 'Padua's Music Mixer' which used the same player */
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32528									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Catman.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32543									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Flodder.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32588									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_1.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32590									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_3.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32591									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_4.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32592									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Shadow_Intro_Tune_5.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32597									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Sockendurst.sid
UPDATE hvsc_files SET player = "Padua's Music Mixer" WHERE id = 32609									-- _High Voltage SID Collection/MUSICIANS/N/Nebula/Urlaubskurs.sid

