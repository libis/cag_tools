<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
define("__PROG__","entitiesRelaties");

include('header.php');

require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__CA_MODELS_DIR__ . '/ca_entities_x_entities.php');

require_once(__MY_DIR__."/cag_tools/classes/ca_entities_bis.php");
require_once(__MY_DIR__."/cag_tools/classes/Lists.php");
require_once(__MY_DIR__."/cag_tools/classes/Entities.php");
require_once(__MY_DIR__."/cag_tools/classes/EntitiesRelaties.php");

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$my_list = new Lists();
$my_entity = new Entities();
$my_entrelatie = new EntitiesRelaties();

$t_list = new ca_lists();

$t_entity_left = new ca_entities_bis();

$t_rel_types = new ca_relationship_types();
$vn_entities_x_entities = $t_rel_types->getRelationshipTypeID('ca_entities_x_entities', 'contact');
//==============================================================================initialisaties
$teller = 1;
$fields = array('c_contact', 'persoonFunctie', 'c_adres_straat', 'c_adres_postalcode',
                    'c_adres_city', 'c_adres_country', 'c_adresTelefoon' );
$adres_onbekend = $t_list->getItemIDFromList('adres_onbekend_lijst', 'nee');
//==============================================================================
//inlezen (in array) mapping-bestand
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

    $succes = array();
    $log->logInfo('====='.$teller.'=====');
    //$log->logInfo('de originele data', $resultarray);

    $aantal_contact = $t_func->Herhalen($resultarray, $fields);

    if ($aantal_contact > 0) {
        $contacts = $t_func->makeArray2($resultarray, $aantal_contact, $fields);

        $log->logInfo('de relevante gegevens', $contacts);

        if ( (isset($resultarray['use']))  && (!empty($resultarray['use'])) ) {
            $resultarray['preferred_label'] = $resultarray['use'];
        }

        $fields_pref = array('preferred_label');
        $aantal_pref = sizeof($resultarray['preferred_label']);
        $pref = $t_func->makeArray2($resultarray, $aantal_pref, $fields_pref);

        $Identificatie = $my_entity->defineIdentificatie($resultarray, $pref);
        $log->logInfo("Identificatie: ", $Identificatie);

        $search_string = $t_func->generateSortValue(trim($Identificatie), $pn_locale_id);
        $va_left_keys = $t_entity_left->getEntityIDsByUpperNameSort($search_string);
        $aantal_left = sizeof($va_left_keys);

        if ($aantal_left >= 1) {
            if ($aantal_left > 1) {
                $log->logWarn("WARNING: problems with entity", $search_string);
                $log->logWarn('Meerdere kandidaten gevonden', $va_left_keys);
                $log->logWarn('We nemen de eerste entity_id', $va_left_keys[0]);
            }

            $vn_left_id = $va_left_keys[0];

            $log->logInfo('left_id', $vn_left_id);

            $aantal = $aantal_contact - 1 ;

            for ($i=0; $i <= $aantal; $i++) {

                if (!empty($contacts['c_contact'][$i])) {
                    //relatie leggen met de contact
                    $vs_right_string = $t_func->generateSortValue(trim($contacts['c_contact'][$i]), $pn_locale_id);

                    $log->logInfo('relatie tussen '.$search_string.' en '.$vs_right_string);

                    $succes = $my_entrelatie->createEntityRelationship($vn_left_id, 'ca_entities', $vs_right_string, $vn_entities_x_entities);
                }
                $log->logInfo('succes', $succes);

                if ($succes[0] === 1) {
                    //plaatsen gerelateerde record in geheugen
                    $entity_id = $succes[1];

                    //functiegegevens
                    if ( (isset($contacts['persoonFunctie'][$i])) && (!empty($contacts['persoonFunctie'][$i])) ) {
                        //check if persoonFunctie exists in list persoonFunctie_lijst -> aanmaken
                        $t_item = $my_list->createListItem('persoonFunctie_lijst', trim($contacts['persoonFunctie'][$i]), $pn_locale_id);

                        if ($t_item) {
                            $container = 'persoonFunctie';
                            $functie = $t_list->getItemIDFromList('persoonFunctie_lijst', trim($contacts['persoonFunctie'][$i]));
                            $data = array('persoonFunctie'  => $functie,
                                        'locale_id'         => $pn_locale_id);
                            $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
                            $log->logInfo('persoonFunctie', $data);
                        }
                        unset($container);
                        unset($data);
                        unset($functie);
                    }
                    //adresgegevens
                    if ( (!empty($contacts['c_adres_straat'][$i])) || (!empty($contacts['c_adres_postalcode'][$i]))
                      || (!empty($contacts['c_adres_city'][$i])) || (!empty($contacts['c_adres_country'][$i]))
                      || (!empty($contacts['c_adresTelefoon'][$i])) ) {
                        $container = 'adres';
                        $data= array('adres_onbekend'    =>  $adres_onbekend,
                                    'adres_straat'      =>  $contacts['c_adres_straat'][$i],
                                    'adres_postalcode'  =>  $contacts['c_adres_postalcode'][$i],
                                    'adres_city'        =>  $contacts['c_adres_city'][$i],
                                    'adres_stateprovince'=> '',
                                    'adres_country'     =>  $contacts['c_adres_country'][$i],
                                    'adresOpmerking'    =>  'Contactinformatie',
                                    'adresTelefoon'     =>  $contacts['c_adresTelefoon'][$i],
                                    'adresEmail'        =>  '',
                                    'adresWebsite'      =>  '',
                                    'locale_id'         =>  $pn_locale_id);
                        $my_entity->addSomeEntityAttribute($entity_id, $container, $data);
                        $log->logInfo('adres', $data);
                    }
                    unset($container);
                    unset($data);
                }
                unset($succes);
            }
        }
    }
    $teller = $teller + 1;
    $reader->next();
}
$reader->close();

$log->logInfo("EINDE VERWERKING");