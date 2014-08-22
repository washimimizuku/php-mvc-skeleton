<?php

/**
 * A pdf format class
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Layout {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    CONST ALIGN_LEFT    = 'L';
    CONST ALIGN_CENTER  = 'C';
    CONST ALIGN_RIGHT   = 'R';
    CONST ALIGN_JUSTIFY = 'J';
    
    CONST BORDER_TOP    = 'T';
    CONST BORDER_BOTTOM = 'B';
    CONST BORDER_RIGHT  = 'R';
    CONST BORDER_LEFT   = 'L';
    CONST BORDER_ALL    = '1';
    CONST BORDER_NONE   = '0';
    
    /** @var Layout_Color */
    private $fillColor;
    
    /** @var Layout_Color */
    private $textColor;
    
    /** @var Layout_Font */
    private $font;
    
    /** @var Layout_Color */
    private $drawColor;
    
    /** @var string */
    private $border = self::BORDER_ALL;
    
    private $lineWidth;
    
    /** @var string */
    private $align;
    
    public function __destruct() {        
        unset($this->fillColor, $this->textColor, $this->font, $this->drawColor, $this->lineWidth, $this->border, $this->align);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                             GETTERS & SETTERS                              //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Returns the fill color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout_Color
     */
    public function getFillColor()
    {
        return $this->fillColor;
    }
    
    /**
     * Set the fill color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout_Color $fillColor 
     * @return Void
     */
    public function setFillColor(Layout_Color $fillColor)
    {
        if ($fillColor) {
            $this->fillColor = $fillColor;
        }
    }
    
    /**
     * Returns the text color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout_Color
     */
    public function getTextColor()
    {
        return $this->textColor;
    }
    
    /**
     * Set the text color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout_Color $textColor 
     * @return Void
     */
    public function setTextColor(Layout_Color $textColor)
    {
        if ($textColor) {
            $this->textColor = $textColor;
        }
    }
    
    /**
     * Returns the font props
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout_Font
     */
    public function getFont()
    {
        return $this->font;
    }
    
    /**
     * Set the font props
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout_Font $font
     * @return Void
     */
    public function setFont(Layout_Font $font)
    {
        if ($font) {
            $this->font = $font;
        }
    }
    
    /**
     * Returns the draw color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout_Color
     */
    public function getDrawColor()
    {
        return $this->drawColor;
    }
    
    /**
     * Set the draw color
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout_Color $drawColor 
     * @return Void
     */
    public function setDrawColor(Layout_Color $drawColor)
    {
        if ($drawColor) {
            $this->drawColor = $drawColor;
        }
    }
    
    /**
     * Returns the border width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return float
     */
    public function getLineWidth()
    {
        return $this->lineWidth;
    }
    
    /**
     * Set the border width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param float $lineWidth 
     * @return Void
     */
    public function setLineWidth($lineWidth)
    {
        if ($lineWidth) {
            $this->lineWidth = $lineWidth;
        }
    }
    
    /**
     * Returns the cells border
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return string
     */
    public function getBorder()
    {
        return $this->border;
    }
    
    /**
     * Set the cells border
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param string $border 
     * @return Void
     */
    public function setBorder($border)
    {
        if ($border) {
            $this->border = $border;
        }
    }
    
    /**
     * Returns the cells alignment
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }
    
    /**
     * Set the cells alignment
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param string $align 
     * @return Void
     */
    public function setAlign($align)
    {
        if ($align) {
            $this->align = $align;
        }
    }
}

?>
