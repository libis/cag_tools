<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EntitiesRelaties
 *
 * @author AnitaR
 */
class EntitiesRelaties {

    public function createEntityRelationship($entity_id, $right, $vs_right_string, $relationship) {

        global $log;

        $verder = array();
        if ($right === "ca_entities"){
            $entity = new ca_entities_bis();
            $search_string = $vs_right_string;
            $va_right_keys = $entity->getEntityIDsByUpperNameSort($search_string);
        }
        if ( (sizeof($va_right_keys)) === 0 ) {

            $log->logError("ERROR: problems with right-entity", $vs_right_string);
            $log->logError('GEEN kandidaten gevonden', $va_right_keys);
            $verder = array(0, 0);
            #object aanmaken ?
        } elseif ((sizeof($va_right_keys)) >= 1 ){

            if ((sizeof($va_right_keys)) > 1 ) {
                $log->logWarn("WARNING: problems with right-entity", $vs_right_string);
                $log->logWarn('Meerdere kandidaten gevonden', $va_right_keys);
                $log->logWarn('We nemen de eerste entity_id', $va_right_keys[0]);
            }

            $vn_right_id = $va_right_keys[0];

            $t_entity = new ca_entities_bis();
            $t_entity->setMode(ACCESS_WRITE);
            $t_entity->load($entity_id);
            $t_entity->getPrimaryKey();

            $t_entity->addRelationship($right, $vn_right_id, $relationship);

            if ($t_entity->numErrors()) {
                $log->logError("ERROR LINKING entities: ");
                $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                $verder = array(0, 0);

            }else{
                $log->logInfo("SUCCESS: relation with {$right}/{$vn_right_id} succesfull");
                $verder = array(1, $vn_right_id);
            }

            unset($t_entity);
        }
        return $verder;
    }
}

?>
