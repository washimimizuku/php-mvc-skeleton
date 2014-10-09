<?php

/**
 * A class to group Array Methods together
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class UtilsArray {
    /*************************
     *       ARRAYS UTILS
     *************************/
    
    /**
     * Sort an std array depending of a property
     * @access public
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Array      $array
     * @param String     $property 
     * @param Boolean    $sort       SORT_ASC or SORT_DESC
     * @return Array
     */
    public static function array_std_sort($array, $property, $sortValue = SORT_ASC)
    {
        // init
        global $key;
        global $sort;

        $key    = $property;
        $sort   = $sortValue;

        // sorting array
        if (is_array($array)) {
            usort($array, array('UtilsArray', 'compareStdArray'));
        }
        
        return $array;
    }
    
    /**
     * Generate an array using one or more attributes of a parent array 
     * @access public
     * @static
     * @param Array $aData          Parent array
     * @param Array $attributeName  Name of the attribute we want to fetch
     * @param Array $_              Others attributes
     * @return Array
     */
    public static function array_child($aData, $attributeName, $_ = null)
    {
        // Init
        $aReturn = array();
        if (is_array($aData) OR ($aData instanceof Traversable)) {
            // getting attributes
            $aArgs	= func_get_args();
            $aArgs	= array_slice($aArgs, 1);

            $isMultiAtt = false;
            if (count($aArgs) > 1) {
                $isMultiAtt = true;
            }

            if ($attributeName) {

                foreach ($aData as $element) {

                    // Init
                    $value = null;

                    if ($isMultiAtt) {
                        $isArray = false;
                        if (is_array($element)) {
                            $isArray = true;
                            $value = array();
                        } else {
                            $value = new stdClass();
                        }

                        foreach ($aArgs as $attribut) {
                            if ($isArray) {
                                if (isset($element[$attribut])) {
                                    $value[$attribut]	= $element[$attribut];
                                } else {
                                    $value[$attribut]	= null;
                                }
                            } else {
                                if (isset($element->$attribut)) {
                                    $value->$attribut	= $element->$attribut;
                                } else {
                                    $value->$attribut	= null;
                                }
                            }
                        }
                    } else {
                        if (is_array($element) AND isset($element[$attributeName])) {
                            $value = $element[$attributeName];
                        } else if (isset($element->$attributeName)) {
                            $value = $element->$attributeName;
                        }
                    }

                    $aReturn[] = $value;
                }
            }
        }

        return $aReturn;
    }
    
    public static function array_map_recursive($function, $array) {
        $returnArray = array();
        foreach ($array as $k => $v) {
            $returnArray[$k] = (is_array($v))? self::array_map_recursive($function, $v) : $function($v); // or call_user_func($fn, $v)
        }
        
        return $returnArray;
    }
    
    /** 
    * Flattens an array, or returns FALSE on fail. 
    */ 
    public static function array_flatten($array, $onlyFirstValue = false) { 
        if (!is_array($array)) { 
            return FALSE; 
        } 
        $result = array(); 
        foreach ($array as $key => $value) { 
            if (is_array($value)) {
                if (!$onlyFirstValue) {
                    $result = array_merge($result, self::array_flatten($value)); 
                } else {
                    $result[$key] = reset($value);
                }
            } else {
                $result[$key] = $value; 
            } 
        } 
        return $result; 
    }
    
    public static function array_sort_recursive(&$a)
    {
        sort($a);
        $c = count($a);
        for($i = 0; $i < $c; $i++)
            if (is_array($a[$i]))
                self::array_sort_recursive($a[$i]);
    }
    
    //////////////// CALLBACKS /////////////////
    
    public static function filter_numeric($var)
    {
        return is_numeric($var);
    }
    
    /**
     * Callback function used with usort().
     * @access private
     * @static
     * @author Julien Hoarau <jh@datasphere.ch>
     * @param Mixed $a
     * @param Mixed $b
     * @return Integer -1, 0 or 1.
     */
    private static function compareStdArray($a, $b)
    {
        // init
        $return			= 0;
        if (is_array($a)) {
            $aProperty		= $a[$GLOBALS['key']];
        } else {
            $aProperty		= $a->$GLOBALS['key'];
        }
        if (is_array($b)) {
            $bProperty		= $b[$GLOBALS['key']];
        } else {
            $bProperty		= $b->$GLOBALS['key'];
        }

        if ((isset ($aProperty)) AND (isset ($bProperty))) {
            switch(true) {
                case ((!is_string($aProperty)) AND ((!is_string($bProperty)))) : {
                    if ($aProperty != $bProperty) {
                        if ($GLOBALS['sort'] == SORT_ASC) {
                            if ($aProperty > $bProperty) {
                                $return		= 1;
                            } elseif ($aProperty < $bProperty) {
                                $return		= -1;
                            }
                        } elseif ($GLOBALS['sort'] == SORT_DESC) {
                            if ($aProperty > $bProperty) {
                                $return		= -1;
                            } elseif ($aProperty < $bProperty) {
                                $return		= 1;
                            }
                        }
                    } else {
                        $return	= 0;
                    }
                    break;
                }
                default : {
                    $aProperty	= strval($aProperty);
                    $bProperty	= strval($bProperty);

                    if (strcasecmp($aProperty, $bProperty) != 0) {
                        if ($GLOBALS['sort'] == SORT_ASC) {
                            if (strcasecmp($aProperty, $bProperty) > 0) {
                                $return		= 1;
                            } elseif (strcasecmp($aProperty, $bProperty) < 0) {
                                $return		= -1;
                            }
                        } elseif ($GLOBALS['sort'] == SORT_DESC) {
                            if (strcasecmp($aProperty, $bProperty) > 0) {
                                $return		= -1;
                            } elseif (strcasecmp($aProperty, $bProperty) < 0) {
                                $return		= 1;
                            }
                        }
                    } else {
                        $return	= 0;
                    }
                    break;
                }
            }
        }
        return $return;
    }
}

?>
