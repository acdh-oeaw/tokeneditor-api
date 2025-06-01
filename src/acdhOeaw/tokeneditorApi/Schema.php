<?php

/*
 * The MIT License
 *
 * Copyright 2018 Austrian Centre for Digital Humanities.
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

namespace acdhOeaw\tokeneditorApi;

use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\util\DbHandle;

/**
 * Description of Schema
 *
 * @author zozlak
 */
class Schema extends BaseHttpEndpoint {
    protected int $documentId = -1;
    
    public function getCollection(DataFormatter $f, HeadersFormatter $h): void {
        $pdo   = DbHandle::getHandle();
        $schema = new \acdhOeaw\tokeneditorModel\Schema($pdo);
        $xml = $schema->loadDb($this->documentId);
        $f->raw($xml, 'text/xml', $this->documentId . '_schema.xml');
    }
}

