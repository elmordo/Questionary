<?php
class AdminController extends Zend_Controller_Action {
	
	/**
	 * @var Application_Model_Row_User
	 */
	protected $_user = null;
	
	/**
	 * @var Application_Model_Users
	 */
	protected $_tableUsers;
	
	/**
	 * @var Application_Model_Questionaries
	 */
	protected $_tableQuestionaries;
	
	/**
	 * @var Zend_Session_Namespace
	 */
	protected $_session;
	
	/**
	 * inicializuje instanci
	 */
	public function init() {
		// priprava tabulek
		$this->_tableQuestionaries = new Application_Model_Questionaries;
		$this->_tableUsers = new Application_Model_Users;
		
		// kontrola prihlaseneho uzivatele
		$session = new Zend_Session_Namespace("admin");
		$this->_session = $session;
		
		if ($session->user_id) {
			// nacteni a zapis uzivatele
			$this->_user = $this->_tableUsers->find($session->user_id)->current();
		}
	}
	
	/**
	 * smaze dotaznik
	 */
	public function deleteqAction() {
		
	}
	
	/**
	 * smaze uzivatele
	 */
	public function deleteuserAction() {
		
	}
	
	/**
	 * editace sablony dotazniku
	 */
	public function editqAction() {
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
		
		$info = $this->_tableQuestionaries->find($questionary->id)->current();
		
		$this->view->questionary = $questionary->toClass();
		$this->view->questionaryRow = $questionary;
		$this->view->info = $info;
	}
	
	/**
	 * zobrazi vyplneny dotaznik
	 */
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
			// if (!$filled->is_locked) throw new Zend_Exception("Filled values group #$filled->id is not locked to edit");
		} catch (Zend_Exception $e) {
			throw $e;
		}
		
		$questionary = $filled->toClass();
		$questionary->setLocked(true);
		
		$this->view->questionary = $questionary;
		$this->view->filled = $filled;
	}
	
	/**
	 * vypis vyplnenych dotazniku jednoho druhu
	 */
	public function filledlistAction() {
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("id" => 0), $data);
		
		// kontrola dat
		$quesionary = null;
		
		try {
			$tableQuestionaries = new Questionary_Model_Questionaries();
			$quesionary = $tableQuestionaries->find($data["id"])->current();
			
			if (!$quesionary) throw new Zend_Exception();
		} catch (Zend_Exception $e) {
			$this->_forward("qlist");
			return;
		}
		
		// nalezeni vyplnenych dotazniku
		$tableFilleds = new Questionary_Model_Filleds();
		$filleds = $quesionary->findDependentRowset($tableFilleds, "questionary", $tableFilleds->select()->where("is_locked")->order("filled_at desc"));
		
		$this->view->questionary = $quesionary;
		$this->view->filleds = $filleds;
	}
	
	/**
	 * vypise informace o uzivateli
	 */
	public function getuserAction() {
		
	}
	
	/**
	 * vypis menu
	 */
	public function indexAction() {
		
	}
	
	/**
	 * vypis formulare pro login
	 */
	public function loginAction() {
		
	}
	
	/**
	 * vytvori novy dotaznik
	 */
	public function postqAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("name" => ""), $data);
		
		// kontrola dat
		try {
			if (empty($data["name"])) throw new Zend_Exception("Name is empty");
		} catch (Zend_Exception $e) {
			$this->_forward("index");
			return;
		}
		
		// vytvoreni dotazniku
		$q = $this->_tableQuestionaries->createQuestionary($this->_user, $data["name"]);
		
		// presmerovani
		$info = $q["info"];
		
		$this->_redirect("/admin/editq?questionary[id]=" . $info->questionary_id);
	}
	
	/**
	 * vytvori uzivatele
	 */
	public function postuserAction() {
		
	}
	
	/**
	 * ulozi dotaznik
	 */
	public function putqJsonAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("questionary", array());
		$data = array_merge(array("id" => 0, "content" => null, "is_published" => 0, "is_visible" => 0, "is_editable" => 0), $data);
		
		// vyhodnoceni dat
		if (!$data["id"] || is_null($data["content"])) throw new Zend_Exception("Invalid sent data");
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionaryRow = $tableQuestionaries->loadById($data["id"]);
		
		if (!$questionaryRow) throw new Zend_Exception("Questionary #" . $data["id"] . " has not been found");
		
		$questionary = $questionaryRow->toClass();
		$questionary->setFromArray($data["content"]);
		
		$questionaryRow->saveClass($questionary);
		
		// aktualizace dat informaci
		$info = $this->_tableQuestionaries->find($questionaryRow->id)->current();
		
		$info->is_visible = $data["is_visible"];
		$info->is_published = $data["is_published"];
		$info->is_editable = $data["is_editable"];
		
		$info->save();
		
		// vypnuti layoutu
		$this->view->layout()->disableLayout();
		$this->view->questionary = $questionary;
	}
	
	/**
	 * updatuje uzivaetle
	 */
	public function putuserAction() {
		
	}
	
	/**
	 * vypis sablon dotazniku
	 */
	public function qlistAction() {
		// nacteni vlastnich dotazniku
		$ownQ = $this->_user->findQuestionaries();
		
		// nacteni editable ostatnich
		$where = array(
			"is_editable",
			"user_id != " . $this->_user->id
		);
		
		$otherQ = $this->_tableQuestionaries->fetchAll($where);
		
		$this->view->own = $ownQ;
		$this->view->other = $otherQ;
	}
	
	/**
	 * zalogovani uzivatele
	 */
	public function signinAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("user", array());
		$data = array_merge(array("login" => "", "password" => ""), $data);
		
		// kontrola dat
		$user = null;
		try {
			$user = $this->_tableUsers->findByLogin($data["login"]);
			
			// kontrola nalezeni
			if (!$user) throw new Zend_Exception("User not found");
			
			// kontrola hesla
			if (!$user->checkPassword($data["password"])) throw new Zend_Exception("Incorect password");
		} catch (Zend_Exception $e) {
			die($e->getMessage());
			$this->_forward("login");
			return;
		}
		
		// nastaveni uzivatele
		$this->_session->user_id = $user->id;
		
		// presmerovani na index
		$this->_redirect("/admin/index");
	}
	
	/**
	 * odhlaseni uzivatele
	 */
	public function signoutAction() {
		// odebrani informaci o uzivatelu
		$this->_session->user_id = null;
		
		// presmerovani na login
		$this->_redirect("/admin/login");
	}
	
	/*
	 * vyhodnoceni pristupove pravo
	 */
	 public function preDispatch() {
	 	// vyhodnoceni akci a podobne
	 	if (!$this->_user) {
	 		// uzivatel neni prihlasen - zkontroluje se akce
	 		$action = strtolower($this->getRequest()->getActionName());
			
	 		switch ($action) {
	 			case "login":
	 			case "signin":
					// verejne akce
					break;
					
				default:
					// zobrazi se login
					$this->getRequest()->setActionName("login");
	 		}
	 	}
	 }
}
