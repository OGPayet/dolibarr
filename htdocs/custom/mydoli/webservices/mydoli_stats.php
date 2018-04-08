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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';

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
$server->configureWSDL('WebServicesDolibarrOther',$ns);
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
// Define WSDL Return object for document
$server->wsdl->addComplexType(
	'creance',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'name' => array('name'=>'name','type'=>'xsd:string'),
		'date' => array('name'=>'date','type'=>'xsd:string'),
		'montant' => array('name'=>'montant','type'=>'xsd:double')
	)
);
$server->wsdl->addComplexType(
    'CreanceArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'creance' => array(
            'name' => 'creance',
            'type' => 'tns:creance',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:creance'
);
// Define WSDL Return object for document
$server->wsdl->addComplexType(
	'alertestock',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'ref' => array('name'=>'name','type'=>'xsd:string'),
		'enstock' => array('name'=>'enstock','type'=>'xsd:int'),
		'encommande' => array('name'=>'encommande','type'=>'xsd:int'),
		'acommander' => array('name'=>'acommander','type'=>'xsd:int')
	)
);
$server->wsdl->addComplexType(
    'alertestockArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'alertestock' => array(
            'name' => 'alertestock',
            'type' => 'tns:alertestock',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:alertestock'
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
    'getCAM',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','data'=>'xsd:string'),
    $ns,
    $ns.'#getCAM',
    $styledoc,
    $styleuse,
    'WS to get CA mensuels'
);
$server->register(
    'getCAMyear',
    // Entry values
    array('authentication'=>'tns:authentication','year'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','data'=>'xsd:string'),
    $ns,
    $ns.'#getCAMyear',
    $styledoc,
    $styleuse,
    'WS to get CA mensuels for a specific year'
);
// Register WSDL
$server->register(
    'getCreances',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','creances'=>'tns:CreanceArray'),
    $ns,
    $ns.'#getCreances',
    $styledoc,
    $styleuse,
    'WS to get Factures impayees'
);
// Register WSDL
$server->register(
    'getAlerteStock',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','alertes'=>'tns:alertestockArray'),
    $ns,
    $ns.'#getAlerteStock',
    $styledoc,
    $styleuse,
    'WS to get alertes de stocks'
);


// Full methods code
function getCAM($authentication)
{
	global $db,$conf,$langs,$user,$mysoc;

	dol_syslog("Function: getCAM login=".$authentication['login']);

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
		$langs->load('bills');
		$langs->load('companies');
		$langs->load('other');
		clearstatcache();
		$nowyear=strftime("%Y", dol_now());
		$year = $nowyear;
		$startyear=$year-1;
		$endyear=$year;
		$mode='customer';
		$stats = new FactureStats($db, $socid, $mode);
		$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
		$resData="";
		$i=0;
		$nblot=count($data[0])-1;
		while ($i < $nblot)
		{
			$x=0;
			foreach($data as $key => $valarray)
			{
				$resData .= "".$valarray[$i+1].";";
				$x++;
			}
			$i++;
		}
		$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
		$objectresp['data']=$resData;

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function getCAMyear($authentication,$year)
{
	global $db,$conf,$langs,$user,$mysoc;

	dol_syslog("Function: getCAM login=".$authentication['login']);

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
		$langs->load('bills');
		$langs->load('companies');
		$langs->load('other');
		clearstatcache();
		$startyear=intval($year)-1;
		$endyear=intval($year);
		$mode='customer';
		$stats = new FactureStats($db, $socid, $mode);
		$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
		$resData="";
		$i=0;
		$nblot=count($data[0])-1;
		while ($i < $nblot)
		{
			$x=0;
			foreach($data as $key => $valarray)
			{
				$resData .= "".$valarray[$i+1].";";
				$x++;
			}
			$i++;
		}
		$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
		$objectresp['data']=$resData;

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function getCreances($authentication)
{
	global $db,$conf,$langs,$user,$mysoc;

	dol_syslog("Function: getCreances login=".$authentication['login']);

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
		$sql = "SELECT s.nom as name, s.rowid as socid, s.email";
		$sql.= ", f.rowid as facid, f.facnumber, f.ref_client, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
		$sql.= ", f.datef as df, f.date_lim_reglement as datelimite";
		$sql.= ", f.paye as paye, f.fk_statut, f.type, f.fk_mode_reglement";
		$sql.= ", sum(pf.amount) as am";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ",".MAIN_DB_PREFIX."facture as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.type IN (0,1,3) AND f.fk_statut = 1";
		$sql.= " AND f.paye = 0";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if (! empty($socid)) $sql .= " AND s.rowid = ".$socid;
		$sql.= " GROUP BY s.nom, s.rowid, s.email, f.rowid, f.facnumber, f.ref_client, f.increment, f.total, f.tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp,";
		$sql.= " f.datef, f.date_lim_reglement, f.paye, f.fk_statut, f.type, fk_mode_reglement";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql.= " ORDER BY ";
		$sql.= " datelimite ASC, f.facnumber ASC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$creances=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
                $obj=$db->fetch_object($resql);
                $creances[]=array(
				'id' => $obj->socid,
					'name' => $obj->name,
					'date' => $obj->datelimite,
					'montant' => $obj->total_ttc-$obj->paye
			    );
				$i++;
			}
			$db->free($resql);

			$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
			$objectresp['creances']=$creances;
		}
		else
		{
			$errorcode="SQL_ERROR";
			$errorlabel=$db->lasterror();
			$error++;
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

function getAlerteStock($authentication)
{
	global $db,$conf,$langs,$user,$mysoc;

	dol_syslog("Function: getAlerteStock login=".$authentication['login']);

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
		$sql = 'SELECT p.rowid, p.ref, p.label, p.price,';
		$sql.= ' p.price_ttc, p.price_base_type,p.fk_product_type,';
		$sql.= ' p.tms as datem, p.duration, p.tobuy,';
		$sql.= ' p.desiredstock, p.seuil_stock_alerte as alertstock,';
		$sql.= ' SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").') as stock_physique';
		$sql.= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
		$sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s';
		$sql.= ' ON p.rowid = s.fk_product';
		$sql.= ' WHERE p.entity IN (' . getEntity("product", 1) . ')';
		$sql.= ' AND p.tobuy = 1';
		$sql.= ' GROUP BY p.rowid, p.ref, p.label, p.price';
		$sql.= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
		$sql.= ', p.duration, p.tobuy';
		$sql.= ', p.desiredstock, p.seuil_stock_alerte';
		$sql.= ', s.fk_product';

		$resql = $db->query($sql);
		if ($resql)
		{
			$alertes=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
                $obj=$db->fetch_object($resql);
                if ($obj->stock_physique<=$obj->alertstock)
                {
			$alertes[]=array(
				'id' => $obj->rowid,
					'ref' => $obj->ref,
					'enstock' => $obj->stock_physique,
					'encommande' => 0,
					'acommander' => max(max($obj->desiredstock, $obj->alertstock) - $obj->stock_physique - 0, 0)
				);
			    }
				$i++;
			}
			$db->free($resql);

			$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
			$objectresp['alertes']=$alertes;
		}
		else
		{
			$errorcode="SQL_ERROR";
			$errorlabel=$db->lasterror();
			$error++;
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
