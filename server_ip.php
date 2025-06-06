<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ipify.org");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$ip = curl_exec($ch);
curl_close($ch);

echo "Server IP: $ip";
?>