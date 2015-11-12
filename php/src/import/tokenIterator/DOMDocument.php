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
 * Basic token iterator class using DOM parser (DOMDocument).
 * It is memory inefficient as every DOM parser but quite fast (at least as long 
 * Token class constuctor supports passing Token XML as a DOMElement object; if 
 * conversion to string was required, it would be very slow).
 *
 * @author zozlak
 */
class DOMDocument implements \Iterator{
	private $xmlFilePath;
	private $schema;
	private $tokens;
	private $pos = 0;
	
	/**
	 * 
	 * @param type $path
	 */
	public function __construct($path, \import\Schema $schema, \PDO $PDO) {
		$this->xmlFilePath = $path;
		$this->schema = $schema;
	}
	
	/**
	 * 
	 * @return string
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
		$dom->Load($this->xmlFilePath);
		$xpath = new \DOMXPath($dom);
		foreach($this->schema->getNs() as $prefix => $ns){
			$xpath->registerNamespace($prefix, $ns);
		}
		$this->tokens = $xpath->query($this->schema->getTokenXPath());
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
