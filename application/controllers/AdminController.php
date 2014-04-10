<?php
class AdminController extends Zend_Controller_Action {
	
	const ACTION_NEW = "N";
	const ACTION_DELETE = "D";
	const ACTION_MODIFY = "M";
	const ACTION_MOVE = "V";
	const ACTION_VISIBILITY = "B";
	
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
	 * zapise delta balicek do formulare
	 */
	public function addJsonAction() {
		$questionaryId = $this->getRequest()->getParam("questionaryId", 0);
		$data = (array) $this->getRequest()->getParam("data", array());
		$data = array_merge(array("action" => "", "obj" => null, "params" => "null"), $data);
		$data["params"] = Zend_Json::decode($data["params"]);
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionary = $tableQuestionaries->find($questionaryId)->current();
		if (!$questionary) throw new Zend_Exception("Questionary #$questionaryId has not been found");
		
		$tableItems = new Questionary_Model_QuestionariesItems();
		$adapter = $tableItems->getAdapter();
		
		switch ($data["action"]) {
		case self::ACTION_VISIBILITY:
			$this->doVisibility($questionary, $data);
			break;
			
		case self::ACTION_MOVE:
			$this->doMove($questionary, $data);
			break;
			
		case self::ACTION_MODIFY:
			$this->doModify($questionary, $data);
			break;
			
		case self::ACTION_DELETE:
			$this->doDelete($questionary, $data);
			break;
			
		case self::ACTION_NEW:
			$this->doNew($questionary, $data);
			break;
		}
	}
	
	public function cloneAction() {
		// nacteni dat
		$data = $this->getRequest()->getParam("clone", array());
		$data = array_merge(array("id" => 0, "name" => ""), $data);
		
		if (!$data["id"] || !$data["name"]) {
			$this->_forward("qlist");
			return;
		}
		
		// kontrola existence dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$tableItems = new Questionary_Model_QuestionariesItems();
		$tableRenderables = new Questionary_Model_QuestionariesRenderables();
		$tableAQuestionaries = new Application_Model_Questionaries();
		
		$questionary = $tableQuestionaries->find($data["id"])->current();
		if (!$questionary) {
			$this->_forward("qlist");
			return;
		}
		
		// dotaznik existuje - provede se klonovani
		$clone = $tableQuestionaries->createQuestionary($data["name"]);
		$nameItems = $tableItems->info("name");
		$nameRenderables = $tableRenderables->info("name");
		$nameAQuestionaries = $tableAQuestionaries->info("name");
		
		// vygenerovani klice
		$key = time() . microtime(false);
		$adapter = $tableQuestionaries->getAdapter();
		
		do {
			$key = sha1($key . microtime(false));
			$key = substr($key, 0, 8);
			
			$row = $tableAQuestionaries->fetchRow("display_key like " . $adapter->quote($key));
		} while ($row);
		
		// zapis do application_questionaries
		$sql = "insert into $nameAQuestionaries (questionary_id, user_id, display_key, emails, caption, subcaption, is_visible, is_editable)";
		$sql .= " select $clone->id, user_id, '$key', emails, caption, subcaption, is_visible, is_editable from `$nameAQuestionaries` where questionary_id = " . $questionary->id;
		$adapter->query($sql);
		
		$sql = "insert into `$nameItems` (questionary_id, `class`, `name`, `label`, `default`, `params`, `is_locked`) select $clone->id, `class`, `name`, `label`, `default`, `params`, `is_locked` from `$nameItems` where questionary_id = " . $questionary->id;
		$adapter->query($sql);
		
		$sql = "insert into `$nameRenderables` (questionary_id, item_id, position)";
		$sql .= " select $clone->id, t2.id, position from `$nameRenderables`, `$nameItems` as t1, `$nameItems` as t2 ";
		$sql .= " where t1.questionary_id = $questionary->id and t2.questionary_id = $clone->id and t1.name like t2.name and item_id = t1.id";
		$adapter->query($sql);
		
		$this->_redirect("/admin/editq?questionary[id]=" . $clone->id);
	}
	
	/**
	 * smaze vyplneny dotaznik
	 */
	public function deletefAction() {
		$data = $this->getRequest()->getParam("delete", array());
		$data = array_merge(array("id" => 0), $data);
		
		$tableFilleds = new Questionary_Model_Filleds();
		$item = $tableFilleds->find($data["id"])->current();
		
		$questionaryId = $item->questionary_id;
		$item->delete();
		
		$this->_redirect("/admin/filledlist?questionary[id]=" . $questionaryId);
	}
	
	public function deleteqAction() {
		$data = (array) $this->getRequest()->getParam("delete", array());
		$data = array_merge(array("id" => 0, "confirm" => 0), $data);
		
		// kontrola dat
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionary = $tableQuestionaries->find($data["id"])->current();
		
		if (!$data["confirm"] || !$questionary) {
			$this->_forward("editq");
			return;
		}
		
		// smazani dotanziku a vsech jeho zavislosti
		$questionary->delete();
		
		$this->_redirect("/admin/qlist");
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
		
		// nactnei radku s informacemi o vyplennem dotazniku
		$filledRow = $filled->findDependentRowset("Application_Model_Filleds", "filled")->current();
		
		$this->view->questionary = $questionary;
		$this->view->filled = $filled;
		$this->view->filledRow = $filledRow;
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
			
			$info = $this->_tableQuestionaries->find($quesionary->id)->current();
		} catch (Zend_Exception $e) {
			$this->_forward("qlist");
			return;
		}
		
		// nalezeni vyplnenych dotazniku
		$tableFilleds = new Questionary_Model_Filleds();
		$filleds = $quesionary->findDependentRowset($tableFilleds, "questionary", $tableFilleds->select()->where("is_locked")->order("filled_at desc"));
		
		// nalezeni captionu a subcaption
		$tableFilledsItems = new Questionary_Model_FilledsItems();
		$filledIds = array(0);
		
		foreach ($filleds as $filled) {
			$filledIds[] = $filled->id;
		}
		
		$adapter = $tableFilledsItems->getAdapter();
		
		$where = array(
				$adapter->quoteInto("filled_id in (?)", $filledIds),
				"(name like " . $adapter->quote($info->caption) . " or name like " . $adapter->quote($info->subcaption) . ")"
		);
		
		$captions = $tableFilledsItems->fetchAll($where);
		$captionIndex = array();
		
		foreach ($captions as $item) {
			$captionIndex[$item->filled_id][$item->name] = $item;
		}
		
		$this->view->questionary = $quesionary;
		$this->view->filleds = $filleds;
		$this->view->captionIndex = $captionIndex;
		$this->view->info = $info;
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
		if (!$data["id"]) throw new Zend_Exception("Invalid sent data");
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionaryRow = $tableQuestionaries->loadById($data["id"]);
		
		if (!$questionaryRow) throw new Zend_Exception("Questionary #" . $data["id"] . " has not been found");
		
		// aktualizace dat informaci
		$info = $this->_tableQuestionaries->find($questionaryRow->id)->current();
		
		unset($data["id"]);
		$info->setFromArray($data);
		$questionaryRow->setFromArray($data);
		
		$info->save();
		$questionaryRow->save();
		
		// vypnuti layoutu
		$this->view->layout()->disableLayout();
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
	
	public function revertJsonAction() {
		$questionaryId = $this->getRequest()->getParam("questionaryId", 0);
		$data = (array) $this->getRequest()->getParam("data", array());
		$data = array_merge(array("action" => "", "obj" => null, "params" => "null"), $data);
		$data["params"] = Zend_Json::decode($data["params"]);
		
		// nacteni dotazniku
		$tableQuestionaries = new Questionary_Model_Questionaries();
		$questionary = $tableQuestionaries->find($questionaryId)->current();
		if (!$questionary) throw new Zend_Exception("Questionary #$questionaryId has not been found");
		
		$tableItems = new Questionary_Model_QuestionariesItems();
		$adapter = $tableItems->getAdapter();
		
		switch ($data["action"]) {
			case self::ACTION_VISIBILITY:
				$this->revertVisibility($questionary, $data);
				break;
					
			case self::ACTION_MOVE:
				$this->revertMove($questionary, $data);
				break;
					
			case self::ACTION_MODIFY:
				$this->revertModify($questionary, $data);
				break;
					
			case self::ACTION_DELETE:
				$this->revertDelete($questionary, $data);
				break;
					
			case self::ACTION_NEW:
				$this->revertNew($questionary, $data);
				break;
		}
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
	 
	 /**
	  * nacte prvek dle jmena
	  * 
	  * @return Zend_Db_Table_Row_Abstract
	  */
	 public function getItemByName($questionary, $name) {
	 	$tableItems = new Questionary_Model_QuestionariesItems();
	 	
	 	$where = array(
	 			"questionary_id = " . $questionary->id,
	 			"name like " . $tableItems->getAdapter()->quote($name)
	 	);
	 	
	 	$item = $tableItems->fetchRow($where);
	 	if (!$item) throw new Zend_Exception("Item named $name has not been found");
	 	
	 	return $item;
	 }
	 
	 /**
	  * zapise delta balicek viditelnosti
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function doVisibility($questionary, $data) {
	 	// nacteni prvku
	 	$item = $this->getItemByName($questionary, $data["obj"]);
	 	
	 	// pokud je definovan kontejner, nacte se
	 	$params = $data["params"];
	 	if (!isset($params["container"])) $params["container"] = false;
	 	
	 	if ($params["container"]) {
	 		$container = $this->getItemByName($questionary, $params["container"]);
	 	} else {
	 		$container = false;
	 	}
	 	
	 	// vyhodnoceni skryti/odkryti
	 	if ($params["newStatus"]) {
	 		// prvek je odkryt
	 		if ($container) {
	 			// tento stav u tlacitka vpred nenastane
	 			// ale metoda je volana i tlacitkem zpet, kde stav nastat muze
	 			$contParams = unserialize($container->params);
	 			$newItems = array();
	 			$items = (array) $contParams["items"];
	 			
	 			if ($params["prev"]) {
	 				foreach ($items as $contItem) {
	 					$newItems[] = $contItem;
	 					
	 					if ($contItem == $params["prev"])
	 						$newItems[] = $item->name;
	 				}
	 			} else {
	 				$newItems = array_merge(array($item->name), $items);
	 			}
	 			
	 			$contParams["items"] = $newItems;
	 			$container->params = serialize($contParams);
	 			$container->save();
	 		} else {
	 			// prvek se prida na konec dotazniku
	 			$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 			$last = $tableRenderables->fetchRow("questionary_id = $questionary->id", "position desc");
	 			
	 			$insert = array(
	 					"questionary_id" => $questionary->id,
	 					"item_id" => $item->id
	 			);
	 			
	 			$insert["position"] = $last ? ($last->position + 1) : 0;
	 			
	 			$tableRenderables->insert($insert);
	 		}
	 	} else {
	 		// prvek je skryt
	 		if ($container) {
	 			$containerData = unserialize($container->params);
	 			
	 			$newItems = array();
	 			
	 			foreach ($containerData["items"] as $value) {
	 				if ($value != $item->name) {
	 					$newItems[] = $value;
	 				}
	 			}
	 			
	 			$containerData["items"] = $newItems;
	 			$container->params = serialize($containerData);
	 			$container->save();
	 		} else {
	 			// prvek se jen smaze z renderables
	 			$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 			
	 			$where = array(
	 					"questionary_id = " . $questionary->id,
	 					"item_id = ". $item->id
	 			);
	 			
	 			$renderable = $tableRenderables->fetchRow($where);
	 			
	 			if (!$renderable) return;
	 			
	 			// zaznam o vykresleni byl nalezen - pokracuje se posunem prvku o jednicku
	 			$tableRenderables->update(array("position" => new Zend_Db_Expr("position - 1")), array("questionary_id = $questionary->id", "position > $renderable->position"));
	 			$renderable->delete();
	 		}
	 	}
	 }
	 
	 /**
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 function revertVisibility($questionary, $data) {
	 	$data["params"]["newStatus"] = !$data["params"]["newStatus"];
	 	$this->doVisibility($questionary, $data);
	 }
	 
	 /**
	  * zapise delta balicek zmeny poradi
	  */
	 function doMove($questionary, $data) {
	 	$params = $data["params"];
	 	$params = array_merge(array("itemsNew" => array(), "itemsOld" => array()), $params);
	 	
	 	// pokud neni nic k razeni, ukonci se beh
	 	if (!$params["itemsNew"]) return;
	 	
	 	// vyhodnoceni jestli se jedna o kontejner
	 	if ($data["obj"]) {
	 		// jedna se o kontejner
	 		$container = $this->getItemByName($questionary, $data["obj"]);
	 		
	 		//zapis novych hodnot
	 		$order = array(
	 				"items" => $params["itemsNew"]
	 		);
	 		
	 		$container->params = serialize($order);
	 		$container->save();
	 	} else {
	 		// nejdena se o kontejner a musi se nacist prvky
	 		$tableItems = new Questionary_Model_QuestionariesItems();
	 		$where = array(
	 				"questionary_id = " . $questionary->id,
	 				$tableItems->getAdapter()->quoteInto("name in (?)", $params["itemsNew"])
	 		);
	 		
	 		$items = $tableItems->fetchAll($where);
	 		
	 		// indexace
	 		$itemIndex = array();
	 		
	 		foreach ($items as $item) {
	 			$itemIndex[$item->name] = $item->id;
	 		}
	 		
	 		unset($items);
	 		
	 		// priprava dotazu
	 		$insert = array();
	 		$position = 0;
	 		
	 		foreach ($params["itemsNew"] as $name) {
	 			if (isset($itemIndex[$name])) {
	 				$insert[] = "($questionary->id, " . $itemIndex[$name] . ", $position)";
	 				$position++;
	 			}
	 		}
	 		
	 		// zapis dat
	 		$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 		$tableRenderables->delete("questionary_id = " . $questionary->id);
	 		
	 		if ($insert) {
	 			$sql = "insert into `" . $tableRenderables->info("name") . "` (questionary_id, item_id, position) values ";
	 			$sql .= implode(",", $insert);
	 			
	 			$tableRenderables->getAdapter()->query($sql);
	 		}
	 	}
	 }
	 
	 /**
	  * vrati ucinek presunu prvku
	  */
	 function revertMove($questionary, $data) {
	 	$data["params"]["itemsNew"] = $data["params"]["itemsOld"];
	 	$this->doMove($questionary, $data);
	 }
	 
	 /**
	  * provede modifikaci atributu prvku
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function doModify($questionary, $data) {
	 	// nacteni prvku
	 	$item = $this->getItemByName($questionary, $data["obj"]);
	 	
	 	$params = $data["params"]["settingsNew"];
	 	$params = array_merge(array("label" => "", "default" => "", "params" => array(), "isLocked" => 0), $params);
	 	
	 	// nastaveni parametru
	 	$item->label = $params["label"];
	 	$item->default = $params["default"];
	 	$item->is_locked = $params["isLocked"];
	 	$item->params = serialize($params["params"]);
	 	
	 	$item->save();
	 	
	 	// smazani dat z renderables
	 	if (isset($params["params"]["items"]) && $params["params"]["items"]) {
	 		$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 		$tableItems = new Questionary_Model_QuestionariesItems();
	 		
	 		$where = array(
	 				"questionary_id = $questionary->id",
	 				"item_id in (select id from " . $tableItems->info("name") . " where questionary_id = $questionary->id and name in (" . $tableItems->getAdapter()->quote($params["params"]["items"]) . "))"
	 		);
	 		
	 		$tableRenderables->delete($where);
	 	}
	 }
	 
	 /**
	  * vrati modifikaci prvku
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 function revertModify($questionary, $data) {
	 	// prohozeni old a new settings a zavilani do Modify
	 	$data["params"]["settingsNew"] = $data["params"]["settingsOld"];
	 	$this->doModify($questionary, $data);
	 }
	 
	 /**
	  * smaze prvek
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function doDelete($questionary, $data) {
	 	// nacteni a smazani itemu
	 	$item = $this->getItemByName($questionary, $data["obj"]);
	 	
	 	// kontrola kontejneru
	 	if ($data["params"]["container"]) {
	 		// odebrani elementu z kontejneru
	 		$container = $this->getItemByName($questionary, $data["params"]["container"]);
	 		
	 		$params = unserialize($container->params);
	 		$items = array();
	 		
	 		foreach ($params["items"] as $val) {
	 			if ($val != $item->name) $items[] = $val;
	 		}
	 		
	 		$params["items"] = $items;
	 		$container->params = serialize($params);
	 		$container->save();
	 		
	 	}
	 	
	 	$item->delete();
	 }
	 
	 /**
	  * vrati smazani prvku
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function revertDelete($questionary, $data) {
	 	$params = $data["params"];
	 	$params = array_merge(array("prev" => null, "container" => null, "settings" => array()), $params);
	 	
	 	// vytvoreni prvku
	 	$tableItems = new Questionary_Model_QuestionariesItems();
	 	$st = $params["settings"];
	 	
	 	$item = $tableItems->createRow(array(
	 			"questionary_id" => $questionary->id,
	 			"name" => $st["name"],
	 			"class" => $st["className"],
	 			"default" => $st["default"],
	 			"is_locked" => $st["isLocked"],
	 			"label" => $st["label"],
	 			"params" => serialize($st["params"])
	 	));
	 	
	 	$item->save();
	 	
	 	// vyhodnoceni, jestli prvek patri do kontejneru nebo do dotazniku
	 	if ($params["container"]) {
	 		// nacteni kontejneru
	 		$container = $this->getItemByName($questionary, $params["container"]);
	 		$contParams = unserialize($container->params);
	 		
	 		$newItems = array();
	 		
	 		if (!$params["prev"]) {
	 			$newItems = array_merge(array($item->name), $contParams["items"]);
	 		} else {
	 			foreach ($contParams["items"] as $itemName) {
	 				$newItems[] = $itemName;
	 				
	 				if ($itemName == $params["prev"]) 
	 					$newItems[] = $item->name;
	 			}
	 		}
	 		
	 		$contParams["items"] = $newItems;
	 		
	 		$container->params = serialize($contParams);
	 		$container->save();
	 	} else {
	 		$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 		
	 		// prvek je z dotazniku
	 		if ($params["prev"]) {
	 			// prvek ma predchudce
	 			$prev = $this->getItemByName($questionary, $params["prev"]);
	 			$prevItem = $tableRenderables->fetchRow("item_id = " . $prev->id);
	 			
	 			$where = array(
	 					"questionary_id = " . $questionary->id,
	 					"position > " . $prevItem->position
	 			);
	 			
	 			$tableRenderables->update(array("position" => new Zend_Db_Expr("position + 1")), $where);
	 			
	 			// zapis do renderables
	 			$tableRenderables->insert(array(
	 					"questionary_id" => $questionary->id,
	 					"item_id" => $item->id,
	 					"position" => $prevItem->position + 1
	 			));
	 		} else {
	 			// prvek je prvni
	 			$tableRenderables->update(array("position" => new Zend_Db_Expr("position + 1")), array("questionary_id = " . $questionary->id));
	 		}
	 	}
	 }
	 
	 /**
	  * vytvori novy prvek
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function doNew($questionary, $data) {
	 	// nacteni posledniho zaznamu z databaze
	 	$tableRenderables = new Questionary_Model_QuestionariesRenderables();
	 	$lastItem = $tableRenderables->fetchRow("questionary_id = $questionary->id", "position desc");
	 	$tableItems = new Questionary_Model_QuestionariesItems();
	 	
	 	$params = $data["params"]["item"];
	 	
	 	$item = $tableItems->createRow(array(
	 			"name" => $data["obj"],
	 			"questionary_id" => $questionary->id,
	 			"is_locked" => $params["isLocked"],
	 			"class" => $params["className"],
	 			"default" => $params["default"],
	 			"label" => $params["label"],
	 			"params" => serialize($params["params"])
	 	));
	 	
	 	$item->save();
	 	
	 	// zapsani na konec renderable
	 	$insert = array(
	 			"questionary_id" => $questionary->id,
	 			"item_id" => $item->id
	 	);
	 	
	 	$insert["position"] = $lastItem ? ($lastItem->position + 1) : 0;
	 	$tableRenderables->insert($insert);
	 }
	 
	 /**
	  * vraci vytvoreni noveho prvku
	  * 
	  * @param unknown_type $questionary
	  * @param unknown_type $data
	  */
	 public function revertNew($questionary, $data) {
	 	$item = $this->getItemByName($questionary, $data["obj"]);
	 	
	 	// protoze je prvek ve stavu tesne po pridani, nemuze byt clenem zadne skupiny a mozeme ho smazat
	 	$item->delete();
	 }
}
