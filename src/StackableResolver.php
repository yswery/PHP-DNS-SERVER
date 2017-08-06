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
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->get_answer($question);
            if ($answer) {
                return $answer;
            }
        }

        return array();
    }

    /**
     * Check if any of the resoolvers supports recursion
     *
     * @return boolean true if any resolver supports recursion
     */

    public function allows_recursion() {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->allows_recursion()) {
              return true;
            }
        }

        return false;
    }
}
