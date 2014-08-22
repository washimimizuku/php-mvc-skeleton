<?php

/**
 * A Table Mutli-Cell Class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Table_MultiCell extends Table_Cell {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    /** @var Table_Cell */
    protected $aCells;
    
    public function __construct($width = 0) {
        $this->setWidth($width);
    }
    
    public function __destruct() {
        unset($this->aCells);
        
        parent::__destruct();
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                             GETTERS & SETTERS                              //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Returns the cells array
     * @return Array 
     */
    public function getCells()
    {
        return $this->aCells;
    }
    
    /**
     * Add a cell to the MultiCell
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
     * Set all cells of the MultiCell 
     * @param Array $aCells     TableCell instances 
     * @return void
     */
    public function setCells($aCells)
    {
        if (is_array($aCells) AND count($aCells)) {
            $this->aCells = array();
            foreach ($aCells as $cell) {
                if (!$this->getWidth()) {
                    $this->setWidth($cell->getWidth());
                }
                $this->addCell($cell);
            }
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
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  EXPORTS                                   //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Print the multicell to a pdf file
     * @param PDF $pdf 
     */
    public function PDFexport(PDF &$pdf, $lineHeight, $tableWidth, $defaultAlign, $defaultLayout, $widthCalculationType = Table::WIDTH_TYPE_PERCENT)
    {
        if (null !== $pdf) {
            $xReturn        = $pdf->getX();
            $yReturn        = $pdf->getY();
            $aCells         = $this->getCells();
            foreach ($aCells as &$cell) {
                $x          = $pdf->getX();
                $y          = $pdf->getY();
                
                $cell->PDFexport($pdf, $lineHeight, $tableWidth, $defaultAlign, $defaultLayout, $widthCalculationType);
                
                $xReturn    = $pdf->getX();
                $pdf->setXY($x, $y+$lineHeight);
            }
            
            $pdf->setXY($xReturn, $yReturn);
        }
    }
}
?>
