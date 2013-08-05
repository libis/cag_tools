<?php
/* Doel van dit programma:
 *  entities aanmaken op basis van de velden uit objecten.xml en sinttruiden.xml
 *  dient uitgevoerd VOOR het aanmaken van de objecten
 *  Werkwijze: de velden worden de sleutels van een array
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

define("__PROG__","entities_relaties");
//require_once(__MY_DIR_2__."/cag_tools/classess/My_CAG.php");
require_once(__MY_DIR__."/ca_cag/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__CA_MODELS_DIR__ . '/ca_entities_x_entities.php');
require_once("/www/libis/vol03/lias_html/cag_tools-staging/shared/log/KLogger.php");
//require_once(__CA_LIB_DIR__."/core/Logging/KLogger/KLogger.php");
require_once(__MY_DIR_2__."/cag_tools/classes/ca_places_bis.php");

include __MY_DIR_2__."/cag_tools/classes/MyFunctions_new.php";

$t_func = new MyFunctions_new();
$pn_locale_id = $t_func->idLocale("nl_NL");
$log = $t_func->setLogging();

$t_list = new ca_lists();

$t_entity = new ca_entities();
$t_entity->setMode(ACCESS_WRITE);

$t_rel_types = new ca_relationship_types();
$vn_entities_x_entities = $t_rel_types->getRelationshipTypeID('ca_entities_x_entities', 'contact');

$teller = 1;

//==============================================================================initialisaties
//inlezen (in array) mapping-bestand
//inlezen (in array) mapping-bestand
$mappingarray = $t_func->ReadMappingcsv("cag_entities_mapping.csv");

//inlezen xml-bestand met XMLReader, node per node
$reader = new XMLReader();
$reader->open(__MY_DIR_2__."/cag_tools/data/entities.xml");

while ($reader->read() && $reader->name !== 'record');
//==============================================================================begin van de loop
while ($reader->name === 'record' ) {
    //node omvormen tot associatieve array
    $xmlarray = $t_func->ReadXMLnode($reader);

    $resultarray = $t_func->XMLArraytoResultArray($xmlarray,$mappingarray);

    $log->logInfo('====='.$teller.'=====');

    $fields = array('c_contact', 'persoonFunctie', 'c_adres_straat', 'c_adres_postalcode',
                    'c_adres_city', 'c_adres_country', 'c_adresTelefoon' );
    $aantal_contact = $t_func->Herhalen($resultarray, $fields);

    if ($aantal_contact > 0) {
        $contacts = $t_func->makeArray2($resultarray, $aantal_contact, $fields);

        if ( (isset($resultarray['use']))  && (!empty($resultarray['use'])) ) {
            $resultarray['preferred_label'] = $resultarray['use'];
        }
        $fields_pref = array('preferred_label');
        $aantal_pref = sizeof($resultarray['preferred_label']);
        $pref = $t_func->makeArray2($resultarray, $aantal_pref, $fields_pref);

        if ( (isset($pref['preferred_label'][0])) && (!empty($pref['preferred_label'][0])) ) {
                    $Identificatie = $pref['preferred_label'][0];
        } else {
            $Identificatie = '---???-'.$resultarray['adlibObjectNummer'].'-???--- ';
        }
        $vs_left_string = $Identificatie;
        $va_left_keys = $t_entity->getEntityIDsByName('', $vs_left_string);
        $aantal_left = sizeof($va_left_keys);

        if ($aantal_left >= 1) {
            if ($aantal_left > 1) {
                $log->logWarn("WARNING: problems with entity, meerdere gevonden.  We nemen het eerste: ", $vs_left_string);
            }

            $vn_left_id = $va_left_keys[0];

            $aantal = $aantal_contact - 1 ;

            for ($i=1; $i <= $aantal; $i++) {

                $t_entity->load($vn_left_id);
                $t_entity->getPrimaryKey();
                $t_entity->set('entity_id', $vn_left_id);

                if (!empty($contacts['c_contact'][$i])) {
                    $vs_right_string = trim($contacts['c_contact'][$i]);
                    print 'relatie leggen tussen ' . ($vs_left_string) . '  en   ' . ($vs_right_string) . '  <br />  ';
                    $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);
                    $aantal_right = sizeof($va_right_keys);
                    $verder = 1;

                    if ($aantal_right >= 1) {
                        if ($aantal_right > 1 ) {
                            $log->logWarn("WARNING: problems with entity, meerdere kandidatengevonden.  We nemen de eerste: ", $vs_right_string);
                        }

                        $vn_right_id = $va_right_keys[0];
                        $t_entity->addRelationship('ca_entities', $vn_right_id, $vn_entities_x_entities);

                        if ($t_entity->numErrors()) {
                            $log->logError("ERROR CREATING relationship: ", $vs_left_string." en ".$vs_right_string);
                            $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                            $verder = 0;
                            continue;
                        } else {
                            $log->logInfo("CREATING relationship gelukt: ", $vs_left_string." en ".$vs_right_string);
                            //print "link entity-entity succesvol  <br />";
                            $verder = 1;
                        }
                    } else {
                        $log->logError("ERROR: problems with entity, GEEN kandidatengevonden. ", $vs_right_string);
                        $verder = 0;
                    }

                }

                if ($verder == 1) {
                    //we laden de 'right' entity
                    $t_entity->load($vn_right_id);
                    $t_entity->getPrimaryKey();
                    $t_entity->set('entity_id', $vn_right_id);

                    if ( (isset($contacts['persoonFunctie'])) && (!empty($contacts['persoonFunctie'])) &&
                        $contacts['persoonFunctie'][0] != "" ) {

                        $t_list->clear();
                        $t_list->load(array('list_code' => 'persoonFunctie_lijst'));

                        $t_item = $t_list->getItemIDFromList('persoonFunctie_lijst', $contacts['persoonFunctie'][0]);

                        if ($t_item) {
                            $log->logInfo("Deze LABEL bestaat reeds in LIST persoonFunctie_lijst: ", $contacts['persoonFunctie'][0]);
                        }else{
                            $t_item = $t_list->addItem($contacts['persoonFunctie'][0], true, false, null, null,
                                                       $contacts['persoonFunctie'][0],'', 4, 1);
                            if ($t_item){
                                $log->logInfo("ITEM added tot LIST persoonFunctie_lijst: ", $contacts['persoonFunctie'][0]);
                                //add preferred labels
                                if (!($t_item->addLabel(array(
                                    'name_singular' => $contacts['persoonFunctie'][0],
                                    'name_plural'   => $contacts['persoonFunctie'][0],
                                    'description'   =>  ''
                                    ),$pn_locale_id, null, true ))) {
                                        $log->logError("ERROR ADDING LABEL TO LIST persoonFunctie_lijst: ", $contacts['persoonFunctie'][0]);
                                        $log->logError("ERROR messages: ",join('; ', $t_item->getErrors()));
                                        continue;
                                }else{
                                        $log->logInfo("LABEL added to LIST persoonFunctie_lijst: ", $contacts['persoonFunctie'][0]);
                                }
                            }else{
                                $log->logError("ERROR ADDING ITEM TO LIST persoonFunctie_lijst: ", $contacts['persoonFunctie'][0]);
                                $log->logError("ERROR messages: ",join('; ', $t_list->getErrors()));
                                continue;
                            }
                        }

                        if ($t_item) {
                            $t_entity->addAttribute(array(
                                'persoonFunctie'    => $t_list->getItemIDFromList('persoonFunctie_lijst', $contacts['persoonFunctie'][0]),
                                'locale_id'         => $pn_locale_id
                             ), 'persoonFunctie');
                        } else {
                                $log->logError("ERROR: addAttribute persoonFunctie", $contacts['persoonFunctie'][0]);
                        }
                    }

                    //adresgegevens
                    if ( (!empty($contacts['c_adres_straat'][$i])) || (!empty($contacts['c_adres_postalcode'][$i]))
                      || (!empty($contacts['c_adres_city'][$i])) || (!empty($contacts['c_adres_country'][$i]))
                      || (!empty($contacts['c_adresTelefoon'][$i])) ) {
                        $t_entity->addAttribute(array(
                            'adres_onbekend'    => $t_list->getItemIDFromList('adres_onbekend_lijst', 'ja'),
                            'adres_straat'      => $contacts['c_adres_straat'][$i],
                            'adres_postalcode'  => $contacts['c_adres_postalcode'][$i],
                            'adres_city'        => $contacts['c_adres_city'][$i],
                            'adres_stateprovince' => "",
                            'adres_country'     => $contacts['c_adres_country'][$i],
                            'adresOpmerking'    => 'Contactinformatie',
                            'adresTelefoon'     => $contacts['c_adresTelefoon'][$i],
                            'adresEmail'        => "",
                            'adresWebsite'      => "",
                            'locale_id'         => $pn_locale_id
                        ), 'adres');
                    }

                    //-------------
                    $t_entity->update();
                    //-------------

                    if ($t_entity->numErrors()) {
                        $log->logError("ERROR ADDING attributes to: ", $vn_right_id);
                        $log->logInfo("ERROR message(s) ", join('; ', $t_entity->getErrors()) );
                        continue;
                    } else {
                        $log->logInfo("ADDING attributes gelukt: ", $vn_right_id);;
                    }
                }
            }
        }
    }

    $teller = $teller + 1;
    $reader->next();
}

$reader->close();

$log->logInfo("EINDE VERWERKING");