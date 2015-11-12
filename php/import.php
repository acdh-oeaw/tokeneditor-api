<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$iterator = null;//import\Datafile::PDO;
$save = false;
$onlyOne = true;
$schemaPath = '../sample_data/baffleLex_v_0.2_zmorge_20151002-schema.xml';
$dataPath   = '../sample_data/baffleLex_v_0.2_zmorge_20151002.xml';
//$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
//$dataPath   = '../sample_data/testcases-rm-toks.xml';
//$schemaPath = '../sample_data/SwissProt-schema.xml';
//$dataPath   = '../sample_data/SwissProt.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$PDO->beginTransaction();

$doc = new import\Datafile($PDO);
$doc->loadFile($dataPath, $schemaPath, $iterator);
if($save){
	$doc->save();
}

$pb = new utils\ProgressBar(null, 10);
foreach($doc as $tokenXml){
	$token = new import\Token($tokenXml, $doc->getSchema);
	if($save){
		$token->save($PDO, $doc->getId(), $doc->generateTokenId());
	}
	$pb->next();
	if($pb->getN() > 1 && $onlyOne){
		break;
	}
}
$PDO->commit();