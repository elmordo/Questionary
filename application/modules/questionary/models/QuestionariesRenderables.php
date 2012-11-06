<?php
class Questionary_Model_QuestionariesRenderables extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_questionaries_renderables";
	
	protected $_sequence = false;
	
	protected $_primary = array("questionary_id", "item_id");
	
	protected $_referenceMap = array(
			"item" => array(
					"columns" => "item_id",
					"refTableClass" => "Questionary_Model_QuestionariesItems",
					"refColumns" => "id"
			),
			
			"quesitonary" => array(
					"columns" => "questionary_it",
					"refTableClass" => "Questionary_Model_Questionaries",
					"refColumns" => "id"
			)
	);
}