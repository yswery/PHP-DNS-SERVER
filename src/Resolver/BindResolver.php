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

use Badcow\DNS\Parser\ParseException;
use Badcow\DNS\Parser\Parser;
use Badcow\DNS\ResourceRecord as BadcowRR;
use Badcow\DNS\ZoneBuilder;
use yswery\DNS\ResourceRecord;

class BindResolver extends AbstractResolver
{
    /**
     * BindResolver constructor.
     *
     * @param array $files
     *
     * @throws ParseException
     */
    public function __construct(array $files)
    {
        $this->isAuthoritative = true;
        $this->allowRecursion = false;

        $resourceRecords = [];

        foreach ($files as $file) {
            $fileContents = file_get_contents($file);
            $zone = Parser::parse('.', $fileContents);
            ZoneBuilder::fillOutZone($zone);

            foreach ($zone as $rr) {
                $resourceRecords[] = self::convertResourceRecord($rr);
            }
        }

        $this->addZone($resourceRecords);
    }

    /**
     * Converts Badcow\DNS\ResourceRecord object to yswery\DNS\ResourceRecord object.
     *
     * @return ResourceRecord
     */
    public static function convertResourceRecord(BadcowRR $badcowResourceRecord, bool $isQuestion = false): ResourceRecord
    {
        $rr = new ResourceRecord();
        $rr->setName($badcowResourceRecord->getName());
        $rr->setClass($badcowResourceRecord->getClassId());
        $rr->setRdata(new ArrayRdata($badcowResourceRecord->getRdata()));
        $rr->setTtl($badcowResourceRecord->getTtl());
        $rr->setType($badcowResourceRecord->getRdata()->getTypeCode());
        $rr->setQuestion($isQuestion);

        return $rr;
    }
}
