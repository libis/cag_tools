<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 25/04/14
 * Time: 11:16
 */

include('../classesRestCookie/header_sandbox.php');

require_once('../classesRestCookie/GuzzleRestCookie.php');

$t_guzzle = new GuzzleRestCookie(__INI_FILE__);
$t_relation = new ca_relationship_types();

$relation_id = $t_relation->getRelationshipTypeID('ca_entities_x_collections', 'bewaarinstellingrelatie');

$json_file = "../data/json_voeding_plus.txt";
$json_collections = file_get_contents($json_file);
$data_plus = json_decode($json_collections, TRUE);

$json_rel_file = "../data/json_related_voeding.txt";
$json_related_collections = file_get_contents($json_rel_file);
$data_related_plus = json_decode($json_related_collections, TRUE);

$teller = sizeof($data_plus) -1 ;

echo "Begin\n";

for($i=0; $i <= $teller; $i++ ) {
    echo "=====" . $i . "===================================================================\n";

    $update = $data_plus[$i];
    $name = $update['preferred_labels'][0]['name'];

    echo "Aanmaak collectie : " . $name . "\n";

    $data = $t_guzzle->createObject($update, 'ca_collections');

    if (isset($data['ok']) && !isset($data['collection_id'])) {

        echo "ERROR ERROR:   aanmaak collectie mislukt \n";
        echo "Reden: " . $data['errors'][0] . "\n";

    } else {
        # aanmaak collectie gelukt
        # collection_id registreren
        $collection_id = $data['collection_id'];

        echo "Aanmaak collectie gelukt, collectie_id: " . $collection_id . "\n";

        $count = sizeof($data_related_plus[$i]['related']['ca_entities']) - 1;

        for($j=0; $j <= $count; $j++) {

            $entity_name = $data_related_plus[$i]['related']['ca_entities'][$j]['displayname'];

            $query = "ca_entity_labels.displayname:'" . $entity_name . "'";

            $entity = $t_guzzle->findObject($query, 'ca_entities');

            if (isset($entity['ok']) && !isset($entity['results'])) {

                echo "ERROR ERROR:  entiteit " . $entity_name . " niet gevonden \n";
                echo "Relatie tussen collectie (" . $collection_id . " / " . $update['preferred_labels'][0]['name'] . ") en entiteit (" . $entity_name . ") niet gelegd \n";

            } else {

                if (sizeof($entity['results']) > 1) {

                    echo "ERROR ERROR:   Meer dan 1 kandidaat gevonden voor entiteit " . $entity_name . "\n";
                    echo "Relatie tussen collectie (" . $collection_id . " / " . $update['preferred_labels'][0]['name'] . ") en entiteit (" . $entity_name . ") niet gelegd \n";

                } else {

                    echo 'Entity gevonden: '. $entity_name . " \n";

                    $entity_id = $entity['results'][0]['entity_id'];

                    $related['related']['ca_entities'][] =
                        array(
                            'entity_id'         =>  $entity_id,
                            'type_id'           =>  $relation_id
                        );

                    $data2 = $t_guzzle->updateObject($related, $collection_id, 'ca_collections');


                    if (isset($data2['ok']) && !isset($data2['collection_id'])) {

                        echo "ERROR ERROR:   aanmaak relatie collectie/entiteit mislukt \n";
                        echo "Relatie tussen collectie (" . $collection_id . " / " . $update['preferred_labels'][0]['name'] . ") en entiteit (" . $entity_name . ") niet gelegd \n";

                    } else {

                        echo "Relatie gelegd \n";

                    }

                }
            }
        }
    }
    unset($update);
    unset($related);
}

echo "========================================================================\n";
echo "the end" ;