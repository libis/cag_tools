<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Occurrences {

    public function createOccurrencesArray($resultarray, $fields, &$array, $locale)
    {
        global $t_func;
        global $log;

        $aantal = $t_func->Herhalen($resultarray, $fields);
        $log->logInfo('aantal', $aantal);

        $value1 = '';
        $value2 = '';
        $refNote = '';
        $refAuteur = '';
        $result = '';
        $sleutel = '';

        if ($aantal > 0) {
            $output = $t_func->makeArray2($resultarray, $aantal, $fields);
            $log->logInfo('output', $output);

            for ($i=0; $i < $aantal; $i++) {
                foreach ($output as $veld => $waarde) {
                    if ($veld === 'preferred_label_occur_1') {
                        if ( (isset($waarde[$i])) && (!empty($waarde[$i])) ) {
                            if (!(trim($waarde[$i])) === 'De') {
                                $value1 = $waarde[$i];
                            }
                        }
                    } elseif ($veld === 'preferred_label_occur_2') {
                        if ( (isset($waarde[$i])) && (!empty($waarde[$i])) ) {
                            $value2 = $waarde[$i];
                        }
                    } elseif ($veld === 'refNote') {
                        if ( (isset($waarde[$i])) && (!empty($waarde[$i])) ) {
                            $refNote = $waarde[$i];
                        }
                    } elseif ($veld === 'refAuteur') {
                        if ( (isset($waarde[$i])) && (!empty($waarde[$i])) ) {
                            $refAuteur = $waarde[$i];
                        }
                    }
                }

                $value1 = trim($value1);
                $value2 = trim($value2);
                $refNote = trim($refNote);
                $refAuteur = trim($refAuteur);

                $result = trim($value1.' '.$value2);
                //$sleutel = $t_func->cleanUp($result);
                $sleutel = $t_func->generateSortValue($result, $locale);
                $array[$sleutel]['label'] = $result;
                if ( (!empty($refNote)) && (strpos($array[$sleutel]['refNote'], $refNote) === false) ) {
                    $array[$sleutel]['refNote'] = trim($refNote.' '.$array[$sleutel]['refNote']);
                }
                $array[$sleutel]['refAuteur']= $refAuteur;
            }
            unset($value1);
            unset($value2);
            unset($result);
            unset($sleutel);
        }
    }

    public function insertOccurrence($label, $type, $idno, $status, $locale) {

        global $log;

        $t_occur = new ca_occurrences();
        $t_occur->setMode(ACCESS_WRITE);

        $t_occur->set('type_id', $type);
        $t_occur->set('idno', $idno);
        $t_occur->set('status', $status); //workflow_statuses
        $t_occur->set('access', 1);       //1=accessible to public
        $t_occur->set('locale_id', $locale);
        //----------
        $t_occur->insert();
        //----------
        if ($t_occur->numErrors()) {
            $log->logError("ERROR INSERTING occurrence ".$label." - ".join('; ', $t_occur->getErrors()));
            $occurrence_id = false;
        }else{
            $log->logInfo("insert occurrence ".$label." gelukt");
            //----------
            $t_occur->addLabel(array(
                'name'      => $label
            ),$locale, null, true );

            if ($t_occur->numErrors()) {
                $log->logError("ERROR: ADDING LABEL TO occurrence ".$label." - ".join('; ', $t_occur->getErrors()));
            }else{
                $log->logInfo("addlabel occurrence ".$label." gelukt ");
            }
        }
        $occurrence_id = $t_occur->getPrimaryKey();
        unset($t_occur);

        return $occurrence_id;
    }

    public function addSomeOccurrenceAttribute($occurrence_id, $container, $data)
    {
        global $log;

        $t_occur = new ca_occurrences();
        $t_occur->setMode(ACCESS_WRITE);

        $t_occur->load($occurrence_id);
        $t_occur->getPrimaryKey();

        $t_occur->addAttribute($data, $container);

        $t_occur->update();

        if ($t_occur->numErrors()) {
            $log->logError("ERROR ADDING ATTRIBUTE-CONTAINER: ", $container);
            $log->logError("ERROR message(s) ", join('; ', $t_occur->getErrors()) );
            $log->logError("ERROR de data: ", $data);
            //continue;
        }else{
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $container);
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $data);
        }
        unset($t_occur);
    }

    function createOccurrenceRelationship($object_id, $right, $vs_right_string, $relationship) {

        global $log;

        $verder = array();
        # 1.8.
        if ($right == "ca_occurrences") {
            $occurrence = new ca_occurrences_bis();
            $va_right_keys = $occurrence->getOccurrenceIDsByUpperNameSort($vs_right_string);
        }
        # 2.3.
        if ($right == "ca_entities"){
            $entity = new ca_entities_bis();
            //$va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);
            $va_right_keys = $entity->getEntityIDsByUpperNameSort($vs_right_string);
        }
        # 3.2.
        if ($right == "ca_objects_x_vocabulary_terms") {
            $t_list = new ca_lists();
            $va_right_keys = $t_list->getItemIDFromList('cag_trefwoorden', $vs_right_string);
        }
        # 4.1.
        if ($right == "ca_collections") {
            $collect = new ca_collections();
            $va_right_keys = $collect->getCollectionIDsByName($vs_right_string);
        }

        if ($right == "ca_objects") {
            $object = new ca_objects_bis();
            $va_right_keys = $object->getObjectIDsByElementID($vs_right_string, 'adlibObjectNummer');
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

            $t_object = new ca_objects_bis();
            $t_object->setMode(ACCESS_WRITE);
            $t_object->load($object_id);
            $t_object->getPrimaryKey();

            $t_object->addRelationship($right, $vn_right_id, $relationship);

            if ($t_object->numErrors()) {
                $log->logError("ERROR LINKING entities: ");
                $log->logError("ERROR message(s) ", join('; ', $t_object->getErrors()) );
                $verder = array(0, 0);

            }else{
                $log->logInfo("SUCCESS: relation with {$right}/{$vn_right_id} succesfull");
                $verder = array(1, $vn_right_id);
            }

            unset($t_object);
        }
        return $verder;
    }
}


