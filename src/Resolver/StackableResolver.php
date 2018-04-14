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
     * @param array $query
     *
     * @return array
     */
    public function getAnswer(array $query)
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
     * @inheritdoc
     *
     * @return bool
     */
    public function allowsRecursion()
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->allowsRecursion()) {
                return true;
            }
        }

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
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isAuthority($domain)) {
                return true;
            }
        }

        return false;
    }
}
