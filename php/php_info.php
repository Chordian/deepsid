<?php
require_once("class.account.php"); // Includes setup

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

if (isset($_GET['flag']))
	phpinfo($_GET['flag']);
?>