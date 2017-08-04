<?php

namespace yswery\DNS;

class StackableResolver
{

    /**
     * @var array
     */
    protected $resolvers;

    public function __construct(array $resolvers = array())
    {
        $this->resolvers = $resolvers;
    }

    public function get_answer($question)
    {
        $ret = array(
          'answer'        => array(),
          'authoritative' => false,
        );

        foreach ($this->resolvers as $resolver) {
            $result = $resolver->get_answer($question);
            if ($result['answer']) {
                $ret['answer'] = $result['answer'];

                if ($resolver instanceof JsonStorageProvider) {
                  $ret['authoritative'] = true;
                }
            }
        }

        return $ret;
    }
}
