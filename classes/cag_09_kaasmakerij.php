<?php
/* Doel van dit programma:
 *
 */
define("__PROG__","kaasmakerij");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_objects_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Objects.php");
require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$my_objects = new Objects();

$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagObject_type');
//de workflow_status -> cfr. Sam: allen op Klaar en publiceren
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');

$t_relatie = new ca_relationship_types();

//==============================================================================initialisaties
$teller = 1;
$objectnaamOpmerkingen = '';
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_kaasmakerij_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/kaasmakerij.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    $singlefield = array();

    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);
    $log->logInfo( '=========='.$teller.'========');
    $log->logInfo('de originele data', $resultarray);
    //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
    //om dubbels te voorkomen: voorafgegaan door km_
    $idno = sprintf('%08d', $teller);
    $idno = 'km'.$idno;
    $log->logInfo("idno: ", $idno);
    //einde inlezen één record, begin verwerking één record
    //------------------------------------------------------------------------------
    //de identificatie: gebruiken description ook als preferred label (na overleg met Sam)
    if (isset($resultarray['inhoudBeschrijving'])) {
        $vs_Identificatie = $resultarray['inhoudBeschrijving'];
    } else {
        $vs_Identificatie = "====='.$idno.' geen identificatie=====";
    }

    $vn_left_id = $my_objects->insertObject($vs_Identificatie, $idno, $status, $pn_object_type_id, $pn_locale_id);

    $log->logInfo('object_id ',($vn_left_id));

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Adlib objectnummer -> adlibObjectNummer - (single field container)            (id=236)
//geen arrays

    $adlibnr = 'adlibObjectNummer';
    if ( (isset($resultarray[$adlibnr]) &&
            (!empty($resultarray[$adlibnr]))) &&
            (!is_array($resultarray[$adlibnr])) ) {
        $singlefield[] = $adlibnr;
        $log->logInfo("Singlefield: adlibObjectNummer: ".$resultarray[$adlibnr]);
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//CAG objectnaam -> Objectnaam (cag_thesaurus drop-down) + Bijkomende info
//cagObjectnaamInfo:                                                            (id=405)
//  objectNaam (list: cag_thesaurus) -> apart opgebouwd                         (id=407)
//  objectnaamOpmerkingen (hier niet voorhanden)                                (id=409)
//  Er zijn 2 velden voorhanden: objectNaam_1 en objectNaam_2 zonder iteraties
//  vormen ze om tot een array

    $name_1 = 'objectNaam_1';
    $name_2 = 'objectNaam_2';
    $name = array($name_1, $name_2);
    $objNaam = array();
    //  Voor de eerste objectNaam
    foreach($name as $value) {
        if ( (isset($resultarray[$value])) && (!empty($resultarray[$value])) && (!is_array($resultarray[$value])) ) {
            $objNaam[] =  trim($resultarray[$value]);
        }
    }

    if ( (sizeof($objNaam)) > 0 ) {

        foreach($objNaam as $thesaurus) {

            $cag_thesaurus_id = '';
            if ( (isset($thesaurus)) && (!empty($thesaurus)) ) {
                $cag_thesaurus_id = $t_list->getItemIDFromList('cag_thesaurus', trim($thesaurus));
            }

            if (!empty($cag_thesaurus_id)) {
                $container = 'cagObjectnaamInfo';
                $data = array('locale_id'           =>	$pn_locale_id,
                            'objectNaam'            =>	$cag_thesaurus_id,
                            'objectnaamOpmerkingen' =>	$objectnaamOpmerkingen);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
            }
            unset($cag_thesaurus_id);
            unset($container);
            unset($data);
        }
        unset($objNaam);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//inhoudBeschrijving -> (single field container)                                (id=266)
//geen arrays
    $inhoud = 'inhoudBeschrijving';
    if ( (isset($resultarray[$inhoud]) &&
            (!empty($resultarray[$inhoud]))) &&
            (!is_array($resultarray[$inhoud])) ) {
        $singlefield[] = $inhoud;
        $log->logInfo("Singlefield: inhoudBeschrijving: ".$resultarray[$inhoud]);
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//single field containers

    foreach ($singlefield as $value) {
        if ( (isset($resultarray[$value])) && (!empty($resultarray[$value])) ) {
            $container = $value;
            $data = array($value    =>  trim($resultarray[$value]),
                    'locale_id'     =>  $pn_locale_id);
            $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
            unset($container);
            unset($data);
        }
    }


//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Bewaarinstelling -> ca_objects_x_entities relatie: is eigenaar van

    $bewaar = 'bewaarinstelling';

    if ( (isset($resultarray[$bewaar])) && (!empty($resultarray[$bewaar])) ) {

        $relationship = $t_relatie->getRelationshipTypeID('ca_objects_x_entities', 'eigenaarRelatie');
        $my_objects->processVariable($vn_left_id, 'ca_entities', $resultarray[$bewaar], $relationship);
        unset($relationship);
    }

    $teller = $teller + 1;
    $reader->next();
}
$reader->close();

$log->logInfo("EINDE VERWERKING");