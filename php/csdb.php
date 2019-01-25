<?php
/**
 * DeepSID
 *
 * Call the web service at CSDb and build an HTML page.
 * 
 * @uses		$_GET['fullname']
 * 
 *		- OR -
 * 
 * @uses		$_GET['type']
 * @uses		$_GET['id']
 * @uses		$_GET['back'] - 1 to show a BACK button
 */

require_once("class.account.php"); // Includes setup
require_once("csdb_comments.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$amount_releases = 0;
$scener_handle = array();
$scener_id = array();
$sid_entries = array();

$svg_permalink = '<svg class="permalink" style="enable-background:new 0 0 80 80;" version="1.1" viewBox="0 0 80 80" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M29.298,63.471l-4.048,4.02c-3.509,3.478-9.216,3.481-12.723,0c-1.686-1.673-2.612-3.895-2.612-6.257 s0.927-4.585,2.611-6.258l14.9-14.783c3.088-3.062,8.897-7.571,13.131-3.372c1.943,1.93,5.081,1.917,7.01-0.025 c1.93-1.942,1.918-5.081-0.025-7.009c-7.197-7.142-17.834-5.822-27.098,3.37L5.543,47.941C1.968,51.49,0,56.21,0,61.234 s1.968,9.743,5.544,13.292C9.223,78.176,14.054,80,18.887,80c4.834,0,9.667-1.824,13.348-5.476l4.051-4.021 c1.942-1.928,1.953-5.066,0.023-7.009C34.382,61.553,31.241,61.542,29.298,63.471z M74.454,6.044 c-7.73-7.67-18.538-8.086-25.694-0.986l-5.046,5.009c-1.943,1.929-1.955,5.066-0.025,7.009c1.93,1.943,5.068,1.954,7.011,0.025 l5.044-5.006c3.707-3.681,8.561-2.155,11.727,0.986c1.688,1.673,2.615,3.896,2.615,6.258c0,2.363-0.928,4.586-2.613,6.259 l-15.897,15.77c-7.269,7.212-10.679,3.827-12.134,2.383c-1.943-1.929-5.08-1.917-7.01,0.025c-1.93,1.942-1.918,5.081,0.025,7.009 c3.337,3.312,7.146,4.954,11.139,4.954c4.889,0,10.053-2.462,14.963-7.337l15.897-15.77C78.03,29.083,80,24.362,80,19.338 C80,14.316,78.03,9.595,74.454,6.044z"/></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg>';

if (isset($_GET['fullname'])) {
	// Get the CSDb 'type' and 'id' from the database row
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$select = $db->prepare('SELECT csdbtype, csdbid FROM hvsc_files WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$_GET['fullname']));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount()) {
			$row = $select->fetch();
			$csdb_type = $row->csdbtype;	// Can be 'release' or 'sid'
			$csdb_id = $row->csdbid;		// ID relates to the type
		} else {
			$account->LogActivityError('csdb.php', 'No database info returned; $_GET[\'fullname\'] = '.$_GET['fullname']);
			die(json_encode(array('status' => 'error', 'message' => "Couldn't find the information in the database.")));
		}
	} catch(PDOException $e) {
		$account->LogActivityError('csdb.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

	if (empty($csdb_type))
		die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>No CSDb entry available.</i></p>')));

	$go_back = '';

} else if (isset($_GET['type']) && isset($_GET['id'])) {
	// The 'type' and 'id' was directly specified
	$csdb_type = $_GET['type'];
	$csdb_id = $_GET['id'];
	$go_back = $_GET['back'] ? '<button id="go-back">Back</button>' : '';
} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

// Get the XML from the CSDb web service
$xml = file_get_contents('https://csdb.dk/webservice/?type='.$csdb_type.'&id='.$csdb_id.($csdb_type == 'sid' ? '&depth=3' : ''));
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'warning', 'html' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>'.
		'<b>ID:</b> <a href="https://csdb.dk/'.$csdb_type.'/?id='.$csdb_id.'" target="_blank">'.$csdb_id.'</a>')));
$csdb = simplexml_load_string(utf8_decode($xml));

// Building HTML for release
if ($csdb_type == 'sid') {

	/***** Entry: SID *****/

	$sid_handles = array();
	$sid_groups = array();

	// For user comments in "sid" entries, we need to get them with lower depth to ensure we get all handles
	$xml = file_get_contents('https://csdb.dk/webservice/?type=sid&id='.$csdb_id);
	$simple_csdb = !strpos($xml, '<CSDbData>') ? $csdb : simplexml_load_string(utf8_decode($xml));

	// For some reason the XML user comments for "sid" entries are not backwards
	$user_comments = isset($simple_csdb->SID->UserComment)
		? CommentsTable('User comments', $simple_csdb->SID->UserComment, $scener_handle, $scener_id)
		: '';
	
	$comment_button = '<button id="csdb-comment" data-type="sid" data-id="'.$csdb->SID->ID.'">Comment</button><br />';

	$used_by_releases = $user_comments.$comment_button.'<h3>0 releases found</h3><div style="border-top:1px solid #ced0c0;"></div>';
	if (isset($csdb->SID->UsedIn)) {
		$releases = $csdb->SID->UsedIn->Release;

		// First let's try to harvest scener handles from (unused) comment blocks of all types in each release
		// This is necessary because if used there, their names are not repeated among the used data later! ><
		foreach($releases as $release) {
			if (isset($release->Comments)) {
				foreach($release->Comments->children() as $commentBlock) {
					if (isset($commentBlock->CSDbUser->CSDbEntry->Handle)) {
						$scid = $commentBlock->CSDbUser->CSDbEntry->Handle->ID;
						$user_id = $commentBlock->CSDbUser->ID;
						$scih = '';
						if (isset($commentBlock->CSDbUser->CSDbEntry->Handle->Handle))
							$scih = $commentBlock->CSDbUser->CSDbEntry->Handle->Handle;
						else if (isset($commentBlock->CSDbUser->Handle))
							$scih = $commentBlock->CSDbUser->Handle;
						else if (isset($commentBlock->CSDbUser->Login))
							$scih = $commentBlock->CSDbUser->Login;
						if (!empty($scih)) {
							// We found a scener handle, better store it for a rainy day
							$sid_handles[(string)$scid] = $scih;
							// Also for comment function
							$scener_id[(string)$user_id] = $scid;
							$scener_handle[(string)$user_id] = $scih;
						}
					}
				}
			}
			// Again, this time from (also unused) credits
			if (isset($release->Credits)) {
				foreach($release->Credits->Credit as $credit) {
					if (isset($credit->Handle->Handle))
						// We found a scener handle, better store it for a rainy day
						$sid_handles[(string)$credit->Handle->ID] = $credit->Handle->Handle;
					if (isset($credit->Handle->Scener->Handles->Handle->Handle))
						// The more the merrier
						$sid_handles[(string)$credit->Handle->Scener->Handles->Handle->ID] = $credit->Handle->Scener->Handles->Handle->Handle;
				}
			}
		}

		$used_by_releases = '';
		$amount_releases = 0;
		foreach($releases as $release) {

			// Type of the production that used this SID file
			$type = isset($release->Type) ? $release->Type : '';
			$can_show_internally = true; // Reversed this; hope all release types now work!

			// Handles or groups that used this SID file
			$released_by = '';
			$handles = $release->ReleasedBy->Handle;
			$amount = 4;
			if (isset($handles)) {
				foreach($handles as $handle) {
					$id = $handle->ID;
					$scener = '';
					if (isset($handle->Handle)) {
						// There's a handle, store it for later reference
						$scener = $handle->Handle;
						$sid_handles[(string)$id] = $scener;
					} else if (array_key_exists((string)$id, $sid_handles))
						// We've had this scener before so we know the name
						$scener = $sid_handles[(string)$id];
					$released_by .= (!empty($scener)
						? ', <a href="https://csdb.dk/scener/?id='.$id.'" target="_blank">'.$scener.'</a>'
						: ', [<a href="https://csdb.dk/scener/?id='.$id.'" target="_blank">Scener:'.$id.'</a>]'
					);
					if (!$amount) {
						$released_by .= ' [...]';
						break;
					}
					$amount--;
				}
			}
			$groups = $release->ReleasedBy->Group;
			if (isset($groups) && $amount) {
				foreach($groups as $group) {
					$id = $group->ID;
					$grp = '';
					if (isset($group->Name)) {
						// There's a group name, store it for later reference
						$grp = $group->Name;
						$sid_groups[(string)$id] = $grp;
					} else if (array_key_exists((string)$id, $sid_groups))
						// We've had this group before so we know the name
						$grp = $sid_groups[(string)$id];
					$released_by .= (!empty($grp)
						? ', <a href="https://csdb.dk/group/?id='.$id.'" target="_blank">'.$grp.'</a>'
						: ', [<a href="https://csdb.dk/group/?id='.$id.'" target="_blank">Group:'.$id.'</a>]'
					);
					if (!$amount) {
						$released_by .= ' [...]';
						break;
					}
					$amount--;
				}
			}
			$type_and_released_by = $type.(empty($released_by) ? '' : ' by '.substr($released_by, 2));

			// Release date
			$release_date = '<br /><span class="rdate" style="margin-right:0;"></span>';
			if (isset($release->ReleaseDay) || isset($release->ReleaseMonth) || isset($release->ReleaseYear)) {
				$day = isset($release->ReleaseDay) ? $release->ReleaseDay : '';
				$month = '';
				if (isset($release->ReleaseMonth)) {
					$dateObj = DateTime::createFromFormat('!m', $release->ReleaseMonth);
					$month = $dateObj->format('F');
				}
				$year = isset($release->ReleaseYear) ? $release->ReleaseYear : '';
				$release_date = '<br /><span class="rdate">'.$day.' '.$month.' '.$year.'</span>';
			}

			// Download links
			$download_link = '';
			if (isset($release->DownloadLinks)) {
				$dlinks = $release->DownloadLinks->DownloadLink;
				foreach($dlinks as $dlink) {
					$download_link .= '<span class="count"><a href="'.$dlink->CounterLink.'">DL</a></span>';
				}
			}

			$external_icon = '';
			if (!$can_show_internally) {
				// An external icon indicates that clicking the thumbnail/title goes to CSDb itself
				$external_icon = '<img class="external" src="images/external_link.svg" alt="" />';
			}

			$adapted_name = strlen($release->Name) > 75 ? substr($release->Name, 0, 75).'...' : $release->Name;

			$entry =
				'<tr>'.
					'<td class="thumbnail">'.
						(isset($release->ScreenShot)
							? '<a '.($can_show_internally ? 'class="internal" ' : '').'href="https://csdb.dk/release/?id='.$release->ID.'" data-id="'.$release->ID.'" target="_blank"><img src="'.$release->ScreenShot.'" alt="'.$release->Name.'" /></a>'
							: '<a '.($can_show_internally ? 'class="internal" ' : '').'href="https://csdb.dk/release/?id='.$release->ID.'" data-id="'.$release->ID.'" target="_blank"><img src="images/noscreenshot.gif" alt="'.$release->Name.'" /></a>').
					'</td>'.
					'<td class="info">'.
						'<a class="'.($can_show_internally ? 'internal ' : '').'name" href="https://csdb.dk/release/?id='.$release->ID.'" data-id="'.$release->ID.'" target="_blank">'.$adapted_name.'</a><br />'.
						$type_and_released_by.
						$release_date.
						$download_link.
						$external_icon.
					'</td>'.
				'</tr>';

			// Push HTML and some data to an array for use by the sort drop-down box (in jQuery)
			array_push($sid_entries, array(
				'id'		=> (int)$release->ID,
				'html'		=> $entry,
				'title'		=> strtolower($release->Name),
				'type'		=> strtolower($type),
				'date'		=> (isset($release->ReleaseYear) ? $release->ReleaseYear : '0000').'-'.(isset($release->ReleaseMonth) ? str_pad($release->ReleaseMonth, 2, '0', STR_PAD_LEFT) : '00').'-'.(isset($release->ReleaseDay) ? str_pad($release->ReleaseDay, 2, '0', STR_PAD_LEFT) : '00'),
			));

			$amount_releases++;
		}

		$used_by_releases = 
			$user_comments.
			$comment_button.
			'<h3 id="csdb-releases">'.$amount_releases.' release'.($amount_releases > 1 ? 's' : '').' found</h3>'.
			'<div id="csdb-sort">
				<label for="dropdown-sort-csdb" class="unselectable">Sort by</label>
				<select id="dropdown-sort-csdb" name="sort-csdb">
					<option value="title">Title</option>
					<option value="type">Type</option>
					<option value="oldest">Oldest</option>
					<option value="newest" selected="selected">Newest</option>
					<option value="low-id">Lower ID</option>
					<option value="high-id">Higher ID</option>
				</select>
			</div>'.
			'<table class="releases">'.
			'</table>';
	}

	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-top:0;">'.$csdb->SID->Name.'</h2>'.
		'<a href="//deepsid.chordian.net?tab=csdb&csdbtype=sid&csdbid='.$csdb->SID->ID.'" title="Permalink">'.$svg_permalink.'</a><br />'.
		$used_by_releases.
		'<div id="corner-icons">'.
			'<a href="https://csdb.dk/sid/?id='.$csdb->SID->ID.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
		'</div>';

	if (!empty($user_comments) && $amount_releases == 0)
		$amount_releases = -1; // There are at least comments present in top

} else if ($csdb_type == 'release') {

	/***** Entry: RELEASE *****/

	// Find the SID page ID for creating a 'BACK' button (if the HVSC URL exists on the page)
	if (isset($csdb->Release->UsedSIDs->SID) && isset($_GET['fullname'])) {
		$sids = $csdb->Release->UsedSIDs->SID;
		foreach($sids as $sid) {
			if ($sid->HVSCPath == '/'.str_replace('_High Voltage SID Collection/', '',$_GET['fullname'])) {
				$go_back = '<button id="go-back-init" data-id="'.$sid->ID.'">Back</button>';
				break;
			}
		}
	}

	$sceners = array();
	$amount_releases = -1; // This is how the notification value on the 'CSDb' tab knows it's a release

	// Screenshot
	$screenshot = isset($csdb->Release->ScreenShot) ? $csdb->Release->ScreenShot : 'images/noscreenshot.gif';

	// Handles and/or groups it was released by
	$released_by = '';
	if (isset($csdb->Release->ReleasedBy)) {
		$handles = $csdb->Release->ReleasedBy->Handle; 
		if (isset($handles)) {
			foreach($handles as $handle) {
				$released_by .= ', <a href="https://csdb.dk/scener/?id='.$handle->ID.'" target="_blank">'.$handle->Handle.'</a>';
				if (!array_key_exists((string)$handle->ID, $sceners))
					// Save the handle in case the ID is repeated in 'Credits' further below
					$sceners[(string)$handle->ID] = $handle->Handle;
			}
		}
		$groups = $csdb->Release->ReleasedBy->Group;
		if (isset($groups)) {
			foreach($groups as $group) {
				$released_by .= ', <a href="https://csdb.dk/group/?id='.$group->ID.'" target="_blank">'.$group->Name.'</a>';
			}
		}
		$released_by = '<p><b>Released by:</b><br />'.substr($released_by, 2).'</p>';
	}

	// Release date
	$release_date = '';
	if (isset($csdb->Release->ReleaseDay) || isset($csdb->Release->ReleaseMonth) || isset($csdb->Release->ReleaseYear)) {
		$day = isset($csdb->Release->ReleaseDay) ? $csdb->Release->ReleaseDay : '';
		$month = '';
		if (isset($csdb->Release->ReleaseMonth)) {
			$dateObj = DateTime::createFromFormat('!m', $csdb->Release->ReleaseMonth);
			$month = $dateObj->format('F');
		}
		$year = isset($csdb->Release->ReleaseYear) ? $csdb->Release->ReleaseYear : '';
		$release_date = '<p><b>Release date:</b><br />'.$day.' '.$month.' '.$year.'</p>';
	}

	// Also known as
	$also_known_as = isset($csdb->Release->AKA) ? '<p><b>AKA:</b><br />'.$csdb->Release->AKA.'</p>' : '';

	// Web site
	$web_site = isset($csdb->Release->Website) ? '<p><b>Web site:</b><br />'.
		'<a href="'.$csdb->Release->Website.'" target="_blank">'.$csdb->Release->Website.'</a></p>' : '';

	// Released at - OR - Achievement
	$released_at_or_achievement = '';
	if (isset($csdb->Release->ReleasedAt->Event)) {
		$link = '<a href="https://csdb.dk/event/?id='.$csdb->Release->ReleasedAt->Event->ID.'" target="_blank">'.$csdb->Release->ReleasedAt->Event->Name.'</a>';
		if (isset($csdb->Release->Achievement)) {
			$released_at_or_achievement = '<p><b>Achievement:</b><br />'.
				$csdb->Release->Achievement->Compo.' Competition at '.$link.':<span class="count">'.
				(isset($csdb->Release->Achievement->Place) ? $csdb->Release->Achievement->Place : '?').
				'</span><button id="show-compo" data-compo="'.$csdb->Release->Achievement->Compo.'" data-id="'.$csdb->Release->ReleasedAt->Event->ID.'" data-mark="'.$csdb_id.'">Show</button></p>';
		} else {
			$released_at_or_achievement = '<p><b>Released at:</b><br />'.$link.'</p>';
		}
	}

	// CSDb's user rating
	$csdb_user_rating = isset($csdb->Release->Rating)
		? '<p><b>CSDb user rating:</b><br /><a href="https://csdb.dk/votestatistics.php?type=release&id='.$csdb->Release->ID.'" target="_blank">'.$csdb->Release->Rating.'</a> out of 10</p>'
		: '';
	
	// Credits
	// NOTE: Handles for ID may already have been added to the '$sceners' array by 'Release by' above.
	$credits = '';
	$roles = array();
	if ($csdb->Release->Credits && strtolower($csdb->Release->Type) != 'c64 music') {
		$credits = $csdb->Release->Credits->Credit;
		// First gather an associative array of credit roles where each may contain a list of members
		foreach($credits as $credit) {
			if (!isset($credit->Handle)) break; // Yes, this happens!
			$id = $credit->Handle->ID;
			if (isset($credit->Handle->Handle)) {
				// There's a handle, get it and store the ID for it for later reference
				$handle = $credit->Handle->Handle.','.$id;
				$sceners[(string)$id] = $credit->Handle->Handle;
			} else if (array_key_exists((string)$id, $sceners)) {
				// We've had this scener before so we know the name
				$handle = $sceners[(string)$id].','.$id;
			} else {
				// Can't figure this scener out so just use the ID
				$handle = $id;
			}
			// Throw on the pile of this credit type as there might be more later
			$roles[(string)$credit->CreditType][] = $handle;
		}
		$credits = '';
		// Now build the credit roles with a list of comma-separated members for each
		foreach($roles as $role => $members) {
			$list = '';
			sort($members);
			$amount = 5;
			foreach($members as $member) {
				if (strpos($member, ',')) {
					$parts = explode(',', $member);
					// ID and handle
					$m = '<a href="https://csdb.dk/scener/?id='.$parts[1].'" target="_blank">'.$parts[0].'</a>';
				} else {
					// [Scener:1234]
					$m = '[<a href="https://csdb.dk/scener/?id='.$member.'" target="_blank">Scener:'.$member.'</a>]';
				}
				$list .= ', '.$m;
				if (!$amount) {
					$list .= ' [...]';
					break;
				}
				$amount--;
			}
			$credits .=
				'<tr>'.
					'<td style="padding-right:6px;">'.
						$role.
					'</td>'.
					'<td>'.
						':&nbsp;&nbsp;'.substr($list, 2).
					'</td>'.
				'</tr>';
		}
		$credits = '<p></p><b>Credits:</b><table class="tight">'.$credits.'</table>';
	}

	// SID files used in this release
	$sid_files_used = '';
	$amount_sid = 0;
	if (isset($csdb->Release->UsedSIDs) && strtolower($csdb->Release->Type) != 'c64 music') {
		$sids = $csdb->Release->UsedSIDs->SID;
		foreach($sids as $sid) {
			$sid_files_used .= 
				'<tr>'.
					'<td style="padding-right:16px;">'.
						'<a href="#" class="redirect">'.$sid->HVSCPath.'</a>'.
					'</td>'.
					'<td>'.
						'by '.$sid->Author.
					'</td>'.
				'</tr>';
			$amount_sid++;
		}
		$sid_files_used = '<p></p><b>'.$amount_sid.' SID file'.($amount_sid != 1 ? 's' : '').' used:</b>'.
			'<table class="tight" style="font-size:14px;">'.$sid_files_used.'</table>';
	}

	// List of download links
	$download_links = '';
	if (isset($csdb->Release->DownloadLinks)) {
		$dlinks = $csdb->Release->DownloadLinks->DownloadLink;
		foreach($dlinks as $dlink) {
			$download_links .= '<br /><a href="'.$dlink->CounterLink.'">'.utf8_decode(urldecode($dlink->Link)).'</a>'.
				'<span class="count">'.$dlink->Downloads.'</span>';
		}
		$download_links = '<p><b>Download:</b>'.$download_links.'</p>';
	}

	$goofs = isset($csdb->Release->Comments->Goof)
	? CommentsTable('Goofs', $csdb->Release->Comments->Goof, $scener_handle, $scener_id)
	: '';

	$hidden_parts = isset($csdb->Release->Comments->HiddenPart)
	? CommentsTable('Hidden parts', $csdb->Release->Comments->HiddenPart, $scener_handle, $scener_id)
	: '';

	$production_notes = isset($csdb->Release->Comments->ProductionNote)
	? CommentsTable('Production notes', $csdb->Release->Comments->ProductionNote, $scener_handle, $scener_id)
	: '';

	$user_comments = isset($csdb->Release->Comments->UserComment)
	? CommentsTable('User comments', $csdb->Release->Comments->UserComment, $scener_handle, $scener_id)
	: '';

	$summaries = isset($csdb->Release->Comments->Summary)
		? CommentsTable('Summaries', $csdb->Release->Comments->Summary, $scener_handle, $scener_id)
		: '';

	$trivia = isset($csdb->Release->Comments->Trivia)
	? CommentsTable('Trivia', $csdb->Release->Comments->Trivia, $scener_handle, $scener_id)
	: '';

	$comment_button = '<button id="csdb-comment" data-type="release" data-id="'.$csdb->Release->ID.'">Comment</button>'.
		'<small style="color:#969787;">Shared for all types of comment sections.</small><br />';

	// Now build the HTML
	$html = '<h2 style="display:inline-block;margin-top:0;">'.$csdb->Release->Name.'</h2>'.$go_back.
		'<a href="https://deepsid.chordian.net?tab=csdb&csdbtype=release&csdbid='.$csdb->Release->ID.'" title="Permalink">'.$svg_permalink.'</a><br />'.
		'<table style="border:none;margin-bottom:0;"><tr>'.
			'<td style="padding:0;border:none;width:384px;">'.
				'<img class="screenshot" src="'.$screenshot.'" alt="'.$csdb->Release->Name.'" />'.
			'</td>'.
			'<td style="position:relative;vertical-align:top;">'.
				$released_by.
				$release_date.
				'<p><b>Type:</b><br />'.$csdb->Release->Type.'</p>'.
			'</td>'.
		'</tr></table>'.
		$also_known_as.
		$web_site.
		$released_at_or_achievement.
		$csdb_user_rating.
		$credits.
		$sid_files_used.
		$download_links.
		$summaries.
		$production_notes.
		$trivia.
		$goofs.
		$hidden_parts.
		$user_comments.
		$comment_button.
		'<div id="corner-icons">'.
			'<a href="https://csdb.dk/release/?id='.$csdb->Release->ID.'" title="See this at CSDb" target="_blank"><svg class="outlink" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg></a>'.
		'</div>';
}
echo json_encode(array('status' => 'ok', 'html' => $html.'<i><small>Generated using the <a href="https://csdb.dk/webservice/" target="_blank">CSDb web service</a></small></i>', 'count' => $amount_releases, 'entries' => $sid_entries));
?>