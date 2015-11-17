<?php
/**
 * Sample export script utilizing import\Document class.
 * 
 * To run it:
 * - assure proper database connection settings in config.inc.php
 * - set up configuration variables in lines 13-18
 * - run script from the command line 'php export.php'
 */require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

// document id in the tokeneditor database (see the documents table)
$documentId = 39;
// replate properties values in-place or add full <fs> structures
$inPlace = true;
// path to the export file to create
$exportPath = '../sample_data/export.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$pb = new utils\ProgressBar(null, 10);
$doc = new import\Document($PDO);
$doc->loadDb($documentId);
$doc->export($inPlace, $exportPath, $pb);
$pb->finish();
