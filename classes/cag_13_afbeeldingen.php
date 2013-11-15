<?php
/* Dit script wordt gebruikt om afbeeldingen van Digitool in te laden in CAG
	Opgelet de pid waarde moet hetzelfde zijn zoals het attribuut digitoolUrl verwacht
	Thumbnail en view in Ã©Ã©n lijn
*/
define("__PROG__","afbeeldingen");

include('header.php');

require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');
require_once(__MY_DIR__."/cag_tools/classes/ca_objects_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Objects.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$my_objects = new Objects();

//inlezen csv-bestand met object_ids en pids
$afbeeldingen = array();
$o_tab_parser = new DelimitedDataParser("\t");
// Read csv; line by line till end of file.
if (!$o_tab_parser->parse(__MY_DIR__.'/cag_tools/data/cagDtlOut.csv')) {
	die("Couldn't parse cagDtlOut.csv data\n");
}

$log->logInfo("READING cagDtlOut.csv...");

$vn_c = 1;
//csv-bestand bevat geen hoofdingen, dus niet nodig
//$o_tab_parser->nextRow(); // skip first row
//-------------------------
// waarden inlezen
//-------------------------
while($o_tab_parser->nextRow() && $vn_c) {
	// Get columns from tab file and put them into named variables - makes code easier to read
	$adlib      =	$o_tab_parser->getRowValue(1); //id (niet gebruiken)
	$pid        =	$o_tab_parser->getRowValue(2);

	$afbeeldingen[] = array('pid' => $pid, 'adlib' => $adlib);
        $vn_c++;
}
	// label en idno moeten nog gematcht worden
	// kunstvoorwerp_idno loop vervangen door opzoeken van label
$t_object = new ca_objects_bis();
$t_object->setMode(ACCESS_WRITE);

foreach($afbeeldingen as $beeld) {
    foreach($beeld as $key => $value) {
        if ( ($key) === 'pid') {$pid = $value;}
        if ( ($key) === 'adlib') {$adlib = $value;}

        if ( (isset($pid)) && (isset($value)) ) {
            $va_object_ids = $t_object->getObjectIDsByElementID($adlib, 'adlibObjectNummer');

            if (!empty($va_object_ids)) {

                if (sizeof($va_object_ids) > 1 ){

                    $log->logInfo("WARNING: meerdere objecten voor adlibObjectNummer ".$adlib." gevonden");
                    $log->logInfo("nemen het eerste object.");
                }

                $vn_object_id = $va_object_ids[0];

                $url =
                    $pid."_,_http://resolver.lias.be/get_pid?stream&usagetype=THUMBNAIL&pid=".
                    $pid."_,_http://resolver.lias.be/get_pid?view&usagetype=VIEW_MAIN,VIEW&pid=".
                    $pid;

                $container = 'digitoolUrl';
                $data = array('locale_id'   =>	$pn_locale_id,
                            'digitoolUrl'   =>	$url);

                $my_objects->addSomeObjectAttribute($vn_object_id, $container, $data);
            }
            unset($url);
            unset($data);
            unset($va_object_ids);
            unset($vn_object_id);
            unset($pid);
            unset($adlib);
        }
    }
}

$log->logInfo("ENDED IMPORTING cagDtlOut.csv" );