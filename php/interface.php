<?php
/* 
 * The MIT License
 *
 * Copyright 2015 zozlak.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Quick and dirty HTTP API allowing documents import/export
 * 
 * - POST request treated as a new document import
 *   It has to contain:
 *   - "document" file
 *   - "schema" file
 *   - "name" string
 *   Result is always a JSON
 * - GET request is treated as an export
 *   It has to contain:
 *   - "document_id"
 *   It may contain:
 *   - "inPlace"
 *   Result is XML file (or JSON in case of error)
 */
require_once 'src/utils/ClassLoader.php';
new utils\ClassLoader();
require_once 'config.inc.php';

$PDO = new \PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

// dirty hack for local testing
if(filter_input(INPUT_SERVER, $CONFIG['userid']) === null){
	$_SERVER[$CONFIG['userid']] = 'mzoltak@oeaw.ac.at';
}

if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET'){
	// EXPORT
	try{
		$doc = new import\Document($PDO);
		$doc->loadDb(filter_input(INPUT_GET, 'document_id', FILTER_VALIDATE_INT));
		
		header('Content-type: text/xml');
		echo $doc->export((bool)filter_input(INPUT_GET, 'inPlace'));
	} catch (Exception $ex) {
		exit(json_encode(array(
			'status' => 'ERROR',
			'message' => $ex->getMessage()
		)));
	}
}else if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST'){
	//IMPORT
	try{
		if(!isset($_FILES['document']) || !isset($_FILES['schema'])){
			throw new RuntimeException('document or schema not uploaded');
		}
		
		$PDO->beginTransaction();
		$doc = new import\Document($PDO);
		$doc->loadFile(
			$_FILES['document']['tmp_name'], 
			$_FILES['schema']['tmp_name'],
			filter_input(INPUT_POST, 'name')
		);
		$n = $doc->save();
		
		$query = $PDO->prepare("SELECT count(*) FROM users WHERE user_id = ?");
		$query->execute(array(filter_input(INPUT_SERVER, $CONFIG['userid'])));
		if($query->fetch(PDO::FETCH_COLUMN) == 0){
			$query = $PDO->prepare("INSERT INTO users (user_id) VALUES (?)");
			$query->execute(array(filter_input(INPUT_SERVER, $CONFIG['userid'])));
		}
		
		$query = $PDO->prepare("INSERT INTO documents_users (document_id, user_id) VALUES (?, ?)");
		$query->execute(array(
			$doc->getId(), 
			filter_input(INPUT_SERVER, $CONFIG['userid'])
		));
		header('Content-type: application/json');
		if($n > 0){
			$PDO->commit();
			echo json_encode(array(
				'status' => 'OK',
				'document_id' => $doc->getId(),
				'name' => filter_input(INPUT_POST, 'name'),
				'tokensCount' => $n
			));
		}else{
			$PDO->rollBack();
			echo json_encode(array(
				'status' => 'ERROR',
				'message' => 'no tokens found - maybe your schema is wrong'
			));
		}
	} catch (Exception $ex) {
		exit(json_encode(array(
			'status' => 'ERROR',
			'message' => $ex->getMessage()
		)));
	}
}else{
	exit(json_encode(array(
		'status' => 'ERROR',
		'message' => 'wrong request'
	)));
}
