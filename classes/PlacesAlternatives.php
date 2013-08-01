<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class PlacesAlternatives {

    function importGegevens($log)
    {
        $log->logInfo("Import BE.csv \n");

        $o_tab_parser = new DelimitedDataParser("\t");
        // Read csv; line by line till end of file.
        if (!$o_tab_parser->parse(__MY_DIR_2__."/cag_tools/data/BE.csv")) {
                die("Couldn't parse BE.csv data");
        }

        $alt = array();

        $vn_c1 = 1;

        $o_tab_parser->nextRow(); // skip first row (headings)
        while($o_tab_parser->nextRow()) {
                // Get columns from tab file and put them into named variables - makes code easier to read
                //$vs_land		=	$o_tab_parser->getRowValue(1);
                $vs_gemeente		=	$o_tab_parser->getRowValue(2);
                //$vs_gemeente		=	$o_tab_parser->getRowValue(3);
                $vs_alt           	=	$o_tab_parser->getRowValue(4);

                $log->logInfo("PROCESSING ", $vs_gemeente);

                $alt[] = array($vs_gemeente, $vs_alt);

                $vn_c1++;
        }
        unset($o_tab_parser);

        $log->logInfo("FINISHED CREATIE ARRAY \n");

        return $alt;
    }
}

error_reporting(-1);
set_time_limit(0);
$type = "LOCAL";

if ($type == "LOCAL") {
    define("__MY_DIR__", "c:/xampp/htdocs");
    define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
}
if ($type == "SERVER") {
    define("__MY_DIR__", "/www/libis/vol03/lias_html");
    define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
}

define("__PROG__","places_alternatives");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");
require_once(__MY_DIR_2__."/cag_tools/classes/ca_places_bis.php");
require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');
include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_place = new ca_places_bis();
$t_place->setMode(ACCESS_WRITE);

$t_test = new PlacesAlternatives();

//inlezen BE.csv in een array
$alt = $t_test->importGegevens($log);

foreach($alt as $value) {

    $zoekterm = trim($value['0'])." - %";
    $log->logInfo("=====".$alt."=====");

    $va_keys = $t_place->getPlaceIDsByNamePart($zoekterm);

    if (sizeof($va_keys)> 0) {

        if (sizeof($va_keys) > 1) {
            $log->logInfo("meerdere gemeenten gevonden, voegen alternatieve labels toe aan eerste");
        } else {
            $log->logInfo("1 gemeente gevonden, voegen alternatieve labels toe");
        }
        $place_id = $va_keys['0'];
        $t_place->load($place_id);
        $t_place->getPrimaryKey();
        $t_place->set('place_id', $place_id);

        $temp = explode(",", $value['1']);

        foreach ($temp as $alternatief) {
            $log->logInfo("verwerking alternatief: ", $alternatief);
            $zoekterm2 = trim($alternatief)."%";
            if (!$t_place->getPlaceIDsByNamePart($zoekterm2)) {
                $t_place->addLabel(
                        array('name' => ($alternatief)),
                        $pn_locale_id, null, false
                );
                try {
                    if ($t_place->numErrors()) {
                        throw new Exception("ERROR ADDING LABEL ".$alternatief . join("; ", $t_place->getErrors())."\n");
                    }
                } catch (Exception $e) {
                    $log->logError($e->getMessage());
                }
            }
            unset($zoekterm2);
        }
        unset($place_id);
        unset($temp);
    }
    unset($zoekterm);
    unset($va_keys);
}
$log->logInfo("IMPORT COMPLETE.");