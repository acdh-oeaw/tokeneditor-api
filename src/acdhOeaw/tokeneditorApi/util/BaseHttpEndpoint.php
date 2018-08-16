<?php

/**
 * The MIT License
 *
 * Copyright 2018 zozlak.
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

namespace acdhOeaw\tokeneditorApi\util;

use stdClass;
use acdhOeaw\tokeneditorModel\User;
use zozlak\auth\AuthControllerStatic;
use zozlak\rest\HttpController;
use zozlak\rest\HttpEndpoint;
use zozlak\util\DbHandle;

/**
 * Extracts user name from request headers.
 * 
 * In the future full authorization should be implemented.
 * @author zozlak
 */
class BaseHttpEndpoint extends HttpEndpoint {

    protected $documentId;
    protected $userId;

    /**
     *
     * @var \acdhOeaw\tokeneditorModel\User
     */
    protected $userMngr;

    public function __construct(stdClass $path, HttpController $controller) {
        parent::__construct($path, $controller);

        $this->userId = AuthControllerStatic::getUserName();

        if ($this->documentId) {
            $query = DbHandle::getHandle()->prepare("SELECT count(*) FROM documents WHERE document_id = ?");
            $query->execute([$this->documentId]);
            if (1 !== $query->fetchColumn()) {
                throw new RuntimeException('No such document', 404);
            }

            $this->userMngr = new User(DbHandle::getHandle(), $this->documentId);
            if (!$this->userMngr->isEditor($this->userId)) {
                throw new ForbiddenException('Not a document editor');
            }
        }
    }

}
