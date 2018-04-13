<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS;

/**
 * Class AbstractStorageProvider
 */
interface ResolverInterface
{
    /**
     * @param array $query
     *
     * @return array
     */
    public function getAnswer($query);

    /**
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
