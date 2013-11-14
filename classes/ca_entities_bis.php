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

    public function getEntityIDsByUpperNameSort($ps_surname) {

        global $log;

        $ps_surname = trim(substr(strtoupper($ps_surname), 0, 99));
        $log->logInfo('aangepaste zoekterm', $ps_surname);

        $o_db = $this->getDb();
        $qr_res = $o_db->query("
                SELECT DISTINCT cae.entity_id
                FROM ca_entities cae
                INNER JOIN ca_entity_labels AS cael ON cael.entity_id = cae.entity_id
                WHERE
                        UPPER(cael.name_sort) = ?
        ", (string)$ps_surname);

        $va_entity_ids = array();
        while($qr_res->nextRow()) {
                $va_entity_ids[] = $qr_res->get('entity_id');
        }
        return $va_entity_ids;
    }

    public function getEntityNameByEntityID($pn_entity_id)
    {

        $o_db = $this->getDb();

        $qr_res = $o_db->query("
                SELECT displayname FROM ca_entity_labels attr
                WHERE attr.entity_id = ?
                AND attr.is_preferred = 1
        ", (int) $pn_entity_id);

        $va_entity_names = array();
        while($qr_res->nextRow()) {
                $va_entity_names[] = $qr_res->get('displayname');
        }
        return $va_entity_names;
    }

}