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

        if [ -d catalog ]; then
            echo >&2 "Atenção! A pasta deve estar vazia para inciar o projeto"
        fi

        declare -a sourceTarArgs=(
            --create
            --directory /usr/src/opencart
            --owner "$user" --group "$group"
            --file -
        )
        declare -a targetTarArgs=(
            --extract
            --file -
        )

        if [ "$uid" != '0' ]; then
            targetTarArgs+=( --no-overwrite-dir )
        fi

        tar ${sourceTarArgs[@]} . | tar ${targetTarArgs[@]}

        echo "Arquivos copiados para $PWD"

        timeout=0;
        db_available=0
        db_data=1
        declare -A db_config=(
            [OCBR_DB_DATABASE]="${OCBR_DB_DATABASE:-0}"
            [OCBR_DB_DRIVER]="${OCBR_DB_DRIVER:-0}"
            [OCBR_DB_HOST]="${OCBR_DB_HOST:-0}"
            [OCBR_DB_PASS]="${OCBR_DB_PASS:-0}"
            [OCBR_DB_PORT]="${OCBR_DB_PORT:-0}"
            [OCBR_DB_PREFIX]="${OCBR_DB_PREFIX:-0}"
            [OCBR_DB_USER]="${OCBR_DB_USER:-0}"
            [OCBR_HTTP_SERVER]="${OCBR_HTTP_SERVER:-0}"
        )

        db_error=()

        for db_config_key in ${!db_config[@]}; do
            if [ ${db_config[$db_config_key]} = "0" ]; then
                db_error+=( "$db_config_key" )
                db_data=0
            fi
        done

        if [ ${#db_error[@]} -gt 0 ]; then
            echo "Não foi possível configurar automaticamente. As seguintes variáveis de ambiente estão faltando:"
            echo ${db_error[@]} | tr " " "\n" | sed 's/^/ - /g'
        fi

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
                php /scripts/configure-oc.php
                cp /scripts/admin-config-docker.php admin/config.php
                cp /scripts/config-docker.php config.php
                chown "${user}:${group}" admin/config.php config.php
            else
                echo "Não foi possível conectar-se ao banco de dados"
            fi
        fi
    fi

    for oc_file in \
        "/var/www/html/admin/config.php" \
        "/var/www/html/config.php";
    do
        [[ ! -f $oc_file ]] && touch $oc_file && chown "${user}:${group}" $oc_file;
    done;
fi

if [ $command = "start" ]; then
    php -S 0.0.0.0:8888 -t /var/www/html
else
    exec "$@"
fi
