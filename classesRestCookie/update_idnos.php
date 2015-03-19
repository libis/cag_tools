<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 25/04/14
 * Time: 11:16
 */

define("__PROG__", "st_idnos_prod");

include('header.php');

$log = new Klogger(__LOG_DIR__,KLogger::DEBUG);
$AUTH_CURRENT_USER_ID = 'administrator';

require_once('GuzzleRestCookie.php');

$t_object = new ca_objects();
$t_guzzle = new GuzzleRestCookie(__INI_FILE__);

$query = "ca_objects.idno:'st*'";
$result = $t_guzzle->findObject($query, 'ca_objects');

#$log->logInfo('de objecten', $result);
$teller = 0;

foreach($result['results'] as $object) {

    $teller++;
    $object_id = $object['object_id'];
    $idno = $object['idno'];

    $new_idno = $t_object->setIdnoWithTemplate();

    echo $teller . " | ". $object_id . " | ". $idno . " | ". $new_idno . "\n";

    $log->logInfo($teller . " | ". $object_id . " | ". $idno . " | ". $new_idno);

    $update['intrinsic_fields'] =
        array(
            'idno'  => $new_idno
        );

    $data2 = $t_guzzle->updateObject($update, $object_id, 'ca_objects');

    if (isset($data2['ok']) && ($data2['ok'] != 1)) {

        echo "ERROR ERROR \n";
        $log->logError("ERROR ERROR : Er is iets misgelopen!!!!!", $data);
        $log->logInfo('het eindresultaat',$data2);

    }

}

$log->logInfo('EINDE');