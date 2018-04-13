<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Resolver;

/**
 * Class GoogleDNSForwarder
 */
class GoogleResolver implements ResolverInterface
{
    /**
     * @param array $query
     *
     * @return array
     */
    public function getAnswer($query)
    {
        $curl = curl_init();

        $name = $query[0]['qname'];
        $type = $query[0]['qtype'];
        $class = $query[0]['qclass'];

        curl_setopt($curl, CURLOPT_URL, 'https://dns.google.com/resolve?name='.$name);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $content = trim(curl_exec($curl));
        curl_close($curl);

        $response = json_decode($content, true);

        $answer = [];
        if (isset($response['Answer'])) {
            foreach ($response['Answer'] as $item) {
                if (!filter_var($item['data'], FILTER_VALIDATE_IP)) {
                    continue;
                }

                $answer[] = [
                    'name' => $name,
                    'class' => $class,
                    'ttl' => 300,
                    'data' => [
                        'type' => $type,
                        'value' => $item['data'],
                    ],
                ];
            }
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
        return false;
    }
}
