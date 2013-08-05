<?php
/* Doel van dit programma:
 * My_CAG  TYPE=UPDATE - META=ALL_2 - PART=""
 * My_CAG  TYPE=UPDATE - META=ALL_2 - PART="ST"
 */
error_reporting(-1);
set_time_limit(36000);
$type = "LOCAL";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}
require_once(__MY_DIR_2__."/cag_tools/classes/My_CAG.php");

define("__PROG__","objects_2_".__PART__.__META__);
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__MY_DIR_2__.'/cag_tools/classes/ca_objects_bis.php');
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$t_object = new ca_objects_bis();
$t_object->setMode(ACCESS_WRITE);

if (__META__ === "materiaal" || __META__ === "ALL_2") {
    $materiaal_velden = array('materiaalDeel', 'materiaalNaamOnderdeel', 'materiaal', 'materiaalNotes');
    $materiaal_output = array('materiaalDeel', 'materiaalNaamOnderdeel', 'materiaal', 'materiaalNotes');
}

if (__META__ === "afmeting" || __META__ === "ALL_2") {
    //over welke velden gaat het hier?
    $afmeting_velden = array('dimensions_notes_1', 'dimensions_notes_2', 'dimensions_precisie',
            'unit', 'value', 'dimensionsDeel', 'dimensionsNaamOnderdeel');
    //over welke output gaat het hier?
    //$afmeting_output = array('dimensionsNaamOnderdeel',' dimensions_width',
    //        'dimensions_height','$dimensions_depth','dimensions_circumference','dimensions_diameter',
    //        'dimensions_lengte','dimensions_dikte', 'dimensionsDeel','dimensionsNote');
}

if (__META__ === "completeness" || __META__ === "ALL_2") {
    $completeness_velden = array('completeness', 'completenessNote');
    $completeness_output = array('completeness', 'completenessNote');
}

if (__META__ === "toestand" || __META__ === "ALL_2") {
    $toestand_velden = array('toestandNote_1', 'toestandNote_2', 'toestand');
    $toestand_output = array('toestandNote', 'toestand');
}

if (__META__ === "acquisition" || __META__ === "ALL_2") {
    $acquis_velden = array('acquisitionSource','acquisitionDate_1', 'acquisitionDate_2', 'acquisitionDate_3',
            'acquisitionMethode_2', 'acquisitionNote_1', 'acquisitionNote_2', 'acquisitionNote_3', 'acquisitionNote_4',
            'acquisitionNote_5', 'acquisitionNote_6', 'acquisitionNote_7');
    $acquis_output = array('acquisitionSource','acquisitionDate','acquisitionMethode', 'acquisitionNote');
}
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

    $singlefield = array();
    $singlefieldarray = array();

    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo("==========".($teller)."========");

    //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
    $idno = sprintf('%08d', $teller);
    // voor sinttruiden
    if (__PART__ === "ST") {
        $idno = 'st_'.$idno;
    }
    if (__PART__ === "TEST") {
        $idno = 'test_'.$idno;
    }
    $log->logInfo(($idno));

    # indien objecten al gemaakt zijn
    if (__TYPE__ === "UPDATE") {
        $va_left_keys = $t_object->getObjectIDsByIdno($idno);
        //print_r ($va_left_keys);

        if ((sizeof($va_left_keys)) > 1 ) {
            $message =  "ERROR: PROBLEMS with object {$idno} : meerdere records gevonden !!!!!";
            $log->logInfo($message);
        }
        $vn_left_id = $va_left_keys[0];
        $t_object->load($vn_left_id);
        $t_object->getPrimaryKey();

        $log->logInfo("object_id: ".$vn_left_id);
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.6. Kleur -> nieuw container veld -> niks te doen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.7. Materiaal -> container: materiaalInfo                                    (id=25)
//  Deel ->     List: deel_type (id=140) -> materiaalDeel -> geheel/onderdeel   (id=27)-ok
//  Naam ->     Text: materiaalNaamOnderdeel                                    (id=28)-ok- niet in data
//  Materiaal-> Text: materiaal                                                 (id=29)-ok
//  Bijk.Info-> Text: materiaalNotes                                            (id=31)-tikfout (note ipv notes)

    if (__META__ === "materiaal" || __META__ === "ALL_2") {

        $aantal_materiaal = $t_func->Herhalen($resultarray, $materiaal_velden);

        if ($aantal_materiaal > 0) {

            $container = "materiaalInfo";
            $res_materiaal = $t_func->makeArray2($resultarray, $aantal_materiaal, $materiaal_velden);
            $aantal = $aantal_materiaal - 1;

            for ($i=0; $i <= ($aantal) ; $i++) {

                $t_func->Initialiseer($materiaal_output);

                if ( (isset($res_materiaal['materiaalDeel'][$i])) && (!empty($res_materiaal['materiaalDeel'][$i])) ) {
                    if (strtoupper(substr($resultarray['materiaalDeel'][$i],0,6)) == 'GEHEEL') {
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                    }else{
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
                    }
                }else{
                    $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                }
                if (!empty($res_materiaal['materiaalNotes'][$i])) {
                    $materiaalNotes = $res_materiaal['materiaalNotes'][$i];
                }
                if (!empty($res_materiaal['materiaalDeel'][$i])) {
                    $materiaalNaamOnderdeel = $res_materiaal['materiaalDeel'][$i];
                }
                if (!empty($res_materiaal['materiaal'][$i])) {
                    $materiaal = $res_materiaal['materiaal'][$i];
                }
                if (!empty($res_materiaal['materiaalNotes'][$i])) {
                    $materiaalNotes = $res_materiaal['materiaalNotes'][$i];
                }

                $data = array(  'locale_id'                 =>	$pn_locale_id,
                                'materiaalDeel'             =>	$materiaalDeel,
                                'materiaalNaamOnderdeel'    =>	$materiaalNaamOnderdeel,
                                'materiaal'                 =>	$materiaal,
                                'materiaalNotes'             =>	$materiaalNotes);
                $message = $t_func->createContainer($t_object, $data, $materiaalNaamOnderdeel, $container);
                $log->logInfo($message);

                unset($data);
                $t_func->Vernietig($materiaal_output);
            }
            unset($container);
            unset($res_materiaal);
            unset($aantal);
        }
        unset($aantal_materiaal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.8. Afmetingen -> container: dimensionsInfo                                  (id=32)
//  Deel ->     List:deel_type (id=140) ->dimensionsDeel -> geheel/onderdeel    (id=34)
//  Naam ->     Text: dimensionsNaamOnderdeel -> komt niet voor                 (id=35)
//  Breedte ->  Length: dimensions_width                                        (id=37)
//  Hoogte ->   Length: dimensions_height                                       (id=38)
//  Diepte ->   Length: dimensions_depth                                        (id=39)
//  Omtrek ->   Length: dimensions_circumference                                (id=40)
//  Diameter -> Length: dimensions_diameter                                     (id=41)
//  Lengte ->   Length: dimensions_lengte                                       (id=42)
//  Dikte ->    Length: dimensions_dikte                                        (id=43)
//  Bijk.Info-> Text: dimensions_notes (2) notes_2 bevat hoogte etc....         (id=45)
//  ?? notes_2 dimensions_precisie  value unit

    if (__META__ === "afmeting" || __META__ === "ALL_2") {

        $afmeting_aantal = $t_func->Herhalen($resultarray, $afmeting_velden);

        if ($afmeting_aantal > 0) {

            $container = "dimensionsInfo";

            $afmeting = $t_func->makeArray2($resultarray, $afmeting_aantal, $afmeting_velden);

            $aantal = $afmeting_aantal - 1;

            $dimensionsNaamOnderdeel = '';
            $dimensionsNaamOnderdeel_old= '';
            $dimensions_width = '';
            $dimensions_height = '';
            $dimensions_depth = '';
            $dimensions_circumference = '';
            $dimensions_diameter = '';
            $dimensions_lengte = '';
            $dimensions_dikte = '';
            $dimensionsDeel= '';
            $dimensionsNote = '';

            for ($i = 0; $i <= ($aantal) ; $i++) {

                if (!empty($afmeting['dimensionsDeel'][$i])) {
                    $dimensionsNaamOnderdeel= trim($afmeting['dimensionsDeel'][$i]);
                } else {
                    //$dimensionsNaamOnderdeel= '';
                }

                if ($i == 0) {
                    $dimensionsNaamOnderdeel_old = $dimensionsNaamOnderdeel;
                }
                if ($dimensionsNaamOnderdeel != $dimensionsNaamOnderdeel_old) {

                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'dimensionsDeel'            =>	$dimensionsDeel,
                                    'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel_old,
                                    'dimensions_width'          =>	$dimensions_width,
                                    'dimensions_height'         =>	$dimensions_height,
                                    'dimensions_depth'          =>	$dimensions_depth,
                                    'dimensions_circumference'  =>	$dimensions_circumference,
                                    'dimensions_diameter'       =>	$dimensions_diameter,
                                    'dimensions_lengte'         =>	$dimensions_lengte,
                                    'dimensions_dikte'          =>	$dimensions_dikte,
                                    'dimensions_notes'          =>      $dimensionsNote);

                    $message = $t_func->createContainer($t_object,$data, $dimensionsNaamOnderdeel_old, $container);
                    $log->logInfo($message);

                    //en initialiseren
                    $dimensions_width = '';
                    $dimensions_height = '';
                    $dimensions_depth = '';
                    $dimensions_circumference = '';
                    $dimensions_diameter = '';
                    $dimensions_lengte = '';
                    $dimensions_dikte = '';
                    $dimensionsDeel= '';
                    $dimensionsNote = '';
                    unset($data);
                    //en zetten de nieuwe naam in oud
                    $dimensionsNaamOnderdeel_old = $dimensionsNaamOnderdeel;
                }

                if ($dimensionsNaamOnderdeel == $dimensionsNaamOnderdeel_old) {
                    //Vullen de rest van de gegevens in

                    //de drop-down
                    if ( (isset($afmeting['dimensionsDeel'][$i])) && (!empty($afmeting['dimensionsDeel'][$i]))  ) {
                        if ( ((strtoupper(substr($afmeting['dimensionsDeel'][$i],0,6))) == 'GEHEEL') ||
                             ((strtoupper(substr($afmeting['dimensionsDeel'][$i],0,6))) == 'GEHELE') ||
                             ((strtoupper(substr($afmeting['dimensionsDeel'][$i],0,4))) == 'HELE') ) {

                            $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                        }else{
                            $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
                        }
                    }else{
                        $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                    }

                    $trans = array("," => ".");
                    $afmeting['value'][$i] = strtr($afmeting['value'][$i], $trans);

                    //de dimensions
                    if ( (isset($afmeting['dimensions_notes_2'][$i])) && (!empty($afmeting['dimensions_notes_2'][$i])) ) {
                        //als er geen unit opgegeven is veronderstellen we cm
                        if ( (!isset($afmeting['unit]'][$i])) || (empty($afmeting['unit'][$i])) ) {
                            $afmeting['unit'][$i] = 'cm';
                        }
                        if (($afmeting['unit'][$i] != "cm")) {
                            $log->logInfo("afmijkende unit of measurement", ($afmeting['unit'][$i]) );
                            $afmeting['unit'][$i] = 'cm';
                        }
                        switch ($afmeting['dimensions_notes_2'][$i]) {
                           case "breedte" :
                               $dimensions_width = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "hoogte";
                               $dimensions_height = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "diepte":
                               $dimensions_depth = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "omtrek":
                               $dimensions_circumference = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "diameter":
                               $dimensions_diameter = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "doorsnede":
                               $dimensions_diameter = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "lengte";
                               $dimensions_lengte = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           case "dikte":
                               $dimensions_dikte = $afmeting['value'][$i].' '.$afmeting['unit'][$i];
                               break;
                           default:
                               $dimensionsNote =
                               $afmeting['dimensions_notes_2'][$i].' '.$afmeting['value'][$i].' '.$afmeting['unit'][$i]."\n";
                               break;
                       }
                    }

                    //precision
                    if ( (isset($afmeting['dimensions_precisie'][$i])) && (!empty($afmeting['dimensions_precisie'][$i]))  ) {
                        if (!stristr($dimensionsNote,$afmeting['dimensions_precisie'][$i])) {
                            $dimensionsNote = $dimensionsNote.$afmeting['dimensions_precisie'][$i].' ';
                        }
                       if ( (isset($afmeting['dimensions_notes_1'][$i])) && (!empty($afmeting['dimensions_notes_1'][$i]))  ) {
                           if (!stristr($dimensionsNote, $afmeting['dimensions_note_1'][$i])) {
                                $dimensionsNote = $dimensionsNote.$afmeting['dimensions_note_1'][$i].' ';
                           }
                       }
                    }else{
                       if ( (isset($afmeting['dimensions_notes_1'][$i])) && (!empty($afmeting['dimensions_notes_1'][$i]))  ) {
                           if (!stristr($dimensionsNote, $afmeting['dimensions_notes_1'][$i])) {
                                $dimensionsNote = $dimensionsNote.$afmeting['dimensions_notes_1'][$i].' ';
                           }
                       }
                    }
                }

                if ($afmeting_aantal == $i + 1) {
                    //print 'enige of laatste iteratie';
                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'dimensionsDeel'            =>	$dimensionsDeel,
                                    'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                                    'dimensions_width'          =>	$dimensions_width,
                                    'dimensions_height'         =>	$dimensions_height,
                                    'dimensions_depth'          =>	$dimensions_depth,
                                    'dimensions_circumference'  =>	$dimensions_circumference,
                                    'dimensions_diameter'       =>	$dimensions_diameter,
                                    'dimensions_lengte'         =>	$dimensions_lengte,
                                    'dimensions_dikte'          =>	$dimensions_dikte,
                                    'dimensions_notes'          => $dimensionsNote);
                    $message = $t_func->createContainer($t_object,$data, $dimensionsNaamOnderdeel, $container);
                    $log->logInfo($message);

                    unset($data);
                    unset($dimensionsNaamOnderdeel);
                    unset($dimensionsNaamOnderdeel_old);
                    unset($dimensions_width);
                    unset($dimensions_height);
                    unset($dimensions_depth);
                    unset($dimensions_circumference);
                    unset($dimensions_diameter);
                    unset($dimensions_lengte);
                    unset($dimensions_dikte);
                    unset($dimensionsDeel);
                    unset($dimensionsNote);
                }
            }
            unset($afmeting);
            unset($aantal);
            unset($container);
        }
        unset($afmeting_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.9. Volledigheid -> container: completenessInfo                              (id=256)
//Volledigheid->  List: completeness_lijst -> completeness (onvolledig/volledig)(id=258) (list_id= 142)
//Bijk.Info->     Text: completenessNote                                        (id=260)

    if (__META__ === "completeness" || __META__ === "ALL_2") {

        $compl_aantal = $t_func->Herhalen($resultarray, $completeness_velden);

        if ($compl_aantal > 0) {

            $container = "completenessInfo";
            $rescompl = $t_func->makeArray2($resultarray, $compl_aantal, $completeness_output);
            $aantal = $compl_aantal - 1 ;

            for ($i=0; $i <= $aantal; $i++){

                $t_func->Initialiseer($completeness_output);

                $completeness = $t_list->getItemIDFromList('completeness_lijst', 'blanco'); // item_id = 12653

                if ( (isset($rescompl['completeness'][$i])) && (!empty($rescompl['completeness'][$i])) ) {
                        if (strtoupper(trim($rescompl['completeness'][$i])) == 'VOLLEDIG') {
                            $completeness = $t_list->getItemIDFromList('completeness_lijst', 'volledig'); // item_id = 795
                        }elseif (strtoupper(trim($rescompl['completeness'][$i])) == 'ONVOLLEDIG') {
                            $completeness = $t_list->getItemIDFromList('completeness_lijst', 'onvolledig'); // item_id = 796
                        }
                }

                if ( (isset($rescompl['completenessNote'][$i])) && (!empty($rescompl['completenessNote'][$i])) ) {
                    $completenessNote = $rescompl['completenessNote'][$i];
                }

                $data = array(  'locale_id'         =>	$pn_locale_id,
                                'completeness'      =>	$completeness,
                                'completenessNote'  =>	$completenessNote);

                $message = $t_func->createContainer($t_object,$data, $completenessNote, $container);
                $log->logInfo($message);

                $t_func->Vernietig($completeness_output);
                unset($data);
            }
            unset($container);
            unset($rescompl);
            unset($aantal);
        }
        unset($compl_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.10. Toestand -> container: toestandInfo                                     (id=261)
//  Toestand->      List: toestand_lijst -> toestand (goed/matig/slecht)        (id=263) (list_id= 143)
//  Bijk.Info->     Text: toestandNote (2) -> Syntax?                           (id=265)
//  toestandNote_2: toestandNote_1
//
    if (__META__ === "toestand" || __META__ === "ALL_2") {

        $aantal_toestand = $t_func->Herhalen($resultarray, $toestand_velden);

        if ($aantal_toestand > 0){

            $container = "toestandInfo";
            $res_toestand = $t_func->makeArray2($resultarray, $aantal_toestand, $toestand_velden);
            $aantal = $aantal_toestand - 1;

            for ($i=0; $i <= ($aantal) ; $i++) {

                $t_func->Initialiseer($toestand_output);

                if ( (isset($res_toestand['toestand'][$i])) && (!empty($res_toestand['toestand'][$i]))) {
                    if (stristr(trim($res_toestand['toestand'][$i]),'goed') ) {
                        $toestand = $t_list->getItemIDFromList('toestand_lijst', 'goed');
                    } elseif (stristr(trim($res_toestand['toestand'][$i]),'slecht') ) {
                        $toestand = $t_list->getItemIDFromList('toestand_lijst', 'slecht');
                    } elseif (stristr(trim($res_toestand['toestand'][$i]),'matig') ) {
                        $toestand = $t_list->getItemIDFromList('toestand_lijst', 'matig');
                    }
                }

                if ( (isset($res_toestand['toestandNote_2'][$i])) && (!empty($res_toestand['toestandNote_2'][$i])) ) {
                    if ( (isset($res_toestand['toestandNote_1'][$i])) && (!empty($res_toestand['toestandNote_1'][$i])) ) {
                        $toestandNote = $res_toestand['toestandNote_2'][$i].': '.$res_toestand['toestandNote_1'][$i];
                    } else {
                        $toestandNote = $res_toestand['toestandNote_2'][$i];
                    }
                }else {
                    if ( (isset($res_toestand['toestandNote_1'][$i])) && (!empty($res_toestand['toestandNote_1'][$i])) ) {
                        $toestandNote = $res_toestand['toestandNote_1'][$i];
                    } else {
                        $toestandNote = '';
                    }
                }

                $data = array(  'locale_id'     =>	$pn_locale_id,
                                'toestand'      =>	$toestand,
                                'toestandNote'  =>	$toestandNote);
                $message = $t_func->createContainer($t_object, $data, $toestandNote, $container);
                $log->logInfo($message);

                $t_func->Vernietig($toestand_output);
                unset($data);
            }
            unset($container);
            unset($res_toestand);
            unset($aantal);
        }
        unset($aantal_toestand);
    }

//##############################################################################
//SCHERM 3: INHOUDELIJKE BESCHRIJVING
//##############################################################################
//3.1. Inhoudelijke beschrijving -> single field container -> (geen arrays)
//  (7) deelgegevens dienen samengevoegd tot 'inhoudBeschrijving' -> Hoe        (id=266)

    if (__META__ === "inhoud" || __META__ === "ALL_2") {

        $temp = array();

        if ( (isset($resultarray['inhoudBeschrijving_1'])) || (isset($resultarray['inhoudBeschrijving_2'])) ||
             (isset($resultarray['inhoudBeschrijving_3'])) || (isset($resultarray['inhoudBeschrijving_4'])) ||
             (isset($resultarray['inhoudBeschrijving_5'])) || (isset($resultarray['inhoudBeschrijving_3'])) ||
             (isset($resultarray['inhoudBeschrijving_7'])) ) {

            if ( (is_array($resultarray['inhoudBeschrijving_1'])) || (is_array($resultarray['inhoudBeschrijving_2'])) ||
             (is_array($resultarray['inhoudBeschrijving_3'])) || (is_array($resultarray['inhoudBeschrijving_4'])) ||
             (is_array($resultarray['inhoudBeschrijving_5'])) || (is_array($resultarray['inhoudBeschrijving_3'])) ||
             (is_array($resultarray['inhoudBeschrijving_7'])) ) {
                $message = "ERROR: inhoudBeschrijving-data bevat array(s) -> niet voorzien";
                $log->logInfo($message);
            }else{
                $resultarray['inhoudBeschrijving'] = '';
                if (isset($resultarray['inhoudBeschrijving_1']) && (!is_array($resultarray['inhoudBeschrijving_1'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_1']); }
                if (isset($resultarray['inhoudBeschrijving_2']) && (!is_array($resultarray['inhoudBeschrijving_2'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_2']); }
                if (isset($resultarray['inhoudBeschrijving_3']) && (!is_array($resultarray['inhoudBeschrijving_3'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_3']); }
                if (isset($resultarray['inhoudBeschrijving_4']) && (!is_array($resultarray['inhoudBeschrijving_4'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_4']); }
                if (isset($resultarray['inhoudBeschrijving_5']) && (!is_array($resultarray['inhoudBeschrijving_5'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_5']); }
                if (isset($resultarray['inhoudBeschrijving_6']) && (!is_array($resultarray['inhoudBeschrijving_6'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_6']); }
                if (isset($resultarray['inhoudBeschrijving_7']) && (!is_array($resultarray['inhoudBeschrijving_7'])))
                {   $temp[] = trim($resultarray['inhoudBeschrijving_7']); }

                $resultarray['inhoudBeschrijving'] = (implode("\n", $temp));

                $singlefield[] = 'inhoudBeschrijving';
            }
        }
        unset($temp);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Trefwoorden -> List: cag_trefwoorden (opgelet: vocabulary list)
//Hoe moet deze lijst opgebouwd? Welke info moet erin?
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerde plaats -> ca_places -> Nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Georeference -> ca_objects/entities -> Nieuw veld
//##############################################################################
//SCHERM 4: VERWERVING
//##############################################################################
//Gerelateerde collecties -> [is_part_of] relatie naar ca_collections -> apart programma
//(2) velden: collectieBeschrijving_1 en collectieBeschrijving_2
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Bewaarinstelling -> ? relatie naar ca_entities -> apart programma
//[eigenaar_van] of [bewaarinstalling_van] relatie
//(3) velden: [eigenaar_van], [bewaarinstelling_van] en [bewaarinstelling]
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Inventarisnummer op bewaarplaats -> container: objectInventarisnrBpltsInfo    (id=235)
// Instelling:  objectInventarisnrBplts_inst:                                   (id=415)
// overname van de instellingsnaam uit bewaarinstelling_ven
// Nummer :     objectInventarisnrBplts                                         (id=417)
// (2)velden: Syntax -> Bplts_2: Bplts_1
// (arrays mogelijk) - single field array container
// Moet mee met bovenstaande relatie naar ca_entities ingevuld worden
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Verworven van -> relatie naar ca_entities -> apart programma
//[vorigeeigenaar] relatie
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.5. Verwerving -> container: acquisitionInfo                                 (id=280)
//  Van->       Text: acquisitionSource                                         (id=426)
//  Datum->     DateRange: acquisitionDate (3)velden                            (id=282)
//  Methode->   Text: acquisitionMethode (1)veld                                (id=284)
//  Bijk.Info-> Text: acquisitionNote (7)velden                                 (id=286)
//  (één array -> vormen alles om tot array)

// resultaat: 12 ERROR: reden: Datum is ongeldig

    if (__META__ === "acquisition" || __META__ === "ALL_2") {

        $aantal_verwerving = $t_func->Herhalen($resultarray, $acquis_velden);

        if ($aantal_verwerving > 0) {

            $container = "acquisitionInfo";
            $res_verwerving = $t_func->makeArray2($resultarray, $aantal_verwerving, $acquis_velden);
            $aantal = $aantal_verwerving - 1 ;

            for ($i=0; $i <= ($aantal) ; $i++) {

                $acquisition = array();
                $t_func->Initialiseer($acquis_output);
                //Source
                if ( (isset($res_verwerving['acquisitionSource'][$i])) && (!empty($res_verwerving['acquisitionSource'][$i])) ) {
                    $acquisitionSource = $res_verwerving['acquisitionSource'][$i];
                }
                //Date
                if ( (isset($res_verwerving['acquisitionDate_3'][$i])) && (!empty($res_verwerving['acquisitionDate_3'][$i])) ) {
                    if ( (isset($res_verwerving['acquisitionDate_1'][$i])) && (!empty($res_verwerving['acquisitionDate_1'][$i])) ) {
                        if ( (isset($res_verwerving['acquisitionDate_2'][$i])) && (!empty($res_verwerving['acquisitionDate_2'][$i])) ) {
                            $acquisitionDate =
                            $res_verwerving['acquisitionDate_2'][$i]." ".$res_verwerving['acquisitionDate_3'][$i]."-".$res_verwerving['acquisitionDate_1'][$i];
                        }else{
                            $acquisitionDate = $res_verwerving['acquisitionDate_3'][$i]."-".$res_verwerving['acquisitionDate_1'][$i];
                        }
                    }else{
                        if ( (isset($res_verwerving['acquisitionDate_2'][$i])) && (!empty($res_verwerving['acquisitionDate_2'][$i])) ) {
                            $acquisitionDate = $res_verwerving['acquisitionDate_2'][$i]." ".$res_verwerving['acquisitionDate_3'];
                        }else{
                            $acquisitionDate = $res_verwerving['acquisitionDate_3'][$i];
                        }
                        //$acquisitionDate = $res_verwerving['acquisitionDate_1'][$i];
                    }
                }else{
                    if ( (isset($res_verwerving['acquisitionDate_1'][$i])) && (!empty($res_verwerving['acquisitionDate_1'][$i])) ) {
                        if ( (isset($res_verwerving['acquisitionDate_2'][$i])) && (!empty($res_verwerving['acquisitionDate_2'][$i])) ) {
                            $acquisitionDate =
                            $res_verwerving['acquisitionDate_2'][$i]." ".$res_verwerving['acquisitionDate_1'][$i];
                        }else{
                            $acquisitionDate = $res_verwerving['acquisitionDate_1'][$i];
                        }
                    }else{
                        if ( (isset($res_verwerving['acquisitionDate_2'][$i])) && (!empty($res_verwerving['acquisitionDate_2'][$i])) ) {
                            $acquisitionDate = $res_verwerving['acquisitionDate_2'][$i];
                        }
                    }
                }
                if (!$t_func->is_valid_date($acquisitionDate)) { $acquisitionDate = ""; }
                //Methode
                if ( (isset($res_verwerving['acquisitionMethode_2'][$i])) && (!empty($res_verwerving['acquisitionMethode_2'][$i])) ) {
                    $acquisitionMethode = $res_verwerving['acquisitionMethode_2'][$i];
                }
                //Notes -> maken eerst array en voegen dan samen met implode
                if ( (isset($res_verwerving['acquisitionNote_1'][$i])) && (!empty($res_verwerving['acquisitionNote_1'][$i])) ) {
                    $acquisition[] = "Opm.1: ".$res_verwerving['acquisitionNote_1'][$i];
                }
                if ( (isset($res_verwerving['acquisitionNote_2'][$i])) && (!empty($res_verwerving['acquisitionNote_1'][$i])) ) {
                    if ( (isset($res_verwerving['acquisitionNote_3'][$i])) && (!empty($res_verwerving['acquisitionNote_3'][$i])) ) {
                        $acquisition[] = "Prijs: ".$res_verwerving['acquisitionNote_2'][$i].' '.$res_verwerving['acquisitionNote_3'][$i];
                    }else{
                        $acquisition[] = "Prijs: ".$res_verwerving['acquisitionNote_2'][$i];
                    }
                }else{
                    if ( (isset($res_verwerving['acquisitionNote_3'][$i])) && (!empty($res_verwerving['acquisitionNote_3'][$i])) ) {
                        $acquisition[] = "Prijs: ".$res_verwerving['acquisitionNote_3'][$i];
                    }
                }
                if ( (isset($res_verwerving['acquisitionNote_4'][$i])) && (!empty($res_verwerving['acquisitionNote_4'][$i])) ) {
                    $acquisition[] = "Door: ".$res_verwerving['acquisitionNote_4'][$i];
                }
                if ( (isset($res_verwerving['acquisitionNote_5'][$i])) && (!empty($res_verwerving['acquisitionNote_5'][$i])) ) {
                    $acquisition[] = "Van: ".$res_verwerving['acquisitionNote_5'][$i];
                }
                if ( (isset($res_verwerving['acquisitionNote_6'][$i])) && (!empty($res_verwerving['acquisitionNote_6'][$i])) ) {
                    $acquisition[] = "Prijs: ".$res_verwerving['acquisitionNote_6'][$i];
                }
                if ( (isset($res_verwerving['acquisitionNote_7'][$i])) && (!empty($res_verwerving['acquisitionNote_1'][$i])) ) {
                    $acquisition[] = "Opm.2: ".$res_verwerving['acquisitionNote_7'][$i];
                }

                if (!empty($acquisition)) {
                    asort($acquisition);
                    $acquisitionNote = implode("\n",$acquisition);
                }

                $data = array(  'locale_id'                 =>	$pn_locale_id,
                                'acquisitionSource'         =>  $acquisitionSource,
                                'acquisitionDate'           =>	$acquisitionDate,
                                'acquisitionMethode'        =>	$acquisitionMethode,
                                'acquisitionNote'           =>	$acquisitionNote);
                $message = $t_func->createContainer($t_object,$data, $acquisitionSource, $container);
                $log->logInfo($message);

                $t_func->Vernietig($acquis_output);
                unset($acquisition);
                unset($data);
            }
            unset($container);
            unset($res_verwerving);
            unset($aantal);
        }
        unset($aantal_verwerving);
    }

//##############################################################################
//SCHERM 5: BEHEER
//##############################################################################
//Getoond in verhaal -> container: verhaalInfo
//  Verhaal->   Text: verhaalurl_source
//  URL->       Url: verhaalurl_entry
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Publiceren naar Europeana -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Status -> List: workflow_statuses -> reeds ingevuld
//  deze info is nodig voor aanmaken object (zie bovenaan)
//  combinatie van publication_data en publishing_allowed
//##############################################################################
//SCHERM 6: RELATIES
//##############################################################################
//Gerelateerd object
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerd concept
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerd object - bijkomende info
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerd personen en instellingen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerde plaatsen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Gerelateerd gebeurtenis
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Trefwoorden
//##############################################################################
//SCHERM 7: MEDIA
//##############################################################################
// ??????
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

    //single field containers
    foreach ($singlefield as $value)
    {
        if (isset($resultarray[$value]))
        {
            $data = array(  $value          =>  trim($resultarray[$value]),
                            'locale_id'     =>  $pn_locale_id);
            $message = $t_func->createContainer($t_object, $data, trim($resultarray[$value]), $value);
            $log->logInfo($message);
            unset($data);
        }
    }


    foreach ($singlefieldarray as $value) {
        if ( (isset($resultarray[$value])) && (is_array($resultarray[$value])) ) {
            for ($i= 0; $i <= (count($resultarray[$value])- 1 ); $i++) {

                $data = array(  $value          =>  trim($resultarray[$value][$i]),
                                'locale_id'     =>  $pn_locale_id);
                $message = $t_func->createContainer($t_object, $data, trim($resultarray[$value][$i]), $value);
                $log->logInfo($message);
                unset($data);
            }
        }
    }

    unset($singlefield);
    unset($singlefieldarray);

    $teller = $teller + 1;

    $reader->next();
}
$reader->close();

$log->logInfo("IMPORT COMPLETE.");