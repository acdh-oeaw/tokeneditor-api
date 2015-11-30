<?php
/**
 * Sample import script utilizing import\Document class.
 * 
 * To run it:
 * - assure proper database connection settings in config.inc.php
 * - set up configuration variables in lines 14-23
 * - run script from the command line 'php import.php'
 */
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

// token iterator class; if you do not know it, set to NULL
$iterator = import\Document::XML_READER;
// if processed data should be stored in the database
$save = true; 
// allows to limit number of processed tokens (put 0 to process all)
$limit = 0; 
// path to the XML file describing schema
$schemaPath = '../sample_data/testtext-schema.xml';
// path to the XML file with data
$dataPath   = '../sample_data/testtext.xml';

//$schemaPath = '../sample_data/testcases-rm-toks-schema.xml';
//$dataPath   = '../sample_data/testcases-rm-toks.xml';
//$schemaPath = '../sample_data/SwissProt-schema.xml';
//$dataPath   = '../sample_data/SwissProt.xml';
//$schemaPath = '../sample_data/baffleLex_v_0.2_zmorge_20151002-schema.xml';
//$dataPath   = '../sample_data/baffleLex_v_0.2_zmorge_20151002.xml';

###########################################################

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$PDO->beginTransaction();

$name = explode('/', $dataPath);
$name = array_pop($name);
$pb = new utils\ProgressBar(null, 10);

$doc = new import\Document($PDO);
$doc->loadFile($dataPath, $schemaPath, $name, $iterator);
$doc->save($limit, $pb);

if($save){
	$PDO->commit();
}
$pb->finish();