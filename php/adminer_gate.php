<?php
require_once("class.account.php"); // Includes setup

if (!$account->isAdmin()) {
    http_response_code(403);
    die("This is for administrators only.");
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require "adminer.php";
?>