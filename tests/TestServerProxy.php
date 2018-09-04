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
    /**
     * Intercept the parent ds_error() method to prevent PHP exiting before PHPUnit has finished.
     *
     * @param integer $code
     * @param string $error
     * @param string $file
     * @param integer $line
     * @throws \ErrorException
     */
    public function ds_error($code, $error, $file, $line)
    {
        throw new \ErrorException($error, $code, 1, $file, $line);
    }

    /**
     * Provides access to the private methods so that they can be unit tested.
     *
     * @param string $methodName The name of the private method to be publicly called.
     * @param mixed ...$params The parameters of the private method.
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokePrivateMethod($methodName, ...$params)
    {
        $method = new \ReflectionMethod('\\yswery\\DNS\\Server', $methodName);
        $method->setAccessible(true);

        return $method->invoke($this, ...$params);
    }
}