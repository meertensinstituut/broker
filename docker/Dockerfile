FROM ubuntu:16.04

EXPOSE 80

USER root

WORKDIR "/root"

RUN mkdir lib

RUN apt-get -y update && \
    apt-get install -y apache2 composer curl libapache2-mod-php locales php \
        php-cli php-curl php-sqlite3 php-mbstring sqlite3 unzip && \
    locale-gen en_US.UTF-8 en_US && update-locale LANG=en_US.UTF-8 LANGUAGE=en_US:en

RUN rm -rf /var/www/html
COPY src/ /var/www/html
COPY docker/start.sh /

RUN service apache2 stop && \
    echo "ServerName localhost" > /etc/apache2/conf-available/fqdn.conf && \
    a2enconf fqdn

RUN chmod 777 /var/www/html/cache && \
    composer install -d /var/www/html/

CMD sh /start.sh; bash
