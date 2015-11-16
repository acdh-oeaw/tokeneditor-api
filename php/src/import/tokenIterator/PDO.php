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

namespace import\tokenIterator;

/**
 * Token iterator class using relational database backend.
 * On Postgresql it is very fast but memory ineficient (like every DOM parser).
 * 
 *
 * @author zozlak
 */
class PDO extends TokenIterator {
	private $PDO;
	private $id;
	private $results;
	
	/**
	 * 
	 * @param type $path
	 * @param \import\Schema $schema
	 * @param \PDO $PDO
	 */
	public function __construct($path, \import\Document $document){
		parent::__construct($path, $document);
		$this->PDO = $this->document->getPDO();
		
		$this->id = $this->PDO->
			query("SELECT nextval('import_tmp_seq')")->
			fetchColumn();
		
		$query = $this->PDO->prepare("INSERT INTO import_tmp VALUES (?, ?)");
		$query->execute(array($this->id, preg_replace('/^[^<]*/', '', file_get_contents($this->path))));
	}
	
	/**
	 * 
	 */
	public function __destruct() {
		$query = $this->PDO->prepare("DELETE FROM import_tmp WHERE id = ?");
		$query->execute(array($this->id));
	}

	/**
	 * 
	 */
	public function next() {
		$this->pos++;
		$this->token = $this->results->fetch(\PDO::FETCH_COLUMN);
		if($this->token !== false){
			$tokenDom = new \DOMDocument();
			$tokenDom->loadXml($this->token);
			$this->token = new \import\Token($tokenDom->documentElement, $this->document);
		}
	}

	/**
	 * 
	 */
	public function rewind() {
		$param = array($this->document->getSchema()->getTokenXPath());
		
		$ns = array();
		foreach($this->document->getSchema()->getNs() as $prefix => $namespace){
			$ns[] = 'array[?, ?]';
			$param[] = $prefix;
			$param[] = $namespace;
		}
		$ns = implode(',', $ns);
		if($ns != ''){
			$ns = ', array[' . $ns . ']';
		}
		
		$param[] = $this->id;
		
		$this->results = $this->PDO->prepare("SELECT unnest(xpath(?, xml" . $ns . ")) FROM import_tmp WHERE id = ?");
		$this->results->execute($param);
		$this->pos = -1;
		$this->next();
	}

	public function export($path) {
		throw new \BadMethodCallException('export() is not not implemented for this TokenIterator class');
	}
}
