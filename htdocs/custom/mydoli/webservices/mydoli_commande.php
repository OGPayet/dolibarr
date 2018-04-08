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

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

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
$server->configureWSDL('WebServicesDolibarrCommande',$ns);
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
	'document',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'filename' => array('name'=>'filename','type'=>'xsd:string'),
		'mimetype' => array('name'=>'mimetype','type'=>'xsd:string'),
		'content' => array('name'=>'content','type'=>'xsd:string'),
		'length' => array('name'=>'length','type'=>'xsd:string')
	)
);

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
    'LinesArray2',
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
    'commande',
    'complexType',
    'struct',
    'all',
    '',
    array(
	'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
        'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
        'date' => array('name'=>'date','type'=>'xsd:date'),
        'date_livraison' => array('name'=>'date_livraison','type'=>'xsd:date'),
        'total_ht' => array('name'=>'total_ht','type'=>'xsd:double'),
        'total_tva' => array('name'=>'total_tva','type'=>'xsd:double'),
        'total_ttc' => array('name'=>'total_ttc','type'=>'xsd:double'),
        'note_private' => array('name'=>'note_private','type'=>'xsd:string'),
        'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
        'status' => array('name'=>'status','type'=>'xsd:int'),
        'facture' => array('name'=>'facture','type'=>'xsd:int'),
        'lines' => array('name'=>'lines','type'=>'tns:LinesArray2')
    )
);

$server->wsdl->addComplexType(
    'CommandesArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'commande' => array(
            'name' => 'commande',
            'type' => 'tns:commande',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:commande'
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getCommande',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','commande'=>'tns:commande'),
    $ns,
    $ns.'#getCommande',
    $styledoc,
    $styleuse,
    'WS to get a particular commande'
);
$server->register(
    'getCommandesForThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','commandes'=>'tns:CommandesArray2'),
    $ns,
    $ns.'#getCommandesForThirdParty',
    $styledoc,
    $styleuse,
    'WS to get all commandes of a third party'
);
$server->register(
    'createCommande',
    // Entry values
    array('authentication'=>'tns:authentication','commande'=>'tns:commande'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#createCommande',
    $styledoc,
    $styleuse,
    'WS to create an commande'
);
$server->register(
    'updateCommande',
    // Entry values
    array('authentication'=>'tns:authentication','commande'=>'tns:commande'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#updateCommande',
    $styledoc,
    $styleuse,
    'WS to update an commande'
);
$server->register(
    'factureCommande',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','id_client'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#factureCommande',
    $styledoc,
    $styleuse,
    'WS to bill a commande'
);
$server->register(
    'deleteCommande',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#deleteCommande',
    $styledoc,
    $styleuse,
    'WS to delete a particular commande'
);
$server->register(
    'generateBL',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','document'=>'tns:document'),
    $ns,
    $ns.'#generateBL',
    $styledoc,
    $styleuse,
    'WS to generate BL pdf from Commande'
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
 * Get invoice from id, ref or ref_ext.
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getCommande($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getCommande login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->rights->commande->lire)
		{
			$invoice=new Commande($db);
			$result=$invoice->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$ok=1;
				if ($fuser->admin==0)
                {
			$ok=isCommercial($invoice->socid,$fuser->id);
                }
			    if($ok==1)
			    {
					$linesresp=array();
					$i=0;
					foreach($invoice->lines as $line)
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

					  // Create invoice
					$objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'commande'=>array(
							'id' => $invoice->id,
							'ref' => $invoice->ref,
							'id_client' => $invoice->socid,
							'ref_client' => $invoice->ref_client?$invoice->ref_client:'',   // If not defined, field is not added into soap
							'date' => $invoice->date?dol_print_date($invoice->date,'dayrfc'):'',
							'date_livraison' => $invoice->date_lim_reglement?dol_print_date($invoice->date_livraison,'dayrfc'):'',
							'total_ht' => $invoice->total_ht,
							'total_tva' => $invoice->total_tva,
							'total_ttc' => $invoice->total_ttc,
							'note_private' => $invoice->note_private?$invoice->note_private:'',
							'note_public' => $invoice->note_public?$invoice->note_public:'',
							'status'=> $invoice->statut,
							'facture'=> $invoice->billed,
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
function getCommandesForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getCommandesForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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
		$linesinvoice=array();

		$sql='SELECT rowid,entity';
		$sql.=' FROM '.MAIN_DB_PREFIX.'commande';
		$sql.=" WHERE entity = ".$conf->entity;
		if ($idthirdparty != 'all' ) $sql.=" AND fk_soc = ".$db->escape($idthirdparty);

		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
                // En attendant remplissage par boucle
			    $obj=$db->fetch_object($resql);

			    $invoice=new Commande($db);
			    $invoice->fetch($obj->rowid);

			    // Sécurité pour utilisateur externe
			    if( $socid && ( $socid != $invoice->socid) )
			    {
				$error++;
				$errorcode='PERMISSION_DENIED'; $errorlabel=$invoice->socid.' User does not have permission for this request';
			    }

			    $ok=1;
				if ($fuser->admin==0)
                {
			$ok=isCommercial($invoice->socid,$fuser->id);
                }
			    if((!$error)&&($ok==1))
			    {
				// Define lines of invoice
				$linesresp=array();

// on ne lit plus les lignes de la piece. Elles seront relues quand l'utilisateur editera le devis
// 			    	foreach($invoice->lines as $line)
// 			    	{
// 			    		$linesresp[]=array(
// 	    					'id'=>$line->rang,
// 						'label'=>dol_htmlcleanlastbr($line->label),
// 						'desc'=>dol_htmlcleanlastbr($line->desc),
// 						'qty'=>$line->qty,
// 						'taux_tva'=>$line->tva_tx,
// 						'unitprice'=>$line->subprice,
// 						'remise_percent'=>$line->remise_percent,
// 						'remise_absolue'=>$line->remise,
// 					    'total_ht'=>$line->total_ht,
// 						'total_tva'=>$line->total_tva,
// 						'total_ttc'=>$line->total_ttc,
//                         'product_id'=>$line->fk_product,
//                         'product_label'=>$line->libelle
// 			    		);
// 			    	}

				// Now define invoice
				$linesinvoice[]=array(
					'id' => $invoice->id,
						'ref' => $invoice->ref,
						'id_client' => $invoice->socid,
					'ref_client' => $invoice->ref_client?$invoice->ref_client:'',   // If not defined, field is not added into soap
					'date' => $invoice->date?dol_print_date($invoice->date,'dayrfc'):'',
					'date_livraison' => $invoice->date_lim_reglement?dol_print_date($invoice->date_livraison,'dayrfc'):'',
					'total_ht' => $invoice->total_ht,
					'total_tva' => $invoice->total_tva,
					'total_ttc' => $invoice->total_ttc,
					'note_private' => $invoice->note_private?$invoice->note_private:'',
					'note_public' => $invoice->note_public?$invoice->note_public:'',
					'status'=> $invoice->statut,
					'facture'=> $invoice->billed,
					'lines' => $linesresp
				);
			    }

			    $i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
		        'commandes'=>$linesinvoice

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
 * Create an invoice
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Facture		$invoice			Invoice
 * @return	array							Array result
 */
function createCommande($authentication,$invoice)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: createCommande login=".$authentication['login']);

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
        $newobject=new Commande($db);
        $newobject->socid=$invoice['id_client'];
        $newobject->ref_client=$invoice['ref_client'];
        $newobject->date=dol_stringtotime($invoice['date'],'dayrfc');
        $newobject->note_private=$invoice['note_private'];
        $newobject->note_public=$invoice['note_public'];
        $newobject->statut=0;	// We start with status draft
        $newobject->date_creation=$now;
        $newobject->total_ht=$invoice['total_ht'];
        $newobject->total_tva=$invoice['total_tva'];
        $newobject->total_ttc=$invoice['total_ttc'];

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

        // Trick because nusoap does not store data with same structure if there is one or several lines
        $arrayoflines=array();
        if (isset($invoice['lines']['line'][0])) $arrayoflines=$invoice['lines']['line'];
        else $arrayoflines=$invoice['lines'];

		$rang=0;
        foreach($arrayoflines as $key => $line)
        {
		$newline=new OrderLine($db);
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
				if (empty($newline->product_type)) $newline->product_type=0;
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

       // if ($invoice['status'] == 1)   // We want invoice to have status validated
       // {
            $result=$newobject->valid($fuser);
            if ($result < 0)
            {
                $error++;
            }
       // }

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
            $errorlabel=$db->lastquery;		//$newobject->error;
        }

    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}
function updateCommande($authentication,$id,$invoice)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: updateCommande login=".$authentication['login']);

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

		if ($fuser->rights->commande->lire)
		{
			$newobject=new Commande($db);
			$result=$newobject->fetch($id,"","");
			if ($result > 0)
			{

				$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
				$sql.= " ref_client = '".$newobject->db->escape($invoice['ref_client'])."'";
				$sql.= ",date_commande = '".$newobject->db->idate($invoice['date'])."'";
				$sql.= ",date_livraison = '".$newobject->db->idate($invoice['date_livraison'])."'";
				$sql.= ",note_private = '".$newobject->db->escape($invoice['note_private'])."'";
				$sql.= ",note_public = '".$newobject->db->escape($invoice['note_public'])."'";
				$sql.= ",total_ht = ".doubleVal($invoice['total_ht']);
				$sql.= ",tva = ".doubleVal($invoice['total_tva']);
				$sql.= ",total_ttc = ".doubleVal($invoice['total_ttc']);
				$sql.= " WHERE rowid = ".$newobject->id;
		$resql=$newobject->db->query($sql);
                if (!$resql)
					{
						$error++;
						$errorcode="SQL_ERROR";
						$errorlabel=$db->lasterror();
					}
				//$newobject->statut=$invoice['status'];

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande=".$id;
				$newobject->db->query($sql);

				// Trick because nusoap does not store data with same structure if there is one or several lines
				$arrayoflines=array();
				if (isset($invoice['lines']['line'][0])) $arrayoflines=$invoice['lines']['line'];
				else $arrayoflines=$invoice['lines'];

				$rang=0;
				foreach($arrayoflines as $key => $line)
				{
					// $key can be 'line' or '0','1',...
					$newline=new OrderLine($db);
					$rang++;
				$newline->rang=$rang;
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
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet";
					$sql.= " (fk_commande, label, description, fk_product, product_type,";
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
				//if (intval($invoice['status'])>0)
				 $newobject->generateDocument("", $langs);
			}
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

function factureCommande($authentication,$id='',$id_client='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: factureCommande login=".$authentication['login']." id=".$id." id_client=".$id_client);

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
		$commande=new Commande($db);
		$result=$commande->fetch($id,'','');
		if ($result > 0)
		{
			$commande->classifyBilled();

			$now=dol_now();

			$newobject=new Facture($db);
			$newobject->socid=$commande->socid;
			$newobject->type=0;
			$newobject->ref_client=$commande->ref_client;
			$newobject->date=$now;		//dol_stringtotime($now,'dayrfc');
			$newobject->note_private=$commande->note_private;
			$newobject->note_public=$commande->note_public;
			$newobject->statut=0;	// We start with status draft
			$newobject->fk_project=$commande->fk_project;
			$newobject->date_creation=$now;
			$newobject->total_ht=$commande->total_ht;
			$newobject->total_tva=$commande->total_tva;
			$newobject->total_ttc=$commande->total_ttc;

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

			foreach($commande->lines as $line)
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

function deleteCommande($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: deleteCommande login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->rights->commande->lire)
		{
			$invoice=new Commande($db);
			$result=$invoice->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$result=$invoice->delete(0);
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

function generateBL($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: generateBL login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->rights->commande->lire)
		{
			$invoice=new Commande($db);
			$result=$invoice->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$v=floatval(version_dolibarr());
		if ($v<4)
			$invoice->generateDocument("mydoli_livraison", $langs);
		else
			$invoice->generateDocument("mydoli_livraison4", $langs);


				$objectref = dol_sanitizeFileName($invoice->ref);
				$dir = $conf->commande->dir_output . "/" . $objectref;
				$original_file = $dir . "/" . $objectref . "_BL.pdf";

				$f = fopen($original_file,'r');
				$content_file = fread($f,filesize($original_file));

				$objectret = array(
					'filename' => basename($original_file),
					'mimetype' => dol_mimetype($original_file),
					'content' => base64_encode($content_file),
					'length' => filesize($original_file)
				);

				// Create return object
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'document'=>$objectret
				);

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
