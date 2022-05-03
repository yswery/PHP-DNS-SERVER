# Radio DNS Server
for https://github.com/KIMB-technologies/Radio-API

> See the Docker Image at https://hub.docker.com/r/kimbtechnologies/radio_dns  
> Travis CI Autobuild from https://github.com/KIMB-technologies/Radio-DNS-Server

## Configuration

The configuration is done using env variables.

- `SERVER_BIND` *(optional)* The IP address the server binds on, if not set, 0.0.0.0 is used to bind on all interfaces.
- `SERVER_PORT` *(optional)* The port which is used for the DNS server, should always be the default 53 (unless for testing).
- `SERVER_UPSTREAM` *(optional)* The upstream DNS server, where DNS answers are fetched from 
- `RADIO_DOMAIN` *(required)* The domain where the [Radio-API](https://github.com/KIMB-technologies/Radio-API) can be found. The DNS server will return the `A` record of this domain for all queries containing `wifiradiofrontier.com`. 
- `ALLOWED_DOMAIN` *(optional)* Normally a DNS resolver will answer all queries from all sources. This can be a security risk, so one should only answer the queries from trusted sources. One can give a list (domain names divided by `,`) of domain name here, only queries from the corresponding `A` records will be answered then.  **The default value is `all` which means all sources are trusted. E.g. for testing and usage in local networks.** (Normally giving your DynDNS name is right; More domain names lead to a higher response time to queries.)
- `TIME_SERVER` *(optional)* If the DNS server is queried for `time.wifiradiofrontier.com` it will answer with the `A` record of this domain. So one does not have to host an own NTP server at `RADIO_DOMAIN`. Per default some time server is used.

Run using the [**Docker-compose Example**](./docker-compose.yml)!

## Notice and Used Libraries
- Ubuntu [Docker Image](https://hub.docker.com/_/ubuntu)
- [Python 3](https://www.python.org/)
- Python [DnsLib](https://pypi.org/project/dnslib/)
- Code inspired from [DNSServer](https://github.com/samuelcolvin/dnserver)  
  MIT License, Copyright (c) 2017 Samuel Colvin
- Regex Pattern from [Validators](https://github.com/kvesteri/validators/) used  
  MIT License, Copyright (c) 2013-2014 Konsta Vesterinen