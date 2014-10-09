<?php

/**
 * Description of MongoDB_Exception
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class MongoDB_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 12000;
    const CONNECT               = 12001;
    const QUERY                 = 12002;
}

?>
