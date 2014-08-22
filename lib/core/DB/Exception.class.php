<?php

/**
 * Description of DB_Exception
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class DB_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 11000;
    const CONNECT               = 11001;
    const QUERY                 = 11002;
    const ELEMENT_EXISTS        = 11003;
    const ELEMENT_NOT_EXISTS    = 11004;
    const LINKED_ELEMENTS       = 11005;
    const NULL_RESSOURCE        = 11005;
}

?>
