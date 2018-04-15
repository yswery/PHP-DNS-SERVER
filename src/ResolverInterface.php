<?php
/**
 * @package yswery\DNS
 * @author Ivan Stanojevic <https://github.com/ivanstan>
 */

namespace yswery\DNS;

/**
 * Class ResolverInterface
 */
interface ResolverInterface
{
    /**
     * Return answer for given query.
     *
     * @param array $query
     *
     * @return array
     */
    public function getAnswer(array $query);

    /**
     * Returns true if resolver supports recursion.
     *
     * @return boolean
     */
    public function allowsRecursion();

    /**
     * Check if the resolver knows about a domain.
     * Returns true if the resolver holds info about $domain
     *
     * @param string $domain The domain to check for
     *
     * @return boolean
     */
    public function isAuthority($domain);
}