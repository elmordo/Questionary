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
		
	}
	
	/**
	 * zobrazi vyplneny dotaznik
	 */
	public function filledAction() {
		
	}
	
	/**
	 * vypis vyplnenych dotazniku jednoho druhu
	 */
	public function filledlistAction() {
		
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
