<?php
/**
 * DeepSID
 *
 * Build an HTML page with details about groups/work for a composer.
 * 
 * @uses		$_GET['fullname'] (to folder)
 */

require_once("class.account.php"); // Includes setup
require_once("composer_exotic.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

$fullname = $_GET['fullname'];
if (isset($fullname)) {

	$fullname = ProxyExotic($fullname);

	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		// If we are in a sub folder of a composer (e.g. work tunes or a previous handle) with no profile then re-use
		// NOTE: This block is also used in the 'composer.php' file.
		$folders = explode('/', $fullname);
		if (count($folders) > 3 && $folders[1] == 'MUSICIANS' && !empty($folders[4])) {
			// Do we have a profile for the unique sub folder of this composer?
			$select = $db->prepare('SELECT 1 FROM composers WHERE fullname = :fullname LIMIT 1');
			$select->execute(array(':fullname'=>$fullname));
			if ($select->rowCount() == 0)
				// No, re-use the profile of the parent composer folder then
				$fullname = str_replace('/'.$folders[count($folders) - 1], '', $fullname);
		}

		// Get count of RELEASE
		$select = $db->prepare('SELECT count(application) FROM hvsc_files WHERE fullname LIKE :fullname AND application = "RELEASE"');
		$select->execute(array(':fullname'=>$fullname.'/%'));
		$count_release = $select->rowCount() ? $select->fetchColumn() : 0;

		// Get count of PREVIEW
		$select = $db->prepare('SELECT count(application) FROM hvsc_files WHERE fullname LIKE :fullname AND application = "PREVIEW"');
		$select->execute(array(':fullname'=>$fullname.'/%'));
		$count_preview = $select->rowCount() ? $select->fetchColumn() : 0;

		// Get data from the composer profile (if it exists)
		$select = $db->prepare('SELECT * FROM composers WHERE fullname = :fullname LIMIT 1');
		$select->execute(array(':fullname'=>$fullname));
		$select->setFetchMode(PDO::FETCH_OBJ);

		if ($select->rowCount())
			$row = $select->fetch();
		else
			die(json_encode(array('status' => 'ok', 'html' => ''))); // No profile found

	} catch(PDOException $e) {
		$account->LogActivityError('groups.php', $e->getMessage());
		die(json_encode(array('status' => 'error', 'message' => DB_ERROR)));
	}

} else
	die(json_encode(array('status' => 'error', 'message' => 'You must specify the proper GET variables.')));

// Get the XML from the CSDb web service
$xml = curl('https://csdb.dk/webservice/?type='.$row->csdbtype.'&id='.$row->csdbid);
if (!strpos($xml, '<CSDbData>'))
	die(json_encode(array('status' => 'error', 'warning' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>'.
		'<b>ID:</b> <a href="https://csdb.dk/'.$row->csdbtype.'/?id='.$row->csdbid.'" target="_blank">'.$row->csdbid.'</a>')));
$csdb = simplexml_load_string(utf8_decode($xml));

if ($row->csdbtype == 'scener') {

	// SCENER

	$count_releases = $count_credits = 0;
	$groups_array = array();
	$founder_array = array();

	// Loop through all of the handles this user have ever had
	if (isset($csdb->Handle->Scener->Handles->Handle)) {
		$handles = $csdb->Handle->Scener->Handles->Handle;
		foreach($handles as $handle) {

			if ($handle->ID == $row->csdbid) {
				// Already fiddling with that one (no need to get it again)
				$csdb_handle = $csdb;
				$this_handle = $csdb->Handle->Handle;
			} else {
				// There another handle so get the XML for it now
				$xml = curl('https://csdb.dk/webservice/?type=scener&id='.$handle->ID);
				if (!strpos($xml, '<CSDbData>'))
					continue; // BUG FIX: Don't show alert box but just silently ignore and continue
					/*die(json_encode(array('status' => 'warning', 'message' => '<p style="margin-top:0;"><i>Uh... CSDb? Are you there?</i></p>'.
						'<b>ID:</b> <a href="https://csdb.dk/scener/?id='.$handle->ID.'" target="_blank">'.$handle->ID.'</a>')));*/
				$csdb_handle = simplexml_load_string(utf8_decode($xml));
				$this_handle = $handle->Handle;
			}

			// Get an array of ID values for groups this user was a founder of (if any)
			if (isset($csdb_handle->Handle->Founded->Group)) {
				$founded_group = $csdb_handle->Handle->Founded->Group;
				foreach($founded_group as $grp) {
					$founder_array[] = $grp->ID;
				}
			}

			$all_dates = ['0000-00-00'];
			$members_array = array();

			// Build left table with list of groups in the demo scene
			if (isset($csdb_handle->Handle->MemberOf)) {
				$members = $csdb_handle->Handle->MemberOf;
				foreach($members as $member) {
					if (isset($member->Group)) {
						$id			= $member->Group->ID;
						$name		= $member->Group->Name;
						$status		= $member->Status;
						$founder	= in_array((string)$id, $founder_array);

						$dateStart = '';
						if (isset($member->JoinYear)) {
							$dateStart = $member->JoinYear;
							if (isset($member->JoinMonth)) {
								$dateStart .= '-'.str_pad($member->JoinMonth, 2, '0', STR_PAD_LEFT);
								if (isset($member->JoinDay))
									$dateStart .= '-'.str_pad($member->JoinDay, 2, '0', STR_PAD_LEFT);
							}
							$all_dates[] = $dateStart;
						}

						$dateEnd = '';
						if (isset($member->LeaveYear)) {
							$dateEnd = $member->LeaveYear;
							if (isset($member->LeaveMonth)) {
								$dateEnd .= '-'.str_pad($member->LeaveMonth, 2, '0', STR_PAD_LEFT);
								if (isset($member->LeaveDay))
									$dateEnd .= '-'.str_pad($member->LeaveDay, 2, '0', STR_PAD_LEFT);
							}
							$all_dates[] = $dateEnd;
						}

						$members_array[($status == 'active' ? 'z' : $status).$dateStart.$name] = // $status = 'z' or 'ex'
							'<tr>'.
								'<td>'.
									//'<span class="up icon-before icon-arrowright" title="Joined...">'.
									'<span class="up icon-before '.($founder ? 'icon-founder" title="Founded...">' : 'icon-arrowright" title="Joined...">').
									'<a class="group ellipsis" href="https://csdb.dk/group/?id='.$id.'" target="_blank">'.($status == 'ex' ? '<del>'.$name.'</del>' : $name).'</a></span>'.
								'</td>'.
								'<td>'.
									(empty($dateStart) ? '<span class="filler">0000-00-00</span>' : $dateStart).
								'</td>'.
								'<td>'.
									(empty($dateEnd) ? '<span class="filler">0000-00-00</span>' : $dateEnd).
								'</td>'.
							'</tr>';
					}
				}
			}
			sort($all_dates);
			ksort($members_array);
			$member_of = '<tr><td colspan="3" class="tinyhandle">'.$this_handle.'</td></tr>
				<tr><td colspan="3" class="tinytop">&nbsp;</td></tr>';
			if (empty($members_array)) {
				$member_of .= '<tr><td class="dim">N/A</td><td></td><td><span class="filler">0000-00-00</span></td></tr>';
			} else {
				foreach($members_array as $key => $member) {
					$member_of .= $member;
				}
			}
			$member_of .= '<tr><td colspan="3" class="tinybottom">&nbsp;</td></tr>';
			$groups_array[end($all_dates).str_replace(' ', '_', $this_handle)] = $member_of;
		
			if (isset($csdb_handle->Handle->Released))
				$count_releases += $csdb_handle->Handle->Released->Release->count();
			if (isset($csdb_handle->Handle->Credits))
				$count_credits += $csdb_handle->Handle->Credits->Credit->count();
		}

		$all_groups = '';
		ksort($groups_array);
		foreach($groups_array as $group) {
			$all_groups .= $group;
		}

		if (!empty($all_groups)) {
			$member_of =
				'<table class="tight" style="margin-top:0;">'.
					'<tr>'.
						'<th style="width:220px;padding:0 16px 6px 18px;"><u>Group</u></th>'.
						'<th style="width:100px;padding-bottom:6px;"><u>Joined</u></th>'.
						'<th style="padding-bottom:6px;"><u>Quit</u></th>'.
					'</tr>'.
					$all_groups.
				'</table>';
		}

		$counts = '';
		if ($count_releases)
			$counts = $count_releases.' scener release'.($count_releases == 1 ? '' : 's');
		if ($count_credits)
			$counts .= (!empty($counts) ? ' +' : '').' Credit'.($count_credits == 1 ? '' : 's').' in '.$count_credits.' scene production'.($count_credits == 1 ? '' : 's');
	}

} else {

	// GROUP

	// Get an array of ID values for members that founded this group (if any)
	$founder_array = array();
	if (isset($csdb->Group->Founder)) {
		$founded_group = $csdb->Group->Founder;
		foreach($founded_group as $grp) {
			$founder_array[] = $grp->Handle->ID;
		}
	}

	// Build left table with list of members in this group
	$member_of = '';
	$members_array = array();
	if (isset($csdb->Group->Member)) {
		$members = $csdb->Group->Member;
		foreach($members as $member) {
			if (isset($member->Handle)) {
				$id			= $member->Handle->ID;
				$name		= $member->Handle->Handle;
				$status		= $member->Status;
				$founder	= in_array((string)$id, $founder_array);

				$dateStart = '';
				if (isset($member->JoinYear)) {
					$dateStart = $member->JoinYear;
					if (isset($member->JoinMonth)) {
						$dateStart .= '-'.str_pad($member->JoinMonth, 2, '0', STR_PAD_LEFT);
						if (isset($member->JoinDay))
							$dateStart .= '-'.str_pad($member->JoinDay, 2, '0', STR_PAD_LEFT);
					}
				}

				$dateEnd = '';
				if (isset($member->LeaveYear)) {
					$dateEnd = $member->LeaveYear;
					if (isset($member->LeaveMonth)) {
						$dateEnd .= '-'.str_pad($member->LeaveMonth, 2, '0', STR_PAD_LEFT);
						if (isset($member->LeaveDay))
							$dateEnd .= '-'.str_pad($member->LeaveDay, 2, '0', STR_PAD_LEFT);
					}
				}

				$members_array[($status == 'active' ? 'z' : $status).$dateStart.$name] = 
					'<tr>'.
						'<td>'.
							//'<span class="up icon-before icon-arrowright" title="Joined...">'.
							'<span class="up icon-before '.($founder ? 'icon-founder" title="Founder">' : 'icon-arrowright" title="Member">').
							'<a class="group ellipsis" href="https://csdb.dk/scener/?id='.$id.'" target="_blank">'.($status == 'ex' ? '<del>'.$name.'</del>' : $name).'</a></span>'.
						'</td>'.
						'<td>'.
							$dateStart.
						'</td>'.
						'<td>'.
							$dateEnd.
						'</td>'.
					'</tr>';
			}
		}

		ksort($members_array);
		foreach($members_array as $key => $member) {
			$member_of .= $member;
		}

		if (!empty($member_of)) {
			$member_of =
				'<table class="tight" style="margin-top:0;">'.
					'<tr>'.
						'<th style="width:220px;padding:0 16px 6px 18px;"><u>Member</u></th>'.
						'<th style="width:100px;padding-bottom:6px;"><u>Joined</u></th>'.
						'<th style="padding-bottom:6px;"><u>Quit</u></th>'.
					'</tr>'.
					$member_of.
				'</table>';
		}
	}

	$counts = '';
	if (isset($csdb->Group->Release)) {
		$count = $csdb->Group->Release->count();
		$counts = $count.' group release'.($count == 1 ? '' : 's');
	}

}

// Build right table with list of work (typically music and sfx for games)
$employment = '';
if (!empty($row->employment)) {
	$employment =
		'<tr>'.
			'<th style="width:280px;padding:0 16px 6px 19px;"><u>Work</u></th>'.
			'<th style="padding:0 16px 6px 0;"><u>Years</u></th>'.
		'</tr>';

	$jobs = explode(', ', $row->employment);
	foreach($jobs as $job) {
		$parts = explode('|', $job);

		$company = str_replace('[ds-R]', '<span class="icon-before icon-random" title="Sometimes...">', $parts[0].'</span>');
		$company = str_replace('[ds-C]', '<span class="icon-before icon-created" title="Created...">', $company.'</span>');
		$company = str_replace('[ds-W]', '<span class="icon-before icon-work" title="Employed by...">', $company.'</span>');
		$company = str_replace('[ds-X]', '<span class="icon-before icon-etc" title="...and so on"><i>Further career is unrelated to C64</i>', $company.'</span>');

		$employment .= 
			'<tr>'.
				'<td style="width:280px;padding-right:16px;">'.
					$company.
				'</td>'.
				'<td>'.
					$parts[1].
				'</td>'.
			'</tr>';
	}
	$employment = '<table class="tight" style="margin-top:0;">'.$employment.'</table>';
}
$html = /*'<table class="tight top" style="min-width:100%;font-size:14px;">'.*/
			(!empty($member_of) || !empty($employment) ? '<tr>'.
				'<td class="topline leftline" style="padding:0 0 6px 10px;width:50%;">'.(!empty($member_of) ? $member_of : '').'</td>'.
				'<td class="topline leftline rightline" style="padding:0 0 6px 10px;width:50%;">'.(!empty($employment) ? $employment : '').'</td>'.
			'</tr>' : '').
			'<tr>'.
				'<td class="topline bottomline leftline" style="padding-left:10px;width:50%;">'.(!empty($counts) ? '<span class="icon-before icon-swing title="Produced...">'.$counts.'</span>' : '<div class="nocounts">No CSDb profile</div>').'</td>'.
				'<td class="topline bottomline leftline rightline" style="padding-left:10px;">'.($count_release || $count_preview ? '<span class="icon-before icon-note" title="Made music for...">'.($count_release ? $count_release.' released game'.($count_release != 1 ? 's' : '') : '').($count_release && $count_preview ? ' and ' : '').($count_preview ? $count_preview.' game preview'.($count_preview != 1 ? 's' : '') : '').'</span>' : '<div class="nocounts">No game statistics</div>').'</td>'.
			'</tr>';
		/*'</table>';*/

echo json_encode(array('status' => 'ok', 'html' => $html));
?>