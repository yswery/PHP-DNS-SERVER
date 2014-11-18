<?php

namespace StorageProvider;


use StorageProvider\AbstractStorageProvider;

class JsonStorageProvider extends AbstractStorageProvider {
	private $dns_records;
	private $DS_TYPES = array(1 => 'A', 2 => 'NS', 5 => 'CNAME', 6 => 'SOA', 12 => 'PTR', 15 => 'MX', 16 => 'TXT', 28 => 'AAAA', 41 => 'OPT', 252 => 'AXFR', 255 => 'ANY'); 
	private $DS_TTL = 300;

	public function __construct($record_file, $default_ttl = 300) {
		$handle = fopen($record_file, "r");
		if(!$handle) {
			throw new Exception('Unable to open dns record file.');
		}

		$dns_json = fread($handle, filesize($record_file));
		fclose($handle);

		$dns_records = json_decode($dns_json, true);
		if(!$dns_records) {
			throw new Exception('Unable to parse dns record file.');
		}

		$this->dns_records = $dns_records;
	}

	public function get_answer($question) {
		
		$answer = array();
		$domain = trim($question[0]['qname'], '.');
		$type = $this->DS_TYPES[$question[0]['qtype']];

		if(isset($this->dns_records[$domain]) && isset($this->dns_records[$domain][$type])){
			if(is_array($this->dns_records[$domain][$type])){
				foreach($this->dns_records[$domain][$type] as $ip){
					$answer[] = array(
						'name' => $question[0]['qname'],
						'class' => $question[0]['qclass'],
						'ttl' => $this->DS_TTL,
						'data' => array(
							'type' => $question[0]['qtype'],
							'value' => $ip
						)
					);
				}
			} else {
				$answer[] = array(
					'name' => $question[0]['qname'],
					'class' => $question[0]['qclass'],
					'ttl' => $this->DS_TTL,
					'data' => array(
						'type' => $question[0]['qtype'],
						'value' => $this->dns_records[$domain][$type]
					)
				);
			}
		}

		return $answer;
	}

}