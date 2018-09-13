<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

class JsonResolver implements ResolverInterface
{
    /**
     * @var array
     */
    private $records;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var bool
     */
    private $recursionAvailable;

    /**
     * JsonResolver constructor.
     *
     * @param string $filename the path of the JSON-formatted DNS Zone file
     * @param int    $ttl      the default TTL to be used for all Resource Records omitting a TTL
     *
     * @throws \Exception | \InvalidArgumentException
     */
    public function __construct($filename, $ttl = 300)
    {
        if (!file_exists($filename)) {
            throw new \Exception(sprintf('The file "%s" does not exist.', $filename));
        }

        if (false === $dns_json = file_get_contents($filename)) {
            throw new \Exception(sprintf('Unable to open JSON file: "%s".', $filename));
        }

        if (null === $dns_records = json_decode($dns_json, true)) {
            throw new \Exception(sprintf('Unable to parse JSON file: "%s".', $filename));
        }

        if (!is_int($ttl)) {
            throw new \InvalidArgumentException('Default TTL must be an integer.');
        }

        $this->ttl = $ttl;
        $this->records = $dns_records;
        $this->recursionAvailable = false;
    }

    /**
     * @param ResourceRecord[] $question
     *
     * @return array
     */
    public function getAnswer(array $question): array
    {
        $q_name = ($question[0])->getName();
        $q_type = ($question[0])->getType();
        $q_class = ($question[0])->getClass();

        $domain = trim($q_name, '.');
        $type = RecordTypeEnum::getName($q_type);

        // If there is no resource record or the record does not have the type, return an empty array.
        if (!array_key_exists($domain, $this->records) || !isset($this->records[$domain][$type])) {
            return [];
        }

        $answer = [];
        $data = (array) $this->records[$domain][$type];

        foreach ($data as $rdata) {
            $answer[] = (new ResourceRecord())
                ->setName($q_name)
                ->setType($q_type)
                ->setClass($q_class)
                ->setTtl($this->ttl)
                ->setRdata($rdata);
        }

        return $answer;
    }

    /**
     * Get the currently loaded DNS Records.
     *
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Getter method for $recursion_available property.
     *
     * @return bool
     */
    public function allowsRecursion(): bool
    {
        return $this->recursionAvailable;
    }

    /*
    * Check if the resolver knows about a domain
    *
    * @param  string  $domain the domain to check for
    * @return boolean         true if the resolver holds info about $domain
    */
    public function isAuthority($domain): bool
    {
        $domain = trim($domain, '.');

        return array_key_exists($domain, $this->records);
    }
}
