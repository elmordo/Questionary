<?php
class Questionary_Model_Filleds extends Zend_Db_Table_Abstract {
	protected $_name = "questionary_filleds";
	
	protected $_primary = "id";
	
	protected $_sequence = true;
	
	protected $_referenceMap = array(
			"questionary" => array(
					"columns" => "questionary_id",
					"refTableClass" => "Questionary_Model_Questionaries",
					"refColumns" => "id"
			)
	);
	
	protected $_rowClass = "Questionary_Model_Row_Filled";
	
	protected $_rowsetClass = "Questionary_Model_Rowset_Filleds";
	
	/**
	 * vytvori novy zaznam o vyplneni
	 * 
	 * @param Questionary_Model_Row_Questionary
	 * @return Questionary_Model_Row_Filled
	 */
	public function createFilled(Questionary_Model_Row_Questionary $questionary) {
        // zacatek atransakce
        $adapter = $this->getAdapter();
        $adapter->beginTransaction();
        
        try {
            $row = $this->createRow(array(
                    "questionary_id" => $questionary->id,
                    "modified_at" => new Zend_Db_Expr("NOW()"),
                    "name" => $questionary->name
            ));

            $row->save();

            // nakopirovani dat prvku
            $tableFItems = new Questionary_Model_FilledsItems();
            $tableQItems = new Questionary_Model_QuestionariesItems();

            $nameFItems = $tableFItems->info("name");
            $nameQItems = $tableQItems->info("name");

            $sql = "insert into `$nameFItems` (`filled_id`, `class`, `name`, `label`, `params`, `is_locked`, `data`) SELECT $row->id, `class`, `name`, `label`, `params`, `is_locked`, `default` FROM `$nameQItems` where questionary_id = $questionary->id";
            $adapter->query($sql);

            // zapis renderables
            $tableFRenderables = new Questionary_Model_FilledsRenderables();
            $tableQRenderables = new Questionary_Model_QuestionariesRenderables();

            $nameFRenderables = $tableFRenderables->info("name");
            $nameQRenderables = $tableQRenderables->info("name");

            // zakladni select
            $select = new Zend_Db_Select($adapter);
            $select->from(array("qr" => $nameQRenderables), array(new Zend_Db_Expr($row->id), "position"));
            $select->where("qr.questionary_id = ?", $questionary->id);

            // pripojeni itemu ze zakladni tabulky itemu
            $select->joinInner(array("qi" => $nameQItems), "qi.id = qr.item_id", array());

            // pripojeni tabulky klonovynych itemu
            $select->joinInner(array("fi" => $nameFItems), "qi.name = fi.name and fi.filled_id = $row->id", array("id"));

            // sestaveni insert dotazu
            $sql = "insert into $nameFRenderables (filled_id, position, item_id) " . $select;
            
            $adapter->query($sql);
            
            $adapter->commit();
        } catch (Zend_Db_Exception $e) {
            $adapter->rollBack();
            throw $e;
        }
        
		return $row;
	}
	
	/**
	 * nacte seznam vyplnenych dotazniku dle sablony
	 * 
	 * @param Questionary_Model_Row_Questionary $questionary sablona dotazniku
	 * @return Zend_Db_Table_Rowset
	 */
	public function findByQuestionary(Questionary_Model_Row_Questionary $questionary) {
		return $this->fetchAll("questionary_id = " . $questionary->id, "created_at");
	}
	
	/**
	 * nacte vyplneny dotaznik dle id
	 * 
	 * @param int $id identifikator dotazniku
	 * @return Questionary_Model_Row_Filled
	 */
	public function getById($id) {
		$id = (int) $id;
		
		return $this->find($id)->current();
	}
}