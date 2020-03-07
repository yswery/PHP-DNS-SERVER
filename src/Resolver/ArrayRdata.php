<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Resolver;

use ArrayAccess;
use Badcow\DNS\Rdata\RdataInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Represents Badcow\DNS\Rdata\RdataInterface as an array.
 */
class ArrayRdata implements ArrayAccess
{
    /**
     * @var RdataInterface
     */
    private $rdata;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(RdataInterface $rdata)
    {
        $this->rdata = $rdata;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function getBadcowRdata(): RdataInterface
    {
        return $this->rdata;
    }

    public function offsetExists($offset): bool
    {
        return $this->propertyAccessor->isReadable($this->rdata, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->propertyAccessor->getValue($this->rdata, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->propertyAccessor->setValue($this->rdata, $offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->propertyAccessor->setValue($this->rdata, $offset, null);
    }

    public function __toString()
    {
        return $this->rdata->toText();
    }
}
