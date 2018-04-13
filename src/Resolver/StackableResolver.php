<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Resolver;

/**
 * Class StackableResolver
 */
class StackableResolver implements ResolverInterface
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
     * @param $query
     *
     * @return array
     */
    public function getAnswer($query)
    {
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->getAnswer($query);
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
    public function allowsRecursion()
    {
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
    public function isAuthority($domain)
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isAuthority($domain)) {
                return true;
            }
        }

        return false;
    }
}
