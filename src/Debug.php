<?php namespace webcitron\Subframe;

use webcitron\Subframe\Application;

class Debug
{
    
    private static $boolIsEnabled = null;
    private static $boolManuallyDisabled = false;
    private static $arrMessages = array();
    public static $boolAlreadyPrinted = false;
    
    public static function isEnabled () {
        if (self::$boolIsEnabled === null) {
            if (!empty(filter_input(\INPUT_COOKIE, 'imged-developer')) || (Application::currentEnvironment() !== Application::ENVIRONMENT_PRODUCTION && self::$boolManuallyDisabled  !== true)) {
                self::$boolIsEnabled = true;
            } else {
                self::$boolIsEnabled = false;
            }
        }
        return self::$boolIsEnabled;
    }
    
    public static function disable () {
        self::$boolManuallyDisabled = true;
    }
    
    public static function top () {
//        if (Application::currentEnvironment() === Application::ENVIRONMENT_DEV) {
//            $strGitHead = file_get_contents(APP_DIR.'/../.git/HEAD');
//            $strContainer = "<pre style='display: inline; font-size: 1em;border:1px solid #888; margin:20px auto; padding: 20px; background-color:#f8f8f8;z-index:99999; position:fixed; top:0; left:0; opacity:.5'>%s</pre>";
//            $strContent = 'GIT HEAD: <strong>'.$strGitHead.'</strong>';
//            echo sprintf($strContainer, $strContent);
//        }
    }
    
    public static function log ($strContent, $strPrefix = '') {
        if (!empty($strPrefix)) {
            $strContent = sprintf("<strong>[%s]</strong>\t%s", $strPrefix, $strContent);
        }
        if (self::$boolAlreadyPrinted === false) {
            self::$arrMessages[] = $strContent.PHP_EOL;
        } else {
            echo $strContent.PHP_EOL.'<br />';
        }
    }
    
    public static function output () {
        self::log(sprintf('current environment: %s', Application::currentEnvironment()), 'core');
        $strContainer = "<pre class='container' style='font-size: 1em;border:1px solid #888; margin:20px auto; padding: 20px; background-color:#f8f8f8;'>%s</pre>";
        $strContent = join('', self::$arrMessages);
        
        $strOutput = sprintf($strContainer, $strContent);
        self::$boolAlreadyPrinted = true;
        return $strOutput;
    }

}
