<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2016 Charlie Benke		<charlie@patas-monkey.com>
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
 *	\file	   htdocs/customlink/index.php
 *  \ingroup	tools
 *  \brief	  Homepage customlinks
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");	// For "custom" directory

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');
$langs->load("customlink@customlink");

// pour gérer la traduction de l'expé qui merde au niveau du nom
$langs->load("sendings");


$object=new Customlink($db);


/*
 * View
 */

$transAreaType = $langs->trans("ProductsAndServicesArea");
$helpurl='';

$transAreaType = $langs->trans("CustomLinkArea");
$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';

llxHeader("", $langs->trans("CustomLink"), $helpurl);

print_fiche_titre($transAreaType);


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Search Area of link, tag, ventilation
 */
print '<form method="post" action="listelink.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("SearchLink").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Element").':</td><td>';
select_element_type($typeelement, 'typeelement', 0, 1);
print '</td>';
print '<td rowspan="2"><input type="submit" class="button" value="'.$langs->trans("SearchLink").'"></td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="14" name="refelement"></td>';
print '</tr>';
print "</table></form>";
print '<form method="post" action="listetag.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("SearchTag").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Element").':</td><td>';
select_element_type($typeelement, 'typeelement', 1, 1);
print '</td>';
print '<td rowspan="2"><input type="submit" class="button" value="'.$langs->trans("SearchTag").'"></td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Tag").':</td><td><input class="flat" type="text" size="14" name="tag"></td>';
print '</tr>';
print "<tr ".$bc[true]."><td colspan=3 >";
print_tag_list_count();
print '</td>';
print "</tr>";

print "</table></form><br>";

/*
 * Number of customlink element
 */

$sql = "SELECT COUNT(e.rowid) as total" ;
$sql.= " FROM ".MAIN_DB_PREFIX."element_element as e";
$result = $db->query($sql);
$objp = $db->fetch_object($result);
$nblink=$objp->total;

$sql = "SELECT COUNT(e.rowid) as total" ;
$sql.= " FROM ".MAIN_DB_PREFIX."element_tag as e";
$result = $db->query($sql);
$objp = $db->fetch_object($result);
$nbtag=$objp->total;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';

print "<tr $bc[0]>";
print '<td><a href="listelink.php">'.$langs->trans("NbOfLink").'</a></td><td align="right">'.round($nblink).'</td>';
print "</tr>";
print "<tr $bc[1]>";
print '<td><a href="listetag.php">'.$langs->trans("NbOfTag").'</a></td><td align="right">'.round($nbtag).'</td>';
print "</tr>";
print '</table>';


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last link
 */
$max=15;
$sql = "SELECT *";
$sql .= " FROM ".MAIN_DB_PREFIX."element_element as el";
$sql.= " ORDER BY el.rowid DESC";
$sql.= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0) {
		print '<table class="noborder" width="100%">';
		$colnb=5;
		if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

		print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastNewLinks").'</td></tr>';

		$var=True;

		while ($i < $num) {
			$objp = $db->fetch_object($result);
			$object->fetch($objp->rowid);
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td width=30px class="nowrap">'.$object->getNomUrl().'</td>';
			print '<td>'.$object->getUrlofLink($objp->sourcetype, $objp->fk_source).'</td>';
			print '<td>'.$object->getUrlofLink($objp->targettype, $objp->fk_target).'</td>';

			print "</tr>\n";
			$i++;
		}
		print "</table>";
	}
} else
	dol_print_error($db);

print "<br>";
$max=15;
$sql = "SELECT *";
$sql .= " FROM ".MAIN_DB_PREFIX."element_tag as et";
$sql.= " ORDER BY et.rowid DESC";
$sql.= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0) {
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastNewTags").'</td></tr>';
		$var=True;

		while ($i < $num) {
			$objp = $db->fetch_object($result);
			$object->rowid =$objp->rowid;
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td width=30px class="nowrap">'.$object->getNomUrlTag().'</td>';
			print '<td width=50%>'.$object->getUrlofLink($objp->element, $objp->fk_element).'</td>';
			print '<td align=left><a href="listetag.php?tag='.$objp->tag.'">'.$objp->tag.'</a></td>';

			print "</tr>\n";
			$i++;
		}
		print "</table>";
	}
} else
	dol_print_error($db);
	
print '</div></div></div>';

llxFooter();
$db->close();