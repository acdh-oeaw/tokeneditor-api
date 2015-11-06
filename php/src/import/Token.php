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
 * Description of Token
 *
 * @author zozlak
 */
class Token {
	private $value;
	private $properties = array();
	
	/**
	 * 
	 * @param \DOMElement $xml
	 * @param \DOMXPath $xpath
	 * @param \import\Schema $schema
	 * @throws \LengthException
	 */
	public function __construct(\DOMElement $xml, \DOMXPath $xpath, Schema $schema){
		$this->value = $xml->nodeValue;
		foreach($schema as $prop){
			try{
				$value = $xpath->query($prop->getXPath(), $xml);
				if($value->length != 1){
					throw new \LengthException('property not found or many properties found');
				}
				$value = $value->item(0);
				$this->properties[$prop->getXPath()] = isset($value->value) ? $value->value : $value->nodeValue;
			}catch (\LengthException $e){}
		}
	}
	
	/**
	 * 
	 * @param \PDO $PDO
	 * @param type $documentId
	 */
	public function save(\PDO $PDO, $documentId, $tokenId){
		$query = $PDO->prepare("INSERT INTO tokens (document_id, token_id, value) VALUES (?, ?, ?)");
		$query->execute(array($documentId, $tokenId, $this->value));
		
		$query = $PDO->prepare("INSERT INTO orig_values (document_id, token_id, property_xpath, value) VALUES (?, ?, ?, ?)");
		foreach ($this->properties as $xpath => $value){
			$query->execute(array($documentId, $tokenId, $xpath, $value));
		}
	}
}
