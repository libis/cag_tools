<?php
/* Doel van dit programma:
 *  entities aanmaken op basis van de velden uit objecten.xml en sinttruiden.xml
 *  dient uitgevoerd VOOR het aanmaken van de objecten
 *  Werkwijze: de velden worden de sleutels van een array
 *
 */
error_reporting(-1);
set_time_limit(0);
$type = "LOCAL";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}

define("__PROG__","entities_2");
//require_once(__MY_DIR_2__."/cag_tools/classess/My_CAG.php");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");
require_once(__MY_DIR_2__."/cag_tools/classes/ca_places_bis.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
//$aandeelWerkveld = $t_list->getItemIDFromList('aandeel_werkveld_lijst', 'tempImport');

$t_entity = new ca_entities();
$t_entity->setMode(ACCESS_WRITE);
//==============================================================================initialisaties
$termen = array('abdij','zuster','bibliotheek','dienstmaagd','firma','garage','gasthuis','gebroeders','gemeentebestuur','stad',
            'geelhand','winkel','museum','looza','pclt','fabriek','sancta maria','siba','fruitveiling','hoeve',' vzw');
$individual = array('auteur','behandelaar','persoon');
$hist_persoon = array('vervaardiger');
$organization = array('corporatieve auteur','drukker','instelling','Productie maatschappij','uitgever','vereniging');
//==============================================================================
// de eerste loop door de data
//==============================================================================
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_entities_uit_objecten.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__CA_BASE_DIR__."/cag_tools/data/objecten.xml");
//$reader->open('/www/libis/vol03/lias_html/ca_cag/cag_tools/data/sinttruiden.xml');

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' )
{
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $fields = array('vervaardiger', 'eigenaar_van', 'bewaarinstelling', 'bewaarinstelling_van', 'acquisitionSource');
    $aantal = $t_func->Herhalen($resultarray, $fields);

    if ($aantal > 0) {

        $output = $t_func->makeArray2($resultarray, $aantal, $fields);

        foreach ($output as $value2) {
            foreach ($value2 as $value) {

                if ( (empty($value)) || (strlen(trim($value))) == 0){
                }else{
                    $entities[trim($value)] = "";
                }
            }
        }
        unset($output);
    }
    unset($aantal);
    unset($fields);

    $fields2 = array('gerelateerd_aan');
    $aantal2 = $t_func->Herhalen($resultarray, $fields2);

    if ($aantal2 > 0) {

        $output2 = $t_func->makeArray2($resultarray, $aantal2, $fields2);

        foreach ($output2 as $value) {
            foreach ($value as $value2) {

                if ( (empty($value2)) || (strlen(trim($value2))) == 0){
                }else{
                    $entities2[trim($value2)] = $resultarray['org_type'];
                }
            }
        }
        unset($output2);
    }
    unset($aantal2);
    unset($fields2);

    $reader->next();
}

$reader->close();

$log->logInfo('Inhoud entities-array', $entities);

/*
 * Fase 2: doen hetzelfde voor sinttruiden.xml
 */

//inlezen xml-bestand met XMLReader, node per node
$reader2 = new XMLReader();
$reader2->open(__CA_BASE_DIR__."/cag_tools/data/sinttruiden.xml");

while ($reader2->read() && $reader2->name !== 'record');
//==============================================================================begin van de loop
while ($reader2->name === 'record' )
{
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader2);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $fields = array('vervaardiger', 'eigenaar_van', 'bewaarinstelling', 'bewaarinstelling_van', 'acquisitionSource');
    $aantal = $t_func->Herhalen($resultarray, $fields);

    if ($aantal > 0) {

        $output = $t_func->makeArray2($resultarray, $aantal, $fields);

        foreach ($output as $value2) {
            foreach ($value2 as $value) {

                if ( (empty($value)) || (strlen(trim($value))) == 0){
                }else{
                    $entities[trim($value)] = "";
                }
            }
        }
        unset($output);
    }
    unset($aantal);
    unset($fields);

    $fields2 = array('gerelateerd_aan');
    $aantal2 = $t_func->Herhalen($resultarray, $fields2);

    if ($aantal2 > 0) {

        $output2 = $t_func->makeArray2($resultarray, $aantal2, $fields2);

        foreach ($output2 as $value) {
            foreach ($value as $value2) {

                if ( (empty($value2)) || (strlen(trim($value2))) == 0){
                }else{
                    $entities2[trim($value2)] = $resultarray['org_type'];
                }
            }
        }
        unset($output2);
    }
    unset($aantal2);
    unset($fields2);


    $reader2->next();
}

$reader2->close();

//$log->logInfo('Inhoud entities-array', $entities);

/*
 * Fase 3: bestaan alle $keys van de $entities-array
 */

# verwerking $entities-array

$tel = 1;
foreach ($entities as $key => $value)
{
    $pn_entity_type_id = 0;

    $log->logInfo('====='.$tel.'=====');

    $action = "INSERT";

    if ($key != "") {
        //opzoeken of een record met deze 'key' al bestaat
        $va_left_keys = ($t_entity->getEntityIDsByName('', trim($key)));
        if (!empty($va_left_keys)) {
            //record met deze label bestaat reeds -> DONE
            $action = "";
        }
    }

    if ($action == "INSERT") {

        $Identificatie = trim($key);
        $status = $t_list->getItemIDFromList('workflow_statuses', 'i1');
        if ($t_func->value_in_array($termen, ($key))) {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
        } else {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
        }

        $t_entity->clear();
        $t_entity->set('type_id', $pn_entity_type_id);
        $t_entity->set('idno', 'ent_'.$tel);
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
                'surname'     => $Identificatie,
                'displayname' => $Identificatie
                ),$pn_locale_id, null, true );

            if ($t_entity->numErrors()) {
                $log->logInfo("ERROR ADDING PREFERRED LABEL : ", $Identificatie);
                $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                continue;
            }else{
                $log->logInfo("ADDLABEL gelukt voor: ", $Identificatie);
            }
            unset($Identificatie);
            unset($pn_entity_type_id);
        }
    }
    $tel = $tel + 1;
}

$log->logInfo("EINDE VERWERKING ENTITIES-ARRAY");

unset($tel);

# verwerking $entities2-array

$tel2 = 1;
foreach ($entities2 as $key2 => $value2)
{
    $pn_entity_type_id = 0;

    $log->logInfo('====='.$tel2.'=====');

    $action = "INSERT";

    if ($key2 != "") {
        //opzoeken of een record met deze 'key' al bestaat
        $va_left_keys = ($t_entity->getEntityIDsByName('', trim($key2)));
        if (!empty($va_left_keys)) {
            //record met deze label bestaat reeds -> DONE
            $action = "";
        }
    }

    if ($action == "INSERT") {

        $Identificatie = trim($key2);
        $status = $t_list->getItemIDFromList('workflow_statuses', 'i1');
        if ($t_func->value_in_array($individual, ($value2))) {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
        }
        if (value_in_array($organization, ($value2))) {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
        }

        if (value_in_array($termen, ($key2))) {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
        } else {
            $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
        }

        $t_entity->clear();
        $t_entity->set('type_id', $pn_entity_type_id);
        $t_entity->set('idno', 'ent2_'.$tel2);
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
                'surname'     => $Identificatie,
                'displayname' => $Identificatie
                ),$pn_locale_id, null, true );

            if ($t_entity->numErrors()) {
                $log->logInfo("ERROR ADDING PREFERRED LABEL : ", $Identificatie);
                $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                continue;
            }else{
                $log->logInfo("ADDLABEL gelukt voor: ", $Identificatie);
            }
            unset($Identificatie);
            unset($pn_entity_type_id);
        }
    }
    $tel2 = $tel2 + 1;
}

$log->logInfo("EINDE VERWERKING ENTITIES-2-ARRAY");

unset($tel2);
unset($entities);
unset($entities2);

$log->logInfo("EINDE VERWERKING");