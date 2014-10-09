<?php

/**
 * Description of MySQL_Exception
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
 *
class MySQL_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 13000;
    const CONNECT               = 13001;
    const QUERY                 = 13002;
    const ELEMENT_EXISTS        = 13003;
    const ELEMENT_NOT_EXISTS    = 13004;
    const LINKED_ELEMENTS       = 13005;
    const NULL_RESSOURCE        = 13006;
}

?>
