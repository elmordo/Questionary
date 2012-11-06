<?php
class Questionary_AdminController extends Zend_Controller_Action {
	
	public function createAction() {
		
	}
	
	public function editAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("id" => 0), $data);
		
		// nacteni dotazniku
		try {
			$tableQuestionaries = new Questionary_Model_Questionaries();
			$questionary = $tableQuestionaries->find($data["id"])->current();
			
			if (!$questionary) throw new Zend_Exception("Questionary not found");
		} catch (Zend_Exception $e) {
			die($e->getMessage());
			$this->_forward("list");
			return;
		}
		
		$this->view->questionary = $questionary->toClass();
		$this->view->questionaryRow = $questionary;
	}
	
	public function listAction() {
		$tableQuestionaries = new Questionary_Model_Questionaries();
		
		$list = $tableQuestionaries->fetchAll(1, "name");
		
		$this->view->questionaries = $list;
	}
	
	public function postAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("name" => ""), $data);
		
		try {
			if (empty($data["name"])) throw new Zend_Exception("Name must be set");
		} catch (Zend_Exception $e) {
			$this->_forward("create");
			return;
		}
		
		// vytvoreni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionary = $tableQuestionaries->createQuestionary($data["name"]);
		
		// presmerovani na edit
		$this->_redirect("/questionary/admin/edit?questionary[id]=" . $questionary->id	);
	}
	
	public function putJsonAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("id" => 0, "content" => null), $data);
		
		// vyhodnoceni dat
		if (!$data["id"] || is_null($data["content"])) throw new Zend_Exception("Invalid sent data");
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionaryRow = $tableQuestionaries->loadById($data["id"]);
		
		if (!$questionaryRow) throw new Zend_Exception("Questionary #" . $data["id"] . " has not been found");
		
		$questionary = $questionaryRow->toClass();
		$questionary->setFromArray($data["content"]);
		
		$questionaryRow->saveClass($questionary);
		
		// vypnuti layoutu
		$this->view->layout()->disableLayout();
		$this->view->questionary = $questionary;
	}
}
