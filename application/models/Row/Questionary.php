<?php
class Application_Model_Row_Questionary extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * najde a vraci seznam vyplnenych dotazniku
	 * 
	 * @return Questionary_Model_Rowset_Filleds
	 */
	public function findFilleds() {
		$tableFilleds = new Questionary_Model_Filleds;
		
		$where = "`questionary_id` = " . $tableFilleds->getAdapter()->quote($this->questionary_id);
		
		return $tableFilleds->fetchAll($where, "created_at");
	}
	
	public function findQuestionaryRow() {
		return $this->findParentRow(new Questionary_Model_Questionaries, "questionary");
	}
}
