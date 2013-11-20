<?php
/*Doel van dit programma:
 * Op basis van het objecten.xml-bestand, wordt een array gemaakt van occurrences
 * Vervolgens worden deze occurrences aangemaakt in CA.
 *
 * refPagina = id-242 -> komt 66 keer voor -> moet bij het object vermeld worden -> nok in cag_objecten_relaties.php opnemen
 * refAuteur = id-243 -> komt 61 keer voor -> moet bij publicatie vermeld worden -> ok
 * refNote   = id-255 -> komt 21 keer voor -> moet bij publicatie vermeld worden -> ok
 * ref..Date = id-253 -> nieuw veld
 */
define("__PROG__","occurrences");

include('header.php');

require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Occurrences.php");
require_once(__MY_DIR__."/cag_tools/classes/EntitiesUitObjecten.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_entity = new ca_entities_bis();
$t_occurrence = new ca_occurrences();

$my_occurrence = new Occurrences();
$my_entuitobj =new EntitiesUitObjecten();

$t_list = new ca_lists();
$t_rel_types = new ca_relationship_types();

$pn_occurrence_type_id = $t_list->getItemIDFromList('occurrence_types', 'references');
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');
//==============================================================================initialisaties
$teller = 1;
$occur = array();
$occur2 = array();
$fields = array('preferred_label_occur_1', 'preferred_label_occur_2', 'refNote', 'refAuteur');
$fields2 = array('refNote', 'refAuteur');
$fields3 = array('objectnaamOpmerkingen_3');
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_occurrences_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/objecten.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' )
{
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo('=========='.$teller.'==========');
    $log->logInfo('de data', $resultarray);

    if ( (!isset($resultarray['preferred_label_occur_2']))) {
                $log->logError('ERROR: Record zonder documentation.title (preferred_label_occur_2)', $resultarray);
    } else {

        $my_occurrence->createOccurrencesArray($resultarray, $fields, $occur, $pn_locale_id);
    }

    $my_entuitobj->createEntitiesArray($resultarray, $fields3, $occur2, $pn_locale_id);

    $teller = $teller + 1;
    $reader->next();
}
$log->logInfo('================================ARRAY 1===================================================');
ksort($occur);
$log->logInfo("Inhoud array", $occur);
$log->logInfo('================================'.(sizeof($occur)).'===================================================');
$log->logInfo('================================ARRAY 2===================================================');
ksort($occur2);
$log->logInfo('Inhoud array2', $occur2);
$log->logInfo('================================'.(sizeof($occur2)).'===================================================');
/*
$occur3 = array_merge($occur2,$occur);
$log->logInfo('================================ARRAY 3===================================================');
ksort($occur3);
$log->logInfo('Inhoud array2', $occur3);
$log->logInfo('================================'.(sizeof($occur3)).'===================================================');
 *
 */
//++++++++++++++++++
// Deel 2
//++++++++++++++++++

$log->logInfo('verwerking eerste array');

$new_teller = 1;

foreach ($occur as $key => $value) {
    $occurrence_id = 0;
    if (trim($key) !== "") {
        $idno = sprintf('%08d', $new_teller);

        $log->logInfo("=====".$idno."=====");

        $label = $value['label'];

        $occurrence_id = $my_occurrence->insertOccurrence($label, $pn_occurrence_type_id, $idno, $status, $pn_locale_id);

        if ($occurrence_id) {

            foreach($fields2 as $veld) {

                if ( (!empty($value[$veld]))  &&  $veld === 'refAuteur' ) {
                    /*
                    $container = $veld;
                    $data = array($veld         =>  trim($value[$veld]),
                                'locale_id'     =>  $pn_locale_id);
                    $my_occurrence->addSomeOccurrenceAttribute($occurrence_id, $container, $data);
                     *
                     */
                    $log->logInfo("refAuteur: ", $value[$veld]);

                    $relationship = $t_rel_types->getRelationshipTypeID('ca_entities_x_occurrences', 'auteurRelatie');
                    $search_string = $t_func->generateSortValue(trim($value[$veld]), $pn_locale_id);
                    $va_left_keys = $t_entity->getEntityIDsByUpperNameSort($search_string);

                    if ( (sizeof($va_left_keys)) === 0 ) {

                        $log->logError("ERROR: problems with left-entity", $value[$veld]);
                        $log->logError('GEEN kandidaten gevonden', $va_left_keys);

                    } elseif ((sizeof($va_left_keys)) >= 1 ){

                        if ((sizeof($va_left_keys)) > 1 ) {
                            $log->logWarn("WARNING: problems with left-entity", $value[$veld]);
                            $log->logWarn('Meerdere kandidaten gevonden', $va_left_keys);
                            $log->logWarn('We nemen de eerste entity_id', $va_left_keys[0]);
                        }
                        $vn_left_id = $va_left_keys[0];

                        $t_entity->setMode(ACCESS_WRITE);
                        $t_entity->load($vn_left_id);
                        $t_entity->getPrimaryKey();

                        $t_entity->addRelationship('ca_occurrences', $occurrence_id, $relationship);

                        if ($t_entity->numErrors()) {
                            $log->logError("ERROR LINKING entity en occurrence: ");
                            $log->logError("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                        }else{
                            $log->logInfo("SUCCESS: relation with {$value[$veld]}/{$occurrence_id} succesfull");
                        }
                        //unset($t_entity);
                    }

                }
                //unset($data);
                //unset($container);
            }
        } else {
            $log->logError('ERROR: insertOccurrence mislukt -> addAttribute niet toegelaten', $occur);
        }
        unset($occurrence_id);
   }
   $new_teller = $new_teller + 1;
}

unset($new_teller);

$log->logInfo("EINDE VERWERKING ARRAY_3");

$log->logInfo('verwerking tweede array');

$new_teller2 = 100;

foreach ($occur2 as $key => $value) {
    $occurrence_id = 0;
    if (trim($key) != "") {

        $va_occur_keys = $t_occurrence->getOccurrenceIDsByName($value);

        if ( (empty($va_occur_keys)) ) {

            $idno = sprintf('%08d', $new_teller2);

            $log->logInfo("=====".$idno."=====");

            $label = $value;

            $occurrence_id = $my_occurrence->insertOccurrence($label, $pn_occurrence_type_id, $idno, $status, $pn_locale_id);

            unset($occurrence_id);
        }
   }
   $new_teller2 = $new_teller2 + 1;
}
unset($new_teller2);

$log->logInfo("EINDE VERWERKING ARRAY_2");