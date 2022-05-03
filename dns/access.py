
import ipaddress

from dns.config import Config

class Access():

	def __init__(self):
		self.all = Config["ALLOWED_ALL"]

	def check_access(self, client_ip:str) -> bool:
		if self.all:
			return True
		
		try:
			client_ip = ipaddress.ip_address(client_ip)
		except ValueError:
			return False

		return True

	