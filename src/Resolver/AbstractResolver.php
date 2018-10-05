<?php

namespace yswery\DNS\Resolver;

use yswery\DNS\ResourceRecord;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\UnsupportedTypeException;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var bool
     */
    protected $allowRecursion;

    /**
     * @var bool
     */
    protected $isAuthoritative;

    /**
     * @var ResourceRecord[]
     */
    protected $resourceRecords = [];

    /**
     * @param ResourceRecord[] $query
     *
     * @return array
     */
    public function getAnswer(array $query): array
    {
        $answers = [];
        foreach ($query as $q) {
            $answer = $this->resourceRecords[$q->getName()][$q->getType()][$q->getClass()] ?? null;
            if (null !== $answer) {
                $answers = array_merge($answers, $answer);
            }
        }

        return $answers;
    }

    /**
     * {@inheritdoc}
     */
    public function allowsRecursion(): bool
    {
        return $this->allowRecursion;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthority($domain): bool
    {
        return $this->isAuthoritative;
    }

    /**
     * @param ResourceRecord[] $resourceRecords
     */
    protected function addZone(array $resourceRecords): void
    {
        foreach ($resourceRecords as $resourceRecord) {
            $this->resourceRecords[$resourceRecord->getName()][$resourceRecord->getType()][$resourceRecord->getClass()][] = $resourceRecord;
        }
    }

    /**
     * Add the parent domain to names that are not fully qualified.
     *
     * AbstractResolver::handleName('www', 'example.com.') //Outputs 'www.example.com.'
     * AbstractResolver::handleName('@', 'example.com.') //Outputs 'example.com.'
     * AbstractResolver::handleName('ns1.example.com.', 'example.com.') //Outputs 'ns1.example.com.'
     *
     * @param $name
     * @param $parent
     *
     * @return string
     */
    protected function handleName(string $name, string $parent)
    {
        if ('@' === $name || '' === $name) {
            return $parent;
        }

        if ('.' !== substr($name, -1, 1)) {
            return $name.'.'.$parent;
        }

        return $name;
    }

    /**
     * @param array  $resourceRecord
     * @param int    $type
     * @param string $parent
     *
     * @return mixed
     *
     * @throws UnsupportedTypeException
     */
    protected function extractRdata(array $resourceRecord, int $type, string $parent)
    {
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                return $resourceRecord['address'];
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                return $this->handleName($resourceRecord['target'], $parent);
            case RecordTypeEnum::TYPE_SOA:
                return [
                    'mname' => $this->handleName($resourceRecord['mname'], $parent),
                    'rname' => $this->handleName($resourceRecord['rname'], $parent),
                    'serial' => $resourceRecord['serial'],
                    'refresh' => $resourceRecord['refresh'],
                    'retry' => $resourceRecord['retry'],
                    'expire' => $resourceRecord['expire'],
                    'minimum' => $resourceRecord['minimum'],
                ];
            case RecordTypeEnum::TYPE_MX:
                return [
                    'preference' => $resourceRecord['preference'],
                    'exchange' => $this->handleName($resourceRecord['exchange'], $parent),
                ];
            case RecordTypeEnum::TYPE_TXT:
                return $resourceRecord['text'];
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                return '';
            default:
                throw new UnsupportedTypeException(
                    sprintf('Resource Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }
    }
}
