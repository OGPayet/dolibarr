<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003	  Eric Seigne		  <erics@rycks.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2017 Charlie BENKE		<charlie@patas-monkey.com>
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
 *		\file	   htdocs/factory/product/list.php
 *	  \ingroup	factory
 *		\brief	  Page to list all factory process
 */

$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

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
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype, $objcanvas);

$mesg = '';

$object = new Product($db);
$factory = new Factory($db);
$entrepot=new Entrepot($db);

$form=new Form($db);

$productid=0;
if ($id || $ref) {
	$result = $object->fetch($id, $ref);
	$productid=$object->id;
	$id=$object->id;
	$factory->id =$id;
}

$langs->load("bills");
$langs->load("products");
$langs->load("factory@factory");
$langs->load("stocks");

$search_status = GETPOST("fk_status", 'int');

$sall=GETPOST("contactname");
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid', 'int');
$begin=GETPOST('begin');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.date_end_planned";
if ($page < 0) {
	$page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;


if (GETPOST('button_removefilter')) {
	$search_ref="";
	$search_entrepot="";
	$search_societe="";
	$search_poste="";
	$search_phone="";
	$search_phoneper="";
	$search_phonepro="";
	$search_phonemob="";
	$search_fax="";
	$search_email="";
	$search_skype="";
	$search_priv="";
	$sall="";
	$seach_status=-1;
}


llxHeader("", "", $langs->trans("CardProduct".$product->type));

dol_htmloutput_mesg($mesg);

$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=('product');
dol_fiche_head($head, 'factory', $titre, 0, $picto);
$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

if ($id || $ref) {
	if ($result) {
		if (DOL_VERSION >= "5.0.0") {
			dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');
			$cssclass='titlefield';
			print '<div class="underbanner clearboth"></div>';
		} else {
			print '<table class="border" width="100%">';
			print "<tr>";

			// Reference
			print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object, 'ref', '', 1, 'ref');
			print '</td></tr>';

			// Libelle
			print '<tr><td>'.$langs->trans("Label").'</td>';
			print '<td colspan="3">'.($object->label ? $object->label:$object->libelle).'</td></tr>';

			// Status (to sell)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
			print $object->getLibStatut(2, 0);
			print '</td></tr>';

			// Status (to buy)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
			print $object->getLibStatut(2, 1);
			print '</td></tr>';
		}
		print '</table>';
		print '<table class="border" width="100%">';
		// MultiPrix
		if ($conf->global->PRODUIT_MULTIPRICES) {
			if ($socid) {
				$soc = new Societe($db);
				$soc->id = $socid;
				$soc->fetch($socid);

				print '<tr><td width="25%">'.$langs->trans("SellingPrice").'</td>';

				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
					print '<td>'.price($object->multiprices_ttc["$soc->price_level"]);
				else
					print '<td>'.price($object->multiprices["$soc->price_level"]);

				if ($object->multiprices_base_type["$soc->price_level"])
					print ' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				else
					print ' '.$langs->trans($object->price_base_type);

				print '</td></tr>';

				// Prix mini
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
					print price($object->multiprices_min_ttc["$soc->price_level"]);
				else
					print price($object->multiprices_min["$soc->price_level"]);
				print ' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				print '</td></tr>';

				// TVA
				print '<tr><td>'.$langs->trans("VATRate").'</td>';
				print '<td>'.vatrate($object->multiprices_tva_tx["$soc->price_level"], true).'</td></tr>';
			} else {
				for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
					// TVA
					if ($i == 1) {
						// We show only price for level 1
						print '<tr><td>'.$langs->trans("VATRate").'</td>';
						print '<td>'.vatrate($object->multiprices_tva_tx[1], true).'</td></tr>';
					}
					print '<tr><td width="25%">'.$langs->trans("SellingPrice").' '.$i.'</td>';

					if ($object->multiprices_base_type["$i"] == 'TTC')
						print '<td>'.price($object->multiprices_ttc["$i"]);
					else
						print '<td>'.price($object->multiprices["$i"]);

					if ($object->multiprices_base_type["$i"])
						print ' '.$langs->trans($object->multiprices_base_type["$i"]);
					else
						print ' '.$langs->trans($object->price_base_type);
					print '</td></tr>';

					// Prix mini
					print '<tr><td>'.$langs->trans("MinPrice").' '.$i.'</td><td>';
					if ($object->multiprices_base_type["$i"] == 'TTC')
						print price($object->multiprices_min_ttc["$i"]);
					else
						print price($object->multiprices_min["$i"]);
					print ' '.$langs->trans($object->multiprices_base_type["$i"]);
					print '</td></tr>';
				}
			}
		} else {
			// TVA
			print '<tr><td width="25%">'.$langs->trans("VATRate").'</td>';
			print '<td>'.vatrate($object->tva_tx.($object->tva_npr?'*':''), true).'</td></tr>';

			// Price
			print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
			if ($object->price_base_type == 'TTC') {
				print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
				$sale="";
			} else {
				print price($object->price).' '.$langs->trans($object->price_base_type);
				$sale=$object->price;
			}
			print '</td></tr>';

			// Price minimum
			print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
			if ($object->price_base_type == 'TTC')
				print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
			else
				print price($object->price_min).' '.$langs->trans($object->price_base_type);
			print '</td></tr>';
		}


		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$object->stock_reel.'</td></tr>';

		print '</table>';

		dol_fiche_end();

		// indique si on a déjà une composition de présente ou pas
		$compositionpresente=0;
	}
}

/*
 * View
 */

$head=factory_product_prepare_head($object, $user);
$titre=$langs->trans("Factory");
$picto="factory@factory";
if (GETPOST("fk_status")=="")
	$tabs='orderbuildhistory';
else
	$tabs='orderbuildlist';
dol_fiche_head($head, $tabs, $titre, 0, $picto);


$sql = "SELECT *";
$sql.= " FROM ".MAIN_DB_PREFIX."factory as f";
$sql.= ' WHERE f.fk_product='.$productid;
$sql.= " and f.entity = ".$conf->entity;

switch ($search_status) {
	case 1:
		$sql .= " AND f.fk_statut < 2";
		break;
	case 2:
		$sql .= " AND f.date_end_made is not null";
		break;
}
if ($fk_entrepot != "")		// filtre sur l'entrepot
	$sql .= " AND f.fk_entrepot =".$fk_entrepot;


// Count total nb of records
$nbtotalofrecords = 0;
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);


//print $sql;
dol_syslog("factory/product/list.php sql=".$sql);
$result = $db->query($sql);
//print $sql;
if ($result) {
	$param ='&begin='.urlencode($begin).'&view='.urlencode($view).'&userid='.urlencode($userid);
	$param.='&contactname='.urlencode($sall).'&search_email='.urlencode($search_email);
	$param.='&type='.urlencode($type).'&view='.urlencode($view).'&search_ref='.urlencode($search_ref);
	$param.='&search_entrepot='.urlencode($search_entrepot).'&search_societe='.urlencode($search_societe);

	if (!empty($search_categ))
		$param.='&search_categ='.$search_categ;
	if ($search_status != '')
		$param.='&search_status='.$search_status;

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
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",
					$begin, $param, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "f.fk_entrepot",
					$begin, $param, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
					$langs->trans("FactoryDateStartPlannedShort"), $_SERVER["PHP_SELF"], "f.date_start_planned",
					$begin, $param, 'align=center', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("QuantityPlannedShort"), $_SERVER["PHP_SELF"], "f.qty_planned",
					$begin, $param, 'align=right', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("DurationPlannedShort"), $_SERVER["PHP_SELF"], "f.duration_planned",
					$begin, $param, 'align=center', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("DateEndPlannedShort"), $_SERVER["PHP_SELF"], "f.date_end_planned",
					$begin, $param, 'align=center', $sortfield, $sortorder
	);

	print_liste_field_titre(
					$langs->trans("DateStartMadeShort"), $_SERVER["PHP_SELF"], "f.date_start_made",
					$begin, $param, 'align=right', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("QuantityMadeShort"), $_SERVER["PHP_SELF"], "f.qty_made",
					$begin, $param, 'align=right', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("DurationMadeShort"), $_SERVER["PHP_SELF"], "f.duration_made",
					$begin, $param, 'align=center', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("DateEndMadeShort"), $_SERVER["PHP_SELF"], "f.date_end_made",
					$begin, $param, 'align=center', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Status"), $_SERVER["PHP_SELF"], "f.fk_statut",
					$begin, $param, 'align=right', $sortfield, $sortorder
	);


	print "</tr>\n";

	// Ligne des champs de filtres
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_ref" size="5" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print select_entrepot_list(GETPOST("entrepotid"), "entrepotid", 1, 1);
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" colspan=7 align="center"></td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" value="button_search" class="liste_titre"';
	print ' src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" value="button_removefilter" class="liste_titre"';
	print ' src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'"';
	print ' value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
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

		print '<td align="right">'.$factory->getLibStatut(4).'</td>';
		$i++;
	}

	print "</table>";

	print '</form>';

	if ($num > $limit)
		print_barre_liste(
						'', $page, $_SERVER["PHP_SELF"], '&amp;begin='.$begin.'&amp;view='.$view.'&amp;userid='.$userid,
						$sortfield, $sortorder, '', $num, $nbtotalofrecords, ''
		);

	$db->free($result);
}
else
	dol_print_error($db);

print '<br>';

llxFooter();
$db->close();