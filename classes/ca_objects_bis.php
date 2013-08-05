<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

/**
 * Description of ca_objects_bis
 *
 * @author AnitaR
 */
class ca_objects_bis extends ca_objects 
{
	public function getObjectIDsByIdno($ps_idno, $pn_parent_id=null) 
        {
		$o_db = $this->getDb();
		
		if ($pn_parent_id) {
			$qr_res = $o_db->query("
				SELECT DISTINCT cap.object_id
				FROM ca_objects cap
				WHERE
					cap.idno = ? AND cap.parent_id = ?
			", (string)$ps_idno, (int)$pn_parent_id);
		} else {
			$qr_res = $o_db->query("
				SELECT DISTINCT cap.object_id
				FROM ca_objects cap
				WHERE
					cap.idno = ?
			", (string)$ps_idno);

		}
		$va_object_ids = array();
		while($qr_res->nextRow()) {
			$va_object_ids[] = $qr_res->get('object_id');
		}
		return $va_object_ids;
	}
        
        public function getObjectIDsByElementID($ps_value, $pm_element_code_or_id) 
        {
		$o_db = $this->getDb();
                
		if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return false; }
                if ($t_element->get('parent_id') > 0) { return false; }
                $vn_element_id = $t_element->getPrimaryKey();
                
		$qr_res = $o_db->query("
                        SELECT DISTINCT object_id FROM ca_objects obj
                        INNER JOIN ca_attributes AS attr ON obj.object_id = attr.row_id
                        INNER JOIN ca_attribute_values AS val ON attr.attribute_id = val.attribute_id
                        WHERE attr.table_num = 57 
                        AND val.element_id = ? AND val.value_longtext1 = ?
                ", (int)$vn_element_id, (string)$ps_value);

		$va_object_ids = array();
		while($qr_res->nextRow()) {
			$va_object_ids[] = $qr_res->get('object_id');
		}
		return $va_object_ids;
	}
        
        public function getObjectNameByObjectID($pn_object_id) 
        {
		$o_db = $this->getDb();
                
		$qr_res = $o_db->query("
                        SELECT name FROM ca_object_labels attr
                        WHERE attr.object_id = ? 
                        AND attr.is_preferred = 1
                ", (int)$pn_object_id);

		$va_object_name = array();
		while($qr_res->nextRow()) {
			$va_object_name[] = $qr_res->get('name');
		}
		return $va_object_name;
	}
}