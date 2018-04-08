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
require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
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
$server->configureWSDL('WebServicesDolibarrLivraison',$ns);
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
        'idLivraison' => array('name'=>'idLivraison','type'=>'xsd:int'),
        'idLineCde' => array('name'=>'idLineCde','type'=>'xsd:int'),
        'desc' => array('name'=>'desc','type'=>'xsd:string'),
        'qty' => array('name'=>'qty','type'=>'xsd:double'),
        'total_ht' => array('name'=>'total_ht','type'=>'xsd:double'),
        // From product
        'product_id' => array('name'=>'product_id','type'=>'xsd:int')
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
    'livraison',
    'complexType',
    'struct',
    'all',
    '',
    array(
	'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
        'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
        'id_commande' => array('name'=>'id_commande','type'=>'xsd:int'),
        'date' => array('name'=>'date','type'=>'xsd:date'),
        'total_ht' => array('name'=>'total_ht','type'=>'xsd:double'),
        'note_private' => array('name'=>'note_private','type'=>'xsd:string'),
        'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
        'status' => array('name'=>'status','type'=>'xsd:int'),
        'lines' => array('name'=>'lines','type'=>'tns:LinesArray2')
    )
);

$server->wsdl->addComplexType(
    'LivraisonArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'livraison' => array(
            'name' => 'livraison',
            'type' => 'tns:livraison',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:livraison'
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getLivraison',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','livraison'=>'tns:livraison'),
    $ns,
    $ns.'#getLivraison',
    $styledoc,
    $styleuse,
    'WS to get a particular livraison'
);
$server->register(
    'getLivraisonsForThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','livraisons'=>'tns:LivraisonArray2'),
    $ns,
    $ns.'#getLivraisonForThirdParty',
    $styledoc,
    $styleuse,
    'WS to get all livraisons of a third party'
);
$server->register(
    'createLivraison',
    // Entry values
    array('authentication'=>'tns:authentication','id_commande'=>'xsd:string','id_client'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#createLivraison',
    $styledoc,
    $styleuse,
    'WS to create a livraison'
);
$server->register(
    'updateLivraison',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:int','livraison'=>'tns:livraison'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#updateLivraison',
    $styledoc,
    $styleuse,
    'WS to update a livraison'
);
$server->register(
    'deleteLivraison',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#deleteLivraison',
    $styledoc,
    $styleuse,
    'WS to delete a particular livraison'
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
    'WS to generate BL pdf from livraison'
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
function getLivraison($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getLivraison login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		//if ($fuser->rights->livraison->lire)
		//{
			$invoice=new Livraison($db);
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
				$total_ht=0;
				$idCde=0;
				$line=$invoice->lines[0];
					$commande=New OrderLine($db);
					$commande->fetch($line->fk_origin_line);
					$idCde=$commande->fk_commande;

				$linesresp=array();
					$i=1;
					foreach($invoice->lines as $line)
					{
						//var_dump($line); exit;
						$total_ht+=$line->qty_asked*$line->total_ht;
						$linesresp[]=array(
							'id'=>$i,
							'idLivraison' => $invoice->id,
							'idLineCde'  => $line->fk_origin_line,
							'desc'=>dol_htmlcleanlastbr($line->description),
							'qty'=>$line->qty_asked,
							'total_ht'=>$line->total_ht,
							'product_id'=>$line->fk_product
						);
						$i++;
					}

					  // Create invoice
					$date=$invoice->date_delivery?dol_print_date($invoice->date_delivery,'dayrfc'):'';
				if ($date=='') $date=dol_print_date($invoice->date_creation,'dayrfc');

					$objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'livraison'=>array(
							'id' => $invoice->id,
							'ref' => $invoice->ref,
							'id_client' => $invoice->socid,
							'id_commande' => $idCde,
							'ref_client' => $invoice->ref_customer?$invoice->ref_customer:'',   // If not defined, field is not added into soap
							'date' => $date,
							'total_ht' => $total_ht,
							'note_private' => $invoice->note_private?$invoice->note_private:'',
							'note_public' => $invoice->note_public?$invoice->note_public:'',
							'status'=> $invoice->statut,
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
		//}
		//else
		//{
		//	$error++;
		//	$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		//}
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
function getLivraisonsForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getLivraisonsForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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
		$sql.=' FROM '.MAIN_DB_PREFIX.'livraison';
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

			    $invoice=new Livraison($db);
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
				$idCde=0;
				$linesresp=array();

					$line=$invoice->lines[0];
					$commande=New OrderLine($db);
					$commande->fetch($line->fk_origin_line);
					$idCde=$commande->fk_commande;
					$total_ht=0;
					foreach($invoice->lines as $line)
					{
						//var_dump($line); exit;
						$total_ht+=$line->qty_asked*$line->total_ht;
					}
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
				$date=$invoice->date_delivery?dol_print_date($invoice->date_delivery,'dayrfc'):'';
				if ($date=='') $date=dol_print_date($invoice->date_creation,'dayrfc');

				$linesinvoice[]=array(
					'id' => $invoice->id,
						'ref' => $invoice->ref,
						'id_client' => $invoice->socid,
						'id_commande' => $idCde,
						'ref_client' => $invoice->ref_customer?$invoice->ref_customer:'',   // If not defined, field is not added into soap
						'date' => $date,
						'total_ht' => $total_ht,
						'note_private' => $invoice->note_private?$invoice->note_private:'',
						'note_public' => $invoice->note_public?$invoice->note_public:'',
						'status'=> $invoice->statut,
					'lines' => $linesresp
				);
			    }

			    $i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
		        'livraisons'=>$linesinvoice

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
function createLivraison($authentication,$id_commande,$id_client)
{
    global $db,$conf,$langs,$mysoc,$user;

    $now=dol_now();

    dol_syslog("Function: createLivraison login=".$authentication['login']);

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

    if (! $error)
    {
	$commande = new Commande($db);
		$commande->fetch($id_commande);
		$commande->fetch_lines();
	$db->begin();
	    $object = new Livraison($db);
	    $object->date_delivery   = $now;
	    $object->date_valid   = $object->date_delivery;
		$object->commande_id      = $id_commande;
		$object->origin_id      = $id_commande;
		$object->origin="commande";
		$object->socid= $commande->socid;
	foreach($commande->lines as $line)
		{
			$livLine = new LivraisonLigne($db);
			$livLine->origin_line_id    = $line->id;
			$livLine->libelle           = $line->label;
			$livLine->description       = $line->desc;
			$livLine->qty               = $line->qty;
			$livLine->fk_product        = $line->fk_product;
			$livLine->price				= $line->price;
			$object->lines[] = $livLine;
			//$object->addline($line->id,$line->qty);
		}
		$ret=$object->create($user);
		if ($ret > 0)
		{
			$db->commit();
			foreach($commande->lines as $line)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."livraisondet SET";
				$sql.= " total_ht=".$line->price;
				$sql.= " WHERE fk_livraison = ".$object->id." AND fk_origin_line=".$line->id;
				$db->query($sql);
			}
			$object->valid($user);
			$object->generateDocument("mydoli_typhon", $langs);
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$object->id,'ref'=>$object->ref);

		}
		else
		{
			$db->rollback();
			$error++;
			$errorcode='KO';
            $errorlabel=$db->lasterror;
		}
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

function updateLivraison($authentication,$id,$livraison)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: updateLivraison login=".$authentication['login']);

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

		//if ($fuser->rights->livraison->lire)
		//{
			$newobject=new Livraison($db);
			$result=$newobject->fetch($id,"","");
			if ($result > 0)
			{
				//$newobject->ref_customer=$invoice['ref_client'];
				//$newobject->date_delivery=$invoice['date_livraison'];

				$sql = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
				$sql.= " ref_customer='".$db->escape($livraison['ref_client'])."'";
				$sql.= ", date_delivery = '".$livraison['date']." 00:00:00'";
				$sql.= ", note_private = '".$livraison['note_private']."'";
				$sql.= ", note_public = '".$livraison['note_public']."'";
				$sql.= " WHERE rowid = ".$id;

				$resql=$newobject->db->query($sql);
			    if ($resql)
				{
					$result=$newobject->fetch($id,"","");
					$newobject->generateDocument("mydoli_typhon", $langs);
				}
				else
				{
					$error++;
					$errorcode="KO";
					$errorlabel=$db->lasterror();
				}
			}
        //}


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

function deleteLivraison($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: deleteLivraison login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		//if ($fuser->rights->livraison->lire)
		//{
			$invoice=new Livraison($db);
			$result=$invoice->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				if ($conf->expedition->dir_output)
				{
					$objectref = dol_sanitizeFileName($invoice->ref);
					$dir = $conf->expedition->dir_output."/receipt/" . $objectref;
					$file = $dir . "/" . $objectref . "_sign.jpg";
					if (file_exists($file)) unlink($file);
					$file = $dir . "/thumbs/" . $objectref . "_sign_mini.jpg";
					if (file_exists($file)) unlink($file);
					$file = $dir . "/thumbs/" . $objectref . "_sign_small.jpg";
					if (file_exists($file)) unlink($file);
					rmdir($dir . "/thumbs");
				}
				$result=$invoice->delete();
				if ($result > 0)
				{
					$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$id);
				}
				else
				{
					$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>$result),'id'=>'0');
				}

			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		//}
		//else
		//{
		//	$error++;
		//	$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		//}
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
