<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once __MY_DIR__.'/cag_tools/classes/UserErrors.php';
//require_once __MY_DIR__.'/cag_tools/classes/Logger.php';
/**
 * Description of UserException
 *
 * @author AnitaR
 */
class UserException extends Exception {

    public function __construct($error_code)
   {
      parent::__construct(UserErrors::getErrorMessage($error_code), $error_code);
      //Logger::newMessage($this);
   }
}