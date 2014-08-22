<?php
require_once('fpdf/fpdf.php');

class PDF extends FPDF {
    public $headerTitle = 'Default';
    public $dateTime    = '';

    /**
     * Memory Optimisation from http://www.fpdf.de/downloads/addons/18/ and jh@datasphere.ch
     */
    function _putpages()
    {
        $nb = $this->page;
        if(!empty($this->AliasNbPages))
        {
            // Replace number of pages
            for($n=1;$n<=$nb;$n++)
            {
                if($this->compress)
                    $this->pages[$n] = gzcompress(str_replace($this->AliasNbPages,$nb,gzuncompress($this->pages[$n])));
                else
                    $this->pages[$n] = str_replace($this->AliasNbPages,$nb,$this->pages[$n]);
            }
        }
        if($this->DefOrientation=='P')
        {
            if (isset($this->DefPageFormat)) {
                $wPt = $this->DefPageFormat[0]*$this->k;
                $hPt = $this->DefPageFormat[1]*$this->k;
            } else {
                $wPt = $this->DefPageSize[0]*$this->k;
                $hPt = $this->DefPageSize[1]*$this->k;
            }
        }
        else
        {
            if (isset($this->DefPageFormat)) {
                $wPt = $this->DefPageFormat[1]*$this->k;
                $hPt = $this->DefPageFormat[0]*$this->k;
            } else {
                $wPt = $this->DefPageSize[1]*$this->k;
                $hPt = $this->DefPageSize[0]*$this->k;
            }
        }
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        for($n=1;$n<=$nb;$n++)
        {
            // Page
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if(isset($this->PageSizes[$n]))
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageSizes[$n][0],$this->PageSizes[$n][1]));
            $this->_out('/Resources 2 0 R');
            if(isset($this->PageLinks[$n]))
            {
                // Links
                $annots = '/Annots [';
                foreach($this->PageLinks[$n] as $pl)
                {
                    $rect = sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
                    $annots .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
                    if(is_string($pl[4]))
                        $annots .= '/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
                    else
                    {
                        $l = $this->links[$pl[4]];
                        $h = isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $hPt;
                        $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',1+2*$l[0],$h-$l[1]*$this->k);
                    }
                }
                $this->_out($annots.']');
            }
            if($this->PDFVersion>'1.3')
                $this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
            $this->_out('/Contents '.($this->n+1).' 0 R>>');
            $this->_out('endobj');
            // Page content
            $p = $this->pages[$n];
            $this->_newobj();
            $this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
            $this->_putstream($p);
            $this->_out('endobj');
        }
        // Pages root
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for($i=0;$i<$nb;$i++)
            $kids .= (3+2*$i).' 0 R ';
        $this->_out($kids.']');
        $this->_out('/Count '.$nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$wPt,$hPt));
        $this->_out('>>');
        $this->_out('endobj');
    }

    function _endpage()
    {
        parent::_endpage();
        if($this->compress)
            $this->pages[$this->page] = gzcompress($this->pages[$this->page]);
    }
    
    //Page header
    function Header() {
        //Logo
        $this->Image(getenv('app_root').'/htdocs/images/logo.gif',10,10,50);
        //Arial bold 15
        $this->SetFont('Arial','B', 14);
        //Move to the right
        $this->Cell(70);
        //Title
        $this->SetTextColor(152,30,50);
        $this->Cell(100,10,utf8_decode($this->headerTitle),0,0,'L');
        //Line break
        $this->Ln(20);
    }

    //Page footer
    function Footer() {
        //Position at 1.5 cm from bottom
        $this->SetY(-20);
        //Arial italic 8
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, $this->dateTime, 0, 0, 'L');
        
        //Position at 1.5 cm from bottom
        $this->SetY(-15);
        //Page number
        $this->Cell(0,10,utf8_decode(_('Page')).' '.$this->PageNo().'/{nb}',0,0,'C');
    }

    public function createHorizontalTable($header, $columnsSize, $data, $align){
        if(is_array($header) AND sizeof($header)>0){
            $numberRows = count($header);
            //Colors, line width and bold font
            $this->SetFillColor(249,247,245);
            $this->SetTextColor(0);
            $this->SetDrawColor(128,0,0);
            $this->SetLineWidth(.3);
            
            for($i=0;$i<$numberRows;$i++){
                 
                $this->SetFont('');
                $this->Cell($columnsSize[$i],7,utf8_decode($header[$i]),'LTR',0,'R',true);
                //$this->Cell($columnsSize[$i],7,utf8_decode($header[$i]),'LTR',0,'R',true);
              
                if($i == ($numberRows-1)){
                    $this->SetFont('','B');
                    $this->Cell(0,7,utf8_decode($data[$i]),'LTR',0,$align[$i],true);
                } else {
                    $this->SetFont('','B');
                    $this->Cell($columnsSize[$i],7,utf8_decode($data[$i]),'LTR',0,$align[$i],true);
               }
              
               // $this->Cell($columnsSize[$i],7,utf8_decode($data[$i]),1,0,'C',true);
                
            }
            
            $this->Cell(0,0,'','T');
        }
    }
    
     public function createOneTable($data,$columnsSize, $align , $type = '', $color= '') {
        $numberRows = count($data);
        
        if ($type  == 'title'){
            $this->SetFillColor(152,30,50);
            $this->SetTextColor(255);
            $this->SetDrawColor(128,0,0);
            $this->SetLineWidth(.3);
            $this->SetFont('','B');
            
        } else {
            
            $this->SetFillColor(249,247,245);
            $this->SetTextColor(0);
            $this->SetFont(''); 
                    
        }
        if($color == 'red'){
            $this->SetTextColor(204,0,0);
        }
        
        if($color == 'green'){
            $this->SetTextColor(0,153,0); 
        }
        
        $fill=false;
       for($i=0;$i<$numberRows;$i++) {
            $this->Cell($columnsSize[$i],7,utf8_decode($data[$i]),1,0,$align[$i],true);
        }
        $this->Ln();
        $this->Cell(array_sum($columnsSize),0,'','T');
     }
     
    //Colored table
    function createTable ($header, $columnsSize, $data, $align, $box=array()) {
        if ($box) {
            $this->SetFillColor(249,247,245);
            $this->SetTextColor(0);
            $this->SetFont('');
            $this->SetDrawColor(128,0,0);
            $this->SetLineWidth(.3);

            $boxText = '';
            foreach ($box as $row) {
                $boxText .= "$row\n";
            }
            $this->MultiCell(50,4,utf8_decode($boxText),'1','L',0);
            $this->Ln();
        }

        $numberRows = count($header);
        //Colors, line width and bold font
        $this->SetFillColor(152,30,50);
        $this->SetTextColor(255);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');

        //Header
        for($i=0;$i<$numberRows;$i++) {
            $this->Cell($columnsSize[$i],7,utf8_decode($header[$i]),1,0,'C',true);
        }
        $this->Ln();

        //Color and font restoration
        $this->SetFillColor(249,247,245);
        $this->SetTextColor(0);
        $this->SetFont('');
        //Data
        $fill=false;
        
        foreach($data as $row) {
            for($i=0;$i<$numberRows;$i++) {
                if ( is_array($row[$i])) {
                    $text = utf8_decode($row[$i][0]?$row[$i][0]:'');
                    $family = isset( $row[$i]['font'])?$row[$i]['family']:'';;
                    $style = isset( $row[$i]['style'])?$row[$i]['style']:'';;
                    $size = isset( $row[$i]['size'])?$row[$i]['size']:'';;
                    $this->SetFont($family,$style,$size );
                    $this->Cell($columnsSize[$i],6,$text,'LR',0,$align[$i],$fill);
                    $this->SetFont('','',10);
                    
                } else {
                        $this->Cell($columnsSize[$i],6,utf8_decode($row[$i]),'LR',0,$align[$i],$fill);
                    }
                }
            $this->Ln();
            $fill=!$fill;
        }
        $this->Cell(array_sum($columnsSize),0,'','T');
    }

    //Colored vertical table
    function createVerticalTable ($header, $data, $titleColumnSize, $valueColumnSize) {
        $numberRows = count($header);

        for($i=0;$i<$numberRows;$i++) {
            $this->whiteCell($titleColumnSize, $header[$i], 'R');
            $this->whiteCell($valueColumnSize, $data[$i], 'L');
            $this->Ln();
        }
        $this->Cell($titleColumnSize+$valueColumnSize,0,'','T');
    }

    function redCell ($size, $text, $align, $fill=1) {
        $this->SetFillColor(152,30,50);
        $this->SetTextColor(255);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');

        $this->Cell($size,6,utf8_decode($text),1,0,$align,$fill);
    }
    
    function whiteCell($size, $text, $align, $fill=0) {
        $this->SetFillColor(249,247,245);
        $this->SetTextColor(0);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('');

        $this->Cell($size,7,utf8_decode($text),1,0,$align,$fill);
    }
    
    /**
     * Apply the specified layout
     * @param Layout $layout 
     */
    public function applyLayout(Layout $layout)
    {
        $fillColor  = $layout->getFillColor();
        $drawColor  = $layout->getDrawColor();
        $textColor  = $layout->getTextColor();
        $font       = $layout->getFont();
        $lineWidth  = $layout->getLineWidth();
        
        // fill color
        if ($fillColor) {
            $color = $fillColor->getValue();
            if (is_array($color)) {
                $this->SetFillColor($color[0], $color[1], $color[2]);
            } else if (is_int($color)) {
                $this->SetFillColor($color);
            }
            
            unset($color);
        }
        
        // draw color
        if ($drawColor) {
            $color = $drawColor->getValue();
            if (is_array($color)) {
                $this->SetDrawColor($color[0], $color[1], $color[2]);
            } else if (is_int($color)) {
                $this->SetDrawColor($color);
            }
            
            unset($color);
        }
        
        // text color
        if ($textColor) {
            $color = $textColor->getValue();
            if (is_array($color)) {
                $this->SetTextColor($color[0], $color[1], $color[2]);
            } else if (is_int($color)) {
                $this->SetTextColor($color);
            }
            
            unset($color);
        }
        
        // line width
        if (is_numeric($lineWidth)) {
            $this->SetLineWidth($lineWidth);
        }
        
        // font
        if ($font) {
            $this->SetFont($font->family, $font->style, $font->size);
        }
    }
    
    /**
     * Calculate the number of lines that a multicell need
     * @author http://www.fpdf.de/downloads/addons/3/ and Julien Hoarau
     * @param type $w
     * @param type $txt
     * @return int 
     */
    public function NbLines($w, $txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw     = &$this->CurrentFont['cw'];
        if ($w==0) {
            $w  = $this->w - $this->rMargin - $this->x;
        }
        
        $wmax   = ($w-2 * $this->cMargin) * 1000/$this->FontSize;
        
        $s      = str_replace("\r", '', $txt);
        
        $nb=strlen($s);
        if ($nb > 0 AND $s[$nb-1]=="\n") {
            $nb--;
        }
        
        $sep    = -1;
        $i      = 0;
        $j      = 0;
        $l      = 0;
        $nl     = 1;
        
        $specialChars   = array(utf8_decode("Â"), utf8_decode("Ã"), utf8_decode("Ä"));
        while ($i<$nb) {
            $c  = $s[$i];
            
            if (in_array($c, $specialChars)) {
                $i++;
                $c .= $s[$i]; 
            }
            
            if ($c=="\n") {
                $i++;
                $sep    = -1;
                $j      = $i;
                $l      = 0;
                $nl++;
                continue;
            }
            
            if ($c==' ') {
                $sep    = $i;
            }
            
            
            
            $l  += $cw[ utf8_decode($c)];
            
            if ($l>$wmax) {
                if ($sep==-1) {
                    if ($i==$j) {
                        $i++;
                    }
                } else {
                    $i  = $sep+1;
                }
                $sep    = -1;
                $j      = $i;
                $l      = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
    
    /**
     * Check the space and add a page if needed
     * @author http://www.fpdf.de/downloads/addons/3/, updated by Julien Hoarau jh@datasphere.ch
     * @param type $h
     * @return boolean 
     */
    public function CheckPageBreak($h, $autoAddPage = true)
    {
        $isPageBreak = false;
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY() + $h > $this->PageBreakTrigger) {
            if ($autoAddPage) {
                $this->AddPage($this->CurOrientation);
            }
            $isPageBreak = true;
        }
        
        return $isPageBreak;
    }
    
    public function getPageWidth() {
        return $this->w - $this->lMargin - $this->rMargin;
    }
}
?>