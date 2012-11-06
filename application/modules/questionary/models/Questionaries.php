<?php
class Questionary_Model_Questionaries extends Zend_Db_Table_Abstract {
	
	protected $_name = "questionary_questionaries";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_rowsetClass = "Questionary_Model_Rowset_Questionaries";
	
	protected $_rowClass = "Questionary_Model_Row_Questionary";
	
	public function createQuestionary($name) {
		$retVal = $this->createRow(array("name" => $name));
		$retVal->save();
		
		return $retVal;
	}
	
	/**
	 * nacte a vraci radek dotazniku dle id
	 * 
	 * @param int $id idnetifikacni cislo dotazniku
	 * @return Questionary_Model_Row_Quesionary
	 */
	public function loadById($id) {
		return $this->fetchRow("id = " . $this->getAdapter()->quote($id));
	}
}