<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 16/06/14
 * Time: 13:44
 */

class myRestFunctions {

    public function __construct() {
        $this->guzzle = new GuzzleRest();
    }

    function createRelationship($right, $right_string, $relatie, &$update) {

        $t_rel_types = new ca_relationship_types();

        $key_new = trim(str_replace(array('vzw', '-'), array('', ' '), $right_string));

        if ($right == "ca_occurrences") {
            echo 'TODO';

            $id_type = 'occurrence_id';
            $type_id = $t_rel_types->getRelationshipTypeID('ca_objects_x_occurrences', $relatie);

        } elseif ($right === "ca_entities"){
            $query = "ca_entities.preferred_labels:'".trim($key_new)."'";
            $data = $this->guzzle->findObject($query, 'ca_entities');

            $id_type = 'entity_id';
            $type_id = $t_rel_types->getRelationshipTypeID('ca_objects_x_entities', $relatie);

        } elseif ($right == "ca_collections") {
            echo 'TODO';

            $id_type = 'collection_id';
            $type_id = $t_rel_types->getRelationshipTypeID('ca_objects_x_collections', $relatie);

        } elseif ($right == "ca_objects") {
            $query = "ca_objects.objectInventarisnrBpltsInfo.objectInventarisnrBplts:\"".trim($key_new)."\"";
            $data = $this->guzzle->findObject($query, 'ca_objects');

            $id_type ='object_id';
            $type_id = $t_rel_types->getRelationshipTypeID('ca_objects_x_objects', $relatie);

        }

        if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
            if (sizeof($data['results']) > 1) {
                echo "Meer dan 1 kandidaat gevonden voor ". $key_new . "\n";
                $id = $this->juisteTerm($data, $key_new, $id_type);
                //$data2 = $this->guzzle->getObject($id, $id_type);

            } else {
                echo 'Gevonden: '. $key_new . " | " . $id . " \n ";
                $id = $data['results'][0][$id_type];
                //$data2 = $this->guzzle->getObject($id, $id_type);
            }

            $update['related'][$right][] =
                array(
                    $id_type         =>  $id,
                    'type_id'   =>  $type_id
                );

        } else {

            echo "ERROR: niet gevonden: " . $key_new . " bestaat (nog) niet\n";
            echo "TODO: aanmaken on the fly";
        }
    }

    function juisteTerm($data, $key_new, $id_type) {
        //$term = $data['results'][0]['display_label'];

        foreach($data['results'] as $value) {
            if (strtoupper($value['display_label']) === strtoupper($key_new)) {
                $id = $value[$id_type];
                return $id;
            }
        }

        return '';

    }

} 