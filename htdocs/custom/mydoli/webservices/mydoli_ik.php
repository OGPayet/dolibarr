<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/webservices/server_contact.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once($dir.'master.inc.php');
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

dol_syslog("Call IK webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrIK',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
	'login' => array('name'=>'login','type'=>'xsd:string'),
	'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
    )
);

// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
    'trajet',
    'complexType',
    'struct',
    'all',
    '',
	array(
        'date_heure' => array('name'=>'date_heure','type'=>'xsd:string'),
        'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
        'label' => array('name'=>'label','type'=>'xsd:string'),
        'lieu' => array('name'=>'lieu','type'=>'xsd:string'),
        'distance' => array('name'=>'distance','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
	'TrajetsArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'trajet' => array(
		'name' => 'trajet',
		'type' => 'tns:trajet',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);


// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


$server->register(
	'getTrajetsForPeriod',
	// Entry values
	array('authentication'=>'tns:authentication','date_debut'=>'xsd:date','date_fin'=>'xsd:date'),
	// Exit values
	array('result'=>'tns:result','trajets'=>'tns:TrajetsArray2'),
	$ns,
	$ns.'#getTrajetsForPeriod',
	$styledoc,
	$styleuse,
	'WS to get trajets of a period'
);


/**
 * Get list of contacts for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
 //calcul distance itineraire
//https://maps.googleapis.com/maps/api/directions/json?origin=2+rue+du+centre+68320+wickerschwihr&destination=23+rue+stanislas+68000+colmar
//DirectionsResponse.status=OK
//DirectionsResponse.route.leg.distance.value=999 999 m			.text=9999,99km
//DirectionsResponse.route.leg.start_location.lat   .lng		GPS départ
//DirectionsResponse.route.leg.end_location.lat   .lng			GPS arrivée
function getDistance($depart,$arrivee)
{
    $address = urlencode($address);

    //AIzaSyDi9ZUlx8sSnb_Mtn9Dm4t91ilPgEXj3yo

    $url = "https://maps.googleapis.com/maps/api/directions/json?origin=".urlencode($depart)."&destination=".urlencode($arrivee);
    $response = file_get_contents($url);
    $json = json_decode($response);

	$distance=0;
	$statut=$json->geocoded_waypoints[0]->geocoder_status;
	if ($statut=='OK')
	{
		$statut=$json->geocoded_waypoints[1]->geocoder_status;
		if ($statut=='OK')
		{
		$distance = intval($json->routes[0]->legs[0]->distance->value);
	}
    }

    return (2*$distance);
}
function getTrajetsForPeriod($authentication,$date_debut,$date_fin)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getTrajetsForPeriod login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
	// Check parameters

	$lieuDepart=$conf->global->MAIN_INFO_SOCIETE_ADDRESS." ".$conf->global->MAIN_INFO_SOCIETE_ZIP." ".$conf->global->MAIN_INFO_SOCIETE_TOWN;


	$sql = "SELECT id, datep, label, fk_soc, location, fk_action,entity FROM ".MAIN_DB_PREFIX."actioncomm WHERE entity=".$conf->entity." AND fk_user_author=".$fuser->id." AND fk_action=50 AND (DATE(datep) BETWEEN '".$date_debut."' AND '".$date_fin."') ORDER BY datep,id";

	$resql=$db->query($sql);
	if ($resql)
	{
		$linesAgenda=array();
		$num=$db->num_rows($resql);
		$i=0;
		while ($i < $num)
		{
			// En attendant remplissage par boucle
			$agenda=$db->fetch_object($resql);

			if ($agenda->location!='')
			{
				//*** calcul distance
				$distance=getDistance($lieuDepart,$agenda->location);

				if ($distance>0)
				{
					$linesAgenda[]=array(
						'date_heure' => $agenda->datep?$agenda->datep:'',
					'id_client' => $agenda->fk_soc,
					'label' => $agenda->label?$agenda->label:'',
					'lieu' => $agenda->location?$agenda->location:'',
					    'distance' => $distance
					);
				}
			}
			$i++;
		}

		$objectresp=array(
		'result'=>array('result_code'=>'OK', 'result_label'=>''),
		'trajets'=>$linesAgenda
		);
	}
	else
	{
		$error++;
		$errorcode="SQL_ERROR"; $errorlabel=$db->lasterror();
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
