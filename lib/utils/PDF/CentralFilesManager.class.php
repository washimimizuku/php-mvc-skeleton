<?php

/**
 * A class manager for central files pdf
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class PDF_CentralFilesManager extends PDF_Manager {
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////
    
    /** @var CentralFile */
    protected $currentCentralFile;
    
    protected $wRecords = true;
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a pdf file of Archive central files (copy of createCentralFileHistoryPdf)
     * @param type $aCentralFiles
     * @param type $wDetail
     * @param type $aFilters
     * @param type $locale 
     */
    public function createCentralFileArchivePdf($aCentralFiles = array(), $wDetail = true, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);
        
        $this->wRecords = $wDetail;
        
        // init pdf file
        $headerTitle    = _("Central Files Archive");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        // get sent files
        $aSentFiles     = array();
        $aRejectedRecords = array();
        foreach ($aCentralFiles as &$centralFile) {
            $aRecords   = $centralFile->getRecords($this->getAuthorizations());
            
            foreach ($aRecords as &$record) {
                if ($record->wcodvru == 'V' AND $record->wfiloutput) {
                    $sentFile       = $record->getSentFile();
                    
                    if (!isset($aSentFiles[$sentFile->dlfileput])) {
                        $aSentFiles[$sentFile->dlfileput]   = $sentFile;
                    }
                    $aSentFiles[$sentFile->dlfileput]->addRecord($record);
                } else {
                    if (!isset($aRejectedRecords[$record->wnumfic])) {
                        $aRejectedRecords[$record->wnumfic]   = array();
                    }
                    $aRejectedRecords[$record->wnumfic][]     = $record;
                }
            }
            $centralFile->__destruct();
            unset($centralFile);
        }
        unset($aCentralFiles);
        gc_collect_cycles();
        
        $hasSentFiles       = false;
        foreach ($aSentFiles as &$sentFile) {
            $hasSentFiles   = true;
            
            $aRecords       = $sentFile->getRecords(null, true);
            $aCompanies     = Record_Manager::getCompanies($aRecords);
            $summarySection = $this->generateCentralFileHistorySummarySection($sentFile, $aCompanies, $aFilters);
            $summarySection->PDFexport($this->pdf);
            $summarySection->__destruct();
            unset($summarySection);
            
            $this->pdf->AddPage();
            $aRecordsByInitialFilesSections = $this->generateRecordsByInitialFiles($sentFile->getRecords(), $aCompanies);
            
            foreach ($aRecordsByInitialFilesSections as &$recordsByInitialFilesSection) {
                $recordsByInitialFilesSection->PDFexport($this->pdf);
                $recordsByInitialFilesSection->__destruct();
                unset($recordsByInitialFilesSection);
            }
            unset($aRecordsByInitialFilesSections);
            
            if ($sentFile !== end($aSentFiles)) {
                $this->pdf->AddPage();
            }
            $sentFile->__destruct();
            unset($sentFile, $aRecords, $aCompanies);
        }
        unset($aSentFiles);
        
        // rejected transactions
        if ($aRejectedRecords) {
            if ($hasSentFiles) {
                $this->pdf->AddPage();
            }
            
            $rejectedSection = $this->generateRejectedTransactionsSection($aRejectedRecords);
            $rejectedSection->PDFexport($this->pdf);
            $rejectedSection->__destruct();
        }
        unset($aRejectedRecords);
    }
    
    /**
     * Create a pdf file of history central files
     * @param type $aCentralFiles
     * @param type $wDetail
     * @param type $aFilters
     * @param type $locale 
     */
    public function createCentralFileHistoryPdf($aCentralFiles = array(), $wDetail = true, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);
        
        $this->wRecords = $wDetail;
        
        // init pdf file
        $headerTitle    = _("Central Files History");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        // get sent files
        $aSentFiles     = array();
        $aRejectedRecords = array();
        foreach ($aCentralFiles as &$centralFile) {
            $aRecords   = $centralFile->getRecords($this->getAuthorizations());
            
            foreach ($aRecords as &$record) {
                if ($record->wcodvru == 'V' AND $record->wfiloutput) {
                    $sentFile       = $record->getSentFile();
                    
                    if ($sentFile) {
                        if (!isset($aSentFiles[$sentFile->dlfileput])) {
                            $aSentFiles[$sentFile->dlfileput]   = $sentFile;
                        }
                        $aSentFiles[$sentFile->dlfileput]->addRecord($record);
                    }
                } else {
                    if (!isset($aRejectedRecords[$record->wnumfic])) {
                        $aRejectedRecords[$record->wnumfic]   = array();
                    }
                    $aRejectedRecords[$record->wnumfic][]     = $record;
                }
            }
            $centralFile->__destruct();
            unset($centralFile);
        }
        unset($aCentralFiles);
        
        $hasSentFiles       = false;
        foreach ($aSentFiles as &$sentFile) {
            if ($hasSentFiles) {
                $this->pdf->AddPage();
            }
            
            $hasSentFiles   = true;
            
            $aRecords       = $sentFile->getRecords(null, true);
            $aCompanies     = Record_Manager::getCompanies($aRecords);
            $summarySection = $this->generateCentralFileHistorySummarySection($sentFile, $aCompanies, $aFilters);
            $summarySection->PDFexport($this->pdf);
            $summarySection->__destruct();
            unset($summarySection);
            
            $this->pdf->AddPage();
            $aRecordsByInitialFilesSections = $this->generateRecordsByInitialFiles($sentFile->getRecords(), $aCompanies);

            foreach ($aRecordsByInitialFilesSections as &$recordsByInitialFilesSection) {
                $recordsByInitialFilesSection->PDFexport($this->pdf);
                $recordsByInitialFilesSection->__destruct();
                unset($recordsByInitialFilesSection);
            }
            unset($aRecordsByInitialFilesSections);
            
            $sentFile->__destruct();
            unset($sentFile, $aRecords, $aCompanies);
        }
        unset($aSentFiles);
        
        
        // rejected transactions
        if ($aRejectedRecords) {
            if ($hasSentFiles) {
                $this->pdf->AddPage();
            }
            
            $rejectedSection = $this->generateRejectedTransactionsSection($aRejectedRecords);
            $rejectedSection->PDFexport($this->pdf);
            $rejectedSection->__destruct();
        }
        unset($aRejectedRecords);
    }
    
    /**
     * Generate a summary of a central file history
     * @param SentFile $sentFile
     * @return Section 
     */
    private function generateCentralFileHistorySummarySection(SentFile $sentFile, $aCompanies, $aFilters = array())
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $aRecords   = $sentFile->getRecords(null, true);
        
        // header of the section
        $header = UtilsManager::createTableHeader(array(array(_('Summary').' - '._('File sent to bank'), 0)));
        $table->setHeader($header);
        unset($header);
        
        // Details of the section
        // Name of the file sent to bank
        $sFilename  = explode('-', $sentFile->dlfileput);
        $sFilename  = $sFilename[1];
        $fileName = UtilsManager::createTableDataLine(array(
                                                        array(_('Name of the file sent to bank'), 20, $this->defaultHorizontalTitleLayout),
                                                        array($sFilename, 0, $this->defaultHorizontalValueLayout)
                                                    ));
        $table->addDataLine($fileName);
        unset($fileName);
        
        // receiver
        $receiver   = UtilsManager::createTableDataLine(array(
                                                            array(_('BIC of the receiving bank'), 20, $this->defaultHorizontalTitleLayout),
                                                            array(SentFile_Manager::getReceivingBankBIC($sentFile, reset($aRecords)->wnumfic), 0, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($receiver);
        unset($receiver);
        
        // companies
        $aCompaniesLines   = $this->generateCompaniesSection($aCompanies);
        if (is_array($aCompaniesLines)) {
            foreach ($aCompaniesLines as &$companiesLine) {
                $table->addDataLine($companiesLine);
            }
        }
        unset($aCompaniesLines);
        
        // Operation type
        $operationType      = UtilsManager::createTableDataLine(array(
                                                                    array(_('Operation type'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array(reset($aRecords)->typope, 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($operationType);
        unset($operationType);
        
        // number of transactions
        $nbTransactions = UtilsManager::createTableDataLine(array(
                                                                array(_('Number of sent transactions'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(count($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($nbTransactions);
        unset($nbTransactions);
        
        // total amount
        $totalAmount    = UtilsManager::createTableDataLine(array(
                                                                array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(Record_Manager::formatTotalAmount($aRecords, true, false), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($totalAmount);
        unset($totalAmount);

        // ServiceBureau Execution Date
        $dateSBLine    = UtilsManager::createTableDataLine(array(
                                                                array(_('SB processing date and time'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(Utils::formatDate($sentFile->dlrcdat.$sentFile->dlrctim), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($dateSBLine);
        unset($dateSBLine);
        
        // date of the sent file
        $dateSentFileLine    = UtilsManager::createTableDataLine(array(
                                                                        array(_('Date and time of sent file'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array(Utils::formatDate($sentFile->dlptdat.$sentFile->dlpttim), 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($dateSentFileLine);
        unset($dateSentFileLine);
        
        // delivery notification date
        $date               = Utils::formatDate(substr(reset($aRecords)->wtsptrans, 0, 19));
        $deliveryDate       = UtilsManager::createTableDataLine(array(
                                                                        array(_('Date and time of delivery notification'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array($date, 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($deliveryDate);
        unset($deliveryDate);
        
        // delivery notification status
        if (reset($aRecords)->transStatus == 'ACK') {
            $status = _('Acknowledged');
        } else if (reset($aRecords)->transStatus == 'NAK') {
            $status = _('Not Acknowledged');
        } else {
            $status = _('Unknown');
        }
        $deliveryStatus     = UtilsManager::createTableDataLine(array(
                                                                        array(_('Delivery notification status'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array($status, 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($deliveryStatus);
        unset($deliveryStatus);
        
//        // search
//        if (count($aFilters)) {
//            $aAdvancedSearch            = array();
//            foreach ($aFilters as $key=>$value) {
//                if ($value) {
//                    $aValues                = Utils::getMultiValues($value);
//                    
//                    $searchAttrib           = isset(Utils::$aConvertField[$key])?_(Utils::$aConvertField[$key]):$key;
//                    $aAdvancedSearch[]      = $searchAttrib.': '.  implode(', ', $aValues);
//                }
//            }
//            $sAdvancedSearch            = implode('; ', $aAdvancedSearch);
//            $advancedSearch             = UtilsManager::createTableDataLine(array(
//                                                                            array(_('Advanced search'), 20, $this->defaultHorizontalTitleLayout),
//                                                                            array($sAdvancedSearch, 0, $this->defaultHorizontalValueLayout)
//                                                                            )
//                                                                    );
//            $table->addDataLine($advancedSearch);
//            unset($advancedSearch);
//        }
        
        // Records summary
        $initialFilesSummaryHeader      = UtilsManager::createTableDataLine(array(
                                                                        array(_('Initial files'), 0, $this->defaultHeaderLayout),
                                                                    ));
        $table->addDataLine($initialFilesSummaryHeader);
        unset($initialFilesSummaryHeader);
        
        $aRecordsByCentralFiles         = $sentFile->getRecords();
        
        // Number of initial files
        $nbInitialFiles                 = UtilsManager::createTableDataLine(array(
                                                                        array(_('Number of initial files'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array(count($aRecordsByCentralFiles), 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($nbInitialFiles);
        unset($nbInitialFiles);
        
        // initial files list
        $aFileNames                     = array();
        $aDateAmountTitles              = array();
        $aDateAmountValues              = array();
        foreach ($aRecordsByCentralFiles as &$aRecords) {
            $centralFile                = reset($aRecords)->getCentralFile();
            
            $aFileNames[]               = array($centralFile->wnomfic, 50, $this->defaultHorizontalValueLayout);
                
            $aDateAmountTitles[]        = array(_('Integration date and time'), 15, $this->defaultHorizontalTitleLayout);
            $aDateAmountValues[]        = array(Utils::formatDate($centralFile->wtspcreat), 15, $this->defaultHorizontalValueLayout);

            if ($aRecords AND count($aRecords)) {
                $aDateAmountTitles[]    = array(_('Total Amount'), 15, $this->defaultHorizontalTitleLayout);
                $aDateAmountValues[]    = array(Record_Manager::formatTotalAmount($aRecords), 15, $this->defaultHorizontalValueLayout);
            }
            
            unset($centralFile);
        }
        unset($aRecordsByCentralFiles);
        
        $aFields  = array(
                                                                                array(_('Name of initial files'), 20, $this->defaultHorizontalTitleLayout),
                                                                                $aFileNames,
                                                                                $aDateAmountTitles,
                                                                                $aDateAmountValues
                                                                            );
        $initialFilesList               = UtilsManager::createTableDataLine($aFields);
        $table->addDataLine($initialFilesList);
        unset($initialFilesList, $aFields);

        return $table;
    }    
    
    private function generateRejectedTransactionsSection($aRejectedRecords)
    {
        $section    = new Section();
        
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // header
        $header     = UtilsManager::createTableHeader(array(
                                                            array(_('Rejected transactions'), 0)
                                                        ));
        $table->setHeader($header);
        unset($header);
        
        $section->main  = $table;
        unset($table);
        
        // initial files
        foreach ($aRejectedRecords as &$aRecords) {
            list($aSortedRecords, $aCompanies)  = $this->processRecords($aRecords);
            
            $subSection                         = new Section();
            
            // generate Summary section
            $subSection->main                   = $this->generateInitialFileDetailSection($aRecords, $aCompanies);
            
            $subSection->aSubSections           = $this->generateRecordsByStatusSection($aSortedRecords, $aCompanies, true);
            
            $section->aSubSections[]            = $subSection;
            if ($aRecords !== end($aRejectedRecords)) {
                $section->aSubSections[]            = new PageBreak();
            }
        }
        
        return $section;
    }
    
    private function generateInitialFileDetailSection($aRecords, $aCompanies)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $headerLayout   = clone $this->defaultHeaderLayout;
        $headerLayout->setFillColor(new Layout_Color(array(150,150,150)));
        $table->setDefaultHeaderLayout($headerLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // header of the section
        $header = UtilsManager::createTableHeader(array(array(_('Initial file'), 0)));
        $table->setHeader($header);
        unset($header);
        
        $centralFile    = reset($aRecords)->getCentralFile();
        $centralFile->setRecords($aRecords);
        
        // Details of the section
        // File ID
        $fileID = UtilsManager::createTableDataLine(array(
                                                        array(_('File ID'), 20, $this->defaultHorizontalTitleLayout),
                                                        array($centralFile->wnumfic, 0, $this->defaultHorizontalValueLayout)
                                                    ));
        $table->addDataLine($fileID);
        unset($fileID);
        
        // Initial file
        $initialFile = UtilsManager::createTableDataLine(array(
                                                            array(_('Initial file'), 20, $this->defaultHorizontalTitleLayout),
                                                            array($centralFile->wnomfic, 50, $this->defaultHorizontalValueLayout),
                                                            array(_('Integration date and time'), 15, $this->defaultHorizontalTitleLayout),
                                                            array(Utils::formatDate($centralFile->wtspcreat), 15, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($initialFile);
        unset($initialFile);
        
        // Companies
        $aCompaniesLines   = $this->generateCompaniesSection($aCompanies);
        if (is_array($aCompaniesLines)) {
            foreach ($aCompaniesLines as &$companiesLine) {
                $table->addDataLine($companiesLine);
            }
        }
        unset($aCompaniesLines);
        
        // Operation type
        $operationType      = UtilsManager::createTableDataLine(array(
                                                                    array(_('Operation type'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array($centralFile->typope, 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($operationType);
        unset($operationType);
        
        // Records number
        $recordNumber       = UtilsManager::createTableDataLine(array(
                                                                    array(_('Number of transactions'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array($centralFile->wnbrec, 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($recordNumber);
        unset($recordNumber);
        
        // Total amount
        $totalAmount        = UtilsManager::createTableDataLine(array(
                                                                    array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array(Record_Manager::formatTotalAmount($aRecords, true, false), 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($totalAmount);
        unset($totalAmount);
        
        return $table;
    }
    
    private function generateRecordsByInitialFiles($aRecordsByInitialFiles, $aCompanies)
    {
        $aSections  = array();
        
        $i = 1;
        foreach ($aRecordsByInitialFiles as &$aRecords) {
            
            $initialFileSections                        = new Section();
            $initialFileSections->pageBreakTrigger      = 30;
            
            $centralFile                                = reset($aRecords)->getCentralFile();
            $centralFile->setRecords($aRecords);
            $initialFileSections->main                  = $this->generateInitialFileDetailTable($centralFile, $i);
            unset($centralFile);
            
            // if non detailled, we just write signatures
            if ($this->wRecords) {
                // get Records by companies
                $aRecordsByCompanies                    = $this->sortRecordsByCompanies($aRecords);
                $initialFileSections->aSubSections      = $this->generateRecordsByCompanies($aRecordsByCompanies, $aCompanies);
            } else {
                // get signatures 
                $section                                = $this->generateSignaturesSection($aRecords);
                if ($section) {
                    $initialFileSections->aSubSections[]= $section;
                }
            }
            
            $initialFileSections->aSubSections[]        = new LineBreak();
            
            $aSections[]                                = $initialFileSections;
            $i++;
        }
        
        return $aSections;
    }
    
    /**
     * Create a pdf file of live central files 
     * @param type $aCentralFiles
     * @param type $withDetail
     * @param type $aCustFiles
     * @param type $locale 
     */
    public function createCentralFileLivePdf($aCentralFiles = array(), $wDetail = true, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);
        
        $this->wRecords = $wDetail;
        
        // init pdf file
        $headerTitle    = _("Central Files");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        foreach ($aCentralFiles as &$centralFile) {
            $this->currentCentralFile = $centralFile;
            
            $aRecords   = $centralFile->getRecords($this->getAuthorizations());
            list($aSortedRecords, $aCompanies)   = $this->processRecords($aRecords);
                
            // generate Summary section
            $summarySection = $this->generateCentralFileLiveSummarySection($centralFile, $aCompanies, $aSortedRecords, $aFilters);
            $summarySection->PDFexport($this->pdf);
            $summarySection->__destruct();
            unset($summarySection);
            
            $this->pdf->addPage();
            
            $aRecordsStatusSections  = $this->generateRecordsByStatusSection($aSortedRecords, $aCompanies);
            foreach ($aRecordsStatusSections as &$recordStatusSection) {
                $recordStatusSection->PDFexport($this->pdf);
                $recordStatusSection->__destruct();
            }
            unset($aRecordsStatusSections);
            
            unset($aRecords, $aCompanies, $aSortedRecords);
            
            if ($centralFile !== end($aCentralFiles)) {
                $this->pdf->AddPage();
            }
            
            $centralFile->__destruct();
            unset($centralFile);
        }
        
        if ($this->currentCentralFile) $this->currentCentralFile->__destruct();
        unset($aCentralFiles, $this->currentCentralFile);
    }
    
    /**
     * Generate a summary of a central file
     * @param CentralFile $centralFile
     * @return Table 
     */
    private function generateCentralFileLiveSummarySection(CentralFile $centralFile, $aCompanies, $aSortedRecords, $aFilters = array())
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // header of the section
        $header = UtilsManager::createTableHeader(array(array(_('Summary'), 0)));
        $table->setHeader($header);
        unset($header);
        
        // Details of the section
        // File ID
        $fileID = UtilsManager::createTableDataLine(array(
                                                        array(_('File ID'), 20, $this->defaultHorizontalTitleLayout),
                                                        array($centralFile->wnumfic, 0, $this->defaultHorizontalValueLayout)
                                                    ));
        $table->addDataLine($fileID);
        unset($fileID);
        
        // Initial file
        $initialFile = UtilsManager::createTableDataLine(array(
                                                            array(_('Initial file'), 20, $this->defaultHorizontalTitleLayout),
                                                            array($centralFile->wnomfic, 50, $this->defaultHorizontalValueLayout),
                                                            array(_('Integration date and time'), 15, $this->defaultHorizontalTitleLayout),
                                                            array(Utils::formatDate($centralFile->wtspcreat), 15, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($initialFile);
        unset($initialFile);
        
        // Companies
        $aCompaniesLines   = $this->generateCompaniesSection($aCompanies);
        if (is_array($aCompaniesLines)) {
            foreach ($aCompaniesLines as &$companiesLine) {
                $table->addDataLine($companiesLine);
            }
        }
        unset($aCompaniesLines);
        
        // Operation type
        $operationType      = UtilsManager::createTableDataLine(array(
                                                                    array(_('Operation type'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array($centralFile->typope, 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($operationType);
        unset($operationType);
        
        // Records number
        $recordNumber       = UtilsManager::createTableDataLine(array(
                                                                    array(_('Number of transactions'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array(count($centralFile->getRecords()), 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($recordNumber);
        unset($recordNumber);
        
        // Total amount
        $aRecords           = $centralFile->getRecords();
        $totalAmount        = UtilsManager::createTableDataLine(array(
                                                                    array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                    array(Record_Manager::formatTotalAmount($aRecords, true, false), 0, $this->defaultHorizontalValueLayout)
                                                                ));
        $table->addDataLine($totalAmount);
        unset($totalAmount);
        
//         // search
//        if (count($aFilters)) {
//            $aAdvancedSearch            = array();
//            foreach ($aFilters as $key=>$value) {
//                $aValues                = Utils::getMultiValues($value);
//                $searchAttrib           = isset(Utils::$aConvertField[$key])?_(Utils::$aConvertField[$key]):$key;
//                $aAdvancedSearch[]      = $searchAttrib.': '.  implode(', ', $aValues);
//            }
//            $sAdvancedSearch            = implode('; ', $aAdvancedSearch);
//            $advancedSearch             = UtilsManager::createTableDataLine(array(
//                                                                            array(_('Advanced search'), 20, $this->defaultHorizontalTitleLayout),
//                                                                            array($sAdvancedSearch, 0, $this->defaultHorizontalValueLayout)
//                                                                            )
//                                                                    );
//            $table->addDataLine($advancedSearch);
//            unset($advancedSearch);
//        }
        
        // Records summary
        $recordSummaryHeader    = UtilsManager::createTableDataLine(array(
                                                                        array(_('Transactions'), 0, $this->defaultHeaderLayout),
                                                                    ));
        $table->addDataLine($recordSummaryHeader);
        unset($recordSummaryHeader);
        
        foreach ($aSortedRecords as $status=>&$aRecords) {
            $sTitle             = '';
            switch ($status) {
                // waiting for process
                case '':
                case '_': {
                    $sTitle     = _('Number of awaiting process transactions');
                    break;
                }
                case '1': {
                    $sTitle     = _('Number of pending transactions');
                    break;
                }
                // validated
                case 'V': {
                    $sTitle     =_( 'Number of validated transactions');
                    break;
                }
                // rejected
                case 'R': {
                    $sTitle     =_( 'Number of rejected transactions');
                    break;
                }
                // waiting for unlock to process
                case 'B': {
                    $sTitle     =_( 'Number of awaiting process transactions (locked)');
                    break;
                }
                // waiting for unlock to sent to bank
                case 'G': {
                    $sTitle     =_( 'Number of transactions awaiting bank transmission (locked)');
                    break;
                }
                // sent to bank
                case 'S': {
                    $sTitle     =_( 'Number of transactions sent to bank');
                    break;
                }
            }
            
            $nbRecordsStatus = UtilsManager::createTableDataLine(array(
                                                                        array($sTitle, 20, $this->defaultHorizontalTitleLayout),
                                                                        array(count($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                                    ));
            $table->addDataLine($nbRecordsStatus);
            unset($nbRecordsStatus);
        }
        
        return $table;
    }
    
    /**
     * Generate detail of records sorted by status
     * @param array     $aRecordsByStatus
     * @param array     $aCompanies 
     * return Array
     */
    private function generateRecordsByStatusSection($aRecordsByStatus, $aCompanies, $bSimple = false)
    {
        $aSections    = array();
        
        foreach ($aRecordsByStatus as $status=>&$aRecords) {
            $aSections[] = $this->generateStatusDetailSection($status, $aRecords, $aCompanies, $bSimple);
        }
        
        return $aSections;
    }
    
    private function generateStatusDetailSection($status, $aRecords, $aCompanies, $bSimple = false)
    {
        $statusSection                  = new Section();
        
        // init the table
        $table  = new Table();
        $headerLayout   = clone $this->defaultHeaderLayout;
        if (!$bSimple) {
            $headerLayout->setFillColor(new Layout_Color(array(150,150,150)));
        }
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($headerLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $sSimpleTitle    =_( 'Transactions');
        switch ($status) {
            // waiting for process
            case '':
            case '_': {
                $sTitle     =_( 'Awaiting process Transactions');
                break;
            }
            case '1': {
                $sTitle     =_( 'Pending Transactions');
                break;
            }
            // validated
            case 'V': {
                $sTitle     =_( 'Validated transactions');
                break;
            }
            // rejected
            case 'R': {
                $sTitle     =_( 'Rejected transactions');
                break;
            }
            // waiting for unlock to process
            case 'B': {
                $sTitle     =_( 'Transactions awaiting process (locked)');
                break;
            }
            // waiting for unlock to sent to bank
            case 'G': {
                $sTitle     =_( 'Transactions awaiting bank transmission (locked)');
                break;
            }
            // sent to bank
            case 'S': {
                $sTitle     =_( 'Transactions sent to bank');
                break;
            }
            default: {
                $sTitle     = $sSimpleTitle;
                break;
            }
        }
        
        $sDetail            = $sTitle;
        if ($bSimple) {
            $sTitle         = $sSimpleTitle;
        }
        
        // header
        $header                         = UtilsManager::createTableHeader(array(
                                                                            array($sTitle, 0)
                                                                        ));
        $table->setHeader($header);
        unset($header);
        
        // if files has been sent to bank, we need to display a special template
        if ($status != 'S') {
            // number of transactions
            $nbRecords                      = UtilsManager::createTableDataLine(array(
                                                                                    array(_('Number of ').$sDetail, 20, $this->defaultHorizontalTitleLayout),
                                                                                    array(count($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                                                ));
            $table->addDataLine($nbRecords);
            unset($nbRecords);
            
            // total amount
            $totalAmount                    = UtilsManager::createTableDataLine(array(
                                                                                    array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                                    array(Record_Manager::formatTotalAmount($aRecords, true, true), 0, $this->defaultHorizontalValueLayout)
                                                                                ));
            $table->addDataLine($totalAmount);
            unset($totalAmount);

            $statusSection->main            = $table;

            // if non detailled, we just write signatures
            if ($this->wRecords) {
                // get Records by companies
                $aRecordsByCompanies            = $this->sortRecordsByCompanies($aRecords);
                $statusSection->aSubSections    = $this->generateRecordsByCompanies($aRecordsByCompanies, $aCompanies);
            } else {
                // get signatures 
                $section                        = $this->generateSignaturesSection($aRecords);
                if ($section) {
                    $statusSection->aSubSections[]  = $section;
                }
            }
            
            $statusSection->aSubSections[]      = new LineBreak();
            
        } else {
            $aSentFiles                     = Record_Manager::getSentFiles($aRecords);
            
            // number of sent files
            $nbSentFiles                    = UtilsManager::createTableDataLine(array(
                                                                                    array(_('Number of sent files'), 20, $this->defaultHorizontalTitleLayout),
                                                                                    array(count($aSentFiles), 0, $this->defaultHorizontalValueLayout)
                                                                                ));
            $table->addDataLine($nbSentFiles);
            unset($nbSentFiles);
            
            // total amount
            $totalAmount                    = UtilsManager::createTableDataLine(array(
                                                                                    array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                                    array(Record_Manager::formatTotalAmount($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                                                ));
            $table->addDataLine($totalAmount);
            unset($totalAmount);
            
            $statusSection->main            = $table;
            unset($table);
            
            $statusSection->aSubSections[]  = $this->generateSentFilesSection($aSentFiles, 10);
            
            $statusSection->aSubSections    = array_merge($statusSection->aSubSections, $this->generateRecordsBySentFiles($aSentFiles, $aCompanies));
        }
        
        return $statusSection;
    }
    
    private function generateRecordsBySentFiles($aSentFiles, $aCompanies)
    {
        $aSections  = array();
        
        $i = 1;
        foreach ($aSentFiles as &$sentFile) {
            $aRecords                               = $sentFile->getRecords($this->currentCentralFile->wnumfic);
            
            $sentFilesSections                      = new Section();
            $sentFilesSections->pageBreakTrigger    = 90;
            $sentFilesSections->main                = $this->generateSentFileDetailTable($sentFile, $i);
            
            // if non detailled, we just write signatures
            if ($this->wRecords) {
                // get Records by companies
                $aRecordsByCompanies                = $this->sortRecordsByCompanies($aRecords);
                unset($aRecords);

                $sentFilesSections->aSubSections    = $this->generateRecordsByCompanies($aRecordsByCompanies, $aCompanies);
            } else {
                // get signatures 
                $section                            = $this->generateSignaturesSection($aRecords);
                if ($section) {
                    $sentFilesSections->aSubSections[]  = $section;
                }
            }
            
            $sentFilesSections->aSubSections[]      = new LineBreak();
            
            $aSections[]                            = $sentFilesSections;
            $i++;
        }
        
        return $aSections;
    }
    
    /**
     * Generate a section that details an initial file
     * @param CentralFile $centralFile 
     * return Table
     */
    public function generateInitialFileDetailTable(CentralFile $centralFile, $occurrenceTitle = '') 
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $headerLayout   = clone $this->defaultHeaderLayout;
        $headerLayout->setFillColor(new Layout_Color(array(150,150,150)));
        $table->setDefaultHeaderLayout($headerLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $sTitle     = _('Initial file');
        $sTitle     .= ($occurrenceTitle)?(' : '.$occurrenceTitle):'';
        
        // header
        $header     = UtilsManager::createTableHeader(array(
                                                            array($sTitle, 0)
                                                        ));
        $table->setHeader($header);
        unset($header);
        
        // file name
        $fileName   = UtilsManager::createTableDataLine(array(
                                                            array($centralFile->wnomfic, 70),
                                                            array(_('Integration date and time'), 15, $this->defaultHorizontalTitleLayout),
                                                            array(Utils::formatDate($centralFile->wtspcreat), 15, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($fileName);
        unset($fileName);
        
        $aRecords       = $centralFile->getRecords();
        
        // total amount
        $totalAmount    = UtilsManager::createTableDataLine(array(
                                                                array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(Record_Manager::formatTotalAmount($aRecords, true, false), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($totalAmount);
        unset($totalAmount);
        
        // number of transactions
        $nbTransactions = UtilsManager::createTableDataLine(array(
                                                                array(_('Number of transactions'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(count($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($nbTransactions);
        unset($nbTransactions, $aRecords);
        
        return $table;
    }
    
    /**
     * Generate a section that details a sent file
     * @param SentFile $sentFile 
     * return Table
     */
    public function generateSentFileDetailTable(SentFile $sentFile, $occurrenceTitle = '')
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $sTitle     = _('File sent to bank');
        $sTitle     .= ($occurrenceTitle)?(' : '.$occurrenceTitle):'';
        
        // header
        $header     = UtilsManager::createTableHeader(array(
                                                            array($sTitle, 0)
                                                        ));
        $table->setHeader($header);
        unset($header);
        
        
        $aRecords   = $sentFile->getRecords($this->currentCentralFile->wnumfic);
        
        // file name
        $sFilename  = explode('-', $sentFile->dlfileput);
        $fileName   = UtilsManager::createTableDataLine(array(
                                                            array(_('Name of the file sent to bank'), 20, $this->defaultHorizontalTitleLayout),
                                                            array($sFilename[1], 0, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($fileName);
        unset($fileName);
        
        // receiver
        $receiver   = UtilsManager::createTableDataLine(array(
                                                            array(_('BIC of the receiving bank'), 20, $this->defaultHorizontalTitleLayout),
                                                            array(SentFile_Manager::getReceivingBankBIC($sentFile, $this->currentCentralFile->wnumfic), 0, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($receiver);
        unset($receiver);
        
        // total amount
        $totalAmount    = UtilsManager::createTableDataLine(array(
                                                                array(_('Total amount'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(Record_Manager::formatTotalAmount($aRecords, true), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($totalAmount);
        unset($totalAmount);
        
        // number of transactions
        $nbTransactions = UtilsManager::createTableDataLine(array(
                                                                array(_('Number of sent transactions'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(count($aRecords), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($nbTransactions);
        unset($nbTransactions);
        
        // ServiceBureau Execution Date
        $dateSBLine    = UtilsManager::createTableDataLine(array(
                                                                array(_('SB processing date and time'), 20, $this->defaultHorizontalTitleLayout),
                                                                array(Utils::formatDate($sentFile->dlrcdat.$sentFile->dlrctim), 0, $this->defaultHorizontalValueLayout)
                                                            ));
        $table->addDataLine($dateSBLine);
        unset($dateSBLine);
        
        // date of the sent file
        $dateSentFileLine    = UtilsManager::createTableDataLine(array(
                                                                        array(_('Date and time of sent file'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array(Utils::formatDate($sentFile->dlptdat.$sentFile->dlpttim), 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($dateSentFileLine);
        unset($dateSentFileLine);
        
        // Date and time of delivery notification
        $date               = Utils::formatDate(substr(reset($aRecords)->wtsptrans, 0, 19));
        $deliveryDate       = UtilsManager::createTableDataLine(array(
                                                                        array(_('Date and time of delivery notification'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array($date, 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($deliveryDate);
        unset($deliveryDate);
        
        // delivery notification status
        if (reset($aRecords)->transStatus == 'ACK') {
            $status = _('Acknowledged');
        } else if (reset($aRecords)->transStatus == 'NAK') {
            $status = _('Not Acknowledged');
        } else {
            $status = _('Unknown');
        }
        $deliveryStatus     = UtilsManager::createTableDataLine(array(
                                                                        array(_('Delivery notification status'), 20, $this->defaultHorizontalTitleLayout),
                                                                        array($status, 0, $this->defaultHorizontalValueLayout)
                                                                        )
                                                                );
        $table->addDataLine($deliveryStatus);
        unset($deliveryStatus);
        
        return $table;
    }   
    
    private function generateRecordsByCompanies($aRecordsByCompanies, $aCompanies)
    {
        $aSections  = array();
        
        $layoutEvenLines    = clone $this->defaultLayout;
        $layoutEvenLines->setFillColor(new Layout_Color(array(235,230,225)));
        foreach ($aRecordsByCompanies as $companyCode=>&$aRecords) {
            $companySection                     = new Section();
            $companySection->isCompactView      = true;
            $companySection->pageBreakTrigger   = Section::PAGE_BREAK_TRIGGER_DEFAULT + RecordSection::PAGE_BREAK_TRIGGER_DEFAULT;
            $companySection->main               = $this->generateCompanyDetailTable($aCompanies[$companyCode]);
            
            $aRecordsSections   = array();
            
            $layout             = $layoutEvenLines;
            foreach ($aRecords as &$record) {
                if (!$layout) {
                    $layout     = $layoutEvenLines;
                } else {
                    $layout     = null;
                }
                $aRecordsSections[]         = $this->generateRecordSection($record, $layout);
            }

            $companySection->aSubSections   = $aRecordsSections;

            $aSections[]                    = $companySection;
            unset($companySection, $layout);
        }
        
        return $aSections;
    }
    
    private function generateSignaturesSection($aRecords)
    {
        $section    = null;
        
        $aSignatures            = Record_Manager::getSignatures($aRecords, false);
        if (is_array($aSignatures) AND count($aSignatures)) {
            $section    = new Section();

            // init the table
            $table  = new Table();
            $table->setDefaultLayout($this->defaultLayout);
            $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
            $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

            // header
            $header     = UtilsManager::createTableHeader(array(
                                                                array(_('Signatures'), 0)
                                                            ));
            $table->setHeader($header);
            unset($header);
            
            $aSignatures            = UtilsArray::array_std_sort($aSignatures, 'tspstatut', SORT_ASC);
            $pageBreakTrigger       = 10;
            
            $lastKey    = null;
            $done       = false;
            
            while (!$done) {
                $sSignatures            = '';
                $i                      = 0;
                foreach ($aSignatures as $key=>&$signature) {
                    if ($lastKey    !== null) {
                        if ($lastKey == $key) {
                            $lastKey    = null;
                        } else {
                            continue;
                        }
                    }

                    if ($sSignatures != '') {
                        $sSignatures    .= "\n";
                    }
                    $sSignatures        .= _('Status').' : '.$signature->statut."   "._('User').' : '.$signature->usrstatut."   "._('Timestamp').' : '.$signature->tspstatut."   "._('Reason').' : '.$signature->rmqstatut; 

                    $pageBreakTrigger   += 5;
                    $i++;
                    if ($i > 25) {
                        $lastKey        = $key;
                        break(1);
                    }

                    if ($signature == end($aSignatures)) {
                        $done           = true;
                    }
                }
                $signaturesLine     = UtilsManager::createTableDataLine(array(array($sSignatures, 0, $this->defaultHorizontalValueLayout)));
                $table->addDataLine($signaturesLine);
            }
            unset($signaturesLine, $aSignatures);
            
            $section->main              = $table;
            $section->pageBreakTrigger  = $pageBreakTrigger;
        }
        
        return $section;
    }
    
    /**
     * Generate a line containing Companies
     * @param Array $aCompanies
     * @return array 
     */
    public function generateCompaniesSection($aCompanies)
    {
        $aCompaniesLines    = array();

        if (is_array($aCompanies) AND count($aCompanies)) {
            
            $lastKey    = null;
            $done       = false;
            while (!$done) {
                $i                      = 0;
                $companiesLine          = null;
                $companyNames           = array();
                $companyCodes           = array();
                foreach ($aCompanies as $key=>&$company) {
                    if ($lastKey    !== null) {
                        if ($lastKey == $key) {
                            $lastKey    = null;
                        } else {
                            continue;
                        }
                    }
                    
                    $companyNames[]     = array(trim($company->sonomsoc), 50, $this->defaultHorizontalValueLayout);
                    $companyCodes[]     = array(trim($company->socodsoc), 30, $this->defaultHorizontalValueLayout);

                    $i++;
                    if ($i > 25) {
                        $lastKey        = $key;
                        break;
                    }
                    
                    if ($company == end($aCompanies)) {
                        $done           = true;
                    }
                }
                
                $companiesLine  = UtilsManager::createTableDataLine(array(
                                                                        array(_('Companies'), 20, $this->defaultHorizontalTitleLayout),
                                                                        $companyNames,
                                                                        $companyCodes
                                                                    ));
                $aCompaniesLines[]      = $companiesLine;
                unset($companiesLine);
            }
            
        }
        return $aCompaniesLines;
    }
    
    /**
     * Generate a line containing sent files
     * @param Array $aSentFiles
     * @return TableDataLine 
     */
    public function generateSentFilesSectionOld($aSentFiles, $nbMaxFirstPage = self::MAX_NB_LINES_SENT_FILES)
    {
        $aSentFilesLines        = array();
        if (is_array($aSentFiles) AND count($aSentFiles)) {
            $nbMaxLinesPage     = $nbMaxFirstPage;
            $lastKey            = null;
            $done               = false;
            
            while (!$done) {
                $i                      = 0;
                $aFileNames             = array();
                $aDateAmountTitles      = array();
                $aDateAmountValues      = array();
                
                foreach ($aSentFiles as $key=>&$sentFile) {
                    if ($lastKey    !== null) {
                        if ($lastKey == $key) {
                            $lastKey    = null;
                        } else {
                            continue;
                        }
                    }

                    $aRecords           = $sentFile->getRecords($this->currentCentralFile->wnumfic);

                    $filename = explode('-', $sentFile->dlfileput);

                    $aFileNames[]               = array($filename[1], 50, $this->defaultHorizontalValueLayout);
                    $aDateAmountTitles[]        = array(_('Date and time of sent file'), 15, $this->defaultHorizontalTitleLayout);
                    $aDateAmountValues[]        = array(Utils::formatDate($sentFile->dlptdat.str_pad($sentFile->dlpttim, 6, '0', STR_PAD_LEFT)), 10, $this->defaultHorizontalValueLayout);

                    if ($aRecords AND count($aRecords)) {
                        $aDateAmountTitles[]    = array(_('Total Amount'), 15, $this->defaultHorizontalTitleLayout);
                        $aDateAmountValues[]    = array(Record_Manager::formatTotalAmount($aRecords), 10, $this->defaultHorizontalValueLayout);
                    }

                    $i++;
                    if ($i > $nbMaxLinesPage) {
                        $lastKey        = $key;
                        break(2);
                    }

                    if ($sentFile == end($aSentFiles)) {
                        $done           = true;
                    }
                }
                
                $sentFiles  = UtilsManager::createTableDataLine(array(
                                                                    array(_('Name of files sent to bank'), 25, $this->defaultHorizontalTitleLayout),
                                                                    $aFileNames,
                                                                    $aDateAmountTitles,
                                                                    $aDateAmountValues
                                                                ));
                $aSentFilesLines[]      = $sentFiles;
                unset($sentFiles);
                
                $nbMaxLinesPage      = self::MAX_NB_LINES_SENT_FILES;
            }
            
            unset($aFileNames, $aDateAmountTitles, $aDateAmountValues);
        }
        
        return $aSentFilesLines;
    }
    
    
    /**
     * Generate a line containing sent files
     * @param Array $aSentFiles
     * @return Section 
     */
    public function generateSentFilesSection($aSentFiles, $nbMaxFirstPage = self::MAX_NB_LINES_SENT_FILES)
    {        
        $section        = new Section();
        if (is_array($aSentFiles) AND count($aSentFiles)) {
            // init the table
            $table  = new Table();
            $table->setDefaultLayout($this->defaultLayout);
            $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
            $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
            
            $nbMaxLinesPage     = $nbMaxFirstPage;
            $lastKey            = null;
            $done               = false;
            
            while (!$done) {
                $i                      = 0;
                $aFileNames             = array();
                $aDateAmountTitles      = array();
                $aDateAmountValues      = array();
                
                foreach ($aSentFiles as $key=>&$sentFile) {
                    if ($lastKey    !== null) {
                        if ($lastKey == $key) {
                            $lastKey    = null;
                        } else {
                            continue;
                        }
                    }

                    $aRecords           = $sentFile->getRecords($this->currentCentralFile->wnumfic);

                    $filename = explode('-', $sentFile->dlfileput);

                    $aFileNames[]               = array($filename[1], 50, $this->defaultHorizontalValueLayout);
                    $aDateAmountTitles[]        = array(_('Date and time of sent file'), 15, $this->defaultHorizontalTitleLayout);
                    $aDateAmountValues[]        = array(Utils::formatDate($sentFile->dlptdat.str_pad($sentFile->dlpttim, 6, '0', STR_PAD_LEFT)), 15, $this->defaultHorizontalValueLayout);

                    if ($aRecords AND count($aRecords)) {
                        $aDateAmountTitles[]    = array(_('Total Amount'), 15, $this->defaultHorizontalTitleLayout);
                        $aDateAmountValues[]    = array(Record_Manager::formatTotalAmount($aRecords), 15, $this->defaultHorizontalValueLayout);
                    }
                    $i++;
                    if ($i > $nbMaxLinesPage) {
                        $lastKey        = $key;
                        break(2);
                    }

                    if ($sentFile == end($aSentFiles)) {
                        $done           = true;
                    }
                }
                
                $sentFiles  = UtilsManager::createTableDataLine(array(
                                                                    array(_('Name of files sent to bank'), 20, $this->defaultHorizontalTitleLayout),
                                                                    $aFileNames,
                                                                    $aDateAmountTitles,
                                                                    $aDateAmountValues
                                                                ));
                $table->addDataLine($sentFiles);
                unset($sentFiles);
                
                $nbMaxLinesPage      = self::MAX_NB_LINES_SENT_FILES;
            }
            
            $section->main              = $table;
            $section->pageBreakTrigger  = 10*$nbMaxFirstPage;
            
            unset($aFileNames, $aDateAmountTitles, $aDateAmountValues, $table);
        }
        
        return $section;
    }
    
    /**
     * Generate a section that details a Company
     * @param Company $company 
     * return Table
     */
    public function generateCompanyDetailTable(Company $company)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // header
        $header     = UtilsManager::createTableHeader(array(
                                                            array(_('Company'), 0)
                                                        ));
        $table->setHeader($header);
        unset($header);        
        
        // company name
        $nameLine   = UtilsManager::createTableDataLine(array(
                                                            array(_('Company name'), 20, $this->defaultHorizontalTitleLayout),
                                                            array(trim($company->sonomsoc).', '.$company->sovillesoc, 0, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($nameLine);
        unset($nameLine);
        
        // company Code
        $codeLine   = UtilsManager::createTableDataLine(array(
                                                            array(_('Company code'), 20, $this->defaultHorizontalTitleLayout),
                                                            array(trim($company->socodsoc), 0, $this->defaultHorizontalValueLayout)
                                                        ));
        $table->addDataLine($codeLine);
        unset($codeLine);
        
        
        return $table;
    }
       
    /**
     * Generate a section with informations of a record
     * @param Record $record
     * @return RecordSection 
     */
    private function generateRecordSection(Record $record = null, $lineLayout = null)
    {
        // init the table
        $table = new Table();
        if (!$lineLayout) {
            $lineLayout = $this->defaultLayout;
        }
        
        $table->setDefaultLayout($lineLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['refint']   = 8;
        $aColumnsWidths['rsbene']   = 18;
        $aColumnsWidths['ibancad']  = 8;
        $aColumnsWidths['bbancad']  = 18;
        $aColumnsWidths['ibancac']  = 8;
        $aColumnsWidths['bbancac']  = 18;
        $aColumnsWidths['datexe']   = 6;
        $aColumnsWidths['datval']   = 6;
        $aColumnsWidths['devord']   = 3;
        $aColumnsWidths['amount']   = 7;
        
        // header of the section
        $header             = UtilsManager::createTableHeader(array (
                                                                    array(_('Transaction ref'),             $aColumnsWidths['refint']),
                                                                    array(_('Receiver'),                    $aColumnsWidths['rsbene']),
                                                                    array(_('Debit Bank'),                  $aColumnsWidths['ibancad']),
                                                                    array(_('Debit account'),               $aColumnsWidths['bbancad']),
                                                                    array(_('Credit Bank'),                 $aColumnsWidths['ibancac']),
                                                                    array(_('Credit account'),              $aColumnsWidths['bbancac']),
                                                                    array(_('Execution'),                   $aColumnsWidths['datexe']),
                                                                    array(_('Value'),                       $aColumnsWidths['datval']),
                                                                    array(_('Cur'),                         $aColumnsWidths['devord']),
                                                                    array(_('Amount'),                      $aColumnsWidths['amount']),
                                                                    )
                                                             );
        $table->setHeader($header);
        unset($header);
        
        // record informations
        $recordLine         = UtilsManager::createTableDataLine(array (
                                                                    array($record->getOperationReference(),                                         $aColumnsWidths['refint'], $this->defaultHorizontalValueLayout),
                                                                    array(preg_replace('/\s+/', ' ',$record->rsbene),                               $aColumnsWidths['rsbene'], $this->defaultHorizontalValueLayout),
                                                                    array($record->bicbqcad,                                                        $aColumnsWidths['ibancad'], $this->defaultHorizontalValueLayout),
                                                                    array(($record->ibancad)?$record->ibancad:$record->bbancad,                     $aColumnsWidths['bbancad'], $this->defaultHorizontalValueLayout),
                                                                    array($record->bicbqcac,                                                        $aColumnsWidths['ibancac'], $this->defaultHorizontalValueLayout),
                                                                    array(($record->ibancac)?$record->ibancac:$record->bbancac,                     $aColumnsWidths['bbancac'], $this->defaultHorizontalValueLayout),
                                                                    array(Utils::formatDate($record->datexe, 'Y-m-d'),                              $aColumnsWidths['datexe'], $this->defaultHorizontalValueLayout),
                                                                    array(Utils::formatDate($record->datval, 'Y-m-d'),                              $aColumnsWidths['datval'], $this->defaultHorizontalValueLayout),
                                                                    array($record->devord,                                                          $aColumnsWidths['devord'], $this->defaultHorizontalValueLayout),
                                                                    array($record->getAmountToDisplay(),                                            $aColumnsWidths['amount'], $this->defaultHorizontalTitleLayout),
                                                                    )
                                                             );
        $table->addDataLine($recordLine);
        unset($recordLine);
        
        // validations
        $aSignatures            = $record->getSignatures();
        if (is_array($aSignatures) AND count($aSignatures)) {
            $sSignatures            = '';
            foreach ($aSignatures as &$signature) {
                if ($sSignatures != '') {
                    $sSignatures    .= "\n";
                }
                $sSignatures        .= _('Status').' : '.$signature->statut.' '._('User').' : '.$signature->usrstatut.' '._('Timestamp').' : '.$signature->tspstatut.(($signature->rmqstatut)?"   "._('Reason').' : '.$signature->rmqstatut:'');
                
                $signature->__destruct();
                unset($signature);
            }
            unset($aSignatures);
            
            $signaturesLine     = UtilsManager::createTableDataLine(array(array($sSignatures, 0, $this->defaultHorizontalValueLayout)));
            $table->addDataLine($signaturesLine);
            
            unset($signaturesLine);
        }
        
        $recordSection          = new Section();
        $recordSection->main    = $table;
        unset($table);
        
        return $recordSection;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //                              LISTS                                     //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    
    /**
     * Create a pdf file of central files
     * @param array     $aCentralFiles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createCentralFilesArchiveListPdf($aCentralFiles, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Central Files Archive");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        
        $cfTable            = $this->generateCentralFilesTable($aCentralFiles);
        $cfTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aCentralFiles));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Create a pdf file of central files
     * @param array     $aCentralFiles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createCentralFilesHistoryListPdf($aCentralFiles, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Central Files History");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        
        $cfTable            = $this->generateCentralFilesTable($aCentralFiles);
        $cfTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aCentralFiles));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Create a pdf file of central files
     * @param array     $aCentralFiles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createCentralFilesLiveListPdf($aCentralFiles, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Central Files");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        
        $cfTable            = $this->generateCentralFilesTableLive($aCentralFiles);
        $cfTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aCentralFiles));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Generate the central files list table
     * @param type $aCentralFiles
     * @return Table
     */
    private function generateCentralFilesTable($aCentralFiles)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['fileName']     = 36;
        $aColumnsWidths['type']         = 7;
        $aColumnsWidths['fileID']       = 9;
        $aColumnsWidths['creationDate'] = 12;
        $aColumnsWidths['sentDate']     = 10;
        $aColumnsWidths['total']        = 4;
        $aColumnsWidths['V']            = 4;
        $aColumnsWidths['R']            = 4;
        $aColumnsWidths['currencies']   = 4;
        $aColumnsWidths['totalAmount']  = 10;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('File Name'),   $aColumnsWidths['fileName']),
                array(_('Type'),        $aColumnsWidths['type']),
                array(_('File ID'),     $aColumnsWidths['fileID']),
                array(_('Creation'),    $aColumnsWidths['creationDate']),
                array(_('Sent'),        $aColumnsWidths['sentDate']),
                array(_('Total'),       $aColumnsWidths['total']),
                array(_('V'),           $aColumnsWidths['V']),
                array(_('R'),           $aColumnsWidths['R']),
                array(_('Cur'),         $aColumnsWidths['currencies']),
                array(_('Total Amount'),$aColumnsWidths['totalAmount']),
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aCentralFiles as $cf) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }
            
            $layoutAlignRight   = clone $layout;
            $layoutAlignRight->setAlign('R');
            
            $currencies = '';
            if (count($cf->currency) > 1) {
                $currencies = 'X'.count($cf->currency);
            } else {
                $currencies = $cf->currency[0];
            }
            
            // access list informations
            $cfLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($cf->wnomfic),                           $aColumnsWidths['fileName'],            $layout),
                    array(trim($cf->typope),                            $aColumnsWidths['type'],                $layout),
                    array(trim($cf->wnumfic),                           $aColumnsWidths['fileID'],              $layout),
                    array($cf->wtspcreat,                               $aColumnsWidths['creationDate'],        $layout),
                    array($cf->getSentDateToDisplay(),                  $aColumnsWidths['sentDate'],            $layout),
                    array($cf->wnbrec,                                  $aColumnsWidths['total'],               $layout),
                    array($cf->wnbrecval,                               $aColumnsWidths['V'],                   $layout),
                    array($cf->wnbrecrej,                               $aColumnsWidths['R'],                   $layout),
                    array($currencies,                                  $aColumnsWidths['currencies'],          $layout),
                    array($cf->getAmountToDisplay(),                    $aColumnsWidths['totalAmount'],         $layoutAlignRight),
                )
            );
            $table->addDataLine($cfLine);
            unset($cfLine);

            $i++;
        }

        return $table;
    }
    
    /**
     * Generate the central files list table
     * @param type $aCentralFiles
     * @return Table
     */
    private function generateCentralFilesTableLive($aCentralFiles)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['fileName']     = 36;
        $aColumnsWidths['type']         = 7;
        $aColumnsWidths['fileID']       = 9;
        $aColumnsWidths['creationDate'] = 7;
        $aColumnsWidths['sentDate']     = 8;
        $aColumnsWidths['total']        = 4;
        $aColumnsWidths['B']            = 3;
        $aColumnsWidths['W']            = 3;
        $aColumnsWidths['1']            = 3;
        $aColumnsWidths['G']            = 3;
        $aColumnsWidths['V']            = 3;
        $aColumnsWidths['R']            = 3;
        $aColumnsWidths['currencies']   = 3;
        $aColumnsWidths['totalAmount']  = 8;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('File Name'),   $aColumnsWidths['fileName']),
                array(_('Type'),        $aColumnsWidths['type']),
                array(_('File ID'),     $aColumnsWidths['fileID']),
                array(_('Creation'),    $aColumnsWidths['creationDate']),
                array(_('Sent'),        $aColumnsWidths['sentDate']),
                array(_('Total'),       $aColumnsWidths['total']),
                array(_('B'),           $aColumnsWidths['B']),
                array(_('W'),           $aColumnsWidths['W']),
                array(_('1'),           $aColumnsWidths['1']),
                array(_('G'),           $aColumnsWidths['G']),
                array(_('V'),           $aColumnsWidths['V']),
                array(_('R'),           $aColumnsWidths['R']),
                array(_('Cur'),         $aColumnsWidths['currencies']),
                array(_('Total Amount'),$aColumnsWidths['totalAmount']),
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aCentralFiles as $cf) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }
            
            $layoutAlignRight   = clone $layout;
            $layoutAlignRight->setAlign('R');
            
            $currencies = '';
            if (count($cf->currency) > 1) {
                $currencies = 'X'.count($cf->currency);
            } else {
                $currencies = $cf->currency[0];
            }
            
            // access list informations
            $cfLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($cf->wnomfic),                           $aColumnsWidths['fileName'],            $layout),
                    array(trim($cf->typope),                            $aColumnsWidths['type'],                $layout),
                    array(trim($cf->wnumfic),                           $aColumnsWidths['fileID'],              $layout),
                    array(Utils::formatDate($cf->wtspcreat, 'Y-m-d'),   $aColumnsWidths['creationDate'],        $layout),
                    array($cf->getSentDateToDisplay(),                  $aColumnsWidths['sentDate'],            $layout),
                    array($cf->wnbrec,                                  $aColumnsWidths['total'],               $layout),
                    array($cf->blocked,                                 $aColumnsWidths['B'],                   $layout),
                    array($cf->wnbrecprog,                              $aColumnsWidths['W'],                   $layout),
                    array($cf->wnbrecsig,                               $aColumnsWidths['1'],                   $layout),
                    array($cf->blockedAfter,                            $aColumnsWidths['G'],                   $layout),
                    array($cf->wnbrecval,                               $aColumnsWidths['V'],                   $layout),
                    array($cf->wnbrecrej,                               $aColumnsWidths['R'],                   $layout),
                    array($currencies,                                  $aColumnsWidths['currencies'],          $layout),
                    array($cf->getAmountToDisplay(),                    $aColumnsWidths['totalAmount'],         $layoutAlignRight),
                )
            );
            $table->addDataLine($cfLine);
            unset($cfLine);

            $i++;
        }

        return $table;
    }
    
    /**
     * Create a pdf file of records
     * @param array     $aRecords
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createRecordsHistoryListPdf($aRecords, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Records History");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        
        $recordTable        = $this->generateRecordsTable($aRecords);
        $recordTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aRecords));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Create a pdf file of records
     * @param array     $aRecords
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createRecordsArchiveListPdf($aRecords, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Records Archive");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        
        $recordTable        = $this->generateRecordsTable($aRecords);
        $recordTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aRecords));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Create a pdf file of records
     * @param array     $aRecords
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createRecordsLiveListPdf($aRecords, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle        = _("Records");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        unset($aFilters);
        
        $legendTable        = $this->generateLegendTable();
        $legendTable->PDFexport($this->pdf, true, true);
        unset($legendTable);
        
        $recordTable        = $this->generateRecordsTable($aRecords);
        $recordTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aRecords));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the records list table
     * @param Array $aRecords
     * @return Table
     */
    private function generateRecordsTable($aRecords)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['company']      = 15;
        $aColumnsWidths['type']         = 10;
        $aColumnsWidths['bank']         = 10;
        $aColumnsWidths['account']      = 20;
        $aColumnsWidths['currency']     = 4;
        $aColumnsWidths['amount']       = 10;
        $aColumnsWidths['receiver']     = 18;
        $aColumnsWidths['exec']         = 8;
        $aColumnsWidths['status']       = 5;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Company'),     $aColumnsWidths['company']),
                array(_('Type'),        $aColumnsWidths['type']),
                array(_('Bank'),        $aColumnsWidths['bank']),
                array(_('Account'),     $aColumnsWidths['account']),
                array(_('Cur'),         $aColumnsWidths['currency']),
                array(_('Amount'),      $aColumnsWidths['amount']),
                array(_('Receiver'),    $aColumnsWidths['receiver']),
                array(_('Execution'),   $aColumnsWidths['exec']),
                array(_('Status'),      $aColumnsWidths['status']),
            )
        );
        $table->setHeader($header);
        unset($header);
        
        $aCompanies         = Company_Manager::getCompanies(null, null, null, false);
        
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aRecords as $record) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }
            
            $layoutAlignRight   = clone $layout;
            $layoutAlignRight->setAlign('R');
            
            $sCompany       = '$record->societe';
            if (isset($aCompanies[$record->societe])) {
                $sCompany   = trim($aCompanies[$record->societe]->sonomsoc).' ('.$record->societe.')';
            }
            
            $bic            = $record->bicbqcad;
            $acccount       = ($record->ibancad)?$record->ibancad:$record->bbancad;
            if (substr($record->typope, -4) == 'PREL' OR substr($record->typope, -3) == 'LCR' OR substr($record->typope, -4) == 'DOM80') {
                $bic        = $record->bicbqcac;
                $acccount   = ($record->ibancac)?$record->ibancac:$record->bbancac;
            }
            
            // access list informations
            $recordLine     = UtilsManager::createTableDataLine(
                array (
                    array($sCompany,                                        $aColumnsWidths['company'],         $layout),
                    array(trim($record->typope),                            $aColumnsWidths['type'],            $layout),
                    array(trim($bic),                                       $aColumnsWidths['bank'],            $layout),
                    array(trim($acccount),                                  $aColumnsWidths['account'],         $layout),
                    array(trim($record->devord),                            $aColumnsWidths['currency'],        $layout),
                    array($record->getAmountToDisplay(),                    $aColumnsWidths['amount'],          $layoutAlignRight),
                    array(trim(preg_replace('/\s+/', ' ',$record->rsbene)), $aColumnsWidths['receiver'],        $layout),
                    array(Utils::formatDate($record->datexe, 'Y-m-d'),      $aColumnsWidths['exec'],            $layout),
                    array(trim($record->wcodvru),                           $aColumnsWidths['status'],          $layout),
                )
            );
            $table->addDataLine($recordLine);
            unset($recordLine);
            $record->__destruct();
            
            $i++;
        }
        
        return $table;
    }
    
    public function generateLegendTable()
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $table->setWidth(50);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['key']          = 10;
        $aColumnsWidths['desc']         = 90;
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('B',                                      $aColumnsWidths['key']),
                array(_('Blocked before'),                      $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('W',                                      $aColumnsWidths['key']),
                array(_('Pending'),                             $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('1',                                      $aColumnsWidths['key']),
                array(_('Pending countersign'),                 $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('V',                                      $aColumnsWidths['key']),
                array(_('Validated'),                           $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('G',                                      $aColumnsWidths['key']),
                array(_('Blocked after'),                       $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        $legendLine     = UtilsManager::createTableDataLine(
            array (
                array('R',                                      $aColumnsWidths['key']),
                array(_('Rejected'),                            $aColumnsWidths['desc']),
            )
        );
        $table->addDataLine($legendLine);
        
        return $table;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //                              UTILS                                     //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    
    /**
     * uses a single loop for multiple processes
     * @param type $aRecords 
     * return Array  An array of all processes results
     */
    public function processRecords($aRecords)
    {
        if (!is_array($aRecords)) {
            $aRecords   = array();
        }
        
        $aSortedRecords = array();
        $aCompanies     = array();
        foreach ($aRecords as &$record) {
            //getting companies
            if (!isset($aCompanies[$record->societe])) {
                $aCompanies[$record->societe]   = $record->getCompany();
            }
            
            // sorting records by status code
            $status = $record->wcodvru;
            if ($status === 'B' AND $record->wtspvru) {
                $status = 'G';
            } else if ($status === 'V' AND $record->wfiloutput) {
                // Sent to bank
                $status = 'S';
            }
            if (!isset($aSortedRecords[$status])) {
                $aSortedRecords[$status]   = array();
            }
            $aSortedRecords[$status][] = $record;
        }
        
        return array($aSortedRecords, $aCompanies);
    }
    
    public function sortRecordsByCompanies($aRecords)
    {
        $aRecordsByCompanies = array();
        
        if ($aRecords) {
            foreach ($aRecords as &$record) {
                if (!isset($aRecordsByCompanies[$record->societe])) {
                    $aRecordsByCompanies[$record->societe]  = array();
                }
                $aRecordsByCompanies[$record->societe][]    = $record;
            }
        }
        
        return $aRecordsByCompanies;
    }
    
    public function sortRecordsBySentFiles($aRecords)
    {
        $aRecordsBySentFiles = array();
        
        if ($aRecords) {
            foreach ($aRecords as &$record) {
                $sentFile   = $record->getSentFile();
                if ($sentFile) {
                    if (!isset($aRecordsBySentFiles[$sentFile->dlfileput])) {
                        $aRecordsBySentFiles[$sentFile->dlfileput]  = array();
                    }
                    $aRecordsBySentFiles[$sentFile->dlfileput][]    = $record;
                }
            }
        }
        
        return $aRecordsBySentFiles;
    }
}

?>
