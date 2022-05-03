

"""
	This code is heavily inspired from:
	
		DNS Server by Samuel Colvin
		https://github.com/samuelcolvin/dnserver

		Copyright (c) 2017 Samuel Colvin
		MIT Licensed
		https://github.com/samuelcolvin/dnserver/blob/master/LICENSE)
"""

from datetime import datetime
from textwrap import wrap

from dnslib import DNSLabel, QTYPE, RR, dns

class RecordMeta():

	SERIAL_NO = int((datetime.utcnow() - datetime(1970, 1, 1)).total_seconds())

	TYPE_LOOKUP = {
		'A': (dns.A, QTYPE.A),
		'AAAA': (dns.AAAA, QTYPE.AAAA),
		'CAA': (dns.CAA, QTYPE.CAA),
		'CNAME': (dns.CNAME, QTYPE.CNAME),
		'DNSKEY': (dns.DNSKEY, QTYPE.DNSKEY),
		'MX': (dns.MX, QTYPE.MX),
		'NAPTR': (dns.NAPTR, QTYPE.NAPTR),
		'NS': (dns.NS, QTYPE.NS),
		'PTR': (dns.PTR, QTYPE.PTR),
		'RRSIG': (dns.RRSIG, QTYPE.RRSIG),
		'SOA': (dns.SOA, QTYPE.SOA),
		'SRV': (dns.SRV, QTYPE.SRV),
		'TXT': (dns.TXT, QTYPE.TXT),
		'SPF': (dns.TXT, QTYPE.TXT),
	}

class Record():

	def __init__(self, rname, rtype, args):
		self._rname = DNSLabel(rname)

		rd_cls, self._rtype = RecordMeta.TYPE_LOOKUP[rtype]

		if self._rtype == QTYPE.SOA and len(args) == 2:
			# add sensible times to SOA
			args += (RecordMeta.SERIAL_NO, 3600, 3600 * 3, 3600 * 24, 3600),

		if self._rtype == QTYPE.TXT and len(args) == 1 and isinstance(args[0], str) and len(args[0]) > 255:
			# wrap long TXT records as per dnslib's docs.
			args = wrap(args[0], 255),

		if self._rtype in (QTYPE.NS, QTYPE.SOA):
			ttl = 3600 * 24
		else:
			ttl = 300

		self.rr = RR(
			rname=self._rname,
			rtype=self._rtype,
			rdata=rd_cls(*args),
			ttl=ttl,
		)

	def __str__(self):
		return str(self.rr)