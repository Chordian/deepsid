<?php
/**
 * DeepSID / Parse Tracking File
 *
 * Loads the 'visitors.txt' produced by the 'tracking.php' script, parses it,
 * and returns pretty HTML for displaying.
 */

require_once("php/setup.php");
require_once("php/class.useragent.php");

define('TRACKFILE', 'visitors.txt');
define('CHORDIAN',	'87.60.173.201');

// @link https://www.toms-world.org/blog/parseuseragentstring/
$parser = new parseUserAgentStringClass();

$parser->includeAndroidName	= true;
$parser->includeWindowsName	= true;
$parser->includeMacOSName	= true;

$now = strtotime(date('Y-m-d H:i:s', strtotime(TIME_ADJUST)));

$styling = '
	<style>
		.tracking {
			border: 1px solid #000;
			margin-bottom: 10px;
			padding: 4px 6px;
			width: 400px;
			font-size: 13px;
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
			border-radius: 3px;
		}
		.bot { background: #fee; }
		.mobile { background: #f1f1ff; }
		.user { background: #ffffe6; }
		.jch { background: #efe; }
		.fb { background: #eee; }
		table td {
			vertical-align: top;
			padding-right: 6px;
		}
	</style>';

$stacked = array(
	'user'		=> '',
	'mobile'	=> '',
	'bot'		=> '',
	'jch'		=> '',
	'fb'		=> '',
	'other'		=> '',
);

if (($handle = fopen(TRACKFILE, 'r')) != false) {
	while (($line = fgetcsv($handle)) != false) {
		if (!isset($line[1])) break; // Empty file
		$parser->parseUserAgentString($line[1]);
		$duration = $minutes = round(($now - $line[3]) / 60);
		$hours = 0;
		if ($duration > 60) {
			$hours = floor($duration / 60);
			$minutes = $duration % 60;
		}
		$last = round(($now - $line[4]) / 60);
		$type = ' other';
		if ($parser->type == 'bot' || stripos('x'.$line[1], 'python-') || stripos('x'.$line[1], 'googlebot') || stripos('x'.$line[1], 'twitterbot'))
			$type = ' bot';
		elseif ($parser->type == 'mobile')
			$type = ' mobile';
		elseif ($line[0] == CHORDIAN)
			$type = ' jch';
		elseif (!empty($line[2]))
			$type = ' user';
		elseif (strpos($line[1], 'www.facebook.com'))
			$type = ' fb';
		$box = '
			<div class="tracking'.$type.'">
				'.(!empty($line[2]) ? '<b>'.$line[2].'</b> ('.$line[0].')' : $line[0]).'<br />
				'.date('H:i', $line[3]).' ('.($duration > 2 ? ($hours ? '<b>'.$hours.'</b> hours ' : '').'<b>'.$minutes.'</b> minutes ago' : '<b>just now</b>').')
				- last updated '.($last > 2 ? '<b>'.$last.'</b> minutes ago' : '<b>just now</b>').'<br />
				'.($parser->fullname != 'unknown' ? $parser->fullname : $line[1]).'
			</div>';
		$stacked[ltrim($type)] .= $box;
	}
}
fclose($handle);

echo $styling.
	'<table>
		<tr>
			<td>'.$stacked['jch'].$stacked['user'].$stacked['mobile'].'</td><td>'.$stacked['other'].'</td><td>'.$stacked['bot'].$stacked['fb'].'</td>
		</tr>
	</table>';
?>