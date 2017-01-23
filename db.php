<?php
$db = new mysqli('/*** HOST ***/', '/*** USER ***/', '/*** PASS ***/', '/*** DATABASE ***/');
if($db->connect_errno)
	die('Error MySQLi: '.$db->connect_error);
if(!$db->set_charset('utf8'))
	die('Error MySQLi: '.$db->error);
?>