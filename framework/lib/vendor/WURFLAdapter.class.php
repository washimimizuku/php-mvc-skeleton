<?php
require_once(getenv('app_root').'/adserver/lib/core/ApplicationConfig.class.php');
require_once(getenv('app_root').'/adserver/lib/vendor/WURFL/WURFL/Application.php');

class WURFLAdapter
{
    public $wurflManager;
    
    /** @var WURFLAdapter $instance*/
    private static $instance; //static instance of the class

    function __construct($matchMode = 'performance'){
        $wurflDir   = getenv('app_root').'/adserver/lib/vendor/WURFL/WURFL/';
        $cacheDir   = getenv('app_root').'/adserver/cache/WURFL';
        $configDir  = getenv('app_root').'/adserver/configs/WURFL';
        
        $persistenceDir = $cacheDir.'/persistence';
        $cacheDir       = $cacheDir.'/cache';
        
        // Create WURFL Configuration
        $wurflConfig = new WURFL_Configuration_InMemoryConfig();
        
        // Set location of the WURFL File
        $wurflConfig->wurflFile($configDir.'/wurfl.zip');
        
        // Set the match mode for the API ('performance' or 'accuracy')
        $wurflConfig->matchMode($matchMode);
        
        // Automatically reload the WURFL data if it changes
        $wurflConfig->allowReload(true);
        
        /*
        // Optionally specify which capabilities should be loaded
        //  This is disabled by default as it would cause the demo/index.php
        //  page to fail due to missing capabilities
        */
        if ($matchMode == 'performance') {
            $wurflConfig->capabilityFilter(array(
                
                // Obligatory Capabilities
                "device_os",
                "device_os_version",
                "is_tablet",
                "is_wireless_device",
                "mobile_browser",
                "mobile_browser_version",
                "pointing_method",
                "preferred_markup",
                "resolution_height",
                "resolution_width",
                "ux_full_desktop",
                "xhtml_support_level",
                
                // Virtual Capabilities
                /*
                'is_app',
                'is_smartphone',
                'advertised_browser',
                'advertised_browser_version',
                */
                
                // Extra Needed Capabilities
                'brand_name',
                'model_name',
                'marketing_name',
                'wifi',
                'xhtml_send_sms_string',
                'xhtml_send_mms_string',
                'wml_make_phone_call_string',
                'can_assign_phone_number',
                'is_smarttv',
                'ajax_support_javascript',
            ));
        }
        
        // Setup WURFL Persistence
        $wurflConfig->persistence('file', array('dir' => $persistenceDir));
        
        // Setup Caching
        $wurflConfig->cache('file', array('dir' => $cacheDir, 'expiration' => 36000));
        
        // Create a WURFL Manager Factory from the WURFL Configuration
        $wurflManagerFactory = new WURFL_WURFLManagerFactory($wurflConfig);
        
        // Create a WURFL Manager
        /* @var $wurflManager WURFL_WURFLManager */
        $this->wurflManager = $wurflManagerFactory->create();
    }

    /**
      * Get the singleton instance
      * (construct if needed)
      * @access public
      * @return ApplicationConfig $instance
      */
    static public function getInstance($matchMode = 'performance') {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self($matchMode);
        }
        return self::$instance;
    }

    /**
     * Get the device
     * @access public
     * @param string $userAgent
     * @return WURFL_CustomDevice
     **/
    public function getRequestingDevice($userAgent) {
        return ($this->wurflManager->getDeviceForUserAgent($userAgent));
    }
    
    /**
     * Get the device information
     * @access public
     * @param string $userAgent
     * @return Array
     **/
    public function getDeviceInfo($userAgent) {
        $requestingDevice = $this->wurflManager->getDeviceForUserAgent($userAgent);
    
        $deviceInfo = array('is_wireless_device'            => $requestingDevice->getCapability('is_wireless_device'),          // Tells you if a device is wireless or not. Specifically a mobile phone or a PDA are considered wireless devices, a desktop PC or a laptop are not
                            'is_tablet'                     => $requestingDevice->getCapability('is_tablet'),                   // Tells you if a device is a tablet computer (iPad and similar, regardless of OS)
                            'brand_name'                    => $requestingDevice->getCapability('brand_name'),                  // Brand (ex: Nokia)
                            'model_name'                    => $requestingDevice->getCapability('model_name'),                  // Model (ex: N95)
                            'marketing_name'                => $requestingDevice->getCapability('marketing_name'),              // In addition to Brand and Model, some devices have a marketing name (for ex: BlackBerry 8100 Pearl, Nokia 8800 Scirocco, Samsung M800 Instinct).
                            'device_os'                     => $requestingDevice->getCapability('device_os'),                   // Information about hosting OS
                            'device_os_version'             => $requestingDevice->getCapability('device_os_version'),           // Which version of the hosting OS
                            'wifi'                          => $requestingDevice->getCapability('wifi'),                        // Device can access WiFi connections
                            'xhtml_send_sms_string'         => $requestingDevice->getCapability('xhtml_send_sms_string'),       // Indicates whether device supports the href="sms:+num" syntax to trigger the SMS client from a link. Syntax may be "smsto:" on some devices or not be supported at all.
                            'xhtml_send_mms_string'         => $requestingDevice->getCapability('xhtml_send_mms_string'),       // Indicates whether device supports the href="mms:+num" syntax to trigger the MMS client from a link. Syntax may be "mmsto:" on some devices or not be supported at all.
                            'wml_make_phone_call_string'    => $requestingDevice->getCapability('wml_make_phone_call_string'),  // Prefix to initiate a voice call ("none","tel:", "wtai://wp/mc;")
                            'mobile_browser'                => $requestingDevice->getCapability('mobile_browser'),              // Information about the device browser (Openwave, Nokia, Opera, Access, Teleca,...)
                            'mobile_browser_version'        => $requestingDevice->getCapability('mobile_browser_version'),      // Which version of the browser
                            'can_assign_phone_number'       => $requestingDevice->getCapability('can_assign_phone_number'),     // Device is a mobile phone and may have a phone number associated to it.
                            'is_smarttv'                    => $requestingDevice->getCapability('is_smarttv'),                  // Device is a SmartTV (GoogleTV, Boxee Box, AppleTV, etc.).
                            'ajax_support_javascript'       => $requestingDevice->getCapability('ajax_support_javascript'),     // A device can be said Javascript enabled only if the following features are reliably supported: alert, confirm, access form elements (dynamically set/modify values), setTimeout, setInterval, document.location. If a device fails one of these tests, mark as false (i.e. crippled javascript is not enough to be marked as javascript-enabled)
                            
                            // Virtual Capabilities
                            'is_app'                        => $requestingDevice->getVirtualCapability('is_app'),
                            'is_smartphone'                 => $requestingDevice->getVirtualCapability('is_smartphone'),
                            'advertised_browser'            => $requestingDevice->getVirtualCapability('advertised_browser'),
                            'advertised_browser_version'    => $requestingDevice->getVirtualCapability('advertised_browser_version'),
                            
                            // Obligatory Capabilities of WURFL, we may remove from tracking if not interesting
                            'pointing_method'               => $requestingDevice->getCapability('pointing_method'),
                            'preferred_markup'              => $requestingDevice->getCapability('preferred_markup'),
                            'resolution_height'             => $requestingDevice->getCapability('resolution_height'),
                            'resolution_width'              => $requestingDevice->getCapability('resolution_width'),
                            'ux_full_desktop'               => $requestingDevice->getCapability('ux_full_desktop'),
                            'xhtml_support_level'           => $requestingDevice->getCapability('xhtml_support_level'),
                            );
        
        return $deviceInfo;
    }
    
    /*
     * no clone
     */
    private function __clone() {}

}

?>