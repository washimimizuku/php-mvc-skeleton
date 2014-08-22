<?php

/**
 * Description of LDAP_Exception
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class LDAP_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 12000;
    const CONNECT               = 12001;
    const QUERY                 = 12002;
    const ELEMENT_EXISTS        = 12003;
    const ELEMENT_NOT_EXISTS    = 12004;
    const NULL_RESSOURCE        = 12005;
    const NO_OBJECT_CLASS       = 12006;
}

?>
