<?php

/**
 * xmlParser for centralarchiveLongTermArchive
 * This class is specific to xml central File format 
 * @author yann JAMAR
 */
class XmlFileParser {

    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                  Attributs                                 //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////

    public $doc             = null; // domDucument Object
    public $archiveTab      = array();// archive array
    public $custFileDateTab = array();// custFile array
    private $refSentFile    = ''; //reference on sentFile
    private $refCustFile    = ''; //refenrence on custfile

    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                 XML node constants                         //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////

    const ARCHIVE  = 'archive';
    const STATUS   = 'status';
    const SENTFILE = 'sentfile';
    const CUSTFILE = 'custfile';
    const RECORD   = 'record';
    const AUDIT    = 'audit';
    
    /////////////////////////////////////////////////////////////////////////////////
    //                                                                             //
    //                                 XML archive constants                       //
    //                                                                             //
    /////////////////////////////////////////////////////////////////////////////////

    const ARCHIVE_TYPE   = 'type';
    const ARCHIVE_SOC    = 'soc';
    const ARCHIVE_SOCDS  = 'socds';
    const ARCHIVE_MONTH  = 'month';
    const ARCHIVE_NFSNT  = 'nfsnt';
    const ARCHIVE_NFCUS  = 'nfcus';
    const ARCHIVE_NTRX   = 'ntrx';
    const ARCHIVE_NTRXV  = 'ntrxv';
    const ARCHIVE_NTRXR  = 'ntrxr';
     
    /////////////////////////////////////////////////////////////////////////////////
    //                                                                             //
    //                                 XML statut constants                        //
    //                                                                             //
    /////////////////////////////////////////////////////////////////////////////////
    
    const STATUS_OPCODE = 'opcod';
    const STATUS_NTRX   = 'ntrx';
    
    /////////////////////////////////////////////////////////////////////////////////
    //                                                                             //
    //                                 XML sentfile constants                      //
    //                                                                             //
    /////////////////////////////////////////////////////////////////////////////////
    
    const SENDFILE_FNAMS = 'fnams';
    const SENDFILE_TSDIS = 'tsdis';
    const SENDFILE_TSSND = 'tssnd';
    const SENDFILE_TSDLV = 'tsdlv';
    const SENDFILE_STDLV = 'stdlv';
    const SENDFILE_BICD  = 'bicd';
    const SENDFILE_BICC  = 'bicc';
    const SENDFILE_NTRX  = 'ntrx';
    const SENDFILE_TOT   = 'tot';
    
    /////////////////////////////////////////////////////////////////////////////////
    //                                                                             //
    //                                 XML custfile constants                      //
    //                                                                             //
    /////////////////////////////////////////////////////////////////////////////////
    
    const CUSTFILE_FID   = 'fid';
    const CUSTFILE_FNAMC = 'fnamc';
    const CUSTFILE_TSCRE = 'tscre';
    const CUSTFILE_NREC  = 'nrec';
    const CUSTFILE_NTRX  = 'ntrx';
    const CUSTFILE_NRECV = 'nrecv';
    const CUSTFILE_NRECJ = 'nrecj';
    
    /////////////////////////////////////////////////////////////////////////////////
    //                                                                             //
    //                                 XML record constants                        //
    //                                                                             //
    /////////////////////////////////////////////////////////////////////////////////

    const RECORD_BICD  = 'bicd';
    const RECORD_IBAND = 'iband';
    const RECORD_BBAND = 'bband';
    const RECORD_BICC  = 'bicc';
    const RECORD_IBANC = 'ibanc';
    const RECORD_BBANC = 'bbanc';
    const RECORD_REFIN = 'refin';
    const RECORD_DEVO  = 'devo';
    const RECORD_MONT  = 'mont';
    const RECORD_DTVAL = 'dtval';
    const RECORD_DTCRE = 'dtcre';
    const RECORD_DTEXE = 'dtexe';
    const RECORD_BENEF = 'benef';
    const RECORD_INFO1 = 'info1';
    const RECORD_RFMCL = 'rfmcl';
    const RECORD_TSDIS = 'tsdis';
    const RECORD_TSDLV = 'tsdlv';
    const RECORD_STDLV = 'stdlv';
    
    ////////////////////////////////////////////////////////////////////////////////
    //                                                                            //
    //                                 XML audit Constants                        //
    //                                                                            //
    ////////////////////////////////////////////////////////////////////////////////

    const AUDIT_STATUT = 'statut';
    const AUDIT_ST     = 'st';
    const AUDIT_USER   = 'user';
    const AUDIT_TS     = 'ts';
    const AUDIT_RMQ    = 'rmq';

    /**
     * Take XML file in the construct param
     * @param String $dirFile
     * @return void
     */
    public function __construct() {

        $this->doc                     = new DomDocument;
        $this->doc->preserveWhiteSpace = false;       
        $this->custFileDateTab         = array();
        $this->archiveTab              = array();
        $this->refSentFile             = '';
        $this->refCustFile             = '';
    }

    
     /**
     * Convert XML file in object array
     * @param String $filePath path of the file 
     * @return array
     */
    public function fileXmlToArray($filePath = '') {

        $this->doc->load($filePath);
        $object         = null;
        $object         = new stdClass();
        $root           = $this->doc->documentElement;
        $object->archive  = new stdClass();
        
        $this->getElement($root, $object->archive);
        
        return $this->archiveTab;
    }
    
     /**
     * Get the custfile name array
     * @param none 
     * @return array
     */
    public function getCustfileNameArray(){
        
        return $this->custFileNameTab;
    }
    
      /**
     * Get the custfile date array
     * @param none 
     * @return array
     */
    public function getCustfileDateArray(){
        
        return $this->custFileDateTab;
    }

     /**
     * Recurrent method to get the values of the node element  
     * @param Object $domElement
     * @param Object $objectElement
     * @return void
     */
    public function getElement($domElement, $objectElement) {

        if (trim($domElement->firstChild->nodeValue)) {

            $this->setAttributesToNodeObjectElement($domElement->nodeName, $objectElement, trim($domElement->firstChild->nodeValue)); 
        }
     
        // child elements of the tree
        if ($domElement->childNodes->length > 0) {

            switch ($domElement->nodeName) {

                //CUSTFILE node
                case self::CUSTFILE :

                    if (!is_array(@$objectElement->custfile)) {
                        $objectElement->custfile = array();
                    }

                    $childObject = $this->checkChildNode($domElement->childNodes , $domElement->nodeName);
                    array_push($objectElement->custfile, $childObject);

                    break;
                    
                 //sentfile node
                case self::SENTFILE :

                    if (!is_array(@$objectElement->sentfile)) {
                        $objectElement->sentfile = array();
                    }

                    $childObject = $this->checkChildNode($domElement->childNodes , $domElement->nodeName);
                    array_push($objectElement->sentfile, $childObject);

                    break;

                //record node        
                case self::RECORD :

                    if (!is_array(@$objectElement->record)) {
                        $objectElement->record = array();
                    }

                    $childObject = $this->checkChildNode($domElement->childNodes);
                    array_push($objectElement->record, $childObject);
                    break;

                //archive node    
                case self::AUDIT :
                    if (!is_array(@$objectElement->audit)) {

                        $objectElement->audit = array();
                    }
                    $childObject = $this->checkChildNode($domElement->childNodes);
                    array_push($objectElement->audit, $childObject);
                    break;
                    
                 //archive node    
                case self::STATUS :
                    if (!is_array(@$objectElement->status)) {

                        $objectElement->status = array();
                    }
                    $childObject = $this->checkChildNode($domElement->childNodes);
                    array_push($objectElement->status, $childObject);
                    break;
                 
               
                 //status node    
                case self::ARCHIVE :

                    if (!is_array(@$objectElement->archive)) {

                        $objectElement->archive = array();
                    }

                    $childObject = $this->checkChildNode($domElement->childNodes);
                    array_push($objectElement->archive, $childObject);
                    $this->archiveTab = $objectElement->archive;
                    break;

                //status node    
               
            }
        }
    }

     /**
     * Set the attributes of the node object element
     * @param String $node
     * @param Object $objectElement 
     * @param String $value node value
     * @return void
     */
    private function setAttributesToNodeObjectElement($node = '', $objectElement = null, $value = '') {
        //TODO check in a array the node and redirect to the good switch to avoid that the switch parse
        //all the value !
       
        
        switch ($node) {
            
            // Archive attributes
            case self::ARCHIVE_TYPE :
                $objectElement->type   = $value;
                break;
            case self::ARCHIVE_SOC :
                $objectElement->soc    = $value;
                break;
            case self::ARCHIVE_SOCDS :
                $objectElement->socds  = $value;
                break;
            case self::ARCHIVE_MONTH :
                 $objectElement->month = $value;
                break;
            case self::ARCHIVE_NFSNT :
                $objectElement->nfsnt  = $value;
                break;
            case self::ARCHIVE_NFCUS :
                $objectElement->nfcus  = $value;
                break;
            case self::ARCHIVE_NTRX :
                $objectElement->ntrx   = $value;
                break;
            case self::ARCHIVE_NTRXV :
                $objectElement->ntrxv   = $value;
                break;
            case self::ARCHIVE_NTRXR :
                $objectElement->ntrxr  = $value;
                break;
           
            //Status attributes
            case self::STATUS_OPCODE :
                $objectElement->opcod  = $value;
                break;           
             case self::STATUS_NTRX :
                $objectElement->ntrx   = $value;
                break;
            
            //Sentfile attributes
            case self::SENDFILE_FNAMS :
                $objectElement->fnams  = $value;
                break;
            case self::SENDFILE_TSDIS :
                $objectElement->tsdis  = $value;
                break;
            case self::SENDFILE_TSSND :
                $objectElement->tssnd  = $value;
                break;
            case self::SENDFILE_TSDLV :
                $objectElement->tsdlv  = $value;
                break;
            case self::SENDFILE_STDLV :
                $objectElement->stdlv  = $value;
                break;
            case self::SENDFILE_BICD :
                $objectElement->bicd   = $value;
                break;
            case self::SENDFILE_BICC :
                $objectElement->bicc   = $value;
                break;
            case self::SENDFILE_NTRX :
                $objectElement->ntrx   = $value;
                break;
             case self::SENDFILE_TOT :
                $objectElement->tot   = $value;
                break;
            
            //Custfile attributes
             case self::CUSTFILE_FID :
                $objectElement->fid    = $value;
                break;            
             case self::CUSTFILE_FNAMC :
                $objectElement->fnamc  = $value;            
                break;
            case self::CUSTFILE_TSCRE :
                $objectElement->tscre  = $value;
                break;
             case self::CUSTFILE_NREC :
                $objectElement->nrec   = $value;
                break;
            case self::CUSTFILE_NTRX :
                $objectElement->ntrx   = $value;
                break;
             case self::CUSTFILE_NRECV :
                $objectElement->nrecv  = $value;
                break;
            case self::CUSTFILE_NRECJ :
                $objectElement->nrecj  = $value;
                break;
            
            //Record attributes  
            case self::RECORD_BICD :
                $objectElement->bicd  = $value;
                break;
            case self::RECORD_IBAND :
                $objectElement->iband  = $value;
                break;
            case self::RECORD_BBAND :
                $objectElement->bband  = $value;
                break;
            case self::RECORD_BICC :
                $objectElement->bicc  = $value;
                break;
            case self::RECORD_IBANC :
                $objectElement->ibanc  = $value;
                break;
            case self::RECORD_BBANC :
                $objectElement->bbanc  = $value;
                break;
            case self::RECORD_REFIN :
                $objectElement->refin  = $value;
                break;
            case self::RECORD_DEVO :
                $objectElement->devo  = $value;
                break;
            case self::RECORD_MONT :
                $objectElement->mont  = $value;
                break;
            case self::RECORD_DTVAL :
                $objectElement->dtval  = $value;
                break;
            case self::RECORD_DTCRE :
                $objectElement->dtcre  = $value;
                break;
            case self::RECORD_DTEXE :
                $objectElement->dtexe  = $value;
                break;            
            case self::RECORD_BENEF :
                $objectElement->benef  = $value;
                break;
            case self::RECORD_INFO1 :
                $objectElement->info1  = $value;
                break;
            case self::RECORD_RFMCL :
                $objectElement->rfmcl  = $value;
                break;
            case self::RECORD_TSDIS :
                $objectElement->tsdis  = $value;
                break;
            case self::RECORD_TSDLV :
                $objectElement->tsdlv  = $value;
                break;
            case self::RECORD_STDLV :
                $objectElement->stdlv  = $value;
                break;
            
            //Audit attributes
            case self::AUDIT_STATUT :
                $objectElement->statut  = $value;
                break;
            case self::AUDIT_ST :
                $objectElement->st      = $value;
                break;
            case self::AUDIT_USER :
                $objectElement->user    = $value;
                break;
             case self::AUDIT_TS :
                $objectElement->ts      = $value;
                break;
             case self::AUDIT_RMQ :
                $objectElement->rmq     = $value;
                break;
        }

    }
   
     /**
     * Check child node and call recurrent method getElement to populate the childObject element
     * @param Object $childNode
     * @return Object
     */
    private function checkChildNode($childNode= null, $nodeName = null) {

        $childObject = null;
        $childObject = new stdClass();

        foreach ($childNode as $dom_child) {

            $parentNode = $dom_child->parentNode->parentNode->nodeName;

            if ($dom_child->hasChildNodes()) {

                if ($dom_child->nodeType == XML_ELEMENT_NODE) {

                    $this->getElement($dom_child, $childObject);
                    //

                    if ($nodeName == (self::SENTFILE)) {

                        if (isset($childObject->fnams)) {
                            $this->refSentFile = $childObject->fnams;
                        }
                    }

                    if ($nodeName == (self::CUSTFILE) AND $parentNode == (self::SENTFILE)) {

                        $this->refCustFile = $childObject->fnamc;

                        //date cusfile array
                        if (!isset($this->custFileDateTab['' . $this->refSentFile . '']['' . $this->refCustFile . ''])) {

                            if (isset($childObject->tscre)) {

                                $this->custFileDateTab['' . $this->refSentFile . '']['' . $this->refCustFile . ''] = $childObject->tscre;
                            }
                        } else {

                            if (isset($childObject->tscre)) {

                                $this->custFileDateTab['' . $this->refSentFile . '']['' . $this->refCustFile . ''] = $childObject->tscre;
                            }
                        }
                    }
                }
            }
        }
        return $childObject;
    }
}

?>
