# Radio DNS Server
for https://github.com/KIMB-technologies/Radio-API

> See the Docker Image at https://hub.docker.com/r/kimbtechnologies/radio_dns  
> Travis CI Autobuild from https://github.com/KIMB-technologies/Radio-DNS-Server

## Configuration

The configuration is done using env variables.

- `SERVER_IP` *(optional)* The IP address the server binds on, if not set, 0.0.0.0 is used to bind on all interfaces.
- `SERVER_PORT` *(optional)* The port which is used for the DNS server, should always be the default 53 (unless for testing).
- `RADIO_DOMAIN` *(required)* The domain where the [Radio-API](https://github.com/KIMB-technologies/Radio-API) can be found. The DNS server will return the `A` record of this domain for all queries containing `wifiradiofrontier.com`. 
- `ALLOWED_DOMAIN` *(optional)* Normally a DNS resolver will answer all queries from all sources. This can be a security risk, so one should only answer the queries from trusted sources. One can give a list (domain names divided by `,`) of domain name here, only queries from the corresponding `A` records will be answered then.  **The default value is `all` which means all sources are trusted. E.g. for testing and usage in local networks.** (Normally giving your DynDNS name is right; More domain names lead to a higher response time to queries.)
- `TIME_SERVER` *(optional)* If the DNS server is queried for `time.wifiradiofrontier.com` it will answer with the `A` record of this domain. So one does not have to host an own NTP server at `RADIO_DOMAIN`. Per default some time server is used.

### Docker Compose Example

```yaml
version: "2"

services:
  dns:
    container_name: radio_dns
    network_mode: host	# we need to know the originating ip of requests, else ALLOWED_DOMAIN cloud not be used, can be removed if used with ALLOWED_DOMAIN=all 
    image: kimbtechnologies/radio_dns:latest
    environment:
      - SERVER_IP=0.0.0.0 # the ip to bind on, 0.0.0.0 for all interfaces
      - SERVER_PORT=53 # the dns port, should be 53
      - RADIO_DOMAIN=radio.example.com # the place where https://github.com/KIMB-technologies/Radio-API ist hosted
      - ALLOWED_DOMAIN=home.example.com,home2.example.com # the domains of the home routers (DynDNS) as list divided by ',' or 'all' to allow all sources for the requests
      - TIME_SERVER=ntp0.fau.de # the NTP time server used by the radio (may be changed)
    restart: always
```
