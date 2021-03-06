<?php namespace webcitron\Subframe;

use webcitron\Subframe\Application;

class Router {
    
    private static $objInstance = null;
    public $arrRoutes = array();
    public $boolRoutesLoaded = false;
    
    private static $objCurrentRoute = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$objInstance === null) {
            self::$objInstance = new Router();
            self::$objInstance->loadRoutes();
        }
        return self::$objInstance;
    }
    
    public static function getCurrentRoute ($boolDebug = false) {
        // if ($boolDebug === true) {
        //     echo 'route: '.self::$objCurrentRoute."<br />\n";
        // }
        return self::$objCurrentRoute;
    }
    
    public function loadRoutes () {
        require Application::getInstance()->strDirectory.'/config/routes.php';
    }
    
    public function dispath () {
        $objRequest = Request::getInstance();
        $strCurrentUri = $objRequest->getUri();
        $objLanguages = Languages::getInstance();
        $strCurrentLanguage = $objLanguages->getCurrentLanguage();

        self::$objCurrentRoute = $this->findRoute($strCurrentUri, $strCurrentLanguage);
        return self::$objCurrentRoute;
    }
    
    public function findRoute ($strUri, $strCurrentLanguage, $boolDebug = false) {
        $objRoute = null;
        $arrHits = array();
        
        $objRecognizedRoute = null;
        if (!empty($this->arrRoutes[$strCurrentLanguage])) {
            $arrRoutesToCheck = $this->arrRoutes[$strCurrentLanguage];
        } else {
            $arrRoutesToCheck = $this->arrRoutes;
        }
        foreach ($arrRoutesToCheck as $objRoute) {
//            $strPattern = $objRoute->strUri;
            foreach ($objRoute->arrUris as $strPattern) {
                if (!empty($strPattern)) {
                    $strPattern = str_replace('/', '\/', $strPattern);
                    $strPattern = str_replace('.', '\.', $strPattern);
                    $strPattern = str_replace('-', '\-', $strPattern);
        //            echo str_replace(array('\/', '\-', '\.'), array('/', '-', '.'), $strPattern) .' -> ';
                    $strPattern = sprintf('^%s$', preg_replace('/\{[^}]+\}/', '([^\/]+)', $strPattern)); 
        //            echo str_replace(array('\/', '\-', '/.'), array('/', '-', '.'), $strPattern).'<br />';
                } else {
                    $strPattern = '.*+';
                }
                $strPattern = '/'.$strPattern.'/';
               // echo $strUri .' -> '.$strPattern.'<br />';
                $numPregMatchResult = @preg_match($strPattern, $strUri, $arrHits);
                // echo $numPregMatchResult."<br />";
//                if ($boolDebug === true) {
//                    echo '<pre>'.$numPregMatchResult;
//                    print_r($arrHits);
//                    echo '</pre> ('.$numPregMatchResult.')';
//                }
                if ($numPregMatchResult === 1) {

    //                exit();
                    if (!empty($arrHits)) {
                        $arrFilteredHits = array();
                        if ($boolDebug === true) {
                            echo '1) "'.$numPregMatchResult.'"<pre>';
                            print_r($arrHits);
                            echo '</pre>';
                        }

    //                    for ($numHit = 1; $numHit<count($arrHits); $numHit+=2) {
    //                        $arrFilteredHits[] = $arrHits[$numHit];
    //                    }
    //                    array_shift($arrHits);
    ////                    Request::setParams($arrFilteredHits);
    //                    
    //                    Request::setParams($arrHits);
                        for ($numHit = 1; $numHit<count($arrHits); $numHit++) {
                            if (substr($arrHits[$numHit], -1) !== '/') {
                                $arrFilteredHits[] = $arrHits[$numHit];
                            }
                        }
                        Request::setParams($arrFilteredHits);
    //                    echo '<pre>';
    //                    print_r($arrFilteredHits);
    //                    
    //                    print_r($arrHits);
    //                    exit();

                    }
                    $objRecognizedRoute = $objRoute;
                    Debug::log('On route '.$objRecognizedRoute->strRouteFullName, 'core-router');
                    break;
                }
            }
            if (!empty($objRecognizedRoute)) {
                break;
            }
        }
        
//        if (empty($objRecognizedRoute)) {
//            echo "ERROR! ".__FILE__.'::'.__FUNCTION__.'#'.__LINE__;
//            exit();
//        }
//        echo 's'; 
       // echo '<pre>';
       // print_r($objRecognizedRoute);
       // exit();
        return $objRecognizedRoute;
    }
    
    
    public function getRouteByName($strRouteName) {
        $objRoute = null;
        
        if (!empty($this->arrRoutes[$strRouteName])) {
            $objRoute = $this->arrRoutes[$strRouteName];
        } else if (Application::currentEnvironment() !== Application::ENVIRONMENT_PRODUCTION) {
            echo '<pre>';
            print_r(debug_backtrace());
            echo '</pre>';
            exit('Nie zdefiniowana ścieżka '.$strRouteName);
        } 
        return $objRoute;
    }
    
    public function getRouteByNameAndLang($strRouteName, $strLanguage) {
        $objRoute = null;
        if (!empty($this->arrRoutes[$strLanguage][$strRouteName])) {
            $objRoute = $this->arrRoutes[$strLanguage][$strRouteName];
        } else if (!empty($this->arrRoutes[$strRouteName])) {
            $objRoute = $this->arrRoutes[$strRouteName];
        } else if (Application::currentEnvironment() !== Application::ENVIRONMENT_PRODUCTION) {
            echo '<pre>';
            print_r(debug_backtrace());
            echo '</pre>';
            exit('Nie zdefiniowana ścieżka '.$strLanguage.'/'.$strRouteName);
        } 
        return $objRoute;
    }
    
    
}