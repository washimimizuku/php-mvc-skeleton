<?php
//error_reporting(E_ALL);

require_once('Smarty/Smarty.class.php');

class ApplicationInit {
    public $smarty;
    public $accessAuthorizations;
    public $locale;

    public $config;
    public $userGlobalConfig;
    public $orgGlobalConfig;
    
    public function __construct($wRedirection = true) {
        if (!Utils::sessionStart()) {
            Utils::abort(_('Please contact your administrator.'));
        }

        // init
        $this->init();

        // check application
        $this->checkApplication($wRedirection);

        // assign default smarty attributes
        $this->defaultSmartyAssign();
    }

    /**
     * Init main element of the application
     * @return void
     */
    private function init() {
//        set_error_handler(array($this, 'errorHandler'));
        
        // init log's timer reference
        DebugLog::getInstance();

        // init config
        $this->config       = ApplicationConfig::getInstance();
        
        $user               = LDAPUser_Manager::getUser($this->config->u, $this->config->o);
        
        // save the locale value in User config
        if (isset($_GET["locale"])) {
            $user->setOptionValue('global', LDAPAuthorization_Manager::TARGET_LANGUAGE, $_GET["locale"]);
        }
        
        // getting Global config of the user
        $this->userGlobalConfig = $user->getOptionsValues('global'); 
        
        // init locale (language etc)
        $this->locale = Utils::setLocale(isset($this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_LANGUAGE])?$this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_LANGUAGE]:'');
        
        // init smarty
        $this->smarty = new Smarty();
        $this->smarty->template_dir = $this->config->baseDir."/templates/";
        $this->smarty->config_dir   = $this->config->baseDir."/smarty/configs/";
        $this->smarty->plugins_dir[]= $this->config->baseDir."/smarty/plugins/";

        if (file_exists($this->config->baseDir."/smarty/templates_c/".$this->locale.'/')) {
            $this->smarty->compile_dir = $this->config->baseDir."/smarty/templates_c/".$this->locale.'/';
            $this->smarty->cache_dir   = $this->config->baseDir."/smarty/cache/".$this->locale.'/';
        } else {
            $this->smarty->compile_dir = $this->config->baseDir."/smarty/templates_c/";
            $this->smarty->cache_dir   = $this->config->baseDir."/smarty/cache/";
        }
    }

    /**
     * Check connection elements of the application
     * @return void
     */
    private function checkApplication($wRedirection = true) {
        $org        = LDAPOrganization_Manager::getOrganization($this->config->o);
        
        // getting Global config of the organization
        $this->orgGlobalConfig  = $org->getOptionsValues('global');
        
        // checking password
        $isPassword = $org->getOptionsValues('websuite', LDAPAuthorization_Manager::TARGET_PASSWORD);
        if ($isPassword) {
            // checking nb failed auths
            if (isset($this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_NB_AUTH_FAIL]) AND isset($this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_NB_AUTH_FAIL])) {
                if ($this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_NB_AUTH_FAIL] > $this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_NB_AUTH_FAIL]) {
                    // @todo process that ! 
                    echo _('Too much authentication failures.');
                }
            }
            
            // checking password lifetime
            if (!empty($this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_PWD_LIFETIME]) AND !empty($this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_PWD_CHANGED_DATE])) {
                $pwdDateMax = strtotime($this->userGlobalConfig[LDAPAuthorization_Manager::TARGET_PWD_CHANGED_DATE].' +'.$this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_PWD_LIFETIME].' days');
                if ($pwdDateMax < time()) {
                    // @todo process that ! 
                    echo _('Your password has expired.');
                }
            }
            
            if (empty($_SESSION['login'])) {
                $_SESSION['login']  = false;
                if (empty($_SESSION['logining']) AND $wRedirection) {
                    header('Location: '.$this->config->basePath.'user/login.php');
                    Utils::abort();
                }
            } else {
                $maxInactivity  = isset($this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_MAX_INACTIVITY])?$this->orgGlobalConfig[LDAPAuthorization_Manager::TARGET_MAX_INACTIVITY]:30;
                if (isset($_SESSION['LAST_ACTIVITY']) && $maxInactivity && (time() - $_SESSION['LAST_ACTIVITY'] > $maxInactivity*60)) {
                    // no activity before max activity limit
                    Utils::userLogout($this->config->o, $this->config->u, Session_Manager::LOGOUT_TYPE_EXPIRED_SESSION);
                }
                $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
            }
        } else if (empty($_SESSION['login'])) {
            Utils::userLogin($this->config->o, $this->config->u, Session_Manager::LOGIN_TYPE_NO_PASSWORD);
        }

        if (!empty($_SESSION['login'])) {
            if ($wRedirection) {
                $sessionState   = Session_Manager::getSessionState();

                if ($sessionState) {
                    $_SESSION['login']          = false;
//                    session_destroy();   // destroy session data in storage
//                    session_unset();     // unset $_SESSION variable for the runtime

                    $sRedirection               = $this->config->basePath.'user/sessionLost.php';
                    if ($isPassword) {
                        switch ($sessionState) {
                            case Session_Manager::LOGOUT_TYPE_EXPIRED_SESSION: {
                                $sRedirection   = $this->config->basePath.'user/login.php?expiredSession=1';
                                break;
                            }
                            case Session_Manager::LOGOUT_TYPE_LOGGED_ANOTHER_LOCATION: {
                                $sRedirection   = $this->config->basePath.'user/login.php?loggedAnotherLocation=1';
                                break;
                            }
                            case Session_Manager::LOGOUT_TYPE_USER_LOGOUT:
                            default: {
                                $sRedirection   = $this->config->basePath.'user/login.php';
                                break;
                            }
                        }
                    }

                    header('Location: '.$sRedirection);
                    Utils::abort();
                }
            }
        }

        $checker = new ApplicationChecker();
        try{
            $checker->basicChecking();
        } catch (Exception $e) {
            echo('Error:'.$e->getMessage());
            Utils::abort();
        }
    }

    /**
     * Init default smarty values (used in all pages)
     * @return void
     */
    private function defaultSmartyAssign() {
        $this->smarty->assign('currentPage', $this->config->currentPage);
        $this->smarty->assign('locale', $this->locale);

        // checking WS licences
        $org        = LDAPOrganization_Manager::getOrganization($this->config->o);
        $nbLicences = $org->getOptionsValues('websuite', LDAPAuthorization_Manager::TARGET_NB_LICENCES);
        $aUsers     = LDAPUser_Manager::getUsers(null, null, $org->o);
        if (count($aUsers) > $nbLicences) {
            $this->smarty->assign('importantMessage', _('Maximum number of licenses exceeded !'));
        }
    }
    
//    public $errors  = '';
//    
//    public function errorHandler($errno, $errstr, $errfile, $errline)
//    {
//        if (!(error_reporting() & $errno)) {
//            // This error code is not included in error_reporting
//            return;
//        }
//
//        switch ($errno) {
//            case E_ERROR:
//                $this->errors .= "<b>[EROOR]</b> [$errno] $errstr<br />\n";
//                $this->errors .= "  Fatal error on line $errline in file $errfile";
//                $this->errors .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
//                $this->errors .= "Aborting...<br />\n";
//                exit(1);
//                break;
//
//            case E_WARNING:
//                $this->errors .= "<b>[WARNING]</b> [$errno] $errstr in file $errfile on line $errline <br />\n";
//                break;
//
//            case E_NOTICE:
//                $this->errors .= "<b>[NOTICE]</b> [$errno] $errstr in file $errfile on line $errline <br />\n";
//                break;
//
//            default:
//                $this->errors .= "[Unknown error type]: [$errno] $errstr<br />\n";
//                break;
//        }
//
//        /* Don't execute PHP internal error handler */
//        return true;
//    }
}
?>
