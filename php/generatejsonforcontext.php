<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');

require_once('config.inc.php');


$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


$documentid = filter_input(INPUT_GET, '_docid');
$tokenid = filter_input(INPUT_GET, 'token_id');
$limit = filter_input(INPUT_GET, '_limit');
$ta = array($con);



$propQuery = $con->prepare('SELECT json_agg(value ORDER BY token_id) from tokens where document_id = ? and token_id BETWEEN ?::int - ?::int AND ?::int + ?::int');
$propQuery->execute(array($documentid, $tokenid, $limit, $tokenid, $limit));


header('Content-Type: application/json');
echo $propQuery->fetch(PDO::FETCH_COLUMN);