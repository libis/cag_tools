<?php

/**
 * Doel van dit programma:
 * Op basis van het objecten.xml-bestand, (en sinttruiden.xml)
 * worden de ca_lists  cag_thesaurus en cag_trefwoorden aangemaakt.
 *
 * @author AnitaR <anita.ruijmen@libis.kuleuven.be>
 */

class Lists
{
    public function createCAGList($xml, $gegeven, $lijst, $pn_locale_id, $mappingarray)
    {
        global $log;
        global $t_func;

        $teller2 = 1;

        //inlezen xml-bestand met XMLReader, node per node
        $reader2 = new XMLReader();
        $reader2->open(__MY_DIR__."/cag_tools/data/".$xml);

        while ($reader2->read() && $reader2->name !== "record");
        //=====================================================begin van de loop
        while ($reader2->name === 'record' ) {
            $xmlarray = $t_func->ReadXMLnode($reader2);

            $resultarray = $t_func->XMLArraytoResultArray($xmlarray, $mappingarray);

            $log->logInfo("=====" . $teller2 . "=====");
            $log->logInfo("Originele data: ", $resultarray[$gegeven]);
            //cag_thesaurus: list_id = 37
            if ( (isset($resultarray[$gegeven])) && (!empty($resultarray[$gegeven])) ) {

                $aantal_on = $t_func->Herhalen($resultarray, array($gegeven));

                $res_on = $t_func->makeArray2($resultarray, $aantal_on, array($gegeven));

                $log->logInfo("Bewerkte data, ", $res_on[$gegeven]);

                $aantal = $aantal_on - 1 ;

                for ($i=0; $i <= $aantal; $i++){

                    if (!empty($res_on[$gegeven][$i])) {

                        try {
                            $this->createListItem($lijst, $res_on[$gegeven][$i], $pn_locale_id);
                            $log->logInfo("ListItem toegevoegd: ", $res_on[$gegeven][$i]);

                        } catch (UserException $e) {
                            $log->logError("exception caught:", $e->getMessage());
                        }
                    }
                }
                unset($aantal_on);
                unset($res_on);
                unset($aantal);
            } else {
                $log->logInfo("GEEN DATA BESCHIKBAAR voor {$gegeven} in dit record. \n ");
            }
            unset($res_on);
            $teller2 = $teller2 + 1;
            $reader2->next();
        }
        $reader2->close();
        $log->logInfo("EINDE VERWERKING {$xml} \n");

        unset($teller2);
        unset($reader2);
        unset($xmlarray);
        unset($resultarray);

    }

    public function createListItem($listcode, $data, $locale) {

        global $log;

        $t_list = new ca_lists();
        $t_list->load(array('list_code' => $listcode));

       $listitem = $t_list->getItemIDFromList($listcode, trim($data));

        if (!$listitem){

            $t_item = $t_list->addItem(trim($data), true, false, null, null,
                                       trim($data),'', 4, 1);
            if (!$t_item) {
                throw new UserException(UserErrors::ADDLISTITEMMISLUKT);
                $log->logError("ERROR ADDLISTITEM", $t_list->getErrors());
            } else {
                //add preferred labels
                if (!($t_item->addLabel(array(
                    'name_singular' => trim($data),
                    'name_plural'   => trim($data),
                    'description'   =>  ''
                ),$locale, null, true ))) {
                    throw new UserException(UserErrors::ADDLABEL_LISTITEM_MISLUKT);
                    $log->logError("ERROR ADDLABEL TO LISTITEM", $t_item->getErrors());
                }
            }
        }

        $listitem_id = $t_list->getItemIDFromList($listcode, trim($data));

        return $listitem_id;
    }

}