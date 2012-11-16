<?php
class Application_Model_Filleds extends Zend_Db_Table_Abstract {
	
	protected $_name = "application_filleds";
	
	protected $_sequence = false;
	
	protected $_primary = "key";
	
	protected $_referenceMap = array(
		"filled" => array(
			"columns" => "filled_id",
			"refTableClass" => "Questionary_Model_Filleds",
			"refColumns" => "id"
		)
	);
	
	protected $_rowsetClass = "Application_Model_Rowset_Filleds";
	
	protected $_rowClass = "Application_Model_Row_Filled";
	
	/**
	 * vytvori preklad
	 * 
	 * @param Questionary_Model_Row_Filled $filledQuestionary vyplneny dotaznik
	 * @return Application_Model_Row_Filled
	 */
	public function createKey(Questionary_Model_Row_Filled $filledQuestionary) {
		// vygenerovani klice
		$key = time() . microtime() . $filledQuestionary->id;
		
		do {
			$keyBase = sha1($key);
			$key = substr($keyBase, 0, 16);
			
			$row = $this->find($key)->current();
		} while ($row);
		
		$retVal = $this->createRow(array(
			"key" => $key,
			"filled_id" => $filledQuestionary->id
		));
		
		$retVal->save();
		
		return $retVal;
	}
}
