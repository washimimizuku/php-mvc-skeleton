<?php

/**
 * A Table Cell Class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Table_Cell {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    /** @var string */
    protected $content;
    
    /** @var Integer */
    protected $width;
    
    /** @var TableDataLine */
    protected $parent;
    
    /** @var Layout */
    protected $layout;
    
    public function __construct($content, $width) {
        
        // uses setters if we need to control values
        $this->setContent($content);
        $this->setWidth($width);
    }
    
    public function __destruct() {
        unset($this->layout, $this->parent, $this->content, $this->width);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                             GETTERS & SETTERS                              //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Returns cell's content
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return String 
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Set cell's content
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param string $content 
     * @return Void
     */
    public function setContent($content)
    {
        if ($content) {
            $this->content = $content;
        }
    }
    
    /**
     * Returns cell's width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Integer 
     */
    public function getWidth()
    {
        return $this->width;
    }
    
    /**
     * Set cell's width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Integer $width 
     * @return Void
     */
    public function setWidth($width)
    {
        if (is_int($width) AND $width >= 0) {
            $this->width = $width;
        }
    }
    
    /**
     * Returns cell's alignment
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return string 
     */
    public function getAlign()
    {
        return $this->getLayout(true)->getAlign();
    }
    
    /**
     * Returns cell's line reference
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return TableDataLine 
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Set cell's line reference
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param mixed $parent 
     * @return Void
     */
    public function setParent($parent)
    {
        if ($parent) {
            $this->parent = $parent;
        }
    }
    
    /**
     * Returns line's layout or default layout
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout 
     */
    public function getLayout($checkParent = false)
    {
        $layout = $this->layout;
        if ($layout === null AND $checkParent) {
            $layout = $this->getParent()->getLayout(true);
        }
        return $layout;
    }
    
    /**
     * Set line's layout
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout $layout 
     * @return Void
     */
    public function setLayout(Layout $layout)
    {
        if ($layout) {
            $this->layout = $layout;
        }
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  EXPORTS                                   //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Print the cell to a pdf file
     * @param PDF $pdf 
     */
    public function PDFexport(PDF &$pdf, $lineHeight, $tableWidth, $defaultAlign, $defaultLayout, $widthCalculationType = Table::WIDTH_TYPE_PERCENT, $isLast = false)
    {
        if (null !== $pdf) {
            $x = $pdf->getX();
            $y = $pdf->getY();
            
            $width  = $this->getWidth();
            if ($widthCalculationType == Table::WIDTH_TYPE_PERCENT) {
                $width  = $width*$tableWidth/100;
            }
            
            $layout         = $this->getLayout();
            $border         = Layout::BORDER_ALL;
            if ($layout !== null) {
                $pdf->applyLayout($layout);
                $border     = $layout->getBorder();
            } else if ($defaultLayout) {
                $border     = $defaultLayout->getBorder();
            }
            
            if ($isLast AND $border !== Layout::BORDER_NONE) {
                $border     .= Layout::BORDER_BOTTOM;
            }
            
            $align          = $this->getAlign();
            if ($align === null) {
                $align      = $defaultAlign;
            }
            
            $pdf->MultiCell($width, $lineHeight, utf8_decode($this->getContent()), $border, $align, true);
            $pdf->setXY($x+$width, $y);
            
             // back to default layout
            if ($layout !== null) {
                $pdf->applyLayout($defaultLayout);
            }
        }
    }
}
?>
