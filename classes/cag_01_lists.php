<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

define("__PROG__","lists");

include('header.php');

require_once(__MY_DIR__."/cag_tools/classes/Lists.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_lijst = new Lists();

$mappingarray = $t_func->ReadMappingcsv("cag_lists_mapping.csv");

$data = array();
$data[] = array('sinttruiden.xml', 'objectNaam', 'cag_thesaurus');
$data[] = array('objecten.xml', 'objectNaam', 'cag_thesaurus');
$data[] = array('sinttruiden.xml', 'trefwoord_1', 'cag_trefwoorden');
$data[] = array('sinttruiden.xml', 'trefwoord_2', 'cag_trefwoorden');
$data[] = array('sinttruiden.xml', 'trefwoord_3', 'cag_trefwoorden');
$data[] = array('sinttruiden.xml', 'trefwoord_4', 'cag_trefwoorden');
$data[] = array('objecten.xml', 'trefwoord_1', 'cag_trefwoorden');
$data[] = array('objecten.xml', 'trefwoord_2', 'cag_trefwoorden');
$data[] = array('objecten.xml', 'trefwoord_3', 'cag_trefwoorden');
$data[] = array('objecten.xml', 'trefwoord_4', 'cag_trefwoorden');

$count = sizeof($data) - 1;

for ($i = 0; $i <= $count; $i++) {
    $t_lijst->createList($data[$i][0], $data[$i][1], $data[$i][2], $pn_locale_id, $mappingarray);
}

$log->logInfo("Einde CAGList");