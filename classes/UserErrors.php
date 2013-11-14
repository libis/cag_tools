<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MyExceptions
 *
 * @author AnitaR
 */
class UserErrors {

    const INVALIDLOCAL      = 10001;
    const VALUEISARRAY      = 10002;
    const LISTITEMEXISTS    = 10003;
    const ADDLISTITEMMISLUKT      = 10004;
    const ADDLABEL_LISTITEM_MISLUKT       = 10005;
    const UNEXPECTEDERROR   = 10006;

    public static function getErrorMessage($code)
    {
       switch($code) {
          case self::INVALIDLOCAL:
             return 'Local_id is leeg';
             break;

          case self::VALUEISARRAY:
             return 'Ongeldig datatype: Variabele is een array!';
             break;

          case self::LISTITEMEXISTS:
             return 'List Item bestaat reeds!';
             break;

          case self::ADDLISTITEMMISLUKT:
             return 'AddItem van ListItem mislukt!';
             break;

          case self::ADDLABEL_LISTITEM_MISLUKT:
             return 'AddLabel van ListItem mislukt!';
             break;

          case self::UNEXPECTEDERROR:
          default:
             return 'An unexpected error has occurred';
             break;
       }
    }
}
