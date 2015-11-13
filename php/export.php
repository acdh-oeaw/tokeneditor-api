<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$documentId = 21;

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$doc = new import\Document($PDO);
$doc->loadDb($documentId);
foreach($doc as $token){
	$token->enrich();
}
$doc->export();