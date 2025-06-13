<?php
require_once("class.account.php"); // Includes setup
	try {
		if ($_SERVER['HTTP_HOST'] == LOCALHOST)
			$gb = new PDO(PDO_GB_LOCAL, USER_LOCALHOST, PWD_LOCALHOST);
		else
			$gb = new PDO(PDO_GB_ONLINE, USER_GB_ONLINE, PWD_GB_ONLINE);
		$gb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$gb->exec("SET NAMES UTF8");

		$select_games = $gb->prepare('SELECT * FROM Games WHERE GA_Id = :id LIMIT 1');
		$select_games->execute(array(':id'=>6565));
		$select_games->setFetchMode(PDO::FETCH_OBJ);
		$games = $select_games->fetch();

		// Get the title
		echo $games->Name;

	} catch(PDOException $e) {
		die($e->getMessage());
	}
?>