

from dnslib.proxy import ProxyResolver

from dns.access import Access
from dns.hama import Hama

class Resolver(ProxyResolver):

	def __init__(self, upstream:str):
		# dns upstream, dns upstream port, dns upstream timeout
		super().__init__(upstream, 53, 5)

		self.access = Access()
		self.hama = Hama()

	def resolve(self, request, handler):
		# check client ip
		if self.access.check_access(handler.client_address[0]):
			
			# is hama domain?
			if self.hama.match_domain(request.q):
				reply = request.reply()

				# reply with own api's ip
				rr = self.hama.fetch_answer(request.q)
				if rr != None:
					reply.add_answer(rr)

				return reply

			else:
				# reply the normal way
				return super().resolve(request, handler)
		
		else:
			print("Clients IP not allowed, send empty answer!")
			return request.reply()

