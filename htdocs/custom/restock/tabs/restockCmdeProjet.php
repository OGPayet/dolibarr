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
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/restock/class/restock.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
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

$restockcmde_static=new RestockCmde($db);
$product_static=new Product($db);
$commande_static=new Commande($db);

$projectid='';
$projectid=GETPOST("id");
$projectref=GETPOST("ref");

$object = new Project($db);
$result = $object->fetch($projectid, $projectref);

if (! empty($object->socid)) $object->fetch_thirdparty();

if ( isset($_POST['reload']) ) $action = 'restock';

$title=$langs->trans("RestockOrderClientFromProject");

if ($action != "createrestock") {
	llxHeader('', $title, $helpurl, '');

	$head = project_prepare_head($object);
	dol_fiche_head($head, 'restock', $langs->trans("Project"), 0, ($object->public?'projectpub':'project'));

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

	if (version_compare(DOL_VERSION, "5.0.0") >= 0) {

		$morehtmlref='<div class="refidno">';
		$morehtmlref.=$object->title;

		if ($object->thirdparty->id > 0)
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
		$morehtmlref.='</div>';

		// Define a complementary filter for search of next/prev ref.
		if (! $user->rights->projet->all->lire) {
			$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
			$object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',', array_keys($objectsListId)):'0').")";
		}
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
			// Opportunity status
			print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
			$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
			if ($code) print $langs->trans("OppStatus".$code);
			print '</td></tr>';

			// Opportunity percent
			print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
			if (strcmp($object->opp_percent, ''))
				print price($object->opp_percent, '', $langs, 1, 0).' %';
			print '</td></tr>';

			// Opportunity Amount
			print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
			if (strcmp($object->opp_amount, ''))
				print price($object->opp_amount, '', $langs, 1, 0, 0, $conf->currency);
			print '</td></tr>';
		}

		// Date start - end
		print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
		print dol_print_date($object->date_start, 'day');
		$end=dol_print_date($object->date_end, 'day');
		if ($end) print ' - '.$end;
		print '</td></tr>';

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (strcmp($object->budget_amount, ''))
			print price($object->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->description);
		print '</td></tr>';

		// Categories
		if ($conf->categorie->enabled) {
			print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id, 'project', 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';
		print '</div>';
		print '</div>';
		print '<div class="clearboth"></div>';
	} else {
		print '<table class="border" width="100%">';
		$urlparam =($periodyear ? "&periodyear=".$periodyear:'')."&periodmonth=".$periodmonth;
		$urlparam.=($perioduser ? "&perioduser=".$perioduser:'').($displaymode ? "&displaymode=".$displaymode:'');

		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
		// Define a complementary filter for search of next/prev ref.
		if (! $user->rights->projet->all->lire) {
			$projectsListId = $object->getProjectsAuthorizedForUser($user, $mine, 0);
			$object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',', array_keys($projectsListId)):'0').")";
		}
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', $urlparam);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
		if (! empty($object->societe->id))
			print $object->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
		if (! empty($object->societe->id))
			print $object->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

		// Date start
		print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
		print dol_print_date($object->date_start, 'day');
		if ($object->date_start) {
			$yeardatestart= date("Y", $object->date_start);
			$monthdatestart= date("m", $object->date_start);
		} else {
			$yeardatestart= date("Y");
			$monthdatestart= '01';
		}
		print '</td></tr>';
		// Date end
		print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
		print dol_print_date($object->date_end, 'day');
		if ($object->date_end) {
			$yeardateend= date("Y", $object->date_end);
			$monthdateend= date("m", $object->date_end);
		} else {
			$yeardateend= date("Y");
			$monthdateend= date("m");
		}
		print '</td></tr>';
		print '</table>';
	}
}

if ($action == "") {

	//print_fiche_titre($langs->trans("RestockCustomerOrder"));

	// premier �cran la s�lection des produits
	$param = "&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"");
	$param.= "&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
	$param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
	$param.=($search_categ?"&amp;search_categ=".$search_categ:"");
	$param.=isset($type)?"&amp;type=".$type:"";
	$param.=isset($search_year)?"&amp;search_year=".$search_year:"";
	$param.=isset($search_month)?"&amp;search_month=".$search_month:"";
	print_barre_liste($texte, $page, "restockCmdeProjet.php?id=".$object->id, $param, $sortfield, $sortorder, '', $num);

	print '<form action="restockCmdeProjet.php?id='.$object->id.'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="">';
	print '<table class="liste border" width="100%">';

	// Filter on categories
	$filtercateg='';
	if (! empty($conf->categorie->enabled)) {
		$filtercateg.=$langs->trans('Categories'). ': ';
		$filtercateg.=$htmlother->select_categories(0, $search_categ, 'search_categ', 1);
	}

	// filter on fournisseur
	$filterfourn='';
	if (! empty($conf->fournisseur->enabled)) {
		$fournisseur=new Fournisseur($db);
		$tblfourn=$fournisseur->ListArray();
		$filterfourn.=$langs->trans('Fournisseur'). ': ';
		$filterfourn.= select_fournisseur($tblfourn, $search_fourn, 'search_fourn', 1);
	}

	print '<tr class="liste_titre">';

	print '<td class="liste_titre" >'.$filtercateg.'</td>';
	print '<td class="liste_titre" >'.$filterfourn.'</td>';
	print '</td><td colspan=4 align=right>';
	print '<input type="image" class="liste_titre" name="button_search" src="';
	print DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="';
	print dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="';
	print DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="';
	print dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';
	print '</table>';


	$tblRestock=array();

	// on r�cup�re les produits pr�sents dans les commandes et les propales
	$morefilter=" co.fk_projet =".$object->id;
	$tblRestock=$restockcmde_static->get_array_product_cmdedet($tblRestock, $search_categ, $search_fourn, $morefilter);
	$tblRestock=$restockcmde_static->enrichir_product($tblRestock);

	// on g�re la d�composition (produit virtuel/factory) des produits trouv�s
	// plus tard

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align=center colspan=4>'.$langs->trans("InfoOrder").'</td>';
	print '<td class="liste_titre" align=center colspan=4>'.$langs->trans("InfoProduct").'</td>';
	print '<td align=right></td>';
	print '<td align=right>'.$langs->trans("Stock").'</td>';
	print '<td align=right>'.$langs->trans("StockAlertAbrev").'</td>';
	print '<td align=right>'.$langs->trans("AlreadyOrder1").'</td>';
	print '<td align=center>'.$langs->trans("Qty").'</td></tr>';

	print '</form>';

	print '<form action="restockCmdeProjet.php?id='.$object->id.'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';

	// Lignes des titres
	print "<tr class='liste_titre'>";

	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Customer").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("RefCustomer").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("DateOrder").'</td>';

	print '<td class="liste_titre" align="left">'.$langs->trans("RefProduct").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyOrder").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Physical").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimitAbrev").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";

	$cmdedetlist="";

	foreach ($tblRestock as $lgnRestock) {
		// on affiche que les produits commandable � un fournisseur
		if ($lgnRestock->OnBuyProduct == 1 && $lgnRestock->fk_product_type == 0) {
			$var=!$var;
			print "<tr ".$bc[$var].">";
			$cmdedetlist.=$lgnRestock->fk_commandedet."-";

			print '<td class="nowrap">';
			$commande_static->fetch($lgnRestock->fk_commande);
			$ret = $commande_static->fetch_thirdparty();

			print $commande_static->getNomUrl(1);
			print '</td>';

			// Name
			print '<td align="Left">';
			print $commande_static->thirdparty->getNomUrl(1);
			print '</td>';

			print '<td class="nowrap">';
			print $commande_static->ref_client;
			print '</td>';

			print '<td class="nowrap">';
			print dol_print_date($commande_static->date_commande, "day");
			print '</td>';

			print '<td class="nowrap">';
			$product_static->fetch($lgnRestock->fk_product);
			print $product_static->getNomUrl(1, '', 24);

			// pour g�rer le bon stock
			$product_static->load_stock();
			$lgnRestock->StockQty = $product_static->stock_reel;
			print '</td>';

			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			print '<td align="right">'.price($lgnRestock->PrixVenteHT).'</td>';
			print '<td align="right">'.price($lgnRestock->PrixAchatHT).'</td>';
			print '<td align="right">'.$lgnRestock->qty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';

			$estimedNeed = $lgnRestock->qty;

			// si on travail en r�assort, on ne prend pas en compte le stock et les commandes en cours
			if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
				$estimedNeed-= $lgnRestock->StockQty ;

			if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3)
				$estimedNeed-= $lgnRestock->nbCmdFourn;

			// si il y a encore du besoin, (on a vid� toute le stock et les commandes)
			if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
				if (($estimedNeed > 0) && ($lgnRestock->StockQtyAlert > 0))
					$estimedNeed+=  $lgnRestock->StockQtyAlert;

			if ($estimedNeed < 0)  // si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
				$estimedNeed = 0;

			print '<td align="right">';
			print '<input type=text size=5 name="cmdedet-'.$lgnRestock->fk_commandedet.'" value="'.round($estimedNeed).'">';
			print "</td></tr>\n";
		}
	}

	print '</table>';
	// pour m�moriser les produits � r�stockvisionner
	// on vire le dernier '-' si la prodlist est aliment�
	if ($cmdedetlist)
		$cmdedetlist=substr($cmdedetlist, 0, -1);
	print '<input type=hidden name="cmdedetlist" value="'.$cmdedetlist.'"></td>';

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction"><br><center>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('RestockOrder').'">';
	print '</center></div>';

	print '</form >';
} elseif ($action == "restock") {

	// deuxieme �tape : la s�lection des fournisseur
	print '<form action="restockCmdeProjet.php?id='.$object->id.'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="cmdedetlist" value="'.GETPOST("cmdedetlist").'">';
	print '<table class="liste" width="100%">';
	// Lignes des titres
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">'.$langs->trans("RefOrder").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Refproduct").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("QtyRestock").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("FournishSelectInfo").'</td>';
	print "</tr>\n";


	$tblcmdedet=explode("-", GETPOST("cmdedetlist"));

	$var=true;
	// pour chaque produit
	foreach ($tblcmdedet as $idcmdedet) {
		$nbprod=GETPOST("cmdedet-".$idcmdedet);
		if ($nbprod > 0) {
			// on r�cup�re les infos

			$var=!$var;
			$restockcmde_static->fetchdet($idcmdedet, $nbprod);

			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$commande_static->fetch($restockcmde_static->fk_commande);
			print $commande_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td class="nowrap">';
			$product_static->fetch($restockcmde_static->fk_product);
			print $product_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td>'.$product_static->label.'</td>';
			print '<td align=center>';
			print "<input type=text size=4 name='cmdedet-".$idcmdedet."' value='".$nbprod."'>";
			print '</td><td width=60%>';
			// on r�cup�re les infos fournisseurs
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($restockcmde_static->fk_product, "", "");
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

					print "<tr >";
					$presel=false;
					if ($nbprod < $productfourn->fourn_qty) {
						// si on est or seuil de quantit� on d�sactive le choix
						print '<td>'.img_picto('disabled', 'disable') ;
					} else {
						// on m�morise � la fois l'id du fournisseur et l'id du produit du fournisseur
						if (count($product_fourn_list) > 1) {
							// on revient sur l'�cran avec une pr�selection
							$checked="";
							if (GETPOST("fourn-".$idcmdedet) == $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent){
								$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idcmdedet.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
						} else	 {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idcmdedet.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent.'">&nbsp;';
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
					print price($productfourn->fourn_unitprice);
					if ($productfourn->fourn_remise_percent >0 )
						print '<br>-'.price($productfourn->fourn_remise_percent). "%";
					print '</td>';

					// Unit Charges ???
					if (! empty($conf->margin->enabled)) {
						$unitcharge=($productfourn->fourn_unitcharges?price($productfourn->fourn_unitcharges) : ($productfourn->fourn_qty?price($productfourn->fourn_charges/$productfourn->fourn_qty):"&nbsp;"));
					}
					if ($nbprod < $productfourn->fourn_qty)
						$nbprod = $productfourn->fourn_qty;

					$estimatedFournCost=$nbprod*$productfourn->fourn_unitprice+($unitcharge!="&nbsp;"?$unitcharge:0);

					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if ($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel == true) {
						$totHT = $totHT + $estimatedFournCost;
						$totTTC = $totTTC + $estimatedFournCostTTC;
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
	print '<td colspan=3></td><td align=right>';
	print '<input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'"></td>';
	print '<td><table width=100% ><tr><td ></td>';
	print '<td width=100px align=left>'.$langs->trans("AmountHT")." : <br>";
	print $langs->trans("AmountVAT")." : ".'</td>';
	print '<td width=100px align=right>';
	print price($totHT)." ".$langs->trans("Currency".$conf->currency).'<br>'.price($totTTC)." ";
	print $langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';
	print '</table>';
	print '</td></tr>';
	print '</table>';

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<table width=75%><tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td>';
	print '<td align=left width=200px>';
	// on m�morise la r�f�rence du de la facture client sur la commande fournisseur
	$reforderfourn=$langs->trans('Restockof').'&nbsp;'.dol_print_date(dol_now(), "%d/%m/%Y");
	print '<input type=text size=40 name=reforderfourn value="'.$reforderfourn.'">';
	print '</td><td align=right>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'">';
	print '</td></tr></table>';
	print '</div >';
	print '</form >';
} elseif ($action=="createrestock") {
	// derni�re �tape : la cr�ation des commande fournisseur
	// on r�cup�re la liste des produits � commander
	$tblcmdedet=explode("-", GETPOST("cmdedetlist"));

	// on va utilser un tableau pour stocker commandes fournisseurs et les lignes de commandes fournisseurs � cr�er
	$tblCmdeFourn=array();
	// on parcourt les lignes de commandes produits pour r�cup�rer les fournisseurs, les produits et les quantit�s
	$numlines=1;
	foreach ($tblcmdedet as $idcmdedet) {
		// r�cup des infos de la ligne � cr�er
		$restockcmde_static->fetchdet($idcmdedet, $nbprod);

		$lineoffourn = -1;
		if (GETPOST("fourn-".$idcmdedet)) {
			$tblfourn=explode("-", GETPOST("fourn-".$idcmdedet));
			if ($tblfourn[0]) {
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblCmdeFourn[$j][0] == $tblfourn[0])
						$lineoffourn =$j;

				// si le fournisseur n'est pas d�ja dans le tableau des fournisseurs
				if ($lineoffourn == -1) {
					$tblCmdeFourn[$numlines][0] = $tblfourn[0];
					$tblCmdeFourn[$numlines][1] = array(array(
									$idcmdedet, GETPOST("cmdedet-".$idcmdedet),
									$tblfourn[1], $tblfourn[2], $tblfourn[3]
					));
					$numlines++;
				} else
					$tblCmdeFourn[$lineoffourn][1] = array_merge(
									$tblCmdeFourn[$lineoffourn][1],
									array(array(
													$idcmdedet, GETPOST("cmdedet-".$idcmdedet),
													$tblfourn[1], $tblfourn[2], $tblfourn[3]
									))
					);
			}
		}
	}

	// structure du tableau tblcmefourn
	// [0] -> id du fournisseur
	// [1] -> tableau du produit
		// 0 id de la ligne de commande client
		// 1 quantit� du produit
		// 2 id du prix fournisseur s�lectionn�
		// 3 tx de tva

	// on va maintenant cr�er les commandes fournisseurs
	foreach ($tblCmdeFourn as $CmdeFourn) {
		$idCmdFourn = 0;
		// si il on charge les commandes fournisseurs brouillons
		if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
			// on v�rifie qu'il n'y a pas une commande fournisseur d�j� active
			$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'commande_fournisseur';
			$sql.= ' WHERE fk_soc='.$CmdeFourn[0];
			$sql.= ' AND fk_project ='.$object->id;
			$sql.= ' AND fk_statut=0';
			$sql.= ' AND entity='.$conf->entity;
			if ($conf->global->RESTOCK_FILL_ORDER_DRAFT == 2)
				$sql.= ' AND fk_user_author='.$user->id;

			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$idCmdFourn = $objp->rowid;
			}

			$objectcf = new CommandeFournisseur($db);
			$objectcf->fetch($idCmdFourn);

		}

		// si pas de commande fournisseur s�l�ctionn�e , on en cr�e une
		if ($idCmdFourn == 0) {
			$objectfournisseur = new Fournisseur($db);
			$objectfournisseur->fetch($CmdeFourn[0]);

			$objectcf = new CommandeFournisseur($db);
			$objectcf->ref_supplier		= GETPOST("reforderfourn");
			$objectcf->socid			= $CmdeFourn[0];
			$objectcf->fk_project		= $object->id;
			$objectcf->note_private		= '';
			$objectcf->note_public		= '';

			$objectcf->cond_reglement_id = $objectfournisseur->cond_reglement_supplier_id;
			$objectcf->mode_reglement_id = $objectfournisseur->mode_reglement_supplier_id;

			$idCmdFourn = $objectcf->create($user);
		}

		// [1] -> tableau du produit
		// 0 id de la ligne de commande client
		// 1 quantit� du produit
		// 2 id du prix fournisseur s�lectionn�
		// 3 tx de tva
		// 4 % de remise

		// ensuite on boucle sur les lignes de commandes
		foreach ($CmdeFourn[1] as $lgnCmdeFourn) {

			$restockcmde_static->fetchdet($lgnCmdeFourn[0], $lgnCmdeFourn[1]);

			//var_dump($lgnCmdeFourn);
			//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0,
			//$fk_prod_fourn_price=0, $fourn_ref='', $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $type=0, $info_bits=0, $notrigger=false)
			$result=$objectcf->addline(
							'', 0,
							$lgnCmdeFourn[1],					// $qty
							$lgnCmdeFourn[3],					// TxTVA
							0, 0,
							$restockcmde_static->fk_product,	// $fk_product
							$lgnCmdeFourn[2],					// $fk_prod_fourn_price
							0,
							$lgnCmdeFourn[4],
							'HT',								// $price_base_type
							0, 0								// type
			);

			// r�cup de l'id de la que l'on vient de cr�er
			$sql = 'SELECT rowid from '.MAIN_DB_PREFIX.'commande_fournisseurdet';
			$sql.= ' WHERE fk_commande = '.$idCmdFourn;
			$sql.= ' ORDER BY rowid desc';
			$resql = $db->query($sql);

			if ($resql) {
				$objcf = $db->fetch_object($resql);
				$idcmdefourndet = $objcf->rowid;
			}

			// on cr�e le lien entre les lignes de commandes clients et fournisseurs
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
			$sql.= ' SET fk_commandefourndet = '.$idcmdefourndet;
			$sql.= ' WHERE rowid = '.$lgnCmdeFourn[0];
			$resqlupdate = $db->query($sql);

			// r�cup de l'id de la commande client de la ligne
			$sql = 'SELECT cod.fk_commande, co.ref_client from '.MAIN_DB_PREFIX.'commandedet as cod';
			$sql.= " ,".MAIN_DB_PREFIX."commande as co ";
			$sql.= " WHERE cod.rowid = ".$lgnCmdeFourn[0];
			$sql.= " and co.rowid = cod.fk_commande";
			$resql = $db->query($sql);
//print $sql;exit;
			if ($resql) {
				$objcc = $db->fetch_object($resql);

				//mise � jour du lien entre les commandes si il n'existe pas d�j�
				$objectcf->origin = "commande";
				$objectcf->origin_id = $objcc->fk_commande;
				// on ajoute le lien au autres client
				$ret = $objectcf->add_object_linked();
				// si il y a cr�ation du lien, on ajoute la ref dans la note priv�e
				if ($ret == 1 && $conf->global->RESTOCK_ADD_CUSTORDERREF_IN_PRIVATENOTE) {
					if ($objcc->ref_client) {
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
						$sql.= ' SET note_private = concat_ws("<br>",note_private,"';
						$sql.= $langs->trans("CustomerOrderRef")." : ".$objcc->ref_client.'")';
						$sql.= ' WHERE rowid = '.$idCmdFourn;
						$resqlupdate = $db->query($sql);
					}
				}
				// sinon ce n'est pas la peine, elle y est d�j�...
			}
		}

		// et une petite derni�re pour virer le premier <br>
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
		$sql.= ' SET note_private = substring( note_private, 5)';
		$sql.= ' WHERE rowid = '.$idCmdFourn;
		$resqlupdate = $db->query($sql);
	}


	// une fois que c'est termin�, on affiche les commandes fournisseurs cr�e
	// on cr�e les commandes et on les listes sur l'�cran
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
function select_fournisseur($fournlist, $selected=0, $htmlname='search_fourn', $showempty=1)
{
	global $conf, $langs;

	$nodatarole = '';
	// Enhance with select2
	if ($conf->use_javascript_ajax) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		$comboenhancement = ajax_combobox('select_fournisseur_'.$htmlname);
		$moreforfilter.=$comboenhancement;
		$nodatarole=($comboenhancement?' data-role="none"':'');
	}
	// Print a select with each of them
	$moreforfilter.='<select class="flat minwidth100" id="select_fournisseur_'.$htmlname.'" name="'.$htmlname.'"'.$nodatarole.'>';
	if ($showempty)
		$moreforfilter.='<option value="0">&nbsp;</option>';		   // Should use -1 to say nothing

	if (is_array($fournlist)) {
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
