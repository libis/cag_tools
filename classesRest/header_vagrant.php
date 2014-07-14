<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 19/06/14
 * Time: 14:11
 */
error_reporting(E_ALL ^ E_STRICT);
set_time_limit(0);
$_SERVER['SCRIPT_FILENAME'] =  "/var/www/html/ca_cag/index.php";

define("__MY_DIR__", $_SERVER['DOCUMENT_ROOT']);
define("__MY_DATA__", "../data/");

require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_objects.php");

require_once(__CA_MODELS_DIR__."/ca_lists.php");

require_once("/var/www/html/cag_tools-staging/shared/log/KLogger.php");
define("__LOG_DIR__", "/var/www/html/cag_tools-staging/shared/log/");

//include __MY_DIR__."/cag_tools/Classes/MyFunctions_new.php";