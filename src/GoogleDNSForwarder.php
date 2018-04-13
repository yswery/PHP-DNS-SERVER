<?php

namespace yswery\DNS;

class GoogleDNSForwarder extends AbstractStorageProvider
{
    /**
     * @inheritdoc
     *
     * @param $question
     */
    public function get_answer($question)
    {
        $curl = curl_init();

        $q_name = $question[0]['qname'];
        $q_type = $question[0]['qtype'];
        $q_class = $question[0]['qclass'];

        // Set query data here with the URL
        curl_setopt($curl, CURLOPT_URL, 'https://dns.google.com/resolve?name=' . $q_name);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $content = trim(curl_exec($curl));
        curl_close($curl);

        $response = json_decode($content, true);

        $answer = array();
        if (isset($response['Answer'])) {

            foreach($response['Answer'] as $item) {

                if (!filter_var($item['data'], FILTER_VALIDATE_IP)) {
                    continue;
                }

                $answer[] = array(
                    'name' => $q_name,
                    'class' => $q_class,
                    'ttl' => 300,
                    'data' => array(
                        'type' => $q_type,
                        'value' => $item['data'],
                    ),
                );
            }
        }

        return $answer;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function allows_recursion()
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
    public function is_authority($domain)
    {
        return false;
    }

}