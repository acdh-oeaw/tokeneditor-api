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
	public function __construct(\utils\readContent\ReadContentInterface $xml, \import\Document $document){
		parent::__construct($xml, $document);

		$this->reader = new \XMLReader();
		$tokenXPath = $this->document->getSchema()->getTokenXPath();
		if(!preg_match('|^//[a-zA-Z0-9_:.]+$|', $tokenXPath)){
			throw new \RuntimeException('Token XPath is too complicated for XMLReader');
		}
		$this->tokenXPath = mb_substr($tokenXPath, 2);
		$nsPrefixPos = mb_strpos($this->tokenXPath, ':');
		if($nsPrefixPos !== false){
			$prefix = mb_substr($this->tokenXPath, 0, $nsPrefixPos);
			$ns = $this->document->getSchema()->getNs();
			if(isset($ns[$prefix])){
				$this->tokenXPath = $ns[$prefix] . mb_substr($this->tokenXPath, $nsPrefixPos);
			}
		}
	}
	
	/**
	 * 
	 */
	public function next() {
		$this->pos++;
		$this->token = false;
		do{
			$res = $this->reader->read();
			$name = null;
			if($this->reader->nodeType === \XMLReader::ELEMENT){
				$nsPrefixPos = mb_strpos($this->reader->name, ':');
				$name = 
					($this->reader->namespaceURI ? $this->reader->namespaceURI . ':' : '') .
					($nsPrefixPos ? mb_substr($this->reader->name, $nsPrefixPos + 1) : $this->reader->name);
			}
		}while($res && $name !== $this->tokenXPath);
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
		$this->reader->open($this->xml->getPath());
		$this->pos = -1;
		$this->next();
	}
	
	/**
	 * 
	 * @param type $path
	 * @throws \BadMethodCallException
	 */
	public function export($path) {
		throw new \BadMethodCallException('export() is not not implemented for this TokenIterator class');
	}

	/**
	 * 
	 * @param \import\Token $new
	 * @throws \BadMethodCallException
	 */
	public function replaceToken(\import\Token $new) {
		throw new \BadMethodCallException('replaceToken() is not not implemented for this TokenIterator class');
	}
}
