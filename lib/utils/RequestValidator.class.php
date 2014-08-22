<?php
/**
 * @author Patrick Monnerat <pm@datasphere.ch>
 */

require_once(getenv('app_root').'/lib/utils/Utils.class.php');

class RequestValidatorException extends Exception {}

class RequestValidator {
    //  Predefined value formats.
    const FMT_ANY         = '//';
    const FMT_EMPTY       = '/^$/';
    const FMT_SAME        = '';
    const FMT_INT         = '/^[-+]?(?:0[0-7]*|0x[0-9a-f]+|[1-9]\d*)$/i';
    const FMT_UINT        = '/^(?:0[0-7]*|0x[0-9a-f]+|[1-9]\d*)$/i';
    const FMT_DEC         = '/^[-+]?\d+$/';
    const FMT_FLOAT       = "/^[-]?(\d)*(['][\d][\d][\d])*[,|.]?[\d+]*$/";
    const FMT_UDEC        = '/^\d+$/';
    const FMT_UDECEMPTY   = '/^[-+]?\d*$/';
    const FMT_OCT         = '/^[-+]?[0-7]+$/';
    const FMT_UOCT        = '/^[0-7]+$/';
    const FMT_HEX         = '/^[-+]?(?:0x)?[0-9a-f]+/i';
    const FMT_UHEX        = '/^(?:0x)?[0-9a-f]+/i';
    const FMT_ALPHA       = '/^[a-z0-9_]+$/i';
    const FMT_ALPHAEMPTY  = '/^[a-z0-9_]*$/i';
    const FMT_UPALPHA     = '/^[A-Z0-9]+$/';
    const FMT_LOALPHA     = '/^[a-z0-9]+$/';
    const FMT_ALPHASPACE  = '/^[a-z0-9_ ]+$/i';
    const FMT_ALPHASPEMP  = '/^[a-z0-9_ ]*$/i';
    const FMT_ALPHASPEMPV = '/^[a-z0-9_ ,]*$/i';
    const FMT_ALPHAPIPE   = '/^[a-z0-9_ |]+$/i';
    const FMT_ALPHAACCENT = '/^[A-Za-zÀ-ÿ0-9@_+ .,\'\-|\(\)&]*$/u';
    const FMT_FULLALPHA   = '/^[A-Za-zÀ-ÿ0-9@_+ .,\'|\(\)#&\-;:=]*$/u';

    const FMT_IDENT       = '/^[a-z_][a-z0-9_]*$/i';
    const FMT_DATE        = '/^[0-9\-]*$/';
    const FMT_DATETIME    = '/^[0-9\- :]*$/';
    const FMT_DATETIMETZ  = '/^[A-Z0-9\- :]*$/';
    const FMT_LOCALE      = '/^[a-z_]*$/i';
    const FMT_EMAIL       = '/^[A-Z0-9._%+-@]*$/i';
    const FMT_TELEPHONE   = '/^[0-9+]*$/';
    const FMT_AMOUNT      = '/^[0-9+\-\'.,]*$/';
    const FMT_FILE        = '/^[a-z0-9_\-\/.]+$/i';
    const FMT_AUTHLIST    = '/^[a-z0-9_\-. ]*$/i';

    # Dangerous characters for XSS: > < ( ) [ ] ' " ; : / |
    # Dangerous characters for SQL: ' -- ; #

    //  SSL modes.
    const SSL_NONE      = 0x30;
    const SSL_SERVER    = 0x31;
    const SSL_BOTH      = 0x33;
    const SSL_ANY       = 0x11;


    var $paramList;
    var $fileList;
    var $refererList;
    var $myUrl;
    var $errorProc;

    /**
     * Called when there is an error
     *
     * @param string $msg  Error message
     * @param integer $type Error type
     */
    public function error($msg, $type = E_USER_ERROR) {

        $proc = $this->errorProc;

        if ($proc)  {
            if (!is_callable($proc) || $proc == array(__CLASS__, __METHOD__)) {
                $proc = 'trigger_error';
            }

            call_user_func($proc, $msg, $type);
        } else {
            $this->throwException($msg, $type);
        }
    }

    /**
     * Class constructor
     *
     * @param string $error_proc What function should be called when there is an error
     */
    public function __construct($error_proc = '') {
        $this->paramList = array();
        $this->fileList = array();
        $this->refererList = array();
        $this->errorProc = $error_proc;

        if (!Utils::sessionStart()) {
            Utils::abort(_('Please contact your administrator.'));
        }

        $locale = Utils::setLocale(isset($_GET["locale"])?$_GET["locale"]:'');

        //  Build the request URL.

        if (!isset($_SERVER['SERVER_NAME'])) {
            $this->error('This has not been scheduled by an HTTP request');
            return;
        }

        $this->myUrl = array('scheme' => 'http');

        if (isset($_SERVER['HTTPS'])) {
            $this->myUrl['scheme'] = 'https';
        }

        //  Ignore the user and the password: they are hardly or not
        //      available to the server.

        $this->myUrl['host'] = $_SERVER['SERVER_NAME'];

        if (isset($_SERVER['SERVER_PORT'])) {
            $this->myUrl['port'] = $_SERVER['SERVER_PORT'];
        } else {
            $this->myUrl['port'] = isset($_SERVER['HTTPS'])? 443: 80;
        }

        $this->myUrl['path'] = $_SERVER['SCRIPT_NAME'];

        if (isset($_SERVER['QUERY_STRING'])) {
            $this->myUrl['query'] = $_SERVER['QUERY_STRING'];
        }

        //  Ignore the fragment: it is not available to the server.
    }

    private function arrayName($name) {
        $m = array();

        if (!preg_match('/^(.+?)(\[\])?$/', $name, $m)) {
            $this->error("The given parameter name `$name' is not valid");
            return array(FALSE, FALSE, FALSE);
        }

        $m[0] = TRUE;
        $m[2] = !empty($m[2]);
        return $m;
    }

    /**
     *
     * @param type $name
     * @param type $format
     * @param type $method
     * @param type $defaultValue
     * @param type $keepSpaces
     * @param type $minLength
     * @param type $maxLength
     * @param type $minOccur
     * @param type $maxOccur
     * @return string
     */
    public function addParameter($name, $format, $method = 'REQUEST', $defaultValue = '', $keepSpaces = FALSE, $minLength = 0, $maxLength = -1, $minOccur = 1, $maxOccur = -1) {
        list($status, $shortname, $isArray) = $this->arrayName($name);

        if (!$status) {
            return FALSE;
        }

        if ($maxOccur && !$isArray) {
            $maxOccur = 1;
        }

        if (isset($this->paramList[$shortname])) {
            $this->error("Parameter name `$shortname' listed twice");
            return FALSE;
        }

        if (!is_array($format)) {
            $format = array($format);
        }

        $trim = !$keepSpaces;
        $minLength = (int) $minLength;
        $maxLength = (int) $maxLength;
        $minOccur = (int) $minOccur;
        $maxOccur = (int) $maxOccur;
        $this->paramList[$shortname] = compact('trim', 'isArray', 'minLength', 'maxLength', 'minOccur', 'maxOccur', 'format', 'method', 'defaultValue');

        if ($method == 'REQUEST') {
            $value = isset($_REQUEST[$shortname]) ? $_REQUEST[$shortname] : $defaultValue;
        } elseif ($method == 'POST') {
            $value = isset($_POST[$shortname]) ? $_POST[$shortname] : $defaultValue;
        } elseif ($method == 'GET') {
            $value = isset($_GET[$shortname]) ? $_GET[$shortname] : $defaultValue;
        } else {
            $value = '';
        }

        return $value;
    }

    public function checkParameters($permit_others = FALSE) {
        if (!is_array($_SERVER)) {
            $this->error('This has not been scheduled by an HTTP request');
            return FALSE;
        }

        $status = TRUE;
        $req = is_array($_REQUEST)?array_fill_keys(array_keys($_REQUEST), TRUE): array();

        foreach ($this->paramList as $name => $p) {
            if (!isset($req[$name])) {
                if ($p['minOccur'] > 0) {
                    $this->error("Parameter `$name' is undefined");
                    $status = FALSE;
                }
            } else {
                if ($p['method'] == 'REQUEST') {
                    if (isset($_REQUEST[$name])) {
                        $v = $_REQUEST[$name];
                    } else {
                        return FALSE;
                    }
                } elseif ($p['method'] == 'POST') {
                    if (isset($_POST[$name])) {
                        $v = $_POST[$name];
                    } else {
                        return FALSE;
                    }
                } elseif ($p['method'] == 'GET') {
                    if (isset($_GET[$name])) {
                        $v = $_GET[$name];
                    } else {
                        return FALSE;
                    }
                } else {
                    return FALSE;
                }
                unset($req[$name]);

                if (!is_array($v)) {
                    if ($v && $p['isArray']) {
                        $this->error("Parameter `$name' " .
                            ' should not be an array');
                        $status = FALSE;
                    }

                    $v = array($v);
                } else {
                    if (!$p['isArray']) {
                        $this->error("Parameter `$name' " . 'should be an array');
                        $status = FALSE;
                    }

                    //  Delete empty occurrences.

                    foreach ($v as $i => $w) {
                        if ($w === '') {
                            unset($v[$i]);
                        }
                    }
                }

                if ($p['minOccur'] >= 0 && count($v) < $p['minOccur']) {
                    $this->error("Parameter `$name' has not " . 'enough occurrences');
                    $status = FALSE;
                }

                if ($p['maxOccur'] >= 0 && count($v) > $p['maxOccur']) {
                    $this->error("Parameter `$name' has too " . 'many occurrences');
                    $status = FALSE;
                }

                foreach ($v as $value) {
                    if ($p['trim']) {
                        $value = trim($value);
                    }

                    if ($p['minLength'] >= 0 &&
                        strlen($value) < $p['minLength']) {
                        $this->error("Parameter `$name' " .
                            'value too short');
                        $status = FALSE;
                    }

                    if ($p['maxLength'] >= 0 &&
                        strlen($value) > $p['maxLength']) {
                        $this->error("Parameter `$name' " .
                            'value too long');
                        $status = FALSE;
                    }

                    $i = FALSE;
                    if($value){

                    foreach ($p['format'] as $re) {
                        if ($re == RequestValidator::FMT_ANY) {
                            $i = TRUE;
                            break;
                        } else if (($i = preg_match($re, $value))) {
                            break;
                        }
                    }
                    } else {
                        $i = TRUE;
                    }

                    if (!$i) {
                        $this->error("Parameter '$name' " . 'value has an invalid format');
                        $status = FALSE;
                    }
                }
            }
        }

        if (!$permit_others && !empty($req)) {
            $this->error('Unexpected request parameters \'' . implode("', '", array_keys($req)) . "'");
            $status = FALSE;
        }

        return $status;
    }

    public function addFile($name, $type = RequestValidator::FMT_ANY, $minSize = 0, $maxSize = -1, $minOccur = 1, $maxOccur = -1) {
        list($status, $shortname, $isArray) = $this->arrayName($name);

        if (!$status) {
            return FALSE;
        }

        if ($maxOccur && !$isArray) {
            $maxOccur = 1;
        }

        if (isset($this->fileList[$shortname])) {
            $this->error("addFile(): file `$shortname' listed twice");
            return FALSE;
        }

        $minSize = (int) $minSize;
        $maxSize = (int) $maxSize;
        $minOccur = (int) $minOccur;
        $maxOccur = (int) $maxOccur;

        if (!is_array($type)) {
            $type = array($type);
        }

        $this->fileList[$shortname] = compact('isArray', 'minSize', 'maxSize', 'minOccur', 'maxOccur', 'type');

        $file = isset($_FILES[$name]) ? $_FILES[$name] : '';
        return $file;
    }


    private function fileUploadErrorMessage($error_code) {
        switch ($error_code) {

            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';

            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';

            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';

            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';

            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';

            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';

            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
        }

        return 'Unknown upload error';
    }


    public function checkFiles($permit_others = FALSE) {
        if (!is_array($_SERVER)) {
            $this->error('This has not been scheduled by an HTTP request');
            return FALSE;
        }

        $status = TRUE;
        $files = is_array($_FILES)? array_fill_keys(array_keys($_FILES), TRUE): array();

        foreach ($this->fileList as $name => $p) {
            if (!isset($files[$name])) {
                if ($p['minOccur'] > 0) {
                    $this->error("File `$name' is undefined");
                    $status = FALSE;
                }

                continue;
            }

            $v = $_FILES[$name];
            unset($files[$name]);

            if (!is_array($v['error'])) {
                if ($p['isArray']) {
                    $this->error("File `$name' " .
                        ' should not be an array');
                    $status = FALSE;
                }

                $v = array($v);
            } else {
                if (!$p['isArray']) {
                    $this->error("Parameter `$name' should be " .
                        'an array');
                    $status = FALSE;
                }

                //  If a file is multi-occurrences, it is structured
                //      as an associative array of numeric
                //      index arrays: reverse the index order.

                $w = array();

                foreach ($v as $i => $v2) {
                    foreach ($v2 as $j => $v3) {
                        $w[$j][$i] = $v3;
                    }
                }

                $v = $w;
            }

            //  Remove files not uploaded.

            foreach ($v as $i => $v2) {
                if ($v2['error'] == UPLOAD_ERR_NO_FILE) {
                    unset($v[$i]);
                }
            }

            //  Check occurrence count.

            if ($p['minOccur'] >= 0 && count($v) < $p['minOccur']) {
                $this->error("Parameter `$name' has not " .
                    'enough occurrences');
                $status = FALSE;
            }

            if ($p['maxOccur'] >= 0 && count($v) > $p['maxOccur']) {
                $this->error("Parameter `$name' has too " .
                    'many occurrences');
                $status = FALSE;
            }

            //  Check each file occurrence.

            foreach ($v as $w) {
                //  Check upload status.

                if ($w['error'] != UPLOAD_ERR_OK) {
                    $this->error("File `$name': " .
                        $this->fileUploadErrorMessage($w['error']));
                    $status = FALSE;
                    continue;
                }

                //      Check source file name:
                //      _ Reject if it contains a null byte.
                //      _ Accept Unix & W$ notation.
                //      _ Accept single component- and absolute paths.

                if (!preg_match('#^(?:(?:[a-z]:)?(?:[/\\\\]+[^\\x00/\\\\]+)*[/\\\\]+)?[^\\x00/\\\\]+$#i',
                    $w['name'])) {
                        $this->error("File `$name' has an invalid " .
                            'source name');
                        $status = FALSE;
                        }

                //  Check file size.

                if ($p['minSize'] >= 0 && $w['size'] < $p['minSize']) {
                    $this->error("File `$name' is too short");
                    $status = FALSE;
                }

                if ($p['maxSize'] >= 0 && $w['size'] > $p['maxSize']) {
                    $this->error("File `$name' is too large");
                    $status = FALSE;
                }

                //  Check content type.

                $i = FALSE;

                foreach ($p['type'] as $re) {
                    if ($re == RequestValidator::FMT_ANY) {
                        $i = TRUE;
                        break;
                    } else if (($i = preg_match($re, $value))) {
                        break;
                    }
                }

                if (!$i) {
                    $this->error("File '$name' has an invalid content type");
                    $status = FALSE;
                }
            }
        }

        if (!$permit_others && !empty($files)) {
            $this->error('Unexpected uploaded files `' .
                implode("', `", array_keys($files)) . "'");
            $status = FALSE;
        }

        return $status;
    }


    public function checkMethod($method, $negate = FALSE) {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            $this->error('This has not been scheduled by an HTTP request');
            return FALSE;
        }

        if (!is_array($method)) {
            $method = array($method);
        }

        $method = array_fill_keys(array_map('strtoupper', $method), TRUE);

        if (isset($method[strtoupper($_SERVER['REQUEST_METHOD'])]) == $negate) {
            $this->error('Invalid HTTP request method');
            return FALSE;
        }

        return TRUE;
    }


    public function checkSSL($mode, $negate = FALSE) {
        if (!isset($_SERVER)) {
            $this->error('This has not been scheduled by an HTTP request');
            return FALSE;
        }

        if (!isset($_SERVER['HTTPS'])) {
            $mymode = RequestValidator::SSL_NONE;
        } else if (isset($_SERVER['SSL_CLIENT_S_DN'])) {
            $mymode = RequestValidator::SSL_BOTH;
        } else {
            $mymode = RequestValidator::SSL_SERVER;
        }

        if (!is_array($mode)) {
            $mode = array($mode);
        }

        $ok = $negate;

        foreach ($mode as $m) {
            if (($mymode & ($m >> 4)) == $m & 0xF) {
                $ok = !$negate;
                break;
            }
        }

        if (!$ok) {
            $this->error('Unexpected SSL mode');
        }

        return $ok;
    }

    public function addReferer($scheme = RequestValidator::FMT_ANY, $host = RequestValidator::FMT_ANY, $port = RequestValidator::FMT_ANY, $path = RequestValidator::FMT_ANY, $query = RequestValidator::FMT_ANY, $reject = FALSE) {
        $reject = (bool) $reject;
        $this->refererList[] = compact('scheme', 'host', 'port', 'path', 'query', 'reject');
    }

    public function checkReferer() {
        if (!isset($_SERVER)) {
            $this->error('This has not been scheduled by an HTTP request');
            return FALSE;
        }

        if (!isset($_SERVER['HTTP_REFERER'])) {
            $this->error('No referer (direct call?)');
            return FALSE;
        }

        $rurl = parse_url($_SERVER['HTTP_REFERER']);

        if (isset($rurl['port'])) {
            $rurl['port'] = (int) $rurl['port'];
        } else if (isset($rurl['scheme'])) {
            $rurl['port'] = strcasecmp($rurl['scheme'], 'https')? 80: 443;
        } else {
            $rurl['port'] = -2; // Force failure.
        }

        foreach ($this->refererList as $r) {
            switch ($r['port']) {
                case RequestValidator::FMT_ANY:
                    break;

                case RequestValidator::FMT_SAME:
                    if ($rurl['port'] != (int) $this->myUrl['port']) {
                        continue 2;
                    }
                    break;

                default:
                    if ($rurl['port'] != (int) $r['port'])
                        continue 2;

                    break;
            }

            foreach (array('scheme', 'host', 'path', 'query') as $f) {
                switch ($r[$f]) {
                    case RequestValidator::FMT_ANY:
                        break;

                    case RequestValidator::FMT_SAME:
                        $v1 = isset($rurl[$f])? $rurl[$f]: '';
                        $v2 = isset($this->myUrl[$f])?
                            $this->myUrl[$f]: '';

                        if ($f == 'scheme' || $f == 'host') {
                            $i = strcasecmp($v1, $v2);
                        } else {
                            $i = $v1 != $v2;
                        }

                        if ($i) {
                            continue 3;
                        }
                        break;

                    default:
                        $v1 = isset($rurl[$f])? $rurl[$f]: '';

                        if (!preg_match($r[$f], $v1)) {
                            continue 3;
                        }

                        break;
                }
            }

            if ($r['reject']) {
                break;
            }

            return TRUE;
        }
        $this->error('This referer has no rights to perform this request');
        return FALSE;
    }

    /**
     * Helper functions
     * @author Nuno Barreto <nb@datasphere.ch>
     */

    public function checkParametersSimple($permit_others = FALSE) {
        try {
            $this->checkParameters($permit_others);
        } catch (RequestValidatorException $e) {
            die('One or more of the fields has an invalid character.');
            //die($e->getMessage());
        }
    }

    public function checkRefererSimple() {
        try {
            $this->checkReferer();
        } catch (RequestValidatorException $e) {
            Utils::abort($e->getMessage());
        }
    }
/**
 *
 * @param type $column
 * @param type $method
 * @param type $name
 * @param type $forceMinOccur
 * @param type $defaultValue
 * @param type $isAdd
 * @return type 
 */
    public function addParameterSimple($column, $method = 'REQUEST', $name = '', $forceMinOccur = -1, $defaultValue = '', $isAdd = false) {
        if (!$name) {
            $name = $column['dbField'];
        }
        $format = '//';
        $keepSpaces = FALSE;
        $minLength = 0;
        $maxLength = -1;
        $minOccur = 0;
        $maxOccur = -1;

        if (isset($column['regExp']) AND isset($column['required']) && $column['required'] == 'true') {
            $format = '/'.$column['regExp'].'/i';            
        }
        
        if (isset($column['minLength'])) {
            $minLength = $column['minLength'];

            if ($minLength > 0) {
                $minOccur = 1;
            }
        }
        if (isset($column['maxLength'])) {
            $maxLength = $column['maxLength'];
        }

        if (isset($column['required']) && $column['required'] == 'true') {
            $minOccur = 1;
        }
        if (isset($column['maxOccur'])) {
            $maxOccur = $column['maxOccur'];
        }

        if ($forceMinOccur > -1) {
            $minOccur = $forceMinOccur;
        }
        
        if (!$isAdd) {
            $minLength = 0;
            $minOccur = 0;
            if (isset($column['regExpSearch'])) {
                $format = '/^'.$column['regExpSearch'].'$/i';
            }
        }
        
        $value = $this->addParameter($name, $format, $method, $defaultValue, $keepSpaces, $minLength, $maxLength, $minOccur, $maxOccur);

        return $value;
    }
    public function throwException($msg, $type) {
        throw new RequestValidatorException($msg, $type);
    }


}
?>