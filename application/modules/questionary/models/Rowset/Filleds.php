<?php
class Questionary_Model_Rowset_Filleds extends Zend_Db_Table_Rowset_Abstract {
	
	/**
	 * vraci radek podle jmena
	 * 
	 * @param string name $jmeno polozky
	 * @return Questionary_Model_Row_FilledItem
	 */
	public function getByName($name) {
		// nastaveni hodnot pro cyklus
		$retVal = null;
		$maxI = $this->_count;
		
		for ($i; $i < $count && is_null($retVal); $i++) {
			if ($this[$i]->name == $name) {
				$retVal = $this[$i];
			}
		}
		
		return $retVal;
	}
	
	/**
	 * vraci seznam radku se jmeny, ktere nejsou ve vstupnim seznamu
	 * 
	 * @param array $names vstupni seznam jmen
	 * @return array
	 */
	public function notInList(array $names) {
		// vytvoreni uvodniho indexu
		$index = array();
		$maxI = $this->_count;
		
		for ($i = 0; $i < $maxI; $i++) {
			$index[$this[$i]->name] = $this[$i];
		}
		
		// odebrani jmen
		foreach ($names as $name) {
			unset($index[$name]);
		}
		
		// vytvoreni navratove hodnoty
		$retVal = array();
		
		foreach($index as $item) {
			$retVal[] = $item;
		}
		
		return $item;
	}
}
