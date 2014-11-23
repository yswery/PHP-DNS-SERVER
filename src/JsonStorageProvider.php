<?php

namespace yswery\DNS;

use \Exception;

class JsonStorageProvider extends AbstractStorageProvider {

    private $dns_records;
    private $DS_TTL;

    public function __construct($record_file, $default_ttl = 300)
    {
        $handle = @fopen($record_file, "r");
        if(!$handle) {
            throw new Exception('Unable to open dns record file.');
        }

        $dns_json = fread($handle, filesize($record_file));
        fclose($handle);

        $dns_records = json_decode($dns_json, true);
        if(!$dns_records) {
            throw new Exception('Unable to parse dns record file.');
        }
        
        if(!is_int($default_ttl)) {
            throw new Exception('Default TTL must be an integer.');
        }
        $this->DS_TTL = $default_ttl;

        $this->dns_records = $dns_records;
    }

    public function get_answer($question)
    {
        $answer = array();
        $domain = trim($question[0]['qname'], '.');
        $type = RecordTypeEnum::get_name($question[0]['qtype']);

        if(isset($this->dns_records[$domain]) &&isset($this->dns_records[$domain][$type])) {
            if(is_array($this->dns_records[$domain][$type]) && $type != 'SOA') {
                foreach($this->dns_records[$domain][$type] as $ip) {
                    $answer[] = array('name' => $question[0]['qname'], 'class' => $question[0]['qclass'], 'ttl' => $this->DS_TTL, 'data' => array('type' => $question[0]['qtype'], 'value' => $ip));
                }
            } else {
                $answer[] = array('name' => $question[0]['qname'], 'class' => $question[0]['qclass'], 'ttl' => $this->DS_TTL, 'data' => array('type' => $question[0]['qtype'], 'value' => $this->dns_records[$domain][$type]));
            }
        }

        return $answer;
    }

}
