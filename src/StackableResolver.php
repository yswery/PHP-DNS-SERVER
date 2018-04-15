<?php

namespace yswery\DNS;

class StackableResolver implements ResolverInterface
{

    /**
     * @var array
     */
    protected $resolvers;

    public function __construct(array $resolvers = array())
    {
        $this->resolvers = $resolvers;
    }

    public function getAnswer(array $question)
    {
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->getAnswer($question);
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
    public function allowsRecursion() {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->allowsRecursion()) {
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
    public function isAuthority($domain) {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isAuthority($domain)) {
                return true;
            }
        }
        return false;
    }
}
