<?php
class Questionary_Model_FilledsItems extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_filleds_items";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_rowsetClass = "Questionary_Model_Rowset_FilledsItems";
	
	protected $_rowClass = "Questionary_Model_Row_FilledItem";
	
	protected $_referenceMap = array(
			"filled" => array(
					"columns" => "filled_id",
					"refTableClass" => "Questionary_Model_Filleds",
					"refColumns" => "id"
			)
	);
}