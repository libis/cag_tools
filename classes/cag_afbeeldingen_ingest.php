<?php
/* Dit script wordt gebruikt om afbeeldingen van Digitool in te laden in CA na ingest. de Url wordt samengesteld in dit script
*/
/*
 * Step 1: Initialisation
 */
set_time_limit(36000);
include('header.php');

require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once('ca_objects_bis.php');

require_once(__CA_LIB_DIR__.'/core/Parsers/DelimitedDataParser.php');


$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/en_US/messages.mo', 'nl_NL');

$t_locale = new ca_locales();
$pn_locale_id = $t_locale->loadLocaleByCode('nl_NL');

print "IMPORTING afbeeldingen\n";

/*
// * Step 2: Import
*/

 // want to parse comma delimited data? Pass a comma here instead of a tab.
$o_tab_parserAfbeeldingen = new DelimitedDataParser("\t");

// Read csv; line by line till end of file.
if (!$o_tab_parserAfbeeldingen->parse(__MY_DATA__ . 'afbeeldingen.csv')) {
	die("Couldn't parse afbeeldingen.csv data\n");
}

print "READING afbeeldingen.csv...\n";

$afbeeldingen = array();

//-------------------------
// waarden inlezen
//-------------------------
// Er wordt vanuit gegaan dat er geen headers zijn
while($o_tab_parserAfbeeldingen->nextRow()) {
	// Get columns from tab file and put them into named variables - makes code easier to read
	$label			=	$o_tab_parserAfbeeldingen->getRowValue(1);
	$pid			=	$o_tab_parserAfbeeldingen->getRowValue(2);

	if(!empty($pid) && !empty($label)) {
		$afbeeldingen[$label] = $pid;
	} else {
	  echo "Problem adding " .$label . " and Pid: " . $pid;
	}
}

print "\n Creating afbeelding voor ".$label." \n";

	// label en idno moeten nog gematcht worden
	// kunstvoorwerp_idno loop vervangen door opzoeken van label

$t_object = new ca_objects_bis();

foreach($afbeeldingen as $label_key => $pid_value)
{
	if(strpos($label_key,'_') !== false){
		$temp =  explode("_", $label_key);
		
		if(count($temp) > 2 || count($temp) < 1 ) {
			print "Meer dan/Minder dan 1 underscore gevonden: " . $label_key;
			print "\n lookup wordt toch uitgevoerd";
		} 
		$object_ids = $t_object->getObjectIDsByIdno($temp[0]);
	} else {
		$object_ids = $t_object->getObjectIDsByIdno($label_key);
	}
	if(!empty($object_ids) && !empty($pid_value))
	{
		$AUTH_CURRENT_USER_ID = 'administrator';
		$t_object->setMode(ACCESS_WRITE);

		$t_object->load($object_ids[0]);
		if (trim($pid_value)) {
				$t_object->addAttribute(array(
					'locale_id'	=>	$pn_locale_id,
				        'digitoolUrl'	=>	trim($pid_value)
			                 ), 'digitoolUrl');
		}

		$t_object->update();
		
		if ($t_object->numErrors()) {
			print "\tERROR UPDATING {$object_ids[0]}/{$pid_value}: ".join('; ', $t_user->getErrors())."\n";
			continue;
		} else {
			print "\n toevoegen van afbeelding aan object : " . $label_key ." / ". $lookup. " gelukt";
		}

	} else {
		print "\nGeen object gevonden voor koppelen " . $lookup;
	}
}
print "END IMPORTING afbeeldingen.csv\n";

?>
