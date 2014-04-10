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
		$this->view->key = $this->getRequest()->getParam("key", false);
	}
	
	public function printAction() {
		// nacteni data
		$this->fillAction();
	}
	
	public function saveAction($redirect = true) {
		// nacteni dat
		$data = $this->getRequest()->getParams();
		$data = array_merge(array("questionary-filled-key" => ""), $data);
		
		// nacteni dotazniku
		$tableFilleds = new Application_Model_Filleds();
		$trans = $tableFilleds->find($data["questionary-filled-key"])->current();
		
		if (!$trans) throw new Zend_Exception("Filled values group #" . $data["questionary-filled-key"] . " has not been found");
		
		// nacteni dotazniku
		$filled = $trans->findParentRow("Questionary_Model_Filleds", "filled");
		
		// kontrola uzamceni
		if ($filled->is_locked) {
			// dotaznik je uzamcen
			if ($redirect) {
				$this->_redirect("/client/fill?key=" . $trans->key);
				return;
			} else {
				return;
			}
		}
		
		// vytvoreni instance
		$questionary = $filled->toClass();
		
		// nastaveni dat
		$items = $questionary->getIndex();
		
		foreach ($items as $item) {
			// pokud je prvek odeslan, nastavi se
			if (isset($data[$item->getName()])) {
				$item->fill($data[$item->getName()]);
			} else {
				// kontrola chybnych mezer
				$newName = str_replace(" ", "_", $item->getName());
				
				if (isset($data[$newName])) {
					$item->fill($data[$newName]);
				}
			}
		}
		
		// ulozeni dat
		$filled->saveFilledData($questionary);
		$filled->save();
		
		// presmerovani
		if ($redirect)
			$this->_redirect("/client/fill?key=" . $trans->key);
	}
	
	public function submitAction() {
		// ulozeni dat
		$this->saveAction(false);
		
		// nacteni filled
		$key = $this->getRequest()->getParam("questionary-filled-key", 0);
		$tableFilleds =  new Application_Model_Filleds();
		$filled = $tableFilleds->find($key)->current()->findParentRow("Questionary_Model_Filleds");
		
		// kontrola uzamceni
		if ($filled->is_locked) {
			$this->_redirect("/client/finish");
			return;
		}
		
		// uzamceni dat
		$tableFilledsItems = new Questionary_Model_FilledsItems();
		$nameFilledsItems = $tableFilledsItems->info("name");
		$adapter = $tableFilledsItems->getAdapter();
		
		$sql = "update " . $adapter->quoteIdentifier($nameFilledsItems) . " set `is_locked` = 1 where `filled_id` = " . $adapter->quote($filled->id);
		$adapter->query($sql);
		
		// oznaceni zaznamu jako vyplneneho
		$filled->is_locked = 1;
		$filled->save();
		
		// nacteni ciloveho emailu
		$tableQFilleds = new Questionary_Model_Filleds();
		$tableQuestionaries = new Application_Model_Questionaries();
		
		$nameAFilleds = $tableFilleds->info("name");
		$nameFilleds = $tableQFilleds->info("name");
		$nameAQuestionaries = $tableQuestionaries->info("name");
		
		$sql = "select `$nameAQuestionaries`.emails from `$nameAQuestionaries`, `$nameAFilleds`, `$nameFilleds` where ";
		$sql .= "`$nameAFilleds`.`key` like " . $adapter->quote($key) . " and `$nameAFilleds`.`filled_id` = `$nameFilleds`.`id` and `$nameAQuestionaries`.`questionary_id` = `$nameFilleds`.questionary_id";
		
		$emailCol = $adapter->query($sql)->fetchColumn();
		$emails = explode(",", $emailCol);
		
		// odeslani emailu
		$mailer = new Zend_Mail("utf-8");
		$mailer->setFrom("dotaznik@eskoleni.eu", "System dotazniku");
		
		$mailer->setSubject("Vyplnen dotaznik");
		
		$text = "Byl vyplnen novy dotaznik.\n\nZobrazit ho muzete prechodem na adresu http://questionary.eskoleni.eu/admin/filled?filled[id]=" . $filled->id;
		$text .= "\n\npro zobrazeni musite byt prihlasen jako admin";
		
		$mailer->setBodyText($text);
		
		$validator = new Zend_Validate_EmailAddress();
		$send = false;
		
		foreach ($emails as $email) {
			$email = trim($email);
			
			if ($validator->isValid($email)) {
				$mailer->addTo(trim($email));
				$send = true;
			}
		}
		
		if ($send)
			$mailer->send();
		
		// presmerovani na zobrazeni vyplneneho dotazniku
		// nacteni dat
		$data = $this->getRequest()->getParams();
		$data = array_merge(array("questionary-filled-key" => ""), $data);
		
		$this->_redirect("/client/finish?key=" . $data["questionary-filled-key"]);
	}
}
