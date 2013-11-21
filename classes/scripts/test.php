<pre>
<?php
define("__PROG__","test");

include('../header.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Lists.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_meta = new ca_metadata_elements();
$info = $t_meta->_getElementID('objectvervaardigingInfo');
$log->logInfo('objectvervaardigingInfo', $info);
$place = $t_meta->_getElementID('objectvervaardigingInfo');
$log->logInfo('objectvervaardigingPlace', $place);
exit;

$t_entity = new ca_entities_bis();
$t_entity->setMode(ACCESS_WRITE);


//==============================================================================initialisaties
$teller = 1;
//==============================================================================
$mappingarray = $t_func->ReadMappingcsv("cag_entities_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR__."/cag_tools/data/entities.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo("==================".($teller)."==================");
    $log->logInfo('de originele data', $resultarray);

    $fields_used_for = array('used_for');
    $aantal_used_for = sizeof($resultarray['used_for']);
    $used_for = $t_func->makeArray2($resultarray, $aantal_used_for, $fields_used_for);

    $fields_pref = array('preferred_label');
    $aantal_pref = sizeof($resultarray['preferred_label']);
    $pref = $t_func->makeArray2($resultarray, $aantal_pref, $fields_pref);

    if ( (isset($used_for['used_for'][0])) && (!empty($used_for['used_for'][0])) ) {
        //'used_for' zijn gewoon alternatieve benamingen voor 'preferred_label'
        // controleren of de 'preferred_label' ondertussen al bestaat
        $log->logInfo('If voldaan');
        $search_string = $t_func->cleanUp(trim($pref['preferred_label'][0]));
        $log->logInfo('searchstring', $search_string);
        $va_left_keys = $t_entity->getEntityIDsByUpperNameSort($search_string);
        $log->logInfo('va_left_keys', $va_left_keys);
    }

    $teller = $teller + 1;

    $reader->next();
}

$reader->close();

$log->logInfo("EINDE VERWERKING");



?>
</pre>