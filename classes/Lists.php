<?php

/**
 * Doel van dit programma:
 * Op basis van het objecten.xml-bestand, (en sinttruiden.xml)
 * worden de ca_lists  cag_thesaurus en cag_trefwoorden aangemaakt.
 *
 * @author AnitaR <anita.ruijmen@libis.kuleuven.be>
 */

class Lists
{

    public function createCAGthesaurusList($xml, $pn_locale_id, $log, $mappingarray, $t_func)
    {
        $teller2 = 1;
        //inlezen xml-bestand met XMLReader, node per node
        $reader2 = new XMLReader();
        $reader2->open(__MY_DIR_2__."/cag_tools/data/".$xml);

        while ($reader2->read() && $reader2->name !== "record");
        //=====================================================begin van de loop
        while ($reader2->name === 'record' ) {
            $xmlarray = $t_func->ReadXMLnode($reader2);

            $resultarray = $t_func->XMLArraytoResultArray($xmlarray, $mappingarray);

            $objectNaam = $resultarray['objectNaam'];

            $log->logInfo("=====", ($teller2));
            //cag_thesaurus: list_id = 37
            if ( (isset($objectNaam)) && (!empty($objectNaam)) ) {
                try
                {
                    $message = $t_func->createList("cag_thesaurus", $objectNaam, $pn_locale_id);
                    $log->logInfo("creatie list item", $message);
                }
                catch (Exception $e)
                {
                    throw new Exception("Something went wrong", 0, $e);
                    $log->logError("Exception", $e->getMessage);
                }
            } else {
                $log->logInfo("GEEN DATA BESCHIKBAAR voor objectNaam \n ");
            }

            $teller2 = $teller2 + 1;

            $reader2->next();
        }
        $reader2->close();

        $log->logInfo("EINDE VERWERKING {$xml} \n");

        unset($teller2);
        unset($reader2);
        unset($xmlarray);
        unset($resultarray);

    }

    public function createCAGtrefwoordLijst($xml, $pn_locale_id, $log, $mappingarray, $t_func)
    {
        $teller = 1;
        //inlezen xml-bestand met XMLReader, node per node
        $reader = new XMLReader();
        $reader->open(__MY_DIR_2__."/cag_tools/data/".$xml);

        while ($reader->read() && $reader->name !== 'record');
        //=====================================================begin van de loop
        while ($reader->name === 'record' ) {
            $xmlarray = $t_func->ReadXMLnode($reader);

            $resultarray = $t_func->XMLArraytoResultArray($xmlarray, $mappingarray);

            $log->logInfo("=====", ($teller));
            //cag_trefwoorden: list_id = 38
            $ind = 1;
            for ($ind=1; $ind<=4; $ind++) {

                $trefwoord = $resultarray['trefwoord_'.$ind];

                if ( (isset($trefwoord)) && (!empty($trefwoord)) ) {

                    $message = $t_func->createList("cag_trefwoorden", $trefwoord, $pn_locale_id);
                    $log->logInfo("creatie list item trefwoord_{$ind}: {$trefwoord} van CAG_trefwoorden", $message);
                } else {
                    $log->logInfo("GEEN DATA BESCHIKBAAR voor trefwoord_{$ind} \n ");
                }
            }

            $teller = $teller + 1;
            $reader->next();
        }
        $reader->close();

        $log->logInfo("EINDE VERWERKING {$xml} \n");

        unset($teller);
        unset($reader);
        unset($xmlarray);
        unset($resultarray);
    }

    function settings1($type)
    {
        if ($type == "LOCAL") {
            define("__MY_DIR__", "c:/xampp/htdocs");
            define("__MY_DIR_2__", "c:/xampp/htdocs/ca_cag");
        }
        if ($type == "SERVER") {
            define("__MY_DIR__", "/www/libis/vol03/lias_html");
            define("__MY_DIR_2__", "/www/libis/vol03/lias_html");
        }

    }

    function settings2()
    {
        require_once(__MY_DIR__."/ca_cag/setup.php");
        require_once(__CA_LIB_DIR__."/core/Db.php");
        require_once(__CA_MODELS_DIR__."/ca_locales.php");
        require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");

        include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

        define("__PROG__","lists");
    }
}

error_reporting(-1);
set_time_limit(0);
$type = "SERVER";
$t_test = new Lists();
$t_test->settings1($type);
$t_test->settings2();
$t_func = new MyFunctions_new();

$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();
$mappingarray = $t_func->ReadMappingcsv("cag_lists_mapping.csv");
//CAG__thesaurus
$t_test->createCAGthesaurusList("sinttruiden.xml", $pn_locale_id, $log, $mappingarray, $t_func);
$t_test->createCAGthesaurusList("objecten.xml", $pn_locale_id, $log, $mappingarray, $t_func);
//CAG_trefwoorden
// bestand sinttruiden bevat geen trefwoorden
$t_test->createCAGtrefwoordLijst("sinttruiden.xml", $pn_locale_id, $log, $mappingarray, $t_func);
$t_test->createCAGtrefwoordLijst("objecten.xml", $pn_locale_id, $log, $mappingarray, $t_func);