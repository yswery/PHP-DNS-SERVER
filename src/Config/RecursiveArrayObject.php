<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Config;

use ArrayObject;

class RecursiveArrayObject extends ArrayObject
{
    public function getArrayCopy()
    {
        $resultArray = parent::getArrayCopy();
        foreach ($resultArray as $key => $val) {
            if (!is_object($val)) {
                continue;
            }
            $o = new RecursiveArrayObject($val);
            $resultArray[$key] = $o->getArrayCopy();
        }

        return $resultArray;
    }
}
