#!/usr/bin/env bash
command=$1

if [[ "$command" =~ start|php-fpm|apache2* ]]; then
    uid=$(id -u)
    gid=$(id -g)

    if [ "$uid" -eq 0 ]; then
        user="${APACHE_RUN_USER:-www-data}"
        group="${APACHE_RUN_GROUP:-www-data}"
    else
        user="$uid"
        group="$gid"
    fi

    if [ ! -e index.php ]; then
        if [ "$uid" = '0' ] && [ "$(stat -c '%u:%g' .)" = '0:0' ]; then
            chown "$user:$group" .
        fi

        if [ -d catalog ] || [ -d admin ]; then
            echo >&2 "Atenção! A pasta deve estar vazia para inciar o projeto"
        fi

        sourceTarArgs=(
            --create
            --directory /usr/src/opencart
            --owner "$user" --group "$group"
            --file -
        )
        targetTarArgs=(
            --extract
            --file -
        )

        if [ "$uid" != '0' ]; then
            targetTarArgs+=( --no-overwrite-dir )
        fi

        tar "${sourceTarArgs[@]}" . | tar "${targetTarArgs[@]}"

        echo "Arquivos copiados para $PWD"

        timeout=0;
        db_available=0
        db_data=1
        declare -a db_config=(
            "${OCBR_DB_DATABASE:-0}"
            "${OCBR_DB_DRIVER:-0}"
            "${OCBR_DB_HOST:-0}"
            "${OCBR_DB_PASS:-0}"
            "${OCBR_DB_PORT:-0}"
            "${OCBR_DB_PREFIX:-0}"
            "${OCBR_DB_USER:-0}"
            "${OCBR_HTTP_SERVER:-0}"
        )

        for db_config in ${db_config[@]}; do
            if [ $db_config = "0" ]; then
                db_data=0
            fi
        done

        if [ $db_data -eq 1 ]; then
            while [ $timeout -lt 120 ]; do
                nc -z -w1 $OCBR_DB_HOST $OCBR_DB_PORT
                if [ $? -eq 0 ]; then
                    db_available=1
                    break;
                fi
                timeout=$(( $timeout + 1 ));
                echo "Aguardando conexão com banco de dados. Tentativa $timeout/120"
                sleep 1
            done

            if [ $db_available -eq 1 ]; then
                echo ""
                echo "Configurando dados"
                php /assets/configure-oc.php
                cp /assets/admin-config-docker.php admin/config.php
                cp /assets/config-docker.php config.php
                chown "${user}:${group}" admin/config.php config.php
            else
                echo "Não foi possível conectar-se ao banco de dados"
            fi
        fi
    fi
fi

if [ $command = "start" ]; then
    for oc_file in \
        "/var/www/html/admin/config.php" \
        "/var/www/html/config.php";
    do
        [[ ! -f $oc_file ]] && touch $oc_file;
        chown www-data:www-data $oc_file;
    done;

    php -S 0.0.0.0:8888 -t /var/www/html
else
    exec "$@"
fi
