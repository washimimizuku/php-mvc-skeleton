<?php

/**
 * A generic class manager for pdfs
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class PDF_Manager {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    CONST TABLE_LINE_HEIGHT_DEFAULT = 6;

    CONST MAX_NB_LINES_SENT_FILES   = 13;

    /** @var Layout */
    public $defaultHeaderLayout;
    /** @var Layout */
    public $defaultLayout;
    /** @var Layout */
    public $defaultHorizontalTitleLayout;
    /** @var Layout */
    public $defaultHorizontalValueLayout;
    
    protected $aAuthorizations;
    
    /** @var PDF */
    protected $pdf;

    public function __construct() {
        $this->initPDFLayoutDefault();
    }

    /**
     * Initialize default layouts
     */
    public function initPDFLayoutDefault()
    {
        // default layout
        $defaultLayout                              = new Layout();
        $defaultLayout->setFillColor(new Layout_Color(array(249,247,245)));
        $defaultLayout->setTextColor(new Layout_Color(null, 0));
        $defaultLayout->setDrawColor(new Layout_Color(array(128,0,0)));
        $defaultLayout->setFont(new Layout_Font());
        $defaultLayout->setLineWidth(0.3);
        $defaultLayout->setAlign(Layout::ALIGN_CENTER);
        $this->defaultLayout                        = $defaultLayout;

        // default header layout
        $defaultHeaderLayout                        = new Layout();
        $defaultHeaderLayout->setFillColor(new Layout_Color(array(152,30,50)));
        $defaultHeaderLayout->setTextColor(new Layout_Color(null, 255));
        $defaultHeaderLayout->setDrawColor(new Layout_Color(array(128,0,0)));
        $defaultHeaderLayout->setFont(new Layout_Font('', 'B'));
        $defaultHeaderLayout->setLineWidth(0.3);
        $defaultHeaderLayout->setAlign(Layout::ALIGN_CENTER);
        $this->defaultHeaderLayout                  = $defaultHeaderLayout;

        // default horizontal title layout
        $defaultHorizontalTitleLayout               = new Layout();
        $defaultHorizontalTitleLayout->setAlign(Layout::ALIGN_RIGHT);
        $this->defaultHorizontalTitleLayout         = $defaultHorizontalTitleLayout;

        // default horizontal value layout
        $defaultHorizontalValueLayout               = new Layout();
        $defaultHorizontalValueLayout->setAlign(Layout::ALIGN_LEFT);
        $this->defaultHorizontalValueLayout         = $defaultHorizontalValueLayout;
    }

    public function clear()
    {
        $this->defaultLayout->__destruct();
        $this->defaultHeaderLayout->__destruct();
        $this->defaultHorizontalTitleLayout->__destruct();
        $this->defaultHorizontalValueLayout->__destruct();

        unset($this->defaultLayout, $this->defaultHeaderLayout, $this->defaultHorizontalTitleLayout, $this->defaultHorizontalValueLayout, $this->currentCentralFile);
        gc_collect_cycles();
    }

    /**
     * Init the pdf file
     * @param string $headerTitle
     */
    public function initPdfFile($headerTitle)
    {
        $this->pdf                  = new PDF('L', 'mm', 'A4');
        $this->pdf->AliasNbPages();
        $this->pdf->headerTitle     = $headerTitle;
        $this->pdf->dateTime        = date('Y-m-d H:i:s');
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->AddPage();
    }

    /**
     * Set current language
     * @param string $locale
     */
    public function setLanguage($locale){
        if (!$locale) {
            $locale = 'fr_FR';
        }
        putenv("LC_ALL=".$locale);
        putenv("LANG=".$locale);
        setlocale(LC_ALL, $locale);
        bindtextdomain($locale, getenv('app_root')."/locale");
        bind_textdomain_codeset($locale, 'UTF-8');
        textdomain($locale);
    }

    /**
     * Save or send the document
     * @param string $name
     * @param string $destination : - I: send the file inline to the browser. The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.
     *                              - D: send to the browser and force a file download with the name given by name.
     *                              - F: save to a local file with the name given by name (may include a path).
     *                              - S: return the document as a string. name is ignored.
     */
    public function outputPDF($name, $destination = 'D')
    {
        $this->clear();

        $this->pdf->Close();
        $this->pdf->Output($name.'.pdf', $destination);
    }
    
    public function generateNumberOfRowsTable($number = 0)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
            
        $nb = UtilsManager::createTableDataLine(array(
                                                        array(_('Number of Rows'), 10, $this->defaultHorizontalTitleLayout),
                                                        array($number, 5, $this->defaultHorizontalValueLayout)
                                                    )
                                                );
        $table->addDataLine($nb);
        return $table;
    }
    
    public function generateFiltersTable($aFilters = array())
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        if (isset($aFilters['simpleSearch'])) {
            $header = UtilsManager::createTableHeader(array(
                                                            array(_('Search'), 100, $this->defaultHeaderLayout)
                                                    )
                                                    );
            $table->setHeader($header);
            unset($header);
            
            $search = UtilsManager::createTableDataLine(array(
                                                            array($aFilters['simpleSearch'], 100, $this->defaultHorizontalValueLayout)
                                                            )
                                                    );
            $table->addDataLine($search);
            
        } else {
            $header = UtilsManager::createTableHeader(array(
                                                            array(_('Advanced search'), 100, $this->defaultHeaderLayout)
                                                    )
                                                    );
            $table->setHeader($header);
            unset($header);

            $config = ApplicationConfig::getInstance();
            foreach ($aFilters as $key=>$value) {
                $aValues                = Utils::getMultiValues($value);
                if (count($aValues)) {
                    $searchAttrib           = isset($config->advancedSearchTranslatedIDs[$key])?$config->advancedSearchTranslatedIDs[$key]:$key;
                    
                    switch ($key) {
                        case 'authorizationType':
                            foreach ($aValues as &$value) {
                                foreach ($config->permissionPrefix as $aPrefix) {
                                    if ($value == $aPrefix['id']) {
                                        $value = $aPrefix['value'];
                                    }
                                }
                            }
                            break;
                        case 'groupType':
                            $aValues = (array)$config->filtersGroupsTypes[reset($aValues)]['value'];
                            break;
                        default:
                            break;
                    }
                    

                    $advancedSearch             = UtilsManager::createTableDataLine(array(
                                                                                array($searchAttrib, 20, $this->defaultHorizontalTitleLayout),
                                                                                array(implode(', ', $aValues), 80, $this->defaultHorizontalValueLayout)
                                                                                )
                                                                        );
                    $table->addDataLine($advancedSearch);
                }
            }
        }
        

        return $table;
    }
    
    public function setAuthorizations($aAuthorizations)
    {
        if (is_array($aAuthorizations)) {
            $this->aAuthorizations = $aAuthorizations;
        }
    }
    
    public function getAuthorizations()
    {
        return $this->aAuthorizations;
    }
}

class Section {
    const PAGE_BREAK_TRIGGER_DEFAULT = 20;

    /**
     * Main table of the section
     * @var Table
     */
    public $main;

    /**
     * List of subsections
     * @var Array
     */
    public $aSubSections;

    /**
     * The minium available height, if this height is not available, a page break will appear
     * @var int
     */
    public $pageBreakTrigger    = self::PAGE_BREAK_TRIGGER_DEFAULT;
    
    public $isCompactView       = false;
    
    public function __destruct()
    {
        if (isset($this->main)) $this->main->__destruct();
        if (isset($this->aSubSections)) {
            if (is_array($this->aSubSections)) {
                foreach ($this->aSubSections as &$section) {
                    $section->__destruct();
                }
            }
        }

        unset($this->main, $this->aSubSections);
    }

    public function PDFexport(PDF &$pdf, $isFirst = true, $wHeaderTop = true, $isCompactView = false)
    {
        $pdf->CheckPageBreak($this->pageBreakTrigger);
        
        if ($this->isCompactView) {
            $isCompactView  = $this->isCompactView;
        }
        
        if ($this->main) {
            $this->main->PDFexport($pdf, $isFirst, $wHeaderTop, $isCompactView);
            
            $this->main->__destruct();
            unset($this->main);
        }

        if (is_array($this->aSubSections)) {
            $isFirst        = true;
            foreach ($this->aSubSections as &$subSection) {
                $subSection->PDFexport($pdf, $isFirst, $wHeaderTop, $isCompactView);

                $subSection->__destruct();
                unset($subSection);
                $isFirst    = false;
            }
            unset($this->aSubSections);
        } else if ($this->aSubSections) {
            $this->aSubSections->PDFexport($pdf, $isFirst, $wHeaderTop, $isCompactView);
            $this->aSubSections->__destruct();

            unset($this->aSubSections);
        }
    }
}

class RecordSection extends Section {
    public function PDFexport(PDF &$pdf, $isFirst = false, $wHeaderTop = false, $isCompactView = false)
    {
        $isPageBreak = $pdf->CheckPageBreak($this->pageBreakTrigger);

        if (!$isFirst AND $isPageBreak) {
            $isFirst = true;
        }

        if ($this->main) {
            $this->main->PDFexport($pdf, $isFirst, $wHeaderTop, $isCompactView);
            $this->main->__destruct();
            unset($this->main);
        }
    }
}

class PageBreak extends Section {
    public function PDFexport(PDF &$pdf, $isFirst = false)
    {
        $pdf->AddPage();
    }
}

class LineBreak extends Section {
    public function PDFexport(PDF &$pdf, $isFirst = false)
    {
        $pdf->Ln(PDF_Manager::TABLE_LINE_HEIGHT_DEFAULT);
    }
}

?>
