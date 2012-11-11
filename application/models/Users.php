<?php
class Application_Model_Users extends Zend_Db_Table_Abstract {
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	/**
	 * vytvori noveho uzivatele a zapise ho do databaze
	 * 
	 * @param string $login login noveho uzivatele
	 * @param string $email emailova adresa
	 * @param string $password heslo
	 * @return Application_Model_Row_User
	 */
	public function createUser($login, $email, $password) {
		$retVal = $this->createRow(array(
				"login" => $login,
				"email" => $email,
				"password" => sha1($password)
		));
		
		$retVal->save();
		
		return $retVal;
	}
	
	/**
	 * najde uzivatele podle loginu
	 * 
	 * @param string $login login uzivatele
	 * @return Application_Model_Row_User
	 */
	public function findByLogin($login) {
		$where = $this->getAdapter()->quoteInto("`login` like ?", $login);
		
		return $this->fetchRow($where);
	}
}
