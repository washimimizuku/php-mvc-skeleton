<?php

/**
 * A font class
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Layout_Font {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    public $family;
    public $style;
    public $size;
    
    public function __construct($family = '', $style = '', $size = 8) {
        $this->family   = $family;
        $this->style    = $style;
        $this->size     = $size;
    }
}

?>
