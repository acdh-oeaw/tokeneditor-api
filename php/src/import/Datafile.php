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
class Datafile implements \IteratorAggregate {
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
	public function __construct($path, Schema $schema, \PDO $PDO) {
		if(!is_file($path)){
			throw new \RuntimeException($path . ' is not a valid file');
		}
		$this->path   = $path;
		$this->schema = $schema;
		$this->PDO = $PDO;
		$this->chooseTokenIterator();
	}
	
	public function setTokenIterator($tokenIteratorClass){
		if(!in_array($tokenIteratorClass, array(self::DOM_DOCUMENT, self::XML_READER, self::PDO))){
			throw new \InvalidArgumentException('tokenIteratorClass should be one of import\Datafile::DOM_DOCUMENT, import\Datafile::XML_READER or import\Datafile::PDO');
		}
		$this->tokenIteratorClassName = $tokenIteratorClass;
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
		
		$query = $this->PDO->prepare("INSERT INTO documents (document_id, token_xpath) VALUES (?, ?)");
		$query->execute(array($this->documentId, $this->schema->getTokenXPath()));
				
		$this->schema->save($this->PDO, $this->documentId);
	}

	public function getIterator() {
		$this->tokenIterator = new $this->tokenIteratorClassName($this->path, $this->schema, $this->PDO);
		return $this->tokenIterator;
	}

	private function chooseTokenIterator() {
		try{
			new tokenIterator\XMLReader($this->path, $this->schema, $this->PDO);
			$this->tokenIteratorClassName = self::XML_READER;
		} catch (\RuntimeException $ex) {
			$this->tokenIteratorClassName = self::DOM_DOCUMENT;
		}
	}
}
