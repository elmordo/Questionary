<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FilledsRenderables
 *
 * @author petr
 */
class Questionary_Model_FilledsRenderables extends Zend_Db_Table_Abstract {
    
    protected $_name = "questionary_filleds_renderables";
    
    protected $_primary = array("filled_id", "item_id");
    
    protected $_sequence = false;
    
    protected $_referenceMap = array(
        "item" => array(
            "columns" => array("item_id"),
            "refTableClass" => "Questionary_Model_FilledsItems",
            "refColumns" => array("id")
        ),
        
        "filled" => array(
            "columns" => array("filled_id"),
            "refTableClass" => "Questionary_Model_Filleds",
            "refColumns" => array("id")
        )
    );
}

?>
