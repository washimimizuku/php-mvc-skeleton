<?php
/**
 * GUMP WRAPPER - A fast, extensible PHP input validation class, VERSION EXTENDED
 *
 * @author      HUGO PEREIRA
 * @author      Sean Nieuwoudt (http://twitter.com/SeanNieuwoudt)
 * @copyright   Copyright (c) 2014
 */
class GumpWrapper extends Gump{
    /****************************** EXTENDED VALIDATIORS ******************************/
    
    /** VALIDATE DATE TIME FIELDS **/
    public function validate_date_time($field, $input, $param = NULL){
        $fieldValue = urldecode($input[$field]);
        
        //VALIDATE FIELD
        $format = 'Y-m-d H:i:s';
        $d = DateTime::createFromFormat($format, $fieldValue);
        
      
  
        if(!($d && $d->format($format) == $fieldValue)){
            return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param
            );
        }
    }
    
    
    /** VALIDATE INTPUT FIELD EXIST AND IS SET AND NOT EMPTY **/
    protected function validate_required_full($field, $input, $param = NULL){
        if(isset($input[$field]) && strlen($input[$field]) > 0 &&  !is_null($input[$field])){
            return;
        }
        
        return array(
        'field' => $field,
        'value' => NULL,
        'rule'  => __FUNCTION__,
        'param' => $param
        );
    }
       
    
    /** VALIDATE INTPUT ALPHA NUMERIC WITH SPECIAL CARACTHERS **/
    protected function validate_alpha_numeric_special($field, $input, $param = NULL)
    {
           if(!isset($input[$field]) || empty($input[$field])){
                return;
           }

           
           $pattern     = "/^([0-9A-Za-z ~)?:@\-\. ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])*$/i";
           if(!preg_match($pattern, $input[$field]) !== FALSE)
           {
                   return array(
                           'field' => $field,
                           'value' => $input[$field],
                           'rule'  => __FUNCTION__,
                           'param' => $param
                   );
           }
    }
    
    /** VALIDATE JSON WELL FORMATED **/
    public function validate_json($field, $input, $param = NULL){
        $fieldValue = $input[$field];
        
        //$pattern = '/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/';
        //if (!preg_match($pattern, $fieldValue)) {
        
        if(!is_array(json_decode($fieldValue, true))){
            return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param
            );
        }
    }
    
    /** VALIDATE URL **/
    protected function validate_valid_url($field, $input, $param = NULL){
       
        if(!isset($input[$field]) || empty($input[$field])){
                return;
        }
        
        $link = $input[$field];
        if (!(preg_match("#^https?://.+#", $link) && @fopen($link,"r"))){
            return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param
            );
        }
                
//        if (
//                !filter_var($input[$field], FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) ||
//                !preg_match("#^http://www\.[a-z0-9-_.]+\.[a-z]{2,4}$#i",$input[$field])
//        ) {
//            return array(
//                    'field' => $field,
//                    'value' => $input[$field],
//                    'rule'  => __FUNCTION__,
//                    'param' => $param
//            );
//        }

        
        /*$pattern = '/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/'; 
        if(
                !filter_var($input[$field], FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) ||
                !preg_match($pattern, $input[$field])
        ){
                return array(
                        'field' => $field,
                        'value' => $input[$field],
                        'rule'  => __FUNCTION__,
                        'param' => $param
                );
        }*/
    }
    
    /** VALIDATE NUMERIC(INTEGER/FLOAT) POSITIVES **/
    protected function validate_numeric_positive($field, $input, $param = NULL){
        if(!isset($input[$field])){
            return;
        }
        
        //if ( !((is_int($input[$field]) || ctype_digit($input[$field])) && (int)$input[$field] <= 0) ) {
        if( !(is_numeric($input[$field]) && $input[$field] >= 0)){
            return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param
            );
        }
    }
    
    /** VALIDATE FILENAME **/
    protected function validate_filename($field, $input, $param = NULL){
        
        if (!preg_match('/^[^*?"<>|:]*$/',$input[$field])) {
            return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param
            );
        }
    }
    
    /****************************** EXTENDED VALIDATIORS ******************************/
    
    
    public function validateFields($aInfo, $validators){
        $validated = $this->validate($aInfo, $validators);
        $this->validation_rules($validators);

        if($this->run($aInfo) === false) {
            return array('status'=>'error', 'message'=> $this->get_readable_errors(false));
        } else {
            return array('status'=>'success', 'message'=>'sucess');
        }
    }
    
    
    
    
    /**
    * Process the validation errors and return human readable error messages
    *
    * @param bool $convert_to_string = false
    * @param string $field_class
    * @param string $error_class
    * @return array
    * @return string
    */
   public function get_readable_errors($convert_to_string = false, $field_class="field", $error_class="error-message")
   {
           if(empty($this->errors)) {
                   return ($convert_to_string)? null : array();
           }

           $resp = array();

           foreach($this->errors as $e) {

                $field = ucwords(str_replace(array('_','-'), chr(32), $e['field']));
                $param = $e['param'];

                switch($e['rule']) {
                        case 'validate_filename':
                                $resp[] = "The '$field' field has to be a path to folder";
                                break;
                        case 'validate_numeric_positive':
                                $resp[] = "The '$field' field has to be number and greather then 0";
                                break;
                        case 'validate_required_full':
                                $resp[] = "The '$field' field is required";
                                break;
                        case 'validate_alpha_numeric_special':
                                $resp[] = "The '".$field."' is not a valide string with alpha/numeric/special Characters ";
                                break;
                        case 'validate_json':
                                $resp[] = "The '".$field."' is not json formatted ";
                                break;
                        case 'validate_date_time':
                                $resp[] = "The '".$field."' is malformatted ";
                                break;
                            
                            
                        case 'mismatch' :
                                $resp[] = "There is no validation rule for '$field'";
                                break;
                        case 'validate_required':
                                $resp[] = "The '$field' field is required";
                                break;
                        case 'validate_valid_email':
                                $resp[] = "The '$field' field is required to be a valid email address";
                                break;
                        case 'validate_max_len':
                                if($param == 1) {
                                        $resp[] = "The '$field' field needs to be shorter than $param character";
                                } else {
                                        $resp[] = "The '$field' field needs to be shorter than $param characters";
                                }
                                break;
                        case 'validate_min_len':
                                if($param == 1) {
                                        $resp[] = "The '$field' field needs to be longer than $param character";
                                } else {
                                        $resp[] = "The '$field' field needs to be longer than $param characters";
                                }
                                break;
                        case 'validate_exact_len':
                                if($param == 1) {
                                        $resp[] = "The '$field' field needs to be exactly $param character in length";
                                } else {
                                        $resp[] = "The '$field' field needs to be exactly $param characters in length";
                                }
                                break;
                        case 'validate_alpha':
                                $resp[] = "The '$field' field may only contain alpha characters(a-z)";
                                break;
                        case 'validate_alpha_numeric':
                                $resp[] = "The '$field' field may only contain alpha-numeric characters";
                                break;
                        case 'validate_alpha_dash':
                                $resp[] = "The '$field' field may only contain alpha characters &amp; dashes";
                                break;
                        case 'validate_numeric':
                                $resp[] = "The '$field' field may only contain numeric characters";
                                break;
                        case 'validate_integer':
                                $resp[] = "The '$field' field may only contain a numeric value";
                                break;
                        case 'validate_boolean':
                                $resp[] = "The '$field' field may only contain a true or false value";
                                break;
                        case 'validate_float':
                                $resp[] = "The '$field' field may only contain a float value";
                                break;
                        case 'validate_valid_url':
                                $resp[] = "The '$field' field is required to be a valid URL";
                                break;
                        case 'validate_url_exists':
                                $resp[] = "The '$field' URL does not exist";
                                break;
                        case 'validate_valid_ip':
                                $resp[] = "The '$field' field needs to contain a valid IP address";
                                break;
                        case 'validate_valid_cc':
                                $resp[] = "The '$field' field needs to contain a valid credit card number";
                                break;
                        case 'validate_valid_name':
                                $resp[] = "The '$field' field needs to contain a valid human name";
                                break;
                        case 'validate_contains':
                                $resp[] = "The '$field' field needs to contain one of these values: ".implode(', ', $param);
                                break;
                        case 'validate_street_address':
                                $resp[] = "The '$field' field needs to be a valid street address";
                                break;
                        case 'validate_date':
                                $resp[] = "The '$field' field needs to be a valid date";
                                break;
                        case 'validate_min_numeric':
                                $resp[] = "The '$field' field needs to be a numeric value, equal to, or higher than $param";
                                break;
                        case 'validate_max_numeric':
                                $resp[] = "The '$field' field needs to be a numeric value, equal to, or lower than $param";
                                break;
                }
           }

           if(!$convert_to_string) {
                   return $resp;
           } else {
                   $buffer = '';
                   foreach($resp as $s) {
                           $buffer .= "<span class=\"$error_class\">$s</span>";
                   }
                   return $buffer;
           }
   }
}