<?php
/* Copyright (C) 2012-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/expedition.php
 *	\brief	  List of all Events of equipements associated with an expeditions
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");
$langs->load("interventions");

$id=GETPOST('id', 'int');

$origin		= GETPOST('origin', 'alpha')?GETPOST('origin', 'alpha'):'expedition';   // Example: commande, propal
$origin_id 	= GETPOST('id', 'int')?GETPOST('id', 'int'):'';
if (empty($origin_id)) $origin_id  = GETPOST('origin_id', 'int');	// Id of order or propal
if (empty($origin_id)) $origin_id  = GETPOST('object_id', 'int');	// Id of order or propal
$id = $origin_id;
$ref=GETPOST('ref', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, $origin, $origin_id);

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
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');

$search_equipevttype=GETPOST('search_equipevttype', 'alpha');
if ($search_equipevttype=="-1") $search_equipevttype="";

$action=GETPOST("action");

if ($action == 'deleteline' && $user->rights->equipement->creer) {
	$objectline = new Equipementevt($db);
	if ($objectline->fetch(GETPOST('line_id', 'int')) <= 0) {
		dol_print_error($db);
		exit;
	}
	$action="";
	$result=$objectline->deleteline();
}

// pour valider la pr�paration de l'exp�dition, on sort de l'entrepot les �quipements
if ($action == 'validExpedition' && $user->rights->equipement->creer) {
	$sql= " UPDATE  ".MAIN_DB_PREFIX."equipement as e, ".MAIN_DB_PREFIX."equipementevt as ee";
	$sql.= " SET e.fk_entrepot = null";
	$sql.= " where e.rowid=ee.fk_equipement";
	$sql.= " and ee.fk_expedition=".$id;
	$result=$db->query($sql);

	$action="";
	//$result=$objectline->deleteline();
    header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . $id);
    exit;

}


/*
 *	View
 */

$form = new Form($db);
llxHeader();


$object = new Expedition($db);
$result = $object->fetch($id, $ref);
if (!$id) $id=$object->id;
$object->fetch_thirdparty();

$head = shipping_prepare_head($object);

dol_fiche_head($head, 'equipement', $langs->trans("Sending"), 0, 'sending');


print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

// Societe
print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";

print "</table><br>";

$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.unitweight, e.quantity,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_etatequipement, et.libelle as etatequiplibelle,";
$sql.= " ee.rowid as eerowid, ee.datee, ee.dateo, eet.libelle as equipevttypelibelle, ee.fk_equipementevt_type,";
$sql.= " ee.fk_fichinter, fi.ref as reffichinter, ee.fk_contrat, co.ref as refcontrat,";
$sql.= " ee.fk_expedition, exp.ref as refexpedition ";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as et on e.fk_etatequipement = et.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " , ".MAIN_DB_PREFIX."equipementevt as ee";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as exp on ee.fk_expedition = exp.rowid";

$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
$sql.= " and e.rowid=ee.fk_equipement";
$sql.= " and ee.fk_expedition=".$id;

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
if ($search_equipevttype)	$sql .= " AND ee.fk_equipementevt_type =".$search_equipevttype;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$id;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;
	if ($search_equipevttype>=0)	$urlparam .= "&amp;search_equipevttype=".$search_equipevttype;

	print_barre_liste(
					$langs->trans("ListOfEquipements"), $page, "expedition.php",
					$urlparam, $sortfield, $sortorder, '', $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("RefProduit"), $_SERVER["PHP_SELF"], "p.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Fournisseur"), $_SERVER["PHP_SELF"], "sfou.nom",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "ent.label",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre($langs->trans("Dateo"), $_SERVER["PHP_SELF"], "e.dateo", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Datee"), $_SERVER["PHP_SELF"], "e.datee", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("UnitWeight"), $_SERVER["PHP_SELF"], "e.unitweight", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Contrat"), $_SERVER["PHP_SELF"], "co.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Expedition"), $_SERVER["PHP_SELF"], "exp.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_equipementetat", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre(
					$langs->trans("TypeofEquipementEvent"), $_SERVER["PHP_SELF"], "ee.fk_equipementevt_type",
					"", $urlparam, ' colspan=2 ', $sortfield, $sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';


	print '<td class="liste_titre" colspan="1" align="right">';
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
	print '<td></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refcontrat" value="'.$search_refcontrat.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refexpedition" value="'.$search_refexpedition.'" size="10"></td>';

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print select_equipementevt_type($search_equipevttype, 'search_equipevttype', 1, 1);
	print '</td><td>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$totalWeight=0;
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

		print "<td>";
		if ($objp->fk_soc_fourn) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_fourn);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		// entrepot

		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic->fetch($objp->fk_entrepot);
			print $entrepotstatic->getNomUrl(1);
		}
		print '</td>';



		print '</td>';
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

		print "<td nowrap align='right'>".price($objp->unitweight)."</td>\n";
		$totalWeight+= ($objp->unitweight*$objp->quantity);

		print "<td>";
		if ($objp->fk_contrat>0) {
			$contrat = new Contrat($db);
			$contrat->fetch($objp->fk_contrat);
			print $contrat->getNomUrl(1);
			if ($objp->fk_soc_client != $contrat->socid) {
				$soc = new Societe($db);
				$soc->fetch($contrat->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_fichinter>0) {
			$fichinter = new Fichinter($db);
			$fichinter->fetch($objp->fk_fichinter);
			print $fichinter->getNomUrl(1);
			if ($objp->fk_soc_client != $fichinter->socid) {
				$soc = new Societe($db);
				$soc->fetch($fichinter->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.($objp->equipevttypelibelle ? $langs->trans($objp->equipevttypelibelle):'').'</td>';
		print '<td align="right">';
		// si l'�quipement a �t� envoy�, il n'est plus possible de le supprimer par cette �cran
		if ($objp->fk_entrepot>0) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deleteline&amp;line_id='.$objp->eerowid.'">';
			print img_delete().'</td>';
		}
		print "</tr>\n";

		$i++;
	}
	print '<tr class="liste_total"><td colspan="5" align=right class="liste_total"><b>'.$langs->trans("Total").'</b></td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.price($totalWeight).'</td><td colspan=5>&nbsp;</td>';
	print '</tr>';

	print '</table>';
	print "</form>\n";
	print '<div class="tabsAction">';

	print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validExpedition" ';
	print ' class="butAction" title="'.$langs->trans("ValidExpeditionInfo").'">';
	print $langs->trans("ValidExpedition").'</a>';

	print '</div>';
	$db->free($result);
} else
	dol_print_error($db);

llxFooter();
$db->close();