<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Resolver;

use \Exception;
use \InvalidArgumentException;
use yswery\DNS\RecordTypeEnum;

/**
 * Class JsonStorageProvider
 */
class JsonResolver implements ResolverInterface
{
    /**
     * @var array
     */
    private $records;

    /**
     * @var int
     */
    private $ttl = 300;

    /**
     * JsonStorageProvider constructor.
     *
     * @param string $file The file path of the JSON-formatted DNS Zone file.
     *
     * @throws Exception | InvalidArgumentException
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new Exception(sprintf('The file "%s" does not exist.', $file));
        }

        if (false === $json = file_get_contents($file)) {
            throw new Exception(sprintf('Unable to open JSON file: "%s".', $file));
        }

        if (null === $records = json_decode($json, true)) {
            throw new Exception(sprintf('Unable to parse JSON file: "%s".', $file));
        }

        if (!is_int($this->ttl)) {
            throw new InvalidArgumentException('Default TTL must be an integer.');
        }

        $this->records = $records;
    }

    /**
     * @inheritdoc
     *
     * @param array $query
     *
     * @return array
     */
    public function getAnswer(array $query)
    {
        $queryName = $query[0]['qname'];
        $queryType = $query[0]['qtype'];
        $queryClass = $query[0]['qclass'];
        $domain = trim($queryName, '.');
        $type = RecordTypeEnum::getName($queryType);

        // If there is no resource record or the record does not have the type, return an empty array.
        if (!array_key_exists($domain, $this->records) || !isset($this->records[$domain][$type])) {
            return [];
        }

        $answer = [];
        $data = (array) $this->records[$domain][$type];

        foreach ($data as $rdata) {
            $answer[] = [
                'name' => $queryName,
                'class' => $queryClass,
                'ttl' => $this->ttl,
                'data' => [
                    'type' => $queryType,
                    'value' => $rdata,
                ],
            ];
        }

        return $answer;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function allowsRecursion()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @param string $domain
     *
     * @return bool
     */
    public function isAuthority($domain)
    {
        $domain = trim($domain, '.');

        return array_key_exists($domain, $this->records);
    }

    /**
     * Get the currently loaded DNS Records
     *
     * @return array
     */
    public function getDnsRecords()
    {
        return $this->records;
    }
}
