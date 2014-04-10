<?php
class Questionary_Item_CheckBox extends Questionary_Item_SingleInput {
	
	protected $_checkedVal = "1";

	protected $_checked = 0;

	public function getCheckedVal() {
		return $this->_checkedVal;
	}

	public function getChecked() {
		return $this->_checked;
	}

	public function setFromArray(array $data) {
		$data = array_merge(array("params" => array()), $data);
		
		parent::setFromArray($data);

		// nastaveni parametru
		$params = array_merge(array("checked" => 0, "checkedVal" => "1"), $data["params"]);

		$this->_checkedVal = $params["checkedVal"];
		$this->_checked = $params["checked"];
	}

	public function setCheckedVal($val) {
		$this->_checkedVal = $val;

		return $this;
	}

	public function setChecked($checked) {
		$this->_checked = (bool) $checked;
	}

	public function toArray() {
		$retVal = parent::toArray();

		// naplneni mistnich dat
		$retVal["params"]["checked"] = $this->_checked ? 1 : 0;
		$retVal["params"]["checkedVal"] = $this->_checkedVal;

		return $retVal;
	}
}