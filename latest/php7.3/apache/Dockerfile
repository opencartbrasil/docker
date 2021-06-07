#
# NOTA: Arquivo gerado automaticamente
#
# Não o edite diretamente.
#

FROM php:7.3-apache

ARG VERSION="1.5.0" \
    ENV_MODE="development"

ENV OCBR_HTTP_SERVER="" \
    \
    # Variáveis de ambiente do Banco de Dados
    OCBR_DB_DRIVER="pdo" \
    OCBR_DB_HOST="0.0.0.0" \
    OCBR_DB_USER="root" \
    OCBR_DB_PASS="" \
    OCBR_DB_DATABASE="opencartbrasil" \
    OCBR_DB_PORT="3306" \
    OCBR_DB_PREFIX="ocbr_" \
    \
    # Variáveis de ambiente do usuário da administração
    OCBR_ADMIN_USER="" \
    OCBR_ADMIN_PASS="" \
    OCBR_ADMIN_EMAIL="webmaster@localhost" \
    \
    # Variáveis de ambiente para serviço de e-mail
    MAIL_DRIVER="" \
    MAIL_PARAMETER="" \
    MAIL_SERVER="" \
    MAIL_USER="" \
    MAIL_PASS="" \
    MAIL_PORT="" \
    MAIL_TIMEOUT="" \
    MAIL_ADDITIONAL_MAILS=""

# Instala as dependências necessárias e habilita as extensões do PHP
RUN set -ex; \
        apt update; \
    apt install -y --no-install-recommends \
        apt-transport-https \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libpng-dev \
        libxml2-dev \
        libonig-dev \
        libzip-dev \
        netcat \
        libcurl4-openssl-dev; \
        \
    docker-php-ext-configure gd \
        --with-jpeg-dir=/usr \
    --with-png-dir=/usr \
    --with-freetype-dir=/usr; \
        ; \
    docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        mbstring \
        pdo_mysql \
        zip; \
    \
    curl -sSLo- https://getcomposer.org/installer | php -- --filename=composer --install-dir=/bin;

# Configura regras de exibição do log
RUN { \
        echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'error_log = /dev/stderr'; \
        echo 'log_errors = On'; \
        echo 'log_errors_max_len = 1024'; \
        echo 'ignore_repeated_errors = On'; \
        echo 'ignore_repeated_source = Off'; \
        echo 'html_errors = Off'; \
        echo 'zend.exception_ignore_args = Off'; \
        echo 'zend.exception_string_param_max_len = 25'; \
    } > /usr/local/etc/php/conf.d/error-logging.ini

# Configura o PHP para funcionamento da plataforma
RUN { \
        echo 'allow_url_fopen = On'; \
        echo 'default_charset = UTF-8'; \
        echo 'file_uploads = On'; \
        echo 'max_execution_time = 360'; \
        echo 'memory_limit = 128M'; \
        echo 'upload_max_filesize = 200M'; \
        echo 'open_basedir = none'; \
        echo "user_agent = \"Curl-OpenCartBrasil/${VERSION}\""; \
        echo 'post_max_size = 200M'; \
        echo 'session.auto_start = Off'; \
        echo 'session.use_only_cookies = On'; \
        echo 'session.use_cookies = On'; \
        echo 'session.use_trans_sid = Off'; \
        echo 'session.cookie_httponly = Off'; \
        echo 'session.cache_limiter = nocache'; \
    } > /usr/local/etc/php/conf.d/opencart-brasil.ini;

# Realiza download da versão
RUN set -aux; \
    curl -sSLo opencart.tar.gz "https://github.com/opencartbrasil/opencartbrasil/archive/refs/tags/v${VERSION}.tar.gz"; \
    mkdir -p /usr/src/opencart; \
    tar -xzvf opencart.tar.gz -C /usr/src/opencart --strip-components 1; \
    rm opencart.tar.gz; \
    \
    for oc_folder in  \
        "/var/www/html/admin/" \
        "/var/www/html/install/" \
        "/var/www/html/image/cache/" \
        "/var/www/html/image/catalog/" \
        "/var/www/html/system/storage/cache/" \
        "/var/www/html/system/storage/logs/" \
        "/var/www/html/system/storage/download/" \
        "/var/www/html/system/storage/upload/" \
        "/var/www/html/system/storage/session/" \
        "/var/www/html/system/storage/modification/"; \
    do \
        if [ ! -d $oc_folder ]; then \
            mkdir -p $oc_folder; \
        fi; \
        chown -R www-data:www-data $oc_folder; \
    done; \
    \
    for oc_file in  \
        "/var/www/html/admin/config.php" \
        "/var/www/html/config.php"; \
    do \
        if [ ! -f $oc_file ]; then \
            touch $oc_file; \
        fi; \
        chown www-data:www-data $oc_file; \
    done;

RUN set -aux; \
    a2enmod rewrite; \
    a2enmod expires

VOLUME /var/www/html

COPY scripts/ /scripts

# Built-in
EXPOSE 8888

COPY docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
