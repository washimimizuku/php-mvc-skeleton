<?php

/**
 * A Table Class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Table {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    CONST WIDTH_TYPE_PIXEL      = 'PIXEL';
    CONST WIDTH_TYPE_PERCENT    = 'PERCENT'; // Default
    
    /** @var Table_Header */
    protected $header;
    
    /** @var Array */
    protected $aDataLines;
    
    /** @var integer */
    protected $width;
    
    /** @var string */
    protected $widthCalculationType;
    
    /** @var Layout */
    protected $defaultHeaderLayout;
    
    /** @var Layout */
    protected $defaultLayout;
    
     /** @var Int */
    protected $defaultLineHeight;
    
    public function __construct() {
        
        // uses setters if we need to control values
        $this->setWidth(0);
        $this->setWidthCalculationType(self::WIDTH_TYPE_PERCENT);
        $this->setDefaultLineHeight(5);
    }
    
    public function __destruct() {
        unset($this->header, $this->aDataLines, $this->width, $this->widthCalculationType, $this->defaultHeaderLayout, $this->defaultLayout, $this->defaultLineHeight);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                             GETTERS & SETTERS                              //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Returns the header line
     * @return Table_Header 
     */
    public function getHeader()
    {
        return $this->header;
    }
    
    /**
     * Set a dataLine as Header
     * @return Table_Header 
     */
    public function setHeader(Table_Header $header)
    {
        if ($header) {
            $header->setTable($this);
            $this->header = $header;            
        }
    }
    
    /**
     * Returns the datalines array
     * @return Array 
     */
    public function getDataLines()
    {
        return $this->aDataLines;
    }
    
    /**
     * Add a dataline to the table
     * @param TableLine $dataLine 
     * @return void
     */
    public function addDataLine(Table_DataLine $dataLine)
    {
        if ($dataLine) {
            $dataLine->setTable($this);
            $this->aDataLines[] = $dataLine;
        }
    }
    
     /**
     * Set datalines 
     * @param Array $aDataLines     TableDataLine instances 
     * @return void
     */
    public function setDataLines($aDataLines)
    {
        if (is_array($aDataLines) AND count($aDataLines)) {
            $this->aDataLines = array();
            foreach ($aDataLines as $dataLine) {
                $this->addDataLine($dataLine);
            }
        }
    }
    
    /**
     * Returns table's width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Integer 
     */
    public function getWidth()
    {
        return $this->width;
    }
    
    /**
     * Set table's width
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Integer $width 
     * @return Void
     */
    public function setWidth($width)
    {
        if (is_int($width) AND $width >=0) {
            $this->width = $width;
        }
    }
    
    /**
     * Returns width calculation type
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return string 
     */
    public function getWidthCalculationType()
    {
        return $this->widthCalculationType;
    }
    
    /**
     * Set width calculation type
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param string $type (PERCENT or PIXEL)
     * @return Void
     */
    public function setWidthCalculationType($type)
    {
        if ($type AND in_array($type, array(self::WIDTH_TYPE_PIXEL, self::WIDTH_TYPE_PERCENT))) {
            $this->widthCalculationType = $type;
        }
    }
    
    /**
     * Returns header Layout default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout 
     */
    public function getDefaultHeaderLayout()
    {
        return $this->defaultHeaderLayout;
    }
    
    /**
     * Set header Layout default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout $layout
     * @return Void
     */
    public function setDefaultHeaderLayout(Layout $layout)
    {
        if ($layout) {
            $this->defaultHeaderLayout = $layout;
        }
    }
    
    /**
     * Returns Layout default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout 
     */
    public function getDefaultLayout()
    {
        return $this->defaultLayout;
    }
    
    /**
     * Set Layout default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Layout $layout
     * @return Void
     */
    public function setDefaultLayout(Layout $layout)
    {
        if ($layout) {
            $this->defaultLayout = $layout;
        }
    }
    
    /**
     * Returns line height default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return int 
     */
    public function getDefaultLineHeight()
    {
        return $this->defaultLineHeight;
    }
    
    /**
     * Set line height default
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Int $lineHeightDefault
     * @return Void
     */
    public function setDefaultLineHeight($lineHeightDefault)
    {
        if ($lineHeightDefault) {
            $this->defaultLineHeight = $lineHeightDefault;
        }
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  EXPORTS                                   //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Print the table to a pdf file
     * @param PDF $pdf 
     */
    public function PDFexport(PDF &$pdf, $wHeader = true, $wHeaderTopPage = false, $woEndingLN = false)
    {
        if (null !== $pdf) {
            // init
            $header     = $this->getHeader();
            $aDataLines = $this->getDataLines();
            if (null !== $header AND $wHeader) {
                $height = $header->PDFExport($pdf);
                $pdf->Ln($height);
            }
            
            if (is_array($aDataLines) AND count($aDataLines)) {
                /** @var Table_DataLine $dataLine*/
                foreach ($aDataLines as $key => &$dataLine) {
                    
                    $isLast         = false;
                    if ($aDataLines[$key] === end($aDataLines)) {
                        $isLast     = true;
                        $nextLine = $aDataLines[$key];
                    } else {
                        $nextLine   = $aDataLines[$key+1];
                    }
                    $height         = $dataLine->PDFExport($pdf, $wHeaderTopPage, $isLast, $nextLine);
                    $pdf->Ln($height);
                }
                
            }
            unset($header, $this->header, $aDataLines, $this->aDataLines);
            
//            $pdf->Cell($this->getWidth(),0,'','T');
            if (!$woEndingLN) $pdf->Ln();
        }
    }
}

?>
