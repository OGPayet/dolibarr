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
require_once(DOL_DOCUMENT_ROOT."/core/lib/ws.lib.php");
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

dol_syslog("Call Agenda webservices interfaces");

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
$server->configureWSDL('WebServicesDolibarrAgenda',$ns);
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
    'agenda',
    'complexType',
    'struct',
    'all',
    '',
	array(
		'id' => array('name'=>'id','type'=>'xsd:string'),
        'debut' => array('name'=>'heure_debut','type'=>'xsd:string'),
        'fin' => array('name'=>'heure_fin','type'=>'xsd:string'),
        'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
        'label' => array('name'=>'label','type'=>'xsd:string'),
        'desc' => array('name'=>'desc','type'=>'xsd:string'),
        'lieu' => array('name'=>'lieu','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
	'AgendaArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'agenda' => array(
		'name' => 'agenda',
		'type' => 'tns:agenda',
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


// Register WSDL
$server->register(
    'getAgenda',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:int'),
    // Exit values
    array('result'=>'tns:result','agenda'=>'tns:agenda'),
    $ns,
    $ns.'#getAgenda',
    $styledoc,
    $styleuse,
    'WS to get a RDV'
);

// Register WSDL
$server->register(
	'createAgenda',
	// Entry values
	array('authentication'=>'tns:authentication','agenda'=>'tns:agenda'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:int'),
	$ns,
	$ns.'#createAgenda',
	$styledoc,
	$styleuse,
	'WS to create a RDV'
);

$server->register(
	'getAgendasForPeriod',
	// Entry values
	array('authentication'=>'tns:authentication','date_debut'=>'xsd:date','date_fin'=>'xsd:date'),
	// Exit values
	array('result'=>'tns:result','agendas'=>'tns:AgendaArray2'),
	$ns,
	$ns.'#getAgendasForPeriod',
	$styledoc,
	$styleuse,
	'WS to get all agendas of a period'
);
$server->register(
	'getAgendasForPeriodForUser',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string','date_debut'=>'xsd:date','date_fin'=>'xsd:date'),
	// Exit values
	array('result'=>'tns:result','agendas'=>'tns:AgendaArray2'),
	$ns,
	$ns.'#getAgendasForPeriod',
	$styledoc,
	$styleuse,
	'WS to get all agendas of a period for a user'
);
$server->register(
	'updateAgenda',
	// Entry values
	array('authentication'=>'tns:authentication','agenda'=>'tns:agenda'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:int'),
	$ns,
	$ns.'#updateAgenda',
	$styledoc,
	$styleuse,
	'WS to update a RDV'
);

$server->register(
	'deleteAgenda',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#deleteAgenda',
	$styledoc,
	$styleuse,
	'WS to delete a RDV'
);


/**
 * Get Contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref_ext			Ref external of object
 * @return	mixed
 */
function getAgenda($authentication,$id)
{
    global $db,$conf,$langs,$mysoc;

    dol_syslog("Function: getAgenda login=".$authentication['login']." id=".$id);

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

    if (! $error)
    {
        $fuser->getrights();

        $agenda=new ActionComm($db);
        $result=$agenda->fetch($id,'');
        if ($result > 0)
        {
		$objectresp = array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'agenda'=>array(
					'id' => $agenda->id,
						'debut' => $agenda->datep?$db->idate($agenda->datep):'',
					'fin' => $agenda->datef?$db->idate($agenda->datef):'',
					'id_client' => $agenda->socid,
					'label' => $agenda->label?$agenda->label:'',
					'desc' => $agenda->note?$agenda->note:'',
					'lieu' => $agenda->location?$agenda->location:''
			        ));
         }
         else
         {
             $error++;
             $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
         }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * Create Contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Contact	$contact		    $contact
 * @return	array							Array result
 */
function createAgenda($authentication,$agenda)
{
	global $db,$conf,$langs,$mysoc;

	$now=dol_now();

	dol_syslog("Function: createAgenda login=".$authentication['login']);

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
	if (empty($agenda['label']))
	{
		$error++; $errorcode='KO'; $errorlabel="Label is mandatory.";
	}

	if (! $error)
	{
		$newobject=new Actioncomm($db);

		$newobject->datep=$db->jdate($agenda['debut']);
		$newobject->datef=$agenda['fin']?$db->jdate($agenda['fin']):'';
		$newobject->socid=intval($agenda['id_client']);
		$newobject->label=$agenda['label']?$agenda['label']:'';
		$newobject->note=$agenda['desc']?$agenda['desc']:'';
		$newobject->location=$agenda['lieu']?$agenda['lieu']:'';
		$newobject->type_id=50;
		$newobject->type_code='AC_OTH';
		$newobject->fetch_thirdparty();
		$newobject->societe = $newobject->thirdparty;
		$newobject->userownerid=$fuser->id;
		$newobject->userassigned=$fuser->id;
//		$db->begin();

		$result=$newobject->add($fuser);
		if ($result <= 0)
		{
			$error++;
		}

		if (! $error)
		{
//			$db->commit();
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id);
		}
		else
		{
//			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Get list of contacts for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getAgendasForPeriod($authentication,$date_debut,$date_fin)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getAgendasForPeriod login=".$authentication['login']." idthirdparty=".$idthirdparty);

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

	$sql = "SELECT id, datep, datep2, label, fk_soc, location, note, fk_action,fk_user_action FROM ".MAIN_DB_PREFIX."actioncomm,".MAIN_DB_PREFIX."actioncomm_resources as ar WHERE (ar.fk_element = ".$fuser->id." OR fk_user_action=".$fuser->id.") AND fk_action!=40 AND ((DATE(datep) BETWEEN '".$date_debut."' AND '".$date_fin."') OR (DATE(datep2) BETWEEN '".$date_debut."' AND '".$date_fin."')) AND ar.fk_actioncomm = id AND ar.element_type='user' ORDER BY datep,id";
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

			$linesAgenda[]=array(
				'id' => $agenda->id,
				'debut' => $agenda->datep?$agenda->datep:'',
			    'fin' => $agenda->datep2?$agenda->datep2:'',
			    'id_client' => $agenda->fk_soc,
			    'label' => $agenda->label?$agenda->label:'',
			    'desc' => $agenda->note?$agenda->note:'',
			    'lieu' => $agenda->location?$agenda->location:''
			);
			$i++;
		}

		$objectresp=array(
		'result'=>array('result_code'=>'OK', 'result_label'=>''),
		'agendas'=>$linesAgenda
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
function getAgendasForPeriodForUser($authentication,$id,$date_debut,$date_fin)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getAgendasForPeriodForUser login=".$authentication['login']." idthirdparty=".$idthirdparty);

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

	$sql = "SELECT id, datep, datep2, label, fk_soc, location, note, fk_action,fk_user_action FROM ".MAIN_DB_PREFIX."actioncomm,".MAIN_DB_PREFIX."actioncomm_resources as ar WHERE (ar.fk_element = ".$id." OR fk_user_action=".$id.") AND fk_action!=40 AND ((DATE(datep) BETWEEN '".$date_debut."' AND '".$date_fin."') OR (DATE(datep2) BETWEEN '".$date_debut."' AND '".$date_fin."')) AND ar.fk_actioncomm = id AND ar.element_type='user' ORDER BY datep,id";

	//$sql = "SELECT id, datep, datep2, label, fk_soc, location, note, fk_action,fk_user_action FROM ".MAIN_DB_PREFIX."actioncomm WHERE fk_user_action=".$id." AND fk_action=50 AND ((DATE(datep) BETWEEN '".$date_debut."' AND '".$date_fin."') OR (DATE(datep2) BETWEEN '".$date_debut."' AND '".$date_fin."')) ORDER BY datep,id";

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

			$linesAgenda[]=array(
				'id' => $agenda->id,
				'debut' => $agenda->datep?$agenda->datep:'',
			    'fin' => $agenda->datep2?$agenda->datep2:'',
			    'id_client' => $agenda->fk_soc,
			    'label' => $agenda->label?$agenda->label:'',
			    'desc' => $agenda->note?$agenda->note:'',
			    'lieu' => $agenda->location?$agenda->location:''
			);
			$i++;
		}

		$objectresp=array(
		'result'=>array('result_code'=>'OK', 'result_label'=>''),
		'agendas'=>$linesAgenda
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

/**
 * Update a contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Contact		$contact		    Contact
 * @return	array							Array result
 */
function updateAgenda($authentication,$agenda)
{
	global $db,$conf,$langs,$mysoc;

	$now=dol_now();

	dol_syslog("Function: updateAgenda login=".$authentication['login']);

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
	if (empty($agenda['id']))	{
		$error++; $errorcode='KO'; $errorlabel="Actioncomm id is mandatory.";
	}

	if (! $error)
	{
		$objectfound=false;

		$newobject=new Actioncomm($db);
		$result=$newobject->fetch($agenda['id']);

		if (!empty($newobject->id)) {

			$objectfound=true;

			$newobject->datep=$db->jdate($agenda['debut']);
			$newobject->datef=$agenda['fin']?$db->jdate($agenda['fin']):'';
			$newobject->socid=intval($agenda['id_client']);
			$newobject->label=$agenda['label']?$agenda['label']:'';
			$newobject->note=$agenda['desc']?$agenda['desc']:'';
			$newobject->location=$agenda['lieu']?$agenda['lieu']:'';
			if (! is_array($newobject->userassigned)||(empty($newobject->userassigned)))
			{
				$newobject->userassigned=array();
			$newobject->userassigned[$fuser->id]=array('id'=>$fuser->id);
		}
			//$db->begin();

			$result=$newobject->update($agenda['id'],$fuser);
			if ($result <= 0) {
				$error++;
			}
		}

		if ((! $error) && ($objectfound))
		{
			//$db->commit();
			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'id'=>$newobject->id
			);
		}
		elseif ($objectfound)
		{
			//$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		} else {
			$error++;
			$errorcode='NOT_FOUND';
			$errorlabel='Actioncomm id='.$agenda['id'].' cannot be found';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

function deleteAgenda($authentication,$id)
{
    global $db,$conf,$langs,$mysoc;

    dol_syslog("Function: deleteAgenda login=".$authentication['login']." id=".$id);

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

    if (! $error)
    {
        $fuser->getrights();

        $agenda=new ActionComm($db);
        $result=$agenda->fetch($id,'');
        if ($result > 0)
        {
		$result=$agenda->delete(0);
            if ($result > 0)
		{
			$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$id);
	        }
	        else
	        {
			$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>'0');
	        }
         }
         else
         {
             $error++;
             $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
         }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
