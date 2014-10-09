<?php

/**
 * The parent class of all data classes
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
abstract class DataClass {
    /**
     * Child class.
     * @access private
     * @var DataClass
     */
    private $_caller;

    /**
     * MongoDB connector's instance.
     * @access protected
     * @var MongoDB_Connector
     */
    protected $_mongodbConnector;

    /**
     * MySQL DB read connector's instance.
     * @access protected
     * @var MySQL_Connector
     */
    protected $_mysqlConnectorRead;

    /**
     * MySQL DB write connector's instance.
     * @access protected
     * @var MySQL_Connector
     */
    protected $_mysqlConnectorWrite;

    /**
     * Config instance.
     * @access protected
     * @var ApplicationConfig
     */
    protected $_config;

    /**
     * Object table name
     * @access protected
     * @var string
     */
    protected $_sTable;

    /**
     * Object primary keys
     * @access protected
     * @var array
     */
    protected $_aPrimaryKeys;

    /**
     * Used to check combinations of primary keys (fill just if really needed)
     * @access protected
     * @var array
     */
    protected $_aPrimariesChecks;

    protected $_aOldPrimaryValues;

    /**
     * Set if primary key is autoincremented
     * @access protected
     * @var boolean
     */
    protected $_isAutoIncrement = false;

    /**
     * Database attributes
     * @access protected
     * @var array
     */
    protected $_dbAttributes;

    /**
     * Class constructor
     *
     * @access public
     * @param Mixed         $data SQL Resource or int
     * @return void
     */
    public function __construct($data = null)
    {
        $this->_mysqlConnectorRead  = $this->_getDefaultDBConnector('read');
        $this->_mysqlConnectorWrite = $this->_getDefaultDBConnector('write');
        $this->_config              = ApplicationConfig::getInstance();
        $this->_caller              = get_called_class();

        // check others args
        $args               = func_get_args();
        if (count($args)>1) {
            $data           = $args;
        }
        // init
        $this->init($data);
    }

    /**
     * Class destructor
     * @return void
     */
    function __destruct()
    {
        unset($this->_mysqlConnectorRead, $this->_mysqlConnectorWrite, $this->_config, $this->_caller);
    }

    /**
     * Initialize the object from datas
     *
     * @access public
     * @param mixed         $data SQL Resource or int
     * @return void
     */
    public function init($data)
    {
        switch (true) {
            case (null === $data) : {
                $this->constructEmpty();
                break;
            }
            case (is_scalar($data) OR (is_array($data) AND count($data) == count($this->_aPrimaryKeys))) : {
                $this->constructFromPrimaryKeys($data, false);
                break;
            }
            case is_object($data) OR is_array($data) : {
                $this->constructFromResource($data, null, false, false);
                break;
            }
            default : {
                break;
            }
        }

        $this->_saveOldPrimaryValues();

        $this->_initialize();
    }

    public function getPrimaryKeys(){
        return $this->_aPrimaryKeys;
    }

    /**
     * Class constructor using primary keys
     * @access public
     * @param scalar|array $primaryKeyValue    (there's barely no table with a unique primary key !)
     *                                          this value can be used like this:
     *                                          array(value1, value2) following the order of primary fields set in $this->_aPrimaryKeys
     *                                          or
     *                                          array('primaryKey1'=>value1, 'primaryKey2'=>value2)  Use the second method at your own risks...
     * @return DataClass
     */
    public function constructFromPrimaryKeys($primaryKeyValue, $wInit = true)
    {
        $primaryKeyValue = Utils::getMultiValues($primaryKeyValue, true);

        if ($primaryKeyValue) {
            $aWhere     = array();
            $aValues    = array();
            foreach ($primaryKeyValue as $key=>$value) {
                if (is_string($key) AND property_exists($this, $key)) {
                    $fieldName  = $key;
                } else if (is_array($this->_aPrimaryKeys) AND isset($this->_aPrimaryKeys[$key])) {
                    $fieldName  = $this->_aPrimaryKeys[$key];
                } else {
                    continue;
                }

                $aWhere[]       = 'trim('.$fieldName.') = trim(?)';
                $aValues[]      = $value;
            }

            if (count($aWhere) === count($this->_aPrimaryKeys)) {
                $sQuery = 'SELECT '.$this->_getFields(null, null, false).'
                            FROM '.$this->_sTable.'
                           WHERE '.implode(' AND ', $aWhere);

                $cache = Caching::getInstance();
				$primaryKey = is_array($primaryKeyValue) ? current($primaryKeyValue) : $primaryKeyValue;
                $cacheID = 'DataClass_constructFromPrimaryKeys_'.$this->_sTable.'_'.$this->id.'_'.$primaryKey.'_'.$wInit;

                $aResult = $cache->get($cacheID);

                if (!$aResult) {
                    $aResult = $this->_mysqlConnectorRead->executePreparedRead($sQuery, 0, 0, $aValues);
                    $cache->save($cacheID, $aResult, rand(55, 70) * 1); // About 1 minute
                }

                $aResult = $this->_mysqlConnectorRead->executePreparedRead($sQuery, 0, 0, $aValues);
                if (count($aResult)) {
                    $this->constructFromResource($aResult[0]);
                } else {
                    throw new MySQL_Exception(_("Can't construct specified").' '.$this->_caller.' ('.  json_encode($primaryKeyValue).')', MySQL_Exception::ELEMENT_NOT_EXISTS);
                }
            }
        }

        return $this;
    }

    /**
     * Class constructor using SQL resources
     * @access public
     * @param object    $resource       SQL resources Object
     * @param string    $sAlias         Alias used to decode resource
     * @return DataClass
     */
    public function constructFromResource($resource)
    {
        if ($resource) {
            $resource = (array)$resource;

            foreach ($resource AS $key => $value) {
                // If property has the same name as the database field
                if (property_exists($this, $key)) {
                    $propertyName = $key;
                // If property is camel cased compared to the database field
                /*} else if (property_exists($this, Utils::toCamelCase($key))) {
                    $propertyName = Utils::toCamelCase($key);*/
                // If property is different than the database field
                } else if (in_array($key, $this->_aFields)) {
                    $propertyName = array_search($key, $this->_aFields);
                }

                // numeric
                if (is_numeric($value) AND (strlen((int)$value) == strlen($value)) AND !strstr($value, '.')) {
                    $this->$propertyName = (int) $value;
                // others
                } else {
                    $this->$propertyName = $value;
                }
            }
        } else {
            throw new MySQL_Exception(_("Can't construct specified").' '.$this->_caller, MySQL_Exception::NULL_RESSOURCE);
        }

        return $this;
    }

    /**
     * Empty class constructor
     * @access protected
     * @return void
     */
    protected function constructEmpty() {}

    protected function _saveOldPrimaryValues()
    {
        foreach ($this->_aPrimaryKeys as $primaryKey) {
            $this->_aOldPrimaryValues[$primaryKey]  = $this->$primaryKey;
        }
    }

    /**
     * Return the default DB connector of the class
     * Please override this function to set your own DBConnector
     * @access protected
     * @return DB_Connector
     */
    protected function _getDefaultDBConnector($type = 'write')
    {
        return MySQL_Connector::getInstance($type);
    }

    /*************************************************************
     *
     *                  INSERT, DELETE, UPDATE
     *
     *************************************************************/

    /**
     * Add the object to database
     * @access public
     * @return boolean
     */
    public function add($getLastInsertedId = false)
    {
        if ($this->_checkElementExists()) {
            throw new MySQL_Connector(_("Can't add specified").' '.$this->_caller, MySQL_Connector::ELEMENT_EXISTS);
//            Utils::abort(_('Element already exists.'));
        }

        return $this->_addDB($getLastInsertedId);
    }

    /**
     * Update the object into database
     * @access public
     * @return boolean
     */
    public function update()
    {
        if (!$this->_checkElementExists()) {
            throw new MySQL_Connector(_("Can't update specified").' '.$this->_caller, MySQL_Connector::ELEMENT_NOT_EXISTS);
            //Utils::abort(_('A primary key of the element is empty.'));
        } else if ($this->_checkPrimaryKeysChanges() AND $this->_checkElementExists(true)) {
            throw new MySQL_Connector(_("Can't update specified").' '.$this->_caller, MySQL_Connector::ELEMENT_EXISTS);
        }

        return $this->_updateDB();
    }

    /**
     * Delete the object from database
     * @access public
     * @return boolean
     */
    public function delete()
    {
        if (!$this->_checkElementExists()) {
            throw new MySQL_Connector(_("Can't delete specified").' '.$this->_caller, MySQL_Connector::ELEMENT_NOT_EXISTS);
//            Utils::abort(_('Element already exists.'));
        }

        return $this->_deleteDB();
    }

    /**
     * @see add()
     *
     * @access protected
     * @return Boolean $success
     */
        protected function _addDB($getLastInsertedId = false )
    {
        $success    = false;

        $omit       = null;
        if ($this->_isAutoIncrement) {
            $omit   = $this->_aPrimaryKeys;
        }

        $aFields    = $this->_getFields(null, $omit, false, true);
        $aValues    = array();
        $sQuery     = "INSERT INTO ".$this->_sTable." (".implode(', ', $aFields).")\n VALUES (?".str_repeat(', ?', count($aFields)-1).")";

        foreach ($aFields AS $field) {
            if (isset($this->$field)) {
                $aValues[] = $this->$field;
            } else if (in_array($field, $this->_aFields)) {
                $varName = array_search($field, $this->_aFields);
                $aValues[] = $this->$varName;
            } else {
                $aValues[] = 'NULL';
            }
        }

        $result   = $this->_mysqlConnectorRead->executePreparedWrite($sQuery, $aValues);

        unset($aValues);

        if ($result) {

            // get the auto-incremented primaryKey if needed
            if ($this->_isAutoIncrement) {
                foreach ($result as $res) {
                    $this->constructFromResource($res);
                }
            }

            $success = true;
        }

        if($getLastInsertedId === true){
            return $this->_mysqlConnectorRead->getLastInsertID();
        }else{
            return $success;
        }
    }

    /**
     * @see update()
     *
     * @access protected
     * @return Boolean $success
     */
    protected function _updateDB()
    {
        $omit       = null;
        if ($this->_isAutoIncrement) {
            $omit   = $this->_aPrimaryKeys;
        }
        $success    = false;

        $aFields    = $this->_getFields(null, $omit, false, true);
        $aQuery     = array();
        $aValues    = array();
        $aWhere     = array();
        $sQuery     = " UPDATE ".$this->_sTable ." SET \n";


        foreach ($aFields AS $field) {
            $aQuery[]   = $field.' = ?';
            if (isset($this->$field)) {
                $aValues[]  = $this->$field;
            } else if (in_array($field, $this->_aFields)) {
                $varName = array_search($field, $this->_aFields);
                $aValues[] = $this->$varName;
            } else {
                $aValues[]  = 'NULL';
            }
        }

        foreach ($this->_aPrimaryKeys as $primaryKey) {
            $aWhere[]       = 'trim('.$primaryKey.') = trim(?)';
            $aValues[]      = $this->_aOldPrimaryValues[$primaryKey];
        }

        $sQuery  .= implode(", ", $aQuery);
        $sQuery  .= "\n WHERE ".implode(' AND ', $aWhere);

        $result   = $this->_mysqlConnectorWrite->executePreparedWrite($sQuery, $aValues);
        unset($aValues, $aQuery);

        if ($result) {
            $success = true;
        }

        return $success;
    }

    /**
     * @see delete()
     *
     * @access protected
     * @return Boolean $success
     */
    protected function _deleteDB()
    {
        $success = false;

        $aValues    = array();
        $aWhere     = array();
        $sQuery     = "DELETE FROM ".$this->_sTable;
        foreach ($this->_aPrimaryKeys as $primaryKey) {
            $aWhere[]       = 'trim('.$primaryKey.') = trim(?)';
            $aValues[]      = $this->$primaryKey;
        }
        $sQuery  .= "\n WHERE ".implode(' AND ', $aWhere);

        $result = $this->_mysqlConnectorWrite->executePreparedWrite($sQuery, $aValues);
        unset($aValues);

        if ($result) {
            $success = true;
        }
        return $success;
    }

    /**
     * Returns all fields formated for use in SQL queries
     *
     * @access public
     * @static
     * @see DataClass->_getFields()
     * @param string        $sAlias         To prefix fields with the alias ( e.g: ALIAS.field)
     * @param scalar|array  $omit           Fields those will be left off
     * @param bool          $bSuffix        Allow to suffix fields with the alias or className (e.g : field__ALIAS or field__className)
     * @param bool          $bArray         if true The return value will be an array
     * @return string|array
     */
    public static function getFields($sAlias = null, $omit = null, $bSuffix = false, $bArray = false)
    {
        $instance = new static();
        return $instance->_getFields($sAlias, $omit, $bSuffix, $bArray);
    }

    /**
     * Returns all fields formated for use in SQL queries
     *
     * @access protected
     * @param string        $sAlias         To prefix fields with the alias ( e.g: ALIAS.field)
     * @param scalar|array  $omit           Fields those will be left off
     * @param bool          $bSuffix        Allow to suffix fields with the alias or className (e.g : field__ALIAS or field__className)
     * @param bool          $bArray         if true The return value will be an array
     * @return string|array
     */
    protected function _getFields($sAlias = null, $omit = null, $bSuffix = false, $bArray = false)
    {
        $aOmit = (!is_array($omit)) ? array($omit) : $omit;
        $sAlias = (string) $sAlias;
        $suffix = $sAlias;

        // get database fields
        $aFields = $this->_getDBAttributes();

        // delete omitted fields
        $aFields = array_diff($aFields, $aOmit);

        if ($sAlias OR $bSuffix) {

            if (!$sAlias AND $bSuffix) {
                $suffix = $this->_caller;
            }

            $sAlias = ($sAlias) ? $sAlias.'.' : $sAlias;

            foreach ($aFields AS &$sField) {
                $sField = $sAlias.$sField.' AS '.$sField;
                if ($bSuffix) {
                    $sField .= Utils::DELIM_DOUBLE_UNDERSCORE.$suffix;
                }
            }
        }

        return ($bArray) ? $aFields : implode(', ', $aFields);
    }

    /**
     * Returns all database attributes of the class
     * /!\  By default, this function get all public attributes
     *      Please override this function if all publics attributes are not database fields !
     *      e.g: return array('attribute1', 'attribute2', 'attribute5');
     *
     * @access protected
     * @return array
     */
    protected function _getDBAttributes()
    {
        if (!$this->_dbAttributes) {
            $ref = new ReflectionObject($this);
            $attributes = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
            $attributes = Utils::array_child($attributes, 'name');

            // Check for cases where table name is diferent from atribute name
            foreach ($attributes as $attKey => $attribute) {
                if (isset($this->_aFields[$attribute])) {
                    $attributes[$attKey] = $this->_aFields[$attribute];
                }
            }

            $this->_dbAttributes = $attributes;
        }
        return $this->_dbAttributes;
    }

    /**
     * Check if primary keys are setted
     * @access protected
     * @return boolean
     */
    protected function _checkPrimaryKeys()
    {
        $primaryKeysSetted = true;
        if (is_array($this->_aPrimaryKeys)) {
            foreach ($this->_aPrimaryKeys as $primaryKey) {
                if (!$this->$primaryKey) {
                    $primaryKeysSetted = false;
                    break;
                }
            }
        }

        return $primaryKeysSetted;
    }

    /**
     * Check if primary keys have been modified
     */
    protected function _checkPrimaryKeysChanges()
    {
        $isChanged          = false;
        if (is_array($this->_aPrimaryKeys) AND is_array($this->_aOldPrimaryValues)) {
            foreach ($this->_aPrimaryKeys as $primaryKey) {
                if ($this->$primaryKey != $this->_aOldPrimaryValues[$primaryKey]) {
                    $isChanged  = true;
                    break;
                }
            }
        }

        return $isChanged;
    }

    /**
     * Check if element exists in database
     * @access protected
     * @return boolean
     */
    protected function _checkElementExists($wUpdatedAttributes = false)
    {
        $isElementExists    = false;
        $aWhere             = array();
        $aValues            = array();
        $sWhere             = '';
        if (is_array($this->_aPrimaryKeys)) {
            if (is_array($this->_aPrimariesChecks) AND count($this->_aPrimariesChecks)) {

                foreach ($this->_aPrimariesChecks as $primariesCheck) {
                    $aWhereChecks       = array();
                    $isOK = true;

                    if (is_array($primariesCheck) AND count($primariesCheck)) {
                        foreach ($primariesCheck as $primaryKey) {
                            $aWhereChecks[]   = 'trim(upper('.$primaryKey.')) = trim(upper(?))';
                            if (!$wUpdatedAttributes AND isset($this->_aOldPrimaryValues[$primaryKey]) AND $this->_aOldPrimaryValues[$primaryKey]) {
                                if (!$this->_aOldPrimaryValues[$primaryKey]) {
                                    $isOK    = false;
                                    break;
                                }
                                $aValues[]  = $this->_aOldPrimaryValues[$primaryKey];

                            } else {
                                if (!$this->$primaryKey) {
                                    $isOK    = false;
                                    break;
                                }
                                $aValues[]  = $this->$primaryKey;
                            }
                        }
                    }

                    if (count($aWhereChecks) AND $isOK) {
                        $aWhere[] = '('.implode(" AND\n ", $aWhereChecks).')';
                    }
                }

                if (count($aWhere)) {
                    $sWhere = "\nWHERE ".implode(" OR\n ", $aWhere);
                }
            } else {
                foreach ($this->_aPrimaryKeys as $primaryKey) {
                    $aWhere[]   = 'trim(upper('.$primaryKey.')) = trim(upper(?))';
                    if (!$wUpdatedAttributes AND isset($this->_aOldPrimaryValues[$primaryKey]) AND $this->_aOldPrimaryValues[$primaryKey]) {
                        $aValues[]  = $this->_aOldPrimaryValues[$primaryKey];
                    } else {
                        $aValues[]  = $this->$primaryKey;
                    }
                }

                if (count($aWhere)) {
                    $sWhere = "\nWHERE ".implode(" AND\n ", $aWhere);
                }
            }

        }
        $sQuery     = "SELECT 'True' FROM ".$this->_sTable;
        $sQuery    .= $sWhere;
        //Utils::log(Utils::sprintf_array(str_replace('?','%s', $sQuery), $aValues));

        $aResult    = $this->_mysqlConnectorRead->executePreparedRead($sQuery, 0, 0, $aValues);
        if (count($aResult)) {
            $isElementExists = true;
        }

        return $isElementExists;
    }

    /**
     * Init method called by constructor
     * @access protected
     * @return void
     */
    protected function _initialize() {}

    /**
     * Returns a URL query string with all id values
     *
     * @access public
     * @return String $queryString
     */
    public function getIDQueryString()
    {
        $aPrimaryKeys = array();
        foreach ($this->_aPrimaryKeys as $primaryKey) {
            $aPrimaryKeys[] = $primaryKey.'='.trim($this->$primaryKey);
        }

        $queryString = join('&', $aPrimaryKeys);

        return $queryString;
    }

    /**
     * Returns a string with all id values glued by |
     * @access public
     * @return string
     */
    public function getPrimaryKeysString($glue = '|')
    {
        $primaryString              = '';
        $aPrimaryKeysValues         = array();
        foreach ($this->_aPrimaryKeys as $primaryKey) {
            $aPrimaryKeysValues[]   = trim($this->$primaryKey);
        }

        $primaryString              = implode($glue, $aPrimaryKeysValues);

        return $primaryString;
    }

    /**
     * Returns table name
     * @access public
     * @return string
     */
    public static function getTableName(){
        $instance = new static();
        return $instance->_sTable;
    }
}

?>
