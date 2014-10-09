<?php

/**
 * Description of WS_Exception
 *
 * Exception Prefix codes               <br/>
 * <b>10</b> Core                       <br/>
 * <b>11</b> LDAP                       <br/>
 * <b>12</b> MongoDB                    <br/>
 * <b>13</b> Mysql                      <br/>
 * 
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class WS_Exception extends Exception {
    const NULL_PARAM            = 10001;
    const UNEXPECTED_VALUE      = 10002;
    const FORBIDDEN             = 10003;
    const CLASS_NOT_EXISTS      = 10004;
    const NO_SESSION            = 10005;

    protected static $_aCodes;

    /**
        * Class Constructor
        *
        * @param string $msg
        * @param int $code
        * @param array|scalar $params
        * @param Exception $previous
        *
        * @return void
        */
    public function __construct($msg = '', $code = 0, Exception $previous = null)
    {
        // loading assets
        self::_loadAssets();
        parent::__construct($msg, $code, $previous);
    }

    /**
     * Get the string Code of the exception
     *
     * @access public
     * @return string
     */
    public function getStringCode()
    {
        $caller = get_called_class();
        return self::$_aCodes[$caller][$this->code];
    }

    /**
     * Load needed assets of exceptions
     * @access public
     * @static
     * @return void
     */
    private static function _loadAssets()
    {
        $caller = get_called_class();

        if (!isset(self::$_aCodes[$caller])) {
            self::$_aCodes[$caller] = array_flip(Utils::getConstants($caller));
        }
    }
}

?>
