FROM debian:latest
RUN apt-get update && \
    apt-get install -y composer php-cli apache2 libapache2-mod-php php-xml php-ldap php-pgsql && \
    a2enmod rewrite
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
#    apt-get install -y libldap2-dev libsodium-dev libpq-dev && \
#    docker-php-ext-install ldap intl bz2 zip sodium bcmath pcntl pdo_pgsql pgsql
#RUN echo 'LogLevel alert rewrite:trace6' >> /etc/apache2/apache2.conf
CMD apachectl -D FOREGROUND