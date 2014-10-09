<?php

/**
 * Description of MongoDB_Exception
 *
 * @author Nuno Barreto <nbarreto@gmail.com>
 */
class MongoDB_Exception extends WS_Exception {
    const UNKNOWN_ERROR         = 11000;
    const CONNECT               = 11001;
    const QUERY                 = 11002;
}

?>
