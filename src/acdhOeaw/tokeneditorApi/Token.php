<?php

/**
 * The MIT License
 *
 * Copyright 2016 Austrian Centre for Digital Humanities.
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

use PDO;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorModel\TokenCollection;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\util\DbHandle;

/**
 * Description of Token
 *
 * @author zozlak
 */
class Token extends BaseHttpEndpoint {

    protected $documentId;
    protected $tokenId;

    public function put(DataFormatter $f, HeadersFormatter $h) {
        $pdo = DbHandle::getHandle();

        $propName = $this->filterInput('property_name');
        $value    = $this->filterInput('value');
        $property = $this->propName2propXPath($this->filterInput('name'));

        $query = $pdo->prepare("
            INSERT INTO values (document_id, property_xpath, token_id, user_id, value) 
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (document_id, property_xpath, token_id, user_id)
            DO UPDATE SET value = EXCLUDED.value
        ");
        $param = [$this->documentId, $property, $this->tokenId, $this->userId, $value];
        $query->execute($param);

        $f->data([
            'documentId'   => $this->documentId,
            'tokenId'      => $this->tokenId,
            'propertyName' => $propName,
            'value'        => $value
        ]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h) {
        $pdo = DbHandle::getHandle();

        $pageSize    = $this->filterInput('_pageSize');
        $offset      = $this->filterInput('_offset');
        $tokenId     = $this->filterInput('tokenId');
        $tokenFilter = $this->filterInput('token');
        $propxpath   = $this->propName2propXPath($this->filterInput('propertyName'));

        $tokenArray = new TokenCollection($pdo, $this->documentId, $this->userId);
        if ($tokenId) {
            $tokenArray->setTokenIdFilter($tokenId);
        }
        if ($tokenFilter) {
            $tokenArray->setTokenValueFilter($tokenFilter);
        }

        $propQuery = $pdo->prepare('SELECT name FROM properties WHERE document_id = ?');
        $propQuery->execute([$this->documentId]);
        while ($prop      = $propQuery->fetch(PDO::FETCH_COLUMN)) {
            $value = (string) $this->filterInput(str_replace(' ', '_', $prop));
            if ($value !== '') {
                $tokenArray->addFilter($prop, $value);
            }
        }

        if ($this->filterInput('tokensOnly')) {
            $res = $tokenArray->getTokensOnly($pageSize ? $pageSize : 1000, $offset ? $offset : 0);
        } else if ($this->filterInput('stats')) {
            $res = $tokenArray->getStats($propxpath ? $propxpath : '@state');
        } else {
            $res = $tokenArray->getData($pageSize ? $pageSize : 1000, $offset ? $offset : 0);
        }
        $f->raw($res, 'application/json');
    }

    private function propName2propXPath($propName) {
        $pdo   = DbHandle::getHandle();
        $query = $pdo->prepare("
            SELECT property_xpath 
            FROM properties 
            WHERE document_id = ? AND name = ?
        ");
        $query->execute([$this->documentId, $propName]);
        return $query->fetch(PDO::FETCH_COLUMN);
    }

}
