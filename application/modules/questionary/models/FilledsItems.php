<?php
class Questionary_Model_FilledsItems extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_filleds_items";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_referenceMap = array(
			"filled" => array(
					"columns" => "filled_id",
					"refTableClass" => "Questionary_Model_Filleds",
					"refColumns" => "id"
			)
	);
}