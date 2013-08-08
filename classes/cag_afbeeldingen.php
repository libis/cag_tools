<?php
/* Dit script wordt gebruikt om afbeeldingen van Digitool in te laden in CAG
	Opgelet de pid waarde moet hetzelfde zijn zoals het attribuut digitoolUrl verwacht
	Thumbnail en view in Ã©Ã©n lijn
*/
error_reporting(-1);
set_time_limit(0);
$type = "SERVER";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}

define("__PROG__","afbeeldingen");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__MY_DIR_2____.'/cag_tools/classes/ca_objects_bis.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

//inlezen csv-bestand met object_ids en pids
$afbeeldingen = array();
$o_tab_parser = new DelimitedDataParser("\t");
// Read csv; line by line till end of file.
if (!$o_tab_parser->parse('/www/libis/vol03/lias_html/cag_tools/data/cagDtlOut.csv')) {
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

	$afbeeldingen[$pid] = $adlib;
        $vn_c++;
}

	// label en idno moeten nog gematcht worden
	// kunstvoorwerp_idno loop vervangen door opzoeken van label
$t_object = new ca_objects_bis();
$t_object->setMode(ACCESS_WRITE);

foreach($afbeeldingen as $pid => $adlib) {
        $va_object_ids = $t_object->getObjectIDsByElementID($adlib, 'adlibObjectNummer');

        if (!empty($va_object_ids)) {

            if (sizeof($va_object_ids) > 1 ){

                $log->logInfo("WARNING: meerdere objecten voor adlibObjectNummer ".$adlib." gevonden");
                $log->logInfo("nemen het eerste object.");
            }

            $vn_object_id = $va_object_ids[0];
            $t_object->load($vn_object_id);
            $t_object->getPrimaryKey();

            $url =
                $pid."_,_http://resolver.lias.be/get_pid?stream&usagetype=THUMBNAIL&pid=".
                $pid."_,_http://resolver.lias.be/get_pid?view&usagetype=VIEW_MAIN,VIEW&pid=".
                $pid;

                $t_object->addAttribute(array(
                        'locale_id'     =>	$pn_locale_id,
                        'digitoolUrl'   =>	$url
                ), 'digitoolUrl');

            $t_object->update();

            if ($t_object->numErrors()) {
                    $log->logError("ERROR INSERTING Pid ".$pid." voor adlibObjectNummer ".$adlib.": ".join('; ', $t_object->getErrors()));
                    continue;
            } else {
                    $log->logInfo("Toevoegen van afbeelding ".$pid." aan object ".$vn_object_id." - ". $adlib . " gelukt ");
            }
            unset($url);
            unset($va_object_ids);
            unset($vn_object_id);

        } else {

            $log->logError("ERROR: object met adlibObjectNummer ".$adlib." voor Pid ".$pid." niet gevonden ");
        }

}

$log->logInfo("ENDED IMPORTING cagDtlOut.csv" );