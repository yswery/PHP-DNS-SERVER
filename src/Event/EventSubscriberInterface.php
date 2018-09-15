<?php

namespace yswery\DNS\Event;

interface EventSubscriberInterface
{
    public function getSubscribedEvents(): array;
}
