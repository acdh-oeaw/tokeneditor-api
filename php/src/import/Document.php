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

	private $path;
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
	
	public function loadFile($filePath, $schemaPath, $iteratorClass = null){
		if(!is_file($filePath)){
			throw new \RuntimeException($filePath . ' is not a valid file');
		}
		$this->path   = $filePath;
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
		
		$query = $this->PDO->prepare("SELECT path FROM documents WHERE document_id = ?");
		$query->execute(array($this->documentId));
		$this->path = $query->fetch(\PDO::FETCH_COLUMN);
		
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
	 * @param \PDO $PDO
	 */
	public function save(){
		$this->documentId = $this->PDO->
			query("SELECT nextval('document_id_seq')")->
			fetchColumn();
		
		$query = $this->PDO->prepare("INSERT INTO documents (document_id, token_xpath, path) VALUES (?, ?, ?)");
		$query->execute(array($this->documentId, $this->schema->getTokenXPath(), $this->path));
				
		$this->schema->save($this->documentId);
	}
	
	/**
	 * 
	 * @param string $path path to the file where document will be xported
	 * @param boolean $replace If true, changes will be made in-place 
	 *   (taking the most current value provided by usesrs as the right one). 
	 *   If false, review results will be provided as TEI <fs> elements
	 * @param type $progressBar
	 */
	public function export($path, $replace = false, $progressBar){
		if($replace){
			foreach($this as $token){
				$token->update();
				if($progressBar){
					$progressBar->next();
				}
			}
		}else{
			foreach($this as $token){
				$token->enrich();
				if($progressBar){
					$progressBar->next();
				}
			}
		}
		$this->tokenIterator->export($path);
	}

	public function getIterator() {
		$this->tokenIterator = new $this->tokenIteratorClassName($this->path, $this);
		return $this->tokenIterator;
	}

	private function chooseTokenIterator() {
		try{
			new tokenIterator\XMLReader($this->path, $this);
			$this->tokenIteratorClassName = self::XML_READER;
		} catch (\RuntimeException $ex) {
			$this->tokenIteratorClassName = self::DOM_DOCUMENT;
		}
	}
}
