<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$iterator = null;//import\Datafile::PDO;
$save = false;
$onlyOne = false;
//$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
//$dataPath   = '../sample_data/testcases-rm-toks.xml';
$schemaPath = '../sample_data/SwissProt-schema.xml';
$dataPath   = '../sample_data/SwissProt.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$PDO->beginTransaction();

$schema = new import\Schema($schemaPath);
$doc = new import\Datafile($dataPath, $schema, $PDO);
if($iterator){
	$doc->setTokenIterator($iterator);
}
if($save){
	$doc->save();
}

$pb = new utils\ProgressBar(null, 10);
foreach($doc as $tokenXml){
	$token = new import\Token($tokenXml, $schema);
	if($save){
		$token->save($PDO, $doc->getId(), $doc->generateTokenId());
	}
	$pb->next();
	if($pb->getN() > 1 && $onlyOne){
		break;
	}
}
$PDO->commit();