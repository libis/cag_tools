<?php
/* Doel van dit programma:
 * My_CAG  TYPE=CREATE - META=ALL - PART="" (objecten.xml)
 * My_CAG  TYPE=CREATE - META=ALL - PART="ST" (sinttruiden.xml)
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

define("__PROG__","objects_1_".__PART__.__META__);
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__MY_DIR_2__.'/cag_tools/classes/ca_objects_bis.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagObject_type');

$t_object = new ca_objects_bis();
$t_object->setMode(ACCESS_WRITE);

if (__META__ === "objectNaam" || __META__ === "ALL") {
    //over welke velden gaat het hier?
    #39 objectnaamOpmerkingen_3 word verwijderd -> naar ca_occurrences
    $objectNaam_velden = array('objectNaam', 'objectnaamOpmerkingen_1',
        'objectnaamOpmerkingen_2');
    //over welke output gaat het hier?
    //$objectNaam_output = array('cag_thesaurus_id','objectnaamOpmerkingen');
}

if (__META__ === "Alternatief" || __META__ === "ALL") {
    //over welke velden gaat het hier?
    $alternatief_velden = array('objectnaamAlternatief_1', 'objectnaamAlternatief_2');
}

if (__META__ === "titelAlternatief" || __META__ === "ALL") {
    //over welke velden gaat het hier?
    $titel_velden = array('titelAlternatief_1', 'titelAlternatief_2');
}

if (__META__ === "opschrift" || __META__ === "ALL"){
    //over welke velden gaat het hier?
    $opschrift_velden = array('opschrift_content', 'opschrift_description', 'opschrift_position',
        'opschriftDate', 'opschriftTranslation', 'opschriftNotes_1', 'opschriftNotes_2',
        'opschriftNotes_3', 'opschriftNotes_4', 'opschriftNotes_5', 'opschriftNotes_6');
    //over welke output gaat het hier?
    $opschrift_output = array('opschrift_content','opschrift_description',
        'opschrift_position','opschriftDate','opschriftTranslation','opschriftNotes');
}

//==============================================================================initialisaties
$teller = 1;
$status = 0;
$status1 = 'nee';
$status2 = 'nee';

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
    if (__PART__ === "ST"){
        $idno = 'st_'.$idno;
    }
    if (__PART__ === "TEST"){
        $idno = 'test_'.$idno;
    }
    $log->logInfo(($idno));

    # indien objecten al gemaakt zijn
    if (__TYPE__ === "UPDATE"){
        $va_left_keys = $t_object->getObjectIDsByIdno($idno);

        if ((sizeof($va_left_keys)) > 1 ) {
            $message =  "ERROR: PROBLEMS with object {$idno} : meerdere records gevonden !!!!! \n";
            $log->logInfo($message);
        }
        $vn_left_id = $va_left_keys[0];
        $t_object->load($vn_left_id);
        $t_object->getPrimaryKey();

        $log->logInfo("object_id: ".$vn_left_id."\n");
    }

//einde inlezen één record, begin verwerking één record
//------------------------------------------------------------------------------
    # aanmaken nieuwe objecten
    if (__TYPE__ === "CREATE"){
        //de identificatie
        $log->logInfo("preferred_label: ", $resultarray['preferred_label']);
        $log->logInfo("objectnaam: ", $resultarray['objectNaam']);

        if (isset($resultarray['preferred_label'])) {
            if (is_array($resultarray['preferred_label'])) {
                $vs_Identificatie = $resultarray['preferred_label'][0];
                $log->logInfo("preferred_label => meerdere aanwezig");
            }else{
                $vs_Identificatie = $resultarray['preferred_label'];
            }
        }else{
            //aangepast omwille van ST-TRUIDEN - slechts éénmaal preferred label aanwezig
            $vs_Identificatie = "=====".$idno." geen identificatie =====";

            $log->logInfo("preferred_label => niet aanwezig - nemen objectNaam");

            if ((isset($resultarray['objectNaam'])) && (!is_array($resultarray['objectNaam']))) {
                $vs_Identificatie = trim($resultarray['objectNaam']);
            }

            if ((isset($resultarray['objectNaam'])) && (is_array($resultarray['objectNaam']))) {
                $vs_Identificatie = trim($resultarray['objectNaam'][0]);
            }
        }
        $log->logInfo("Identificatie => {$vs_Identificatie}");

        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //de workflow_status
        if (isset($resultarray['publication_data']) && ($resultarray['publication_data']) == 'ja' )
        {   $status1 = 'ja';    } else     {   $status1 = 'nee';   }
        if (isset($resultarray['publishing_allowed']) && ($resultarray['publishing_allowed']) == 'x' )
        {   $status2 = 'ja';    } else     {   $status2 = 'nee';   }
        if (($status1 == 'ja') && ($status2 == 'ja'))     {   $status = 2;}
        if (($status1 == 'ja') && ($status2 == 'nee'))    {   $status = 1;}
        if (($status1 == 'nee') && ($status2 == 'nee'))   {   $status = 0;}

        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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
                $message = "ERROR INSERTING {$vs_Identificatie}: ".join('; ', $t_object->getErrors())."  \n  ";
                $log->logInfo($message);
                continue;
        }else{
                $message =  "insert object ".$idno." gelukt ";
                $log->logInfo($message);
            //----------
            $t_object->addLabel(array(
                    'name'      => $vs_Identificatie
            ),$pn_locale_id, null, true );

            if ($t_object->numErrors()) {
                    $message = "ERROR ADD LABEL TO {$vs_Identificatie}: ".join('; ', $t_object->getErrors())."  \n  ";
                    $log->logInfo($message);
                    continue;
            }else{
                    $message = "addlabel ".$vs_Identificatie." gelukt ";
                    $log->logInfo($message);
            }
        }

        $resultarray['primary_key'] = $t_object->getPrimaryKey();
    }

//##############################################################################
//SCHERM 1: IDENTIFICATIE
//##############################################################################
//1.1. Move - Objectnaam -> moveObjectnaam -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.2. Adlib objectnummer -> adlibObjectNummer - (single field container)       (id=236)
    if (__META__ === "adlibObjectNummer" || __META__ === "ALL"){
        if (isset($resultarray['adlibObjectNummer']) && (!empty($resultarray['adlibObjectNummer']))) {
            $singlefield[] = 'adlibObjectNummer';
        }
        //print 'adlibObjectNummer: '.($resultarray['adlibObjectNummer']).'\n';
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.3. CAG objectnaam -> objectNaam (cag_thesaurus drop-down) + Bijkomende info
//cagObjectnaamInfo:                                                            (id=405)
//  objectNaam (list: cag_thesaurus) -> apart opgebouwd                         (id=407)
//  objectnaamOpmerkingen (3 samen te voegen velden gescheiden door '\n')       (id=409)

    if (__META__ === "objectNaam" || __META__ === "ALL") {
        //hoeveel herhalingen zijn er ?
        $naam_aantal = $t_func->Herhalen($resultarray, $objectNaam_velden);
        $log->logInfo("objectNaam aantal: ", $naam_aantal);

        if ($naam_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $naam_array = $t_func->makeArray2($resultarray, $naam_aantal, $objectNaam_velden);

            $aantal = $naam_aantal - 1;

            for ($i=0; $i <= ($aantal); $i++) {

                $cag_thesaurus_id = "";
                if ( (isset($naam_array['objectNaam'][$i])) && (!empty($naam_array['objectNaam'][$i])) ) {
                    $log->logInfo("objectNaam", $naam_array['objectNaam'][$i]);
                    $cag_thesaurus_id = $t_list->getItemIDFromList('cag_thesaurus', trim($naam_array['objectNaam'][$i]));
                    $log->logInfo("thesaurus_id", $cag_thesaurus_id);
                }

                $objectNaamTemp = "";
                if (isset($naam_array['objectnaamOpmerkingen_1'][$i])) {
                    $objectNaamTemp  = $objectNaamTemp.(trim($naam_array['objectnaamOpmerkingen_1'][$i]))."\n";
                }
                if (isset($naam_array['objectnaamOpmerkingen_2'][$i])) {
                    $objectNaamTemp  = $objectNaamTemp.(trim($naam_array['objectnaamOpmerkingen_2'][$i]))."\n";
                }
                #39 objectnaamOpmerkingen_3 word verwijderd -> naar ca_occurrences
                /*
                if (isset($naam_array['objectnaamOpmerkingen_3'][$i])) {
                    $objectnaamOpmerkingen  = $objectnaamOpmerkingen.(trim($naam_array['objectnaamOpmerkingen_3'][$i]));
                }
                 *
                 */

                $objectnaamOpmerkingen = trim($objectNaamTemp);
                if (substr($objectnaamOpmerkingen, -2) === "\n") {
                    $objectnaamOpmerkingen = substr($objectnaamOpmerkingen, 0, -2);
                }
                $log->logInfo("objectnaamOpmerkingen", $objectnaamOpmerkingen);

                if (!empty($cag_thesaurus_id)) {
                    $t_object->addAttribute(array(
                            'locale_id'             =>	$pn_locale_id,
                            'objectNaam'            =>	$cag_thesaurus_id,
                            'objectnaamOpmerkingen' =>	$objectnaamOpmerkingen
                    ), 'cagObjectnaamInfo');
                //-------------
                    $t_object->update();
                //-------------

                    if ($t_object->numErrors()) {
                            $message = "ERROR UPDATING cagObjectnaamInfo_1: ".join('; ', $t_object->getErrors())." \n  ";
                            $log->logInfo($message);
                            continue;
                    }else{
                            $message = "update gelukt cagObjectnaamInfo_1 ";
                            $log->logInfo($message);
                    }
                } else {
                    $log->logError("ERROR: ongeldige waarde voor objectnaam", $cag_thesaurus_id);
                }
                unset($cag_thesaurus_id);
                unset($objectnaamOpmerkingen);
            }
            unset($naam_array);
        }
        unset($naam_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.4. Alternatieve benaming -> objectnaamAlternatief                                (id=410)
//twee velden samenvoegen als volgt : objectnaamAlternatief_2 (objectnaamAlternatief_1(=type))
//na samenvoegen: single field container
//Veronderstellen dat dit veld niet herhaald wordt

    if (__META__ === "Alternatief" || __META__ === "ALL") {

        $alternatief_aantal = $t_func->Herhalen($resultarray, $alternatief_velden);
        $log->logInfo("Alternatief aantal: ", $alternatief_aantal);

        if ($alternatief_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $alternatief_array = $t_func->makeArray2($resultarray, $alternatief_aantal, $alternatief_velden);

            $aantal = $alternatief_aantal - 1;

            for ($i=0; $i <= ($aantal); $i++) {
                $resultarray['objectnaamAlternatief'][$i] =
                $t_func->TweeTotSingleField($alternatief_array['objectnaamAlternatief_2'][$i], $alternatief_array['objectnaamAlternatief_1'][$i]);
            }
            unset($alternatief_array);
            $singlefieldarray[] = 'objectnaamAlternatief';
        }
        unset($alternatief_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.5. Alternatieve titel -> titelAlternatief                                   (id=411)
//twee velden samenvoegen: title.translation (title.type)
//na samenvoegen: single field container
//-> zelfde als hierboven !!!

    if (__META__ === "titelAlternatief" || __META__ === "ALL"){

        $titel_aantal = $t_func->Herhalen($resultarray, $titel_velden);
        $log->logInfo("titel aantal: ", $titel_aantal);

        if ($titel_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $titel_array = $t_func->makeArray2($resultarray, $titel_aantal, $titel_velden);

            $aantal = $titel_aantal - 1;

            for ($i=0; $i <= ($aantal); $i++) {
                $resultarray['titelAlternatief'][$i] =
                $t_func->TweeTotSingleField($titel_array['titelAlternatief_2'][$i], $alternatief_array['titelAlternatief_1'][$i]);
            }
            unset($titel_array);
            $singlefieldarray[] = 'titelAlternatief';
            //print 'Obj.naam_alternatief'.($resultarray['objectnaamAlternatief']).'\n';
        }
        //print 'titel_alternatief: '.($resultarray['titelAlternatief']).'\n';
        unset($titel_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.6. Documentatie publicatie -> documentatieRelatie naar ca_occurrences -> apart programma
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.7. Mondelinge bron -> mondelingeBron naar ca_occurrences -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.8. Documentatie internet (URL veld) -> DocumentatieInternet -> Nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//##############################################################################
//SCHERM 2: FYSIEKE BESCHRIJVING
//##############################################################################
//2.1. Fysieke beschrijving -> metadata: physicalDescription ->                      (id=267)
//Hier moeten 7 (netto 4) velden samengevoegd worden
//type 7 is nooit array - type 1-3-2 kunnen dit wel zijn (en hebben dan dezelfde grootte
//Alles moet samengevoegd worden tot één geheel
//In sinttruiden zijn naast 7 (zonder array's) 6 - 5 - 4 ingevuld

// voorlopig niet gewijzigd

    if (__META__ === "physicalDescription" || __META__ === "ALL") {

        $physical = "";
        if (isset($resultarray['physicalDescription_7']) && (!is_array($resultarray['physicalDescription_7']))) {
            $physical = $resultarray['physicalDescription_7'];
        }
        if ( (isset($resultarray['physicalDescription_1']) && (!is_array($resultarray['physicalDescription_1']))) ||
             (isset($resultarray['physicalDescription_3']) && (!is_array($resultarray['physicalDescription_3']))) ||
             (isset($resultarray['physicalDescription_2']) && (!is_array($resultarray['physicalDescription_2']))) ) {
            $physical = $physical."\n".$resultarray['physicalDescription_1'].
              ' '.$resultarray['physicalDescription_3'].' '.$resultarray['physicalDescription_2'];
        }
        if ( (isset($resultarray['physicalDescription_1']) && (is_array($resultarray['physicalDescription_1']))) ||
             (isset($resultarray['physicalDescription_3']) && (is_array($resultarray['physicalDescription_3']))) ||
             (isset($resultarray['physicalDescription_2']) && (is_array($resultarray['physicalDescription_2']))) ) {

            for ($i = 0; $i <= count($resultarray['physicalDescription_1']) - 1; $i++) {
                $physical = $physical."\n".$resultarray['physicalDescription_1'][$i].
                        ' '.$resultarray['physicalDescription_3'][$i].' '.$resultarray['physicalDescription_2'][$i];
            }
        }
        //voor sinttruiden
        if ( (isset($resultarray['physicalDescription_6']) && (!is_array($resultarray['physicalDescription_6']))) ||
             (isset($resultarray['physicalDescription_5']) && (!is_array($resultarray['physicalDescription_5']))) ||
             (isset($resultarray['physicalDescription_4']) && (!is_array($resultarray['physicalDescription_4']))) ) {
            $physical = $physical."\n".$resultarray['physicalDescription_6'].
              ' '.$resultarray['physicalDescription_4'].' '.$resultarray['physicalDescription_5'];
        }
        if ( (isset($resultarray['physicalDescription_6']) && (is_array($resultarray['physicalDescription_6']))) ||
             (isset($resultarray['physicalDescription_5']) && (is_array($resultarray['physicalDescription_5']))) ||
             (isset($resultarray['physicalDescription_4']) && (is_array($resultarray['physicalDescription_4']))) ) {

            for ($i = 0; $i <= count($resultarray['physicalDescription_4']) - 1; $i++) {
                $physical = $physical."\n".$resultarray['physicalDescription_6'][$i].
                        ' '.$resultarray['physicalDescription_4'][$i].' '.$resultarray['physicalDescription_5'][$i];
            }
        }

        if (!empty($physical)) {
            $resultarray['physicalDescription'] = $physical;
            $singlefield[]= "physicalDescription";
            $message =  "Phys. Descr.: ".$resultarray['physicalDescription'];
            $log->logInfo($message);
        }
        unset($physical);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.2. Aantal objecten -> metadata: numberOfObjects -> single field                  (id=404)
//Opgelet: er zijn twee velden numberOfObjects, zijn geen array
//Afspraak: opladen als twee iteraties

    if (__META__ == "numberOfObjects" || __META__ === "ALL") {

        if ( (isset($resultarray['numberOfObjects_1'])) || (isset($resultarray['numberOfObjects_2'])) ) {
            $singlefieldarray[] = 'numberOfObjects';

            if (isset($resultarray['numberOfObjects_1']) && (!is_array($resultarray['numberOfObjects_1']))) {
                $resultarray['numberOfObjects'][] = $resultarray['numberOfObjects_1'];
            }

            if (isset($resultarray['numberOfObjects_2']) && (!is_array($resultarray['numberOfObjects_2']))) {
                $resultarray['numberOfObjects'][] = $resultarray['numberOfObjects_2'];
            }
            if ( (is_array($resultarray['numberOfObjects_1'])) ) {
                $message =  "ERROR: ".($resultarray['numberOfObjects_1'])." is een array -> niet voorzien";
                $log->logInfo($message);
            }
            if ((is_array($resultarray['numberOfObjects_2'])) ) {
                $message =  "ERROR: ".($resultarray['numberOfObjects_2'])." is een array -> niet voorzien";
                $log->logInfo($message);
            }
        }
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.3. Vervaardiger -> vervaardigerRelatie naar ca_entities -> apart programma       cag_objecten_relaties.php
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.4. Vervaardiging -> container: objectVervaardigingInfo -> WACHTEN                cag_objecten_relaties.php
//    Rol ->        List: vervaardiger_rol -> vervaardigerRol
//    Datering->    DateRange: objectVervaardigingDate (5) syntax: 5 2 - 3 1 (4 komt niet voor)
//    Plaats->      Place: objectVervaardigingPlace - ca_places?
//    Bijk.Info->   Text: objectVervaardigingNote  (3)
//    Serienr->     Text: modelSerienummer: nieuw veld
//Eerst ca_places aanmaken, alvorens deze data in te laden !!!!!!!!
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#29 #35 #38
//2.5. Opschrift -> container: opschriftInfo                                         (id=53)
//  Inhoud->        Text: opschrift_content                                     (id=55)
//  Interpretatie-> Text: opschrift_description                                 (id=57)
//  Positie->       Text: opschrift_position                                    (id=59)
//  Datum->         DateRange: opschriftDate                                    (id=61)
//  Vertaling->     Text: opschriftTranslation                                  (id=63)
//  Bijk.Info->     Text: opschriftNotes (6) -> Syntax?                         (id=65)

    if (__META__ === "opschrift" || __META__ === "ALL") {
        //hoeveel herhalingen zijn er ?
        $opschrift_aantal = $t_func->Herhalen($resultarray, $opschrift_velden);
        $log->logInfo("opschrift aantal: ", $opschrift_aantal);

        if ($opschrift_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $resultarray1 = $t_func->makeArray2($resultarray, $opschrift_aantal, $opschrift_velden);

            $aantal = $opschrift_aantal - 1;

            for ($i=0; $i <= ($aantal); $i++) {

                $t_func->Initialiseer($opschrift_output); // -> ?opgevangen in makeArray2

                $opschriftTemp = "";
                if (isset($resultarray1['opschriftNotes_1'][$i]) && (!empty($resultarray1['opschriftNotes_1'][$i])) ) {
                    $opschriftTemp = $opschriftTemp.'Maker: '.$resultarray1['opschriftNotes_1'][$i]."\n";
                }
                if (isset($resultarray1['opschriftNotes_2'][$i]) && (!empty($resultarray1['opschriftNotes_2'][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Interpretatie: ".$resultarray1['opschriftNotes_2'][$i]."\n";
                }
                if (isset($resultarray1['opschriftNotes_3'][$i]) && (!empty($resultarray1['opschriftNotes_3'][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Taal: ".$resultarray1['opschriftNotes_3'][$i]."\n";
                }
                if (isset($resultarray1['opschriftNotes_4'][$i]) && (!empty($resultarray1['opschriftNotes_4'][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Methode: ".$resultarray1['opschriftNotes_4'][$i]."\n";
                }
                if (isset($resultarray1['opschriftNotes_5'][$i]) && (!empty($resultarray1['opschriftNotes_5'][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Opm.: ".$resultarray1['opschriftNotes_5'][$i]."\n";
                }
                if (isset($resultarray1['opschriftNotes_6'][$i]) && (!empty($resultarray1['opschriftNotes_6'][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Type: ".$resultarray1['opschriftNotes_6'][$i]; }

                // een eventuele <enter> achteraan verwijderen
                $opschriftNotes = trim($opschriftTemp);

                if ((substr($opschriftNotes,-2)) == "\n"){
                    $opschriftNotes = substr($opschriftNotes, 0, len($opschriftNotes) -2);
                }

                $t_object->addAttribute(array(
                        'locale_id'             =>	$pn_locale_id,
                        'opschrift_content'     =>	substr($resultarray1['opschrift_content'][$i], 0, 254),
                        'opschrift_description' =>	$resultarray1['opschrift_description'][$i],
                        'opschrift_position'    =>	$resultarray1['opschrift_position'][$i],
                        'opschriftDate'         =>	$resultarray1['opschriftDate'][$i],
                        'opschriftTranslation'  =>	$resultarray1['opschriftTranslation'][$i],
                        'opschriftNotes'        =>	$opschriftNotes
                ), 'opschriftInfo');

            //-------------
                $t_object->update();
            //-------------

                if ($t_object->numErrors()) {
                        $message = "ERROR UPDATING opschriftInfo_array: ".join('; ', $t_object->getErrors())."  \n  ";
                        $log->logInfo($message);
                        continue;
                }else{
                        $message = "update gelukt opschriftInfo_array ";
                        $log->logInfo($message);
                }
                unset($opschriftTemp);
                $t_func->Vernietig($opschrift_output);
            }
            unset($resultarray1);
        }
        unset($opschrift_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Kleur -> nieuw container veld -> niks te doen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Materiaal -> container: materiaalInfo                                         (id=25)
//  Deel ->     List: deel_type (id=140) -> materiaalDeel -> geheel/onderdeel   (id=27)
//  Naam ->     Text: materiaalNaamOnderdeel                                    (id=28)
//  Materiaal-> Text: materiaal                                                 (id=29)
//  Bijk.Info-> Text: materiaalNotes                                            (id=31)
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Afmetingen -> container: dimensionsInfo
//  Deel ->     List:deel_type (id=140) ->dimensionsDeel -> geheel/onderdeel
//  Naam ->     Text: dimensionsNaamOnderdeel -> komt niet voor
//  Breedte ->  Length: dimensions_width
//  Hoogte ->   Length: dimensions_height
//  Diepte ->   Length: dimensions_depth
//  Omtrek ->   Length: dimensions_circumference
//  Diameter -> Length: dimensions_diameter
//  Lengte ->   Length: dimensions_lengte
//  Dikte ->    Length: dimensions_dikte
//  Bijk.Info-> Text: dimensions_notes (2) notes_2 bevat hoogte etc....
//  ?? notes_2 dimensions_precisie  value unit
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Volledigheid -> container: completenessInfo (geen arrays)
//  Volledigheid->  List: completeness_lijst -> completeness (onvolledig/volledig)
//  Bijk.Info->     Text: completenessNote

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Toestand -> container: toestandInfo
//  Toestand->      List: toestand_lijst -> toestand (goed/matig/slecht)
//  Bijk.Info->     Text: toestandNote (2) -> Syntax?

//##############################################################################
//SCHERM 3: INHOUDELIJKE BESCHRIJVING
//##############################################################################
//Inhoudelijke beschrijving -> single field container -> (geen arrays)
//  (7) deelgegevens dienen samengevoegd tot 'inhoudBeschrijving' -> Hoe
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
//Inventarisnummer op bewaarplaats -> metadata objectInventarisnrBplts (text)
//(2) velden: Syntax?
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Verwerving -> container: acquisitionInfo
//  Datum->     DateRange: acquisitionDate (3)
//  Methode->   Text: acquisitionMethode (1)
//  Bijk.Info-> Text: acquisitionNote (7)
//Syntax?

//##############################################################################
//SCHERM 5: BEHEER
//##############################################################################
//Getoond in verhaal -> container: verhaalInfo
//  Verhaal->   Text: verhaalurl_source
//  URL->       Url: verhaalurl_entry
//Beide gegevens zouden uit één veld moeten komen
//+toevoegen aan een set verhalen ????? vragen
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
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

    //single field containers
    foreach ($singlefield as $value)
    {
        if (isset($resultarray[$value]))
        {
            $t_object->addAttribute(array(
                    $value          =>  trim($resultarray[$value]),
                    'locale_id'     =>  $pn_locale_id
            ), $value);
        //-------------
            $t_object->update();
        //-------------

            if ($t_object->numErrors())
            {
                    $message = "ERROR UPDATING ".$value.": ".join('; ', $t_object->getErrors())."  \n  ";
                    $log->logInfo($message);
                    continue;
            }else{
                    $message = "update ".$value." gelukt ";
                    $log->logInfo($message);
            }
        }
    }

    foreach ($singlefieldarray as $value)
    {
        if ( (isset($resultarray[$value])) && (is_array($resultarray[$value])) )
        {
            for ($i= 0; $i <= (count($resultarray[$value]) - 1); $i++)
            {
                $t_object->addAttribute(array(
                        $value          =>  trim($resultarray[$value][$i]),
                        'locale_id'     =>  $pn_locale_id
                ), $value);

            //-------------
                $t_object->update();
            //-------------

                if ($t_object->numErrors())
                {
                        $message =  "ERROR UPDATING ".$value.": ".join('; ', $t_object->getErrors())."  \n  ";
                        $log->logInfo($message);
                        continue;
                }else{
                        $message =  "update ".$value." gelukt ";
                        $log->logInfo($message);
                }
            }
        }
    }

    $teller = $teller + 1;

    $reader->next();
}
$reader->close();

$log->logInfo("IMPORT COMPLETE.");