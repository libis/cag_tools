<?php
/**
 * =============================================================================
 * functies
 * =============================================================================
 */
class MyFunctions_new
{
    function idLocale($taal) {
        //initialiseren objecten read or write
        $t_locale = new ca_locales();
        $locale_id = $t_locale->loadLocaleByCode($taal);

        return $locale_id;
    }

    function setLogging() {
        $logDir = "/www/libis/vol03/lias_html/cag_tools-staging/shared/log/";
        $log = new KLogger($logDir, KLogger::DEBUG);

        return $log;
    }

    /**
    //inlezen (in array) mapping-bestand
    function setMapping($bestand, $o_myfunc) {
        $mapping = __CA_BASE_DIR__."/cag_tools/mapping/".$bestand;
        $mappingarray = $o_myfunc->ReadMappingcsv($mapping);
        return $mappingarray;
    }
     *
     */

    //inlezen configuratiebestand naar array
    function ReadMappingcsv($bestand) {
            $file = __MY_DIR_2__."/cag_tools/mapping/".$bestand;
            $data = array();
            if (($fh = fopen($file, "r")) !== FALSE) {
                $i = 0;
                while (($lineArray = fgetcsv($fh, 200, ';')) !== FALSE) {
                    for ($j=0; $j<count($lineArray); $j++) {
                        $data[$i][$j] = $lineArray[$j];
                    }
                    $i++;
                }
                fclose($fh);
            }
            return $data;
    }

    // inlezen XML-node naar array
    function ReadXMLnode($reader) {
            $dom = new DOMDocument;
            $node = simplexml_import_dom($dom->importNode($reader->expand(), true));
            $json = json_encode($node);
            $xmlarray = json_decode($json, TRUE);
            return $xmlarray;
    }

    //De XMLArray converteren naar Array met enkel benodigde gegevens
    function XMLArraytoResultArray($xmlarray,$mappingarray){

        //maken array van de te weerhouden tags (op basis van Mapping-bestand
        $hooiberg = array();
        for ($j = 1; $j <= count($mappingarray) - 1 ; $j++) {
                $hooiberg[] = $mappingarray[$j]['0'];
        }
        //print_r ($hooiberg);exit;

        //herwerken gegevens in $xmlarray tot $resultarray
        //met daarin enkel de benodigde gegevens (zie $hooiberg)
        //(->reeds gemapt naar juiste CA metadata-element)
        $resultarray = array();

        foreach ($xmlarray as $key => $value) {
            if (in_array($key, $hooiberg))
            {
                for ($j = 1; $j<= count($mappingarray) - 1 ; $j++) {
                    if (($mappingarray[$j][0]) == $key)
                    {
                        $new_key = $mappingarray[$j][1];
                    }
                }
                $resultarray[$new_key] = $value;
            }
        }

        return $resultarray;
    }

    function value_in_array($array, $find){
        $exists = FALSE;

        if(!is_array($array)){
            return;
       }

        foreach ($array as $value) {
            if (strstr(strtoupper($find),strtoupper($value))){
                $exists = TRUE;
            }
        }
        unset($value);
        return $exists;
    }

    function createList($listcode, $data, $locale) {
            $message = "";
            if (is_array($data))
            {
                foreach($data as $value)
                {
                    if (!(is_array($value)) && (!empty($value)) )
                    {
                        $message = $message.$this->createListItem($listcode, $value, $locale);
                    }
                }
            } else {
                if (!empty($data)){
                    $message = $this->createListItem($listcode, $data, $locale);
                }
            }
            return $message;
    }

    function createListItem($listcode, $data, $locale) {
            $t_list = new ca_lists();
            $t_list->load(array('list_code' => $listcode));

            $t_item = $t_list->getItemIDFromList($listcode, trim($data));

            if ($t_item){
                $message = "Item {$data}/{$t_item} bestaat reeds \n";
            }else{

                $t_item = $t_list->addItem(trim($data), true, false, null, null,
                                           trim($data),'', 4, 1);
                if (!$t_item){
                    $message = "Toevoegen item {$data} mislukt \n";
                } else {
                    //add preferred labels
                    if (!($t_item->addLabel(array(
                        'name_singular' => trim($data),
                        'name_plural'   => trim($data),
                        'description'   =>  ''
                    ),$locale, null, true ))) {
                            $message =  "ERROR ADD LABEL TO ".($data).": ".join("; ", $t_item->getErrors())."  \n  ";
                    }else{
                            $message =  "addlabel {$data} gelukt \n";
                    }
                }
            }
            return $message;
    }

    function createPlace($key, $parent, $place, $hierarchy, $locale, $log) {
        $t_place =  new ca_places_bis();
        //$parent = $t_place->getPlaceIDsByName($key);
        $t_place->setMode(ACCESS_WRITE);
        $t_place->set('parent_id', $parent);
        $t_place->set('locale_id', $locale);
        $t_place->set('type_id', $place);
        $t_place->set('source_id', NULL);
        $t_place->set('hierarchy_id', $hierarchy);
        $t_place->set('idno', trim($key));

        // insert the object
        $vn_rc = $t_place->insert();

        try {
            if ($t_place->numErrors()) {
                throw new Exception("ERROR INSERTING PLAATS".$key . join("; ", $t_place->getErrors())."\n");
            }
            // Set a preferred label for the object
            $t_place->addLabel(
                    array('name' => ($key)),
                    $locale, null, true
            );

            try {
                if ($t_place->numErrors()) {
                    throw new Exception("ERROR ADDING LABEL TO ".$key . join("; ", $t_place->getErrors())."\n");
                }
            } catch (Exception $e) {
                $log->logError($e->getMessage());
            }
        } catch (Exception $e) {
            $log->logError($e->getMessage());
        }
        unset($t_place);
        return $vn_rc;
    }

    function createEntity($Identificatie, $type, $status, $locale) {
        $t_entity = new ca_entities();
        $t_entity->setMode(ACCESS_WRITE);
        $t_entity->set('type_id', $type);
        $t_entity->set('idno', '');
        $t_entity->set('status', $status);
        $t_entity->set('access', 1);
        $t_entity->set('surname', $Identificatie);
        $t_entity->set('locale_id', $locale);

        $vn_rc = $t_entity->insert();

        if ($t_entity->numErrors())
        {       print "ERROR INSERTING {$Identificatie}: ".join('; ', $t_entity->getErrors())."<br/>";  }

        $t_entity->addLabel(array(
                'surname'     => $Identificatie,
                'displayname' => $Identificatie
                ),$locale, null, true );

        if ($t_entity->numErrors())
        {   print "ERROR ADD LABEL TO {$Identificatie}: ".join('; ', $t_entity->getErrors())."<br/>";   }

        return $vn_rc;

    }
    //lijkt niet te werken, maar waarom ??? -> te onderzoeken
    function createListItem2($listcode, $data, $locale) {
        $t_list = new ca_lists();
        $t_list->load(array('list_code' => $listcode));

        if (isNull($t_item = $t_list->getItemIDFromList($listcode, trim($data))))
        {
            try {
                $t_item = $t_list->addItem(trim($data), true, false, null, null,
                                       trim($data),'', 4, 1);
                try {
                    $t_item->addLabel(array(
                        'name_singular' => trim($data),
                        'name_plural'   => trim($data),
                        'description'   =>  ''
                    ),$locale, null, true );
                } catch (Exception $e){
                    echo 'addLabel mislukt';
                }
            }catch (Exception $e) {
                echo 'addItem mislukt';
            }
        }

    }

    # OKE
    function TweeTotSingleField($term, $type_term) {

        if (!empty($term)) {
            if (!empty($type_term)) {
                $result = trim($term)." (".(trim($type_term)).")";
            } else {
                $result = trim($term);
            }
        } else {
            if (!empty($type_term)) {
                $result = " (".(trim($type_term)).")";
            } else {
                $result = "";
            }
        }
        return $result;
    }

    # OKE
    function Herhalen($resultarray, $fields) {
        $maximum = 0;
        foreach($fields as $value)
        {
            $aantal = count($resultarray[$value]);
            if ($aantal > $maximum)
            {
                $maximum = $aantal;
            }
        }
        return $maximum;
    }

    function makeArray(&$resultarray, $fields) {
        foreach($fields as $value)
        {
            if ( (isset($resultarray[$value])) && (!is_array($resultarray[$value])) )
            {
                $waarde = $resultarray[$value];
                $resultarray[$value] = array($waarde);
            }
        }
    }
    # OKE
    function makeArray2($resultarray, $aantal, $fields) {

        $resultarray2 = array();

        $aantal = $aantal - 1;

        foreach($fields as $value) {
            if ( (isset($resultarray[$value])) && (!is_array($resultarray[$value])) ) {
                    $resultarray2[$value][0] = $resultarray[$value];
                    for ($i= 1; $i <= $aantal; $i++) {
                        $resultarray2[$value][$i] = "";
                    }
            }

            if ( (isset($resultarray[$value])) && (is_array($resultarray[$value])) )
            {
                for ($i= 0; $i <= $aantal; $i++) {
                    if (!empty($resultarray[$value][$i])) {
                        $resultarray2[$value][$i] = $resultarray[$value][$i];
                    } else {
                        $resultarray2[$value][$i] = "";
                    }
                }
            }
            if ( (!isset($resultarray[$value])) ) {
                for ($i= 0; $i <= $aantal; $i++) {
                    $resultarray2[$value][$i] = "";
                }
            }
        }

        return $resultarray2;
    }

    function cleanDate($string, $type) {
        if ($type == "links") {
            $zoek = array('(moeilijk leesbaar)', 'moeilijk leesbaar', 'exact',
                          'kort na', 'of kort na', 'of iets vroeger', 'of later',
                          'vroeger dan');
        } elseif ($type == "rechts") {
            $zoek = array('circa', 'ongeveer', 'exact', );
        } elseif ($type == "geen") {
            return $string;
        } else {
            throw new Exception("type 'links' of 'rechts' is vereist" );
        }
        return trim(str_replace($zoek,'',$string));
    }

    function stringJoin($string1, $string2, $delimit, $type) {
        $stringResult = '';
        if (is_array($string1)) {       $string1 = $string1['0'];         }
        if (is_array($string2)) {       $string2 = $string2['0'];         }

        $string1 = $this->cleanDate($string1, $type);
        //$string2 = trim(str_replace($zoek,$vervang,$string2));

        if ( (isset($string1)) && (!empty($string1)) && (isset($string2)) && (!empty($string2)) )
        {
            $stringResult = $string1.$delimit.$string2;
        }
        if ( (isset($string1)) && (!empty($string1)) && (empty($string2)) )
        {
            $stringResult = $string1;
        }
        if ( (isset($string2)) && (!empty($string2)) && (empty($string1)) )
        {
            $stringResult = $string2;
        }
        if ( (empty($string1)) && (empty($string2)) )
        {
            $stringResult = '';
        }
        return $stringResult;
    }

    # OKE
    function Initialiseer($variabelen){
        if (is_array($variabelen)) {

            foreach ($variabelen as $value) {
                return $$value = "";
            }
        }
    }

    # OKE
    function Vernietig($variabelen){
        if (is_array($variabelen)) {

            foreach ($variabelen as $value) {
                unset($$value);
                return;
            }
        }
    }

/*
    function createContainer($object, $data, $info, $container){

        try {
            $object->addAttribute($data, $container);

            $object->update();

            if ($object->numErrors()) {
                throw new Exception("ERROR UPDATING {$container} / {$info}  ".
                           join('; ', $object->getErrors()));
            }
            $message =  "SUCCESS";

        } catch (Exception $e) {
            echo $e->getMessage();
            $message = "FAILURE";
        }
        return $message ;
    }
 *
 */

    function createContainer($object, $data , $info, $container){

        $object->addAttribute($data, $container);

        $object->update();

        if ($object->numErrors()) {
               $message =  "ERROR UPDATING {$container} / {$info} ".
                       join('; ', $object->getErrors())."\n";
        }else{
               $message = "update {$container} / {$info} gelukt \n";
        }
        return $message;
    }


    function createRelationship($object, $right, $vs_right_string, $relationship) {
        #1 documentatie + container: regPaginaInfo
        if ($right == "ca_occurrences") {
            $t_occur = new ca_occurrences();
            $va_right_keys = $t_occur->getOccurrenceIDsByName($vs_right_string);
        }
        #2 vervaardiger
        if ($right == "ca_entities"){
            $t_entity = new ca_entities();
            $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);
        }
        #3 vervaardiging

        #4 trefwoorden
        if ($right == "ca_objects_x_vocabulary_terms") {
            $t_list = new ca_lists();
            $va_right_keys[0] = $t_list->getItemIDFromList('cag_trefwoorden', $vs_right_string);
        }
        #5 collecties
        if ($right == "ca_collections") {
            $t_collect = new ca_collections();
            $va_right_keys = $t_collect->getCollectionIDsByName($vs_right_string);
        }
        #6 bewaarinstelling
        # $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);

        #7 inventarisnummer
        # $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);

        #8 verworven
        # $va_right_keys = $t_entity->getEntityIDsByName('', $vs_right_string);

        #9 related
        if ($right == "ca_objects") {
            $t_obj = new ca_objects_bis();
            $t_obj->getObjectIDsByElementID($vs_right_string, 'adlibObjectNummer');
        }

        if ((sizeof($va_right_keys)) == 0 ) {
            $message =  "ERROR: PROBLEMS with {$right} / {$vs_right_string} : bestaat niet !!!!! \n";
            #object aanmaken
        }elseif ((sizeof($va_right_keys)) >= 1 ){
            $message = "";
            if ((sizeof($va_right_keys)) > 1 ) {
                $message =  "WARNING: PROBLEMS with {$right} / {$vs_right_string} : meerdere records gevonden: nemen het eerste !!!!! \n";
            }
            $vn_right_id = $va_right_keys[0];

            $object->addRelationship($right, $vn_right_id, $relationship);

            if ($object->numErrors()) {
                $message = $message. "ERROR LINKING object and occurrence: " . join(';', $object->getErrors()) . " \n ";
            }else{
                $message = $message."SUCCESS: relation with {$right}/{$vn_right_id} succesfull \n";
            }
            return $message;
        }
    }

    function check_input($key, $input)
    {
    #19 //input kan op zijn beurt een 'lege' array bevatten -> opvangen en veralgemenen
        // het plaatsen van de ; achteraan wordt van hoofdprogr naar hier gebracht
        if ( (is_array($input)) and (empty($input)) )
        {   $input = '';    }

        if (($key == '$adresWebsite') and (substr($input,0,7) != 'http://'))
        {
            $input = 'http://'.$input;
        } else {
            $input = $input;
        }
        $input = $input.';';

        return $input;
    }

    /**
    * Checks date if matches given format and validity of the date.
    * Examples:
    * <code>
    * is_date('22.22.2222', 'mm.dd.yyyy'); // returns false
    * is_date('11/30/2008', 'mm/dd/yyyy'); // returns true
    * is_date('30-01-2008', 'dd-mm-yyyy'); // returns true
    * is_date('2008 01 30', 'yyyy mm dd'); // returns true
    * </code>
    * @param string $value the variable being evaluated.
    * @param string $format Format of the date. Any combination of <i>mm<i>, <i>dd<i>, <i>yyyy<i>
    * with single character separator between.
    */
    function is_valid_date($value, $format = 'dd.mm.yyyy'){
        if(strlen($value) >= 6 && strlen($format) == 10){

            // find separator. Remove all other characters from $format
            $separator_only = str_replace(array('m','d','y'),'', $format);
            $separator = $separator_only[0]; // separator is first character

            if($separator && strlen($separator_only) == 2){
                // make regex
                $regexp = str_replace('mm', '(0?[1-9]|1[0-2])', $format);
                $regexp = str_replace('dd', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('yyyy', '(19|20)?[0-9][0-9]', $regexp);
                $regexp = str_replace($separator, "\\" . $separator, $regexp);
                if($regexp != $value && preg_match('/'.$regexp.'\z/', $value)){

                    // check date
                    $arr=explode($separator,$value);
                    $day=$arr[0];
                    $month=$arr[1];
                    $year=$arr[2];
                    if(@checkdate($month, $day, $year))
                        return true;
                }
            }
        }
        return false;
    }
}

