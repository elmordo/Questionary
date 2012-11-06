<?php
abstract class Questionary_Item_SingleInput extends Questionary_Item_Abstract {
	
	/**
	 * maximalni delka vstupu
	 * @var int
	 */
	private $_maxLength = 0;
	
	/**
	 * vraci maximalni delku vstupu
	 * 
	 * @return int
	 */
	public function getLength() {
		return $this->_maxLength;
	}
	
	public function setFromArray(array $data) {
		$data = array_merge(array("params" => array()), $data);
		
		parent::setFromArray($data);
		
		// kontrola dat
		$params = $data["params"];
		
		$params = array_merge(array("maxLength" => 0), $data);
		
		$this->_maxLength = $params["maxLength"];
		
		return $this;
	}
	
	/**
	 * nastavi maximalni delku vstupu
	 * 
	 * @param int $lengthm nova delka
	 * @return Questionary_Item_SingleInput
	 */
	public function setLength($length) {
		// kontrola vstupu
		$length = (int) $length;
		
		if ($length < 0) throw new Questionary_Item_Exception("Length must be equal or greater than zero");
		
		$this->_maxLength = $length;
	}
	
	public function toArray() {
		$retVal = parent::toArray();
		
		$retVal["params"]["maxLength"] = $this->_maxLength;
		
		return $retVal;
	}
}