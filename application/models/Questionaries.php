<?php
class Application_Model_Questionaries extends Zend_Db_Table_Abstract {
	
	protected $_primary = "questionary_id";
	
	protected $_sequence = false;
	
	protected $_name = "application_questionaries";
	
	protected $_referenceMap = array(
		"questionary" => array(
			"columns" => "questionary_id",
			"refTableClass" => "Questionary_Model_Questionaries",
			"refColumns" => "id"
		),
		
		"user" => array(
			"columns" => "user_id",
			"refTableClass" => "Application_Model_Users",
			"refColumns" => "id"
		)
	);
	
	protected $_rowsetClass = "Application_Model_Rowset_Questionaries";
	
	protected $_rowClass = "Application_Model_Row_Questionary";
	
	/**
	 * vytvori novy dotaznik
	 * 
	 * @param Application_Model_Row_User $user uzivatel
	 * @param string $name jmeno dotazniku
	 * @return array
	 */
	public function createQuestionary(Application_Model_Row_User $user, $name) {
		$tableQuestionaries = new Questionary_Model_Questionaries();
		
		$questionary = $tableQuestionaries->createQuestionary($name);
		$infos = $this->createRow(array(
			"questionary_id" => $questionary->id,
			"user_id" => $user->id
		));
		
		$infos->save();
		
		return array(
			"questionary" => $questionary,
			"info" => $infos
		);
	}
	
	/**
	 * nacte informace o dotaznicich podle uzivatele
	 * 
	 * @return Application_Model_Rowset_Questionaries
	 */
	public function findByUser(Application_Model_Row_Users $user) {
		// vygenerovani where
		$where = "user_id = " . $this->getAdapter()->quote($user->id);
		
		return $this->fetchAll($where, "id");
	}
}
