<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EXEL
 *
 * @author yj
 */
class EXCEL {
    
    private $dataCharset    = '';
    
    private $excelCharset    = '';
    
    private $fileName       = '';
    
    private $columnsTitle   = '';
    
    private $search         = '';
    
    public $aDatas          = array();
    
    public $aValuesLists    = array();
    
    public $aColumns        = array();
    

    public function setCharset($excelCharset ,$dataCharset){
        $this->excelCharset = $excelCharset;
        $this->dataCharset = $dataCharset;
    }
    
    public function setFileName($fileName){
        $this->fileName = $fileName;
    }
    
    public function setColumnsTile(array $aTitle){
        $columnsHeader = '';
        foreach($aTitle as $title ){
            $columnsHeader .= $title['name']."\t";                    
        }
        $this->columnsTitle = mb_convert_encoding($columnsHeader."\n",$this->excelCharset,$this->dataCharset);
    }
    
    private function getColumnTitleContent(){
        echo $this->columnsTitle;
    }
    
    public function setSearch($columnString = '', $advancedSearchString = '', $search=''){
        
        $searchContent = '';
        if ($columnString) {
            $searchContent .= _('Order by') . ": " . $columnString . "\t";
        }
        if ($advancedSearchString) {
            $searchContent .= _('Advanced Search') . ": " . $advancedSearchString;
        } elseif ($search) {
            $searchContent .= _('Search') . ": " . $search . "\t";
        }
        if ($columnString or $advancedSearchString or $search) {
            $searchContent .= "\n";
        }        
        $this->search =  mb_convert_encoding("\n".$searchContent,$this->excelCharset,$this->dataCharset);      
    }
    
    private function setExcelHeader(){
         if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
            header("Content-type: application-download;charset=$this->excelCharset");
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header("Content-type: application/vnd.ms-excel; charset=$this->excelCharset");
        }
        header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
        echo chr(255) . chr(254);
       
    }
    
    private function formatExcelDatas(array $aDatas , array $aColumns){

        foreach ($aDatas as $data) {
          // Utils::log($data);
            $value = '';
            foreach ($aColumns as $column) {
               // Utils::log($column);

                $dbField = $column['dbField'];
                if (isset($column['extraField'])) {
                    $extraField = $column['extraField'];
                }

                if (isset($column['getParameter'])) {
                    switch ($column['getParameter']) {
                        case 'organizations':
                            $value = $data->getOrganizationName();
                            break;
                        case 'userString':
                            $value = $data->getLinkedUsersString();
                            break;

                        default:
                             $value = '';
                            break;
                    }
                    $value .="\t";
                } elseif (isset($column['special'])) {

                    switch ($column['special']) {
                        case 'totalAmount':
                            $value = $data->getAmountToDisplay();
                            break;
                        case 'sentDate':
                            $value = $data->getSentDateToDisplay();
                            $value = '="' . $value . '"';
                            break;
                        case 'operationReference':
                            $value = '="' .$data->getOperationReference(). '"';
                            break;
                        case 'bank':

                            if (substr($data->typope, -4, 4) == 'PREL' OR substr($data->typope, -3, 3) == 'LCR') {
                                //$value = '';
                                $value = $data->bicbqcac;
                            } else {
                                $value = $data->bicbqcad;
                            }
                            $value = '="' . $value . '"';
                            break;

                        case 'account' :
                            if (substr($data->typope, -4, 4) == 'PREL' OR substr($data->typope, -3, 3) == 'LCR') {
                                $value = $data->bicbqcad;
                                if ($data->ibancac) {
                                    $value = $data->ibancac;
                                } else {
                                    $value = $data->bbancac;
                                }
                            } else {
                                if ($data->ibancad) {
                                    $value = $data->ibancad;
                                } else {
                                    $value = $data->bbancad;
                                }
                            }
                           $value = '="' . $value . '"';

                            break;

                        default:
                            $value = '';
                            break;
                    }
                    if (is_numeric($value) AND strlen($value) > 11) {
                        $value = '="' . $value . '"';
                    }

                    $value .="\t";
                   // Utils::log($value);
                } elseif (isset($column['objectList'])) {

                    switch ($column['objectList']) {
                        case 'companies':
                            $obj = $data->getCompany();
                            $value = $obj->$column['dbField'];
                            if ($obj->$column['extraField']) {
                                $value .= '(' . $obj->$column['extraField'] . ')';
                            }
                            break;

                        case 'accessLists':
                            $value = $data->getAuthorizationListsNamesString();
                             
                            break;

                        case 'permissions' :
                            $value = $data->getPermissionsNamesString();
                          
                            break;
                        
                         case 'users' :
                            $value = $data->getUsersNamesString();
                            break;
                                                          

                        case 'roles' :
                            $value = $data->getRolesNamesString();
                            break;

                        case 'members' :
                            $value = $data->getMembersNamesString();
                            break;

                        case 'graphs' :
                            $value = $data->getGraphsNamesString();
                            break;

                        case 'filters' :
                            $value = $data->getFiltersNamesString();
                            break;

                        case 'operationTypes' :
                            $value = $data->$column['dbField'];

                            break;

                        default:
                             $value = '';
                            break;
                    }
                    $value .="\t";
                } elseif (isset($column['valuesList'])) {
                    
                    switch ($column['valuesList']) {
                        case 'currencies' :
                            if(isset($data->currency[1])){
                                $value = $data->currencyString;
                            }elseif(isset($data->currency[0])){
                                $value = $data->currency[0];
                            } else {
                                $value = $data->$dbField;
                            }
                            
                            if (isset($data->currencyString)) {
                                $value = $data->currencyString;
                            } elseif(isset($data->currency[0])) {
                                
                                    $value = $data->currency[0];                             
                            }

                            break;
                        case 'types' :
                            $value = $this->aValuesLists[$column['valuesList']][$data->$dbField][$column['listValue']];
                            break;
                        default:
                            $value = $data->$dbField;
                            break;
                    }
                    $value .="\t";
                } elseif(isset($column['extraField'])) {
                    if(isset($data->$extraField)){
                    $value = $data->$extraField;
                    $value.="\t";                 
                    }
                } elseif(isset($column['substr'])) {
                    $value = substr($data->$dbField, 0, $column['substr']);
                    $value.="\t";
                
                } else {
                    
                    if ($dbField == 'gractspadm' || $dbField == 'fiactspadm' || $dbField == 'gractive' || $dbField == 'fiactive') {
                        $flagValue = false;

                        if ($dbField == 'fiactive' || $dbField == 'gractive') {
                            if (isset($data->gractive) && $data->gractive == '0' || isset($data->fiactive) && $data->fiactive == '0') {
                                $flagValue = true;
                            } else {
                                $flagValue = false;
                            }
                        }

                        if (isset($data->gractspadm) && $data->gractspadm == '0' || isset($data->fiactspadm) && $data->fiactspadm == '0') {
                            $flagValue = true;
                        }

                        if ($flagValue) {
                            $value = _('No') . "\t";
                        } else {
                            $value = _('Yes') . "\t";
                        }

                    }elseif(is_numeric($data->$column['dbField']) AND strlen($data->$column['dbField']) < 11 AND $column['dbField']!='sbcptbban') {
                        
                        $value = $data->$column['dbField'] . "\t";
                         
                    } else {                           

                        $value = '="' . $data->$column['dbField'] . '"' . "\t";
                    }
                }
                //Utils::log($value.'#'.$column['dbField']);
                echo mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
            }
            echo mb_convert_encoding("\n", 'UTF-16LE', 'UTF-8');
        }
        
    }
    
    private function formatExcelDatasSummaryPermission(array $aDatas , array $aColumns){
         foreach ($aDatas as $data) {
             $value = '';
             //Utils::log($data->dsType);
             switch ($data->dsType) {
                 case LDAPAuthorizationsList_Manager::TYPE_AUTH_PERMISSION_ROLE:
                     //Utils::log($value);
                     $value = _('Role Permission');    
                     //Utils::log($value);
                     break;
                 
                 case LDAPAuthorizationsList_Manager::TYPE_AUTH_PERMISSION_USER:
                     $value = _('User Permission');                    
                     break;
                 
                 case LDAPAuthorizationsList_Manager::TYPE_AUTH_ACCESS:
                     $value = _('Access List Permission');                    
                     break;                                    
                 default:
                     break;
             }
             $value     .="\t";
             $value     .= $data->dsALN;
             $value     .="\t";
             $module    = explode('_',$data->dsMN);
             if(isset($module[0])){
             $value     .=$module[0]; 
             }
             $value .="\t";
             if(isset($module[1])){
             $value     .=$module[1]; 
             }
             $value .="\t";
             $value     .=$data->getRolesNamesString();
             $value .="\t";
             $value     .=$data->getLinkedUsersString();
             Utils::log($value);
             echo mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
             echo mb_convert_encoding("\n", 'UTF-16LE', 'UTF-8');

         }
  
    }
    
    
    private function getSearchContent() {
        echo $this->search;
    }
    
    public function outPutExcelDoc($summaryPermission = false){
        
        $this->setExcelHeader();
        $this->getColumnTitleContent();
        if($summaryPermission){
            $this->formatExcelDatasSummaryPermission($this->aDatas,$this->aColumns); 
            
        } else {
             $this->formatExcelDatas($this->aDatas,$this->aColumns);           
        }
       
        $this->getSearchContent();
        
    }
    
    

}

?>
