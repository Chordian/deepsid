<?php
/**
 * DeepSID
 *
 * Builds the comments for a CSDb page. Included by other CSDb PHP scripts.
 * 
 * Required arrays outside function:
 * 
 * $scener_handle = array();
 * $scener_id = array();
 * 
 * Example of use:
 * 
 * CommentsTable('Trivia', $csdb->Release->Comments->Trivia, $scener_handle, $scener_id);
 */

require_once("setup.php");
require_once("jbbcode/Parser.php");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')
	die("Direct access not permitted.");

function CommentsTable($title, $comments, &$scener_handle, &$scener_id, $backwards = true) {

	$parser = new JBBCode\Parser();
	$parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());

	$comments_array = array();

	foreach($comments as $comment) { // DD.MM.YYYY => YYYY-MM-DD

		$fixed_date = substr($comment->Date, 6).'-'.substr($comment->Date, 3, 2).'-'.substr($comment->Date, 0, 2);

		// Shortcode test cases for [b]:
		// http://chordian/deepsid/?file=/MUSICIANS/L/LMan/Hi_Fi_Sky.sid

		// Shortcode test cases for [img]:
		// http://chordian/deepsid/?file=/MUSICIANS/J/Jammer/Im_Telling_Ya.sid (big static image)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Whenever_it_Hails.sid (GIF animation)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Your_Time_is_Running_Thin.sid (GIF animation)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Kamil_and_Johan_Home_Alone.sid (broken image link)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=82690 (unhandled integrated graphics)

		// Shortcode test cases for [url]:
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/64_Forever.sid
		// http://chordian/deepsid/?file=/MUSICIANS/S/Stainless_Steel/Hold_the_Line.sid
		// http://chordian/deepsid/?file=/MUSICIANS/S/Stainless_Steel/Smashed.sid
		// http://chordian/deepsid/?file=/MUSICIANS/H/Hermit/Damballa.sid
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=146776

		// Shortcode test cases for [code]:
		// http://chordian/deepsid/?file=/MUSICIANS/C/Chiummo_Gaetano/Arcade_Memories_3SID.sid

		// Shortcode test cases for [quote]:
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Pastel_Coloured_Nights.sid (with and without scener)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Winterland_Hades.sid
		// http://chordian/deepsid/?file=/MUSICIANS/P/Psycho/90_Seconds.sid
		// http://chordian/deepsid/?file=/MUSICIANS/H/Holt_Hein/Intro_without_Intro.sid
		// http://chordian/deepsid/?file=/MUSICIANS/G/Groepaz/Wer_ist_Paula.sid (with several newlines)

		// Test case for a too long single word that could mangle the comments table:
		// http://chordian/deepsid/?file=/MUSICIANS/S/Stainless_Steel/Praise_You.sid
		// http://chordian/deepsid/?file=/MUSICIANS/N/Nagie_Sascha/Project_Genesis_short_edit.sid

		// Test cases for where most (if not all) comment block types have been used:
		// http://chordian/deepsid/?file=/MUSICIANS/D/DRAX/Introject.sid (then click "10 Years HVSC")
		// http://chordian/deepsid/?file=/MUSICIANS/C/CreaMD/Black_Sistah.sid
		// http://chordian/deepsid/?file=/MUSICIANS/C/CreaMD/Gonna_Make_You_Sweep.sid (all of them used)

		// Test cases for SID release lists with user comments:
		// NOTE: Sometimes there is no scener ID and handle and all we get is the user id (hence "unknown") here
		// below. Unfortunately these user id are intrinsic and can't be used to look up in the web service.
		// http://chordian/deepsid/?file=/MUSICIANS/S/SounDemoN/Vicious_SID_2-Cybernoid_2.sid
		// http://chordian/deepsid/?file=/MUSICIANS/H/Hubbard_Rob/Knucklebusters.sid
		// http://chordian/deepsid/?file=/MUSICIANS/F/Follin_Tim/Bionic_Commando.sid (three user comments)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Cruzer_on_Chocolate_Islands.sid (same + unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Ashes_to_Ashes.sid (eight user comments + unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Datalife_Verbatim.sid (unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Harden_Your_Horns.sid (unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Jars_Revenge_Soundtrack.sid (unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/L/Linus/Special_Agent_Rocco_Montefiori.sid
		// http://chordian/deepsid/?file=/MUSICIANS/J/JCH/Chimerang.sid (unknown)
		// http://chordian/deepsid/?file=/MUSICIANS/D/DRAX/Caught_in_the_Middle.sid (unknown)

		// Test cases for raw URL links that are long and/or have special characters:
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=61763 (%20)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=138582 (%20)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=151257 (:)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=151258 (works but many long ones)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=79092 (~)
		// http://chordian/deepsid/?tab=csdb&csdbtype=release&csdbid=89110 (#)

		// Test cases for checking if all credits are found properly in release lists:
		// http://chordian/deepsid/?file=/MUSICIANS/H/Hubbard_Rob/Knucklebusters.sid ('Neotunes [optional NeoRAM/GeoRAM]')
		// http://chordian/deepsid/?file=/MUSICIANS/H/Hubbard_Rob/Las_Vegas_Video_Poker.sid ('Video Poker Sounds')
		// http://chordian/deepsid/?file=/MUSICIANS/H/Hubbard_Rob/Last_V8.sid ('Music from TMC')

		$fixed_comment = (string)$comment->Text;

		// Shorten ------------------- lines typically used for competition results
		$fixed_comment = str_replace(str_repeat('-', 50), str_repeat('-', 10), $fixed_comment);

		// Handle extremely long words (i.e. other than -------------------)
		$fixed_comment = preg_replace('$([^\s]{80,})$', '<span class="long">$1</span>', $fixed_comment);

		// Use this library to convert all BBCodes into viable HTML
		// NOTE: See BBCode definitions in: /deepsid/php/jbbcode/DefaultCodeDefinitionSet.php
		$parser->parse($fixed_comment);
		$fixed_comment = $parser->getAsHtml();

		// Turn all raw URL types into a real clickable link
		// NOTE: The skipping regex parts make sure [img] and [url] shortcodes remain unaffected.
		$fixed_comment = preg_replace('~<img.*?/>(*SKIP)(*F)|<a.*?</a>(*SKIP)(*F)|(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i',
			' <a href="$0" target="_blank">$0</a> ', $fixed_comment);

		// Figure out handle, and if we get it, store ID too as repeated use doesn't have handle along with it
		$handle = '';
		if (isset($comment->CSDbUser->Handle))
			$handle = $comment->CSDbUser->Handle;
		else if (isset($comment->CSDbUser->CSDbEntry->Handle->Handle))
			$handle = $comment->CSDbUser->CSDbEntry->Handle->Handle;
		else if (isset($comment->CSDbUser->Login))
			$handle = $comment->CSDbUser->Login;

		$user_id = $comment->CSDbUser->ID; // This ID can't be used to find scener ID but it's always available

		$scid = 0;
		if (isset($comment->CSDbUser->CSDbEntry)) {
			$scid = $comment->CSDbUser->CSDbEntry->Handle->ID;
			// There's a scener ID, store it for later reference
			$scener_id[(string)$user_id] = $scid;
		} else if(array_key_exists((string)$user_id, $scener_id))
			// We've obtained the scener ID for this scener before so get it now
			$scid = $scener_id[(string)$user_id];

		if (!empty($handle))
			// There's a handle for this scener; store it for later reference
			$scener_handle[(string)$user_id] = $handle;
		else if (array_key_exists((string)$user_id, $scener_handle))
			// We've had this scener before so we know the name
			$handle = $scener_handle[(string)$user_id];

		// If the scener ID is in the 'composers' database table then get his/her HVSC home folder
		$hvsc_folder = '';
		if ($scid) {
			try {
				if ($_SERVER['HTTP_HOST'] == LOCALHOST)
					$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
				else
					$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->exec("SET NAMES UTF8");
		
				$select = $db->prepare('SELECT fullname FROM composers WHERE csdbid = :csdbid LIMIT 1');
				$select->execute(array(':csdbid'=>$scid));
				$select->setFetchMode(PDO::FETCH_OBJ);
		
				if ($select->rowCount())
					$hvsc_folder = $select->fetch()->fullname;

			} catch(PDOException $e) {
				// Just forget it then...
			}
		}

		if (!empty($hvsc_folder)) {
			// Figure out the name of the thumbnail (if it exists) for the composer
			$fn = str_replace('_High Voltage SID Collection/', '', $hvsc_folder);
			$fn = str_replace("_Compute's Gazette SID Collection/", "cgsc_", $fn);
			$fn = strtolower(str_replace('/', '_', $fn));
			$thumbnail = 'images/composers/'.$fn.'.jpg';
			if (!file_exists('../'.$thumbnail)) $thumbnail = 'images/composer.png';
		} else {
			// Not a composer but there might be a thumbnail in a different folder
			$fn = preg_replace('/[^a-z0-9]+/i', ' ', $handle);
			$fn = trim($fn);
			$fn = str_replace(" ", "_", $fn);
			$fn = strtolower($fn);
			$thumbnail = 'images/csdb/'.$fn.'.jpg';
			if (!file_exists('../'.$thumbnail)) $thumbnail = '';
		}

		array_push($comments_array, '<tr>'.
			'<td class="user">'.
				($scid
					? '<a href="https://csdb.dk/scener/?id='.$scid.'" target="_blank"><b>'.$handle.'</b></a>'
					: '<b>'.(!empty($handle) ? $handle : '[ID:'.$comment->CSDbUser->ID.']').'</b>'
				).
				'<br /><span class="date">'.$fixed_date.'</span><br />'.
				(!empty($hvsc_folder)
					? '<a href="'.HOST.'?file=/'.$hvsc_folder.'"><img class="avatar" src="'.$thumbnail.'" alt="" /></a>'
					: (!empty($thumbnail) ? '<img class="avatar" src="'.$thumbnail.'" title="Not a composer" alt="" style="cursor:not-allowed;" />' : '')
				).
				'<span class="count pm"><a href="https://csdb.dk/privatemessages/sendmessage.php?userid='.$comment->CSDbUser->ID.'&selectdone.x=1" target="_blank">PM</a></span>'.
				// (!empty($hvsc_folder) ? '<img class="home-folder" src="images/if_folder.svg" alt="" />' : '').
				(!empty($hvsc_folder) ? '<span class="count home-folder" title="Show DeepSID folder" data-home="'.$hvsc_folder.'"><img style="width:14px;" src="images/if_folder.svg" alt="" /></span>' : '').
			'</td>'.
			'<td class="comment">'.
				nl2br($fixed_comment).
			'</td>'.
		'</tr>');
	}

	// Chain the comments together with oldest in top (contrary to CSDb but complies with modern forum standards)
	$final_comments = '';
	$ca = $backwards ? array_reverse($comments_array) : $comments_array;
	foreach($ca as $comment) {
		$final_comments .= $comment;
	}

	$final_comments = '<b style="display:inline-block;margin-top:20px;">'.$title.':</b>'.
		'<span class="oldest-in-top">Oldest in top</span>'.
		'<table class="comments">'.$final_comments.'</table>';
	return $final_comments;
}
?>