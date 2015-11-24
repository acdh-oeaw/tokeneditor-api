<?php

/*
 * Copyright (C) 2015 ACDH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace import;

/**
 * Description of Datafile
 *
 * @author zozlak
 */
class Document implements \IteratorAggregate {
	const DOM_DOCUMENT = '\import\tokenIterator\DOMDocument';
	const XML_READER = '\import\tokenIterator\XMLReader';
	const PDO = '\import\tokenIterator\PDO';

	private $datafile;
	private $name;
	private $schema;
	private $PDO;
	private $tokenIteratorClassName;
	private $tokenIterator;

	private $documentId;
	private $tokenId = 0;
	
	/**
	 * 
	 * @param type $path
	 * @param \import\Schema $schema
	 * @throws \RuntimeException
	 */
	public function __construct(\PDO $PDO) {
		$this->PDO = $PDO;
		$this->schema = new Schema($this->PDO);
	}
	
	public function loadFile($filePath, $schemaPath, $name, $iteratorClass = null){
		if(!is_file($filePath)){
			throw new \RuntimeException($filePath . ' is not a valid file');
		}
		$this->datafile = new \utils\readContent\ReadContentFile($filePath);
		$this->name = $name;
		$this->schema->loadFile($schemaPath);
		$this->chooseTokenIterator();
		
		if($iteratorClass === null){
			$this->chooseTokenIterator();
		}else{
			if(!in_array($iteratorClass, array(self::DOM_DOCUMENT, self::XML_READER, self::PDO))){
				throw new \InvalidArgumentException('tokenIteratorClass should be one of import\Datafile::DOM_DOCUMENT, import\Datafile::XML_READER or import\Datafile::PDO');
			}
			$this->tokenIteratorClassName = $iteratorClass;
		}
	}
	
	public function loadDb($documentId){
		$this->documentId = $documentId;
		$this->schema->loadDb($this->documentId);
		
		$query = $this->PDO->prepare("SELECT name FROM documents WHERE document_id = ?");
		$query->execute(array($this->documentId));
		$this->name = $query->fetch(\PDO::FETCH_COLUMN);

		$query = $this->PDO->prepare("SELECT xml FROM documents WHERE document_id = ?");
		$this->datafile = new \utils\readContent\ReadContentDb($query, array($this->documentId));
		
		$this->tokenIteratorClassName = self::DOM_DOCUMENT;
	}
	
	/**
	 * 
	 * @return integer
	 */
	public function getId(){
		return $this->documentId;
	}
	
	/**
	 * 
	 * @return Schema
	 */
	public function getSchema(){
		return $this->schema;
	}
	
	/**
	 * 
	 * @return type
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * 
	 * @return PDO
	 */
	public function getPDO(){
		return $this->PDO;
	}
	
	/**
	 * 
	 * @return integer
	 */
	public function generateTokenId(){
		$this->tokenId++;
		return $this->tokenId;
	}

	/**
	 * 
	 * @param int $limit
	 * @param \utils\ProgressBar $progressBar
	 * @return int number of proccessed tokens
	 */
	public function save($limit = 0, $progressBar = null){
		$this->documentId = $this->PDO->
			query("SELECT nextval('document_id_seq')")->
			fetchColumn();
		
		$query = $this->PDO->prepare("INSERT INTO documents (document_id, token_xpath, name, xml) VALUES (?, ?, ?, ?)");
		$query->execute(array($this->documentId, $this->schema->getTokenXPath(), $this->name, preg_replace('/^[^<]+/', '', $this->datafile->read())));
		unset($query); // free memory
				
		$this->schema->save($this->documentId);
		
		$nn = 0;
		foreach($this as $n => $token){
			$token->save();
			if($progressBar){
				$progressBar->next();
			}
			if($n > $limit && $limit > 0){
				break;
			}
			$nn = $n + 1;
		}
		return $nn;
	}
	
	/**
	 * 
	 * @param string $path path to the file where document will be xported
	 * @param boolean $replace If true, changes will be made in-place 
	 *   (taking the most current value provided by usesrs as the right one). 
	 *   If false, review results will be provided as TEI <fs> elements
	 * @param type $progressBar
	 */
	public function export($replace = false, $path = null, $progressBar = null){
		if($replace){
			foreach($this as $token){
				$this->tokenIterator->replaceCurrentToken($token->update());
				if($progressBar){
					$progressBar->next();
				}
			}
		}else{
			foreach($this as $token){
				$this->tokenIterator->replaceCurrentToken($token->enrich());
				if($progressBar){
					$progressBar->next();
				}
			}
		}
		return $this->tokenIterator->export($path);
	}

	public function getIterator() {
		$this->tokenIterator = new $this->tokenIteratorClassName($this->datafile, $this);
		return $this->tokenIterator;
	}

	private function chooseTokenIterator() {
		try{
			new tokenIterator\XMLReader($this->datafile, $this);
			$this->tokenIteratorClassName = self::XML_READER;
		} catch (\RuntimeException $ex) {
			$this->tokenIteratorClassName = self::DOM_DOCUMENT;
		}
	}
}
