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
use zozlak\rest\HttpController;
use zozlak\util\ClassLoader;
use zozlak\util\Config;
use zozlak\util\DbHandle;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
header('Cache-Control: no-cache');

require_once 'vendor/autoload.php';
new ClassLoader();

set_error_handler('\zozlak\rest\HttpController::errorHandler');

try {
    $config = new Config('config.ini');

    DbHandle::setHandle($config->get('db'));

    $controller = new HttpController('acdhOeaw\\tokeneditorApi', $config->get('apiBase'));
    $controller->setConfig($config);
    $controller->handleRequest();

    DbHandle::commit();
} catch (Throwable $e) {
    HttpController::reportError($e, $config->get('debug'));
}