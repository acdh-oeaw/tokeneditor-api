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
	 */
	public function save($limit = 0, $progressBar = null){
		$this->documentId = $this->PDO->
			query("SELECT nextval('document_id_seq')")->
			fetchColumn();
		
		$query = $this->PDO->prepare("INSERT INTO documents (document_id, token_xpath, name, xml) VALUES (?, ?, ?, ?)");
		$query->execute(array($this->documentId, $this->schema->getTokenXPath(), $this->name, $this->datafile->read()));
		unset($query); // free memory
				
		$this->schema->save($this->documentId);
		
		foreach($this as $n => $token){
			$token->save();
			if($progressBar){
				$progressBar->next();
			}
			if($n > $limit && $limit > 0){
				break;
			}
		}
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
