<?php 
require_once('config.inc.php');
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
include 'TokenArray.php';
$documentid = $_POST['docid'];
$ta = new TokenArray();
$json = $ta->generateJSON($documentid, $con);
echo $json;


