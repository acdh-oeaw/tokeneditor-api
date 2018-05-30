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
use zozlak\rest\HttpController;
use zozlak\rest\HttpEndpoint;

/**
 * Extracts user name from request headers.
 * 
 * In the future full authorization should be implemented.
 * @author zozlak
 */
class BaseHttpEndpoint extends HttpEndpoint {

    protected $userId;

    public function __construct(stdClass $path, HttpController $controller) {
        parent::__construct($path, $controller);
        
        $tmp = filter_input(\INPUT_SERVER, $this->getConfig('userId'));
        if(preg_match('/^Digest username/', $tmp)){
            $tmp = preg_replace('/.*username="([^"]+)".*/', '\1', $tmp);
        }
        $this->userId = $tmp ?? $this->getConfig('guestUser');
    }

}
