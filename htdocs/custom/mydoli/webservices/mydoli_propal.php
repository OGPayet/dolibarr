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
 *       \file       htdocs/webservices/server_invoice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once $dir.'master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");
$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');
$langs->load("deliveries");
$langs->load('sendings');

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
$server->configureWSDL('WebServicesDolibarrDevis',$ns);
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

// Define other specific objects
$server->wsdl->addComplexType(
    'line',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:int'),
        'label' => array('name'=>'label','type'=>'xsd:string'),
        'desc' => array('name'=>'desc','type'=>'xsd:string'),
        'qty' => array('name'=>'qty','type'=>'xsd:double'),
        'taux_tva' => array('name'=>'taux_tva','type'=>'xsd:double'),
        'unitprice' => array('name'=>'unitprice','type'=>'xsd:double'),
        'remise_percent' => array('name'=>'remise_percent','type'=>'xsd:double'),
        'total_ht' => array('name'=>'total_ht','type'=>'xsd:double'),
	'total_tva' => array('name'=>'total_tva','type'=>'xsd:double'),
	'total_ttc' => array('name'=>'total_ttc','type'=>'xsd:double'),
        // From product
        'product_id' => array('name'=>'product_id','type'=>'xsd:int'),
        'product_label' => array('name'=>'product_label','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'LinesArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'line' => array(
            'name' => 'line',
            'type' => 'tns:line',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:line'
);


$server->wsdl->addComplexType(
    'devis',
    'complexType',
    'struct',
    'all',
    '',
    array(
	'id' => array('name'=>'id','type'=>'xsd:int'),
        'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
        'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
        'date' => array('name'=>'date','type'=>'xsd:date'),
        'date_livraison' => array('name'=>'date_livraison','type'=>'xsd:date'),
        'date_expiration' => array('name'=>'date_expiration','type'=>'xsd:date'),
        'status' => array('name'=>'status','type'=>'xsd:int'),
        'status_lib' => array('name'=>'status_lib','type'=>'xsd:string'),
        'total_ht' => array('name'=>'total_ht','type'=>'xsd:double'),
        'total_tva' => array('name'=>'total_tva','type'=>'xsd:double'),
        'total_ttc' => array('name'=>'total_ttc','type'=>'xsd:double'),
        'note_private' => array('name'=>'note_private','type'=>'xsd:string'),
        'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
        'lines' => array('name'=>'lines','type'=>'tns:LinesArray')
    )
);

$server->wsdl->addComplexType(
    'DevisArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'devis' => array(
            'name' => 'devis',
            'type' => 'tns:devis',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:devis'
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getDevis',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','devis'=>'tns:devis'),
    $ns,
    $ns.'#getDevis',
    $styledoc,
    $styleuse,
    'WS to get a particular devis'
);
$server->register(
    'getDevisForThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','devis'=>'tns:DevisArray'),
    $ns,
    $ns.'#getDevisForThirdParty',
    $styledoc,
    $styleuse,
    'WS to get all devis of a third party'
);
$server->register(
    'createDevis',
    // Entry values
    array('authentication'=>'tns:authentication','devis'=>'tns:devis'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#createDevis',
    $styledoc,
    $styleuse,
    'WS to create a devis'
);
$server->register(
    'updateDevis',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','devis'=>'tns:devis'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#updateDevis',
    $styledoc,
    $styleuse,
    'WS to update a devis'
);
$server->register(
    'factureDevis',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','id_client'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#factureDevis',
    $styledoc,
    $styleuse,
    'WS to bill a devis'
);
$server->register(
    'commandeDevis',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','id_client'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#commandeDevis',
    $styledoc,
    $styleuse,
    'WS to order a devis'
);
$server->register(
    'deleteDevis',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#deleteDevis',
    $styledoc,
    $styleuse,
    'WS to delete a particular devis'
);


function isCommercial($idTiers,$idUser)
{
	global $db,$conf,$langs;

	$sql= "SELECT fk_soc,fk_user FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_soc=".$idTiers." AND fk_user=".$idUser;

    $resql=$db->query($sql);
    if ($resql)
    {
          $num=$db->num_rows($resql);
          if ($num>=1)
          {
		$db->free($resql);
		return 1;
          }
	}
	$db->free($resql);
	$sql= "SELECT fk_soc,fk_user FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_soc=".$idTiers;
    $resql=$db->query($sql);
    if ($resql)
    {
          $num=$db->num_rows($resql);
          if ($num<1)
          {
		$db->free($resql);
		return 1;
          }
	}
	$db->free($resql);

	return 0;
}
/**
 * Get devis from id, ref or ref_ext.
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getDevis($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getDevis login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->propale->lire)
		{
			$sql.='SELECT *';
			$sql.=' FROM '.MAIN_DB_PREFIX.'propal as p';
			$sql.=" WHERE p.rowid = ".$id;
			$resql=$db->query($sql);
			if ($resql)
			{
				$devisSQL=$db->fetch_object($resql);
			}

			$devis=new Propal($db);
			$result=$devis->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$ok=1;
				if ($fuser->admin==0)
                {
			$ok=isCommercial($devis->socid,$fuser->id);
                }
			    if($ok==1)
			    {
					$linesresp=array();
					$i=0;
					foreach($devis->lines as $line)
					{
						//var_dump($line); exit;
						$linesresp[]=array(
							'id'=>$line->rang,
							'label'=>dol_htmlcleanlastbr($line->label),
							'desc'=>dol_htmlcleanlastbr($line->desc),
							'qty'=>$line->qty,
							'taux_tva'=>$line->tva_tx,
							'unitprice'=>$line->subprice,
							'remise_percent'=>$line->remise_percent,
							'remise_absolue'=>$line->remise,
							'total_ht'=>$line->total_ht,
							'total_tva'=>$line->total_tva,
							'total_ttc'=>$line->total_ttc,
							'product_id'=>$line->fk_product,
							'product_label'=>$line->libelle
						);
						$i++;
					}

					// Create Devis
					$objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'devis'=>array(
							'id' => $devis->id,
							'ref' => $devis->ref,
							'ref_client' => $devis->ref_client,
							'id_client' => $devis->socid,
							'date' => $devisSQL->datep?$devisSQL->datep:'',
							'date_livraison' => $devisSQL->date_livraison?dol_print_date($devisSQL->date_livraison,'dayrfc'):'',
							'date_expiration' => $devisSQL->date_expiration?dol_print_date($devisSQL->date_expiration,'dayrfc'):'',
							'status' => $devis->statut,
							'status_lib' => $devis->statut_libelle,
							'remise_percent' => $devis->remise_percent,
							'remise_absolue' => $devis->remise_absolue,
							'total_ht' => $devis->total_ht,
							'total_tva' => $devis->total_tva,
							'total_ttc' => $devis->total_ttc,
							'note_private' => $devis->note_private?$devis->note_private:'',
							'note_public' => $devis->note_public?$devis->note_public:'',
							'lines' => $linesresp
						));
					}
					else
					{
						$error++;
						$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
					}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
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


/**
 * Get list of invoices for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getDevisForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getDevisForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && empty($idthirdparty))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel='Parameter id is not provided';
	}

	if (! $error)
	{
		$linesDevis=array();

		$sql.='SELECT *';
		$sql.=' FROM '.MAIN_DB_PREFIX.'propal as p';
		$sql.=" WHERE p.entity = ".$conf->entity;
		if ($idthirdparty != 'all' ) $sql.=" AND p.fk_soc = ".$db->escape($idthirdparty);

//*** Filtre date
//$sql.=" AND p.datep >= '2016-01-01'";

		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
                // En attendant remplissage par boucle
			    $obj=$db->fetch_object($resql);

			    $devis=new Propal($db);
			    $devis->fetch($obj->rowid);

			    // Sécurité pour utilisateur externe
			    if( $socid && ( $socid != $devis->socid) )
			    {
				$error++;
				$errorcode='PERMISSION_DENIED'; $errorlabel=$invoice->socid.' User does not have permission for this request';
			    }
			    $ok=1;
				if ($fuser->admin==0)
                {
			$ok=isCommercial($devis->socid,$fuser->id);
                }
			    if((!$error)&&($ok==1))
			    {
				// Define lines of invoice
				$linesresp=array();

// on ne lit plus les lignes de la piece. Elles seront relues quand l'utilisateur editera le devis
// 					foreach($devis->lines as $line)
// 			    	{
// 			    		$linesresp[]=array(
// 	    					'id'=>$line->rang,
// 							'label'=>dol_htmlcleanlastbr($line->label),
// 							'desc'=>dol_htmlcleanlastbr($line->desc),
// 							'qty'=>$line->qty,
// 							'taux_tva'=>$line->tva_tx,
// 							'unitprice'=>$line->subprice,
// 							'remise_percent'=>$line->remise_percent,
// 							'remise_absolue'=>$line->remise,
// 							'total_ht'=>$line->total_ht,
// 							'total_tva'=>$line->total_tva,
// 							'total_ttc'=>$line->total_ttc,
// 							'product_id'=>$line->fk_product,
//                         	'product_label'=>$line->libelle
// 			    		);
// 			    	}

				// Now define invoice
				$linesDevis[]=array(
					'id' => $devis->id,
						'ref' => $devis->ref,
					'ref_client' => $devis->ref_client,
					'id_client' => $devis->socid,
					'date' => $obj->datep?$obj->datep:'',
					'date_livraison' => $obj->date_livraison?dol_print_date($obj->date_livraison,'dayrfc'):'',
					'date_expiration' => $obj->date_expiration?dol_print_date($obj->date_expiration,'dayrfc'):'',
					'status' => $devis->statut,
					'status_lib' => $devis->statut_libelle,
					'remise_percent' => $devis->remise_percent,
					'remise_absolue' => $devis->remise_absolue,
					'total_ht' => $devis->total_ht,
					'total_tva' => $devis->total_tva,
					'total_ttc' => $devis->total_ttc,
					'note_private' => $devis->note_private?$devis->note_private:'',
					'note_public' => $devis->note_public?$devis->note_public:'',
					'lines' => $linesresp
				);
			    }

			    $i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
		        'devis'=>$linesDevis

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
 * Create a Devis
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Devis		$devis				Devis
 * @return	array							Array result
 */
function createDevis($authentication,$devis)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: createDevis login=".$authentication['login']);

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
        $newobject=new Propal($db);
        $newobject->socid=$devis['id_client'];
        $newobject->ref_client=$devis['ref_client'];
        $newobject->date=dol_stringtotime($devis['date'],'dayrfc');
        $newobject->note_private=$devis['note_private'];
        $newobject->note_public=$devis['note_public'];
        $newobject->statut=$devis['status'];
        $newobject->datec=$now;
        $newobject->datep=$devis['date'];
        $newobject->date_livraison=$devis['date_livraison'];
        $newobject->fin_validite=dol_stringtotime($devis['date_expiration'],'dayrfc');
        $newobject->total_ht=$devis['total_ht'];
        $newobject->total_tva=$devis['total_tva'];
        $newobject->total_ttc=$devis['total_ttc'];
        $newobject->remise=0;


	//take mode_reglement and cond_reglement from thirdparty
        $soc = new Societe($db);
        $res=$soc->fetch($newobject->socid);
        if ($res > 0) {
	    $newobject->mode_reglement_id = ($soc->mode_reglement_id?$soc->mode_reglement_id:0);		//! empty($devis['payment_mode_id'])?$devis['payment_mode_id']:$soc->mode_reglement_id;
            $newobject->cond_reglement_id  = ($soc->cond_reglement_id?$soc->cond_reglement_id:0);
        }
        else
        {
		$newobject->mode_reglement_id =0; //$devis['payment_mode_id'];
		$newobject->cond_reglement_id = 0;
		}
        // Trick because nusoap does not store data with same structure if there is one or several lines
        $arrayoflines=array();
        if (isset($devis['lines']['line'][0])) $arrayoflines=$devis['lines']['line'];
        else $arrayoflines=$devis['lines'];

		$rang=0;
        foreach($arrayoflines as $key => $line)
        {
            // $key can be 'line' or '0','1',...
            $newline=new PropaleLigne($db);
            $rang++;
		$newline->rang=$rang;
            $newline->fk_product=intval($line['product_id']);
            $newline->product_type=0;
            if ($newline->fk_product!=0)
            {
		$prod = new Product($db);
		$res=$soc->fetch($newline->fk_product);
			if ($res > 0)
			{
				$newline->product_type=$prod->fk_product_type;
			}
		}
            $newline->libelle=$line['label'];
            $newline->desc=$line['desc'];
            $newline->qty=$line['qty'];
            $newline->subprice=$line['unitprice'];
            $newline->remise_percent=$line['remise_percent'];
            $newline->tva_tx=$line['taux_tva'];
            $newline->total_ht=$line['total_ht'];
            $newline->total_tva=$line['total_tva'];
            $newline->total_ttc=$line['total_ttc'];

            $newobject->lines[]=$newline;
        }
        //var_dump($newobject->date_lim_reglement); exit;
        //var_dump($invoice['lines'][0]['type']);

        $db->begin();

        $result=$newobject->create($fuser,0);
        if ($result < 0)
        {
            $error++;
        }

        if (! $error)
        {
            $db->commit();

            //if ($newobject->statut>0)
            //{
		$newobject->valid($fuser);
		$v=floatval(version_dolibarr());
		if ($v<4)
			$newobject->generateDocument("mydoli_azur", $langs);
		else
			$newobject->generateDocument("mydoli_azur4", $langs);
            //}
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
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

/**
 * Update a Devis
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Devis		$devis				Devis
 * @return	array							Array result
 */
function updateDevis($authentication,$id,$devis)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: updateDevis login=".$authentication['login']);

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

		if ($fuser->rights->propale->lire)
		{
			$newobject=new Propal($db);
			$result=$newobject->fetch($id,"","");
			if ($result > 0)
			{
				if (($newobject->statut==0)&&(intval($devis['status'])>0)) $newobject->valid($fuser);

				$sql = "UPDATE ".MAIN_DB_PREFIX."propal SET";
				$sql.= " ref_client = '".$newobject->db->escape($devis['ref_client'])."'";
				$sql.= ",datep = '".$newobject->db->idate($devis['date'])."'";
				$sql.= ",date_livraison = ".($devis['date_livraison']?"'".$newobject->db->idate($devis['date_livraison'])."'":"NULL");
				$sql.= ",fin_validite = ".($devis['date_expiration']?"'".$newobject->db->idate($devis['date_expiration'])."'":"NULL");
				$sql.= ",note_private = '".$newobject->db->escape($devis['note_private'])."'";
				$sql.= ",note_public = '".$newobject->db->escape($devis['note_public'])."'";
				$sql.= ",total_ht = ".doubleVal($devis['total_ht']);
				$sql.= ",tva = ".doubleVal($devis['total_tva']);
				$sql.= ",total = ".doubleVal($devis['total_ttc']);
				$sql.= ",fk_statut = ".intval($devis['status']);
		$sql.= " WHERE rowid = ".$newobject->id;
		$resql=$newobject->db->query($sql);
                if (!$resql)
					{
						$error++;
						$errorcode="SQL_ERROR";
						$errorlabel=$db->lasterror();
					}
				//$newobject->statut=$devis['status'];

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal=".$id;
				$newobject->db->query($sql);

				// Trick because nusoap does not store data with same structure if there is one or several lines
				$arrayoflines=array();
				if (isset($devis['lines']['line'][0])) $arrayoflines=$devis['lines']['line'];
				else $arrayoflines=$devis['lines'];
				$rang=0;
				foreach($arrayoflines as $key => $line)
				{
					// $key can be 'line' or '0','1',...
					$newline=new PropaleLigne($db);
					$rang++;
				$newline->rang=$rang;

				$newline->product_type=0;
					$newline->fk_product=intval($line['product_id']);
					if ($newline->fk_product!=0)
					{
						$prod = new Product($db);
						$res=$prod->fetch($newline->fk_product);
						if ($res > 0)
						{
							$newline->product_type=$prod->fk_product_type;
						}
					}
					$newline->libelle=$line['label'];
					$newline->desc=$line['desc'];
					$newline->qty=$line['qty'];
					$newline->subprice=$line['unitprice'];
					$newline->remise_percent=$line['remise_percent'];
					$newline->tva_tx=$line['taux_tva'];
					$newline->total_ht=$line['total_ht'];
					$newline->total_tva=$line['total_tva'];
					$newline->total_ttc=$line['total_ttc'];

					$nl++;
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."propaldet";
					$sql.= " (fk_propal, label, description, fk_product, product_type,";
					$sql.= " qty, tva_tx, subprice, remise_percent, ";
					$sql.= " total_ht, total_tva, total_ttc, rang)";
					$sql.= " VALUES (".$id.",";
					$sql.= " ".(! empty($newline->libelle)?"'".$newline->db->escape($newline->libelle)."'":"NULL").",";
					$sql.= " '".$newline->db->escape($newline->desc)."',";
					$sql.= " ".($newline->fk_product?"'".$newline->fk_product."'":"NULL").",";
					$sql.= " '".$newline->product_type."',";
					$sql.= " ".price2num($newline->qty).",";
					$sql.= " ".price2num($newline->tva_tx).",";
					$sql.= " ".($newline->subprice?price2num($newline->subprice):"NULL").",";
					$sql.= " ".price2num($newline->remise_percent).",";
					$sql.= " ".price2num($newline->total_ht).",";
					$sql.= " ".price2num($newline->total_tva).",";
					$sql.= " ".price2num($newline->total_ttc).",";
					$sql.= " ".$rang.")";

					$resql=$newline->db->query($sql);
					if (!$resql)
					{
						$error++;
						$errorcode="SQL_ERROR";
						$errorlabel=$db->lasterror();
					}
					//$newobject->lines[]=$newline;
				}
				$result=$newobject->fetch($id,"","");
				//if (intval($devis['status'])>0)
				$v=floatval(version_dolibarr());
		if ($v<4)
			$newobject->generateDocument("mydoli_azur", $langs);
		else
			$newobject->generateDocument("mydoli_azur4", $langs);

			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
			}
        }
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}

        if (! $error)
        {
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

function factureDevis($authentication,$id='',$id_client='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: factureDevis login=".$authentication['login']." id=".$id." id_client=".$id_client);

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

	if (!$error)
	{
	// Check parameters
		if (($id=='')||($id_client==''))
		{
			$error++;
			$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id and id_client can not be empty.";
		}
	}

	if (!$error)
	{
		$fuser->getrights();
		$devis=new Propal($db);
		$result=$devis->fetch($id,'','');
		if ($result > 0)
		{
			$devis->classifyBilled();

			$now=dol_now();

			$newobject=new Facture($db);
			$newobject->socid=$devis->socid;
			$newobject->type=0;
			$newobject->ref_client=$devis->ref_client;
			$newobject->date=$now;		//dol_stringtotime($now,'dayrfc');
			$newobject->note_private=$devis->note_private;
			$newobject->note_public=$devis->note_public;
			$newobject->statut=0;	// We start with status draft
			$newobject->fk_project=$devis->fk_project;
			$newobject->date_creation=$now;
			$newobject->total_ht=$devis->total_ht;
			$newobject->total_tva=$devis->total_tva;
			$newobject->total_ttc=$devis->total_ttc;

		//take mode_reglement and cond_reglement from thirdparty
			$soc = new Societe($db);
			$res=$soc->fetch($newobject->socid);
			if ($res > 0) {
				$newobject->mode_reglement_id = ($soc->mode_reglement_id?$soc->mode_reglement_id:0);		//! empty($invoice['payment_mode_id'])?$invoice['payment_mode_id']:$soc->mode_reglement_id;
				$newobject->cond_reglement_id  = ($soc->cond_reglement_id?$soc->cond_reglement_id:0);
			}
			else
			{
				$newobject->mode_reglement_id = 0;		//$invoice['payment_mode_id'];
				$newobject->cond_reglement_id = 0;
			}

			foreach($devis->lines as $line)
			{
				$newline=new FactureLigne($db);
				$rang=$line->rang;
				$newline->rang=$rang;
				$newline->fk_product=$line->fk_product;
				$newline->product_type=0;
				if ($newline->fk_product!=0)
				{
					$prod = new Product($db);
					$res=$soc->fetch($newline->fk_product);
					if ($res > 0)
					{
						$newline->product_type=$prod->fk_product_type;
						if (empty($newline->product_type)) $newline->product_type=0;
					}
				}
				$newline->libelle=dol_htmlcleanlastbr($line->label);
				$newline->desc=dol_htmlcleanlastbr($line->desc);
				$newline->qty=$line->qty;
				$newline->subprice=$line->subprice;
				$newline->remise_percent=$line->remise_percent;
				$newline->tva_tx=$line->tva_tx;
				$newline->total_ht=$line->total_ht;
				$newline->total_tva=$line->total_tva;
				$newline->total_ttc=$line->total_ttc;

				$newobject->lines[]=$newline;
			}

			$db->begin();

			$result=$newobject->create($fuser,0);
			if ($result < 0)
			{
				$error++;
				$errorcode='KO';
				$errorlabel='Create invoice failed';
			}

			if (! $error)
			{
				$result=$newobject->validate($fuser);
				if ($result < 0)
				{
					$error++;
					$errorcode='KO';
					$errorlabel='Validate invoice failed';
				}
		    }

			if (! $error)
			{
				$db->commit();
				$newobject->generateDocument("", $langs);
				$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
			}
			else
			{
				$db->rollback();
				$error++;
				$errorcode='KO';
				$errorlabel=$db->lasterror;	//$newobject->error;
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
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'id'=>0);
	}
	return $objectresp;
}
function commandeDevis($authentication,$id='',$id_client='')
{
	global $db,$conf,$langs,$mysoc,$user;

	dol_syslog("Function: commandeDevis login=".$authentication['login']." id=".$id." id_client=".$id_client);

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

	if (!$error)
	{
	// Check parameters
		if (($id=='')||($id_client==''))
		{
			$error++;
			$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id and id_client can not be empty.";
		}
	}

	if (!$error)
	{
		$user->getrights();
		$devis=new Propal($db);
		$result=$devis->fetch($id,'','');
		if ($result > 0)
		{

			$newobject=new Commande($db);
			$result=$newobject->createFromProposal($devis);

			if ($result < 0)
			{
				$error++;
				$errorcode='KO';
				$errorlabel='Create order failed';
			}

			if (! $error)
			{
				$result=$newobject->valid($user);
				if ($result < 0)
				{
					$error++;
					$errorcode='KO';
					$errorlabel='Validate invoice failed';
				}
		    }

			if (! $error)
			{

				$newobject->generateDocument("", $langs);
				$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
			}
			else
			{
				$error++;
				$errorcode='KO';
				$errorlabel=$db->lasterror;	//$newobject->error;
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
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'id'=>0);
	}
	return $objectresp;
}
function deleteDevis($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: deleteDevis login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->propale->lire)
		{
			$devis=new Propal($db);
			$result=$devis->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$result=$devis->delete($fuser,0);
				if ($result > 0)
				{
					$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$id);
				}
				else
				{
					$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>'0');
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
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
