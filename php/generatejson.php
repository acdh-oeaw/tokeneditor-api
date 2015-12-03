<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');

require_once('config.inc.php');
include 'TokenArray.php';

$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$userid = filter_input(INPUT_SERVER, $CONFIG['userid']);
$documentid = filter_input(INPUT_GET, 'docid');
$tokenid = filter_input(INPUT_GET, 'tokenid');

$ta = new TokenArray($con);
if($tokenid){
	$ta->setTokenIdFilter($tokenid);
}

$json = $ta->generateJSON($documentid, $userid);
header('Content-Type: application/json');
echo $json;
