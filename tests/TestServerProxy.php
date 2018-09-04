<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Tests;

use yswery\DNS\Server;

class TestServerProxy extends Server
{
    public function ds_error($code, $error, $file, $line) {
        throw new \ErrorException($error, $code, 1, $file, $line);
    }

    public function invokePrivateMethod($methodName, ...$params)
    {
        $method = new \ReflectionMethod('\\yswery\\DNS\\Server', $methodName);
        $method->setAccessible(true);

        return $method->invoke($this, ...$params);
    }
}