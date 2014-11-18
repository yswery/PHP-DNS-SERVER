<?php

namespace yswery\DNS;

abstract class AbstractStorageProvider {

    abstract function get_answer($question);

}
