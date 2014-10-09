<?php

class MySQL_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 12000;
    const CONNECT               = 12001;
    const QUERY                 = 12002;
    const ELEMENT_EXISTS        = 12003;
    const ELEMENT_NOT_EXISTS    = 12004;
    const LINKED_ELEMENTS       = 12005;
    const NULL_RESSOURCE        = 12006;
}

?>
