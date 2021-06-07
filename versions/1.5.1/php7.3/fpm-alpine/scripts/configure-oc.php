<?php

define('DIR_APPLICATION', '/var/www/html');

require_once DIR_APPLICATION . '/system/helper/general.php';

function library($class) {
    $file = DIR_APPLICATION . '/system/library/' . str_replace('\\', '/', strtolower($class)) . '.php';

    if (is_file($file)) {
        include_once $file;

        return true;
    }
    
    return false;
}

spl_autoload_register('library');
spl_autoload_extensions('.php');

date_default_timezone_set('UTC');

class Installer {
    private $db = null;

    public function __construct() {
        try {
            $this->db = new DB(
                getenv('OCBR_DB_DRIVER'),
                getenv('OCBR_DB_HOST'),
                getenv('OCBR_DB_USER'),
                getenv('OCBR_DB_PASS'),
                getenv('OCBR_DB_DATABASE'),
                getenv('OCBR_DB_PORT')
            );
        } catch (Exception $e) {
            echo "Falha ao tentar conexão com o banco de dados.", PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
            echo "Instalação automática ignorada.", PHP_EOL, PHP_EOL;

            $this->db = null;
        }
    }

    public function is_valid() {
        return $this->db !== null;
    }

    public function is_data_valid() {
        $result = true;

        $fields = array(
            'OCBR_HTTP_SERVER',
            'OCBR_DB_DRIVER',
            'OCBR_DB_HOST',
            'OCBR_DB_USER',
            'OCBR_DB_PASS',
            'OCBR_DB_DATABASE',
            'OCBR_DB_PORT',
            'OCBR_DB_PREFIX',
            'OCBR_ADMIN_USER',
            'OCBR_ADMIN_PASS',
            'OCBR_ADMIN_EMAIL',
        );

        foreach ($fields as $field) {
            if (empty(trim(getenv($field)))) {
                $result = false;
                echo "A variável de ambiente $field é obrigatória", PHP_EOL;
            }
        }

        return $result;
    }

    public function is_mail_valid() {
        $result = true;

        $fields = array(
            'MAIL_DRIVER',
            'MAIL_SERVER',
            'MAIL_USER',
            'MAIL_PASS',
            'MAIL_PORT',
            'MAIL_TIMEOUT',
        );

        foreach ($fields as $field) {
            if (empty(trim(getenv($field)))) {
                $result = false;
            }
        }

        return $result;
    }

    public function is_installed($data) {
        $table_setting = $this->db->query('SHOW TABLES LIKES "' . $data['db_prefix'] . 'setting"');

        return $table_setting->num_rows > 0;
    }

    public function setup_db(array $data) {
        if (!$this->is_data_valid()) {
            exit(1);
        }

        if ($this->is_installed($data)) {
            echo "A loja já está instalada";
            return;
        }

        $file = DIR_APPLICATION . '/install/opencart.sql';

        if (!file_exists($file)) {
            exit('Não foi possível carregar o arquivo sql: ' . $file);
        }

        $lines = file($file);

        if ($lines) {
            $sql = '';

            foreach ($lines as $line) {
                if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
                    $sql .= $line;

                    if (preg_match('/;\s*$/', $line)) {
                        $sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $sql);
                        $sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $data['db_prefix'], $sql);
                        $sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql);

                        $this->db->query($sql);

                        $sql = '';
                    }
                }
            }

            $this->db->query("SET CHARACTER SET utf8");
            $this->db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");
            $this->db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $this->db->escape($data['username']) . "', salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', firstname = 'Fulano', lastname = 'de Tal', email = '" . $this->db->escape($data['email']) . "', status = '1', date_added = NOW()");
            $this->db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
            $this->db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $this->db->escape($data['email']) . "'");
            $this->db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
            $this->db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $this->db->escape(token(1024)) . "'");
            $this->db->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");
            $this->db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET username = 'Default', `key` = '" . $this->db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");
            $api_id = $this->db->getLastId();
            $this->db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
            $this->db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "'");
            $this->db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'FAT-" . date('Y') . "-' WHERE `key` = 'config_invoice_prefix'");
        }
    }

    public function setup_mail(array $data) {
        if (!$this->is_mail_valid($data)) {
            exit(1);
        }

        $db_prefix = $data['db_prefix'];
        unset($data['db_prefix']);

        foreach ($data as $key => $value) {
            $this->db->query('DELETE FROM `' . $db_prefix . 'setting` WHERE `code` = "' . $this->db->escape($key) . '"');
            $this->db->query('
                INSERT INTO `' . $db_prefix . 'setting`
                SET `store_id` = 0,
                    `code` = "config",
                    `key` = "' . $this->db->escape($key) . '",
                    `value` = "' . $this->db->escape($value) . '";
            ');
        }
    }
}

$installer = new Installer();

if ($installer->is_valid()) {
    $installer->setup_db([
        'db_driver' => getenv('OCBR_DB_DRIVER'),
        'db_hostname' => getenv('OCBR_DB_HOST'),
        'db_username' => getenv('OCBR_DB_USER'),
        'db_password' => getenv('OCBR_DB_PASS'),
        'db_database' => getenv('OCBR_DB_DATABASE'),
        'db_port' => getenv('OCBR_DB_PORT'),
        'db_prefix' => getenv('OCBR_DB_PREFIX'),
        'username' => getenv('OCBR_ADMIN_USER'),
        'password' => getenv('OCBR_ADMIN_PASS'),
        'email' => getenv('OCBR_ADMIN_EMAIL'),
        'http_server' => getenv('OCBR_HTTP_SERVER'),
    ]);

    $installer->setup_mail([
        'db_prefix' => getenv('OCBR_DB_PREFIX'),
        'config_mail_engine' => getenv('MAIL_DRIVER'),
        'config_mail_parameter' => getenv('MAIL_PARAMETER'),
        'config_mail_smtp_hostname' => getenv('MAIL_SERVER'),
        'config_mail_smtp_username' => getenv('MAIL_USER'),
        'config_mail_smtp_password' => getenv('MAIL_PASS'),
        'config_mail_smtp_port' => getenv('MAIL_PORT'),
        'config_mail_smtp_timeout' => getenv('MAIL_TIMEOUT'),
        'config_mail_alert' => getenv('MAIL_ADDITIONAL_MAILS'),
    ]);
}