<?php

/**
 * Class MongoDB_Connector
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class MongoDB_Connector {
    public $dbname;

    public $connection;
    public $db;
    public $collection;

    private static $instance; //static instance of the class

    public function __construct($dbname, $collection)
    {
        $config = ApplicationConfig::getInstance();

        if ($dbname) {
            $this->dbname = $dbname;
        } else {
            $this->dbname = $config->mongodbDatabase;
        }

        // connect
        /*$this->connection = new MongoClient("mongodb://".$config->mongodbHost.':'.$config->mongodbPort,
                                            array('username'    => $config->mongodbUser,
                                                  'password'    => $config->mongodbPassword,
                                                  'db'          => $this->dbname,
                                                  'replicaSet'  => 'adserver'));
        $this->connection->setReadPreference(MongoClient::RP_PRIMARY_PREFERRED, array());*/

        // connect
        $this->connection = new MongoClient("mongodb://".$config->mongodbHost.':'.$config->mongodbPort,
                                            array('username'    => $config->mongodbUser,
                                                  'password'    => $config->mongodbPassword,
                                                  'db'          => $this->dbname));

        $this->setDatabase($this->dbname);

        if ($collection) {
            $this->setCollection($collection);
        }
    }

    public function setDatabase($c)
    {
        $this->db = $this->connection->selectDB($c);
    }

    public function setCollection($c)
    {
        $this->collection = $this->db->selectCollection($c);
    }

    public function insert($f)
    {
        $this->collection->insert($f);
        $id = $f['_id'];

        return $id;
    }

    public function get($f = array())
    {
        $cursor = $this->collection->find($f);

        $k = array();
        $i = 0;

        while( $cursor->hasNext())
        {
            $k[$i] = $cursor->getNext();
            $i++;
        }

        return $k;
    }

    public function find($f = array())
    {
        $cursor = $this->collection->find($f);

        return $cursor;
    }

    public function update($f1, $f2)
    {
        $this->collection->update($f1, $f2);
    }

    /*public function getAll()
    {
        $cursor = $this->collection->find();
        foreach ($cursor as $id => $value)
        {
            echo "$id: ";
            var_dump( $value );
        }
    }*/

    public function delete($f, $one = FALSE)
    {
        $c = $this->collection->remove($f, $one);
        return $c;
    }

    public function ensureIndex($args)
    {
        return $this->collection->ensureIndex($args);
    }

    public function __destruct() {
        self::$instance = null;
    }

    /*
    * static function to prevent several instanciations of the class
    * return MongoDB_Connector
    */
    static public function getInstance($dbname = 'adserverTracking', $collection='') {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($dbname, $collection);
        }

        return self::$instance;
    }

    /*
     * no clone
     */
    private function __clone() {}

}

?>
