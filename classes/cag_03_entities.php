<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
define("__PROG__","entities");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Lists.php");
require_once(__MY_DIR__."/cag_tools/classes/Entities.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$my_list = new Lists();

$t_list = new ca_lists();

$my_entity = new Entities();

//==============================================================================initialisaties
$teller = 1;
$pref = array();
//==============================================================================
$arrayField = array('$adresTelefoon' => 'adresTelefoon', '$adresEmail' => 'adresEmail', '$adresWebsite' => 'adresWebsite');
$singleField = array ('adlibObjectNummer','persoonBiografie','opmerkingEntities');
$org = $t_list->getItemIDFromList('entity_types', 'organization');
$datum_type = $t_list->getItemIDFromList('persoonDatum_type','i1');
$adres_onbekend = $t_list->getItemIDFromList('adres_onbekend_lijst', 'nee');
$aandeelWerkveld = $t_list->getItemIDFromList('aandeel_werkveld_lijst', 'tempImport');
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_entities_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/entities.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo("==================".($teller)."==================");
    $log->logInfo('de originele data', $resultarray);

    $action = $my_entity ->actionToTake($resultarray, $pref, $pn_locale_id);

    $log->logInfo("actie (INSERT of UPDATE)? ", $action);
    $log->logInfo('de gewijzigde data', $resultarray);

    if ($action === "INSERT") {

        $pn_entity_type_id = $my_entity->whichEntityTypeId($resultarray);

        if ($pn_entity_type_id === 0) {
            $log->logError("ERROR: entity_type niet vast te stellen: ", $resultarray['entity_types']);
            $log->logError("ERROR: dus we stoppen ermee voor record: ", $resultarray['adlibObjectNummer']);
            $action = "";
            //$reader->next();
        }
        //al wat nu volgt is enkel nodig indien we entity_type hebben kunnen vaststellen (dwz != 0)
        if ($pn_entity_type_id !== 0) {
            //er zijn een aantal records zonder preferred_label
            //dus creeren we er zelf één op basis van priref
            $Identificatie = $my_entity->defineIdentificatie($resultarray, $pref);
            $log->logInfo("Identificatie: ", $Identificatie);

            $status = $my_entity->defineStatus($resultarray);
            $log->logInfo("status: ", $status);
            $idno = $resultarray['adlibObjectNummer'];

            $entity_id = $my_entity->insertEntity($Identificatie, $idno, $status, $pn_entity_type_id, $pn_locale_id);

            $my_entity->checkPreferredLabel($pref, $entity_id, $pn_locale_id, TRUE);

            $container = 'categoriePersOrg';
            $data = array('categoriePersOrg'  =>  $pn_entity_type_id,
                          'locale_id'         =>  $pn_locale_id);
            $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
        }
        unset($container);
        unset($data);
    }

    if ($action === "UPDATE") {

        $va_left_keys = $resultarray['va_left_keys'];

        if ((sizeof($va_left_keys)) > 1 ) {
            $log->logError("ERROR: PROBLEMS with entity: meerdere kandidaten gevonden, we nemen de eerste !!!!!", $va_left_keys);
        }

        $entity_id  = $va_left_keys[0];
    }

    if ($action === "INSERT" || $action === "UPDATE") {

        $log->logInfo('Entity_id: ', $entity_id);

        #voorbereiden data
        //er zijn een aantal velden die op hun beurt weer een array zijn
        //velden worden samengevoegd gescheiden door ' ; '
        //dit is het geval voor telefoonnummer, email, url
        foreach ($arrayField as $key => $value) {
            if (isset($resultarray[$value])) {
                if (is_array($resultarray[$value])) {
                    $$key = $t_func->check_input($key, $resultarray[$value][0]);

                    $teller_aantal = (count($resultarray[$value]) - 1);

                    for ($j = 1; $j <= $teller_aantal; $j++) {
                        $$key = $$key.$t_func->check_input($key,$resultarray[$value][$j]);
                    }
                }else{
                    $$key = $t_func->check_input($key, $resultarray[$value]);
                }
    #6          //opdat er op het einde geen ; komt als er slechts één item is
    #19         //deze array kan eindigen met ;  -> weghalen
                //zie recoord 2369 - 10000338 - adresTelefoon
                if (substr_count($$key,';') === 1 ) {
                    $$key = trim($$key,';');
                }
                $resultarray[$value] = $$key;
            }
        }
        //single field containers toevoegen
        //'adlibObjectNummer','persoonBiografie','opmerkingEntities'
        foreach ($singleField as $value) {
            if ( (isset($resultarray[$value])) &&(!empty($resultarray[$value])) ) {
                $container = $value;
                $data = array($value        =>  trim($resultarray[$value]),
                            'locale_id'     =>  $pn_locale_id);
                $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
            }
            unset($container);
            unset($data);
        }

        //enkel in te vullen bij een instelling
        if ($pn_entity_type_id === $org ) {
            if ( (isset($resultarray['aandeel'])) && (!empty($resultarray['aandeel'])) ) {
                //'aandeel_lijst' => list_id =132
                $container = 'werkveldInfo';
                $aandeel = $t_list->getItemIDFromListByItemValue('aandeel_lijst','aandeel_'.trim($resultarray['aandeel']));
                $data = array(
                    'aandeelWerkveld'   => $aandeelWerkveld,
                    'aandeel'           => $aandeel,
                    'locale_id'         => $pn_locale_id);
                $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
            }
            unset($container);
            unset($data);
            unset($aandeel);

            if ( (isset($resultarray['non_preferred_label'])) && (!empty($resultarray['non_preferred_label'])) ) {
                //non preferred label kan van type array zijn
                if (is_array($resultarray['non_preferred_label'])) {
                    $l = 0;
                    $aantal_labels = (count($resultarray['non_preferred_label']) - 1);
                    for ($l = 0; $l <= $aantal_labels; $l++) {
                        if (!empty($resultarray['non_preferred_label'][$l])) {
                            $Identificatie = trim($resultarray['non_preferred_label'][$l]);
                            $my_entity->addOtherPreferredLabel($entity_id, $Identificatie, $pn_locale_id, FALSE);
                        }
                    }
                    unset($aantal_labels);
                } else {
                    $Identificatie = trim($resultarray['non_preferred_label']);
                    $my_entity->addOtherPreferredLabel($entity_id, $Identificatie, $pn_locale_id, FALSE);
                }
            }
        }

        //de alternatieve labels
        if (isset($resultarray['alt_label'])) {

            $fields_alt = array('alt_label');
            $aantal_alt = sizeof($resultarray['alt_label']);

            if ($aantal_alt > 0) {
                $alt = $t_func->makeArray2($resultarray, $aantal_alt, $fields_alt);

                foreach ($alt['alt_label'] as $key => $value) {
                    if ( !empty($value) ) {
                        $Identificatie = trim($value);
                        $my_entity->addOtherPreferredLabel($entity_id, $Identificatie, $pn_locale_id, FALSE);
                        $log->logInfo('alt_Label aangemaakt', $Identificatie);
                    }
                }
                unset($alt);
            }
            unset($fields_alt);
            unset($aantal_alt);
        }

        //adresgegevens
        if ( (isset($resultarray['adres_straat'])) || (isset($resultarray['adres_postalcode']))
          || (isset($resultarray['adres_city'])) || (isset($resultarray['adres_stateprovince']))
          || (isset($resultarray['adres_country'])) || (isset($resultarray['adresOpmerking']))
          || (isset($resultarray['adresTelefoon'])) || (isset($resultarray['adresEmail']))
          || (isset($resultarray['adresWebsite'])) ) {

            $container = 'adres';
            $data= array('adres_onbekend'    =>  $adres_onbekend,
                        'adres_straat'      =>  $resultarray['adres_straat'],
                        'adres_postalcode'  =>  $resultarray['adres_postalcode'],
                        'adres_city'        =>  $resultarray['adres_city'],
                        'adres_stateprovince'=> $resultarray['adres_stateprovince'],
                        'adres_country'     =>  $resultarray['adres_country'],
                        'adresOpmerking'    =>  $resultarray['adresOpmerking'],
                        'adresTelefoon'     =>  $resultarray['adresTelefoon'],
                        'adresEmail'        =>  $resultarray['adresEmail'],
                        'adresWebsite'      =>  $resultarray['adresWebsite'],
                        'locale_id'         =>  $pn_locale_id);
            $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
        }
        unset($container);
        unset($data);

        //Job_title: lijst persoonFunctie_lijst (list_id = 123): wordt 'on the fly' opgebouwd
    #19 //Er zijn 3 gevalen (1144  10000277) waar persoonFunctie een array is)
        // en (937 10002167) en (1879  10000081) en er zit een lege array bij
        // Vormen alles naar een array_vorm om
        if (isset($resultarray['persoonFunctie'])) {
            $fields_functies = array('persoonFunctie');
            $aantal_functies = sizeof($resultarray['persoonFunctie']);

            if ($aantal_functies > 0) {
                $functie = $t_func->makeArray2($resultarray, $aantal_functies, $fields_functies);

                foreach ($functie['persoonFunctie'] as $key => $value) {
                    if ((trim($value)) !== '') {

                        $t_item = $my_list->createListItem('persoonFunctie_lijst', trim($value), $pn_locale_id);

                        if ($t_item) {
                            $container = 'persoonFunctie';
                            $listitem_id = $t_list->getItemIDFromList('persoonFunctie_lijst',trim($value));
                            $data = array('persoonFunctie'    => $listitem_id,
                                        'locale_id'         => $pn_locale_id);
                            $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
                        }
                        unset($listitem_id);
                        unset($container);
                        unset($data);
                    }
                }
                unset($functie);
            }
            unset($aantal_functies);
            unset($fields_functies);
        }

     #8 geboorte-/sterftedatum -> veronderstellen GEEN ARRAYS
        if ( (isset($resultarray['geboorteDatum'])) && (!empty($resultarray['geboorteDatum'])) ) {

            $persoonDatum = $my_entity->createPersoonDatum($resultarray);
            $persoonDatumOpmerking = $my_entity->createPersoonDatumOpmerking($resultarray);
            $container = 'persoonDatumInfo';
            $data = array('persoonDatum'          =>  $persoonDatum,
                        'persoonDatum_lijst'    =>  $datum_type,
                        'persoonDatumOpmerking' =>  $persoonDatumOpmerking,
                        'locale_id'              => $pn_locale_id);
            $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
            unset($container);
            unset($data);
            unset($persoonDatum);
            unset($persoonDatumOpmerking);
        }
    }
    unset($action);
    unset($Identificatie);
    unset($status);
    unset($va_left_keys);
    unset($pn_entity_type_id);
    unset($entity_id);
    unset($resultarray);

    $teller = $teller + 1;
    $reader->next();
    /*
    if ($teller > 2568) {
        die;
    } else {
        $reader->next();
    }
     *
     */
}
$reader->close();

$log->logInfo("EINDE VERWERKING");