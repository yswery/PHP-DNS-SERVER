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
          'answer' => array(),
          'authoritative' => false,
        );

        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->get_answer($question);
            if ($answer) {
                $ret['answer'] = $answer;

                if ($resolver instanceof JsonStorageProvider) {
                  $ret['authoritative'] = true;
                }
            }
        }

        return $ret;
    }
}
