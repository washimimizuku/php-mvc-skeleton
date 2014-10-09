<?php

/**
 * Description of Class_Exception
 * This is a dummy class, to be used as a template whenever we need to create a new class
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class Class_Exception extends WS_Exception {
    const DELETION_FORBIDDEN_RECORD_LIVE            = 20001;
    const DELETION_FORBIDDEN_RECORD_HISTORY         = 20002;
    const DELETION_FORBIDDEN_RECORD_ARCHIVE         = 20003;
    const DELETION_FORBIDDEN_FILTERS                = 20004;
    const DELETION_FORBIDDEN_PERMISSIONS            = 20005;
}

?>
