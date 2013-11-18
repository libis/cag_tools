<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/My_CAG.php");

define("__PROG__","objects_relaties");

require_once(__CA_MODELS_DIR__."/ca_collections.php");
require_once(__MY_DIR__.'/cag_tools/classes/ca_entities_bis.php');
require_once(__MY_DIR__.'/cag_tools/classes/ca_occurrences_bis.php');
require_once(__MY_DIR__.'/cag_tools/classes/ca_objects_bis.php');
require_once(__MY_DIR__.'/cag_tools/classes/ca_places_bis.php');
require_once(__MY_DIR__.'/cag_tools/classes/Occurrences.php');
require_once(__MY_DIR__.'/cag_tools/classes/Objects.php');
require_once(__MY_DIR__."/cag_tools/classes/EntitiesUitObjecten.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$t_relatie = new ca_relationship_types();

$o_db = new Db();
$t_place = new ca_places_bis();

$t_texp = new TimeExpressionParser(null, null, true);
$t_texp->setLanguage('nl_NL');

$t_entity = new ca_entities_bis();
$t_object = new ca_objects_bis();

$my_objects = new Objects();
$my_entuitobj =new EntitiesUitObjecten();
//==============================================================================initialisaties
$teller = 1;
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv(__MAPPING__);

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__DATA__);

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo("==========".($teller)."========");
    $log->logInfo('de originele data', $resultarray);

    //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
    $idno = sprintf('%08d', $teller);
    // voor sinttruiden
    if (__PART__ === "ST"){
        $idno = 'st'.$idno;
    }
    if (__PART__ === "TEST"){
        $idno = 'test'.$idno;
    }
    $log->logInfo('idno ',($idno));
    $log->logInfo("adlibObjectNummer", $resultarray['adlibObjectNummer']);

    $va_left_keys = $t_object->getObjectIDsByIdno($idno);

    if ((sizeof($va_left_keys)) > 1 ) {
        $log->logError('WARNING: PROBLEMS with object ' . $idno . ': meerdere records gevonden. We nemen het eerste !!!!!');
    }
    $vn_left_id = $va_left_keys[0];
    /*
    $t_object->load($vn_left_id);
    $t_object->getPrimaryKey();
    $t_object->set('object_id', $vn_left_id);
     *
     */

    $log->logInfo("object_id: ",$vn_left_id);

//##############################################################################
//SCHERM 1: IDENTIFICATIE
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.8. Documentatie publicatie -> documentatieRelatie naar ca_occurrences -> apart programma - TODO
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    # eerst cag_occurrences.php uitvoeren voor aanmaak occurrences
    # veronderstellen GEEN ARRAYS


    $label_occur_1 = 'preferred_label_occur_1';
    $label_occur_2 = 'preferred_label_occur_2';
    $refPag = 'refPagina';

    if ( (isset($resultarray[$label_occur_1])) || (isset($resultarray[$label_occur_2])) || (isset($resultarray[$refPag])) ) {

        $fields = array('preferred_label_occur_1', 'preferred_label_occur_2', 'refPagina');
        $occur = array();

        if ( (!isset($resultarray[$label_occur_2]))) {
            $log->logWarn('WARNING: Record zonder documentation.title (preferred_label_occur_2)', $resultarray);
        } else {
            $aantal_occur = $t_func->Herhalen($resultarray, $fields);
            $occur = $t_func->makeArray2($resultarray, $aantal_occur, $fields);
        }

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_occurrences', 'documentatieRelatie');
        $aantal = $aantal_occur - 1;
        $i = 0;
        for($i = 0; $i <= $aantal; $i++) {

            if (trim($occur[$label_occur_2][$i]) !==  "") {

                $vs_right_string = trim($occur[$label_occur_2][$i]);
                //$search_string = trim($occur[$label_occur_2][$i]);
                $search_string = $t_func->cleanUp(trim($occur[$label_occur_2][$i]));
                $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en  publicatie " . $vs_right_string);
                $succes = $my_objects->createRelationship($vn_left_id, 'ca_occurrences', $search_string, $relationship);
            }
            $log->logInfo('succes', $succes);

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //1.9. Documentatie pagina -> refPagina -> apart programma samen met documentatieRelatie
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

            if ($succes[0] === 1) {

                if ( (isset($occur[$refPag][$i])) && (!is_array($occur[$refPag][$i])) && (!empty($occur[$refPag][$i])) ) {
                    $data = array(  'refPaginaDoc'      =>  $vs_right_string,
                                    'refPagina'         =>  trim($occur[$refPag][$i]),
                                    'locale_id'         =>  $pn_locale_id);
                    $container = "refPaginaInfo";

                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                    unset($data);
                    unset($container);
                }
            }
            unset($vs_right_string);
            unset($search_string);
            unset($succes);
        }
        unset($aantal);
        unset($aantal_occur);
        unset($occur);
        unset($relationship);
        unset($fields);
    }

    $authority = 'objectnaamOpmerkingen_3';

    if ( (isset($resultarray[$authority])) && (!empty($resultarray[$authority])) ) {

        $fields3 = array('objectnaamOpmerkingen_3');
        $occur2 = array();

        $my_entuitobj->createEntitiesArray($resultarray, $fields3, $occur2);

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_occurrences', 'documentatieRelatie');

        foreach ($occur2 as $key => $value) {
            if (trim($key) !==  "") {

                $vs_right_string = $value;
                //$search_string = $value;
                $search_string = $t_func->cleanUp(trim($value));
                $log->logInfo("relatie leggen tussen object " . $vn_left_id . "  en publicatie(objectnaamOpmerking)  " . $vs_right_string);
                $succes = $my_objects->createRelationship($vn_left_id, 'ca_occurrences', $search_string, $relationship);
            }
            $log->logInfo('succes', $succes);

            unset($vs_right_string);
            unset($search_string);
            unset($succes);
        }
        unset($occur2);
        unset($relationship);
        unset($fields);
    }

//##############################################################################
//SCHERM 2: FYSIEKE BESCHRIJVING
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.3. Vervaardiger -> vervaardigerRelatie naar ca_entities -> apart programma       cag_objecten_relaties.php
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    # Vormen alles om tot array
    # eerst cag_entities uitvoeren voor aanmaak entities op basis van objecten.xml en sinttruiden.xml
    //objecten -> enkele 13 problemen met een link naar entiteit Boerenbond !!!!!!
    //sinttruiden -> 34 onbekende entiteiten -> Aanmaken ??? DONE

    /* samen met volgend punt
    $verv = 'vervaardiger';

    if ( (isset($resultarray[$verv])) ) {

        $fields = array($verv);
        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'vervaardigerRelatie');
        $verv_aantal = $t_func->Herhalen($resultarray, $fields);

        if ($verv_aantal > 0) {

            $vervaardiger = $t_func->makeArray2($resultarray, $verv_aantal, $fields);
            $aantal = $verv_aantal - 1;
            $i = 0;
            for ($i=0; $i <= $aantal; $i++) {

                if (!empty($vervaardiger[$verv][$i])) {

                    $vs_right_string = trim($vervaardiger[$verv][$i]);
                    $search_string = $t_func->cleanUp($vs_right_string);
                    $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $succes = $my_objects->createRelationship($vn_left_id, 'ca_entities', $search_string, $relationship);

                    $log->logInfo('succes', $succes);

                    unset($vs_right_string);
                    unset($search_string);
                }
            }
            unset($aantal);
            unset($i);
            unset($vervaardiger);
        }
        unset($fields);
        unset($verv_aantal);
        unset($relationship);
    }
     *
     */

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.4. Vervaardiging -> container: objectVervaardigingInfo -> WACHTEN                cag_objecten_relaties.php
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//    Rol ->        List: vervaardiger_rol -> vervaardigerRol
//    Datering->    DateRange: objectVervaardigingDate (5) syntax: 5 2 - 3 1 (4 komt niet voor)
//    Plaats->      Place: objectVervaardigingPlace - ca_places?
//    Bijk.Info->   Text: objectVervaardigingNote  (3)
//    Serienr->     Text: modelSerienummer: nieuw veld
// Eerst ca_places aanmaken, alvorens deze data in te laden !!!!!!!!


    $vervaardiger = 'vervaardiger';
    $vRol = 'vervaardigerRol';
    $vDate_1 = 'objectVervaardigingDate_1';
    $vDate_2 = 'objectVervaardigingDate_2';
    $vDate_3 = 'objectVervaardigingDate_3';
    $vDate_4 = 'objectVervaardigingDate_4';
    $vDate_5 = 'objectVervaardigingDate_5';
    $vPlace = 'objectVervaardigingPlace';
    $vNote_1 = 'objectVervaardigingNote_1';
    $vNote_2 = 'objectVervaardigingNote_2';
    $vNote_3 = 'objectVervaardigingNote_3';
    $vNote_a1 = 'objectVervaardigingNote_a1';
    $vNote_a2 = 'objectVervaardigingNote_a2';
    $vNote_a3 = 'objectVervaardigingNote_a3';
    $vNote_a4 = 'objectVervaardigingNote_a4';

    if ( (isset($resultarray[$vervaardiger])) || (isset($resultarray[$vRol])) || (isset($resultarray[$vDate_1])) ||
          (isset($resultarray[$vDate_2])) || (isset($resultarray[$vDate_3])) || (isset($resultarray[$vDate_4])) ||
          (isset($resultarray[$vDate_5])) || (isset($resultarray[$vPlace])) || (isset($resultarray[$vNote_1])) ||
          (isset($resultarray[$vNote_2])) || (isset($resultarray[$vNote_3])) || (isset($resultarray[$vNote_a1])) ||
          (isset($resultarray[$vNote_a2])) || (isset($resultarray[$vNote_a3])) || (isset($resultarray[$vNote_a4])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'vervaardigerRelatie');
        $fields = array($vervaardiger, $vRol, $vDate_1, $vDate_2, $vDate_3, $vDate_4, $vDate_5, $vPlace,
            $vNote_1, $vNote_2, $vNote_3, $vNote_a1, $vNote_a2, $vNote_a3, $vNote_a4 );
        $aantal_vervaardiger = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_vervaardiger > 0) {

            $res_vervaardiger = $t_func->makeArray2($resultarray, $aantal_vervaardiger, $fields);
            //$log->logInfo('de relevante data', $res_vervaardiger);
            $aantal = $aantal_vervaardiger - 1;
            $i = 0;
            for ($i=0; $i <= ($aantal) ; $i++) {
                $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en vervaardiger " . $res_vervaardiger[$vervaardiger][$i]);
                $succes = $my_objects->processVariable($vn_left_id, 'ca_entities', $res_vervaardiger[$vervaardiger][$i], $relationship);
                //$log->logInfo('succes', $succes);

                //vervaardiger - ok
                $vs_vervaardiger = '';
                if (isset($res_vervaardiger[$vervaardiger][$i]) && (!empty($res_vervaardiger[$vervaardiger][$i])) ) {
                    $vn_right_id = $succes[1];
                    $va_right_string = $t_entity->getEntityNameByEntityID($vn_right_id);
                    $vs_vervaardiger = $va_right_string[0];
                }

                # De bijkomende info registreren
                //vervaardigerRol - ok
                $vervaardigerRol = $t_list->getItemIDFromList('vervaardiger_rol', 'blank');

                if (isset($res_vervaardiger[$vRol][$i]) && (!empty($res_vervaardiger[$vRol][$i])) ) {
                    $vervaardigerRol = $t_list->getItemIDFromListByLabel('vervaardiger_rol', $res_vervaardiger[$vRol][$i]);
                    if (!$vervaardigerRol) {
                        $log->logWarn ("WARNING: fout in vervaardigerRol", $res_vervaardiger[$vRol][$i]);
                    }
                }
                //vervaardigingDate
                #64
                $vDate_5i = $t_func->cleanDateSpecial($res_vervaardiger[$vDate_5][$i]);
                $vDate_3i = $t_func->cleanDateSpecial($res_vervaardiger[$vDate_3][$i]);

                $vervaardigingDate_1 =
                $t_func->stringJoin($t_func->cleanDate($vDate_5i, 'links'), $res_vervaardiger[$vDate_2][$i], " ");

                $vervaardigingDate_2 =
                $t_func->stringJoin($t_func->cleanDate($vDate_3i, 'rechts'), $res_vervaardiger[$vDate_1][$i], " ");

                $vervaardigingDate_3 =
                $t_func->stringJoin($vervaardigingDate_1, $vervaardigingDate_2, " - ", 'geen');

                $log->logInfo('originele datum ', $vervaardigingDate_3);

                //werkt dit wel ????
                //
                if ( (!empty($vervaardigingDate_3)) && ($t_texp->parse($vervaardigingDate_3)) ) {
                    $vervaardigingDate = ($vervaardigingDate_3);
                } elseif (empty($vervaardigingDate_3)) {
                } else {
                    $log->logWarn('WARNING: problemen met datum:', $t_texp->getParseErrorMessage());
                }

                $vervaardigingDate = ($vervaardigingDate_3);

                //vervaardigingPlace
                $vervaardigingPlace = '';
                if (isset($res_vervaardiger[$vPlace][$i]) && (!empty($res_vervaardiger[$vPlace][$i])) ) {
                    $vs_gemeente = $res_vervaardiger[$vPlace][$i];
                    //$vs_right_string =  '%'.$vs_gemeente.'%';
                    $vs_right_string =  $vs_gemeente.' - %';
                    $va_right_keys = $t_place->getPlaceIDsByNamePart($vs_right_string);

                    if (empty($va_right_keys)) {
                        $vs_right_string2 = $vs_gemeente.'%';
                        $va_right_keys2 = $t_place->getPlaceIDsByNamePart($vs_right_string2);

                        if (empty($va_right_keys2)) {

                            $log->logInfo("creating term ".$vs_gemeente." and adding labels for term");

                            $va_root = $t_place->getPlaceIDsByName('DIVERSEN');
                            $vn_root = $va_root[0];
                            $vn_place_id = $t_list->getItemIDFromList('place_types', 'city');
                            $vn_hierarchy_id = $t_list->getItemIDFromList('place_hierarchies', 'i1');
                            $vervaardigingPlace = $t_func->createPlace($vs_gemeente, $vn_root, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
                            $log->logInfo($vervaardigingPlace." aangemaakt");
                        } else {
                            if ((sizeof($va_right_keys2)) > 1 ) {
                                $log->logWarn("WARNING: problems with place", $vs_right_string2);
                                $log->logWarn('Meerdere kandidaten gevonden', $va_right_keys2);
                                $log->logWarn('We nemen de eerste place_id', $va_right_keys2[0]);
                            }
                            $vervaardigingPlace = $va_right_keys2[0];
                        }

                    }else{
                        if ((sizeof($va_right_keys)) > 1 ) {
                            $log->logWarn("WARNING: problems with place", $vs_right_string);
                            $log->logWarn('Meerdere kandidaten gevonden', $va_right_keys);
                            $log->logWarn('We nemen de eerste place_id', $va_right_keys[0]);
                        }
                        $vervaardigingPlace = $va_right_keys[0];
                    }
                } else {
                    $vervaardigingPlace = null;
                }

                //vervaardigingNote
                $vervaardigingNote = '';
                $temp = array();

                $zoek = array('circa', 'jaren', 'vóór', 'voor');
                /*
                if ( (strstr($vervaardigingDate, 'circa')) || (strstr($vervaardigingDate, 'jaren')) ||
                     (strstr($vervaardigingDate, 'vóór'))  || (strstr($vervaardigingDate, 'voor')) ) {
                    $temp[] = $vervaardigingDate;
                    $vervaardigingDate_org = $vervaardigingDate;
                    $vervaardigingDate = trim(str_replace($zoek, '', $vervaardigingDate));
                }
                 *
                 */

                $vNotes = array($res_vervaardiger[$vNote_1][$i], $res_vervaardiger[$vNote_2][$i], $res_vervaardiger[$vNote_3][$i],
                    $res_vervaardiger[$vNote_a1][$i], $res_vervaardiger[$vNote_a2][$i], $res_vervaardiger[$vNote_a3][$i],
                    $res_vervaardiger[$vNote_a4][$i]);
                foreach($vNotes as $value) {
                    if ( (isset($value)) && (!empty($value)) ) {
                        $temp[] = trim($value);
                    }
                }
                if ( (strlen($vDate_5i) < strlen($res_vervaardiger[$vDate_5][$i])) ||
                     (strlen($vDate_3i) < strlen($res_vervaardiger[$vDate_3][$i])) ) {
                    $temp[] = "Moeilijk leesbaar";
                }

                if (!empty($temp)) {
                    $vervaardigingNote = implode("\n", $temp);
                }

                $serienummer = '';

                if ( (!empty($vervaardiger)) || (!empty($vervaardigingDate)) || (!empty($vervaardigingPlace)) ||
                     (!empty($vervaardigingNote)) || (!empty($serienummer)) || ($vervaardigerRol !== "") ) {
                    $container = "objectVervaardigingInfo";
                    $data = array(  'locale_id'         =>	$pn_locale_id,
                                'objectVervaardiger'        =>	$vs_vervaardiger,
                                'vervaardigerRol'           =>	$vervaardigerRol,
                                'objectVervaardigingDate'   =>	$vervaardigingDate,
                                'objectVervaardigingPlace'  =>	$vervaardigingPlace,
                                'objectVervaardigingNote'   =>	$vervaardigingNote,
                                'modelSerienummer'          =>	$serienummer);

                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
                    //nodig? JA (objectvervaardigingInfo: 829 / objectvervaardigingPlace = 853)
                    if ($vervaardigingPlace !== null)  {

                        $qry1 = "select attribute_id from ca_attributes where element_id = 829 and table_num = 57
                                 and row_id = $vn_left_id ";

                        $qr_attr_ids = $o_db->query($qry1);

                        while($qr_attr_ids->nextRow()) {
                            $attribute_id = $qr_attr_ids->get('attribute_id');

                            $qry2 = "update ca_attribute_values
                                     set value_integer1 = $vervaardigingPlace
                                     where element_id = 853 and attribute_id = $attribute_id";
                            $o_db->query($qry2);
                            /*
                            //objectvervaardigingsDate 847)
                            if (isset($vervaardigingDate_org)) {
                                $qry3 = "update ca_attribute_values
                                        set value_longtext1 = $vervaardigingDate_org
                                        where element_id = 847 and attribute_id = $attribute_id";
                                $o_db->query($qry3);
                            }
                             *
                             */
                        }
                    }
                }
                unset($vn_right_id);
                unset($va_right_string);
                unset($vervaardiger);
                unset($vervaardigerRol);
                unset($vervaardigingDate_1);
                unset($vervaardigingDate_2);
                unset($vervaardigingDate_3);
                unset($vervaardigingDate);
                //unset($vervaardigingDate_org);
                unset($vervaardigingNote);
                unset($vervaardigingPlace);
                unset($vDate_5i);
                unset($vDate_3i);
                unset($temp);
                unset($data);
                unset($container);
                unset($succes);
            }
            unset($res_vervaardiger);
            unset($aantal);
            unset($i);
        }
        unset($aantal_vervaardiger);
        unset($fields);
        unset($relationship);
    }


//##############################################################################
//SCHERM 3: INHOUDELIJKE BESCHRIJVING
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//3.2. Trefwoorden -> List: cag_trefwoorden (opgelet: vocabulary list)
//Hoe moet deze lijst opgebouwd? Welke info moet erin?
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    # Vormen alle gegevens om tot één gemeenschappelijke array

    # eerst cag_lists.php uitvoeren voor aanmaak vocabulary list

    $tw_1 = 'trefwoord_1';
    $tw_2 = 'trefwoord_2';
    $tw_3 = 'trefwoord_3';
    $tw_4 = 'trefwoord_4';

    if ( (isset($resultarray[$tw_1])) || (isset($resultarray[$tw_2])) ||
         (isset($resultarray[$tw_3])) || (isset($resultarray[$tw_4])) ) {

        $fields = array($tw_1, $tw_2, $tw_3, $tw_4);
        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_vocabulary_terms', 'trefwoord');
        $temp = array();

        foreach($fields as $value) {
            //geen arrays
            if ( (isset($resultarray[$value])) && (!empty($resultarray[$value])) ) {
                if (!is_array($resultarray[$value])) {
                    $temp[] = $resultarray[$tw_1];
                } else {
                    $temp = (array_merge($temp, $resultarray[$value]));
                }
            }
        }

        $trefwoord = array_unique($temp);
        $log->logInfo('de trefwoorden-array', $trefwoord);

        //verbanden leggen
        foreach(($trefwoord) as $key => $value) {
            if ( (!is_array($trefwoord[$key])) && (!empty($trefwoord[$key])) ) {

                $vs_right_string = $value;
                $search_string = $vs_right_string;
                $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en cag_trefwoord  " . $vs_right_string);

                $succes = $my_objects->createRelationship($vn_left_id, "ca_list_items", $search_string, $relationship);

                $log->logInfo('succes', $succes);

                unset($vs_right_string);
                unset($search_string);
            }
        }
        unset($temp);
        unset($relationship);
        unset($trefwoord);
        unset($fields);
    }

//##############################################################################
//SCHERM 4: VERWERVING
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.1. Gerelateerde collecties -> [is_part_of] relatie naar ca_collections -> apart programma
//(2) velden: collectieBeschrijving_1 en collectieBeschrijving_2 -> 2 afzonderlijke relaties
// veronderstellen GEEN ARRAYS en komt niet voor in SintTruiden
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


    $col_1 = 'collectieBeschrijving_1';
    $col_2 = 'collectieBeschrijving_2';
    $col = array($col_1, $col_2);

    foreach ($col as $collectie) {
        if ( (isset($resultarray[$collectie])) && (!empty($resultarray[$collectie])) ) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_collections', 'part_of');
            $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en collectie " . $resultarray[$collectie]);
            $my_objects->processVariable($vn_left_id, 'ca_collections', $resultarray[$collectie], $relationship);
            unset($relationship);
        }
    }

    /* refactoring dubbele code
    if ( (isset($resultarray[$col_1])) && (!empty($resultarray[$col_1])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_collections', 'part_of');
        $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en collectie " . $resultarray[$col_1]);
        $my_objects->processVariable($vn_left_id, 'ca_collections', $resultarray[$col_1], $relationship);
        unset($relationship);
    }

    if ( (isset($resultarray[$col_2])) && (!empty($resultarray[$col_2])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_collections', 'part_of');
        $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en collectie " . $resultarray[$col_2]);
        $my_objects->processVariable($vn_left_id, 'ca_collections', $resultarray[$col_2], $relationship);
        unset($relationship);
    }
     *
     */

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.2. Bewaarinstelling -> ? relatie naar ca_entities -> apart programma
//[eigenaar_van] of [bewaarinstalling_van] relatie
//(3) velden: [eigenaar_van], [bewaarinstelling_van] en [bewaarinstelling]
// veronderstellen GEEN ARRAYS en komt niet voor in Sint-Truiden
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


    $eigenaar = 'eigenaar_van';

    if ( (isset($resultarray[$eigenaar])) && (!empty($resultarray[$eigenaar])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'eigenaarRelatie');
        $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en entiteit(eigenaar_van) " . $resultarray[$eigenaar]);
        $my_objects->processVariable($vn_left_id, 'ca_entities', $resultarray[$eigenaar], $relationship);
        unset($relationship);
    }

    $bewaar = 'bewaarinstelling';

    if ( (isset($resultarray[$bewaar])) && (!empty($resultarray[$bewaar])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'bewaarinstelling');
        $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en entiteit(bewaarinstelling) " . $resultarray[$bewaar]);
        $my_objects->processVariable($vn_left_id, 'ca_entities', $resultarray[$bewaar], $relationship);
        unset($relationship);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.3. Inventarisnummer op bewaarplaats -> container: objectInventarisnrBpltsInfo (id=235)
// Instelling:  objectInventarisnrBplts_inst:                                   (id=415)
// overname van de instellingsnaam uit bewaarinstelling_ven
// Nummer :     objectInventarisnrBplts                                         (id=417)
// (2)velden: Syntax -> Bplts_2: Bplts_1
// (arrays mogelijk) - single field array container
// Moet mee met bovenstaande relatie naar ca_entities ingevuld worden
// komt niet voor in SintTruiden    // hier zijn WEL ARRAYS

    # eerst aanmaken met cag_entities

    $bewaar_van = 'bewaarinstelling_van';
    $plaats_1 = 'objectInventarisnrBplts_1';
    $plaats_2 = 'objectInventarisnrBplts_2';

    if ( (isset($resultarray[$bewaar_van])) || (isset($resultarray[$plaats_1])) || (isset($resultarray[$plaats_2])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'bewaarinstelling');
        $fields = array($bewaar_van, $plaats_1, $plaats_2);
        $aantal_inst = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_inst > 0) {

            $res_inst = $t_func->makeArray2($resultarray, $aantal_inst, $fields);
            $aantal = $aantal_inst - 1 ;
            $i = 0;
            for ($i=0; $i <= ($aantal); $i++) {

                if ( (isset($res_inst[$bewaar_van][$i]) && !empty($res_inst[$bewaar_van][$i])) ) {

                    $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en entiteit(bewaarinstelling_van) " . $res_inst[$bewaar_van][$i]);
                    $succes = $my_objects->processVariable($vn_left_id, 'ca_entities', $res_inst[$bewaar_van][$i], $relationship);
                }

                /* door #69 niet meer nodig
                $plaats_temp = '';
                //als de relatie gelegd is vullen we de inventariscontainer in
                if ( (isset($res_inst[$plaats_2][$i])) && (!empty($res_inst[$plaats_2][$i])) &&
                    (isset($res_inst[$plaats_1][$i])) && (!empty($res_inst[$plaats_1][$i])) ) {
                    $plaats_temp = $res_inst[$plaats_2][$i].': '.$res_inst[$plaats_1][$i];
                }
                if ( (isset($res_inst[$plaats_2][$i])) && (!empty($res_inst[$plaats_2][$i])) &&
                    (!isset($res_inst[$plaats_1][$i])) ) {
                    $plaats_temp = $res_inst[$plaats_2][$i];
                }
                if ( (!isset($res_inst[$plaats_2][$i])) &&
                    (isset($res_inst[$plaats_1][$i])) && (!empty($res_inst[$plaats_1][$i])) ) {
                     $plaats_temp = $res_inst[$plaats_1][$i];
                }
                $plaats = trim($plaats_temp);
                 *
                 */
                if ( (!empty($res_inst[$plaats_1][$i]) || !empty($res_inst[$plaats_2][$i]) )) {

                    //$vn_right_id = $succes[1];
                    //$va_right_string = $t_entity->getEntityNameByEntityID($vn_right_id);
                    //$vs_right_string = $va_right_string[0];
                    $vs_right_string = $res_inst[$bewaar_van][$i];

                    $data = array(  'locale_id'                     =>  $pn_locale_id,
                                    'objectInventarisnrBplts_inst'  =>	$vs_right_string,
                                    'objectInventarisnrBplts'       =>	$res_inst[$plaats_1][$i],
                                    'objectInventarisnrBpltsType'   =>  $res_inst[$plaats_2][$i] );
                    $container = "objectInventarisnrBpltsInfo";

                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
                }
                //unset($plaats);
                //unset($plaats_temp);
                unset($vn_right_id);
                unset($va_right_string);
                unset($container);
                unset($data);
                unset($succes);
            }
            unset($i);
            unset($aantal);
            unset($res_inst);
        }
        unset($aantal_inst);
        unset($fields);
        unset($relationship);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.4. Verworven van -> relatie naar ca_entities -> apart programma
//[vorigeeigenaar] relatie


    $verworven = 'acquisitionSource';

    if ( (isset($resultarray[$verworven])) && (!empty($resultarray[$verworven])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'vorigeeigenaar');
        $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en entiteit(acquisitionSource) " . $resultarray[$verworven]);
        $my_objects->processVariable($vn_left_id, 'ca_entities', $resultarray[$verworven], $relationship);
        unset($relationship);
    }

//##############################################################################
//SCHERM 6: RELATIES
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 6.1. Gerelateerde object ->
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 6.3. Gerelateerd object -> Bijkomende info
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


    $related = 'related';
    $rnotes_1 = 'related_object_notes_1';
    $rnotes_2 = 'related_object_notes_2';

    if ( (isset($resultarray[$related])) || (isset($resultarray[$rnotes_1])) || (isset($resultarray[$rnotes_2])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_objects', 'related');
        $fields = array($related, $rnotes_1, $rnotes_2);
        $aantal_objecten = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_objecten > 0) {

            $res_objecten = $t_func->makeArray2($resultarray, $aantal_objecten, $fields);
            $aantal = $aantal_objecten - 1 ;
            $i = 0;
            for ($i=0; $i <= ($aantal) ; $i++) {

                $log->logInfo("relatie leggen tussen object " . $vn_left_id . " en object(related) " . $res_objecten[$related][$i]);
                $succes = $my_objects->processVariable($vn_left_id, 'ca_objects', $res_objecten[$related][$i], $relationship);

                // de bijkomende info registreren
                $notes = "";
                if (  (!empty($res_objecten[$rnotes_2][$i]))  ){
                    if (  (!empty($res_objecten[$rnotes_1][$i]))  ){
                        $notes = trim($res_objecten[$rnotes_2][$i])."\n".trim($res_objecten[$rnotes_1][$i]);
                    }else{
                        $notes = trim($res_objecten[$rnotes_2][$i]);
                    }
                } else {
                    if ( (!empty($res_objecten[$rnotes_1][$i]))  ){
                        $notes = trim($res_objecten[$rnotes_1][$i]);
                    }else{
                        $notes = "";
                    }
                }

                if (!empty($notes)) {

                    $vn_right_id = $succes[1];
                    $va_right_string = $t_object->getObjectNameByObjectID($vn_right_id);

                    $container = "'related_object_notesInfo'";
                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'related_object_rel'        =>	$vn_right_id.": ".$va_right_string[0],
                                    'related_object_notes'      =>	trim($notes) );

                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
                }
                unset($vn_right_id);
                unset($va_right_string);
                unset($container);
                unset($data);
                unset($notes);
                unset($succes);
            }
            unset($i);
            unset($aantal);
            unset($res_objecten);
        }
        unset($aantal_objecten);
        unset($fields);
        unset($relationship);
    }

    $teller = $teller + 1;

    $reader->next();
}

$reader->close();

$log->logInfo('EINDE VERWERKING');