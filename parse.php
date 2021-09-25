<?php
/**
 * DeepSID / Parse Tracking File
 *
 * Loads the 'visitors.txt' produced by the 'tracking.php' script, parses it,
 * and returns pretty HTML for displaying.
 * 
 * @used-by		(external)
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
		body {
			margin: 0;
		}
		.counts {
			font-family: arial, sans-serif;
			padding: 6px 11px 6px;
			margin-bottom: 6px;
			border-bottom: 1px solid #aaa;
			background: #eee;
		}
			.counts span {
				color: #999;
				margin-left: 32px;
			}
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
		table {
			margin-left: 8px;
		}
			table td {
				vertical-align: top;
				padding-right: 6px;
			}
		.duplicate { color: #d00; }
	</style>';

$stacked = array(
	'user'		=> '',
	'mobile'	=> '',
	'bot'		=> '',
	'jch'		=> '',
	'fb'		=> '',
	'other'		=> '',
);

$count = array(
	'other'		=> 0,
	'bot'		=> 0,
	'mobile'	=> 0,
	'jch' 		=> 0,
	'user' 		=> 0,
	'fb' 		=> 0,
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
		if ($parser->type == 'bot' || stripos('x'.$line[1], 'python-') || stripos('x'.$line[1], 'googlebot') || stripos('x'.$line[1], 'twitterbot') || stripos('x'.$line[1], 'mediatoolkitbot'))
			$type = ' bot';
		elseif ($parser->type == 'mobile')
			$type = ' mobile';
		elseif ($line[0] == CHORDIAN)
			$type = ' jch';
		elseif (!empty($line[2]))
			$type = ' user';
		elseif (strpos($line[1], 'www.facebook.com'))
			$type = ' fb';
		$count[trim($type)]++;
		$ip = str_replace('DUPLICATE IP ADDRESS', '<span class="duplicate">DUPLICATE IP ADDRESS</span>', $line[0]);
		$box = '
			<div class="tracking'.$type.'">
				'.(!empty($line[2]) ? '<b>'.$line[2].'</b> ('.$ip.')' : $ip).'<br />
				'.date('H:i', $line[3]).' ('.($duration > 2 ? ($hours ? '<b>'.$hours.'</b> hours ' : '').'<b>'.$minutes.'</b> minutes ago' : '<b>just now</b>').')
				- last updated '.($last > 2 ? '<b>'.$last.'</b> minutes ago' : '<b>just now</b>').'<br />
				'.($parser->fullname != 'unknown' ? $parser->fullname : $line[1]).'
			</div>';
		$stacked[ltrim($type)] .= $box;
	}
}
fclose($handle);

$counts = '
	<div class="counts"><b>DeepSID</b>
		<span><b>Users:</b> '.$count['user'].'</span>
		<span><b>Mobile:</b> '.$count['mobile'].'</span>
		<span><b>Other:</b> '.$count['other'].'</span>
		<span><b>Bots:</b> '.$count['bot'].'</span>
		<span><b>Facebook:</b> '.$count['fb'].'</span>
		<span style="color:#000;"><b>Visitors:</b> '.($count['other'] + $count['mobile'] + $count['user'] + $count['fb']).'</span>
	</div>';

echo $styling.$counts.
	'<table>
		<tr>
			<td>'.$stacked['jch'].$stacked['user'].$stacked['mobile'].'</td><td>'.$stacked['other'].'</td><td>'.$stacked['bot'].$stacked['fb'].'</td>
		</tr>
	</table>';
?>