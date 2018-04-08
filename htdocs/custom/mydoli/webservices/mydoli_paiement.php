<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");
$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

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
$server->configureWSDL('WebServicesDolibarrPaiement',$ns);
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
        'entity' => array('name'=>'entity','type'=>'xsd:string')
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

// Define WSDL Return object for document
$server->wsdl->addComplexType(
	'facture',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id_facture' => array('name'=>'id_facture','type'=>'xsd:int'),
		'montant_paye' => array('name'=>'montant_paye','type'=>'xsd:double'),
	)
);
$server->wsdl->addComplexType(
    'FactureArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'facture' => array(
            'name' => 'facture',
            'type' => 'tns:facture',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:facture'
);
$server->wsdl->addComplexType(
	'paiement',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'date' => array('name'=>'date','type'=>'xsd:date'),
		'montant' => array('name'=>'montant','type'=>'xsd:double'),
		'mode' => array('name'=>'mode','type'=>'xsd:int'),
		'num' => array('name'=>'num','type'=>'xsd:string'),
		'emetteur' => array('name'=>'emetteur','type'=>'xsd:string'),
		'banque' => array('name'=>'banque','type'=>'xsd:string'),
		'compte' => array('name'=>'compte','type'=>'xsd:int'),
		'factures' => array('name'=>'factures','type'=>'tns:FactureArray')
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

// Register WSDL
$server->register(
    'createPaiement',
    // Entry values
   array('authentication'=>'tns:authentication','paiement'=>'tns:paiement'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#createPaiement',
    $styledoc,
    $styleuse,
    'WS to create a payment'
);

// Full methods code
function createPaiement($authentication,$paiement)
{
	global $db,$conf,$langs,$user,$mysoc;

	dol_syslog("Function: createPaiement login=".$authentication['login']);

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
    $user=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
	if ($user->societe_id) $socid=$user->societe_id;
	$user->getrights();

    if (! $error)
	{
		if ($user->rights->facture->paiement)
		{
			$db->begin();

			$amounts=array();
			$arrayoflines=array();
			if (isset($paiement['factures']['facture'][0])) $arrayoflines=$paiement['factures']['facture'];
			else $arrayoflines=$paiement['factures'];
			foreach($arrayoflines as $key => $line)
			{
				$amounts[$line['id_facture']] = price2num($line['montant_paye']);
			}
			$newobject = new Paiement($db);
		$newobject->datepaye     = $paiement['date'];
		$newobject->amounts      = $amounts;   // Array with all payments dispatching
		$newobject->paiementid   = $paiement['mode'];
		$newobject->num_paiement = $paiement['num'];
		$newobject->note         = "";

		$paiement_id = $newobject->create($user, 1);
		if ($paiement_id > 0)
	        {
			$label='(CustomerInvoicePayment)';
			$result=$newobject->addPaymentToBank($user,'payment',$label,$paiement['compte'],$paiement['emetteur'],$paiement['banque']);
			if ($result > 0)
			{
				$db->commit();
				$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$paiement_id);
			}
			else
				{
					$db->rollback();
					$error++;
					$errorcode='SQL_ERROR'; $errorlabel=$db->lastquery;
				}
		}
		else
			{
				$db->rollback();
				$error++;
				$errorcode='SQL_ERROR'; $errorlabel=$db->lastquery;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
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
