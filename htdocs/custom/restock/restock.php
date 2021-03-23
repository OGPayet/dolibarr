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
 *  \file	   htdocs/restock/restock.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

dol_include_once('/restock/class/restock.class.php');

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->load("bills");


// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

$action=GETPOST("action");

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"])) {
	$sref="";
	$snom="";
	$search_categ=0;
	$search_fourn=0;
} else {
	$search_categ=GETPOST("search_categ");
	$search_fourn=GETPOST("search_fourn");
}

/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

if ( isset($_POST['reload']) ) $action = 'restock';

$title=$langs->trans("RestockProduct");

if ($action=="") {
	llxHeader('', $title, $helpurl, '');

	// premier �cran la s�lection des produits
	$param="&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"");
	$param.="&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
	$param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
	$param.=($search_categ?"&amp;search_categ=".$search_categ:"");
	$param.=isset($type)?"&amp;type=".$type:"";

	print_barre_liste($texte, $page, "restock.php", $param, $sortfield, $sortorder, '', $num);

	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="">';

	print '<table class="liste" width="100%">';

	// Filter on categories
	$filtercateg='';
	if (! empty($conf->categorie->enabled)) {
		$filtercateg.=$langs->trans('Categories'). ': ';
		$filtercateg.=$htmlother->select_categories(0, $search_categ, 'search_categ', 1);
	}

	$filterfourn="";
	if (! empty($conf->fournisseur->enabled)) {
		$fournisseur=new Fournisseur($db);
		$tblfourn=$fournisseur->ListArray();
		$filterfourn.=$langs->trans('Fournisseur').': ';
		$filterfourn.=select_fournisseur($tblfourn, $search_fourn, 'search_fourn');
	}

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" >'.$filtercateg.'</td>';
	print '<td class="liste_titre" colspan="3">'.$filterfourn.'</td>';
	print '</td><td colspan=4 align=right>';
	print '<input type="image" class="liste_titre" name="button_search"';
	print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter"';
	print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';
	print '</table>';

	// d�termination du besoin
	$select0propals=$conf->global->RESTOCK_PROPOSAL_DRAFT;
	$select1propals=$conf->global->RESTOCK_PROPOSAL_VALIDATE;
	$select2propals=$conf->global->RESTOCK_PROPOSAL_SIGNED;
	$select0commandes=$conf->global->RESTOCK_ORDER_DRAFT;
	$select1commandes=$conf->global->RESTOCK_ORDER_VALIDATE;
	$select2commandes=$conf->global->RESTOCK_ORDER_PARTIAL;
	$select0factures=$conf->global->RESTOCK_BILL_DRAFT;
	$select1factures=$conf->global->RESTOCK_BILL_VALIDATE;
	$select2factures=$conf->global->RESTOCK_BILL_PARTIAL;

	$restock_static=new Restock($db);
	$tblRestock=array();

	//$lgnRestock->nbCmdeDraft=0;
	//$lgnRestock->nbCmdeValidate=0;
	//$lgnRestock->nbCmdePartial=0;
	//$lgnRestock->nbPropDraft=0;
	//$lgnRestock->nbPropValidate=0;
	//$lgnRestock->nbPropSigned=0;
	//$lgnRestock->nbBillDraft=0;
	//$lgnRestock->nbBillValidate=0;
	//$lgnRestock->nbBillPartial=0;

	// on r�cup�re les produits pr�sents dans les commandes et les propales
	if ($select0propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 1);
	if ($select2propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 2);
	if ($select0commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 1);
	if ($select2commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 2);
	if ($select0factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 1);
	// pai� partiellement
	if ($select2factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 3);

	// on g�re la d�composition des produits
	$tblRestockTemp=array();

	foreach ($tblRestock as $lgnRestock) {

		// on r�cup�re la composition et les quantit�s
		$tbllistofcomponent=$restock_static->getcomponent($lgnRestock->id, 1, $search_categ, $search_fourn);

		$numlines=count($tblRestockTemp);
		foreach ($tbllistofcomponent as $lgncomponent) {
			$lineofproduct = -1;

			// on regarde si on trouve d�j� le produit dans le tableau
			for ($j = 0 ; $j <= $numlines ; $j++)
				if ($tblRestockTemp[$j]->id == $lgncomponent[0])
					$lineofproduct=$j;

			if ($lineofproduct >= 0) {
				// on multiplie par la quantit� du composant
				$tblRestockTemp[$lineofproduct]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$lineofproduct]->nbCmdeValidate = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$lineofproduct]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$lineofproduct]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$lineofproduct]->nbPropValidate = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$lineofproduct]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$lineofproduct]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$lineofproduct]->nbBillValidate = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$lineofproduct]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
			} else {
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$tblRestockTemp[$numlines]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$numlines]->nbCmdeValidate  = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$numlines]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$numlines]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$numlines]->nbPropValidate  = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$numlines]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$numlines]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$numlines]->nbBillValidate  = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$numlines]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
				$numlines++;
			}
		}
	}

	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);

	print '</form>';
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre border" align=center colspan="4">'.$langs->trans("InfoProduct").'</td>';
	if ($select0propals+$select1propals+$select2propals > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InPropal").'</td>';
	if ($select0commandes+$select1commandes+$select2commandes > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InOrder").'</td>';
	if ($select0factures+$select1factures+$select2factures > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InBill").'</td>';
	print '</td><td colspan="2" align=center>'.$langs->trans("RestockStock");
	//print '</td><td align=center>'.$langs->trans("RestockStockAlertAbrev");
	print '</td><td align=right>'.$langs->trans("AlreadyOrder1");
	print '</td><td align=center>'.$langs->trans("Qty").'</td></tr>';

	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	if ($select0propals+$select1propals+$select2propals > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockValidate").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockSigned").'</td>';
	}
	if ($select0commandes+$select1commandes+$select2commandes > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockValidate").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("ActionsRunningshort").'</td>';
	}
	if ($select0factures+$select1factures+$select2factures > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("BillsUnpaid").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockPaid").'</td>';
	}
	print '<td class="liste_titre" align="right">'.$langs->trans("Physical").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("RestockStockLimitAbrev").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";

	$idprodlist="";
	$product_static=new Product($db);
	foreach ($tblRestock as $lgnRestock) {

	if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==1 && $lgnRestock->fk_product_type=="0"
		|| $conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==2 && $lgnRestock->fk_product_type=="1"
		|| $conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==0) {

		$product_fourn = new ProductFournisseur($db);
		$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
		if (count($product_fourn_list) > 0) {
			$estimedNeed=0;
			$estimedNeed+=$lgnRestock->nbPropDraft*$select0propals/100;
			$estimedNeed+=$lgnRestock->nbPropValidate*$select1propals/100;;
			$estimedNeed+=$lgnRestock->nbPropSigned*$select2propals/100;;
			$estimedNeed+=$lgnRestock->nbCmdeDraft*$select0commandes/100;
			$estimedNeed+=$lgnRestock->nbCmdeValidate*$select1commandes/100;
			$estimedNeed+=$lgnRestock->nbCmdePartial*$select2commandes/100;
			$estimedNeed+=$lgnRestock->nbBillDraft*$select0factures/100;
			$estimedNeed+=$lgnRestock->nbBillValidate*$select1factures/100;
			$estimedNeed+=$lgnRestock->nbBillpartial*$select2factures/100;
			$var=!$var;
			print "<tr ".$bc[$var].">";
			$idprodlist.=$lgnRestock->id."-";
			print '<td class="nowrap">';
			$product_static->fetch($lgnRestock->id);
			print $product_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			print '<td align="right">'.price($lgnRestock->PrixVenteHT).'</td>';
			print '<td align="right">'.price($lgnRestock->PrixAchatHT).'</td>';
			if ($select0propals+$select1propals+$select2propals > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbPropDraft.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbPropValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbPropSigned.'</td>';
			}
			if ($select0commandes+$select1commandes+$select2commandes > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbCmdeDraft.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbCmdeValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbCmdePartial.'</td>';
			}
			if ($select0factures+$select1factures+$select2factures > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbBillDraft.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbBillValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbBillPartial.'</td>';
			}
			print '<td align="right">'.$lgnRestock->StockQty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
			if (count($product_fourn_list) > 0) {
				$estimedNeed=0;
				$estimedNeed+=$lgnRestock->nbPropDraft*$select0propals/100;
				$estimedNeed+=$lgnRestock->nbPropValidate*$select1propals/100;;
				$estimedNeed+=$lgnRestock->nbPropSigned*$select2propals/100;;
				$estimedNeed+=$lgnRestock->nbCmdeDraft*$select0commandes/100;
				$estimedNeed+=$lgnRestock->nbCmdeValidate*$select1commandes/100;
				$estimedNeed+=$lgnRestock->nbCmdePartial*$select2commandes/100;
				$estimedNeed+=$lgnRestock->nbBillDraft*$select0factures/100;
				$estimedNeed+=$lgnRestock->nbBillValidate*$select1factures/100;
				$estimedNeed+=$lgnRestock->nbBillpartial*$select2factures/100;

				// si on travail en r�assort, on ne prend pas en compte le stock et les commandes en cours
				if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->StockQty ;

				if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->nbCmdFourn;

				// si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
				if ($estimedNeed < 0)
					$estimedNeed = 0;

				print '<td align="right">';
				print '<input type=text size=5 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'">';
				print "</td>\n";

			} else {
				print '<td align="right">';
				print $langs->trans("NoFournish");
				print '</td>';
			}

//			//si il y a encore du besoin, (on a vid� toute le stock et les commandes)
//			if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
//				if (($estimedNeed > 0) && ($lgnRestock->StockQtyAlert > 0))
//					$estimedNeed+= $lgnRestock->StockQtyAlert;
//
//			if ($estimedNeed < 0)  // si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
//				$estimedNeed = 0;
		}
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
	llxHeader('', $title, $helpurl, '');

	// deuxieme �tape : la s�lection des fournisseur
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
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
	// pour chaqe produit
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
						// le taux de tva et le pourcentage de remise...
						$valueCheck = $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id;
						$valueCheck.= '-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent;
						if (count($product_fourn_list) > 1) {
							// on revient sur l'�cran avec une pr�selection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $valueCheck) {
								$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$valueCheck.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$valueCheck.'">&nbsp;';
						}
						//mouchard pour les tests
						//print '<input type=text  value="'.$valueCheck.'">&nbsp;';
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
					//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
					print '</td>';

					// Unit Charges ???
					if (! empty($conf->margin->enabled))
						$unitcharge=($productfourn->fourn_unitcharges ?
										price($productfourn->fourn_unitcharges) :
										($productfourn->fourn_qty ?
														price($productfourn->fourn_charges/$productfourn->fourn_qty):
														"&nbsp;"
										)
						);
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
	print '<input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'"></td>';
	print '<td><table width=100% ><tr><td ></td>';
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
	print '<table width=75%>';
	print '<tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td><td align=left width=200px>';
	// on m�morise la r�f�rence du de la facture client sur la commande fournisseur
	print '<input type=text size=40 name=reforderfourn';
	print ' value="'.$langs->trans('Restockof').'&nbsp;'.dol_print_date(dol_now(), "%d/%m/%Y").'"></td>';
	print '<td align=right>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'">';
	print '</td></tr></table>';
	print '</div >';
	print '</form >';
} elseif ($action=="createrestock") {
	// derni�re �tape : la cr�ation des commande fournisseur
	// on r�cup�re la liste des produits � commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utiliser un tableau pour stocker les commandes fournisseurs
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
					$tblCmdeFourn[$numlines][1] = array(array(
									$idproduct, GETPOST("prd-".$idproduct),
									$tblfourn[1], $tblfourn[2], $tblfourn[3]
					));
				} else {
					$tblCmdeFourn[$lineoffourn][1] = array_merge(
									$tblCmdeFourn[$lineoffourn][1],
									array(array(
													$idproduct, GETPOST("prd-".$idproduct),
													$tblfourn[1], $tblfourn[2], $tblfourn[3]
									))
					);
				}
			}
		}
	}
	//var_dump($tblCmdeFourn);
	// on va maintenant cr�er les commandes fournisseurs
	foreach ($tblCmdeFourn as $cmdeFourn) {
		$idCmdFourn = 0;
		// si il on charge les commandes fournisseurs brouillons
		if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
			// on v�rifie qu'il n'y a pas une commande fournisseur d�j� active
			$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cof';
			$sql.= ' WHERE fk_soc='.$cmdeFourn[0];
			$sql.= ' AND fk_statut=0';
			$sql.= ' AND entity='.$conf->entity;
			if ($conf->global->RESTOCK_FILL_ORDER_DRAFT == 2 || $conf->global->RESTOCK_FILL_ORDER_DRAFT == 4)
				$sql.= ' AND fk_user_author='.$user->id;

			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$idCmdFourn = $objp->rowid;
			}
			$objectcf = new CommandeFournisseur($db);
			$objectcf->fetch($idCmdFourn);
		}

		if ($idCmdFourn == 0) {
			$objectcf = new CommandeFournisseur($db);
			$objectcf->ref_supplier  	= GETPOST("reforderfourn");
			$objectcf->socid		 	= $cmdeFourn[0];
			$objectcf->note_private		= '';
			$objectcf->note_public		= '';
//			$objectcf->origin_id = GETPOST("id");
//			$objectcf->origin = "commande";
//			$objectcf->linked_objects[$objectcf->origin] = $objectcf->origin_id;
			$idCmdFourn = $objectcf->create($user);
		}

		// ensuite on boucle sur les lignes de commandes
		foreach ($cmdeFourn[1] as $lgnCmdeFourn) {
			$idlgnFourn = 0;
			// on v�rifie qu'il n'y a pas d�j� une ligne de commande pour ce produit
			$sql = 'SELECT rowid, description FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cofd';
			$sql.= ' WHERE fk_commande='.$idCmdFourn;
			$sql.= ' AND fk_product='.$lgnCmdeFourn[0];

			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$idlgnFourn = $objp->rowid;
			}
			// si pas de ligne existante ou cr�ation d'une ligne � chaque fois
			if ($idlgnFourn == 0 && $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
				//var_dump($lgnCmdeFourn);
				$savedValue = $conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY;
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = true;
				$result=$objectcf->addline(
								'', '',
								$lgnCmdeFourn[1],	// $qty
								$lgnCmdeFourn[3],	// TxTVA
								0, 0,
								$lgnCmdeFourn[0],	// $fk_product
								$lgnCmdeFourn[2],	// $fk_prod_fourn_price
								0,
								$lgnCmdeFourn[4],	// remise_percent
								'HT',				// $price_base_type
								0, 0				// type
				);
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = $savedValue;
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
								'HT',
								0, 0
				);
			}
		}
	}

	// une fois que c'est termin�, on affiche les commandes fournisseurs cr�e
	// on cr�e les commandes et on les listes sur l'�cran
	// pour les versions plus ancienne que la 3.7
	if (version_compare(DOL_VERSION, "3.7.0") < 0)
		header("Location: ".DOL_URL_ROOT."/fourn/commande/liste.php?search_ref_supplier=".GETPOST("reforderfourn"));
	else
		header("Location: ".DOL_URL_ROOT."/fourn/commande/list.php?search_refsupp=".GETPOST("reforderfourn"));
	exit;
}
llxFooter();
$db->close();

/**
 * Return select list for categories (to use in form search selectors)
 *
 * @param  attary	$fournlist	fournish list
 * @param  string	$selected	Preselected value
 * @param  string	$htmlname	Name of combo list
 * @return string				Html combo list code
 */
function select_fournisseur($fournlist, $selected=0, $htmlname='search_fourn')
{
//	global $langs;

	// Print a select with each of them
	$moreforfilter ='<select class="flat" name="'.$htmlname.'">';
	$moreforfilter.='<option value="">&nbsp;</option>';	// Should use -1 to say nothing
	if (is_array($fournlist)) {
		foreach ($fournlist as $key => $value) {
			foreach ($fournlist as $key => $value) {
				$moreforfilter.='<option value="'.$key.'"';
				if ($key == $selected)
					$moreforfilter.=' selected="selected"';
				$moreforfilter.='>'.dol_trunc($value, 50, 'middle').'</option>';
			}
		}
		$moreforfilter.='</select>';

		return $moreforfilter;
	}
}
