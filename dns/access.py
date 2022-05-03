
import time
import ipaddress

from dns.config import Config
from dns.client import DNSClient

class Access():

	_IP_MAX_AGE = 300

	def __init__(self):
		self.all = Config["ALLOWED_ALL"]

		if not self.all:
			self.allowed_ips = [{
					'domain' : d,
					'ip' : None,
					'time' : 0
				} for d in Config["ALLOWED"]
			]

	def check_access(self, client_ip:str) -> bool:
		if self.all:
			return True
		
		try:
			client_ip = ipaddress.ip_address(client_ip)
		except ValueError:
			return False

		# sort, that newest time() will be checked first
		# 	so, we will check all domains not timed out first
		self.allowed_ips.sort(key=lambda x: x['time'], reverse=True)

		for a_ip in self.allowed_ips:
			if a_ip['ip'] is None or a_ip['time'] + self._IP_MAX_AGE < int(time.time()):
				a_ip['ip'] = DNSClient.resolve_a(a_ip['domain'])
				a_ip['time'] = int(time.time())

			if client_ip == a_ip['ip']:
				return True

		return False

	