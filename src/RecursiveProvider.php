<?php

namespace yswery\DNS;

use \Exception;

class RecursiveProvider extends AbstractStorageProvider {

    private $dns_answer_names = array(
        'DNS_A' => 'ip',
        'DNS_AAAA' => 'ipv6',
        'DNS_CNAME' => 'target',
        'DNS_TXT' => 'txt',
        'DNS_MX' => 'target',
        'DNS_NS' => 'target',
        'DNS_SOA' => array('mname', 'rname', 'serial', 'retry', 'refresh', 'expire', 'minimum-ttl'),
        'DNS_PTR' => 'target',
    );

    private $servers = array();
    private $serverpicker = -1;

    public function __construct(array $servers = array(), callable $serverpicker = null) {
        $this->servers = $servers;
        if($serverpicker == null){
            $this->serverpicker = function(){return -1;};
        }else{
            $this->serverpicker = $serverpicker;
        }
    }

    public function get_answer($question)
    {
        $answer = array();
        $domain = trim($question[0]['qname'], '.');
        $type = RecordTypeEnum::get_name($question[0]['qtype']);

        $records = $this->get_records_recursivly($domain, $type);
        foreach($records as $record) {
            $answer[] = array('name' => (isset($record["domain"])?$record["domain"]:$question[0]['qname']), 'class' => $question[0]['qclass'], 'ttl' => $record['ttl'], 'data' => array('type' => (isset($record['type'])?RecordTypeEnum::get_type_index($record['type']):$question[0]['qtype']), 'value' => $record['answer']));
        }

        return $answer;
    }

    private function get_records_recursivly($domain, $type)
    {

        $result = array();
        $result = $this->try_configured_servers($domain, $type);
        if(!empty($result)){
            return $result;
        }
        $dns_const_name =  $this->get_dns_cost_name($type);

        if (!$dns_const_name) {
            throw new Exception('Not supported dns type to query.');
        }

        $dns_answer_name = $this->dns_answer_names[$dns_const_name];

        $records = dns_get_record($domain, constant($dns_const_name));

        foreach($records as $record) {
            if(is_array($dns_answer_name)) {
                foreach($dns_answer_name as $name) {
                    $answer[$name] = $record[$name];
                }
            } else{
                $answer = $record[$dns_answer_name];
            }
            $result[] = array('answer' => $answer, 'ttl' => $record['ttl']);
        }

        return $result;
    }

    private function try_configured_servers($domain, $type){
        $response = array();
        $servers = $this->servers;
        $select = call_user_func_array($this->serverpicker, array($domain, $type));
        if($select && array_key_exists($select,$servers)){
            $response = $this->dig($domain,$type, $servers[$select]);
            unset($servers[$select]);
        }
        if(empty($response) && !empty($servers)){
            foreach($servers as $server) {
                $response = $this->dig($domain,$type, $server);
                if($response){
                    return $response;
                }
            }
        }
        return $response;
    }

    private function dig($domain,$type,$server){
        $line = exec(escapeshellcmd("dig +nocmd +noall +answer +time=1 +tries=1 -t $type @$server $domain"), $response);
        $result = array();
        if(strstr($line, "connection timed out")===false) {
            foreach ($response as $value) {
                $value = preg_split('/\s+/', $value);
                array_filter($value);
                switch (strtoupper($type)) {
                    case "MX": $result[] = array_combine(array("domain", "ttl", "class", "type", "priority", "answer"), $value);
                        break;
                    case "SOA": $value = array_combine(array("domain", "ttl", "IN", "class", "mname", "rname","serial","retry","refresh","expire" ,"minimum-ttl"), $value);
                                $result[] = array("answer"=>$value, "ttl"=>$value["ttl"]);
                        break;
                    default: $result[] = array_combine(array("domain", "ttl", "class", "type", "answer"), $value);
                        break;
                }
            }
            return $result;
        }
        return array();
    }

    private function get_dns_cost_name($type)
    {
        $const_name = "DNS_".strtoupper($type);
        $name = defined($const_name) ? $const_name : false;

        return $name;
    }

}

