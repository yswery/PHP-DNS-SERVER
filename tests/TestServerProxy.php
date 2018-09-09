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
use yswery\DNS\ResolverInterface;

class TestServerProxy extends Server
{
    public function __construct(ResolverInterface $ds_storage, $bind_ip = '0.0.0.0', $bind_port = 53, $default_ttl = 300, $max_packet_len = 512)
    {
        parent::__construct($ds_storage, $bind_ip, $bind_port, $default_ttl, $max_packet_len);

        //Prevent application from dying while testing
        restore_error_handler();
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
