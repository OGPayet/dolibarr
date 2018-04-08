<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/webservices/server_other.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once $dir.'master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

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
$server->configureWSDL('WebServicesDolibarrUsers',$ns);
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

// Define WSDL Return object for users
$server->wsdl->addComplexType(
	'user',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'login' => array('name'=>'login','type'=>'xsd:string'),
		'firstname' => array('name'=>'firstname','type'=>'xsd:string'),
		'lastname' => array('name'=>'lastname','type'=>'xsd:string'),
		'color' => array('name'=>'color','type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
	'userArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'user' => array(
		'name' => 'user',
		'type' => 'tns:user',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);
// Define other specific objects
// None


// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


$server->register(
	'getUsers',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','users'=>'tns:userArray2'),
	$ns,
	$ns.'#getUsers',
	$styledoc,
	$styleuse,
	'WS to get users list'
);
$server->register(
	'createUser',
	// Entry values
	array('authentication'=>'tns:authentication','user'=>'tns:user'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#createUser',
	$styledoc,
	$styleuse,
	'WS to create a new user'
);
$server->register(
	'updateUser',
	// Entry values
	array('authentication'=>'tns:authentication','user'=>'tns:user'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#updateUser',
	$styledoc,
	$styleuse,
	'WS to modify a user'
);
$server->register(
	'deleteUser',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:int'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#deleteUser',
	$styledoc,
	$styleuse,
	'WS to delete a user'
);


// Full methods code
function getUsers($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getUsers login=".$authentication['login']);

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
		$sql = "SELECT rowid, login, lastname, firstname, color,pass_crypted  FROM ".MAIN_DB_PREFIX."user WHERE entity=0 OR entity=".$conf->entity." ORDER BY rowid";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$lines[]=array(
				'id' => $obj->rowid,
				'login' => $obj->login,
				'firstname' => $obj->firstname,
				'lastname' => $obj->lastname,
				'color' => $obj->color
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'users'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function createUser($authentication,$user)
{
	global $db,$conf,$langs,$mysoc;

	$now=dol_now();

	dol_syslog("Function: createUser login=".$authentication['login']);

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
	if (empty($user['login']))
	{
		$error++; $errorcode='KO'; $errorlabel="Login is mandatory.";
	}

	if (! $error)
	{
		$newobject=new User($db);

		$newobject->login=$user['login'];
		$newobject->lastname=$user['lastname'];
		$newobject->firstname=$user['firstname'];
		$newobject->color=$user['color'];

		$db->begin();

		$result=$newobject->create($fuser);
		if ($result <= 0)
		{
			$error++;
		}

		if (! $error)
		{
			$db->commit();
			$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'id'=>$newobject->id);
		}
		else
		{
			$db->rollback();
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
function updateUser($authentication,$user)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: updateUser login=".$authentication['login']);

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
		$object=new User($db);
		$result=$object->fetch($user['id']);
		if (!empty($object->id))
		{
			$object->login=$user['login'];
			$object->firstname=$user['firstname'];
			$object->lastname=$user['lastname'];
			$object->color=$user['color'];

			$result=$object->update($fuser);
			if ($result > 0)
			{
				$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'id'=>$object->id);
			}
			else
			{
				$error++;
				$errorcode='KO';
				$errorlabel=$object->error;
			}
		}
		else
		{
			$error++;
			$errorcode="NOT_FOUND"; $errorlabel="Object not found";
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function deleteUser($authentication,$id)
{
    global $db,$conf,$langs,$mysoc;

    dol_syslog("Function: deleteUser login=".$authentication['login']." id=".$id);

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

        $user=new User($db);
        $result=$user->fetch($id,0,"");
        if ($result > 0)
        {
			$result=$user->delete();
            if ($result > 0)
		{
			$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'id'=>$id);
	        }
	        else
	        {
			$objectresp = array('result'=>array('result_code'=>'KO', 'result_label'=>''),'id'=>'0');
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
