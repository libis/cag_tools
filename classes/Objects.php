<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Objects {

     # --------------------------------------------------------------------------------
    /**
     * Insert of update ???
     *
     * @param string $idno unieke identificatie van het object
     * @return int $vn_left_id de object_id van het gezochte object of waarde NULL
     */
    public function actionToTake($idno)
    {
        global $log;

        $t_object = new ca_objects_bis();
        # controleren of objecten al gemaakt zijn
        $va_left_keys = $t_object->getObjectIDsByIdno($idno);

        $gevonden = sizeof($va_left_keys);

        if (($gevonden) === 0 ) {
            //actie blijft bij insert
            $log->logInfo('ACTION to take: CREATE');
            $vn_left_id = NULL;
        } elseif ($gevonden >= 1) {
            $vn_left_id = $va_left_keys[0];
            $log->logInfo('ACTION to take: UPDATE record', $vn_left_id);
            if ($gevonden === 1) {
                $log->logInfo('slechts één record gevonden', $va_left_keys[0]);
            } elseif ($gevonden > 1 ) {
                $log->logWarn('WARNING: meerdere records gevonden, we nemen het eerste', $va_left_keys);
            }
        }
        return $vn_left_id;
    }

    public function defineIdentificatie($resultarray, $idno)
    {
        global $log;

        $vs_Identificatie = "=====".$idno." geen identificatie =====";
        if ( (isset($resultarray['preferred_label'])) && (!empty($resultarray['preferred_label'])) ) {
            if (is_array($resultarray['preferred_label'])) {
                $vs_Identificatie = $resultarray['preferred_label'][0];
                $log->logWarn("WARNING: preferred_label => meerdere aanwezig, nemen de eerste");
            }else{
                $vs_Identificatie = $resultarray['preferred_label'];
            }
        }else{
            //aangepast omwille van ST-TRUIDEN - slechts éénmaal preferred label aanwezig
            $log->logWarn("WARNING: preferred_label => niet aanwezig - nemen objectNaam");

            if ( (isset($resultarray['objectNaam'])) && (!empty($resultarray['objectNaam'])) ) {
                if (!is_array($resultarray['objectNaam'])) {
                    $vs_Identificatie = trim($resultarray['objectNaam']);
                } else {
                    $vs_Identificatie = trim($resultarray['objectNaam'][0]);
                }
            }
        }
        $log->logInfo("Identificatie => {$vs_Identificatie}");
        return $vs_Identificatie;
    }

    public function defineStatus($resultarray)
    {

        if (isset($resultarray['publication_data']) && ($resultarray['publication_data']) === 'ja' )
        {   $status1 = 'ja';    } else     {   $status1 = 'nee';   }
        if (isset($resultarray['publishing_allowed']) && ($resultarray['publishing_allowed']) === 'x' )
        {   $status2 = 'ja';    } else     {   $status2 = 'nee';   }

        if (($status1 == 'ja') && ($status2 == 'ja'))     {   $status = 2;}
        if (($status1 == 'ja') && ($status2 == 'nee'))    {   $status = 1;}
        if (($status1 == 'nee') && ($status2 == 'nee'))   {   $status = 0;}

        return $status;
    }

    public function insertObject($vs_Identificatie, $idno, $status, $pn_object_type_id, $pn_locale_id)
    {
        global $log;

        $t_object = new ca_objects();
        $t_object->setMode(ACCESS_WRITE);

        $t_object->set('type_id', $pn_object_type_id);
        //opgelet !!! vergeet leading zeros niet
        $t_object->set('idno', $idno);
        $t_object->set('status', $status); //workflow_statuses
        $t_object->set('access', 1);       //1=accessible to public
        $t_object->set('locale_id', $pn_locale_id);
    //----------
        $t_object->insert();
    //----------
        if ($t_object->numErrors()) {
            $log->logError("ERROR INSERTING OBJECT: ", $vs_Identificatie);
            $log->logError("ERROR message(s) ", join('; ', $t_object->getErrors()) );
            //continue;
        }else{
            $log->logInfo("INSERT OBJECT gelukt: ", $vs_Identificatie);
            //----------
            $t_object->addLabel(array(
                    'name'      => $vs_Identificatie
            ),$pn_locale_id, null, true );

            if ($t_object->numErrors()) {
                $log->logError("ERROR ADDING PREFERRED LABEL TO OBJECT: ", $vs_Identificatie);
                $log->logError("ERROR message(s) ", join('; ', $t_object->getErrors()) );
                //continue;
            }else{
                $log->logInfo("ADDLABEL OBJECT gelukt voor: ", $vs_Identificatie);
            }
        }
        $object_id = $t_object->getPrimaryKey();
        unset($t_object);

        return $object_id;
    }

    public function addSomeObjectAttribute($object_id, $container, $data)
    {
        global $log;

        $t_object = new ca_objects();
        $t_object->setMode(ACCESS_WRITE);

        $t_object->load($object_id);
        $t_object->getPrimaryKey();

        $t_object->addAttribute($data, $container);

        $t_object->update();

        if ($t_object->numErrors()) {
            $log->logError("ERROR ADDING ATTRIBUTE-CONTAINER: ", $container);
            $log->logError("ERROR message(s) ", join('; ', $t_object->getErrors()) );
            $log->logError("ERROR de data: ", $data);
            //continue;
        }else{
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $container);
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $data);
        }
        unset($t_object);
    }

    # --------------------------------------------------------------------------------
    /**
     * Relatie leggen tussen een object en een item uit een andere ca_tabel
     *
     * @param int $object_id id van object uit ca_objects
     * @param string $right naam van de tabel waarmee relatie gelegd moet worden
     * @param string $vs_right_string waarde(label) waarmee gelinkt moet worden
     * @param int $relationship type_id uit ca_relationship_types
     * @return array $verder array met bij succes  [0] = 1  en [1] = int = id van gerelateerde occurrence/entiteit/collection/object/....
     *                                 bij failure [0] = 0  en [1] = 0)
     */
    function createRelationship($object_id, $right, $vs_right_string, $relationship) {

        global $log;

        $verder = array();
        # 1.8.
        if ($right == "ca_occurrences") {
            $occurrence = new ca_occurrences_bis();
            $va_right_keys = $occurrence->getOccurrenceIDsByUpperNameSort($vs_right_string);
        # 2.3.
        } elseif ($right == "ca_entities"){
            $entity = new ca_entities_bis();
            //$va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);
            $va_right_keys = $entity->getEntityIDsByUpperNameSort($vs_right_string);
        # 3.2.
        } elseif ($right == "ca_list_items") {
            $t_list = new ca_lists();
            $va_right_keys = $t_list->getItemIDFromList('cag_trefwoorden', $vs_right_string);
        # 4.1.
        } elseif ($right == "ca_collections") {
            $collect = new ca_collections();
            $va_right_keys = $collect->getCollectionIDsByName($vs_right_string);

        } elseif ($right == "ca_objects") {
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

            if ($right == "ca_list_items") {
                $vn_right_id = $va_right_keys;
            } else {
                $vn_right_id = $va_right_keys[0];
            }

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
            unset($vn_right_id);
        }
        return $verder;
    }

    # --------------------------------------------------------------------------------
    /**
     * Voorbereiden (herleiden tot sort_name) van een string voor het leggen van een relatie
     * sort_name wordt gebruikt bij occurrences en entiteiten
     *
     * @param int $n_left_id id van object uit ca_objects
     * @param string $right naam van de tabel waarmee relatie gelegd moet worden
     * @param string $vs_right_string waarde(label) waarmee gelinkt moet worden
     * @param int $relationship type_id uit ca_relationship_types
     * @return array $succes array met bij succes  [0] = 1  en [1] = int = id van gerelateerde occurrence/entiteit/collection/object/....
     *                                 bij failure [0] = 0  en [1] = 0)
     */

    public function processVariable($vn_left_id, $right, $vs_right_string, $relationship, $locale) {

        global $log;
        global $t_func;

        $succes = array(0, 0);

        if ( (isset($vs_right_string)) && (!empty($vs_right_string)) ) {

            if ( ($right === 'ca_entities') || ($right === 'ca_occurrences') ) {
                $search_string = $t_func->generateSortValue(trim($vs_right_string), $locale);
            } else {
                $search_string = trim($vs_right_string);
            }

            $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

            $succes = $this->createRelationship($vn_left_id, $right, $search_string, $relationship);

            $log->logInfo('succes', $succes);

            unset($vs_right_string);
            unset($search_string);
        }
        return $succes;
    }

    /*
    public function processVariable($vn_left_id, $resultarray, $variable, $relationship, $right) {

        global $log;
        global $t_func;

        $fields = array($variable);
        $aantal = $t_func->Herhalen($resultarray, $fields);

        if ($aantal > 0) {

            $result = $t_func->makeArray2($resultarray, $aantal, $fields);
            $aantal = $aantal - 1 ;
            $i = 0;
            for ($i=0; $i <= ($aantal); $i++) {

                if ( (isset($result[$variable][$i])) && (!empty($result[$variable][$i])) ) {

                    $vs_right_string = trim($result[$variable][$i]);

                    if ($right === 'ca_entities') {
                        $search_string = $t_func->cleanUp(trim($vs_right_string));
                    } else {
                        $search_string = trim($vs_right_string);
                    }

                    $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $succes = $this->createRelationship($vn_left_id, $right, $search_string, $relationship);

                    $log->logInfo('succes', $succes);

                    unset($vs_right_string);
                    unset($search_string);
                }
            }
            unset($aantal);
            unset($result);
        }
        unset($fields);
        return $succes;
    }
     *
     */

}