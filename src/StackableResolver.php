<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS;

/**
 * Class StackableResolver
 */
class StackableResolver
{
    /**
     * @var array
     */
    protected $resolvers;

    /**
     * StackableResolver constructor.
     *
     * @param array $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @param $question
     *
     * @return array
     */
    public function get_answer($question)
    {
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->get_answer($question);
            if ($answer) {
                return $answer;
            }
        }

        return [];
    }

    /**
     * Check if any of the resoolvers supports recursion
     *
     * @return boolean true if any resolver supports recursion
     */
    public function allows_recursion()
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->allows_recursion()) {
                return true;
            }
        }
    }

    /*
     * Check if any resolver knows about a domain
     *
     * @param  string  $domain the domain to check for
     * @return boolean         true if some resolver holds info about $domain
     */
    public function is_authority($domain)
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->is_authority($domain)) {
                return true;
            }
        }

        return false;
    }
}
