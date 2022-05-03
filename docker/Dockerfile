FROM ubuntu:22.04

ARG H_GID=1000
ARG H_UID=1000

RUN apt update && \
	apt install -y \
		python3 \
		python3-pip 

RUN python3 -m pip install --no-cache-dir --upgrade pip

# sytem and user setup 
RUN ln -s /usr/bin/python3 /usr/local/bin/python \
	&& addgroup --gid $H_GID user \
	&& adduser user --uid $H_UID --ingroup user --gecos "" --home /home/user/ --disabled-password

 # install pythin packages
RUN pip3 install --no-cache-dir \
	dnslib

# install the code of the repo
COPY ./docker/setup.py /home/user/
RUN pip3 install -e /home/user/

ENV SERVER_IP=0.0.0.0 \
    SERVER_PORT=53 \
    RADIO_DOMAIN=radio.example.com \
    ALLOWED_DOMAIN=all \
    TIME_SERVER=ntp0.fau.de

# open port
EXPOSE 53/udp

# add source
COPY --chown=user:user ./dns /home/user/dns/
COPY --chown=user:user ./server.py /home/user/

# run
USER user
CMD ["/usr/bin/python3","/home/user/server.py"]
