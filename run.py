#!/usr/bin/env python3

import time

from dns.config import Config
from dns.server import Server

if __name__ == "__main__":

	if Config["DEVMODE"]:
		print("Devmode enabled, no server will be started!")
		print("\tAttach to container and start manually.")

		while True:
			time.sleep(1)

	else:
		server = Server()

		try:
			while server.is_alive():
				time.sleep(1)

		except KeyboardInterrupt:
			print("Stopped by KeyboardInterruption")