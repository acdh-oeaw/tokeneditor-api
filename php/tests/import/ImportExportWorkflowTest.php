<?php

namespace import;

require_once '../src/utils/ClassLoader.php';
new \utils\ClassLoader('../src');

/**
 * Description of ImportExportWorkflowTest
 *
 * @author zozlak
 */
class ImportExportWorkflowTest extends \PHPUnit_Framework_TestCase {
	static private $connSettings = 'pgsql: dbname=tokeneditor';
	static private $PDO;
	static private $validInPlace = <<<RES
<?xml version="1.0" standalone="no"?>
<TEI xmlns:tei="http://www.tei-c.org/ns/1.0"><teiHeader><fileDesc><titleStmt><title>testtext</title></titleStmt><publicationStmt><p/></publicationStmt><sourceDesc/></fileDesc></teiHeader><text><body><tei:w id="w1" lemma="aaa">Hello<type>bbb</type></tei:w><tei:w id="w2" lemma="ccc">World<type>ddd</type></tei:w><tei:w id="w3" lemma="eee">!<type>fff</type></tei:w></body></text></TEI>
RES;
	static private $validFull = <<<RES
<?xml version="1.0" standalone="no"?>
<TEI xmlns:tei="http://www.tei-c.org/ns/1.0"><teiHeader><fileDesc><titleStmt><title>testtext</title></titleStmt><publicationStmt><p/></publicationStmt><sourceDesc/></fileDesc></teiHeader><text><body><tei:w id="w1" lemma="Hello">Hello<type>NE<fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>./type</string></f><f name="value"><string>bbb</string></f></fs></type><fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>@lemma</string></f><f name="value"><string>aaa</string></f></fs></tei:w><tei:w id="w2" lemma="World">World<type>NN<fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>./type</string></f><f name="value"><string>ddd</string></f></fs></type><fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>@lemma</string></f><f name="value"><string>ccc</string></f></fs></tei:w><tei:w id="w3" lemma="!">!<type>$.<fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>./type</string></f><f name="value"><string>fff</string></f></fs></type><fs type="tokeneditor"><f name="user"><string>test</string></f><f name="date"><string>2015-11-20</string></f><f name="property_xpath"><string>@lemma</string></f><f name="value"><string>eee</string></f></fs></tei:w></body></text></TEI>
RES;

	static public function setUpBeforeClass() {
		self::$PDO = new \PDO(self::$connSettings);
		self::$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		self::$PDO->beginTransaction();
		self::$PDO->query("TRUNCATE documents CASCADE");
		self::$PDO->query("TRUNCATE users CASCADE");
		self::$PDO->query("INSERT INTO users VALUES ('test')");
	}
	
	public static function tearDownAfterClass(){
		self::$PDO->rollback();
		unlink('tmp.xml');
	}
	
	protected function setUp(){
	}
	
	protected function insertValues($docId){
		$query = self::$PDO->prepare("INSERT INTO documents_users VALUES (?, 'test')");
		$query->execute(array($docId));
		$query = self::$PDO->prepare("
			INSERT INTO values (document_id, property_xpath, token_id, user_id, value, date) 
			SELECT document_id, property_xpath, token_id, 'test', ?, now() FROM orig_values WHERE document_id = ? AND property_xpath = ? AND token_id = ?
		");
		$query->execute(array('aaa', $docId, '@lemma', 1));
		$query->execute(array('bbb', $docId, './type', 1));
		$query->execute(array('ccc', $docId, '@lemma', 2));
		$query->execute(array('ddd', $docId, './type', 2));
		$query->execute(array('eee', $docId, '@lemma', 3));
		$query->execute(array('fff', $docId, './type', 3));
	}


	public function testDefaultInPlace(){
		$doc = new Document(self::$PDO);
		$doc->loadFile('../../sample_data/testtext.xml', '../../sample_data/testtext-schema.xml', 'test');
		$doc->save();
		$docId = $doc->getId();
		
		$this->insertValues($docId);
		
		$doc = new Document(self::$PDO);
		$doc->loadDb($docId);
		$doc->export(true, 'tmp.xml');
		$this->assertEquals(trim(self::$validInPlace), trim(file_get_contents('tmp.xml')));
	}
	
	public function testDefaultFull(){
		$doc = new Document(self::$PDO);
		$doc->loadFile('../../sample_data/testtext.xml', '../../sample_data/testtext-schema.xml', 'test');
		$doc->save();
		$docId = $doc->getId();
		
		$this->insertValues($docId);
		
		$doc = new Document(self::$PDO);
		$doc->loadDb($docId);
		$result = trim($doc->export());
		$date = date('Y-m-d');
		$result = preg_replace('/<string>' . $date . '[0-9 :.]+/', '<string>' . $date, $result);
		$this->assertEquals(trim(self::$validFull), $result);
	}

	public function testXMLReader(){
		$doc = new Document(self::$PDO);
		$doc->loadFile('../../sample_data/testtext.xml', '../../sample_data/testtext-schema.xml', 'test', Document::XML_READER);
		$doc->save();
		$docId = $doc->getId();
		
		$this->insertValues($docId);
		
		$doc = new Document(self::$PDO);
		$doc->loadDb($docId);
		$this->assertEquals(trim(self::$validInPlace), trim($doc->export(true)));
	}

	public function testPDO(){
		$doc = new Document(self::$PDO);
		$doc->loadFile('../../sample_data/testtext.xml', '../../sample_data/testtext-schema.xml', 'test', Document::PDO);
		$doc->save();
		$docId = $doc->getId();
		
		$this->insertValues($docId);
		
		$doc = new Document(self::$PDO);
		$doc->loadDb($docId);
		$this->assertEquals(trim(self::$validInPlace), trim($doc->export(true)));
	}
	
	public function testDOMDocument(){
		$doc = new Document(self::$PDO);
		$doc->loadFile('../../sample_data/testtext.xml', '../../sample_data/testtext-schema.xml', 'test', Document::DOM_DOCUMENT);
		$doc->save();
		$docId = $doc->getId();
		
		$this->insertValues($docId);
		
		$doc = new Document(self::$PDO);
		$doc->loadDb($docId);
		$this->assertEquals(trim(self::$validInPlace), trim($doc->export(true)));
	}	
}
