<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS;

use \Exception;
use \InvalidArgumentException;

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
     * @var boolean
     */
    private $allowsRecursion;

    /**
     * JsonStorageProvider constructor.
     *
     * @param string $file The filepath of the JSON-formatted DNS Zone file.
     * @param int $default_ttl The TTL to be used for all Resource Records omitting a TTL.
     * @throws Exception | InvalidArgumentException
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new Exception(sprintf('The file "%s" does not exist.', $file));
        }

        if (false === $dns_json = file_get_contents($file)) {
            throw new Exception(sprintf('Unable to open JSON file: "%s".', $file));
        }

        if (null === $dns_records = json_decode($dns_json, true)) {
            throw new Exception(sprintf('Unable to parse JSON file: "%s".', $file));
        }

        if (!is_int($this->ttl)) {
            throw new InvalidArgumentException('Default TTL must be an integer.');
        }

        $this->records = $dns_records;
        $this->allowsRecursion = false;
    }

    /**
     * @param $query
     * @return array
     */
    public function getAnswer($query)
    {
        $q_name = $query[0]['qname'];
        $q_type = $query[0]['qtype'];
        $q_class = $query[0]['qclass'];
        $domain = trim($q_name, '.');
        $type = RecordTypeEnum::get_name($q_type);

        // If there is no resource record or the record does not have the type, return an empty array.
        if (!array_key_exists($domain, $this->records) || !isset($this->records[$domain][$type])) {
            return [];
        }

        $answer = [];
        $data = (array)$this->records[$domain][$type];

        foreach ($data as $rdata) {
            $answer[] = [
                'name' => $q_name,
                'class' => $q_class,
                'ttl' => $this->ttl,
                'data' => [
                    'type' => $q_type,
                    'value' => $rdata,
                ],
            ];
        }

        return $answer;
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


    /**
     * Getter method for $recursion_available property
     *
     * @return boolean
     */
    public function allowsRecursion()
    {
        return $this->allowsRecursion;
    }

    /*
    * Check if the resolver knows about a domain
    *
    * @param  string  $domain the domain to check for
    * @return boolean         true if the resolver holds info about $domain
    */
    public function isAuthority($domain)
    {
        $domain = trim($domain, '.');

        return array_key_exists($domain, $this->records);
    }
}
