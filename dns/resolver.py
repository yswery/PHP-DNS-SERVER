


"""
	This code is partly inspired from:
	
		DNS Server by Samuel Colvin
		https://github.com/samuelcolvin/dnserver

		Copyright (c) 2017 Samuel Colvin
		MIT Licensed
		https://github.com/samuelcolvin/dnserver/blob/master/LICENSE)
"""

import re
from urllib import response
from dns.hama import Hama
from dnslib import QTYPE
from dnslib.proxy import ProxyResolver

from dns.access import Access
from dns.hama import Hama

from dnslib.label import DNSLabel

class Resolver(ProxyResolver):

	def __init__(self, upstream:str):
		# dns upstream, dns upstream port, dns upstream timeout
		super().__init__(upstream, 53, 5)

		self.access = Access()
		self.hama = Hama()

	
	def resolve(self, request, handler):
		print("I")

		# check client ip
		if not self.access.check_access(handler.client_address[0]):
			return request.reply()

		print("II")

		# check for hama domain and fake it
		if self.hama.match_domain(request.q.qname):
			self.hama.manipulate_request(request)

		# forward request to upstream
		request = super().resolve(request, handler)

		print(request.header.__dict__)
		print(request.questions[0].__dict__)
		print(request.auth[0].__dict__)
		print(request.ar[0].__dict__)


		return request
