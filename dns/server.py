
import time 

from dnslib.server import DNSServer

from dns.config import Config
from dns.resolver import Resolver

class Server():

	def __init__(self):
		self.resolver = Resolver(Config['UPSTREAM'])

		self.dns_server = DNSServer(self.resolver, address=Config["BIND"], port=Config['PORT'])
		self.dns_server.start_thread()

	def is_alive(self) -> bool:
		return self.dns_server.isAlive()

if __name__ == "__main__":
	server = Server()

	while server.is_alive():
		time.sleep(1)