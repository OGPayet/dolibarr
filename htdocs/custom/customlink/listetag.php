<?php
/* Copyright (C) 2014-2017	Charlie BENKE	 <charlie@patas-monkey.Com>
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
 *	  \file	   htdocs/customlink/listtag.php
 *	  \ingroup	tools
 *	  \brief	  liste liens tag présent dans 
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
if (! $sortfield) $sortfield="et.element";
if (! $sortorder) $sortorder="ASC";

$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page;

$objectlink=new Customlink($db);

$refid="";

$action = GETPOST("action");
if ($action=="delete") {
	$objectlink->rowid=GETPOST("rowid");
	$ret=$objectlink->deletetag($user);
}


$typeelement=GETPOST("typeelement");
if (!$typeelement)
	$typeelement=-1;
$refelement=GETPOST("refelement");
if ($refelement) {
	// on détermine l'id de l'élément selon le type
	$refid=$objectlink->get_idlink($typeelement, $refelement);
}
$tag=GETPOST("tag");

$sql = "SELECT *";
$sql .= " FROM ".MAIN_DB_PREFIX."element_tag as et";
$sql .= " WHERE 1=1";
// on applique les filtres
if ($typeelement != '-1')
	$sql .= " AND element='".$typeelement."'";	
// on applique les filtres
if ($tag)
	$sql .= " AND tag like'%".$tag."%'";	

$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit+1, $offset);


$help_url='EN:Module_CustomLink_En|FR:Module_Customlink|ES:M&oacute;dulo_Customlink';
llxHeader("", $langs->trans("ListOfLinks"), $help_url);

print_barre_liste($langs->trans("ListOfTags"), $page, "index.php", "", $sortfield, $sortorder, '', $num);

print '<br>';
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="filter">';
print '<table ><tr>';
print '<td>';
select_element_type($typeelement, 'typeelement', 1);
print '</td>';
print '<td>'.$langs->trans("Tag").'</td><td>';
print '<input type=text size=10 name=tag id=tag value="'.$tag.'"></td>';
print '<td><input type=submit name=search ></td>';
print '</tr></table>';
print '</form>';
print '<br>';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';

print_liste_field_titre("", "", "", '', '', ' width=50px align="left"', "", "");
print_liste_field_titre(
				$langs->trans("SourceRef"), "listetag.php", 
				"", '', '', 'align="left"', $sortfield, $sortorder
);
print_liste_field_titre(
				$langs->trans("TagLink"), "listetag.php", 
				"", '', '', 'align="left"', $sortfield, $sortorder
);
print_liste_field_titre("", "", "", '', '', 'width=50px align="right"', "", "");

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
			
			print '<td><a href="fichetag.php?rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			print '<td>'.$objectlink->getUrlofLink($objp->element, $objp->fk_element).'</td>';
			print '<td>'.$objp->tag.'</td>';
			print '<td align=right><a href="listetag.php?rowid='.$objp->rowid.'&action=delete">';
			print img_delete().'</a></td>';
			print "</tr>\n";
			$i++;
		}
	}
} else
	dol_print_error($db);

print "</table>";

$db->free($result);

llxFooter();
$db->close();