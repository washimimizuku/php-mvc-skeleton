<?php
/**
 * Description of Class_Exception
 * This is a dummy class, to be used as a template whenever we need to create a new class
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 */
class Class_Exception extends WS_Exception {
    const DELETION_FORBIDDEN_RECORD_LIVE            = 11001;
    const DELETION_FORBIDDEN_RECORD_HISTORY         = 11002;
    const DELETION_FORBIDDEN_RECORD_ARCHIVE         = 11003;
    const DELETION_FORBIDDEN_FILTERS                = 11004;
    const DELETION_FORBIDDEN_PERMISSIONS            = 11005;
}

?>
