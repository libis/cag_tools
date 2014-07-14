<?php
/**
 * Created by PhpStorm.
 * User: AnitaR
 * Date: 10/06/14
 * Time: 14:19
 */

define("__PROG__","objecten_karrenmuseum_vervolg");

include('header_loc.php');
$log = new KLogger(__LOG_DIR__, KLogger::DEBUG);
$AUTH_CURRENT_USER_ID = 'administrator';

//require_once(__CA_MODELS_DIR__."/ca_objects.php");
require_once(__CA_LIB_DIR__ . '/core/Parsers/DelimitedDataParser.php');
require_once("GuzzleRest.php");

$t_locale = new ca_locales();
$locale_id = 'nl_NL';

$t_list = new ca_lists();

$t_guzzle = new GuzzleRest();

// want to parse comma delimited data? Pass a comma here instead of a tab.
$o_tab_parser = new DelimitedDataParser("\t");

print "IMPORTING objecten karrenmuseum \n";

if (!$o_tab_parser->parse(__MY_DATA__ . "karrenmuseum/Objecten.csv")) {
    die("Couldn't parse Objecten karrenmuseum data\n");
}

$vn_c = 1;

$o_tab_parser->nextRow(); // skip first row
$o_tab_parser->nextRow(); // skip second row
$o_tab_parser->nextRow(); // skip third row

while ($o_tab_parser->nextRow()) {
// Get columns from tab file and put them into named variables - makes code easier to read
    $projectnr          =   $o_tab_parser->getRowValue(1); #
    $inventarisnr       =   $o_tab_parser->getRowValue(2); #
    $objectnaam         =   $o_tab_parser->getRowvalue(15);#

    $V_wiel_diam        =   $o_tab_parser->getRowvalue(28);#
    $V_naaf_lengte      =   $o_tab_parser->getRowvalue(29);#
    $V_naaf_diam        =   $o_tab_parser->getRowvalue(30);#
    $V_spoorbreedte     =   $o_tab_parser->getRowvalue(31);#
    $A_wiel_diam        =   $o_tab_parser->getRowvalue(32);#
    $A_naaf_lengte      =   $o_tab_parser->getRowvalue(33);#
    $A_naaf_diam        =   $o_tab_parser->getRowvalue(34);#
    $A_spoortbreedte    =   $o_tab_parser->getRowvalue(35);#

    $schamelring_diam   =   $o_tab_parser->getRowvalue(36);#
    $disselboom_lengte  =   $o_tab_parser->getRowvalue(37);#
    $afstand_basis      =   $o_tab_parser->getRowvalue(38);#
    $afstand_a          =   $o_tab_parser->getRowvalue(39);#
    $afstand_b          =   $o_tab_parser->getRowvalue(40);#

    $afstand_rongen     =   $o_tab_parser->getRowvalue(41);#
    $zitbord_hoogte     =   $o_tab_parser->getRowvalue(42);#

    $authenticiteit     =   $o_tab_parser->getRowvalue(43);
    $conditie           =   $o_tab_parser->getRowvalue(44);
    $volledigheid       =   $o_tab_parser->getRowvalue(45);
    $motivatie          =   $o_tab_parser->getRowvalue(46);
    $motivatie_cond     =   $o_tab_parser->getRowvalue(47);

    ###############################################################

    $update = array();

    echo $vn_c." | ";
    echo $locale_id." \n\r ";

# object_id opzoeken !!!!!!

    $query = "ca_objects.objectInventarisnrBplts:\"".$projectnr."\"";
    //$query = 'ca_objects.objectnaamAlternatief:halsjuk (synoniem)';

    $data = $t_guzzle->findObject($query, 'ca_objects');

    if (isset($data['ok']) && ($data['ok'] == 1) && is_array($data['results'])) {
        if (sizeof($data['results']) > 1) {
            echo "Meer dan 1 kandidaat gevonden \n";
            $log->logError('Meer dan één kandidaat gevonden', $data);
            //exit;
        } else {
            $objectId = $data['results'][0]['object_id'];
            echo $objectId."\n";
        }
    } else {
        echo "projectnr niet gevonden - object bestaat (nog) niet\n";
        $log->logError('projectnr niet gevonden - object bestaat nog niet');

    }

# attributes part

    $dimensions_width = '';
    $dimensions_height = '';
    $dimensions_depth = '';
    $dimensions_circumference = '';
    $dimensions_diameter = '';
    $dimensions_lengte = '';
    $dimensions_weight = '';
    $dimensions_dikte = '';
    $dimensionsDeel = $t_list->getItemIDFromList('deel_type', 'onderdeel');;
    $dimensionsNote = '';
    $dimensionsNaamOnderdeel = '';

    echo $V_wiel_diam." | ";
    if ($V_wiel_diam !== '') {

        $dimensionsNaamOnderdeel = 'Voorwielen';
        $dimensions_diameter = $V_wiel_diam/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_diameter ='';
    }

    echo $V_naaf_lengte." | ".$V_naaf_diam." | ";
    if ($V_naaf_lengte !== '' || $V_naaf_diam !== '') {

        $dimensionsNaamOnderdeel = 'Naaf voorwielen';
        if ($V_naaf_lengte !== '') { $dimensions_lengte = $V_naaf_lengte/10 . ' cm'; }
        if ($V_naaf_diam !== '') { $dimensions_diameter = $V_naaf_diam/10 . ' cm'; }

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$pn_locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );

        $dimensionsNaamOnderdeel = '';
        $dimensions_lengte = '';
        $dimensions_diameter ='';
    }

    echo $V_spoorbreedte." | ";
    if ($V_spoorbreedte !== '') {

        $dimensionsNaamOnderdeel = 'Spoorbreedte vooraan';
        $dimensions_width = $V_spoorbreedte/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_width ='';

    }

    echo $A_wiel_diam." | ";
    if ($A_wiel_diam !== '') {

        $dimensionsNaamOnderdeel = 'Achterwielen';
        $dimensions_diameter = $A_wiel_diam/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_diameter ='';
    }

    echo $A_naaf_lengte." | ".$A_naaf_diam." | ";
    if ($A_naaf_lengte !== '' || $A_naaf_diam !== '') {

        $dimensionsNaamOnderdeel = 'Naaf achterwielen';
        if ($A_naaf_lengte !== '') { $dimensions_lengte = $A_naaf_lengte/10 . ' cm'; }
        if ($A_naaf_lengte !== '') { $dimensions_diameter = $A_naaf_diam/10 . ' cm'; }

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$pn_locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );

        $dimensionsNaamOnderdeel = '';
        $dimensions_lengte = '';
        $dimensions_diameter ='';
    }

    echo $A_spoorbreedte." | ";
    if ($A_spoorbreedte !== '') {

        $dimensionsNaamOnderdeel = 'Spoorbreedte achteraan';
        $dimensions_width = $A_spoorbreedte/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_width ='';
    }

    echo $schamelring_diam." | ";
    if ($schamelring_diam !== '') {

        $dimensionsNaamOnderdeel = 'Schamelring onderstel';
        $dimensions_diameter = $schamelring_diam/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_diameter ='';

    }

    echo $disselboom_lengte." | ";
    if ($disselboom_lengte !== '') {

        $dimensionsNaamOnderdeel = 'Disselboom';
        $dimensions_lengte = $A_spoorbreedte/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_lengte ='';
    }

    echo $afstand_basis." | ";
    if ($afstand_basis !== '') {

        $dimensionsNaamOnderdeel = 'Wielbasis';
        //$dimensionsNote = 'Afstand tussen center voorwiel en achterwiel, wielbasis';
        $dimensions_lengte = $afstand_basis/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_lengte ='';
        $dimensionsNote = '';
    }

    echo $afstand_a." | ";
    if ($afstand_a !== '') {

        $dimensionsNaamOnderdeel = 'Afstand berries aan bak';
        //$dimensionsNote = 'Afstand tss. de lamoen-, glij-, draag-, berriebomen t.h.v. voorzijde karkast';
        $dimensions_width = $afstand_a/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_width ='';
        $dimensionsNote = '';

    }

    echo $afstand_b." | ";
    if ($afstand_b !== '') {

        $dimensionsNaamOnderdeel = 'Afstand berries aan bak';
        //$dimensionsNote = 'Afstand tss. de lamoen- gij- of berrieboomeinden ';
        $dimensions_width = $afstand_b/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_width ='';
        $dimensionsNote = '';

    }

    echo $afstand_rongen." | ";
    if ($afstand_rongen !== '') {

        $dimensionsNaamOnderdeel = 'Afstand tussen rongen / staanders';
        //$dimensionsNote = 'Afstand tussen de rongen of staanders van de zijborden';
        $dimensions_width = $afstand_rongen/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_width ='';
        $dimensionsNote = '';

    }

    echo $zitbord_hoogte." | ";
    if ($zitbord_hoogte !== '') {

        $dimensionsNaamOnderdeel = 'Zijbord';
        //$dimensionsNote = 'Hoogte zijbord t.o.v. de bovenzijde laadvloer';
        $dimensions_height = $zitbord_hoogte/10 .' cm';

        $update['attributes']['dimensionsInfo'][] =
            array(
                'locale_id'                 =>	$locale_id,
                'dimensionsDeel'            =>	$dimensionsDeel,
                'dimensionsNaamOnderdeel'   =>	$dimensionsNaamOnderdeel,
                'dimensions_width'          =>	$dimensions_width,
                'dimensions_height'         =>	$dimensions_height,
                'dimensions_depth'          =>	$dimensions_depth,
                'dimensions_circumference'  =>	$dimensions_circumference,
                'dimensions_diameter'       =>	$dimensions_diameter,
                'dimensions_lengte'         =>	$dimensions_lengte,
                'dimensions_weight'         =>  $dimensions_weight,
                'dimensions_dikte'          =>	$dimensions_dikte,
                'dimensions_notes'          =>  $dimensionsNote
            );
        $dimensionsNaamOnderdeel = '';
        $dimensions_height ='';
        $dimensionsNote = '';

    }

    unset($dimensionsNaamOnderdeel);
    unset($dimensions_width);
    unset($dimensions_height);
    unset($dimensions_depth);
    unset($dimensions_circumference);
    unset($dimensions_diameter);
    unset($dimensions_lengte);
    unset($dimensions_weight);
    unset($dimensions_dikte);
    unset($dimensionsDeel);
    unset($dimensionsNote);

    echo $conditie." | " . $motivatie_cond . " | ";
    if ($conditie !== '') {

        $lijst = 'toestand_lijst';
        $toestand_id = '';
        $toestandNote = '';

        if (strtoupper($conditie) === 'A') {
            $toestand_id = $t_list->getItemIDFromList($lijst, 'goed');
        } elseif (strtoupper($conditie) === 'C') {
            $toestand_id = $t_list->getItemIDFromList($lijst, 'slecht');
        } elseif (strtoupper($conditie) === 'B') {
            $toestand_id = $t_list->getItemIDFromList($lijst, 'matig');
        }

        if ($motivatie_cond !== '') { $toestandNote = 'Motivatie: '. $motivatie_cond; }

        if ($toestand_id !== '') {
            $update['attributes']['toestandInfo'] [] =
                array(
                    'locale_id'     =>	$locale_id,
                    'toestand'      =>	$toestand_id,
                    'toestandNote'  =>	$toestandNote
                );
        }
        unset($toestand_id);
        unset($toestandNote);

    }

    echo $volledigheid." | " . $authenticiteit." | " . $motivatie ." | ";
    if ($volledigheid !== '') {

        $lijst = 'completeness_lijst';
        $complet_id = '';
        $completNote = '';

        if (strtoupper($volledigheid) === 'A') {
            $complet_id = $t_list->getItemIDFromList($lijst, 'volledig');
        } elseif (strtoupper($volledigheid) === 'B') {
            $complet_id = $t_list->getItemIDFromList($lijst, 'onvolledig');
        } elseif (strtoupper($volledigheid) === 'C') {
            $complet_id = $t_list->getItemIDFromList($lijst, 'fragmentarisch');
        }

        if (strtoupper($authenticiteit) === 'A') {
            $completNote= "Authenticiteit: goed\n";
        } elseif (strtoupper($authenticiteit) === 'B') {
            $completNote = "Authenticiteit: slecht\n";
        } elseif (strtoupper($authenticiteit) === 'C') {
            $completNote = "Authenticiteit: matig\n";
        }

        if ($motivatie !== '') {$completNote = $completNote."Motivatie: ".$motivatie; }

        if ($complet_id !== '') {
            $update['attributes']['completenessInfo'] [] =
                array(
                    'locale_id'         =>	$locale_id,
                    'completeness'      =>	$complet_id,
                    'completenessNote'  =>	$completNote
                );
        }

        unset($complet_id);
        unset($completNote);
    }

    print_r($update);

    $log->logInfo('de volledige json array', $update);
    echo "\n\r ";

    $data = $t_guzzle->updateObject($update, $objectId, 'ca_objects');

    $log->logInfo('het resultaat', $data);

    $vn_c++;

}
echo "the end";