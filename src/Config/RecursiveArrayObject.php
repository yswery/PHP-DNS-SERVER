<?php

namespace yswery\DNS\Config;

use ArrayObject;

class RecursiveArrayObject extends ArrayObject
{
    function getArrayCopy()
    {
        $resultArray = parent::getArrayCopy();
        foreach($resultArray as $key => $val) {
            if (!is_object($val)) {
                continue;
            }
            $o = new RecursiveArrayObject($val);
            $resultArray[$key] = $o->getArrayCopy();
        }
        return $resultArray;
    }
}