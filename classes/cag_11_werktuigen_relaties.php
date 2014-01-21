<?php
/* Doel van dit programma:
 *
 */
define("__PROG__","werktuigen_relaties");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_objects_bis.php");
require_once(__MY_DIR__.'/cag_tools/classes/ca_occurrences_bis.php');
require_once(__MY_DIR__."/cag_tools/classes/Objects.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagConceptVoorwerp_type');
$pn_occurrence_type_id = $t_list->getItemIDFromList('occurrence_types', 'references');
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');

$t_relatie = new ca_relationship_types();
$vn_objects_x_objects_broader = $t_relatie->getRelationshipTypeID('ca_objects_x_objects', 'broaderRelatie');
//$vn_objects_x_objects_narrower = $t_relatie->getRelationshipTypeID('ca_objects_x_objects', 'narrowerRelatie');
$vn_objects_x_occurrences = $t_relatie->getRelationshipTypeID('ca_objects_x_occurrences', 'documentatieRelatie');

$t_occur = new ca_occurrences_bis();
$t_object2 = new ca_objects_bis();
//==============================================================================initialisaties
$teller = 0;
$teller_occur = 200;
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_werktuigen_relaties_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/Werktuigen.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);
    //print_r ($xmlarray);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $teller = $teller + 1;
    $log->logInfo('=========='.$teller.'========');
    $log->logInfo('de originele data', $resultarray);
    //------------------------------------------------------------------------------
    //de identificatie
    $idno = sprintf('%04d', $teller);
    $idno = 'concept'.$idno;
    $log->logInfo("idno: ",$idno);

    $t_object = new ca_objects_bis();
    $t_object->setMode(ACCESS_WRITE);
    $va_left_keys = $t_object->getObjectIDsByIdno($idno);

    if ((sizeof($va_left_keys)) > 1 ) {
        $log->logWarn("WARNING: PROBLEM: found more than one object -> take the first one !!!!!", $va_left_keys);
    }

    $vn_left_id = $va_left_keys[0];
    $t_object->load($vn_left_id);
    $t_object->getPrimaryKey();
    $t_object->set('object_id', $vn_left_id);
    $vs_left_string = $idno;

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // Broader terms
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    $aantal_broader = $t_func->Herhalen($resultarray, array('broader'));

    if ($aantal_broader > 0) {

        $result_broader = $t_func->makeArray2($resultarray, $aantal_broader, array('broader'));
        $aantal = $aantal_broader - 1;
        $i = 0;

        for ($i=0; $i <= ($aantal) ; $i++) {

            if ( (!is_array($result_broader['broader'][$i])) && (!empty($result_broader['broader'][$i]))) {
                $vs_right_string = trim($result_broader['broader'][$i]);

                $log->logInfo('relatie leggen tussen ' . $vs_left_string . '  en   ' . $vs_right_string);

                $va_right_keys = $t_object2->getObjectIDsByName($vs_right_string, null, $pn_object_type_id);

                if (empty($va_right_keys) ) {
                    $log->logError("ERROR: PROBLEM: broader object ". ($vs_right_string) ." niet gevonden!!!!!");
                } else {
                    $vn_right_id = $va_right_keys[0];

                    $t_object->addRelationship('ca_objects_x_objects', $vn_right_id,  $vn_objects_x_objects_broader);

                    if ($t_object->numErrors()) {
                        $log->logInfo("ERROR LINKING  object and broader object: " . join(';', $t_object->getErrors()));
                        continue;
                    } else {
                        $log->logInfo("relatie tot broader term ".$vs_right_string." gelukt");
                    }
                }
                unset($vs_right_string);
                unset($va_right_keys);
                unset($vn_right_id);
            }
        }
        unset($aantal);
        unset($result_broader);
    }
    unset($aantal_broader);

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // Narrower terms
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    /*
     *
    $aantal_narrower = $t_func->Herhalen($resultarray, array('narrower'));

    $result_narrower = $t_func->makeArray2($resultarray, $aantal_narrower, array('narrower'));

    foreach(($result_narrower['narrower']) as $key => $value) {
        if (  (!is_array($result_narrower['narrower'][$key])) && (!empty($result_narrower['narrower'][$key])) ) {
            $vs_right_string = $value;

            $log->logInfo('relatie leggen tussen ' . $vs_left_string . '  en   ' . $vs_right_string);

            $va_right_keys = $t_object->getObjectIDsByName($vs_right_string, null, 22);

            if (empty($va_right_keys) ) {
                $log->logInfo("ERROR: PROBLEM: narrower object ". ($vs_right_string) ." niet gevonden!!!!!");
            } else {
                $vn_right_id = $va_right_keys[0];

                $t_object->addRelationship('ca_objects_x_objects', $vn_right_id,  $vn_objects_x_objects_narrower);

                if ($t_object->numErrors()) {
                    $log->logInfo("ERROR LINKING  object and narrower object: " . join(';', $t_object->getErrors()));
                    continue;
                } else {
                    $log->logInfo("relatie tot narrower term ".$vs_right_string." gelukt");
                }
            }
        }
        unset($vs_right_string);
        unset($va_right_keys);
        unset($vn_right_id);
    }
    unset($result_narrower);
    unset($aantal_narrower);
     *
     */

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // reference
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    if ((isset($resultarray['reference'])) && (!is_array($resultarray['reference']))) {
       if (((trim($resultarray['reference'])) !== '') || (trim($resultarray['reference']) !== '|')) {
            $resultarray['new'] = explode(" | ", $resultarray['reference']);

            foreach($resultarray['new'] as $value) {
                if (strlen(trim($value)) > 0 ) {
                    $vs_right_string = trim($value);

                    $log->logInfo('relatie leggen tussen ' . $vs_left_string . '  en   ' . $vs_right_string);

                    $va_right_keys = $t_occur->getOccurrenceIDsByName($vs_right_string);

                    if (empty($va_right_keys)) {
                        $idno_occur = sprintf('%08d', $teller_occur);

                        $t_occur = new ca_occurrences();
                        $t_occur->setMode(ACCESS_WRITE);
                        $t_occur->set('type_id', $pn_occurrence_type_id);
                        //opgelet !!! vergeet leading zeros niet
                        $t_occur->set('idno', $idno_occur);
                        $t_occur->set('status', $status); //workflow_statuses
                        $t_occur->set('access', 1);       //1=accessible to public
                        $t_occur->set('locale_id', $pn_locale_id);
                        //----------
                       $t_occur->insert();
                        //----------
                        if ($t_occur->numErrors()) {
                            $log->logInfo("ERROR INSERTING OCCURRENCE ".$value." - ".join('; ', $t_occur->getErrors()));
                            continue;
                        } else {
                            $log->logInfo('insert occurrence '.$value.' gelukt');

                            $teller_occur = $teller_occur + 1 ;
                            //----------
                            $t_occur->addLabel(array(
                                    'name'      => trim($value)
                            ),$pn_locale_id, null, true );

                            if ($t_occur->numErrors()) {
                                    $log->logInfo("ERROR ADD LABEL TO ".$value." - ".join('; ', $t_occur->getErrors()));
                                    continue;
                            } else {
                                    $log->logInfo('addlabel '.$value.' gelukt ');
                            }
                        }
                        $va_right_keys[0] = $t_occur->getPrimaryKey();
                    }

                    if ((count($va_right_keys)) > 1 ) {
                        $log->logInfo("WARNING: PROBLEM: found more than one occurrence -> taking the first one !!!!!");
                    }
                    if ((count($va_right_keys)) >= 1 ) {
                        $vn_right_id = $va_right_keys[0];

                        $t_object->addRelationship('ca_occurrences', $vn_right_id, $vn_objects_x_occurrences);

                        if ($t_object->numErrors()) {
                            $log->logInfo("ERROR LINKING object and occurrence: " . join(';', $t_object->getErrors()));
                            continue;
                        } else {
                            $log->logInfo("relatie tot reference ".$vs_right_string." gelukt");
                        }
                    }
                }
            }
       }

    }
    
    $reader->next();
}

$reader->close();

$log->logInfo("EINDE VERWERKING");