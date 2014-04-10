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
	/**
	 * vraci dotaznik jako tridu
	 * 
	 * @return Questionary_Questionary
	 */
	public function toClass() {
		$retVal = new Questionary_Questionary();
		
		$retVal->setName($this->name);
		
		// nacteni dat
		$items = $this->findDependentRowset("Questionary_Model_FilledsItems", "filled");
		
		// prochazeni a registrace itemu a jejich indexace dle id
		$itemIndex = array();
		
		foreach ($items as $item) {
			// vytvoreni instance
			$itemInstance = $retVal->addItem($item->name, $item->class);
			$retVal->setRenderable($itemInstance, false);
			
			$itemIndex[$item->id] = $item;
		}
		
		// nastaveni parametru
		foreach ($items as $item) {
			$instance = $retVal->getByName($item->name);
			
			// sestaveni hodnot
            $value = unserialize($item->data);
            
			$params = array(
					"label" => $item->label,
					"value" => $value,
                    "default" => $value,
					"isLocked" => $item->is_locked,
					"params" => unserialize($item->params)
			);
            
			$instance->setFromArray($params);
		}
		
		// nacteni zobrazovanych itemu
		$tableRenderables = new Questionary_Model_FilledsRenderables();
		
		$reders = $this->findDependentRowset($tableRenderables, "filled", $tableRenderables->select(false)->order("position"));
		
		foreach ($reders as $render) {
			// ziskani jmena
			$itemName = $itemIndex[$render->item_id]->name;
			
			// ziskani itemu
			$item = $retVal->getByName($itemName);
			$retVal->setRenderable($item, true);
		}
		
		return $retVal;
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