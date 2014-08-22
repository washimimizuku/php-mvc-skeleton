<?php
require_once 'DB.php';

class DB_Connector {
    public $db;
    public $dblib;
    public $dbname;
   
    private static  $instance;//static instance of the class

    public function __construct() {
        setlocale(LC_CTYPE, "fr_FR.UTF8");

        $config = ApplicationConfig::getInstance();

        $dsn = 'odbc://'.$config->dbuser.':'.$config->dbpassword.'@'.$config->dbhost;
        $options = array(
            'debug'       => 2,
            'portability' => DB_PORTABILITY_ALL,
        );

        $db =& DB::connect($dsn, $options);
        if (PEAR::isError($db)) {
            throw new DB_Exception($db->getMessage(), DB_Exception::CONNECT);
//            Utils::abort($db->getMessage());
        } else {
            $db->setFetchMode(DB_FETCHMODE_ASSOC);

            $this->db = $db;
        }
        
        $organization   = LDAPOrganization_Manager::getOrganization($config->o);
        $this->dblib    = $organization->dsDatabase.'.';
        $this->dbname   = $organization->dsDatabase;
    }

    /**
     * Generic Query Executor
     *
     * This method was created to enclose the procedures necessary for the
     * execution of a database select in DB2 using Pear::DB. Most of the code is
     * done to correct a bug in the DB2 connector, which always returns the
     * first item of the list, no matter what page you ask.
     *
     * @param string $query Query to be executed
     * @param integer $page Page of the list requested
     * @param integer $quantity Number of rows requested
     * @return array $arrayResult Array with rows retrieved
     */
    public function executeQueryGeneric($query, $page=0, $quantity=0) {
        if (!(is_numeric($page) && is_numeric($quantity))){
            Utils::abort(_('Page or Quantity is not a numeric value'));
        }
        
        $result = '';
        if ($quantity > 0) {
            $result = $this->db->limitQuery($query, $page*$quantity, $quantity);
        } else {
            $result = $this->db->query($query);
        }
        if (PEAR::isError($result)) {
            throw new DB_Exception($result->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($result->getMessage()." ".$query);
        }

        $arrayResult = array();
        while ($row = $result->fetchRow()) {
            array_push($arrayResult, $row);
        }

        return $arrayResult;
    }
    
    public function executeReadGeneric($query, $page=0, $quantity=0) {
        if (!(is_numeric($page) && is_numeric($quantity))){
            Utils::abort(_('Page or Quantity is not a numeric value'));
        }
        $result = '';
        if ($quantity > 0) {
            $result = $this->db->limitQuery($query, $page*$quantity, $quantity);
        } else {
            $result = $this->db->query($query);
        }
        if (PEAR::isError($result)) {
            throw new DB_Exception($result->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($result->getMessage()." ".$query);
        }

        $arrayResult = array();
        while ($row = $result->fetchRow()) {
            array_push($arrayResult, $row);
        }

        return $arrayResult;
    }
    
    public function executeWriteGeneric($query) {
        $result = $this->db->query($query);

        if (PEAR::isError($result)) {
            throw new DB_Exception($result->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($result->getMessage()." ".$query);
        }
        
        return $result;
    }

    public function executePreparedReadQuery($query, $values, $page=0, $quantity=0, $freePrepare = false) {
        if (!(is_numeric($page) && is_numeric($quantity))){
            Utils::abort(_('Page or Quantity is not a numeric value'));
        }
        $sth = $this->db->prepare($query);

        $result = '';
        if ($quantity > 0) {
            $result = $this->db->limitQuery($query, $page*$quantity, $quantity, $values);
        } else {
            $result = $this->db->execute($sth, $values);
        }
        
        if ($freePrepare) {
            $this->db->freePrepared($sth);
        }
        
        if (PEAR::isError($result)) {
            throw new DB_Exception($result->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($result->getMessage()." ".$query);
        }

        $arrayResult = array();
        while ($row = $result->fetchRow()) {
            array_push($arrayResult, $row);
        }
        $result->free();
        
        unset ($sth, $result);
        
        return $arrayResult;
    }

    public function executePreparedWriteQuery($query, $values) {
        $sth = $this->db->prepare($query);
        $result = $this->db->execute($sth, $values);

        if (PEAR::isError($result)) {
            throw new DB_Exception($result->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($result->getMessage()." ".$query);
        }
        
        return $result;
    }

    public function getNumberOfRows($query) {
        //var_dump($query);
        $resultCount = $this->db->query($query);

        if (PEAR::isError($resultCount)) {
            throw new DB_Exception($resultCount->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($resultCount->getMessage()." ".$query);
        }

        $resultCount->fetchInto($row);
        $number = array_shift($row);

        return $number;
    }
    
    public function getPreparedNumberOfRows($query, $values) {
        $sth = $this->db->prepare($query);
        $resultCount = $this->db->execute($sth, $values);

        if (PEAR::isError($resultCount)) {
            throw new DB_Exception($resultCount->getMessage()." ".$query, DB_Exception::QUERY);
//            Utils::abort($resultCount->getMessage()." ".$query);
        }

        $resultCount->fetchInto($row);
        $number = array_shift($row);

        return $number;
    }
    
    public function __destruct() {
        $this->db->disconnect();
        self::$instance = null;
    }
    
    /*  
    * static function to prevent several instanciations of the class
    * return DB_Connector
    */
    static public function getInstance() {
        
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
       return self::$instance;
    }
  
  
    /*
     * no clone
     */
    private function __clone() {}
    
    public function checkTableExistence($tableName)
    {
        $isExists = false;
        if ($tableName) {
            $aTablesNames = $this->getDBTables($tableName);
            if (count($aTablesNames)) {
                $isExists = true;
            }
        }
        
        return $isExists;
    }
    
    public function getDBTables($tableName = '')
    {
        $aTablesNames = array();
        
        $sqlRqt             = "SELECT table_name FROM sysibm.tables";
        $sqlRqt             .= " WHERE table_type = 'BASE TABLE' AND TABLE_SCHEMA = '".$this->dbname."'";
        $aValues            = array();
        if ($tableName) {
            $sqlRqt         .= " AND upper(table_name) LIKE UPPER(?)";
            $aValues[]      = $tableName;
        }
        $sqlRqt             .= " ORDER BY table_name ASC";
        
        // execute query
        $arrayResult = $this->executePreparedReadQuery($sqlRqt, $aValues);
        
        // instanciate objects
        foreach ($arrayResult as $res) {
            
            $aTablesNames[]     = $res['table_name'];

        }
        
        return $aTablesNames;
    }
}

?>
