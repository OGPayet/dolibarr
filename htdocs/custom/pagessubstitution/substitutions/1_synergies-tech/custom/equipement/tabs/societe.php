<?php
/* Copyright (C) 2012-2017	Charlene Benke	 <charles.fr@benke.fr>
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
 *	\file	   htdocs/equipement/tabs/societe.php
 *	\brief	  List of all equipement of a company
 *	\ingroup	equipement
 */
// Change this following line to use the correct relative path (../, ../../, etc)
// to work if your module directory is into a subdir of root htdocs directory
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';
if (!$res && file_exists("../../../main.inc.php")) $res = @include '../../../main.inc.php';
if (!$res && file_exists("../../../../main.inc.php")) $res = @include '../../../../main.inc.php';
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include '../../../../../main.inc.php';
if (!$res && file_exists("../../../../../../main.inc.php")) $res = @include '../../../../../../main.inc.php';
if (!$res && file_exists("../../../../../../../main.inc.php")) $res = @include '../../../../../../../main.inc.php';
if (!$res && file_exists("../../../../../../../../main.inc.php")) $res = @include '../../../../../../../../main.inc.php';
if (!$res) die("Include of main fails");
if (!empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';


require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");

// Security check
if (!empty(GETPOST('id', 'int'))) {
	$socid = GETPOST('id', 'int');
} else {
	$socid = GETPOST('socid', 'int');
}

if ($user->societe_id) $socid = $user->societe_id;
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

if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "e.datec";


$limit = $conf->liste_limit;


$search_ref = GETPOST('search_ref', 'alpha');
$search_refProduct = GETPOST('search_refProduct', 'alpha');
$search_company_fourn = GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn = GETPOST('search_reffact_fourn', 'alpha');
$search_company_client = GETPOST('search_company_client', 'alpha');
$search_reffact_client = GETPOST('search_reffact_client', 'alpha');
$search_entrepot = GETPOST('search_entrepot', 'alpha');
$search_etatequipement = GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement == "-1") $search_etatequipement = "";


$form = new Form($db);
$title = $langs->trans("ThirdParty") . ' - ' . $langs->trans("Equipement");
$help_url = 'EN:Module_Equipement|FR:Module_Equipement|ES:Modulo_Equipement';

llxHeader('', $title, $help_url);

$object = new Societe($db);
$result = $object->fetch($socid);

$head = societe_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("ThirdParty"), 0, 'company');
$linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php">' . $langs->trans("BackToList") . '</a>';


if (DOL_VERSION >= "5.0.0") {
	/*
	 * Affichage onglets
	 */
	if (!empty($conf->notification->enabled)) $langs->load("mails");


	dol_banner_tab($object, 'socid', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom');

	//-------------------------------------------------------------------------------
	// Modification - Begin
	// Hook
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addBannerTab', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	// Modification - End
	//-------------------------------------------------------------------------------

	$cssclass = 'titlefield';
	//if ($action == 'editnote_public') $cssclass='titlefieldcreate';
	//if ($action == 'editnote_private') $cssclass='titlefieldcreate';

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	if (!empty($conf->global->SOCIETE_USEPREFIX)) {
		// Old not used prefix field
		print '<tr><td class="' . $cssclass . '">' . $langs->trans('Prefix') . '</td>';
		print '<td colspan="3">' . $object->prefix_comm . '</td></tr>';
	}

	if ($object->client) {
		print '<tr><td class="' . $cssclass . '">';
		print $langs->trans('CustomerCode') . '</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0)
			print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr><td class="' . $cssclass . '">';
		print $langs->trans('SupplierCode') . '</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0)
			print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
		print '</td></tr>';
	}

	print "</table>";
	print '</div>';
} else {
	print '<table class="border" width="100%">';
	print '<tr><td width="20%">' . $langs->trans('ThirdPartyName') . '</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'id', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
	print '</td></tr>';

	if (!empty($conf->global->SOCIETE_USEPREFIX)) {
		// Old not used prefix field
		print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $object->prefix_comm . '</td></tr>';
	}

	if ($object->client) {
		print '<tr><td>';
		print $langs->trans('CustomerCode') . '</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0)
			print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr><td>';
		print $langs->trans('SupplierCode') . '</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0)
			print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
		print '</td></tr>';
	}
	print '</table><br><br>';
}

$sql = "SELECT";
$sql .= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sql .= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql .= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.facnumber as refFacture,";
$sql .= " e.datee, e.dateo, ee.libelle as etatequiplibelle";

$sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as ent on e.fk_entrepot = ent.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as scli on e.fk_soc_client = scli.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture as f on e.fk_facture = f.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p on e.fk_product = p.rowid";
$sql .= " WHERE e.entity = " . $conf->entity;
$sql .= " AND (e.fk_soc_fourn = " . $socid . " OR e.fk_soc_client = " . $socid . ")";

if ($search_ref)			$sql .= " AND e.ref like '%" . $db->escape($search_ref) . "%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%" . $db->escape($search_refProduct) . "%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%" . $db->escape($search_company_fourn) . "%'";
if ($search_reffact_fourn)	$sql .= " AND ff.ref like '%" . $db->escape($search_reffact_fourn) . "%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%" . $db->escape($search_entrepot) . "%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%" . $db->escape($search_company_client) . "%'";
if ($search_reffact_client)	$sql .= " AND f.facnumber like '%" . $db->escape($search_reffact_client) . "%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =" . $search_etatequipement;

$sql .= " ORDER BY " . $sortfield . " " . $sortorder;
$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic = new Equipement($db);

	$urlparam = "&amp;id=" . $socid;
	if ($search_ref)				$urlparam .= "&amp;search_ref=" . $db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=" . $db->escape($search_refProduct);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=" . $db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&amp;search_reffact_fourn=" . $db->escape($search_reffact_fourn);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=" . $db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=" . $db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&amp;search_reffact_client=" . $db->escape($search_reffact_client);
	if ($search_etatequipement >= 0)	$urlparam .= "&amp;search_etatequipement=" . $search_etatequipement;

	print_barre_liste(
		$langs->trans("ListOfEquipements"),
		$page,
		"societe.php",
		$urlparam,
		$sortfield,
		$sortorder,
		'',
		$num
	);

	print '<form method="get" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
	print '<input type="hidden" class="flat" name="id" value="' . $socid . '" >';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print_liste_field_titre(
		$langs->trans("Ref"),
		$_SERVER["PHP_SELF"],
		"e.ref",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("RefProduit"),
		$_SERVER["PHP_SELF"],
		"p.ref",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Fournisseur"),
		$_SERVER["PHP_SELF"],
		"sfou.nom",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("RefFactFourn"),
		$_SERVER["PHP_SELF"],
		"ff.facnumber",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("RefFactClient"),
		$_SERVER["PHP_SELF"],
		"f.facnumber",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Dateo"),
		$_SERVER["PHP_SELF"],
		"e.dateo",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Datee"),
		$_SERVER["PHP_SELF"],
		"e.datee",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("EtatEquip"),
		$_SERVER["PHP_SELF"],
		"e.fk_equipementetat",
		"",
		$urlparam,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Status"),
		$_SERVER["PHP_SELF"],
		"e.fk_statut",
		"",
		$urlparam,
		'align="right"',
		$sortfield,
		$sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_refProduct" value="' . $search_refProduct . '" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="' . $search_company_fourn . '" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="' . $search_reffact_fourn . '" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_client" value="' . $search_reffact_client . '" size="10"></td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="' . $monthdatee . '">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardatee" value="' . $syear . '">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="' . $monthdateo . '">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="' . $syear . '">';
	print '</td>';

	// liste des état des équipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print '<select class="flat" name="viewstatut">';
	print '<option value="">&nbsp;</option>';
	print '<option ';
	if ($viewstatut == '0') print ' selected ';
	print ' value="0">' . $equipementstatic->LibStatut(0) . '</option>';
	print '<option ';
	if ($viewstatut == '1') print ' selected ';
	print ' value="1">' . $equipementstatic->LibStatut(1) . '</option>';
	print '<option ';
	if ($viewstatut == '2') print ' selected ';
	print ' value="2">' . $equipementstatic->LibStatut(2) . '</option>';
	print '</select>';
	print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png"';
	print ' value="' . dol_escape_htmltag($langs->trans("Search")) . '"';
	print ' title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print "</td></tr>\n";


	$var = True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);
		$var = !$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$equipementstatic->id = $objp->rowid;
		$equipementstatic->ref = $objp->ref;
		print $equipementstatic->getNomUrl(1);
		print "</td>";

		print '<td>';
		if ($objp->fk_product) {
			$productstatic = new Product($db);
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

		print "<td>";
		if ($objp->fk_facture_fourn) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($objp->fk_facture_fourn);
			print $factfournstatic->getNomUrl(1);
		}
		print '</td>';


		print "<td>";
		if ($objp->fk_facture) {
			$facturestatic = new Facture($db);
			$facturestatic->fetch($objp->fk_facture);
			print $facturestatic->getNomUrl(1);
		}
		print '</td>';
		print "<td nowrap align='center'>" . dol_print_date($db->jdate($objp->dateo), 'day') . "</td>\n";
		print "<td nowrap align='center'>" . dol_print_date($db->jdate($objp->datee), 'day') . "</td>\n";
		print '<td align="right">' . ($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle) : '') . '</td>';

		print '<td align="right">' . $equipementstatic->LibStatut($objp->fk_statut, 5) . '</td>';
		print "</tr>\n";

		$i++;
	}
	//print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
	//print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
	//print '</tr>';

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

llxFooter();
$db->close();
