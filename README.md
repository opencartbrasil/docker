<p align="center">
    <a href="https://www.opencartbrasil.com.br/" title="OpenCart Brasil">
        <img src="https://user-images.githubusercontent.com/630535/121281061-b1a1cb00-c8ad-11eb-9c58-82accf999857.png" alt="Logo OpenCart Brasil">
    </a>
</p>

# OpenCart Brasil no Docker

![Docker Pulls](https://img.shields.io/docker/pulls/opencartbrasil/opencartbrasil)
![Docker Stars](https://img.shields.io/docker/stars/opencartbrasil/opencartbrasil)

## Imagens da versão OpenCart Brasil

- `1.5` e `latest`
- `1.5-apache`
- `1.5-fpm`
- `1.5-alpine`

Você pode usar tags para isso. Por exemplo:

```bash
docker run -p 80:80 opencartbrasil/opencartbrasil:latest
```

Sendo que para funcionar corretamente, é necessário realizar antes a conexão com o banco de dados conforme o exemplo abaixo:

```bash
# Conexão com banco de dados
docker run --network some-name \
    -e MYSQL_DATABASE=opencartbrasil \
    -e MYSQL_USER=store \
    -e MYSQL_PASSWORD=store \
    -e MYSQL_RANDOM_ROOT_PASSWORD=yes \
    mysql:5.7;

# OpenCart Brasil
docker run --network some-name -p 80:80 opencartbrasil/opencartbrasil:latest
```

## Instalação automática

É possível iniciar um *container* com o OpenCart Brasil instalado e configurado, para isso, é necessário preencher no mínimo as variáveis de ambiente abaixo:

 - OCBR_DB_USER
 - OCBR_DB_PASS
 - OCBR_DB_DATABASE
 - OCBR_ADMIN_USER
 - OCBR_ADMIN_PASS
 - OCBR_ADMIN_EMAIL
 - OCBR_HTTP_SERVER

Conforme o exemplo abaixo:

 ```bash
 docker run --network host \
    -p 80:80 \
    -e OCBR_DB_USER=store \
    -e OCBR_DB_PASS=store \
    -e OCBR_DB_DATABASE=opencartbrasil \
    -e OCBR_ADMIN_USER=admin \
    -e OCBR_ADMIN_PASS=123456 \
    -e OCBR_ADMIN_EMAIL=webmaster@localhost \
    -e "OCBR_HTTP_SERVER=http://localhost/" \
    opencartbrasil/opencartbrasil:latest
 ```

Assim o processo de instalação e configuração do OpenCart Brasil será realizado automaticamente.

**Observação:** Se a conexão com o banco de dados não for estabelecida em até 2 minutos, o processo de instalação automática falhará.

## Instalação via *Docker Compose* ou *Docker Stack Deploy*

Arquivo **ocbr.yaml** com OpenCart Brasil e MySQL:

```yaml
version: '3'

networks:
    app-network:

services:
  app:
    image: opencartbrasil/opencartbrasil:latest
    container_name: app
    volumes:
      - ./src:/var/www/html
    networks:
      - app-network
    ports:
      - 80:80
    environment:
      OCBR_HTTP_SERVER: "http://localhost/"
      OCBR_DB_HOST: "db"
      OCBR_DB_USER: "store"
      OCBR_DB_PASS: "store"
      OCBR_DB_DATABASE: "opencartbrasil"
      OCBR_ADMIN_USER: "admin"
      OCBR_ADMIN_PASS: "123456"
    depends_on:
      - db

  db:
    image: mysql:5.7
    container_name: db
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_USER: store
      MYSQL_PASSWORD: store
      MYSQL_DATABASE: opencartbrasil
    networks:
      - app-network
```

Execute no terminal `docker stack deploy -c ocbr.yaml opencartbrasil` ou `docker-compose up`

## Variáveis de ambiente

Ao iniciar a instância do OpenCart Brasil, é possível pré-definir algumas configurações.

Seguem as variáveis de ambientes suportadas:

**Geral**

| Nome | Descrição | Padrão |
| ---- | --------- | ------ |
| OCBR_HTTP_SERVER | Informa o domínio da loja. É necessário informar o "scheme" da URL. Ex.: **http://localhost/** |  |

**Banco de Dados**

| Nome | Descrição | Padrão |
| ---- | --------- | ------ |
| OCBR_DB_HOST | Endereço do servidor de Banco de Dados | 0.0.0.0 |
| OCBR_DB_USER | Usuário do Banco de Dados | root |
| OCBR_DB_PASS | Senha do Banco de Dados | |
| OCBR_DB_DATABASE | Nome do Banco de Dados. É necessário que seja criado antes da instalação. | opencartbrasil |
| OCBR_DB_PORT | Porta para conexão com Banco de Dados | 3306 |
| OCBR_DB_PREFIX | Prefixo das tabelas | ocbr_ |

**Usuário Administrativo**

| Nome | Descrição | Padrão |
| ---- | --------- | ------ |
| OCBR_ADMIN_USER | Usuário do painel administrativo |  |
| OCBR_ADMIN_PASS | Senha do painel administrativo |  |
| OCBR_ADMIN_EMAIL | E-mail do usuário administrativo | webmaster@localhost |

**Envio de E-mail**

| Nome | Descrição | Padrão |
| ---- | --------- | ------ |
| MAIL_DRIVER | Forma de envio de e-mails (*smtp*, *mail*) | mail |
| MAIL_PARAMETER | Parâmetros adicionais para o *driver mail*. Veja mais informações na [documentação](https://www.php.net/manual/pt_BR/function.mail.php) |  |
| MAIL_SERVER | Servidor de e-mail SMTP |  |
| MAIL_USER | Usuário do servidor SMTP |  |
| MAIL_PASS | Senha do usuário do servidor SMTP |  |
| MAIL_PORT | Porta de conexão para SMTP |  |
| MAIL_TIMEOUT | Tempo máximo, em segundos, de conexão com o servidor SMTP | 30 |
| MAIL_ADDITIONAL_MAILS | E-mails adicionais. Utilize vírgula para separá-los |  |

## Configurando o NGINX

Ao utilizar a versão com [*php-fpm*](https://www.php.net/manual/pt_BR/install.fpm.php), é possível utilizar o [Nginx](https://www.nginx.com/) como servidor web. Para um bom funcionamento, utilize a configuração abaixo:

```
location / {
  try_files $uri $uri/ @opencart;
}

location @opencart {
  rewrite ^/sitemap.xml$ /index.php?route=extension/feed/google_sitemap last;
  rewrite ^/googlebase.xml$ /index.php?route=extension/feed/google_base last;
  rewrite ^/system/storage/(.*) /index.php?route=error/not_found last;

  rewrite ^/(.+)$ /index.php?_route_=$1 last;
}

location ~* (\.twig|\.tpl|\.ini|\.log|(?<!robots)\.txt)$ {
  deny all;
}
```

## Licença

Este projeto é de código aberto licenciado sob a [GPL v3](https://github.com/opencartbrasil/docker/blob/main/LICENSE).
