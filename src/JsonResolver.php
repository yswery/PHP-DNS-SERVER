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
     * @var bool
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

        foreach ($records as $domain => &$record) {
            $record['domain'] = $domain;
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

        $answer = [];
        $domains = $this->domainLookup($domain);

        foreach ($domains as $domain) {

            if (!isset($domain[$type])) {
                continue;
            }

            $answer[] = [
                'name' => $domain['domain'],
                'class' => $qClass,
                'ttl' => $this->ttl,
                'data' => [
                    'type' => $qType,
                    'value' => $domain[$type],
                ],
            ];
        }

        return $answer;
    }

    private function domainLookup($domain)
    {
        if (isset($this->records[$domain])) {
            return $this->records[$domain];
        }

        $pattern = str_replace('.', '\.', $domain);
        $pattern = str_replace('*', '.', $domain);

        return array_filter($this->records, function ($record, $key) use ($pattern) {
            if (preg_match('/'.$pattern.'/i', $key)) {
                return $record;
            }
        }, ARRAY_FILTER_USE_BOTH);
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
