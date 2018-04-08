<?php
/* Copyright (C) 2012-2017	Charlie Benke	 <charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/predefevent.php
 *	\brief	  List of predefined event of the product
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("product");
$langs->load("equipement@equipement");

$productid=GETPOST('id', 'int');
$action=GETPOST('action');



if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');


$form = new Form($db);
llxHeader();

$object = new Product($db);

$refproduct=GETPOST('ref', 'alpha');
$result = $object->fetch($productid, $refproduct);
if ($refproduct) $productid = $object->id;

$head = product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==1?'service':'product');
dol_fiche_head($head, 'equipement', $titre, 0, $picto);



/*
 * Actions
 */

if ($action=="save") {
	$sql = "SELECT eet.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_equipementevt_type as eet";
	$result=$db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementevt_predef ";
		$sql.= " WHERE fk_product = ".$productid;
		$res=$db->query($sql);

		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);
			$description = GETPOST("descr-".$objp->rowid);
			if ( $description != "") {
				// on aliment la table des actions prédef
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."equipementevt_predef ";
				$sql.= "(fk_product, fk_equipementevt_type, description) VALUES";
				$sql.= " (".$productid.", ".$objp->rowid. ", ";
				$sql.= "'".$db->escape($description)."')";
				$res=$db->query($sql);
			}
			$i++;
		}
	}
}

/*
 * View
 */

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="border" width="100%">';

print '<tr>';
print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($object,'ref','',1,'ref');


print '</td>';
print '</tr>';

// Label
print '<tr><td>'.$langs->trans("Label").'</td>';
print '<td colspan="3">'.($object->label ? $object->label:$object->libelle).'</td></tr>';

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
print $object->getLibStatut(2, 0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
print $object->getLibStatut(2, 1);
print '</td></tr>';

print '</table></form><br>';

print dol_fiche_end();

$equipement = new Equipement($db);

$head = equipement_product_prepare_head($object, $user);

//dol_fiche_head($head, 'task_task', $langs->trans("Task"),0,'projecttask');
dol_fiche_head($head, 'event', $langs->trans("AssociatedEquipement"), 0, "equipement@equipement");

$sql = "SELECT eet.rowid, eet.libelle, eet.active, eep.description, eep.fk_product";

$sql.= " FROM ".MAIN_DB_PREFIX."c_equipementevt_type as eet";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt_predef as eep ON eep.fk_equipementevt_type = eet.rowid";
$sql.= " WHERE eep.fk_product is null or eep.fk_product = ".$productid;

$sql.= " ORDER BY eet.libelle";

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	print_barre_liste($langs->trans("ListOfEquipementEvt"), "", "", "", "", "", "", $num);

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$productid.'">';
	print '<input type="hidden" class="flat" name="action" value="save">';
	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre(
					$langs->trans("Label")." / ".$langs->trans("Description"), $_SERVER["PHP_SELF"], "libelle",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Active"), $_SERVER["PHP_SELF"], "active",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print "</tr>\n";


	$var=True;

	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td >".$langs->trans($objp->libelle)."</td>";
		print "<td width=50px>".yn($objp->active).'</td>';
		print "</tr>\n";
		print "<tr $bc[$var]>";
		print "<td colspan=2 ><textarea cols=120 rows=5 name='descr-".$objp->rowid."'>";
		print $objp->description;
		print"</textarea></td>";
		print "</tr>\n";

		$i++;
	}
	print '</table>';
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction">';
print '<input type="submit" class="button" value="'.$langs->trans('Save').'" name="addline">';
	print '</div></div>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

print dol_fiche_end();

llxFooter();
$db->close();