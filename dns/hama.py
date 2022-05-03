from typing import Union

from dnslib import QTYPE
from dnslib.dns import DNSQuestion, RR, A

from dns.config import Config
from dns.client import DNSClient

class Hama():

	_DOMAIN = "wifiradiofrontier.com."
	_TIME_DOMAIN = "time.wifiradiofrontier.com."

	def __init__(self):
		self.domain = Config["RADIO"]
		self.time_domain = Config["TIME"]

	def match_domain(self, question:DNSQuestion) -> bool:
		if isinstance(question, DNSQuestion):
			if question.qname.matchSuffix(self._DOMAIN):
				if question.qtype == QTYPE.A:
					return True

		return False

	def fetch_answer(self, question:DNSQuestion) -> Union[RR, None]:
		if question.qname.matchSuffix(self._TIME_DOMAIN):
			ip_address = DNSClient.resolve_a(Config["TIME"])
		else:
			ip_address = DNSClient.resolve_a(Config["RADIO"])

		if ip_address == None:
			return None
		else:
			return RR(
				question.qname,
				QTYPE.A,
				rdata=A(ip_address),
				ttl=300
			)

	