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
use RuntimeException;
use stdClass;
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
        $inPlace = $this->filterInput('inPlace') ?? false;
        
        $csvPriority = array_search('text/csv', $this->getAccept());
        $xmlPriority = min(array_search('application/xml', $this->getAccept()), array_search('text/xml', $this->getAccept()));
        $format = $xmlPriority <= $csvPriority ? 'application/xml' : 'text/csv';
        if ($this->filterInput('format'))  {
            if (!in_array($this->filterInput('format'), ['application/xml', 'text/xml', 'text/csv'])) {
                throw new BadMethodCallException('Format parameter has to be application/xml, text/xml or text/csv', 400);
            }
            $format = $this->filterInput('format');
        }
        $ext = substr($format, -3);
        $fileName = $this->getConfig('tmpDir') . '/' . time() . rand() . '.' . $ext;
        
        $doc = new mDocument(DbHandle::getHandle());
        $doc->loadDb($this->documentId);
        try {
            switch ($format) {
                case 'text/csv':
                    $doc->exportCsv($fileName);
                    break;
                default:
                    $doc->export($inPlace, $fileName);
            }
            $f->file($fileName, $format, $doc->getName() . '.' . $ext);
        } finally {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }

    public function delete(DataFormatter $f, HeadersFormatter $h) {
        if (!$this->userMngr->isOwner($this->userId)) {
            throw new ForbiddenException('Not a document owner');
        }

        $doc = new mDocument(DbHandle::getHandle());
        $doc->loadDb($this->documentId);
        $doc->delete($this->getConfig('saveDir'));

        $f->data(['documentId' => $this->documentId]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h) {
        $pdo = DbHandle::getHandle();
        $d   = new mDocument($pdo);

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
            $d->loadDb($i->documentId);
            $i->properties = $this->getProperties($d);
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
                    'properties'  => $this->getProperties($doc),
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

    private function getProperties(mDocument $doc) {
        $props = [];
        foreach ($doc->getSchema() as $p) {
            $props[$p->getName()] = Property::encodeProperty($p);
        }
        return $props;
    }

}
