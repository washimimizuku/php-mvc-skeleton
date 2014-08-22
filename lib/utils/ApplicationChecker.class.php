<?php
require_once(getenv('app_root').'/lib/core/ApplicationConfig.class.php');

class ApplicationCheckerFailure extends Exception {}

class ApplicationChecker {
    public function __construct() {
    }

    /*public function getAccessAuthorizations1() {
        $config = ApplicationConfig::getInstance();
        $authorizations = new Authorizations();

        $authorized = array();
        
        $configLDAP = @new ldap_openldap($config->ldap_uri);
        $orgdn = ldap_build_dn('o', $config->o, $configLDAP->basedn);

        $users = new Users();
        $allAuthLists = $users->getAllAuthorizationLists($config->o, $config->u);
        
        
        foreach ($config->modules as $module) {
            $moduleName = $module['pathName'];
            $authorized[$moduleName]['access'] = $authorizations->checkAuthorization1($config->o, $config->u, $moduleName,'access', $orgdn, $allAuthLists);
            foreach ($module['sections'] as $section) {
                $sectionName = $moduleName.'_'.$section['pathName'];
                $authorized[$sectionName]['access'] = $authorizations->checkAuthorization1($config->o, $config->u, $sectionName,'access', $orgdn, $allAuthLists);
                $authorized[$sectionName]['create'] = $authorizations->checkAuthorization1($config->o, $config->u, $sectionName,'edit', $orgdn, $allAuthLists);
                $authorized[$sectionName]['edit'] = $authorizations->checkAuthorization1($config->o, $config->u, $sectionName,'edit', $orgdn, $allAuthLists);
                $authorized[$sectionName]['erase'] = $authorizations->checkAuthorization1($config->o, $config->u, $sectionName,'erase', $orgdn, $allAuthLists);
                $authorized[$sectionName]['approve'] = $authorizations->checkAuthorization1($config->o, $config->u, $sectionName,'approve', $orgdn, $allAuthLists);
            }
        }
        
        return($authorized);
    }*/
    
//    public function getAccessAuthorizations() {
//        $config = ApplicationConfig::getInstance();
//        $authorizations = new Authorizations();
//
//        $authorized = array();
//        
//        foreach ($config->modules as $module) {
//            $moduleName = $module['pathName'];
//            $authorized[$moduleName]['access'] = $authorizations->checkAuthorization($config->o, $config->u, $moduleName,'access');
//            foreach ($module['sections'] as $section) {
//                $sectionName = $moduleName.'_'.$section['pathName'];
//                $authorized[$sectionName]['access'] = $authorizations->checkAuthorization($config->o, $config->u, $sectionName,'access');
//                $authorized[$sectionName]['create'] = $authorizations->checkAuthorization($config->o, $config->u, $sectionName,'edit');
//                $authorized[$sectionName]['edit'] = $authorizations->checkAuthorization($config->o, $config->u, $sectionName,'edit');
//                $authorized[$sectionName]['erase'] = $authorizations->checkAuthorization($config->o, $config->u, $sectionName,'erase');
//                $authorized[$sectionName]['approve'] = $authorizations->checkAuthorization($config->o, $config->u, $sectionName,'approve');
//            }
//        }
//        
//        return($authorized);
//    }
    

    public function basicChecking () {
        $config = ApplicationConfig::getInstance();

        // Check execution context.
        if (!isset($_SERVER['SERVER_NAME'])) {
            header('Content-Type: text/plain');
            echo _('This program cannot be called outside a web server') . "\n";
            exit;
        }

        // Check PHP version. Define missing symbols in old versions.
        if (!defined('PHP_VERSION') || PHP_VERSION < 5) {
            throw new ApplicationCheckerFailure(
                _('PHP version 5.0.0 or higher is required to run this program'));
        }
        if (!defined('PHP_VERSION_ID')) {
            $v = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', 10000 * $v[0] + 100 * $v[1] + $v[2]);
        }
        if (PHP_VERSION_ID < 50207) {
            $v = explode('.', PHP_VERSION);
            define('PHP_MAJOR_VERSION', $v[0]);
            define('PHP_MINOR_VERSION', $v[1]);
            define('PHP_RELEASE_VERSION', $v[2]);
        }

        //  Check obsolescent magic_quotes settings.
        if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime()) {
            throw new ApplicationCheckerFailure(_('`magic_quotes_runtime\' should be turned off in this application.').
                _('Please report this error message to your system administrator'));
        }
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
            throw new ApplicationCheckerFailure(_('`magic_quotes_gpc\' should be turned off in this application.').
                _('Please report this error message to your system administrator'));
        }
        if (ini_get('magic_quotes_sybase')) {
            throw new ApplicationCheckerFailure(_('`magic_quotes_sybase\' should be turned off in this application.').
                _('Please report this error message to your system administrator'));
        }

        //  Make sure we got a client SSL certificate.
        if (!isset($_SERVER['HTTPS'])) {
            throw new ApplicationCheckerFailure(
                _('this application must be invoked using the https protocol'));
        }

        if (!isset($_SERVER['SSL_CLIENT_S_DN'])) {
            throw new ApplicationCheckerFailure(_('a SSL client certificate should have been requested by your browser.').'<br/>'.
                _('The cause of this problem is probably a server misconfiguration').'<br/>'.
                _('Please report this error message to your system administrator'));
        }

        //  Check validity of client certificate.
        if (!isset($_SERVER['SSL_CLIENT_S_DN_CN']) ||
            !isset($_SERVER['SSL_CLIENT_S_DN_O']) ||
            !isset($_SERVER['SSL_CLIENT_I_DN_O']) ||
            $_SERVER['SSL_CLIENT_S_DN_O'] != $_SERVER['SSL_CLIENT_I_DN_O']) {
            throw new ApplicationCheckerFailure(_('the SSL client certificate has not been signed by your organization\'s authority.').
                _('This mismatch should have been detected earlier: this is the symptom of a probable server misconfiguration').
                _('Please report this error message to your system administrator and request a proper certificate from your organization\'s authority.'));
        }
        
        //  Connect to LDAP server.
        $configLDAP = @new LDAP_OpenLDAP($config->ldap_uri);

        if (!$configLDAP->linkid)
            throw new ApplicationCheckerFailure(_('cannot contact the LDAP server.').
                _('Please report this error message to your system administrator'));
        
        //  Check certificate revocation and user status.
        $user   = LDAPUser_Manager::getUser($config->u, $config->o);
        if ($user->getEnabled()) {
            $sn = $_SERVER['SSL_CLIENT_M_SERIAL'];
            $aRevokedSerials    = $user->getRevokedSerialNumber();
            foreach ($aRevokedSerials as $revokedSerial) {
                if (ltrim($sn,'0') == ltrim($revokedSerial,'0')) {
                    throw new ApplicationCheckerFailure(_('your certificate has been blocked.'));
                }
            }
        } else {
            throw new ApplicationCheckerFailure(_('your account has been blocked.'));
        }
        
        //  Check for existence of database in organization.
        $configLDAP_O_BASE = ldap_build_dn('o', $config->o, $configLDAP->basedn);
        //$configLDAP_U_BASE = ldap_build_dn('cn', $config->u, $configLDAP_O_BASE);
        $ldapset = @ldap_read($configLDAP->linkid, $configLDAP_O_BASE,
            'objectClass=dsOrganization', array('dsDatabase'));

        if (!$ldapset || ldap_count_entries($configLDAP->linkid, $ldapset) != 1)
            throw new ApplicationCheckerFailure(sprintf(_('your organization `%s\' is not authorized to access this application.'), $config->o).
                _('Please report this error message to your system administrator'));

        $ua = Utils::getBrowser();
        
        $browserName = $ua['name'];
        $browserVersion = (int)$ua['version_short'];
        $compatibilityView = $ua['compatibilityView'];

        if ( ( ($browserName == 'Internet Explorer') && ($browserVersion < 8) && !$compatibilityView) ||
            (($browserName == 'Mozilla Firefox') && ($browserVersion < 3)) ||
            (($browserName == 'Safari') && ($browserVersion < 4)) ||
            (($browserName == 'Opera') && ($browserVersion < 8)) ||
            (($browserName == 'Google Chrome') && ($browserVersion < 7))) {
            header('Location: '.$config->basePath.'browsers.php');
            Utils::abort();
        }
    }
}
?>
