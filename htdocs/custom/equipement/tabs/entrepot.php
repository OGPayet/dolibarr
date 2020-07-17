<?php
/* Copyright (C) 2012-2015	Charlie BENKE	<charlie@patas-monlkey.com>
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
 *	\file	   htdocs/equipement/tabs/entrepot.php
 *	\brief	  List of all equipement store in an entrepot
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
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("stocks");
$langs->load("equipement@equipement");

$entrepotid=GETPOST('id', 'int');

// Security check

if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
if ($search_refProduct=="-1") $search_refProduct="";
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_etatequipement=GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement=="-1") $search_etatequipement="";


$action = GETPOST('action', 'alpha');


if ($action=='adjuststock') {
	// quantit� dans l'entrepot
	$sql = "SELECT p.rowid as rowid, p.ref, p.label as produit, ps.reel";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_stock ps, ".MAIN_DB_PREFIX."product p";
	$sql.= " WHERE ps.fk_product = p.rowid";
	$sql.= " AND ps.reel <> 0";	// We do not show if stock is 0 (no product in this warehouse)
	$sql.= " AND ps.fk_entrepot = ".$fk_entrepot;
	$sql.= " ORDER BY p.ref";

	// quantit� d'�quipement


	// ajout d'un mouvement d'ajustement
}


/*
 *	View
 */

$form = new Form($db);
llxHeader();

$object = new Entrepot($db);
$result = $object->fetch($entrepotid);

$head = stock_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("Warehouse"), 0, 'stock');
$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php">'.$langs->trans("BackToList").'</a>';

if (DOL_VERSION < '5.0.0') {
	print '<table class="border" width="100%">';
	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'id', '', 1, 'rowid', 'libelle');
	print '</td>';

	print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$object->lieu.'</td></tr>';

} else {


	$morehtmlref='<div class="refidno">';
	$morehtmlref.=$langs->trans("LocationSummary").' : '.$object->lieu;
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'libelle', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent" >';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print '<td colspan="3">'.dol_htmlentitiesbr($object->description).'</td></tr>';

	// Address
	print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
	print $object->address;
	print '</td></tr>';

	// Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->zip.'</td>';
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->town.'</td></tr>';

	print "</table>";
	print "<br>";

}


dol_fiche_end();
print "<br>";

$head = equipement_entrepot_prepare_head($object);
$head = dol_fiche_head($head, 'card', $langs->trans("Equipement"), 0, 'equipement@equipement');


$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.numversion, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.quantity, scli.nom as CompanyClient, e.fk_facture, f.facnumber as refFacture,";
$sql.= " e.datee, e.dateo, ee.libelle as etatequiplibelle";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
$sql.= " AND e.fk_entrepot =".$db->escape($entrepotid);
if ($search_ref)			$sql.= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql.= " AND p.rowid =".$search_refProduct;
if ($search_company_fourn)	$sql.= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_reffact_fourn)	$sql.= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
if ($search_company_client)	$sql.= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_reffact_client)	$sql.= " AND f.facnumber like '%".$db->escape($search_reffact_client)."%'";
if ($search_etatequipement)	$sql.= " AND e.fk_etatequipement =".$search_etatequipement;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$entrepotid;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&amp;search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&amp;search_reffact_client=".$db->escape($search_reffact_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$entrepotid.'">';

	print '<table class="noborder nopadding" width="100%">';
	print '<tr><td width=120px valign=top align=left rowspan='.($num+1).'>';
	if ($search_refProduct && $num > 0) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id;
		print '&search_refProduct='.$search_refProduct.'&action=adjuststock">';
		print $langs->trans("AdjustStock").'</a>';
		print "<br><br>";
	}

	print select_produitEntrepot($search_refProduct, $entrepotid, "search_refProduct", 1, 1, 40);

	print '</td>';
	print '<td  valign=top align=left >';

	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Product"), $_SERVER["PHP_SELF"], "p.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("NumVersion"), $_SERVER["PHP_SELF"], "e.numversion",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Fournisseur"), $_SERVER["PHP_SELF"], "sfou.nom",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre($langs->trans("RefFactFourn"), $_SERVER["PHP_SELF"], "ff.facnumber", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("EquipementQty"), $_SERVER["PHP_SELF"], "scli.nom", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Dateo"), $_SERVER["PHP_SELF"], "e.dateo", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Datee"), $_SERVER["PHP_SELF"], "e.datee", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre(
					$langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_equipementetat",
					"", $urlparam,'', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Status"), $_SERVER["PHP_SELF"], "e.fk_statut",
					"", $urlparam, 'align="right"', $sortfield, $sortorder
	);
	print "</tr>\n";

	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	// on affiche les produits pr�sent dans l'entrepot et leur quantit� en stock annonc�
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="10"></td>';


	print '<td class="liste_titre" colspan="2" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardatee" value="'.$syear.'">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
	print '</td>';

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement,'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print '<select class="flat" name="viewstatut">';
	print '<option value="">&nbsp;</option>';
	print '<option ';
	if ($viewstatut=='0') print ' selected ';
	print ' value="0">'.$equipementstatic->LibStatut(0).'</option>';
	print '<option ';
	if ($viewstatut=='1') print ' selected ';
	print ' value="1">'.$equipementstatic->LibStatut(1).'</option>';
	print '<option ';
	if ($viewstatut=='2') print ' selected ';
	print ' value="2">'.$equipementstatic->LibStatut(2).'</option>';
	print '</select>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		print "</td>";
		print '<td>';
		if ($objp->fk_product) {
			$productstatic=new Product($db);
			$productstatic->fetch($objp->fk_product);
			print $productstatic->getNomUrl(1);
		}
		print '</td>';
		print '<td>'.$objp->numversion."</td>";

		print "<td>";
		if ($objp->fk_soc_fourn) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_fourn);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_facture_fourn) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($objp->fk_facture_fourn);
			print $factfournstatic ->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		print $objp->quantity;
		print '</td>';
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo),'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee),'day')."</td>\n";
		print '<td align="right">'.($objp->etatequiplibelle?$langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut,5).'</td>';
		print "</tr>\n";

		$i++;
	}
	//print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
	//print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
	//print '</tr>';

	print '</table>';
	print '</td></tr></table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);



llxFooter();
$db->close();

?>
<script type="text/javascript">
	<!--
	function MM_jumpMenu(targ,selObj,restore){ //v3.0
		eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
		if (restore) selObj.selectedIndex=0;
	}
	//-->
</script>