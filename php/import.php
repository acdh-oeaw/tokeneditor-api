<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
$dataPath   = '../sample_data/testcases-rm-toks.xml';
//$schemaPath = '../sample_data/SwissProt-schema.xml';
//$dataPath   = '../sample_data/SwissProt.xml';

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$PDO->beginTransaction();

$schema = new import\Schema($schemaPath);
$doc = new import\Datafile($dataPath, $schema);
$doc->save($PDO);

$pb = new utils\ProgressBar(null, 10);
foreach($doc as $tokenXml){
	$token = new import\Token($tokenXml, $doc->getDOMXPath(), $schema);
	$token->save($PDO, $doc->getId(), $doc->generateTokenId());
	$pb->next();
}
//$PDO->commit();