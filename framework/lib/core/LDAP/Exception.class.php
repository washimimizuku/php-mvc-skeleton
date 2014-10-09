<?php

/**
 * Description of LDAP_Exception
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class LDAP_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 11000;
    const CONNECT               = 11001;
    const QUERY                 = 11002;
    const ELEMENT_EXISTS        = 11003;
    const ELEMENT_NOT_EXISTS    = 11004;
    const NULL_RESSOURCE        = 11005;
    const NO_OBJECT_CLASS       = 11006;
}

?>
