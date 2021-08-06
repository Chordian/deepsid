<?php
/**
 * DeepSID
 *
 * Check if a file exists on the server.
 * 
 * @uses		$_GET['file']
 * 
 * @used-by		controls.js
 * @used-by		main.js
 */

echo file_exists('../'.$_GET['file']);
?>