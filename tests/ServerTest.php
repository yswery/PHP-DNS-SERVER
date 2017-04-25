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

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestServerProxy
     */
    private $server;

    public function setUp()
    {
        $this->server = new TestServerProxy;
    }

    public function testDs_encode_label()
    {
        $input_1 = 'www.example.com.';
        $expectation_1 = chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $input_2 = '.';
        $expectation_2 = "\0";

        $input_3 = 'tld.';
        $expectation_3 = chr(3) . 'tld' . "\0";

        $this->assertEquals($expectation_1, $this->server->ds_encode_label($input_1));
        $this->assertEquals($expectation_2, $this->server->ds_encode_label($input_2));
        $this->assertEquals($expectation_3, $this->server->ds_encode_label($input_3));
    }
}