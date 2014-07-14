<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 3/06/14
 * Time: 15:16
 */

define("__PROG__","objecten_karrenmuseum_bis");

include('header_loc.php');
$log = new KLogger(__LOG_DIR__, KLogger::DEBUG);
$AUTH_CURRENT_USER_ID = 'administrator';

//require_once("ca_entities_bis.php");
require_once(__CA_MODELS_DIR__."/ca_objects.php");
require_once(__CA_LIB_DIR__ . '/core/Parsers/DelimitedDataParser.php');
require_once("GuzzleRest.php");
require_once("myRestFunctions.php");

$t_locale = new ca_locales();
$locale_id = 'nl_NL';

$AUTH_CURRENT_USER_ID = 'administrator';
$t_object = new ca_objects();
$t_list = new ca_lists();

$t_guzzle = new GuzzleRest();
$t_myfunc = new myRestFunctions();

// want to parse comma delimited data? Pass a comma here instead of a tab.
$o_tab_parser = new DelimitedDataParser("\t");

print "IMPORTING objecten karrenmuseum \n";

if (!$o_tab_parser->parse(__MY_DATA__ . "karrenmuseum/Objecten.csv")) {
    die("Couldn't parse Objecten karrenmuseum data\n");
}

$vn_c = 1;

$o_tab_parser->nextRow(); // skip first row
$o_tab_parser->nextRow(); // skip second row
$o_tab_parser->nextRow(); // skip third row

while ($o_tab_parser->nextRow()) {
// Get columns from tab file and put them into named variables - makes code easier to read
    $projectnr          =   $o_tab_parser->getRowValue(1); #
    $inventarisnr       =   $o_tab_parser->getRowValue(2); #
    $data_pre           =   $o_tab_parser->getRowvalue(3); #
    $data_1             =   $o_tab_parser->getRowvalue(4); #
    $data_2             =   $o_tab_parser->getRowvalue(5); #
    $bewaarplaats       =   $o_tab_parser->getRowvalue(6); #
    $pre_vervaard1      =   $o_tab_parser->getRowvalue(7); #
    $vervaard1          =   $o_tab_parser->getRowvalue(8); #
    $pre_verwerv2       =   $o_tab_parser->getRowvalue(9); #
    $verwerving2        =   $o_tab_parser->getRowvalue(10);#
    $postcode           =   $o_tab_parser->getRowvalue(11); //?
    $plaats             =   $o_tab_parser->getRowvalue(12); //?
    $provincie          =   $o_tab_parser->getRowvalue(13); //?
    $land               =   $o_tab_parser->getRowvalue(14); //?
    $objectnaam         =   $o_tab_parser->getRowvalue(15);#
    $onderdeel1         =   $o_tab_parser->getRowvalue(16);#
    $soort1             =   $o_tab_parser->getRowvalue(17);#
    $onderdeel2         =   $o_tab_parser->getRowvalue(18);#
    $soort2             =   $o_tab_parser->getRowvalue(19);#
    $onderdeel3         =   $o_tab_parser->getRowvalue(20);#
    $soort3             =   $o_tab_parser->getRowvalue(21);#
    $onderdeel4         =   $o_tab_parser->getRowvalue(22);#
    $soort4             =   $o_tab_parser->getRowvalue(23);#
    $onderdeel5         =   $o_tab_parser->getRowvalue(24);#
    $soort5             =   $o_tab_parser->getRowvalue(25);#
    $status             =   $o_tab_parser->getRowvalue(26);#
    $gebruik_foto       =   $o_tab_parser->getRowvalue(27);//??

    ###############################################################

    $update = array();

    echo $vn_c." | ";
    echo $locale_id." \n\r ";

# intrinsic values
    echo $status." | ";
    $idno = $t_object->setIdnoWithTemplate();
    $statusnew = $t_list->getItemIDFromList('workflow_statuses', 'i1');
    //'klaar maar niet publiceren heeft idno = i1
    echo $statusnew."\n";

    if (!isset($statusnew)) { die ("fout in workflow_statuses");}

    $type_id = $t_list->getItemIDFromList('object_types', 'cagObject_type');
    $update['intrinsic_fields'] =
        array(
            'idno'    => $idno,
            'type_id' => $type_id,
            //'status'  => '0'
            'status' => $statusnew
        );

# preferred_label part

    echo $objectnaam." | ";
    if (isset($objectnaam) && $objectnaam !== '') {

        $update['preferred_labels'][] =
            array(
                'locale'        => $locale_id,
                'name'          => $objectnaam
            );
    }

# attributes part
    if (isset($objectnaam) && $objectnaam !== '') {
        $update['attributes']['objectnaamAlternatief'][] =
            array(
                'locale'                => $locale_id,
                'objectnaamAlternatief' => $objectnaam
            );
    }

    echo $projectnr." \n\r ";
    if ($projectnr !== '') {
        $update['attributes']['objectInventarisnrBpltsInfo'][] =
                array(
                        'locale'                        =>  $locale_id,
                        'objectInventarisnrBplts'       =>  $projectnr,
                        'objectInventarisnrBpltsType'   => 'projectnummer'
                    );
    }

    echo $inventarisnr." \n\r";
    if ($inventarisnr !== '') {
        $update['attributes']['objectInventarisnrBpltsInfo'][] =
                    array(
                        'locale'                        =>  $locale_id,
                        'objectInventarisnrBplts'       =>  $inventarisnr,
                        'objectInventarisnrBpltsType'   => 'inventarisnummer'
                    );
    }

    echo $pre_verwerv2." | ".$verwerving2." | ";
    if ($verwerving2 !== '' || $pre_verwerv2 !== ''){

        $acquisitionSource = '';
        $acquisitionDate = '';
        $acquisitionMethode = '';
        $acquisitionNote = '';

        if ($verwerving2 !== '') { $acquisitionSource = $verwerving2;}
        if ($pre_verwerv2 !== '') { $acquisitionNote = $pre_verwerv2;}
        if ($verwerving2 === '' && ($postcode !== '' || $plaats !== '' || $provincie !== '')) {
            $acquisitionNote = trim($acquisitionNote ." ". $postcode . "/" . $plaats . "/" . $provincie . "/" . $land);
        }

        if ($acquisitionSource !== '' || $acquisitionNote !== '') {
            $acquisitionMethode = $t_list->getItemIDFromListByLabel('acquisitionMethode_lijst', '-');
            $update['attributes']['acquisitionInfo'][] =
                array(
                    'locale_id'                 =>	$locale_id,
                    'acquisitionSource'         =>  $acquisitionSource,
                    'acquisitionDate'           =>	$acquisitionDate,
                    'acquisitionMethode'        =>	$acquisitionMethode,
                    'acquisitionNote'           =>	$acquisitionNote
                );
        }

        unset($acquisitionNote);
        unset($acquisitionDate);
        unset($acquisitionMethode);
        unset($acquisitionSource);
    }

//    if ($verwerving2 != '' &&  er zijn adresgegevens -> invullen bij entity -> niet nodig: gecheckt )

    echo $data_pre." | ".$data_1." | ".$data_2." | ".$pre_vervaard1." | ".$vervaard1." | ";
    if (isset($data_pre) || isset($data_1) || isset($data_2) || isset($pre_vervaard1) || isset($vervaard1)) {

        $objectVervaardiger = '';
        $vervaardigerRol = '';
        $objectVervaardigingDate = '';
        $objectVervaardigingNote = '';

        if ($data_1 !== '') {
            if ($data_2 !== '') {
                $objectVervaardigingDate = $data_1.' - '.$data_2;
            } else {
                $objectVervaardigingDate = $data_1;
            }
        }

        if ($data_pre === 'bouwjaar') {
            $objectVervaardigingNote = 'bouwjaar';
        }

        if (isset($pre_vervaard1) && !is_null($pre_vervaard1)) {
            $vervaardigerRol = $t_list->getItemIDFromList('vervaardiger_rol', trim($pre_vervaard1));
        }

        if (isset($vervaard1) && !is_null($vervaard1)) {
            $objectVervaardiger = trim($vervaard1);
        }

        if ($objectVervaardiger !== '' || $vervaardigerRol !== '' || $objectVervaardigingDate !== '' || $objectVervaardigingNote !== '') {

            if ($vervaardigerRol === '') {
                $vervaardigerRol = $t_list->getItemIDFromList('vervaardiger_rol', 'blank');
            }
            if (substr($objectVervaardigingDate, 0, 2) === 'na') {
                $objectVervaardigingDate = substr($objectVervaardigingDate, 3);
            }
            $update['attributes']['objectVervaardigingInfo'][] =
                    array(
                        'locale_id'                 =>	$locale_id,
                        'objectVervaardiger'        =>	$objectVervaardiger,
                        'vervaardigerRol'           =>	$vervaardigerRol,
                        'objectVervaardigingDate'   =>	$objectVervaardigingDate,
                        'objectVervaardigingPlace'  =>	'',
                        'objectVervaardigingNote'   =>	$objectVervaardigingNote,
                        'modelSerienummer'          =>	''
                    );
        }

        unset($objectVervaardiger);
        unset($vervaardigerRol);
        unset($objectVervaardigingDate);
        unset($objectVervaardigingNote);
    }


    $onderdeel = array($onderdeel1, $onderdeel2, $onderdeel3, $onderdeel4, $onderdeel5);
    $soort = array($soort1, $soort2, $soort3, $soort4, $soort5);

    for ($i=0; $i<=4; $i++) {

        echo $onderdeel[$i]." | ".$soort[$i]." | ";
        if (($onderdeel[$i] === 'kar') || ($onderdeel[$i] === 'wagen') || $onderdeel[$i] === 'kruiwagen') {
            $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'geheel');
        } else {
            $materiaalDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');
        }

        if ($onderdeel[$i] !== '' && $soort[$i] !== '') {

            $update['attributes']['materiaalInfo'][] =
                        array(
                            'locale_id'                 =>	$locale_id,
                            'materiaalDeel'             =>	$materiaalDeel,
                            'materiaalNaamOnderdeel'    =>	$onderdeel[$i],
                            'materiaal'                 =>	$soort[$i],
                            'materiaalNotes'            =>	''
                        );
        }
        unset($materiaalDeel);
    }

# related part
    echo $verwerving2." | ";
    if ($verwerving2 !== '') {

        $t_myfunc->createRelationship('ca_entities', $verwerving2, 'vorigeeigenaar', $update);

    }

    echo $bewaarplaats." | ";
    if ($bewaarplaats !== '') {

        $t_myfunc->createRelationship('ca_entities', $bewaarplaats, 'bewaarinstelling', $update);

    }

    echo $vervaard1." | ";
    if ($vervaard1 !== '') {

        $t_myfunc->createRelationship('ca_entities', $vervaard1, 'vervaardigerRelatie', $update);

    }

    //print_r($update);

    $log->logInfo('de volledige json array', $update);
    echo "\n\r ";

    $data = $t_guzzle->createObject($update, 'ca_objects');

    $log->logInfo('het resultaat', $data);

    if (isset($data['ok']) && ($data['ok'] != 1)) {

        echo "ERROR ERROR \n";
        $log->logError("ERROR ERROR : Er is iets misgelopen!!!!!", $data);

    }

    $vn_c++;

}
echo "the end";