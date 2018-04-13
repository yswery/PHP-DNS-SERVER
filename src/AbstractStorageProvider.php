<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS;

abstract class AbstractStorageProvider
{
    abstract public function get_answer($question);

    abstract public function allows_recursion();

    /**
     * Check if the resolver knows about a domain.
     * Returns true if the resolver holds info about $domain
     *
     * @param string $domain The domain to check for
     *
     * @return boolean
     */
    abstract public function is_authority($domain);

}
