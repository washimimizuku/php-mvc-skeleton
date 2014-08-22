<?php

/**
 * This class is used to instanciate only one Ldap connection
 *
 * @author Yann JAMAR
 */
class LDAP_Connector {
    
    //Attributes 
    
    private static  $instance; // instance of the ldap connexion
    protected       $connexion;// connection ressource
    
    public function __construct($host = '', $port='' , $user = '', $pass = ''){
        //  Connect to server and authenticate.
        //  Returns the connection resource or FALSE if error.
        if (!isset($host)) {
            $this->connexion = @ldap_connect();
        } elseif (!isset($port)) {
            $this->connexion = @ldap_connect($host);
        } else {
            $this->connexion = @ldap_connect($host, $port);    
        }
        
        if (!$this->connexion) {
            $this->connexion = false;
        }
        
        ldap_set_option($this->connexion, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connexion, LDAP_OPT_REFERRALS, 0);
        
        if (!isset($user)) {
            $r = ldap_bind($this->connexion);
        } elseif (!isset($pass)) {
            $r = ldap_bind($this->connexion, $user);
        } else {
            $r = ldap_bind($this->connexion, $user, $pass);
        }
        
        if (!$r) {
            ldap_close($this->connexion);
            $this->connexion = false;
        }
    }
    
    /*
     * static function to prevent several instanciations of the class
     *
     */
    static public function getInstance($host = '' , $port = '' , $user = '' , $pass = '') {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self($host  , $port  , $user  , $pass );
        }
        return self::$instance;
    }
    
    
    /*
     * no clone
     */
    private function __clone() {}
        /*
         * getter of the ldap connection
         */
        public function getConnexion() {
            return $this->connexion;
        }
    }

?>
