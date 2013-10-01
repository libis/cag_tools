<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PlacesAlternatives {

    function importGegevens()
    {
        global $log;

        $log->logInfo("Import BE.csv \n");

        $o_tab_parser = new DelimitedDataParser("\t");
        // Read csv; line by line till end of file.
        if (!$o_tab_parser->parse(__MY_DIR__."/cag_tools/data/BE.csv")) {
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

define("__PROG__","places_alternatives");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_places_bis.php");
require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_place = new ca_places_bis();
$t_place->setMode(ACCESS_WRITE);

$t_test = new PlacesAlternatives();

//inlezen BE.csv in een array
$alt = $t_test->importGegevens();

$teller = 0;

foreach($alt as $value) {

    $zoekterm = trim($value['0'])." - %";

    $log->logInfo("=====".$alt."=====", $value);

    $va_keys = $t_place->getPlaceIDsByNamePart($zoekterm);

    if (sizeof($va_keys)> 0) {

        if (sizeof($va_keys) > 1) {
            #56
            $log->logError("ERROR: meerdere gemeenten gevonden voor ", $zoekterm);
            $log->logError("ERROR: namelijk ", $va_keys);
            $teller = $teller + 1;
        } else {
            $log->logInfo("slechts 1 gemeente gevonden, voegen alternatieve labels toe", $va_keys);

            $place_id = $va_keys['0'];
            $t_place->load($place_id);
            $t_place->getPrimaryKey();
            $t_place->set('place_id', $place_id);

            $temp = explode(",", $value['1']);

            $log->logInfo("de alternatieven", $temp);

            foreach ($temp as $alternatief) {

                $zoekterm2 = trim($alternatief)."%";

                $log->logInfo("verwerking alternatief: ", $zoekterm2);

                if (!$t_place->getPlaceIDsByNamePart($zoekterm2)) {
                    $t_place->addLabel(
                            array('name' => ($alternatief)),
                            $pn_locale_id, null, false
                    );

                    if ($t_place->numErrors()) {
                        $log->logERROR("ERROR ADDING ALTERNATIVE FOR ", $alternatief . join("; ", $t_place->getErrors())."\n");
                    } else {
                        $log->logInfo("Alternatief met succes toegevoegd", $alternatief);
                    }
                }
                unset($zoekterm2);
            }
            unset($place_id);
            unset($temp);
            unset ($alternatief);
        }
    } else {
        $log->logInfo("geen geldige gemeente gevonden om mee te linken", $va_keys);
    }
    unset($zoekterm);
    unset($va_keys);
}
$log->logInfo("IMPORT COMPLETE.");
$log-> logInfo("gemeenten met 'meerdere records gevonden'", $teller);