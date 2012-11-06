<?php
class Questionary_Bootstrap extends Zend_Application_Module_Bootstrap {
	
	public function initResourceLoader() {
		Zend_Loader_Autoloader::getInstance()->registerNamespace("Questionary_");
		
		parent::initResourceLoader();
	}
	
}