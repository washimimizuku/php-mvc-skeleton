<?php

require_once(getenv('app_root').'/lib/utils/DebugLog.class.php');
require_once(getenv('app_root').'/lib/utils/UtilsArray.class.php');

/**
 * Various utility functions that don't belong to a specific class
 *
 * @author Patrick Monnerat <pm@datasphere.ch>
 * @author Nuno Barreto <nb@datasphere.ch>
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Utils {
    public static $aConvertField        = array(
        'company' => 'Company',
        'companyCode' => 'Company code',
        'code' => 'Company code',
        'operationType' => 'Type',
        'bic' => 'BIC',
        'iban' => 'IBAN',
        'bban' => 'BBAN',
        'account' => 'Account',
        'country' => 'Country',
        'city' => 'City',
        'division' => 'Division',
        'name' => 'Name',
        'currency' => 'Currency',
        'description' => 'Description',
        'amount' => 'Amount',
        'amountMin' => 'Amount min',
        'amountMax' => 'Amount max',
        'receiver' => 'Receiver',
        'executionDate' => 'Execution Date',
        'executionDateMin' => 'Execution Date range start',
        'executionDateMax' => 'Execution Date range end',
        'sentDate' => 'Sent Date',
        'sentDateMin' => 'Sent Date range start',
        'sentDateMax' => 'Sent Date range end',
        'creationDate' => 'Creation Date',
        'creationDateMin' => 'Creation Date range start',
        'creationDateMax' => 'Creation Date range end',
        'operationReference' => 'Operation Reference',
        'status' => 'Status',
        'statusTab' => 'Status tab'
    );

    const DELIM_DOUBLE_UNDERSCORE	= '__';

    /**
     * Apply an update routine (called with the session creation)
     */
    private static function applyUpdateRoutine()
    {

        try {
            ///////////////////////////// @TODO DELETE AFTER NEXT RELEASE /////////////////////////////
            // routine 2012-10-08 from JH : Add dsType to existing Authorization Lists
            $config = ApplicationConfig::getInstance();
            $aAuthLists = LDAPAuthorizationsList_Manager::getAuthorizationsLists(null, null, $config->o, null, null, null, array(LDAPAuthorizationsList_Manager::PREFIX_AUTH_ACCESS,LDAPAuthorizationsList_Manager::PREFIX_AUTH_USER,LDAPAuthorizationsList_Manager::PREFIX_AUTH_ROLE));
            foreach ($aAuthLists as $authList) {
                switch ($authList->getClassPrefix()) {
                    case LDAPAuthorizationsList_Manager::PREFIX_AUTH_ACCESS:
                        $authList->dsType = LDAPAuthorizationsList_Manager::TYPE_AUTH_ACCESS;
                        break;
                    case LDAPAuthorizationsList_Manager::PREFIX_AUTH_USER:
                        $authList->dsType = LDAPAuthorizationsList_Manager::TYPE_AUTH_PERMISSION_USER;
                        break;
                    case LDAPAuthorizationsList_Manager::PREFIX_AUTH_ROLE:
                        $authList->dsType = LDAPAuthorizationsList_Manager::TYPE_AUTH_PERMISSION_ROLE;
                        break;
                    default:
                        break;
                }
                $authList->update();
            }
            ////////////////////////////////////////////////////////////////////////////////////////
        } catch (Exception $exc) {
            Utils::abort(_('Please contact your administrator.'));
        }
    }

    /**
     * Transform decimal number into hexadecimal
     *
     * @author Patrick Monnerat <pm@datasphere.ch>
     *
     * @param integer $number Integer to be converted to hexadecimal
     *
     * @return string    Hexadecimal number
     */
    public static function lrgDec2Hex($number) {
        $hex = array();

        while($number > 0) {
            array_push($hex, strtoupper(dechex(bcmod($number, '16'))));
            $number = bcdiv($number, '16', 0);
        }
        krsort($hex);
        return implode($hex);
    }

    /**
     * Finds out which browser is being used
     *
     * @author Ruudrp <ruudrp@live.nl>
     *
     * @return array    browser information
     */
    public static function getBrowser() {
        $u_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";
        $compatibilityViewFlag = false;

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        $ub = '';
        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
            if(preg_match('/Trident/i',$u_agent)){
                $compatibilityViewFlag = true;      
            }
        } elseif(preg_match('/Firefox/i',$u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif(preg_match('/Chrome/i',$u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif(preg_match('/Safari/i',$u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif(preg_match('/Opera/i',$u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif(preg_match('/Netscape/i',$u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            } else {
                if (isset($matches['version'][1])){
                    $version= $matches['version'][1];
                }
            }
        } else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {
            $version="?";
            $version_short = "?";
        } else {
            $elements = explode('.', $version);
            $version_short = $elements[0];
        }

        return array(
            'compatibilityView' => $compatibilityViewFlag,
            'userAgent'     => $u_agent,
            'name'          => $bname,
            'version'       => $version,
            'version_short' => $version_short,
            'platform'      => $platform,
            'pattern'       => $pattern
        );
    }

    /**
     * Truncate text
     *
     * @author Chirp Internet: www.chirp.com.au
     *
     * @param string $string Text to be truncated
     * @param integer $limit  Amount of characters we want to have
     * @param string $break  Where to break the string
     * @param string $pad    What to insert at the end of the string
     *
     * @return string    truncated text
     *
     */
    public static function truncate($string, $limit, $break=" ", $pad="...") {
        // return with no change if string is shorter than $limit
        if(strlen($string) <= $limit) return $string;

        $string = substr($string, 0, $limit);
        if(false !== ($breakpoint = strrpos($string, $break))) {
            $string = substr($string, 0, $breakpoint);
        }

        return $string . $pad;
    }

    /**
     * Safer substitute to die(), so that errors are not shown in production
     *
     * @author Patrick Monnerat <pm@datasphere.ch>
     *
     * @param string|bool $msg Message to show
     */
    public static function abort($msg = NULL) {
        if (!is_null($msg)) {
            if (is_int($msg)) {
                $msg = "$msg";
            } elseif (empty($msg)) {
                $msg = _("abort() called.");
            } elseif (!is_string($msg)) {
                $msg = "$msg";
            }

            if (!ini_get("display_errors")) {
                echo (_("An error occurred. Please report timestamp '").date('c')._("' to Datasphere support."));
            }

            $level = error_reporting();
            error_reporting($level | E_USER_ERROR);
            trigger_error($msg, E_USER_ERROR);
            error_reporting($level);
        }

        die();
    }

    /**
     * Sets locale based on what comes from $_GET, and returns real locale
     *
     * @author Nuno Barreto <nb@datasphere.ch>
     *
     * @param string $locale Locale guessed from $_GET
     */
    public static function setLocale($locale) {
        if (isset($locale) && $locale) {
            if (strlen($locale) == 2) {
                $locale = $locale."_".strtoupper($locale);
            } else {
                $locale = $locale;
            }
            $_SESSION['locale'] = $locale;
        } elseif (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                if (substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr') {
                    $language = 'fr_FR';
                } else {
                    $language = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                }
            } else {
                $language = 'en_UK';
            }

            if (strlen($language) > 2) {
                $locale = strtolower(substr($language, 0, 2))."_".strtoupper(substr($language, 3, 2));
            } else {
                $locale = strtolower($language)."_".strtoupper($language);
            }
        }

        putenv("LC_ALL=".$locale);
        putenv("LANG=".$locale);
        setlocale(LC_ALL, $locale);

        bindtextdomain($locale, getenv('app_root')."/locale");
        bind_textdomain_codeset($locale, 'UTF-8');
        textdomain($locale);

        return $locale;
    }

    public static function sessionStart() {
        $salt = 'whatever123!"#/$/%&2';

       // self::checkSession();
        if (!session_id()) {
            session_start();
            $config                   = ApplicationConfig::getInstance();
             if ( isset($_SESSION['user']) && isset($_SESSION['organization']))
            {
            	if ( ( $_SESSION['user'] != $config->u ) || ( $_SESSION['organization'] != $config->o ) )
            	{
            		session_destroy();
                	Utils::abort("invalid session number");
            	}
            }

            $_SESSION['user']         = $config->u;
            $_SESSION['organization'] = $config->o;
        }

        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;

            // apply Update if needed
            self::applyUpdateRoutine();
        }

        if (isset($_SESSION['HTTP_USER_AGENT'])) {
            if ($_SESSION['HTTP_USER_AGENT'] != md5($salt.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'])) {
                unset($_SESSION['HTTP_USER_AGENT']);
                unset($_SESSION['initiated']);
                session_regenerate_id(true);
                session_destroy();

                session_start();
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['HTTP_USER_AGENT'] = md5($salt.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);

                header('Location: /');
                Utils::abort();

                return 0;
            } else {
                return 1;
            }
        } else {
            if (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REMOTE_ADDR'])) {
                $_SESSION['HTTP_USER_AGENT'] = md5($salt.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
                return 1;
            } else {
                return 0;
            }
        }
    }

     public static function checkSession() {

        if (session_id()) {

            $file     = session_save_path() . '/sess_' . session_id();
            if(file_exists($file)){
            $config   = ApplicationConfig::getInstance();
            $contents = file_get_contents($file);
            session_decode($contents);

            if ($_SESSION['user'] != $config->u OR $_SESSION['organization'] != $config->o) {
                session_destroy();
                header('Location: '.$config->basePath.'dashboard/index.php');
            }
        }
        }
    }

    public static function sessionRegenerate() {
        $salt = 'whatever123!"#/$/%&2';

        if (!session_id()) {
            session_start();
        }
        
        session_regenerate_id(true);
        $_SESSION['HTTP_USER_AGENT'] = md5($salt.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
    }
    
    public static function userLogin($organization, $user, $loginType = '')
    {
        Utils::sessionRegenerate();
        
        $loginDate              = date('Y-m-d H:i:s');
        
        // closing opened user sessions
        $aOldSessions           = Session_Manager::getOpenedUserSessions($organization, $user);
        foreach ($aOldSessions as $oldSession) {
            $oldSession->endSession($loginDate, Session_Manager::LOGOUT_TYPE_LOGGED_ANOTHER_LOCATION);
        }
        
        // creating new session
        $config                 = ApplicationConfig::getInstance();
        $session                = Session_Manager::createUserSession(session_id(), $organization, $user, $loginDate, $loginType, $config->ipReal, $config->ipProxy);
        
        $_SESSION['login'] = true;
    }
    
    public static function userLogout($organization, $user, $logoutType = '')
    {
        if (!session_id()) {
            session_start();
        }
        
        $logoutDate             = date('Y-m-d H:i:s');
        $session                = Session_Manager::getUserSessionBySessionKey($organization, $user, session_id());
        
        if ($session) {
            $session->endSession($logoutDate, $logoutType);
        }
//        session_regenerate_id();
//        session_destroy();
//        session_unset();
    }
    
    /**
     * @deprecated
     */
    function strToHex($string) {
        $hex='';
        for ($i=0; $i < strlen($string); $i++) {
            $hex .= dechex(ord($string[$i]));
        }
        return $hex;
    }

    /**
     * @deprecated
     */
    function hexToStr($hex) {
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }

    /**
     * @deprecated
     */
    function bc_frombase($s, $base, $scale = 0) {
        //  Convert a number in a given base into an arbitrary precision
        //      number.

        $s = strtolower(trim($s));
        $negative = substr("$s/", 0, 1) == '-';

        if ($negative)
            $s = substr($s, 1);

        $digits = array_flip(str_split('0123456789abcdefghijklmnopqrstuvwxyz'));
        $point = NULL;
        $acc = '0';

        foreach (str_split($s) as $c)
            if ($point !== NULL && $c == '.')
                $point = '1';
            else if (!isset($digits[$c]) || $digits[$c] >= $base)
                return FALSE;
            else {
                $acc = bcadd(bcmul($acc, $base, 0), $digits[$c], 0);

                if (!is_null($point))
                    $point = bcmul($point, $base, 0);
                }

        if ($point !== NULL)
            $acc = bcdiv($acc, $point, $scale);

        return $negative? "-$acc": $acc;
    }

    /**
     * @deprecated
     */
    function bc_tobase($s, $base) {
        //  Convert an arbitrary precision number into a number in the
        //      specified base.

        $s = trim($s);
        $negative = substr("$s/", 0, 1) == '-';

        if ($negative)
            $s = substr($s, 1);

        $digits = str_split('0123456789abcdefghijklmnopqrstuvwxyz');

        $point = -1;
        $parts = explode('.', $s, 2);

        if (isset($parts[1])) {
            $s = $parts[0] . $parts[1];
            $point = strlen($parts[1]);

            if (!$point)
                $point = -1;
            }

        $result = '';

        do {
            $d = bcmod($s, $base);
            $s = bcdiv(bcsub($s, $d, 0), $base, 0);
            $result = $digits[$d + 0] . $result;

            if (!--$point)
                $result = ".$result";
        } while ($s != '0' || $point > 0);

        return $negative? "-$result": $result;
    }

    /**
     * Transliterate a string
     * @param string    $string
     * @param char      $replacementChar
     * @return string
     */
    public static function translit($string, $replacementChar = '_') {
        return preg_replace('/[^a-z0-9]+/i', $replacementChar, $string);
    }

      /**
     * Format date with a given string "YYYYMMDD" and add separator
     *
     * @author Yann JAMAR <yj@datasphere.ch>
     *
     * @param string $locale Locale guessed from $_GET
     * @return String in the format date
     */
    public static function getDateFromString($stringDate,$separator){

         $year  = substr($stringDate, 0, 4);
         $month = substr($stringDate, 4, 2);
         $day   = substr($stringDate, 6);
         $date  = $year.$separator.$month.$separator.$day;
         return $date;
    }
     /**
     * Format time with a given string "hhmmss" and add separator
     *
     * @author Yann JAMAR <yj@datasphere.ch>
     *
     * @param string $locale Locale guessed from $_GET
     * @return String in the format date
     */
    public static function getTimeFromString($stringTime,$separator){
        $stringTime = str_pad($stringTime, 6, '0', STR_PAD_LEFT);

        $hours   = substr($stringTime, 0, 2);
        $minutes = substr($stringTime, 2, 2);
        $seconds = substr($stringTime, 4, 2);
        $time    = $hours.$separator.$minutes.$separator.$seconds;
        return $time;
    }

    /**
        * Return an array of values from an undefined number of values (single or array)
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param  Mixed     $value
        * @param  boolean   $keepNullValues     Define if we keep null values
        * @return Array
        */
    public static function getMultiValues($value = null, $keepNullValues = false)
    {
        // init
        $aValues                = array();

        // single value
        if (is_string($value) OR is_numeric($value) OR is_bool($value) OR is_object($value)) {
            $aValues	= array($value);
        // multiple values
        } else if (is_array($value) AND (count($value) > 0)) {
            $aValues	= $value;
        }

        if (!$keepNullValues) {
            if (is_object(reset($aValues)) OR is_array($aValues)) {
                $aValues        = array_filter($aValues);
            } else {
                $aValues        = array_filter($aValues, 'strlen');
            }
        }

        return $aValues;
    }

    /**
     * Returns first non-null value
     * @param Mixed $_  Args
     * @return Mixed
     */
    public static function coalesce($_)
    {
        // Args
        $aArgs = func_get_args();

        $aValues = array_filter($aArgs, array('Utils', 'isNotNull'));
        return reset($aValues);
    }

    /**
     * Test variable nullity
     * @access public
     * @static
     * @param String $arg
     * @return mixed
     */
    public static function isNotNull($arg)
    {
        if ((is_string($arg) AND (($arg == '') OR (strtoupper($arg) == 'NULL') OR ($arg == 'undefined') OR (strtoupper($arg) == 'NAN') OR ($arg == '0000-00-00 00:00:00') OR ($arg == '0000-00-00') OR ($arg == '00:00:00')))
            OR ((is_integer($arg) OR is_float($arg) OR is_numeric($arg)) AND ($arg == 0)) OR ($arg == null)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Repeats <tt>$string</tt> <tt>$multiplier</tt> times, separated with <tt>$sep</tt>.
     *
     * str_repeat_sep('?', ',', 3) ==> "?,?,?"
     * str_repeat_seap('..', '/', 3) ==> "../../.."
     *
     * @param string $string
     * @param string $sep
     * @param int $multiplier
     * @return string
     */
    public static function str_repeat_sep($string, $sep, $multiplier) {
        $ret = "";
        for($i=0;$i<$multiplier;$i++) {
            if ($i) $ret.=$sep;
            $ret.=$string;
        }
        return $ret;
    }

    public static function sprintf_array($format, $values)
    {
        return call_user_func_array('sprintf', array_merge((array)$format, $values));
    }

    /**
     * Format as a DB Amount
     * e.g : 3'000.25  =>  000000000000300025
     * @param string $amount
     * @return string
     */
    public static function formatAsDBAmount($amount) {
        $nbDigit = 0;

        $amount         = str_replace("'", '', $amount);
        $amount         = str_replace(',', '.', $amount);

        $testAmount = explode('.', $amount);
        if(isset($testAmount[1])){
            $nbDigit = strlen($testAmount[1]);
        }

        $leftDigits     = strstr($amount, '.', true);
        $rightDigits    = str_replace('.', '', strstr($amount, '.'));
        $rightDigits    = str_pad($rightDigits, $nbDigit , '0');

        if (!$leftDigits) {
            $amount     = str_replace('.', '', $amount);
            $amount     = $amount.$rightDigits;
        } else {
            $amount     = $leftDigits.$rightDigits;
        }

        if (substr($amount,0,1) == '-') {
            $amount = '-'.str_pad(substr($amount,1), 17, '0', STR_PAD_LEFT);
        } else {
            $amount = str_pad($amount, 18, '0', STR_PAD_LEFT);
        }

        return $amount;
    }

    public static function formatNumberDec($number, $rangeMin = -999999999999, $rangeMax = 999999999999)
    {
        $number         = str_replace("'", '', $number);
        $number         = str_replace(',', '.', $number);

        if (floatval($number) < $rangeMin) {
            $number  = $rangeMin;
        }
        if (floatval($number) > $rangeMax) {
            $number  = $rangeMax;
        }

        return $number;
    }

    /**
     * Format an amount as 1'337.42
     * @param float     $amount
     * @param boolean   $wCurrency  If true, result =  CHF 1'337.42
     */
    public static function formatAmountToDisplay($amount, $currency = '', $nbDec = 2)
    {
        $sAmount        = number_format($amount, (int)$nbDec, '.', "'");
        if ($currency != '') {
            $sAmount    = $currency.' '.$sAmount;
        }

        return $sAmount;
    }

    public static function formatDate($sDate, $format = 'Y-m-d H:i:s')
    {
        $sdateReturn    = '';
        
        if ($sDate) {
            $length = strlen($sDate);

            // yyyy-mm-dd.hh.ii.ss.uuuuuu
            if ($length == 26) {
                $sDate  = substr($sDate, 0, 19);
            }

            // yyyy-mm-dd.hh.ii.ss
            $sDate      = str_replace(array('-', '/', '.', ':', ' '), '', $sDate);

            // yyyymmddhhiiss
            $length     = strlen($sDate);
            switch ($length) {
                // date time
                case 16 :
                case 14 :
                case 13 : {
                    $date   = self::getDateFromString(substr($sDate, 0, 8), '-');
                    $time   = self::getTimeFromString(substr($sDate, 8), ':');
                    $sDate  = $date.' '.$time;
                    break;
                }
                // date
                case 8 : {
                    $sDate   = self::getDateFromString($sDate, '-');
                    break;
                }
                // time
                case 6 : {
                    $sDate   = self::getTimeFromString($sDate, ':');
                    break;
                }
                default : {
                    break;
                }
            }

            $timestamp  = strtotime($sDate);

            $sdateReturn = date($format, $timestamp);
        }

        return $sdateReturn;
    }

    /**
        * Add a log message to the logFile
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param Mixed $input   Content to display
        * @param String $label   Label of the content to display (optionnal)
        * @return void
        */
    public static function log($input, $label = null)
    {
        // init
        $log	= DebugLog::getInstance();

        if ($log) {
            if (!is_scalar($input)) {
                $log->debugNonScalarObject($input, $label);
            } else {
                    if (is_bool($input)) {
                            $log->debug('(bool) '.(int) $input);
                    } else {
                            $log->debug($input);
                    }
            }
        }
    }

    public static function logTimer($label = '', $timerReference = null)
    {
        // init
        $log	= DebugLog::getInstance();
        if ($log) {
            $log->logTimer($label, $timerReference);
        }
    }

    public static function logMemoryUsage($label = '', $formated = true)
    {
        $memory         = memory_get_usage();
        $memoryReal     = memory_get_usage(true);
        $sLog           = ''.$memory.'(REAL = '.$memoryReal.')';
        if ($formated) {
            $sLog   = round(($memory/1024)/1024, 5)."M (REAL = ".round(($memoryReal/1024)/1024, 5)."M, Max :".ini_get('memory_limit').")";
        }
        $sLog       = (($label)?$label.' : ':'').$sLog;
        self::log($sLog);
    }

    /**
        * Get constants variables of an object
        * @access public
        * @static
        * @param Object $object
        * @param String $f_lib      Regex for the constant label
        * @param String $f_value    Regex for the constant value
        * @return Array
        */
    public static function getConstants($object, $f_lib = null, $f_value = null)
    {
        // init
        $reflect = new ReflectionClass($object);
        $constants = $reflect->getConstants();

        foreach ($constants as $name => $value) {
            if (!is_null($f_lib) AND !preg_match($f_lib, $name)) {
                unset($constants[$name]);
                continue;
            }
            if (!is_null($f_value) AND !preg_match($f_value, $value)) {
                unset($constants[$name]);
                continue;
            }
        }
        return $constants;
    }

    /**
     * Create a directory if not exists
     * @param String $path
     * @return Boolean
     */
    public static function createDirectory($path){

        if(!is_dir($path)){
            mkdir($path, 0777);
            return TRUE;
        } else {
        return FALSE;
        }

    }
    /**
     * Save file with his path
     * @param type $path
     * @return Boolean
     */
    public static function saveFile($path,$content){
        if($path AND $content){
            $fp = fopen($path, 'w');
            fwrite($fp, $content);
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }
    
    
    
    

    /**
     * Delete dir recursively (Be careful with that ! )
     * @param String $dir
     * @return Boolean
     */
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
         return true;
       } else {
           return false;
       }
    }
    /**
     *
     * @param type $line
     * @param type $charMajWidth
     * @param type $charMinWidth
     * @return type 
     */
    public static function getLineWidth( $line = '' , $charMajWidth = 10 , $charMinWidth = 8){
        $majPattern = '/[A-Z_ 23456789mw]+/';
        $smallPattern = '/[i\.jtl1I\')(]+/';
        preg_match_all($majPattern, $line, $matchesMaj);
        preg_match_all($smallPattern, $line, $matchesSmall);
        $nbMaj = 0;
        $nbSmall = 0;
        foreach($matchesMaj[0] as $value){
            $nbMaj += strlen($value);    
        }
        foreach($matchesSmall[0] as $value){
            $nbSmall += strlen($value);    
        }       
        
        $nbMin = strlen($line) - $nbMaj;
        $lenght = $nbMaj*$charMajWidth + $nbMin*$charMinWidth - $nbSmall*8;
        return $lenght;
    }
}
?>
