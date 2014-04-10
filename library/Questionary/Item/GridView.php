<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GridView
 *
 * @author petr
 */
class Questionary_Item_GridView extends Questionary_Item_Abstract {
    
    private $_columns = array();
    
    private $_minRows = 1;

    private $_showDel = true;

    private $_showAdd = true;
    
    public function setFromArray(array $data) {
        parent::setFromArray($data);
        
        $this->_columns = $data["params"]["columns"];
        $this->_minRows = (int) $data["params"]["minRows"];
        $this->_showDel = (int) @$data["params"]["showDel"];
        $this->_showAdd = (int) @$data["params"]["showAdd"];

        return $this;
    }
    
    public function toArray() {
        $retVal = parent::toArray();
        
        $retVal["params"]["columns"] = $this->_columns;
        $retVal["params"]["minRows"] = $this->_minRows;
        $retVal["params"]["showDel"] = $this->_showDel;
        $retVal["params"]["showAdd"] = $this->_showAdd;
        
        return $retVal;
    }
}

?>
