<?php
abstract class Questionary_Item_ChooseInput extends Questionary_Item_Abstract {

	/**
	 * prvky pro vyber
	 * 
	 * @var array
	 */
	private $_items = array();
	
	/**
	 * vycisti moznosti vyberu a vychozi prvek
	 * 
	 * @return Questionary_Item_ChooseInput
	 */
	public function clear() {
		$this->_default = null;
		$this->_items = array();
	}
	
	/**
	 * vraci klice hodnot
	 * 
	 * @return array
	 */
	public function getKeys() {
		return array_keys($this->_items);
	}
	
	/**
	 * vraci asociativni pole v paru klic->hodnota
	 * obsahem jsou hodnoty zobrazovane uzivateli
	 * 
	 * @return array
	 */
	public function getOptions() {
		return $this->_items;
	}
	
	/**
	 * vraci moznost nastaveni
	 * @param string $name jmeno hodnoty (klic)
	 * @return mixed
	 * @throws Questionary_Item_Exception
	 */
	public function getOption($name) {
		if (!$this->isOption($name)) throw new Questionary_Item_Exception("Item named '$name' does not exist");
		
		return $this->_items[$name];
	}
	
	/**
	 * zkonstroluje, jestli moznost existuje
	 * 
	 * @param stirng $name jmeno hodnoty (klic)
	 * @return bool
	 */
	public function isOption($name) {
		return isset($this->_items[$name]);
	}
	
	/**
	 * odebere moznost nastaveni
	 * 
	 * @param string $name jmeno hodnoty
	 * @return Questionary_Item_ChooseInput
	 * @throws Questionary_Questionary_Exception
	 */
	public function removeOption($name) {
		// kontrola existence
		if (!$this->isOption($name)) throw new Questionary_Item_Exception("Option named '$name' does not exists");
		
		// odebrani moznosti
		unset($this->_items[$name]);
		
		// kontrola defaultni hodnoty
		if ($this->_default == $name) $this->_default = null;
		
		return $this;
	}
	
	/**
	 * nastavi vychozi hodnotu
	 * 
	 * @param string $defaultVal vychozi hodnota
	 * @return Questionary_Item_ChooseInput
	 */
	public function setDefault($defaultVal) {
		// kontrola existence
		if (!$this->isOption($defaultVal)) throw new Questionary_Item_Exception("Item named '$defaultVal' does not exist");
		
		// nastaveni vychozi hodnoty
		$this->_default = $defaultVal;
		
		return $this;
	}
	
	public function setFromArray(array $data) {
		$data = array_merge(array("params" => array()), $data);
		
		parent::setFromArray($data);
		
		// kontrola dat
		$params = $data["params"];
		$params = array_merge(array("options" => array()), $params);
		
		// nastaveni dat
		$this->_items = $params["options"];
		
		return $this;
	}
	
	/**
	 * nastavi moznosti
	 * 
	 * @param array $options asociativni pole moznosti
	 * @return Questionary_Item_ChooseInput
	 */
	public function setOptions(array $options) {
		// nastaveni dat
		$this->_items = $options;
		
		// kontrola existence default
		if (!isset($this->_items[$this->_default])) $this->_default = null;
		
		return $this;
	}
	
	/**
	 * nfastavi jednu hodnotu
	 * 
	 * @param string $name jmeno hodnoty(klic)
	 * @param mixed $value hodnota
	 * @return Questionary_Item_ChooseInput
	 */
	public function setOption($name, $value) {
		// nastaveni hodnoty
		$this->_items[$name] = $value;
		
		return $this;
	}
	
	public function toArray() {
		$retVal = parent::toArray();
		
		$retVal["params"]["options"] = $this->_items;
		
		return $retVal;
	}
}