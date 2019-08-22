# Radio DNS Server
for https://github.com/KIMB-technologies/Radio-API

> Travis CI Autobuild from https://github.com/KIMB-technologies/Radio-DNS-Server

```yaml
version: "2"

services:
  dns:
    container_name: radio_dns
    network_mode: host	# we need to know the originating ip of requests, else ALLOWED_DOMAIN cloud not be used
    image: kimbtechnologies/radio_dns:latest
    environment:
      - SERVER_IP=0.0.0.0 # the ip to bind on, 0.0.0.0 for all interfaces
      - SERVER_PORT=53 # the dns port, should be 53
      - RADIO_DOMAIN=radio.example.com # the place where https://github.com/KIMB-technologies/Radio-API ist hosted
      - ALLOWED_DOMAIN=home.example.com # the domain of the home router (dynDNS)
      - TIME_SERVER=ntp0.fau.de # the ntp time server used by the radio (may be changend)
    restart: always
```
