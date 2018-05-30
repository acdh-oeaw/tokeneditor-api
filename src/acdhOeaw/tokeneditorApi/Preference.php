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
use RuntimeException;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\util\DbHandle;

/**
 * Description of Preference
 *
 * @author zozlak
 */
class Preference extends BaseHttpEndpoint {

    protected $documentId;
    protected $preferenceId;

    public function get(DataFormatter $f, HeadersFormatter $h) {
        $pdo    = DbHandle::getHandle();
        $query  = '
			SELECT value
			FROM 
				documents_users_preferences
			WHERE document_id = ? AND user_id = ? AND key = ?
        ';
        $query  = $pdo->prepare($query);
        $query->execute([$this->documentId, $this->userId, $this->preferenceId]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            throw new RuntimeException('There is no such document and key', 400);
        }
        $f->data($result->value);
    }

    public function delete(DataFormatter $f, HeadersFormatter $h) {
        $pdo   = DbHandle::getHandle();
        $query = '
            DELETE FROM documents_users_preferences 
			WHERE document_id = ? AND user_id = ? AND key = ?
        ';
        $query = $pdo->prepare($query);
        $query->execute([$this->documentId, $this->userId, $this->preferenceId]);
        if ($query->rowCount() !== 1) {
            throw new RuntimeException('There is no such document and key', 400);
        }
        $f->data([
            'document_id' => $this->documentId,
            'preference'  => $this->preferenceId
        ]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h) {
        $pdo    = DbHandle::getHandle();
        $query  = '
			SELECT key, value
			FROM documents_users_preferences
			WHERE document_id = ? AND user_id = ?
        ';
        $query  = $pdo->prepare($query);
        $query->execute([$this->documentId, $this->userId]);
        $result = [];
        while ($i      = $query->fetch(PDO::FETCH_OBJ)) {
            $result[$i->key] = $i->value;
        }
        $f->data($result);
    }

    public function put(DataFormatter $f, HeadersFormatter $h) {
        $value = $this->filterInput('value');
        $pdo   = DbHandle::getHandle();
        $query = '
			UPDATE documents_users_preferences
			SET value = ?
			WHERE document_id = ? AND user_id = ? AND key = ?
        ';
        $query = $pdo->prepare($query);
        $query->execute([$value, $this->documentId, $this->userId, $this->preferenceId]);
        if ($query->rowCount() !== 1) {
            throw new RuntimeException('There is no such document and key', 400);
        }
        $f->data([
            'document_id' => $this->documentId,
            'preference'  => $this->preferenceId,
            'value'       => $value
        ]);
    }

    public function postCollection(DataFormatter $f, HeadersFormatter $h) {
        $key   = $this->filterInput('preference');
        $value = $this->filterInput('value');
        $pdo   = DbHandle::getHandle();

        $query = '
			SELECT count(*)
			FROM documents_users_preferences 
			WHERE document_id = ? AND user_id = ? AND key = ?
        ';
        $query = $pdo->prepare($query);
        $query->execute([$this->documentId, $this->userId, $key]);
        if ($query->fetchColumn() != 0) {
            throw new RuntimeException('Preference already defined', 400);
        }

        $query = '
			INSERT INTO documents_users_preferences (document_id, user_id, key, value)
			VALUES (?, ?, ?, ?)
        ';
        $query = $pdo->prepare($query);
        $query->execute([$this->documentId, $this->userId, $key, $value]);

        $f->data([
            'document_id' => $this->documentId,
            'preference'  => $key,
            'value'       => $value
        ]);
    }

}
