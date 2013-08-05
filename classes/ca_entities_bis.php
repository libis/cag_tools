<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(__CA_MODELS_DIR__.'/ca_entities.php');
/**
 * Description of ca_entities_bis
 *
 * @author AnitaR
 */
class ca_entities_bis extends ca_entities 
{
    
    public function getEntityIDByIdno($idno)
    {
        $o_db = $this->getDb();
        $qr_res = $o_db->query("
                SELECT DISTINCT cae.entity_id
                FROM ca_entities cae
                WHERE
                        cae.idno = ? 
        ", (int)$idno);

        $va_entity_ids = array();
        while($qr_res->nextRow()) {
                $va_entity_ids[] = $qr_res->get('entity_id');
        }
        return $va_entity_ids;
    }
}

?>
