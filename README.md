# DeepSID

These are the source codes for [DeepSID](http://deepsid.chordian.net), a modern online SID player for the High Voltage and Compute's Gazette SID collections. It can play music originally composed for the [Commodore 64](https://en.wikipedia.org/wiki/Commodore_64).

## Setting up for offline use

You can use this in a local version on your own computer, if you want to. First, setup an environment that makes it possible to run PHP and MySQL. I personally use [WampServer](http://www.wampserver.com/en/), but there are a ton of options on the internet.

###Folders and files

Download the GitHub tree above as well as the following files:

* [DeepSID_Fonts.zip](https://chordian.net/files/deepsid/DeepSID_Fonts.zip)
* [DeepSID_Images.zip](https://chordian.net/files/deepsid/DeepSID_Images.zip)
* [DeepSID_Images_Brands.zip](https://chordian.net/files/deepsid/DeepSID_Images_Brands.zip)
* [DeepSID_Images_Composers.zip](https://chordian.net/files/deepsid/DeepSID_Images_Composers.zip)
* [DeepSID_Images_Countries.zip](https://chordian.net/files/deepsid/DeepSID_Images_Countries.zip)
* [DeepSID_Images_GB64.zip](https://chordian.net/files/deepsid/DeepSID_Images_GB64.zip)
* [DeepSID_Images_Players.zip](https://chordian.net/files/deepsid/DeepSID_Images_Players.zip)

1. Unpack the GitHub tree archive into a folder that works with your enviroment. I'll assume the root folder of /deepsid/ from now on.
2. Create the /deepsid/fonts/ sub folder and unpack DeepSID_Fonts.zip into it.
3. Create the /deepsid/images/ sub folder and unpack DeepSID_Images.zip into it.
4. Create the /deepsid/images/brands/ sub folder and unpack DeepSID_Images_Brands.zip into it.
5. Create the /deepsid/images/composers/ sub folder and unpack DeepSID_Images_Composers.zip into it.
6. Create the /deepsid/images/countries/ sub folder and unpack DeepSID_Images_Countries.zip into it.
7. Create the /deepsid/images/gb64/ sub folder and unpack DeepSID_Images_GB64.zip into it.
8. Create the /deepsid/images/players/ sub folder and unpack DeepSID_Images_Players.zip into it.

Download the following SID collections that will work with the database supplied below:

* [High Voltage SID Collection #70](http://www.prg.dtu.dk/HVSC/HVSC_70-all-of-them.7z)
* [Compute's Gazette SID Collection v1.36](http://www.c64music.co.uk/CGSC_v136.7z)

1. Create the /deepsid/hvsc/ sub folder.
2. Unpack the HVSC archive into the /deepsid/hvsc/ folder. This should create a /C64Music/ sub folder.
3. Rename the /C64Music/ sub folder to /_High Voltage SID Collection/ instead.
4. Unpack the CGSC archive into the /deepsid/hvsc/ folder. This should create a /CGSC/ sub folder.
5. Rename the /CGSC/ sub folder to /_Compute's Gazette SID Collection/ instead.

### Database

Download the following file:

* [DeepSID_Database.zip](https://chordian.net/files/deepsid/DeepSID_Database.zip)

This file contains all the MySQL database files that maches the HVSC and CGSC versions above. It has one test user (user ID 1) with the password "test" for checking out a few basic ratings. There are no playlists.

1. Create a database in your MySQL database, ready to receive tables.
2. Import all the SQL files into that database, one by one.
3. Edit the /deepsid/php/setup.php file and LOCALHOST constants in it to match your database.
4. Remember to also change the ROOT_HVSC and HOST constants in the same file.

The user ID of 2 assigned to "JCH" in setup.php is used for the letter folder quality filters. You can change this ID
number to a user of your choice. The folder ratings of this user will then affect those quality filters.

You can run the /deepsid/logs/activity.htm to see activity and also any errors. Note that it never logs ratings by other users, because frankly, what they rate SID tunes is none of our business.