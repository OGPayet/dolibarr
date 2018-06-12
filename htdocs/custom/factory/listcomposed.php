<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Eric Seigne		  <erics@rycks.com>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2017	Charlie BENKE		<charlie@patas-monkey.com>
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
 *		\file	   htdocs/factory/listcomposed.php
 *	  \ingroup	factory
 *		\brief	  Page to list all factory product
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');

$factoryid=GETPOST('factoryid', 'int');
$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'factory', $fieldvalue,'factory','','', $fieldtype, $objcanvas);

$mesg = '';

$product = new Product($db);
$factory = new Factory($db);
$entrepot=new Entrepot($db);

$form=new Form($db);


$langs->load("bills");
$langs->load("products");
$langs->load("factory@factory");
$langs->load("stocks");

$search_product = GETPOST("search_product", 'alpha');

$sall=GETPOST("contactname");
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid', 'int');
$begin=GETPOST('begin');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.ref";
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

// Both test are required to be compatible with all browsers
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$search_ref="";
	$search_product="";
	$search_categ=-1;
}

llxHeader("", "", $langs->trans("FactoryListComposed"));
dol_htmloutput_mesg($mesg);
print_fiche_titre($langs->trans("FactoryList"));

/*
 * View
 */

$sql = "SELECT  p.*";
$sql.= " FROM ".MAIN_DB_PREFIX."product_factory pf";
$sql.= " , ".MAIN_DB_PREFIX."product as p";
$sql.= ' WHERE p.rowid=pf.fk_product_father';

if ($search_product != "")		// filtre sur le produit (saisie libre)
	$sql .= " AND p.ref like '%".$search_product."%'";

// Count total nb of records
$nbtotalofrecords = 0;
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);


//print $sql;
dol_syslog("factory/list.php sql=".$sql);
$result = $db->query($sql);
//print $sql;
if ($result) {
	$param ='&begin='.urlencode($begin).'&view='.urlencode($view);
	$param.='&userid='.urlencode($userid).'&contactname='.urlencode($sall);
	$param.='&type='.urlencode($type).'&view='.urlencode($view).'&search_lastname='.urlencode($search_lastname);
	$param.='&search_firstname='.urlencode($search_firstname).'&search_societe='.urlencode($search_societe);
	$param.='&search_email='.urlencode($search_email);
	if (!empty($search_categ)) $param.='&search_categ='.$search_categ;
	if ($search_status != -1) $param.='&amp;search_status='.$search_status;

	$num = $db->num_rows($result);
	$i = 0;


	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="view" value="'.$view.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print '<table class="liste" width="100%">';

	// Ligne des titres
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "f.ref", $begin, $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("descr"), $_SERVER["PHP_SELF"], "p.ref", $begin, $param, '', $sortfield, $sortorder);

	print_liste_field_titre($langs->trans("NbOfMade"), $_SERVER["PHP_SELF"], "f.date_start_planned", $begin, $param, 'align=center', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("NbComposant"), $_SERVER["PHP_SELF"], "f.qty_planned", $begin, $param, 'align=right', $sortfield, $sortorder);
	print '<td class="liste_titre" align="right">';
	print '<input type="image" name="button_removefilter" class="liste_titre" src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1);
	print '" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";

	// Ligne des champs de filtres
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_categ" size="5" value="'.$search_OF.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_product" size="5" value="'.$search_product.'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" value="button_search" class="liste_titre"';
	print ' src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print '</tr>';

	$var=True;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($result);
		$var=!$var;
		print "<tr ".$bc[$var].">";

		// Name
		print '<td valign="middle">';
		$factory->fetch($obj->rowid);
		print $factory->getNomUrl(1);
		print '</td>';

		print '<td valign="middle">';
		$product->fetch($obj->fk_product);
		print $product->getNomUrl(1);
		print $factory->PopupProduct($obj->fk_product, $i);
		print '</td>';

		// entrepot
		print '<td>';
		$entrepot->fetch($obj->fk_entrepot);
		print $entrepot->getNomUrl(1);
		print '</td>';

		// planned
		print '<td align="center">'.dol_print_date($db->jdate($obj->date_start_planned), "day").'</td>';
		print '<td align="right">'.$obj->qty_planned.'</td>';
		print '<td align="right">'.convertSecondToTime($obj->duration_planned, 'allhourmin').'</td>';
		print '<td align="center">'.dol_print_date($db->jdate($obj->date_end_planned), "day").'</td>';

		// made
		print '<td align="center">'.dol_print_date($db->jdate($obj->date_start_made), "day").'</td>';
		print '<td align="right">'.$obj->qty_made.'</td>';
		print '<td align="right">'.convertSecondToTime($obj->duration_made, 'allhourmin').'</td>';
		print '<td align="center">'.dol_print_date($db->jdate($obj->date_end_made), "day").'</td>';

		print '<td align="right" >'.$factory->getLibStatut(5).'</td>';
		print '<td></td>';
		print "</tr>\n";
		$i++;
	}

	print "</table>";

	print '</form>';

	if ($num > $limit)
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], '&amp;begin='.$begin.'&amp;view='.$view.'&amp;userid='.$userid, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');

	$db->free($result);
} else
	dol_print_error($db);

print '<br>';

llxFooter();
$db->close();