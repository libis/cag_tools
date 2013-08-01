<?php
/*Doel van dit programma:
 * Op basis van het objecten.xml-bestand, wordt een array gemaakt van collecties
 * op basis van de inhoud van de velden administration_name en collection.
 * Vervolgens worden deze collecties aangemaakt in CA.
 *
 * @author AnitaR <anita.ruijmen@libis.kuleuven.be>
 *
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

define("__PROG__","collecties");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_collections.php");
require_once(__MY_DIR_2__."/cag_tools/classes/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");
require_once(__MY_DIR_2__."/cag_tools/classes/ca_places_bis.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$pn_collection_type_id = $t_list->getItemIDFromList('collection_types', 'algemeneCollectie');
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');

//==============================================================================initialisaties
$teller = 1;
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_collecties_mapping.csv");

//++++++++++++++++++
// STAP 1
// lijst (array) maken van voorkomende waarden (waarde is de key in de array - > gevolg: geen dubbels)
//++++++++++++++++++

$reader = new XMLReader();
$reader->open(__CA_BASE_DIR__."/cag_tools/data/objecten.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' )
{
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    if ( (isset($resultarray['collectieBeschrijving_1'])) && (!empty($resultarray['collectieBeschrijving_1'])) ) {
        $sleutel = $resultarray['collectieBeschrijving_1'];
    }
    $collectie[$sleutel] = array();

    if ( (isset($resultarray['collectieBeschrijving_2'])) && (!empty($resultarray['collectieBeschrijving_2'])) ) {
        $sleutel = $resultarray['collectieBeschrijving_2'];
    }
    $collectie[$sleutel]= array();

    $reader->next();
}
$reader->close();

$log->logInfo('Inhoud collectie-array', $collectie);

//++++++++++++++++++
// STAP 2
// Collecties aanmaken ahv key's van de gemaakte array
//++++++++++++++++++

foreach ($collectie as $key => $value)
{
    if ($key != "") {
        $idno = sprintf('%08d', $teller);
        $log->logInfo("inserting collection idno: ", $idno);

        $t_collect = new ca_collections();
        $t_collect->setMode(ACCESS_WRITE);
        $t_collect->set('type_id', $pn_collection_type_id);
        //opgelet !!! vergeet leading zeros niet
        $t_collect->set('idno', $idno);
        $t_collect->set('status', $status); //workflow_statuses
        $t_collect->set('access', 1);       //1=accessible to public
        $t_collect->set('locale_id', $pn_locale_id);
    //----------
        $t_collect->insert();
    //----------
        try {
            if ($t_collect->numErrors()) {
                throw new Exception("ERROR INSERTING COLLECTION ".$key . join("; ", $t_collect->getErrors())."\n");
            }
            $log->logInfo("insert gelukt ", $key);

            $t_collect->addLabel(array(
                    'name'      => $key
            ),$pn_locale_id, null, true );

            try {
                if ($t_collect->numErrors()) {
                    throw new Exception("ERROR INSERTING COLLECTION ".$key . join("; ", $t_collect->getErrors())."\n");
                }
                $log->logInfo("AddLabel gelukt ", $key);

            } catch (Exception $e) {
                $log->logError($e->getMessage());
            }
        } catch (Exception $e) {
            $log->logError($e->getMessage());
        }
    }
    $teller = $teller + 1;
}
unset($t_collect);
$log->logInfo("EINDE VERWERKING \n");