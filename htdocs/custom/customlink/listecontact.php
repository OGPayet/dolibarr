<?php
/* Copyright (C) 2014-2017	Charlie BENKE	 <charlie@patas-monkey.com>
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
 *	  \file	   htdocs/customlink/listecontact.php
 *	  \ingroup	tools
 *	  \brief	  liste liens présent dans 
 */


$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');

$langs->load("customlink@customlink");


// Security check
$result=restrictedArea($user, 'customlink');
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="ec.fk_c_type_contact";
if (! $sortorder) $sortorder="ASC";

$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page;

$objectlink=new Customlink($db);

$refid="";

$action = GETPOST("action");
if ($action=="delete") {
	$ret=$objectlink->fetch(GETPOST("rowid"));
	$ret=$objectlink->delete($user);
}

$typeelement=GETPOST("typeelement");
if (!$typeelement)
	$typeelement=-1;
$refelement=GETPOST("refelement");
if ($refelement) {
	// on détermine l'id de l'élément selon le type
	$refid=$objectlink->get_idlink($typeelement, $refelement);
}

$sql = "SELECT *";
$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
// on applique les filtres
if ($typeelement !=-1) {
	if ($refid > 0) {
		$sql .= " WHERE concat(sourcetype,'-', fk_source)='".$typeelement."-".$refid."'";
		$sql .= " OR concat(targettype,'-', fk_target)='".$typeelement."-".$refid."'";
	}
}

$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit+1, $offset);

$help_url='EN:Module_CustomLink_En|FR:Module_Customlink|ES:M&oacute;dulo_Customlink';
llxHeader("", $langs->trans("ListOfContacts"), $help_url);

print_barre_liste(
				$langs->trans("ListOfContacts"), $page, 
				"listecontact.php", "", $sortfield, $sortorder, '', $num
);
print '<br>';
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="filter">';
print '<table ><tr>';
print '<td>';
select_element_type($typeelement, 'typeelement', 1);
print '</td>';
print '<td>'.$langs->trans("Ref").'</td><td>';
print '<input type=text size=10 name=nomcontact id=nomcontact value="'.GETPOST("nomcontact").'"></td>';
print '<td><input type=submit name=search ></td>';
print '</tr></table>';
print '</form><br>';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';

print_liste_field_titre($langs->trans("UpdateContact"), "", "", '', '', ' width=50px align="left"', "", "");
print_liste_field_titre($langs->trans("SourceRef"), "index.php", "", '', '', 'align="left"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("TargetRef"), "index.php", "", '', '', 'align="left"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DeleteContact"), "", "", '', '', 'width=50px align="right"', "", "");

print "</tr>\n";
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	if ($num) {
		$var=True;
		while ($i < min($num, $limit)) {
			$objp = $db->fetch_object($result);

			$var=!$var;
			print "<tr ".$bc[$var].">";

			print '<td><a href="fichecontact.php?rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			print '<td><a href="listecontact.php?rowid='.$objp->rowid.'&action=delete">'.img_delete().'</a></td>';
			print "</tr>\n";
			$i++;
		}
	}
} else
	dol_print_error($db);

$db->free($result);
print "</table>";

llxFooter();
$db->close();