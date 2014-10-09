<?php

define('ROOT_PATH', getenv('app_root'));

require_once(getenv('app_root').'/framework/configs/adserver.config.php');
require_once(getenv('app_root').'/framework/lib/ClassAutoloader.class.php');

/**
 * Class ApplicationConfig
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class ApplicationConfig {
    public $mysqlDatabase;
    public $mysqlHost;
    public $mysqlPort;
    public $mysqlUser;
    public $mysqlPassword;

    public $mongodbDatabase;
    public $mongodbHost;
    public $mongodbPort;
    public $mongodbUser;
    public $mongodbPassword;

    public $memcacheServer;
    public $memcachePort;

    public $rabbitHost;
    public $rabbitPort;
    public $rabbitUser;
    public $rabbitPass;
    public $rabbitVhost;

    public $rootPath;

    public $rijndaelSalt;

    /** @var ApplicationConfig $instance*/
    private static $instance; //static instance of the class

    function __construct(){
        date_default_timezone_set("UTC");

        // init log's timer reference
        if (gethostname() == 'DEV_HOST') {
            DebugLog::getInstance();
        }

        global $frameworkConfig;

        $this->mysqlDatabase    = $frameworkConfig['mysql']['database'];
        $this->mysqlHost        = $frameworkConfig['mysql']['host'];
        $this->mysqlPort        = $frameworkConfig['mysql']['port'];
        $this->mysqlUser        = $frameworkConfig['mysql']['user'];
        $this->mysqlPassword    = $frameworkConfig['mysql']['password'];

        $this->mongodbDatabase  = $frameworkConfig['mongodb']['database'];
        $this->mongodbHost      = $frameworkConfig['mongodb']['host'];
        $this->mongodbPort      = $frameworkConfig['mongodb']['port'];
        $this->mongodbUser      = $frameworkConfig['mongodb']['user'];
        $this->mongodbPassword  = $frameworkConfig['mongodb']['password'];

        $this->memcacheServer   = $frameworkConfig['memcache']['server'];
        $this->memcachePort     = $frameworkConfig['memcache']['port'];

        $this->rabbitHost       = $frameworkConfig['rabbitmq']['host'];
        $this->rabbitPort       = $frameworkConfig['rabbitmq']['port'];
        $this->rabbitUser       = $frameworkConfig['rabbitmq']['user'];
        $this->rabbitPass       = $frameworkConfig['rabbitmq']['pass'];
        $this->rabbitVhost      = $frameworkConfig['rabbitmq']['vhost'];

        $this->rootPath         = $frameworkConfig['rootPath'];

        $this->rijndaelSalt     = $frameworkConfig['rijndael']['salt'];

        unset($GLOBALS['adServerConfig']);
    }

    /**
      * Get the singleton instance
      * (construct if needed)
      * @access public
      * @return ApplicationConfig $instance
      */
    static public function getInstance() {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*
     * no clone
     */
    private function __clone() {}
}

?>
