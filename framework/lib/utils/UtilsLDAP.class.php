<?php

/**
 * A class to group LDAP utils methods together
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class UtilsLDAP {
    
    protected static $aLDAPtoPHPClasses = array(
        'dsOrganization' => 'LDAPOrganization',
        'dsUser' => 'LDAPUser',
        'dsRole' => 'LDAPRole',
        'dsAuthorizationList' => 'LDAPAuthorizationsList',
        'dsBooleanAuthorization' => 'LDAPAuthorization_BooleanAuthorization',
        'dsStringAuthorization' => 'LDAPAuthorization_StringAuthorization',
        'dsIntegerAuthorization' => 'LDAPAuthorization_IntegerAuthorization',
        'dsGroup' => 'LDAPGroup',
        'dsGraph' => 'LDAPGraph'
    );
    
    /*************************
     *       LDAP UTILS
     *************************/
    
    public static function constructFromLDAPObjectClass($ressources, $objectClass = null)
    {
        $element = null;
        
        if (!$objectClass) {
            if (isset($ressources['objectclass'])) {
                $objectClass    = is_array($ressources['objectclass'])?$ressources['objectclass'][0]:$ressources['objectclass'];
            } else {
                throw new LDAP_Exception('@NO_OBJECT_CLASS||'.'Can\'t construct because there is no objectclass', LDAP_Exception::NO_OBJECT_CLASS);
            }
        }
        
        if (isset(self::$aLDAPtoPHPClasses[$objectClass])) {
            $className      = self::$aLDAPtoPHPClasses[$objectClass];
            if ($objectClass === 'dsAuthorizationList') {
                $className  = self::getAuthorizationListClass($ressources);
            } else if ($objectClass === 'dsGroup') {
                $className  = self::getGroupClass($ressources);
            }
            
            $element = new $className($ressources);
        } else {
            throw new WS_Exception('Can\'t construct from object class '.$objectClass, WS_Exception::CLASS_NOT_EXISTS);
        }
        
        return $element;
    }
    
    private static function getAuthorizationListClass($ressources)
    {
        $authType   = '';
        $isChild    = false;
        
        if (!isset($ressources['dstype'][0])) {
            $authType   = LDAPAuthorizationsList_Manager::getAuthorizationListPrefixByName($ressources['dsaln'][0]);

            if (!$authType AND isset($ressources['dn'])) {
                // check parent 
                $parsedDn                   = parse_dn($ressources['dn']);
                $parentRDN                  = $parsedDn[1];
                $parentAuthType             = '';
                if ($parentRDN['attrib'] === LDAPAuthorizationsList::getRDN()) {
                    $parentDnValue          = $parentRDN['value'];

                    $parentAuthType         = LDAPAuthorizationsList_Manager::getAuthorizationListPrefixByName($parentDnValue);
                }

                $authType   = $parentAuthType;
                $isChild    = true;
            }
            $className  = LDAPAuthorizationsList_Manager::getAuthorizationListClassByType($authType, $isChild);
        } else {
            $className  = LDAPAuthorizationsList_Manager::getAuthorizationListClassByType($ressources['dstype'][0]);
        }
        
        return $className;
    }
    
    private static function getGroupClass($ressources)
    {
        $type       = '';
        if (isset($ressources['dstype'])) {
            $type   = $ressources['dstype'];
            if (is_array($ressources['dstype'])) {
                $type = $ressources['dstype'][0];
            }
        }
        
        $className  = LDAPGroup_Manager::getGroupClassByType($type);
        
        return $className;
    }
    
    public static function checkDNExists($dn)
    {
        $isExists   = false;
        
        // connect to ldap
        $config                 = ApplicationConfig::getInstance();
        $configLDAP             = new LDAP_OpenLDAP($config->ldap_uri);
        
        $res       = @ldap_read($configLDAP->linkid, $dn, 'objectClass=*');
        $res       = @ldap_get_entries_short($configLDAP->linkid, $res, true);
        
        if (is_array($res) AND count($res)) {
            $isExists   = true;
        }
        
        return $isExists;
    }
    
    /**
     * Copy an LDAP element (even non-leaf element)
     * @param string|object     $dn                 DN of the element
     * @param string            $newRDNValue        Value of the new RDN to avoid duplicate errors while not moving
     * @param string            $newParentDN        DN of the parent element (uses to move the element)
     * @param boolean           $isDeleteOriginal   Define if the original element will be deleted
     */
    public static function copyElement($dn, $newRDNValue, $newParentDN = '', $isDeleteOriginal = false, $wSubElements = true)
    {
        if (is_object($dn)) {
            $dn     = $dn->getDN();
        }
        
        // connect to ldap
        $config                 = ApplicationConfig::getInstance();
        $configLDAP             = new LDAP_OpenLDAP($config->ldap_uri);
        
        $res                    = @ldap_read($configLDAP->linkid, $dn, 'objectClass=*');
        $res                    = @ldap_get_entries_short($configLDAP->linkid, $res, true);
        
        $ldapElement            = null;
        if (is_array($res) AND count($res)) {
            $ldapElement            = self::constructFromLDAPObjectClass($res[0]);
            $originalLdapElement    = clone $ldapElement;
            
            if ($newParentDN) {
                $ldapElement->setBaseDN($newParentDN);
            }
            $ldapElement->{$ldapElement->getRDN()} = $newRDNValue;
            $ldapElement->add();
            
            if ($wSubElements) {
                $aChildEntries          = $originalLdapElement->getChildEntries();
                foreach ($aChildEntries as $childEntry) {
                    self::copyElement($childEntry->getDN(), $childEntry->{$childEntry->getRDN()}, $ldapElement->getDN(), false);
                }
            }
            
            if ($isDeleteOriginal) {
                $originalLdapElement->delete(true, false);
            }
        }
        
        return $ldapElement;
    }
}

?>
