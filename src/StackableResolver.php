<?php

namespace yswery\DNS;

class StackableResolver
{

    /**
     * @var array
     */
    protected $resolvers;

    public function get_resolvers() {
        $resolvers_names = array();
        foreach ($this->resolvers as $resolver) {
            $resolver_names[] = (new \ReflectionClass($resolver))->getShortName();
        }
      return $resolver_names;
    }

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
