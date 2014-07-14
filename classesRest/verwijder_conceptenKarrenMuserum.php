<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 17/06/14
 * Time: 9:55
 */

define("__PROG__","concepten_karrenmuseum");

include('header_loc.php');
$log = new KLogger(__LOG_DIR__, KLogger::DEBUG);
$AUTH_CURRENT_USER_ID = 'administrator';

require_once(__CA_LIB_DIR__ . '/core/Parsers/DelimitedDataParser.php');
require_once("GuzzleRest.php");
require_once("myRestFunctions.php");

$t_locale = new ca_locales();
$locale_id = 'nl_NL';

$t_list = new ca_lists();

$t_guzzle = new GuzzleRest();
$t_myfunc = new myRestFunctions();

for($vn_c = 1100; $vn_c <= 1215; $vn_c++) {

    echo $vn_c." | ";

    $idno = 'concept'.$vn_c;

    echo $idno." | ";

    $query = "ca_objects.idno:\"".$idno."\"";
    //$query = 'ca_objects.objectnaamAlternatief:halsjuk (synoniem)';

    $data = $t_guzzle->findObject($query, 'ca_objects');

    if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
        if (sizeof($data['results']) > 1) {
            echo "Meer dan 1 kandidaat gevonden \n";
            $log->logError('Meer dan één kandidaat gevonden', $data);
            //exit;
        } else {
            $objectId = $data['results'][0]['object_id'];
            echo $objectId."\n";
        }
    } else {
        echo "projectnr niet gevonden - object bestaat (nog) niet\n";
        $log->logError('projectnr niet gevonden - object bestaat nog niet');

    }

    $data2 = $t_guzzle->deleteObject($objectId, 'ca_objects');

    $log->logInfo('het resultaat', $data2);

}
echo " THE END...";