<?php

/**
 * A class to manage all utils
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class UtilsManager {
    public static function get8bitInt($i)
    {
        $i = ($i > 255)? 255: $i;
        $i = ($i < 0)? 0: $i;
        
        return $i;
    }
    
    /***************************************************************************
     *                          Table Utils
     ***************************************************************************/
    
    
    /**
     * Create a table header from an array of fields (triplet (name, width, layout(optional)))
     * @access public
     * @static
     * @uses createTableDataLine()
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param array $aFields    Array of triplet (name, width, layout(optional))
     * @param int $height       height of the line
     * @param Layout $layout    Layout of the line
     * @return Table_Header 
     */
    public static function createTableHeader(Array $aFields, $height = null, Layout $layout = null)
    {
        $line = new Table_Header($height);
        
        $aCells = self::convertFieldsToCells($aFields);
        
        if (count($aCells)) {
            $line->setCells($aCells);
        }

        if ($layout !== null) {
            $line->setLayout($layout);
        }
        
        return $line;
    }
    
    /**
     * Create a table line from an array of fields (triplet (name, width, layout(optional)))
     * @access public
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param array $aFields  Array of triplet (name, width, layout(optional))
     * @param int $height       height of the line
     * @param Layout $layout    Layout of the line
     * @return Table_DataLine 
     */
    public static function createTableDataLine(Array $aFields, $height = null, Layout $layout = null)
    {
        $line = new Table_DataLine($height);
        
        $aCells = self::convertFieldsToCells($aFields);
        
        if (count($aCells)) {
            $line->setCells($aCells);
        }
        
        if ($layout !== null) {
            $line->setLayout($layout);
        }
        
        return $line;
    }
    
    /**
     * Create cells from an array of fields (couple (name, width))
     * @access private
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param array $aFields  Array of couples (name, width)
     * @return Array 
     */
    public static function convertFieldsToCells($aFields)
    {
        $aCells = array();
        foreach ($aFields as &$field) {
            if (is_array($field[0])) {
                // it's a multiline-Cell
                $multiCell          = self::convertFieldsToMultiCell($field);
                if ($multiCell) {
                    $aCells[]       = $multiCell;
                }
            } else {
                $cell           = self::convertFieldToCell($field);
                if ($cell) {
                    $aCells[]       = $cell;
                }
            }
        }
        
        return $aCells;
    }
    
    /**
     * Create cell from a field (couple (name, width))
     * @access private
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param array $field      couple (name, width)
     * @return Array 
     */
    public static function convertFieldToCell($field)
    {
        $cell = null;
        if (isset($field[0]) AND isset($field[1])) {
            $cell       = new Table_Cell($field[0], $field[1]);
            if (isset($field[2])) {
                $cell->setLayout($field[2]);
            }
        }
        
        return $cell;
    }
    
    /**
     * Create a multicell from an array of fields (couple (name, width))
     * @access private
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param array $aFields  Array of couples (name, width)
     * @return Table_MultiCell
     */
    public static function convertFieldsToMultiCell($aFields)
    {
        $multiCell  = new Table_MultiCell();
        
        $aCells     = array();
        foreach ($aFields as &$field) {
            if (is_array($field[0])) {
                // it's a multiline-Cell ... again, U MAD ?! 
                $aCells[]       = self::convertFieldsToMultiCell($field);
            } else {
                $cell           = self::convertFieldToCell($field);
                if ($cell) {
                    $aCells[]       = $cell;
                }
            }
        }
        
        $multiCell->setCells($aCells);
        
        return $multiCell;
    }
    
    public static function getCellNbLines(PDF $pdf, Table_Cell $cell, $tableWidth, $widthCalculationType = Table::WIDTH_TYPE_PERCENT)
    {
        $width              = $cell->getWidth();
        if ($widthCalculationType == Table::WIDTH_TYPE_PERCENT) {
            $width          = $width*$tableWidth/100;
        }
        
        $nbLines            = 0;
        if ($cell instanceof Table_MultiCell) {
            $aCells         = $cell->getCells();
            foreach ($aCells as &$cellOfMultiCell) {
                $nbLines    += self::getCellNbLines($pdf, $cellOfMultiCell, $tableWidth, $widthCalculationType);
            }
        } else {
            $nbLines        = $pdf->NbLines($width, $cell->getContent());
        }
        
        return $nbLines;
    }
}

?>
