<?php

// HTTP
define('HTTP_SERVER', rtrim(getenv('OCBR_HTTP_SERVER'), '/') . '/admin/');
define('HTTP_CATALOG', rtrim(getenv('OCBR_HTTP_SERVER'), '/') . '/');

// HTTPS
define('HTTPS_SERVER', rtrim(getenv('OCBR_HTTP_SERVER'), '/') . '/admin/');
define('HTTPS_CATALOG', rtrim(getenv('OCBR_HTTP_SERVER'), '/') . '/');

// DIR
define('DIR_APPLICATION', '/var/www/html/admin/');
define('DIR_IMAGE', '/var/www/html/image/');
define('DIR_SYSTEM', '/var/www/html/system/');
define('DIR_CATALOG', '/var/www/html/catalog/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', getenv('OCBR_DB_DRIVER'));
define('DB_HOSTNAME', getenv('OCBR_DB_HOST'));
define('DB_USERNAME', getenv('OCBR_DB_USER'));
define('DB_PASSWORD', getenv('OCBR_DB_PASS'));
define('DB_DATABASE', getenv('OCBR_DB_DATABASE'));
define('DB_PORT', getenv('OCBR_DB_PORT'));
define('DB_PREFIX', getenv('OCBR_DB_PREFIX'));

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
