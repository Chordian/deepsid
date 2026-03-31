<?php
require_once("class.account.php"); // Includes setup
	try {
		$gb = new PDO(
			'mysql:host='.$config['db_gb64_host'].';dbname='.$config['db_gb64_name'],
			$config['db_gb64_user'],
			$config['db_gb64_pwd']);
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