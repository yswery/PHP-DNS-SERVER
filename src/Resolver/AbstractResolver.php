<?php

namespace yswery\DNS\Resolver;

use yswery\DNS\ResourceRecord;

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
}