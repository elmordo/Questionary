<?php
abstract class Questionary_Item_Container extends Questionary_Item_Abstract {
	
	/**
	 * seznam objektu v poradi ve kterem se budou renderovat
	 * 
	 * @var array<Questionary_Item_Abstract>
	 */
	private $_items = array();
	
	/**
	 * prida objekt do skupiny
	 * 
	 * @param Questionary_Item_Abstract $item objekt
	 * @return Questionary_Item_Container
	 */
	public function addItem(Questionary_Item_Abstract $item) {
		try {
			// odstraneni z puvodniho kontejneru, pokud je to treba
			$item->_clearContainer();
		} catch (Questionary_Exception $e) {
			
		}
		
		// zapis do tohoto kontejneru
		$this->_items[] = $item;
		$item->setRenderable(false);
		
		return $this;
	}
	
	/**
	 * vycisti skupinu
	 * 
	 * @return Questionary_Item_Container
	 */
	public function clear() {
		// odstranenni prvku
		$items = $this->_items;
		
		foreach ($items as $item) {
			$this->removeItem($item);
		}
		
		return $this;
	}
	
	/**
	 * vraci objekty ve skupine
	 * 
	 * @return array<Questionary_Item_Abstract>
	 */
	public function getItems() {
		return $this->_items;
	}
	
	/**
	 * odebere objekt ze skupiny
	 * 
	 * @param Questionary_Item_Abstract $item objekt k odebrani
	 * @return Questionary_Item_Container
	 */
	public function removeItem(Questionary_Item_Abstract $item) {
		// kontrola existence
		$found = false;
		$buffer = array();
		
		foreach ($this->_items as $localItem) {
			// kontrola shodnosti
			if ($localItem == $item) {
				$found = true;
			} else {
				$buffer[] = $localItem;
			}
		}
		
		// kontrola nalezeni
		if (!$found) throw new Questionary_Item_Exception("Item not found");
		
		// odebrani dat
		$this->_items = $buffer;
		$item->_clearContainer();
		$item->setRenderable(true);
		
		return $this;
	}
	
	public function setFromArray(array $data) {
		$data = array_merge(array("params" => array()), $data);
		
		// zavolani predchozi metody
		parent::setFromArray($data);
		
		// nastaveni dat
		$data["params"] = array_merge(array("items" => array()), $data["params"]);
		
		// zapis dat
		foreach ($data["params"]["items"] as $itemName) {
			$item = $this->getQuestionary()->getByName($itemName);
			
			$this->addItem($item);
		}
		
		return $this;
	}
	
	/**
	 * nastavi seznam clenu skupiny
	 * 
	 * @param array $items nove prvky
	 * @return Questionary_Item_Container
	 */
	public function setItems(array $items) {
		// vymazani skupiny
		$this->clear();
		
		// zapis novych hodnot
		foreach ($items as $item) {
			$this->addItem($item);
		}
		
		return $this;
	}
	
	public function toArray() {
		// zavolani predka
		$retVal = parent::toArray();
		
		// zapis dalsich hodnot
		$retVal["params"]["items"] = array();
		
		foreach ($this->_items as $item) {
			$retVal["params"]["items"][] = $item->getName();
		}
		
		return $retVal;
	}
}