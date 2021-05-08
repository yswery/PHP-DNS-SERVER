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

use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\ResolverInterface;
use yswery\DNS\ResourceRecord;

class DummyResolver implements ResolverInterface
{
    public function isAuthority($domain): bool
    {
        return true;
    }

    public function allowsRecursion(): bool
    {
        return false;
    }

    /**
     * @param ResourceRecord[] $queries
     *
     * @return array
     */
    public function getAnswer(array $queries, ?string $client = null): array
    {
        $q = $queries[0];

        return [(new ResourceRecord())
            ->setName($q->getName())
            ->setClass($q->getClass())
            ->setTtl(300)
            ->setType(RecordTypeEnum::TYPE_OPT)
            ->setRdata('Some data'), ];
    }
}
