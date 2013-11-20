<?php
/**
 * Description of Entities
 *
 * @author AnitaR
 */
class Entities {
     # --------------------------------------------------------------------------------
    /**
     * Functie om te bepalen of we INSERT of UPDATE hebben
     *
     * @param array &$resultarray input-array - passed by
     * @param array &$pref
     * @param int $locale taal_code
     * @return string $action INSERT of UPDATE
     */

    public function actionToTake(&$resultarray, &$pref, $locale)
    {
        global $t_func;

        $t_entity = new ca_entities_bis();

        $action = 'INSERT';
        $search_string = '';
        $va_left_keys = array();

        $fields_use = array('use');
        $aantal_use = sizeof($resultarray['use']);
        $use = $t_func->makeArray2($resultarray, $aantal_use, $fields_use);

        //controleren of er use of used_for vermelding is
        if ( (isset($use['use'][0])) && (!empty($use['use'][0])) ) {
            //opzoeken of een record met deze 'use' al bestaat
            $search_string = $t_func->generateSortValue(trim($use['use'][0]), $locale);
            $va_left_keys = ($t_entity->getEntityIDsByUpperNameSort($search_string));
            if (!empty($va_left_keys)) {
                //record met deze label bestaat reeds -> gegevens toevoegen -> UPDATE
                $action = "UPDATE";
                //bestaat deze alternatieve benaming al?
                $resultarray['alt_label'] = $resultarray['preferred_label'];
            }else{
                //record met deze naam bestaat nog niet -> INSERT
                $action = "INSERT";
                // 'use' wordt de Identificatie
                // 'preferred_label' wordt een alternatieve benaming
                $resultarray['alt_label'] = $resultarray['preferred_label'];
                $resultarray['preferred_label'] = $resultarray['use'];
            }
        }

        $fields_used_for = array('used_for');
        $aantal_used_for = sizeof($resultarray['used_for']);
        $used_for = $t_func->makeArray2($resultarray, $aantal_used_for, $fields_used_for);

        $fields_pref = array('preferred_label');
        $aantal_pref = sizeof($resultarray['preferred_label']);
        $pref = $t_func->makeArray2($resultarray, $aantal_pref, $fields_pref);

        if ( (isset($used_for['used_for'][0])) && (!empty($used_for['used_for'][0])) ) {
            //'used_for' zijn gewoon alternatieve benamingen voor 'preferred_label'
            // controleren of de 'preferred_label' ondertussen al bestaat
            $search_string = $t_func->generateSortValue(trim($pref['preferred_label'][0]), $locale);
            $va_left_keys = $t_entity->getEntityIDsByUpperNameSort($search_string);
            if (empty($va_left_keys)) {
                //record bestaat nog niet -> INSERT
                $action = "INSERT";
                //'preferred_label' blijft 'preferred_label en wordt de Identificatie
                //'used_for' worden alternatieve benamingen
                $resultarray['alt_label'] = $resultarray['used_for'];
            }else{
                //record bestaat al -> UPDATE
                $action = "UPDATE";
                //bestaan alle alternatieve benamingen al ?
                $resultarray['alt_label'] = $resultarray['used_for'];
            }
        }

        //en daarnaast bestaan nog 'non_preferred_labels'
        if ( (!isset($use['use'][0])) && (!isset($used_for['used_for'][0])) ) {
            //ook best even controleren of entity met gegeven 'preferred_label niet al bestaat
            $search_string = $t_func->generateSortValue(trim($pref['preferred_label'][0]), $locale);
            $va_left_keys = $t_entity->getEntityIDsByUpperNameSort($search_string);
            if (!empty($va_left_keys)) {
                $action = "UPDATE";
            }
        }
        $resultarray['va_left_keys'] = $va_left_keys;
        unset($t_entity);

        return $action;
    }

    public function whichEntityTypeId($resultarray)
    {
        global $t_func;

        $t_list = new ca_lists();

#62 #48
        $termen = array('abdij', 'zuster','bibliotheek','dienstmaagd','firma','garage','gasthuis','gebroeders','gemeentebestuur','stad',
                    'geelhand','winkel', 'collectie', 'museum','looza','pclt','fabriek','sancta maria','siba','fruitveiling','hoeve',
                    'vzw', 'beeldbank', 'soma', 'fonds', 'kadoc', 'swaene', 'zuiderkempen');
        $individual = array('auteur','behandelaar','persoon');
        $hist_persoon = array('vervaardiger');
        $organization = array('corporatieve auteur','drukker','instelling','Productie maatschappij','uitgever','vereniging','website');

        $pn_entity_type_id = 0;

#77
        if ( (isset($resultarray['entity_type'])) && (!empty($resultarray['entity_type'])) &&
             ($resultarray['entity_type'] === 'ai') ) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
        } elseif ( (isset($resultarray['entity_type'])) && (!empty($resultarray['entity_type'])) &&
                 ($resultarray['entity_type'] === 'ap') ) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
        } elseif ( (isset($resultarray['entity_type'])) && (!empty($resultarray['entity_type'])) &&
                 ($resultarray['entity_type'] === 'hi') ) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischeOrganisatie');
        } elseif ( (isset($resultarray['entity_type'])) && (!empty($resultarray['entity_type'])) &&
                 ($resultarray['entity_type'] === 'hp') ) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
        } else {

            $aantal = count($resultarray['entity_types']);

            if ($aantal === 1) {
                if (in_array(($resultarray['entity_types']), $individual)) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                }

                if (in_array(($resultarray['entity_types']), $hist_persoon)) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
                }

                if (in_array(($resultarray['entity_types']), $organization)) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                }

                if (trim($resultarray['entity_types']) === 'verwervingsbron') {
    #62 #48
                    if ($t_func->value_in_array($termen, strtolower($resultarray['preferred_label']))) {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                    } else {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                    }
                }
            }elseif ($aantal > 1) {
                //baseren ons op de eerste waarde
                if ($t_func->value_in_array($individual, ($resultarray['entity_types'][0]))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                }

                if ($t_func->value_in_array($organization, ($resultarray['entity_types'][0]))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                }

                if (trim($resultarray['entity_types'][0]) === 'verwervingsbron') {
    #62 #48
                    if ($t_func->value_in_array($termen, strtolower($resultarray['preferred_label'][0]))) {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                    } else {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                    }
                }

                if (trim($resultarray['entity_types'][0]) === 'vervaardiger') {
                    if ($t_func->value_in_array($individual, ($resultarray['entity_types'][1]))) {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
                    }

                    if ($t_func->value_in_array($organization, ($resultarray['entity_types'][1]))) {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischeOrganisatie');
                    }

                    if (trim($resultarray['entity_types'][1]) === 'verwervingsbron') {
                        $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
                    }
                }
            }
        }
        unset($t_list);

        return $pn_entity_type_id;
    }

    public function defineIdentificatie($resultarray, $pref)
    {
        if ( (isset($pref['preferred_label'][0])) && (!empty($pref['preferred_label'][0])) ) {
            $Identificatie = $pref['preferred_label'][0];
        } else {
            $Identificatie = '---???-'.$resultarray['adlibObjectNummer'].'-???--- ';
        }
        return $Identificatie;
    }

    public function defineStatus(&$resultarray)
    {

        $t_list = new ca_lists();

        if (isset($resultarray['workflow_statusses'])) {
            if (strtoupper(trim($resultarray['workflow_statusses'])) === 'JA') {
                $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i2');
            } elseif (strtoupper(trim($resultarray['workflow_statusses'])) === 'NEEN') {
                $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i0');
            } else {
                $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i1');
            }
        }else{
            $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i1');
        }

        $status = $resultarray['status'];
        unset($t_list);

        return $status;
    }

    public function insertEntity($Identificatie, $idno, $status, $pn_entity_type_id, $pn_locale_id)
    {
        global $log;

        $t_entity = new ca_entities();
        $t_entity->setMode(ACCESS_WRITE);

        $t_entity->set('type_id', $pn_entity_type_id);
        $t_entity->set('idno', $idno);
        $t_entity->set('status', $status);
        $t_entity->set('access', 1);
        $t_entity->set('surname', $Identificatie);
        $t_entity->set('locale_id', $pn_locale_id);
    //----------
        $t_entity->insert();
    //----------
        if ($t_entity->numErrors()) {
            $log->logError("ERROR INSERTING ENTITY: ", $Identificatie);
            $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
            //continue;
        }else{
            $log->logInfo("INSERT ENTITY gelukt: ", $Identificatie);
            //----------
            $t_entity->addLabel(array(
                'surname'     => substr($Identificatie, 0, 99),
                'displayname' => substr($Identificatie, 0, 511)
                ),$pn_locale_id, null, true );

            if ($t_entity->numErrors()) {
                $log->logError("ERROR ADDING PREFERRED LABEL TO ENTITY: ", $Identificatie);
                $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                //continue;
            }else{
                $log->logInfo("ADDLABEL ENTITY gelukt voor: ", $Identificatie);
            }
        }
        $entity_id = $t_entity->getPrimaryKey();
        unset($t_entity);

        return $entity_id;
    }

    public function checkPreferredLabel($pref, $entity_id, $pn_locale_id)
    {

        if ( (isset($pref['preferred_label'][1])) && (!empty($pref['preferred_label'][1])) ) {
            $Identificatie = trim($pref['preferred_label'][1]);

            $this->addOtherPreferredLabel($entity_id, $Identificatie, $pn_locale_id, TRUE);
        }

    }

    public function addSomeEntityAttribute($entity_id, $container, $data)
    {
        global $log;

        $t_entity = new ca_entities();
        $t_entity->setMode(ACCESS_WRITE);

        $t_entity->load($entity_id);
        $t_entity->getPrimaryKey();

        $t_entity->addAttribute($data, $container);

        $t_entity->update();

        if ($t_entity->numErrors()) {
            $log->logError("ERROR ADDING ATTRIBUTE-CONTAINER: ", $container);
            $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
            $log->logError("ERROR de data: ", $data);
            //continue;
        }else{
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $container);
            $log->logInfo("ADDATTRIBUTE ENTITY gelukt voor: ", $data);
        }
        unset($t_entity);
    }

    public function addOtherPreferredLabel($entity_id, $Identificatie, $pn_locale_id, $option)
    {
        global $log;
        global $t_func;

        $t_entity = new ca_entities_bis();
        $t_entity->setMode(ACCESS_WRITE);
        $t_entity->load($entity_id);
        $t_entity->getPrimaryKey();

        $search_string = $t_func->generateSortValue(trim($Identificatie), $pn_locale_id);
        $va_left_keys = ($t_entity->getEntityIDsByUpperNameSort($search_string));

        if (empty($va_left_keys)) {
            $t_entity->addLabel(array(
                'surname'     => substr($Identificatie, 0, 99),
                'displayname' => substr($Identificatie, 0, 99)
                ),$pn_locale_id, null, $option );

            if ($t_entity->numErrors()) {
                $log->logError("ERROR ADDING PREFERRED LABEL FOR ENTITY: ", $Identificatie);
                $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                //continue;
            }else{
                $log->logInfo("ADDLABEL gelukt voor ENTITY: ", $Identificatie);
            }
        }
        unset($t_entity);
    }

    public function createPersoonDatum($resultarray)
    {
        $persoonDatum = "";
        if ( (isset($resultarray['sterfteDatum'])) && (!empty($resultarray['sterfteDatum'])) ) {
            $persoonDatum = $resultarray['geboorteDatum']." - ".$resultarray['sterfteDatum'];
        }  else {
            $persoonDatum = $resultarray['geboorteDatum']." - ";
        }
        return $persoonDatum;
    }

    public function createPersoonDatumOpmerking($resultarray)
    {
        $persoonDatumOpmerking = '';
        if ( (isset($resultarray['geboortePlaats'])) && (!empty($resultarray['geboortePlaats'])) ) {
            $persoonDatumOpmerking = "Geboorteplaats: ".$resultarray['geboortePlaats'];
        } else {
            $persoonDatumOpmerking = "";
        }
        return $persoonDatumOpmerking;
    }
}