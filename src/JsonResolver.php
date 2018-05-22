<?php

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
     * @var boolean
     */
    private $recursionAvailable;

    /**
     * JsonResolver constructor.
     *
     * @param string $file The file path of the JSON-formatted DNS Zone file.
     * @param int    $ttl  The TTL to be used for all Resource Records omitting a TTL.
     *
     * @throws \Exception | \InvalidArgumentException
     */
    public function __construct($file, $ttl = 300)
    {
        if (!file_exists($file)) {
            throw new \Exception(sprintf('The file "%s" does not exist.', $file));
        }

        if (false === $json = file_get_contents($file)) {
            throw new \Exception(sprintf('Unable to open JSON file: "%s".', $file));
        }

        if (null === $records = json_decode($json, true)) {
            throw new \Exception(sprintf('Unable to parse JSON file: "%s".', $file));
        }

        if (!is_int($ttl)) {
            throw new \InvalidArgumentException('Default TTL must be an integer.');
        }

        $this->ttl = $ttl;
        $this->records = $records;
        $this->recursionAvailable = false;
    }

    /*
     * {@inheritdoc}
     */
    public function getAnswer(array $question)
    {
        $qName = $question[0]['qname'];
        $qType = $question[0]['qtype'];
        $qClass = $question[0]['qclass'];
        $domain = trim($qName, '.');
        $type = RecordTypeEnum::getName($qType);

        // If there is no resource record or the record does not have the type, return an empty array.
        if (!array_key_exists($domain, $this->records) || !isset($this->records[$domain][$type])) {
            return [];
        }

        $answer = [];
        $data = (array)$this->records[$domain][$type];

        foreach ($data as $rdata) {
            $answer[] = [
                'name' => $qName,
                'class' => $qClass,
                'ttl' => $this->ttl,
                'data' => [
                    'type' => $qType,
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
    public function getRecords()
    {
        return $this->records;
    }


    /*
     * {@inheritdoc}
     */
    public function allowsRecursion()
    {
        return $this->recursionAvailable;
    }

    /*
     * {@inheritdoc}
     */
    public function isAuthority($domain)
    {
        $domain = trim($domain, '.');

        return array_key_exists($domain, $this->records);
    }
}
