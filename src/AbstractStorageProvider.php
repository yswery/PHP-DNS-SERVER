<?php

namespace yswery\DNS;

abstract class AbstractStorageProvider
{
    abstract public function get_answer($question);

    abstract public function is_authority($domain);
}
