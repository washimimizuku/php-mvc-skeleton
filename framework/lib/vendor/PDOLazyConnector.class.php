<?php

/**
 * This class acts as a proxy to a PDO object, exposing all its original
 * methods and properties, but only connects on the first use (instead of
 * connecting on the constructor)
 *
 * NOTE: This DOES NOT expose static properties and methods. Please
 * keep using PDO for those.
 *
 * @author Edson Medina
 */

class PDOLazyConnector
{
	private $dsn;
	private $username;
	private $password;
	private $driver_options;
	private $dbh;

	public function __construct ($dsn, $username, $password, $driver_options = array ())
	{
		static $pdoHandlerCache = array ();

		$this->dsn = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->driver_options = $driver_options;

		// reuse connections
		if (!empty($pdoHandlerCache[$this->dsn])) {
			$this->dbh = $pdoHandlerCache[$this->dsn];
		}
	}

	public function __call ($function, $args)
	{
		// connect to db (first time only)
		$this->__init_dbh ();

		// invoke the original method
		return call_user_func_array (array($this->dbh, $function), $args);
	}

	public function __get ($property)
	{
		return $this->dbh->$property;
	}

	private function __init_dbh ()
	{
		// If db handler is not open yet, do it now
		if (empty ($this->dbh)) {
			$this->dbh = new PDO ($this->dsn, $this->username, $this->password, $this->driver_options);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			if ( defined( 'PDO::ATTR_EMULATE_PREPARES' ) )
			{
				$this->dbh->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
			}

			// Use UTF-8 in all connections
			$this->dbh->exec ('SET NAMES utf8');
		}
	}

	public function __sleep()
	{
		return array('dsn', 'username', 'password', 'driver_options');
	}

	public function __wakeup()
	{
	}
}

?>