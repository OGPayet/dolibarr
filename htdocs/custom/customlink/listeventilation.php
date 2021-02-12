<?php
/* Copyright (C) 2014-2016	Charlie BENKE	 <charlie@patas-monkey.com>
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
 *	  \file	   htdocs/customlink/listeventilation.php
 *	  \ingroup	tools
 *	  \brief	  liste des ventilation réalisées
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) 
	$res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');

$langs->load("customlink@customlink");


// Security check
$result=restrictedArea($user, 'customlink');
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="ffv.datev";
if (! $sortorder) $sortorder="ASC";

$sourceref = GETPOST("sourceref");
$socnamesource = GETPOST("socnamesource");
$targetref = GETPOST("targetref");
$socnametarget = GETPOST("socnametarget");
$label = GETPOST("label");
$dateventil = GETPOST("dateventil");
$total_ttc = GETPOST("total_ttc");

$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page;

$objectlink=new Customlink($db);

$refid="";

$action = GETPOST("action");
if ($action=="delete") {
	$objectlink->rowid=GETPOST("rowid");
	$ret=$objectlink->deleteVentilation($user);
}
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture_fourn_ventil as ffv";
$sql .= " WHERE 1=1";

if ($sourceref)
	$sql.= " AND fk_facture_fourn=".$objectlink->get_idlink('invoice_supplier', $sourcename);
if ($targetref) // pour le moment le filtre ne se fait que sur les facture client...
	$sql.= " AND fk_facture_link=".$objectlink->get_idlink('facture', $targetname);

if ($socnamesource)
	$sql.= " AND fk_socid_fourn=".$objectlink->get_idsoc($socnamesource);

if ($socnametarget)
	$sql.= " AND fk_socid_link=".$objectlink->get_idsoc($socnametarget);

if ($label)
	$sql.= " AND label like '%".$db->escape($label)."%'";


$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit+1, $offset);
//print $sql;

$help_url='EN:Module_CustomLink_En|FR:Module_Customlink|ES:M&oacute;dulo_Customlink';
llxHeader("", $langs->trans("ListOfVentilations"), $help_url);

print_barre_liste(
				$langs->trans("ListOfVentilations"), $page, "listeventilation.php", 
				"", $sortfield, $sortorder, '', $num
);

print '<br>';
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="filter">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print_liste_field_titre(
				$langs->trans("SourceRef")." - ".$langs->trans("Tiers"), "listeventilation.php",
				"", '', '', 'align="left"', $sortfield, $sortorder
);
print_liste_field_titre(
				$langs->trans("TargetRef")." - ".$langs->trans("Tiers")." / ".$langs->trans("Products"),
				"listeventilation.php", "", '', '', 'align="left"', $sortfield, $sortorder
);
print_liste_field_titre($langs->trans("label"), "", "", "", $urlparam, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DateVentilation"), "", "", "", '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre(
				$langs->trans("AmountTTC"), "listeventilation.php", "", '', '', 
				'align="right"', $sortfield, $sortorder
);
print_liste_field_titre("", "", "", '', '', '', "", "");
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type=text size=15 name=sourceref id=sourceref value="'.$sourceref.'">';
print '&nbsp;<input type=text size=15 name=socnamesource id=socnamesource value="'.$socnamesource.'"></td>';
print '<td class="liste_titre"><input type=text size=15 name=targetref id=targetref value="'.$targetref.'">';
print '&nbsp;<input type=text size=15 name=socnametarget id=socnametarget value="'.$socnametarget.'"></td>';
print '<td class="liste_titre"><input type=text size=20 name=label id=label value="'.$label.'"></td>';
print '<td colspan=3 class="liste_titre" align="right">';
print '<input class="liste_titre" ';
print ' type="image" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" ';
print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter"';
print ' src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'" ';
print ' value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"';
print ' title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';

print "</tr>\n";
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;
	if ($num) {
		$var=True;
		while ($i < min($num, $conf->liste_limit)) {
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td>'.$objectlink->getUrlofLink('invoice_supplier', $objp->fk_facture_fourn, 1).'</td>';
			print '<td>';

			switch ($objp->fk_facture_typelink) {
				case 0 :
					print $objectlink->getUrlofLink('facture', $objp->fk_facture_link, 1);
					break;
				case 1 :
					print $objectlink->getUrlofLink('invoice_supplier', $objp->fk_facture_link, 1);
					break;
				case 2 :
					print $objectlink->getUrlofLink('factory', $objp->fk_facture_link, 1);
					break;
			}
			print '</td>';
			print '<td valign=top>'.$objp->label.'</td>';
			print '<td valign=top align=right>'.dol_print_date($objp->datev, "daytext").'</td>';
			print '<td valign=top align=right>'.price($objp->total_ttc).'</td>';
			print '<td valign=top align=right><a href="lisventilation.php?rowid='.$objp->rowid.'&action=delete">';
			print img_delete().'</a></td>';
			print "</tr>\n";
			$i++;
		}
	}
}
else
	dol_print_error($db);

$db->free($result);

print "</table>";
print '</form>';

llxFooter();
$db->close();