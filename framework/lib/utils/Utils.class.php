<?php
/**
 * Utils.class.php
 *
 * Path: /lib/utils/Utils.class.php
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 * @package utils
 */

/**
 * Utils class
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 */
class Utils {
    function seemsUTF8($str) {
        if (is_array($str)) { // This should be removed and replaced by iterative check, but works
            return true;
        }
        $length = strlen($str);
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) $n = 0; # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }

    // Alternative to utf8_encode()
    function removeNonUTF8Characters ($string) {
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        return ($string);
    }

    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param    string   $str    String in camel case format
     * @return    string            $str Translated into underscore format
     */
    public static function fromCamelCase($str) {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    public static function toCamelCase($str, $capitalise_first_char = false) {
        if($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    /**
     * Returns a random id after taking into account the priority of each element.
     * $arrayWithPriority format example:
     *          array(array(1 => 5.4),
     *                array(2 => 10.2),
     *                array(3 => 4.21),
     *                array(4 => 17.33),
     *                array(5 => 7.99),
     *               );
     *
     * @param   array   $arrayWithPriority
     * @param   int     $maximumValue
     * @return  int
     */
    public static function getArrayWithPriority($arrayWithPriority, $maximumPriority = 10) {
        $cache = Caching::getInstance();
        $cacheID = 'Utils_getArrayWithPriority_'.md5(serialize($arrayWithPriority)).'_'.$maximumPriority;

        if ($cachedData = $cache->get($cacheID)) {
            return $cachedData;
        } else {
            if ($arrayWithPriority) {
                $minimum = PHP_INT_MAX;
                $maximum = 0;

                foreach($arrayWithPriority as $row) {
                    if ($minimum > $row) {
                        $minimum = $row;
                    }
                    if ($maximum < $row) {
                        $maximum = $row;
                    }
                }
                $augmentedArray = array();

                foreach($arrayWithPriority as $key => $row) {
                    // It works, believe me, don't mess with it!!!
                    $normalizedPriority = 1;
                    if (($maximum-$minimum) > 0){
                        $normalizedPriority = intval((($row-$minimum)/($maximum-$minimum)) * ($maximumPriority - 1)) + 1;
                    } else {
                        $normalizedPriority = $maximumPriority / 2;
                    }

                    for ($i = 0; $i < $normalizedPriority; $i++) {
                        $augmentedArray[] = $key;
                    }
                }

                $cache->save($cacheID, $augmentedArray, rand(55, 70)); // About 1 minute
                return $augmentedArray;
            } else {
                $cache->save($cacheID, 0, rand(55, 70) * 10); // About 10 minutes
                return 0;
            }
        }
    }

    public static function getRandomArrayIDWithPriority($arrayWithPriority, $maximumPriority = 10) {
        $augmentedArray = self::getArrayWithPriority($arrayWithPriority, $maximumPriority);

        return $augmentedArray[array_rand($augmentedArray)];
    }

    // Parameters can be arrays of strings or strings!!!
    public static function aproximateCompare($firstArray, $secondArray) {
        $cache = Caching::getInstance();
        $cacheID = 'Utils_aproximateCompare_'.md5(serialize($firstArray).serialize($secondArray));

        if ($cachedData = $cache->get($cacheID)) {
            return $cachedData;
        } else {
            if (is_string($firstArray)) {
                $firstArray = array($firstArray);
            } elseif (is_array($firstArray)) {
            } else {
                return 0;
            }

            if (is_string($secondArray)) {
                $secondArray = array($secondArray);
            } elseif (is_array($secondArray)) {
            } else {
                return 0;
            }

            $equivalence = 0;
            foreach ($firstArray as $first) {
                foreach ($secondArray as $second) {
                    // Test real equivalence all lowercase
                    if (strtolower($first) == strtolower($second)) {
                        $equivalence = 100;
                        break(2);
                    } else {
                        $length = strlen($first);
                        if (strlen($second) < $length) {
                            $length = strlen($second);
                        }

                        $firstSub = substr($first,0,$length);
                        $secondSub = substr($second,0,$length);

                        similar_text(strtolower($firstSub), strtolower($secondSub), $similarity);

                        if ($similarity == 100) {
                            $equivalence = 100;
                            break(2);
                        } elseif ($similarity > $equivalence) {
                            $equivalence = $similarity;
                        }
                    }
                }
            }

            $cache->save($cacheID, $equivalence, rand(55, 70) * 15); // About 15 minutes
            return $equivalence;
        }
    }

    // Direct alternative for file_get_contents();
    public static function getUrlContent($url, $timeout = 2, $force = false, $getCurlInfo = false) {
        // Avoid sending postbacks when in dev
        if (gethostname() == 'YDSERVER-LX' && !$force) {
            $log = DebugLog::getInstance();
            $log->debugNonScalarObject($url);

            #echo "<b>Postback:</b> $url<br>";

            return $url;
        } else {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout * 2);

            $data = curl_exec($ch);

            if($getCurlInfo === false){
                curl_close($ch);
                return $data;
            }else{
                $curlInfo = curl_getinfo($ch);
                curl_close($ch);
                return array_merge(array("data"=>$data), array("curlInfo"=>$curlInfo));
            }
        }
    }

    /**
     * From: http://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/
       Example:
            $data = array(
             'http://search.yahooapis.com/VideoSearchService/V1/videoSearch?appid=YahooDemo&query=Pearl+Jam&output=json',
             'http://search.yahooapis.com/ImageSearchService/V1/imageSearch?appid=YahooDemo&query=Pearl+Jam&output=json',
             'http://search.yahooapis.com/AudioSearchService/V1/artistSearch?appid=YahooDemo&artist=Pearl+Jam&output=json'
            );
            $r = multiRequest($data);
     *
     * @param type $data
     * @param type $options
     * @return type
     */
    function getMultipleUrlContents($data, $options = array()) {
        // array of curl handles
        $curly = array();
        // data to be returned
        $result = array();

        // multi handle
        $mh = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($data as $id => $d) {

          $curly[$id] = curl_init();

          $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
          curl_setopt($curly[$id], CURLOPT_URL,            $url);
          curl_setopt($curly[$id], CURLOPT_HEADER,         0);
          curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

          // post?
          if (is_array($d)) {
            if (!empty($d['post'])) {
              curl_setopt($curly[$id], CURLOPT_POST,       1);
              curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
            }
          }

          // extra options?
          if (!empty($options)) {
            curl_setopt_array($curly[$id], $options);
          }

          curl_multi_add_handle($mh, $curly[$id]);
        }

        // execute the handles
        $running = null;
        do {
          curl_multi_exec($mh, $running);
        } while($running > 0);


        // get content and remove handles
        foreach($curly as $id => $c) {
          $result[$id] = curl_multi_getcontent($c);
          curl_multi_remove_handle($mh, $c);
        }

        // all done
        curl_multi_close($mh);

        return $result;
    }

    // Splits string at the nth occurrence of a character
    function splitStringAtNth($string, $needle, $nth){
        $max = strlen($string);
        $n = 0;
        for ($i = 0; $i < $max; $i++) {
            if ($string[$i] == $needle) {
                $n++;
                if($n >= $nth) {
                    break;
                }
            }
        }
        /*$arr[] = substr($string, 0, $i);
        $arr[] = substr($string, $i+1, $max);

        return $arr;*/

        $str = substr($string, 0, $i);

        return $str;
    }

    public static function compileParameterStringFromArray($parameters) {
        if (is_array($parameters)) {
            $parametersStep = array();
            foreach ($parameters as $parameterKey => $parameterValue) {
                if ($parameterKey == 'yd') {
                    $parametersStep[] = $parameterKey.'='.$parameterValue;
                } else {
                    $parametersStep[] = $parameterKey.'='.urlencode($parameterValue);
                }
            }

            $parametersString = join('&', $parametersStep);

            return ($parametersString);
        } else {
            return '';
        }
    }

    public static function compileUrlWithParameters($url, $parameters) {
        $parametersString = '';
        if (is_array($parameters)) {
            $parametersString = self::compileParameterStringFromArray($parameters);
        } else {
            $parametersString = $parameters;
        }

        // If URL already as parameters
        if (strpos($url, '?')) {
            if ((substr($url, -1) == '?') || (substr($url, -1) == '&')) {
                $url = $url.$parametersString;
            } else {
                $url = $url.'&'.$parametersString;
            }
        // If URL does not have parameters
        } else {
            $url = $url.'?'.$parametersString;
        }

        return ($url);
    }

    /**
     * Call url with separated parameters
     *
     * @param   string      $url            URL to call
     * @param   array       $parameters     Parameters to add to URL
     * @param   integer     $timeout        Timeout in seconds
     *
     * @return  string      Url Content
     *
     */
    public static function getUrlWithParameters($url, $parameters, $timeout = 15) {
        $url = self::compileUrlWithParameters ($url, $parameters);

        return self::getUrlContent($url, $timeout);
    }

    /**
     * Check if variable exists before using it
     *
     * @param   array   $var    Variable to check if exists
     * @param   array   $key    Variable key to check if exists
     * @param   array   $safe   What to return when empty
     *
     * @return  mixed   Variable value or '' or $safe
     *
     */
    public static function safeGetVariable($var, $key, $safe = '') {
        if (!empty($var[$key])) {
            $safe = $var[$key];
        }
        return $safe;
    }

    /**
     * Truncate text
     *
     * @author  Chirp Internet: www.chirp.com.au
     *
     * @param   string      $string     Text to be truncated
     * @param   integer     $limit      Amount of characters we want to have
     * @param   string      $break      Where to break the string
     * @param   string      $pad        What to insert at the end of the string
     *
     * @return  string      truncated text
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
     * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
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
                echo (_("An error occurred. Please report timestamp '").gmdate('c')._("' to YDigital Media support."));
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
     * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
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
        * Return an array of values from an undefined number of values (single or array)
        * @access public
        * @static
        * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
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
            $aValues    = array($value);
        // multiple values
        } else if (is_array($value) AND (count($value) > 0)) {
            $aValues    = $value;
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
    /**
        * Add a log message to the logFile
        * @access public
        * @static
        * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
        * @param Mixed $input   Content to display
        * @param String $label   Label of the content to display (optionnal)
        * @return void
        */
    public static function log($input, $label = null)
    {
        // init
        $log    = DebugLog::getInstance();

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
        $log    = DebugLog::getInstance();
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


    # Utils Array


    /**
     * Sort an std array depending of a property
     * @access public
     * @static
     * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
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
            $aArgs  = func_get_args();
            $aArgs  = array_slice($aArgs, 1);

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
                                    $value[$attribut]   = $element[$attribut];
                                } else {
                                    $value[$attribut]   = null;
                                }
                            } else {
                                if (isset($element->$attribut)) {
                                    $value->$attribut   = $element->$attribut;
                                } else {
                                    $value->$attribut   = null;
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
     * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
     * @param Mixed $a
     * @param Mixed $b
     * @return Integer -1, 0 or 1.
     */
    private static function compareStdArray($a, $b)
    {
        // init
        $return         = 0;
        if (is_array($a)) {
            $aProperty      = $a[$GLOBALS['key']];
        } else {
            $aProperty      = $a->$GLOBALS['key'];
        }
        if (is_array($b)) {
            $bProperty      = $b[$GLOBALS['key']];
        } else {
            $bProperty      = $b->$GLOBALS['key'];
        }

        if ((isset ($aProperty)) AND (isset ($bProperty))) {
            switch(true) {
                case ((!is_string($aProperty)) AND ((!is_string($bProperty)))) : {
                    if ($aProperty != $bProperty) {
                        if ($GLOBALS['sort'] == SORT_ASC) {
                            if ($aProperty > $bProperty) {
                                $return     = 1;
                            } elseif ($aProperty < $bProperty) {
                                $return     = -1;
                            }
                        } elseif ($GLOBALS['sort'] == SORT_DESC) {
                            if ($aProperty > $bProperty) {
                                $return     = -1;
                            } elseif ($aProperty < $bProperty) {
                                $return     = 1;
                            }
                        }
                    } else {
                        $return = 0;
                    }
                    break;
                }
                default : {
                    $aProperty  = strval($aProperty);
                    $bProperty  = strval($bProperty);

                    if (strcasecmp($aProperty, $bProperty) != 0) {
                        if ($GLOBALS['sort'] == SORT_ASC) {
                            if (strcasecmp($aProperty, $bProperty) > 0) {
                                $return     = 1;
                            } elseif (strcasecmp($aProperty, $bProperty) < 0) {
                                $return     = -1;
                            }
                        } elseif ($GLOBALS['sort'] == SORT_DESC) {
                            if (strcasecmp($aProperty, $bProperty) > 0) {
                                $return     = -1;
                            } elseif (strcasecmp($aProperty, $bProperty) < 0) {
                                $return     = 1;
                            }
                        }
                    } else {
                        $return = 0;
                    }
                    break;
                }
            }
        }
        return $return;
    }


    # Utils JSON

    public static function jsEscape($str) {
        return addcslashes($str,"\\\'\"&\n\r<>");
    }

    public static function generateLink($href = '', $imgUrl = '', $imgTitle = '', $linkText = '', $target = '')
    {
        $link = '';
        $link = "<a ";
        if ($target) {
            $link .= " target=\"".$target."\"";
        }
        $link .= " href=\"".$href."\">";
        if ($imgUrl) {
            $imgTitle   = htmlspecialchars($imgTitle);
            $link       .= "<img src=\"".$imgUrl."\" title=\"".$imgTitle."\"  style=\"width:16px;height:16px;\" />";
        }
        if ($linkText) {
            $link       .= $linkText;
        }
        $link .= "</a>";

        return $link;
    }

    public static function generateMainDialogLink($dialogTitle = '', $href = '', $imgUrl = '', $imgTitle = '', $linkText = '')
    {
        $link = '';
        $link = "<a href=\"#\" onClick=\"dijit.byId('MainDialog').attr('title', '".self::jsEscape($dialogTitle)."');dijit.byId('MainDialog').attr('href','".$href."');dijit.byId('MainDialog').show();dijit.byId('MainDialog').closeButtonNode.title = '"._('Cancel')."';\">";
        if ($imgUrl) {
            $imgTitle   = htmlspecialchars($imgTitle);
            $link       .= "<img src=\"".$imgUrl."\" title=\"".$imgTitle."\" alt=\"".$imgTitle."\" style=\"width:16px;height:16px;\"  />";
        }
        if ($linkText) {
            $link       .= $linkText;
        }
        $link .= "</a>";

        return $link;
    }




    public static function stringUrlToArray($string){
        $infoArrayCampaignAux = explode('&', $string);
        $infoArrayCampaign = array();
        foreach($infoArrayCampaignAux as $value){
            if(!empty($value)){
                list($key, $value) = explode('=', $value);
                $infoArrayCampaign[$key] = urldecode($value);
            }

        }

        return $infoArrayCampaign;
    }
    public static function compare2DateTimes($startDate, $endDate){
        $tsStartDate = strtotime($startDate);
        $tsEndDate = strtotime($endDate);

        if($tsStartDate > $tsEndDate){
            return array('status'=>'ko', 'message'=> 'START DATE: '.$startDate.' bigger then END DATA:'.$endDate);
        }elseif($tsStartDate == $tsEndDate){
            return array('status'=>'ko', 'message'=> 'START DATE: '.$startDate.' equals END DATE:'.$endDate);
        }else{
            return 'ok';
        }
    }

    public static function getInfoFile($file){
        //GET MIME TYPE
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file);


        //GET INFO FILE
        list($width, $height, $type, $attr) = getimagesize($file);

        return array('width' => $width, 'height' => $height, 'type' => $type, 'attr' =>$attr, 'mimeType' => $mimeType);
    }
    public static function getInfoFileResource($file){
        //GET MIME TYPE
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);

        //GET INFO FILE
        $image = imagecreatefromstring($file);
        $width = imagesx($image);
        $height = imagesy($image);

        return array('width' => $width, 'height' => $height, 'mimeType' => $mimeType);
    }


    public static function decryptPathFile($file){
        $firstFolder = substr($file, 0, 2);
        $secondFolder = substr($file, 2, 2);

        return $firstFolder.DIRECTORY_SEPARATOR.$secondFolder.DIRECTORY_SEPARATOR.$file;
    }

    public static function parsePutToVariables($raw_data){
        // Fetch content and determine boundary
        //$raw_data = file_get_contents('php://input');
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));
        $data = array();

        // Fetch each part
        if(!empty($raw_data)){
            $parts = array_slice(explode($boundary, $raw_data), 1);

            foreach ($parts as $part) {
                // If this is the last part, break
                if ($part == "--\r\n") break;

                // Separate content from headers
                $part = ltrim($part, "\r\n");
                list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

                // Parse the headers list
                $raw_headers = explode("\r\n", $raw_headers);
                $headers = array();
                foreach ($raw_headers as $header) {
                    list($name, $value) = explode(':', $header);
                    $headers[strtolower($name)] = ltrim($value, ' ');
                }

                // Parse the Content-Disposition to get the field name, etc.
                if (isset($headers['content-disposition'])) {
                    $filename = null;
                    preg_match(
                        '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                        $headers['content-disposition'],
                        $matches
                    );
                    list(, $type, $name) = $matches;
                    isset($matches[4]) and $filename = $matches[4];

                    // handle your fields here
                    switch ($name) {
                        // this is a file upload
                        case 'userfile':
                             file_put_contents($filename, $body);
                             break;

                        // default for all other files is to populate $data
                        default:
                             $data[$name] = substr($body, 0, strlen($body) - 2);
                             break;
                    }
                }
            }
        }


        return $data;
    }
}
?>
