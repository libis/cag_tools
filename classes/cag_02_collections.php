<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
define("__PROG__","collecties");

include('header.php');

require_once(__CA_MODELS_DIR__."/ca_collections.php");
require_once(__MY_DIR__."/cag_tools/classes/Collections.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_local = new Collections();

//==============================================================================initialisaties
$collectie = array();
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_collecties_mapping.csv");

//++++++++++++++++++
// STAP 1
// lijst (array) maken van voorkomende waarden (waarde is de key in de array - > gevolg: geen dubbels)
//++++++++++++++++++
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/objecten.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' )
{
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $t_local->createCollectionArray($resultarray['collectieBeschrijving_1'], $collectie, $pn_locale_id);
    $t_local->createCollectionArray($resultarray['collectieBeschrijving_2'], $collectie, $pn_locale_id);

    $reader->next();
}
$reader->close();

$log->logInfo('Inhoud collectie-array', $collectie);

//++++++++++++++++++
// STAP 2
// Collecties aanmaken ahv key's van de gemaakte array
//++++++++++++++++++
$t_list = new ca_lists();
$pn_collection_type_id = $t_list->getItemIDFromList('collection_types', 'algemeneCollectie');
$status = $t_list->getItemIDFromList('workflow_statuses', 'i2');

$teller = 1;

foreach ($collectie as $value)
{
    if ($value !== '') {

        $idno = sprintf('%08d', $teller);
        $log->logInfo("inserting collection idno: ", $idno);

        $t_local->insertCollection($pn_collection_type_id, $idno, $status, 1, $pn_locale_id, $value);

    }
    $teller = $teller + 1;
}

$log->logInfo("EINDE VERWERKING \n");