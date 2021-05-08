<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Resolver;

use yswery\DNS\ResourceRecord;

class StackableResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface[]
     */
    protected $resolvers;

    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @param ResourceRecord[] $question
     *
     * @return array
     */
    public function getAnswer(array $question, ?string $client = null): array
    {
        foreach ($this->resolvers as $resolver) {
            $answer = $resolver->getAnswer($question);
            if (!empty($answer)) {
                return $answer;
            }
        }

        return [];
    }

    /**
     * Check if any of the resolvers supports recursion.
     *
     * @return bool true if any resolver supports recursion
     */
    public function allowsRecursion(): bool
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->allowsRecursion()) {
                return true;
            }
        }

        return false;
    }

    /*
     * Check if any resolver knows about a domain
     *
     * @param  string  $domain the domain to check for
     * @return boolean         true if some resolver holds info about $domain
     */
    public function isAuthority($domain): bool
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isAuthority($domain)) {
                return true;
            }
        }

        return false;
    }
}
