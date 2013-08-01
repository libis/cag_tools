<?php
/*Doel van dit programma:
 * Op basis van het objecten.xml-bestand, wordt een array gemaakt van occurrences
 * Vervolgens worden deze occurrences aangemaakt in CA.
 *
 * refPagina = id-242 -> komt 66 keer voor -> moet bij het object vermeld worden -> nok in cag_objecten_relaties.php opnemen
 * refAuteur = id-243 -> komt 61 keer voor -> moet bij publicatie vermeld worden -> ok
 * refNote   = id-255 -> komt 21 keer voor -> moet bij publicatie vermeld worden -> ok
 * ref..Date = id-253 -> nieuw veld
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

define("__PROG__","occurrences_2");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
//list_id = 24 -> correct
$pn_occurrence_type_id = $t_list->getItemIDFromList('occurrence_types', 'references');
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');
//==============================================================================initialisaties
$fout = array();
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_objecten_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__CA_BASE_DIR__."/cag_tools/data/objecten.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' )
{
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    if ( (is_array($resultarray['objectnaamOpmerkingen_3'])) ) {
        $aantal = $resultarray['objectnaamOpmerkingen_3'] -1;
        for ($i= 0; $i<= $aantal; $i++) {
            if ( (isset($resultarray['objectnaamOpmerkingen_3'][$i])) && (!empty($resultarray['objectnaamOpmerkingen_3'][$i])) ) {
                $sleutel = $resultarray['objectnaamOpmerkingen_3'][$i];
            }
            $sleutel = $sleutel.$resultarray['objectnaamOpmerkingen_3'][$i];
            $occur[$sleutel] = array();
        }
    }else{
        if ( (isset($resultarray['objectnaamOpmerkingen_3'])) && (!empty($resultarray['objectnaamOpmerkingen_3'])) ) {
            $sleutel = $resultarray['objectnaamOpmerkingen_3'];
        }
        $occur[$sleutel]= array();
    }
    $reader->next();
}

$log->logInfo("Inhoud array", $occur);

//++++++++++++++++++
// Deel 2
//++++++++++++++++++

$teller = 100;

foreach ($occur as $key => $value) {
    if (trim($key) != "") {
        $idno = sprintf('%08d', $teller);

        $log->logInfo("=====".$idno."=====");

        $t_occur = new ca_occurrences();
        $t_occur->setMode(ACCESS_WRITE);
        $t_occur->set('type_id', $pn_occurrence_type_id);
        //opgelet !!! vergeet leading zeros niet
        $t_occur->set('idno', $idno);
        $t_occur->set('status', $status); //workflow_statuses
        $t_occur->set('access', 1);       //1=accessible to public
        $t_occur->set('locale_id', $pn_locale_id);
        //----------
        $t_occur->insert();
        //----------
        if ($t_occur->numErrors()) {
            $log->logError("ERROR INSERTING ".$key." - ".join('; ', $t_occur->getErrors())."\n");
            continue;
        }else{
            $log->logInfo("insert ".$key." gelukt \n");
            //----------
            $t_occur->addLabel(array(
                'name'      => $key
            ),$pn_locale_id, null, true );

            if ($t_occur->numErrors()) {
                $log->logError("ERROR ADD LABEL TO ".$key." - ".join('; ', $t_occur->getErrors())." \n");
                continue;
            }else{
                $log->logInfo("addlabel ".$key." gelukt \n");
            }

            foreach($value as $key2 => $value2) {
                if (!(is_array($value2))) {
                    //refAuteur = 243, refNote = 255, refPagina = 242, refPublicationDate = 253
                    $t_occur->addAttribute(array(
                            $key2           =>  trim($value2),
                            'locale_id'     =>  $pn_locale_id
                    ), $key2);

                    //-------------
                    $t_occur->update();
                    //-------------

                    if ($t_occur->numErrors()) {
                        $log->logError("ERROR UPDATING ".$key2.": ".join('; ', $t_occur->getErrors())." \n ");
                        continue;
                    }else{
                        $log->logInfo('update '.$key2.' gelukt \n');
                    }
                }
            }
        }
   }
   $teller = $teller + 1;
}

$log->logInfo("EINDE VERWERKING");