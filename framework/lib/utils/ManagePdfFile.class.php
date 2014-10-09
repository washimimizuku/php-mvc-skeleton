<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ManagePdfFile
 *
 * @author yj
 */
require_once(getenv('app_root').'/lib/workflow/PDF.class.php');
require_once(getenv('app_root').'/lib/utils/Utils.class.php');

class ManagePdfFile {
    
    //Static var
    public Static $directory       = '';
    public Static $db              = ''; 
    public Static $outPutDirectory = '';
    //Attributes
    public $fileName               = '';
    public $companyName            = '';
    public $operationType          = '';
    public $detail                 = '';
    public $fileArray              = array();
    public $auditRejectedArray     = array();
    public $auditValidArray        = array();
    public $currencyArray          = array();

    //String constants
    const XML_EXTENSION            = 'xml';
    const PDF_EXTENSION            = 'pdf';
    const MD5_EXTENSION            = 'md5'; 
    const FILENAME_SEPARATOR       = '_';
    const DIRECTORY_SEPARATOR      = '/';
    
    //file state
    const NO_DETAILS               = 'ND';
    const DETAILS                  = 'D';
    const ALL                      = 'ALL';
    
    //language
    const fr_FR                    = 'fr_FR';
    const en_UK                    = 'en_UK';
    
    //Ip 
    const SERVER_DEV               = 'linuxdev.datasphere.ch';
    
  
    
    public function __construct() {
        
        $this->directory          = '';
        $this->companyName        = '';
        $this->operationType      = '';
        $this->detail             = '';
        $this->fileName           = '';
        $this->auditRejectedArray = array();
        $this->auditValidArray    = array();
        
    }
    
    public function init(){
        
        $this->directory          = '';
        $this->companyName        = '';
        $this->operationType      = '';
        $this->detail             = '';
        $this->auditRejectedArray = array();
        $this->auditValidArray    = array();
        $this->currencyArray      = array();
        
    }
    
    /**
     * Scan xml dir archive directory 
     * @param String $directory
     * @return array 
     */
    public function scanDir($directory){
        $dirArray = array();
        if(is_dir($directory)){
            $dir = opendir($directory);
            if($dir){
                while(false !== ($f = readdir($dir))){

                     if($f!='..' AND $f !='.' ){
                            $dirArray[] = $f;
                     }              
                 }
             }
            return $dirArray;
        } else {
            
            echo 'Directory '.$directory. ' not found';
            die();
        }
    }
    
    
     /**
     * Scan xml archive directory 
     * @param String $directory root path of the pdf archives directory (required)
     * @return array 
     */
    public function scanDirXml($directory){
        if(is_dir($directory)){
            $dir = opendir($directory);
            if($dir){
                while(false !== ($f = readdir($dir))){

                     if($f!='..' AND $f !='.' AND (strtolower(substr($f,-3)) == self::XML_EXTENSION)){
                            $this->fileArray[] = $f;
                        }              
                }
            }
            return $this->fileArray;
        } else {
            
            echo 'Directory '.$directory. ' not found';
            die();
        }
    }
    
     /**
     * Scan pdf archive directory with global filter
     * @param String $rootPath root path of the pdf archives directory (required)
     * @param String $search search string 
     * @param String $bei bei of the customer (db) (required)     
     */
      public static function scanDirGlobalSearch($rootPath,$search, $bei){
         
         if($search){
         
          $fileArrayResult=glob("{".$rootPath.self::DIRECTORY_SEPARATOR.$bei.'*'.$search.'*'.self::PDF_EXTENSION."}",GLOB_BRACE);
            if( is_array($fileArrayResult) AND (sizeof($fileArrayResult) > 0)) {
                return $fileArrayResult;
            } else {
                return FALSE;
            }
         }
         
     }
    
     /**
     * Scan pdf archive directory with filter
     * @param String $rootPath root path of the pdf archives directory (required)
     * @param String $bei bei of the customer (db) (required)
     * @param String $operationType operation type 
     * @param String $idCompany id of the company
     * @param String $date date of the file archive in the format YEAR-MONTH (xxxx-yy)
     * @param String $state ManagePdfFile::DETAIL / ManagePdfFile::NO_DETAIL / ManagePdfFile::ALL filter on detail of transaction 
     * @return array OR boolean result of the dir scan filter OR FALSE if there is no match
     */
    public static function scanDirPdfArchives($rootPath ,$bei , $operationType = '', $idCompany = '', $date = '', $state = self::ALL) {
        //init filter
        $fileFilter      = '';
        $fileArrayResult = array();
        
        if($rootPath AND $bei){
            $fileFilter = $bei;
            
            //filter on operation type
            if($operationType){
                $fileFilter .= self::FILENAME_SEPARATOR.$operationType;
             } else {
                $fileFilter .=self::FILENAME_SEPARATOR.'*';
            }
            
            //filter on company ID
            if($idCompany){
                
                //if company ID is a number we have to pad the string with 10 characters 
                if(preg_match("/^[0-9]*$/",$idCompany)){
                  $idCompany = str_pad($idCompany, 10, "0", STR_PAD_LEFT);
                }
                
                $fileFilter .=self::FILENAME_SEPARATOR.$idCompany.'*';
             } else {
                $fileFilter .=self::FILENAME_SEPARATOR.'*';
            }
            
            //filter on archive date
            if($date){               
                $fileFilter .=self::FILENAME_SEPARATOR.$date.'*';
            } else {
                $fileFilter .=self::FILENAME_SEPARATOR.'*';
            }
            //filter on detail
            switch ($state){
                case self::DETAILS :
                    $fileFilter .=self::FILENAME_SEPARATOR.self::DETAILS.'*'; 
                    break;
                
                case self::NO_DETAILS :
                     $fileFilter .=self::FILENAME_SEPARATOR.self::NO_DETAILS.'*';
                    break;                                  
            }
                     
            $fileArrayResult=glob("{".$rootPath.self::DIRECTORY_SEPARATOR.$fileFilter.self::PDF_EXTENSION."}",GLOB_BRACE);
            if( is_array($fileArrayResult) AND (sizeof($fileArrayResult) > 0)) {
                return $fileArrayResult;
            } else {
                return FALSE;
            }  
        } else {
            return FALSE;
        }
        
    }

     /**
     * Scan pdf archive directory with filter
     * @param String $rootPath root path of the pdf archives directory (required)
     * @param String $bei bei of the customer (db) (required)
     * @param Array $operationTypes list of operation typesas returned from Authorizations::getAuthorizedOperationTypes()
     * @param Array $companies list of companies as returned from Authorizations::getAuthorizedCompanies()
     * @param String $date date of the file archive in the format YEAR-MONTH (xxxx-yy)
     * @param String $state ManagePdfFile::DETAILS / ManagePdfFile::NO_DETAILS / ManagePdfFile::ALL filter on detail of transaction 
     * @return array OR boolean result of the dir scan filter OR FALSE if there is no match
     */
    public function scanDirPdfArchivesWithPluralFilters($rootPath , $bei, $operationTypes = array(), $companies = array(), $date = array(), $state = self::ALL, $defaultState = self::ALL) {
        $files = array();
        
        if ($companies) {
             
            foreach($companies['access'] as $companyID => $company) {
                if ($operationTypes) {
                    foreach ($operationTypes['access'] as $opID => $operationType) {
                        if (isset($operationType["detail"])) {
                            if ($operationType["detail"]) {
                                $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], $companyID, $date, self::DETAILS);
                            } else{
                                $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], $companyID, $date, self::NO_DETAILS);
                            }
                        } else {
                            $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], $companyID, $date, $defaultState);
                        }
                        
                        if ($filesTemp) {
                            $files = array_merge($files,$filesTemp);
                        }
                    }
                } else {
                   
                    $filesTemp = self::scanDirPdfArchives($rootPath, $bei, '', $companyID, $date, $defaultState);
                    if ($filesTemp) {
                        $files = array_merge($files,$filesTemp);
                    }
                }
            }
        } elseif ($operationTypes) {
            foreach ($operationTypes['access'] as $opID => $operationType) {
                if (isset($operationType["detail"])) {
                    if ($operationType["detail"]) {
                        $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], '', $date, self::DETAILS);
                    } else{
                        $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], '', $date, self::NO_DETAILS);
                    }
                } else {
                    $filesTemp = self::scanDirPdfArchives($rootPath, $bei, $operationType['value'], '', $date, $defaultState);
                }
                
                if ($filesTemp) {
                    $files = array_merge($files,$filesTemp);
                }
            }
           
        } else {
            $filesTemp = self::scanDirPdfArchives($rootPath, $bei, '', '', $date, $defaultState);
                    
            if ($filesTemp) {
                $files = array_merge($files,$filesTemp);
            }
        }
        
        return $files;
    }
    
    public function getDataFromFilename($files) {
        $filesArray = array();
        
        foreach ($files as $file) {
            $completePath = $file;
            $filename = trim(strrchr($file, '/'), '/');
            $checksum = str_replace(self::PDF_EXTENSION, self::MD5_EXTENSION, $filename);

            $detail = strrchr($file, '_');
            $detail = str_replace('_', '', substr($detail, 0, strlen($detail) - strlen(strrchr($detail, '.'))));
            if ($detail == 'D') {
                $detail = _('Yes');
            } else {
                $detail = _('No');
            }
            
            $file = substr($file, 0, strlen($file) - strlen(strrchr($file, '_')));
            $date = strrchr($file, '_');
            $date = trim($date, '_');
            
            $file = substr($file, 0, strlen($file) - strlen(strrchr($file, '_')));
            $file = strrchr($file, '/');
            $file = trim($file, '/');
            $file = strstr($file, '_');
            $file = trim($file, '_');
            
            $company = strstr($file, '_');
            $company = trim($company, '_');

            $operationType = substr($file, 0, strlen($file) - strlen(strstr($file, '_')));
            
            $filesArray[] = array('filename' => $filename,
                                  'date' => $date,
                                  'detail' => $detail,
                                  'company' => $company,
                                  'operationType' => $operationType,
                                  'completePath' => $completePath,
                                  'checksum' => $checksum);
        }
        
        return $filesArray;
    }
    
     /**
     * Create PDF central file archives  
     * @param Array $centralFilesArchiveList array of the xml 
     * @param Boolean $withDetail if TRUE create pdf with transaction else without transactions
     * @param Array $custfileDateArray array of the custfiles(file name and date) of the sentFile node     
     * @return void
     */
     public function createCentralFileArchivePdf($centralFilesArchiveList, $withDetail = TRUE, $custfileDateArray = array(), $locale = 'fr_FR') {
       
        
        $this->setLanguage($locale);
        $this->setPdfDirectory($locale);
        $pdf = new PDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->headerTitle = _("Central files Archives");
        $pdf->SetFont('Arial', '', 8);
        $pdf->AddPage();
        
        foreach ($centralFilesArchiveList as &$centralFile) {

            //create central file summary header
            $pdf->redCell(0, _('Archive summary'), 'C');
            $pdf->Ln();
            
            //Create table central file type and month
            $centralFilesPDF    = array($centralFile->type, $centralFile->month);
            $archiveTableHeader = array(_('Operation type'), _('Processing month'));
            $columnsSize        = array(70, 68);
            $tableAlign         = array('L', 'L');
            $pdf->createHorizontalTable($archiveTableHeader, $columnsSize, $centralFilesPDF, $tableAlign);
            $pdf->Ln(7);

            //Create table socds and soc
            $centralFilesSocdsSoc       = array($centralFile->socds, $centralFile->soc);
            $archiveTableHeaderSocdsSoc = array(_('Company name'), _('Company code'));
            $pdf->createVerticalTable($archiveTableHeaderSocdsSoc, $centralFilesSocdsSoc, 70, 0);
            $pdf->Ln();
            $pdf->redCell(0, _('Files'), 'C');
            $pdf->Ln();

            $nfsnt = (isset($centralFile->nfsnt)) ? $centralFile->nfsnt : 0;
            $nfcus = (isset($centralFile->nfcus)) ? $centralFile->nfcus : 0;
            $ntrx  = (isset($centralFile->ntrx)) ? $centralFile->ntrx : 0;
            $ntrxv = (isset($centralFile->ntrxv)) ? $centralFile->ntrxv : 0;
            $ntrxr = (isset($centralFile->ntrxr)) ? $centralFile->ntrxr : 0;

            //Create tablenumber of sent files and number of received customer files
            $fileTableHeader = array(_('Number of files sent to bank'), _('Number of customer received files'));
            $fileData = array($nfsnt, $nfcus);
            $pdf->createVerticalTable($fileTableHeader, $fileData, 70, 0);
            $pdf->Ln();
            $pdf->redCell(0, _('Transactions'), 'C');
            $pdf->Ln();

            //create table of executed transactions sent transactions and rejected transactions
            $transactionTableHeader = array(_('Number of processed transactions'), _('Number of transactions sent to the bank'),
                                            _('Number of transactions rejected from user'));
            
            $transactionData        = array($ntrx, $ntrxv,
                                            $ntrxr);
            
            $pdf->createVerticalTable($transactionTableHeader, $transactionData, 70, 0);

            if (isset($centralFile->status)) {

                foreach ($centralFile->status as $status) {

                    $fileTableValidate = array(_('File state'));

                    if (isset($status->opcod) AND $status->opcod == 'V') {
                        // $pdf->createVerticalTable($fileTableValidate, $status->opcod, 50, 227);    
                    }

                    $pdf->Ln(2);

                    //custifle rejected
                    if (isset($status->custfile)) {

                        foreach ($status->custfile as $custfile) {
                            $pdf->AddPage();
                            $fid   = (isset($custfile->fid)) ? $custfile->fid : '';
                            $fnamc = (isset($custfile->fnamc)) ? $custfile->fnamc : '';
                            $tscre = (isset($custfile->tscre)) ? $custfile->tscre : '';
                            $nrec  = (isset($custfile->nrec)) ? $custfile->nrec : 0;
                            $nrecv = (isset($custfile->nrecv)) ? $custfile->nrecv : 0;
                            $nrecj = (isset($custfile->nrecj)) ? $custfile->nrecj : 0;
                            $ntrx  = (isset($custfile->ntrx)) ? $custfile->ntrx : 0;

                            $pdf->redCell(0, _('Initial file'), 'C');
                            $pdf->Ln();
                            $columnsSize    = array(205, 42, 30);
                            $tableAlign     = array('C', 'R', 'L');

                            $custFileTab    = array($fnamc, _('Integration date and time'), $tscre);
                            $pdf->createOneTable($custFileTab, $columnsSize, $tableAlign);
                            $pdf->Ln();

                            $custFileTitle3 = array(_('Number of rejected transactions'), _('Integration date and time'));
                            $custFileTab3   = array($ntrx, $tscre);

                            $pdf->createVerticalTable($custFileTitle3, $custFileTab3, 70, 0);
                            $pdf->Ln();

                            //record
                            if ($withDetail) {
                                if (isset($custfile->record)) {
                                    $recordFileTitle = array(_('Transaction ref'), _('Beneficiary'),
                                                             _('Debit bank'), _('Debit account'),
                                                             _('Credit bank'), _('Credit account'),
                                                             _('Exedate'), _('Valdate'),
                                                             _('Cur'), _('Amount'));


                                    $columnsSize = array(27, 50, 23, 45, 27, 45, 14, 14, 9, 23);
                                    $tableAlign  = array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'R');

                                    foreach ($custfile->record as $record) {

                                        $recordFileTab = array();
                                        $bicd  = (isset($record->bicd)) ? $record->bicd : '';
                                        $iband = (isset($record->iband)) ? $record->iband : '';
                                        $bband = (isset($record->bband)) ? $record->bband : '';
                                        //if the iban is not null it is displayed else the bbanc is displayed
                                        if (!$iband) {
                                            $iband = $bband;
                                        }
                                        $bicc  = (isset($record->bicc)) ? $record->bicc : '';
                                        $ibanc = (isset($record->ibanc)) ? $record->ibanc : '';
                                        $bbanc = (isset($record->bbanc)) ? $record->bbanc : '';

                                        if (!$ibanc) {
                                            $ibanc = $bbanc;
                                        }

                                        $refin = (isset($record->refin)) ? $record->refin : '';
                                        $devo  = (isset($record->devo)) ? $record->devo : '';
                                        $mont  = (isset($record->mont)) ? $record->mont : '';
                                        $dtval = (isset($record->dtval)) ? $record->dtval : '';
                                        //$dtcre = (isset($record->dtcre)) ? $record->dtcre : '';
                                        $dtexe = (isset($record->dtexe)) ? $record->dtexe : '';

                                        $benef = (isset($record->benef)) ? $record->benef : '';
                                        //truncate $benef string 
                                        $benef = Utils::truncate($benef, 26,'');
                                        
                                        $info1 = (isset($record->info1)) ? $record->info1 : '';
                                        //$rfmcl = (isset($record->rfmcl)) ? $record->rfmcl : '';
                                        //$tsdis = (isset($record->tsdis)) ? $record->tsdis : '';
                                        //$tsdlv = (isset($record->tsdlv)) ? $record->tsdlv : '';
                                        //$stdlv = (isset($record->stdlv)) ? $record->stdlv : '';

                                        $recordFileTab[] = array($refin, $benef,
                                                                $bicd, $iband,
                                                                $bicc, $ibanc,
                                                                $dtexe, $dtval,
                                                                 $devo, $mont
                                                                );

                                        $pdf->createTable($recordFileTitle, $columnsSize, $recordFileTab, $tableAlign);
                                        $pdf->Ln();
                                                                               

                                        //audit

                                        if (isset($record->audit) AND sizeof($record->audit > 0)) {
                                          
                                            $auditBox         = '';
                                            $auditBoxNoDetail = '';
                                            
                                            foreach ($record->audit as $audit) {
                                                $auditFileTab = array();
                                                $st           = (isset($audit->st)) ? $audit->st : '';
                                                $user         = (isset($audit->user)) ? $audit->user : '';
                                                $ts           = (isset($audit->ts)) ? $audit->ts : '';
                                                $tsNoDetails  = substr($ts, 0, -3);
                                                $rmq          = (isset($audit->rmq)) ? $audit->rmq : '';
                                                if($rmq){
                                                    $auditBox         .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$ts.' '._('Notice').' : '.$rmq."\n";
                                                    $auditBoxNoDetail .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$tsNoDetails.' '._('Notice').' :'.$rmq."\n";
                                                } else {
                                                            
                                                    $auditBox          .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$ts."\n";
                                                    $auditBoxNoDetail .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$tsNoDetails."\n";       
                                                }
                                            }
                                            
                                            //save audit to put it in the pdf no detail
                                            if (!isset( $this->auditRejectedArray['' . $fnamc. ''])) {
                                                $this->auditRejectedArray['' . $fnamc. ''][] = $auditBoxNoDetail;
                                            } else {
                                                
                                                if(!in_array($auditBoxNoDetail, $this->auditRejectedArray['' . $fnamc. ''])){
                                                    $this->auditRejectedArray['' . $fnamc. ''][] = $auditBoxNoDetail;
                                                }
                                            }
                                             $pdf->MultiCell(0,4,utf8_decode($auditBox),'1','L',0);
                                        }
                                    }
                                }
                            } else {
                                
                                if (isset( $this->auditRejectedArray['' . $fnamc. ''])) {
                                    
                                    if( sizeof($this->auditRejectedArray['' . $fnamc. ''])>0 ){
                                        
                                        foreach ($this->auditRejectedArray['' . $fnamc. ''] as $auditEntry) {
                                          $pdf->MultiCell(0,4,utf8_decode($auditEntry),'1','L',0);  
                                        }                                 
                                    }
                                }
                               
                            }
                        }
                    }

                    //send file 
                    if (isset($status->sentfile)) {
                        foreach ($status->sentfile as $sentfile) {
                            
                            $pdf->AddPage();
                            
                            $fnams = (isset($sentfile->fnams)) ? $sentfile->fnams : '';
                            $tsdis = (isset($sentfile->tsdis)) ? $sentfile->tsdis : '';
                            $tssnd = (isset($sentfile->tssnd)) ? $sentfile->tssnd : '';
                            $tsdlv = (isset($sentfile->tsdlv)) ? $sentfile->tsdlv : '';
                            $stdlv = (isset($sentfile->stdlv)) ? $sentfile->stdlv : '';
                            $ntrx  = (isset($sentfile->ntrx)) ? $sentfile->ntrx : 0;
                            $bicc  = (isset($sentfile->bicc)) ? $sentfile->bicc : '';
                            $bic   = (isset($sentfile->bicd)) ? $sentfile->bicd : $bicc;
                            $tot   = (isset($sentfile->tot)) ? $sentfile->tot : '';
                            $pdf->redCell(0, _('File sent to the bank'), 'C');
                            $pdf->Ln();
                            
                            $sendFileTitle = array(_('File name sent to the bank'), $fnams, _('Date and time of sent file'), $tssnd);
                            $columnsSize   = array(70, 135, 42, 30);
                            $tableAlign    = array('R', 'L', 'R', 'L');
                            $pdf->createOneTable($sendFileTitle, $columnsSize, $tableAlign);
                            $pdf->Ln();
                            

                            //bicd or bicd     
                            $bicFileTitle = array(_('Receiving bank BIC'));
                            $bicFileData  = array($bic);
                            $pdf->createVerticalTable($bicFileTitle, $bicFileData, 70, 0);
                            //Integration date and filename of custfile in sendFile node
                            $pdf->Ln();
                            $pdf->redCell(0, _('Initials file'), 'C');
                            $pdf->Ln();                                                      

                            $columnsSize = array(205, 42, 30);
                            $tableAlign  = array('C', 'R', 'L');

                            if (isset($custfileDateArray[$fnams])) {

                                foreach ($custfileDateArray[$fnams] as $fileName => $date) {

                                    $custFilesTab = array($fileName, _('Integration date and time')
                                        , $date);
                                    $pdf->createOneTable($custFilesTab, $columnsSize, $tableAlign);
                                    $pdf->Ln();
                                }
                            }

                            //sendFile summary    
                            $sendFileSummary = array(_('Total amount'), _('Number of sent transactions'),
                                                     _(' SB processing date and time'), _('Date and time of sent file'),
                                                     _('Date and time of delivery notification'));
                            
                            if (!$withDetail) {
                                //do the sum of the different currency and display them
                                if (isset($this->currencyArray['' . $fnams. ''])) {
                                    $tot .= ' ( ';

                                    if (sizeof($this->currencyArray['' . $fnams. '']) > 0) {

                                        foreach ($this->currencyArray['' . $fnams. ''] as $cur => $amountEntry) {
                           
                                            $tot .= array_sum($amountEntry).' '.$cur.' / '; 
                                            
                                        }
                                    }
                                    
                                   
                                }
                            $tot = str_replace(',', '.', $tot);
                            $tot = substr($tot, 0, -2);
                            $tot .= ' )';
                            }
                           
                            $sendFileSummaryData = array($tot, $ntrx,
                                $tsdis, $tssnd,
                                $tsdlv);
                             $testTab = array(_('Delivery notification status'),$stdlv);
                                        
                            $columnsSize = array(70, 0);
                            $tableAlign  = array('R', 'L');
                            $color = '';
                            if($stdlv == 'ACK'){
                                
                                $color = 'green';
   
                            }  elseif ($stdlv == 'NACK') {
                                
                                $color = 'red';
                            }

                            $pdf->createVerticalTable($sendFileSummary, $sendFileSummaryData, 70, 0);
                            $pdf->Ln();
                            $pdf->createOneTable($testTab, $columnsSize, $tableAlign,'',$color);
                            $pdf->Ln();
                            
                            //custfile 
                            if (isset($sentfile->custfile)) {
                                $first = true;
                                foreach ($sentfile->custfile as $custfile) {

                                    $fnamc = $custfile->fnamc;

                                    //record
                                    if ($withDetail) {
                                        if($first){
                                            $pdf->whiteCell(0, _('File') . ' :' . $custfile->fnamc, 'C');
                                            $pdf->Ln();
                                           
                                            $first = false;
                                            
                                        } else {
                                            
                                            $pdf->AddPage();
                                            $pdf->whiteCell(0, _('File') . ' :' . $custfile->fnamc, 'C');
                                            $pdf->Ln();
                                                                                        
                                        }
                                        
                                        $first = false;
                                        if (isset($custfile->record)) {
                                            
                                            $recordFileTitle = array(_('Transaction ref'), _('Beneficiary'),
                                                                     _('Debit bank'), _('Debit account'),
                                                                     _('Credit bank'), _('Credit account'),
                                                                     _('Exedate'), _('Valdate'),
                                                                     _('Cur'), _('Amount'));

                                            $columnsSize = array(27, 50, 23, 45, 27, 45, 14, 14, 9, 23);
                                            $tableAlign = array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'R');

                                            foreach ($custfile->record as $record) {
                                                                                             
                                                $recordFileTab = array();
                                                $bicd  = (isset($record->bicd)) ? $record->bicd : '';
                                                $iband = (isset($record->iband)) ? $record->iband : '';
                                                $bband = (isset($record->bband)) ? $record->bband : '';
                                                //if the iban is not null it is displayed else the bbanc is displayed
                                                if (!$iband) {
                                                    $iband = $bband;
                                                }
                                                $bicc  = (isset($record->bicc)) ? $record->bicc : '';
                                                $ibanc = (isset($record->ibanc)) ? $record->ibanc : '';
                                                $bbanc = (isset($record->bbanc)) ? $record->bbanc : '';

                                                if (!$ibanc) {
                                                    $ibanc = $bbanc;
                                                }

                                                $refin = (isset($record->refin)) ? $record->refin : '';
                                                $devo  = (isset($record->devo)) ? $record->devo : '';
                                                $mont  = (isset($record->mont)) ? $record->mont : '';
                                                $dtval = (isset($record->dtval)) ? $record->dtval : '';
                                                //$dtcre = (isset($record->dtcre)) ? $record->dtcre : '';
                                                $dtexe = (isset($record->dtexe)) ? $record->dtexe : '';
                                                $benef = (isset($record->benef)) ? $record->benef : '';
                                                $benef = Utils::truncate($benef, 26,'');
                                                $info1 = (isset($record->info1)) ? $record->info1 : '';
                                                //$rfmcl = (isset($record->rfmcl)) ? $record->rfmcl : '';
                                                //$tsdis = (isset($record->tsdis)) ? $record->tsdis : '';

                                                $recordFileTab[] = array($refin, $benef,
                                                    $bicd, $iband,
                                                    $bicc, $ibanc,
                                                    $dtexe, $dtval,
                                                    $devo, $mont
                                                );
                                                if (!isset($this->currencyArray['' . $fnams. '']['' . $devo. ''])){
                                                    $this->currencyArray['' . $fnams. '']['' . $devo. ''][] = $mont;
                                                    
                                                } else {
                                                    $this->currencyArray['' . $fnams. '']['' . $devo. ''][] = $mont;                                                    
                                                }

                                                //audit
                                                if (isset($record->audit) AND sizeof($record->audit > 0)) {

                                                    $auditBox         = '';
                                                    $auditBoxNoDetail = '';
                                                    foreach ($record->audit as $audit) {

                                                        $st          = (isset($audit->st)) ? $audit->st : '';
                                                        $user        = (isset($audit->user)) ? $audit->user : '';
                                                        $ts          = (isset($audit->ts)) ? $audit->ts : '';
                                                        $tsNoDetails = substr($ts, 0, -3);
                                                        $rmq         = (isset($audit->rmq)) ? $audit->rmq : '';
                                                        
                                                        if($rmq){
                                                            $auditBox         .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$ts.' '._('Notice').' :'.$rmq."\n";
                                                            $auditBoxNoDetail .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$tsNoDetails.' '._('Notice').' :'.$rmq."\n";
                                                        } else {
                                                            
                                                            $auditBox         .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$ts."\n";
                                                            $auditBoxNoDetail .= _('Status') . ' : ' . $st.' '._('Signatory').' : '.$user.' '._('At').' : '.$tsNoDetails."\n";                                   
                                                        }                                                                  
                                                    }
                                                      //save audit to put it in the pdf no detail
                                                    if (!isset( $this->auditValidArray['' . $fnams. '']['' . $fnamc. ''])) {
                                                        $this->auditValidArray['' . $fnams. '']['' . $fnamc. ''][] =  $auditBoxNoDetail;
                                                    } else {

                                                        if(!in_array($auditBoxNoDetail, $this->auditValidArray['' . $fnams. '']['' . $fnamc. ''])){
                                                            $this->auditValidArray['' . $fnams. '']['' . $fnamc. ''][] =  $auditBoxNoDetail;
                                                        }
                                                    }
                                                    
                                                    //check if there is enough place to put another record
                                                     if($pdf->GetY()>170){
                                                        $pdf->AddPage();
                                                        $pdf->createTable($recordFileTitle, $columnsSize, $recordFileTab, $tableAlign);
                                                        $pdf->Ln();
                                                        $pdf->MultiCell(0,4,utf8_decode($auditBox),'1','L',0);
                                                     } else {
                                                        $pdf->createTable($recordFileTitle, $columnsSize, $recordFileTab, $tableAlign);
                                                        $pdf->Ln();
                                                        $pdf->MultiCell(0,4,utf8_decode($auditBox),'1','L',0);
                                                     }
                                                    
                                                    
                                                }
                                            }
                                           
                                        }
                                    } else {
                                         
                                        if($pdf->GetY()>170){
                                            $pdf->AddPage();   
                                        }
                                        $pdf->redCell(0, _('File') . ' :' . $custfile->fnamc, 'C');
                                        $pdf->Ln();
                                        if (isset($this->auditValidArray['' . $fnams. '']['' . $fnamc. ''])) {

                                            if (sizeof($this->auditValidArray['' . $fnams. '']['' . $fnamc. '']) > 0) {

                                                foreach ($this->auditValidArray['' . $fnams. '']['' . $fnamc. ''] as $auditEntry) {
                                                    
                                                    $pdf->SetTextColor(0);
                                                    $pdf->SetFont('');
                                                    $pdf->MultiCell(0, 4, utf8_decode($auditEntry), '1', 'L', 0);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
         
        unset($centralFilesArchiveList);
               
        
        if ($withDetail) {
            
            $pdf->Output(self::$outPutDirectory . '/' . $this->fileName . self::FILENAME_SEPARATOR . self::DETAILS . '.pdf', 'F');
            
        } else {
           
            $pdf->Output(self::$outPutDirectory . '/' . $this->fileName . self::FILENAME_SEPARATOR . self::NO_DETAILS . '.pdf', 'F');
        }
    }
    
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
    
    
     public function setPdfDirectory($locale){
         
         self::$outPutDirectory = self::$directory.$locale.self::DIRECTORY_SEPARATOR.self::$db;
         return self::$outPutDirectory;
       
        
    }
    
    
    public function generatePdf($arrayXmlFile = array(),$xmlDirectory = '', $pdfDirectory = '' , $db = '' ){
        
         if(is_array($arrayXmlFile) AND sizeof($arrayXmlFile)>0){
             $i = 0;
            //scan all the xml file and put the result in array
            foreach ($arrayXmlFile as $file) {
                //init var
                $xmlFile                  = array();
                $centralFilesArchiveList  = array();
                $custfileDateArray        = array();
                $xmlFile                  = new XmlFileParser();
                //convert xml into array of object
                $centralFilesArchiveList  = $xmlFile->fileXmlToArray($xmlDirectory.'/'.$file); 
              
                $custfileDateArray        = $xmlFile->getCustfileDateArray();
                //create the pdf file with detail and no details

                $pdfFileName              = explode('.', $file);                              
                $newPdfName               = explode('_',$pdfFileName[0],2);
                $newPdfName               = explode('_',$newPdfName[1],2);               
                $newPdfName               = $db.'_'.$newPdfName[1];
                
                $this->fileName  = $newPdfName;
                echo $file."\n";
                //Create pdf with transactions
                $this->createCentralFileArchivePdf($centralFilesArchiveList, TRUE, $custfileDateArray,'en_UK');
                $this->createCentralFileArchivePdf($centralFilesArchiveList, FALSE, $custfileDateArray,'en_UK');
                $this->init();
                $this->createCentralFileArchivePdf($centralFilesArchiveList, TRUE, $custfileDateArray,'fr_FR');
                $this->createCentralFileArchivePdf($centralFilesArchiveList, FALSE, $custfileDateArray,'fr_FR');
                $i++;
                
            }

            //create checksum file for pdf FR
             $arrayPdfFile = self::scanDirPdfArchives($pdfDirectory.'fr_FR'.'/'.$db, $db);
            echo "Generate checksum files from ".$pdfDirectory.'fr_FR'.'/'.$db." \n";
            if (sizeof($arrayPdfFile) > 0) {

                foreach ($arrayPdfFile as $pdfFile) {

                    $md5File = substr($pdfFile, 0, -3) . self::MD5_EXTENSION;
                    $fp = fopen($md5File, 'w');
                    fwrite($fp, md5_file($pdfFile));
                    fclose($fp);
                }
            }
            
            //create checksum file for pdf UK
            $arrayPdfFile = self::scanDirPdfArchives($pdfDirectory.'en_UK'.'/'.$db, $db);
            echo "Generate checksum files from ".$pdfDirectory.'en_UK'.'/'.$db."\n";
            if (sizeof($arrayPdfFile) > 0) {

                foreach ($arrayPdfFile as $pdfFile) {

                    $md5File = substr($pdfFile, 0, -3) . ManagePdfFile::MD5_EXTENSION;
                    $fp = fopen($md5File, 'w');
                    fwrite($fp, md5_file($pdfFile));
                    fclose($fp);
                }
            }
   
            echo 'Job finished for '.$db.' at '.date("Y-m-d H:i:s").".\n";
            
            echo ''.($i*2).' PDF files available in '.$pdfDirectory.'fr_FR'.'/'.$db."\n";
            echo ''.($i*2).' PDF files available in '.$pdfDirectory.'en_UK'.'/'.$db."\n";
             //delete xml files 
             foreach ($arrayXmlFile as $file) {
                 try{
                    if(!@unlink($xmlDirectory.'/'.$file)){
                        throw new Exception('File '.$xmlDirectory.'/'.$file.' can\'t be deleted'."\n");
                    }
                 } catch (Exception $e){
                     echo $e->getMessage();
                 }
             }

        } else {
             echo 'No Xml files to convert in '.$xmlDirectory."...come back later.\n";
        }
    }
    
    
    public static function checkPdfArchivesDirectories($path){
     
        if(!is_dir($path)){
            mkdir($path, 0777);
        } else {
        return FALSE;
        }

    }
 
    public static function rrmdir($dir) {
       if (is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
           if ($object != "." && $object != "..") {
             if (filetype($dir."/".$object) == "dir") self::rrmdir($dir."/".$object); else unlink($dir."/".$object);
           }
         }
         reset($objects);
         rmdir($dir);
       }
    }    
    
    
    
    public static function getHostname(){
        
        $shellHostname = exec('hostname');
        
        return trim($shellHostname);
        
    }
    
    public static function configLdap(){
        
        $config = ApplicationConfig::getInstance();
        switch(self::getHostname()){
            case self::SERVER_DEV :
                $config->ldap_uri = 'ldap://cn=administrator,cn=corporates,dc=linuxdev,dc=datasphere,dc=ch:goldenkey@127.0.0.1/cn=corporates,dc=linuxdev,dc=datasphere,dc=ch';
             break;
            default :
                $config->ldap_uri = 'ldap://cn=administrator,cn=corporates,dc=sb,dc=datasphere,dc=ch:sesame@127.0.0.1/cn=corporates,dc=sb,dc=datasphere,dc=ch';
                break;
        }
       
        
    }
        
   
  
}

?>