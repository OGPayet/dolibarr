<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015-2016	Charlie BENKE			<charlie@patas-monkey.com>
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
 *  \file	   htdocs/factory/orderfactory.php
 *  \ingroup	product
 *  \brief	  Page de création des Ordres de fabrication des commandes en cours
 */

$res=@include(".../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
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

$commande = new Commande($db);
$factory = new Factory($db);
$product = new Product($db);
$entrepot=new Entrepot($db);



/*
 * Actions
 */
// build product on each store

if ($action == 'createof' ) {

	if (! empty($conf->global->FACTORY_ADDON)
		&& is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
		dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");


	// on détermine les produits à partir des commandes à créer
	$tblof = $factory->getProductsListFromOrders();

	// on récupère les valeurs saisies
	$factory->fk_entrepot=GETPOST("entrepotid");
	//$factory->date_start_planned=$object->date_commande;
	//$factory->date_end_planned=$object->date_livraison;


	$productid=$tblof[0]['fk_product'];
	// on boucle sur la liste des produits à fabriquer
	foreach ($tblof as $line) {
		if ($line['fk_product'] != $productid) {
			$factory->id = $productid;
			$factory->qty_planned=GETPOST("qty-".$productid);
			// seulement si il y a des choses à fabriquer
			if ($factory->qty_planned > 0) {
				// on récupère le text de l'extrafields si besoin
				if ($conf->global->factory_extrafieldsNameInfo) {
					$sql = 'SELECT DISTINCT pe.'.$conf->global->factory_extrafieldsNameInfo. ' as addinforecup';
					$sql.= ' FROM '.MAIN_DB_PREFIX.'product_extrafields as pe ';
					$sql.= ' WHERE pe.fk_object =' .$productid;
					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						if ($objp->addinforecup)
							$factory->description=$objp->addinforecup;
					}
				}

				$factory->sousprods = array();
				$newref=$factory->createof();

				// récupération du tableau des commandes
				$tblcommandeid = explode(":", substr($commandelist, 0, -1));

				// on boucle sur les commandes à associer à l'OF
				foreach ($tblcommandeid as $commandeid) {
					// on crée un lien entre la commande et l'of
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
					$sql.= "fk_source, sourcetype, fk_target, targettype";
					$sql.= ") VALUES (";
					$sql.=  $commandeid ." , 'commande'";
					$sql.= ", ".$newref.", 'factory'";
					$sql.= ")";
					$db->query($sql);
				}
			}

			// on mémorise le nouveau produit qui a fait la rupture
			$productid = $line['fk_product'];
			$commandelist = $line['fk_commande'].":";
		} else {
			// on mémorise juste l'id de la commande pour faire le lien ensuite
			$commandelist .= $line['fk_commande'].":";
		}
	}

	// on traite enfin le dernier produit à la sortie de la liste
	$factory->id = $productid;
	$factory->qty_planned=GETPOST("qty-".$productid);
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

		// récupération du tableau des commandes
		$tblcommandeid = explode(":", substr($commandelist, 0, -1));

		// on boucle sur les commandes à associer à l'OF
		foreach ($tblcommandeid as $commandeid) {
			// on crée un lien entre la commande et l'of
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
			$sql.= "fk_source, sourcetype, fk_target, targettype";
			$sql.= ") VALUES (";
			$sql.=  $commandeid ." , 'commande'";
			$sql.= ", ".$newref.", 'factory'";
			$sql.= ")";
			$db->query($sql);
		}
	}


	//var_dump($object);
	// on redirige pour éviter le F5
//	header("Location: ".$_SERVER["PHP_SELF"]);
//	exit;
}


/*
 * View
 */


$form = new Form($db);

llxHeader("", "", $langs->trans("FactoryFromOrders"));

// on détermine les produits à partir des commandes à créer
$tblof = $factory->getProductsListFromOrders();

print '<table class="border" width="100%">';

print_fiche_titre($langs->trans("ListOfProductBuildableFromOrders"), '', '');
print '<form action="orderfactory.php" >';
print '<input type="hidden" name="action" value="createof">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';

// Show object lines
if (count($tblof) > 0) {
	print '<tr class="liste_titre nodrag nodrop">';

	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">&nbsp;</td>';

	// Description
	print '<td width=150px>'.$langs->trans('Ref').'</td>';
	print '<td><label for="">'.$langs->trans('Description').'</label></td>';
	print '<td><label for="">'.$langs->trans('OrderList').'</label></td>';

	// Qty
	print '<td align="right" width="120">'.$langs->trans('QtyOrdered').'</label></td>';
	print '<td align="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td align="right">'.$langs->trans("StockLimit").'</td>';
	print '<td align="right" width="120"><label for="qty">'.$langs->trans('QtyToBuild').'</label></td>';


	print "</tr>\n";

	$num = count($tblof);
	$var = true;
	$i	 = 1;

	$productid=$tblof[0]['fk_product'];
	$product_static = new Product($db);

	foreach ($tblof as $line) {

		// if new product, we print the old
		if ($productid != $line['fk_product']) {
			$var=!$var;
			$product_static->fetch($line['fk_product']);
			$text=$product_static->getNomUrl(1);

			$product_static->load_stock();
			$stock_reel = $product_static->stock_reel;
			$seuil_stock_alerte = $product_static->seuil_stock_alerte;

			// Define output language and label
			if (! empty($conf->global->MAIN_MULTILANGS)) {

				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang))
					$newlang=$object->thirdparty->default_lang;		// For language to language of customer
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				if (! empty($product_static->multilangs[$outputlangs->defaultlang]["label"]))
					$label = $product_static->multilangs[$outputlangs->defaultlang]["label"];
				else
					$label = $line->product_label;
			} else
				$label = $product_static->label;


			$text.= ' - '.$label;
			$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($product_static->description));

			// build the line
			print "<tr>";
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">'.$i.'</td>';
			print "<td align=left>".$text.'</td>';
			print "<td align=left>".$description.'</td>';
			print "<td align=left>".$orderList.'</td>';

			print "<td align=right>".$orderqty.'</td>';
			print "<td align=right>".$stock_reel.'</td>';
			print "<td align=right>".$seuil_stock_alerte.'</td>';

			print "<td align=right>';
			print '<input type=text size=3 name='qty-".$productid."' value='".$orderqty ."'>";
			print "</td></tr>";

			// on mémorise les nouvelles valeurs
			$productid= $line['fk_product'];
			$commande->fetch($line['fk_commande']);
			$orderlist = $commande->getNomUrl(1)."&nbsp;";
			$orderqty  = $line['qty'];
		} else {
			$commande->fetch($line['fk_commande']);
			$orderlist.= $commande->getNomUrl(1)."&nbsp;";
			$orderqty+=$line['qty'];
		}
		$i++;
	}

	// et à la fin on traite le dernier
	$var=!$var;
	$product_static->fetch($line['fk_product']);
	$text=$product_static->getNomUrl(1);

	$product_static->load_stock();
	$stock_reel = $product_static->stock_reel;
	$seuil_stock_alerte = $product_static->seuil_stock_alerte;

	// Define output language and label
	if (! empty($conf->global->MAIN_MULTILANGS)) {

		$outputlangs = $langs;
		$newlang='';
		if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
		if (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang))
			$newlang=$object->thirdparty->default_lang;		// For language to language of customer
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (! empty($product_static->multilangs[$outputlangs->defaultlang]["label"]))
			$label = $product_static->multilangs[$outputlangs->defaultlang]["label"];
		else
			$label = $line->product_label;
	} else
		$label = $product_static->label;


	$text.= ' - '.$label;
	$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($product_static->description));

	// build the line
	print "<tr>";
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
		print '<td align="center" width="5">'.$i.'</td>';

	print "<td align=left>".$text.'</td>';
	print "<td align=left>".$description.'</td>';
	print "<td align=left>".$orderList.'</td>';

	print "<td align=right>".$orderqty.'</td>';
	print "<td align=right>".$stock_reel.'</td>';
	print "<td align=right>".$seuil_stock_alerte.'</td>';

	print "<td align=right><input type=text size=3 name='qty-".$productid."' value='".$orderqty ."'></td>";
	print "</tr>";


	print '<tr class="liste_total">';
	print '<td colspan=5 align=right>'.$langs->trans("Warehouse").' :';
	print select_entrepot_list(GETPOST("entrepotid"), "entrepotid", 0, 1);
	print '</td>';
	print '<td  align=center><input type="submit" class="butAction" value="'.$langs->trans("CreateOF").'"></td>';
	print '</tr>';
	print '</table>';

}
print '</form>';

llxFooter();
$db->close();