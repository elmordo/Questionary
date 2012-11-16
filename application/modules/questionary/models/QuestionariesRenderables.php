<?php
class Questionary_Model_QuestionariesRenderables extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_questionaries_renderables";
	
	protected $_sequence = false;
	
	protected $_primary = array("questionary_id", "item_id");
	
	protected $_rowsetClass = "Questionary_Model_Rowset_QuestionariesRenderables";
	
	protected $_rowClass = "Questionary_Model_Row_QuestionaryRenderable";
	
	protected $_referenceMap = array(
			"item" => array(
					"columns" => "item_id",
					"refTableClass" => "Questionary_Model_QuestionariesItems",
					"refColumns" => "id"
			),
			
			"questionary" => array(
					"columns" => "questionary_id",
					"refTableClass" => "Questionary_Model_Questionaries",
					"refColumns" => "id"
			)
	);
}