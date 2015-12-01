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

namespace utils\readContent;

/**
 * Description of ReadContentDb
 *
 * @author zozlak
 */
class ReadContentDb implements ReadContentInterface {
	static public $tmpDir = '/tmp/';
	
	private $query;
	private $param;
	private $fetchStyle;
	private $path;
	
	/**
	 * 
	 * @param \PDOStatement $query
	 * @param array $param
	 */
	public function __construct(\PDOStatement $query, array $param, $fetchStyle = \PDO::FETCH_COLUMN) {
		$this->query = $query;
		$this->param = $param;
		$this->fetchStyle = $fetchStyle;
	}
	
	public function __destruct() {
		if($this->path !== null){
			unlink($this->path);
		}
	}


	public function read(){
		$this->query->execute($this->param);
		return $this->query->fetch($this->fetchStyle);
	}

	/**
	 * The only way to do it is to gather data from database and store them
	 * in a temporary file
	 */
	public function getPath() {
		$this->path = tempnam(sys_get_temp_dir(), '');
		file_put_contents($this->path, $this->read());
		return $this->path;
	}
}
