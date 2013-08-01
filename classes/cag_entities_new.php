<?php
/*
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

define("__PROG__","entities_1");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$aandeelWerkveld = $t_list->getItemIDFromList('aandeel_werkveld_lijst', 'tempImport');

$t_entity = new ca_entities();
$t_entity->setMode(ACCESS_WRITE);

//==============================================================================initialisaties
$teller = 1;
//==============================================================================inlezen bestanden
$termen = array('abdij','zuster','bibliotheek','dienstmaagd','firma','garage','gasthuis','gebroeders','gemeentebestuur','stad',
            'geelhand','winkel','museum','looza','pclt','fabriek','sancta maria','siba','fruitveiling','hoeve',' vzw');
$individual = array('auteur','behandelaar','persoon');
$hist_persoon = array('vervaardiger');
$organization = array('corporatieve auteur','drukker','instelling','Productie maatschappij','uitgever','vereniging');

//$adres = array('adres_straat','adres_postalcode','adres_city','adres_stateprovince','adres_country','adresOpmerking',
//                'adresTelefoon','adresEmail','adresWebsite');

$arrayField = array('$adresTelefoon' => 'adresTelefoon', '$adresEmail' => 'adresEmail', '$adresWebsite' => 'adresWebsite');

$singleField = array ('adlibObjectNummer','persoonBiografie','opmerkingEntities');

//==============================================================================
// de eerste loop door de data
//==============================================================================
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_entities_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__CA_BASE_DIR__."/cag_tools/data/entities.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo("========".($teller)."========");


    $action = "INSERT";

    $fields_use = array('use');
    $aantal_use = sizeof($resultarray['use']);
    $use = $t_func->makeArray2($resultarray, $aantal_use, $fields_use);

    //controleren of er use of used_for vermelding is
    if ( (isset($use['use'][0])) && (!empty($use['use'][0])) ) {
        //opzoeken of een record met deze 'use' al bestaat
        $va_left_keys = ($t_entity->getEntityIDsByName('', ($use['use'][0])));
        if (!empty($va_left_keys)) {
            //record met deze label bestaat reeds -> gegevens toevoegen -> UPDATE
            $action = "UPDATE";
            //bestaat deze alternatieve benaming al?
            $resultarray['alt_label'] = $resultarray['preferred_label'];
        }else{
            //record met deze naam bestaat nog niet -> INSERT
            // 'use' worde de Identificatie
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
        $va_left_keys = $t_entity->getEntityIDsByName('', trim($pref['preferred_label'][0]));
        if (empty($va_left_keys)) {
            //record bestaat nog niet -> INSERT
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
        $va_left_keys = $t_entity->getEntityIDsByName('', trim($pref['preferred_label'][0]));
        if (!empty($va_left_keys)) {
            $action = "UPDATE";
        }
    }

    $log->logInfo("actie (INSERT of UPDATE)? ", $action);

    if ($action == "INSERT") {
        //bepalen welk type entiteit we gaan invoegen
        $pn_entity_type_id = 0;

        $aantal = count($resultarray['entity_types']);

        if ($aantal == 1) {
            if (in_array(($resultarray['entity_types']), $individual)) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
            }

            if (in_array(($resultarray['entity_types']), $hist_persoon)) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
            }

            if (in_array(($resultarray['entity_types']), $organization)) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
            }

            if (trim($resultarray['entity_types']) == 'verwervingsbron') {
                if ($t_func->value_in_array($termen, ($resultarray['preferred_label']))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                } else {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                }
            }
        }elseif ($aantal > 1) {
            if ($t_func->value_in_array($individual, ($resultarray['entity_types'][0]))) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
            }

            if ($t_func->value_in_array($organization, ($resultarray['entity_types'][0]))) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
            }

            if (trim($resultarray['entity_types'][0]) == 'verwervingsbron') {
                if ($t_func->value_in_array($termen, ($resultarray['preferred_label']))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
                } else {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
                }
            }

            if (trim($resultarray['entity_types'][0]) == 'vervaardiger') {
                if ($t_func->value_in_array($individual, ($resultarray['entity_types'][1]))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
                }

                if ($t_func->value_in_array($organization, ($resultarray['entity_types'][1]))) {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischeOrganisatie');
                }

                if (trim($resultarray['entity_types'][1]) == 'verwervingsbron') {
                    $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'historischePersoon');
                }
            }
        }

        if ($pn_entity_type_id == 0) {
            $log->logInfo("ERROR: entity_type niet vast te stellen: ", $resultarray['entity_types']);
            $log->logInfo("ERROR: dus we stoppen ermee voor record: ", $resultarray['adlibObjectNummer']);
            $action = "";
            //$reader->next();
        }
        //al wat nu volgt is enkel nodig indien we entity_type hebben kunnen vaststellen (dwz != 0)
        if ($pn_entity_type_id != 0) {
            //er zijn een aantal records zonder preferred_label
            //dus creeren we er zelf één op basis van priref
            if ( (isset($pref['preferred_label'][0])) && (!empty($pref['preferred_label'][0])) ) {

                    $Identificatie = $pref['preferred_label'][0];

            } else {
                $Identificatie [] = '---???-'.$resultarray['adlibObjectNummer'].'-???--- ';
            }
//------------------------------------------------------------------------------
            //workflow_statusses ->
            if (isset($resultarray['workflow_statusses'])) {

                if (strtoupper(trim($resultarray['workflow_statusses'])) == 'JA') {
                    $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i2');
                } elseif (strtoupper(trim($resultarray['workflow_statusses'])) == 'NEEN') {
                    $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i0');
                } else {
                    $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i1');
                }
            }else{
                $resultarray['status'] = $t_list->getItemIDFromList('workflow_statuses', 'i1');
            }

            $status = $resultarray['status'];
            $log->logInfo("status: ", $status);

            $t_entity->clear();

            $t_entity->set('type_id', $pn_entity_type_id);
            $t_entity->set('idno', $resultarray['adlibObjectNummer']);
            $t_entity->set('status', $status);
            $t_entity->set('access', 1);
            $t_entity->set('surname', $Identificatie);
            $t_entity->set('locale_id', $pn_locale_id);
        //----------
            $t_entity->insert();
        //----------
            if ($t_entity->numErrors()) {
                $log->logInfo("ERROR INSERTING record: ", $Identificatie);
                $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                continue;
            }else{
                $log->logInfo("INSERT gelukt voor: ", $Identificatie);

            //----------
                $t_entity->addLabel(array(
                    'surname'     => substr($Identificatie, 0, 99),
                    'displayname' => substr($Identificatie, 0, 99)
                    ),$pn_locale_id, null, true );

                if ($t_entity->numErrors()) {
                    $log->logInfo("ERROR ADDING PREFERRED LABEL : ", $Identificatie);
                    $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                    continue;
                }else{
                    $log->logInfo("ADDLABEL gelukt voor: ", $Identificatie);
                }
                if ( (isset($pref['preferred_label'][1])) && (!empty($pref['preferred_label'][1])) ) {

                    $Identificatie = $pref['preferred_label'][1];

                    $t_entity->addLabel(array(
                        'surname'     => substr($Identificatie, 0, 99),
                        'displayname' => substr($Identificatie, 0, 99)
                        ),$pn_locale_id, null, true );

                    if ($t_entity->numErrors()) {
                        $log->logInfo("ERROR ADDING PREFERRED LABEL : ", $Identificatie);
                        $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                        continue;
                    }else{
                        $log->logInfo("ADDLABEL gelukt voor: ", $Identificatie);
                    }
                }

                $resultarray['primary_key'] = $t_entity->getPrimaryKey();
                //--------
                $t_entity->addAttribute(array(
                        'categoriePersOrg'  =>  $pn_entity_type_id,
                        'locale_id'         =>  $pn_locale_id
                ),'categoriePersOrg');
            }
        }
    }

    if ($action == "UPDATE"){

        if ((sizeof($va_left_keys)) > 1 ) {
            $log->logError("ERROR: PROBLEMS with entity: meerdere records gevonden, we nemen de eerste !!!!!", $va_left_keys[0]);
        }

        $vn_left_id = $va_left_keys[0];
        $t_entity->load($vn_left_id);
        $resultarray['primary_key'] = $t_entity->getPrimaryKey();
    }

    if ($action == "INSERT" || $action == "UPDATE") {

        $log->logInfo('Primary key', $resultarray['primary_key']);

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
                if (substr_count($$key,';') == 1 ) {
                    $$key = trim($$key,';');
                }

                $resultarray[$value] = $$key;
            }
        }
        //single field containers
        foreach ($singleField as $value) {
            if ( (isset($resultarray[$value])) &&(!empty($resultarray[$value])) ) {
                $t_entity->addAttribute(array(
                        $value          =>  trim($resultarray[$value]),
                        'locale_id'     =>  $pn_locale_id
                ), $value);
            }
        }

        //enkel in te vullen bij een instelling
        if ($pn_entity_type_id == $t_list->getItemIDFromList('entity_types', 'organization')) {
            if ( (isset($resultarray['aandeel'])) && (!empty($resultarray['aandeel'])) ) {
                //'aandeel_lijst' => list_id =132
                $t_entity->addAttribute(array(
                    'aandeelWerkveld'   => $aandeelWerkveld,
                    'aandeel'           => $t_list->getItemIDFromListByItemValue('aandeel_lijst','aandeel_'.trim($resultarray['aandeel'])),
                    'locale_id'         => $pn_locale_id
                ), 'werkveldInfo');
            }

            if ( (isset($resultarray['non_preferred_label'])) && (!empty($resultarray['non_preferred_label'])) ) {
                //non preferred label kan van type array zijn
                if (is_array($resultarray['non_preferred_label'])) {
                    $l = 0;
                    $aantal_labels = (count($resultarray['non_preferred_label']) - 1);
                    for ($l = 0; $l <= $aantal_labels; $l++) {
                        if (!empty($resultarray['non_preferred_label'][$l])) {
                            $t_entity->addLabel(array(
                                'displayname'   =>  trim($resultarray['non_preferred_label'][$l]),
                                'surname'       =>  trim($resultarray['non_preferred_label'][$l])
                            ),$pn_locale_id, NULL,FALSE);
                        }
                    }
                } else {
                    $t_entity->addLabel(array(
                        'displayname'   =>  trim($resultarray['non_preferred_label']),
                        'surname'       =>  trim($resultarray['non_preferred_label'])
                    ),$pn_locale_id, NULL,FALSE);
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
                        $va_left_keys = $t_entity->getEntityIDsByName('', trim($value));
                        if (empty($va_left_keys)) {
                            $t_entity->addLabel(array(
                                'displayname'   =>  trim($value),
                                'surname'       =>  trim($value)
                            ),$pn_locale_id, NULL,FALSE);
                        }
                    }
                }
            }
        }

        //adresgegevens
        if ( (isset($resultarray['adres_straat'])) || (isset($resultarray['adres_postalcode']))
          || (isset($resultarray['adres_city'])) || (isset($resultarray['adres_stateprovince']))
          || (isset($resultarray['adres_country'])) || (isset($resultarray['adresOpmerking']))
          || (isset($resultarray['adresTelefoon'])) || (isset($resultarray['adresEmail']))
          || (isset($resultarray['adresWebsite'])) ) {

            $t_entity->addAttribute(array(
                    'adres_onbekend'    =>  $t_list->getItemIDFromList('adres_onbekend_lijst', 'nee'),
                    'adres_straat'      =>  $resultarray['adres_straat'],
                    'adres_postalcode'  =>  $resultarray['adres_postalcode'],
                    'adres_city'        =>  $resultarray['adres_city'],
                    'adres_stateprovince'=> $resultarray['adres_stateprovince'],
                    'adres_country'     =>  $resultarray['adres_country'],
                    'adresOpmerking'    =>  $resultarray['adresOpmerking'],
                    'adresTelefoon'     =>  $resultarray['adresTelefoon'],
                    'adresEmail'        =>  $resultarray['adresEmail'],
                    'adresWebsite'      =>  $resultarray['adresWebsite'],
                    'locale_id'         =>  $pn_locale_id
            ),'adres');
        }

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
                    if ((trim($value)) != "") {
                        $t_list->clear();
                        $t_list->load(array('list_code' => 'persoonFunctie_lijst'));

                        $t_item = $t_list->getItemIDFromList('persoonFunctie_lijst', trim($value));

                        if ($t_item) {
                            $log->logInfo("Deze LABEL bestaat reeds in LIST persoonFunctie_lijst: ", $value);
                        }else{
                            $t_item = $t_list->addItem(trim($value), true, false, null, null,
                                                       trim($value),'', 4, 1);
                            if ($t_item){
                                $log->logInfo("ITEM added tot LIST persoonFunctie_lijst: ", $value);
                                //add preferred labels
                                if (!($t_item->addLabel(array(
                                    'name_singular' => trim($value),
                                    'name_plural'   => trim($value),
                                    'description'   =>  ''
                                    ),$pn_locale_id, null, true ))) {
                                        $log->logError("ERROR ADDING LABEL TO LIST persoonFunctie_lijst: ", $value);
                                        $log->logError("ERROR messages: ",join('; ', $t_item->getErrors()));
                                        continue;
                                }else{
                                        $log->logInfo("LABEL added to LIST persoonFunctie_lijst: ", $value);
                                }
                            }else{
                                $log->logError("ERROR ADDING ITEM TO LIST persoonFunctie_lijst: ", $value);
                                $log->logError("ERROR messages: ",join('; ', $t_list->getErrors()));
                                continue;
                            }
                        }

                        if ($t_item) {
                            $t_entity->addAttribute(array(
                                    'persoonFunctie'    => $t_list->getItemIDFromList('persoonFunctie_lijst',trim($value)),
                                    'locale_id'         => $pn_locale_id
                            ), 'persoonFunctie');
                        }else{
                            $log->logError("ERROR: addAttribute persoonFunctie", $value);
                        }
                    }
                }
                unset($functie);
            }
            unset($aantal_functies);
            unset($fields_functies);
        }

     #8 geboorte-/sterftedatum -> veronderstellen GEEN ARRAYS
        if ( (isset($resultarray['geboorteDatum'])) && (!empty($resultarray['geboorteDatum'])) ) {
            if ( (isset($resultarray['sterfteDatum'])) && (!empty($resultarray['sterfteDatum'])) ) {
                $persoonDatum = $resultarray['geboorteDatum']." - ".$resultarray['sterfteDatum'];
            }  else {
                $persoonDatum = $resultarray['geboorteDatum']." - ";
            }

            if ( (isset($resultarray['geboortePlaats'])) && (!empty($resultarray['geboortePlaats'])) ) {
                $persoonDatumOpmerking = "Geboorteplaats: ".$resultarray['geboortePlaats'];
            } else {
                $persoonDatumOpmerking = "";
            }

            $t_entity->addAttribute(array(
                    'persoonDatum'          =>  $persoonDatum,
                    'persoonDatum_lijst'    =>  $t_list->getItemIDFromList('persoonDatum_type','i1'),
                    'persoonDatumOpmerking' =>  $persoonDatumOpmerking,
                    'locale_id'              => $pn_locale_id
            ), 'persoonDatumInfo');

            unset($persoonDatum);
            unset($persoonDatumOpmerking);
        }
     #8
    //-------------
        $t_entity->update();
    //-------------
        if ($t_entity->numErrors())  {
            $log->logInfo("ERROR UPDATING record : ", $resultarray['adlibObjectNummer']);
            $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
            continue;
        }else{
            $log->logInfo("UPDATE gelukt voor record: ", $resultarray['adlibObjectNummer']);
        }
    }
    unset($fields_alt);
    unset($fields_functies);
    unset($fields_pref);
    unset($fields_use);
    unset($fields_used_for);
    unset($aantal_alt);
    unset($aantal_functies);
    unset($aantal_pref);
    unset($aantal_use);
    unset($aantal_used_for);
    unset($alt);
    unset($functie);
    unset($pref);
    unset($use);
    unset($used_for);

    $teller = $teller + 1;
    $reader->next();
}

$reader->close();

$log->logInfo("EINDE VERWERKING");


//==============================================================================
//----------------
//relatie leggen --> apart programma van maken -> functie voor schrijven
//----------------
/* -> zie cag_entities_relaties_new.php
$k = 0;
$l = 0;
$aantal = count($relaties) - 1;
for ($k = 0; $k <= ($aantal); $k++)
{
    $vn_left_id = $relaties[$k]['primary_key'];
    $fields = array('c_contact');
    $aantal_contact = sizeof($relaties[$k]['c_contact']);

    if ($aantal_contact > 0) {
        $contacts = makeArray2($relaties[$k], $aantal_contact, $fields);

        foreach ($contacts as $key => $value) {
            $vs_right_id = trim($value);
            $log->logInfo("Relatie leggen tussen: ", $vn_left_id."en".$vs_right_id);
            $t_entity->load($vn_left_id);
            $t_entity->getPrimaryKey();
            $t_entity->set('entity_id', $vn_left_id);

            $va_right_id = $t_entity->getEntityIDsByName('', trim($value));

            $aantal = sizeof($va_right_id);

            if ($aantal == 1) {
                $t_entity->addRelationship('ca_entities', $va_right_id[0], $vn_entities_x_entities);

                if ($t_entity->numErrors()) {
                    $log->logInfo("ERROR ADDING RELATIONSHIP : ", $value);
                    $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                    continue;
                }else{
                    $log->logInfo("ADDING RELATIONSHIP gelukt voor record: ", $relaties[$k]);
                }
            }else{
                $log->logError("ERROR: relatie leggen mislukt");
            }
            unset($va_right_id);
        }
        unset($contacts);
    }
    unset($aantal_contact);
    unset($fields);
    unset($vn_left_id);
}
 *
 */