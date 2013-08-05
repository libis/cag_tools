<?php
/* Doel van dit programma:
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
define("__PROG__","werktuigen");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__MY_DIR_2__.'/cag_tools/classes/ca_objects_bis.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();
$pn_object_type_id = $t_list->getItemIDFromList('object_types', 'cagConceptVoorwerp_type');
$preferred_use = $t_list->getItemIDFromList('object_label_types', 'uf');
$preferred_alt = $t_list->getItemIDFromList('object_label_types', 'alt');
//==============================================================================initialisaties
$teller = 0;
//==============================================================================inlezen bestanden
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_werktuigen_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR_2__."/cag_tools/data/Werktuigen.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    $singlefield = array();

    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $teller = $teller + 1;
    $log->logInfo( '=========='.$teller.'========');

    $idno = sprintf('%04d', $teller);
    $idno = 'concept_'.$idno;
    $log->logInfo("idno: ", $idno);

    //einde inlezen één record, begin verwerking één record
    //------------------------------------------------------------------------------
    //de identificatie
    if (isset($resultarray['preferred_label'])) {
        if (is_array($resultarray['preferred_label'])) {
            $vs_Identificatie = $resultarray['preferred_label'][0];
            $log->logInfo('WARNING: preferred_label => meerdere aanwezig: enkel de eerste genomen');
        }else {
            $vs_Identificatie = $resultarray['preferred_label'];
        }
    }else{
        $vs_Identificatie = "====='.$idno.' geen identificatie=====";
        $log->logInfo('WARNING: preferred_label => niet aanwezig');
    }

    $log->logInfo("Identificatie: ", $vs_Identificatie);
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //de workflow_status
    if (isset($resultarray['publication_data']) && (strtoupper($resultarray['publication_data'])) == 'JA' ) {
        $status1 = 'JA';
    } else {
        $status1 = 'nee';
    }

    if (($status1 == 'JA') )    {   $status = $t_list->getItemIDFromList('workflow_statuses', 'i2');}

    if (($status1 == 'NEE'))    {   $status = $t_list->getItemIDFromList('workflow_statuses', 'i0');}

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    $t_object = new ca_objects();
    $t_object->setMode(ACCESS_WRITE);
    $t_object->set('type_id', $pn_object_type_id);
    //opgelet !!! vergeet leading zeros niet
    $t_object->set('idno', $idno);
    $t_object->set('status', $status); //workflow_statuses
    $t_object->set('access', 1);       //1=accessible to public
    $t_object->set('locale_id', $pn_locale_id);
    //----------
    $t_object->insert();
    //----------
    if ($t_object->numErrors()) {
        $log->logInfo("ERROR INSERTING ".$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
        continue;
    }else{
        $log->logInfo('insert '.$vs_Identificatie.' gelukt ');
        //----------
        $t_object->addLabel(array(
                'name'      => $vs_Identificatie
        ),$pn_locale_id, null, true );

        if ($t_object->numErrors()) {
            $log->logInfo("ERROR ADD LABEL TO " .$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
            continue;
        }else{
            $log->logInfo('addlabel '.$vs_Identificatie.' gelukt');
        }
    }

    $resultarray['primary_key'] = $t_object->getPrimaryKey();

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Alternatieve Naam
    //**************************************************************************
    ///deze zijn er niet in de gegevens
    if ( (isset($resultarray['non_preferred_label_alt'])) && (!is_array($resultarray['non_preferred_label_alt']))  ) {
        $t_object->addLabel(array(
                'name'      => $resultarray['non_preferred_label_alt']
        ),$pn_locale_id, $preferred_alt, false );

        if ($t_object->numErrors()) {
                $log->logInfo("ERROR NON_PREFERRED_ALT ADD LABEL TO ".$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
                continue;
        }else{
                $log->logInfo('non_preferred_alt addlabel TO '.$vs_Identificatie.' gelukt');
        }
    }

    if (is_array($resultarray['non_preferred_label_alt'])) {
    //Omdat er array's tussenzitten, vormen we alles om tot een array
        $aantal_use = $t_func->Herhalen($resultarray, array('non_preferred_label_use'));
        $result_use = $t_func->makeArray2($resultarray, $aantal_use, array('non_preferred_label_use'));

        for ($i=0; $i < ($aantal_use - 1); $i++) {
            if (!empty($result_use['non_preferred_label_use'][$i])) {
                $t_object->addLabel(array(
                        'name'      => $result_use['non_preferred_label_use'][$i]
                ),$pn_locale_id, $preferred_use, false );

                if ($t_object->numErrors()) {
                        $log->logInfo("ERROR ADD NON_PREFERRED_USE LABEL TO ".$vs_Identificatie.": ".join('; ', $t_object->getErrors()));
                        continue;
                }else{
                        $log->logInfo('non_preferred_use addlabel TO '.$vs_Identificatie.' gelukt');
                }
            }
        }
    }

    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Adlibobjectnr (id = 236)
    //**************************************************************************
    //textveld adlibObjectNummer -> geen arrays
    if (isset($resultarray['adlibObjectNummer'])  && (!is_array($resultarray['adlibObjectNummer'])) ) {
        $singlefield[] = 'adlibObjectNummer';
    }
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Definitie (id = 402)
    //**************************************************************************
    //textveld conceptDefinitie -> array komt een aantal (6) keer voor, maar is dan leeg
    if (isset($resultarray['conceptDefinitie'])  && (!is_array($resultarray['conceptDefinitie'])) ) {
        $resultarray['conceptDefinitie'] = str_replace(" | ", " <br /> ", $resultarray['conceptDefinitie']);

        $singlefield[] = 'conceptDefinitie';
    }
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Algemene Beschrijving (id = 23)
    //**************************************************************************
    //htmlveld algemeneBeschrijving -> array komt een aantal (6) keer voor, maar is dan leeg
    if (isset($resultarray['algemeneBeschrijving'])  && (!is_array($resultarray['algemeneBeschrijvig'])) ) {
        $resultarray['algemeneBeschrijving'] = str_replace(" | ", " <br /> ", $resultarray['algemeneBeschrijving']);

        $singlefield[] = 'algemeneBeschrijving';
    }
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Technische Beschrijving (id = 22)
    //**************************************************************************
    //htmlveld technischeBeschrijving -> array komt een aantal (15) keer voor, maar is dan leeg
    if (isset($resultarray['technischeBeschrijving'])  && (!is_array($resultarray['technischeBeschrijvig'])) ) {
        $resultarray['technischeBeschrijving'] = str_replace(" | ", " <br /> ", $resultarray['technischeBeschrijving']);

        $singlefield[] = 'technischeBeschrijving';
    }
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //digitoolUrl (id = ) -> ToDo
    //**************************************************************************
    //
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //De verwerking
    //**************************************************************************
    //
    foreach ($singlefield as $value)
    {
        if (isset($resultarray[$value]))
        {
            $t_object->addAttribute(array(
                    $value          =>  trim($resultarray[$value]),
                    'locale_id'     =>  $pn_locale_id
            ), $value);
            //-------------
            $t_object->update();
            //-------------

            if ($t_object->numErrors()) {
                    $log->logInfo("ERROR UPDATING ".$value.": ".join('; ', $t_object->getErrors()));
                    continue;
            }else{
                    $log->logInfo('update '.$value.' gelukt ');
            }
        }
    }

    $reader->next();
}
$reader->close();

$log->logInfo("EINDE VERWERKING");