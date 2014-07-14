<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 12/06/14
 * Time: 14:07
 */
define("__PROG__","entities_karrenmuseum");

include('header_vagrant.php');
//$log = setLogging();
$AUTH_CURRENT_USER_ID = 'administrator';

require_once("../classes/ca_entities_bis.php");
require_once(__CA_LIB_DIR__ . '/core/Parsers/DelimitedDataParser.php');
require_once("GuzzleRest.php");

$t_locale = new ca_locales();
$locale_id = 'nl_NL';

$t_entity = new ca_entities_bis();
$t_list = new ca_lists();

$t_guzzle = new GuzzleRest();

$va_bewaarplaats = array();
$va_vervaardiger = array();
$va_verwerving = array();

// want to parse comma delimited data? Pass a comma here instead of a tab.
$o_tab_parser = new DelimitedDataParser("\t");

print "IMPORTING enities uit objecten karrenmuseum \n";

if (!$o_tab_parser->parse(__MY_DATA__ . "karrenmuseum/Objecten.csv")) {
    die("Couldn't parse Objecten karrenmuseum data\n");
}

$vn_c = 1;

$o_tab_parser->nextRow(); // skip first row
$o_tab_parser->nextRow(); // skip second row
$o_tab_parser->nextRow(); // skip third row

while ($o_tab_parser->nextRow()) {
// Get columns from tab file and put them into named variables - makes code easier to read
    $bewaarplaats       =   $o_tab_parser->getRowvalue(6); #

    $vervaard1          =   $o_tab_parser->getRowvalue(8); #

    $verwerving2        =   $o_tab_parser->getRowvalue(10);#
    $postcode           =   $o_tab_parser->getRowvalue(11); //?
    $plaats             =   $o_tab_parser->getRowvalue(12); //?
    $provincie          =   $o_tab_parser->getRowvalue(13); //?
    $land               =   $o_tab_parser->getRowvalue(14); //?

    ###############################################################
    $va_bewaarplaats[$bewaarplaats] = '';
    $va_vervaardiger[$vervaard1] = '';
    $va_verwerving[$verwerving2] = $postcode."/".$plaats."/".$provincie."/".$land;

}

//$id = 3010;
//$data = $t_guzzle->getObject($id, 'ca_entities');


echo "\n\n";
echo 'Aantal Bewaarplaats: ' . sizeof($va_bewaarplaats). " \n\n ";

foreach($va_bewaarplaats as $key => $value) {

    if (trim($key) !== '') {

        $key_new = str_replace(array('-', 'vzw'), array('', ''), $key);

        $query = "ca_entities.preferred_labels:'".trim($key_new)."'";
        $data = $t_guzzle->findObject($query, 'ca_entities');

        if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
            if (sizeof($data['results']) > 1) {
                echo "Meer dan 1 kandidaat gevonden voor ". $key . "\n";
                $id = juisteTerm($data, $key_new);
                echo "\t\t".$id ." | ". $value . "\n";
                $data2 = $t_guzzle->getObject($id, 'ca_entities');
            } else {
                $id = $data['results'][0]['entity_id'];
                echo 'Gevonden: '. $key . " | " . $id . " \n ";
            }
        } else {

            echo "projectnr niet gevonden - entity " . $key . " bestaat (nog) niet\n";

        }
    }
}

/*
echo "\n\n";
echo 'Aantal Vervaardiger: ' . sizeof($va_vervaardiger). " \n\n ";

foreach($va_vervaardiger as $key => $value) {

    if (trim($key) !== '') {
        $query = $key;
        $data = $t_guzzle->findObject($query, 'ca_entities');

        if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
            if (sizeof($data['results']) > 1) {
                echo "Meer dan 1 kandidaat gevonden voor ". $key . "\n";
            } else {
                $id = $data['results'][0]['entity_id'];
                echo 'Gevonden: '. $key . " | " . $id . " \n ";
            }
        } else {

            echo "projectnr niet gevonden - entitie " . $key . " bestaat (nog) niet\n";

        }
    }
}

echo "\n\n";
echo 'Aantal Verwerving: ' . sizeof($va_verwerving). " \n\n ";

foreach($va_verwerving as $key => $value) {

    if (trim($key) !== '') {

        $key_new = str_replace(array(' - ', 'vzw'), array('  ', ' '), $key);

        $query = 'ca_entities.preferred_labels.displayname:'.trim($key_new);
        //$query = 'ca_objects.objectnaamAlternatief:halsjuk (synoniem)';
        $data = $t_guzzle->findObject($query, 'ca_entities');

        if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
            if (sizeof($data['results']) > 1) {
                echo "Meer dan 1 kandidaat gevonden voor ". $key . "\n";
                $id = juisteTerm($data, $key_new);
                echo "\t\t".$id ." | ". $value . "\n";
                $data2 = $t_guzzle->getObject($id, 'ca_entities');

            } else {
                $id = $data['results'][0]['entity_id'];
                echo 'Gevonden: '. $key . " | " . $id . " \n ";
                echo "\t\t".$value . "\n";
                $data2 = $t_guzzle->getObject($id, 'ca_entities');

            }
        } else {

            echo "projectnr niet gevonden - entity " . $key . " bestaat (nog) niet\n";

        }
        echo "\t\t".$value . "\n";
    }
}
*
 *
 */

function hoogsteId($data) {

    $id = $data['results'][0]['entity_id'];

    foreach($data['results'] as $value) {
        if ($value['entity_id'] > $id) {
            $id = $value['entity_id'];
        }
    }

    return $id;

}

function juisteTerm($data, $key) {

    $term = $data['results'][0]['display_label'];

    foreach($data['results'] as $value) {
        if ($value['display_label'] === $key) {
            $id = $value['entity_id'];
            return $id;
        }
    }

    return '';


}