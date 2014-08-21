<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 18/07/14
 * Time: 10:08
 */
require_once('ca_objects_bis.php');
require_once("../classesRest/GuzzleRest.php");
$AUTH_CURRENT_USER_ID = 'administrator';

$t_func = new MyFunctions_new();
$locale_id = ("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$t_object = new ca_objects_bis();
$t_guzzle = new GuzzleRest();

$teller = 1;
$wel = 0;
$niet = 0;
$materiaal_velden = array('materiaalDeel', 'materiaal', 'materiaalNotes');

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

    $log->logInfo("==========".($teller)."========");

    $mat_deel = 'materiaalDeel';
    $mat = 'materiaal';
    $mat_notes = 'materiaalNotes';

    if ( (isset($resultarray[$mat_deel])) ) {

        $log->logInfo('de originele data', $resultarray);

        $update = array('remove_attributes'  => array('materiaalInfo'));

        #opzoeken objectnr
        //teller wordt als idno gebruikt, maar met leading zeros tot 8 posities
        $idno = sprintf('%08d', $teller);

        $log->logInfo('idno ',($idno));
        $log->logInfo("adlibObjectNummer", $resultarray['adlibObjectNummer']);

        $va_left_keys = $t_object->getObjectIDsByIdno($idno);

        if ((sizeof($va_left_keys)) > 1 ) {
            $log->logError('WARNING: PROBLEMS with object ' . $idno . ': meerdere records gevonden. We nemen het eerste !!!!!');
        }

        $vn_left_id = $va_left_keys[0];

        $log->logInfo("object_id: ",$vn_left_id);

        $t_object->load($vn_left_id);

        //$aanwezig = $t_object->getAttributeForIDs('materiaalInfo', array($vn_left_id));
        $aanwezig = $t_object->getAttributeDisplayValues('materiaalInfo', $vn_left_id);
        $log->logInfo('aanwezige informtie', $aanwezig);

        $materiaal_aantal = $t_func->Herhalen($resultarray, $materiaal_velden);

        if ($materiaal_aantal > 0) {

            $materiaal = $t_func->makeArray2($resultarray, $materiaal_aantal, $materiaal_velden);
            $aantal = $materiaal_aantal - 1;
            $i = 0;

            for ($i=0; $i <= ($aantal) ; $i++) {

                $materiaal[$mat_deel][$i] = $materiaal[$mat_deel][$i];

                if ( (isset($materiaal[$mat_deel][$i])) && (!empty($materiaal[$mat_deel][$i])) ) {
                    if (strtoupper(substr($materiaal[$mat_deel][$i],0,6)) == 'GEHEEL') {
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                        $materiaal[$mat_deel][$i] = trim(substr($materiaal[$mat_deel][$i], 8));
                    }else{
                        $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
                    }
                }else{
                    $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
                }

                if ($materiaal[$mat_deel][$i] === '') { $materiaal[$mat_deel][$i] = null; }
                if ($materiaal[$mat][$i] === '') { $materiaal[$mat][$i] = null; }
                if ($materiaal[$mat_notes][$i] === '') { $materiaal[$mat_notes][$i] = null; }

                //$update['73'][] =
                $update['attributes']['materiaalInfo'][] =
                    array(
                        'locale_id'                 =>	$locale_id,
                        'materiaalDeel'             =>	$materiaalDeel,
                        'materiaalNaamOnderdeel'    =>	$materiaal[$mat_deel][$i],
                        'materiaal'                 =>	$materiaal[$mat][$i],
                        'materiaalNotes'             =>	$materiaal[$mat_notes][$i]);

                unset($materiaalDeel);

            }
            unset($materiaal);
            unset($aantal);
            unset($i);
        }

        if ($materiaal_aantal === sizeof($aanwezig['73'])) {

                $log->logInfo('virale aanpassing WEL mogelijk');
                $wel++;

                $data = $t_guzzle->updateObject($update, $vn_left_id, 'ca_objects');

                if (isset($data['ok']) && ($data['ok'] != 1)) {

                   echo "ERROR ERROR \n";
                   $log->logError("ERROR ERROR : Er is iets misgelopen!!!!!", $data);
                }

        } else {

                $log->logError('virale aanpassing NIET mogelijk');
                $niet++;

        }

        $log->logInfo('het eindresultaat', $update);

        unset($update);

    }

    $teller++;

    $reader->next();
}

$reader->close();
$log->logInfo('EINDE VERWERKING');
$log->logInfo('==================================');
$log->logInfo('viraal aan te passen', $wel);
$log->logInfo('viraal NIET aan te passen', $niet);
$log->logInfo('==================================');
        /*
                *
         *
         */
