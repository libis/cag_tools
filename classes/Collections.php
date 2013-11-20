<?php
/**
 * Description of Collections
 *
 * @author AnitaR
 */
class Collections {

    public function createCollectionArray($value, &$collectie, $locale) {

        global $t_func;
        global $log;

        $sleutel = "";
        if (!is_array($value)) {
            if ( (isset($value)) && (!empty($value)) ) {
                //$sleutel = $t_func->cleanUp($value);
                $sleutel = $t_func->generateSortValue($value, $locale);
                $collectie[$sleutel] =  $value;
            }
        } else {
            $log->logError("ERROR: het datatype (array) kan niet verwerkt worden", $value);
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
