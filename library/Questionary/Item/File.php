<?php
class Questionary_Item_File extends Questionary_Item_SingleInput {
	
	/**
	 * seznam prijimanych pripon
	 * 
	 * @var array<string>
	 */
	private $_accepted = array();
	
	/**
	 * prida priponu
	 * 
	 * @param string $suffix pripona
	 * @return Questionary_Item_File
	 * @throws Questionary_Item_Exception
	 */
	public function addAccepted($suffix) {
		// prevedeni na mala pismena
		$suffix = strtolower($suffix);
		
		// kontrola existence
		if ($this->isAccepting($suffix)) throw new Questionary_Item_Exception("Suffix '$suffix' is already on accepting list");
		
		// zapsani do sezanamu
		$this->_accepted[] = $suffix;
		
		return $this;
	}
	
	/**
	 * vraci seznam prijimanych pripon
	 * 
	 * @return array
	 */
	public function getAccepted() {
		return $this->_accepted;
	}
	
	/**
	 * zkonstroluje, zda prvek prijima priponu
	 * 
	 * @param string $suffix pripona
	 * @return bool
	 */
	public function isAccepting($suffix) {
		// prevedeni na mapa pismena
		$suffix = strtolower($suffix);
		
		return in_array($suffix, $this->_accepted);
	}
	
	/**
	 * odebere prijimanou priponu
	 * 
	 * @param string $suffix pripona k odebrani
	 * @return Questionary_Item_File
	 * @throws Questionary_Item_Exception
	 */
	public function removeAccepted($suffix) {
		// kontrola existence
		if (!$this->isAccepting($suffix)) throw new Questionary_Item_Exception("Suffix '$suffix' is not in accepting list");
		
		// prevedeni na mala pismena
		$suffix = strtolower($suffix);
		
		// nalezeni a odstraneni
		$buffer = array();
		
		foreach ($this->_accepted as $item) {
			// pokud se vysledek porovnani nerovna 0, pak se zapise polozka do seznamu
			if (strcmp($item, $suffix)) {
				$buffer[] = $item;
			}
		} 
		
		// prepsani starych hodnot
		$this->_accepted = $buffer;
		
		return $this;
	}
	
	/**
	 * nastavi prijimane pripony
	 * 
	 * @param array $accepted nove pripony
	 * @throws Questionary_Item_Exception
	 */
	public function setAccepted(array $accepted) {
		$this->_accepted = $accepted;
		
		return $this;
	}
	
	public function setFromArray(array $data) {
		parent::setFromArray($data);
		
		$data["params"] = array_merge(array("accepted" => array()), $data["params"]);
		
		$this->_accepted = $data["accepted"];
		
		return $this;
	}
	
	public function toArray() {
		$retVal = parent::toArray();
		
		$retVal["params"]["accepted"] = $this->_accepted;
		
		return $retVal;
	}
}