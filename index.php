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
use zozlak\util\Config;
use zozlak\util\DbHandle;
use zozlak\auth\AuthController;
use zozlak\auth\AuthControllerStatic;
use zozlak\auth\usersDb\PdoDb;
use zozlak\auth\authMethod\GoogleToken;
use zozlak\auth\authMethod\TrustedHeader;
use zozlak\auth\authMethod\HttpBasic;
use zozlak\auth\authMethod\Guest;
use zozlak\auth\authMethod\Token;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
header('Cache-Control: no-cache');

require_once 'vendor/autoload.php';

set_error_handler('\zozlak\rest\HttpController::errorHandler');

$config = null;
try {
    $config = new Config('config.ini');

    DbHandle::setHandle($config->get('db'));

    $usersDb = new PdoDb($config->get('db'), 'users', 'user_id', 'data');
    $usersDb->putUser($config->get('demoUser'), HttpBasic::pswdData($config->get('demoPswd')));
    AuthControllerStatic::init($usersDb);
    //AuthControllerStatic::addMethod(new Token(filter_input(INPUT_COOKIE, $config->get('authTokenVar')) ?? '', $config->get('authTokenTime')));
    AuthControllerStatic::addMethod(new HttpBasic($config->get('authBasicRealm')), AuthController::ADVERTISE_ONCE);
    //AuthControllerStatic::addMethod(new GoogleToken(filter_input(INPUT_COOKIE, $config->get('googleTokenVar')) ?? ''));
    //AuthControllerStatic::addMethod(new TrustedHeader($config->get('shibUserHeader')));
    if ($config->get('guestUser')) {
        AuthControllerStatic::addMethod(new Guest($config->get('guestUser')));
    }
    // For unknown reasons the cluster setup doesn't keep the HTTP_AUTHORIZATION
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = 'basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
    }
    AuthControllerStatic::authenticate(false);
    try {
        header('TokeneditorUser: ' . AuthControllerStatic::getUserName());
    } catch (\Exception $e) {
        AuthControllerStatic::advertise();
    }

    
    $controller = new HttpController('acdhOeaw\\tokeneditorApi', $config->get('apiBase'));
    $controller->setConfig($config);

    $possible = [
        'application/json' => '\\zozlak\\rest\\JsonFormatter',
        'text/xml'         => '\\acdhOeaw\\tokeneditorApi\\util\\XmlFormatter', 
        'application/xml'  => '\\acdhOeaw\\tokeneditorApi\\util\\XmlFormatter', 
        'text/csv'         => '\\zozlak\\rest\\CsvFormatter', 
    ];
    $accepted = $controller->getAccept(array_keys($possible));
    $forced = filter_input(INPUT_GET, '_format');
    if (in_array($forced, array_keys($possible))) {
        $accepted = [$forced];
    }
    if (count($accepted) == 0) {
        $accepted = ['text/xml'];
    }
    $controller->setFormatters(['default' => $possible[$accepted[0]]]);
 
    $controller->handleRequest();

    DbHandle::commit();
} catch (Throwable $e) {
    HttpController::reportError($e, $config?->get('debug'));
}
