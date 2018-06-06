<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015-2017	Charlie BENKE			<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file	   htdocs/factory/tabs/factoryorder.php
 *  \ingroup	product
 *  \brief	  Page de création des Ordres de fabrication sur la fiche produit
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');


$langs->load("bills");
$langs->load("products");
$langs->load("companies");
$langs->load("orders");
$langs->load("factory@factory");

$factoryid=GETPOST('factoryid', 'int');
$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'commande', $id, '');

$mesg = '';

$object = new Commande($db);
$factory = new Factory($db);
$product = new Product($db);
$entrepot=new Entrepot($db);


$productid=0;
if ($id || $ref) {
	$result = $object->fetch($id, $ref);
	$productid=$object->id;
	$object->fetch_thirdparty();
	$id=$object->id;
	$factory->id =$id;
}

/*
 * Actions
 */
// build product on each store
if ($action == 'createof' ) {
	if (! empty($conf->global->FACTORY_ADDON) && is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
		dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");


	// on récupère les valeurs saisies
	$factory->fk_entrepot=GETPOST("entrepotid");
	$factory->date_start_planned=$object->date_commande;
	$factory->date_end_planned=$object->date_livraison;
	// on boucle sur la liste des produits à fabriquer
	foreach ($object->lines as $line) {
		// only for product buildable
		if ($factory->is_FactoryProduct($line->fk_product) > 0) {
			$factory->id =$line->fk_product;
			$factory->qty_planned=GETPOST("qty-".$line->id);
			// seulement si il y a des choses à fabriquer
			if ($factory->qty_planned > 0) {
				// on récupère le text de l'extrafields si besoin
				if ($conf->global->factory_extrafieldsNameInfo) {
					$sql = 'SELECT DISTINCT pe.'.$conf->global->factory_extrafieldsNameInfo. ' as addinforecup';
					$sql.= ' FROM '.MAIN_DB_PREFIX.'product_extrafields as pe ';
					$sql.= ' WHERE pe.fk_object =' .$line->fk_product;
					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						if ($objp->addinforecup)
							$factory->description=$objp->addinforecup;
					}
				}

				$factory->sousprods = array();
				$newref=$factory->createof();

				// on crée un lien entre la commande et l'of
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
				$sql.= "fk_source, sourcetype, fk_target, targettype";
				$sql.= ") VALUES (";
				$sql.=  $id ." , 'commande'";
				$sql.= ", ".$newref.", 'factory'";
				$sql.= ")";
				$db->query($sql);
			}
		}


	}
	//var_dump($object);
	// on redirige pour éviter le F5
	header("Location: ".$_SERVER["PHP_SELF"].'?id='.$id);
	exit;
}


/*
 * View
 */


$form = new Form($db);

llxHeader("", "", $langs->trans("FactoryOrder".$product->type));

$head = commande_prepare_head($object);

dol_fiche_head($head, 'factory', $langs->trans("CustomerOrder"), 0, 'order');

$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php'.(! empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';



if (DOL_VERSION >= "5.0.0") {
	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		if (! empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
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
	print '<tr><td width="18%">' . $langs->trans('Ref') . '</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td>';
	print '</tr>';

	// Ref commande client
	print '<tr><td>';
	print $langs->trans('RefCustomer') ;
	print '</td><td colspan="3">';
		print $object->ref_client;
	print '</td>';
	print '</tr>';

	// Third party
	print '<tr><td>' . $langs->trans('Company') . '</td>';
	print '<td colspan="3">' . $object->thirdparty->getNomUrl(1) . '</td>';
	print '</tr>';

	// Date
	print '<tr><td>';
	print $langs->trans('Date');
	print '</td><td colspan="3">';
	print $object->date ? dol_print_date($object->date, 'daytext') : '&nbsp;';
	print '</td>';
	print '</tr>';

	// Delivery date planed
	print '<tr><td height="10">';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';
	print '</td><td colspan="3">';
	print $object->date_livraison ? dol_print_date($object->date_livraison, 'daytext') : '&nbsp;';

	print '</td>';
	print '</tr>';

	// Total TVA
	print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td align="right">' . price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) {
		print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
		print '<td align="right">' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) {
		print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
		print '<td align="right">' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}

	// Total TTC
	print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td align="right">';
	print price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	// Statut
	print '<tr><td>' . $langs->trans('Status') . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';
	print '</table><br><br>';

}
$result = $object->getLinesArray();
print '<br><br>';
print_fiche_titre($langs->trans("ListOfProductBuildable"),'','');
print '<form action="factoryorder.php?id='.$id.'" method="post">';
print '<input type="hidden" name="action" value="createof">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';

if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">&nbsp;</td>';

// Description
print '<td width=150px>'.$langs->trans('Ref').'</td>';
print '<td><label for="">'.$langs->trans('Description').'</label></td>';

// Qty
print '<td align="right" width="120">'.$langs->trans('QtyOrdered').'</label></td>';
print '<td align="right">'.$langs->trans("PhysicalStock").'</td>';
print '<td align="right">'.$langs->trans("StockLimit").'</td>';
print '<td align="right" width="120"><label for="qty">'.$langs->trans('QtyToBuild').'</label></td>';
print "</tr>\n";

// Show object lines
if (! empty($object->lines)) {
	$num = count($object->lines);
	$var = true;
	$i	 = 1;
	$bproductToBuild=false;
	foreach ($object->lines as $line) {
		$var=!$var;
		// only for product buildable
		if ($factory->is_FactoryProduct($line->fk_product) > 0) {
			$bproductToBuild=true;
			$product_static = new Product($db);
			$product_static->type=$line->fk_product_type;
			$product_static->id=$line->fk_product;
			$product_static->ref=$line->ref;
			$text=$product_static->getNomUrl(1);

			$product_static->fetch($line->fk_product);

			$product_static->load_stock();
			$stock_reel = $product_static->stock_reel;
			$seuil_stock_alerte = $product_static->seuil_stock_alerte;

			// Define output language and label
			if (! empty($conf->global->MAIN_MULTILANGS)) {
//				if (! is_object($object->thirdparty))
//				{
//					dol_print_error('','Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
//					return;
//				}

				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang))
					$newlang=$object->thirdparty->default_lang;		// For language to language of customer
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$label = (! empty($product_static->multilangs[$outputlangs->defaultlang]["label"])) ? $product_static->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
			} else
				$label = $line->product_label;


			$text.= ' - '.(! empty($line->label)?$line->label:$label);
			$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($line->description));

			// build the line
			print "<tr>";
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">'.$i.'</td>';
			print "<td align=left>".$text.'</td>';
			print "<td align=left>".$description.'</td>';
			print "<td align=right>".$line->qty.'</td>';
			print "<td align=right>".$stock_reel.'</td>';
			print "<td align=right>".$seuil_stock_alerte.'</td>';

			// détermination de ce qu'il reste à fabriquer
			$qtymade=$factory->getQtylink($id, $line->fk_product);
			if ( ($line->qty - $qtymade) > 0)
				$qtymade = $line->qty - $qtymade;
			elseif ( $qtymade == 0)
				$qtymade = $line->qty ;
			else
				$qtymade = 0;
			print "<td align=right><input type=text size=3 name='qty-".$line->id."' value='".$qtymade."'></td>";

			print "</tr>";
			$i++;
		}
	}
	if ($bproductToBuild) {
		print '<tr class="liste_total">';
		print '<td colspan=5 align=right>'.$langs->trans("Warehouse").' :';
		print select_entrepot_list(GETPOST("entrepotid"),"entrepotid",0,1);
		print '</td>';
		print '<td  align=center><input type="submit" class="butAction" name="verifyof" value="'.$langs->trans("CreateOF").'"></td>';
		print '</tr>';
	}
} else
	print '<tr><td colspan="4">'.$langs->trans("NoFactoryProductFound").'</td></tr>';

print '</table>';
print '</form>';
print '<br><br>';
print_fiche_titre($langs->trans("ListOfProductInOF"),'','');
print '<table id="tablelines" class="noborder noshadow" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';

print '<td width=150px>'.$langs->trans('Ref').'</td>';
print '<td width=150px>'.$langs->trans('RefProduct').'</td>';
print '<td width=150px>'.$langs->trans('Warehouse').'</td>';
print '<td align=center><label for="">'.$langs->trans('DateStartOF').'</label></td>';
print '<td align=center><label for="">'.$langs->trans('DateEndOF').'</label></td>';
// Duration
print '<td align="right" width="100"><label for="qty">'.$langs->trans('DurationInOFPlanned').'</label></td>';
print '<td align="right" width="100"><label for="qty">'.$langs->trans('DurationInOFMade').'</label></td>';

// Qty
print '<td align="right" width="100"><label for="qty">'.$langs->trans('QtyInOFPlanned').'</label></td>';
print '<td align="right" width="100"><label for="qty">'.$langs->trans('QtyInOFMade').'</label></td>';
print '<td align="right" width="100"><label for="qty">'.$langs->trans('Statut').'</label></td>';

print "</tr>\n";

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."element_element as el";
$sql .= " WHERE el.fk_source= ".$id." AND el.sourcetype='commande'";
$sql .= " AND  el.targettype='factory'";

$resOFLink = $db->query($sql);
$num = $db->num_rows($resOFLink);
$i=0;
$var=true;

if ($num == 0)
	print '<tr><td colspan="4">'.$langs->trans("NoLinkedOFMatchFound").'</td></tr>';

while ($i < $num) {
	$objp = $db->fetch_object($resOFLink);
	$factory->fetch($objp->fk_target);
	$product->fetch($factory->fk_product);
	$entrepot->fetch($factory->fk_entrepot);

	$var=!$var;
	$i++;
	print "\n<tr ".$bc[$var].">";
	print "<td>".$factory->getNomURL(1)."</td>";
	print "<td>".$product->getNomURL(1)."</td>";
	print "<td>".$entrepot->getNomURL(1)."</td>";
	if ($factory->statut >= 2) {
		print "<td align=center>".dol_print_date($factory->date_start_made, 'daytext')."</td>";
		print "<td align=center>".dol_print_date($factory->date_end_made, 'daytext')."</td>";
	} else {
		print "<td align=center>".dol_print_date($factory->date_start_planned, 'daytext')."</td>";
		print "<td align=center>".dol_print_date($factory->date_end_planned, 'daytext')."</td>";
	}

	print "<td align=right>".($factory->duration_planned?convertSecondToTime($factory->duration_planned, 'allhourmin'):"")."</td>";
	print "<td align=right>".($factory->duration_made?convertSecondToTime($factory->duration_made, 'allhourmin'):"")."</td>";

	print "<td align=right>".$factory->qty_planned."</td>";
	print "<td align=right>".$factory->qty_made."</td>";
	print "<td align=right>".$factory->getLibStatut(4)."</td>";
	print '</tr>';
}
print '</table>';

dol_htmloutput_mesg($mesg);

/* Barre d'action				*/
print '<div class="tabsAction">';
print '</div>';
llxFooter();
$db->close();