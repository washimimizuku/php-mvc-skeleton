<?php

/**
 * Description of LDAP_OpenLDAP
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class LDAP_OpenLDAP {
    var $user;      // Bind DN.
    var $pass;      // Bind password.
    var $host;      // Server host name.
    var $port;      // Server port.
    var $basedn;    // Base DN of queries.
    var $linkid;    // Connection resource.

    //  Connect to server and authenticate.
    //  Returns the connection resource or FALSE if error.
    function connect_ldap_server()
    {
        // try to get instance of the connection if not , create instance
        $ds =  LDAP_Connector::getInstance($this->host,$this->port,$this->user,$this->pass)->getConnexion();

            /* 

                if (!isset($this->host))
                        $ds = @ldap_connect();
                elseif (!isset($this->port))
                        $ds = @ldap_connect($this->host);

                else
                        $ds = @ldap_connect($this->host, $this->port);


        if (!$ds)
            return FALSE;

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        if (!isset($this->user))
            $r = ldap_bind($ds);
        elseif (!isset($this->pass))
            $r = ldap_bind($ds, $this->user);
        else
            $r = ldap_bind($ds, $this->user, $this->pass);

        if (!$r) {
            ldap_close($ds);
            return FALSE;
            }
            echo $ds.'<br />';
            */
        return $ds;
    }

    function &ldap_search_entries($select, $filter)
    {
        //  Perform an LDAP search.
        $sr = @ldap_search($this->linkid, $this->basedn, $select, (array) $filter);
        if (!$sr) {
            return false;
        }
        
        return @ldap_get_entries($this->linkid, $sr);
    }

    //  Class constructor.
    function __construct($texturi)
    {
        $uri = parse_ldap_uri($texturi);
        if ($uri) {
            foreach ($uri as $name => $value) {
                $this->$name = $value;
            }

            $conn = $this->connect_ldap_server();

            if (!$conn) {
                throw new LDAP_Exception(null, LDAP_Exception::CONNECT);
            } else {
                $this->linkid = $conn;
            }
        }
    }


    //  Class destructor.
    function __destruct()
    {
        if (isset($this->linkid)) {
            //ldap_close($this->linkid);
            unset($this->linkid);
        }
    }


}

?>
