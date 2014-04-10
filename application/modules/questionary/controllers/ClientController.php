<?php
class Questionary_ClientController extends Zend_Controller_Action {

	/**
	 * obsahuje konfiguraci nactenou z ini souboru
	 * @var Array
	 */
	private $_config=null;

	/**
	 * pripravi instanci k pouziti
	 */
	public function init() {
		// nacteni konfigurace
		$config = Zend_Controller_Front::getInstance()->getParam("bootstrap")->getApplication()->getOption("questionary");

		$this->_config = array_merge(array("callback" => array()), $config);
	}

	public function deleteAction() {
		// nacteni dat
		$data = $this->_request->getParam("filled", array());
		$data = array_merge(array("id" => 0), $data);

		// nacteni informaci z databaze
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->find($data["id"])->current();

		if (!$filled) throw new Exception("Filled questionary was not found", 1);

		// provedeni callbacku
		$filledClass = $filled->toClass();
		$this->_doCallback("delete", $filledClass, $filled, array());

		$filled->delete();
	}
	
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
		
		// zapsani vyplneni a prevod na objektovou reprezentaci
		$tableFilleds = new Questionary_Model_Filleds();
		$filled = $tableFilleds->createFilled($questionary);
		$filledClass = $filled->toClass();

		// provedeni call backu
		$this->_doCallback("get", $filledClass, $filled, $this->_request->getParam("params", array()));

		// ulozeni zmenenych hodnot
		$filled->saveFilledData($filledClass);

		// presmerovani na vyplneni
		$this->view->filled = $filled;
		$this->view->params = (array) $this->_request->getParam("params", array());
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
			
			// provedeni callbacku
			$this->_doCallback("fill", $questionary);
		} catch (Zend_Exception $e) {
			// pokracovani probublanu
			throw $e;
		}

		// nastaveni hodnot tlacitek
		$config = array_merge(array("button" => array()), $this->_config);
		$buttons = array_merge(array("submit" => 1, "save" => 1, "reset" => 1), $config["button"]);
		
		$this->view->questionary = $questionary;
		$this->view->filled = $filled;
		$this->view->questionary = $questionary;
		$this->view->params = (array) $this->_request->getParam("params", array());
		$this->view->buttons = $buttons;
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

			$questionary = $filled->toClass();

		} catch (Zend_Exception $e) {
			throw $e;
		}
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

		$this->_doCallback("save", $questionary, $filled, (array) $this->_request->getParam("params"));
		
		// ulozeni dat
		$filled->saveFilledData($questionary);
		$filled->save();
		
		// presmerovani
		$this->view->filled = $filled;
		$this->view->params = (array) $this->_request->getParam("params", array());
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

	protected function _doCallback($callback, $questionary, $filled=null, array $params = array()) {
		// provedeni callbacku
		if (isset($this->_config["callback"][$callback])) {
			$className = $this->_config["callback"][$callback];
			$callback = new $className();

			$callback->callback($questionary, $filled, $params);
		}
	}
}