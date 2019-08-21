<?php

namespace yswery\DNS\Resolver;

class HamaResolver implements ResolverInterface
{

	private $sysRes;
	private $fwdServer;

	public function  __construct($res, $rdom){
		$this->sysRes = $res;
		$this->fwdServer = $rdom;
	}

	public function getAnswer(array $queries): array{
		$ok = false;
		foreach( $queries as &$q){
			$name = $q->getName();
			if( $this->isAuthority($name) ){
				$qq = clone $q;
				$qq->setName($this->fwdServer);
				$qq = $this->sysRes->getAnswer( array( $qq ) )[0];
				$qq->setName($name);
				$q = $qq;
				$ok = true;
			}
		}
		return $ok ? $queries : [];
	}

    	public function allowsRecursion(): bool{
	    return false;
	}

	public function isAuthority($domain): bool {
		return \stripos( $domain, 'wifiradiofrontier.com' ) !== false
			&& $domain != 'time.wifiradiofrontier.com';
	}
}
