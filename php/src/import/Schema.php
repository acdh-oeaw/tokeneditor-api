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
 * Description of Schema
 *
 * @author zozlak
 */
class Schema implements \IteratorAggregate {
	private $dom;
	private $tokenXPath;
	private $namespaces = array();
	private $properties = array();
	
	/**
	 * 
	 * @param type $path
	 * @throws \RuntimeException
	 * @throws \LengthException
	 */
	public function __construct($path) {
		if(!is_file($path)){
			throw new \RuntimeException($path . ' is not a valid file');
		}
		$this->dom = new \SimpleXMLElement(file_get_contents($path));
		
		foreach($this->dom->attributes() as $attr => $val){
			if(preg_match('/^xmlns:/', $attr)){
				$this->namespaces[] = new Ns(preg_replace('/^xmlns:/', '', $attr), $val);
			}
		}		
		
		if(!isset($this->dom->tokenXPath) || count($this->dom->tokenXPath) != 1){
			throw new \LengthException('exactly one tokenXPath has to be provided');
		}
		$this->tokenXPath = (string)$this->dom->tokenXPath;
		
		if(
			!isset($this->dom->properties) 
			|| !isset($this->dom->properties->property) 
			|| count($this->dom->properties->property) == 0
		){
			throw new \LengthException('no token properties defined');
		}
		foreach($this->dom->properties->property as $i){
			$this->properties[] = new Property($i);
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getTokenXPath(){
		return $this->tokenXPath;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getNs(){
		return $this->namespaces;
	}
	
	/**
	 * 
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->properties);
	}
	
	/**
	 * 
	 * @param \PDO $PDO
	 * @param type $datafileId
	 */
	public function save(\PDO $PDO, $datafileId){
		foreach($this->properties as $prop){
			$prop->save($PDO, $datafileId);
		}
	}
}
