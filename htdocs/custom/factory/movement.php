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
 *  \file	   htdocs/factory/movement.php
 *  \ingroup	factory
 *  \brief	  list des mouvement de stock associ� � l'OF
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";

require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');

if (! empty($conf->global->FACTORY_ADDON) && is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
	dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");


$langs->load("bills");
$langs->load("products");
$langs->load("stocks");
$langs->load("factory@factory");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (! $sortfield) $sortfield="m.datem";
if (! $sortorder) $sortorder="DESC";


// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result = restrictedArea($user, 'factory');

$mesg = '';

$product = new Product($db);
$factory = new Factory($db);
$form = new Form($db);

$productid=0;
if ($id || $ref) {
	// chargement l'of et le produit associ�
	$result = $factory->fetch($id, $ref);
	$result = $product->fetch($factory->fk_product);
	$id = $factory->id;
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("","", $langs->trans("FactoryMovement"));

dol_htmloutput_mesg($mesg);

$head=factory_prepare_head($factory, $user);
$titre=$langs->trans("Factory");
$picto="factory@factory";
dol_fiche_head($head, 'factorymovement', $titre, 0, $picto);


print '<form name="closeof" action="'.$_SERVER["PHP_SELF"].'?id='.$factory->id.'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="closeof">';
print '<table class="border" width="100%">';
print "<tr>";

//$bproduit = ($product->isproduct());

// Reference
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan=3>';
print $form->showrefnav($factory, 'ref', '', 1, 'ref');
print '</td></tr>';



// Lieu de stockage
print '<tr><td>'.$langs->trans("Warehouse").'</td><td colspan=3>';
if ($factory->fk_entrepot>0) {
	$entrepotStatic=new Entrepot($db);
	$entrepotStatic->fetch($factory->fk_entrepot);
	print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
}

print '</td></tr>';

// Date start planned
print '<tr><td width=20%>'.$langs->trans("FactoryDateStartPlanned").'</td><td width=30%>';
print dol_print_date($factory->date_start_planned, 'day');
print '</td><td width=20%>'.$langs->trans("DateStartMade").'</td><td width=30%>';
print dol_print_date($factory->date_start_made, 'day');
print '</td></tr>';

// Date end planned
print '<tr><td>'.$langs->trans("DateEndPlanned").'</td><td>';
print dol_print_date($factory->date_end_planned, 'day');
print '</td><td>'.$langs->trans("DateEndMade").'</td><td>';
print dol_print_date($factory->date_end_made, 'day');
print '</td></tr>';

// quantity
print '<tr><td>'.$langs->trans("QuantityPlanned").'</td><td>';
print $factory->qty_planned;
print '</td><td>'.$langs->trans("QuantityMade").'</td><td>';
print $factory->qty_made;
print '</td></tr>';

// duration
print '<tr><td>'.$langs->trans("FactoryDurationPlanned").'</td><td>';
print convertSecondToTime($factory->duration_planned,'allhourmin');
print '</td><td>'.$langs->trans("DurationMade").'</td><td>';

print convertSecondToTime($factory->duration_made,'allhourmin');
print '</td></tr>';

print '<tr><td>'.$langs->trans('Status').'</td><td colspan=3>'.$factory->getLibStatut(4).'</td></tr>';
print '<tr><td valign=top>'.$langs->trans('Description').'</td><td colspan=3>';
print str_replace(array("\r\n", "\n"), "<br>", $factory->description);
print '</td></tr>';
print '</table>';
print '<br>';

// tableau de description du produit
print '<table width=100% ><tr><td valign=top width=40%>';
print_fiche_titre($langs->trans("ProducttoBuild"), '', '');

print '<table class="border" width="100%">';

//$bproduit = ($object->isproduct());
print '<tr><td width=30% class="fieldrequired">'.$langs->trans("Product").'</td>';
print '<td>'.$product->getNomUrl(1)." : ".$product->label.'</td></tr>';

// TVA
print '<tr><td>'.$langs->trans("VATRate").'</td>';
print '<td>'.vatrate($product->tva_tx.($product->tva_npr?'*':''), true).'</td></tr>';

// Price
print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
if ($product->price_base_type == 'TTC') {
	print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
	$sale="";
} else {
	print price($product->price).' '.$langs->trans($product->price_base_type);
	$sale=$product->price;
}
print '</td></tr>';

// Price minimum
print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
if ($product->price_base_type == 'TTC')
	print price($product->price_min_ttc).' '.$langs->trans($product->price_base_type);
else
	print price($product->price_min).' '.$langs->trans($product->price_base_type);
print '</td></tr>';

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
print $product->getLibStatut(2, 0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
print $product->getLibStatut(2, 1);
print '</td></tr>';

print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
$product->load_stock();
print '<td>'.$product->stock_reel.'</td></tr>';

print '</table>';

print '</td>';

// tableau de description de la composition du produit
print '<td valign=top>';

// indique si on a d�j� une composition de pr�sente ou pas
$compositionpresente=0;

$prods_arbo =$factory->getChildsOF($id);
print_fiche_titre($langs->trans("FactorisedProductsNumber").' : '.count($prods_arbo), '', '');

// List of subproducts
if (count($prods_arbo) > 0) {
	$compositionpresente=1;
	//print '<b>'.$langs->trans("FactoryTableInfo").'</b><BR>';
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUnitNeed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyFactoryNeed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyConsummed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLosed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUsed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyRestocked").'</td>';


	print '</tr>';
	$mntTot=0;
	$pmpTot=0;

	foreach ($prods_arbo as $value) {
		// verify if product have child then display it after the product name
		$tmpChildArbo=$factory->getChildsArbo($value['id']);
		$nbChildArbo="";
		if (count($tmpChildArbo) > 0)
			$nbChildArbo=" (".count($tmpChildArbo).")";
		print '<tr>';
		print '<td align="left">'.$factory->getNomUrlFactory($value['id'], 1, 'fiche').$nbChildArbo;
		print $factory->PopupProduct($value['id']);
		print '</td>';
		print '<td align="left" title="'.$value['description'].'">';
		print $value['label'].'</td>';
		print '<td align="center">'.$value['nb'];
		if ($value['globalqty'] == 1)
			print "&nbsp;G";
		print '</td>';
		print '<td align="center">'.($value['qtyplanned']).'</td>';

		print '<td align="right">'.$value['qtyused'].'</td>';
		print '<td align="right">'.$value['qtydeleted'].'</td>';
		print '<td align="right">'.($value['qtyused']+$value['qtydeleted']).'</td>';
		print '<td align="right">'.($value['qtyplanned']-($value['qtyused']+$value['qtydeleted'])).'</td>';

		print '</tr>';
	}
	print '</table>';
}
print '</td>';
print '</tr></table>';

print '<br><br>';
print_fiche_titre($langs->trans("FactoryMovement"), '', '');
// list des mouvements associ�s � l'of

$productstatic=new Product($db);
$movement=new MouvementStock($db);
$form=new Form($db);

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type,";
$sql.= " e.ref as stock, e.rowid as entrepot_id, e.lieu,";
$sql.= " m.rowid as mid, m.value, m.datem, m.label, m.fk_origin, m.origintype";
//$sql.= ", m.inventorycode, m.batch, m.eatby, m.sellby";
$sql.= " FROM (".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product as p,";
$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m)";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock', 1).")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
$sql.= " AND m.fk_origin = ".$id;
$sql.= " AND m.origintype = 'factory'";

$sql.= $db->order($sortfield, $sortorder);

//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$param='';
	if ($id) $param.='&id='.$id;
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "m.datem", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductRef"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductLabel"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LabelMovement"), $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Units"), $_SERVER["PHP_SELF"], "m.value", "", $param, 'align="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$arrayofuniqueproduct=array();

	$var=True;
	$i=0;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
		// Product ref
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->product_ref;
		$productstatic->label=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Product label
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Label of movement
		print '<td>'.$objp->label.'</td>';
		// Value
		print '<td align="right">';
		if ($objp->value > 0) print '+';
		print $objp->value.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table></form><br>";
}
llxFooter();
$db->close();
