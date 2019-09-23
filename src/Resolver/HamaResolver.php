<?php

namespace yswery\DNS\Resolver;

class HamaResolver implements ResolverInterface
{

	private $sysRes;
	private $fwdServer;
	private $timeServer;

	public function  __construct($res, $rdom, $tsrv ){
		$this->sysRes = $res;
		$this->fwdServer = $rdom;
		$this->timeServer = $tsrv;
	}

	public function getAnswer(array $queries): array{
		$ok = false;
		foreach( $queries as &$q ){
			$name = $q->getName();
			if( $this->isAuthority($name) ){
				$qq = clone $q;
				$qq->setName( \stripos( $name, 'time.' ) === false ? $this->fwdServer : $this->timeServer );
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
		return \stripos( $domain, 'wifiradiofrontier.com' ) !== false;
	}
}
