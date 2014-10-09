<?php

/**
 * Class Caching
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class Caching {
    public $cache;
    public $type;

    private static $instance; //static instance of the class

    public function __construct($type) {
        $config = ApplicationConfig::getInstance();
        $this->type = $type;

        if ($this->type == 'file') {
            require_once 'Cache/Lite.php';

            $cacheOptions = array(
                'cacheDir' => getenv('app_root').'/adserver/cache/Cache_Lite/',
                'automaticSerialization' => TRUE,
            );

            $this->cache = new Cache_Lite($cacheOptions);
        } else if ($this->type == 'ram') {
            $meminstance = new Memcached();
            $meminstance->addServer($config->memcacheServer, $config->memcachePort);

            $this->cache = $meminstance;
        }
    }

    public function get($id) {
        if ($this->type == 'file') {
            return $this->cache->get($id);
        } else if ($this->type == 'ram') {
            return $this->cache->get(md5($id));
        }
    }

    public function save($id, $data, $time=60) {
        if (gethostname() == 'YDSERVER-LX') {
            $time=20;
        }

        if ($this->type == 'file') {
            $this->cache->setLifeTime($time);

            return $this->cache->save($data, $id);
        } else if ($this->type == 'ram') {
            $this->cache->set(md5($id), $data, $time);
        }
    }

    /*
    * static function to prevent several instanciations of the class
    * return Caching
    */
    static public function getInstance($type = 'ram') {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($type);
        }

        return self::$instance;
    }

    public function __destruct() {
        self::$instance = null;
    }

    /*
     * no clone
     */
    private function __clone() {}

}


?>
