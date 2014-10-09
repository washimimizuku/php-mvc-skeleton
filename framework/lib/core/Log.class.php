<?php

require_once(getenv('app_root').'/lib/core/ApplicationConfig.class.php');
require_once(getenv('app_root').'/lib/core/DBConnector.class.php');
require_once(getenv('app_root').'/lib/utils/Utils.class.php');

class Log {
    private $db;
    private $dbConnector;
    private $config;

    public function __construct() {
        $this->config = ApplicationConfig::getInstance();

        $this->dbConnector = DB_Connector::getInstance();
        $this->db = $this->dbConnector->db;
    }

    public function logOperation ($timestamp, $organization, $user, $module, $section, $operation, $description='', $field='', $oldValue='', $newValue='') {
        $query = "INSERT INTO ".$this->dbConnector->dblib."APPLOGOPE (OLTIMESTP, OLORGAN, OLUSER, OLMODULE, OLSECTION, OLOPERAT, OLDESC, OLFIELD, OLOLDVAL, OLNEWVAL, OLIP1, OLIP2) ";
        $query.= "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)  ";
        $values = array($timestamp, $organization, $user, $module, $section, $operation, $description, $field, $oldValue, $newValue, $this->config->ipReal, $this->config->ipProxy);

        $sth = $this->db->prepare($query);
        $result = $this->db->execute($sth, $values);

        return $result;
    }

    public function getLog ($organization='', $user='', $startDate='', $endDate='', $page=0, $quantity=0) {
        $config = ApplicationConfig::getInstance();
        $authorizations = new Authorizations();
        if (!$authorizations->checkAuthorization($config->o, $config->u, 'admin_logs', 'access')) {
            header('Location: '.$config->basePath);
            Utils::abort();
        }
        
        $values = array();
        $queryBegin  = "SELECT OLTIMESTP, OLORGAN, OLUSER, OLMODULE, OLSECTION, OLOPERAT, OLDESC, OLFIELD, OLOLDVAL, OLNEWVAL FROM ".$this->dbConnector->dblib."APPLOGOPE ";
        $queryMiddle = '';
        if ($organization) {
            $queryMiddle = "WHERE OLORGAN=? ";
            $values[] = $organization;
        }
        if ($user) {
            $queryMiddle .= "AND OLUSER=? ";
            $values[] = $user;
        }
        if ($startDate) {
            $queryMiddle .= "AND OLTIMESTP>? ";
            $values[] = $startDate;
       }
        if ($endDate) {
            $queryMiddle .= "AND OLTIMESTP<? ";
            $values[] = $endDate;
        }
        $queryEnd = 'ORDER BY OLTIMESTP DESC';

        $query = $queryBegin.$queryMiddle.$queryEnd;
        $arrayResult = $this->dbConnector->executePreparedReadQuery($query, $values, $page, $quantity);

        $queryCount = "SELECT COUNT(*) FROM ".$this->dbConnector->dblib."APPLOGOPE ".$queryMiddle;
        $numberRows = $this->dbConnector->getPreparedNumberOfRows($queryCount, $values);
        $arrayResult['numberOfRows'] = $numberRows;

        return $arrayResult;
    }
}

?>
