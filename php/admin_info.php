<?php
/**
 * DeepSID
 *
 * Show the information page in the 'Admin' tab.
 * 
 * For administrators only.
 * 
 * @used-by		main.js
 */

require_once("class.account.php"); // Includes setup

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");
if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

$baseURL = $_SERVER['HTTP_HOST'] == LOCALHOST ? "http://chordian/deepsid/php/" : "https://deepsid.chordian.net/php/";

$html = '<h3>Information</h3>
			<h4>Tracking</h4>
				<div class="script">
					<div class="monitor">Visitor tracking</div>
					<span>Show a tracking page with the current number of visitors.</span>
					<button class="run-script" data-script="'.$baseURL.'../parse.php">SHOW</button>
				</div>
				<div class="script">
					<div class="monitor">Activity log</div>
					<span>Show a log with the various features users access on the site.</span>
					<button class="run-script" data-script="'.$baseURL.'../logs/activity.htm">SHOW</button>
				</div>
				<div class="script">
					<div class="monitor">Tags log</div>
					<span>Show a log of the latest tags added to or removed from SID files.</span>
					<button class="run-script" data-script="'.$baseURL.'../logs/activity.htm">SHOW</button>
				</div>
				<div class="script">
					<div class="monitor">Database errors</div>
					<span>Show a log of account-related database errors.</span>
					<button class="run-script" data-script="'.$baseURL.'../logs/db_errors_account.txt">SHOW</button>
				</div>
	';

$html .=  '<h4>PHP constants</h4>
	<table style="font-size:13px;">
		<tr>
			<th style="width:2%;">PHP_VERSION</th>
			<td style="width:48%;">'.PHP_VERSION.'</td>
			<th style="width:2%;">PHP_OS</th>
			<td style="width:48%;">'.PHP_OS.'</td>
		</tr>
		<tr>
			<th>PHP_SAPI</th>
			<td>'.PHP_SAPI.'</td>
			<th>PHP_MAXPATHLEN</th>
			<td>'.PHP_MAXPATHLEN.'</td>
		</tr>
	</table>';

$html .=  '<h4>PHP ini</h4>
	<table style="font-size:13px;">
		<tr>
			<th style="width:2%;">memory_limit</th>
			<td style="width:48%;">'.ini_get('memory_limit').'</td>
			<th style="width:2%;">upload_max_filesize</th>
			<td style="width:48%;">'.ini_get('upload_max_filesize').'</td>
		</tr>
		<tr>
			<th>max_execution_time</th>
			<td>'.ini_get('max_execution_time').'</td>
			<th>post_max_size</th>
			<td>'.ini_get('post_max_size').'</td>
		</tr>
	</table>';
/*		<tr>
			<th>display_errors</th>
			<td>'.ini_get('display_errors').'</td>
			<th>log_errors</th>
			<td>'.ini_get('log_errors').'</td>
		</tr>
		<tr>
			<th>error_log</th>
			<td>'.ini_get('error_log').'</td>
			<th>date.timezone</th>
			<td>'.ini_get('date.timezone').'</td>
		</tr>
	</table>';*/

$html .=  '<h4>PHP variables</h4>
	<table style="font-size:13px;">
		<tr>
			<th style="width:1px;">$_SERVER[\'SERVER_SOFTWARE\']</th>
			<td>'.$_SERVER['SERVER_SOFTWARE'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'HTTP_USER_AGENT\']</th>
			<td>'.$_SERVER['HTTP_USER_AGENT'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'SERVER_NAME\']</th>
			<td>'.$_SERVER['SERVER_NAME'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'HTTP_HOST\']</th>
			<td>'.$_SERVER['HTTP_HOST'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'REQUEST_URI\']</th>
			<td>'.$_SERVER['REQUEST_URI'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'SCRIPT_FILENAME\']</th>
			<td>'.$_SERVER['SCRIPT_FILENAME'].'</td>
		</tr>
		<tr>
			<th>$_SERVER[\'DOCUMENT_ROOT\']</th>
			<td>'.$_SERVER['DOCUMENT_ROOT'].'</td>
		</tr>
	</table>';

$html .= '<h4>PHP info</h4>
	<table style="font-size:13px;">
		<tr>
			<td><a href="php/php_info.php?flag=1" target="_blank">PHPInfo(1)</a></td>
			<td>INFO_GENERAL</td>
			<td>The configuration line, php.ini location, build date, Web Server, System and more.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=2" target="_blank">PHPInfo(2)</a></td>
			<td>INFO_CREDITS</td>
			<td>PHP Credits.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=4" target="_blank">PHPInfo(4)</a></td>
			<td>INFO_CONFIGURATION</td>
			<td>Current Local and Master values for PHP directives.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=8" target="_blank">PHPInfo(8)</a></td>
			<td>INFO_MODULES</td>
			<td>Loaded modules and their respective settings.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=16" target="_blank">PHPInfo(16)</a></td>
			<td>INFO_ENVIRONMENT</td>
			<td>Environment Variable information that\'s also available in $_ENV.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=32" target="_blank">PHPInfo(32)</a></td>
			<td>INFO_VARIABLES</td>
			<td>Shows all predefined variables from EGPCS (Environment, GET, POST, Cookie, Server).</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=64" target="_blank">PHPInfo(64)</a></td>
			<td>INFO_LICENSE</td>
			<td>PHP License information.</td>
		</tr>
		<tr>
			<td><a href="php/php_info.php?flag=-1" target="_blank">PHPInfo(-1)</a></td>
			<td>INFO_ALL</td>
			<td>Shows all of the above.</td>
		</tr>
	</table>';

/*$html .=  '<h4>Session info</h4>
	<table style="font-size:13px;">
		<tr>
			<th style="width:1px;">session_id()</th>
			<td>'.session_id().'</td>
		</tr>
		<tr>
			<th>session_save_path()</th>
			<td>'.session_save_path().'</td>
		</tr>
		<tr>
			<th>session_status()</th>
			<td>'.session_status().'</td>
		</tr>
	</table>';*/

/*$html .=  '<h4>Memory usage</h4>
	<table style="font-size:13px;">
		<tr>
			<th style="width:1px;">memory_get_usage(true)</th>
			<td>'.memory_get_usage(true).'</td>
		</tr>
		<tr>
			<th>memory_get_peak_usage(true)</th>
			<td>'.memory_get_peak_usage(true).'</td>
		</tr>
	</table>';*/

die(json_encode(array('status' => 'ok', 'html' => $html)));
?>