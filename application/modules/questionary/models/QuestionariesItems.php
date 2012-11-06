<?php
class Questionary_Model_QuestionariesItems extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_questionaries_items";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_referenceMap = array(
			"questionary" => array(
					"columns" => "questionary_id",
					"refTableClass" => "Questionary_Model_Questionaries",
					"refColumns" => "id"
			)
	);
}