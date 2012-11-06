<?php
class Questionary_Model_Row_Questionary extends Zend_Db_Table_Row_Abstract {
	
	public function saveClass(Questionary_Questionary $questionary) {
		// nastaveni jmena
		$this->name = $questionary->getName();
		$this->save();
		
		// ziskani itemu
		$items = $questionary->getIndex();
		
		// smazani neexistujicich itemu
		$itemList = array("");
		
		foreach ($items as $item) {
			$itemList[] = $item->getName();
		}
		
		$tableItems = new Questionary_Model_QuestionariesItems();
		$adapter = $tableItems->getAdapter();
		$where = array("questionary_id = " . $this->id, "`name` not in (" . $adapter->quote($itemList) . ")");
		
		// nacteni a indexace dat existujicich itemu
		$dbItems = $this->findDependentRowset($tableItems, "questionary");
		$dbItemIndex = array();
		
		foreach ($dbItems as $item) {
			$dbItemIndex[$item->name] = $item;
		}
		
		// prochazeni zaznamu dotazniku a update nebo vytvoreni hodnot
		foreach ($items as $item) {
			// kontrola, jeslti je item v indexu
			if (!isset($dbItemIndex[$item->getName()])) {
				// item neni v seznamu, vytvori se novy zaznam
				$row = $tableItems->createRow(array(
					"name" => $item->getName(),
					"questionary_id" => $this->id,
					"class" => $item->getClassName()
				));
				
				$row->save();
				
				// zapis radku do indexu
				$dbItemIndex[$row->name] = $row;
			}
			
			// update hodnot
			$arrDef = $item->toArray();
			$row = $dbItemIndex[$item->getName()];
			
			$row->label = $arrDef["label"];
			$row["default"] = $arrDef["default"];
			$row["label"] = $arrDef["label"];
			$row["is_locked"] = $arrDef["isLocked"];
			
			// zapis parametru
			$row["params"] = serialize($arrDef["params"]);
			$row->save();
		}
		
		$tableItems->delete($where);
	}
	
	public function toClass() {
		$retVal = new Questionary_Questionary();
		
		$retVal->setName($this->name);
		
		// nacteni dat
		$items = $this->findDependentRowset("Questionary_Model_QuestionariesItems", "questionary");
		
		// prochazeni a registrace itemu
		foreach ($items as $item) {
			// vytvoreni instance
			$itemInstance = $retVal->addItem($item->name, $item->class);
		}
		
		// nastaveni parametru
		foreach ($items as $item) {
			$instance = $retVal->getByName($item->name);
			
			// sestaveni hodnot
			$params = array(
					"label" => $item->label,
					"default" => $item->default,
					"isLocked" => $item->is_locked,
					"params" => unserialize($item->params)
			);
				
			$instance->setFromArray($params);
		}
		
		return $retVal;
	}
}
