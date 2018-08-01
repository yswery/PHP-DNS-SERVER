<?php

namespace yswery\DNS;

class StackableResolver implements ResolverInterface
{
    /**
     * @var array
     */
    protected $resolvers;

    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /*
     * {@inheritdoc}
     */
    public function getAnswer(array $question)
    {
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->getAnswer($question);
            if ($answer) {
                return $answer;
            }
        }

        return [];
    }

    /*
     * {@inheritdoc}
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

    /*
     * {@inheritdoc}
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
