<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EntitiesUitObjecten
 *
 * @author AnitaR
 */
class EntitiesUitObjecten {

    public function createEntitiesArray($resultarray, $fields, &$array)
    {
        global $t_func;
        global $log;

        $aantal = $t_func->Herhalen($resultarray, $fields);

        if ($aantal > 0) {
            $output = $t_func->makeArray2($resultarray, $aantal, $fields);

            foreach ($output as $veld) {
                foreach ($veld as $value) {
                    if (!is_array($value)) {
                        if ( (isset($value)) && (!empty($value)) ) {
                            $sleutel = $t_func->cleanUp($value);
                            $array[$sleutel] = $value;
                        }
                    } else {
                        $log->logError("ERROR: het datatype (array) kan niet verwerkt worden", $value);
                    }
                }
            }
        }
    }
}