FROM alpine:3.22

RUN apk add --no-cache curl iaxmodem iaxmodem-doc hylafaxplus supervisor bash expect

RUN cp /usr/share/doc/iaxmodem/config.ttyIAX /var/spool/hylafaxplus/etc/

COPY supervisord.conf /etc/supervisord.conf
# COPY ttyIAX /etc/iaxmodem/ttyIAX
COPY faxsetup.exp /faxsetup.exp
# COPY config.ttyIAX /var/spool/hylafaxplus/etc/config.ttyIAX

RUN expect /faxsetup.exp

COPY avantfax /tmp/avantfax

RUN cd /tmp/avantfax && ./alpine-install.sh

COPY startup.sh /startup.sh
COPY setup_db.php /setup_db.php

CMD ["/startup.sh"]