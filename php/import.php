<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$iterator = import\Document::DOM_DOCUMENT;
$save = false;
$onlyOne = false;
$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
$dataPath   = '../sample_data/testcases-rm-toks.xml';
//$schemaPath = '../sample_data/SwissProt-schema.xml';
//$dataPath   = '../sample_data/SwissProt.xml';
//$schemaPath = '../sample_data/baffleLex_v_0.2_zmorge_20151002-schema.xml';
//$dataPath   = '../sample_data/baffleLex_v_0.2_zmorge_20151002.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$PDO->beginTransaction();

$pb = new utils\ProgressBar(null, 10);
$doc = new import\Document($PDO);
$doc->loadFile($dataPath, $schemaPath, $iterator);
if($save){
	$doc->save();
}

foreach($doc as $token){
	if($save){
		$token->save();
	}
	$pb->next();
	if($pb->getN() > 1 && $onlyOne){
		break;
	}
}
$PDO->commit();
$pb->finish();