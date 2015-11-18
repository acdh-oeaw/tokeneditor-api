<?php 
require_once('config.inc.php');
include 'TokenArray.php';
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);

$documentid = $_GET['docid'];
$ta = new TokenArray();
$json = $ta->generateJSON($documentid, $con);
echo $json;


