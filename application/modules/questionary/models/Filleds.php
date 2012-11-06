<?php
class Questionary_Model_Filleds extends Zend_Db_Table_Abstract {
	protected $_name = "questionary_filleds";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_referenceMap = array(
			"questionary" => array(
					"columns" => "questionary_id",
					"refTableClass" => "Questionary_Model_Questionaries",
					"refColumns" => "id"
			)
	);
	
	protected $_rowClass = "Questionary_Model_Row_Filled";
	
	public function createFilled($questionary) {
		$row = $this->createRow(array(
				"questionary_id" => $questionary->id,
				"modified_at" => new Zend_Db_Expr("NOW()")
		));
		
		$row->save();
		
		return $row;
	}
	
	/**
	 * nacte seznam vyplnenych dotazniku dle sablony
	 * 
	 * @param Questionary_Model_Row_Questionary $questionary sablona dotazniku
	 * @return Zend_Db_Table_Rowset
	 */
	public function findByQuestionary(Questionary_Model_Row_Questionary $questionary) {
		return $this->fetchAll("questionary_id = " . $questionary->id, "created_at");
	}
	
	/**
	 * nacte vyplneny dotaznik dle id
	 * 
	 * @param int $id identifikator dotazniku
	 * @return Questionary_Model_Row_Filled
	 */
	public function getById($id) {
		$id = (int) $id;
		
		return $this->find($id)->current();
	}
}