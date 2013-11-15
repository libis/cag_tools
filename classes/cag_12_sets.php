<?php
/* Doel van dit programma:
 *
 */
define("__PROG__","sets");

include('header.php');

require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__MY_DIR__."/cag_tools/classes/ca_objects_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Objects.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$vn_set_type_id	= $t_list->getItemIDFromList('set_types', 'public_presentation');
//==============================================================================initialisaties
$teller = 0;
//==============================================================================
// FASE I : aanmaken van de sets
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_sets_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/objecten.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {

    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $teller = $teller + 1;
    $log->logInfo( '=========='.$teller.'========');

    if (!empty($resultarray)) {
        $log->logInfo('de originele data', $resultarray);
        //print_r($resultarray);
        $aantal_set = $t_func->Herhalen($resultarray, array('set'));
        $res_set = $t_func->makeArray2($resultarray, $aantal_set, array('set'));

        foreach($res_set['set'] as $value) {
            if (!empty($value)) {
                $value_temp = str_replace(" ", "_", $value);
                $value_new = str_replace(array(',', '.', '(', ')', '!', '?', ':'), '', $value_temp);
                $set[trim($value)] = $value_new;
            }
            unset($value_temp);
            unset($value_new);
        }
        unset($aantal_set);
        unset($res_set);
    }

    $reader->next();
}

$reader->close();

$log->logInfo("EINDE AANMAAK SET-LIJST");

//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
ksort($set);
$log->logInfo("+++++++++++++++++++++SET LIJST+++++++++++++++++++++++", $set);
$log->logInfo('=====================================================');

foreach($set as $key => $value) {
    $t_set = new ca_sets();
    if (!$t_set->load(array('set_code' => $value))) {
	$t_set->setMode(ACCESS_WRITE);
	$t_set->set('set_code', $value);
	$t_set->set('name_singular', $key);
	$t_set->set('name_plural', $key);
	$t_set->set('parent_id', null);
	$t_set->set('table_num', 57);
	$t_set->set('type_id', $vn_set_type_id );
	$t_set->set('status', 2);
	$t_set->set('access', 1);
	$t_set->set('user_id', 1);

        try {

            $t_set->insert();

            if ($t_set->numErrors()) {
                $log->logInfo("ERROR: couldn't create ca_sets row for ".$key.": ".join('; ', $t_set->getErrors()));
                throw new Exception ("ERROR: couldn't create ca_sets row for ".$key.": ".join('; ', $t_set->getErrors()));
            }

            $log->logInfo("aanmaken set ".$key." gelukt");

            try {

                $t_set->addLabel(array('name' => $key), $pn_locale_id, null, true);

                if ($t_set->numErrors()) {
                    $log->logInfo("ERROR: couldn't addlabel to ca_sets row for ".$key.": ".join('; ', $t_set->getErrors()));
                    throw new Exception("ERROR: couldn't addlabel to ca_sets row for ".$key.": ".join('; ', $t_set->getErrors()));
                }
                $log->logInfo("addlabel set ".$key." gelukt");
            } catch (Exception $e) {
                echo ($e->getMessage());
            }
        } catch (Exception $e) {
            echo ($e->getMessage());
        }
    }
}
unset($set);

$log->logInfo("EINDE AANMAAK SETS");

//==============================================================================
// FASE I : de records toewijzen aan de sets
//==============================================================================inlezen bestanden
$teller2 = 0;
$reader2 = new XMLReader();
$reader2->open(__MY_DIR__."/cag_tools/data/objecten.xml");

while ($reader2->read() && $reader2->name !== 'record');
//==============================================================================begin van de loop
while ($reader2->name === 'record' ) {

    $xmlarray = $t_func->ReadXMLnode($reader2);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray, $mappingarray);

    $teller2 = $teller2 + 1;
    $log->logInfo( '=========='.$teller2.'========');

    if (!empty($resultarray)) {
        $log->logInfo('de originele data', $resultarray);
        $idno = sprintf('%08d', $teller2);

        $t_object = new ca_objects_bis();
        $t_object->setMode(ACCESS_WRITE);
        $va_left_keys = $t_object->getObjectIDsByIdno($idno);

        if ((sizeof($va_left_keys)) > 1 ) {
            $log->logError("ERROR: PROBLEM: found more than one object -> take the first one !!!!!");
        }
        $vn_left_id = $va_left_keys[0];
        //$t_object->load($vn_left_id);
        //$t_object->getPrimaryKey();
        //$t_object->set('object_id', $vn_left_id);

        $aantal_set = $t_func->Herhalen($resultarray, array('set'));
        $res_set = $t_func->makeArray2($resultarray, $aantal_set, array('set'));
        //print_r ($res_set);

        foreach($res_set['set'] as $value) {
            if (strlen(trim($value)) > 0 ) {
                $value_temp = str_replace(" ", "_", $value);
                $value_new = str_replace(array(',', '.', '(', ')', '!', '?', ':'), '', $value_temp);

                $t_set2 = new ca_sets();
                $t_set2->load(array('set_code' => $value_new));
                $t_set2->setMode(ACCESS_WRITE);
                $t_set2->getPrimaryKey();
                $t_set2->addItem($vn_left_id);

                $t_set2->update();

                if ($t_set2->numErrors()) {
                        $log->logError("ERROR UPDATING SET: ".$value_new." : ".join('; ', $t_set2->getErrors()));

                } else {
                        print "update set succesvol\n";
                }
            }
            unset($value_temp);
            unset($value_new);
        }
        unset($aantal_set);
        unset($res_set);
    }

    $reader2->next();
}
$log->logInfo('EINDE VERWERKING');
$reader2->close();