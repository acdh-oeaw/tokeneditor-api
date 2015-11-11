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

const DOM_DOCUMENT = '\import\tokenIterator\DOMDocument';
const XML_READER = '\import\tokenIterator\XMLReader';
const PDO = '\import\tokenIterator\PDO';

/**
 * Description of Datafile
 *
 * @author zozlak
 */
class Datafile implements \IteratorAggregate {
	private $path;
	private $schema;
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
	public function __construct($path, Schema $schema) {
		if(!is_file($path)){
			throw new \RuntimeException($path . ' is not a valid file');
		}
		$this->path   = $path;
		$this->schema = $schema;
		$this->chooseTokenIterator();
	}
	
	public function setTokenIterator($tokenIteratorClass){
		if(!in_array($tokenIteratorClass, array(DOM_DOCUMENT, XML_READER, PDO))){
			throw new \InvalidArgumentException('tokenIteratorClass should be one of import::DOM_DOCUMENT, import::XML_READER or import::PDO');
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
	public function save(\PDO $PDO){
		$this->documentId = $PDO->
			query("SELECT nextval('document_id_seq')")->
			fetchColumn();
		
		$query = $PDO->prepare("INSERT INTO documents (document_id, token_xpath) VALUES (?, ?)");
		$query->execute(array($this->documentId, $this->schema->getTokenXPath()));
				
		$this->schema->save($PDO, $this->documentId);
	}

	public function getIterator() {
		$this->tokenIterator = new $this->tokenIteratorClassName($this->path, $this->schema);
		return $this->tokenIterator;
	}

	public function chooseTokenIterator() {
		$this->tokenIteratorClassName = DOM_DOCUMENT;
	}
}
