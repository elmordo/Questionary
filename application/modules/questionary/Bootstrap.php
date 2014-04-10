<?php
class Questionary_Bootstrap extends Zend_Application_Module_Bootstrap {
	
	public function initResourceLoader() {
		Zend_Loader_Autoloader::getInstance()->registerNamespace("Questionary_");
		
		parent::initResourceLoader();
	}
    
    public function _initRoutes() {
        $this->bootstrap('FrontController');
		$frontController = $this->getResource('FrontController');
		$router = $frontController->getRouter();
		
		$router->addRoute(
			'questionaries',
			new Zend_Controller_Router_Route('/questionary',
											 array('module' => "questionary",
                                                 'controller' => 'admin',
											 	   'action' => 'index'))
		);
    }
	
}