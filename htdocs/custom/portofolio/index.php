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

dol_include_once('/portofolio/core/lib/portofolio.lib.php');

$result = restrictedArea($user, 'portofolio', $id, 'portofolio', '', '');
$langs->load("portofolio@portofolio");

if ($conf->category->enabled) {
	// on ajoute selon les catégories présentes
	$categoriesarray =  array();
	if (! empty($conf->product->enabled) || ! empty($conf->product->enabled));
		$categoriesarray=array_merge(
						$categoriesarray, array('product' => array(0, $langs->transnoentities('Products')))
		);

	if (! empty($conf->societe->enabled)) {
		$categoriesarray=array_merge(
						$categoriesarray, array('societe' => array(2, $langs->transnoentities('Companys')))
		);
		if (! empty($conf->fournisseur->enabled))
			$categoriesarray=array_merge($categoriesarray, array('fournisseur'	=> array(1, $langs->transnoentities('Fournish'))));
		$categoriesarray=array_merge($categoriesarray, array('socpeople'		=> array(4, $langs->transnoentities('Contacts'))));
	}
	if (! empty($conf->adherent->enabled))
		$categoriesarray=array_merge($categoriesarray, array('member'			=> array(3, $langs->transnoentities('Members'))));

	if (! empty($conf->projet->enabled) && DOL_VERSION >= "5.0.0")
		$categoriesarray=array_merge($categoriesarray, array('project'			=> array(6, $langs->transnoentities('Projects'))));

	$categoriesarray=array_merge($categoriesarray, array('user'					=> array(4, $langs->transnoentities('Users'))));
}

/*
 * View
 */

$transAreaType = $langs->trans("PortofolioArea");
$helpurl='EN:Module_Portofolio|FR:Module_Portofolio|ES:M&oacute;dulo_Portofolio';

llxHeader("", $transAreaType, $helpurl);

print_fiche_titre($transAreaType);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche portofolio
 */

/*
 * Nombre de clients par commerciaux
 */
$userstatic=new User($db);
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("CommercialThirdpartiesRepart").'</td></tr>';

$sql = "SELECT fk_user, COUNT(*) as total";
$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " GROUP BY sc.fk_user";
$result = $db->query($sql);
$statCommericaux="";
while ($objp = $db->fetch_object($result)) {
	$userstatic->fetch($objp->fk_user);
	print dolGetFirstLastname($obj_usr->firstname, $obj_usr->lastname);
	$statCommericaux.= "<tr >";
	$statCommericaux.= '<td>'.$userstatic->getNomUrl(1, '', 0, 0, 24, 1);
	$statCommericaux.= '</td><td align="right">'.$objp->total.'</td>';
	$statCommericaux.= "</tr>";
	$total=$total+$objp->total;
}
print $statCommericaux;
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';
print '<br>';


/*
 * nombre d'utilisateur par groupes d'utilisateurs
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("UserGroupRepart").'</td></tr>';

$sql = "SELECT ug.rowid, ug.nom, COUNT(*) as total";
$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as ug";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ug.rowid = ugu.fk_usergroup";
$sql.= " GROUP BY ug.rowid, ug.nom";
$result = $db->query($sql);

$statProducts="";
$total=0;

if ($result) {
	while ($objp = $db->fetch_object($result)) {
		$statProducts.= "<tr >";
		$statProducts.= '<td>';
		$statProducts.= $objp->nom;
		$statProducts.= '</td><td align="right">'.$objp->total.'</td>';
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
 * Elements par familles de catégories
 */
if ($conf->category->enabled) {
	foreach ($categoriesarray as $keycat => $valuecat) {
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("CategoriesByFamily", $valuecat[1]).'</td></tr>';

		$max=10;
		$sql = "SELECT c.rowid, c.label, count(*) as total";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_".$keycat." as ct ON c.rowid = ct.fk_categorie";
		$sql.= " WHERE c.type=".$valuecat[0];
		$sql.= " GROUP BY c.rowid, c.label";
		$sql.= $db->order("total", "DESC");
		$sql.= $db->plimit($max, 0);

//		print $sql;
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);

			$i = 0;
			if ($num > 0) {
				$var=True;
				while ($i < $num) {
					$objp = $db->fetch_object($result);

					$var=!$var;
					print "<tr ".$bc[$var].">";
					print '<td nowrap="nowrap">';
					print $objp->label;
					print "</td>\n";
					print "<td align=right>".$objp->total."</td>";
					print "</tr>\n";
					$i++;
				}
			}
		}
		print '</table><br>';
	}
}
print '</td></tr></table>';

llxFooter();
$db->close();