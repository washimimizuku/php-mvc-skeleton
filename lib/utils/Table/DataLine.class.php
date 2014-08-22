<?php

/**
 * A Table dataLine Class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Table_DataLine {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    /** @var integer */
    protected $height;
    
    /** @var Table_Cell */
    protected $aCells;
    
    /** @var Table */
    protected $table;
    
    /** @var Layout */
    protected $layout;
    
    public function __construct($height = null) {
        $this->setHeight($height);
    }
    
    public function __destruct() {        
        unset($this->table, $this->layout, $this->aCells, $this->height);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                             GETTERS & SETTERS                              //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Get line's height
     * @return Integer $height 
     */
    public function getHeight()
    {
        $height = $this->height;
        if (!$height) {
            $height = $this->getTable()->getDefaultLineHeight();
        }
        return $height;
    }
    
    /**
     * Set line's height
     * @param Integer $height 
     * @return void 
     */
    public function setHeight($height)
    {
        if ($height AND is_int($height)) {
            $this->height = $height;
        }
    }
    
    /**
     * Returns the cells array
     * @return Array 
     */
    public function getCells()
    {
        return $this->aCells;
    }
    
    /**
     * Add a cell to the line
     * @param Table_Cell $cell 
     * @return void
     */
    public function addCell(Table_Cell $cell)
    {
        if ($cell) {
            $cell->setParent($this);
            $this->aCells[] = $cell;
        }
    }
    
    /**
     * Set all cells of the line 
     * @param Array $aCells     Table_Cell instances 
     * @return void
     */
    public function setCells($aCells)
    {
        if (is_array($aCells) AND count($aCells)) {
            $this->aCells = array();
            foreach ($aCells as &$cell) {
                $this->addCell($cell);
            }
        }
    }
    
    /**
     * Returns line's table reference
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Table 
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * Set line's table reference
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Table $line 
     * @return Void
     */
    public function setTable(Table $table)
    {
        if ($table) {
            $this->table = $table;
        }
    }
    
    /**
     * Returns line's layout or default layout
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout 
     */
    public function getLayout($forceParent = false)
    {
        $layout = $this->layout;
        if ($layout === null OR $forceParent) {
            $layout = $this->getTable()->getDefaultLayout();
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
     * Print the table to a pdf file
     * @param PDF       $pdf 
     * @param boolean   $wHeaderTopPage
     * return int Height of the line
     */
    public function PDFexport(PDF &$pdf, $wHeaderTop = false, $isLast = false, Table_DataLine $nextLine = null)
    {
        if (null !== $pdf) {
            // init
            $aCells         = $this->getCells();
            $height         = $this->getHeight();
            
            $nbCells        = count($aCells);
            if(is_array($aCells) AND $nbCells) {
                
                // get the layout and alignment
                $defaultAlign   = 'C';
                $defaultLayout  = $this->getLayout();
                if ($defaultLayout !== null) {
                    $pdf->applyLayout($defaultLayout);
                    if ($defaultLayout->getAlign() == null) {
                        $defaultAlign  = $this->getLayout(true)->getAlign();
                    }
                }
                
                // get the tablewidth
                $tableWidth = $this->getTable()->getWidth();
                if ($tableWidth === 0) {
                    $tableWidth = $pdf->getPageWidth();
                }
                
                list($height, $nbLines) = $this->calculateLineHeight($pdf, $tableWidth);
                
                // Issue a page break first if needed
                if ($pdf->CheckPageBreak($height, false) AND $wHeaderTop) {
//                    $pdf->Cell($tableWidth,0,'','T');
                    
                    $pdf->CheckPageBreak($height);
                    
                    $header = $this->getTable()->getHeader();
                    
                    if ($header) {
                        $headerHeight   = $header->PDFexport($pdf);
                        $pdf->Ln($headerHeight);
                    }
                    
                    // re-apply default layout !
                    $pdf->applyLayout($defaultLayout);
                }
                
                if ($nextLine) {
                    $currY          = $pdf->GetY();
                    $pdf->Ln($height);
                    list($nextLineHeight, $nbLinesNext) = $nextLine->calculateLineHeight($pdf);
                    
                    if ($pdf->CheckPageBreak($nextLineHeight, false)) {
                        $isLast = true;
                    }
                    
                    $pdf->setY($currY);
                }
                
                /** @var TableCell $cell */
                foreach ($aCells as $key=>&$cell) {
                    $lineHeight     = $height/$nbLines[$key];
                    
                    $cell->PDFexport($pdf, $lineHeight, $tableWidth, $defaultAlign, $defaultLayout, $this->getTable()->getWidthCalculationType(), $isLast);
                    unset($cell);
                }
            }
            
            return $height;
        }
    }
    
    public function calculateLineHeight($pdf, $tableWidth = null)
    {
        $aCells             = $this->getCells();
        $nbCells            = count($aCells);
        $oneLineHeight      = $this->getHeight(); // useful for multiLine height calculation
        
        if (!$tableWidth) {
            $tableWidth     = $this->getTable()->getWidth();
            if ($tableWidth === 0) {
                $tableWidth = $pdf->getPageWidth();
            }
        }
        
         // get the height of the line
        $nb         = 0;
        $nbLines    = array();
        for ($i = 0; $i<$nbCells; $i++) {
            $nbLines[$i]    = UtilsManager::getCellNbLines($pdf, $aCells[$i], $tableWidth, $this->getTable()->getWidthCalculationType());

            $max = max($nb, $nbLines[$i]);
            if ($nb < $max) {
                $nb         = $max;
            }
        }

        if ($nb > 1) {
            $oneLineHeight = $oneLineHeight - 1;
        }
        return array($nb*$oneLineHeight, $nbLines);
    }
}

?>
