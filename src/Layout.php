<?php namespace webcitron\Subframe;


use webcitron\Subframe\Application;
use webcitron\Subframe\Router;

class Layout {
    
    public $arrPlaceholderBoxes = array();
    
    public function render ($arrLayoutData = array()) {
        
        $arrBoxesResponseContents = array();
        foreach ($this->arrPlaceholderBoxes as $strPlaceholderName => $arrBoxes) {
            $arrBoxesResponseContents[$strPlaceholderName] = array();
            
            foreach ($arrBoxes as $objBox) {
                $objBoxResponse = $objBox->launch();
                $arrBoxesResponseContents[$strPlaceholderName][] = $objBoxResponse->__toString();
            }
        }
        
        $strLayoutFullName = get_called_class();
        $arrLayoutFullNameTokens = explode('\\', $strLayoutFullName);
        $strLayoutName = array_pop($arrLayoutFullNameTokens);
        $strLayoutPath = sprintf('%s/layout/view/%s', Application::getInstance()->strDirectory, $strLayoutName);
        
        $objTemplater = Templater::createSpecifiedTemplater(Config::get('templater'));
        
        $objCurrentRoute = Router::getCurrentRoute();
        if (!empty($objCurrentRoute)) {
            $arrLayoutData['route'] = array(
                'name' => $objCurrentRoute->strRouteName, 
                'action' => $objCurrentRoute->strMethodName
            );
        }
        
        if (method_exists($this, 'launch')) {
            $arrLayoutData = array_merge($this->launch(), $arrLayoutData);
        }
        $strLayoutContent = $objTemplater->getTemplateFileContent($strLayoutPath, $arrLayoutData);
        
        foreach ($this->arrPlaceholderBoxes as $strPlaceholderName => $arrBoxes) {
            $strLayoutContent = str_replace(
                sprintf('[placeholder:%s]', $strPlaceholderName), 
                join('', $arrBoxesResponseContents[$strPlaceholderName]), 
                $strLayoutContent
            );
        }
        
        
        // removing unused placeholders
        $strLayoutContent = preg_replace('/\[placeholder\:([a-z0-9\-]+)\]/', '', $strLayoutContent);
//        echo $strLayoutContent;exit();
//        echo $strLayoutContent;exit();
        return $strLayoutContent;
    }
    
   final public function addBoxes($strPlaceholderName, $mulBoxes) {
       if (is_array($mulBoxes)) {
        $this->arrPlaceholderBoxes[$strPlaceholderName] = $mulBoxes;
       } else {
           $this->arrPlaceholderBoxes[$strPlaceholderName][] = $mulBoxes;
       }
   }
    
//    public static function launch($strBoardName) {
//        
//        $strBoardFullPath = sprintf('\board\%s', $strBoardName);
//        $objSpecifiedBoard = new $strBoardFullPath();
//        $objBoardMethod = new \ReflectionMethod($strBoardFullPath, 'index');
//        $arrRequestParams = Request::getParams();
//        $objResponse = $objBoardMethod->invokeArgs($objSpecifiedBoard, $arrRequestParams);
//        
//        echo '<pre>';
//        print_r($objSpecifiedBoard);
//        exit();
//    }
    
    
}