<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$iterator = null;//import\Datafile::PDO;
$save = true;
$onlyOne = false;
$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
$dataPath   = '../sample_data/testcases-rm-toks.xml';
//$schemaPath = '../sample_data/SwissProt-schema.xml';
//$dataPath   = '../sample_data/SwissProt.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
