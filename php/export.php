<?php
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$documentId = 39;
$inPlace = true;
$exportPath = '../sample_data/export.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$pb = new utils\ProgressBar(null, 10);
$doc = new import\Document($PDO);
$doc->loadDb($documentId);
$doc->export($exportPath, $inPlace, $pb);
$pb->finish();
