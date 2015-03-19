<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 25/04/14
 * Time: 11:16
 */

define("__PROG__", "export_voeding_local");

include('header_voeding.php');

$log = new Klogger(__LOG_DIR__,KLogger::DEBUG);

require_once('../classesRestCookie/GuzzleRestCookie.php');

$t_guzzle = new GuzzleRestCookie(__INI_FILE__);

$collections_array = array();
$collections_related = array();

$json_collections = "";
$json_related_collections = "";

$query = "ca_collections:'*'";
$result = $t_guzzle->findObject($query, 'ca_collections');

#$log->logInfo('de objecten', $result);
$teller = 0;

/*
$bundles = array(
    "bundles"       => array(
        "ca_collections.distributieInfo.distributieAmbachten"	=>	array("convertCodesToDisplayText" => true, "returnAsArray" => true),
        "ca_collections.promotiemateriaalInfo.promotiemateriaal"	=>	array("convertCodesToDisplayText" => true, "returnAsArray" => true)
         )
    );
$body = json_encode($bundles);
 *
 */

foreach($result['results'] as $collection) {

    $collection_id = $collection['collection_id'];
    $idno = $collection['idno'];

    echo $teller . " | ". $collection_id . " | ". $idno . "\n";

    $log->logInfo($teller . " | ". $collection_id . " | ". $idno );

    $data = $t_guzzle->getFullObject($collection_id, 'ca_collections');

    $collections_array[$teller]['intrinsic_fields'] = $data['intrinsic_fields'];
    $collections_array[$teller]['preferred_labels'] = $data['preferred_labels'];
    $collections_array[$teller]['attributes'] = $data['attributes'];

    $collections_related[$teller]['preferred_labels'] = $data['preferred_labels'];
    $collections_related[$teller]['related'] = $data['related'];

    /* met curl
    $url2 = "http://userid:password@localhost/voeding/service.php/item/ca_collections/id/".$collection_id."?pretty=1&format=edit";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_URL, $url2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $result2 = curl_exec($ch);
    $tt = json_decode($result2, TRUE);
     *
     */

    $teller++;
}

$log->logInfo('EINDE DEEL I');

$json_collections = json_encode($collections_array);
$json_file = "../data/json_voeding.txt";
file_put_contents($json_file, $json_collections);

$json_related_collections = json_encode($collections_related);
$json_rel_file = "../data/json_related_voeding.txt";
file_put_contents($json_rel_file, $json_related_collections);

#############################################################

$teller = 0;
require_once(__CA_LIB_DIR__ . '/core/Parsers/DelimitedDataParser.php');
$o_tab_parser = new DelimitedDataParser("\t");

if (!$o_tab_parser->parse("../data/tabel.txt")) {
    die("Couldn't parse tabel.txt\n");
}

while ($o_tab_parser->nextRow()) {
// Get columns from tab file and put them into named variables - makes code easier to read
    $old_term           =   $o_tab_parser->getRowValue(1); #
    $new_term           =   $o_tab_parser->getRowValue(2); #

    ###############################################################

    $tabel[$teller]['old'] = $old_term;
    $tabel[$teller]['new'] = $new_term;

    $teller++;
}

$json_collections_temp = str_replace('"','&quot', $json_collections);

for($i = 0; $i <= ($teller - 1); $i++) {

    $json_collections_new = str_replace($tabel[$i]['old'], $tabel[$i]['new'], $json_collections_temp);

    $json_collections_temp = $json_collections_new;
}

$json_collections = str_replace('&quot','"', $json_collections_temp);

$json_file_plus = "../data/json_voeding_plus.txt";
file_put_contents($json_file_plus, $json_collections);

$data_plus = json_decode($json_collections, TRUE);

echo "DONE";

