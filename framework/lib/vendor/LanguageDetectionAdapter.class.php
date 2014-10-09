<?php
require_once(getenv('app_root').'/adserver/lib/vendor/LanguageDetection/php_language_detection.php');

class LanguageDetectionAdapter
{
    public static function getLanguageInfo($httpAccessLanguage = '') {
        $languageInfo = get_languages('data', '', $httpAccessLanguage);
        
        return $languageInfo;
    }
}

?>