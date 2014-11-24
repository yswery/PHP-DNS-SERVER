<?php

namespace yswery\DNS;

class StackableResolver {

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
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->get_answer($question);
            if ($answer) {
                return $answer;
            }
        }
        
        return array();
    }

}
