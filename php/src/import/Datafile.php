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
class Datafile implements \Iterator {
	private $path;
	private $schema;

	private $documentId;
	private $tokenId = 0;

	private $xpath;
	private $tokens;
	private $pos = 0;
	
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
	}

	/**
	 * 
	 * @return string
	 */
	public function getDOMXPath(){
		return $this->xpath;
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
	
	/**
	 * 
	 * @return type
	 */
	public function current() {
		return $this->tokens[$this->pos];
	}

	/**
	 * 
	 * @return integer
	 */
	public function key() {
		return $this->pos;
	}

	/**
	 * 
	 */
	public function next() {
		$this->pos++;
	}

	/**
	 * 
	 */
	public function rewind() {
		$dom = new \DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->Load($this->path);
		$this->xpath = new \DOMXPath($dom);
		foreach($this->schema->getNs() as $ns){
			$this->xpath->registerNamespace($ns->prefix, $ns->namespace);
		}
		$this->tokens = $this->xpath->query($this->schema->getTokenXPath());
		$this->pos = 0;
	}

	/**
	 * 
	 * @return boolean
	 */
	public function valid() {
		return $this->pos < $this->tokens->length;
	}
}
