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

use Exception;
use stdClass;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorModel\User;
use zozlak\auth\AuthControllerStatic;
use zozlak\auth\authMethod\HttpBasic;
use zozlak\auth\authMethod\Token;
use zozlak\rest\HttpController;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\rest\ForbiddenException;

/**
 * Description of Document
 *
 * @author zozlak
 */
class Editor extends BaseHttpEndpoint {

    protected int $documentId;
    protected string $editorId;

    public function __construct(stdClass $path, HttpController $controller) {
        parent::__construct($path, $controller);

        if ($this->documentId && !$this->userMngr->isOwner($this->userId)) {
            throw new ForbiddenException('Not a document owner');
        }
        if ($this->editorId) {
            $this->editorId = urldecode($this->editorId);
        }
    }

    public function get(DataFormatter $f, HeadersFormatter $h): void {
        if ($this->editorId !== 'current') {
            throw new Exception('Not Found', 404);
        } 
        $user = AuthControllerStatic::getUserName();
        $data  = AuthControllerStatic::getUserData();
        $token = Token::createToken($data, $user);
        AuthControllerStatic::putUserData($data, false);

        $shib = false;
        foreach (array_keys($_COOKIE) as $i) {
            if (substr($i, 0, 13) === '_shibsession_') {
                $shib = true;
                break;
            }
        }

        $f->data([
            'login' => $user,
            'token' => $token,
            'shibboleth' => $shib,
            'timeout' => $this->getConfig('authTokenTime')
        ]);
    }

    public function getCollection(DataFormatter $f, HeadersFormatter $h): void {
        $f->data($this->userMngr->getUsers());
    }

    public function delete(DataFormatter $f, HeadersFormatter $h): void {
        $this->userMngr->setRole($this->editorId, User::ROLE_NONE);
        $h->setStatus(204);
    }

    public function put(DataFormatter $f, HeadersFormatter $h): void {
        $role = $this->filterInput('role');
        $name = $this->filterInput('name');
        $this->userMngr->setRole($this->editorId, $role, $name);
        $h->setStatus(204);
    }

    public function patch(DataFormatter $f, HeadersFormatter $h): void {
        if ($this->editorId !== 'current') {
            throw new Exception('Not Found', 404);
        } 

        $pswd = $this->filterInput('password');
        if (strlen($pswd) < 6) {
            throw new Exception('Password has to be at least six characters long', 400);
        }
        AuthControllerStatic::putUserData(HttpBasic::pswdData($pswd));
        $h->setStatus(204);
    }
    
}
