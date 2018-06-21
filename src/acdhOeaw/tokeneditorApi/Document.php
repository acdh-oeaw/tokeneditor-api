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
use stdClass;
use Throwable;
use ZipArchive;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorModel\Document as mDocument;
use acdhOeaw\tokeneditorModel\User;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\rest\HttpController;
use zozlak\util\DbHandle;
use zozlak\rest\ForbiddenException;

/**
 * Description of Document
 *
 * @author zozlak
 */
class Document extends BaseHttpEndpoint {

    protected $documentId;

    public function __construct(stdClass $path, HttpController $controller) {
        parent::__construct($path, $controller);

        $tmpDir = $this->getConfig('tmpDir');
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0700, true);
        }

        $storageDir = $this->getConfig('storageDir');
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0700, true);
        }
    }

    public function get(DataFormatter $f, HeadersFormatter $h) {
        $doc = new mDocument(DbHandle::getHandle());
        $doc->loadDb($this->documentId);

        $fileName = $this->getConfig('tmpDir') . '/' . time() . rand() . '.xml';
        try {
            $doc->export((bool) $this->filterInput('inPlace'), $fileName);
            $f->file($fileName, 'text/xml', $doc->getName() . '.xml');
        } catch (Throwable $ex) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }

    public function delete(DataFormatter $f, HeadersFormatter $h) {
        if (!$this->userMngr->isOwner($this->userId)) {
            throw new ForbiddenException('Not a document owner');
        }

        $pdo   = DbHandle::getHandle();
        $param = [$this->documentId];
        $query = $pdo->prepare("DELETE FROM values WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM orig_values WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM tokens WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM properties WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM dict_values WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM documents_users WHERE document_id = ?");
        $query->execute($param);
        $query = $pdo->prepare("DELETE FROM documents WHERE document_id = ?");
        $query->execute($param);

        unlink($this->getConfig('storageDir') . '/' . $this->documentId . '.xml'); //TODO do it without referencing global variables

        $f->data(['documentId' => $this->documentId]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h) {
        $pdo = DbHandle::getHandle();

        $query = $pdo->prepare('
			SELECT document_id AS "documentId", name, count(*) AS "tokenCount"
			FROM 
				documents 
				JOIN documents_users USING (document_id) 
				JOIN tokens using (document_id)
			WHERE user_id = ? AND role <> \'none\'
			GROUP BY 1, 2
			ORDER BY 2
		');
        $query->execute([$this->userId]);
        $f->initCollection();
        while ($i     = $query->fetch(PDO::FETCH_OBJ)) {
            $i->properties = $this->getProperties($i->documentId);
            $f->append($i);
        }
        $f->closeCollection();
    }

    public function postCollection(DataFormatter $f, HeadersFormatter $h) {
        $dir  = $file = '';
        try {
            if (!isset($_FILES['document']) || !isset($_FILES['schema']) || !is_file($_FILES['document']['tmp_name']) || !is_file($_FILES['schema']['tmp_name'])) {
                throw new RuntimeException('document or schema not uploaded correctly');
            }
            $zip = new ZipArchive();
            if ($zip->open($_FILES['document']['tmp_name']) === true) {
                $name = $zip->getNameIndex(0);
                $dir  = $this->getConfig('tmpDir') . '/' . time() . rand();
                mkdir($dir);
                $zip->extractTo($dir, $name);
                $zip->close();
                $file = $dir . '/' . $name;

                $_FILES['document']['tmp_name'] = $file;
            }

            $pdo = DbHandle::getHandle();
            $doc = new mDocument($pdo);
            $doc->loadFile(
                $_FILES['document']['tmp_name'], $_FILES['schema']['tmp_name'], $this->filterInput('name')
            );
            $n   = $doc->save($this->getConfig('storageDir'));

            $this->userMngr = new User($pdo, $doc->getId());
            $this->userMngr->setRole($this->userId, User::ROLE_OWNER);

            if ($n > 0) {
                $pdo->commit();
                if (1 === $pdo->query("SELECT count(*) FROM documents")->fetch(PDO::FETCH_COLUMN)) {
                    $pdo->query("VACUUM ANALYZE");
                }
                $f->data([
                    'documentId'  => $doc->getId(),
                    'name'        => filter_input(INPUT_POST, 'name'),
                    'properties'  => $this->getProperties($doc->getId()),
                    'tokensCount' => $n
                ]);
            } else {
                $pdo->rollBack();
                throw new RuntimeException('no tokens found - maybe your schema is wrong', 400);
            }
        } finally {
            if ($file !== '') {
                unlink($file);
            }
            if ($dir !== '') {
                rmdir($dir);
            }
        }
    }

    private function getProperties($documentId) {
        $pdo = DbHandle::getHandle();

        $propQuery = $pdo->prepare('
			SELECT 
				property_xpath AS "propertyXPath", 
				name, 
				type_id AS "typeId",
				ord,
				read_only AS "readOnly",
				json_agg(value ORDER BY value) AS values
			FROM 
				properties
				LEFT JOIN dict_values USING (document_id, property_xpath)
			WHERE document_id = ?
			GROUP BY document_id, 1, 2, 3, 4
			ORDER BY ord	
		');

        $propQuery->execute([$documentId]);
        $properties = [];
        while ($prop       = $propQuery->fetch(PDO::FETCH_OBJ)) {
            $prop->values            = json_decode($prop->values);
            $properties[$prop->name] = $prop;
        }
        return $properties;
    }

}
