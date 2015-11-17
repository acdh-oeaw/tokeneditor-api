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
 * Token iterator class developed using stream XML parser (XMLReader).
 * It is memory efficient (requires constant memory no matter XML size)
 * and very fast but (at least at the moment) can handle token XPaths
 * specyfying single node name only.
 * This is because XMLReader does not provide any way to execute XPaths on it
 * and I was to lazy to implement more compound XPath handling. Maybe it will
 * be extended in the future.
 *
 * @author zozlak
 */
class XMLReader extends TokenIterator {
	private $reader;
	
	/**
	 * 
	 * @param type $path
	 * @param \import\Schema $schema
	 * @param \PDO $PDO
	 * @throws \RuntimeException
	 */
	public function __construct($path, \import\Document $document){
		parent::__construct($path, $document);

		$this->reader = new \XMLReader();
		$tokenXPath = $this->document->getSchema()->getTokenXPath();
		if(!preg_match('|^//[a-zA-Z0-9_:.]+$|', $tokenXPath)){
			throw new \RuntimeException('Token XPath is too complicated for XMLReader');
		}
		$this->tokenXPath = mb_substr($tokenXPath, 2);
	}
	
	/**
	 * 
	 */
	public function next() {
		$this->pos++;
		$this->token = false;
		do{
			$res = $this->reader->read();
		}while(
			($this->reader->nodeType != \XMLReader::ELEMENT || $this->reader->name != $this->tokenXPath) 
			&& $res
		);
		if($res){
			$tokenDom = new \DOMDocument();
			$tokenDom->loadXml($this->reader->readOuterXml());
			$this->token = new \import\Token($tokenDom->documentElement, $this->document);
		}
	}

	/**
	 * 
	 */
	public function rewind() {
		$this->reader->open($this->path);
		$this->pos = -1;
		$this->next();
	}
	
	public function export($path) {
		throw new \BadMethodCallException('export() is not not implemented for this TokenIterator class');
	}
}
