<?php
class Application_Model_Row_User extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * nalezne dotazniky uzivatele
	 * dotazniky jsou ulozeny v poli
	 * kazdy prvek pole obsahuze stdClass s hodnotami
	 * 
	 * quesitonary [Questionary_Model_Row_Questionary] - puvodni radek dotazniku
	 * info [Application_Model_Row_Questionary] - rozsirujici informace o dotazniku
	 * 
	 * @return array
	 */
	public function findQuestionaries() {
		$infos = $this->findDependentRowset(new Application_Model_Questionaries, "user");
		$infoIndex = array();
		
		$questionaryIds = array(0);
		
		foreach ($infos as $info) {
			$infoIndex[$info->questionary_id] = $info;
			$questionaryIds[] = $info->questionary_id;
		}
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries;
		$where = $tableQuestionaries->getAdapter()->quoteInto("id in (?)", $questionaryIds);
		
		$origins = $tableQuestionaries->fetchAll($where, "name");
		
		// zapsani do pole
		$retVal = array();
		
		foreach ($origins as $item) {
			$retVal[] = array(
					"questionary" => $item,
					"info" => $infoIndex[$item->id]
			);
		}
		
		return $retVal;
	}

	/**
	 * zkosntroluje heslo uzivatele, jeslti je spravne
	 * 
	 * @param string $password kontrolovane heslo
	 * @return bool
	 */
	public function checkPassword($password) {
		return (!strcmp($this->_hashPassword($password), $this->password));
	}
	
	/**
	 * nastavi nove heslo
	 * 
	 * @param string $password nove heslo
	 * @return Application_Model_Row_User
	 */
	public function setPassword($password) {
		$this->password = $this->_hashPassword($password);
	}
	
	/**
	 * zahashuje heslo
	 * 
	 * @param string $psw heslo k zahashovani
	 * @return string
	 */
	protected function _hashPassword($psw) {
		return sha1($psw);
	}
}
