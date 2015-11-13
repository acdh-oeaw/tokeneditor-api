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
	private $PDO;
	private $documentId;
	private $tokenXPath;
	private $namespaces = array();
	private $properties = array();
	
	/**
	 * 
	 * @param type $path
	 * @throws \RuntimeException
	 * @throws \LengthException
	 */
	public function __construct(\PDO $PDO){
		$this->PDO = $PDO;
	}
	
	public function loadFile($path) {
		if(!is_file($path)){
			throw new \RuntimeException($path . ' is not a valid file');
		}
		$this->loadXML(file_get_contents($path));
	}
	
	public function loadXML($xml){
		$dom = new \SimpleXMLElement($xml);
		
		if(!isset($dom->tokenXPath) || count($dom->tokenXPath) != 1){
			throw new \LengthException('exactly one tokenXPath has to be provided');
		}
		$this->tokenXPath = $dom->tokenXPath;
		
		if(
			!isset($dom->properties) 
			|| !isset($dom->properties->property) 
			|| count($dom->properties->property) == 0
		){
			throw new \LengthException('no token properties defined');
		}
		foreach($dom->properties->property as $i){
			$this->properties[] = new Property($i);
		}
		
		$this->namespaces = $dom->getDocNamespaces();
	}
	
	public function loadDb($documentId){
		$this->documentId = $documentId;
		
		$schema = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><schema';
		$query = $this->PDO->prepare("SELECT prefix, ns FROM documents_namespaces WHERE document_id = ?");
		$query->execute(array($this->documentId));
		while($ns = $query->fetch(\PDO::FETCH_OBJ)){
			$schema .= ' xmlns:' . $ns->prefix . '="' . $ns->ns . '"';
		}
		$schema .= '>';
		
		$query = $this->PDO->prepare("SELECT token_xpath FROM documents WHERE document_id = ?");
		$query->execute(array($this->documentId));
		$schema .= '<tokenXPath>' . $query->fetch(\PDO::FETCH_COLUMN) . '</tokenXPath>';
		
		$schema .= '<properties>';
		$query = $this->PDO->prepare("SELECT property_xpath, type_id, name FROM properties WHERE document_id = ?");
		$valuesQuery = $this->PDO->prepare("SELECT value FROM dict_values WHERE (document_id, property_xpath) = (?, ?)");
		$query->execute(array($this->documentId));
		while($prop = $query->fetch(\PDO::FETCH_OBJ)){
			$schema .= '<property>';
			$schema .= '<propertyName>' . $prop->name . '</propertyName>';
            $schema .= '<propertyXPath>' . $prop->property_xpath . '</propertyXPath>';
            $schema .= '<propertyType>' . $prop->type_id . '</propertyType>';
			
			$valuesQuery->execute(array($this->documentId, $prop->property_xpath));
			$values = $valuesQuery->fetchAll(\PDO::FETCH_COLUMN);
			if(count($values) > 0){
				$schema .= '<values>';
				foreach($values as $v){
					$schema .= '<value>' . $v . '</value>';
				}
				$schema .= '</values>';
			}
			$schema .= '</property>';
		}
		$schema .= '</properties>';

		$schema .= '</schema>';
		
		$this->loadXML($schema);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getTokenXPath(){
		return (string)$this->tokenXPath;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getNs(){
		return $this->tokenXPath->getDocNamespaces();
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
	public function save($documentId){
		$query = $this->PDO->prepare("INSERT INTO documents_namespaces (document_id, prefix, ns) VALUES (?, ?, ?)");
		foreach($this->getNs() as $prefix => $ns){
			$query->execute(array($documentId, $prefix, $ns));
		}
		
		foreach($this->properties as $prop){
			$prop->save($this->PDO, $documentId);
		}
	}
}
