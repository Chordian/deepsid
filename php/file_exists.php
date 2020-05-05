<?php
/**
 * DeepSID
 *
 * Check if a file exists on the server.
 * 
 * @uses		$_GET['file']
 */
echo file_exists('../'.$_GET['file']);
?>