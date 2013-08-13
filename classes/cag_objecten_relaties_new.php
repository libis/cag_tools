<?php
/*Doel van dit programma:
 * My_CAG  TYPE=RELATION - META=RELAT - PART="" (objecten.xml)
 * My_CAG  TYPE=RELATION - META=RELAT - PART=""ST (sinttruiden.xml)
 *
 */
error_reporting(-1);
set_time_limit(0);
$type = "SERVER";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}
require_once(__MY_DIR_2__."/cag_tools/classes/My_CAG.php");

define("__PROG__","objects_relaties_".__PART__.__META__);
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__.'/ca_entities.php');
require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
require_once(__MY_DIR_2__.'/cag_tools/classes/ca_objects_bis.php');
require_once(__MY_DIR_2__.'/cag_tools/classes/ca_places_bis.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$t_relatie = new ca_relationship_types();

$o_db = new Db();
$t_place = new ca_places_bis();

$t_texp = new TimeExpressionParser(null, null, true);
$t_texp->setLanguage('nl_NL');

$t_entity = new ca_entities();

$t_object = new ca_objects_bis();
$t_object->setMode(ACCESS_WRITE);
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
while ($reader->name === 'record' )
{
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $message =  "==========".($teller)."========";
    $log->logInfo($message);

    //------------------------------------------------------------------------------
    //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
    $idno = sprintf('%08d', $teller);
    // voor sinttruiden
    if (__PART__ === "ST"){
        $idno = 'st_'.$idno;
    }
    if (__PART__ === "TEST"){
        $idno = 'test_'.$idno;
    }
    $log->logInfo("idno: ",$idno);
    $log->logInfo("adlibObjectNummer", $resultarray['adlibObjectNummer']);

    if (__TYPE__ === "RELATION"){
        $va_left_keys = $t_object->getObjectIDsByIdno($idno);

        if ((sizeof($va_left_keys)) > 1 )
        {
            $message =  "WARNING: PROBLEMS with object {$idno} : meerdere records gevonden. We nemen het eerste !!!!! \n";
            $log->logInfo($message);
        }
        $vn_left_id = $va_left_keys[0];
        $t_object->load($vn_left_id);
        $t_object->getPrimaryKey();
        $t_object->set('object_id', $vn_left_id);

        $log->logInfo("object_id: ",$vn_left_id);
    }

//##############################################################################
//SCHERM 1: IDENTIFICATIE
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 1. Documentatie publicatie -> documentatieRelatie naar ca_occurrences -> apart programma
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

    # eerst cag_occurrences.php uitvoeren voor aanmaak occurrences
    # veronderstellen GEEN ARRAYS -

    if (__META__ === "documentatie" || __META__ === "RELAT") {
    // komt niet voor bij sint-truiden
        # bepalen van de zoeksleutel occurrences
        //dit stuk code is identiek aan hetgeen gebruikt is in cag_occurrences om deze aan te maken
        //we laten dit dus ongemoeid & in principe bestaan alle occurrences
        if ( (!isset($resultarray['preferred_label_occur_2'])) && (isset($resultarray['refPagina'])) ) {
            //als er enkel een paginanr is, doen we niks
            $log->logInfo("WARNING: refPagina gegeven zonder preferred_label_occur_2 (documentation.title)");

            //we doen enkel iets als preferred_label_occur_2 bestaat
        }elseif (isset($resultarray['preferred_label_occur_2'])) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_occurrences', 'documentatieRelatie');

            if ( (is_array($resultarray['preferred_label_occur_2'])) ) {
                if (isset($resultarray['preferred_label_occur_1'])) {
                    $sleutel = $resultarray['preferred_label_occur_1'].' '.$resultarray['preferred_label_occur_2'][0];
                }else{
                    $sleutel = $resultarray['preferred_label_occur_2'][0];
                }
                $sleutel = $sleutel.$resultarray['preferred_label_occur_2'][1];
            }else{
                if (isset($resultarray['preferred_label_occur_1'])) {
                    $sleutel = $resultarray['preferred_label_occur_1'].' '.$resultarray['preferred_label_occur_2'];
                }else{
                    $sleutel = $resultarray['preferred_label_occur_2'];
                }
            }

            $vs_right_string = $sleutel;

            $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

            $message = $t_func->createRelationship($t_object, "ca_occurrences", $vs_right_string, $relationship);
            $log->logInfo($message);
        }
        unset($sleutel);
        unset($relationship);

        if ( (isset($resultarray['refPagina'])) && (!is_array($resultarray['refPagina'])) && (!empty($resultarray['refPagina'])) ) {

            $data = array(  'refPaginaDoc'      =>  $vs_right_string,
                            'refPagina'         =>  trim($resultarray['refPagina']),
                            'locale_id'         =>  $pn_locale_id);
            $container = "refPaginaInfo";

            $message = $t_func->createContainer($t_object, $data, $vs_right_string, $container);
            $log->logInfo($message);

        }
        unset($vs_right_string);
        unset($data);
        unset($container);
    }

//##############################################################################
//SCHERM 2: FYSIEKE BESCHRIJVING
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 2. Vervaardiger -> vervaardigerRelatie naar ca_entities -> apart programma
// Vormen alles om tot array

    # eerst cag_entities uitvoeren voor aanmaak entities op basis van objecten.xml en sinttruiden.xml
    //objecten -> enkele 13 problemen met een link naar entiteit Boerenbond !!!!!!
    //sinttruiden -> 34 onbekende entiteiten -> Aanmaken ??? DONE

    if (__META__ === "vervaardiger" || __META__ === "RELAT") {

        $fields = array('vervaardiger');
        $verv_aantal = $t_func->Herhalen($resultarray, $fields);

        if ($verv_aantal > 0) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'vervaardigerRelatie');
            $vervaardiger = $t_func->makeArray2($resultarray, $verv_aantal, $fields);
            $aantal = $verv_aantal - 1;

            for ($i=0; $i <= $aantal; $i++) {

                if (!empty($vervaardiger['vervaardiger'][$i])) {

                    $vs_right_string = trim($vervaardiger['vervaardiger'][$i]);

                    $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $message = $t_func->createRelationship($t_object, "ca_entities", $vs_right_string, $relationship);
                    $log->logInfo($message);

                    unset($vs_right_string);
                }
            }
            unset($vervaardiger);
            unset($aantal);
            unset($relationship);
        }
        unset($fields);
        unset($verv_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 3. Vervaardiging -> container: objectVervaardigingInfo -> WACHTEN!!!!!!! op ca_places (id=268) -> nu 274
//    Vervaardiger->Text: vervaardiger                                          (id=424) -> nu 276
//    Rol ->        List: vervaardiger_rol -> vervaardigerRol                   (id=270) -> nu 278
//    Datering->    DateRange: objectVervaardigingDate (5) syntax: 5 2 - 3 1 (4 komt niet voor)(id=272) -> nu 280
//    Plaats->      Place: objectVervaardigingPlace - link naar ca_places?      (id=274) -> nu 282
//    Bijk.Info->   Text: objectVervaardigingNote  (3)                          (id=276) -> nu 284
//    Serienr->     Text: modelSerienummer: nieuw veld                          (id=279) -> nu 287

    # eerst ca_places aanmaken, alvorens deze data in te laden !!!!!!!!

    if (__META__ === "vervaardiging" || __META__ === "RELAT") {

        $fields = array('vervaardiger', 'vervaardigerRol', 'objectVervaardigingDate_1', 'objectVervaardigingDate_2',
            'objectVervaardigingDate_3', 'objectVervaardigingDate_4', 'objectVervaardigingDate_5', 'objectVervaardigingPlace',
            'objectVervaardigingNote_1', 'objectVervaardigingNote_2', 'objectVervaardigingNote_3', 'objectVervaardigingNote_a1',
            'objectVervaardigingNote_a2', 'objectVervaardigingNote_a3', 'objectVervaardigingNote_a4' );
        $aantal_vervaardiger = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_vervaardiger > 0) {

            $container = "objectVervaardigingInfo";
            //$output = array('vervaardiger','vervaardigerRol','vervaardigingDate','vervaardigingPlace','vervaardigingNote','serienummer');
            $res_vervaardiger = $t_func->makeArray2($resultarray, $aantal_vervaardiger, $fields);
            $aantal = $aantal_vervaardiger - 1;

            for ($i=0; $i <= ($aantal) ; $i++) {

                //$t_func->Initialiseer($output);
                $vervaardiger = '';
                $vervaardigerRol = '';
                $vervaardigingDate = '';
                $vervaardigingPlace = '';
                $vervaardigingNote = '';
                $serienummer = '';

                //vervaardiger - ok
                if (isset($res_vervaardiger['vervaardiger'][$i])
                && (!empty($res_vervaardiger['vervaardiger'][$i])) ) {

                    $va_right_keys = $t_entity->getEntityIDsByName('', $res_vervaardiger['vervaardiger'][$i]);
                    $t_entity->load($va_right_keys[0]);
                    $t_entity->getPrimaryKey();;
                    $vervaardiger = $t_entity->getLabelForDisplay();
                }

                //vervaardigerRol - ok
                if (isset($res_vervaardiger['vervaardigerRol'][$i])
                && (!empty($res_vervaardiger['vervaardigerRol'][$i])) ) {
                    $vervaardigerRol = $t_list->getItemIDFromListByLabel('vervaardiger_rol', $res_vervaardiger['vervaardigerRol'][$i]);
                    if (!$vervaardigerRol) {
                        $log->logInfo ("WARNING: fout in vervaardigerRol", $res_vervaardiger['vervaardigerRol'][$i]);
                        $vervaardigerRol = $t_list->getItemIDFromList('vervaardiger_rol', 'blank');
                    }
                }else{
                    $vervaardigerRol = $t_list->getItemIDFromList('vervaardiger_rol', 'blank');
                }
                //vervaardigingDate
                $vervaardigingDate_1 =
                     $t_func->stringJoin($t_func->cleanDate($res_vervaardiger['objectVervaardigingDate_5'][$i],'links'),
                             $res_vervaardiger['objectVervaardigingDate_2'][$i], " ");

                $vervaardigingDate_2 =
                     $t_func->stringJoin($t_func->cleanDate($res_vervaardiger['objectVervaardigingDate_3'][$i],'rechts'),
                             $res_vervaardiger['objectVervaardigingDate_1'][$i], " ");

                $vervaardigingDate_3 =
                     $t_func->stringJoin($vervaardigingDate_1, $vervaardigingDate_2, " - ", 'geen');

                $log->logInfo('originele datum ', $vervaardigingDate_3);

                if ( (!empty($vervaardigingDate_3)) && ($t_texp->parse($vervaardigingDate_3)) ) {
                    $vervaardigingDate = ($vervaardigingDate_3);
                } else {
                    $log->logWarn('WARNING: problemen met datum:', $t_texp->getParseErrorMessage());
                }

                //vervaardigingPlace
                if (isset($res_vervaardiger['objectVervaardigingPlace'][$i]) && (!empty($res_vervaardiger['objectVervaardigingPlace'][$i])) ) {
                    $vs_gemeente = $res_vervaardiger['objectVervaardigingPlace'][$i];
                    //$vs_right_string =  '%'.$vs_gemeente.'%';
                    $vs_right_string =  $vs_gemeente.'%';
                    $va_right_keys = $t_place->getPlaceIDsByNamePart($vs_right_string);

                    if (empty($va_right_keys)) {
                        $log->logInfo("creating term ".$vs_gemeente." and adding labels for term");

                        $va_root = $t_place->getPlaceIDsByName('DIVERSEN');
                        $vn_root = $va_root[0];
                        $vn_place_id = $t_list->getItemIDFromList('place_types', 'city');
                        $vn_hierarchy_id = $t_list->getItemIDFromList('place_hierarchies', 'i1');
                        $vervaardigingPlace = $t_func->createPlace($vs_gemeente, $vn_root, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
                        $log->logInfo($vervaardigingPlace." aangemaakt");
                    }else{
                        $vervaardigingPlace = $va_right_keys[0];
                    }
                }else{
                    $vervaardigingPlace = null;
                }

                //vervaardigingNote
                $temp = array();

                if ( (isset($res_vervaardiger['objectVervaardigingNote_1'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_1'][$i])) ) ) {
                    $temp[] =  $res_vervaardiger['objectVervaardigingNote_1'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_2'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_2'][$i])) ) ) {
                    $temp[] = $res_vervaardiger['objectVervaardigingNote_2'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_3'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_3'][$i])) ) ) {
                    $temp[] = $res_vervaardiger['objectVervaardigingNote_3'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_a1'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_a1'][$i])) ) ) {
                    $temp[] =  $res_vervaardiger['objectVervaardigingNote_a1'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_a2'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_a2'][$i])) ) ) {
                    $temp[] = $res_vervaardiger['objectVervaardigingNote_a2'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_a3'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_a3'][$i])) ) ) {
                    $temp[] = $res_vervaardiger['objectVervaardigingNote_a3'][$i];
                }
                if ( (isset($res_vervaardiger['objectVervaardigingNote_a4'][$i]) && (!empty($res_vervaardiger['objectVervaardigingNote_a4'][$i])) ) ) {
                    $temp[] = $res_vervaardiger['objectVervaardigingNote_a4'][$i];
                }
                if (!empty($temp)) {
                    $vervaardigingNote = implode("\n", $temp);
                }

                if ( (!empty($vervaardiger)) || (!empty($vervaardigingDate)) || (!empty($vervaardigingPlace)) ||
                     (!empty($vervaardigingNote)) || (!empty($serienummer)) || ($vervaardigerRol != "") ) {

                    $data = array(  'locale_id'         =>	$pn_locale_id,
                                'objectVervaardiger'        =>	$vervaardiger,
                                'vervaardigerRol'           =>	$vervaardigerRol,
                                'objectVervaardigingDate'   =>	$vervaardigingDate,
                                'objectVervaardigingPlace'  =>	$vervaardigingPlace,
                                'objectVervaardigingNote'   =>	$vervaardigingNote,
                                'modelSerienummer'          =>	$serienummer);

                    $message = $t_func->createContainer($t_object, $data, $vervaardiger, $container);
                    $log->logInfo($message);
                    $log->logInfo("de gegevens: ", $data);

                    //nodig? JA
                    if ($vervaardigingPlace != null)  {
                        $qry1 = "select attribute_id from ca_attributes where element_id = 274 and table_num = 57
                                 and row_id = ".$vn_left_id;

                        $qr_attr_ids = $o_db->query($qry1);

                        while($qr_attr_ids->nextRow()) {
                            $attribute_id = $qr_attr_ids->get('attribute_id');

                            $qry2 = "update ca_attribute_values
                                     set value_integer1 = ".$vervaardigingPlace.
                                    " where element_id = 282 and attribute_id = ".$attribute_id;

                            $o_db->query($qry2);
                        }
                    }
                }
                //$t_func->Vernietig($output);
                unset($vervaardiger);
                unset($vervaardigerRol);
                unset($vervaardigingDate_1);
                unset($vervaardigingDate_2);
                unset($vervaardigingDate_3);
                unset($vervaardigingDate);
                unset($vervaardigingNote);
                unset($vervaardigingPlace);
                unset($temp);
                unset($data);
            }
            unset($res_vervaardiger);
            unset($aantal);
            unset($container);
        }
        unset($aantal_vervaardiger);
        unset($fields);
    }

//##############################################################################
//SCHERM 3: INHOUDELIJKE BESCHRIJVING
//##############################################################################
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 4. Trefwoorden -> List: cag_trefwoorden (opgelet: vocabulary list)
//Hoe moet deze lijst opgebouwd? Welke info moet erin?
//Vormen alle gegevens om tot één gemeenschappelijke array

    # eerst cag_lists.php uitvoeren voor aanmaak vocabulary list

    if (__META__ === "trefwoorden" || __META__ === "RELAT") {

        if ( (isset($resultarray['trefwoord_1'])) || (isset($resultarray['trefwoord_2'])) ||
             (isset($resultarray['trefwoord_3'])) || (isset($resultarray['trefwoord_4'])) ) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_vocabulary_terms', 'trefwoord');
            $temp = array();

            //geen arrays
            if ( (isset($resultarray['trefwoord_1'])) && (!is_array($resultarray['trefwoord_1'])) ) {
                $temp[] = $resultarray['trefwoord_1'];
            }
            if ( (isset($resultarray['trefwoord_3'])) && (!is_array($resultarray['trefwoord_3'])) ) {
                $temp[] = $resultarray['trefwoord_3'];
            }
            if ( (isset($resultarray['trefwoord_4'])) && (!is_array($resultarray['trefwoord_4'])) ) {
                $temp[] = $resultarray['trefwoord_4'];
            }
            //wel arrays
            if ( (isset($resultarray['trefwoord_1'])) && (is_array($resultarray['trefwoord_1'])) ) {
                $temp = (array_merge($temp,$resultarray['trefwoord_1']));
            }
            if ( (isset($resultarray['trefwoord_3'])) && (is_array($resultarray['trefwoord_3'])) ) {
                $temp = (array_merge($temp,$resultarray['trefwoord_3']));
            }
            if ( (isset($resultarray['trefwoord_4'])) && (is_array($resultarray['trefwoord_4'])) ) {
                $temp = (array_merge($temp,$resultarray['trefwoord_4']));
            }
            $resultarray['trefwoord'] = array_unique($temp);

            //verbanden leggen
            foreach(($resultarray['trefwoord']) as $key => $value) {
                if ( (!is_array($resultarray['trefwoord'][$key])) && (!empty($resultarray['trefwoord'][$key])) ) {

                    $vs_right_string = $value;

                    $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $message = $t_func->createRelationship($t_object, "ca_objects_x_vocabulary_terms", $vs_right_string, $relationship);
                    $log->logInfo($message);
                }
                unset($vs_right_string);
            }
            unset($temp);
            unset($relationship);
        }
    }

//##############################################################################
//SCHERM 4: VERWERVING
//##############################################################################
// 5. Gerelateerde collecties -> [is_part_of] relatie naar ca_collections -> apart programma
//(2) velden: collectieBeschrijving_1 en collectieBeschrijving_2 -> 2 afzonderlijke relaties
// veronderstellen GEEN ARRAYS en komt niet voor in SintTruiden

    if (__META__ === "collecties" || __META__ === "RELAT") {

        if (isset($resultarray['collectieBeschrijving_1'])) {

            if( (!is_array($resultarray['collectieBeschrijving_1'])) && (!empty($resultarray['collectieBeschrijving_1'])) ) {

                $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_collections', 'part_of');

                $vs_right_string = $resultarray['collectieBeschrijving_1'];

                $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                $message = $t_func->createRelationship($t_object, "ca_collections", $vs_right_string, $relationship);
                $log->logInfo($message);

                unset($vs_right_string);
                unset($relationship);

            }else{
                $log->logInfo("WARNING: overwachte data (array) in veld 'collectieBeschrijving_1'");
            }
        }

        if (isset($resultarray['collectieBeschrijving_2'])) {
            if ( (!is_array($resultarray['collectieBeschrijving_2'])) && (!empty($resultarray['collectieBeschrijving_2'])) ) {

                $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_collections', 'part_of');

                $vs_right_string = $resultarray['collectieBeschrijving_2'];

                $log->logInfo("relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                $message = $t_func->createRelationship($t_object, "ca_collections", $vs_right_string, $relationship);
                $log->logInfo($message);

                unset($vs_right_string);
                unset($relationship);

            }else{
                $log->logInfo("WARNING: overwachte data (array) in veld 'collectieBeschrijving_2'");
            }
        }
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 6. Bewaarinstelling -> ? relatie naar ca_entities -> apart programma
//[eigenaar_van] of [bewaarinstelling_van] relatie
//(2) velden: [eigenaar_van] en [bewaarinstelling]
// GEEN arrays voor 'eigenaar_van' en 'bewaarinstelling' -> verwerking geen probleem
// veronderstellen GEEN ARRAYS en komt niet voor in Sint-Truiden

    # eerst aanmaken met cag_entities

    if (__META__ === "bewaarinstelling" || __META__ === "RELAT") {

        if (isset($resultarray['eigenaar_van'])) {
            if ( (!is_array($resultarray['eigenaar_van'])) && (!empty($resultarray['eigenaar_van'])) ) {

                $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'eigenaarRelatie');

                $vs_right_string = $resultarray['eigenaar_van'];

                $log->logInfo("2. relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                $message = $t_func->createRelationship($t_object, "ca_entities", $vs_right_string, $relationship);
                $log->logInfo($message);

                unset($vs_right_string);
                unset($relationship);
            }else{
                $log->logInfo("WARNING: overwachte data (array) in veld 'eigenaar_van'");
            }
        }

        if (isset($resultarray['bewaarinstelling'])) {
            if( (!is_array($resultarray['bewaarinstelling'])) && (!empty($resultarray['bewaarinstelling'])) ) {

                $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'bewaarinstelling');

                $vs_right_string = $resultarray['bewaarinstelling'];

                $log->logInfo("3. relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                $message = $t_func->createRelationship($t_object, "ca_entities", $vs_right_string, $relationship);
                $log->logInfo($message);

                unset($vs_right_string);
                unset($relationship);
            }else{
                $log->logInfo("WARNING: overwachte data in veld 'bewaarinstelling'");
            }
        }
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// [bewaarinstelling_van]
// 7. Inventarisnummer op bewaarplaats -> container: objectInventarisnrBpltsInfo(id=235)
// Instelling:  objectInventarisnrBplts_inst:                                   (id=415)
// overname van de instellingsnaam uit bewaarinstelling_van
// Nummer :     objectInventarisnrBplts                                         (id=417)
// (2)velden: Syntax -> Bplts_2: Bplts_1
// komt niet voor in SintTruiden
// hier zijn WEL ARRAYS

    # eerst aanmaken met cag_entities

    if (__META__ === "inventarisnr" || __META__ === "RELAT") {

        $fields = array('bewaarinstelling_van', 'objectInventarisnrBplts_1', 'objectInventarisnrBplts_2');
        $aantal_inst = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_inst > 0) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'bewaarinstelling');
            $res_inst = $t_func->makeArray2($resultarray, $aantal_inst, $fields);
            $aantal = $aantal_inst - 1 ;

            for ($i=0; $i <= ($aantal); $i++) {

                if ( (isset($res_inst['bewaarinstelling_van'][$i])) && (!empty($res_inst['bewaarinstelling_van'][$i])) ) {

                    $vs_right_string = $res_inst['bewaarinstelling_van'][$i];

                    $log->logInfo("1. relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $message = $t_func->createRelationship($t_object, "ca_entities", $vs_right_string, $relationship);
                    $log->logInfo($message);
                }

                //als de relatie gelegd is vullen we de inventariscontainer in
                if ( (isset($res_inst['objectInventarisnrBplts_2'][$i])) && (!empty($res_inst['objectInventarisnrBplts_2'][$i])) &&
                     (isset($res_inst['objectInventarisnrBplts_1'][$i])) && (!empty($res_inst['objectInventarisnrBplts_1'][$i])) ) {
                    $res_inst['objectInventarisBplts'][$i] =
                            $res_inst['objectInventarisnrBplts_2'][$i].': '.$res_inst['objectInventarisnrBplts_1'][$i];
                }
                if ( (isset($res_inst['objectInventarisnrBplts_2'][$i])) && (!empty($res_inst['objectInventarisnrBplts_2'][$i])) &&
                     (!isset($res_inst['objectInventarisnrBplts_1'][$i])) ) {
                    $res_inst['objectInventarisBplts'][$i] = $res_inst['objectInventarisnrBplts_2'][$i];
                }
                if ( (!isset($res_inst['objectInventarisnrBplts_2'][$i])) && (isset($res_inst['objectInventarisnrBplts_1'][$i])) &&
                     (!empty($res_inst['objectInventarisnrBplts_2'][$i])) ) {
                     $res_inst['objectInventarisBplts'][$i] = $res_inst['objectInventarisnrBplts_1'][$i];
                }

                if ( (isset($res_inst['objectInventarisBplts'][$i])) && (!is_array($res_inst['objectInventarisBplts'][$i])) &&
                     (!empty($res_inst['objectInventarisBplts'][$i])) ) {

                    $data = array(  'locale_id'                     =>  $pn_locale_id,
                                    'objectInventarisnrBplts_inst'  =>	$vs_right_string,
                                    'objectInventarisnrBplts'       =>	$res_inst['objectInventarisBplts'][$i]);
                    $container = "objectInventarisnrBpltsInfo";

                    $message = $t_func->createContainer($t_object, $data, $vs_right_string, $container);
                    $log->logInfo($message);
                }
                unset($vs_right_string);
                unset($data);
                unset($container);
            }
            unset($res_inst);
            unset($relationship);
            unset($aantal);
        }
        unset($fields);
        unset($aantal_inst);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//8. Verworven van -> relatie naar ca_entities -> apart programma
//[vorigeeigenaar] relatie     relatie= 99

    # # eerst aanmaken met cag_entities

    if (__META__ === "verworven" || __META__ === "RELAT") {

        $fields = array('acquisitionSource');
        $aantal_acq = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_acq > 0) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'vorigeeigenaar');
            $res_acq = $t_func->makeArray2($resultarray, $aantal_acq, $fields);
            $aantal = $aantal_acq - 1 ;

            for ($i=0; $i <= ($aantal); $i++) {

                if ( (isset($res_acq['acquisitionSource'][$i])) && (!empty($res_acq['acquisitionSource'][$i])) ) {

                    $vs_right_string = trim($res_acq['acquisitionSource'][$i]);

                    $log->logInfo("1. relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $message = $t_func->createRelationship($t_object, "ca_entities", $vs_right_string, $relationship);
                    $log->logInfo($message);
                }
                unset($vs_right_string);
            }
            unset($aantal);
            unset($res_acq);
            unset($relationship);
        }
        unset($aantal_acq);
        unset($fields);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// 9. Related -> relatie naar ca_objects
// [related] relatie     relatie= 127

    # eerst alle objecten aangemaakt zijn

    if (__META__ === "related" || __META__ === "RELAT") {
        // de relatie leggen
        $fields = array('related', 'related_object_notes_1', 'related_object_notes_2');
        $aantal_objecten = $t_func->Herhalen($resultarray, $fields);

        if ($aantal_objecten > 0) {

            $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_objects', 'related');
            $res_objecten = $t_func->makeArray2($resultarray, $aantal_objecten, $fields);
            $aantal = $aantal_objecten - 1 ;

            for ($i=0; $i <= ($aantal) ; $i++) {

                if ( (isset($res_objecten['related'][$i])) && (!empty($res_objecten['related'][$i])) ) {

                    $vs_right_string = trim($res_objecten['related'][$i]);

                    $log->logInfo("1. relatie leggen tussen " . $vn_left_id . "  en   " . $vs_right_string);

                    $message = $t_func->createRelationship($t_object, "ca_objects", $vs_right_string, $relationship);
                    $log->logInfo($message);
                }

                // de bijkomende info registreren
                $notes = "";
                if (  (!empty($res_objecten['related_object_notes_2'][$i]))  ){
                    if (  (!empty($res_objecten['related_object_notes_1'][$i]))  ){
                        $notes = trim($res_objecten['related_object_notes_2'][$i])."\n".
                                 trim($res_objecten['related_object_notes_1'][$i]);
                    }else{
                        $notes = trim($res_objecten['related_object_notes_2'][$i]);
                    }
                } else {
                    if ( (!empty($res_objecten['related_object_notes_1'][$i]))  ){
                        $notes = trim($res_objecten['related_object_notes_1'][$i]);
                    }else{
                        $notes = "";
                    }
                }

                if (trim($notes) != ""){

                    $va_right_keys = $t_object->getObjectIDsByElementID($vs_right_string, 'adlibObjectNummer');
                    $vn_right_id = $va_right_keys[0];
                    $va_right_string = $t_object->getObjectNameByObjectID($vn_right_id);
                    $container = "'related_object_notesInfo'";
                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'related_object'            =>	$vn_right_id.": ".$va_right_string[0],
                                    'related_object_notes'      =>	trim($notes) );

                    $message = $t_func->createContainer($t_object, $data, $vn_right_id.": ".$va_right_string[0], $container);
                    $log->logInfo($message);

                    unset($va_right_keys);
                    unset($vn_right_id);
                    unset($va_right_string);
                    unset($container);
                    unset($data);
                }
                unset($notes);
                unset($vs_right_string);
            }
            unset($aantal);
            unset($relationship);
            unset($res_objecten);
        }
        unset($aantal_objecten);
        unset($fields);
    }

//##############################################################################
//SCHERM 5: BEHEER
//##############################################################################
//Getoond in verhaal -> container: verhaalInfo
//Beide gegevens zouden uit één veld moeten komen
//+toevoegen aan een set verhalen ????? vragen

    $teller = $teller + 1;

    $reader->next();
}

$log->logInfo("IMPORT COMPLETE.");
