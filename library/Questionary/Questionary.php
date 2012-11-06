<?php
class Questionary_Questionary {
	
	/**
	 * pole prvku formulare
	 * klicem je jmeno prvku
	 * 
	 * @var array<Questionary_Item_Abstract>
	 */
	private $_itemIndex = array();
	
	/**
	 * seznam vykreslitelnych prvku formulare
	 * prvky jsou v tom poradi, ve kterem se budou vykreslovat
	 *
	 * @var array<Questionary_Item_Abstract>
	 */
	protected $_items = array();
	
	/**
	 * prepinac uzamceni celeho dotazniku
	 * 
	 * @var bool
	 */
	protected $_isLocked = false;
	
	/**
	 * obsahuje jmeno dotazniku
	 * 
	 * @var string
	 */
	protected $_name = "";
	
	/**
	 * vytvori novy prvek formulare
	 * 
	 * @param string $name jmeno instance prvku
	 * @param string $class trida prvku
	 * @param Questionary_Questionary $parent rodicovsky formular
	 */
	protected static function _factory($name, $class, Questionary_Questionary $parent) {
		// sestaveni jmena
		$className = "Questionary_Item_$class";
		
		$item = new $className($parent, $name);
		
		return $item;
	}
	
	/**
	 * prida novy prvek do formulare a vraci ho
	 * 
	 * @param stirng $name jmeno noveho prvku
	 * @param string $class trida prvku
	 * @return Questionary_Item_Abstract
	 */
	public function addItem($name, $class) {
		// kontrola existence jmena
		if (isset($this->_itemIndex[$name])) throw new Questionary_Questionary_Exception("Item named '$name' already exists");
		
		// vytvoreni itemu
		$item = self::_factory($name, $class, $this);
		
		$this->_itemIndex[$name] = $item;
		$this->_items[] = $item;
		
		return $item;
	}
	
	/**
	 * vraci prvek dle jmena
	 * 
	 * @param string $name jmeno prvku
	 * @return Questionary_Item_Abstract
	 * @throws Questionary_Questionary_Exception
	 */
	public function getByName($name) {
		// kontrola existence
		if (!isset($this->_itemIndex[$name])) throw new Questionary_Questionary_Exception("Item named '$name' does not exist");

		return $this->_itemIndex[$name];
	}
	
	/**
	 * vraci index vsech prvku
	 * 
	 * @return array<Questionary_Item_Abstract>  
	 */
	public function getIndex() {
		return $this->_itemIndex;
	}
	
	/**
	 * vraci prvky v poradi, jak se budou vykreslovat
	 * 
	 * @return array<Questionary_Item_Abstract>
	 */
	public function getItems() {
		return $this->_items;
	}
	
	/**
	 * vraci stav uzamceni dotazniku
	 * 
	 * @return bool
	 */
	public function getLocked() {
		return $this->_isLocked;
	}
	
	/**
	 * vraci jmeno dotazniku
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * vraci seznam existujicich jmen prvku
	 * 
	 * @return array<string>
	 */
	public function getNames() {
		return array_keys($this->_itemIndex);
	}
	
	/**
	 * vraci nastaveni vykreslovani prvku
	 * 
	 * @param Questionary_Item_Abstract $item zkoumany prvek
	 * @return bool
	 */
	public function getRenderable(Questionary_Item_Abstract $item) {
		$retVal = false;
		
		foreach ($this->_items as $i) {
			$retVal = $i === $item;
			
			if ($retVal) break;
		}
		
		return $retVal;
	}
	
	/**
	 * odebere prvek z dotazniku
	 * 
	 * @param Questionary_Item_Abstract $item prvek k odebrani
	 * @return Questionary_Questionary
	 */
	public function removeItem(Questionary_Item_Abstract $item) {
		// kontrola existence
		if (!isset($this->_itemIndex[$item->getName()])) throw new Questionary_Questionary_Exception("Item named '" . $item->getName() . "' not found");
		
		// odebrani z indexu
		unset($this->_itemIndex[$item->getName()]);
		
		// odebrani ze seznamu
		$buffer = array();
		
		foreach ($this->_items as $i) {
			if ($i !== $item) {
				$buffer[] = $i;
			}
		}
		
		// zapis zmen
		$this->_items = $buffer;
		
		// detach itemu
		$item->detach();
		
		return $this;
	}
	
	/**
	 * nastavi data dotazniku z pole
	 * 
	 * @param array $data data dotazniku
	 * @return Questionary_Questionary
	 */
	public function setFromArray(array $data) {
		// spojeni s defaultnim polem
		$data = array_merge(array("name" => "", "itemList" => array(), "items" => array(), "isLocked" => 0), $data);
		
		// nastaveni jmena a uzamceni
		$this->_name = $data["name"];
		$this->_isLocked = $data["isLocked"];
		
		// vytvoreni objektu
		$this->_itemIndex = array();
		
		foreach ($data["itemList"] as $item) {
			$this->addItem($item["name"], $item["className"]);
		}
		
		// nastaveni dat
		foreach ($data["itemList"] as $item) {
			$this->_itemIndex[$item["name"]]->setFromArray($item);
		}
		
		// nastaveni renderable
		$this->_items = array();
		
		foreach ($data["items"] as $itemName) {
			$renders[] = $this->_itemIndex[$itemName];
		}
		
		return $this;
	}
	
	/**
	 * nastavi uzamceni dotazniku
	 * 
	 * @param bool $locked novy stav uzamceni
	 * @return Questionary_Questionary
	 */
	public function setLocked($locked) {
		$this->_isLocked = (int) $locked;
		
		return $this;
	}
	
	/**
	 * nastavi nove jmeno dotazniku
	 * 
	 * @param string $name nove jmeno dotazniku
	 * @return Questionary_Questionary
	 */
	public function setName($name) {
		$this->_name = (string) $name;
		
		return $this;
	}
	
	/**
	 * nastavi renderable prvku. Prvek bude pripadne pridan na konec seznamu
	 * 
	 * @param Questionary_Item_Abstract $item prvek, ktery bude nastaven
	 * @param bool $renderable nove nastaveni
	 * @return Questionary_Questionary 
	 */
	public function setRenderable(Questionary_Item_Abstract $item, $renderable) {
		// kontrola, jestli je prvek v dotazniku
		if (!isset($this->_itemIndex[$item->getName()])) throw new Questionary_Questionary_Exception("Item named '" . $item->getName() . "' is not exists");
		
		// odebrani prvku, pokud je zarazen
		$buffer = array();
		
		foreach ($this->_items as $i) {
			if ($i !== $item) $buffer[] = $i;
		}
		
		// zapis na konec seznamu
		$buffer[] = $item;
		
		// prepis hodnot
		$this->_items = $buffer;
		
		return $this;
	}
	
	/**
	 * nastavi nove poradi renderovani
	 * pokud prvek v seznamu neni, nebude se vykreslovat
	 * 
	 * @param array $order poradi jmen prvku k vykresleni
	 * @return Questionary_Questionary
	 */
	public function setOrder(array $order) {
		// kontrola prvku jeslti jsou v dotazniku a jeslti jsou obsazeny pouze jednou
		$index = array();
		
		foreach ($order as $item) {
			// kontrola jeslti je prvek v dotazniku
			if (!isset($this->_itemIndex[$item->getName()])) throw new Questionary_Questionary_Exception("Item named '" . $item->getName() . "' is not exists");
			
			// kontrola unikatnosti v posloupnosti
			if (isset($index[$item->getName()])) throw new Questionary_Questionary_Exception("Item named '" . $item->getName() . "' has duplicate in order");
		}
		
		// nastaveni noveho seznamu
		$this->_items = $order;
	}
	
	public function toArray() {
		// priprava navratove hodnoty
		$retVal = array(
				"name" => $this->_name, 
				"itemList" => array(), 
				"items" => array(),
				"isLocked" => (int) $this->_isLocked
		);
		
		// sestaveni definici
		foreach ($this->_itemIndex as $item) {
			$retVal["itemList"][] = $item->toArray();
		}
		
		// sestaveni seznamu pro renderable
		foreach ($this->_items as $item) {
			$retVal["items"][] = $item->getName();
		}
		
		return $retVal;
	}
}
