<?php

define('ROOT_PATH', getenv('app_root'));

require_once(getenv('app_root').'/configs/structure.config.php');
require_once(getenv('app_root').'/lib/ClassAutoloader.class.php');
require_once(getenv('app_root').'/lib/core/LDAP/LDAP.class.php');

class ApplicationConfig {
    public $dbhost;
    public $dbuser;
    public $dbpassword;
    public $ldap_uri;
    public $ca_dir;
    public $ca_hash_dir;
    public static $pdf_dir;
    public static $archives_dir;

    public $modules;
    public $filterModules;
    public $filterOperations;
    public $filterTypes;
    public $yesno;
    public $blockingType;
    public $passwordType;
    public $signatureList;
    public $rejectList;
    public $operationReferenceField;
    public $emailEvents;
    public $emailSignatures;
    public $permissionTypes;
    public $permissionPrefix;
    public $filtersGroupsTypes;
    public $graphsGroupsTypes;
    public $statusList;
    public $advancedSearchTranslatedIDs;

    public $baseDir;
    public $basePath;
    public $currentPage;
    public $o = '';
    public $u = '';

    //Graph configuration
    public $wireItPath;
    public $buildFilterScript;
    public $buildGraphScript;
    public $graphImagesPath;
    public $graphIconsPath;
    public static $filters_dir;
    public static $graphs_dir;

    //public $authList = '';

    public $isDev = false;      // DO NOT SET THAT TO TRUUUUUUUUUUUUEEEEE  (rage-face) !!! JH

    /** @var ApplicationConfig $instance*/
    private static $instance; //static instance of the class

    function __construct(){
        global $config;
        global $structure;
        
        date_default_timezone_set($config['timezone']);
        $this->baseDir  = getenv('app_root');
        $this->basePath = '/';
        
        $this->currentPage  = end(preg_split('#[/\\\\]#', $_SERVER['PHP_SELF'], 0, PREG_SPLIT_NO_EMPTY));
        
        if (isset($_SERVER['SCRIPT_FILENAME']) &&
          isset($_SERVER['SCRIPT_NAME'])) {
            $fn = preg_split('#[/\\\\]#', $_SERVER['SCRIPT_FILENAME'], -1, PREG_SPLIT_NO_EMPTY);
           $n = preg_split('#[/\\\\]#', $_SERVER['SCRIPT_NAME'], -1, PREG_SPLIT_NO_EMPTY);
            array_unshift($fn, 'htdocs');
            array_unshift($n, 'htdocs');
            do {
                $cfn = array_pop($fn);
                $cn = array_pop($n);
            } while ($cfn == $cn && $cfn != 'htdocs');
            array_push($n, $cn);
            array_shift($n);
            $this->basePath = '/';
            if ($n) {
                $this->basePath .= implode('/', $n) . '/';
            }
        } elseif (isset($_SERVER['PHP_SELF']) && preg_match('#^(.*/htdocs/).*?$#', $_SERVER['PHP_SELF'], $matches)) {
             $this->basePath = $matches[1];
             
        }
        
        // Uncomment if Dev Logs are wanted
        //$this->isDev = true;           // PLEASE DON'T COMMIT IT TO TRUE !!!

        // Get Certificate Organization
        if(isset($_SERVER['SSL_CLIENT_S_DN_O'])){
            $this->o = $_SERVER['SSL_CLIENT_S_DN_O'];
        }

        // Get Certificate User
        if(isset($_SERVER['SSL_CLIENT_S_DN_CN'])){
            $this->u = $_SERVER['SSL_CLIENT_S_DN_CN'];
        }

        $this->determineIpAddresses();

        $this->dbhost            = $config['dbhost'];
        $this->dbuser            = $config['dbuser'];
        $this->dbpassword        = $config['dbpassword'];
        $this->ldap_uri          = $config['ldap_uri'];
        $this->ca_dir            = $config['ca_dir'];
        $this->ca_hash_dir       = $config['ca_hash_dir'];
        $this->pdf_dir           = $config['pdf_dir'];
        $this->archives_dir      = $config['archives_dir'];
        $this->wireItPath        = $config['wireitpath'];
        $this->buildFilterScript = $config['buildfilterscript'];
        $this->buildGraphScript  = $config['buildgraphscript'];
        $this->graphIconsPath    = $config['graphiconspath'];
        $this->graphImagesPath   = $config['graphimagespath'];
        $this->filters_dir       = $config['filters_dir'];
        $this->graphs_dir        = $config['graphs_dir'];


        $this->modules          = $structure['modules'];
        //$this->filterModules    = $structure['filterModules'];
        $this->filterOperations = $structure['filterOperations'];
        $this->filterTypes      = $structure['filterTypes'];
        $this->yesno            = $structure['yesno'];
        $this->blockingType     = $structure['blockingType'];
        $this->passwordType     = $structure['passwordType'];
        $this->signatureList    = $structure['signatureList'];
        $this->rejectList       = $structure['rejectList'];

        $this->operationReferenceField     = $structure['operationReferenceField'];
        $this->emailEvents                 = $structure['emailEvents'];
        $this->emailSignatures             = $structure['emailSignatures'];
        $this->permissionTypes             = $structure['permissionTypes'];
        $this->permissionPrefix            = $structure['permissionPrefix'];
        $this->filtersGroupsTypes          = $structure['filtersGroupsTypes'];
        $this->graphsGroupsTypes           = $structure['graphsGroupsTypes'];
        $this->statusList                  = $structure['statusList'];
        $this->advancedSearchTranslatedIDs = $structure['advancedSearchTranslatedIDs'];

        unset($GLOBALS['config']);
        unset($GLOBALS['structure']);
    }

    /**
      * Get the singleton instance
      * (construct if needed)
      * @access public
      * @return ApplicationConfig   $instance
      */
    static public function getInstance() {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieves the best guess of the client's actual IP address.
     * Takes into account numerous HTTP proxy headers due to variations
     * in how different ISPs handle IP addresses in headers between hops.
     */
    public function determineIpAddresses() {
        $ipReal     = '';
        $ipProxy    = '';

        // check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            $ipReal = $_SERVER['HTTP_CLIENT_IP'];
        }

        // check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check if multiple ips exist in var
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if ($this->validate_ip($ip)) {
                    $ipReal = $ip;
                    break;
                }
            }
        } else {
            if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED'])) {
                $ipReal = $_SERVER['HTTP_X_FORWARDED'];
            } else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
                $ipReal = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            } else if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
                $ipReal = $_SERVER['HTTP_FORWARDED_FOR'];
            } else if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED'])) {
                $ipReal = $_SERVER['HTTP_FORWARDED'];
            }
            if(isset($_SERVER['REMOTE_ADDR'])){
                if ($ipReal) {
                    $ipProxy    = $_SERVER['REMOTE_ADDR'];
                } else {
                    $ipReal     = $_SERVER['REMOTE_ADDR'];
                }
            }
        }

        $this->ipReal   = $ipReal;
        $this->ipProxy  = $ipProxy;
    }

    /**
    * Ensures an ip address is both a valid IP and does not fall within
    * a private network range.
    *
    * @access public
    * @param string $ip
    */
    public function validate_ip($ip) {
        $isValid    = true;
        if (filter_var($ip, FILTER_VALIDATE_IP,
                            FILTER_FLAG_IPV4 |
                            FILTER_FLAG_IPV6 |
                            FILTER_FLAG_NO_PRIV_RANGE |
                            FILTER_FLAG_NO_RES_RANGE) === false) {
            $isValid = false;
        }
        return $isValid;
    }

    /*
     * no clone
     */
    private function __clone() {}
    
    public function getRedirection($urlReference = 'dashboard')
    {
        $moduleRedirect = '';
        
        $user           = LDAPUser_Manager::getUser($this->u, $this->o);
        $moduleName     = implode('_', Utils::getMultiValues(explode('/', $urlReference)));
        if (LDAPAuthorizationsList_Manager::getUserAccessAuthorization($user, $moduleName, 'access')) {
            
            // getting subModules
            $module = $this->getModuleConfig($moduleName);
            if ($module['type'] != 'inactive' AND isset($module['sections'])) {
                foreach ($module['sections'] as $section) {
                    $sectionName            = $moduleName.'_'.$section['pathName'];
                    $section                = $this->getModuleConfig($sectionName);
                    if ($section['type'] != 'inactive' AND LDAPAuthorizationsList_Manager::getUserAccessAuthorization($user, $sectionName, 'access')) {
                        $moduleRedirect     = $section['pathName'];
                        break;
                    }
                }
            }
        } else {
            $moduleRedirect     = $this->getNextModule($moduleName);
            if ($moduleRedirect) {
                $moduleRedirect     = $this->basePath.'/'.$moduleRedirect;
            }
        }
        
        
        return $moduleRedirect;
    }
    
    public function getModuleConfig($module)
    {
        $returnModule = null;
        
        $aMod       = explode('_', $module);
        $module     = $this->modules; 
        foreach ($aMod as $mod) {
            $returnModule   = $module[$mod];
            if (!isset($returnModule['sections'])) {
                break;
            } else {
                $module         = $returnModule['sections'];
            }
        }
        return $returnModule;
    }
    
    public function getModulePath($module)
    {
        $path       = '';
        
        $aMod       = explode('_', $module);
        $module     = $this->modules; 
        foreach ($aMod as $mod) {
            $returnModule   = $module[$mod];
            $path           .= $returnModule['pathName'].DIRECTORY_SEPARATOR;
            
            if (!isset($returnModule['sections'])) {
                break;
            } else {
                $module         = $returnModule['sections'];
            }
        }
        
        return $path;
    }
    
    public function getNextModule($moduleName, $next = true)
    {
        $returnModule = '';
        $user           = LDAPUser_Manager::getUser($this->u, $this->o);
        
        $modules    = $this->modules; 
        if (!$next) $modules    = array_reverse($modules, true); 
        
        $moduleOk = false;
        foreach ($modules as $module) {
            if ($moduleOk AND $module['type'] != 'inactive' AND LDAPAuthorizationsList_Manager::getUserAccessAuthorization($user, $module['pathName'], 'access')) {
                $returnModule   = $module['pathName'];
                break;
            }
            
            if ($module['pathName'] == $moduleName) {
                $moduleOk = true;
            }
        }
        
        if (!$returnModule) {
            $returnModule = $this->getNextModule($moduleName, false);
        }
        
        return $returnModule;
    }
}

?>