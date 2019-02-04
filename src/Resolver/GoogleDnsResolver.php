<?php

namespace yswery\DNS\Resolver;

use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;

class GoogleDnsResolver extends AbstractResolver
{
    private const API_ENDPOINT = 'https://dns.google.com/resolve';

    private const ANSWER_FIELD_NAME = 'Answer';

    private const NAME_QUERY_PARAM = 'name';
    private const TYPE_QUERY_PARAM = 'type';
    private const TTL_FIELD_NAME = 'TTL';
    private const DATA_FIELD_NAME = 'data';

    /** @var int */
    private $defaultTtl;

    public function __construct($defaultTtl = 300)
    {
        $this->allowRecursion = true;
        $this->isAuthoritative = true;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * @param ResourceRecord[] $queries
     *
     * @return ResourceRecord[]
     */
    public function getAnswer(array $queries): array
    {
        $answers = [];
        foreach ($queries as $query) {
            $response = $this->request($query->getName(), $query->getType());
            $answers[] = $this->createAnswer($query, $response);
        }

        return array_merge(...$answers);
    }

    /**
     * @param ResourceRecord $query
     *
     * @param array|null     $response
     *
     * @return ResourceRecord[]
     */
    public function createAnswer(ResourceRecord $query, ?array $response): array
    {
        $answers = [];

        if (!is_array($response)) {
            return [$this->getEmptyAnswer($query)];
        }

        if (!isset($response[self::ANSWER_FIELD_NAME]) || empty($response[self::ANSWER_FIELD_NAME])) {
            return [$this->getEmptyAnswer($query)];
        }

        foreach ($response[self::ANSWER_FIELD_NAME] as $item) {
            $answer = $this->getEmptyAnswer($query);

            $answer->setTtl($item[self::TTL_FIELD_NAME] ?? $this->defaultTtl);

            if ($query->getType() === RecordTypeEnum::TYPE_A && isset($item[self::DATA_FIELD_NAME])) {
                $answer->setRdata($item[self::DATA_FIELD_NAME]);
            }

            if ($query->getType() === RecordTypeEnum::TYPE_AAAA && isset($item[self::DATA_FIELD_NAME])) {
                $answer->setRdata($item[self::DATA_FIELD_NAME]);
            }

            $answers[] = $answer;
        }

        return $answers;
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return array|null
     */
    private function request(string $name, string $type): ?array
    {
        $session = curl_init();

        $query = [
            self::NAME_QUERY_PARAM => $name,
            self::TYPE_QUERY_PARAM => $type,
        ];

        $url = self::API_ENDPOINT.'?'.http_build_query($query);

        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_URL, $url);

        $response = curl_exec($session);

        curl_close($session);

        $response = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $response;
    }

    /**
     * @param ResourceRecord $query
     *
     * @return ResourceRecord
     */
    private function getEmptyAnswer(ResourceRecord $query): ResourceRecord
    {
        $answer = new ResourceRecord();
        $answer->setName($query->getName());
        $answer->setType($query->getType());
        $answer->setTtl($this->defaultTtl);

        return $answer;
    }
}
