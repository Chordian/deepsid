<?php
/**
 * DeepSID / Cron Job
 *
 * This script tests that the SOASC servers are up and working. It should be
 * called periodically by either a cron job or an online cron service. Calling
 * it about every 3-5 minutes should suffice.
 * 
 * First, a request goes to http://se2a1.iiiii.info/dl.php?d=/soasc/.../&url=1
 * which then returns the full URL to a SOASC mirror site. If successful, the
 * script then tries to access the mirror URL returned from it. This is then
 * repeated for an array of test files for each SID model, just to be sure.
 * 
 * The switch "&survey=1" is also specified to let SOASC know it's just a test.
 * 
 * A status code is written to the "soasc.txt" file in the DeepSID root:
 * 
 *	  0 = Everything is OK					GREEN
 *    1 = This script did not finish		YELLOW
 *	  2 = The DL script timed out			RED
 *    3 = The mirror URL timed out			RED
 * 
 * In addition to the status code, a time stamp is written. This can be used to
 * check the last time this script was called. If a long time has passed, the
 * cron service must have stopped processing the script for whatever reason.
 * 
 * Mirrors for testing:
 * 
 * http://se2a1.iiiii.info:40000/files/index.php
 * http://anorien.csc.warwick.ac.uk/mirrors/oakvalley/soasc/
 * http://ftp.acc.umu.se/mirror/media/Oakvalley/soasc/
 */

require_once("setup.php");

function RequestURL($path) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'DeepSID_Survey');
	curl_setopt($ch, CURLOPT_URL, $path);
	// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	// NOTE: The output of the path will be grabbed here. In the case of the DL script, it's
	// just a mirror URL string, but the mirror URL itself should return the file contents.
	$data = curl_exec($ch);
	if ($data == false) $data = '';
	curl_close($ch);

	return $data;
}

$time = date('Y-m-d H:i:s', strtotime(TIME_ADJUST));

file_put_contents('../soasc.txt', $time.',1'); // In case the script terminates

$test_files = array( // These files are suitable as it's a very short sfx (less than 60 KB each)
	'hvsc/050/FLAC/MUSICIANS/J/Joseph_Richard/Monster_Museum_T032.sid_CSG8580R5.flac',
	'hvsc/050/FLAC/MUSICIANS/J/Joseph_Richard/Monster_Museum_T032.sid_MOS6581R2.flac',
	//'hvsc/050/FLAC/MUSICIANS/J/Joseph_Richard/Monster_Museum_T032.sid_MOS6581R4.flac',
);

// NOTE: Don't add a bad HTTP response code in addition to a non-zero status code. Some online
// cron job services assume the script itself is in error and will terminate after a while.

foreach($test_files as $file) {
	$mirror = RequestURL('http://www.se2a1.net/dl.php?url=1&survey=1&d=soasc/'.$file);
	// file_put_contents('../soasc_mirror.txt', $mirror);
	if (empty($mirror)) {
		file_put_contents('../soasc.txt', $time.',2'); // The DL script timed out
		die;
	}
	$result = RequestURL($mirror);
	// file_put_contents('../soasc_result.txt', $result);
	if (empty($result)) {
		file_put_contents('../soasc.txt', $time.',3'); // The mirror URL timed out
		die;
	}
	sleep(1);
}

file_put_contents('../soasc.txt', $time.',0'); // All went well
?>