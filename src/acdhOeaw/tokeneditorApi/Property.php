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

use PDO;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorModel\Document as mDocument;
use acdhOeaw\tokeneditorModel\Property as mProperty;
use zozlak\rest\DataFormatter;
use zozlak\rest\ForbiddenException;
use zozlak\rest\HeadersFormatter;
use zozlak\util\DbHandle;

/**
 * Description of Property
 *
 * @author zozlak
 */
class Property extends BaseHttpEndpoint {

    static public function encodeProperty(mProperty $p) {
        return [
            'propertyXPath' => $p->getXPath(),
            'name'          => $p->getName(),
            'typeId'        => $p->getType(),
            'ord'           => $p->getOrd(),
            'readOnly'      => $p->getReadOnly(),
            'optional'      => $p->getOptional(),
            'values'        => $p->getValues()
        ];
    }

    protected $documentId;
    protected $propertyId;

    public function getCollection(DataFormatter $f, HeadersFormatter $h) {
        $d     = new mDocument(DbHandle::getHandle());
        $d->loadDb($this->documentId);
        $props = [];
        foreach ($d->getSchema() as $p) {
            $props[$p->getName()] = Property::encodeProperty($p);
        }
        $f->data($props);
    }

    public function get(DataFormatter $f, HeadersFormatter $h) {
        $d = new mDocument(DbHandle::getHandle());
        $d->loadDb($this->documentId);
        foreach ($d->getSchema() as $p) {
            if ($p->getName() === $this->propertyId) {
                $f->data(self::encodeProperty($p));
                return;
            }
        }
        throw new \RuntimeException('no such property', 404);
    }

    public function patch(DataFormatter $f, HeadersFormatter $h) {
        if (!$this->userMngr->isOwner($this->userId)) {
            throw new ForbiddenException('Not a document owner');
        }
        
        $pdo = DbHandle::getHandle();

        $query = $pdo->prepare("SELECT property_xpath FROM properties WHERE document_id = ? AND name = ?");
        $query->execute([$this->documentId, $this->propertyId]);
        $xpath = $query->fetchColumn();
        if ($xpath === false) {
            throw new \RuntimeException('no such property', 404);
        }

        $name     = $this->filterInput('name');
        $type     = $this->filterInput('typeId');
        $ord      = $this->filterInput('ord');
        $readOnly = $this->filterInput('readOnly');
        $optional = $this->filterInput('optional');
        $values   = $this->filterInput('values');

        if ($values !== null && !is_array($values)) {
            throw new \BadMethodCallException('values parameter must be an array', 400);
        }

        $set   = [];
        $param = [];
        if ($name) {
            $set[]   = 'name = ?';
            $param[] = $name;
        }
        if ($type) {
            $types = $pdo->query("SELECT type_id FROM property_types")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($type, $types)) {
                throw new \BadMethodCallException('unknown property type', 400);
            }
            $set[]   = 'type_id = ?';
            $param[] = $type;
        }
        if ($ord !== null) {
            $query = $pdo->prepare("SELECT name FROM properties WHERE document_id = ? AND ord = ?");
            $query->execute([$this->documentId, $ord]);
            $tmp = $query->fetchColumn();
            if ($tmp !== false && $tmp !== $this->propertyId) {
                throw new \BadMethodCallException('order value already used by another property', 400);
            }
            $set[]   = 'ord = ?';
            $param[] = $ord;
        }
        if ($readOnly !== null) {
            $set[]   = 'read_only = ?';
            $param[] = (int) ((bool) $readOnly);
        }
        if ($optional !== null) {
            $set[]   = 'optional = ?';
            $param[] = (int) ((bool) $optional);
        }

        if (count($set) > 0) {
            $query = "UPDATE properties SET " . implode(', ', $set) . "WHERE document_id = ? AND property_xpath = ?";
            $param = array_merge($param, [$this->documentId, $xpath]);
            $query = $pdo->prepare($query);
            $query->execute($param);
        }

        if ($values !== null) {
            $query = $pdo->prepare("DELETE FROM dict_values WHERE document_id = ? AND property_xpath = ?");
            $query->execute([$this->documentId, $xpath]);
            $query = $pdo->prepare("INSERT INTO dict_values (document_id, property_xpath, value) VALUES (?, ?, ?)");
            foreach ($values as $i) {
                $query->execute([$this->documentId, $xpath, $i]);
            }
        }

        $this->propertyId = $name ?? $this->propertyId;
        $this->get($f, $h);
    }

}
