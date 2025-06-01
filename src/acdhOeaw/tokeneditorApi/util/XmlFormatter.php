<?php

/**
 * The MIT License
 *
 * Copyright 2019 Austrian Centre for Digital Humanities.
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

use BadMethodCallException;
use zozlak\rest\DataFormatter;

/**
 * A dummy class allowing to distinguish the desired output format on the
 * HttpEndpoint class level
 * @author zozlak
 */
class XmlFormatter extends DataFormatter {

    protected function sendBuffer(): int {
        return 0;
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(): array {
        return ['Content-Type' => 'text/xml'];
    }

    public function data(mixed $d): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }

    public function initCollection(string $key = ''): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }

    public function closeCollection(): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }
    
    public function initObject(string $key = ''): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }
    
    public function closeObject(): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }

    public function append(mixed $d, string $key = ''): DataFormatter {
        throw new BadMethodCallException('Method not implemented');
    }
}
