<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(__CA_MODELS_DIR__.'/ca_places.php');
/**
 * Description of ca_places_bis
 *
 * @author AnitaR
 */
class ca_places_bis extends ca_places
{

    public function getPlaceIDsByNamePart($ps_name, $pn_parent_id = null)
    {
        $o_db = $this->getDb();

        if ($pn_parent_id) {
            $qr_res = $o_db->query("
                SELECT DISTINCT cap.place_id
                FROM ca_places cap
                INNER JOIN ca_place_labels AS capl ON capl.place_id = cap.place_id
                WHERE
                    capl.name like ? AND cap.parent_id = ?
            ", (string)$ps_name, (int)$pn_parent_id
            );
        } else {
            $qr_res = $o_db->query("
                SELECT DISTINCT cap.place_id
                FROM ca_places cap
                INNER JOIN ca_place_labels AS capl ON capl.place_id = cap.place_id
                WHERE
                    capl.name like ?
                    ORDER BY capl.name ASC
            ", (string)$ps_name
            );
        }
        $va_place_ids = array();
        while ($qr_res->nextRow()) {
                $va_place_ids[] = $qr_res->get('place_id');
        }
        return $va_place_ids;
    }
}
