<?php

class MySQL_Connector {
    public $dbname;
    public $db;
    public $hostType;

    private static $instance; //static instance of the class

    public function __construct($type = 'write', $dbname = '', $dsn = array()) {
        $config = ApplicationConfig::getInstance();
        if (!$dsn) {

            if ($dbname) {
                $this->dbname = $dbname;
            } else {
                $this->dbname = $config->mysqlDatabase;
            }

            $host = '';
            if ($type == 'read') {
                $host = $config->mysqlHost['read'][array_rand($config->mysqlHost['read'])];
                $this->hostType = 'read';
            } else {
                $host = $config->mysqlHost['write'][array_rand($config->mysqlHost['write'])];
                $this->hostType = 'write';
            }

            $dsn = array(
                'phptype'  => 'mysql',
                'hostspec' => $host,
                'username' => $config->mysqlUser,
                'password' => $config->mysqlPassword,
                'database' => $this->dbname,
                'charset' => 'utf8'
            );

        } else {
            if ($dbname) {
                $this->dbname = $dbname;
                $dsn['database'] = $dbname;
            }
        }

        $options = array(
			PDO::ATTR_PERSISTENT => true
		);

		$driverServerName = "mysql:dbname={$dsn['database']};host={$dsn['hostspec']}";
		try {
			$this->db = new PDOLazyConnector($driverServerName, $dsn['username'], $dsn['password'], $options);
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
    }

    public function executeRead($query, $page = 0, $quantity = 0, $params = array()) {
        if (!(is_numeric($page) && is_numeric($quantity))){
            throw new Exception(_('Page or Quantity is not a numeric value.'));
        }

        if ($quantity > 0) {
            $query .= " LIMIT :offset, :quantity";
        }

		$sth = $this->db->prepare($query);

		if ($quantity > 0)
		{
			$offset =  $page*$quantity;
			$sth->bindParam(":offset", $offset, PDO::PARAM_INT);
			$sth->bindParam(":quantity", $quantity, PDO::PARAM_INT);
		}

		if (!empty($params))
		{
			foreach ($params as $key => $value)
			{
				$sth->bindValue($key + 1, $value);
			}
		}

		try
		{
			$sth->execute();
		}
		catch(PDOException $e) {
			throw new Tonic\Exception( $e->getMessage() , (int)$e->getCode() );
		}

		$arrayResult = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $arrayResult;
    }

	public function executePreparedRead($query, $page = 0, $quantity = 0, $values = array(), $dataTypes = array())
	{
		return $this->executeRead($query,$page, $quantity, $values);
	}

    public function executeWrite($query)
	{
		try
		{
			$result = $this->db->exec($query);
		}
		catch(PDOException $e) {
			throw new Tonic\Exception( $e->getMessage() , (int)$e->getCode() );
		}

        return $result;
    }

    /**
     * To do insert with multiple values, just "insert values (?,?),(?,?)" and $values is array with all the values
     *
     * @param type $query
     * @param type $values
     * @param type $dataTypes
     * @return type
     * @throws MySQL_Exception
     */
    public function executePreparedWrite($query, $values = array(), $dataTypes = array())
	{
		$sth = $this->db->prepare($query);

		if (!empty($values))
		{
			foreach ($values as $key => $value)
			{
				$sth->bindValue($key + 1, $value);
			}
		}

		try
		{
			$result = $sth->execute();
		}
		catch(PDOException $e) {
			throw new Tonic\Exception( $e->getMessage() , (int)$e->getCode() );
		}

		return $result;
    }

	public function getLastInsertID()
	{
		try
		{
			$id = $this->db->lastInsertId();
		}
		catch(PDOException $e) {
			throw new Tonic\Exception( $e->getMessage() , (int)$e->getCode() );
		}

		return $id;
	}



	/**
     * Execute multiple queries with transactions
     *
     * @param type $queries
     * @return boolean
     * @throws MySQL_Exception
     */
    public function executeMultiplePreparedQueriesWithTransactions($queries)
	{
        // Open a transaction
		if ($this->db->beginTransaction())
		{
			foreach ($queries as $query)
			{
				$sql    = $query['sql'];
				$values = $query['values'];

				try
				{
					$result = $this->executePreparedWrite($sql, $values);

					if (!$result)
					{
						throw new PDOException("Something went wrong with a transaction! SQL:\n\n" . $sql);
					}
				}
				catch (PDOException $e)
				{
					$this->db->rollback();
					throw new Tonic\Exception($e->getMessage(), (int)$e->getCode());
				}
			}

			if ($this->db->inTransaction())
			{
				return $this->db->commit();
			} else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
    }

    public function __destruct() {
		$this->db = null;
		self::$instance = null;
    }

    /*
    * static function to prevent several instanciations of the class
    * return DB_Connector
    */
    static public function getInstance($type = 'write', $dbname = '', $dsn = array()) {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($type, $dbname, $dsn);
        } else {
            $test = self::$instance;
            if ($test->hostType != $type) {
                self::$instance = new self($type, $dbname, $dsn);
            }
        }

        return self::$instance;
    }


    /*
     * no clone
     */
    private function __clone() {}

}


?>
