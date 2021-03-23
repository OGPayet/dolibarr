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
 *  \file	   htdocs/restock/restockProduct.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once('/restock/class/restock.class.php');

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");


// Security check
if ($user->societe_id) $socid=$user->societe_id;

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas)?$object->canvas:GETPOST("canvas");
$objcanvas='';
if (! empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'card', $canvas);
}

$action=GETPOST("action");
$id = GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$object = new Product($db);

$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype, $objcanvas);

if (! $object->fetch($id, $ref) > 0)
	dol_print_error($db);

$id = $object->id;

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



if ( isset($_POST['reload']) ) $action = 'restock';

// header forwarding issue
// en cas de createrestock, comme il y a redirection ensuite, on n'affiche pas la page
if ($action!="createrestock") {
	$title=$langs->trans("RestockProduct");

	llxHeader('', $title,'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes', '');
	$head = product_prepare_head($object, $user);
	dol_fiche_head($head, 'restock', $langs->trans("CardProduct".$object->type), 0, 'product');

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
	$linkback.= $langs->trans("BackToList").'</a>';

	if (version_compare(DOL_VERSION, "5.0.0") >= 0) {
		$morehtmlref='<div class="refidno">';
		$morehtmlref.='<br>'.$langs->trans('PhysicalStock') . ' : ';
		$morehtmlref.= $object->stock_reel.'</div>';

		dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', 'ref', $morehtmlref);
		$cssclass='titlefield';
		print '<div class="underbanner clearboth"></div>';
	} else {
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print "</td></tr>";

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td>';
		print '<td colspan="3">'.($object->label ? $object->label:$object->libelle).'</td></tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
		print $object->getLibStatut(2, 0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
		print $object->getLibStatut(2, 1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$object->stock_reel.'</td></tr>';

		print '</table><br><br>';
	}
}

if ($action=="") {
	print '<form action="restockProduct.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';
	print '<input type="hidden" name="id" value="'.$id.'">';

	$restock_static=new Restock($db);
	$tblRestock=array();
	// on cr�e un tableau que pour le produit
	$tblRestock[0] = new Restock($db);
	$tblRestock[0]->id= $id;

	// on g�re la d�composition des produits
	$tblRestockTemp=array();
	foreach ($tblRestock as $lgnRestock) {
		// on r�cup�re la composition et les quantit�s
		$tbllistofcomponent=$restock_static->getcomponent($lgnRestock->id, 1);
		$numlines=count($tblRestockTemp);
		foreach ($tbllistofcomponent as $lgncomponent) {
			$lineofproduct = -1;
			// on regarde si on trouve d�j� le produit dans le tableau
			for ($j = 0 ; $j <= $numlines ; $j++)
				if ($tblRestockTemp[$j]->id == $lgncomponent[0])
					$lineofproduct=$j;

			// on multiplie par la quantit� du composant
			if ($lineofproduct >= 0)
				$tblRestockTemp[$lineofproduct]->nbCmdeClient = $lgncomponent[1]*$lgnRestock->nbCmdeClient;
			else {
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$tblRestockTemp[$numlines]->nbCmdeClient = $lgncomponent[1]*$lgnRestock->nbCmdeClient;

				$numlines++;
			}
		}
	}
	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);

	// Lignes des titres
	print '<table class="liste" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";

	//var_dump($tblRestock);
	$idprodlist="";
	$product_static=new Product($db);
	foreach ($tblRestock as $lgnRestock) {
		// on affiche que les produits commandable � un fournisseur ?
		if ($lgnRestock->OnBuyProduct == 1 && $lgnRestock->fk_product_type == 0) {
			$var=!$var;
			print "<tr ".$bc[$var].">";
			$idprodlist.=$lgnRestock->id."-";
			print '<td class="nowrap">';
			$product_static->id = $lgnRestock->id;
			$product_static->ref = $lgnRestock->ref_product;
			$product_static->type = 0;
			print $product_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			print '<td align="right">'.price($lgnRestock->PrixVenteHT).'</td>';
			print '<td align="right">'.price($lgnRestock->PrixAchatHT).'</td>';
			print '<td align="right">'.$lgnRestock->StockQty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
			if (count($product_fourn_list) > 0) {
				// d�termination du besoin
				$estimedNeed=$lgnRestock->nbCmdeClient;
				// si on travail en r�assort, on ne prend pas en compte le stock et les commandes en cours
				if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->StockQty;

				if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->nbCmdFourn;

				// si il y a encore du besoin, (on a vid� toute le stock et les commandes)
				if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					if (($estimedNeed > 0) && ($lgnRestock->StockQtyAlert > 0))
						$estimedNeed+= $lgnRestock->StockQtyAlert;

				// si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
				if ($estimedNeed < 0)
					$estimedNeed = 0;
				print '<td align="right">';
				print '<input type=text size=5 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'"></td>';
			} else {
				print '<td align="right">';
				print $langs->trans("NoFournish");
				print '</td>';
			}
			print "</tr>\n";
		}
	}

	print '</table>';
	// pour m�moriser les produits � r�stockvisionner
	// on vire le dernier '-' si la prodlist est aliment�
	if ($idprodlist)
		$idprodlist=substr($idprodlist, 0, -1);
	print '<input type=hidden name="prodlist" value="'.$idprodlist.'"></td>';

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction"><br><center>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('RestockOrder').'">';
	print '</center></div >';
	print '</form >';
} elseif ($action=="restock") {
	// deuxieme �tape : la s�lection des fournisseur
	print '<form action="restockProduct.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';

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
			print $product_static->getNomUrl(1, '', 24);
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
					if ($nbprod < $productfourn->fourn_qty) {
						// si on est or seuil de quantit� on d�sactive le choix
						print '<td>'.img_picto('disabled', 'disable');
					} else {
						// on m�morise � la fois l'id du fournisseur et l'id du produit du fournisseur
						if (count($product_fourn_list) > 1) {
							// on revient sur l'�cran avec une pr�selection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent) {
								$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
						}
					}

					print $productfourn->getSocNomUrl(1, 'supplier').'</td>';

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
						$totHT=$totHT+$estimatedFournCost;
						$totTTC=$totTTC+$estimatedFournCostTTC;
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
	print '<input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'">';
	print '</td><td><table width=100% ><tr><td ></td>';
	print '<td width=100px align=left>'.$langs->trans("AmountHT")." : <br>";
	print $langs->trans("AmountVAT")." : ".'</td>';
	print '<td width=100px align=right>'.price($totHT)." ".$langs->trans("Currency".$conf->currency);
	print '<br>'.price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

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
	print '<input type=text size=30 name=reforderfourn value="'.$langs->trans('RestockofProduct').'&nbsp;'.$object->ref.'"></td>';
	print '<td align=right><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'"></td>';
	print '</tr></table>';
	print '</div >';
	print '</form >';
} elseif ($action=="createrestock") {
	// derni�re �tape : la cr�ation des commande fournisseur
	// on r�cup�re la liste des produits � commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utilser un tableau pour stocker les commandes fournisseurs
	$tblCmdeFourn=array();
	// on parcourt les produits pour r�cup�rer les fournisseurs, les produits et les quantit�sds
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
					$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct), $tblfourn[1], $tblfourn[2], $tblfourn[3]));
				} else {
					$tblCmdeFourn[$lineoffourn][1] = array_merge(
									$tblCmdeFourn[$lineoffourn][1],
									array(array($idproduct, GETPOST("prd-".$idproduct), $tblfourn[1], $tblfourn[2], $tblfourn[3]))
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
			$objectcf->fetch($idCmdFourn);
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
			$objectcf->origin_id = 0; // GETPOST("id");

			$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
			$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;

			$objectcf->origin = "";

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
				$savedValue = $conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY;
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = true;
				// on cree la commande fournisseur
				$result=$objectcf->addline(
								'', '',
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
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = $savedValue;

			} else {
				$tmpcmdeligncmdefourn= new CommandeFournisseurLigne($db);
				$tmpcmdeligncmdefourn->fetch($idlgnFourn);
				$result=$objectcf->updateline($idlgnFourn,
								$tmpcmdeligncmdefourn->desc,
								$tmpcmdeligncmdefourn->subprice,
								$tmpcmdeligncmdefourn->qty + $lgnCmdeFourn[1],
								$tmpcmdeligncmdefourn->remise_percent,
								$tmpcmdeligncmdefourn->tva_tx,
								$tmpcmdeligncmdefourn->localtax1_tx=0,
								$tmpcmdeligncmdefourn->localtax2_tx=0,
								'HT',
								0, 0
				);
			}
		}
	}
	// une fois que c'est termin�, on affiche les commandes fournisseurs cr�e
	// on cr�e les commandes et on les listes sur l'�cran
	if (version_compare(DOL_VERSION, "3.7.0") < 0)
		header("Location: ".DOL_URL_ROOT."/fourn/commande/liste.php?search_ref_supplier=".GETPOST("reforderfourn"));
	else
		header("Location: ".DOL_URL_ROOT."/fourn/commande/list.php?search_refsupp=".GETPOST("reforderfourn"));
	exit;
}

print '</div>';
print '<div class="fichecenter"><div class="fichehalfleft">';
/*
 * Linked object block
*/
if (version_compare(DOL_VERSION,"5.0.0") >= 0)
	$somethingshown = $form->showLinkedObjectBlock($object, "");
else
	$somethingshown=$object->showLinkedObjectBlock();
print '</div>';
print '</div>';
llxFooter();
$db->close();
