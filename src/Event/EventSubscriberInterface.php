<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Event;

/**
 * Interface EventSubscriberInterface
 */
interface EventSubscriberInterface
{
    /**
     * @param array $data
     *
     * @return void
     */
    public function onEvent(array $data);

    /**
     * @param array $data
     *
     * @return void
     *
     * @deprecated
     */
    public function onError(array $data);
}