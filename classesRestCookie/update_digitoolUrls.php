<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 25/04/14
 * Time: 11:16
 */

define("__PROG__", "digitoolUrl_prod");

include('header.php');

$log = new Klogger(__LOG_DIR__,KLogger::DEBUG);

require_once('GuzzleRestCookie.php');

$t_object = new ca_objects();
$t_guzzle = new GuzzleRestCookie(__INI_FILE__);

$query = "ca_objects.digitoolUrl:'resolver'";
$result = $t_guzzle->findObject($query, 'ca_objects');

$teller = 0;

foreach($result['results'] as $object) {

    $teller++;
    $object_id = $object['object_id'];
    $idno = $object['idno'];

    echo $teller . " | ". $object_id . " | ". $idno . "\n";

    $log->logInfo($teller . " | ". $object_id . " | ". $idno);

    $update_new = array();
    $temp = array();

    $data_new = $t_guzzle->getFullObject($object_id, 'ca_objects');

    if (isset($data_new['attributes']['digitoolUrl'])) {

        $digitoolUrl = $data_new['attributes']['digitoolUrl'];

        #$log->logInfo('het benodigde deel ?', $digitoolUrl);

        foreach($digitoolUrl as $key => $value) {

            if(strpos($value['digitoolUrl'], '_') > 0) {

                $value['digitoolUrl'] = substr($value['digitoolUrl'], 0, strpos($value['digitoolUrl'], '_'));

            }

            $temp['digitoolUrl'][] = $value;

        }

        $update_new = array(
            "remove_attributes" => array("digitoolUrl"),
            "attributes" => ($temp)
        );



        $data2 = $t_guzzle->updateObject($update_new, $object_id, 'ca_objects');

        $log->logInfo('het eindresultaat',$data2);

        if (isset($data2['ok']) && ($data2['ok'] != 1)) {

            echo "ERROR ERROR \n";
            $log->logError("ERROR ERROR : Er is iets misgelopen!!!!!", $data);

        }

        if ($teller >= 1000) {
           exit;
        }

    }

}

$log->logInfo('EINDE');