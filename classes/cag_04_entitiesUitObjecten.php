<?php
/* Doel van dit programma:
 *  entities aanmaken op basis van de velden uit objecten.xml en sinttruiden.xml
 *  dient uitgevoerd VOOR het aanmaken van de objecten
 *  Werkwijze: de velden worden de sleutels van een array
 *
 */
define("__PROG__","entitiesUitObjecten");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Lists.php");
require_once(__MY_DIR__."/cag_tools/classes/Entities.php");
require_once(__MY_DIR__."/cag_tools/classes/EntitiesUitObjecten.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$t_entity = new ca_entities_bis();

$my_entity = new Entities();

$my_entuitobj = new EntitiesUitObjecten();

$entities = array();
$entities2 = array();
//==============================================================================initialisaties
$termen = array('abdij', 'zuster','bibliotheek','dienstmaagd','firma','garage','gasthuis','gebroeders','gemeentebestuur','stad',
                    'geelhand','winkel', 'collectie', 'museum','looza','pclt','fabriek','sancta maria','siba','fruitveiling','hoeve',
                    'vzw', 'beeldbank', 'soma', 'fonds', 'kadoc', 'swaene', 'zuiderkempen');
$fields = array('vervaardiger', 'eigenaar_van', 'bewaarinstelling', 'bewaarinstelling_van', 'acquisitionSource');
$fields2 = array('gerelateerd_aan');
//==============================================================================
//inlezen (in array) mapping-bestand
$xml = array("/cag_tools/data/objecten.xml", "/cag_tools/data/sinttruiden.xml");

$mappingarray = $t_func->ReadMappingcsv("cag_entities_uit_objecten.csv");
//inlezen xml-bestand met XMLReader, node per node

foreach ($xml as $bestand) {

    $reader = new XMLReader();
    $reader->open(__MY_DIR__.$bestand);

    while ($reader->read() && $reader->name !== 'record');
    //==============================================================================begin van de loop
    while ($reader->name === 'record' )
    {
        //node omvormen tot associatieve array
        $xmlarray = $t_func->ReadXMLnode($reader);

        $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

        $my_entuitobj->createEntitiesArray($resultarray, $fields, $entities, $pn_locale_id);

        $my_entuitobj->createEntitiesArray($resultarray, $fields2, $entities2, $pn_locale_id);

        $reader->next();
    }
    $reader->close();

}
$log->logInfo('Inhoud entities-array', $entities);
$log->logInfo('Inhoud entities2-array', $entities2);

# verwerking $entities-arrays
$arrays = array('entities', 'entities2');

$status = $t_list->getItemIDFromList('workflow_statuses', 'i1');

$tel = 1;
foreach ($arrays as $input) {
    //array to process
    $array_to_process = $$input;

    foreach ($array_to_process as $key => $value) {
        $pn_entity_type_id = 0;

        $log->logInfo('====='.$tel.'=====');

        $action = "INSERT";

        if ($key !== "") {
            //opzoeken of een record met deze 'key' al bestaat
            $search_string = $key;
            $va_left_keys = ($t_entity->getEntityIDsByUpperNameSort($search_string));
            if (!empty($va_left_keys)) {
                $log->logInfo('Record met deze label bestaat reeds', $va_left_keys);
                $action = "";
            }
        }

        $log->logInfo('actie (INSERT of ...)?', $action);

        if ($action === "INSERT") {

            $Identificatie = trim($value);
#62 #48
            if ($t_func->value_in_array($termen, strtolower($value))) {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'organization');
            } else {
                $pn_entity_type_id = $t_list->getItemIDFromList('entity_types', 'individual');
            }

            $log->logInfo('entity_type_id', $pn_entity_type_id);

            $idno = 'euo_'.sprintf('%04d',$tel);
            $my_entity->insertEntity($Identificatie, $idno, $status, $pn_entity_type_id, $pn_locale_id);

            unset($search_string);
            unset($Identificatie);
            unset($idno);
            unset($pn_entity_type_id);
        }
        $tel = $tel + 1;
    }
    $log->logInfo('EINDE VERWERKING', $input);
}
$log->logInfo('EINDE PROGRAMMA');