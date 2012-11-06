<?php
class Questionary_Model_Row_Filled extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * vraci TRUE, pokud je hodnota uzamcena
	 * 
	 * @return bool
	 */
	public function isLocked() {
		return (bool) $this->is_locked;
	}
	
	/**
	 * uzamkne hodnotu
	 * 
	 * @return Questionary_Model_Row_Filled
	 */
	public function lock() {
		$this->is_locked = 1;
		
		return $this;
	}
	
	/**
	 * z instance radku sestavi vyplneny dotaznik
	 * vraci instnaci dotazniku
	 * 
	 * @return Questionary_Questionary
	 */
	public function toClass() {
		// nalezeni predka
		$questionaryRow = $this->findParentRow("Questionary_Model_Questionaries", "questionary");
		
		$questionary = $questionaryRow->toClass();
		
		// nastaveni uzamceni
		$questionary->setLocked($this->is_locked);
		
		// nacteni vyplneni
		$fills = $this->findDependentRowset("Questionary_Model_FilledsItems", "filled");
		
		foreach ($fills as $item) {
			try {
				// nastaveni hodnoty
				
				/**
				 * @var Questionary_Item_Abstract $qItem
				 */
				$qItem = $questionary->getByName($item->name);
				
				$qItem->fill(unserialize($item->data));
			} catch (Zend_Exception $e) {
				
			}
		}
		
		return $questionary;
	}
	
	/**
	 * ulozi data z dotazniku
	 * 
	 * @param Questionary_Questionary $questionary instance dotazniku
	 * @return Questionary_Model_Row_Filled
	 */
	public function saveFilledData(Questionary_Questionary $questionary) {
		// nalezeni a indexace vyplnenych prvku
		$tableFilledItems = new Questionary_Model_FilledsItems();
		
		$filledItems = $this->findDependentRowset($tableFilledItems, "filled");
		$filledIndex = array();
		
		foreach ($filledItems as $item) {
			$filledIndex[$item->name] = $item;
		}
		
		// priprava dat
		$items = $questionary->getIndex();
		
		// prochazeni elementy a zapis prvku
		foreach ($items as $item) {
			// kontrola, jeslti prvek je vytvroren
			if (isset($filledIndex[$item->getName()])) {
				// prvek uz vyplnen byl, bude se updatovat
				$filledItem = $filledIndex[$item->getName()];
				
				$filledItem->data = serialize($item->getValue());
				$filledItem->save();
			} else {
				// provede se insert
				$filledItem = $tableFilledItems->createRow(array(
						"filled_id" => $this->id,
						"name" => $item->getName(),
						"is_locked" => 0,
						"data" => serialize($item->getValue())
				));
				
				$filledItem->save();
			}
		}
		
		return $this;
	}
}