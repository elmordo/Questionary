<?php
class ClientController extends Zend_Controller_Action {
	
	public function getAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("key", "");
		
		$tableQuestionaries = new Application_Model_Questionaries();
		$questionary = $tableQuestionaries->fetchRow(
				$tableQuestionaries->getAdapter()->quoteInto("display_key like ?", $data)
		);
		
		if (!$questionary) {
			$this->_forward("index", "index");
			Zend_Registry::set("error", 1);
			return;
		}
		
		// zapsani vyplneni
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->createFilled($questionary->findQuestionaryRow());
		
		$tableFilledsApp = new Application_Model_Filleds();
		$trans = $tableFilledsApp->createKey($filled);
		
		// presmerovani na vyplneni
		$this->_redirect("/client/fill?key=" . $trans->key);
	}
	
	public function fillAction() {
		// nacteni data
		$data = $this->getRequest()->getParam("key", "");
		
		try {
			$tableFilleds = new Application_Model_Filleds();
			$filledTrans = $tableFilleds->find($data)->current();
			
			if (!$filledTrans) throw new Zend_Exception("Filled values group #" . $data["id"] . " has not been found");
			
			$filled = $filledTrans->findParentRow(new Questionary_Model_Filleds, "filled");
			
			// kontrola uzamceni
			// if ($filled->is_locked) throw new Zend_Exception("Filled values group #" . $filled->id . " is locked to edit");
			
			// vytvoreni dotazniku a naplneni dat
			$questionary = $filled->toClass();
		} catch (Zend_Exception $e) {
			// pokracovani probublanu
			throw $e;
		}
		
		$this->view->questionary = $questionary;
		$this->view->filled = $filled;
		$this->view->trans = $filledTrans;
	}
	
	public function finishAction() {
		
	}
	
	public function saveAction($redirect = true) {
		// nacteni dat
		$data = $this->getRequest()->getParams();
		$data = array_merge(array("questionary-filled-id" => 0), $data);
		
		// nacteni dotazniku
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->getById($data["questionary-filled-id"]);
		
		if (!$filled) throw new Zend_Exception("Filled values group #" . $data["questionary-filled-id"] . " has not been found");
		
		// vytvoreni instance
		$questionary = $filled->toClass();
		
		// nastaveni dat
		$items = $questionary->getIndex();
		
		foreach ($items as $item) {
			// pokud je prvek odeslan, nastavi se
			if (isset($data[$item->getName()])) {
				$item->fill($data[$item->getName()]);
			}
		}
		
		// ulozeni dat
		$filled->saveFilledData($questionary);
		$filled->save();
		
		// presmerovani
		if ($redirect)
			$this->_redirect("/client/fill?filled[id]=" . $filled->id);
	}
	
	public function submitAction() {
		// ulozeni dat
		$this->saveAction(false);
		
		// nacteni filled
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->getById($this->getRequest()->getParam("questionary-filled-id", 0));
		
		// uzamceni dat
		$tableFilledsItems = new Questionary_Model_FilledsItems();
		$nameFilledsItems = $tableFilledsItems->info("name");
		$adapter = $tableFilledsItems->getAdapter();
		
		$sql = "update " . $adapter->quoteIdentifier($nameFilledsItems) . " set `is_locked` = 1 where `filled_id` = " . $adapter->quote($filled->id);
		$adapter->query($sql);
		
		// oznaceni zaznamu jako vyplneneho
		$filled->is_locked = 1;
		$filled->save();
		
		// presmerovani na zobrazeni vyplneneho dotazniku
		$this->_redirect("/client/finish");
	}
}
