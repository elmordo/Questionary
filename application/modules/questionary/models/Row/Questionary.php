<?php
class Questionary_Model_Row_Questionary extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * ulozi tridu dotazniku do databaze
	 * 
	 * @param Questionary_Questionary $questionary dotaznik reprezentovany jako trida
	 * @return Questionary_Model_Row_Questionary
	 */
	public function saveClass(Questionary_Questionary $questionary) {
        // zacatek transakce
        $tableItems = new Questionary_Model_QuestionariesItems();
        $tableRenderables = new Questionary_Model_QuestionariesRenderables();
        
        $nameItems = $tableItems->info("name");
        $nameQuestionaries = $this->getTable()->info("name");
        $nameRenderables = $tableRenderables->info("name");
        
		$adapter = $tableItems->getAdapter();
        $adapter->beginTransaction();
        
        // budou se odchytavat vyjimky
        try {
            // zamek tabulek pro zapis
            $sql = "LOCK TABLES $nameItems WRITE, $nameQuestionaries WRITE, $nameRenderables WRITE";
            $adapter->query($sql);
            
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

            $where = array("questionary_id = " . $this->id, "`name` not in (" . $adapter->quote($itemList) . ")");

            // nacteni a indexace dat existujicich itemu
            $dbItems = $this->findDependentRowset($tableItems, "questionary");
            $dbItemIndex = array();

            foreach ($dbItems as $item) {
                $dbItemIndex[$item->name] = $item;
            }

            // vyprezdneni renderable
            $tableRenderables->delete("questionary_id = " . $tableRenderables->getAdapter()->quote($this->id));

            // prochazeni zaznamu dotazniku a update nebo vytvoreni hodnot
            $toInsert = array();
            $toUpdate = array();
            
            foreach ($items as $item) {
                // nacteni hodnot do pole
                $arrDef = $item->toArray();

                // kontrola, jeslti je item v indexu
                if (!isset($dbItemIndex[$item->getName()])) {
                    // sestaveni insertu
                    $tmp = array(
                            $adapter->quote($item->getName()),
                            $adapter->quote($this->id),
                            $adapter->quote($item->getClassName()),
                            $adapter->quote($arrDef["label"]),
                            $adapter->quote(serialize($arrDef["default"])),
                            $adapter->quote($arrDef["isLocked"]),
                            $adapter->quote(serialize($arrDef["params"]))
                    );

                    $toInsert[] = "(" . implode(",", $tmp) . ")";
                } else {
                    // update
                    $sql = "update `$nameItems` set ";

                    $tmp = array(
                            "`label` = " . $adapter->quote($arrDef["label"]),
                            "`default` = " . $adapter->quote(serialize($arrDef["default"])),
                            "`is_locked` = " . $adapter->quote($arrDef["isLocked"]),
                            "`params` = " . $adapter->quote(serialize($arrDef["params"]))
                    );

                    $sql .= implode(",", $tmp);

                    $sql .= " where id = " . $dbItemIndex[$item->getName()]->id;

                    $toUpdate[] = $sql;
                }
            }

            // zapis dat
            $chunks = array_chunk($toUpdate, 100);

            foreach ($chunks as $chunk) {
                $adapter->query(implode(";", $chunk));
            }

            $chunks = array_chunk($toInsert, 100);
            $sqlBase = "insert into `$nameItems` (`name`, `questionary_id`, `class`, `label`, `default`, `is_locked`, `params`) values ";

            foreach ($chunks as $chunk) {
                $sql = $sqlBase . implode(",", $chunk);

                $adapter->query($sql);
            }

            // zapis renderables
            $renderables = $questionary->getItems();

            $i = 0;

            // inicializace vkladani
            $toInsert = array();
            
            // znovunacteni a indexace dat existujicich itemu
            $dbItems = $this->findDependentRowset($tableItems, "questionary");
            $dbItemIndex = array();

            foreach ($dbItems as $item) {
                $dbItemIndex[$item->name] = $item;
            }

            // sestaveni seznamu dat pro vkladani
            foreach ($renderables as $item) {
                // priprava dat
                $itemRow = $dbItemIndex[$item->getName()];

                $tmp = array($this->id, $itemRow->id, $i++);
                $toInsert[] = "(" . implode(",", $tmp) . ")";
            }

            // vlozeni dat
            if ($toInsert) {
                $sql = "insert into `" . $tableRenderables->info("name") . "` (`questionary_id`, `item_id`, `position`) values " . implode(",", $toInsert);
                $adapter->query($sql);
            }

            $tableItems->delete($where);
        } catch (Exception $e) {
            // rollback transakce, odemceni tabulek a propagace vyjimky
            $adapter->rollBack();
            $sql = "UNLOCK TABLES";
            $adapter->query($sql);
            
            throw $e;
        }
        
        // ulozeni transakce
        //$adapter->commit();
	}
	
	/**
	 * vraci dotaznik jako tridu
	 * 
	 * @return Questionary_Questionary
	 */
	public function toClass() {
		$retVal = new Questionary_Questionary();
		
		$retVal->setName($this->name);
		
		// nacteni dat
		$items = $this->findDependentRowset("Questionary_Model_QuestionariesItems", "questionary");
		
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
			$params = array(
					"label" => $item->label,
					"default" => unserialize($item->default),
					"isLocked" => $item->is_locked,
					"params" => unserialize($item->params)
			);
				
			$instance->setFromArray($params);
		}
		
		// nacteni zobrazovanych itemu
		$tableRenderables = new Questionary_Model_QuestionariesRenderables();
		
		$reders = $this->findDependentRowset($tableRenderables, "questionary", $tableRenderables->select(false)->order("position"));
		
		foreach ($reders as $render) {
			// ziskani jmena
			$itemName = $itemIndex[$render->item_id]->name;
			
			// ziskani itemu
			$item = $retVal->getByName($itemName);
			$retVal->setRenderable($item, true);
		}
		
		return $retVal;
	}
}
