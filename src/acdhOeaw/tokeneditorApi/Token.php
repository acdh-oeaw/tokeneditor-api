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

use BadMethodCallException;
use PDO;
use PDOException;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorModel\TokenCollection;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\rest\ForbiddenException;
use zozlak\util\DbHandle;

/**
 * Description of Token
 *
 * @author zozlak
 */
class Token extends BaseHttpEndpoint {

    protected int $documentId;
    protected int $tokenId;

    public function put(DataFormatter $f, HeadersFormatter $h): void {
        if (!$this->userMngr->isEditor($this->userId)) {
            throw new ForbiddenException('Document editor rights required');
        }

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
        try {
            $query->execute($param);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'values_document_id_fkey') !== false) {
                throw new BadMethodCallException('This token does not have such a property', 400);
            } else {
                throw $e;
            }
        }

        $f->data([
            'documentId'   => $this->documentId,
            'tokenId'      => $this->tokenId,
            'propertyName' => $propName,
            'value'        => $value
        ]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h): void {
        $pdo = DbHandle::getHandle();

        $pageSize  = $this->filterInput('_pageSize');
        $offset    = $this->filterInput('_offset');
        $tokenId   = $this->filterInput('tokenId');
        $order     = $this->filterInput('_order');

        $tokenArray = new TokenCollection($pdo, $this->documentId, $this->userId);
        $tokenArray->setSorting(is_array($order) ? $order : [$order]);
        if ($tokenId) {
            $tokenArray->setTokenIdFilter($tokenId);
        }

        $propQuery = $pdo->prepare('SELECT name FROM properties WHERE document_id = ?');
        $propQuery->execute([$this->documentId]);
        while ($prop      = $propQuery->fetch(PDO::FETCH_COLUMN)) {
            $value = $this->filterInput(str_replace(' ', '_', $prop));
            if ($value !== null) {
                $tokenArray->addFilter($prop, (string) $value);
            }
        }

        if ($this->filterInput('_tokensOnly')) {
            $res = $tokenArray->getTokensOnly($pageSize ? (int) $pageSize : 1000, $offset ? (int) $offset : 0);
        } else if ($this->filterInput('_stats')) {
            $res = $tokenArray->getStats($this->filterInput('_stats'));
        } else {
            $res = $tokenArray->getData($pageSize ? (int) $pageSize : 1000, $offset ? (int) $offset : 0);
        }

        if (preg_match('/Csv/', get_class($f))) {
            $res = json_decode($res);
            $f->data($res->data);
        } else {
            $f->raw($res, 'application/json');
        }
    }

    private function propName2propXPath(string $propName): string {
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
