<?php
$start = microtime(true);
// @todo Improve this; it should use the new config system
$db = new PDO('mysql:host=chordian.net.mysql;dbname=chordian_net_deepsid;charset=utf8', 'chordian_net_deepsid', '[sovs]');
$db->query("SELECT COUNT(*) FROM files");
echo "Time: ".(microtime(true)-$start)."s\n";
?>