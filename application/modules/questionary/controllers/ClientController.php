<?php
class Questionary_ClientController extends Zend_Controller_Action {
	
	public function listAction() {
		// nacteni seznamu dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		
		$questionaries = $tableQuestionaries->fetchAll(null, "name");
		
		$this->view->questionaries = $questionaries;
	}
	
	public function getAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("id" => 0), $data);
		
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionary = $tableQuestionaries->find($data["id"])->current();
		
		if (!$questionary) throw new Zend_Exception("Questionary id " . $data["id"] . " not found");
		
		// zapsani vyplneni
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->createFilled($questionary);
		
		// presmerovani na vyplneni
		$this->_redirect("/questionary/client/fill?filled[id]=" . $filled->id);
	}
	
	public function fillAction() {
		// nacteni data
		$data = $this->getRequest()->getParam("filled", array());
		$data = array_merge(array("id" => 0), $data);
		
		try {
			$tableFilleds = new Questionary_Model_Filleds();
			$filled = $tableFilleds->getById($data["id"]);
			
			if (!$filled) throw new Zend_Exception("Filled values group #" . $data["id"] . " has not been found");
			
			// kontrola uzamceni
			if ($filled->is_locked) throw new Zend_Exception("Filled values group #" . $filled->id . " is locked to edit");
			
			// vytvoreni dotazniku a naplneni dat
			$questionary = $filled->toClass();
		} catch (Zend_Exception $e) {
			// pokracovani probublanu
			throw $e;
		}
		
		$this->view->questionary = $questionary;
		$this->view->filled = $filled;
	}
	
	public function filledAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("filled", array());
		$data = array_merge(array("id" => 0), $data);
		
		// kontrola dat
		try {
			$tableFilleds = new Questionary_Model_Filleds();
			$filled = $tableFilleds->getById($data["id"]);
			
			if (!$filled) throw new Zend_Exception("Filled values group #" . $data["id"] . " has not been found");
			
			// kontrola zamceni
			if (!$filled->is_locked) throw new Zend_Exception("Filled values group #$filled->id is not locked to edit");
		} catch (Zend_Exception $e) {
			throw $e;
		}
		
		$questionary = $filled->toClass();
		
		$this->view->questionary = $questionary;
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
			$this->_redirect("/questionary/client/fill?filled[id]=" . $filled->id);
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
		$this->_redirect("/questionary/client/filled?filled[id]=" . $filled->id);
	}
}