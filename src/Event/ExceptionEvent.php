<?php

namespace yswery\DNS\Event;

class ExceptionEvent extends Event
{
    private $exception;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}
