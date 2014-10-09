<?php

/**
 * Global configurations file
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */

// Are we in the development server?
$isDev = false;
if (gethostname() == 'DEV_HOST') {
    error_reporting(E_ALL);
    $isDev = true;
}

// General Configurations
$frameworkConfig = array();

if ($isDev) {
    # Development  configurations

    # Mysql
    $frameworkConfig['mysql'] = array();
    $frameworkConfig['mysql']['database']       = 'DB_NAME';
    $frameworkConfig['mysql']['host']           = array();
    $frameworkConfig['mysql']['host']['write']  = array('DB_WRITE_HOST1', 'DB_WRITE_HOST2');
    $frameworkConfig['mysql']['host']['read']   = array('DB_READ_HOST1',  'DB_READ_HOST2');
    $frameworkConfig['mysql']['port']           = 'DB_PORT';
    $frameworkConfig['mysql']['user']           = 'DB_USER';
    $frameworkConfig['mysql']['password']       = 'DB_PASSWORD';

    # Mongodb
    $frameworkConfig['mongodb'] = array();
    $frameworkConfig['mongodb']['database']     = 'MONGODB_COLLECTION';
    $frameworkConfig['mongodb']['host']         = 'MONGODB_HOST';
    $frameworkConfig['mongodb']['port']         = 'MONGODB_PORT';
    $frameworkConfig['mongodb']['user']         = 'MONGODB_USER';
    $frameworkConfig['mongodb']['password']     = 'MONGODB_PASSWORD';

    # Memcache
    $frameworkConfig['memcache']['server']      = 'MEMCACHE_HOST';
    $frameworkConfig['memcache']['port']        = 'MEMCACHE_PORT';

    # RabbitMQ
    $frameworkConfig['rabbitmq']['host']        = 'RABBITMQ_HOST';
    $frameworkConfig['rabbitmq']['port']        = 5672; // RABBITMQ_PORT
    $frameworkConfig['rabbitmq']['user']        = 'RABBITMQ_USER';
    $frameworkConfig['rabbitmq']['pass']        = 'RABBITMQ_PASSWORD';
    $frameworkConfig['rabbitmq']['vhost']       = '/';

    # Paths
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
        $frameworkConfig['rootPath']            = preg_replace('#[/\\\\]www[/\\\\].*?$#', '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']).'/www';
    } else {
        $frameworkConfig['rootPath']            = '';
    }

} else {
    # Production configurations

    # Mysql
    $frameworkConfig['mysql'] = array();
    $frameworkConfig['mysql']['database']       = 'DB_NAME';
    $frameworkConfig['mysql']['host']           = array();
    $frameworkConfig['mysql']['host']['write']  = array('DB_WRITE_HOST1', 'DB_WRITE_HOST2');
    $frameworkConfig['mysql']['host']['read']   = array('DB_READ_HOST1',  'DB_READ_HOST2');
    $frameworkConfig['mysql']['port']           = 'DB_PORT';
    $frameworkConfig['mysql']['user']           = 'DB_USER';
    $frameworkConfig['mysql']['password']       = 'DB_PASSWORD';

    # Mongodb
    $frameworkConfig['mongodb'] = array();
    $frameworkConfig['mongodb']['database']     = 'MONGODB_COLLECTION';
    $frameworkConfig['mongodb']['host']         = 'MONGODB_HOST';
    $frameworkConfig['mongodb']['port']         = 'MONGODB_PORT';
    $frameworkConfig['mongodb']['user']         = 'MONGODB_USER';
    $frameworkConfig['mongodb']['password']     = 'MONGODB_PASSWORD';

    # Memcache
    $frameworkConfig['memcache']['server']      = 'MEMCACHE_HOST';
    $frameworkConfig['memcache']['port']        = 'MEMCACHE_PORT';

    # RabbitMQ
    $frameworkConfig['rabbitmq']['host']        = 'RABBITMQ_HOST';
    $frameworkConfig['rabbitmq']['port']        = 5672; // RABBITMQ_PORT
    $frameworkConfig['rabbitmq']['user']        = 'RABBITMQ_USER';
    $frameworkConfig['rabbitmq']['pass']        = 'RABBITMQ_PASSWORD';
    $frameworkConfig['rabbitmq']['vhost']       = '/';

    # Paths
    $frameworkConfig['rootPath']                = 'http://tk.ydads.org';
}

# Salt for encryption and decryption
$frameworkConfig['rijndael']['salt']            = 'THIS SHOULD BE A VERY SMART SALT';

?>
