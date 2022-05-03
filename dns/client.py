
from typing import Union

from dnslib import QTYPE
from dnslib.dns import DNSRecord, DNSQuestion

from dns.config import Config

class DNSClient():
	"""
		Simple DNS client for resolving A records
	"""

	def resolve_a(domain:str) -> Union[str, None]:
		"""
			Resolve A record

			Args:
				domain: domain to resolve

			Returns:
				ip address or None, if not found
		"""
		question = DNSQuestion(domain, QTYPE.A)
		query = DNSRecord(q = question)

		response = query.send(Config["UPSTREAM"], 53)

		for records in DNSRecord.parse(response).rr:
			if records.rtype == QTYPE.A:
				return repr(records.rdata)

		return None
