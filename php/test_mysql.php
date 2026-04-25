<?php
$start = microtime(true);
// @todo Improve this to use the new config system
$db = new PDO('mysql:host=chordian.net.mysql;dbname=chordian_net_deepsid;charset=utf8', 'chordian_net_deepsid', '[sovs]');
$stmt = $db->query("SELECT COUNT(*) FROM hvsc_files");
echo "Time: ".(microtime(true)-$start)."s\n";
?>