<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
require_once('config.inc.php');
include 'TokenArray.php';
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$userid = $CONFIG['userid'];
$documentid = $_GET['docid'];
$ta = new TokenArray();
$json = $ta->generateJSON($documentid, $con, $userid);
echo $json;


