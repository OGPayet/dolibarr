<?php
/* Copyright (C) 2012-2017	Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/index.php
 *  \ingroup	equipement
 *  \brief	  Page accueil des équipement est des évènements
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory


dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$equipementid = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;

$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');
$langs->load("equipement@equipement");

$equipement_static = new Equipement($db);


/*
 * View
 */

$transAreaType = $langs->trans("EquipementsArea");
$helpurl='EN:Module_Equipements|FR:Module_Equipements|ES:M&oacute;dulo_Equipementos';

llxHeader("", $transAreaType, $helpurl);

print_fiche_titre($transAreaType);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche equipement
 */
$rowspan=5;
if ($conf->barcode->enabled) $rowspan++;
print '<form method="post" action="list.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<tr class='liste_titre'>";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="14" name="sall"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
if ($conf->barcode->enabled) {
	print "<tr $bc[0]><td>";
	print $langs->trans("BarCode").':</td><td><input class="flat" type="text" size="14" name="sbarcode"></td>';
	print '</tr>';
}
print "<tr $bc[0] ><td>".$langs->trans("RefProduit").':</td>';
print '<td><input class="flat" type="text" size="14" name="srefproduit"></td>';
print '</tr>';
print "</table></form><br>";

/*
 * Equipement par état
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("EquipementEtatRepart").'</td></tr>';

$sql = "SELECT COUNT(e.rowid) as total, e.fk_etatequipement, ee.libelle as etatequiplibelle";
$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= ' WHERE e.entity IN ('.getEntity($product_static->element, 1).')';
$sql.= " GROUP BY e.fk_etatequipement, ee.libelle ";
$result = $db->query($sql);
$statProducts="";
while ($objp = $db->fetch_object($result)) {
	$statProducts.= "<tr >";
	$statProducts.= '<td><a href="list.php?search_etatequipement='.$objp->fk_etatequipement.'">';
	$statProducts.= ($objp->etatequiplibelle?$langs->trans($objp->etatequiplibelle):$langs->trans("None"));
	$statProducts.= '</a></td><td align="right">'.$objp->total.'</td>';
	$statProducts.= "</tr>";
	$total=$total+$objp->total;
}
print $statProducts;
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';
print '<br>';


/*
 * Evenement par status
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("EquipementEvtTypeRepart").'</td></tr>';

$sql = "SELECT COUNT(ee.rowid) as total, ee.fk_equipementevt_type, eet.libelle as statuteventlibelle";
$sql.= " FROM ".MAIN_DB_PREFIX."equipementevt as ee";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
//$sql.= ' WHERE ee.entity IN ('.getEntity($product_static->element, 1).')';
$sql.= " GROUP BY ee.fk_equipementevt_type, eet.libelle ";
$result = $db->query($sql);

$statProducts="";
$total=0;
if ($result) {
	while ($objp = $db->fetch_object($result)) {
		$statProducts.= "<tr >";
		$statProducts.= '<td><a href="listEvent.php?search_equipevttype='.$objp->fk_equipementevt_type.'">';
		$statProducts.= ($objp->statuteventlibelle?$langs->trans($objp->statuteventlibelle):$langs->trans("None"));
		$statProducts.= '</a></td><td align="right">'.$objp->total.'</td>';
		$statProducts.= "</tr>";
		$total=$total+$objp->total;
	}
	print $statProducts;
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';

/*
 * Last modified equipements
 */
$max=10;
$sql = "SELECT e.rowid, e.ref, p.ref as refproduit, e.quantity, p.label, ee.libelle as etatequimement,";
$sql.= " e.tms as datem, fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " WHERE e.entity IN (".getEntity($product_static->element, 1).")";
$sql.= $db->order("e.tms", "DESC");
$sql.= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	if ($num > 0) {
		$lastModifiedEquipement = $langs->trans("LastEquipement", $max);

		print '<table class="noborder" width="100%">';
		$colnb=5;
		print '<tr class="liste_titre"><td colspan="'.$colnb.'">'.$lastModifiedEquipement.'</td></tr>';

		$var=True;

		while ($i < $num) {
			$objp = $db->fetch_object($result);

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td nowrap="nowrap">';
			$equipement_static->fetch($objp->rowid);
			//$equipement_static->ref=$objp->ref;
			//$equipement_static->fk_product=$objp->refproduit;
			print $equipement_static->getNomUrl(1);
			print "</td>\n";
			print '<td>'.$objp->refproduit. " - ".dol_trunc($objp->label, 32).'</td>';
			print "<td>".($objp->etatequimement?$langs->trans($objp->etatequimement):'')."</td>";
			print "<td>".dol_print_date($db->jdate($objp->datem), 'day')."</td>";
			print "<td align=right>".$equipement_static->LibStatut($objp->fk_statut, 5)."</td>";
			print "</tr>\n";
			$i++;
		}

		print "</table>";
	}
} else
	dol_print_error($db);

print '<br><br>';

/*
 * Last modified EquipementEvent
 */
$max=10;
$sql = "SELECT e.rowid, e.ref, p.ref as refproduit, p.label, e.fk_statut,";
$sql.= " eet.libelle as statuteventlibelle, ee.datee as dateevt";
$sql.= " FROM ".MAIN_DB_PREFIX."equipementevt as ee";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid,";
$sql.= " ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " WHERE e.entity IN (".getEntity($product_static->element, 1).")";
$sql.= " and e.rowid = ee.fk_equipement";
$sql.= $db->order("ee.tms", "DESC");
$sql.= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0) {
		$lastModifiedEquipement = $langs->trans("LastEquipementEvent", $max);

		print '<table class="noborder" width="100%">';
		$colnb=5;
		print '<tr class="liste_titre"><td colspan="'.$colnb.'">'.$lastModifiedEquipement.'</td></tr>';
		$var=True;

		while ($i < $num) {
			$objp = $db->fetch_object($result);

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td nowrap="nowrap">';
			$equipement_static->fetch($objp->rowid);
			print $equipement_static->getNomUrl(1);
			print "</td>\n";
			print '<td>'.$objp->refproduit. " - ".dol_trunc($objp->label, 32).'</td>';
			print "<td>".($objp->statuteventlibelle?$langs->trans($objp->statuteventlibelle):'')."</td>";
			print "<td>";
			print dol_print_date($db->jdate($objp->dateevt), 'day');
			print "</td>";
			print "<td align=right>".$equipement_static->LibStatut($objp->fk_statut, 5)."</td>";
			print "</tr>\n";
			$i++;
		}
		print "</table>";
	}
} else
	dol_print_error($db);

print '</td></tr></table>';

llxFooter();
$db->close();