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
class DOMDocument extends TokenIterator {
	private $dom;
	private $tokens;
	
	/**
	 * 
	 * @param type $path
	 */
	public function __construct($path, \import\Document $document) {
		parent::__construct($path, $document);
	}
	
	/**
	 * 
	 */
	public function next() {
		$this->token = false;
		$this->pos++;
		if($this->pos < $this->tokens->length){
			$doc = new \DOMDocument();
			$tokenNode = $doc->importNode($this->tokens[$this->pos], true);
			$this->token = new \import\Token($tokenNode, $this->document);
		}
	}

	/**
	 * 
	 */
	public function rewind() {
		$this->dom = new \DOMDocument();
		$this->dom->preserveWhiteSpace = false;
		$this->dom->Load($this->path);
		$xpath = new \DOMXPath($this->dom);
		foreach($this->document->getSchema()->getNs() as $prefix => $ns){
			$xpath->registerNamespace($prefix, $ns);
		}
		$this->tokens = $xpath->query($this->document->getSchema()->getTokenXPath());
		$this->pos = -1;
		$this->next();
	}
	
	public function export($path){
		$this->dom->save($path);
	}
}
