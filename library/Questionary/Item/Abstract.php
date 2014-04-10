<?php
abstract class Questionary_Item_Abstract {
	
	/**
	 * kontejner skupiny, pokud je prvek zanoren
	 * jinak je null
	 * 
	 * @var Questionary_Item_Container
	 */
	private $_container = null;
	
	/**
	 * vychozi hodnota sloupce
	 * 
	 * @var mixed
	 */
	private $_default = null;
	
	/**
	 * vyplnena hodnota
	 * 
	 * @var mixed
	 */
	private $_filledVal = null;
	
	/**
	 * stavova hodnota vyplneni dat
	 * 
	 * @var bool
	 */
	private $_isFilled = false;
	
	/**
	 * prepinac uzamceni prvku
	 * 
	 * @var bool
	 */
	private $_isLocked = false;
	
	/**
	 * popisek prvku
	 * 
	 * @var string
	 */
	private $_label = "";
	
	/**
	 * jmeno prvku
	 * @var stirng
	 */
	private $_name;
	
	/**
	 * rodicovsky dotaznik
	 * 
	 * @var Questionary_Questionary
	 */
	private $_questionary;
	
	/**
	 * konstruktor objektu ktery nastavi rodicovsky dotaznik
	 * 
	 * @param Questionary_Questionary $questionary
	 * @param string $name jmeno prvku
	 */
	public function __construct(Questionary_Questionary $questionary, $name) {
		$this->_questionary = $questionary;
		$this->_name = $name;
	}
	
	/**
	 * vycisti fyplnena data a nastavi _isFilled = flase
	 * 
	 * @return Questionary_Item_Abstract
	 */
	public function clearData() {
		$this->_filledVal = null;
		$this->_isFilled = false;
		
		return $this;
	}
	
	/**
	 * odebere prvek z dotazniku
	 */
	public function detach() {
		// kontrola jeslti je prvek v kontejneru
		if (!$this->_questionary) throw new Questionary_Item_Exception("Item is not in questionary");
		
		try {
			$this->_questionary->removeItem($this);
		} catch (Questionary_Questionary_Exception $e) {
			
		}
		
		$this->_questionary = null;
	}
	
	/**
	 * naplni data od uzivatele
	 * prepne $_isFilled = true
	 * 
	 * @param mixed $data nova data
	 * @return Questionary_Item_Abstract
	 */
	public function fill($data) {
		$this->_filledVal = $data;
		$this->_isFilled = true;
		
		return $this;
	}
	
	/**
	 * vraci vyplnena data
	 * 
	 * @return mixed
	 */
	public function filledData() {
		// kontrola vyplneni
		if (!$this->_isFilled) throw new Questionary_Item_Exception("Item is not filled");
		
		return $this->_filledVal;
	}
	
	/**
	 * vraci jmeno tridy prvku (bez prefixu)
	 * 
	 * @return string
	 */
	public function getClassName() {
		// zjisteni jmena tridy
		$fullName = get_class($this);
		
		list($prefix, $item, $className) = explode("_", $fullName, 3);
		
		return $className;
	}
	
	/**
	 * vraci kontejner, ve kterem je prvek ulozen
	 * 
	 * @return Questionary_Item_Container
	 */
	public function getContainer() {
		return $this->_container;
	}
	
	/**
	 * vraci vychozi hodnotu
	 * 
	 * @return mixed
	 */
	public function getDefault() {
		return $this->_default;
	}
	
	/**
	 * vrac popisek prvku
	 * @return string
	 */
	public function getLabel() {
		return $this->_label;
	}
	
	/**
	 * vraci jmeno prvku
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * vraci rodicovsky dotaznik
	 * 
	 * @return Questionary_Questionary
	 */
	public function getQuestionary() {
		return $this->_questionary;
	}
	
	/**
	 * vraci hodnotu pole
	 * pokud jsou vyplnena data, vraci tyto data
	 * pokud data vyplnena nejsou vraci vychozi hodnotu
	 * 
	 * @return mixed
	 */
	public function getValue() {
		return $this->_isFilled ? $this->_filledVal : $this->_default;
	}
	
	/**
	 * vraci TRUE, pokud byla nastavena data
	 * jinak vraci FALSE
	 * 
	 * @return bool
	 */
	public function isFilled() {
		return $this->_isFilled;
	}
	
	/**
	 * vraci TRUE pokud je prvek uzamcen
	 * jinak vraci FALSE
	 * 
	 * @return bool
	 */
	public function isLocked() {
		return $this->_isLocked;
	}
	
	/**
	 * vraci TRUE, pokud je prvek renderovan primo z tridy dotazniku
	 * jinak vraci FALSE
	 * 
	 * @return bool
	 */
	public function isRenderable() {
		return $this->_questionary->getRenderable($this);
	}
	
	/**
	 * nastavi uzamceni objektu
	 * 
	 * @param bool $locked nove nastaveni uzamceni
	 * @return Questionary_Item_Abstract
	 */
	public function lock($locked) {
		$this->_isLocked = (bool) $locked;
		
		return $this;
	}
	
	/**
	 * nastavi novou vychozi hodnotu
	 * 
	 * @param unknown_type $data
	 * @return Questionary_Item_Abstract
	 */
	public function setDefault($data) {
		$this->_default = $data;
		
		return $this;
	}
	
	/**
	 * nastavi parametry objektu z pole 
	 * 
	 * @param array $data data objektu v poli
	 * @return Questionary_Item_Abstract
	 */
	public function setFromArray(array $data) {
		// nastaveni vychozich dat
		$data = array_merge(array(
				"label" => "",
				"isLocked" => false,
				"isFilled" => false,
				"value" => null,
				"params" => array(),
				"default" => null
		), $data);
		
		// odstraneni jmena, pokud existuje
		if (isset($data["name"])) unset($data["name"]);
		
		// nastaveni dat
		$this->_label = $data["label"];
		$this->_isFilled = $data["isFilled"];
		$this->_isLocked = $data["isLocked"];
		$this->_filledVal = $data["value"];
		$this->_default = $data["default"];
		
		return $this;
	}
	
	/**
	 * nastavi novy popisek
	 * 
	 * @param string $label popisek
	 * @return Questionary_Item_Abstract
	 */
	public function setLabel($label) {
		$this->_label = (string) $label;
		
		return $this;
	}
	
	/**
	 * nastavi renderovani primo z dotazniku
	 * 
	 * @param bool $renderable nove nastaveni renderovani
	 * @return Questionary_Item_Abstract
	 */
	public function setRenderable($renderable) {
		$this->_questionary->setRenderable($this, $renderable);
		
		return $this;
	}

	/**
	 * nastavi novou hodnotu prvku
	 * @param mixed $value nova hodnota
	 * @return Questionary_Item_Abstract
	 */
	public function setValue($value) {
		$this->_filledVal = $value;
		$this->_isFilled = true;

		return $this;
	}
	
	/**
	 * serializuje tridu do pole
	 * 
	 * @return array data objektu
	 */
	public function toArray() {
		return array(
				"name" => $this->_name,
				"className" => $this->getClassName(),
				"label" => $this->_label,
				"isLocked" => $this->_isLocked,
				"isFilled" => $this->_isFilled,
				"value" => $this->_filledVal,
				"default" => $this->_default,
				"params" => array()
		);
	}
	
	/**
	 * nastavi novy kontejner objektu
	 *
	 * @param Questionary_Item_Container $container kontejner
	 * @return Questionary_Item_Abstract
	 */
	protected function _setContainer(Questionary_Item_Container $container) {
		// nastaveni kontejneru. Vlastni operaci ma na starosti kontejnerova trida
		$this->_container = $container;
		
		return $this;
	}
	
	/**
	 * odebere objekt z kontejneru
	 * 
	 * @return Questionary_Item_Abstract
	 */
	protected function _clearContainer() {
		$this->_container = null;
		
		return $this;
	}
}
