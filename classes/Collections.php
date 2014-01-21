<?php
/**
 * Description of Collections
 *
 * @author AnitaR
 */
class Collections
{

    public function createCollectionArray($value, $velden, &$collectie, $locale) {

        global $t_func;

        $aantal = $t_func->Herhalen($value, $velden);

        if ($aantal > 0) {
            //we vormen alle elementen om tot een array van dezelfde vorm
            $array = $t_func->makeArray2($value, $aantal, $velden);
            $n_aantal = $aantal - 1;
            $i = 0;
            for ($i=0; $i <= ($n_aantal); $i++) {

                $sleutel = "";
                if ( (isset($array[$velden[0]][$i])) && (!empty($array[$velden[0]][$i])) ) {
                    //$sleutel = $t_func->cleanUp($value);
                    $sleutel = $t_func->generateSortValue($array[$velden[0]][$i], $locale);
                    $collectie[$sleutel] =  $array[$velden[0]][$i];
                }
            }
        }
    }

    public function insertCollection($type, $idno, $status, $access, $locale, $name)
    {
        global $log;

        $t_collect = new ca_collections();
        $t_collect->setMode(ACCESS_WRITE);
        $t_collect->set('type_id', $type);
        $t_collect->set('idno', $idno);
        $t_collect->set('status', $status); //workflow_statuses
        $t_collect->set('access', $access); //1=accessible to public
        $t_collect->set('locale_id', $locale);
        //----------
        $vn_rc = $t_collect->insert();
        //----------

        if ($t_collect->numErrors()) {
            $log->logError("ERROR INSERTING COLLECTION " . $name . join("; ", $t_collect->getErrors()) . "\n");
        } else {
            $log->logInfo("insert gelukt voor collectie: ", $name);

            $t_collect->addLabel(array(
                    'name'      => $name
            ),$locale, null, true );

            if ($t_collect->numErrors()) {
                $log->logError("ERROR ADDING LABEL TO COLLECTION " . $name . join("; ", $t_collect->getErrors()) . "\n");
            } else {
                $log->logInfo("AddLabel gelukt voor collectie: ", $name);
            }
        }
        unset($t_collect);
        return $vn_rc;
    }
}
