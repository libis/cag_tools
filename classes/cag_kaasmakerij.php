<?php
/* Doel van dit programma:
 *
 */
error_reporting(-1);
set_time_limit(36000);
$type = "SERVER";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}
define("__PROG__","werktuigen");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_MODELS_DIR__.'/ca_entities.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagObject_type');
//de workflow_status -> cfr. Sam: allen op Klaar en publiceren
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');

$t_relatie = new ca_relationship_types();
$vn_objects_x_eigenaar_van = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'eigenaarRelatie');

$t_entity = new ca_entities();
//==============================================================================initialisaties
$teller = 1;
$objectnaamOpmerkingen = '';
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_kaasmakerij_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR_2__."/cag_tools/data/kaasmakerij.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    $singlefield = array();

    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);
    $log->logInfo( '=========='.$teller.'========');
    //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
    //om dubbels te voorkomen: voorafgegaan door km_
    $idno = sprintf('%08d', $teller);
    $idno = 'km_'.$idno;
    $log->logInfo("idno: ", $idno);
    //einde inlezen één record, begin verwerking één record
    //------------------------------------------------------------------------------
    //de identificatie: gebruiken description ook als preferred label (na overleg met Sam)
    if (isset($resultarray['inhoudBeschrijving'])) {
        $vs_Identificatie = $resultarray['inhoudBeschrijving'];
    } else {
        $vs_Identificatie = "====='.$idno.' geen identificatie=====";
    }

    $vs_left_string = $vs_Identificatie;
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //de workflow_status -> cfr. Sam: allen op Klaar en publiceren
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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
        $log->logInfo("ERROR INSERTING ".$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
        continue;
    } else {
        $log->logInfo('insert '.$vs_Identificatie.' gelukt ');
        //----------
        $t_object->addLabel(array(
                'name'      => $vs_Identificatie
        ),$pn_locale_id, null, true );

        if ($t_object->numErrors()) {
            $log->logInfo("ERROR ADD LABEL TO " .$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
            continue;
        } else {
            $log->logInfo('addlabel '.$vs_Identificatie.' gelukt');
        }
    }

    $resultarray['primary_key'] = $t_object->getPrimaryKey();

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Adlib objectnummer -> adlibObjectNummer - (single field container)            (id=236)
//geen arrays
    if (isset($resultarray['adlibObjectNummer']) ) {
        $singlefield[] = 'adlibObjectNummer';
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//CAG objectnaam -> Objectnaam (cag_thesaurus drop-down) + Bijkomende info
//cagObjectnaamInfo:                                                            (id=405)
//  objectNaam (list: cag_thesaurus) -> apart opgebouwd                         (id=407)
//  objectnaamOpmerkingen (hier niet voorhanden)                                (id=409)
//  Er zijn 2 velden voorhanden: objectNaam_1 en objectNaam_2 zonder iteraties
//  vormen ze om tot een array

    //  Voor de eerste objectNaam
    if ((isset($resultarray['objectNaam_1'])) && (!is_array($resultarray['objectNaam_1']))) {
        $resultarray['objectNaam'][] =  trim($resultarray['objectNaam_1']);
    }

    if ((isset($resultarray['objectNaam_2'])) && (!is_array($resultarray['objectNaam_2']))) {
        $resultarray['objectNaam'][] = trim($resultarray['objectNaam_2']);
    }

    if (isset($resultarray['objectNaam']))  {
        foreach( $resultarray['objectNaam'] as $value) {
            $cag_thesaurus_id = $t_list->getItemIDFromList('cag_thesaurus', trim($value));

            if (is_null($cag_thesaurus_id)) {
                $t_func->createList('cag_thesaurus',$resultarray['objectNaam'], $pn_locale_id);
                $cag_thesaurus_id = $t_list->getItemIDFromList('cag_thesaurus', trim($value));
            }
            $t_object->addAttribute(array(
                'locale_id'             =>	$pn_locale_id,
                'objectNaam'            =>	$cag_thesaurus_id,
                'objectnaamOpmerkingen' =>	$objectnaamOpmerkingen
            ), 'cagObjectnaamInfo');
            //-------------
            $t_object->update();
            //-------------
            if ($t_object->numErrors())             {
                    $log->logInfo("ERROR UPDATING cagObjectnaamInfo_2: ".join('; ', $t_object->getErrors()));
                    continue;
            } else {
                    $log->logInfo('update cagObjectnaamInfo_2 gelukt ');
            }
            unset($cag_thesaurus_id);
        }
    }
    unset($resultarray['objectNaam']);

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//inhoudBeschrijving -> (single field container)                                (id=266)
//geen arrays
    if (isset($resultarray['inhoudBeschrijving']) ) {
        $singlefield[] = 'inhoudBeschrijving';
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//single field containers

    foreach ($singlefield as $value)     {
        if (isset($resultarray[$value]))         {
            $t_object->addAttribute(array(
                $value          =>  trim($resultarray[$value]),
                'locale_id'     =>  $pn_locale_id
            ), $value);
            //-------------
            $t_object->update();
            //-------------
            if ($t_object->numErrors()) {
                    $log->logInfo("ERROR UPDATING ".$value.": ".join('; ', $t_object->getErrors()));
                    continue;
            } else {
                    $log->logInfo('update '.$value.' gelukt ');
            }
        }
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Bewaarinstelling -> ca_objects_x_entities relatie: is eigenaar van

    if (isset($resultarray['Bewaarinstelling'])) {
        $vs_right_string = $resultarray['Bewaarinstelling'];

        $log->logInfo('2. relatie leggen tussen ' . $vs_left_string . '  en   ' . $vs_right_string);

        $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);

        if ((sizeof($va_right_keys)) > 1 ) {
            $log->logInfo("WARNING: PROBLEM: found more than one entity -> taking the first one !!!!!");
        }
        if (!(empty($va_right_keys))) {
            $vn_right_id = $va_right_keys[0];

            $t_object->addRelationship('ca_entities', $vn_right_id,  $vn_objects_x_eigenaar_van);

            if ($t_object->numErrors()) {
                $log->logInfo("ERROR LINKING object and entity : " . join(';', $t_object->getErrors()));
                continue;
            } else {
                $log->logInfo("relatie tot bewaarinstelling ".$vs_right_string." gelukt");
            }
            unset($vs_right_string);
            unset($vn_right_id);
        } else {
            $log->logInfo("entity ".$vs_right_string." niet gevonden");
        }
    }

    $teller = $teller + 1;
    $reader->next();
}
$reader->close();

$log->logInfo("EINDE VERWERKING");