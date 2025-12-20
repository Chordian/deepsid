<?php
/**
 * DeepSID
 *
 * Export ratings to a CSV file.
 * 
 * @used-by		main.js
 */

require_once("php/class.account.php"); // Includes setup

/**
 * Adapt the 'fullname' column value.
 *
 * @param	string		the text from 'fullname' column
 *
 * @return	string		name adapted to make more sense
 */
function AdaptName($name) {
	if (substr($name, 0, 1) == '!')
		$name = '_DeepSID personal playlist: "'.substr($name, 1).'"';
	else if (substr($name, 0, 1) == '$')
		$name = '_DeepSID public playlist: "'.substr($name, 1).'"';
	$name = str_replace('_High Voltage SID Collection/', 'HVSC/', $name);
	$name = str_replace('_Compute\'s Gazette SID Collection/', 'CGSC/', $name);
	return $name;
}

/***** START *****/

if ($account->CheckLogin()) {

	try {

		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$db = new PDO(PDO_LOCALHOST, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$db = new PDO(PDO_ONLINE, USER_ONLINE, PWD_ONLINE);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES UTF8");

		$select = $db->prepare('SELECT hvsc_files.fullname as file, hvsc_folders.fullname as folder, rating FROM ratings r'.
			' LEFT JOIN hvsc_files on r.table_id = hvsc_files.id AND r.type = "FILE"'.
			' LEFT JOIN hvsc_folders on r.table_id = hvsc_folders.id AND r.type = "FOLDER"'.
			' WHERE r.user_id = :userid');
		$select->execute(array(':userid'=>$account->UserID()));
		$select->setFetchMode(PDO::FETCH_OBJ);

		$i = 0;
		foreach($select as $row) {
			if (!empty($row->file))
				$csv[++$i] = array(AdaptName($row->file), $row->rating);
			else if (!empty($row->folder))
				$csv[++$i] = array(AdaptName($row->folder), $row->rating);
		}

		array_multisort($csv, 0);

		// Prepend header for CSV file
		array_unshift($csv, array('Name', 'Rating (1-5)'));

		if ($i) {

			header('Pragma: no-cache');
			header('Expires: 0');
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="ratings.csv"');

			// Write array of lines to the CSV file
			$fp = fopen('php://output', 'w');
			foreach($csv as $line)
				fputcsv($fp, $line, ';');
			fclose($fp);

			$account->LogActivity('User "'.$account->UserName().'" exported '.$i.' ratings to a CSV file');

		} else
			die('There were no ratings to be exported.');

	} catch(PDOException $e) {
		$account->LogActivityError(basename(__FILE__), $e->getMessage());
		die('A database error occurred.');
	}

} else
	die('User is not logged in.');
?>