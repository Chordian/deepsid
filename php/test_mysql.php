<?php
$start = microtime(true);
$db = new PDO('mysql:host=chordian.net.mysql;dbname=chordian_net_deepsid;charset=utf8', 'chordian_net_deepsid', 'oikcfquh');
$stmt = $db->query("SELECT COUNT(*) FROM hvsc_files");
echo "Time: ".(microtime(true)-$start)."s\n";
?>