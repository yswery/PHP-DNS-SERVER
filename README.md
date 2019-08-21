Radio DNS Server for https://github.com/KIMB-technologies/Radio-API

```yaml
version: "2"

services:
  dns:
    container_name: radio_dns
    network_mode: host
    image: kimbtechnologies/radio_dns:latest
    environment:
      - SERVER_IP=0.0.0.0
      - SERVER_PORT=53
      - RADIO_DOMAIN=radio.example.com # the place where https://github.com/KIMB-technologies/Radio-API ist hosted
      - ALLOWED_DOMAIN=home.example.com # the domain of the home router (dynDns)
    restart: always
```