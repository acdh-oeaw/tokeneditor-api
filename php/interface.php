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
 * - POST request are treated as a new document import
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
	$docId = filter_input(INPUT_GET, 'document_id');
	$tokenId = filter_input(INPUT_GET, 'token_id');
	if($docId != '' && $tokenId !== null){
		// GET TOKENS LIST
		include 'TokenArray.php';
		$tokenArray = new TokenArray($PDO);
		$userId = filter_input(INPUT_SERVER, $CONFIG['userid']);
		$pagesize = filter_input(INPUT_GET, '_pagesize');
		$offset =  filter_input(INPUT_GET, '_offset');
		$tokenId = filter_input(INPUT_GET, 'token_id');
		$tokenF = filter_input(INPUT_GET, 'token');
		if($tokenId){
			$tokenArray->setTokenIdFilter($tokenId);
		}
		if($tokenF){
			$tokenArray->setTokenValueFilter($tokenF);
		}
		$propQuery = $PDO->prepare('SELECT name FROM properties WHERE document_id = ?');
		$propQuery->execute(array($docId));
		while($prop = $propQuery->fetch(PDO::FETCH_COLUMN)){
			$value = (string)filter_input(INPUT_GET, $prop);
			if($value !== ''){
				$tokenArray->addFilter($prop, $value);
			}
		}
		header('Content-Type: application/json');
		echo $tokenArray->getTokensOnly($docId, $userId, $pagesize ? $pagesize : 1000, $offset ? $offset : 0);
	}elseif($docId != ''){
		// EXPORT
		try{
			$doc = new import\Document($PDO);
			$doc->loadDb($docId);

			header('Content-type: text/xml');
			echo $doc->export((bool)filter_input(INPUT_GET, 'inPlace'), \import\DOM_DOCUMENT);
		} catch (Exception $ex) {
			exit(json_encode(array(
				'status' => 'ERROR',
				'message' => $ex->getMessage()
			)));
		}
	}else{
		// DOCUMENTS LIST
		$propQuery = $PDO->prepare('
			SELECT 
				property_xpath AS "propertyXPath", 
				name, 
				type_id AS "typeId",
				ord,
				read_only AS "readOnly",
				json_agg(value ORDER BY value) AS values
			FROM 
				properties
				LEFT JOIN dict_values USING (document_id, property_xpath)
			WHERE document_id = ?
			GROUP BY document_id, 1, 2, 3, 4
			ORDER BY ord	
		');
		$query = $PDO->prepare('
			SELECT document_id AS "documentId", name, count(*) AS "tokenCount"
			FROM 
				documents 
				JOIN documents_users USING (document_id) 
				JOIN tokens using (document_id)
			WHERE user_id = ?
			GROUP BY 1, 2
			ORDER BY 2
		');
		$query->execute(array(filter_input(INPUT_SERVER, $CONFIG['userid'])));
		$docs = $query->fetchAll(\PDO::FETCH_OBJ);
		foreach($docs as &$i){
			$propQuery->execute(array($i->documentId));
			$i->properties = array();
			while($prop = $propQuery->fetch(\PDO::FETCH_OBJ)){
				$prop->values = json_decode($prop->values);
				$i->properties[$prop->name] = $prop;
			}
		}
		unset($i);
		exit(json_encode(array(
			'status' => 'OK',
			'data' => $docs
		)));
	}
}else if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST'){
	$docId = filter_input(INPUT_GET, 'document_id');
	$propName = filter_input(INPUT_GET, 'property_name');
	if($docId != '' && $propName != ''){
		try{
			// BULK UPDATE
			$query = $PDO->prepare("SELECT property_xpath FROM properties WHERE document_id = ? AND name = ?");
			$query->execute(array($docId, $propName));
			$propXPath = $query->fetch(PDO::FETCH_COLUMN);
			$tokenIds = explode(',', filter_input(INPUT_POST, 'tokenid'));
			if($tokenIds[0] == '' || $propXPath == ''){
				throw new RuntimeException('wrong property name or empty token id list');
			}else{
				$value  = filter_input(INPUT_POST, 'value');
				$userId = filter_input(INPUT_SERVER, $CONFIG['userid']);
				$tokenIdsStr = mb_substr(str_repeat(', ?', count($tokenIds)), 2);

				$PDO->beginTransaction();

				// update existing
				$queryStr = "
					UPDATE values 
					SET value = ?, date = now()
					WHERE 
						document_id = ? 
						AND property_xpath = ? 
						AND user_id = ? 
						AND value != ?
						AND token_id IN (" . $tokenIdsStr . ")";
				$query = $PDO->prepare($queryStr);
				$query->execute(array_merge(array($value, $docId, $propXPath, $userId, $value), $tokenIds));

				// add missing
				$queryStr = "
					INSERT INTO values (document_id, property_xpath, token_id, user_id, value, date)
					SELECT document_id, property_xpath, token_id, ?::text, ?::text, now()
					FROM
						documents
						JOIN properties USING (document_id)
						JOIN tokens USING (document_id)
						LEFT JOIN values USING (document_id, token_id, property_xpath)
					WHERE
						document_id = ?
						AND property_xpath = ?
						AND token_id IN (" . $tokenIdsStr . ")
						AND user_id IS NULL";
				$query = $PDO->prepare($queryStr);
				$query->execute(array_merge(array($userId, $value, $docId, $propXPath), $tokenIds));

				$PDO->commit();
				echo json_encode(array(
					'status' => 'OK'
				));
			}
		}catch(Exception $ex){
			exit(json_encode(array(
				'status' => 'ERROR',
				'message' => $ex->getMessage()
			)));
		}
	}else{
		$dir = $file = '';
		// IMPORT
		try{
			if(!isset($_FILES['document']) || !isset($_FILES['schema'])){
				throw new RuntimeException('document or schema not uploaded');
			}
			$zip = new ZipArchive();
			if($zip->open($_FILES['document']['tmp_name']) === true){
				$name = $zip->getNameIndex(0);
				$dir = 'tmp/' . time() . rand();
				mkdir($dir);
				$zip->extractTo($dir, $name);
				$zip->close();
				$file = $dir . '/' . $name;
				$_FILES['document']['tmp_name'] = $file;
			}

			$PDO->beginTransaction();
			$doc = new import\Document($PDO);
			$doc->loadFile(
				$_FILES['document']['tmp_name'], 
				$_FILES['schema']['tmp_name'],
				filter_input(INPUT_POST, 'name')
			);
			$n = $doc->save($CONFIG['storageDir']);

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
				if(1 == $PDO->query("SELECT count(*) FROM documents")->fetch(PDO::FETCH_COLUMN)){
					$PDO->query("VACUUM ANALYZE");
				}
				echo json_encode(array(
					'status' => 'OK',
					'documentId' => $doc->getId(),
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
			header('Content-type: application/json');
			exit(json_encode(array(
				'status' => 'ERROR',
				'message' => $ex->getMessage()
			)));
		} finally {
			if($file !== ''){
				unlink($file);
			}
			if($dir !== ''){
				rmdir($dir);
			}
		}
	}
}else{
	exit(json_encode(array(
		'status' => 'ERROR',
		'message' => 'wrong request'
	)));
}
