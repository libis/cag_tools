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

// want to parse comma delimited data? Pass a comma here instead of a tab.
$o_tab_parser = new DelimitedDataParser("\t");

print "IMPORTING objecten karrenmuseum \n";

if (!$o_tab_parser->parse(__MY_DATA__ . "karrenmuseum/Concepten.csv")) {
    die("Couldn't parse Objecten karrenmuseum data\n");
}

$vn_c = 1000;

$o_tab_parser->nextRow(); // skip first row
$o_tab_parser->nextRow(); // skip second row
$o_tab_parser->nextRow(); // skip third row

while ($o_tab_parser->nextRow()) {
// Get columns from tab file and put them into named variables - makes code easier to read
    $term               =   $o_tab_parser->getRowValue(1); #
    $projectnr          =   $o_tab_parser->getRowValue(2); #
    $objecten           =   $o_tab_parser->getRowvalue(3); #
    $beschrijving_kort  =   $o_tab_parser->getRowvalue(4); #
    $beschrijving_lang  =   $o_tab_parser->getRowvalue(5); #
    $geografisch        =   $o_tab_parser->getRowvalue(6); #
    $afbeelding         =   $o_tab_parser->getRowvalue(7); #
    $opmerking          =   $o_tab_parser->getRowvalue(8); #
    $alternatief        =   $o_tab_parser->getRowvalue(9); #
    $aantal             =   $o_tab_parser->getRowvalue(10);#

    ###############################################################

    $update = array();

    echo $vn_c." | ";
    echo $locale_id." \n\r ";

    echo $term." | ";

    $type_id = $t_list->getItemIDFromList('object_types', 'cagConceptVoorwerp_type');

    if (isset($term) && (substr(strtoupper($term), 0, 10) !== 'HOOFDGROEP')) {

        $query = "ca_objects.preferred_labels:\"". trim($term) ."\" and ca_objects.type_id:".$type_id."";

        $data = $t_guzzle->findObject($query, 'ca_objects');

        if (isset($data['ok']) && ($data['ok'] == 1) && !is_array($data['results'])) {

            echo "Deze term bestaat nog niet -> intrinsic-fields en preferred-label ook aanmaken";

            # intrinsic values

            $idno = 'concept'.$vn_c;
            $statusnew = $t_list->getItemIDFromList('workflow_statuses', 'i1');
            //'klaar maar niet publiceren heeft idno = i1
            echo $idno." | ".$statusnew."\n";

            if (!isset($statusnew)) { die ("fout in workflow_statuses");}



            $update['intrinsic_fields'] =
                array(
                    'idno'    => $idno,
                    'type_id' => $type_id,
                    'status'  => $statusnew
                );

            # preferred_label part

            $update['preferred_labels'][] =
                array(
                    'locale'        => $locale_id,
                    'name'          => trim($term)
                );

            $id = '';

        } elseif (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {

            echo "Deze term bestaat reeds-> Doe enkel update (add) van de attributen";

            if (sizeof($data['results']) > 1) {
                echo "Meer dan 1 kandidaat gevonden voor ". $term . "\n";
                $id = $t_myfunc->juisteTerm($data, $term, 'object_id');
                //$data2 = $this->guzzle->getObject($id, $id_type);

            } else {
                echo 'Gevonden: '. $term . " | " . $id . " \n ";
                $id = $data['results'][0]['object_id'];
                //$data2 = $this->guzzle->getObject($id, $id_type);
            }

        }

        # nonpreferred_labels - hierdoor kunnen dubbels ontstaan !!!!!!!

        echo $alternatief. " | ";
        if (isset($alternatief) && $alternatief !== '') {

            $update['nonpreferred_labels'][] =
                array(
                    'locale'        => $locale_id,
                    'name'          => $alternatief
                );
        }

        # attributes part

        echo $beschrijving_kort." \n\r ";
        if (isset($beschrijving_kort) && $beschrijving_kort !== '') {
            $update['attributes']['conceptDefinitie'][] =
                array(
                    'locale'                 =>  $locale_id,
                    'conceptDefinitie'       =>  trim($beschrijving_kort)
                );
        }

        echo $beschrijving_lang."\n\r";
        echo $geografisch."\n\r";
        echo $opmerking."\n\r";

        $temp = array();
        if (isset($beschrijving_lang) && $beschrijving_lang !== ''){
            $temp[] = trim($beschrijving_lang);
        }
        if (isset($geografisch) && $geografisch !== '') {
            $temp[] = trim($geografisch);
        }
        if (isset($opmerking) && $opmerking !== '') {
            $temp[] = trim($opmerking);
        }

        if (sizeof($temp) >=1) {

            $alg_beschr = implode("\n", $temp);

            $update['attributes']['algemeneBeschrijving'][] =
                array(
                    'locale'                 =>  $locale_id,
                    'algemeneBeschrijving'   =>  trim($alg_beschr)
                );

            unset($temp);
            unset($alg_beschr);
        }

        echo $aantal." \n\r ";
        if (isset($aantal) && $aantal !== '') {
            $update['attributes']['bewaarde_objecten'][] =
                array(
                    'locale'                  =>  $locale_id,
                    'bewaarde_objecten'       =>  $aantal
                );
        }


    # related part
        echo $objecten." | ";
        if (isset($objecten) && $objecten !== '') {

            $va_objects = explode("; ", $objecten);

            foreach($va_objects as $value) {

                $t_myfunc->createRelationship('ca_objects', trim($value), 'conceptRelatie', $update);

            }

            unset($va_objects);
        }


        print_r($update);

        $log->logInfo('de volledige json array', $update);
        echo "\n\r ";

        if ($id === '') {

            echo 'create';
            $data2 = $t_guzzle->createObject($update, 'ca_objects');
            $vn_c++;

        } else {

            echo "update \n ";
            $data2 = $t_guzzle->updateObject($update, $id, 'ca_objects');

        }

        $log->logInfo('het resultaat', $data2);

    }

}
echo " THE END...";