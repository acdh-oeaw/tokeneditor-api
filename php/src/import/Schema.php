<?php

/*
 * Copyright (C) 2015 ACDH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		
		if(isset($dom->namespaces) && isset($dom->namespaces->namespace)){
			foreach($dom->namespaces->namespace as $i){
				$this->namespaces[(string)$i->prefix[0]] = (string)$i->uri[0];
			}
		}
	}
	
	public function loadDb($documentId){
		$this->documentId = $documentId;
		
		$schema = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><schema>';

		$schema .= '<namespaces>';
		$query = $this->PDO->prepare("SELECT prefix, ns FROM documents_namespaces WHERE document_id = ?");
		$query->execute(array($this->documentId));
		while($ns = $query->fetch(\PDO::FETCH_OBJ)){
			$schema .= '<namespace><prefix>' . $ns->prefix . '</prefix><uri>' . $ns->ns . '</uri></namespace>';
		}
		$schema .= '</namespaces>';
		
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
