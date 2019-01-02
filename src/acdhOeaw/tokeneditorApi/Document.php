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
use XMLWriter;
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

        $format   = preg_match('/Csv/', get_class($f)) ? 'text/csv' : 'text/xml';
        $ext      = substr($format, -3);
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
        $doc->delete($this->getConfig('storageDir'));

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
        if ($this->userId === $this->getConfig('demoUser')) {
            throw new ForbiddenException('Demo user can not upload new documents');
        }

        $dir  = $file = '';
        try {
            if (!empty($this->filterInput('tokens')) && !empty($this->filterInput('schema'))) {
                $this->json2xml();
            }
            
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

    private function getProperties(mDocument $doc): array {
        $props = [];
        foreach ($doc->getSchema() as $p) {
            $props[$p->getName()] = Property::encodeProperty($p);
        }
        return $props;
    }

    private function json2xml(): void {
        $dataPath   = tempnam($this->getConfig('tmpDir'), '');
        $schemaPath = tempnam($this->getConfig('tmpDir'), '');

        $data = $this->filterInput('tokens');
        if (is_string($data)) {
            $data = parse_json($data);
        }
        $schema = $this->filterInput('schema');
        if (is_string($schema)) {
            $schema = parse_json($schema);
        }

        $propMap = [];

        $writer = new XMLWriter();
        $writer->openUri($schemaPath);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('schema');
        $writer->startElement('tokenXPath');
        $writer->text('//t');
        $writer->endElement();
        $writer->startElement('properties');
        foreach ($schema as $p) {
            $tag                       = 't' . (count($propMap) + 1);
            $propMap[$p->propertyName] = $tag;

            $writer->startElement('property');
            $writer->startElement('propertyName');
            $writer->text($p->propertyName);
            $writer->endElement();
            $writer->startElement('propertyXPath');
            $writer->text('./' . $tag);
            $writer->endElement();
            $writer->startElement('propertyType');
            $writer->text($p->propertyType);
            $writer->endElement();
            if (isset($p->readOnly)) {
                $writer->startElement('readOnly');
                $writer->endElement();
            }
            if (isset($p->optional)) {
                $writer->startElement('optional');
                $writer->endElement();
            }
            if (isset($p->values)) {
                $writer->startElement('propertyValues');
                foreach ($p->values as $v) {
                    $writer->startElement('value');
                    $writer->text($v);
                    $writer->endElement();
                }
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endDocument();
        $writer->flush();

        $writer = new XMLWriter();
        $writer->openUri($dataPath);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('root');
        foreach ($data as $t) {
            $writer->startElement('t');
            foreach ($t as $k => $v) {
                if (isset($propMap[$k])) {
                    $writer->startElement($propMap[$k]);
                    $writer->text($v);
                    $writer->endElement();
                }
            }
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();

        $_FILES['document'] = ['tmp_name' => $dataPath];
        $_FILES['schema']   = ['tmp_name' => $schemaPath];
    }

}
