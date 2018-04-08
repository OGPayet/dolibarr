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


dol_syslog("Call Contact webservices interfaces");

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
$server->configureWSDL('WebServicesDolibarrMateriel',$ns);
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

$materiel_fields = array(
	'id' => array('name'=>'id','type'=>'xsd:string'),
	'socid' => array('name'=>'socid','type'=>'xsd:string'),
	'ref' => array('name'=>'ref','type'=>'xsd:string'),
	'marque' => array('name'=>'marque','type'=>'xsd:string'),
	'model' => array('name'=>'model','type'=>'xsd:string'),
	'noserie' => array('name'=>'noserie','type'=>'xsd:string'),
	'options' => array('name'=>'options','type'=>'xsd:string'),
	'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
	'date_achat' => array('name'=>'date_achat','type'=>'xsd:date'),
	'garantie' => array('name'=>'garantie','type'=>'xsd:int')
);

// Define other specific objects
$server->wsdl->addComplexType(
    'materiel',
    'complexType',
    'struct',
    'all',
    '',
	$materiel_fields
);

$server->wsdl->addComplexType(
	'MaterielsArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'materiel' => array(
		'name' => 'materiel',
		'type' => 'tns:materiel',
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
    'getMateriel',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','materiel'=>'tns:materiel'),
    $ns,
    $ns.'#getMateriel',
    $styledoc,
    $styleuse,
    'WS to get a materiel'
);

// Register WSDL
$server->register(
	'createMateriel',
	// Entry values
	array('authentication'=>'tns:authentication','materiel'=>'tns:materiel'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#createMateriel',
	$styledoc,
	$styleuse,
	'WS to create a materiel'
);

$server->register(
	'getMaterielsForThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','materiels'=>'tns:MaterielsArray2'),
	$ns,
	$ns.'#getMaterielsForThirdParty',
	$styledoc,
	$styleuse,
	'WS to get all materiels of a third party'
);

// Register WSDL
$server->register(
	'updateMateriel',
	// Entry values
	array('authentication'=>'tns:authentication','materiel'=>'tns:materiel'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#updateMateriel',
	$styledoc,
	$styleuse,
	'WS to update a materiel'
);

$server->register(
    'deleteMateriel',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#deleteMateriel',
    $styledoc,
    $styleuse,
    'WS to delete a materiel'
);

//***** Auto create table materiel
function testTableExist()
{
	global $db,$conf,$langs;

	$checktable = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."materiels'");
	if (($checktable)&&($db->num_rows($checktable)<=0))
	{
		$db->query("CREATE TABLE ".MAIN_DB_PREFIX."materiels (rowid INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,ref VARCHAR(50) NOT NULL,socid INT(6),marque VARCHAR(50),model VARCHAR(50),noserie VARCHAR(50),options TEXT,note_public TEXT,date_achat DATE,garantie INT(1))");
	}
}

/**
 * Get Contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref_ext			Ref external of object
 * @return	mixed
 */
function getMateriel($authentication,$id,$ref_ext)
{
    global $db,$conf,$langs,$mysoc;

    dol_syslog("Function: getMateriel login=".$authentication['login']." id=".$id." ref_ext=".$ref_ext);

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
    if (! $error && ($id && $ref_ext))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

		testTableExist();
		$sql = "SELECT rowid,socid,ref,marque,model,noserie,options,note_public,date_achat,garantie FROM ".MAIN_DB_PREFIX."materiels WHERE rowid=".$id;
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);

			$materiel_result_fields =array(
				'id' => $obj->rowid,
				'ref' => $obj->ref,
				'socid' => $obj->socid,
				'marque' => $obj->marque,
				'model' => $obj->model,
				'noserie' => $obj->noserie,
				'options' => $obj->options,
				'note_public' => $obj->note_public,
				'date_achat' => $obj->date_achat,
				'garantie' => $obj->garantie
			);

			// Create
			$objectresp = array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'materiel'=>$materiel_result_fields
			);

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
function createMateriel($authentication,$materiel)
{
	global $db,$conf,$langs,$mysoc;

	$now=dol_now();

	dol_syslog("Function: createMateriel login=".$authentication['login']);

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

		testTableExist();

		$ref=$materiel['ref'];
		$socid=intval($materiel['socid']);
		$marque=$materiel['marque'];
		$model=$materiel['model'];
		$noserie=$materiel['noserie'];
		$options=$materiel['options'];
		$note_public=$materiel['note_public'];
		$date_achat=$materiel['date_achat'];
		$garantie=intval($materiel['garantie']);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."materiels (";
		$sql.= " ref";
		$sql.= ", socid";
        $sql.= ", marque";
        $sql.= ", model";
        $sql.= ", noserie";
		$sql.= ", options";
		$sql.= ", note_public";
		$sql.= ", date_achat";
		$sql.= ", garantie";
		$sql.= ") VALUES (";
		$sql.= "'".$db->escape($ref)."',";
		$sql.= " ".$socid.",";
		$sql.= "'".$db->escape($marque)."',";
        $sql.= "'".$db->escape($model)."',";
		$sql.= "'".$db->escape($noserie)."',";
		$sql.= "'".$db->escape($options)."',";
		$sql.= "'".$db->escape($note_public)."',";
		$sql.= "'".$date_achat."',";
		$sql.= "".$garantie."";
		$sql.= ")";

		$result=$db->query($sql);
		if ($result)
		{
			$id=$db->last_insert_id(MAIN_DB_PREFIX."materiels");
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$id,'ref'=>$ref);
		}
		else
		{
			$error++;
			$errorcode='KO';
			$errorlabel=$db->lasterror();
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
function getMaterielsForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getMaterielsForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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
	if (! $error && empty($idthirdparty))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel='Parameter id is not provided';
	}

	if (! $error)
	{
		testTableExist();
		$sql = "SELECT rowid,ref,socid,marque,model,noserie,options,note_public,date_achat,garantie";
		$sql.= " FROM ".MAIN_DB_PREFIX."materiels";
		if ($idthirdparty!='all') $sql.= " WHERE socid=$idthirdparty";

		$resql=$db->query($sql);
		if ($resql)
		{
			$linesmateriels=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				// Now define invoice
				$linesmateriels[]=array(
				'id' => $obj->rowid,
				'ref' => $obj->ref,
				'socid' => $obj->socid,
				'marque' => $obj->marque,
				'model' => $obj->model,
				'noserie' => $obj->noserie,
				'options' => $obj->options,
				'note_public' => $obj->note_public,
				'date_achat' => $obj->date_achat,
				'garantie' => $obj->garantie
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'materiels'=>$linesmateriels

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


/**
 * Update a contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Contact		$contact		    Contact
 * @return	array							Array result
 */
function updateMateriel($authentication,$materiel)
{
	global $db,$conf,$langs,$mysoc;

	$now=dol_now();

	dol_syslog("Function: updateMateriel login=".$authentication['login']);

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
	if (empty($materiel['id']))	{
		$error++; $errorcode='KO'; $errorlabel="Engine id is mandatory.";
	}

	if (! $error)
	{
		testTableExist();

		$sql = "SELECT rowid,socid,ref,marque,model,noserie,options,note_public,date_achat,garantie FROM ".MAIN_DB_PREFIX."materiels WHERE rowid=".$materiel['id'];
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);
			if ($obj->rowid!=0)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."materiels SET";
				$sql.= " ref='".$db->escape($materiel['ref'])."'";
				$sql.= ", socid=".$materiel['socid'];
				$sql.= ", marque='".$db->escape($materiel['marque'])."'";
				$sql.= ", model='".$db->escape($materiel['model'])."'";
				$sql.= ", noserie='".$db->escape($materiel['noserie'])."'";
				$sql.= ", options='".$db->escape($materiel['options'])."'";
				$sql.= ", note_public='".$db->escape($materiel['note_public'])."'";
				$sql.= ", date_achat='".$materiel['date_achat']."'";
				$sql.= ", garantie=".intval($materiel['garantie']);
				$sql.= " WHERE rowid=".$materiel['id'];
				$result = $db->query($sql);
				if ($result)
				{
					$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$obj->rowid);
				}
				else
				{
					$error++;
					$errorcode='KO';
					$errorlabel=$db->lasterror();
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND';
				$errorlabel='Materiel id='.$materiel['id'].' cannot be found';
			}
		}
		else
		{
			$error++;
			$errorcode='NOT_FOUND';
			$errorlabel='Materiel id='.$materiel['id'].' cannot be found';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

function deleteMateriel($authentication,$id)
{
    global $db,$conf,$langs,$mysoc;

    dol_syslog("Function: deleteMateriel login=".$authentication['login']." id=".$id);

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
	if (empty($id))	{
		$error++; $errorcode='KO'; $errorlabel="Engine id is mandatory.";
	}

	if (! $error)
	{
		testTableExist();

		$fuser->getrights();

        testTableExist();

		$sql = "SELECT rowid,socid,ref,marque,model,noserie,options,note_public,date_achat,garantie FROM ".MAIN_DB_PREFIX."materiels WHERE rowid=".$id;
		$resql=$db->query($sql);
		if ($resql)
        {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."materiels WHERE rowid=".$id;
			$result=$db->query($sql);
            if ($result)
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
