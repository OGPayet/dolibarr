<?php
/* Copyright (C) 2013-2017		Charlie BENKE		<charlie@patas-monkey.com>
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
 *  \file	   htdocs/restock/restockCmdClient.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';

dol_include_once('restock/class/restock.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->loadLangs(array('admin','orders','sendings','companies','bills','propal','supplier_proposal','deliveries','products','stocks','productbatch'));


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'commande', $id, '');
//$result=restrictedArea($user,'produit','','','','','','');

$action=GETPOST("action");
$id = GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$object = new Commande($db);


if (! $object->fetch($id, $ref) > 0)
	dol_print_error($db);

$object->fetch_thirdparty();
$id = $object->id;

if ($conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN > 0) {
	header("Location: restockCmdClientDirect.php?id=".$id);
	exit;
}
/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"])) {
	$sref="";
	$snom="";
	$search_categ=0;
}


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

$soc = new Societe($db);
$soc->fetch($object->socid);

$restock_static=new Restock($db);

if ( isset($_POST['reload']) ) $action = 'restock';

if($action == 'confirm_direct') {
	if($user->rights->commande->order_advance->send && $user->rights->expedition->creer && $user->rights->fournisseur->commande->creer && $user->rights->fournisseur->supplier_order_advance->validate && $user->rights->fournisseur->commande->commander && $user->rights->fournisseur->commande->receptionner) {
		$fournids = explode(",",GETPOST('fournid'));

		$expedition = array();

		foreach($fournids as $fournid) {
			/*
			 * Validation
			 */
			$object = new CommandeFournisseur($db);

			$ret = $object->fetch($fournid);
			if ($ret < 0) dol_print_error($db,$object->error);
			$ret = $object->fetch_thirdparty();
			if ($ret < 0) dol_print_error($db,$object->error);

			$object->date_commande=dol_now();
			$result = $object->valid($user);
			if ($result	>= 0)
			{
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($fournid); // Reload to get new records

					$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($result < 0) dol_print_error($db,$result);
				}
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
			/*
			 * Approbation
			 */
		   $idwarehouse=GETPOST('idwarehouse', 'int');

			$qualified_for_stock_change=0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(2);
			}
			else
			{
				$qualified_for_stock_change=$object->hasProductsOrServices(1);
			}

			// Check parameters
			if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change)	// warning name of option should be STOCK_CALCULATE_ON_SUPPLIER_APPROVE_ORDER
			{
				if (! $idwarehouse || $idwarehouse == -1)
				{
					$error++;
					setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
					$action='';
				}
			}

			if (! $error)
			{
				$result	= $object->approve($user, $idwarehouse, ($action=='confirm_approve2'?1:0));
				if ($result > 0)
				{
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
						if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			/*
			 * Passage en commande
			 */
			$result = $object->commande($user, GETPOST("order_date"),	GETPOST("order_methode"), GETPOST('order_comment'));
			if ($result > 0)
			{
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$object->generateDocument("muscadet_restock", $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
				$action = '';
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}

			/*
			 * Reception des commandes
			 */
			if (GETPOST("shipping_methode") != '')
			{
                if (!GETPOST("shipping_date")) {
                    $error++;
                    setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("DeliveryDate")), 'errors');
                } else {
                    $result = $object->Livraison($user, GETPOST("shipping_date"), GETPOST("shipping_methode"), GETPOST("shipping_comment"));   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
                    if ($result > 0)
                    {
                        $langs->load("deliveries");
                        setEventMessages($langs->trans("DeliveryStateSaved"), null);
                        $action = '';
                    }
                    else if($result == -3)
                    {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                    else
                    {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                }
			}
			else
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Delivery")), null, 'errors');
			}
		}

		/*
		 * Dispatch product
		 */
		$error = 0;

		$db->begin();

		$pos = 0;
		foreach ($_POST as $key => $value)
		{
			// without batch module enabled
			if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg))
			{
				$pos ++;

				// $numline=$reg[2] + 1; // line of product
				$numline = $pos;
				$prod = "product_" . $reg[1] . '_' . $reg[2];
				$qty = "qty_" . $reg[1] . '_' . $reg[2];
				$ent = "entrepot_" . $reg[1] . '_' . $reg[2];
				$pu = "pu_" . $reg[1] . '_' . $reg[2]; // This is unit price including discount
				$fk_commandefourndet = "fk_commandefourndet_" . $reg[1] . '_' . $reg[2];

				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = ".$id." and fk_product = ".GETPOST($prod, 'int');
				$resql=$db->query($sql);
				if ($resql)	{
					$obj = $db->fetch_object($resql);

					$expedition["idl".$pos] =  $obj->rowid;
					$expedition["ent1".$pos] =  GETPOST($ent, 'int');
					$expedition["qtyl".$pos] =  GETPOST($qty, 'int');
				}

				// We ask to move a qty
				if (GETPOST($qty) > 0) {
					if (! (GETPOST($ent, 'int') > 0)) {
						dol_syslog('No dispatch for line ' . $key . ' as no warehouse choosed');
						$text = $langs->transnoentities('Warehouse') . ', ' . $langs->transnoentities('Line') . ' ' . ($numline);
						setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
						$error ++;
					}

					if (! $error) {
						$result = $object->dispatchProduct($user, GETPOST($prod, 'int'), GETPOST($qty), GETPOST($ent, 'int'), GETPOST($pu), GETPOST('comment'), '', '', '', GETPOST($fk_commandefourndet, 'int'), $notrigger);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error ++;
						}
					}
				}
			}
		}

		if (! $error) {
			$result = $object->calcAndSetStatusDispatch($user, GETPOST('closeopenorder')?1:0, GETPOST('comment'));
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error ++;
			}
		}

		if (! $notrigger && ! $error) {
			global $conf, $langs, $user;
			// Call trigger

			$result = $object->call_trigger('ORDER_SUPPLIER_DISPATCH', $user);
			// End call triggers

			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error ++;
			}
		}

		if ($result >= 0 && ! $error) {
			$db->commit();
		} else {
			$db->rollback();
		}

		/*
		 * Creation des exp�ditions
		 */
		$error=0;
		$predef='';

		$db->begin();

		$objectexp = new Expedition($db);
		$extrafieldsexp = new ExtraFields($db);
		$extralabelsexp = $extrafieldsexp->fetch_name_optionals_label($objectexp->table_element);

		$objectexp->origin				= 'commande';
		$objectexp->origin_id			= $id;
		$objectexp->weight				= GETPOST('weight','int')==''?"NULL":GETPOST('weight','int');
		$objectexp->sizeH				= GETPOST('sizeH','int')==''?"NULL":GETPOST('sizeH','int');
		$objectexp->sizeW				= GETPOST('sizeW','int')==''?"NULL":GETPOST('sizeW','int');
		$objectexp->sizeS				= GETPOST('sizeS','int')==''?"NULL":GETPOST('sizeS','int');
		$objectexp->size_units			= 0;
		$objectexp->weight_units		= 0;

		$objectsrc = new Commande($db);
		$objectsrc->fetch($objectexp->origin_id);
		$extrafieldssrc = new ExtraFields($db);
		$extralabelssrc = $extrafieldssrc->fetch_name_optionals_label($objectsrc->table_element);
		$ret = $extrafieldssrc->setOptionalsFromPost($extralabelssrc, $objectsrc);
		if ($ret < 0) $error++;
		$objectsrc->update($user);
		$db->commit();

		$objectexp->socid					= $objectsrc->socid;
		$objectexp->fk_delivery_address	= $objectsrc->fk_delivery_address;

		// Open DSI -- Set default model of document -- Begin
		if (!empty($conf->global->EXPEDITION_ADDON_PDF)) {
            $objectexp->model_pdf = $conf->global->EXPEDITION_ADDON_PDF;
        }
        // Open DSI -- Set default model of document -- End

		$stockLine = array();

		$num=count($objectsrc->lines);
		$totalqty=0;

		for ($i = 1; $i <= $num; $i++)
		{
			$idl="idl".$i;

			$sub_qty=array();
			$subtotalqty=0;

			$stockLocation="ent1".$i;
			$qty = "qtyl".$i;

			if (isset($expedition[$stockLocation]))
			{
				// save sub line of warehouse
				$stockLine[$i]['qty']=$expedition[$qty];
				$stockLine[$i]['warehouse_id']=$expedition[$stockLocation];
				$stockLine[$i]['ix_l']=$expedition[$idl];

				$totalqty+=$expedition[$qty];
			}
			else
			{
				//var_dump(GETPOST($qty,'int')); var_dump($_POST); var_dump($batch);exit;
				//shipment line for product with no batch management and no multiple stock location
				if ($expedition[$qty] > 0) $totalqty+$expedition[$qty];
			}
		}

		if ($totalqty > 0)		// There is at least one thing to ship
		{
			for ($i = 1; $i <= $num; $i++)
			{
				$qty = "qtyl".$i;
				// not batch mode
				if (isset($stockLine[$i]))
				{
					if ($stockLine[$i]['qty']>0)
					{
						$ret=$objectexp->addline($stockLine[$i]['warehouse_id'], $stockLine[$i]['ix_l'], $stockLine[$i]['qty'], $array_options[$i]);
						$objectexp->fetch_lines();
						$db->commit();
						if ($ret < 0)
						{
							setEventMessages($objectexp->error, $objectexp->errors, 'errors');
							$error++;
						}
					}
				}
				else
				{
					if ($expedition[$qty] > 0 || ($expedition[$qty] == 0 && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS))
					{
						$ent = "entl".$i;
						$idl = "idl".$i;
						$entrepot_id = is_numeric($expedition[$ent])?$expedition[$ent]:GETPOST('entrepot_id','int');
						if ($entrepot_id < 0) $entrepot_id='';
						if (! ($objectsrc->lines[$i]->fk_product > 0)) $entrepot_id = 0;

						$ret=$objectexp->addline($entrepot_id, $expedition[$idl], $expedition[$qty], $array_options[$i]);
						$db->commit();
						if ($ret < 0)
						{
							setEventMessages($objectexp->error, $objectexp->errors, 'errors');
							$error++;
						}
					}
				}
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafieldsexp->setOptionalsFromPost($extralabelsexp, $objectexp);
			if ($ret < 0) $error++;

			if (! $error)
			{
				$ret=$objectexp->create($user);		// This create shipment (like Odoo picking) and line of shipments. Stock movement will when validating shipment.
				$db->commit();
				if ($ret <= 0)
				{
					setEventMessages($objectexp->error, $objectexp->errors, 'errors');
					$error++;
				}
			}
		}
		else
		{
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("QtyToShip").'/'.$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
			$error++;
		}

		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
		exit;
	}
}

// header forwarding issue
// en cas de createrestock, comme il y a redirection ensuite, on n'affiche pas la page
if ($action!="createrestock") {
	$title=$langs->trans("RestockOrderProduct");

	llxHeader('', $title,'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes','');
	$head = commande_prepare_head($object);
	dol_fiche_head($head, 'restock', $langs->trans("CustomerOrder"), 0, 'order');

	$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	if (version_compare(DOL_VERSION, "5.0.0") >= 0) {
		$morehtmlref='<div class="refidno">';
		// Ref customer
		$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$langs->load("projects");
			$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '"';
				$morehtmlref.=' title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else {
				$morehtmlref.='';
			}
		}
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
	} else {

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print "</td></tr>";

		// Ref commande client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		print $object->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';
		print '</table><br><br>';
	}
}
// Direct shipping
if ($action == 'direct') {
	if($user->rights->commande->order_advance->send && $user->rights->expedition->creer && $user->rights->fournisseur->commande->creer && $user->rights->fournisseur->supplier_order_advance->validate && $user->rights->fournisseur->commande->commander && $user->rights->fournisseur->commande->receptionner) {
		// derni�re �tape : la cr�ation des commandes fournisseurs
		// on r�cup�re la liste des produits � commander
		$tblproduct=explode("-", GETPOST("prodlist"));

		// on va utiliser un tableau pour stocker les commandes fournisseurs
		$tblCmdeFourn=array();
		// on parcourt les produits pour r�cup�rer les fournisseurs, les produits et les quantit�s
		foreach ($tblproduct as $idproduct) {
			$numlines=count($tblCmdeFourn);
			$lineoffourn = -1;
			if (GETPOST("fourn-".$idproduct)) {
				$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
				if ($tblfourn[0]) {
					for ($j = 0 ; $j < $numlines ; $j++)
						if ($tblCmdeFourn[$j][0] == $tblfourn[0])
							$lineoffourn =$j;

					// si le fournisseur n'est pas d�ja dans le tableau des fournisseurs
					if ($lineoffourn == -1) {
						$tblCmdeFourn[$numlines][0] = $tblfourn[0];
						$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct),
										$tblfourn[1], $tblfourn[2], $tblfourn[3]));
					} else {
						$tblCmdeFourn[$lineoffourn][1] = array_merge(
										$tblCmdeFourn[$lineoffourn][1],
										array(array($idproduct, GETPOST("prd-".$idproduct),
										$tblfourn[1], $tblfourn[2], $tblfourn[3]))
						);
					}
				}
			}
		}

		$tblIdCmdFourn = array();
		// on va maintenant cr�er les commandes fournisseurs
		foreach ($tblCmdeFourn as $CmdeFourn) {
			$idCmdFourn = 0;
			// si il on charge les commandes fournisseurs brouillons
			if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
				// on v�rifie qu'il n'y a pas une commande fournisseur d�j� active
				$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cof';
				$sql.= ' WHERE fk_soc='.$CmdeFourn[0];
				$sql.= ' AND fk_statut=0';
				$sql.= ' AND entity='.$conf->entity;
				if  (	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 2
					||	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 4)
					$sql.= ' AND fk_user_author='.$user->id;

				$resql = $db->query($sql);
				if ($resql) {
					$objp = $db->fetch_object($resql);
					$idCmdFourn = $objp->rowid;
				}
				$objectcf = new CommandeFournisseur($db);
				$objectcf->origin = "commande";
				$objectcf->origin_id = GETPOST("id");
				$objectcf->fetch($idCmdFourn);
				// on ajoute le lien
				$ret = $objectcf->add_object_linked();
			}

			// en cr�ation
			if ($idCmdFourn == 0) {
				$objectfournisseur = new Fournisseur($db);
				$objectfournisseur->fetch($CmdeFourn[0]);

				$objectcf = new CommandeFournisseur($db);
				$objectcf->ref_supplier  	= GETPOST("reforderfourn");
				$objectcf->socid		 	= $CmdeFourn[0];
				$objectcf->note_private	= '';
				$objectcf->note_public   	= '';
				$objectcf->origin_id = GETPOST("id");

				$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
				$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;

				$objectcf->origin = "commande";
				$objectcf->linked_objects[$objectcf->origin] = $objectcf->origin_id;
				$idCmdFourn = $objectcf->create($user);
			}

			// ensuite on boucle sur les lignes de commandes
			foreach ($CmdeFourn[1] as $lgnCmdeFourn) {
				$idlgnFourn = 0;
				// on v�rifie qu'il n'y a pas d�j� une ligne de commande pour ce produit
				$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cofd';
				$sql.= ' WHERE fk_commande='.$idCmdFourn;
				$sql.= ' AND fk_product='.$lgnCmdeFourn[0];
				$resql = $db->query($sql);
				if ($resql) {
					$objp = $db->fetch_object($resql);
					$idlgnFourn = ($objp->rowid?$objp->rowid:0);
				}

				// si pas de ligne existante ou cr�ation d'une ligne � chaque fois
				if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
					// on cree la commande fournisseur
					$result=$objectcf->addline(
									'', 0,
									$lgnCmdeFourn[1],	// $qty
									$lgnCmdeFourn[3],	// TxTVA
									0, 0,
									$lgnCmdeFourn[0],	// $fk_product
									$lgnCmdeFourn[2],	// $fk_prod_fourn_price
									0, 					// $fourn_ref
									$lgnCmdeFourn[4],	// $remise_percent
									'HT',				// $price_base_type
									0, 0				// type
					);

					// r�cup de l'id de la que l'on vient de cr�er
					$sql = 'SELECT rowid from '.MAIN_DB_PREFIX.'commande_fournisseurdet';
					$sql.= ' WHERE fk_commande = '.$idCmdFourn;
					$sql.= ' ORDER BY rowid desc';
					$resql = $db->query($sql);

					if ($resql) {
						$objcf = $db->fetch_object($resql);
						$idlgnFourn = $objcf->rowid;
					}
				} else {
					$tmpcmdeligncmdefourn= new CommandeFournisseurLigne($db);
					$tmpcmdeligncmdefourn->fetch($idlgnFourn);
					$result=$objectcf->updateline(
									$idlgnFourn,
									$tmpcmdeligncmdefourn->desc,
									$tmpcmdeligncmdefourn->subprice,
									$tmpcmdeligncmdefourn->qty + $lgnCmdeFourn[1],
									$tmpcmdeligncmdefourn->remise_percent,
									$tmpcmdeligncmdefourn->tva_tx,
									$tmpcmdeligncmdefourn->localtax1_tx=0,
									$tmpcmdeligncmdefourn->localtax2_tx=0,
									'HT', 0, 0
					);
				}

				// on enregistre l'id pour la ligne de la commande client
				// attention, si le produit est sur deux ligne dans la commande client cela d�conne

				$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
				$sql.= ' SET fk_commandefourndet = '.$idlgnFourn;
				$sql.= ' WHERE fk_product = '.$lgnCmdeFourn[0];
				$sql.= ' AND fk_commande = '. $id;
				$resqlupdate = $db->query($sql);
			}
			$restock_static->add_contact_delivery_client($id,$idCmdFourn);

			$tblIdCmdFourn[] = $idCmdFourn;
		}

		$array_fourn = array();
		foreach ($tblIdCmdFourn as $CmdeFourn) {
			$array_fourn[] = $CmdeFourn;
		}
        //-------------------------
        // Modification - Open-DSI - Begin
        $object->fetch_optionals();
        // Modification - Open-DSI - End
        //-------------------------

		// Create an array for form
		$restock_static=new Restock($db);
		$liv = array();
		$liv[''] = '&nbsp;';
		$liv['tot']	= $langs->trans("CompleteOrNoMoreReceptionExpected");
		$liv['par']	= $langs->trans("PartialWoman");
		$liv['nev']	= $langs->trans("NeverReceived");
		$liv['can']	= $langs->trans("Canceled");
		$formquestion = array(
							array(
								'text' => "<b>".$langs->trans("PassationOrder")."</b>",
								array('type' => 'date', 'name' => 'order_date', 'label' => $langs->trans("OrderDate"), 'value' => dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'))),
								array('type' => 'select', 'name' => 'order_methode', 'label' => $langs->trans("OrderMode"), 'values' =>  $restock_static->selectInputMethodRestock(GETPOST('methodecommande'), "methodecommande", 1)),
								array('type' => 'text', 'name' => 'order_comment', 'label' => $langs->trans("Comment"), 'value' =>  GETPOST('comment')),
                                //-------------------------
                                // Modification - Open-DSI - Begin
                                array('type' => 'hidden', 'name' => 'options_companyrelationships_fk_soc_benefactor', 'value' => $object->array_options['options_companyrelationships_fk_soc_benefactor'])
                                // Modification - Open-DSI - End
                                //-------------------------
							),
							array(
								'text' => "<b>".$langs->trans("ReceptionOrder")."</b>",
								array('type' => 'date', 'name' => 'shipping_date', 'label' => '<b>' . $langs->trans("DeliveryDate") . '</b>', 'value' => dol_mktime(0, 0, 0,GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'))),
								array('type' => 'select', 'name' => 'shipping_methode', 'label' => $langs->trans("Delivery"), 'values' => $liv),
								array('type' => 'text', 'name' => 'shipping_comment', 'label' => $langs->trans("Comment"), 'value' =>  GETPOST('comment'))
							),
							array(
								array('type' => 'hidden', 'name' => 'fournid', 'value' => implode(",",$array_fourn)),
								array('type' => 'hidden', 'name' => 'options_expd', 'value' => 1)
							),
							array(
								'text' => "<b>".$langs->trans("DispatchOrder")."</b>",
								array('type' => 'dispatch', 'name' => 'dispatch', 'value' => implode(",",$array_fourn))
							)
						);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $restock_static->formconfirmRestock($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('PopTitle'), $langs->trans('PopQuestion', $object->ref), 'confirm_direct', $formquestion, 'yes', 0, 500, 800);
		print $formconfirm;

		print '<script>
					$(document).ready(function() {
						 $("form").submit(function(e){
							var error = "";
							if(!$("#shipping_date").val()) {
								error += "'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("DeliveryDate")).'<br/>";
							}
							if(!$("#shipping_methode").val() || $("#shipping_methode").val() == "-1") {
								error += "'.$langs->trans("ErrorSelectShipping").'<br/>";
							}
							$("[id^=entrepot_]").each(function() {
								if(!$(this).val() || $(this).val() == "-1") {
									error += "'.$langs->trans("ErrorSelectDispatch").'<br/>";
								}
							});
							if(error != "") {
								e.preventDefault();
								$.jnotify(error, "error");
							}
						});
					});
				</script>';
	}
}
if ($action=="") {
    if ($object->statut == Commande::STATUS_DRAFT) {
        print '<div class="center">' . $langs->trans("RestockOrderMustBeValidatedBeforeToProcess") . '</div>';
    } else {
        $liste_contact = $object->liste_contact();
        $contact_shipping = false;
        if ($liste_contact) {
            foreach ($liste_contact as $contact) {
                if ($contact['code'] == 'SHIPPING') {
                    $contact_shipping = true;
                }
            }
        }
        if ($contact_shipping == false) {
            setEventMessages($langs->trans('EmptyContact'), null, 'errors');
        }

        // premiere �tape : la d�termination des quantit� � commander
        print '<form action="restockCmdClient.php" method="post" name="formulaire">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

        print '<input type="hidden" name="action" value="restock">';
        print '<input type="hidden" name="id" value="' . $id . '">';

        $tblRestock = array();

        // on r�cup�re les produits pr�sents dans la commande
        $tblRestock = $restock_static->get_array_product_cmde_client($tblRestock, $id);

        // on g�re la d�composition des produits
        $tblRestockTemp = array();

        foreach ($tblRestock as $lgnRestock) {

            // on r�cup�re la composition et les quantit�s
            $tbllistofcomponent = $restock_static->getcomponent($lgnRestock->id, 1);

            foreach ($tbllistofcomponent as $lgncomponent) {
                $numlines = count($tblRestockTemp);
                $lineofproduct = -1;
                // on regarde si on trouve d�j� le produit dans le tableau
                for ($j = 0; $j <= $numlines; $j++)
                    if ($tblRestockTemp[$j]->id == $lgncomponent[0])
                        $lineofproduct = $j;

                // si produit d�ja r�f�renc�, on ajoute au tableau en multipliant par la quantit� du composant
                if ($lineofproduct >= 0)
                    $tblRestockTemp[$lineofproduct]->nbCmdeClient += $lgncomponent[1] * $lgnRestock->nbCmdeClient;
                else {
                    // on rajoute une ligne dans le tableau
                    $tblRestockTemp[$numlines] = new Restock($db);
                    $tblRestockTemp[$numlines]->id = $lgncomponent[0];
                    $tblRestockTemp[$numlines]->nbCmdeClient = $lgncomponent[1] * $lgnRestock->nbCmdeClient;
                    $tblRestockTemp[$numlines]->MntCmdeClient = $lgnRestock->MntCmdeClient;
                    $numlines++;
                }
            }
        }

        $tblRestock = $restock_static->enrichir_product($tblRestockTemp);

        // Lignes des titres
        print '<table class="liste" width="100%">';
        print "<tr class=\"liste_titre\">";
        print '<td class="liste_titre" align="left">' . $langs->trans("Ref") . '</td>';
        print '<td class="liste_titre" align="left">' . $langs->trans("Label") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("SellingPrice") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("BuyingPriceMinShort") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("Ordered") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("PhysicalStock") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("StockLimit") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("AlreadyOrder2") . '</td>';
        print '<td class="liste_titre" align="right">' . $langs->trans("QtyRestock") . '</td>';
        print "</tr>\n";

        // on cr�e la liste des choses � cr�er
        $idprodlist = "";
        $cmdedetlist = "";

        $product_static = new Product($db);
        foreach ($tblRestock as $lgnRestock) {
            $var = !$var;
            print "<tr " . $bc[$var] . ">";
            $idprodlist .= $lgnRestock->id . "-";

            print '<td class="nowrap">';
            $product_static->id = $lgnRestock->id;
            $product_static->ref = $lgnRestock->ref_product;
            $product_static->type = 0;
            print $product_static->getNomUrl(1, '', 24);
            print '</td>';
            print '<td align="left">' . $lgnRestock->libproduct . '</td>';
            // on affiche le prix de vente de la commande
            print '<td align="right">' . price($lgnRestock->PrixVenteCmdeHT) . '</td>';
            print '<td align="right">' . price($lgnRestock->PrixAchatHT) . '</td>';
            print '<td align="right">' . $lgnRestock->nbCmdeClient . '</td>';
            print '<td align="right">' . $lgnRestock->StockQty . '</td>';
            print '<td align="right">' . $lgnRestock->StockQtyAlert . '</td>';
            print '<td align="right">' . $lgnRestock->nbCmdFourn . '</td>';
            $product_fourn = new ProductFournisseur($db);
            $product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
            if (count($product_fourn_list) > 0) {
                // d�termination du besoin
                $estimedNeed = $lgnRestock->nbCmdeClient;
                // si on travail en r�assort, on ne prend pas en compte le stock et les commandes en cours
                if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
                    $estimedNeed -= $lgnRestock->StockQty;

                if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3)
                    $estimedNeed -= $lgnRestock->nbCmdFourn;

                // si il y a encore du besoin, (on a vid� toute le stock et les commandes)
                if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
                    if (($estimedNeed > 0) && ($lgnRestock->StockQtyAlert > 0))
                        $estimedNeed += $lgnRestock->StockQtyAlert;

                if ($estimedNeed < 0)  // si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
                    $estimedNeed = 0;

                print '<td align="right">';
                print '<input type=text size=5 name="prd-' . $lgnRestock->id . '" value="' . round($estimedNeed) . '"></td>';
            } else {
                print '<td align="right">';
                print $langs->trans("NoFournish");
                print '</td>';
            }
            print "</tr>\n";
        }

        print '</table>';
        // pour m�moriser les produits � r�stockvisionner
        // on vire le dernier '-' si la prodlist est aliment�
        if ($idprodlist)
            $idprodlist = substr($idprodlist, 0, -1);
        print '<input type=hidden name="prodlist" value="' . $idprodlist . '"></td>';

        /*
         * Boutons actions
        */
        print '<div class="tabsAction">';
        print '<br><center><input type="submit" class="button" name="bouton" value="' . $langs->trans('RestockOrder') . '"></center>';
        print '</div >';

        print '</form >';
    }
} elseif ($action=="restock") {
	// deuxieme �tape : la s�lection des fournisseur
	print '<form action="restockCmdClient.php" method="post" name="formulaire" id="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" id="action" value="createrestock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';
	print '<input type="hidden" name="cmdedetlist" value="'.GETPOST("cmdedetlist").'">';

	print '<table class="liste" width="100%">';
	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("QtyRestock").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("FournishSelectInfo").'</td>';
	print "</tr>\n";
	$product_static=new Product($db);

	$tblproduct=explode("-", GETPOST("prodlist"));
	$var=true;
	foreach ($tblproduct as $idproduct) {
		$nbprod=GETPOST("prd-".$idproduct);
		if ($nbprod > 0) {
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$product_static->id = $idproduct;
			$product_static->fetch($idproduct);
			print $product_static->getNomUrl(1,'',24);
			print '</td>';
			print '<td>'.$product_static->label.'</td>';
			print '<td align=center>';
			print "<input type=text size=4 name='prd-".$idproduct."' value='".$nbprod."'>";
			print '</td><td width=60%>';

			// on r�cup�re les infos fournisseurs
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($idproduct, "", "");

			if (count($product_fourn_list) > 0) {
				print '<table class="liste" width="100%">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans("Suppliers").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Ref").'</td>';
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
					print '<td class="liste_titre">'.$langs->trans("Availability").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("QtyMinAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("VAT").'</td>';

				// Charges ????
				print '<td class="liste_titre" align="right">'.$langs->trans("UnitPriceHTAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("HT").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("TTC").'</td>';
				print "</tr>\n";

				// pour chaque fournisseur du produit
				foreach ($product_fourn_list as $productfourn) {
					//var_dump($productfourn);
					print "<tr >";
					$presel=false;
					if ($nbprod < $productfourn->fourn_qty)
					{	// si on est or seuil de quantit� on d�sactive le choix
						print '<td>'.img_picto('disabled','disable') ;
					} else {
						// on m�morise � la fois l'id du fournisseur et l'id du produit du fournisseur
						if (count($product_fourn_list) > 1) {
							// on revient sur l'�cran avec une pr�selection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent)
							{	$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idproduct.'"';
							print ' value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
						}
						//mouchard pour les tests
						//print '<input type=text  value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'">&nbsp;';
					}
					print $productfourn->getSocNomUrl(1,'supplier').'</td>';

					// Supplier
					print '<td align="left">'.$productfourn->fourn_ref;
					print ($productfourn->supplier_reputation?' ('.$langs->trans($productfourn->supplier_reputation).')':"");
					print '</td>';

					//Availability
					if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) {
						$form->load_cache_availability();
						$availability= $form->cache_availability[$productfourn->fk_availability]['label'];
						print '<td align="left">'.$availability.'</td>';
					}

					// Quantity
					print '<td align="right">';
					print $productfourn->fourn_qty;
					print '</td>';

					// VAT rate
					print '<td align="right">';
					print vatrate($productfourn->fourn_tva_tx, true);
					print '</td>';

					// Unit price
					print '<td align="right">';
					if ($productfourn->fourn_remise_percent)
						$unitprice = $productfourn->fourn_unitprice * (1-($productfourn->fourn_remise_percent/100));
					elseif ($productfourn->fourn_remise)
						$unitprice = $productfourn->fourn_unitprice -$productfourn->fourn_remise;
					else
						$unitprice = $productfourn->fourn_unitprice;
					print price($unitprice);
					print '</td>';

					// Unit Charges ???
					if (! empty($conf->margin->enabled)) {
						$unitcharge=($productfourn->fourn_unitcharges?price($productfourn->fourn_unitcharges) : ($productfourn->fourn_qty?price($productfourn->fourn_charges/$productfourn->fourn_qty):"&nbsp;"));
					}
					if ($nbprod < $productfourn->fourn_qty)
						$nbprod = $productfourn->fourn_qty;
					$estimatedFournCost=$nbprod*$unitprice+($unitcharge!="&nbsp;"?$unitcharge:0);
					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if ($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel==true) {
						$totHT = $totHT+$estimatedFournCost;
						$totTTC = $totTTC+$estimatedFournCostTTC;
					}
					print '</tr>';
				}
				print "</table>";
			} else
				print $langs->trans("NoFournishForThisProduct");

			print '</td>';
			print '</tr>';
		}
	}
	print '<tr >';
	print '<td colspan=2></td><td align=right>';
	print '<input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'"></td>';
	print '<td><table width=100% ><tr><td ></td>';
	print '<td width=100px align=left>'.$langs->trans("AmountHT")." : <br>";
	print $langs->trans("AmountVAT")." : ".'</td>';
	print '<td width=100px align=right>';
	print price($totHT)." ".$langs->trans("Currency".$conf->currency).'<br>';
	print price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';
	print '</table>';
	print '</td></tr>';
	print '</table>';

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<table width=75%><tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td><td align=left>';
	// on m�morise la r�f�rence du de la facture client sur la commande fournisseur
	print '<input type=text size=30 name=reforderfourn value="'.$langs->trans('RestockofCmdeClient').'&nbsp;'.$object->ref.'"></td>';
	print '<td align=right><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'">   ';
	if($user->rights->commande->order_advance->send && $user->rights->expedition->creer && $user->rights->fournisseur->commande->creer && $user->rights->fournisseur->supplier_order_advance->validate && $user->rights->fournisseur->commande->commander && $user->rights->fournisseur->commande->receptionner) {
		print '<button type="button" id="direct" class="button">'.$langs->trans('DirectShipping').'</button></td>';
	}
	print '</tr></table>';
	print '</div >';
	print '</form >';

	print '<script>
			$("#direct").on("click", function() {
				$("#formulaire").find("#action").val("direct");
				$("#formulaire").submit();
			});
			</script>';
} elseif ($action=="createrestock") {
	// derni�re �tape : la cr�ation des commandes fournisseurs
	// on r�cup�re la liste des produits � commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utiliser un tableau pour stocker les commandes fournisseurs
	$tblCmdeFourn=array();
	// on parcourt les produits pour r�cup�rer les fournisseurs, les produits et les quantit�s
	foreach ($tblproduct as $idproduct) {
		$numlines=count($tblCmdeFourn);
		$lineoffourn = -1;
		if (GETPOST("fourn-".$idproduct)) {
			$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
			if ($tblfourn[0]) {
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblCmdeFourn[$j][0] == $tblfourn[0])
						$lineoffourn =$j;

				// si le fournisseur n'est pas d�ja dans le tableau des fournisseurs
				if ($lineoffourn == -1) {
					$tblCmdeFourn[$numlines][0] = $tblfourn[0];
					$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct),
									$tblfourn[1], $tblfourn[2], $tblfourn[3]));
				} else {
					$tblCmdeFourn[$lineoffourn][1] = array_merge(
									$tblCmdeFourn[$lineoffourn][1],
									array(array($idproduct, GETPOST("prd-".$idproduct),
									$tblfourn[1], $tblfourn[2], $tblfourn[3]))
					);
				}
			}
		}
	}

	// on va maintenant cr�er les commandes fournisseurs
	foreach ($tblCmdeFourn as $CmdeFourn) {
		$idCmdFourn = 0;
		// si il on charge les commandes fournisseurs brouillons
		if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
			// on v�rifie qu'il n'y a pas une commande fournisseur d�j� active
			$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cof';
			$sql.= ' WHERE fk_soc='.$CmdeFourn[0];
			$sql.= ' AND fk_statut=0';
			$sql.= ' AND entity='.$conf->entity;
			if  (	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 2
				||	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 4)
				$sql.= ' AND fk_user_author='.$user->id;

			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$idCmdFourn = $objp->rowid;
			}
			$objectcf = new CommandeFournisseur($db);
			$objectcf->origin = "commande";
			$objectcf->origin_id = GETPOST("id");
			$objectcf->fetch($idCmdFourn);
			// on ajoute le lien
			$ret = $objectcf->add_object_linked();
		}

		// en cr�ation
		if ($idCmdFourn == 0) {
			$objectfournisseur = new Fournisseur($db);
			$objectfournisseur->fetch($CmdeFourn[0]);

			$objectcf = new CommandeFournisseur($db);
			$objectcf->ref_supplier  	= GETPOST("reforderfourn");
			$objectcf->socid		 	= $CmdeFourn[0];
			$objectcf->note_private	= '';
			$objectcf->note_public   	= '';
			$objectcf->origin_id = GETPOST("id");

			$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
			$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;

			$objectcf->origin = "commande";
			$objectcf->linked_objects[$objectcf->origin] = $objectcf->origin_id;
			$idCmdFourn = $objectcf->create($user);
		}

		// ensuite on boucle sur les lignes de commandes
		foreach ($CmdeFourn[1] as $lgnCmdeFourn) {
			$idlgnFourn = 0;
			// on v�rifie qu'il n'y a pas d�j� une ligne de commande pour ce produit
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cofd';
			$sql.= ' WHERE fk_commande='.$idCmdFourn;
			$sql.= ' AND fk_product='.$lgnCmdeFourn[0];
			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$idlgnFourn = ($objp->rowid?$objp->rowid:0);
			}

			// si pas de ligne existante ou cr�ation d'une ligne � chaque fois
			if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
				// on cree la commande fournisseur
				$result=$objectcf->addline(
								'', 0,
								$lgnCmdeFourn[1],	// $qty
								$lgnCmdeFourn[3],	// TxTVA
								0, 0,
								$lgnCmdeFourn[0],	// $fk_product
								$lgnCmdeFourn[2],	// $fk_prod_fourn_price
								0, 					// $fourn_ref
								$lgnCmdeFourn[4],	// $remise_percent
								'HT',				// $price_base_type
								0, 0				// type
				);

				// r�cup de l'id de la que l'on vient de cr�er
				$sql = 'SELECT rowid from '.MAIN_DB_PREFIX.'commande_fournisseurdet';
				$sql.= ' WHERE fk_commande = '.$idCmdFourn;
				$sql.= ' ORDER BY rowid desc';
				$resql = $db->query($sql);

				if ($resql) {
					$objcf = $db->fetch_object($resql);
					$idlgnFourn = $objcf->rowid;
				}
			} else {
				$tmpcmdeligncmdefourn= new CommandeFournisseurLigne($db);
				$tmpcmdeligncmdefourn->fetch($idlgnFourn);
				$result=$objectcf->updateline(
								$idlgnFourn,
								$tmpcmdeligncmdefourn->desc,
								$tmpcmdeligncmdefourn->subprice,
								$tmpcmdeligncmdefourn->qty + $lgnCmdeFourn[1],
								$tmpcmdeligncmdefourn->remise_percent,
								$tmpcmdeligncmdefourn->tva_tx,
								$tmpcmdeligncmdefourn->localtax1_tx=0,
								$tmpcmdeligncmdefourn->localtax2_tx=0,
								'HT', 0, 0
				);
			}

			// on enregistre l'id pour la ligne de la commande client
			// attention, si le produit est sur deux ligne dans la commande client cela d�conne

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
			$sql.= ' SET fk_commandefourndet = '.$idlgnFourn;
			$sql.= ' WHERE fk_product = '.$lgnCmdeFourn[0];
			$sql.= ' AND fk_commande = '. $id;
			$resqlupdate = $db->query($sql);
		}
		$restock_static->add_contact_delivery_client($id,$idCmdFourn);
	}


	// une fois que c'est termin�, on affiche la ou les commandes fournisseurs cr�e
	if (count($tblCmdeFourn) == 1) {
		// on cr�e les commandes et on les listes sur l'�cran
		if (version_compare(DOL_VERSION, "3.7.0") < 0)
			header("Location: ".DOL_URL_ROOT."/fourn/commande/fiche.php?id=".$idCmdFourn);
		else
			header("Location: ".DOL_URL_ROOT."/fourn/commande/card.php?id=".$idCmdFourn);
	} else {
		// on cr�e les commandes et on les listes sur l'�cran
		if (version_compare(DOL_VERSION, "3.7.0") < 0)
			header("Location: ".DOL_URL_ROOT."/fourn/commande/liste.php?search_ref_supplier=".GETPOST("reforderfourn"));
		else
			header("Location: ".DOL_URL_ROOT."/fourn/commande/list.php?search_refsupp=".GETPOST("reforderfourn"));
	}
	exit;
}

print '</div>';
print '<div class="fichecenter"><div class="fichehalfleft">';

//
// Linked object block
//
// show only if not a draft
if ($object->statut != Commande::STATUS_DRAFT) {
    if (version_compare(DOL_VERSION, "5.0.0") >= 0)
        $somethingshown = $form->showLinkedObjectBlock($object, "");
    else
        $somethingshown = $object->showLinkedObjectBlock();
}

print '</div>';
print '</div>';

llxFooter();
$db->close();
