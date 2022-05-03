FROM ubuntu:22.04

ARG H_GID=1000
ARG H_UID=1000

RUN apt update && \
	apt install -y \
		python3 \
		python3-pip \
		libcap2-bin

RUN python3 -m pip install --no-cache-dir --upgrade pip

# sytem and user setup 
RUN ln -s /usr/bin/python3 /usr/local/bin/python \
	&& addgroup --gid $H_GID user \
	&& adduser user --uid $H_UID --ingroup user --gecos "" --home /home/user/ --disabled-password \
	&& setcap CAP_NET_BIND_SERVICE=+eip /usr/bin/python3.10

 # install python packages
RUN pip3 install --no-cache-dir \
	dnslib

# install the code of the repo
COPY ./docker/setup.py /home/user/
RUN pip3 install -e /home/user/

ENV PYTHONUNBUFFERED=1 

# open port
EXPOSE 53/udp

# add source
COPY --chown=user:user ./dns /home/user/dns/
COPY --chown=user:user ./run.py /home/user/

# run
USER user
WORKDIR /home/user/
CMD ["/usr/bin/python3","/home/user/run.py"]
