<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Event;

class Events
{
    /**
     * Message is received from client in raw format.
     *
     * @Event("yswery\DNS\Event\MessageEvent")
     */
    public const MESSAGE = 'dns.message';

    /**
     * Query is parsed to DNS Message class.
     *
     * @Event("yswery\DNS\Event\QueryReceiveEvent")
     */
    public const QUERY_RECEIVE = 'dns.query_receive';

    /**
     * Query is resolved and sent to the client.
     *
     * @Event("yswery\DNS\Event\QueryResponseEvent")
     */
    public const QUERY_RESPONSE = 'dns.query_response';

    /**
     * Exception is thrown when processing and responding to query.
     *
     * @Event("yswery\DNS\Event\ServerExceptionEvent")
     */
    public const SERVER_EXCEPTION = 'dns.server_exception';

    /**
     * Server is started and listening for queries.
     *
     * @Event("yswery\DNS\Event\ServerStartEvent")
     */
    public const SERVER_START = 'dns.server_start';

    /**
     * Exception is thrown when there is any error starting the server
     *
     * @Event("yswery\DNS\Event\ServerExceptionEvent")
     */
    public const SERVER_START_FAIL = 'dns.server_start_fail';
}
