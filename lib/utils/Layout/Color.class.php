<?php
/**
 * A color class
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Layout_Color {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    public $r       = null;
    public $g       = null;
    public $b       = null;
    public $a       = null;
    
    public $grayLvl = null;
    
    public function __construct($aRGBA = null, $grayLvl = null) {
        if (is_array($aRGBA) AND count($aRGBA) >= 3) {
            $this->r        = UtilsManager::get8bitInt($aRGBA[0]);
            $this->g        = UtilsManager::get8bitInt($aRGBA[1]);
            $this->b        = UtilsManager::get8bitInt($aRGBA[2]);
            
            // alpha optional
            $this->a        = (isset($aRGBA[3]))?UtilsManager::get8bitInt($aRGBA[3]):null;
        } else if ($grayLvl !== null) {
            
            $this->grayLvl  = UtilsManager::get8bitInt($grayLvl);
        } else {
            $this->r        = 0;
            $this->g        = 0;
            $this->b        = 0;
        }
        
    }
    
    /**
     * Returns the color used(grayLevel or rgba)
     * @return array|int
     */
    public function getValue()
    {
        $color = null;
        if ($this->r !== null AND $this->g !== null AND $this->b !== null) {
            $color = array($this->r, $this->g, $this->b);
        } else if ($this->grayLvl !== null) {
            $color = $this->grayLvl;
        }
        
        return $color;
    }
}

?>
