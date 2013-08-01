<?php
/**
 * Description of Places
 *
 * @author AnitaR <anita.ruijmen@libis.kuleuven.be>
 */

class Places
{
    function createRootStructure($t_func, $vn_hierarchy_id, $pn_locale_id, $log) {
        // Root-info
        $antw = array('Antwerpen', 'Mechelen', 'Turnhout');
        $limb = array('Hasselt', 'Maaseik', 'Tongeren');
        $ovla = array('Aalst', 'Dendermonde', 'Eeklo', 'Gent', 'Oudenaarde', 'Sint-Niklaas');
        $vlbr = array('Halle-Vilvoorde', 'Leuven');
        $wvla = array('Brugge', 'Diksmuide', 'Ieper', 'Kortrijk', 'Oostende', 'Roeselare', 'Tielt', 'Veurne');

        $brwa = array('Nivelles');
        $hain = array('Ath', 'Charleroi', 'Thuin', 'Mons', 'Soignies', 'Tournai', 'Mouscron');
        $namu = array('Dinant', 'Philippeville', 'Virton');
        $lieg = array('Huy', 'Liège', 'Virviers', 'Waremme');
        $luxe = array('Arlon', 'Bastogne', 'Virton', 'Neufchâteau', 'Marche-en-Famenne');
        // 1. De provincies
        $vla =  array('Antwerpen (provincie)' => $antw, 'Limburg (provincie)' => $limb, 'Oost-Vlaanderen (provincie)' => $ovla,
                      'Vlaams Brabant (provincie)' => $vlbr, 'West-Vlaanderen (provincie)' => $wvla);
        $bru = array('Brussel (provincie)' => '');

        $wal = array('Brabant Wallon (provincie)' => $brwa, 'Hainaut (provincie)' => $hain, 'Liège (provincie)' => $lieg,
                     'Namur (provincie)' => $namu, 'Hainaut (provincie)' => $hain, 'Luxembourg (provincie)' => $luxe);
        // 1. Onder België: de 3 gewesten
        $gewest = array('Brussel' => $bru, 'Vlaanderen' => $vla, 'Wallonië' => $wal, 'Speciaal' => '');
        // 0. Landen
        $root = array('België' => $gewest, 'Zweden' => '', 'Italië' => '', 'Oostenrijk' => '', 'Frankrijk' => '',
                      'Verenigde Staten' => '', 'Verenigd Koninkrijk' => '', 'Nederland' => '', 'Duitsland' => '',
                      'Zwitserland' => '', 'Canada' => '', 'Spanje' => '', 'Argentinië' => '');


         // de rootstructuur aanmaken
        $log->logInfo("CREATIE  ROOT-STRUCTURE");
        $t_list = new ca_lists();

        foreach ($root as $key => $value) {
            //de landen
            $vn_place_id = $t_list->getItemIDFromList('place_types', 'country');
            $land_id = $t_func->createPlace($key, 1, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
            $log->logInfo("===land: ".$land_id." - ".$key." aangemaakt");

            if (is_array($value)) {

                foreach ($value as $key2 => $value2) {

                    //de gewesten
                    $vn_place_id = $t_list->getItemIDFromList('place_types', 'state');
                    $gewest_id = $t_func->createPlace($key2, $land_id, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
                    $log->logInfo("===gewest: ".$gewest_id." - ".$key2." aangemaakt");

                    if (is_array($value2)) {

                        foreach ($value2 as $key3 => $value3) {

                            //de provincies
                            $vn_place_id = $t_list->getItemIDFromList('place_types', 'state');
                            $prov_id = $t_func->createPlace($key3, $gewest_id, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
                            $log->logInfo("===provincie: ".$prov_id." - ".$key3." aangemaakt");

                            if (is_array($value3)) {

                                foreach ($value3 as $value4) {

                                    //de arrondissementen
                                    $vn_place_id = $t_list->getItemIDFromList('place_types', 'other');
                                    $t_func->createPlace(trim($value4), $prov_id, $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);
                                    $log->logInfo("===deelgemeente: ".$value4." aangemaakt");
                                }
                            }
                        }
                    }
                }
            }
        }

        $log->logInfo("FINISHED CREATIE ROOT-STRUCTURE \n");

    }

    function importHoofdGemeenten($t_func, $vn_hierarchy_id, $pn_locale_id, $log)
    {
        $log->logInfo("Import BELGIE_NEW_1.csv \n");

        $o_tab_parser = new DelimitedDataParser("\t");
        // Read csv; line by line till end of file.
        if (!$o_tab_parser->parse(__MY_DIR__."/cag_tools/data/BELGIE_NEW_1.csv")) {
                die("Couldn't parse BELGIE_NEW_1.csv data");
        }

        $vn_c1 = 1;

        $o_tab_parser->nextRow(); // skip first row (headings)
        while($o_tab_parser->nextRow()) {
                // Get columns from tab file and put them into named variables - makes code easier to read
                //$vs_land		=	$o_tab_parser->getRowValue(1);
                $vs_postcode		=	$o_tab_parser->getRowValue(2);
                //$vs_gemeente		=	$o_tab_parser->getRowValue(3);
                $vs_gewest      	=	$o_tab_parser->getRowValue(4);
                $vs_provincie           =	$o_tab_parser->getRowValue(5);
                $vs_arrondissement	=	$o_tab_parser->getRowValue(6);
                $vs_latitude            =	$o_tab_parser->getRowValue(7);
                $vs_longitude 		=	$o_tab_parser->getRowValue(8);
                $vs_gemeente            =       $o_tab_parser->getRowValue(11);

                $log->logInfo("PROCESSING ", $vs_gemeente);

                $this->VerwerkGemeente($vs_arrondissement, $vs_gewest, $vs_provincie,
                        $vs_gemeente, $vs_postcode, $vs_latitude, $vs_longitude,
                        $t_func, $vn_hierarchy_id, $pn_locale_id, $log);

                $vn_c1++;
        }
        unset($o_tab_parser);

        $log->logInfo("FINISHED CREATIE HOOFD-GEMEENTEN \n");
    }

    function importDeelGemeenten($t_func, $vn_hierarchy_id, $pn_locale_id, $log)
    {
        $log->logInfo("Import BELGIE_NEW_2.csv \n");

        $o_tab_parser = new DelimitedDataParser("\t");
        // Read csv; line by line till end of file.
        if (!$o_tab_parser->parse(__MY_DIR__."/cag_tools/data/BELGIE_NEW_2.csv")) {
                die("Couldn't parse BELGIE_NEW_2.csv data \n");
        }

        $vn_c2 = 1;

        $o_tab_parser->nextRow(); // skip first row (headings)
        while($o_tab_parser->nextRow()) {
                // Get columns from tab file and put them into named variables - makes code easier to read
                //$vs_land		=	$o_tab_parser->getRowValue(1);
                $vs_postcode		=	$o_tab_parser->getRowValue(2);
                //$vs_gemeente		=	$o_tab_parser->getRowValue(3);
                $vs_gewest      	=	$o_tab_parser->getRowValue(4);
                $vs_provincie           =	$o_tab_parser->getRowValue(5);
                //$vs_arrondissement	=	$o_tab_parser->getRowValue(6);
                $vs_latitude            =	$o_tab_parser->getRowValue(7);
                $vs_longitude 		=	$o_tab_parser->getRowValue(8);
                $vs_arrondissement      =       $o_tab_parser->getRowValue(11);
                $vs_gemeente            =       $o_tab_parser->getRowValue(12);

                if (trim($vs_gemeente) != "") {
                    $log->logInfo("PROCESSING ", $vs_gemeente);

                    $vs_arrondissement = trim($vs_arrondissement)." - %";

                    $this->VerwerkGemeente($vs_arrondissement, $vs_gewest, $vs_provincie,
                            $vs_gemeente, $vs_postcode, $vs_latitude, $vs_longitude,
                            $t_func, $vn_hierarchy_id, $pn_locale_id, $log);
                }

                $vn_c2++;
        }
        unset($o_tab_parser);

        $log->logInfo("FINISHED CREATIE DEEL-GEMEENTEN \n");
    }

    function importSpecialekes($t_func, $vn_hierarchy_id, $pn_locale_id, $log)
    {
        $log->logInfo("Import BELGIE_NEW_3.csv \n");

        $o_tab_parser = new DelimitedDataParser("\t");
        // Read csv; line by line till end of file.
        if (!$o_tab_parser->parse(__MY_DIR__."/cag_tools/data/BELGIE_NEW_3.csv")) {
                die("Couldn't parse BELGIE_NEW_3.csv data \n");
        }

        $vn_c3 = 1;

        $o_tab_parser->nextRow(); // skip first row (headings)
        while($o_tab_parser->nextRow()) {
                // Get columns from tab file and put them into named variables - makes code easier to read
                //$vs_land		=	$o_tab_parser->getRowValue(1);
                $vs_postcode		=	$o_tab_parser->getRowValue(2);
                $vs_gemeente		=	$o_tab_parser->getRowValue(3);
                $vs_gewest      	=	$o_tab_parser->getRowValue(4);
                $vs_provincie           =	$o_tab_parser->getRowValue(5);
                $vs_arrondissement	=	$o_tab_parser->getRowValue(6);
                $vs_latitude            =	$o_tab_parser->getRowValue(7);
                $vs_longitude 		=	$o_tab_parser->getRowValue(8);

                $log->logInfo("PROCESSING ", $vs_gemeente);

                $this->VerwerkGemeente($vs_arrondissement, $vs_gewest, $vs_provincie,
                        $vs_gemeente, $vs_postcode, $vs_latitude, $vs_longitude,
                        $t_func, $vn_hierarchy_id, $pn_locale_id, $log);

                $vn_c3++;
        }
        unset($o_tab_parser);

        $log->logInfo("FINISHED CREATIE SPECIALEKES \n");
    }

    function VerwerkGemeente($vs_arrondissement, $vs_gewest, $vs_provincie,
        $vs_gemeente, $vs_postcode, $vs_latitude, $vs_longitude,
        $t_func, $vn_hierarchy_id, $pn_locale_id, $log){

        $t_list = new ca_lists();
        $t_place = new ca_places_bis();
        $t_place->setMode(ACCESS_WRITE);

        $vn_place_id = $t_list->getItemIDFromList('place_types', 'city');

        $va_root = $t_place ->getPlaceIDsByNamePart($vs_arrondissement);

        if (($vs_gewest == "") && ($vs_arrondissement == "") && ($vs_provincie == ""))
        {
            $va_root = $t_place ->getPlaceIDsByName('Speciaal');
        }
        if (($vs_arrondissement == "") && ($vs_provincie == 'Bruxelles'))
        {
            $va_root = $t_place ->getPlaceIDsByName('Brussel (provincie)');
        }
        if ($vs_arrondissement == "Virton")
        {
            $va_parent_id = $t_place->getPlaceIDsByName(($vs_provincie.' (provincie)'));
            $va_root = $t_place ->getPlaceIDsByName($vs_arrondissement, $va_parent_id[0]);
        }

        $zoekterm = trim($vs_gemeente)." - %";
        $vs_gemeente = trim($vs_gemeente)." - ".$vs_postcode;

        if (!$t_place->getPlaceIDsByNamePart($zoekterm)) {

            $log->logInfo("creating term ".$vs_gemeente." underneath ".$vs_arrondissement." and adding labels for term ");

            $place_id = $t_func->createPlace($vs_gemeente, $va_root[0], $vn_place_id, $vn_hierarchy_id, $pn_locale_id, $log);

            $t_place->load($place_id);
            $t_place->getPrimaryKey();
            $t_place->set('place_id', $place_id);

            $vs_georeference = $vs_gemeente.' ['.$vs_latitude.', '.$vs_longitude.']';

            $t_place->addAttribute(array(
                    'locale_id'         =>	$pn_locale_id,
                    'georeference'      =>	$vs_georeference
            ), 'georeference');

            $t_place->update();

            if ($t_place->numErrors()) {
                $log->logError("ERROR INSERTING {$vs_gemeente}: ".join('; ', $t_place->getErrors())." \n");
            } else {
                $log->logInfo("SUCCESS: Georeference added for {$vs_gemeente}");
            }
        } else {
            $log->logInfo("gemeente {$zoekterm} bestaat reeds ");
        }
        unset($t_list);
        unset($t_place);
    }

}

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

define("__PROG__","places");
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
//$mappingarray = $t_func->ReadMappingcsv("cag_lists_mapping.csv");
$t_list = new ca_lists();
$vn_hierarchy_id = $t_list->getItemIDFromList('place_hierarchies', 'i1');
unset($t_list);

$t_test = new Places();
$t_test->createRootStructure($t_func, $vn_hierarchy_id, $pn_locale_id, $log);
$t_test->importHoofdGemeenten($t_func, $vn_hierarchy_id, $pn_locale_id, $log);
$t_test->importDeelGemeenten($t_func, $vn_hierarchy_id, $pn_locale_id, $log);
$t_test->importSpecialekes($t_func, $vn_hierarchy_id, $pn_locale_id, $log);