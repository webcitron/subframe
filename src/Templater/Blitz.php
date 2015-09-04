<?php namespace webcitron\Subframe\Templater;

use webcitron\Subframe\Application;

class Blitz
{
    private static $objInstance = null;
    private $objBlitz = null;
    private $arrMetaData = array();

    private function __construct()
    {
        $this->objBlitz = new SubBlitz();
    }
    
    public static function getInstance() {
        if (self::$objInstance === null) {
            self::$objInstance = new Blitz();
        }
        return self::$objInstance;
    }
    
    
    public function getTemplateFileContent ($strFilePath, $arrViewData = array()) {
         $strTemplateFileContent = $this->objBlitz->include($strFilePath.'.tpl', $arrViewData);
        
        return $strTemplateFileContent;
    }
    
    // -----
    
    public function renderResponseView($objCurrentRoute, $strLayoutName, $strViewName, $arrViewData, $arrMetaData = array()) {
        $this->arrMetaData = $arrMetaData;
        $arrLayoutData = array_merge(
            $arrViewData, 
            array(
                'strController' => $objCurrentRoute->strControllerName, 
                'strAction' => $objCurrentRoute->strActionName
            )
        );
        $strLayout = $this->objBlitz->include(APP_DIR.'/layouts/'.$strLayoutName.'.tpl', $arrLayoutData);
        $strView = $this->objBlitz->include(APP_DIR.'/views/'.$strViewName.'.tpl', $arrViewData);

        $strOutput = str_replace('[[#placeholder:page#]]', $strView, $strLayout);

        return $strOutput;
    }

    public function renderController($objController)
    {
        $strLayoutName = $objController->strLayout;
        $strViewName = $objController->strView;
        $arrViewData = $objController->arrViewData;

        $strLayout = $this->objBlitz->include(APP_DIR.'/layouts/'.$strLayoutName.'.tpl', $arrViewData);
        $strView = $this->objBlitz->include(APP_DIR.'/views/'.$strViewName.'.tpl', $arrViewData);

        $strOutput = str_replace('[[#placeholder:page#]]', $strView, $strLayout);

        return $strOutput;
    }
    
    public function getMetaData($strKey) {
        $strReturn = isset($this->arrMetaData[$strKey]) ? $this->arrMetaData[$strKey] : '';
        return $strReturn;
    }

    public function render($objPage)
    {
        $strLayoutName = $objPage->strLayout;
        $strPageName = $objPage->strPageName;
        $strPageView = $objPage->getView();

        $strLayout = $this->objBlitz->include(APP_DIR.'/layouts/'.$strLayoutName.'.html');
        $strPageContent = $this->objBlitz->include(APP_DIR.'/pages/'.strtolower($strPageName).'/'.$strPageView.'.html');

        foreach ($objPage->arrBoxes as $strBoxPlaceholder => $arrBoxes) {
            $strPlaceholderContent = '';
            foreach ($arrBoxes as $strBoxName) {
                $arrBoxIdTokens = explode('.', $strBoxName);
                $strView = array_pop($arrBoxIdTokens);
                $strBox = array_pop($arrBoxIdTokens);
                $strFilepath = APP_DIR.'/boxes/';
                foreach ($arrBoxIdTokens as $strBoxIdToken) {
                    $strFilepath .= $strBoxIdToken.'/';
                }
                $strFilepath .= $strBox.'/'.ucfirst($strBox).'.class.php';
                require_once $strFilepath;

                $objBox = new $strBox;
                $objBox->strBoxName = $strBox;
                $objBox->strActionName = $strView;

                $objViewMethod = new ReflectionMethod($strBox, ucfirst($strView));
                $objViewMethod->invoke($objBox);

                $strBoxContent = $this->objBlitz->include(APP_DIR.'/boxes/'.$objBox->strBoxName.'/views/'.$objBox->strActionName.'.'.$objBox->strView.'.tpl');

                $strPlaceholderContent .= $strBoxContent;
            }

            $strPageContent = str_replace('[[#placeholder:box:'.$strBoxPlaceholder.'#]]', $strPlaceholderContent, $strPageContent);
        }
        $strOutput = str_replace('[[#placeholder:page#]]', $strPageContent, $strLayout);

        return $strOutput;
    }
    

}

class SubBlitz extends \Blitz implements \webcitron\Subframe\ITemplaterHelper {
    
    
    /**
     * @return string
     *  Find route based on its name and return its uri (with parameteres)
     */
    public static function url()
    {
        // dynamic parameteres :(
        $arrParams = func_get_args();
        $strRouteName = array_shift($arrParams);
        return \webcitron\Subframe\Url::route($strRouteName, $arrParams);
    }
    
    public static function plaintext ($strInput) {
        $strString = htmlspecialchars(strip_tags($strInput));
        return $strString;
    }
    
    public static function html ($strInput) {
//        $strString = addslashes($strInput);
        return $strInput;
    }
    
    public static function renderUserJs () {
        $objJsController = \webcitron\Subframe\JsController::getInstance();
        $objApplication = Application::getInstance();
        $strUserJs = $objJsController->render($objApplication->strName);
        return $strUserJs;
    }
    
    public static function renderHeadAddons () {
        $objJsController = \webcitron\Subframe\JsController::getInstance();
        $objApplication = Application::getInstance();
        $strHeadAddons = $objJsController->render($objApplication->strName);
        return $strHeadAddons;
    }
    
    public static function metaData($strKey, $strWrapper = '', $boolNeedEscaping = true) {
        $objTemplaterBlitz = Blitz::getInstance();
        $strReturn = $objTemplaterBlitz->getMetaData($strKey);
        if (!empty($strWrapper)) {
            if (empty($strReturn)) {
                $strReturn = '';
            } else {
                if ($boolNeedEscaping === true) {
                    $strReturn = htmlspecialchars($strReturn);
                }
                $strReturn = sprintf($strWrapper, $strReturn);
            }
        }
        return $strReturn;
    }
    
    public static function baseUrl() {
        $strBaseUrl = Application::url();
        return $strBaseUrl;
    }
    
    public static function prettyDateTime ($mulDateTime) {
        if (empty($mulDateTime)) {
            return '<i>nie określono</i>';
        } else if (intval($mulDateTime) === $mulDateTime) {
            $numTimestamp = $mulDateTime;
        } else {
            $numTimestamp = strtotime($mulDateTime);
        }
        $numNow = time();
        $strReturn = '';
        
        if ($numTimestamp >= $numNow - (60*15)) {
            $strReturn = 'przed chwilą';
        } else if ($numTimestamp >= $numNow - (60*30)) {
            $strReturn = 'pół godziny temu';
        } else if ($numTimestamp >= $numNow - (60*60)) {
            $strReturn = 'godzinę temu';
        } else if ($numTimestamp >= $numNow - (60*60*12)) {
            $strReturn = 'w ciągu ostatnich 12 godz';
        } else {
            $strReturn = self::prettyDate($mulDateTime);
        }
        
        return $strReturn;
    }
    
    public static function prettyDate ($mulDateTime) {
        if (intval($mulDateTime) === $mulDateTime) {
            $numTimestamp = $mulDateTime;
        } else {
            $numTimestamp = strtotime($mulDateTime);
        }
        $numNow = time();
        $strReturn = '';
        if (date('Ymd', $numTimestamp) === date('Ymd', $numNow)) {
            $strReturn = 'dzisiaj';
        } else if (date('Ymd', $numTimestamp) === date('Ymd', $numNow-(60*60*24))) {
            $strReturn = 'wczoraj';
        } else {
            $strReturn = date('d.m.Y', $numTimestamp);
        }
        return $strReturn;
    }
    
    public function pagination ($strPaginationName, $boolExtended = true) {
        $objPagination = \backend\classes\Pagination::get($strPaginationName);
        return $objPagination->render($boolExtended);
    }
    
    
    public function currentEnvironment () {
        return Application::currentEnvironment();
    }
    
    public function makeGrid ($arrItems) {
        $strHtml = '';
        if (!empty($arrItems)) {
            $strHtml .= '<div class="row stream-row">';
            $arrConfig = array();
            $arrConfig[] = array(3, array('col-md-6 col-sm-4', 'col-md-3 col-sm-4', 'col-md-3 col-sm-4'));
            $arrConfig[] = array(3, array('col-md-4 col-sm-6', 'col-md-4 col-sm-3', 'col-md-4 col-sm-3'));
            $arrConfig[] = array(4, array('col-md-2 col-sm-3', 'col-md-3 col-sm-3', 'col-md-5 col-sm-3', 'col-md-2 col-sm-3'));

            $numRowConfigIndex = 0;
            $numItemInRowIndex = 0;

            $strTempalatePath = dirname(__FILE__).'/../../../../../app/imagehost2/box/files/view/GridItemTemplate.tpl';
    //        $this->load('{{ include("'.$strTempalatePath.'") }}');
            foreach ($arrItems as $arrItem) {
    //            $strHtml .= 'current row classes count '.count($arrConfig[$numRowConfigIndex][1]).' ';
                if ($numItemInRowIndex === count($arrConfig[$numRowConfigIndex][1])) {
                    // change row
                    $numRowConfigIndex++;
                    if ($numRowConfigIndex === count($arrConfig)) {
                        $numRowConfigIndex = 0;
                    }
                    $numItemInRowIndex = 0;
                    $strHtml .= '</div><div class="row stream-row">';
                }
                $strCellClasses = $arrConfig[$numRowConfigIndex][1][$numItemInRowIndex];
    //            $this->block('/arrItem', $arrItem, true);
                $strCell = $this->include($strTempalatePath, $arrItem);
                $strHtml .= '<div class="item-wrapper '.$strCellClasses.'">'.$strCell.'</div>';
                $numItemInRowIndex++;
            }

            $strHtml .= '</div>';
        }
        
        return $strHtml;
    }
    
}
