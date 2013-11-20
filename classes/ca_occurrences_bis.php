
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
/**
 * Description of ca_occurrences_bis
 *
 * @author AnitaR
 */
class ca_occurrences_bis extends ca_occurrences
{

    public function getOccurrenceIDsByUpperNameSort($ps_name) {

        global $log;

        $ps_name = trim(strtoupper($ps_name));
        $log->logInfo('aangepaste zoekterm', $ps_name);
        
        $o_db = $this->getDb();
        $qr_res = $o_db->query("
            SELECT DISTINCT cap.occurrence_id
            FROM ca_occurrences cap
            INNER JOIN ca_occurrence_labels AS capl ON capl.occurrence_id = cap.occurrence_id
            WHERE
                    UPPER(capl.name_sort) = ?
                ", (string)$ps_name);

        $va_occurrence_ids = array();
        while($qr_res->nextRow()) {
            $va_occurrence_ids[] = $qr_res->get('occurrence_id');
        }
        return $va_occurrence_ids;
    }

}