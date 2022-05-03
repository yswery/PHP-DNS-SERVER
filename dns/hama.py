

from dnslib.label import DNSLabel
from dnslib.dns import DNSRecord

from dns.record import Record
from dns.config import Config

class Hama():

	_DOMAIN = "wifiradiofrontier.com."
	_TIME_DOMAIN = "time.wifiradiofrontier.com."

	def __init__(self):
		self.new_label_radio = DNSLabel(Config["RADIO"])
		self.new_label_time = DNSLabel(Config["TIME"])

	def match_domain(self, domain:DNSLabel) -> bool:
		if not isinstance(domain, DNSLabel):
			return False
		return domain.matchSuffix(self._DOMAIN)

	def manipulate_request(self, request:DNSRecord):
		if not isinstance(request, DNSRecord):
			return False

		if request.q.qname.matchSuffix(self._TIME_DOMAIN):
			request.q.qname = self.new_label_time
		else:
			request.q.qname = self.new_label_radio

		


	