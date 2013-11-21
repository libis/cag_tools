<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


require_once(__MY_DIR__."/cag_tools/classes/ca_objects_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Lists.php");
require_once(__MY_DIR__."/cag_tools/classes/Objects.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$t_texp = new TimeExpressionParser(null, null, true);
$t_texp->setLanguage('nl_NL');

$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagObject_type');

$my_objects = new Objects();

//==============================================================================initialisaties
$teller = 1;
$objectNaam_velden = array('objectNaam', 'objectnaamOpmerkingen_1', 'objectnaamOpmerkingen_2');
$alternatief_velden = array('objectnaamAlternatief_1', 'objectnaamAlternatief_2');
$titel_velden = array('titelAlternatief_1', 'titelAlternatief_2');
$opschrift_velden = array('opschrift_content', 'opschrift_description', 'opschrift_position',
        'opschriftDate', 'opschriftTranslation', 'opschriftNotes_1', 'opschriftNotes_2',
        'opschriftNotes_3', 'opschriftNotes_5');
$materiaal_velden = array('materiaalDeel', 'materiaalNaamOnderdeel', 'materiaal', 'materiaalNotes');
$afmeting_velden = array('dimensions_notes_1', 'dimensions_notes_2', 'dimensions_precisie',
            'unit', 'value', 'dimensionsDeel', 'dimensionsNaamOnderdeel');
$completeness_velden = array('completeness', 'completenessNote');
$toestand_velden = array('toestandNote_1', 'toestandNote_2', 'toestand');
$acquis_velden = array('acquisitionSource','acquisitionDate_1', 'acquisitionDate_2', 'acquisitionDate_3',
            'acquisitionMethode_2', 'acquisitionNote_1', 'acquisitionNote_2', 'acquisitionNote_3', 'acquisitionNote_4',
            'acquisitionNote_5', 'acquisitionNote_6', 'acquisitionNote_7');
$status = 0;
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv(__MAPPING__);

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__DATA__);

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record') {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $singlefield = array();
    $singlefieldarray = array();

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

    $vn_left_id = $my_objects ->actionToTake($idno);

    # aanmaken nieuwe objecten
    if ($vn_left_id === NULL){
        //de identificatie
        $log->logInfo("preferred_label: ", $resultarray['preferred_label']);
        $log->logInfo("objectnaam: ", $resultarray['objectNaam']);

        $vs_Identificatie = $my_objects->defineIdentificatie($resultarray, $idno);
        $log->logInfo("Identificatie => {$vs_Identificatie}");
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //de workflow_status
        $status = $my_objects->defineStatus($resultarray);

        $vn_left_id = $my_objects->insertObject($vs_Identificatie, $idno, $status, $pn_object_type_id, $pn_locale_id);
    }
    //??nodig??
    /*
    if ($vn_left_id !== NULL) {

        $t_object->load($vn_left_id);
        $t_object->getPrimaryKey();
    }
     *
     */

    $log->logInfo('object_id ',($vn_left_id));

//##############################################################################
//SCHERM 1: IDENTIFICATIE
//##############################################################################
//1.1. Move - Objectnaam -> moveObjectnaam -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.2. Object identificatiecode -> idno
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.3. Adlib objectnummer -> adlibObjectNummer -> (single field container)      (id=236)

    $adlibnr = 'adlibObjectNummer';
    if ( (isset($resultarray[$adlibnr]) &&
            (!empty($resultarray[$adlibnr]))) &&
            (!is_array($resultarray[$adlibnr])) ) {
        $singlefield[] = $adlibnr;
        $log->logInfo("Singlefield: adlibObjectNummer: ".$resultarray[$adlibnr]);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.4. CAG objectnaam -> objectNaam (cag_thesaurus drop-down) + Bijkomende info
//cagObjectnaamInfo:                                                            (id=405)
//  objectNaam (list: cag_thesaurus) -> apart opgebouwd                         (id=407)
//  objectnaamOpmerkingen (2 samen te voegen velden gescheiden door '\n')       (id=409)

    if ( (isset($resultarray['objectNaam'])) && (!empty($resultarray['objectNaam'])) ) {
        //hoeveel herhalingen zijn er ?
        $naam_aantal = $t_func->Herhalen($resultarray, $objectNaam_velden);

        if ($naam_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $naam_array = $t_func->makeArray2($resultarray, $naam_aantal, $objectNaam_velden);
            $aantal = $naam_aantal - 1;
            $i = 0;
            for ($i=0; $i <= ($aantal); $i++) {

                $cag_thesaurus_id = '';
                if ( (isset($naam_array['objectNaam'][$i])) && (!empty($naam_array['objectNaam'][$i])) ) {
                    $cag_thesaurus_id = $t_list->getItemIDFromList('cag_thesaurus', trim($naam_array['objectNaam'][$i]));
                }

                if (!empty($cag_thesaurus_id)) {
                    $objectNaamTemp = "";
                    if (isset($naam_array['objectnaamOpmerkingen_1'][$i])) {
                        $objectNaamTemp  = $objectNaamTemp.(trim($naam_array['objectnaamOpmerkingen_1'][$i]))."\n";
                    }
                    if (isset($naam_array['objectnaamOpmerkingen_2'][$i])) {
                        $objectNaamTemp  = $objectNaamTemp.(trim($naam_array['objectnaamOpmerkingen_2'][$i]))."\n";
                    }
                    #39 objectnaamOpmerkingen_3 word verwijderd -> naar ca_occurrences

                    $objectnaamOpmerkingen = trim($objectNaamTemp);
                    if (substr($objectnaamOpmerkingen, -2) === "\n") {
                        $objectnaamOpmerkingen = substr($objectnaamOpmerkingen, 0, -2);
                    }

                    $container = 'cagObjectnaamInfo';
                    $data = array('locale_id'             =>	$pn_locale_id,
                                'objectNaam'            =>	$cag_thesaurus_id,
                                'objectnaamOpmerkingen' =>	$objectnaamOpmerkingen);
                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
                }
                unset($cag_thesaurus_id);
                unset($objectNaamTemp);
                unset($objectnaamOpmerkingen);
                unset($container);
                unset($data);
            }
            unset($naam_array);
            unset($aantal);
            unset($i);
        }
        unset($naam_aantal);
    }
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.5. Alternatieve benaming -> objectnaamAlternatief                                (id=410)
//twee velden samenvoegen als volgt : objectnaamAlternatief_2 (objectnaamAlternatief_1(=type))
//na samenvoegen: single field container
//Veronderstellen dat dit veld niet herhaald wordt

    $alt_name = 'objectnaamAlternatief';
    $alt_name_1 = 'objectnaamAlternatief_1';
    $alt_name_2 = 'objectnaamAlternatief_2';

    if ( ( (isset($resultarray[$alt_name_2])) && (!empty($resultarray[$alt_name_2])) ) ||
         ( (isset($resultarray[$alt_name_1])) && (!empty($resultarray[$alt_name_1])) ) ) {

        $alternatief_aantal = $t_func->Herhalen($resultarray, $alternatief_velden);

        if ($alternatief_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $alternatief_array = $t_func->makeArray2($resultarray, $alternatief_aantal, $alternatief_velden);

            $singlefieldarray[] = $alt_name;
            $aantal = $alternatief_aantal - 1;
            $i = 0;
            for ($i=0; $i <= ($aantal); $i++) {
                $resultarray[$alt_name][$i] =
                $t_func->TweeTotSingleField($alternatief_array[$alt_name_2][$i], $alternatief_array[$alt_name_1][$i]);
            }
            $log->logInfo("Singlefieldarray: objectnaamAlternatief: ".$resultarray[$alt_name]);
            unset($alternatief_array);
            unset($aantal);
            unset($i);
        }
        unset($alternatief_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.6. Titel -> preferred label
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.7. Alternatieve titel -> titelAlternatief                                   (id=411)
//twee velden samenvoegen: title.translation (title.type)
//na samenvoegen: single field container

    $alt_titel = 'titelAlternatief';
    $alt_titel_1 = 'titelAlternatief_1';
    $alt_titel_2 = 'titelAlternatief_2';

    if ( ( (isset($resultarray[$alt_titel_2])) && (!empty($resultarray[$alt_titel_2])) ) ||
         ( (isset($resultarray[$alt_titel_1])) && (!empty($resultarray[$alt_titel_1])) ) ) {

        $titel_aantal = $t_func->Herhalen($resultarray, $titel_velden);

        if ($titel_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $titel_array = $t_func->makeArray2($resultarray, $titel_aantal, $titel_velden);

            $singlefieldarray[] = $alt_titel;
            $aantal = $titel_aantal - 1;
            $i = 0;
            for ($i=0; $i <= ($aantal); $i++) {
                $resultarray[$alt_titel][$i] =
                $t_func->TweeTotSingleField($titel_array[$alt_titel_2][$i], $alternatief_array[$alt_titel_1][$i]);
            }
            $log->logInfo("Singlefieldarray: titelAlternatief: ".$resultarray[$alt_titel]);
            unset($titel_array);
            unset($aantal);
            unset($i);
        }
        unset($titel_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.8. Documentatie publicatie -> documentatieRelatie naar ca_occurrences -> apart programma - TODO
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.9. Documentatie pagina -> refPagina -> apart programma samen met documentatieRelatie
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.10. Mondelinge bron -> mondelingeBron naar ca_occurrences -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//1.11. Internet -> DocumentatieInternet -> Nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//##############################################################################
//SCHERM 2: FYSIEKE BESCHRIJVING
//##############################################################################
//2.1. Fysieke beschrijving -> metadata: physicalDescription ->                 (id=267)
//Hier moeten 7 (netto 4) velden samengevoegd worden
//type 7 is nooit array - type 1-3-2 kunnen dit wel zijn (en hebben dan dezelfde grootte
//Alles moet samengevoegd worden tot één geheel
//In sinttruiden zijn naast 7 (zonder array's) 6 - 5 - 4 ingevuld

    $desc = 'physicalDescription';
    $desc_1 = 'physicalDescription_1';
    $desc_2 = 'physicalDescription_2';
    $desc_3 = 'physicalDescription_3';
    $desc_4 = 'physicalDescription_4';
    $desc_5 = 'physicalDescription_5';
    $desc_6 = 'physicalDescription_6';
    $desc_7 = 'physicalDescription_7';

    if ( (isset($resultarray[$desc_1])) || (isset($resultarray[$desc_2])) || (isset($resultarray[$desc_3])) ||
         (isset($resultarray[$desc_4])) || (isset($resultarray[$desc_5])) || (isset($resultarray[$desc_6])) ||
         (isset($resultarray[$desc_7])) ) {

        $physical = '';
        if ( (isset($resultarray[$desc_7]) && (!empty($resultarray[$desc_7])) && (!is_array($resultarray[$desc_7]))) ) {
            $physical = trim($resultarray[$desc_7]);
        }

        if ( (isset($resultarray[$desc_1]) && (!empty($resultarray[$desc_1])) && (!is_array($resultarray[$desc_1]))) ||
             (isset($resultarray[$desc_3]) && (!empty($resultarray[$desc_3])) && (!is_array($resultarray[$desc_3]))) ||
             (isset($resultarray[$desc_2]) && (!empty($resultarray[$desc_2])) && (!is_array($resultarray[$desc_2]))) ) {
            $physical = $physical."\n".$resultarray[$desc_1].' '.$resultarray[$desc_3].' '.$resultarray[$desc_2];
        }
        if ( (isset($resultarray[$desc_1]) && (!empty($resultarray[$desc_1])) && (is_array($resultarray[$desc_1]))) ||
             (isset($resultarray[$desc_3]) && (!empty($resultarray[$desc_3])) && (is_array($resultarray[$desc_3]))) ||
             (isset($resultarray[$desc_2]) && (!empty($resultarray[$desc_2])) && (is_array($resultarray[$desc_2]))) ) {

            $aantal = count($resultarray[$desc_1]) - 1;
            $i = 0;
            for ($i = 0; $i <= $aantal; $i++) {
                $physical = $physical."\n".$resultarray[$desc_1][$i].' '.$resultarray[$desc_3][$i].' '.$resultarray[$desc_2][$i];
            }
            unset($i);
            unset($aantal);
        }
        //voor sinttruiden
        if ( (isset($resultarray[$desc_6]) && (!empty($resultarray[$desc_6])) && (!is_array($resultarray[$desc_6]))) ||
             (isset($resultarray[$desc_5]) && (!empty($resultarray[$desc_5])) && (!is_array($resultarray[$desc_5]))) ||
             (isset($resultarray[$desc_4]) && (!empty($resultarray[$desc_4])) && (!is_array($resultarray[$desc_4]))) ) {

            $physical = $physical."\n".$resultarray[$desc_6].' '.$resultarray[$desc_4].' '.$resultarray[$desc_5];
        }
        if ( (isset($resultarray[$desc_6]) && (!empty($resultarray[$desc_6])) && (is_array($resultarray[$desc_6]))) ||
             (isset($resultarray[$desc_5]) && (!empty($resultarray[$desc_5])) && (is_array($resultarray[$desc_5]))) ||
             (isset($resultarray[$desc_4]) && (!empty($resultarray[$desc_4])) && (is_array($resultarray[$desc_4]))) ) {

            $aantal = count($resultarray[$desc_4]) - 1;
            $i = 0;
            for ($i = 0; $i <= $aantal; $i++) {
                $physical = $physical."\n".$resultarray[$desc_6][$i].' '.$resultarray[$desc_4][$i].' '.$resultarray[$desc_5][$i];
            }
            unset($i);
            unset($aantal);
        }

        if (!empty($physical)) {
            $resultarray[$desc] = $physical;
            $singlefield[]= $desc;
            $log->logInfo("Singlefield: Physical Description: ".$resultarray[$desc]);
        }
        unset($physical);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.2. Aantal objecten -> metadata: numberOfObjects -> single field             (id=404)
//Opgelet: er zijn twee velden numberOfObjects, zijn geen array
//Afspraak: opladen als twee iteraties

    $nrofobj = 'numberOfObjects';
    $nrofobj_1 = 'numberOfObjects_1';
    $nrofobj_2 = 'numberOfObjects_2';

    if ( (isset($resultarray[$nrofobj_1])) || (isset($resultarray[$nrofobj_2])) ) {

        $singlefieldarray[] = $nrofobj;

        if (isset($resultarray[$nrofobj_1]) && (!empty($resultarray[$nrofobj_1])) ) {
            if (!is_array($resultarray[$nrofobj_1])) {
                $resultarray[$nrofobj][] = $resultarray[$nrofobj_1];
            } else {
                $log->logError('verwerking datatype (array) niet voorzien: numberOfObjects_1', $resultarray[$nrofobj_1]);
            }
        }

        if (isset($resultarray[$nrofobj_2]) &&  (!empty($resultarray[$nrofobj_2])) ) {
            if (!is_array($resultarray[$nrofobj_2])) {
                $resultarray[$nrofobj][] = $resultarray[$nrofobj_2];
            } else {
                $log->logError('verwerking datatype (array) niet voorzien: numberOfObjects_2', $resultarray[$nrofobj_2]);
            }
        }
        $log->logInfo("Singlefieldarray: numberOfObjects: ".$resultarray[$nrofobj]);
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
// Eerst ca_places aanmaken, alvorens deze data in te laden !!!!!!!!
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.5. Opschrift -> container: opschriftInfo                                    (id=53)
//  Inhoud->        Text: opschrift_content                                     (id=55)
//  Interpretatie-> Text: opschrift_description                                 (id=57)
//  Positie->       Text: opschrift_position                                    (id=59)
//  Datum->         DateRange: opschriftDate                                    (id=61)
//  Vertaling->     Text: opschriftTranslation                                  (id=63)
//  Bijk.Info->     Text: opschriftNotes (6) -> Syntax?                         (id=65)

    $o_content = 'opschrift_content';
    $o_desc = 'opschrift_description';
    $o_pos = 'opschrift_position';
    $o_date = 'opschriftDate';
    $o_trans = 'opschriftTranslation';

    $o_note_1 = 'opschriftNotes_1';
    $o_note_2 = 'opschriftNotes_2';
    $o_note_3 = 'opschriftNotes_3';
    //$o_note_4 = 'opschriftNotes_4';
    $o_note_5 = 'opschriftNotes_5';
    //$o_note_6 = 'opschriftNotes_6';

    if ( (isset($resultarray[$o_content])) || (isset($resultarray[$o_desc])) || (isset($resultarray[$o_pos])) ||
         (isset($resultarray[$o_date])) || (isset($resultarray[$o_trans])) || (isset($resultarray[$o_note_1])) ||
         (isset($resultarray[$o_note_2])) || (isset($resultarray[$o_note_3])) || (isset($resultarray[$o_note_5])) ) {
        //hoeveel herhalingen zijn er ?
        $opschrift_aantal = $t_func->Herhalen($resultarray, $opschrift_velden);

        if ($opschrift_aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $opschrift = $t_func->makeArray2($resultarray, $opschrift_aantal, $opschrift_velden);
            $aantal = $opschrift_aantal - 1;
            $i = 0;

            for ($i=0; $i <= ($aantal); $i++) {

                $opschriftTemp = '';

                if (isset($opschrift[$o_note_1][$i]) && (!empty($opschrift[$o_note_1][$i])) ) {
                    $opschriftTemp = $opschriftTemp.'Maker: '.$opschrift[$o_note_1][$i]."\n";
                }
                if (isset($opschrift[$o_note_2][$i]) && (!empty($opschrift[$o_note_2][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Interpretatie: ".$opschrift[$o_note_2][$i]."\n";
                }
                if (isset($opschrift[$o_note_3][$i]) && (!empty($opschrift[$o_note_3][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Taal: ".$opschrift[$o_note_3][$i]."\n";
                }
#               if (isset($opschrift[$o_note_4][$i]) && (!empty($opschrift[$o_note_4][$i])) ) {
#                   $opschriftTemp = $opschriftTemp."Methode: ".$opschrift[$o_note_4][$i]."\n";
#               }
                if (isset($opschrift[$o_note_5][$i]) && (!empty($opschrift[$o_note_5][$i])) ) {
                    $opschriftTemp = $opschriftTemp."Opm.: ".$opschrift[$o_note_5][$i]."\n";
                }
#               if (isset($opschrift[$o_note_6][$i]) && (!empty($opschrift[$o_note_6][$i])) ) {
#                   $opschriftTemp = $opschriftTemp."Type: ".$opschrift[$o_note_6][$i];
#               }

                // een eventuele <enter> achteraan verwijderen
                $o_notes = trim($opschriftTemp);

                if ((substr($o_notes,-2)) == "\n"){
                    $o_notes = substr($o_notes, 0, len($o_notes) -2);
                }

                $container = 'opschriftInfo';
                $data = array('locale_id'       =>	$pn_locale_id,
                        'opschrift_content'     =>	substr($opschrift[$o_content][$i], 0, 254),
                        'opschrift_description' =>	$opschrift[$o_desc][$i],
                        'opschrift_position'    =>	$opschrift[$o_pos][$i],
                        'opschriftDate'         =>	$opschrift[$o_date][$i],
                        'opschriftTranslation'  =>	$opschrift[$o_trans][$i],
                        'opschriftNotes'        =>	$o_notes);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                unset($opschriftTemp);
                unset($o_notes);
                unset($container);
                unset($data);
            }
            unset($opschrift);
            unset($aantal);
            unset($i);
        }
        unset($opschrift_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.6.Kleur -> nieuw container veld -> niks te doen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.7. Materiaal -> container: materiaalInfo                                    (id=25)
//2.7. Materiaal -> container: materiaalInfo                                    (id=25)
//  Deel ->     List: deel_type (id=140) -> materiaalDeel -> geheel/onderdeel   (id=27)-ok
//  Naam ->     Text: materiaalNaamOnderdeel                                    (id=28)-ok- niet in data
//  Materiaal-> Text: materiaal                                                 (id=29)-ok
//  Bijk.Info-> Text: materiaalNotes                                            (id=31)-tikfout (note ipv notes)

    $mat_deel = 'materiaalDeel';
    $mat_naam = 'materiaalNaamOnderdeel';
    $mat = 'materiaal';
    $mat_notes = 'materiaalNotes';

    if ( (isset($resultarray[$mat_deel])) || (isset($resultarray[$mat_naam])) || (isset($resultarray[$mat])) ||
          (isset($resultarray[$mat_notes])) ) {

        $materiaal_aantal = $t_func->Herhalen($resultarray, $materiaal_velden);

        if ($materiaal_aantal > 0) {

            $materiaal = $t_func->makeArray2($resultarray, $materiaal_aantal, $materiaal_velden);
            $aantal = $materiaal_aantal - 1;
            $i = 0;

            for ($i=0; $i <= ($aantal) ; $i++) {

                if ( (isset($materiaal[$mat_deel][$i])) && (!empty($materiaal[$mat_deel][$i])) ) {
                    if (strtoupper(substr($materiaal[$mat_deel][$i],0,6)) == 'GEHEEL') {
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                    }else{
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
                    }
                }else{
                    $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                }

                $container = "materiaalInfo";
                $data = array(  'locale_id'                 =>	$pn_locale_id,
                                'materiaalDeel'             =>	$materiaalDeel,
                                'materiaalNaamOnderdeel'    =>	$materiaal[$mat_naam][$i],
                                'materiaal'                 =>	$materiaal[$mat][$i],
                                'materiaalNotes'             =>	$materiaal[$mat_notes][$i]);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                unset($materiaalDeel);
                unset($container);
                unset($data);
            }
            unset($materiaal);
            unset($aantal);
            unset($i);
        }
        unset($materiaal_aantal);
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
//  Gewicht->   Length: dimensions_weight                                       (id=)
//  Dikte ->    Length: dimensions_dikte                                        (id=43)
//  Bijk.Info-> Text: dimensions_notes (2) notes_2 bevat hoogte etc....         (id=45)
//  ?? notes_2 dimensions_precisie  value unit

    $dim_note_1 = 'dimensions_notes_1';
    $dim_note_2 = 'dimensions_notes_2';
    $dim_prec = 'dimensions_precisie';
    $unit ='unit';
    $value = 'value';
    $dim_deel = 'dimensionsDeel';
    $dim_naam = 'dimensionsNaamOnderdeel';

    if ( (isset($resultarray[$dim_note_1])) || (isset($resultarray[$dim_note_2])) || (isset($resultarray[$unit])) ||
         (isset($resultarray[$value])) || (isset($resultarray[$dim_deel])) || (isset($resultarray[$dim_naam])) ||
         (isset($resultarray[$dim_prec])) ) {

        $afmeting_aantal = $t_func->Herhalen($resultarray, $afmeting_velden);

        if ($afmeting_aantal > 0) {

            $afmeting = $t_func->makeArray2($resultarray, $afmeting_aantal, $afmeting_velden);

            $aantal = $afmeting_aantal - 1;
            $i = 0;

            $dimensionsNaamOnderdeel = '';
            $dimensionsNaamOnderdeel_old = '';
            $dimensions_width = '';
            $dimensions_height = '';
            $dimensions_depth = '';
            $dimensions_circumference = '';
            $dimensions_diameter = '';
            $dimensions_lengte = '';
            $dimensions_weight = '';
            $dimensions_dikte = '';
            $dimensionsDeel = '';
            $dimensionsNoteTemp = array();
            $dimensionsNote = '';

            for ($i = 0; $i <= ($aantal) ; $i++) {

                if (!empty($afmeting[$dim_deel][$i])) {
                    $dimensionsNaamOnderdeel= trim($afmeting[$dim_deel][$i]);
                } else {
                    //$dimensionsNaamOnderdeel= '';
                }

                if ($i === 0) {
                    $dimensionsNaamOnderdeel_old = $dimensionsNaamOnderdeel;
                }
                if ($dimensionsNaamOnderdeel !== $dimensionsNaamOnderdeel_old) {

                    if (!empty($dimensionsNoteTemp)) {
                        $dimensionsNote = implode("\n", $dimensionsNoteTemp);
                    }

                    $container = "dimensionsInfo";
                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'dimensionsDeel'            =>	$dimensionsDeel,
                                    'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel_old,
                                    'dimensions_width'          =>	$dimensions_width,
                                    'dimensions_height'         =>	$dimensions_height,
                                    'dimensions_depth'          =>	$dimensions_depth,
                                    'dimensions_circumference'  =>	$dimensions_circumference,
                                    'dimensions_diameter'       =>	$dimensions_diameter,
                                    'dimensions_lengte'         =>	$dimensions_lengte,
                                    'dimensions_weight'         =>      $dimensions_weight,
                                    'dimensions_dikte'          =>	$dimensions_dikte,
                                    'dimensions_notes'          =>      $dimensionsNote);
                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                    //en initialiseren
                    $dimensions_width = '';
                    $dimensions_height = '';
                    $dimensions_depth = '';
                    $dimensions_circumference = '';
                    $dimensions_diameter = '';
                    $dimensions_lengte = '';
                    $dimensions_weight = '';
                    $dimensions_dikte = '';
                    $dimensionsDeel = '';
                    $dimensionsNoteTemp = array();
                    $dimensionsNote = '';
                    unset($data);
                    //en zetten de nieuwe naam in oud
                    $dimensionsNaamOnderdeel_old = $dimensionsNaamOnderdeel;
                }

                if ($dimensionsNaamOnderdeel === $dimensionsNaamOnderdeel_old) {
                    //Vullen de rest van de gegevens in

                    //de drop-down $dimensionsDeel
                    if ( (isset($afmeting[$dim_deel][$i])) && (!empty($afmeting[$dim_deel][$i]))  ) {
                        if ( ((strtoupper(substr($afmeting[$dim_deel][$i],0,6))) == 'GEHEEL') ||
                             ((strtoupper(substr($afmeting[$dim_deel][$i],0,6))) == 'GEHELE') ||
                             ((strtoupper(substr($afmeting[$dim_deel][$i],0,4))) == 'HELE') ) {
                            $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                        }else{
                            $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
                        }
                    }else{
                        $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                    }
                    //de waarden (punt vervangen door komma)
                    $trans = array("," => ".");
                    $afmeting[$value][$i] = strtr($afmeting[$value][$i], $trans);

                    //de dimensions
                    if ( (isset($afmeting[$dim_note_2][$i])) && (!empty($afmeting[$dim_note_2][$i])) ) {
                        //als er geen unit opgegeven is veronderstellen we cm
                        if ( (!isset($afmeting[$unit][$i])) || (empty($afmeting[$unit][$i])) ) {
                            $afmeting[$unit][$i] = 'cm';
                        }
                        //nagaan of waarde geen '-' bevat -> in opmerkingen plaatsen
                        if ( (strpos($afmeting[$value][$i],'-') > 0 ) ||  (strpos($afmeting[$value][$i],'+') > 0 ) ) {
                            $dimensionsNoteTemp[] =
                            $afmeting[$dim_note_2][$i].': '.$afmeting[$value][$i].' '.$afmeting[$unit][$i];
                        } else {
                            if ( (isset($afmeting[$value][$i])) && (!empty($afmeting[$value][$i])) ) {
                                switch ($afmeting[$dim_note_2][$i]) {
                                   case "breedte" :
                                       $dimensions_width = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "hoogte";
                                       $dimensions_height = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "diepte":
                                       $dimensions_depth = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "omtrek":
                                       $dimensions_circumference = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "diameter":
                                   case "doorsnede":
                                       $dimensions_diameter = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "lengte";
                                       $dimensions_lengte = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "gewicht";
                                       $dimensions_weight = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   case "dikte":
                                       $dimensions_dikte = $afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                                   default:
                                       $dimensionsNoteTemp[] =
                                       $afmeting[$dim_note_2][$i].': '.$afmeting[$value][$i].' '.$afmeting[$unit][$i];
                                       break;
                               }
                            }
                        }
                    }

                    //precision (in $dimensionsNote veld (samen met dimensions_notes_1)
                    if ( (isset($afmeting[$dim_prec][$i])) && (!empty($afmeting[$dim_prec][$i]))  ) {
                        $dimensionsNoteTemp[] = $afmeting[$dim_note_2][$i].': '.$afmeting[$dim_prec][$i];
                    }
                    //bijkomende opmerking
                    if ( (isset($afmeting[$dim_note_1][$i])) && (!empty($afmeting[$dim_note_1][$i]))  ) {
                         $dimensionsNoteTemp[] = $afmeting[$dim_note_2][$i].': '.$afmeting[$dim_note_1][$i];
                    }
                }

                if ($afmeting_aantal === ($i + 1) ) {
                    //print 'enige of laatste iteratie';

                    if (!empty($dimensionsNoteTemp)) {
                        $dimensionsNote = implode("\n", $dimensionsNoteTemp);
                    }

                    $container = "dimensionsInfo";
                    $data = array(  'locale_id'                 =>	$pn_locale_id,
                                    'dimensionsDeel'            =>	$dimensionsDeel,
                                    'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                                    'dimensions_width'          =>	$dimensions_width,
                                    'dimensions_height'         =>	$dimensions_height,
                                    'dimensions_depth'          =>	$dimensions_depth,
                                    'dimensions_circumference'  =>	$dimensions_circumference,
                                    'dimensions_diameter'       =>	$dimensions_diameter,
                                    'dimensions_lengte'         =>	$dimensions_lengte,
                                    'dimensions_weight'         =>      $dimensions_weight,
                                    'dimensions_dikte'          =>	$dimensions_dikte,
                                    'dimensions_notes'          =>      $dimensionsNote);
                    $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                    unset($data);
                    unset($dimensionsNaamOnderdeel);
                    unset($dimensionsNaamOnderdeel_old);
                    unset($dimensions_width);
                    unset($dimensions_height);
                    unset($dimensions_depth);
                    unset($dimensions_circumference);
                    unset($dimensions_diameter);
                    unset($dimensions_lengte);
                    unset($dimensions_weight);
                    unset($dimensions_dikte);
                    unset($dimensionsDeel);
                    unset($dimensionsNoteTemp);
                    unset($dimensionsNote);
                }
            }
            unset($afmeting);
            unset($aantal);
            unset($i);
        }
        unset($afmeting_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.9. Volledigheid -> container: completenessInfo                              (id=256)
//Volledigheid->  List: completeness_lijst -> completeness (onvolledig/volledig)(id=258) (list_id= 142)
//Bijk.Info->     Text: completenessNote                                        (id=260)

    $complet = 'completeness';
    $complet_note = 'completenessNote';
    $complet_lijst = 'completeness_lijst';
    $complet_id = $t_list->getItemIDFromList($complet_lijst, 'blanco'); // item_id = 12653

    if ( (isset($resultarray[$complet])) || (isset($resultarray[$complet_note])) ) {

        $compl_aantal = $t_func->Herhalen($resultarray, $completeness_velden);

        if ($compl_aantal > 0) {

            $completeness = $t_func->makeArray2($resultarray, $compl_aantal, $completeness_velden);
            $aantal = $compl_aantal - 1 ;
            $i = 0;

            for ($i=0; $i <= $aantal; $i++){

                if ( (isset($completeness[$complet][$i])) && (!empty($completeness[$complet][$i])) ) {
                    if (strtoupper(trim($completeness[$complet][$i])) === 'VOLLEDIG') {
                        $complet_id = $t_list->getItemIDFromList($complet_lijst, 'volledig'); // item_id = 795
                    }elseif (strtoupper(trim($completeness[$complet][$i])) === 'ONVOLLEDIG') {
                        $complet_id = $t_list->getItemIDFromList($complet_lijst, 'onvolledig'); // item_id = 796
                    }
                }

                $container = "completenessInfo";
                $data = array(  'locale_id'         =>	$pn_locale_id,
                                'completeness'      =>	$complet_id,
                                'completenessNote'  =>	$completeness[$complet_note][$i]);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                unset($container);
                unset($data);
            }
            unset($completeness);
            unset($aantal);
            unset($i);
        }
        unset($compl_aantal);
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//2.10. Toestand -> container: toestandInfo                                     (id=261)
//  Toestand->      List: toestand_lijst -> toestand (goed/matig/slecht)        (id=263) (list_id= 143)
//  Bijk.Info->     Text: toestandNote (2) -> Syntax?                           (id=265)
//  toestandNote_2: toestandNote_1

    $toest_note_1 = 'toestandNote_1';
    $toest_note_2 = 'toestandNote_2';
    $toestand = 'toestand';
    $toest_lijst = 'toestand_lijst';
    $toestand_id = $t_list->getItemIDFromList($toest_lijst, 'blanco');

    if ( (isset($resultarray[$toest_note_1])) || (isset($resultarray[$toest_note_2])) || (isset($resultarray[$toestand])) ) {

        $aantal_toestand = $t_func->Herhalen($resultarray, $toestand_velden);

        if ($aantal_toestand > 0){

            $res_toestand = $t_func->makeArray2($resultarray, $aantal_toestand, $toestand_velden);
            $aantal = $aantal_toestand - 1;
            $i = 0;

            for ($i=0; $i <= ($aantal) ; $i++) {

                if ( (isset($res_toestand[$toestand][$i])) && (!empty($res_toestand[$toestand][$i]))) {
                    if (stristr(trim($res_toestand[$toestand][$i]), 'goed') ) {
                        $toestand_id = $t_list->getItemIDFromList($toest_lijst, 'goed');
                    } elseif (stristr(trim($res_toestand[$toestand][$i]), 'slecht') ) {
                        $toestand_id = $t_list->getItemIDFromList($toest_lijst, 'slecht');
                    } elseif (stristr(trim($res_toestand[$toestand][$i]), 'matig') ) {
                        $toestand_id = $t_list->getItemIDFromList($toest_lijst, 'matig');
                    }
                }
                $toestandNote = '';
                if ( (isset($res_toestand[$toest_note_2][$i])) && (!empty($res_toestand[$toest_note_2][$i])) ) {
                    if ( (isset($res_toestand[$toest_note_1][$i])) && (!empty($res_toestand[$toest_note_1][$i])) ) {
                        $toestandNote = $res_toestand[$toest_note_2][$i].': '.$res_toestand[$toest_note_1][$i];
                    } else {
                        $toestandNote = $res_toestand[$toest_note_2][$i];
                    }
                }else {
                    if ( (isset($res_toestand[$toest_note_1][$i])) && (!empty($res_toestand[$toest_note_1][$i])) ) {
                        $toestandNote = $res_toestand[$toest_note_1][$i];
                    } else {
                        $toestandNote = '';
                    }
                }

                $container = "toestandInfo";
                $data = array(  'locale_id'     =>	$pn_locale_id,
                                'toestand'      =>	$toestand_id,
                                'toestandNote'  =>	$toestandNote);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                unset($toestandNote);
                unset($container);
                unset($data);
            }
            unset($res_toestand);
            unset($aantal);
            unset($i);
        }
        unset($aantal_toestand);
    }

//##############################################################################
//SCHERM 3: INHOUDELIJKE BESCHRIJVING
//##############################################################################
//3.1. Inhoudelijke beschrijving -> single field container -> (geen arrays)
//  (7) deelgegevens dienen samengevoegd tot 'inhoudBeschrijving' -> Hoe        (id=266)

    $inhoud = 'inhoudBeschrijving';
    $inhoud_1 = 'inhoudBeschrijving_1';
    $inhoud_2 = 'inhoudBeschrijving_2';
    $inhoud_3 = 'inhoudBeschrijving_3';
    $inhoud_4 = 'inhoudBeschrijving_4';
    $inhoud_5 = 'inhoudBeschrijving_5';
    $inhoud_6 = 'inhoudBeschrijving_6';
    $inhoud_7 = 'inhoudBeschrijving_7';

    if ( (isset($resultarray[$inhoud_1])) || (isset($resultarray[$inhoud_2])) || (isset($resultarray[$inhoud_3])) ||
        (isset($resultarray[$inhoud_4])) || (isset($resultarray[$inhoud_5])) || (isset($resultarray[$inhoud_6])) ||
        (isset($resultarray[$inhoud_7])) ) {

        if ( (is_array($resultarray[$inhoud_1])) || (is_array($resultarray[$inhoud_2])) ||
         (is_array($resultarray[$inhoud_3])) || (is_array($resultarray[$inhoud_4])) ||
         (is_array($resultarray[$inhoud_5])) || (is_array($resultarray[$inhoud_6])) ||
         (is_array($resultarray[$inhoud_7])) ) {

                $log->logError("ERROR: inhoudBeschrijving-data bevat array(s) -> niet voorzien");
        }else{

            $temp = array();
            $inhoud_array = array($inhoud_1, $inhoud_2, $inhoud_3, $inhoud_4, $inhoud_5, $inhoud_6, $inhoud_7);
            $resultarray[$inhoud] = '';

            foreach($inhoud_array as $value) {
                if (isset($resultarray[$value]) && (!empty($resultarray[$value])) && (!is_array($resultarray[$value])) ) {
                    $temp[] = trim($resultarray[$value]);
                }
            }

            $resultarray[$inhoud] = (implode("\n", $temp));

            $singlefield[] = $inhoud;

            unset($inhoud_array);
            unset($temp);
        }
    }

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//3.2. Trefwoorden -> List: cag_trefwoorden (opgelet: vocabulary list)
//Hoe moet deze lijst opgebouwd? Welke info moet erin?
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//3.3. Gerelateerde plaats -> ca_places -> Nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//3.4. Georeference -> ca_objects/entities -> Nieuw veld

//##############################################################################
//SCHERM 4: VERWERVING
//##############################################################################
//4.1. Gerelateerde collecties -> [is_part_of] relatie naar ca_collections -> apart programma
//(2) velden: collectieBeschrijving_1 en collectieBeschrijving_2
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.2. Bewaarinstelling -> ? relatie naar ca_entities -> apart programma
//[eigenaar_van] of [bewaarinstalling_van] relatie
//(3) velden: [eigenaar_van], [bewaarinstelling_van] en [bewaarinstelling]
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.3. Inventarisnummer op bewaarplaats -> container: objectInventarisnrBpltsInfo    (id=235)
// Instelling:  objectInventarisnrBplts_inst:                                   (id=415)
// overname van de instellingsnaam uit bewaarinstelling_ven
// Nummer :     objectInventarisnrBplts                                         (id=417)
// (2)velden: Syntax -> Bplts_2: Bplts_1
// (arrays mogelijk) - single field array container
// Moet mee met bovenstaande relatie naar ca_entities ingevuld worden
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.4. Verworven van -> relatie naar ca_entities -> apart programma
//[vorigeeigenaar] relatie
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//4.5. Verwerving -> container: acquisitionInfo                                 (id=280)
//  Van->       Text: acquisitionSource                                         (id=426)
//  Datum->     DateRange: acquisitionDate (3)velden                            (id=282)
//  Methode->   Text: acquisitionMethode (1)veld                                (id=284)
//  Bijk.Info-> Text: acquisitionNote (7)velden                                 (id=286)
//  (één array -> vormen alles om tot array)

// resultaat: 12 ERROR: reden: Datum is ongeldig

    $acq_source = 'acquisitionSource';
    $acq_date_1 = 'acquisitionDate_1';
    $acq_date_2 = 'acquisitionDate_2';
    $acq_date_3 = 'acquisitionDate_3';
    $acq_meth_2 = 'acquisitionMethode_2';
    $acq_note_1 = 'acquisitionNote_1';
    $acq_note_2 = 'acquisitionNote_2';
    $acq_note_3 = 'acquisitionNote_3';
    $acq_note_4 = 'acquisitionNote_4';
    $acq_note_5 = 'acquisitionNote_5';
    $acq_note_6 = 'acquisitionNote_6';
    $acq_note_7 = 'acquisitionNote_7';

    if ( (isset($resultarray[$acq_source])) ||
         (isset($resultarray[$acq_date_1])) || (isset($resultarray[$acq_date_2])) || (isset($resultarray[$acq_date_3])) ||
         (isset($resultarray[$acq_meth_2])) || (isset($resultarray[$acq_note_1])) ||
         (isset($resultarray[$acq_note_2])) || (isset($resultarray[$acq_note_3])) || (isset($resultarray[$acq_note_4])) ||
         (isset($resultarray[$acq_note_5])) || (isset($resultarray[$acq_note_6])) || (isset($resultarray[$acq_note_7])) ) {

        $aantal_verwerving = $t_func->Herhalen($resultarray, $acquis_velden);

        if ($aantal_verwerving > 0) {

            $res_verwerving = $t_func->makeArray2($resultarray, $aantal_verwerving, $acquis_velden);
            $aantal = $aantal_verwerving - 1 ;
            $i = 0;

            for ($i=0; $i <= ($aantal) ; $i++) {

                //Date -> drie data samenvoegen
                $acquisitionDate = '';
                if ( (isset($res_verwerving[$acq_date_3][$i])) && (!empty($res_verwerving[$acq_date_3][$i])) ) {
                    if ( (isset($res_verwerving[$acq_date_1][$i])) && (!empty($res_verwerving[$acq_date_1][$i])) ) {
                        if ( (isset($res_verwerving[$acq_date_2][$i])) && (!empty($res_verwerving[$acq_date_2][$i])) ) {
                            $acquisitionDate =
                            $res_verwerving[$acq_date_2][$i]." ".$res_verwerving[$acq_date_3][$i]."-".$res_verwerving[$acq_date_1][$i];
                        }else{
                            $acquisitionDate = $res_verwerving[$acq_date_3][$i]."-".$res_verwerving[$acq_date_1][$i];
                        }
                    }else{
                        if ( (isset($res_verwerving[$acq_date_2][$i])) && (!empty($res_verwerving[$acq_date_2][$i])) ) {
                            $acquisitionDate = $res_verwerving[$acq_date_2][$i]." ".$res_verwerving[$acq_date_3];
                        }else{
                            $acquisitionDate = $res_verwerving[$acq_date_3][$i];
                        }
                    }
                }else{
                    if ( (isset($res_verwerving[$acq_date_1][$i])) && (!empty($res_verwerving[$acq_date_1][$i])) ) {
                        if ( (isset($res_verwerving[$acq_date_2][$i])) && (!empty($res_verwerving[$acq_date_2][$i])) ) {
                            $acquisitionDate =
                            $res_verwerving[$acq_date_2][$i]." ".$res_verwerving[$acq_date_1][$i];
                        }else{
                            $acquisitionDate = $res_verwerving[$acq_date_1][$i];
                        }
                    }else{
                        if ( (isset($res_verwerving[$acq_date_2][$i])) && (!empty($res_verwerving[$acq_date_2][$i])) ) {
                            $acquisitionDate = $res_verwerving[$acq_date_2][$i];
                        }
                    }
                }

                $acquisition = array();
                //if (!$t_func->is_valid_date($acquisitionDate)) { $acquisitionDate = ""; }
                if (!($t_texp->parse($acquisitionDate)) ) {
                    $log->logWarn('WARNING: problemen met datum - naar Note-veld:', $t_texp->getParseErrorMessage());
                    $acquisition[] = "Acquisitiondatum: ".$acquisitionDate;
                    $acquisitionDate = null;
                }

                //Methode

                $acquisitionNote = '';
                $acq_array = array($acq_note_1 => 'Opm.1: ', $acq_note_4 => 'Door: ', $acq_note_5 => 'Van: ',
                                    $acq_note_6 => 'Prijs: ', $acq_note_7 => 'Opm.2: ');

                //Notes -> maken eerst array en voegen dan samen met implode
                foreach ($acq_array as $key => $value) {
                    if ( (isset($res_verwerving[$key][$i])) && (!empty($res_verwerving[$key][$i])) ) {
                        $acquisition[] = $value.$res_verwerving[$key][$i];
                    }
                }
                if ( (isset($res_verwerving[$acq_note_2][$i])) && (!empty($res_verwerving[$acq_note_2][$i])) ) {
                    if ( (isset($res_verwerving[$acq_note_3][$i])) && (!empty($res_verwerving[$acq_note_3][$i])) ) {
                        $acquisition[] = "Prijs: ".$res_verwerving[$acq_note_2][$i].' '.$res_verwerving[$acq_note_3][$i];
                    }else{
                        $acquisition[] = "Prijs: ".$res_verwerving[$acq_note_2][$i];
                    }
                }else{
                    if ( (isset($res_verwerving[$acq_note_3][$i])) && (!empty($res_verwerving[$acq_note_3][$i])) ) {
                        $acquisition[] = "Prijs: ".$res_verwerving[$acq_note_3][$i];
                    }
                }
                if (!empty($acquisition)) {
                    asort($acquisition);
                    $acquisitionNote = implode("\n",$acquisition);
                }

                $container = "acquisitionInfo";
                $data = array(  'locale_id'                 =>	$pn_locale_id,
                                'acquisitionSource'         =>  $res_verwerving[$acq_source][$i],
                                'acquisitionDate'           =>	$acquisitionDate,
                                'acquisitionMethode'        =>	$res_verwerving[$acq_meth_2][$i],
                                'acquisitionNote'           =>	$acquisitionNote);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);

                unset($acquisition);
                unset($acquisitionNote);
                unset($acquisitionDate);
                unset($acq_array);
                unset($container);
                unset($data);
            }
            unset($res_verwerving);
            unset($aantal);
            unset($i);
        }
        unset($aantal_verwerving);
    }

//##############################################################################
//SCHERM 5: BEHEER
//##############################################################################
//5.1. Publiceren naar Europeana -> nieuw veld
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//5.2. Status -> List: workflow_statuses -> reeds ingevuld
//  deze info is nodig voor aanmaken object (zie bovenaan)
//  combinatie van publication_data en publishing_allowed
//##############################################################################
//SCHERM 6: RELATIES
//##############################################################################
//6.1. Gerelateerd object
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.2. Gerelateerd concept
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.3. Gerelateerd object - bijkomende info
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.4. Gerelateerd personen en instellingen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.5. Gerelateerd collecties
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.6. Gerelateerde plaatsen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.7. Gerelateerd gebeurtenissen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//6.8. Trefwoorden
//##############################################################################
//SCHERM 7: SETS
//##############################################################################
//7.1. Sets -> cag_sets
//##############################################################################
//SCHERM 8: MEDIA
//##############################################################################
//8.1. Media -> cag_afbeeldingen
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

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

    foreach ($singlefieldarray as $value) {
        if ( (isset($resultarray[$value])) && (!empty($resultarray[$value])) && (is_array($resultarray[$value])) ) {
            $aantal = count($resultarray[$value]) - 1;
            $i = 0 ;
            for ($i= 0; $i <= $aantal; $i++) {
                $container = $value;
                $data = array($value    =>  trim($resultarray[$value][$i]),
                        'locale_id'     =>  $pn_locale_id);
                $my_objects->addSomeObjectAttribute($vn_left_id, $container, $data);
                unset($data);
                unset($container);
            }
            unset($aantal);
            unset($i);
        }
    }

    $teller = $teller + 1;

    $reader->next();
}

$reader->close();

$log->logInfo('EINDE VERWERKING');