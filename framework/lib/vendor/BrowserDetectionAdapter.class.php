<?php
require_once(getenv('app_root').'/adserver/lib/vendor/BrowserDetection/browser_detection_php_ar.php');

class BrowserDetectionAdapter
{
    public static function getBrowserInfo($type = 'full_assoc', $test_excludes='', $external_ua_string='') {
        $browserInfo = browser_detection($type, $test_excludes, $external_ua_string);
        
        return $browserInfo;
    }
}

?>